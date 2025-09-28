<?php
declare(strict_types=1);

namespace Services;

use Core\Database;
use Core\Logger;

class CloudStorageIntegrationService
{
    private Database $db;
    private Logger $logger;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->logger = new Logger();
    }

    /**
     * Upload file to cloud storage
     */
    public function uploadFile(string $filePath, string $cloudPath, array $metadata = []): array
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO cloud_storage_files (file_path, cloud_path, metadata, uploaded_at)
                VALUES (?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $filePath,
                $cloudPath,
                json_encode($metadata),
                date('Y-m-d H:i:s')
            ]);
            
            return ['success' => true, 'file_id' => $this->db->lastInsertId()];
        } catch (\Exception $e) {
            $this->logger->error("Failed to upload file to cloud storage: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get cloud storage analytics
     */
    public function getCloudStorageAnalytics(int $days = 30): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    DATE(uploaded_at) as date,
                    COUNT(*) as files_uploaded,
                    SUM(file_size) as total_size,
                    AVG(file_size) as avg_file_size
                FROM cloud_storage_files 
                WHERE uploaded_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY DATE(uploaded_at)
                ORDER BY date DESC
            ");
            
            $stmt->execute([$days]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get cloud storage analytics: " . $e->getMessage());
            return [];
        }
    }
}