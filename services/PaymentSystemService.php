<?php
declare(strict_types=1);

namespace Services;

use Core\Database;
use Core\Logger;
use Core\Cache;

class PaymentSystemService
{
    private Database $db;
    private Logger $logger;
    private Cache $cache;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->logger = new Logger();
        $this->cache = new Cache();
    }

    public function createPaymentIntent(array $data): array
    {
        try {
            // Create payment intent record
            $intentId = $this->db->insert('payment_intents', [
                'user_id' => $data['user_id'],
                'amount' => $data['amount'],
                'currency' => $data['currency'] ?? 'usd',
                'description' => $data['description'] ?? '',
                'status' => 'requires_payment_method',
                'metadata' => json_encode($data['metadata'] ?? []),
                'created_at' => date('Y-m-d H:i:s')
            ]);

            // Generate client secret
            $clientSecret = 'pi_' . $intentId . '_secret_' . bin2hex(random_bytes(16));

            // Update with client secret
            $this->db->update('payment_intents', ['id' => $intentId], [
                'client_secret' => $clientSecret,
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            return [
                'id' => $intentId,
                'client_secret' => $clientSecret,
                'amount' => $data['amount'],
                'currency' => $data['currency'] ?? 'usd',
                'status' => 'requires_payment_method'
            ];

        } catch (\Exception $e) {
            $this->logger->error("Failed to create payment intent", [
                'error' => $e->getMessage(),
                'user_id' => $data['user_id'] ?? null
            ]);
            throw $e;
        }
    }

    public function processPayment(array $paymentData): array
    {
        try {
            $this->db->beginTransaction();

            // Create payment record
            $paymentId = $this->db->insert('payments', [
                'user_id' => $paymentData['user_id'],
                'amount' => $paymentData['amount'],
                'currency' => $paymentData['currency'] ?? 'USD',
                'payment_method' => $paymentData['payment_method'],
                'payment_intent_id' => $paymentData['payment_intent_id'] ?? null,
                'status' => 'pending',
                'description' => $paymentData['description'] ?? '',
                'metadata' => json_encode($paymentData['metadata'] ?? []),
                'created_at' => date('Y-m-d H:i:s')
            ]);

            // Process with payment gateway
            $gatewayResult = $this->processWithGateway($paymentData);

            // Update payment status
            $this->db->update('payments', ['id' => $paymentId], [
                'gateway_transaction_id' => $gatewayResult['transaction_id'],
                'status' => $gatewayResult['status'],
                'gateway_fee' => $gatewayResult['fee'] ?? 0,
                'net_amount' => $paymentData['amount'] - ($gatewayResult['fee'] ?? 0),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            $this->db->commit();

            $this->logger->info("Payment processed successfully", [
                'payment_id' => $paymentId,
                'user_id' => $paymentData['user_id'],
                'amount' => $paymentData['amount']
            ]);

            return [
                'success' => true,
                'payment_id' => $paymentId,
                'transaction_id' => $gatewayResult['transaction_id'],
                'status' => $gatewayResult['status']
            ];

        } catch (\Exception $e) {
            $this->db->rollback();
            $this->logger->error("Payment processing failed", [
                'error' => $e->getMessage(),
                'user_id' => $paymentData['user_id'] ?? null
            ]);

            throw $e;
        }
    }

    public function getUserPayments(int $userId, int $page = 1, int $limit = 20): array
    {
        $offset = ($page - 1) * $limit;
        
        return $this->db->query(
            "SELECT p.*, s.plan_name 
             FROM payments p
             LEFT JOIN subscriptions sub ON p.subscription_id = sub.id
             LEFT JOIN subscription_plans s ON sub.plan_id = s.id
             WHERE p.user_id = :user_id 
             ORDER BY p.created_at DESC 
             LIMIT :limit OFFSET :offset",
            [
                ':user_id' => $userId, 
                ':limit' => $limit,
                ':offset' => $offset
            ]
        )->fetchAll();
    }

    public function getPayment(int $paymentId): ?array
    {
        $payment = $this->db->query(
            "SELECT p.*, u.username, u.email 
             FROM payments p
             JOIN users u ON p.user_id = u.id
             WHERE p.id = :id",
            [':id' => $paymentId]
        )->fetch();

        return $payment ?: null;
    }

    public function getUserTotalSpent(int $userId): float
    {
        $result = $this->db->query(
            "SELECT SUM(amount) as total FROM payments 
             WHERE user_id = :user_id AND status = 'completed'",
            [':user_id' => $userId]
        )->fetch();

        return (float)($result['total'] ?? 0);
    }

    public function getUserPaymentMethods(int $userId): array
    {
        return $this->db->query(
            "SELECT * FROM payment_methods 
             WHERE user_id = :user_id 
             AND is_active = 1 
             ORDER BY is_default DESC, created_at DESC",
            [':user_id' => $userId]
        )->fetchAll();
    }

    public function addPaymentMethod(int $userId, array $methodData): int
    {
        return $this->db->insert('payment_methods', [
            'user_id' => $userId,
            'type' => $methodData['type'],
            'provider' => $methodData['provider'],
            'token' => $methodData['token'],
            'last_four' => $methodData['last_four'] ?? null,
            'brand' => $methodData['brand'] ?? null,
            'expires_at' => $methodData['expires_at'] ?? null,
            'is_default' => $methodData['is_default'] ?? 0,
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function updateUserPaymentMethod(int $userId, string $paymentMethodId): bool
    {
        try {
            $this->db->beginTransaction();

            // Remove default from all methods
            $this->db->update('payment_methods', ['user_id' => $userId], [
                'is_default' => 0,
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            // Set new default (assuming paymentMethodId is from our database)
            $updated = $this->db->update('payment_methods', [
                'token' => $paymentMethodId,
                'user_id' => $userId
            ], [
                'is_default' => 1,
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            $this->db->commit();
            return $updated > 0;

        } catch (\Exception $e) {
            $this->db->rollback();
            $this->logger->error("Failed to update payment method", [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'payment_method_id' => $paymentMethodId
            ]);
            return false;
        }
    }

    public function recordPayment(array $paymentData): int
    {
        return $this->db->insert('payments', [
            'user_id' => $paymentData['user_id'],
            'subscription_id' => $paymentData['subscription_id'] ?? null,
            'amount' => $paymentData['amount'],
            'currency' => $paymentData['currency'],
            'payment_method' => $paymentData['payment_method'],
            'gateway_transaction_id' => $paymentData['gateway_transaction_id'],
            'status' => $paymentData['status'],
            'description' => $paymentData['description'] ?? '',
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function updatePaymentStatus(string $paymentIntentId, string $status): bool
    {
        $updated = $this->db->update('payments', [
            'payment_intent_id' => $paymentIntentId
        ], [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        return $updated > 0;
    }

    public function recordRefund(array $refundData): int
    {
        return $this->db->insert('refunds', [
            'payment_id' => $refundData['payment_id'],
            'amount' => $refundData['amount'],
            'reason' => $refundData['reason'],
            'gateway_refund_id' => $refundData['gateway_refund_id'],
            'status' => 'completed',
            'processed_by' => $refundData['processed_by'],
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function generateInvoice(array $payment): string
    {
        // This would generate a PDF invoice
        // For now, return a simple text representation
        $invoice = "INVOICE\n\n";
        $invoice .= "Payment ID: {$payment['id']}\n";
        $invoice .= "Amount: {$payment['currency']} {$payment['amount']}\n";
        $invoice .= "Date: {$payment['created_at']}\n";
        $invoice .= "Status: {$payment['status']}\n";
        
        return $invoice;
    }

    // Admin methods
    public function getTotalRevenue(): float
    {
        $cacheKey = 'total_revenue';
        
        if ($cached = $this->cache->get($cacheKey)) {
            return (float)$cached;
        }

        $result = $this->db->query(
            "SELECT SUM(amount) as total FROM payments WHERE status = 'completed'"
        )->fetch();
        
        $total = (float)($result['total'] ?? 0);
        $this->cache->set($cacheKey, $total, 300); // Cache for 5 minutes
        
        return $total;
    }

    public function getMonthlyRevenue(): float
    {
        $result = $this->db->query(
            "SELECT SUM(amount) as total FROM payments 
             WHERE status = 'completed' 
             AND MONTH(created_at) = MONTH(CURRENT_DATE())
             AND YEAR(created_at) = YEAR(CURRENT_DATE())"
        )->fetch();

        return (float)($result['total'] ?? 0);
    }

    public function getRecentPayments(int $limit = 10): array
    {
        return $this->db->query(
            "SELECT p.*, u.username 
             FROM payments p
             JOIN users u ON p.user_id = u.id
             ORDER BY p.created_at DESC 
             LIMIT :limit",
            [':limit' => $limit]
        )->fetchAll();
    }

    public function getAllPayments(int $page = 1, int $limit = 50, ?string $status = null, ?string $search = null): array
    {
        $offset = ($page - 1) * $limit;
        $where = [];
        $params = [':limit' => $limit, ':offset' => $offset];

        if ($status) {
            $where[] = "p.status = :status";
            $params[':status'] = $status;
        }

        if ($search) {
            $where[] = "(u.username LIKE :search OR u.email LIKE :search OR p.gateway_transaction_id LIKE :search)";
            $params[':search'] = "%{$search}%";
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        return $this->db->query(
            "SELECT p.*, u.username, u.email 
             FROM payments p
             JOIN users u ON p.user_id = u.id
             {$whereClause}
             ORDER BY p.created_at DESC 
             LIMIT :limit OFFSET :offset",
            $params
        )->fetchAll();
    }

    public function getFailedPayments(int $limit = 20): array
    {
        return $this->db->query(
            "SELECT p.*, u.username, u.email 
             FROM payments p
             JOIN users u ON p.user_id = u.id
             WHERE p.status = 'failed'
             ORDER BY p.created_at DESC 
             LIMIT :limit",
            [':limit' => $limit]
        )->fetchAll();
    }

    public function getRevenueChartData(int $days = 30): array
    {
        return $this->db->query(
            "SELECT DATE(created_at) as date, SUM(amount) as revenue
             FROM payments 
             WHERE status = 'completed' 
             AND created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
             GROUP BY DATE(created_at)
             ORDER BY date",
            [':days' => $days]
        )->fetchAll();
    }

    public function getPaymentStats(): array
    {
        $stats = [];

        // Total revenue
        $stats['total_revenue'] = $this->getTotalRevenue();

        // Monthly revenue
        $stats['monthly_revenue'] = $this->getMonthlyRevenue();

        // Payment counts by status
        $statusCounts = $this->db->query(
            "SELECT status, COUNT(*) as count FROM payments GROUP BY status"
        )->fetchAll();

        foreach ($statusCounts as $status) {
            $stats['payments_' . $status['status']] = $status['count'];
        }

        // Average payment amount
        $result = $this->db->query(
            "SELECT AVG(amount) as avg FROM payments WHERE status = 'completed'"
        )->fetch();
        $stats['average_payment'] = (float)($result['avg'] ?? 0);

        return $stats;
    }

    private function processWithGateway(array $paymentData): array
    {
        // This would integrate with actual payment gateways like Stripe, PayPal, etc.
        // For now, return a mock successful response
        return [
            'transaction_id' => 'txn_' . uniqid(),
            'status' => 'completed',
            'fee' => $paymentData['amount'] * 0.029 + 0.30 // Mock Stripe fee
        ];
    }

    private function processRefundWithGateway(string $transactionId, float $amount): array
    {
        // This would integrate with actual payment gateways
        // For now, return a mock successful response
        return [
            'refund_id' => 'ref_' . uniqid(),
            'status' => 'completed'
        ];
    }
}