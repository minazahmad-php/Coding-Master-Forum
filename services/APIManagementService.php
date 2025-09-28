<?php
declare(strict_types=1);

namespace Services;

use Core\Database;
use Core\Logger;

class APIManagementService
{
    private Database $db;
    private Logger $logger;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->logger = new Logger();
    }

    /**
     * Create API key
     */
    public function createAPIKey(int $userId, string $name, array $permissions = []): array
    {
        try {
            $apiKey = bin2hex(random_bytes(32));
            $hashedKey = hash('sha256', $apiKey);
            
            $stmt = $this->db->prepare("
                INSERT INTO api_keys (user_id, name, key_hash, permissions, created_at)
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $userId,
                $name,
                $hashedKey,
                json_encode($permissions),
                date('Y-m-d H:i:s')
            ]);
            
            return ['success' => true, 'api_key' => $apiKey];
        } catch (\Exception $e) {
            $this->logger->error("Failed to create API key: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Validate API key
     */
    public function validateAPIKey(string $apiKey): array
    {
        try {
            $hashedKey = hash('sha256', $apiKey);
            
            $stmt = $this->db->prepare("
                SELECT ak.*, u.username, u.email
                FROM api_keys ak
                LEFT JOIN users u ON ak.user_id = u.id
                WHERE ak.key_hash = ? AND ak.is_active = 1
            ");
            
            $stmt->execute([$hashedKey]);
            $result = $stmt->fetch();
            
            if ($result) {
                return ['valid' => true, 'user' => $result];
            }
            
            return ['valid' => false];
        } catch (\Exception $e) {
            $this->logger->error("Failed to validate API key: " . $e->getMessage());
            return ['valid' => false];
        }
    }

    /**
     * Track API usage
     */
    public function trackAPIUsage(string $apiKey, string $endpoint, int $responseTime, int $statusCode): bool
    {
        try {
            $hashedKey = hash('sha256', $apiKey);
            
            $stmt = $this->db->prepare("
                INSERT INTO api_usage_logs (key_hash, endpoint, response_time, status_code, created_at)
                VALUES (?, ?, ?, ?, ?)
            ");
            
            return $stmt->execute([
                $hashedKey,
                $endpoint,
                $responseTime,
                $statusCode,
                date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            $this->logger->error("Failed to track API usage: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get API usage analytics
     */
    public function getAPIUsageAnalytics(int $days = 30): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    endpoint,
                    COUNT(*) as total_requests,
                    AVG(response_time) as avg_response_time,
                    COUNT(CASE WHEN status_code >= 200 AND status_code < 300 THEN 1 END) as successful_requests,
                    COUNT(CASE WHEN status_code >= 400 THEN 1 END) as failed_requests
                FROM api_usage_logs 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY endpoint
                ORDER BY total_requests DESC
            ");
            
            $stmt->execute([$days]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get API usage analytics: " . $e->getMessage());
            return [];
        }
    }
}