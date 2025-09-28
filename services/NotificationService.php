<?php
declare(strict_types=1);

namespace Services;

class NotificationService {
    private Database $db;
    private array $notificationTypes;
    private array $deliveryChannels;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->notificationTypes = $this->getNotificationTypes();
        $this->deliveryChannels = ['email', 'push', 'browser', 'sms', 'in_app'];
    }
    
    private function getNotificationTypes(): array {
        return [
            'new_post' => [
                'title' => 'New Post',
                'template' => '{author} posted in {thread}',
                'channels' => ['email', 'push', 'browser', 'in_app']
            ],
            'new_reply' => [
                'title' => 'New Reply',
                'template' => '{author} replied to your post in {thread}',
                'channels' => ['email', 'push', 'browser', 'in_app']
            ],
            'mention' => [
                'title' => 'Mentioned',
                'template' => '{author} mentioned you in {thread}',
                'channels' => ['email', 'push', 'browser', 'in_app']
            ],
            'quote' => [
                'title' => 'Quoted',
                'template' => '{author} quoted your post in {thread}',
                'channels' => ['email', 'push', 'browser', 'in_app']
            ],
            'like' => [
                'title' => 'Post Liked',
                'template' => '{author} liked your post',
                'channels' => ['push', 'browser', 'in_app']
            ],
            'follow' => [
                'title' => 'New Follower',
                'template' => '{author} started following you',
                'channels' => ['email', 'push', 'browser', 'in_app']
            ],
            'message' => [
                'title' => 'New Message',
                'template' => '{author} sent you a message',
                'channels' => ['email', 'push', 'browser', 'sms', 'in_app']
            ],
            'group_message' => [
                'title' => 'Group Message',
                'template' => '{author} sent a message in {group}',
                'channels' => ['email', 'push', 'browser', 'in_app']
            ],
            'system' => [
                'title' => 'System Notification',
                'template' => '{message}',
                'channels' => ['email', 'push', 'browser', 'in_app']
            ],
            'security' => [
                'title' => 'Security Alert',
                'template' => '{message}',
                'channels' => ['email', 'push', 'browser', 'sms', 'in_app']
            ]
        ];
    }
    
    public function sendNotification(
        int $userId,
        string $type,
        array $data = [],
        array $channels = [],
        int $priority = 1
    ): bool {
        if (!isset($this->notificationTypes[$type])) {
            return false;
        }
        
        $notificationType = $this->notificationTypes[$type];
        $channels = empty($channels) ? $notificationType['channels'] : $channels;
        
        try {
            // Create notification record
            $notificationId = $this->createNotification($userId, $type, $data, $priority);
            
            // Send through each channel
            foreach ($channels as $channel) {
                if (in_array($channel, $this->deliveryChannels)) {
                    $this->sendThroughChannel($notificationId, $channel, $userId, $type, $data);
                }
            }
            
            return true;
        } catch (\Exception $e) {
            error_log("Notification sending failed: " . $e->getMessage());
            return false;
        }
    }
    
    private function createNotification(int $userId, string $type, array $data, int $priority): int {
        $notificationType = $this->notificationTypes[$type];
        $title = $notificationType['title'];
        $message = $this->formatMessage($notificationType['template'], $data);
        
        return $this->db->insert('notifications', [
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => json_encode($data),
            'priority' => $priority,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    private function formatMessage(string $template, array $data): string {
        $message = $template;
        
        foreach ($data as $key => $value) {
            $message = str_replace('{' . $key . '}', $value, $message);
        }
        
        return $message;
    }
    
    private function sendThroughChannel(int $notificationId, string $channel, int $userId, string $type, array $data): void {
        $userPreferences = $this->getUserNotificationPreferences($userId);
        
        // Check if user has enabled this channel for this notification type
        if (!$this->isChannelEnabled($userPreferences, $channel, $type)) {
            return;
        }
        
        switch ($channel) {
            case 'email':
                $this->sendEmailNotification($notificationId, $userId, $type, $data);
                break;
            case 'push':
                $this->sendPushNotification($notificationId, $userId, $type, $data);
                break;
            case 'browser':
                $this->sendBrowserNotification($notificationId, $userId, $type, $data);
                break;
            case 'sms':
                $this->sendSMSNotification($notificationId, $userId, $type, $data);
                break;
            case 'in_app':
                $this->sendInAppNotification($notificationId, $userId, $type, $data);
                break;
        }
    }
    
    private function sendEmailNotification(int $notificationId, int $userId, string $type, array $data): void {
        $user = $this->getUserInfo($userId);
        if (!$user || !$user['email']) {
            return;
        }
        
        $notificationType = $this->notificationTypes[$type];
        $subject = $notificationType['title'];
        $message = $this->formatMessage($notificationType['template'], $data);
        
        // Create email template
        $emailContent = $this->createEmailTemplate($subject, $message, $data);
        
        // Send email
        $emailService = new EmailService();
        $emailService->sendEmail(
            $user['email'],
            $subject,
            $emailContent,
            $user['username']
        );
        
        // Update notification status
        $this->updateNotificationStatus($notificationId, 'email_sent');
    }
    
    private function sendPushNotification(int $notificationId, int $userId, string $type, array $data): void {
        $user = $this->getUserInfo($userId);
        if (!$user || !$user['push_token']) {
            return;
        }
        
        $notificationType = $this->notificationTypes[$type];
        $title = $notificationType['title'];
        $message = $this->formatMessage($notificationType['template'], $data);
        
        // Send push notification
        $pushService = new PushNotificationService();
        $pushService->sendNotification($user['push_token'], $title, $message, $data);
        
        // Update notification status
        $this->updateNotificationStatus($notificationId, 'push_sent');
    }
    
    private function sendBrowserNotification(int $notificationId, int $userId, string $type, array $data): void {
        $notificationType = $this->notificationTypes[$type];
        $title = $notificationType['title'];
        $message = $this->formatMessage($notificationType['template'], $data);
        
        // Store for real-time delivery
        $this->storeRealTimeNotification($userId, $title, $message, $data);
        
        // Update notification status
        $this->updateNotificationStatus($notificationId, 'browser_sent');
    }
    
    private function sendSMSNotification(int $notificationId, int $userId, string $type, array $data): void {
        $user = $this->getUserInfo($userId);
        if (!$user || !$user['phone']) {
            return;
        }
        
        $notificationType = $this->notificationTypes[$type];
        $message = $this->formatMessage($notificationType['template'], $data);
        
        // Send SMS
        $smsService = new SMSService();
        $smsService->sendSMS($user['phone'], $message);
        
        // Update notification status
        $this->updateNotificationStatus($notificationId, 'sms_sent');
    }
    
    private function sendInAppNotification(int $notificationId, int $userId, string $type, array $data): void {
        // In-app notifications are already stored in the database
        // This method can be used for additional processing
        
        // Update notification status
        $this->updateNotificationStatus($notificationId, 'in_app_sent');
    }
    
    public function getUserNotifications(int $userId, int $page = 1, int $perPage = 20): array {
        $offset = ($page - 1) * $perPage;
        
        return $this->db->fetchAll(
            "SELECT * FROM notifications 
             WHERE user_id = :user_id 
             ORDER BY created_at DESC 
             LIMIT :offset, :per_page",
            [
                'user_id' => $userId,
                'offset' => $offset,
                'per_page' => $perPage
            ]
        );
    }
    
    public function getUnreadNotifications(int $userId): array {
        return $this->db->fetchAll(
            "SELECT * FROM notifications 
             WHERE user_id = :user_id AND read_at IS NULL 
             ORDER BY created_at DESC",
            ['user_id' => $userId]
        );
    }
    
    public function markAsRead(int $notificationId, int $userId): bool {
        try {
            $this->db->update(
                'notifications',
                ['read_at' => date('Y-m-d H:i:s')],
                'id = :id AND user_id = :user_id',
                ['id' => $notificationId, 'user_id' => $userId]
            );
            
            return true;
        } catch (\Exception $e) {
            error_log("Error marking notification as read: " . $e->getMessage());
            return false;
        }
    }
    
    public function markAllAsRead(int $userId): bool {
        try {
            $this->db->update(
                'notifications',
                ['read_at' => date('Y-m-d H:i:s')],
                'user_id = :user_id AND read_at IS NULL',
                ['user_id' => $userId]
            );
            
            return true;
        } catch (\Exception $e) {
            error_log("Error marking all notifications as read: " . $e->getMessage());
            return false;
        }
    }
    
    public function deleteNotification(int $notificationId, int $userId): bool {
        try {
            $this->db->delete(
                'notifications',
                'id = :id AND user_id = :user_id',
                ['id' => $notificationId, 'user_id' => $userId]
            );
            
            return true;
        } catch (\Exception $e) {
            error_log("Error deleting notification: " . $e->getMessage());
            return false;
        }
    }
    
    public function getUserNotificationPreferences(int $userId): array {
        $preferences = $this->db->fetch(
            "SELECT notification_preferences FROM users WHERE id = :user_id",
            ['user_id' => $userId]
        );
        
        if (!$preferences || !$preferences['notification_preferences']) {
            return $this->getDefaultNotificationPreferences();
        }
        
        return json_decode($preferences['notification_preferences'], true);
    }
    
    private function getDefaultNotificationPreferences(): array {
        return [
            'email' => [
                'new_post' => true,
                'new_reply' => true,
                'mention' => true,
                'quote' => true,
                'follow' => true,
                'message' => true,
                'group_message' => false,
                'system' => true,
                'security' => true
            ],
            'push' => [
                'new_post' => false,
                'new_reply' => true,
                'mention' => true,
                'quote' => true,
                'follow' => true,
                'message' => true,
                'group_message' => true,
                'system' => false,
                'security' => true
            ],
            'browser' => [
                'new_post' => false,
                'new_reply' => true,
                'mention' => true,
                'quote' => true,
                'follow' => true,
                'message' => true,
                'group_message' => true,
                'system' => false,
                'security' => true
            ],
            'sms' => [
                'new_post' => false,
                'new_reply' => false,
                'mention' => false,
                'quote' => false,
                'follow' => false,
                'message' => true,
                'group_message' => false,
                'system' => false,
                'security' => true
            ],
            'in_app' => [
                'new_post' => true,
                'new_reply' => true,
                'mention' => true,
                'quote' => true,
                'follow' => true,
                'message' => true,
                'group_message' => true,
                'system' => true,
                'security' => true
            ]
        ];
    }
    
    public function updateNotificationPreferences(int $userId, array $preferences): bool {
        try {
            $this->db->update(
                'users',
                ['notification_preferences' => json_encode($preferences)],
                'id = :user_id',
                ['user_id' => $userId]
            );
            
            return true;
        } catch (\Exception $e) {
            error_log("Error updating notification preferences: " . $e->getMessage());
            return false;
        }
    }
    
    private function isChannelEnabled(array $preferences, string $channel, string $type): bool {
        return isset($preferences[$channel][$type]) && $preferences[$channel][$type];
    }
    
    private function getUserInfo(int $userId): ?array {
        return $this->db->fetch(
            "SELECT id, username, email, phone, push_token FROM users WHERE id = :user_id",
            ['user_id' => $userId]
        );
    }
    
    private function updateNotificationStatus(int $notificationId, string $status): void {
        try {
            $this->db->update(
                'notifications',
                ['status' => $status],
                'id = :id',
                ['id' => $notificationId]
            );
        } catch (\Exception $e) {
            error_log("Error updating notification status: " . $e->getMessage());
        }
    }
    
    private function createEmailTemplate(string $subject, string $message, array $data): string {
        $template = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>{$subject}</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #667eea; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
                .button { display: inline-block; padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>" . SITE_NAME . "</h1>
                </div>
                <div class='content'>
                    <h2>{$subject}</h2>
                    <p>{$message}</p>
                    " . (isset($data['action_url']) ? "<a href='{$data['action_url']}' class='button'>View</a>" : "") . "
                </div>
                <div class='footer'>
                    <p>This is an automated notification from " . SITE_NAME . "</p>
                    <p><a href='" . SITE_URL . "/settings/notifications'>Manage notification preferences</a></p>
                </div>
            </div>
        </body>
        </html>";
        
        return $template;
    }
    
    private function storeRealTimeNotification(int $userId, string $title, string $message, array $data): void {
        // Store for WebSocket delivery
        $this->db->insert('realtime_notifications', [
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'data' => json_encode($data),
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    public function getNotificationStats(int $userId): array {
        return [
            'total' => $this->db->fetchColumn(
                "SELECT COUNT(*) FROM notifications WHERE user_id = :user_id",
                ['user_id' => $userId]
            ),
            'unread' => $this->db->fetchColumn(
                "SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND read_at IS NULL",
                ['user_id' => $userId]
            ),
            'today' => $this->db->fetchColumn(
                "SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND DATE(created_at) = CURDATE()",
                ['user_id' => $userId]
            ),
            'this_week' => $this->db->fetchColumn(
                "SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
                ['user_id' => $userId]
            )
        ];
    }
    
    public function cleanupOldNotifications(int $days = 30): int {
        return $this->db->delete(
            'notifications',
            'created_at < DATE_SUB(NOW(), INTERVAL :days DAY)',
            ['days' => $days]
        );
    }
}