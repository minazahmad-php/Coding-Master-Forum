<?php
declare(strict_types=1);

namespace Services;

use Core\Database;
use Core\Logger;

class PerformanceAnalyticsService
{
    private Database $db;
    private Logger $logger;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->logger = new Logger();
    }

    /**
     * Track performance metric
     */
    public function trackPerformanceMetric(string $metricName, float $value, array $metadata = []): bool
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO performance_metrics (metric_name, value, metadata, created_at)
                VALUES (?, ?, ?, ?)
            ");
            
            return $stmt->execute([
                $metricName,
                $value,
                json_encode($metadata),
                date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            $this->logger->error("Failed to track performance metric: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get performance overview
     */
    public function getPerformanceOverview(int $days = 30): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    metric_name,
                    AVG(value) as avg_value,
                    MIN(value) as min_value,
                    MAX(value) as max_value,
                    STDDEV(value) as std_deviation,
                    COUNT(*) as measurement_count,
                    MAX(created_at) as last_measurement
                FROM performance_metrics 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY metric_name
                ORDER BY avg_value DESC
            ");
            
            $stmt->execute([$days]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get performance overview: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get page load times
     */
    public function getPageLoadTimes(int $days = 30): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    metadata->>'$.page' as page,
                    AVG(value) as avg_load_time,
                    MIN(value) as min_load_time,
                    MAX(value) as max_load_time,
                    COUNT(*) as measurement_count,
                    COUNT(CASE WHEN value > 3000 THEN 1 END) as slow_loads,
                    AVG(CASE WHEN value > 3000 THEN 1 ELSE 0 END) as slow_load_percentage
                FROM performance_metrics 
                WHERE metric_name = 'page_load_time' 
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY metadata->>'$.page'
                ORDER BY avg_load_time DESC
            ");
            
            $stmt->execute([$days]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get page load times: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get database performance metrics
     */
    public function getDatabasePerformanceMetrics(int $days = 30): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    metric_name,
                    AVG(value) as avg_value,
                    MIN(value) as min_value,
                    MAX(value) as max_value,
                    COUNT(*) as measurement_count,
                    COUNT(CASE WHEN value > 1000 THEN 1 END) as slow_queries,
                    AVG(CASE WHEN value > 1000 THEN 1 ELSE 0 END) as slow_query_percentage
                FROM performance_metrics 
                WHERE metric_name IN ('query_execution_time', 'database_connection_time', 'query_count')
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY metric_name
                ORDER BY avg_value DESC
            ");
            
            $stmt->execute([$days]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get database performance metrics: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get server performance metrics
     */
    public function getServerPerformanceMetrics(int $days = 30): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    metric_name,
                    AVG(value) as avg_value,
                    MIN(value) as min_value,
                    MAX(value) as max_value,
                    COUNT(*) as measurement_count,
                    COUNT(CASE WHEN value > 80 THEN 1 END) as high_usage_measurements,
                    AVG(CASE WHEN value > 80 THEN 1 ELSE 0 END) as high_usage_percentage
                FROM performance_metrics 
                WHERE metric_name IN ('cpu_usage', 'memory_usage', 'disk_usage', 'network_usage')
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY metric_name
                ORDER BY avg_value DESC
            ");
            
            $stmt->execute([$days]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get server performance metrics: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get performance trends
     */
    public function getPerformanceTrends(string $metricName, int $days = 30): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    DATE(created_at) as date,
                    AVG(value) as avg_value,
                    MIN(value) as min_value,
                    MAX(value) as max_value,
                    COUNT(*) as measurement_count
                FROM performance_metrics 
                WHERE metric_name = ? 
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY DATE(created_at)
                ORDER BY date DESC
            ");
            
            $stmt->execute([$metricName, $days]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get performance trends: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get performance alerts
     */
    public function getPerformanceAlerts(int $days = 7): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    metric_name,
                    value,
                    metadata,
                    created_at,
                    CASE 
                        WHEN metric_name = 'page_load_time' AND value > 5000 THEN 'Critical'
                        WHEN metric_name = 'page_load_time' AND value > 3000 THEN 'Warning'
                        WHEN metric_name = 'cpu_usage' AND value > 90 THEN 'Critical'
                        WHEN metric_name = 'cpu_usage' AND value > 80 THEN 'Warning'
                        WHEN metric_name = 'memory_usage' AND value > 90 THEN 'Critical'
                        WHEN metric_name = 'memory_usage' AND value > 80 THEN 'Warning'
                        WHEN metric_name = 'query_execution_time' AND value > 2000 THEN 'Critical'
                        WHEN metric_name = 'query_execution_time' AND value > 1000 THEN 'Warning'
                        ELSE 'Info'
                    END as alert_level
                FROM performance_metrics 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                AND (
                    (metric_name = 'page_load_time' AND value > 3000) OR
                    (metric_name = 'cpu_usage' AND value > 80) OR
                    (metric_name = 'memory_usage' AND value > 80) OR
                    (metric_name = 'query_execution_time' AND value > 1000)
                )
                ORDER BY created_at DESC
            ");
            
            $stmt->execute([$days]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get performance alerts: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get performance by time of day
     */
    public function getPerformanceByTimeOfDay(string $metricName, int $days = 30): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    HOUR(created_at) as hour,
                    AVG(value) as avg_value,
                    MIN(value) as min_value,
                    MAX(value) as max_value,
                    COUNT(*) as measurement_count
                FROM performance_metrics 
                WHERE metric_name = ? 
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY HOUR(created_at)
                ORDER BY hour
            ");
            
            $stmt->execute([$metricName, $days]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get performance by time of day: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get performance by day of week
     */
    public function getPerformanceByDayOfWeek(string $metricName, int $days = 30): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    DAYOFWEEK(created_at) as day_of_week,
                    DAYNAME(created_at) as day_name,
                    AVG(value) as avg_value,
                    MIN(value) as min_value,
                    MAX(value) as max_value,
                    COUNT(*) as measurement_count
                FROM performance_metrics 
                WHERE metric_name = ? 
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY DAYOFWEEK(created_at), DAYNAME(created_at)
                ORDER BY day_of_week
            ");
            
            $stmt->execute([$metricName, $days]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get performance by day of week: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get performance heatmap
     */
    public function getPerformanceHeatmap(string $metricName, int $days = 30): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    HOUR(created_at) as hour,
                    DAYOFWEEK(created_at) as day_of_week,
                    AVG(value) as avg_value,
                    COUNT(*) as measurement_count
                FROM performance_metrics 
                WHERE metric_name = ? 
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY HOUR(created_at), DAYOFWEEK(created_at)
                ORDER BY day_of_week, hour
            ");
            
            $stmt->execute([$metricName, $days]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get performance heatmap: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get performance optimization recommendations
     */
    public function getPerformanceOptimizationRecommendations(): array
    {
        try {
            $recommendations = [];
            
            // Check page load times
            $stmt = $this->db->query("
                SELECT 
                    metadata->>'$.page' as page,
                    AVG(value) as avg_load_time
                FROM performance_metrics 
                WHERE metric_name = 'page_load_time' 
                AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY metadata->>'$.page'
                HAVING avg_load_time > 3000
                ORDER BY avg_load_time DESC
            ");
            
            $slowPages = $stmt->fetchAll();
            foreach ($slowPages as $page) {
                $recommendations[] = [
                    'type' => 'page_optimization',
                    'priority' => 'high',
                    'message' => "Page '{$page['page']}' has average load time of " . round($page['avg_load_time']) . "ms. Consider optimizing images, reducing JavaScript, or implementing caching.",
                    'metric' => 'page_load_time',
                    'value' => $page['avg_load_time']
                ];
            }
            
            // Check database performance
            $stmt = $this->db->query("
                SELECT 
                    AVG(value) as avg_query_time
                FROM performance_metrics 
                WHERE metric_name = 'query_execution_time' 
                AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            ");
            
            $avgQueryTime = $stmt->fetch()['avg_query_time'] ?? 0;
            if ($avgQueryTime > 1000) {
                $recommendations[] = [
                    'type' => 'database_optimization',
                    'priority' => 'high',
                    'message' => "Average query execution time is " . round($avgQueryTime) . "ms. Consider adding database indexes or optimizing queries.",
                    'metric' => 'query_execution_time',
                    'value' => $avgQueryTime
                ];
            }
            
            // Check server resources
            $stmt = $this->db->query("
                SELECT 
                    metric_name,
                    AVG(value) as avg_usage
                FROM performance_metrics 
                WHERE metric_name IN ('cpu_usage', 'memory_usage', 'disk_usage')
                AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY metric_name
                HAVING avg_usage > 80
            ");
            
            $highUsage = $stmt->fetchAll();
            foreach ($highUsage as $usage) {
                $recommendations[] = [
                    'type' => 'server_optimization',
                    'priority' => 'medium',
                    'message' => "High {$usage['metric_name']}: " . round($usage['avg_usage']) . "%. Consider upgrading server resources or optimizing application.",
                    'metric' => $usage['metric_name'],
                    'value' => $usage['avg_usage']
                ];
            }
            
            return $recommendations;
        } catch (\Exception $e) {
            $this->logger->error("Failed to get performance optimization recommendations: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get performance benchmarks
     */
    public function getPerformanceBenchmarks(): array
    {
        try {
            $stmt = $this->db->query("
                SELECT 
                    metric_name,
                    AVG(value) as current_avg,
                    MIN(value) as current_min,
                    MAX(value) as current_max,
                    COUNT(*) as measurement_count
                FROM performance_metrics 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY metric_name
            ");
            
            $currentMetrics = $stmt->fetchAll();
            
            $benchmarks = [];
            foreach ($currentMetrics as $metric) {
                $benchmarks[] = [
                    'metric_name' => $metric['metric_name'],
                    'current_avg' => $metric['current_avg'],
                    'current_min' => $metric['current_min'],
                    'current_max' => $metric['current_max'],
                    'measurement_count' => $metric['measurement_count'],
                    'benchmark' => $this->getBenchmarkValue($metric['metric_name']),
                    'status' => $this->getBenchmarkStatus($metric['metric_name'], $metric['current_avg'])
                ];
            }
            
            return $benchmarks;
        } catch (\Exception $e) {
            $this->logger->error("Failed to get performance benchmarks: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get benchmark value for a metric
     */
    private function getBenchmarkValue(string $metricName): float
    {
        $benchmarks = [
            'page_load_time' => 2000, // 2 seconds
            'query_execution_time' => 500, // 500ms
            'cpu_usage' => 70, // 70%
            'memory_usage' => 80, // 80%
            'disk_usage' => 85, // 85%
            'network_usage' => 60, // 60%
        ];
        
        return $benchmarks[$metricName] ?? 0;
    }

    /**
     * Get benchmark status
     */
    private function getBenchmarkStatus(string $metricName, float $currentValue): string
    {
        $benchmark = $this->getBenchmarkValue($metricName);
        
        if ($benchmark === 0) return 'unknown';
        
        $ratio = $currentValue / $benchmark;
        
        if ($ratio <= 0.8) return 'excellent';
        if ($ratio <= 1.0) return 'good';
        if ($ratio <= 1.2) return 'acceptable';
        return 'poor';
    }

    /**
     * Export performance analytics data
     */
    public function exportPerformanceAnalytics(array $filters = []): string
    {
        try {
            $whereClause = '';
            $params = [];
            
            if (!empty($filters['metric_name'])) {
                $whereClause .= " AND metric_name = ?";
                $params[] = $filters['metric_name'];
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
                    metric_name,
                    value,
                    metadata,
                    created_at
                FROM performance_metrics
                WHERE 1=1 {$whereClause}
                ORDER BY created_at DESC
            ");
            
            $stmt->execute($params);
            $data = $stmt->fetchAll();
            
            // Convert to CSV
            $csv = "Metric Name,Value,Metadata,Created At\n";
            foreach ($data as $row) {
                $csv .= implode(',', array_map(function($value) {
                    return '"' . str_replace('"', '""', $value) . '"';
                }, $row)) . "\n";
            }
            
            return $csv;
        } catch (\Exception $e) {
            $this->logger->error("Failed to export performance analytics: " . $e->getMessage());
            return '';
        }
    }
}