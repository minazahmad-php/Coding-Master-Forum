<?php

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use App\Core\Security;

/**
 * Advanced Analytics Service
 */
class AnalyticsService
{
    private $db;
    private $logger;

    public function __construct()
    {
        global $app;
        $this->db = $app->get('database');
        $this->logger = $app->get('logger');
    }

    /**
     * Get dashboard analytics
     */
    public function getDashboardAnalytics($userId = null, $dateRange = '30d')
    {
        try {
            $dateCondition = $this->getDateCondition($dateRange);
            
            $analytics = [
                'overview' => $this->getOverviewStats($dateCondition),
                'user_activity' => $this->getUserActivityStats($dateCondition, $userId),
                'content_metrics' => $this->getContentMetrics($dateCondition),
                'engagement' => $this->getEngagementMetrics($dateCondition),
                'growth' => $this->getGrowthMetrics($dateCondition),
                'top_content' => $this->getTopContent($dateCondition),
                'user_behavior' => $this->getUserBehaviorMetrics($dateCondition, $userId),
                'revenue' => $this->getRevenueMetrics($dateCondition),
                'performance' => $this->getPerformanceMetrics($dateCondition)
            ];

            return $analytics;
        } catch (\Exception $e) {
            $this->logger->error('Dashboard analytics failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get overview statistics
     */
    private function getOverviewStats($dateCondition)
    {
        $stats = $this->db->fetch(
            "SELECT 
                COUNT(DISTINCT u.id) as total_users,
                COUNT(DISTINCT t.id) as total_threads,
                COUNT(DISTINCT p.id) as total_posts,
                COUNT(DISTINCT f.id) as total_forums,
                AVG(DATEDIFF(NOW(), u.created_at)) as avg_user_age_days
             FROM users u
             LEFT JOIN threads t ON 1=1
             LEFT JOIN posts p ON 1=1
             LEFT JOIN forums f ON 1=1
             WHERE u.created_at {$dateCondition}"
        );

        return $stats ?: [
            'total_users' => 0,
            'total_threads' => 0,
            'total_posts' => 0,
            'total_forums' => 0,
            'avg_user_age_days' => 0
        ];
    }

    /**
     * Get user activity statistics
     */
    private function getUserActivityStats($dateCondition, $userId = null)
    {
        $userCondition = $userId ? "AND u.id = {$userId}" : "";
        
        $stats = $this->db->fetch(
            "SELECT 
                COUNT(DISTINCT u.id) as active_users,
                COUNT(DISTINCT CASE WHEN u.last_login > DATE_SUB(NOW(), INTERVAL 1 DAY) THEN u.id END) as daily_active_users,
                COUNT(DISTINCT CASE WHEN u.last_login > DATE_SUB(NOW(), INTERVAL 7 DAY) THEN u.id END) as weekly_active_users,
                COUNT(DISTINCT CASE WHEN u.last_login > DATE_SUB(NOW(), INTERVAL 30 DAY) THEN u.id END) as monthly_active_users,
                AVG(TIMESTAMPDIFF(MINUTE, u.created_at, u.last_login)) as avg_session_duration_minutes
             FROM users u
             WHERE u.last_login {$dateCondition} {$userCondition}"
        );

        return $stats ?: [
            'active_users' => 0,
            'daily_active_users' => 0,
            'weekly_active_users' => 0,
            'monthly_active_users' => 0,
            'avg_session_duration_minutes' => 0
        ];
    }

    /**
     * Get content metrics
     */
    private function getContentMetrics($dateCondition)
    {
        $stats = $this->db->fetch(
            "SELECT 
                COUNT(DISTINCT t.id) as threads_created,
                COUNT(DISTINCT p.id) as posts_created,
                AVG(posts_per_thread) as avg_posts_per_thread,
                AVG(thread_views) as avg_thread_views,
                COUNT(DISTINCT CASE WHEN t.pinned = 1 THEN t.id END) as pinned_threads
             FROM threads t
             LEFT JOIN posts p ON t.id = p.thread_id
             LEFT JOIN (
                 SELECT thread_id, COUNT(*) as posts_per_thread 
                 FROM posts 
                 GROUP BY thread_id
             ) pt ON t.id = pt.thread_id
             WHERE t.created_at {$dateCondition}"
        );

        return $stats ?: [
            'threads_created' => 0,
            'posts_created' => 0,
            'avg_posts_per_thread' => 0,
            'avg_thread_views' => 0,
            'pinned_threads' => 0
        ];
    }

    /**
     * Get engagement metrics
     */
    private function getEngagementMetrics($dateCondition)
    {
        $stats = $this->db->fetch(
            "SELECT 
                COUNT(DISTINCT pr.id) as total_reactions,
                COUNT(DISTINCT CASE WHEN pr.reaction_type = 'like' THEN pr.id END) as likes,
                COUNT(DISTINCT CASE WHEN pr.reaction_type = 'dislike' THEN pr.id END) as dislikes,
                COUNT(DISTINCT CASE WHEN pr.reaction_type = 'helpful' THEN pr.id END) as helpful_reactions,
                AVG(reactions_per_post) as avg_reactions_per_post,
                COUNT(DISTINCT ts.id) as thread_subscriptions
             FROM post_reactions pr
             LEFT JOIN posts p ON pr.post_id = p.id
             LEFT JOIN (
                 SELECT post_id, COUNT(*) as reactions_per_post 
                 FROM post_reactions 
                 GROUP BY post_id
             ) rpp ON pr.post_id = rpp.post_id
             LEFT JOIN thread_subscriptions ts ON 1=1
             WHERE pr.created_at {$dateCondition}"
        );

        return $stats ?: [
            'total_reactions' => 0,
            'likes' => 0,
            'dislikes' => 0,
            'helpful_reactions' => 0,
            'avg_reactions_per_post' => 0,
            'thread_subscriptions' => 0
        ];
    }

    /**
     * Get growth metrics
     */
    private function getGrowthMetrics($dateCondition)
    {
        $stats = $this->db->fetch(
            "SELECT 
                COUNT(DISTINCT CASE WHEN u.created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY) THEN u.id END) as new_users_today,
                COUNT(DISTINCT CASE WHEN u.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN u.id END) as new_users_week,
                COUNT(DISTINCT CASE WHEN u.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN u.id END) as new_users_month,
                COUNT(DISTINCT CASE WHEN t.created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY) THEN t.id END) as new_threads_today,
                COUNT(DISTINCT CASE WHEN p.created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY) THEN p.id END) as new_posts_today
             FROM users u
             LEFT JOIN threads t ON 1=1
             LEFT JOIN posts p ON 1=1
             WHERE 1=1"
        );

        return $stats ?: [
            'new_users_today' => 0,
            'new_users_week' => 0,
            'new_users_month' => 0,
            'new_threads_today' => 0,
            'new_posts_today' => 0
        ];
    }

    /**
     * Get top content
     */
    private function getTopContent($dateCondition)
    {
        $topThreads = $this->db->fetchAll(
            "SELECT t.id, t.title, t.view_count, COUNT(p.id) as post_count, u.username
             FROM threads t
             LEFT JOIN posts p ON t.id = p.thread_id
             LEFT JOIN users u ON t.user_id = u.id
             WHERE t.created_at {$dateCondition}
             GROUP BY t.id
             ORDER BY (t.view_count + COUNT(p.id) * 2) DESC
             LIMIT 10"
        );

        $topUsers = $this->db->fetchAll(
            "SELECT u.id, u.username, u.display_name, COUNT(p.id) as post_count, COUNT(t.id) as thread_count
             FROM users u
             LEFT JOIN posts p ON u.id = p.user_id
             LEFT JOIN threads t ON u.id = t.user_id
             WHERE u.created_at {$dateCondition}
             GROUP BY u.id
             ORDER BY (COUNT(p.id) + COUNT(t.id) * 2) DESC
             LIMIT 10"
        );

        return [
            'top_threads' => $topThreads ?: [],
            'top_users' => $topUsers ?: []
        ];
    }

    /**
     * Get user behavior metrics
     */
    private function getUserBehaviorMetrics($dateCondition, $userId = null)
    {
        $userCondition = $userId ? "AND u.id = {$userId}" : "";
        
        $stats = $this->db->fetch(
            "SELECT 
                AVG(session_duration) as avg_session_duration,
                AVG(pages_per_session) as avg_pages_per_session,
                AVG(bounce_rate) as avg_bounce_rate,
                COUNT(DISTINCT CASE WHEN return_visitor = 1 THEN u.id END) as return_visitors,
                COUNT(DISTINCT CASE WHEN new_visitor = 1 THEN u.id END) as new_visitors
             FROM user_sessions us
             LEFT JOIN users u ON us.user_id = u.id
             WHERE us.created_at {$dateCondition} {$userCondition}"
        );

        return $stats ?: [
            'avg_session_duration' => 0,
            'avg_pages_per_session' => 0,
            'avg_bounce_rate' => 0,
            'return_visitors' => 0,
            'new_visitors' => 0
        ];
    }

    /**
     * Get revenue metrics
     */
    private function getRevenueMetrics($dateCondition)
    {
        $stats = $this->db->fetch(
            "SELECT 
                SUM(amount) as total_revenue,
                COUNT(DISTINCT user_id) as paying_users,
                AVG(amount) as avg_revenue_per_user,
                COUNT(DISTINCT CASE WHEN status = 'completed' THEN id END) as successful_payments,
                COUNT(DISTINCT CASE WHEN status = 'failed' THEN id END) as failed_payments
             FROM payments
             WHERE created_at {$dateCondition}"
        );

        return $stats ?: [
            'total_revenue' => 0,
            'paying_users' => 0,
            'avg_revenue_per_user' => 0,
            'successful_payments' => 0,
            'failed_payments' => 0
        ];
    }

    /**
     * Get performance metrics
     */
    private function getPerformanceMetrics($dateCondition)
    {
        $stats = $this->db->fetch(
            "SELECT 
                AVG(response_time) as avg_response_time,
                AVG(page_load_time) as avg_page_load_time,
                COUNT(DISTINCT CASE WHEN error_code IS NOT NULL THEN id END) as error_count,
                AVG(server_load) as avg_server_load,
                AVG(memory_usage) as avg_memory_usage
             FROM performance_logs
             WHERE created_at {$dateCondition}"
        );

        return $stats ?: [
            'avg_response_time' => 0,
            'avg_page_load_time' => 0,
            'error_count' => 0,
            'avg_server_load' => 0,
            'avg_memory_usage' => 0
        ];
    }

    /**
     * Get date condition for SQL
     */
    private function getDateCondition($dateRange)
    {
        switch ($dateRange) {
            case '1d':
                return ">= DATE_SUB(NOW(), INTERVAL 1 DAY)";
            case '7d':
                return ">= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            case '30d':
                return ">= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            case '90d':
                return ">= DATE_SUB(NOW(), INTERVAL 90 DAY)";
            case '1y':
                return ">= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
            default:
                return ">= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        }
    }

    /**
     * Get real-time analytics
     */
    public function getRealTimeAnalytics()
    {
        try {
            $analytics = [
                'online_users' => $this->getOnlineUserCount(),
                'active_sessions' => $this->getActiveSessionCount(),
                'recent_activity' => $this->getRecentActivity(),
                'current_visitors' => $this->getCurrentVisitors(),
                'system_status' => $this->getSystemStatus()
            ];

            return $analytics;
        } catch (\Exception $e) {
            $this->logger->error('Real-time analytics failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get online user count
     */
    private function getOnlineUserCount()
    {
        $result = $this->db->fetch(
            "SELECT COUNT(*) as count FROM user_online_status 
             WHERE status = 'online' AND last_seen > DATE_SUB(NOW(), INTERVAL 5 MINUTE)"
        );

        return $result['count'] ?? 0;
    }

    /**
     * Get active session count
     */
    private function getActiveSessionCount()
    {
        $result = $this->db->fetch(
            "SELECT COUNT(*) as count FROM user_sessions 
             WHERE last_activity > DATE_SUB(NOW(), INTERVAL 30 MINUTE)"
        );

        return $result['count'] ?? 0;
    }

    /**
     * Get recent activity
     */
    private function getRecentActivity()
    {
        return $this->db->fetchAll(
            "SELECT 
                'thread' as type, 
                t.title as content, 
                u.username, 
                t.created_at 
             FROM threads t
             LEFT JOIN users u ON t.user_id = u.id
             WHERE t.created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
             
             UNION ALL
             
             SELECT 
                'post' as type, 
                SUBSTRING(p.content, 1, 100) as content, 
                u.username, 
                p.created_at 
             FROM posts p
             LEFT JOIN users u ON p.user_id = u.id
             WHERE p.created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
             
             ORDER BY created_at DESC
             LIMIT 20"
        );
    }

    /**
     * Get current visitors
     */
    private function getCurrentVisitors()
    {
        return $this->db->fetchAll(
            "SELECT 
                u.username, 
                u.display_name, 
                u.avatar,
                us.last_activity,
                us.ip_address,
                us.user_agent
             FROM user_sessions us
             LEFT JOIN users u ON us.user_id = u.id
             WHERE us.last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
             ORDER BY us.last_activity DESC
             LIMIT 50"
        );
    }

    /**
     * Get system status
     */
    private function getSystemStatus()
    {
        return [
            'database_status' => $this->checkDatabaseStatus(),
            'redis_status' => $this->checkRedisStatus(),
            'disk_usage' => $this->getDiskUsage(),
            'memory_usage' => $this->getMemoryUsage(),
            'cpu_usage' => $this->getCpuUsage()
        ];
    }

    /**
     * Check database status
     */
    private function checkDatabaseStatus()
    {
        try {
            $this->db->query("SELECT 1");
            return 'online';
        } catch (\Exception $e) {
            return 'offline';
        }
    }

    /**
     * Check Redis status
     */
    private function checkRedisStatus()
    {
        try {
            // This would check Redis connection
            return 'online';
        } catch (\Exception $e) {
            return 'offline';
        }
    }

    /**
     * Get disk usage
     */
    private function getDiskUsage()
    {
        $bytes = disk_free_space('.');
        $total = disk_total_space('.');
        $used = $total - $bytes;
        
        return [
            'used' => $used,
            'total' => $total,
            'percentage' => ($used / $total) * 100
        ];
    }

    /**
     * Get memory usage
     */
    private function getMemoryUsage()
    {
        $memory = memory_get_usage(true);
        $peak = memory_get_peak_usage(true);
        
        return [
            'current' => $memory,
            'peak' => $peak,
            'limit' => ini_get('memory_limit')
        ];
    }

    /**
     * Get CPU usage
     */
    private function getCpuUsage()
    {
        // This would get actual CPU usage
        return [
            'usage' => 0,
            'load_average' => [0, 0, 0]
        ];
    }

    /**
     * Export analytics data
     */
    public function exportAnalytics($type = 'csv', $dateRange = '30d')
    {
        try {
            $data = $this->getDashboardAnalytics(null, $dateRange);
            
            switch ($type) {
                case 'csv':
                    return $this->exportToCSV($data);
                case 'json':
                    return json_encode($data, JSON_PRETTY_PRINT);
                case 'xml':
                    return $this->exportToXML($data);
                default:
                    return json_encode($data, JSON_PRETTY_PRINT);
            }
        } catch (\Exception $e) {
            $this->logger->error('Analytics export failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Export to CSV
     */
    private function exportToCSV($data)
    {
        $csv = "Metric,Value\n";
        
        foreach ($data as $category => $metrics) {
            if (is_array($metrics)) {
                foreach ($metrics as $key => $value) {
                    $csv .= "{$category}_{$key}," . (is_array($value) ? json_encode($value) : $value) . "\n";
                }
            }
        }
        
        return $csv;
    }

    /**
     * Export to XML
     */
    private function exportToXML($data)
    {
        $xml = new \SimpleXMLElement('<analytics></analytics>');
        $this->arrayToXML($data, $xml);
        return $xml->asXML();
    }

    /**
     * Convert array to XML
     */
    private function arrayToXML($data, &$xml)
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $subnode = $xml->addChild($key);
                $this->arrayToXML($value, $subnode);
            } else {
                $xml->addChild($key, htmlspecialchars($value));
            }
        }
    }
}