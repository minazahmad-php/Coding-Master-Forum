<?php
declare(strict_types=1);

namespace Services;

use Core\Database;
use Core\Logger;

class ContentAnalyticsService
{
    private Database $db;
    private Logger $logger;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->logger = new Logger();
    }

    /**
     * Track content interaction
     */
    public function trackContentInteraction(int $contentId, string $contentType, string $action, int $userId = null): bool
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO content_interactions (content_id, content_type, action, user_id, ip_address, user_agent, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            return $stmt->execute([
                $contentId,
                $contentType,
                $action,
                $userId,
                $_SERVER['REMOTE_ADDR'] ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? '',
                date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            $this->logger->error("Failed to track content interaction: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get content performance metrics
     */
    public function getContentPerformanceMetrics(int $contentId, string $contentType): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    action,
                    COUNT(*) as count,
                    COUNT(DISTINCT user_id) as unique_users,
                    COUNT(DISTINCT ip_address) as unique_ips
                FROM content_interactions 
                WHERE content_id = ? AND content_type = ?
                GROUP BY action
            ");
            
            $stmt->execute([$contentId, $contentType]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get content performance metrics: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get trending content
     */
    public function getTrendingContent(string $contentType = 'all', int $limit = 10, int $days = 7): array
    {
        try {
            $whereClause = $contentType !== 'all' ? "AND content_type = ?" : "";
            $params = $contentType !== 'all' ? [$contentType, $days] : [$days];
            
            $stmt = $this->db->prepare("
                SELECT 
                    content_id,
                    content_type,
                    COUNT(*) as interaction_count,
                    COUNT(DISTINCT user_id) as unique_users,
                    MAX(created_at) as last_interaction
                FROM content_interactions 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY) {$whereClause}
                GROUP BY content_id, content_type
                ORDER BY interaction_count DESC, unique_users DESC
                LIMIT ?
            ");
            
            $stmt->execute(array_merge($params, [$limit]));
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get trending content: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get content engagement metrics
     */
    public function getContentEngagementMetrics(int $days = 30): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    content_type,
                    COUNT(DISTINCT content_id) as total_content,
                    COUNT(*) as total_interactions,
                    AVG(interaction_count) as avg_interactions_per_content,
                    COUNT(DISTINCT user_id) as unique_users
                FROM (
                    SELECT 
                        content_id,
                        content_type,
                        user_id,
                        COUNT(*) as interaction_count
                    FROM content_interactions 
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                    GROUP BY content_id, content_type, user_id
                ) content_stats
                GROUP BY content_type
                ORDER BY total_interactions DESC
            ");
            
            $stmt->execute([$days]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get content engagement metrics: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get content quality metrics
     */
    public function getContentQualityMetrics(): array
    {
        try {
            $stmt = $this->db->query("
                SELECT 
                    'Posts' as content_type,
                    COUNT(*) as total_count,
                    AVG(LENGTH(content)) as avg_length,
                    COUNT(CASE WHEN LENGTH(content) > 1000 THEN 1 END) as long_content_count,
                    COUNT(CASE WHEN LENGTH(content) < 100 THEN 1 END) as short_content_count,
                    AVG(views_count) as avg_views,
                    AVG(likes_count) as avg_likes,
                    AVG(comments_count) as avg_comments
                FROM posts
                UNION ALL
                SELECT 
                    'Comments' as content_type,
                    COUNT(*) as total_count,
                    AVG(LENGTH(content)) as avg_length,
                    COUNT(CASE WHEN LENGTH(content) > 500 THEN 1 END) as long_content_count,
                    COUNT(CASE WHEN LENGTH(content) < 50 THEN 1 END) as short_content_count,
                    AVG(views_count) as avg_views,
                    AVG(likes_count) as avg_likes,
                    AVG(replies_count) as avg_comments
                FROM comments
            ");
            
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get content quality metrics: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get content creation trends
     */
    public function getContentCreationTrends(int $days = 30): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    DATE(created_at) as date,
                    COUNT(CASE WHEN content_type = 'post' THEN 1 END) as posts_count,
                    COUNT(CASE WHEN content_type = 'comment' THEN 1 END) as comments_count,
                    COUNT(CASE WHEN content_type = 'reply' THEN 1 END) as replies_count,
                    COUNT(*) as total_content
                FROM (
                    SELECT 'post' as content_type, created_at FROM posts
                    UNION ALL
                    SELECT 'comment' as content_type, created_at FROM comments
                ) content_union
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY DATE(created_at)
                ORDER BY date DESC
            ");
            
            $stmt->execute([$days]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get content creation trends: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get content performance by category
     */
    public function getContentPerformanceByCategory(): array
    {
        try {
            $stmt = $this->db->query("
                SELECT 
                    c.name as category_name,
                    COUNT(p.id) as posts_count,
                    AVG(p.views_count) as avg_views,
                    AVG(p.likes_count) as avg_likes,
                    AVG(p.comments_count) as avg_comments,
                    SUM(p.views_count) as total_views,
                    SUM(p.likes_count) as total_likes,
                    SUM(p.comments_count) as total_comments
                FROM categories c
                LEFT JOIN posts p ON c.id = p.category_id
                GROUP BY c.id, c.name
                ORDER BY total_views DESC
            ");
            
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get content performance by category: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get content moderation metrics
     */
    public function getContentModerationMetrics(int $days = 30): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    status,
                    COUNT(*) as count,
                    AVG(CASE WHEN moderator_id IS NOT NULL THEN 1 ELSE 0 END) as avg_moderated,
                    AVG(TIMESTAMPDIFF(MINUTE, created_at, updated_at)) as avg_moderation_time_minutes
                FROM posts 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY status
                ORDER BY count DESC
            ");
            
            $stmt->execute([$days]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get content moderation metrics: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get content recommendation effectiveness
     */
    public function getContentRecommendationEffectiveness(): array
    {
        try {
            $stmt = $this->db->query("
                SELECT 
                    recommendation_type,
                    COUNT(*) as recommendations_count,
                    COUNT(CASE WHEN clicked = 1 THEN 1 END) as clicks_count,
                    AVG(CASE WHEN clicked = 1 THEN 1 ELSE 0 END) as click_through_rate,
                    AVG(CASE WHEN clicked = 1 THEN engagement_score ELSE 0 END) as avg_engagement_score
                FROM content_recommendations
                GROUP BY recommendation_type
                ORDER BY click_through_rate DESC
            ");
            
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get content recommendation effectiveness: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get content A/B testing results
     */
    public function getContentABTestingResults(): array
    {
        try {
            $stmt = $this->db->query("
                SELECT 
                    test_name,
                    variant,
                    COUNT(*) as impressions,
                    COUNT(CASE WHEN clicked = 1 THEN 1 END) as clicks,
                    AVG(CASE WHEN clicked = 1 THEN 1 ELSE 0 END) as click_through_rate,
                    AVG(engagement_score) as avg_engagement_score,
                    AVG(conversion_rate) as avg_conversion_rate
                FROM content_ab_tests
                GROUP BY test_name, variant
                ORDER BY test_name, click_through_rate DESC
            ");
            
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get content A/B testing results: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Export content analytics data
     */
    public function exportContentAnalytics(array $filters = []): string
    {
        try {
            $whereClause = '';
            $params = [];
            
            if (!empty($filters['content_type'])) {
                $whereClause .= " AND content_type = ?";
                $params[] = $filters['content_type'];
            }
            
            if (!empty($filters['date_from'])) {
                $whereClause .= " AND created_at >= ?";
                $params[] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $whereClause .= " AND created_at <= ?";
                $params[] = $filters['date_to'];
            }
            
            $stmt = $this->db->prepare("
                SELECT 
                    content_id,
                    content_type,
                    action,
                    COUNT(*) as count,
                    COUNT(DISTINCT user_id) as unique_users,
                    COUNT(DISTINCT ip_address) as unique_ips,
                    MIN(created_at) as first_interaction,
                    MAX(created_at) as last_interaction
                FROM content_interactions
                WHERE 1=1 {$whereClause}
                GROUP BY content_id, content_type, action
                ORDER BY count DESC
            ");
            
            $stmt->execute($params);
            $data = $stmt->fetchAll();
            
            // Convert to CSV
            $csv = "Content ID,Content Type,Action,Count,Unique Users,Unique IPs,First Interaction,Last Interaction\n";
            foreach ($data as $row) {
                $csv .= implode(',', array_map(function($value) {
                    return '"' . str_replace('"', '""', $value) . '"';
                }, $row)) . "\n";
            }
            
            return $csv;
        } catch (\Exception $e) {
            $this->logger->error("Failed to export content analytics: " . $e->getMessage());
            return '';
        }
    }
}