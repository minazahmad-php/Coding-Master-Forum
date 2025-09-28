<?php
declare(strict_types=1);

namespace Services;

use Core\Database;
use Core\Logger;

class WorkflowAutomationService
{
    private Database $db;
    private Logger $logger;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->logger = new Logger();
    }

    /**
     * Create workflow
     */
    public function createWorkflow(array $workflowData): array
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO workflows (name, description, trigger_event, actions, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $workflowData['name'],
                $workflowData['description'],
                $workflowData['trigger_event'],
                json_encode($workflowData['actions']),
                'active',
                date('Y-m-d H:i:s')
            ]);
            
            return ['success' => true, 'workflow_id' => $this->db->lastInsertId()];
        } catch (\Exception $e) {
            $this->logger->error("Failed to create workflow: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Execute workflow
     */
    public function executeWorkflow(int $workflowId, array $context): bool
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM workflows WHERE id = ? AND status = 'active'
            ");
            
            $stmt->execute([$workflowId]);
            $workflow = $stmt->fetch();
            
            if (!$workflow) {
                return false;
            }
            
            $actions = json_decode($workflow['actions'], true);
            
            foreach ($actions as $action) {
                $this->executeAction($action, $context);
            }
            
            // Log workflow execution
            $this->logWorkflowExecution($workflowId, $context);
            
            return true;
        } catch (\Exception $e) {
            $this->logger->error("Failed to execute workflow: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Execute individual action
     */
    private function executeAction(array $action, array $context): bool
    {
        try {
            switch ($action['type']) {
                case 'send_email':
                    return $this->sendEmail($action['email'], $action['subject'], $action['body']);
                case 'create_notification':
                    return $this->createNotification($action['user_id'], $action['message']);
                case 'update_status':
                    return $this->updateStatus($action['entity_type'], $action['entity_id'], $action['status']);
                default:
                    return false;
            }
        } catch (\Exception $e) {
            $this->logger->error("Failed to execute action: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send email
     */
    private function sendEmail(string $email, string $subject, string $body): bool
    {
        // Email sending implementation
        return true;
    }

    /**
     * Create notification
     */
    private function createNotification(int $userId, string $message): bool
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO notifications (user_id, message, type, created_at)
                VALUES (?, ?, ?, ?)
            ");
            
            return $stmt->execute([
                $userId,
                $message,
                'workflow',
                date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            $this->logger->error("Failed to create notification: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update status
     */
    private function updateStatus(string $entityType, int $entityId, string $status): bool
    {
        try {
            $table = $entityType . 's'; // Convert to table name
            $stmt = $this->db->prepare("
                UPDATE {$table} SET status = ? WHERE id = ?
            ");
            
            return $stmt->execute([$status, $entityId]);
        } catch (\Exception $e) {
            $this->logger->error("Failed to update status: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Log workflow execution
     */
    private function logWorkflowExecution(int $workflowId, array $context): bool
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO workflow_executions (workflow_id, context, executed_at)
                VALUES (?, ?, ?)
            ");
            
            return $stmt->execute([
                $workflowId,
                json_encode($context),
                date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            $this->logger->error("Failed to log workflow execution: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get workflow analytics
     */
    public function getWorkflowAnalytics(int $days = 30): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    w.name as workflow_name,
                    COUNT(we.id) as executions_count,
                    COUNT(DISTINCT we.context->>'$.user_id') as unique_users,
                    AVG(execution_time) as avg_execution_time
                FROM workflows w
                LEFT JOIN workflow_executions we ON w.id = we.workflow_id
                WHERE we.executed_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY w.id, w.name
                ORDER BY executions_count DESC
            ");
            
            $stmt->execute([$days]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get workflow analytics: " . $e->getMessage());
            return [];
        }
    }
}