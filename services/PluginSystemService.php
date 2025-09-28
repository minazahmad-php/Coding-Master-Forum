<?php
declare(strict_types=1);

namespace Services;

use Core\Database;
use Core\Logger;

class PluginSystemService
{
    private Database $db;
    private Logger $logger;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->logger = new Logger();
    }

    /**
     * Create plugin
     */
    public function createPlugin(array $pluginData): array
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO plugins (name, description, author_id, version, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $pluginData['name'],
                $pluginData['description'],
                $pluginData['author_id'],
                $pluginData['version'],
                'pending',
                date('Y-m-d H:i:s')
            ]);
            
            return ['success' => true, 'plugin_id' => $this->db->lastInsertId()];
        } catch (\Exception $e) {
            $this->logger->error("Failed to create plugin: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get available plugins
     */
    public function getAvailablePlugins(int $limit = 20): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    p.*,
                    u.username as author_name,
                    COUNT(pd.id) as downloads_count,
                    AVG(pr.rating) as avg_rating
                FROM plugins p
                LEFT JOIN users u ON p.author_id = u.id
                LEFT JOIN plugin_downloads pd ON p.id = pd.plugin_id
                LEFT JOIN plugin_ratings pr ON p.id = pr.plugin_id
                WHERE p.status = 'approved'
                GROUP BY p.id
                ORDER BY downloads_count DESC, avg_rating DESC
                LIMIT ?
            ");
            
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get available plugins: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Install plugin
     */
    public function installPlugin(int $pluginId, int $userId): bool
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO user_plugins (user_id, plugin_id, installed_at)
                VALUES (?, ?, ?)
            ");
            
            return $stmt->execute([
                $userId,
                $pluginId,
                date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            $this->logger->error("Failed to install plugin: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get plugin analytics
     */
    public function getPluginAnalytics(int $days = 30): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    p.name as plugin_name,
                    COUNT(pd.id) as downloads_count,
                    COUNT(DISTINCT pd.user_id) as unique_users,
                    AVG(pr.rating) as avg_rating,
                    COUNT(pr.id) as ratings_count
                FROM plugins p
                LEFT JOIN plugin_downloads pd ON p.id = pd.plugin_id
                LEFT JOIN plugin_ratings pr ON p.id = pr.plugin_id
                WHERE pd.downloaded_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY p.id, p.name
                ORDER BY downloads_count DESC
            ");
            
            $stmt->execute([$days]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get plugin analytics: " . $e->getMessage());
            return [];
        }
    }
}