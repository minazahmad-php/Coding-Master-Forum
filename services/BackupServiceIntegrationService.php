<?php
declare(strict_types=1);

namespace Services;

use Core\Database;
use Core\Logger;

class BackupServiceIntegrationService
{
    private Database $db;
    private Logger $logger;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->logger = new Logger();
    }

    /**
     * Create backup
     */
    public function createBackup(string $backupType, array $options = []): array
    {
        try {
            $backupId = uniqid('backup_');
            $stmt = $this->db->prepare("
                INSERT INTO backups (backup_id, backup_type, status, options, created_at)
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $backupId,
                $backupType,
                'pending',
                json_encode($options),
                date('Y-m-d H:i:s')
            ]);
            
            return ['success' => true, 'backup_id' => $backupId];
        } catch (\Exception $e) {
            $this->logger->error("Failed to create backup: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get backup history
     */
    public function getBackupHistory(int $days = 30): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    backup_id,
                    backup_type,
                    status,
                    created_at,
                    completed_at,
                    TIMESTAMPDIFF(MINUTE, created_at, completed_at) as duration_minutes
                FROM backups 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                ORDER BY created_at DESC
            ");
            
            $stmt->execute([$days]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get backup history: " . $e->getMessage());
            return [];
        }
    }
}