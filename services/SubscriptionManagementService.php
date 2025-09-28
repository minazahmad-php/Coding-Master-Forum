<?php
declare(strict_types=1);

namespace Services;

use Core\Database;
use Core\Logger;

class SubscriptionManagementService
{
    private Database $db;
    private Logger $logger;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->logger = new Logger();
    }

    /**
     * Create subscription
     */
    public function createSubscription(int $userId, int $planId): array
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO user_subscriptions (user_id, plan_id, status, start_date, end_date, created_at)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $startDate = date('Y-m-d H:i:s');
            $endDate = date('Y-m-d H:i:s', strtotime('+1 month'));
            
            $stmt->execute([
                $userId,
                $planId,
                'active',
                $startDate,
                $endDate,
                date('Y-m-d H:i:s')
            ]);
            
            return ['success' => true, 'subscription_id' => $this->db->lastInsertId()];
        } catch (\Exception $e) {
            $this->logger->error("Failed to create subscription: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get subscription plans
     */
    public function getSubscriptionPlans(): array
    {
        try {
            $stmt = $this->db->query("
                SELECT 
                    id,
                    name,
                    description,
                    price,
                    duration,
                    features,
                    created_at
                FROM subscription_plans
                ORDER BY price ASC
            ");
            
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get subscription plans: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get subscription analytics
     */
    public function getSubscriptionAnalytics(int $days = 30): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    sp.name as plan_name,
                    COUNT(us.id) as total_subscriptions,
                    COUNT(CASE WHEN us.status = 'active' THEN 1 END) as active_subscriptions,
                    COUNT(CASE WHEN us.status = 'cancelled' THEN 1 END) as cancelled_subscriptions,
                    AVG(sp.price) as avg_price
                FROM subscription_plans sp
                LEFT JOIN user_subscriptions us ON sp.id = us.plan_id
                WHERE us.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY sp.id, sp.name
                ORDER BY total_subscriptions DESC
            ");
            
            $stmt->execute([$days]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get subscription analytics: " . $e->getMessage());
            return [];
        }
    }
}