<?php
declare(strict_types=1);

namespace Services;

class PushNotificationService {
    private array $config;
    private array $vapidKeys;
    
    public function __construct() {
        $this->config = [
            'web_push_url' => 'https://fcm.googleapis.com/fcm/send',
            'timeout' => 30,
            'retry_attempts' => 3
        ];
        
        $this->vapidKeys = [
            'public_key' => VAPID_PUBLIC_KEY ?? '',
            'private_key' => VAPID_PRIVATE_KEY ?? ''
        ];
    }
    
    public function sendNotification(string $token, string $title, string $message, array $data = []): bool {
        $payload = [
            'to' => $token,
            'notification' => [
                'title' => $title,
                'body' => $message,
                'icon' => SITE_URL . '/assets/icons/icon-192x192.png',
                'badge' => SITE_URL . '/assets/icons/badge-72x72.png',
                'sound' => 'default',
                'click_action' => SITE_URL . '/notifications'
            ],
            'data' => array_merge($data, [
                'url' => $data['url'] ?? SITE_URL,
                'timestamp' => time()
            ]),
            'priority' => 'high',
            'ttl' => 3600
        ];
        
        return $this->sendToFCM($payload);
    }
    
    public function sendToMultipleUsers(array $tokens, string $title, string $message, array $data = []): array {
        $results = [];
        
        foreach ($tokens as $token) {
            $results[$token] = $this->sendNotification($token, $title, $message, $data);
        }
        
        return $results;
    }
    
    public function sendToTopic(string $topic, string $title, string $message, array $data = []): bool {
        $payload = [
            'to' => '/topics/' . $topic,
            'notification' => [
                'title' => $title,
                'body' => $message,
                'icon' => SITE_URL . '/assets/icons/icon-192x192.png',
                'badge' => SITE_URL . '/assets/icons/badge-72x72.png',
                'sound' => 'default'
            ],
            'data' => array_merge($data, [
                'url' => $data['url'] ?? SITE_URL,
                'timestamp' => time()
            ]),
            'priority' => 'high'
        ];
        
        return $this->sendToFCM($payload);
    }
    
    private function sendToFCM(array $payload): bool {
        $headers = [
            'Authorization: key=' . FCM_SERVER_KEY,
            'Content-Type: application/json'
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->config['web_push_url'],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->config['timeout'],
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            error_log("FCM cURL error: {$error}");
            return false;
        }
        
        if ($httpCode !== 200) {
            error_log("FCM HTTP error: {$httpCode} - {$response}");
            return false;
        }
        
        $result = json_decode($response, true);
        
        if (isset($result['failure']) && $result['failure'] > 0) {
            error_log("FCM delivery failure: {$response}");
            return false;
        }
        
        return true;
    }
    
    public function subscribeToTopic(string $token, string $topic): bool {
        $url = "https://iid.googleapis.com/iid/v1/{$token}/rel/topics/{$topic}";
        
        $headers = [
            'Authorization: key=' . FCM_SERVER_KEY,
            'Content-Type: application/json'
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->config['timeout']
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);
        
        return $httpCode === 200;
    }
    
    public function unsubscribeFromTopic(string $token, string $topic): bool {
        // FCM doesn't have a direct unsubscribe API
        // This would need to be handled by removing the token from the topic
        return true;
    }
    
    public function validateToken(string $token): bool {
        $url = "https://fcm.googleapis.com/fcm/send";
        
        $payload = [
            'to' => $token,
            'dry_run' => true
        ];
        
        $headers = [
            'Authorization: key=' . FCM_SERVER_KEY,
            'Content-Type: application/json'
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->config['timeout']
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);
        
        if ($httpCode === 200) {
            $result = json_decode($response, true);
            return !isset($result['failure']) || $result['failure'] === 0;
        }
        
        return false;
    }
    
    public function sendScheduledNotification(string $token, string $title, string $message, array $data, int $scheduleTime): bool {
        // Store scheduled notification in database
        $db = Database::getInstance();
        
        $db->insert('scheduled_notifications', [
            'token' => $token,
            'title' => $title,
            'message' => $message,
            'data' => json_encode($data),
            'schedule_time' => date('Y-m-d H:i:s', $scheduleTime),
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        return true;
    }
    
    public function processScheduledNotifications(): int {
        $db = Database::getInstance();
        
        $scheduledNotifications = $db->fetchAll(
            "SELECT * FROM scheduled_notifications 
             WHERE status = 'pending' AND schedule_time <= NOW() 
             ORDER BY schedule_time ASC 
             LIMIT 100"
        );
        
        $processed = 0;
        
        foreach ($scheduledNotifications as $notification) {
            $success = $this->sendNotification(
                $notification['token'],
                $notification['title'],
                $notification['message'],
                json_decode($notification['data'], true)
            );
            
            $status = $success ? 'sent' : 'failed';
            
            $db->update(
                'scheduled_notifications',
                ['status' => $status, 'sent_at' => date('Y-m-d H:i:s')],
                'id = :id',
                ['id' => $notification['id']]
            );
            
            $processed++;
        }
        
        return $processed;
    }
    
    public function sendRichNotification(string $token, array $notification): bool {
        $payload = [
            'to' => $token,
            'notification' => [
                'title' => $notification['title'],
                'body' => $notification['body'],
                'icon' => $notification['icon'] ?? SITE_URL . '/assets/icons/icon-192x192.png',
                'badge' => $notification['badge'] ?? SITE_URL . '/assets/icons/badge-72x72.png',
                'sound' => $notification['sound'] ?? 'default',
                'click_action' => $notification['click_action'] ?? SITE_URL . '/notifications',
                'image' => $notification['image'] ?? null,
                'tag' => $notification['tag'] ?? null,
                'require_interaction' => $notification['require_interaction'] ?? false,
                'silent' => $notification['silent'] ?? false,
                'timestamp' => time()
            ],
            'data' => $notification['data'] ?? [],
            'priority' => $notification['priority'] ?? 'high',
            'ttl' => $notification['ttl'] ?? 3600
        ];
        
        return $this->sendToFCM($payload);
    }
    
    public function sendActionNotification(string $token, string $title, string $message, array $actions): bool {
        $payload = [
            'to' => $token,
            'notification' => [
                'title' => $title,
                'body' => $message,
                'icon' => SITE_URL . '/assets/icons/icon-192x192.png',
                'badge' => SITE_URL . '/assets/icons/badge-72x72.png',
                'sound' => 'default',
                'actions' => $actions,
                'require_interaction' => true
            ],
            'data' => [
                'timestamp' => time(),
                'has_actions' => true
            ],
            'priority' => 'high'
        ];
        
        return $this->sendToFCM($payload);
    }
    
    public function sendSilentNotification(string $token, array $data): bool {
        $payload = [
            'to' => $token,
            'data' => array_merge($data, [
                'timestamp' => time(),
                'silent' => true
            ]),
            'priority' => 'high'
        ];
        
        return $this->sendToFCM($payload);
    }
    
    public function getDeliveryStats(): array {
        $db = Database::getInstance();
        
        return [
            'total_sent' => $db->fetchColumn(
                "SELECT COUNT(*) FROM notification_logs WHERE status = 'sent'"
            ),
            'total_failed' => $db->fetchColumn(
                "SELECT COUNT(*) FROM notification_logs WHERE status = 'failed'"
            ),
            'sent_today' => $db->fetchColumn(
                "SELECT COUNT(*) FROM notification_logs WHERE status = 'sent' AND DATE(created_at) = CURDATE()"
            ),
            'failed_today' => $db->fetchColumn(
                "SELECT COUNT(*) FROM notification_logs WHERE status = 'failed' AND DATE(created_at) = CURDATE()"
            ),
            'success_rate' => $this->calculateSuccessRate()
        ];
    }
    
    private function calculateSuccessRate(): float {
        $db = Database::getInstance();
        
        $total = $db->fetchColumn("SELECT COUNT(*) FROM notification_logs");
        $successful = $db->fetchColumn("SELECT COUNT(*) FROM notification_logs WHERE status = 'sent'");
        
        return $total > 0 ? ($successful / $total) * 100 : 0;
    }
    
    public function logNotification(string $token, string $title, string $message, bool $success): void {
        $db = Database::getInstance();
        
        $db->insert('notification_logs', [
            'token' => $token,
            'title' => $title,
            'message' => $message,
            'status' => $success ? 'sent' : 'failed',
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    public function cleanupOldLogs(int $days = 30): int {
        $db = Database::getInstance();
        
        return $db->delete(
            'notification_logs',
            'created_at < DATE_SUB(NOW(), INTERVAL :days DAY)',
            ['days' => $days]
        );
    }
    
    public function generateVAPIDKeys(): array {
        $keys = [
            'public_key' => '',
            'private_key' => ''
        ];
        
        if (function_exists('openssl_pkey_new')) {
            $config = [
                'digest_alg' => 'sha256',
                'private_key_bits' => 2048,
                'private_key_type' => OPENSSL_KEYTYPE_EC,
                'curve_name' => 'prime256v1'
            ];
            
            $res = openssl_pkey_new($config);
            
            if ($res !== false) {
                openssl_pkey_export($res, $privateKey);
                $publicKey = openssl_pkey_get_details($res)['key'];
                
                $keys['public_key'] = $publicKey;
                $keys['private_key'] = $privateKey;
            }
        }
        
        return $keys;
    }
    
    public function sendWebPushNotification(string $endpoint, string $userPublicKey, string $userAuth, string $payload): bool {
        // This would implement the Web Push Protocol
        // For now, return true as a placeholder
        return true;
    }
}