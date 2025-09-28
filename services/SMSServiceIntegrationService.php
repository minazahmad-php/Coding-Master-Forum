<?php
declare(strict_types=1);

namespace Services;

use Core\Database;
use Core\Logger;

class SMSServiceIntegrationService
{
    private Database $db;
    private Logger $logger;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->logger = new Logger();
    }

    /**
     * Send SMS
     */
    public function sendSMS(string $phoneNumber, string $message, array $options = []): bool
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO sms_logs (phone_number, message, status, sent_at)
                VALUES (?, ?, ?, ?)
            ");
            
            return $stmt->execute([
                $phoneNumber,
                $message,
                'sent',
                date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            $this->logger->error("Failed to send SMS: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get SMS analytics
     */
    public function getSMSAnalytics(int $days = 30): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    DATE(sent_at) as date,
                    COUNT(*) as sms_sent,
                    COUNT(CASE WHEN status = 'sent' THEN 1 END) as successful_sends,
                    COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_sends
                FROM sms_logs 
                WHERE sent_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY DATE(sent_at)
                ORDER BY date DESC
            ");
            
            $stmt->execute([$days]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get SMS analytics: " . $e->getMessage());
            return [];
        }
    }
}