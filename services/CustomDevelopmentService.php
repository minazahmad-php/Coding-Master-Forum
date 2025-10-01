<?php
declare(strict_types=1);

namespace Services;

use Core\Database;
use Core\Logger;

class CustomDevelopmentService
{
    private Database $db;
    private Logger $logger;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->logger = new Logger();
    }

    /**
     * Create custom component
     */
    public function createCustomComponent(array $componentData): array
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO custom_components (name, type, code, author_id, created_at)
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $componentData['name'],
                $componentData['type'],
                $componentData['code'],
                $componentData['author_id'],
                date('Y-m-d H:i:s')
            ]);
            
            return ['success' => true, 'component_id' => $this->db->lastInsertId()];
        } catch (\Exception $e) {
            $this->logger->error("Failed to create custom component: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get custom components
     */
    public function getCustomComponents(int $userId, int $limit = 20): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    cc.*,
                    u.username as author_name
                FROM custom_components cc
                LEFT JOIN users u ON cc.author_id = u.id
                WHERE cc.author_id = ?
                ORDER BY cc.created_at DESC
                LIMIT ?
            ");
            
            $stmt->execute([$userId, $limit]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get custom components: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get custom development analytics
     */
    public function getCustomDevelopmentAnalytics(int $days = 30): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    type,
                    COUNT(*) as component_count,
                    COUNT(DISTINCT author_id) as unique_authors,
                    AVG(LENGTH(code)) as avg_code_length
                FROM custom_components 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY type
                ORDER BY component_count DESC
            ");
            
            $stmt->execute([$days]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get custom development analytics: " . $e->getMessage());
            return [];
        }
    }
}