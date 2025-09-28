<?php
declare(strict_types=1);

namespace Services;

use Core\Database;
use Core\Logger;

class DataScienceToolsService
{
    private Database $db;
    private Logger $logger;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->logger = new Logger();
    }

    /**
     * Create data analysis
     */
    public function createDataAnalysis(array $analysisData): array
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO data_analyses (name, description, query, results, created_at)
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $analysisData['name'],
                $analysisData['description'],
                $analysisData['query'],
                json_encode($analysisData['results']),
                date('Y-m-d H:i:s')
            ]);
            
            return ['success' => true, 'analysis_id' => $this->db->lastInsertId()];
        } catch (\Exception $e) {
            $this->logger->error("Failed to create data analysis: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get data analyses
     */
    public function getDataAnalyses(int $limit = 20): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    id,
                    name,
                    description,
                    query,
                    results,
                    created_at
                FROM data_analyses
                ORDER BY created_at DESC
                LIMIT ?
            ");
            
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get data analyses: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get data science analytics
     */
    public function getDataScienceAnalytics(int $days = 30): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as analyses_count,
                    AVG(LENGTH(query)) as avg_query_length,
                    AVG(LENGTH(results)) as avg_results_length
                FROM data_analyses 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY DATE(created_at)
                ORDER BY date DESC
            ");
            
            $stmt->execute([$days]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get data science analytics: " . $e->getMessage());
            return [];
        }
    }
}