<?php
declare(strict_types=1);

namespace Services;

use Core\Database;
use Core\Logger;

class TrainingModulesService
{
    private Database $db;
    private Logger $logger;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->logger = new Logger();
    }

    /**
     * Create training module
     */
    public function createTrainingModule(array $moduleData): array
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO training_modules (title, description, type, content, created_at)
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $moduleData['title'],
                $moduleData['description'],
                $moduleData['type'],
                $moduleData['content'],
                date('Y-m-d H:i:s')
            ]);
            
            return ['success' => true, 'module_id' => $this->db->lastInsertId()];
        } catch (\Exception $e) {
            $this->logger->error("Failed to create training module: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get training modules
     */
    public function getTrainingModules(int $limit = 20): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    tm.*,
                    COUNT(tmc.id) as completions_count,
                    AVG(tmc.completion_time) as avg_completion_time
                FROM training_modules tm
                LEFT JOIN training_module_completions tmc ON tm.id = tmc.module_id
                GROUP BY tm.id
                ORDER BY tm.created_at DESC
                LIMIT ?
            ");
            
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get training modules: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Complete training module
     */
    public function completeTrainingModule(int $moduleId, int $userId): bool
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO training_module_completions (module_id, user_id, completed_at)
                VALUES (?, ?, ?)
            ");
            
            return $stmt->execute([
                $moduleId,
                $userId,
                date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            $this->logger->error("Failed to complete training module: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get training module analytics
     */
    public function getTrainingModuleAnalytics(int $days = 30): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    tm.title as module_title,
                    tm.type as module_type,
                    COUNT(tmc.id) as completions_count,
                    COUNT(DISTINCT tmc.user_id) as unique_users,
                    AVG(tmc.completion_time) as avg_completion_time
                FROM training_modules tm
                LEFT JOIN training_module_completions tmc ON tm.id = tmc.module_id
                WHERE tmc.completed_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY tm.id, tm.title, tm.type
                ORDER BY completions_count DESC
            ");
            
            $stmt->execute([$days]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get training module analytics: " . $e->getMessage());
            return [];
        }
    }
}