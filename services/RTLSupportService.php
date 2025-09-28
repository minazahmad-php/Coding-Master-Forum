<?php
declare(strict_types=1);

namespace Services;

use Core\Database;
use Core\Logger;

class RTLSupportService
{
    private Database $db;
    private Logger $logger;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->logger = new Logger();
    }

    /**
     * Check if language is RTL
     */
    public function isRTLLanguage(string $languageCode): bool
    {
        try {
            $stmt = $this->db->prepare("
                SELECT is_rtl
                FROM languages
                WHERE code = ?
            ");
            
            $stmt->execute([$languageCode]);
            $result = $stmt->fetch();
            
            return $result ? (bool) $result['is_rtl'] : false;
        } catch (\Exception $e) {
            $this->logger->error("Failed to check RTL language: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get RTL languages
     */
    public function getRTLLanguages(): array
    {
        try {
            $stmt = $this->db->query("
                SELECT 
                    code,
                    name,
                    native_name
                FROM languages
                WHERE is_rtl = 1 AND is_active = 1
                ORDER BY name
            ");
            
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get RTL languages: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get RTL analytics
     */
    public function getRTLAnalytics(): array
    {
        try {
            $stmt = $this->db->query("
                SELECT 
                    l.is_rtl,
                    COUNT(u.id) as user_count,
                    AVG(u.posts_count) as avg_posts,
                    AVG(u.comments_count) as avg_comments
                FROM users u
                LEFT JOIN languages l ON u.language_code = l.code
                GROUP BY l.is_rtl
                ORDER BY user_count DESC
            ");
            
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get RTL analytics: " . $e->getMessage());
            return [];
        }
    }
}