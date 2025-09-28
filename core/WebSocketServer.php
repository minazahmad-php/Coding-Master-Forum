<?php
declare(strict_types=1);

/**
 * Modern Forum - WebSocket Server
 * Handles real-time communication for the forum
 */

namespace Core;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Loop;
use React\Socket\SocketServer;

class WebSocketServer implements MessageComponentInterface
{
    private array $clients = [];
    private array $userConnections = [];
    private array $roomConnections = [];
    private Database $db;
    private Logger $logger;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->logger = new Logger();
    }

    public function onOpen(ConnectionInterface $conn): void
    {
        $this->clients[$conn->resourceId] = $conn;
        $this->logger->info("New WebSocket connection: {$conn->resourceId}");
    }

    public function onMessage(ConnectionInterface $from, $msg): void
    {
        try {
            $data = json_decode($msg, true);
            
            if (!$data || !isset($data['type'])) {
                $this->sendError($from, 'Invalid message format');
                return;
            }

            switch ($data['type']) {
                case 'auth':
                    $this->handleAuth($from, $data);
                    break;
                case 'join_room':
                    $this->handleJoinRoom($from, $data);
                    break;
                case 'leave_room':
                    $this->handleLeaveRoom($from, $data);
                    break;
                case 'message':
                    $this->handleMessage($from, $data);
                    break;
                case 'typing':
                    $this->handleTyping($from, $data);
                    break;
                case 'stop_typing':
                    $this->handleStopTyping($from, $data);
                    break;
                case 'presence':
                    $this->handlePresence($from, $data);
                    break;
                case 'notification':
                    $this->handleNotification($from, $data);
                    break;
                default:
                    $this->sendError($from, 'Unknown message type');
            }
        } catch (\Exception $e) {
            $this->logger->error('WebSocket message error', [
                'error' => $e->getMessage(),
                'message' => $msg
            ]);
            $this->sendError($from, 'Message processing failed');
        }
    }

    public function onClose(ConnectionInterface $conn): void
    {
        $this->removeConnection($conn);
        $this->logger->info("WebSocket connection closed: {$conn->resourceId}");
    }

    public function onError(ConnectionInterface $conn, \Exception $e): void
    {
        $this->logger->error('WebSocket error', [
            'connection_id' => $conn->resourceId,
            'error' => $e->getMessage()
        ]);
        
        $this->removeConnection($conn);
        $conn->close();
    }

    private function handleAuth(ConnectionInterface $conn, array $data): void
    {
        if (!isset($data['token'])) {
            $this->sendError($conn, 'Authentication token required');
            return;
        }

        try {
            $user = $this->authenticateUser($data['token']);
            
            if ($user) {
                $this->userConnections[$conn->resourceId] = $user;
                $this->sendMessage($conn, [
                    'type' => 'auth_success',
                    'user' => [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'avatar' => $user['avatar']
                    ]
                ]);
                
                // Notify others about user coming online
                $this->broadcastPresenceUpdate($user, 'online');
                
                $this->logger->info("User authenticated via WebSocket", [
                    'user_id' => $user['id'],
                    'username' => $user['username']
                ]);
            } else {
                $this->sendError($conn, 'Invalid authentication token');
            }
        } catch (\Exception $e) {
            $this->logger->error('WebSocket authentication error', [
                'error' => $e->getMessage()
            ]);
            $this->sendError($conn, 'Authentication failed');
        }
    }

    private function handleJoinRoom(ConnectionInterface $conn, array $data): void
    {
        if (!isset($data['room'])) {
            $this->sendError($conn, 'Room name required');
            return;
        }

        $room = $data['room'];
        $user = $this->userConnections[$conn->resourceId] ?? null;

        if (!$user) {
            $this->sendError($conn, 'Authentication required');
            return;
        }

        // Add connection to room
        if (!isset($this->roomConnections[$room])) {
            $this->roomConnections[$room] = [];
        }
        
        $this->roomConnections[$room][$conn->resourceId] = $conn;

        // Notify room members about new user
        $this->broadcastToRoom($room, [
            'type' => 'user_joined',
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'avatar' => $user['avatar']
            ],
            'room' => $room
        ], $conn);

        $this->sendMessage($conn, [
            'type' => 'room_joined',
            'room' => $room
        ]);

        $this->logger->info("User joined room", [
            'user_id' => $user['id'],
            'room' => $room
        ]);
    }

    private function handleLeaveRoom(ConnectionInterface $conn, array $data): void
    {
        if (!isset($data['room'])) {
            return;
        }

        $room = $data['room'];
        $user = $this->userConnections[$conn->resourceId] ?? null;

        if (isset($this->roomConnections[$room][$conn->resourceId])) {
            unset($this->roomConnections[$room][$conn->resourceId]);
            
            if (empty($this->roomConnections[$room])) {
                unset($this->roomConnections[$room]);
            }
        }

        if ($user) {
            // Notify room members about user leaving
            $this->broadcastToRoom($room, [
                'type' => 'user_left',
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username']
                ],
                'room' => $room
            ], $conn);
        }
    }

    private function handleMessage(ConnectionInterface $conn, array $data): void
    {
        if (!isset($data['message']) || !isset($data['room'])) {
            $this->sendError($conn, 'Message and room required');
            return;
        }

        $user = $this->userConnections[$conn->resourceId] ?? null;
        
        if (!$user) {
            $this->sendError($conn, 'Authentication required');
            return;
        }

        $message = [
            'type' => 'message',
            'id' => uniqid(),
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'avatar' => $user['avatar']
            ],
            'message' => $data['message'],
            'room' => $data['room'],
            'timestamp' => time()
        ];

        // Broadcast message to room
        $this->broadcastToRoom($data['room'], $message, $conn);

        // Save message to database if it's a chat room
        if (strpos($data['room'], 'chat_') === 0) {
            $this->saveChatMessage($message);
        }

        $this->logger->info("Message sent", [
            'user_id' => $user['id'],
            'room' => $data['room'],
            'message_length' => strlen($data['message'])
        ]);
    }

    private function handleTyping(ConnectionInterface $conn, array $data): void
    {
        if (!isset($data['room'])) {
            return;
        }

        $user = $this->userConnections[$conn->resourceId] ?? null;
        
        if (!$user) {
            return;
        }

        $this->broadcastToRoom($data['room'], [
            'type' => 'typing',
            'user' => [
                'id' => $user['id'],
                'username' => $user['username']
            ],
            'room' => $data['room']
        ], $conn);
    }

    private function handleStopTyping(ConnectionInterface $conn, array $data): void
    {
        if (!isset($data['room'])) {
            return;
        }

        $user = $this->userConnections[$conn->resourceId] ?? null;
        
        if (!$user) {
            return;
        }

        $this->broadcastToRoom($data['room'], [
            'type' => 'stop_typing',
            'user' => [
                'id' => $user['id'],
                'username' => $user['username']
            ],
            'room' => $data['room']
        ], $conn);
    }

    private function handlePresence(ConnectionInterface $conn, array $data): void
    {
        $user = $this->userConnections[$conn->resourceId] ?? null;
        
        if (!$user) {
            return;
        }

        $status = $data['status'] ?? 'online';
        
        $this->broadcastPresenceUpdate($user, $status);
    }

    private function handleNotification(ConnectionInterface $conn, array $data): void
    {
        if (!isset($data['user_id'])) {
            $this->sendError($conn, 'User ID required');
            return;
        }

        $userId = $data['user_id'];
        
        // Find connection for this user
        foreach ($this->userConnections as $connId => $user) {
            if ($user['id'] == $userId) {
                $userConn = $this->clients[$connId] ?? null;
                if ($userConn) {
                    $this->sendMessage($userConn, [
                        'type' => 'notification',
                        'data' => $data['data'] ?? []
                    ]);
                }
                break;
            }
        }
    }

    private function authenticateUser(string $token): ?array
    {
        try {
            // Verify JWT token or session token
            $stmt = $this->db->prepare("
                SELECT u.id, u.username, u.email, u.avatar, u.first_name, u.last_name
                FROM users u
                JOIN sessions s ON u.id = s.user_id
                WHERE s.session_id = ? AND s.expires_at > CURRENT_TIMESTAMP
            ");
            
            $stmt->execute([$token]);
            $user = $stmt->fetch();
            
            return $user ?: null;
        } catch (\Exception $e) {
            $this->logger->error('WebSocket authentication error', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    private function saveChatMessage(array $message): void
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO chat_messages (room, user_id, message, created_at)
                VALUES (?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $message['room'],
                $message['user']['id'],
                $message['message'],
                date('Y-m-d H:i:s', $message['timestamp'])
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to save chat message', [
                'error' => $e->getMessage(),
                'message' => $message
            ]);
        }
    }

    private function broadcastToRoom(string $room, array $message, ?ConnectionInterface $exclude = null): void
    {
        if (!isset($this->roomConnections[$room])) {
            return;
        }

        foreach ($this->roomConnections[$room] as $connId => $conn) {
            if ($exclude && $conn === $exclude) {
                continue;
            }
            
            $this->sendMessage($conn, $message);
        }
    }

    private function broadcastPresenceUpdate(array $user, string $status): void
    {
        $message = [
            'type' => 'presence_update',
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'avatar' => $user['avatar']
            ],
            'status' => $status,
            'timestamp' => time()
        ];

        // Broadcast to all connected clients
        foreach ($this->clients as $conn) {
            $this->sendMessage($conn, $message);
        }
    }

    private function sendMessage(ConnectionInterface $conn, array $message): void
    {
        try {
            $conn->send(json_encode($message));
        } catch (\Exception $e) {
            $this->logger->error('Failed to send WebSocket message', [
                'error' => $e->getMessage(),
                'connection_id' => $conn->resourceId
            ]);
        }
    }

    private function sendError(ConnectionInterface $conn, string $error): void
    {
        $this->sendMessage($conn, [
            'type' => 'error',
            'message' => $error
        ]);
    }

    private function removeConnection(ConnectionInterface $conn): void
    {
        $connId = $conn->resourceId;
        
        // Remove from clients
        unset($this->clients[$connId]);
        
        // Remove from user connections
        $user = $this->userConnections[$connId] ?? null;
        unset($this->userConnections[$connId]);
        
        // Remove from all rooms
        foreach ($this->roomConnections as $room => $connections) {
            if (isset($connections[$connId])) {
                unset($this->roomConnections[$room][$connId]);
                
                if (empty($this->roomConnections[$room])) {
                    unset($this->roomConnections[$room]);
                } else if ($user) {
                    // Notify room members about user leaving
                    $this->broadcastToRoom($room, [
                        'type' => 'user_left',
                        'user' => [
                            'id' => $user['id'],
                            'username' => $user['username']
                        ],
                        'room' => $room
                    ]);
                }
            }
        }
        
        // Notify about user going offline
        if ($user) {
            $this->broadcastPresenceUpdate($user, 'offline');
        }
    }

    public static function start(int $port = 8080): void
    {
        $server = IoServer::factory(
            new HttpServer(
                new WsServer(
                    new self()
                )
            ),
            $port
        );

        echo "WebSocket server started on port $port\n";
        $server->run();
    }
}