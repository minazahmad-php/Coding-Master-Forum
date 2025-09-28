<?php
declare(strict_types=1);

namespace Services;

use Core\Database;
use Core\Logger;

class ConversionAnalyticsService
{
    private Database $db;
    private Logger $logger;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->logger = new Logger();
    }

    /**
     * Track conversion event
     */
    public function trackConversionEvent(int $userId, string $conversionType, float $value = 0.0, array $metadata = []): bool
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO conversion_events (user_id, conversion_type, value, metadata, ip_address, user_agent, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            return $stmt->execute([
                $userId,
                $conversionType,
                $value,
                json_encode($metadata),
                $_SERVER['REMOTE_ADDR'] ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? '',
                date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            $this->logger->error("Failed to track conversion event: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get conversion funnel metrics
     */
    public function getConversionFunnelMetrics(): array
    {
        try {
            $stmt = $this->db->query("
                SELECT 
                    'Visitors' as step,
                    COUNT(DISTINCT ip_address) as count,
                    100.0 as conversion_rate
                FROM page_views
                UNION ALL
                SELECT 
                    'Registered Users' as step,
                    COUNT(*) as count,
                    (COUNT(*) / (SELECT COUNT(DISTINCT ip_address) FROM page_views)) * 100 as conversion_rate
                FROM users
                UNION ALL
                SELECT 
                    'First Post' as step,
                    COUNT(*) as count,
                    (COUNT(*) / (SELECT COUNT(*) FROM users)) * 100 as conversion_rate
                FROM users WHERE posts_count > 0
                UNION ALL
                SELECT 
                    'First Comment' as step,
                    COUNT(*) as count,
                    (COUNT(*) / (SELECT COUNT(*) FROM users)) * 100 as conversion_rate
                FROM users WHERE comments_count > 0
                UNION ALL
                SELECT 
                    'Active Users (7 days)' as step,
                    COUNT(*) as count,
                    (COUNT(*) / (SELECT COUNT(*) FROM users)) * 100 as conversion_rate
                FROM users WHERE last_login >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                UNION ALL
                SELECT 
                    'Active Users (30 days)' as step,
                    COUNT(*) as count,
                    (COUNT(*) / (SELECT COUNT(*) FROM users)) * 100 as conversion_rate
                FROM users WHERE last_login >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get conversion funnel metrics: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get conversion rates by source
     */
    public function getConversionRatesBySource(int $days = 30): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    CASE 
                        WHEN referrer = '' OR referrer IS NULL THEN 'Direct'
                        WHEN referrer LIKE '%google%' THEN 'Google'
                        WHEN referrer LIKE '%facebook%' THEN 'Facebook'
                        WHEN referrer LIKE '%twitter%' THEN 'Twitter'
                        WHEN referrer LIKE '%linkedin%' THEN 'LinkedIn'
                        ELSE 'Other'
                    END as source,
                    COUNT(DISTINCT pv.ip_address) as visitors,
                    COUNT(DISTINCT u.id) as registered_users,
                    COUNT(DISTINCT CASE WHEN u.posts_count > 0 THEN u.id END) as users_with_posts,
                    COUNT(DISTINCT CASE WHEN u.comments_count > 0 THEN u.id END) as users_with_comments,
                    COUNT(DISTINCT CASE WHEN u.last_login >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN u.id END) as active_users_7d,
                    COUNT(DISTINCT CASE WHEN u.last_login >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN u.id END) as active_users_30d
                FROM page_views pv
                LEFT JOIN users u ON pv.ip_address = u.ip_address
                WHERE pv.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY source
                ORDER BY visitors DESC
            ");
            
            $stmt->execute([$days]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get conversion rates by source: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get conversion trends
     */
    public function getConversionTrends(int $days = 30): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    DATE(created_at) as date,
                    COUNT(DISTINCT ip_address) as visitors,
                    COUNT(DISTINCT user_id) as registered_users,
                    COUNT(DISTINCT CASE WHEN posts_count > 0 THEN user_id END) as users_with_posts,
                    COUNT(DISTINCT CASE WHEN comments_count > 0 THEN user_id END) as users_with_comments,
                    AVG(CASE WHEN posts_count > 0 THEN 1 ELSE 0 END) as post_conversion_rate,
                    AVG(CASE WHEN comments_count > 0 THEN 1 ELSE 0 END) as comment_conversion_rate
                FROM (
                    SELECT 
                        pv.created_at,
                        pv.ip_address,
                        u.id as user_id,
                        u.posts_count,
                        u.comments_count
                    FROM page_views pv
                    LEFT JOIN users u ON pv.ip_address = u.ip_address
                    WHERE pv.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                ) daily_data
                GROUP BY DATE(created_at)
                ORDER BY date DESC
            ");
            
            $stmt->execute([$days]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get conversion trends: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get conversion attribution analysis
     */
    public function getConversionAttributionAnalysis(): array
    {
        try {
            $stmt = $this->db->query("
                SELECT 
                    u.id as user_id,
                    u.username,
                    u.created_at as registration_date,
                    pv.referrer as first_referrer,
                    pv.created_at as first_visit,
                    TIMESTAMPDIFF(HOUR, pv.created_at, u.created_at) as time_to_conversion_hours,
                    u.posts_count,
                    u.comments_count,
                    u.reputation,
                    CASE 
                        WHEN u.posts_count > 0 AND u.comments_count > 0 THEN 'High Value'
                        WHEN u.posts_count > 0 OR u.comments_count > 0 THEN 'Medium Value'
                        ELSE 'Low Value'
                    END as user_value
                FROM users u
                LEFT JOIN page_views pv ON u.ip_address = pv.ip_address
                WHERE pv.created_at = (
                    SELECT MIN(pv2.created_at) 
                    FROM page_views pv2 
                    WHERE pv2.ip_address = u.ip_address
                )
                ORDER BY u.created_at DESC
                LIMIT 1000
            ");
            
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get conversion attribution analysis: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get conversion cohort analysis
     */
    public function getConversionCohortAnalysis(): array
    {
        try {
            $stmt = $this->db->query("
                SELECT 
                    DATE_FORMAT(registration_date, '%Y-%m') as cohort_month,
                    COUNT(*) as cohort_size,
                    COUNT(CASE WHEN posts_count > 0 THEN 1 END) as users_with_posts,
                    COUNT(CASE WHEN comments_count > 0 THEN 1 END) as users_with_comments,
                    COUNT(CASE WHEN last_login >= DATE_ADD(registration_date, INTERVAL 1 DAY) THEN 1 END) as day_1_active,
                    COUNT(CASE WHEN last_login >= DATE_ADD(registration_date, INTERVAL 7 DAY) THEN 1 END) as day_7_active,
                    COUNT(CASE WHEN last_login >= DATE_ADD(registration_date, INTERVAL 30 DAY) THEN 1 END) as day_30_active,
                    AVG(posts_count) as avg_posts_per_user,
                    AVG(comments_count) as avg_comments_per_user,
                    AVG(reputation) as avg_reputation
                FROM users
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                ORDER BY cohort_month DESC
            ");
            
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get conversion cohort analysis: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get conversion by device type
     */
    public function getConversionByDeviceType(int $days = 30): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    ua.device_type,
                    COUNT(DISTINCT pv.ip_address) as visitors,
                    COUNT(DISTINCT u.id) as registered_users,
                    COUNT(DISTINCT CASE WHEN u.posts_count > 0 THEN u.id END) as users_with_posts,
                    COUNT(DISTINCT CASE WHEN u.comments_count > 0 THEN u.id END) as users_with_comments,
                    AVG(CASE WHEN u.posts_count > 0 THEN 1 ELSE 0 END) as post_conversion_rate,
                    AVG(CASE WHEN u.comments_count > 0 THEN 1 ELSE 0 END) as comment_conversion_rate
                FROM page_views pv
                LEFT JOIN user_agents ua ON pv.user_agent = ua.user_agent
                LEFT JOIN users u ON pv.ip_address = u.ip_address
                WHERE pv.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY ua.device_type
                ORDER BY visitors DESC
            ");
            
            $stmt->execute([$days]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get conversion by device type: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get conversion by geographic location
     */
    public function getConversionByGeographicLocation(int $days = 30): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    ig.country,
                    ig.region,
                    COUNT(DISTINCT pv.ip_address) as visitors,
                    COUNT(DISTINCT u.id) as registered_users,
                    COUNT(DISTINCT CASE WHEN u.posts_count > 0 THEN u.id END) as users_with_posts,
                    COUNT(DISTINCT CASE WHEN u.comments_count > 0 THEN u.id END) as users_with_comments,
                    AVG(CASE WHEN u.posts_count > 0 THEN 1 ELSE 0 END) as post_conversion_rate,
                    AVG(CASE WHEN u.comments_count > 0 THEN 1 ELSE 0 END) as comment_conversion_rate
                FROM page_views pv
                LEFT JOIN ip_geolocation ig ON pv.ip_address = ig.ip_address
                LEFT JOIN users u ON pv.ip_address = u.ip_address
                WHERE pv.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY ig.country, ig.region
                ORDER BY visitors DESC
                LIMIT 50
            ");
            
            $stmt->execute([$days]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get conversion by geographic location: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get conversion optimization insights
     */
    public function getConversionOptimizationInsights(): array
    {
        try {
            $stmt = $this->db->query("
                SELECT 
                    'Registration Form' as page,
                    COUNT(DISTINCT CASE WHEN page = '/register' THEN session_id END) as visitors,
                    COUNT(DISTINCT CASE WHEN page = '/register' AND user_id IS NOT NULL THEN session_id END) as conversions,
                    AVG(CASE WHEN page = '/register' AND user_id IS NOT NULL THEN 1 ELSE 0 END) as conversion_rate
                FROM page_views
                UNION ALL
                SELECT 
                    'Login Page' as page,
                    COUNT(DISTINCT CASE WHEN page = '/login' THEN session_id END) as visitors,
                    COUNT(DISTINCT CASE WHEN page = '/login' AND user_id IS NOT NULL THEN session_id END) as conversions,
                    AVG(CASE WHEN page = '/login' AND user_id IS NOT NULL THEN 1 ELSE 0 END) as conversion_rate
                FROM page_views
                UNION ALL
                SELECT 
                    'Create Post' as page,
                    COUNT(DISTINCT CASE WHEN page = '/posts/create' THEN session_id END) as visitors,
                    COUNT(DISTINCT CASE WHEN page = '/posts/create' AND user_id IS NOT NULL THEN session_id END) as conversions,
                    AVG(CASE WHEN page = '/posts/create' AND user_id IS NOT NULL THEN 1 ELSE 0 END) as conversion_rate
                FROM page_views
            ");
            
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get conversion optimization insights: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get conversion value analysis
     */
    public function getConversionValueAnalysis(): array
    {
        try {
            $stmt = $this->db->query("
                SELECT 
                    'High Value Users' as segment,
                    COUNT(*) as user_count,
                    AVG(posts_count) as avg_posts,
                    AVG(comments_count) as avg_comments,
                    AVG(reputation) as avg_reputation,
                    SUM(posts_count * 0.1 + comments_count * 0.05 + reputation * 0.01) as total_value
                FROM users
                WHERE posts_count > 10 OR comments_count > 50 OR reputation > 100
                UNION ALL
                SELECT 
                    'Medium Value Users' as segment,
                    COUNT(*) as user_count,
                    AVG(posts_count) as avg_posts,
                    AVG(comments_count) as avg_comments,
                    AVG(reputation) as avg_reputation,
                    SUM(posts_count * 0.1 + comments_count * 0.05 + reputation * 0.01) as total_value
                FROM users
                WHERE (posts_count > 0 AND posts_count <= 10) OR (comments_count > 0 AND comments_count <= 50) OR (reputation > 0 AND reputation <= 100)
                UNION ALL
                SELECT 
                    'Low Value Users' as segment,
                    COUNT(*) as user_count,
                    AVG(posts_count) as avg_posts,
                    AVG(comments_count) as avg_comments,
                    AVG(reputation) as avg_reputation,
                    SUM(posts_count * 0.1 + comments_count * 0.05 + reputation * 0.01) as total_value
                FROM users
                WHERE posts_count = 0 AND comments_count = 0 AND reputation = 0
            ");
            
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get conversion value analysis: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Export conversion analytics data
     */
    public function exportConversionAnalytics(array $filters = []): string
    {
        try {
            $whereClause = '';
            $params = [];
            
            if (!empty($filters['conversion_type'])) {
                $whereClause .= " AND conversion_type = ?";
                $params[] = $filters['conversion_type'];
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
                    user_id,
                    conversion_type,
                    value,
                    metadata,
                    ip_address,
                    user_agent,
                    created_at
                FROM conversion_events
                WHERE 1=1 {$whereClause}
                ORDER BY created_at DESC
            ");
            
            $stmt->execute($params);
            $data = $stmt->fetchAll();
            
            // Convert to CSV
            $csv = "User ID,Conversion Type,Value,Metadata,IP Address,User Agent,Created At\n";
            foreach ($data as $row) {
                $csv .= implode(',', array_map(function($value) {
                    return '"' . str_replace('"', '""', $value) . '"';
                }, $row)) . "\n";
            }
            
            return $csv;
        } catch (\Exception $e) {
            $this->logger->error("Failed to export conversion analytics: " . $e->getMessage());
            return '';
        }
    }
}