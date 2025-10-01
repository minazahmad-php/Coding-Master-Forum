<?php
declare(strict_types=1);

namespace Services;

use Core\Database;
use Core\Logger;

class UserAnalyticsService
{
    private Database $db;
    private Logger $logger;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->logger = new Logger();
    }

    /**
     * Track user activity
     */
    public function trackActivity(int $userId, string $action, array $metadata = []): bool
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO user_activities (user_id, action, metadata, ip_address, user_agent, created_at)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            return $stmt->execute([
                $userId,
                $action,
                json_encode($metadata),
                $_SERVER['REMOTE_ADDR'] ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? '',
                date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            $this->logger->error("Failed to track user activity: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user activity summary
     */
    public function getUserActivitySummary(int $userId, int $days = 30): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    action,
                    COUNT(*) as count,
                    DATE(created_at) as date
                FROM user_activities 
                WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY action, DATE(created_at)
                ORDER BY date DESC
            ");
            
            $stmt->execute([$userId, $days]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get user activity summary: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get user engagement metrics
     */
    public function getUserEngagementMetrics(int $userId): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(DISTINCT DATE(created_at)) as active_days,
                    COUNT(*) as total_activities,
                    AVG(activities_per_day) as avg_daily_activities
                FROM (
                    SELECT 
                        created_at,
                        COUNT(*) as activities_per_day
                    FROM user_activities 
                    WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                    GROUP BY DATE(created_at)
                ) daily_stats
            ");
            
            $stmt->execute([$userId]);
            return $stmt->fetch() ?: [];
        } catch (\Exception $e) {
            $this->logger->error("Failed to get user engagement metrics: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get user behavior patterns
     */
    public function getUserBehaviorPatterns(int $userId): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    HOUR(created_at) as hour,
                    DAYOFWEEK(created_at) as day_of_week,
                    COUNT(*) as activity_count
                FROM user_activities 
                WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY HOUR(created_at), DAYOFWEEK(created_at)
                ORDER BY activity_count DESC
            ");
            
            $stmt->execute([$userId]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get user behavior patterns: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get user retention metrics
     */
    public function getUserRetentionMetrics(): array
    {
        try {
            $stmt = $this->db->query("
                SELECT 
                    DATE(created_at) as date,
                    COUNT(DISTINCT user_id) as new_users,
                    COUNT(DISTINCT CASE WHEN last_login >= DATE_SUB(created_at, INTERVAL 1 DAY) THEN user_id END) as retained_1d,
                    COUNT(DISTINCT CASE WHEN last_login >= DATE_SUB(created_at, INTERVAL 7 DAY) THEN user_id END) as retained_7d,
                    COUNT(DISTINCT CASE WHEN last_login >= DATE_SUB(created_at, INTERVAL 30 DAY) THEN user_id END) as retained_30d
                FROM users 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY DATE(created_at)
                ORDER BY date DESC
            ");
            
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get user retention metrics: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get user cohort analysis
     */
    public function getUserCohortAnalysis(): array
    {
        try {
            $stmt = $this->db->query("
                SELECT 
                    DATE_FORMAT(created_at, '%Y-%m') as cohort_month,
                    COUNT(*) as cohort_size,
                    COUNT(CASE WHEN last_login >= DATE_ADD(created_at, INTERVAL 1 DAY) THEN 1 END) as day_1_retention,
                    COUNT(CASE WHEN last_login >= DATE_ADD(created_at, INTERVAL 7 DAY) THEN 1 END) as day_7_retention,
                    COUNT(CASE WHEN last_login >= DATE_ADD(created_at, INTERVAL 30 DAY) THEN 1 END) as day_30_retention
                FROM users 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                ORDER BY cohort_month DESC
            ");
            
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get user cohort analysis: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get user segmentation
     */
    public function getUserSegmentation(): array
    {
        try {
            $stmt = $this->db->query("
                SELECT 
                    CASE 
                        WHEN last_login >= DATE_SUB(NOW(), INTERVAL 1 DAY) THEN 'Active'
                        WHEN last_login >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 'Recently Active'
                        WHEN last_login >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 'Inactive'
                        ELSE 'Dormant'
                    END as segment,
                    COUNT(*) as user_count,
                    AVG(posts_count) as avg_posts,
                    AVG(comments_count) as avg_comments
                FROM users 
                GROUP BY segment
                ORDER BY user_count DESC
            ");
            
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get user segmentation: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get user conversion funnel
     */
    public function getUserConversionFunnel(): array
    {
        try {
            $stmt = $this->db->query("
                SELECT 
                    'Registered' as step,
                    COUNT(*) as count
                FROM users
                UNION ALL
                SELECT 
                    'First Post' as step,
                    COUNT(*) as count
                FROM users WHERE posts_count > 0
                UNION ALL
                SELECT 
                    'First Comment' as step,
                    COUNT(*) as count
                FROM users WHERE comments_count > 0
                UNION ALL
                SELECT 
                    'Active User' as step,
                    COUNT(*) as count
                FROM users WHERE last_login >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            ");
            
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get user conversion funnel: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get user lifetime value
     */
    public function getUserLifetimeValue(): array
    {
        try {
            $stmt = $this->db->query("
                SELECT 
                    AVG(posts_count * 0.1 + comments_count * 0.05 + reputation * 0.01) as avg_ltv,
                    MAX(posts_count * 0.1 + comments_count * 0.05 + reputation * 0.01) as max_ltv,
                    MIN(posts_count * 0.1 + comments_count * 0.05 + reputation * 0.01) as min_ltv
                FROM users
            ");
            
            return $stmt->fetch() ?: [];
        } catch (\Exception $e) {
            $this->logger->error("Failed to get user lifetime value: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Export user analytics data
     */
    public function exportUserAnalytics(array $filters = []): string
    {
        try {
            $whereClause = '';
            $params = [];
            
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
                    u.id,
                    u.username,
                    u.email,
                    u.created_at,
                    u.last_login,
                    u.posts_count,
                    u.comments_count,
                    u.reputation,
                    COUNT(ua.id) as total_activities
                FROM users u
                LEFT JOIN user_activities ua ON u.id = ua.user_id
                WHERE 1=1 {$whereClause}
                GROUP BY u.id
                ORDER BY u.created_at DESC
            ");
            
            $stmt->execute($params);
            $data = $stmt->fetchAll();
            
            // Convert to CSV
            $csv = "User ID,Username,Email,Created At,Last Login,Posts Count,Comments Count,Reputation,Total Activities\n";
            foreach ($data as $row) {
                $csv .= implode(',', array_map(function($value) {
                    return '"' . str_replace('"', '""', $value) . '"';
                }, $row)) . "\n";
            }
            
            return $csv;
        } catch (\Exception $e) {
            $this->logger->error("Failed to export user analytics: " . $e->getMessage());
            return '';
        }
    }
}