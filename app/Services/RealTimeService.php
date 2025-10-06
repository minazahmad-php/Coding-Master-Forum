<?php

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use App\Core\Security;

/**
 * Real-time Communication Service
 */
class RealTimeService
{
    private $db;
    private $logger;
    private $redis;
    private $socketServer;

    public function __construct()
    {
        global $app;
        $this->db = $app->get('database');
        $this->logger = $app->get('logger');
        $this->redis = $app->get('redis');
        $this->socketServer = config('realtime.socket_server');
    }

    /**
     * Send real-time notification
     */
    public function sendNotification($userId, $type, $data)
    {
        try {
            $notification = [
                'id' => Security::generateToken(16),
                'user_id' => $userId,
                'type' => $type,
                'data' => $data,
                'created_at' => date('Y-m-d H:i:s'),
                'read' => false
            ];

            // Store in database
            $this->db->query(
                "INSERT INTO realtime_notifications (id, user_id, type, data, created_at) VALUES (?, ?, ?, ?, ?)",
                [$notification['id'], $userId, $type, json_encode($data), $notification['created_at']]
            );

            // Send via WebSocket
            $this->sendWebSocketMessage($userId, 'notification', $notification);

            // Send via Redis for scaling
            $this->redis->publish('notifications', json_encode([
                'user_id' => $userId,
                'type' => $type,
                'data' => $data
            ]));

            $this->logger->info("Real-time notification sent to user {$userId}: {$type}");
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Real-time notification failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send live chat message
     */
    public function sendChatMessage($fromUserId, $toUserId, $message, $type = 'text')
    {
        try {
            $messageId = Security::generateToken(16);
            
            $chatMessage = [
                'id' => $messageId,
                'from_user_id' => $fromUserId,
                'to_user_id' => $toUserId,
                'message' => $message,
                'type' => $type,
                'created_at' => date('Y-m-d H:i:s'),
                'read' => false
            ];

            // Store in database
            $this->db->query(
                "INSERT INTO chat_messages (id, from_user_id, to_user_id, message, type, created_at) VALUES (?, ?, ?, ?, ?, ?)",
                [$messageId, $fromUserId, $toUserId, $message, $type, $chatMessage['created_at']]
            );

            // Send via WebSocket
            $this->sendWebSocketMessage($toUserId, 'chat_message', $chatMessage);

            // Send delivery confirmation to sender
            $this->sendWebSocketMessage($fromUserId, 'message_sent', [
                'message_id' => $messageId,
                'status' => 'delivered'
            ]);

            $this->logger->info("Chat message sent from {$fromUserId} to {$toUserId}");
            return $messageId;
        } catch (\Exception $e) {
            $this->logger->error('Chat message sending failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Start video call
     */
    public function startVideoCall($fromUserId, $toUserId)
    {
        try {
            $callId = Security::generateToken(16);
            
            $callData = [
                'id' => $callId,
                'from_user_id' => $fromUserId,
                'to_user_id' => $toUserId,
                'status' => 'ringing',
                'created_at' => date('Y-m-d H:i:s')
            ];

            // Store call in database
            $this->db->query(
                "INSERT INTO video_calls (id, from_user_id, to_user_id, status, created_at) VALUES (?, ?, ?, ?, ?)",
                [$callId, $fromUserId, $toUserId, 'ringing', $callData['created_at']]
            );

            // Send call invitation via WebSocket
            $this->sendWebSocketMessage($toUserId, 'video_call_invitation', $callData);

            $this->logger->info("Video call started from {$fromUserId} to {$toUserId}: {$callId}");
            return $callId;
        } catch (\Exception $e) {
            $this->logger->error('Video call start failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Answer video call
     */
    public function answerVideoCall($callId, $userId, $accepted = true)
    {
        try {
            $status = $accepted ? 'accepted' : 'rejected';
            
            $this->db->query(
                "UPDATE video_calls SET status = ?, answered_at = NOW() WHERE id = ? AND to_user_id = ?",
                [$status, $callId, $userId]
            );

            // Get call data
            $call = $this->db->fetch(
                "SELECT * FROM video_calls WHERE id = ?",
                [$callId]
            );

            if ($call) {
                // Notify caller about answer
                $this->sendWebSocketMessage($call['from_user_id'], 'video_call_answered', [
                    'call_id' => $callId,
                    'accepted' => $accepted
                ]);

                if ($accepted) {
                    // Send WebRTC offer/answer data
                    $this->sendWebSocketMessage($userId, 'video_call_accepted', [
                        'call_id' => $callId,
                        'from_user_id' => $call['from_user_id']
                    ]);
                }
            }

            $this->logger->info("Video call {$callId} {$status} by {$userId}");
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Video call answer failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * End video call
     */
    public function endVideoCall($callId, $userId)
    {
        try {
            $this->db->query(
                "UPDATE video_calls SET status = 'ended', ended_at = NOW() WHERE id = ? AND (from_user_id = ? OR to_user_id = ?)",
                [$callId, $userId, $userId]
            );

            // Get call data
            $call = $this->db->fetch(
                "SELECT * FROM video_calls WHERE id = ?",
                [$callId]
            );

            if ($call) {
                // Notify both participants
                $this->sendWebSocketMessage($call['from_user_id'], 'video_call_ended', [
                    'call_id' => $callId
                ]);
                $this->sendWebSocketMessage($call['to_user_id'], 'video_call_ended', [
                    'call_id' => $callId
                ]);
            }

            $this->logger->info("Video call {$callId} ended by {$userId}");
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Video call end failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update user online status
     */
    public function updateUserStatus($userId, $status = 'online')
    {
        try {
            $this->db->query(
                "INSERT INTO user_online_status (user_id, status, last_seen, updated_at) 
                 VALUES (?, ?, NOW(), NOW()) 
                 ON DUPLICATE KEY UPDATE 
                 status = VALUES(status), 
                 last_seen = NOW(), 
                 updated_at = NOW()",
                [$userId, $status]
            );

            // Broadcast status update
            $this->broadcastUserStatus($userId, $status);

            $this->logger->info("User {$userId} status updated to {$status}");
            return true;
        } catch (\Exception $e) {
            $this->logger->error('User status update failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get online users
     */
    public function getOnlineUsers($limit = 50)
    {
        return $this->db->fetchAll(
            "SELECT u.id, u.username, u.display_name, u.avatar, s.status, s.last_seen 
             FROM users u 
             LEFT JOIN user_online_status s ON u.id = s.user_id 
             WHERE s.status = 'online' AND s.last_seen > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
             ORDER BY s.last_seen DESC 
             LIMIT ?",
            [$limit]
        );
    }

    /**
     * Send typing indicator
     */
    public function sendTypingIndicator($fromUserId, $toUserId, $isTyping = true)
    {
        try {
            $this->sendWebSocketMessage($toUserId, 'typing_indicator', [
                'from_user_id' => $fromUserId,
                'is_typing' => $isTyping
            ]);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Typing indicator failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send live post update
     */
    public function sendLivePostUpdate($threadId, $postData)
    {
        try {
            // Get thread subscribers
            $subscribers = $this->db->fetchAll(
                "SELECT user_id FROM thread_subscriptions WHERE thread_id = ?",
                [$threadId]
            );

            foreach ($subscribers as $subscriber) {
                $this->sendWebSocketMessage($subscriber['user_id'], 'live_post_update', [
                    'thread_id' => $threadId,
                    'post' => $postData
                ]);
            }

            $this->logger->info("Live post update sent for thread {$threadId}");
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Live post update failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send WebSocket message
     */
    private function sendWebSocketMessage($userId, $type, $data)
    {
        try {
            $message = [
                'type' => $type,
                'data' => $data,
                'timestamp' => time()
            ];

            // Send via Redis for WebSocket server
            $this->redis->publish('websocket', json_encode([
                'user_id' => $userId,
                'message' => $message
            ]));

            return true;
        } catch (\Exception $e) {
            $this->logger->error('WebSocket message failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Broadcast user status
     */
    private function broadcastUserStatus($userId, $status)
    {
        try {
            $user = $this->db->fetch(
                "SELECT id, username, display_name, avatar FROM users WHERE id = ?",
                [$userId]
            );

            if ($user) {
                $this->redis->publish('user_status', json_encode([
                    'user_id' => $userId,
                    'status' => $status,
                    'user' => $user
                ]));
            }

            return true;
        } catch (\Exception $e) {
            $this->logger->error('User status broadcast failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user notifications
     */
    public function getUserNotifications($userId, $limit = 20)
    {
        return $this->db->fetchAll(
            "SELECT * FROM realtime_notifications 
             WHERE user_id = ? 
             ORDER BY created_at DESC 
             LIMIT ?",
            [$userId, $limit]
        );
    }

    /**
     * Mark notification as read
     */
    public function markNotificationAsRead($userId, $notificationId)
    {
        try {
            $this->db->query(
                "UPDATE realtime_notifications SET read = 1, read_at = NOW() 
                 WHERE id = ? AND user_id = ?",
                [$notificationId, $userId]
            );

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Notification mark as read failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get unread notification count
     */
    public function getUnreadNotificationCount($userId)
    {
        $result = $this->db->fetch(
            "SELECT COUNT(*) as count FROM realtime_notifications 
             WHERE user_id = ? AND read = 0",
            [$userId]
        );

        return $result['count'] ?? 0;
    }
}