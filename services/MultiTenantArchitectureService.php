<?php
declare(strict_types=1);

namespace Services;

use Core\Database;
use Core\Logger;

class MultiTenantArchitectureService
{
    private Database $db;
    private Logger $logger;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->logger = new Logger();
    }

    /**
     * Create tenant
     */
    public function createTenant(array $tenantData): array
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO tenants (name, domain, admin_id, status, created_at)
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $tenantData['name'],
                $tenantData['domain'],
                $tenantData['admin_id'],
                'active',
                date('Y-m-d H:i:s')
            ]);
            
            return ['success' => true, 'tenant_id' => $this->db->lastInsertId()];
        } catch (\Exception $e) {
            $this->logger->error("Failed to create tenant: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get tenant by domain
     */
    public function getTenantByDomain(string $domain): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    t.*,
                    u.username as admin_name
                FROM tenants t
                LEFT JOIN users u ON t.admin_id = u.id
                WHERE t.domain = ? AND t.status = 'active'
            ");
            
            $stmt->execute([$domain]);
            return $stmt->fetch() ?: [];
        } catch (\Exception $e) {
            $this->logger->error("Failed to get tenant by domain: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get tenant analytics
     */
    public function getTenantAnalytics(): array
    {
        try {
            $stmt = $this->db->query("
                SELECT 
                    t.name as tenant_name,
                    t.domain,
                    COUNT(u.id) as user_count,
                    COUNT(p.id) as posts_count,
                    COUNT(c.id) as comments_count,
                    t.created_at
                FROM tenants t
                LEFT JOIN users u ON t.id = u.tenant_id
                LEFT JOIN posts p ON u.id = p.user_id
                LEFT JOIN comments c ON u.id = c.user_id
                GROUP BY t.id, t.name, t.domain, t.created_at
                ORDER BY user_count DESC
            ");
            
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get tenant analytics: " . $e->getMessage());
            return [];
        }
    }
}