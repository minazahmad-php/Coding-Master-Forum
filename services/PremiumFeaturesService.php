<?php
declare(strict_types=1);

namespace Services;

use Core\Database;
use Core\Logger;

class PremiumFeaturesService
{
    private Database $db;
    private Logger $logger;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->logger = new Logger();
    }

    /**
     * Check premium access
     */
    public function checkPremiumAccess(int $userId, string $feature): bool
    {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count
                FROM user_subscriptions us
                LEFT JOIN subscription_plans sp ON us.plan_id = sp.id
                LEFT JOIN plan_features pf ON sp.id = pf.plan_id
                WHERE us.user_id = ? 
                AND us.status = 'active'
                AND pf.feature_name = ?
            ");
            
            $stmt->execute([$userId, $feature]);
            $result = $stmt->fetch();
            
            return $result['count'] > 0;
        } catch (\Exception $e) {
            $this->logger->error("Failed to check premium access: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get premium features
     */
    public function getPremiumFeatures(): array
    {
        try {
            $stmt = $this->db->query("
                SELECT 
                    feature_name,
                    description,
                    price,
                    category
                FROM premium_features
                ORDER BY category, price
            ");
            
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get premium features: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get user premium status
     */
    public function getUserPremiumStatus(int $userId): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    us.*,
                    sp.name as plan_name,
                    sp.price as plan_price,
                    sp.features as plan_features
                FROM user_subscriptions us
                LEFT JOIN subscription_plans sp ON us.plan_id = sp.id
                WHERE us.user_id = ?
                ORDER BY us.created_at DESC
                LIMIT 1
            ");
            
            $stmt->execute([$userId]);
            return $stmt->fetch() ?: [];
        } catch (\Exception $e) {
            $this->logger->error("Failed to get user premium status: " . $e->getMessage());
            return [];
        }
    }
}