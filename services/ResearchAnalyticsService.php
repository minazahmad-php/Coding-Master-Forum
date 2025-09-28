<?php
declare(strict_types=1);

namespace Services;

use Core\Database;
use Core\Logger;

class ResearchAnalyticsService
{
    private Database $db;
    private Logger $logger;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->logger = new Logger();
    }

    /**
     * Create research project
     */
    public function createResearchProject(array $projectData): array
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO research_projects (title, description, researcher_id, status, created_at)
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $projectData['title'],
                $projectData['description'],
                $projectData['researcher_id'],
                'active',
                date('Y-m-d H:i:s')
            ]);
            
            return ['success' => true, 'project_id' => $this->db->lastInsertId()];
        } catch (\Exception $e) {
            $this->logger->error("Failed to create research project: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get research projects
     */
    public function getResearchProjects(int $limit = 20): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    rp.*,
                    u.username as researcher_name,
                    COUNT(rd.id) as data_points_count
                FROM research_projects rp
                LEFT JOIN users u ON rp.researcher_id = u.id
                LEFT JOIN research_data rd ON rp.id = rd.project_id
                GROUP BY rp.id
                ORDER BY rp.created_at DESC
                LIMIT ?
            ");
            
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get research projects: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get research analytics
     */
    public function getResearchAnalytics(int $days = 30): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    rp.title as project_title,
                    COUNT(rd.id) as data_points_count,
                    COUNT(DISTINCT rd.researcher_id) as unique_researchers,
                    AVG(rd.value) as avg_value,
                    MIN(rd.value) as min_value,
                    MAX(rd.value) as max_value
                FROM research_projects rp
                LEFT JOIN research_data rd ON rp.id = rd.project_id
                WHERE rd.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY rp.id, rp.title
                ORDER BY data_points_count DESC
            ");
            
            $stmt->execute([$days]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get research analytics: " . $e->getMessage());
            return [];
        }
    }
}