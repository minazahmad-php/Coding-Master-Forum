<?php
declare(strict_types=1);

namespace Services;

use Core\Database;
use Core\Logger;

class EngagementAnalyticsService
{
    private Database $db;
    private Logger $logger;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->logger = new Logger();
    }

    /**
     * Track engagement event
     */
    public function trackEngagementEvent(int $userId, string $eventType, array $metadata = []): bool
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO engagement_events (user_id, event_type, metadata, ip_address, user_agent, created_at)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            return $stmt->execute([
                $userId,
                $eventType,
                json_encode($metadata),
                $_SERVER['REMOTE_ADDR'] ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? '',
                date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            $this->logger->error("Failed to track engagement event: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get engagement metrics
     */
    public function getEngagementMetrics(int $days = 30): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    event_type,
                    COUNT(*) as total_events,
                    COUNT(DISTINCT user_id) as unique_users,
                    AVG(events_per_user) as avg_events_per_user,
                    COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as events_last_7_days,
                    COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY) THEN 1 END) as events_last_1_day
                FROM (
                    SELECT 
                        event_type,
                        user_id,
                        created_at,
                        COUNT(*) as events_per_user
                    FROM engagement_events 
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                    GROUP BY event_type, user_id
                ) user_stats
                GROUP BY event_type
                ORDER BY total_events DESC
            ");
            
            $stmt->execute([$days]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get engagement metrics: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get user engagement score
     */
    public function getUserEngagementScore(int $userId, int $days = 30): float
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    SUM(
                        CASE event_type
                            WHEN 'post_created' THEN 10
                            WHEN 'comment_created' THEN 5
                            WHEN 'like_given' THEN 2
                            WHEN 'share' THEN 3
                            WHEN 'bookmark' THEN 2
                            WHEN 'follow' THEN 5
                            WHEN 'login' THEN 1
                            ELSE 1
                        END
                    ) as engagement_score
                FROM engagement_events 
                WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            
            $stmt->execute([$userId, $days]);
            $result = $stmt->fetch();
            return (float) ($result['engagement_score'] ?? 0);
        } catch (\Exception $e) {
            $this->logger->error("Failed to get user engagement score: " . $e->getMessage());
            return 0.0;
        }
    }

    /**
     * Get top engaged users
     */
    public function getTopEngagedUsers(int $limit = 10, int $days = 30): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    u.id,
                    u.username,
                    u.email,
                    COUNT(ee.id) as total_events,
                    SUM(
                        CASE ee.event_type
                            WHEN 'post_created' THEN 10
                            WHEN 'comment_created' THEN 5
                            WHEN 'like_given' THEN 2
                            WHEN 'share' THEN 3
                            WHEN 'bookmark' THEN 2
                            WHEN 'follow' THEN 5
                            WHEN 'login' THEN 1
                            ELSE 1
                        END
                    ) as engagement_score,
                    MAX(ee.created_at) as last_activity
                FROM users u
                LEFT JOIN engagement_events ee ON u.id = ee.user_id
                WHERE ee.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY u.id, u.username, u.email
                ORDER BY engagement_score DESC
                LIMIT ?
            ");
            
            $stmt->execute([$days, $limit]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get top engaged users: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get engagement trends
     */
    public function getEngagementTrends(int $days = 30): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as total_events,
                    COUNT(DISTINCT user_id) as unique_users,
                    COUNT(CASE WHEN event_type = 'post_created' THEN 1 END) as posts_created,
                    COUNT(CASE WHEN event_type = 'comment_created' THEN 1 END) as comments_created,
                    COUNT(CASE WHEN event_type = 'like_given' THEN 1 END) as likes_given,
                    COUNT(CASE WHEN event_type = 'share' THEN 1 END) as shares,
                    COUNT(CASE WHEN event_type = 'bookmark' THEN 1 END) as bookmarks,
                    COUNT(CASE WHEN event_type = 'follow' THEN 1 END) as follows
                FROM engagement_events 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY DATE(created_at)
                ORDER BY date DESC
            ");
            
            $stmt->execute([$days]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get engagement trends: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get content engagement analysis
     */
    public function getContentEngagementAnalysis(int $days = 30): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    content_type,
                    content_id,
                    COUNT(*) as total_engagements,
                    COUNT(DISTINCT user_id) as unique_users,
                    COUNT(CASE WHEN event_type = 'like' THEN 1 END) as likes,
                    COUNT(CASE WHEN event_type = 'comment' THEN 1 END) as comments,
                    COUNT(CASE WHEN event_type = 'share' THEN 1 END) as shares,
                    COUNT(CASE WHEN event_type = 'bookmark' THEN 1 END) as bookmarks,
                    AVG(engagement_score) as avg_engagement_score
                FROM (
                    SELECT 
                        content_type,
                        content_id,
                        user_id,
                        event_type,
                        CASE event_type
                            WHEN 'like' THEN 2
                            WHEN 'comment' THEN 5
                            WHEN 'share' THEN 3
                            WHEN 'bookmark' THEN 2
                            ELSE 1
                        END as engagement_score
                    FROM engagement_events 
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                    AND event_type IN ('like', 'comment', 'share', 'bookmark')
                ) engagement_stats
                GROUP BY content_type, content_id
                ORDER BY total_engagements DESC
                LIMIT 100
            ");
            
            $stmt->execute([$days]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get content engagement analysis: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get engagement by time of day
     */
    public function getEngagementByTimeOfDay(int $days = 30): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    HOUR(created_at) as hour,
                    COUNT(*) as total_events,
                    COUNT(DISTINCT user_id) as unique_users,
                    AVG(events_per_user) as avg_events_per_user
                FROM (
                    SELECT 
                        created_at,
                        user_id,
                        COUNT(*) as events_per_user
                    FROM engagement_events 
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                    GROUP BY HOUR(created_at), user_id
                ) hourly_stats
                GROUP BY HOUR(created_at)
                ORDER BY hour
            ");
            
            $stmt->execute([$days]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get engagement by time of day: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get engagement by day of week
     */
    public function getEngagementByDayOfWeek(int $days = 30): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    DAYOFWEEK(created_at) as day_of_week,
                    DAYNAME(created_at) as day_name,
                    COUNT(*) as total_events,
                    COUNT(DISTINCT user_id) as unique_users,
                    AVG(events_per_user) as avg_events_per_user
                FROM (
                    SELECT 
                        created_at,
                        user_id,
                        COUNT(*) as events_per_user
                    FROM engagement_events 
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                    GROUP BY DAYOFWEEK(created_at), user_id
                ) daily_stats
                GROUP BY DAYOFWEEK(created_at), DAYNAME(created_at)
                ORDER BY day_of_week
            ");
            
            $stmt->execute([$days]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get engagement by day of week: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get engagement heatmap
     */
    public function getEngagementHeatmap(int $days = 30): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    HOUR(created_at) as hour,
                    DAYOFWEEK(created_at) as day_of_week,
                    COUNT(*) as engagement_count,
                    COUNT(DISTINCT user_id) as unique_users
                FROM engagement_events 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY HOUR(created_at), DAYOFWEEK(created_at)
                ORDER BY day_of_week, hour
            ");
            
            $stmt->execute([$days]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get engagement heatmap: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get engagement correlation analysis
     */
    public function getEngagementCorrelationAnalysis(): array
    {
        try {
            $stmt = $this->db->query("
                SELECT 
                    event_type_1,
                    event_type_2,
                    COUNT(*) as co_occurrence_count,
                    AVG(time_difference_minutes) as avg_time_difference_minutes
                FROM (
                    SELECT 
                        e1.event_type as event_type_1,
                        e2.event_type as event_type_2,
                        TIMESTAMPDIFF(MINUTE, e1.created_at, e2.created_at) as time_difference_minutes
                    FROM engagement_events e1
                    JOIN engagement_events e2 ON e1.user_id = e2.user_id
                    WHERE e1.event_type != e2.event_type
                    AND ABS(TIMESTAMPDIFF(MINUTE, e1.created_at, e2.created_at)) <= 60
                ) correlation_data
                GROUP BY event_type_1, event_type_2
                ORDER BY co_occurrence_count DESC
                LIMIT 50
            ");
            
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get engagement correlation analysis: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get engagement prediction model
     */
    public function getEngagementPredictionModel(int $userId): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    HOUR(created_at) as preferred_hour,
                    DAYOFWEEK(created_at) as preferred_day,
                    event_type as preferred_event_type,
                    COUNT(*) as frequency,
                    AVG(TIMESTAMPDIFF(HOUR, LAG(created_at) OVER (ORDER BY created_at), created_at)) as avg_interval_hours
                FROM engagement_events 
                WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY HOUR(created_at), DAYOFWEEK(created_at), event_type
                ORDER BY frequency DESC
                LIMIT 10
            ");
            
            $stmt->execute([$userId]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get engagement prediction model: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Export engagement analytics data
     */
    public function exportEngagementAnalytics(array $filters = []): string
    {
        try {
            $whereClause = '';
            $params = [];
            
            if (!empty($filters['user_id'])) {
                $whereClause .= " AND user_id = ?";
                $params[] = $filters['user_id'];
            }
            
            if (!empty($filters['event_type'])) {
                $whereClause .= " AND event_type = ?";
                $params[] = $filters['event_type'];
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
                    event_type,
                    metadata,
                    ip_address,
                    user_agent,
                    created_at
                FROM engagement_events
                WHERE 1=1 {$whereClause}
                ORDER BY created_at DESC
            ");
            
            $stmt->execute($params);
            $data = $stmt->fetchAll();
            
            // Convert to CSV
            $csv = "User ID,Event Type,Metadata,IP Address,User Agent,Created At\n";
            foreach ($data as $row) {
                $csv .= implode(',', array_map(function($value) {
                    return '"' . str_replace('"', '""', $value) . '"';
                }, $row)) . "\n";
            }
            
            return $csv;
        } catch (\Exception $e) {
            $this->logger->error("Failed to export engagement analytics: " . $e->getMessage());
            return '';
        }
    }
}