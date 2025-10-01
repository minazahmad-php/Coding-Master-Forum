<?php
declare(strict_types=1);

namespace Services;

use Core\Database;
use Core\Logger;

class MonitoringServiceIntegrationService
{
    private Database $db;
    private Logger $logger;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->logger = new Logger();
    }

    /**
     * Track system metrics
     */
    public function trackSystemMetrics(array $metrics): bool
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO system_metrics (metric_name, value, metadata, recorded_at)
                VALUES (?, ?, ?, ?)
            ");
            
            foreach ($metrics as $metric) {
                $stmt->execute([
                    $metric['name'],
                    $metric['value'],
                    json_encode($metric['metadata'] ?? []),
                    date('Y-m-d H:i:s')
                ]);
            }
            
            return true;
        } catch (\Exception $e) {
            $this->logger->error("Failed to track system metrics: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get system health status
     */
    public function getSystemHealthStatus(): array
    {
        try {
            $stmt = $this->db->query("
                SELECT 
                    metric_name,
                    AVG(value) as avg_value,
                    MAX(value) as max_value,
                    MIN(value) as min_value,
                    COUNT(*) as measurement_count
                FROM system_metrics 
                WHERE recorded_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
                GROUP BY metric_name
                ORDER BY avg_value DESC
            ");
            
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get system health status: " . $e->getMessage());
            return [];
        }
    }
}