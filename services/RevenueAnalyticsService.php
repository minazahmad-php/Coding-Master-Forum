<?php
declare(strict_types=1);

namespace Services;

use Core\Database;
use Core\Logger;

class RevenueAnalyticsService
{
    private Database $db;
    private Logger $logger;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->logger = new Logger();
    }

    /**
     * Track revenue event
     */
    public function trackRevenueEvent(int $userId, string $revenueType, float $amount, string $currency = 'USD', array $metadata = []): bool
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO revenue_events (user_id, revenue_type, amount, currency, metadata, ip_address, user_agent, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            return $stmt->execute([
                $userId,
                $revenueType,
                $amount,
                $currency,
                json_encode($metadata),
                $_SERVER['REMOTE_ADDR'] ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? '',
                date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            $this->logger->error("Failed to track revenue event: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get revenue overview
     */
    public function getRevenueOverview(int $days = 30): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    SUM(amount) as total_revenue,
                    COUNT(*) as total_transactions,
                    COUNT(DISTINCT user_id) as unique_customers,
                    AVG(amount) as avg_transaction_value,
                    MAX(amount) as max_transaction_value,
                    MIN(amount) as min_transaction_value,
                    SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN amount ELSE 0 END) as revenue_last_7_days,
                    SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY) THEN amount ELSE 0 END) as revenue_last_1_day
                FROM revenue_events 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            
            $stmt->execute([$days]);
            return $stmt->fetch() ?: [];
        } catch (\Exception $e) {
            $this->logger->error("Failed to get revenue overview: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get revenue by type
     */
    public function getRevenueByType(int $days = 30): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    revenue_type,
                    SUM(amount) as total_revenue,
                    COUNT(*) as transaction_count,
                    COUNT(DISTINCT user_id) as unique_customers,
                    AVG(amount) as avg_transaction_value,
                    MAX(amount) as max_transaction_value,
                    MIN(amount) as min_transaction_value
                FROM revenue_events 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY revenue_type
                ORDER BY total_revenue DESC
            ");
            
            $stmt->execute([$days]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get revenue by type: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get revenue trends
     */
    public function getRevenueTrends(int $days = 30): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    DATE(created_at) as date,
                    SUM(amount) as daily_revenue,
                    COUNT(*) as transaction_count,
                    COUNT(DISTINCT user_id) as unique_customers,
                    AVG(amount) as avg_transaction_value
                FROM revenue_events 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY DATE(created_at)
                ORDER BY date DESC
            ");
            
            $stmt->execute([$days]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get revenue trends: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get customer lifetime value
     */
    public function getCustomerLifetimeValue(): array
    {
        try {
            $stmt = $this->db->query("
                SELECT 
                    user_id,
                    COUNT(*) as total_transactions,
                    SUM(amount) as total_revenue,
                    AVG(amount) as avg_transaction_value,
                    MIN(created_at) as first_transaction,
                    MAX(created_at) as last_transaction,
                    TIMESTAMPDIFF(DAY, MIN(created_at), MAX(created_at)) as customer_lifespan_days
                FROM revenue_events
                GROUP BY user_id
                ORDER BY total_revenue DESC
            ");
            
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get customer lifetime value: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get revenue by customer segment
     */
    public function getRevenueByCustomerSegment(): array
    {
        try {
            $stmt = $this->db->query("
                SELECT 
                    CASE 
                        WHEN total_revenue >= 1000 THEN 'High Value'
                        WHEN total_revenue >= 100 THEN 'Medium Value'
                        WHEN total_revenue > 0 THEN 'Low Value'
                        ELSE 'No Revenue'
                    END as segment,
                    COUNT(*) as customer_count,
                    SUM(total_revenue) as total_revenue,
                    AVG(total_revenue) as avg_revenue_per_customer,
                    AVG(total_transactions) as avg_transactions_per_customer,
                    AVG(avg_transaction_value) as avg_transaction_value
                FROM (
                    SELECT 
                        user_id,
                        SUM(amount) as total_revenue,
                        COUNT(*) as total_transactions,
                        AVG(amount) as avg_transaction_value
                    FROM revenue_events
                    GROUP BY user_id
                ) customer_stats
                GROUP BY segment
                ORDER BY total_revenue DESC
            ");
            
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get revenue by customer segment: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get revenue by geographic location
     */
    public function getRevenueByGeographicLocation(int $days = 30): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    ig.country,
                    ig.region,
                    SUM(re.amount) as total_revenue,
                    COUNT(re.id) as transaction_count,
                    COUNT(DISTINCT re.user_id) as unique_customers,
                    AVG(re.amount) as avg_transaction_value
                FROM revenue_events re
                LEFT JOIN ip_geolocation ig ON re.ip_address = ig.ip_address
                WHERE re.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY ig.country, ig.region
                ORDER BY total_revenue DESC
                LIMIT 50
            ");
            
            $stmt->execute([$days]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get revenue by geographic location: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get revenue by device type
     */
    public function getRevenueByDeviceType(int $days = 30): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    ua.device_type,
                    ua.browser,
                    ua.os,
                    SUM(re.amount) as total_revenue,
                    COUNT(re.id) as transaction_count,
                    COUNT(DISTINCT re.user_id) as unique_customers,
                    AVG(re.amount) as avg_transaction_value
                FROM revenue_events re
                LEFT JOIN user_agents ua ON re.user_agent = ua.user_agent
                WHERE re.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY ua.device_type, ua.browser, ua.os
                ORDER BY total_revenue DESC
            ");
            
            $stmt->execute([$days]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get revenue by device type: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get revenue by traffic source
     */
    public function getRevenueByTrafficSource(int $days = 30): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    CASE 
                        WHEN pv.referrer = '' OR pv.referrer IS NULL THEN 'Direct'
                        WHEN pv.referrer LIKE '%google%' THEN 'Google'
                        WHEN pv.referrer LIKE '%facebook%' THEN 'Facebook'
                        WHEN pv.referrer LIKE '%twitter%' THEN 'Twitter'
                        WHEN pv.referrer LIKE '%linkedin%' THEN 'LinkedIn'
                        ELSE 'Other'
                    END as source,
                    SUM(re.amount) as total_revenue,
                    COUNT(re.id) as transaction_count,
                    COUNT(DISTINCT re.user_id) as unique_customers,
                    AVG(re.amount) as avg_transaction_value
                FROM revenue_events re
                LEFT JOIN page_views pv ON re.user_id = pv.user_id
                WHERE re.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY source
                ORDER BY total_revenue DESC
            ");
            
            $stmt->execute([$days]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get revenue by traffic source: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get revenue forecasting
     */
    public function getRevenueForecasting(int $days = 30): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    DATE(created_at) as date,
                    SUM(amount) as daily_revenue,
                    COUNT(*) as transaction_count,
                    COUNT(DISTINCT user_id) as unique_customers
                FROM revenue_events 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY DATE(created_at)
                ORDER BY date ASC
            ");
            
            $stmt->execute([$days]);
            $historicalData = $stmt->fetchAll();
            
            // Simple forecasting using moving average
            $forecast = [];
            $totalRevenue = 0;
            $totalDays = count($historicalData);
            
            foreach ($historicalData as $day) {
                $totalRevenue += $day['daily_revenue'];
            }
            
            $avgDailyRevenue = $totalDays > 0 ? $totalRevenue / $totalDays : 0;
            
            // Forecast next 7 days
            for ($i = 1; $i <= 7; $i++) {
                $forecastDate = date('Y-m-d', strtotime("+{$i} days"));
                $forecast[] = [
                    'date' => $forecastDate,
                    'forecasted_revenue' => $avgDailyRevenue,
                    'confidence' => max(0, 1 - ($i * 0.1)) // Decreasing confidence
                ];
            }
            
            return $forecast;
        } catch (\Exception $e) {
            $this->logger->error("Failed to get revenue forecasting: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get revenue optimization insights
     */
    public function getRevenueOptimizationInsights(): array
    {
        try {
            $stmt = $this->db->query("
                SELECT 
                    'Peak Hours' as insight_type,
                    HOUR(created_at) as hour,
                    SUM(amount) as total_revenue,
                    COUNT(*) as transaction_count,
                    AVG(amount) as avg_transaction_value
                FROM revenue_events
                GROUP BY HOUR(created_at)
                ORDER BY total_revenue DESC
                LIMIT 5
                UNION ALL
                SELECT 
                    'Peak Days' as insight_type,
                    DAYOFWEEK(created_at) as day_of_week,
                    SUM(amount) as total_revenue,
                    COUNT(*) as transaction_count,
                    AVG(amount) as avg_transaction_value
                FROM revenue_events
                GROUP BY DAYOFWEEK(created_at)
                ORDER BY total_revenue DESC
                LIMIT 5
            ");
            
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get revenue optimization insights: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get revenue by subscription plan
     */
    public function getRevenueBySubscriptionPlan(int $days = 30): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    sp.name as plan_name,
                    sp.price as plan_price,
                    COUNT(re.id) as subscription_count,
                    SUM(re.amount) as total_revenue,
                    AVG(re.amount) as avg_revenue_per_subscription,
                    COUNT(DISTINCT re.user_id) as unique_subscribers
                FROM revenue_events re
                LEFT JOIN subscription_plans sp ON re.metadata->>'$.plan_id' = sp.id
                WHERE re.revenue_type = 'subscription' 
                AND re.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY sp.id, sp.name, sp.price
                ORDER BY total_revenue DESC
            ");
            
            $stmt->execute([$days]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get revenue by subscription plan: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Export revenue analytics data
     */
    public function exportRevenueAnalytics(array $filters = []): string
    {
        try {
            $whereClause = '';
            $params = [];
            
            if (!empty($filters['revenue_type'])) {
                $whereClause .= " AND revenue_type = ?";
                $params[] = $filters['revenue_type'];
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
                    revenue_type,
                    amount,
                    currency,
                    metadata,
                    ip_address,
                    user_agent,
                    created_at
                FROM revenue_events
                WHERE 1=1 {$whereClause}
                ORDER BY created_at DESC
            ");
            
            $stmt->execute($params);
            $data = $stmt->fetchAll();
            
            // Convert to CSV
            $csv = "User ID,Revenue Type,Amount,Currency,Metadata,IP Address,User Agent,Created At\n";
            foreach ($data as $row) {
                $csv .= implode(',', array_map(function($value) {
                    return '"' . str_replace('"', '""', $value) . '"';
                }, $row)) . "\n";
            }
            
            return $csv;
        } catch (\Exception $e) {
            $this->logger->error("Failed to export revenue analytics: " . $e->getMessage());
            return '';
        }
    }
}