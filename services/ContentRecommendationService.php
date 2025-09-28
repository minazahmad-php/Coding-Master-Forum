<?php
declare(strict_types=1);

namespace Services;

use Core\Database;
use Core\Logger;

class ContentRecommendationService
{
    private Database $db;
    private Logger $logger;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->logger = new Logger();
    }

    /**
     * Get content recommendations for user
     */
    public function getContentRecommendations(int $userId, int $limit = 10): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    p.id,
                    p.title,
                    p.content,
                    p.views_count,
                    p.likes_count,
                    p.comments_count,
                    u.username as author,
                    c.name as category_name,
                    COUNT(cr.id) as recommendation_score
                FROM posts p
                LEFT JOIN users u ON p.user_id = u.id
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN content_recommendations cr ON p.id = cr.content_id
                WHERE p.status = 'published'
                AND p.id NOT IN (
                    SELECT post_id FROM user_post_interactions 
                    WHERE user_id = ? AND interaction_type = 'view'
                )
                GROUP BY p.id
                ORDER BY recommendation_score DESC, p.views_count DESC
                LIMIT ?
            ");
            
            $stmt->execute([$userId, $limit]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get content recommendations: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Track recommendation interaction
     */
    public function trackRecommendationInteraction(int $userId, int $contentId, string $interactionType): bool
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO content_recommendations (user_id, content_id, interaction_type, created_at)
                VALUES (?, ?, ?, ?)
            ");
            
            return $stmt->execute([
                $userId,
                $contentId,
                $interactionType,
                date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            $this->logger->error("Failed to track recommendation interaction: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get recommendation analytics
     */
    public function getRecommendationAnalytics(int $days = 30): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    interaction_type,
                    COUNT(*) as total_interactions,
                    COUNT(DISTINCT user_id) as unique_users,
                    COUNT(DISTINCT content_id) as unique_content,
                    AVG(interaction_score) as avg_interaction_score
                FROM (
                    SELECT 
                        interaction_type,
                        user_id,
                        content_id,
                        CASE 
                            WHEN interaction_type = 'click' THEN 3
                            WHEN interaction_type = 'like' THEN 2
                            WHEN interaction_type = 'share' THEN 4
                            ELSE 1
                        END as interaction_score
                    FROM content_recommendations 
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                ) interaction_stats
                GROUP BY interaction_type
                ORDER BY total_interactions DESC
            ");
            
            $stmt->execute([$days]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get recommendation analytics: " . $e->getMessage());
            return [];
        }
    }
}