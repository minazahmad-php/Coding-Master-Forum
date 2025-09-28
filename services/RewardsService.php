<?php
declare(strict_types=1);

namespace Services;

class RewardsService {
    private Database $db;
    private array $rewardTypes;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->rewardTypes = $this->getRewardTypes();
    }
    
    private function getRewardTypes(): array {
        return [
            'points' => [
                'name' => 'Points',
                'description' => 'Virtual currency points',
                'icon' => 'fas fa-coins',
                'color' => '#FFD700'
            ],
            'badge' => [
                'name' => 'Badge',
                'description' => 'Achievement badge',
                'icon' => 'fas fa-medal',
                'color' => '#FF6B6B'
            ],
            'achievement' => [
                'name' => 'Achievement',
                'description' => 'Achievement unlock',
                'icon' => 'fas fa-trophy',
                'color' => '#4ECDC4'
            ],
            'title' => [
                'name' => 'Custom Title',
                'description' => 'Custom user title',
                'icon' => 'fas fa-crown',
                'color' => '#9C27B0'
            ],
            'avatar_border' => [
                'name' => 'Avatar Border',
                'description' => 'Special avatar border',
                'icon' => 'fas fa-border-all',
                'color' => '#E91E63'
            ],
            'profile_theme' => [
                'name' => 'Profile Theme',
                'description' => 'Custom profile theme',
                'icon' => 'fas fa-palette',
                'color' => '#00BCD4'
            ],
            'priority_support' => [
                'name' => 'Priority Support',
                'description' => 'Priority customer support',
                'icon' => 'fas fa-headset',
                'color' => '#FF9800'
            ],
            'beta_access' => [
                'name' => 'Beta Access',
                'description' => 'Early access to new features',
                'icon' => 'fas fa-flask',
                'color' => '#795548'
            ],
            'exclusive_content' => [
                'name' => 'Exclusive Content',
                'description' => 'Access to exclusive content',
                'icon' => 'fas fa-lock',
                'color' => '#607D8B'
            ],
            'discount' => [
                'name' => 'Discount',
                'description' => 'Percentage discount',
                'icon' => 'fas fa-percentage',
                'color' => '#4CAF50'
            ],
            'free_item' => [
                'name' => 'Free Item',
                'description' => 'Free item or service',
                'icon' => 'fas fa-gift',
                'color' => '#FF5722'
            ],
            'special_permission' => [
                'name' => 'Special Permission',
                'description' => 'Special forum permission',
                'icon' => 'fas fa-key',
                'color' => '#3F51B5'
            ]
        ];
    }
    
    public function createReward(array $rewardData): bool {
        try {
            $this->db->insert('rewards', [
                'name' => $rewardData['name'],
                'description' => $rewardData['description'],
                'type' => $rewardData['type'],
                'value' => $rewardData['value'],
                'icon' => $rewardData['icon'] ?? $this->rewardTypes[$rewardData['type']]['icon'],
                'color' => $rewardData['color'] ?? $this->rewardTypes[$rewardData['type']]['color'],
                'rarity' => $rewardData['rarity'] ?? 'common',
                'category' => $rewardData['category'] ?? 'general',
                'requirements' => json_encode($rewardData['requirements'] ?? []),
                'conditions' => json_encode($rewardData['conditions'] ?? []),
                'expires_at' => $rewardData['expires_at'] ?? null,
                'is_active' => $rewardData['is_active'] ?? true,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            return true;
        } catch (\Exception $e) {
            error_log("Error creating reward: " . $e->getMessage());
            return false;
        }
    }
    
    public function awardReward(int $userId, int $rewardId, array $data = []): bool {
        $reward = $this->getReward($rewardId);
        if (!$reward || !$reward['is_active']) {
            return false;
        }
        
        // Check if user already has this reward
        if ($this->hasReward($userId, $rewardId)) {
            return false;
        }
        
        // Check requirements
        if (!$this->checkRequirements($userId, $reward)) {
            return false;
        }
        
        // Check conditions
        if (!$this->checkConditions($userId, $reward, $data)) {
            return false;
        }
        
        try {
            $this->db->beginTransaction();
            
            // Award the reward
            $this->db->insert('user_rewards', [
                'user_id' => $userId,
                'reward_id' => $rewardId,
                'awarded_at' => date('Y-m-d H:i:s'),
                'data' => json_encode($data),
                'is_active' => true
            ]);
            
            // Apply the reward
            $this->applyReward($userId, $reward, $data);
            
            $this->db->commit();
            
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            error_log("Error awarding reward: " . $e->getMessage());
            return false;
        }
    }
    
    private function applyReward(int $userId, array $reward, array $data): void {
        switch ($reward['type']) {
            case 'points':
                $this->db->query(
                    "UPDATE users SET points = points + :points WHERE id = :user_id",
                    ['points' => $reward['value'], 'user_id' => $userId]
                );
                break;
                
            case 'badge':
                $this->db->insert('user_badges', [
                    'user_id' => $userId,
                    'badge_id' => $reward['value'],
                    'earned_at' => date('Y-m-d H:i:s')
                ]);
                break;
                
            case 'achievement':
                $this->db->insert('user_achievements', [
                    'user_id' => $userId,
                    'achievement_id' => $reward['value'],
                    'earned_at' => date('Y-m-d H:i:s')
                ]);
                break;
                
            case 'title':
                $this->db->update(
                    'users',
                    ['custom_title' => $reward['value']],
                    'id = :user_id',
                    ['user_id' => $userId]
                );
                break;
                
            case 'avatar_border':
                $this->db->insert('user_avatar_borders', [
                    'user_id' => $userId,
                    'border_id' => $reward['value'],
                    'unlocked_at' => date('Y-m-d H:i:s')
                ]);
                break;
                
            case 'profile_theme':
                $this->db->insert('user_profile_themes', [
                    'user_id' => $userId,
                    'theme_id' => $reward['value'],
                    'unlocked_at' => date('Y-m-d H:i:s')
                ]);
                break;
                
            case 'priority_support':
                $this->db->update(
                    'users',
                    ['priority_support' => true],
                    'id = :user_id',
                    ['user_id' => $userId]
                );
                break;
                
            case 'beta_access':
                $this->db->update(
                    'users',
                    ['beta_access' => true],
                    'id = :user_id',
                    ['user_id' => $userId]
                );
                break;
                
            case 'exclusive_content':
                $this->db->insert('user_exclusive_access', [
                    'user_id' => $userId,
                    'content_id' => $reward['value'],
                    'unlocked_at' => date('Y-m-d H:i:s')
                ]);
                break;
                
            case 'discount':
                $this->db->insert('user_discounts', [
                    'user_id' => $userId,
                    'discount_percentage' => $reward['value'],
                    'expires_at' => $reward['expires_at'],
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                break;
                
            case 'free_item':
                $this->db->insert('user_free_items', [
                    'user_id' => $userId,
                    'item_id' => $reward['value'],
                    'claimed_at' => date('Y-m-d H:i:s')
                ]);
                break;
                
            case 'special_permission':
                $this->db->insert('user_special_permissions', [
                    'user_id' => $userId,
                    'permission' => $reward['value'],
                    'granted_at' => date('Y-m-d H:i:s')
                ]);
                break;
        }
    }
    
    private function checkRequirements(int $userId, array $reward): bool {
        $requirements = json_decode($reward['requirements'], true);
        
        if (empty($requirements)) {
            return true;
        }
        
        foreach ($requirements as $requirement => $value) {
            switch ($requirement) {
                case 'min_level':
                    $userLevel = $this->db->fetchColumn(
                        "SELECT level FROM users WHERE id = :user_id",
                        ['user_id' => $userId]
                    );
                    if ($userLevel < $value) {
                        return false;
                    }
                    break;
                    
                case 'min_points':
                    $userPoints = $this->db->fetchColumn(
                        "SELECT points FROM users WHERE id = :user_id",
                        ['user_id' => $userId]
                    );
                    if ($userPoints < $value) {
                        return false;
                    }
                    break;
                    
                case 'min_posts':
                    $postCount = $this->db->fetchColumn(
                        "SELECT COUNT(*) FROM posts WHERE user_id = :user_id",
                        ['user_id' => $userId]
                    );
                    if ($postCount < $value) {
                        return false;
                    }
                    break;
                    
                case 'min_threads':
                    $threadCount = $this->db->fetchColumn(
                        "SELECT COUNT(*) FROM threads WHERE user_id = :user_id",
                        ['user_id' => $userId]
                    );
                    if ($threadCount < $value) {
                        return false;
                    }
                    break;
                    
                case 'min_achievements':
                    $achievementCount = $this->db->fetchColumn(
                        "SELECT COUNT(*) FROM user_achievements WHERE user_id = :user_id",
                        ['user_id' => $userId]
                    );
                    if ($achievementCount < $value) {
                        return false;
                    }
                    break;
                    
                case 'min_badges':
                    $badgeCount = $this->db->fetchColumn(
                        "SELECT COUNT(*) FROM user_badges WHERE user_id = :user_id",
                        ['user_id' => $userId]
                    );
                    if ($badgeCount < $value) {
                        return false;
                    }
                    break;
                    
                case 'account_age_days':
                    $accountAge = $this->db->fetchColumn(
                        "SELECT DATEDIFF(NOW(), created_at) FROM users WHERE id = :user_id",
                        ['user_id' => $userId]
                    );
                    if ($accountAge < $value) {
                        return false;
                    }
                    break;
            }
        }
        
        return true;
    }
    
    private function checkConditions(int $userId, array $reward, array $data): bool {
        $conditions = json_decode($reward['conditions'], true);
        
        if (empty($conditions)) {
            return true;
        }
        
        foreach ($conditions as $condition => $value) {
            switch ($condition) {
                case 'daily_limit':
                    $todayCount = $this->db->fetchColumn(
                        "SELECT COUNT(*) FROM user_rewards 
                         WHERE user_id = :user_id AND reward_id = :reward_id 
                         AND DATE(awarded_at) = CURDATE()",
                        ['user_id' => $userId, 'reward_id' => $reward['id']]
                    );
                    if ($todayCount >= $value) {
                        return false;
                    }
                    break;
                    
                case 'weekly_limit':
                    $weekCount = $this->db->fetchColumn(
                        "SELECT COUNT(*) FROM user_rewards 
                         WHERE user_id = :user_id AND reward_id = :reward_id 
                         AND YEARWEEK(awarded_at) = YEARWEEK(NOW())",
                        ['user_id' => $userId, 'reward_id' => $reward['id']]
                    );
                    if ($weekCount >= $value) {
                        return false;
                    }
                    break;
                    
                case 'monthly_limit':
                    $monthCount = $this->db->fetchColumn(
                        "SELECT COUNT(*) FROM user_rewards 
                         WHERE user_id = :user_id AND reward_id = :reward_id 
                         AND YEAR(awarded_at) = YEAR(NOW()) AND MONTH(awarded_at) = MONTH(NOW())",
                        ['user_id' => $userId, 'reward_id' => $reward['id']]
                    );
                    if ($monthCount >= $value) {
                        return false;
                    }
                    break;
                    
                case 'total_limit':
                    $totalCount = $this->db->fetchColumn(
                        "SELECT COUNT(*) FROM user_rewards 
                         WHERE user_id = :user_id AND reward_id = :reward_id",
                        ['user_id' => $userId, 'reward_id' => $reward['id']]
                    );
                    if ($totalCount >= $value) {
                        return false;
                    }
                    break;
            }
        }
        
        return true;
    }
    
    public function hasReward(int $userId, int $rewardId): bool {
        $count = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM user_rewards 
             WHERE user_id = :user_id AND reward_id = :reward_id AND is_active = 1",
            ['user_id' => $userId, 'reward_id' => $rewardId]
        );
        
        return $count > 0;
    }
    
    public function getUserRewards(int $userId): array {
        return $this->db->fetchAll(
            "SELECT r.*, ur.awarded_at, ur.data, ur.is_active
             FROM user_rewards ur
             JOIN rewards r ON ur.reward_id = r.id
             WHERE ur.user_id = :user_id AND ur.is_active = 1
             ORDER BY ur.awarded_at DESC",
            ['user_id' => $userId]
        );
    }
    
    public function getReward(int $rewardId): ?array {
        $reward = $this->db->fetch(
            "SELECT * FROM rewards WHERE id = :reward_id",
            ['reward_id' => $rewardId]
        );
        
        return $reward ?: null;
    }
    
    public function getRewardsByType(string $type): array {
        return $this->db->fetchAll(
            "SELECT * FROM rewards WHERE type = :type AND is_active = 1 ORDER BY rarity, name",
            ['type' => $type]
        );
    }
    
    public function getRewardsByCategory(string $category): array {
        return $this->db->fetchAll(
            "SELECT * FROM rewards WHERE category = :category AND is_active = 1 ORDER BY rarity, name",
            ['category' => $category]
        );
    }
    
    public function getRewardsByRarity(string $rarity): array {
        return $this->db->fetchAll(
            "SELECT * FROM rewards WHERE rarity = :rarity AND is_active = 1 ORDER BY name",
            ['rarity' => $rarity]
        );
    }
    
    public function getAvailableRewards(int $userId): array {
        return $this->db->fetchAll(
            "SELECT r.* FROM rewards r
             WHERE r.is_active = 1 
             AND r.id NOT IN (
                 SELECT reward_id FROM user_rewards 
                 WHERE user_id = :user_id AND is_active = 1
             )
             ORDER BY r.rarity, r.name",
            ['user_id' => $userId]
        );
    }
    
    public function getRewardStats(): array {
        return [
            'total_rewards' => $this->db->fetchColumn("SELECT COUNT(*) FROM rewards"),
            'active_rewards' => $this->db->fetchColumn("SELECT COUNT(*) FROM rewards WHERE is_active = 1"),
            'total_awarded' => $this->db->fetchColumn("SELECT COUNT(*) FROM user_rewards"),
            'rewards_by_type' => $this->getRewardsByTypeStats(),
            'rewards_by_rarity' => $this->getRewardsByRarityStats(),
            'rewards_by_category' => $this->getRewardsByCategoryStats(),
            'top_rewards' => $this->getTopRewards()
        ];
    }
    
    private function getRewardsByTypeStats(): array {
        return $this->db->fetchAll(
            "SELECT type, COUNT(*) as count, COUNT(ur.id) as awarded_count
             FROM rewards r
             LEFT JOIN user_rewards ur ON r.id = ur.reward_id
             GROUP BY type
             ORDER BY count DESC"
        );
    }
    
    private function getRewardsByRarityStats(): array {
        return $this->db->fetchAll(
            "SELECT rarity, COUNT(*) as count, COUNT(ur.id) as awarded_count
             FROM rewards r
             LEFT JOIN user_rewards ur ON r.id = ur.reward_id
             GROUP BY rarity
             ORDER BY count DESC"
        );
    }
    
    private function getRewardsByCategoryStats(): array {
        return $this->db->fetchAll(
            "SELECT category, COUNT(*) as count, COUNT(ur.id) as awarded_count
             FROM rewards r
             LEFT JOIN user_rewards ur ON r.id = ur.reward_id
             GROUP BY category
             ORDER BY count DESC"
        );
    }
    
    private function getTopRewards(int $limit = 10): array {
        return $this->db->fetchAll(
            "SELECT r.*, COUNT(ur.id) as awarded_count
             FROM rewards r
             LEFT JOIN user_rewards ur ON r.id = ur.reward_id
             GROUP BY r.id
             ORDER BY awarded_count DESC
             LIMIT :limit",
            ['limit' => $limit]
        );
    }
    
    public function getRewardTypes(): array {
        return $this->rewardTypes;
    }
    
    public function updateReward(int $rewardId, array $data): bool {
        try {
            $data['updated_at'] = date('Y-m-d H:i:s');
            
            $this->db->update(
                'rewards',
                $data,
                'id = :reward_id',
                ['reward_id' => $rewardId]
            );
            
            return true;
        } catch (\Exception $e) {
            error_log("Error updating reward: " . $e->getMessage());
            return false;
        }
    }
    
    public function deleteReward(int $rewardId): bool {
        try {
            $this->db->beginTransaction();
            
            // Deactivate user rewards
            $this->db->update(
                'user_rewards',
                ['is_active' => false],
                'reward_id = :reward_id',
                ['reward_id' => $rewardId]
            );
            
            // Delete the reward
            $this->db->delete('rewards', 'id = :reward_id', ['reward_id' => $rewardId]);
            
            $this->db->commit();
            
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            error_log("Error deleting reward: " . $e->getMessage());
            return false;
        }
    }
    
    public function revokeReward(int $userId, int $rewardId): bool {
        try {
            $this->db->update(
                'user_rewards',
                ['is_active' => false, 'revoked_at' => date('Y-m-d H:i:s')],
                'user_id = :user_id AND reward_id = :reward_id',
                ['user_id' => $userId, 'reward_id' => $rewardId]
            );
            
            return true;
        } catch (\Exception $e) {
            error_log("Error revoking reward: " . $e->getMessage());
            return false;
        }
    }
    
    public function getRewardHistory(int $userId, int $limit = 50): array {
        return $this->db->fetchAll(
            "SELECT r.*, ur.awarded_at, ur.data, ur.is_active, ur.revoked_at
             FROM user_rewards ur
             JOIN rewards r ON ur.reward_id = r.id
             WHERE ur.user_id = :user_id
             ORDER BY ur.awarded_at DESC
             LIMIT :limit",
            ['user_id' => $userId, 'limit' => $limit]
        );
    }
    
    public function getRewardLeaderboard(int $limit = 10): array {
        return $this->db->fetchAll(
            "SELECT u.id, u.username, u.avatar, COUNT(ur.id) as reward_count,
                    SUM(CASE WHEN r.type = 'points' THEN r.value ELSE 0 END) as total_points
             FROM users u
             LEFT JOIN user_rewards ur ON u.id = ur.user_id AND ur.is_active = 1
             LEFT JOIN rewards r ON ur.reward_id = r.id
             GROUP BY u.id, u.username, u.avatar
             ORDER BY reward_count DESC, total_points DESC
             LIMIT :limit",
            ['limit' => $limit]
        );
    }
}