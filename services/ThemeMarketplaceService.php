<?php
declare(strict_types=1);

namespace Services;

use Core\Database;
use Core\Logger;

class ThemeMarketplaceService
{
    private Database $db;
    private Logger $logger;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->logger = new Logger();
    }

    /**
     * Create theme
     */
    public function createTheme(array $themeData): array
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO themes (name, description, author_id, version, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $themeData['name'],
                $themeData['description'],
                $themeData['author_id'],
                $themeData['version'],
                'pending',
                date('Y-m-d H:i:s')
            ]);
            
            return ['success' => true, 'theme_id' => $this->db->lastInsertId()];
        } catch (\Exception $e) {
            $this->logger->error("Failed to create theme: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get available themes
     */
    public function getAvailableThemes(int $limit = 20): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    t.*,
                    u.username as author_name,
                    COUNT(td.id) as downloads_count,
                    AVG(tr.rating) as avg_rating
                FROM themes t
                LEFT JOIN users u ON t.author_id = u.id
                LEFT JOIN theme_downloads td ON t.id = td.theme_id
                LEFT JOIN theme_ratings tr ON t.id = tr.theme_id
                WHERE t.status = 'approved'
                GROUP BY t.id
                ORDER BY downloads_count DESC, avg_rating DESC
                LIMIT ?
            ");
            
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get available themes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Install theme
     */
    public function installTheme(int $themeId, int $userId): bool
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO user_themes (user_id, theme_id, installed_at)
                VALUES (?, ?, ?)
            ");
            
            return $stmt->execute([
                $userId,
                $themeId,
                date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            $this->logger->error("Failed to install theme: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get theme analytics
     */
    public function getThemeAnalytics(int $days = 30): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    t.name as theme_name,
                    COUNT(td.id) as downloads_count,
                    COUNT(DISTINCT td.user_id) as unique_users,
                    AVG(tr.rating) as avg_rating,
                    COUNT(tr.id) as ratings_count
                FROM themes t
                LEFT JOIN theme_downloads td ON t.id = td.theme_id
                LEFT JOIN theme_ratings tr ON t.id = tr.theme_id
                WHERE td.downloaded_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY t.id, t.name
                ORDER BY downloads_count DESC
            ");
            
            $stmt->execute([$days]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get theme analytics: " . $e->getMessage());
            return [];
        }
    }
}