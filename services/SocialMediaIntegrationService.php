<?php
declare(strict_types=1);

namespace Services;

use Core\Database;
use Core\Logger;

class SocialMediaIntegrationService
{
    private Database $db;
    private Logger $logger;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->logger = new Logger();
    }

    /**
     * Share content to social media
     */
    public function shareToSocialMedia(int $contentId, string $contentType, string $platform, int $userId): bool
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO social_media_shares (content_id, content_type, platform, user_id, created_at)
                VALUES (?, ?, ?, ?, ?)
            ");
            
            return $stmt->execute([
                $contentId,
                $contentType,
                $platform,
                $userId,
                date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            $this->logger->error("Failed to share to social media: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get social media analytics
     */
    public function getSocialMediaAnalytics(int $days = 30): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    platform,
                    COUNT(*) as total_shares,
                    COUNT(DISTINCT user_id) as unique_users,
                    COUNT(DISTINCT content_id) as unique_content,
                    AVG(shares_per_user) as avg_shares_per_user
                FROM (
                    SELECT 
                        platform,
                        user_id,
                        content_id,
                        COUNT(*) as shares_per_user
                    FROM social_media_shares 
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                    GROUP BY platform, user_id, content_id
                ) share_stats
                GROUP BY platform
                ORDER BY total_shares DESC
            ");
            
            $stmt->execute([$days]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get social media analytics: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get trending content for social media
     */
    public function getTrendingContentForSocialMedia(int $limit = 10): array
    {
        try {
            $stmt = $this->db->query("
                SELECT 
                    p.id,
                    p.title,
                    p.content,
                    p.views_count,
                    p.likes_count,
                    p.comments_count,
                    COUNT(sms.id) as social_shares,
                    u.username as author
                FROM posts p
                LEFT JOIN social_media_shares sms ON p.id = sms.content_id AND sms.content_type = 'post'
                LEFT JOIN users u ON p.user_id = u.id
                WHERE p.status = 'published'
                GROUP BY p.id
                ORDER BY social_shares DESC, p.views_count DESC
                LIMIT ?
            ");
            
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get trending content for social media: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get social media engagement metrics
     */
    public function getSocialMediaEngagementMetrics(int $days = 30): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    DATE(created_at) as date,
                    platform,
                    COUNT(*) as shares_count,
                    COUNT(DISTINCT user_id) as unique_users,
                    COUNT(DISTINCT content_id) as unique_content
                FROM social_media_shares 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY DATE(created_at), platform
                ORDER BY date DESC, shares_count DESC
            ");
            
            $stmt->execute([$days]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get social media engagement metrics: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get social media performance by content type
     */
    public function getSocialMediaPerformanceByContentType(int $days = 30): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    content_type,
                    platform,
                    COUNT(*) as shares_count,
                    COUNT(DISTINCT user_id) as unique_users,
                    COUNT(DISTINCT content_id) as unique_content,
                    AVG(shares_per_content) as avg_shares_per_content
                FROM (
                    SELECT 
                        content_type,
                        platform,
                        user_id,
                        content_id,
                        COUNT(*) as shares_per_content
                    FROM social_media_shares 
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                    GROUP BY content_type, platform, user_id, content_id
                ) content_stats
                GROUP BY content_type, platform
                ORDER BY shares_count DESC
            ");
            
            $stmt->execute([$days]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get social media performance by content type: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get social media user behavior
     */
    public function getSocialMediaUserBehavior(int $days = 30): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    user_id,
                    COUNT(*) as total_shares,
                    COUNT(DISTINCT platform) as platforms_used,
                    COUNT(DISTINCT content_id) as content_shared,
                    AVG(shares_per_day) as avg_shares_per_day,
                    MAX(created_at) as last_share
                FROM (
                    SELECT 
                        user_id,
                        platform,
                        content_id,
                        created_at,
                        COUNT(*) as shares_per_day
                    FROM social_media_shares 
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                    GROUP BY user_id, platform, content_id, DATE(created_at)
                ) user_stats
                GROUP BY user_id
                ORDER BY total_shares DESC
                LIMIT 100
            ");
            
            $stmt->execute([$days]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get social media user behavior: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get social media content recommendations
     */
    public function getSocialMediaContentRecommendations(int $userId, int $limit = 10): array
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
                    COUNT(sms.id) as social_shares,
                    u.username as author,
                    c.name as category_name
                FROM posts p
                LEFT JOIN social_media_shares sms ON p.id = sms.content_id AND sms.content_type = 'post'
                LEFT JOIN users u ON p.user_id = u.id
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.status = 'published'
                AND p.user_id != ?
                AND p.id NOT IN (
                    SELECT content_id 
                    FROM social_media_shares 
                    WHERE user_id = ? AND content_type = 'post'
                )
                GROUP BY p.id
                ORDER BY social_shares DESC, p.views_count DESC
                LIMIT ?
            ");
            
            $stmt->execute([$userId, $userId, $limit]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get social media content recommendations: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get social media platform preferences
     */
    public function getSocialMediaPlatformPreferences(int $days = 30): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    platform,
                    COUNT(*) as total_shares,
                    COUNT(DISTINCT user_id) as unique_users,
                    AVG(shares_per_user) as avg_shares_per_user,
                    COUNT(DISTINCT content_id) as unique_content,
                    AVG(shares_per_content) as avg_shares_per_content
                FROM (
                    SELECT 
                        platform,
                        user_id,
                        content_id,
                        COUNT(*) as shares_per_user,
                        COUNT(*) as shares_per_content
                    FROM social_media_shares 
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                    GROUP BY platform, user_id, content_id
                ) platform_stats
                GROUP BY platform
                ORDER BY total_shares DESC
            ");
            
            $stmt->execute([$days]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get social media platform preferences: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get social media content performance
     */
    public function getSocialMediaContentPerformance(int $contentId, string $contentType): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    platform,
                    COUNT(*) as shares_count,
                    COUNT(DISTINCT user_id) as unique_users,
                    AVG(shares_per_user) as avg_shares_per_user,
                    MIN(created_at) as first_share,
                    MAX(created_at) as last_share
                FROM (
                    SELECT 
                        platform,
                        user_id,
                        created_at,
                        COUNT(*) as shares_per_user
                    FROM social_media_shares 
                    WHERE content_id = ? AND content_type = ?
                    GROUP BY platform, user_id
                ) content_stats
                GROUP BY platform
                ORDER BY shares_count DESC
            ");
            
            $stmt->execute([$contentId, $contentType]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get social media content performance: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get social media user engagement score
     */
    public function getSocialMediaUserEngagementScore(int $userId, int $days = 30): float
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_shares,
                    COUNT(DISTINCT platform) as platforms_used,
                    COUNT(DISTINCT content_id) as content_shared,
                    AVG(shares_per_day) as avg_shares_per_day
                FROM (
                    SELECT 
                        platform,
                        content_id,
                        DATE(created_at) as share_date,
                        COUNT(*) as shares_per_day
                    FROM social_media_shares 
                    WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                    GROUP BY platform, content_id, DATE(created_at)
                ) user_stats
            ");
            
            $stmt->execute([$userId, $days]);
            $result = $stmt->fetch();
            
            if (!$result) return 0.0;
            
            // Calculate engagement score based on multiple factors
            $score = 0;
            $score += $result['total_shares'] * 1; // 1 point per share
            $score += $result['platforms_used'] * 5; // 5 points per platform
            $score += $result['content_shared'] * 2; // 2 points per unique content
            $score += $result['avg_shares_per_day'] * 3; // 3 points per daily average
            
            return (float) $score;
        } catch (\Exception $e) {
            $this->logger->error("Failed to get social media user engagement score: " . $e->getMessage());
            return 0.0;
        }
    }

    /**
     * Get social media content suggestions
     */
    public function getSocialMediaContentSuggestions(int $userId, int $limit = 5): array
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
                    COUNT(sms.id) as social_shares,
                    u.username as author,
                    c.name as category_name,
                    CASE 
                        WHEN p.views_count > 1000 THEN 'High Traffic'
                        WHEN p.views_count > 500 THEN 'Medium Traffic'
                        ELSE 'Low Traffic'
                    END as traffic_level
                FROM posts p
                LEFT JOIN social_media_shares sms ON p.id = sms.content_id AND sms.content_type = 'post'
                LEFT JOIN users u ON p.user_id = u.id
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.status = 'published'
                AND p.user_id != ?
                AND p.id NOT IN (
                    SELECT content_id 
                    FROM social_media_shares 
                    WHERE user_id = ? AND content_type = 'post'
                )
                GROUP BY p.id
                ORDER BY social_shares DESC, p.views_count DESC, p.likes_count DESC
                LIMIT ?
            ");
            
            $stmt->execute([$userId, $userId, $limit]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get social media content suggestions: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Export social media analytics data
     */
    public function exportSocialMediaAnalytics(array $filters = []): string
    {
        try {
            $whereClause = '';
            $params = [];
            
            if (!empty($filters['platform'])) {
                $whereClause .= " AND platform = ?";
                $params[] = $filters['platform'];
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
                    platform,
                    user_id,
                    created_at
                FROM social_media_shares
                WHERE 1=1 {$whereClause}
                ORDER BY created_at DESC
            ");
            
            $stmt->execute($params);
            $data = $stmt->fetchAll();
            
            // Convert to CSV
            $csv = "Content ID,Content Type,Platform,User ID,Created At\n";
            foreach ($data as $row) {
                $csv .= implode(',', array_map(function($value) {
                    return '"' . str_replace('"', '""', $value) . '"';
                }, $row)) . "\n";
            }
            
            return $csv;
        } catch (\Exception $e) {
            $this->logger->error("Failed to export social media analytics: " . $e->getMessage());
            return '';
        }
    }
}