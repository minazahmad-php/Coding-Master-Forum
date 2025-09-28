<?php
declare(strict_types=1);

/**
 * Modern Forum - Real-time Client
 * Handles WebSocket communication on the client side
 */

namespace Core;

class RealTimeClient
{
    private ?\WebSocket\Client $client = null;
    private string $serverUrl;
    private string $token;
    private array $eventHandlers = [];
    private bool $connected = false;
    private int $reconnectAttempts = 0;
    private int $maxReconnectAttempts = 5;
    private int $reconnectDelay = 1000; // milliseconds

    public function __construct(string $serverUrl = 'ws://localhost:8080', string $token = '')
    {
        $this->serverUrl = $serverUrl;
        $this->token = $token;
    }

    public function connect(): bool
    {
        try {
            $this->client = new \WebSocket\Client($this->serverUrl);
            $this->client->text(json_encode([
                'type' => 'auth',
                'token' => $this->token
            ]));
            
            $this->connected = true;
            $this->reconnectAttempts = 0;
            
            $this->emit('connected');
            return true;
        } catch (\Exception $e) {
            $this->logger->error('WebSocket connection failed', [
                'error' => $e->getMessage(),
                'server_url' => $this->serverUrl
            ]);
            
            $this->handleReconnect();
            return false;
        }
    }

    public function disconnect(): void
    {
        if ($this->client) {
            $this->client->close();
            $this->client = null;
        }
        
        $this->connected = false;
        $this->emit('disconnected');
    }

    public function sendMessage(string $type, array $data = []): bool
    {
        if (!$this->connected || !$this->client) {
            return false;
        }

        try {
            $message = array_merge(['type' => $type], $data);
            $this->client->text(json_encode($message));
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to send WebSocket message', [
                'error' => $e->getMessage(),
                'type' => $type,
                'data' => $data
            ]);
            return false;
        }
    }

    public function joinRoom(string $room): bool
    {
        return $this->sendMessage('join_room', ['room' => $room]);
    }

    public function leaveRoom(string $room): bool
    {
        return $this->sendMessage('leave_room', ['room' => $room]);
    }

    public function sendChatMessage(string $room, string $message): bool
    {
        return $this->sendMessage('message', [
            'room' => $room,
            'message' => $message
        ]);
    }

    public function startTyping(string $room): bool
    {
        return $this->sendMessage('typing', ['room' => $room]);
    }

    public function stopTyping(string $room): bool
    {
        return $this->sendMessage('stop_typing', ['room' => $room]);
    }

    public function updatePresence(string $status): bool
    {
        return $this->sendMessage('presence', ['status' => $status]);
    }

    public function on(string $event, callable $handler): void
    {
        if (!isset($this->eventHandlers[$event])) {
            $this->eventHandlers[$event] = [];
        }
        
        $this->eventHandlers[$event][] = $handler;
    }

    public function off(string $event, callable $handler = null): void
    {
        if ($handler === null) {
            unset($this->eventHandlers[$event]);
        } else {
            $key = array_search($handler, $this->eventHandlers[$event] ?? []);
            if ($key !== false) {
                unset($this->eventHandlers[$event][$key]);
            }
        }
    }

    private function emit(string $event, array $data = []): void
    {
        $handlers = $this->eventHandlers[$event] ?? [];
        
        foreach ($handlers as $handler) {
            try {
                $handler($data);
            } catch (\Exception $e) {
                $this->logger->error('Event handler error', [
                    'event' => $event,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    private function handleReconnect(): void
    {
        if ($this->reconnectAttempts >= $this->maxReconnectAttempts) {
            $this->emit('reconnect_failed');
            return;
        }

        $this->reconnectAttempts++;
        
        $this->emit('reconnecting', [
            'attempt' => $this->reconnectAttempts,
            'max_attempts' => $this->maxReconnectAttempts
        ]);

        // Schedule reconnect
        $this->scheduleReconnect();
    }

    private function scheduleReconnect(): void
    {
        $delay = $this->reconnectDelay * $this->reconnectAttempts;
        
        // Use JavaScript setTimeout for client-side reconnection
        echo "<script>
            setTimeout(function() {
                if (window.realTimeClient) {
                    window.realTimeClient.connect();
                }
            }, $delay);
        </script>";
    }

    public function listen(): void
    {
        if (!$this->client) {
            return;
        }

        try {
            while ($this->connected) {
                $message = $this->client->receive();
                $data = json_decode($message, true);
                
                if ($data) {
                    $this->handleMessage($data);
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('WebSocket listen error', [
                'error' => $e->getMessage()
            ]);
            
            $this->connected = false;
            $this->handleReconnect();
        }
    }

    private function handleMessage(array $data): void
    {
        switch ($data['type']) {
            case 'auth_success':
                $this->emit('authenticated', $data);
                break;
            case 'room_joined':
                $this->emit('room_joined', $data);
                break;
            case 'user_joined':
                $this->emit('user_joined', $data);
                break;
            case 'user_left':
                $this->emit('user_left', $data);
                break;
            case 'message':
                $this->emit('message', $data);
                break;
            case 'typing':
                $this->emit('typing', $data);
                break;
            case 'stop_typing':
                $this->emit('stop_typing', $data);
                break;
            case 'presence_update':
                $this->emit('presence_update', $data);
                break;
            case 'notification':
                $this->emit('notification', $data);
                break;
            case 'error':
                $this->emit('error', $data);
                break;
            default:
                $this->emit('unknown_message', $data);
        }
    }

    public function isConnected(): bool
    {
        return $this->connected;
    }

    public function getReconnectAttempts(): int
    {
        return $this->reconnectAttempts;
    }

    public function setMaxReconnectAttempts(int $max): void
    {
        $this->maxReconnectAttempts = $max;
    }

    public function setReconnectDelay(int $delay): void
    {
        $this->reconnectDelay = $delay;
    }
}