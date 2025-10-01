<?php
declare(strict_types=1);

namespace Services;

use Core\Database;
use Core\Logger;

class LocalizationService
{
    private Database $db;
    private Logger $logger;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->logger = new Logger();
    }

    /**
     * Get localized content
     */
    public function getLocalizedContent(int $contentId, string $contentType, string $languageCode): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    lc.*,
                    l.name as language_name
                FROM localized_content lc
                LEFT JOIN languages l ON lc.language_code = l.code
                WHERE lc.content_id = ? 
                AND lc.content_type = ? 
                AND lc.language_code = ?
            ");
            
            $stmt->execute([$contentId, $contentType, $languageCode]);
            return $stmt->fetch() ?: [];
        } catch (\Exception $e) {
            $this->logger->error("Failed to get localized content: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Create localized content
     */
    public function createLocalizedContent(array $contentData): array
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO localized_content (content_id, content_type, language_code, title, content, created_at)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $contentData['content_id'],
                $contentData['content_type'],
                $contentData['language_code'],
                $contentData['title'],
                $contentData['content'],
                date('Y-m-d H:i:s')
            ]);
            
            return ['success' => true, 'localized_content_id' => $this->db->lastInsertId()];
        } catch (\Exception $e) {
            $this->logger->error("Failed to create localized content: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get localization analytics
     */
    public function getLocalizationAnalytics(): array
    {
        try {
            $stmt = $this->db->query("
                SELECT 
                    lc.language_code,
                    l.name as language_name,
                    lc.content_type,
                    COUNT(*) as content_count,
                    AVG(LENGTH(lc.content)) as avg_content_length
                FROM localized_content lc
                LEFT JOIN languages l ON lc.language_code = l.code
                GROUP BY lc.language_code, l.name, lc.content_type
                ORDER BY content_count DESC
            ");
            
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get localization analytics: " . $e->getMessage());
            return [];
        }
    }
}