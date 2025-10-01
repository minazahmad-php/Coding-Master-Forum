<?php
declare(strict_types=1);

namespace Services;

use Core\Database;
use Core\Logger;

class ComplianceService
{
    private Database $db;
    private Logger $logger;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->logger = new Logger();
    }

    /**
     * Create compliance policy
     */
    public function createCompliancePolicy(array $policyData): array
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO compliance_policies (name, description, type, content, created_at)
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $policyData['name'],
                $policyData['description'],
                $policyData['type'],
                $policyData['content'],
                date('Y-m-d H:i:s')
            ]);
            
            return ['success' => true, 'policy_id' => $this->db->lastInsertId()];
        } catch (\Exception $e) {
            $this->logger->error("Failed to create compliance policy: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get compliance policies
     */
    public function getCompliancePolicies(int $limit = 20): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    id,
                    name,
                    description,
                    type,
                    created_at,
                    updated_at
                FROM compliance_policies
                ORDER BY created_at DESC
                LIMIT ?
            ");
            
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get compliance policies: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Track compliance violation
     */
    public function trackComplianceViolation(array $violationData): bool
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO compliance_violations (policy_id, user_id, violation_type, description, created_at)
                VALUES (?, ?, ?, ?, ?)
            ");
            
            return $stmt->execute([
                $violationData['policy_id'],
                $violationData['user_id'],
                $violationData['violation_type'],
                $violationData['description'],
                date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            $this->logger->error("Failed to track compliance violation: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get compliance analytics
     */
    public function getComplianceAnalytics(int $days = 30): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    cp.name as policy_name,
                    cp.type as policy_type,
                    COUNT(cv.id) as violations_count,
                    COUNT(DISTINCT cv.user_id) as unique_violators,
                    AVG(CASE WHEN cv.violation_type = 'minor' THEN 1 ELSE 0 END) as minor_violations_rate,
                    AVG(CASE WHEN cv.violation_type = 'major' THEN 1 ELSE 0 END) as major_violations_rate
                FROM compliance_policies cp
                LEFT JOIN compliance_violations cv ON cp.id = cv.policy_id
                WHERE cv.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY cp.id, cp.name, cp.type
                ORDER BY violations_count DESC
            ");
            
            $stmt->execute([$days]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get compliance analytics: " . $e->getMessage());
            return [];
        }
    }
}