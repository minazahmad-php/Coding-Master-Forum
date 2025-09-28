<?php
declare(strict_types=1);

namespace Services;

use Core\Database;
use Core\Logger;

class TrafficAnalyticsService
{
    private Database $db;
    private Logger $logger;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->logger = new Logger();
    }

    /**
     * Track page view
     */
    public function trackPageView(string $page, string $referrer = '', int $userId = null): bool
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO page_views (page, referrer, user_id, ip_address, user_agent, session_id, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            return $stmt->execute([
                $page,
                $referrer,
                $userId,
                $_SERVER['REMOTE_ADDR'] ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? '',
                session_id(),
                date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            $this->logger->error("Failed to track page view: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Track session
     */
    public function trackSession(string $sessionId, int $userId = null): bool
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO sessions (session_id, user_id, ip_address, user_agent, referrer, created_at, last_activity)
                VALUES (?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE last_activity = VALUES(last_activity)
            ");
            
            return $stmt->execute([
                $sessionId,
                $userId,
                $_SERVER['REMOTE_ADDR'] ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? '',
                $_SERVER['HTTP_REFERER'] ?? '',
                date('Y-m-d H:i:s'),
                date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            $this->logger->error("Failed to track session: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get traffic overview
     */
    public function getTrafficOverview(int $days = 30): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_page_views,
                    COUNT(DISTINCT session_id) as unique_sessions,
                    COUNT(DISTINCT ip_address) as unique_visitors,
                    COUNT(DISTINCT user_id) as unique_users,
                    AVG(page_views_per_session) as avg_page_views_per_session,
                    AVG(session_duration_minutes) as avg_session_duration_minutes
                FROM (
                    SELECT 
                        session_id,
                        ip_address,
                        user_id,
                        COUNT(*) as page_views_per_session,
                        TIMESTAMPDIFF(MINUTE, MIN(created_at), MAX(created_at)) as session_duration_minutes
                    FROM page_views 
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                    GROUP BY session_id, ip_address, user_id
                ) session_stats
            ");
            
            $stmt->execute([$days]);
            return $stmt->fetch() ?: [];
        } catch (\Exception $e) {
            $this->logger->error("Failed to get traffic overview: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get page performance metrics
     */
    public function getPagePerformanceMetrics(int $days = 30): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    page,
                    COUNT(*) as page_views,
                    COUNT(DISTINCT session_id) as unique_sessions,
                    COUNT(DISTINCT user_id) as unique_users,
                    AVG(time_on_page_seconds) as avg_time_on_page_seconds,
                    COUNT(CASE WHEN referrer != '' THEN 1 END) as traffic_from_referrers,
                    COUNT(CASE WHEN referrer = '' THEN 1 END) as direct_traffic
                FROM page_views 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY page
                ORDER BY page_views DESC
            ");
            
            $stmt->execute([$days]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get page performance metrics: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get traffic sources
     */
    public function getTrafficSources(int $days = 30): array
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
                        WHEN referrer LIKE '%youtube%' THEN 'YouTube'
                        ELSE 'Other'
                    END as source,
                    COUNT(*) as visits,
                    COUNT(DISTINCT session_id) as unique_sessions,
                    COUNT(DISTINCT ip_address) as unique_visitors,
                    AVG(time_on_page_seconds) as avg_time_on_page_seconds
                FROM page_views 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY source
                ORDER BY visits DESC
            ");
            
            $stmt->execute([$days]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get traffic sources: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get geographic traffic data
     */
    public function getGeographicTrafficData(int $days = 30): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    country,
                    region,
                    city,
                    COUNT(*) as visits,
                    COUNT(DISTINCT ip_address) as unique_visitors,
                    COUNT(DISTINCT session_id) as unique_sessions
                FROM page_views pv
                LEFT JOIN ip_geolocation ig ON pv.ip_address = ig.ip_address
                WHERE pv.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY country, region, city
                ORDER BY visits DESC
                LIMIT 100
            ");
            
            $stmt->execute([$days]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get geographic traffic data: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get device and browser analytics
     */
    public function getDeviceBrowserAnalytics(int $days = 30): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    device_type,
                    browser,
                    os,
                    COUNT(*) as visits,
                    COUNT(DISTINCT session_id) as unique_sessions,
                    COUNT(DISTINCT ip_address) as unique_visitors,
                    AVG(time_on_page_seconds) as avg_time_on_page_seconds
                FROM page_views pv
                LEFT JOIN user_agents ua ON pv.user_agent = ua.user_agent
                WHERE pv.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY device_type, browser, os
                ORDER BY visits DESC
            ");
            
            $stmt->execute([$days]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get device browser analytics: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get traffic trends
     */
    public function getTrafficTrends(int $days = 30): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as page_views,
                    COUNT(DISTINCT session_id) as unique_sessions,
                    COUNT(DISTINCT ip_address) as unique_visitors,
                    COUNT(DISTINCT user_id) as unique_users,
                    AVG(time_on_page_seconds) as avg_time_on_page_seconds
                FROM page_views 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY DATE(created_at)
                ORDER BY date DESC
            ");
            
            $stmt->execute([$days]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get traffic trends: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get bounce rate analysis
     */
    public function getBounceRateAnalysis(int $days = 30): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    page,
                    COUNT(*) as total_sessions,
                    COUNT(CASE WHEN page_views_per_session = 1 THEN 1 END) as bounced_sessions,
                    AVG(CASE WHEN page_views_per_session = 1 THEN 1 ELSE 0 END) as bounce_rate,
                    AVG(time_on_page_seconds) as avg_time_on_page_seconds
                FROM (
                    SELECT 
                        page,
                        session_id,
                        COUNT(*) as page_views_per_session,
                        AVG(time_on_page_seconds) as time_on_page_seconds
                    FROM page_views 
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                    GROUP BY page, session_id
                ) session_stats
                GROUP BY page
                ORDER BY bounce_rate DESC
            ");
            
            $stmt->execute([$days]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get bounce rate analysis: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get conversion funnel
     */
    public function getConversionFunnel(): array
    {
        try {
            $stmt = $this->db->query("
                SELECT 
                    'Homepage' as step,
                    COUNT(DISTINCT session_id) as sessions
                FROM page_views WHERE page = '/'
                UNION ALL
                SELECT 
                    'Registration Page' as step,
                    COUNT(DISTINCT session_id) as sessions
                FROM page_views WHERE page = '/register'
                UNION ALL
                SELECT 
                    'Registered Users' as step,
                    COUNT(DISTINCT user_id) as sessions
                FROM users
                UNION ALL
                SELECT 
                    'First Post' as step,
                    COUNT(DISTINCT user_id) as sessions
                FROM users WHERE posts_count > 0
                UNION ALL
                SELECT 
                    'Active Users' as step,
                    COUNT(DISTINCT user_id) as sessions
                FROM users WHERE last_login >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            ");
            
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get conversion funnel: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get real-time traffic data
     */
    public function getRealTimeTrafficData(): array
    {
        try {
            $stmt = $this->db->query("
                SELECT 
                    COUNT(*) as active_users,
                    COUNT(DISTINCT session_id) as active_sessions,
                    COUNT(DISTINCT ip_address) as unique_ips
                FROM page_views 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
            ");
            
            return $stmt->fetch() ?: [];
        } catch (\Exception $e) {
            $this->logger->error("Failed to get real-time traffic data: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Export traffic analytics data
     */
    public function exportTrafficAnalytics(array $filters = []): string
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
                    page,
                    referrer,
                    user_id,
                    ip_address,
                    user_agent,
                    session_id,
                    created_at,
                    time_on_page_seconds
                FROM page_views
                WHERE 1=1 {$whereClause}
                ORDER BY created_at DESC
            ");
            
            $stmt->execute($params);
            $data = $stmt->fetchAll();
            
            // Convert to CSV
            $csv = "Page,Referrer,User ID,IP Address,User Agent,Session ID,Created At,Time on Page (seconds)\n";
            foreach ($data as $row) {
                $csv .= implode(',', array_map(function($value) {
                    return '"' . str_replace('"', '""', $value) . '"';
                }, $row)) . "\n";
            }
            
            return $csv;
        } catch (\Exception $e) {
            $this->logger->error("Failed to export traffic analytics: " . $e->getMessage());
            return '';
        }
    }
}