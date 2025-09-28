<?php
declare(strict_types=1);

namespace Services;

class PerformanceMonitoringService {
    private Database $db;
    private array $metrics;
    private array $thresholds;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->metrics = [];
        $this->thresholds = $this->getThresholds();
    }
    
    private function getThresholds(): array {
        return [
            'response_time' => [
                'warning' => 1.0, // seconds
                'critical' => 3.0
            ],
            'memory_usage' => [
                'warning' => 80, // percentage
                'critical' => 95
            ],
            'cpu_usage' => [
                'warning' => 80, // percentage
                'critical' => 95
            ],
            'disk_usage' => [
                'warning' => 80, // percentage
                'critical' => 95
            ],
            'database_connections' => [
                'warning' => 80, // percentage of max
                'critical' => 95
            ],
            'error_rate' => [
                'warning' => 5, // percentage
                'critical' => 10
            ],
            'query_time' => [
                'warning' => 1.0, // seconds
                'critical' => 3.0
            ]
        ];
    }
    
    public function startMonitoring(): void {
        $this->metrics['start_time'] = microtime(true);
        $this->metrics['start_memory'] = memory_get_usage();
        $this->metrics['start_peak_memory'] = memory_get_peak_usage();
    }
    
    public function endMonitoring(): array {
        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        $endPeakMemory = memory_get_peak_usage();
        
        $metrics = [
            'response_time' => $endTime - $this->metrics['start_time'],
            'memory_usage' => $endMemory - $this->metrics['start_memory'],
            'peak_memory' => $endPeakMemory,
            'end_time' => $endTime,
            'end_memory' => $endMemory
        ];
        
        // Log metrics
        $this->logMetrics($metrics);
        
        // Check thresholds
        $alerts = $this->checkThresholds($metrics);
        
        return [
            'metrics' => $metrics,
            'alerts' => $alerts,
            'status' => $this->getOverallStatus($alerts)
        ];
    }
    
    private function logMetrics(array $metrics): void {
        try {
            $this->db->insert('performance_metrics', [
                'response_time' => $metrics['response_time'],
                'memory_usage' => $metrics['memory_usage'],
                'peak_memory' => $metrics['peak_memory'],
                'cpu_usage' => $this->getCpuUsage(),
                'disk_usage' => $this->getDiskUsage(),
                'database_connections' => $this->getDatabaseConnections(),
                'error_rate' => $this->getErrorRate(),
                'query_time' => $this->getAverageQueryTime(),
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            error_log("Error logging performance metrics: " . $e->getMessage());
        }
    }
    
    private function checkThresholds(array $metrics): array {
        $alerts = [];
        
        // Check response time
        if ($metrics['response_time'] > $this->thresholds['response_time']['critical']) {
            $alerts[] = [
                'type' => 'critical',
                'metric' => 'response_time',
                'value' => $metrics['response_time'],
                'threshold' => $this->thresholds['response_time']['critical'],
                'message' => 'Response time is critically high'
            ];
        } elseif ($metrics['response_time'] > $this->thresholds['response_time']['warning']) {
            $alerts[] = [
                'type' => 'warning',
                'metric' => 'response_time',
                'value' => $metrics['response_time'],
                'threshold' => $this->thresholds['response_time']['warning'],
                'message' => 'Response time is high'
            ];
        }
        
        // Check memory usage
        $memoryUsagePercent = ($metrics['peak_memory'] / $this->getMemoryLimit()) * 100;
        if ($memoryUsagePercent > $this->thresholds['memory_usage']['critical']) {
            $alerts[] = [
                'type' => 'critical',
                'metric' => 'memory_usage',
                'value' => $memoryUsagePercent,
                'threshold' => $this->thresholds['memory_usage']['critical'],
                'message' => 'Memory usage is critically high'
            ];
        } elseif ($memoryUsagePercent > $this->thresholds['memory_usage']['warning']) {
            $alerts[] = [
                'type' => 'warning',
                'metric' => 'memory_usage',
                'value' => $memoryUsagePercent,
                'threshold' => $this->thresholds['memory_usage']['warning'],
                'message' => 'Memory usage is high'
            ];
        }
        
        // Check CPU usage
        $cpuUsage = $this->getCpuUsage();
        if ($cpuUsage > $this->thresholds['cpu_usage']['critical']) {
            $alerts[] = [
                'type' => 'critical',
                'metric' => 'cpu_usage',
                'value' => $cpuUsage,
                'threshold' => $this->thresholds['cpu_usage']['critical'],
                'message' => 'CPU usage is critically high'
            ];
        } elseif ($cpuUsage > $this->thresholds['cpu_usage']['warning']) {
            $alerts[] = [
                'type' => 'warning',
                'metric' => 'cpu_usage',
                'value' => $cpuUsage,
                'threshold' => $this->thresholds['cpu_usage']['warning'],
                'message' => 'CPU usage is high'
            ];
        }
        
        // Check disk usage
        $diskUsage = $this->getDiskUsage();
        if ($diskUsage > $this->thresholds['disk_usage']['critical']) {
            $alerts[] = [
                'type' => 'critical',
                'metric' => 'disk_usage',
                'value' => $diskUsage,
                'threshold' => $this->thresholds['disk_usage']['critical'],
                'message' => 'Disk usage is critically high'
            ];
        } elseif ($diskUsage > $this->thresholds['disk_usage']['warning']) {
            $alerts[] = [
                'type' => 'warning',
                'metric' => 'disk_usage',
                'value' => $diskUsage,
                'threshold' => $this->thresholds['disk_usage']['warning'],
                'message' => 'Disk usage is high'
            ];
        }
        
        // Check database connections
        $dbConnections = $this->getDatabaseConnections();
        if ($dbConnections > $this->thresholds['database_connections']['critical']) {
            $alerts[] = [
                'type' => 'critical',
                'metric' => 'database_connections',
                'value' => $dbConnections,
                'threshold' => $this->thresholds['database_connections']['critical'],
                'message' => 'Database connections are critically high'
            ];
        } elseif ($dbConnections > $this->thresholds['database_connections']['warning']) {
            $alerts[] = [
                'type' => 'warning',
                'metric' => 'database_connections',
                'value' => $dbConnections,
                'threshold' => $this->thresholds['database_connections']['warning'],
                'message' => 'Database connections are high'
            ];
        }
        
        // Check error rate
        $errorRate = $this->getErrorRate();
        if ($errorRate > $this->thresholds['error_rate']['critical']) {
            $alerts[] = [
                'type' => 'critical',
                'metric' => 'error_rate',
                'value' => $errorRate,
                'threshold' => $this->thresholds['error_rate']['critical'],
                'message' => 'Error rate is critically high'
            ];
        } elseif ($errorRate > $this->thresholds['error_rate']['warning']) {
            $alerts[] = [
                'type' => 'warning',
                'metric' => 'error_rate',
                'value' => $errorRate,
                'threshold' => $this->thresholds['error_rate']['warning'],
                'message' => 'Error rate is high'
            ];
        }
        
        // Check query time
        $queryTime = $this->getAverageQueryTime();
        if ($queryTime > $this->thresholds['query_time']['critical']) {
            $alerts[] = [
                'type' => 'critical',
                'metric' => 'query_time',
                'value' => $queryTime,
                'threshold' => $this->thresholds['query_time']['critical'],
                'message' => 'Query time is critically high'
            ];
        } elseif ($queryTime > $this->thresholds['query_time']['warning']) {
            $alerts[] = [
                'type' => 'warning',
                'metric' => 'query_time',
                'value' => $queryTime,
                'threshold' => $this->thresholds['query_time']['warning'],
                'message' => 'Query time is high'
            ];
        }
        
        return $alerts;
    }
    
    private function getOverallStatus(array $alerts): string {
        $criticalCount = count(array_filter($alerts, function($alert) {
            return $alert['type'] === 'critical';
        }));
        
        $warningCount = count(array_filter($alerts, function($alert) {
            return $alert['type'] === 'warning';
        }));
        
        if ($criticalCount > 0) {
            return 'critical';
        } elseif ($warningCount > 0) {
            return 'warning';
        } else {
            return 'healthy';
        }
    }
    
    private function getCpuUsage(): float {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            return round($load[0] * 100, 2);
        }
        
        return 0;
    }
    
    private function getDiskUsage(): float {
        $total = disk_total_space('/');
        $free = disk_free_space('/');
        $used = $total - $free;
        
        return round(($used / $total) * 100, 2);
    }
    
    private function getDatabaseConnections(): int {
        try {
            $connections = $this->db->fetchColumn("SELECT COUNT(*) FROM sqlite_master WHERE type='table'");
            return $connections;
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    private function getErrorRate(): float {
        try {
            $totalRequests = $this->db->fetchColumn(
                "SELECT COUNT(*) FROM performance_metrics WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)"
            );
            
            $errors = $this->db->fetchColumn(
                "SELECT COUNT(*) FROM error_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)"
            );
            
            return $totalRequests > 0 ? round(($errors / $totalRequests) * 100, 2) : 0;
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    private function getAverageQueryTime(): float {
        try {
            $avgTime = $this->db->fetchColumn(
                "SELECT AVG(query_time) FROM performance_metrics WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)"
            );
            
            return round($avgTime, 4);
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    private function getMemoryLimit(): int {
        $limit = ini_get('memory_limit');
        if ($limit === '-1') {
            return PHP_INT_MAX;
        }
        
        return $this->parseMemoryLimit($limit);
    }
    
    private function parseMemoryLimit(string $limit): int {
        $unit = strtolower(substr($limit, -1));
        $value = (int) $limit;
        
        switch ($unit) {
            case 'g':
                return $value * 1024 * 1024 * 1024;
            case 'm':
                return $value * 1024 * 1024;
            case 'k':
                return $value * 1024;
            default:
                return $value;
        }
    }
    
    public function getPerformanceStats(string $period = 'hour'): array {
        $interval = $this->getInterval($period);
        
        return [
            'average_response_time' => $this->getAverageResponseTime($interval),
            'average_memory_usage' => $this->getAverageMemoryUsage($interval),
            'average_cpu_usage' => $this->getAverageCpuUsage($interval),
            'average_disk_usage' => $this->getAverageDiskUsage($interval),
            'total_requests' => $this->getTotalRequests($interval),
            'error_count' => $this->getErrorCount($interval),
            'slow_queries' => $this->getSlowQueries($interval),
            'peak_memory' => $this->getPeakMemory($interval),
            'peak_cpu' => $this->getPeakCpu($interval)
        ];
    }
    
    private function getInterval(string $period): string {
        switch ($period) {
            case 'minute':
                return '1 MINUTE';
            case 'hour':
                return '1 HOUR';
            case 'day':
                return '1 DAY';
            case 'week':
                return '1 WEEK';
            case 'month':
                return '1 MONTH';
            default:
                return '1 HOUR';
        }
    }
    
    private function getAverageResponseTime(string $interval): float {
        try {
            $avgTime = $this->db->fetchColumn(
                "SELECT AVG(response_time) FROM performance_metrics WHERE created_at >= DATE_SUB(NOW(), INTERVAL {$interval})"
            );
            
            return round($avgTime, 4);
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    private function getAverageMemoryUsage(string $interval): float {
        try {
            $avgMemory = $this->db->fetchColumn(
                "SELECT AVG(memory_usage) FROM performance_metrics WHERE created_at >= DATE_SUB(NOW(), INTERVAL {$interval})"
            );
            
            return round($avgMemory, 2);
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    private function getAverageCpuUsage(string $interval): float {
        try {
            $avgCpu = $this->db->fetchColumn(
                "SELECT AVG(cpu_usage) FROM performance_metrics WHERE created_at >= DATE_SUB(NOW(), INTERVAL {$interval})"
            );
            
            return round($avgCpu, 2);
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    private function getAverageDiskUsage(string $interval): float {
        try {
            $avgDisk = $this->db->fetchColumn(
                "SELECT AVG(disk_usage) FROM performance_metrics WHERE created_at >= DATE_SUB(NOW(), INTERVAL {$interval})"
            );
            
            return round($avgDisk, 2);
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    private function getTotalRequests(string $interval): int {
        try {
            return $this->db->fetchColumn(
                "SELECT COUNT(*) FROM performance_metrics WHERE created_at >= DATE_SUB(NOW(), INTERVAL {$interval})"
            );
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    private function getErrorCount(string $interval): int {
        try {
            return $this->db->fetchColumn(
                "SELECT COUNT(*) FROM error_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL {$interval})"
            );
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    private function getSlowQueries(string $interval): int {
        try {
            return $this->db->fetchColumn(
                "SELECT COUNT(*) FROM performance_metrics WHERE query_time > 1.0 AND created_at >= DATE_SUB(NOW(), INTERVAL {$interval})"
            );
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    private function getPeakMemory(string $interval): float {
        try {
            $peakMemory = $this->db->fetchColumn(
                "SELECT MAX(peak_memory) FROM performance_metrics WHERE created_at >= DATE_SUB(NOW(), INTERVAL {$interval})"
            );
            
            return round($peakMemory, 2);
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    private function getPeakCpu(string $interval): float {
        try {
            $peakCpu = $this->db->fetchColumn(
                "SELECT MAX(cpu_usage) FROM performance_metrics WHERE created_at >= DATE_SUB(NOW(), INTERVAL {$interval})"
            );
            
            return round($peakCpu, 2);
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    public function getPerformanceTrends(string $period = 'day'): array {
        $interval = $this->getInterval($period);
        
        return $this->db->fetchAll(
            "SELECT 
                DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00') as time_period,
                AVG(response_time) as avg_response_time,
                AVG(memory_usage) as avg_memory_usage,
                AVG(cpu_usage) as avg_cpu_usage,
                AVG(disk_usage) as avg_disk_usage,
                COUNT(*) as request_count
             FROM performance_metrics 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL {$interval})
             GROUP BY DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00')
             ORDER BY time_period ASC"
        );
    }
    
    public function getAlerts(string $period = 'hour'): array {
        $interval = $this->getInterval($period);
        
        return $this->db->fetchAll(
            "SELECT * FROM performance_alerts 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL {$interval})
             ORDER BY created_at DESC"
        );
    }
    
    public function createAlert(array $alertData): bool {
        try {
            $this->db->insert('performance_alerts', [
                'type' => $alertData['type'],
                'metric' => $alertData['metric'],
                'value' => $alertData['value'],
                'threshold' => $alertData['threshold'],
                'message' => $alertData['message'],
                'status' => $alertData['status'] ?? 'active',
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            return true;
        } catch (\Exception $e) {
            error_log("Error creating performance alert: " . $e->getMessage());
            return false;
        }
    }
    
    public function getPerformanceDashboard(): array {
        return [
            'current_status' => $this->getCurrentStatus(),
            'stats' => $this->getPerformanceStats('hour'),
            'trends' => $this->getPerformanceTrends('day'),
            'alerts' => $this->getAlerts('hour'),
            'thresholds' => $this->thresholds,
            'system_info' => $this->getSystemInfo()
        ];
    }
    
    private function getCurrentStatus(): array {
        $currentMetrics = [
            'response_time' => $this->getAverageResponseTime('1 MINUTE'),
            'memory_usage' => $this->getAverageMemoryUsage('1 MINUTE'),
            'cpu_usage' => $this->getAverageCpuUsage('1 MINUTE'),
            'disk_usage' => $this->getAverageDiskUsage('1 MINUTE'),
            'database_connections' => $this->getDatabaseConnections(),
            'error_rate' => $this->getErrorRate(),
            'query_time' => $this->getAverageQueryTime()
        ];
        
        $alerts = $this->checkThresholds($currentMetrics);
        $status = $this->getOverallStatus($alerts);
        
        return [
            'status' => $status,
            'metrics' => $currentMetrics,
            'alerts' => $alerts
        ];
    }
    
    private function getSystemInfo(): array {
        return [
            'php_version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'os' => PHP_OS,
            'architecture' => php_uname('m')
        ];
    }
    
    public function getThresholds(): array {
        return $this->thresholds;
    }
    
    public function updateThresholds(array $thresholds): bool {
        try {
            $this->thresholds = array_merge($this->thresholds, $thresholds);
            
            // Save to database
            $this->db->update(
                'performance_thresholds',
                [
                    'thresholds' => json_encode($this->thresholds),
                    'updated_at' => date('Y-m-d H:i:s')
                ],
                'id = 1'
            );
            
            return true;
        } catch (\Exception $e) {
            error_log("Error updating performance thresholds: " . $e->getMessage());
            return false;
        }
    }
    
    public function cleanupOldMetrics(int $days = 30): bool {
        try {
            $deleted = $this->db->query(
                "DELETE FROM performance_metrics WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY)",
                ['days' => $days]
            );
            
            return true;
        } catch (\Exception $e) {
            error_log("Error cleaning up old metrics: " . $e->getMessage());
            return false;
        }
    }
    
    public function exportMetrics(string $format = 'json'): array {
        $metrics = $this->db->fetchAll(
            "SELECT * FROM performance_metrics ORDER BY created_at DESC LIMIT 1000"
        );
        
        switch ($format) {
            case 'json':
                return $metrics;
            case 'csv':
                return $this->convertToCsv($metrics);
            default:
                return $metrics;
        }
    }
    
    private function convertToCsv(array $data): array {
        if (empty($data)) {
            return [];
        }
        
        $csv = [];
        $csv[] = implode(',', array_keys($data[0]));
        
        foreach ($data as $row) {
            $csv[] = implode(',', array_values($row));
        }
        
        return $csv;
    }
}