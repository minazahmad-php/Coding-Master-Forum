<?php
declare(strict_types=1);

namespace Services;

use Core\Database;
use Core\Logger;

class PaymentSystemService
{
    private Database $db;
    private Logger $logger;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->logger = new Logger();
    }

    /**
     * Process payment
     */
    public function processPayment(array $paymentData): array
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO payments (user_id, amount, currency, payment_method, status, transaction_id, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $paymentData['user_id'],
                $paymentData['amount'],
                $paymentData['currency'],
                $paymentData['payment_method'],
                'pending',
                $paymentData['transaction_id'],
                date('Y-m-d H:i:s')
            ]);
            
            return ['success' => true, 'payment_id' => $this->db->lastInsertId()];
        } catch (\Exception $e) {
            $this->logger->error("Failed to process payment: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get payment history
     */
    public function getPaymentHistory(int $userId, int $limit = 20): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    id,
                    amount,
                    currency,
                    payment_method,
                    status,
                    transaction_id,
                    created_at
                FROM payments 
                WHERE user_id = ?
                ORDER BY created_at DESC
                LIMIT ?
            ");
            
            $stmt->execute([$userId, $limit]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get payment history: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get payment analytics
     */
    public function getPaymentAnalytics(int $days = 30): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as total_payments,
                    SUM(amount) as total_amount,
                    AVG(amount) as avg_amount,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as successful_payments
                FROM payments 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY DATE(created_at)
                ORDER BY date DESC
            ");
            
            $stmt->execute([$days]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get payment analytics: " . $e->getMessage());
            return [];
        }
    }
}