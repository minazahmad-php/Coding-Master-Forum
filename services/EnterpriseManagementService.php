<?php
declare(strict_types=1);

namespace Services;

use Core\Database;
use Core\Logger;

class EnterpriseManagementService
{
    private Database $db;
    private Logger $logger;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->logger = new Logger();
    }

    /**
     * Create organization
     */
    public function createOrganization(array $orgData): array
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO organizations (name, description, admin_id, status, created_at)
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $orgData['name'],
                $orgData['description'],
                $orgData['admin_id'],
                'active',
                date('Y-m-d H:i:s')
            ]);
            
            return ['success' => true, 'organization_id' => $this->db->lastInsertId()];
        } catch (\Exception $e) {
            $this->logger->error("Failed to create organization: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get organizations
     */
    public function getOrganizations(int $limit = 20): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    o.*,
                    u.username as admin_name,
                    COUNT(ou.id) as member_count
                FROM organizations o
                LEFT JOIN users u ON o.admin_id = u.id
                LEFT JOIN organization_users ou ON o.id = ou.organization_id
                GROUP BY o.id
                ORDER BY o.created_at DESC
                LIMIT ?
            ");
            
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get organizations: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Add user to organization
     */
    public function addUserToOrganization(int $organizationId, int $userId, string $role = 'member'): bool
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO organization_users (organization_id, user_id, role, joined_at)
                VALUES (?, ?, ?, ?)
            ");
            
            return $stmt->execute([
                $organizationId,
                $userId,
                $role,
                date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            $this->logger->error("Failed to add user to organization: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get enterprise analytics
     */
    public function getEnterpriseAnalytics(int $days = 30): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    o.name as organization_name,
                    COUNT(ou.id) as member_count,
                    COUNT(p.id) as posts_count,
                    COUNT(c.id) as comments_count,
                    AVG(u.reputation) as avg_reputation
                FROM organizations o
                LEFT JOIN organization_users ou ON o.id = ou.organization_id
                LEFT JOIN users u ON ou.user_id = u.id
                LEFT JOIN posts p ON u.id = p.user_id
                LEFT JOIN comments c ON u.id = c.user_id
                WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY o.id, o.name
                ORDER BY member_count DESC
            ");
            
            $stmt->execute([$days]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get enterprise analytics: " . $e->getMessage());
            return [];
        }
    }
}