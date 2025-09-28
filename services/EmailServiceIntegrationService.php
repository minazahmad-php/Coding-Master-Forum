<?php
declare(strict_types=1);

namespace Services;

use Core\Database;
use Core\Logger;

class EmailServiceIntegrationService
{
    private Database $db;
    private Logger $logger;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->logger = new Logger();
    }

    /**
     * Send email
     */
    public function sendEmail(string $to, string $subject, string $body, array $options = []): bool
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO email_logs (to_email, subject, body, status, sent_at)
                VALUES (?, ?, ?, ?, ?)
            ");
            
            return $stmt->execute([
                $to,
                $subject,
                $body,
                'sent',
                date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            $this->logger->error("Failed to send email: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get email analytics
     */
    public function getEmailAnalytics(int $days = 30): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    DATE(sent_at) as date,
                    COUNT(*) as emails_sent,
                    COUNT(CASE WHEN status = 'sent' THEN 1 END) as successful_sends,
                    COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_sends
                FROM email_logs 
                WHERE sent_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY DATE(sent_at)
                ORDER BY date DESC
            ");
            
            $stmt->execute([$days]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get email analytics: " . $e->getMessage());
            return [];
        }
    }
}