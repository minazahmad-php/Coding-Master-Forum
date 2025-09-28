<?php
declare(strict_types=1);

namespace Services;

use Core\Database;
use Core\Logger;

class MultiLanguageSupportService
{
    private Database $db;
    private Logger $logger;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->logger = new Logger();
    }

    /**
     * Get available languages
     */
    public function getAvailableLanguages(): array
    {
        try {
            $stmt = $this->db->query("
                SELECT 
                    id,
                    code,
                    name,
                    native_name,
                    is_rtl,
                    is_active
                FROM languages
                WHERE is_active = 1
                ORDER BY name
            ");
            
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get available languages: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Set user language
     */
    public function setUserLanguage(int $userId, string $languageCode): bool
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE users 
                SET language_code = ? 
                WHERE id = ?
            ");
            
            return $stmt->execute([$languageCode, $userId]);
        } catch (\Exception $e) {
            $this->logger->error("Failed to set user language: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get translation
     */
    public function getTranslation(string $key, string $languageCode = 'en'): string
    {
        try {
            $stmt = $this->db->prepare("
                SELECT value
                FROM translations
                WHERE translation_key = ? AND language_code = ?
            ");
            
            $stmt->execute([$key, $languageCode]);
            $result = $stmt->fetch();
            
            return $result ? $result['value'] : $key;
        } catch (\Exception $e) {
            $this->logger->error("Failed to get translation: " . $e->getMessage());
            return $key;
        }
    }

    /**
     * Get language analytics
     */
    public function getLanguageAnalytics(): array
    {
        try {
            $stmt = $this->db->query("
                SELECT 
                    u.language_code,
                    l.name as language_name,
                    COUNT(*) as user_count,
                    AVG(u.posts_count) as avg_posts,
                    AVG(u.comments_count) as avg_comments
                FROM users u
                LEFT JOIN languages l ON u.language_code = l.code
                GROUP BY u.language_code, l.name
                ORDER BY user_count DESC
            ");
            
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get language analytics: " . $e->getMessage());
            return [];
        }
    }
}