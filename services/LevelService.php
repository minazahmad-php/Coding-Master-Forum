<?php
declare(strict_types=1);

namespace Services;

class LevelService {
    private Database $db;
    private array $levelConfig;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->levelConfig = $this->getLevelConfig();
    }
    
    private function getLevelConfig(): array {
        return [
            'base_points' => 100,
            'multiplier' => 1.2,
            'max_level' => 100,
            'prestige_enabled' => true,
            'prestige_threshold' => 50,
            'level_titles' => [
                1 => 'Newcomer',
                5 => 'Explorer',
                10 => 'Contributor',
                15 => 'Helper',
                20 => 'Expert',
                25 => 'Mentor',
                30 => 'Specialist',
                35 => 'Authority',
                40 => 'Master',
                45 => 'Legend',
                50 => 'Elite',
                60 => 'Champion',
                70 => 'Hero',
                80 => 'Icon',
                90 => 'Legendary',
                100 => 'Mythical'
            ],
            'level_colors' => [
                1 => '#808080',   // Gray
                5 => '#4CAF50',   // Green
                10 => '#2196F3',  // Blue
                15 => '#FF9800',  // Orange
                20 => '#9C27B0',  // Purple
                25 => '#E91E63',  // Pink
                30 => '#00BCD4',  // Cyan
                35 => '#FF5722',  // Deep Orange
                40 => '#795548',  // Brown
                45 => '#607D8B',  // Blue Grey
                50 => '#FFD700',  // Gold
                60 => '#FF6B6B',  // Red
                70 => '#4ECDC4',  // Teal
                80 => '#45B7D1',  // Sky Blue
                90 => '#96CEB4',  // Mint
                100 => '#FECA57'  // Yellow
            ],
            'level_benefits' => [
                5 => [
                    'custom_avatar_border' => true,
                    'priority_support' => false,
                    'exclusive_badges' => false,
                    'beta_access' => false,
                    'moderator_consideration' => false
                ],
                10 => [
                    'custom_avatar_border' => true,
                    'priority_support' => true,
                    'exclusive_badges' => false,
                    'beta_access' => false,
                    'moderator_consideration' => false
                ],
                20 => [
                    'custom_avatar_border' => true,
                    'priority_support' => true,
                    'exclusive_badges' => true,
                    'beta_access' => false,
                    'moderator_consideration' => false
                ],
                30 => [
                    'custom_avatar_border' => true,
                    'priority_support' => true,
                    'exclusive_badges' => true,
                    'beta_access' => true,
                    'moderator_consideration' => false
                ],
                40 => [
                    'custom_avatar_border' => true,
                    'priority_support' => true,
                    'exclusive_badges' => true,
                    'beta_access' => true,
                    'moderator_consideration' => true
                ],
                50 => [
                    'custom_avatar_border' => true,
                    'priority_support' => true,
                    'exclusive_badges' => true,
                    'beta_access' => true,
                    'moderator_consideration' => true,
                    'prestige_unlocked' => true
                ]
            ]
        ];
    }
    
    public function calculateLevel(int $points): int {
        $basePoints = $this->levelConfig['base_points'];
        $multiplier = $this->levelConfig['multiplier'];
        $maxLevel = $this->levelConfig['max_level'];
        
        $level = 1;
        $requiredPoints = 0;
        
        while ($level < $maxLevel) {
            $requiredPoints += $basePoints * pow($multiplier, $level - 1);
            
            if ($points < $requiredPoints) {
                break;
            }
            
            $level++;
        }
        
        return min($level, $maxLevel);
    }
    
    public function getPointsRequiredForLevel(int $level): int {
        if ($level <= 1) {
            return 0;
        }
        
        $basePoints = $this->levelConfig['base_points'];
        $multiplier = $this->levelConfig['multiplier'];
        
        $totalPoints = 0;
        for ($i = 1; $i < $level; $i++) {
            $totalPoints += $basePoints * pow($multiplier, $i - 1);
        }
        
        return (int) $totalPoints;
    }
    
    public function getPointsNeededForNextLevel(int $currentLevel, int $currentPoints): int {
        $nextLevel = $currentLevel + 1;
        $pointsRequired = $this->getPointsRequiredForLevel($nextLevel);
        
        return max(0, $pointsRequired - $currentPoints);
    }
    
    public function getLevelProgress(int $currentLevel, int $currentPoints): array {
        $currentLevelPoints = $this->getPointsRequiredForLevel($currentLevel);
        $nextLevelPoints = $this->getPointsRequiredForLevel($currentLevel + 1);
        
        $progressPoints = $currentPoints - $currentLevelPoints;
        $totalPointsNeeded = $nextLevelPoints - $currentLevelPoints;
        
        $progressPercentage = $totalPointsNeeded > 0 ? 
            round(($progressPoints / $totalPointsNeeded) * 100, 2) : 100;
        
        return [
            'current_level' => $currentLevel,
            'next_level' => $currentLevel + 1,
            'current_points' => $currentPoints,
            'level_points' => $currentLevelPoints,
            'next_level_points' => $nextLevelPoints,
            'progress_points' => $progressPoints,
            'total_points_needed' => $totalPointsNeeded,
            'progress_percentage' => $progressPercentage,
            'points_needed' => $this->getPointsNeededForNextLevel($currentLevel, $currentPoints)
        ];
    }
    
    public function getLevelTitle(int $level): string {
        $titles = $this->levelConfig['level_titles'];
        
        // Find the highest title for this level
        $highestTitle = 'Newcomer';
        foreach ($titles as $titleLevel => $title) {
            if ($level >= $titleLevel) {
                $highestTitle = $title;
            }
        }
        
        return $highestTitle;
    }
    
    public function getLevelColor(int $level): string {
        $colors = $this->levelConfig['level_colors'];
        
        // Find the highest color for this level
        $highestColor = '#808080'; // Default gray
        foreach ($colors as $colorLevel => $color) {
            if ($level >= $colorLevel) {
                $highestColor = $color;
            }
        }
        
        return $highestColor;
    }
    
    public function getLevelBenefits(int $level): array {
        $benefits = $this->levelConfig['level_benefits'];
        
        // Find the highest benefits for this level
        $highestBenefits = [
            'custom_avatar_border' => false,
            'priority_support' => false,
            'exclusive_badges' => false,
            'beta_access' => false,
            'moderator_consideration' => false,
            'prestige_unlocked' => false
        ];
        
        foreach ($benefits as $benefitLevel => $levelBenefits) {
            if ($level >= $benefitLevel) {
                $highestBenefits = array_merge($highestBenefits, $levelBenefits);
            }
        }
        
        return $highestBenefits;
    }
    
    public function getUserLevel(int $userId): array {
        $user = $this->db->fetch(
            "SELECT points, level, prestige_level FROM users WHERE id = :user_id",
            ['user_id' => $userId]
        );
        
        if (!$user) {
            return [
                'level' => 1,
                'points' => 0,
                'prestige_level' => 0,
                'title' => 'Newcomer',
                'color' => '#808080',
                'benefits' => $this->getLevelBenefits(1),
                'progress' => $this->getLevelProgress(1, 0)
            ];
        }
        
        $level = (int) $user['level'];
        $points = (int) $user['points'];
        $prestigeLevel = (int) $user['prestige_level'];
        
        return [
            'level' => $level,
            'points' => $points,
            'prestige_level' => $prestigeLevel,
            'title' => $this->getLevelTitle($level),
            'color' => $this->getLevelColor($level),
            'benefits' => $this->getLevelBenefits($level),
            'progress' => $this->getLevelProgress($level, $points)
        ];
    }
    
    public function updateUserLevel(int $userId): bool {
        $user = $this->db->fetch(
            "SELECT points, level, prestige_level FROM users WHERE id = :user_id",
            ['user_id' => $userId]
        );
        
        if (!$user) {
            return false;
        }
        
        $currentPoints = (int) $user['points'];
        $currentLevel = (int) $user['level'];
        $currentPrestige = (int) $user['prestige_level'];
        
        $newLevel = $this->calculateLevel($currentPoints);
        
        // Check for prestige
        $newPrestige = $currentPrestige;
        if ($this->levelConfig['prestige_enabled'] && 
            $newLevel >= $this->levelConfig['prestige_threshold'] && 
            $newLevel > $currentLevel) {
            
            $prestigeGained = floor($newLevel / $this->levelConfig['prestige_threshold']);
            $newPrestige = $currentPrestige + $prestigeGained;
            
            // Reset level after prestige
            $newLevel = $newLevel % $this->levelConfig['prestige_threshold'];
            if ($newLevel == 0) {
                $newLevel = $this->levelConfig['prestige_threshold'];
            }
        }
        
        try {
            $this->db->update(
                'users',
                [
                    'level' => $newLevel,
                    'prestige_level' => $newPrestige,
                    'level_updated_at' => date('Y-m-d H:i:s')
                ],
                'id = :user_id',
                ['user_id' => $userId]
            );
            
            // Log level up event
            if ($newLevel > $currentLevel) {
                $this->logLevelUp($userId, $currentLevel, $newLevel, $currentPrestige, $newPrestige);
            }
            
            return true;
        } catch (\Exception $e) {
            error_log("Error updating user level: " . $e->getMessage());
            return false;
        }
    }
    
    private function logLevelUp(int $userId, int $oldLevel, int $newLevel, int $oldPrestige, int $newPrestige): void {
        try {
            $this->db->insert('level_up_logs', [
                'user_id' => $userId,
                'old_level' => $oldLevel,
                'new_level' => $newLevel,
                'old_prestige' => $oldPrestige,
                'new_prestige' => $newPrestige,
                'level_difference' => $newLevel - $oldLevel,
                'prestige_difference' => $newPrestige - $oldPrestige,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            error_log("Error logging level up: " . $e->getMessage());
        }
    }
    
    public function getLevelLeaderboard(int $limit = 10, int $offset = 0): array {
        return $this->db->fetchAll(
            "SELECT u.id, u.username, u.avatar, u.points, u.level, u.prestige_level,
                    u.level_updated_at,
                    COUNT(ua.id) as achievement_count,
                    COUNT(ub.id) as badge_count
             FROM users u
             LEFT JOIN user_achievements ua ON u.id = ua.user_id
             LEFT JOIN user_badges ub ON u.id = ub.user_id
             GROUP BY u.id, u.username, u.avatar, u.points, u.level, u.prestige_level, u.level_updated_at
             ORDER BY u.prestige_level DESC, u.level DESC, u.points DESC
             LIMIT :offset, :limit",
            ['offset' => $offset, 'limit' => $limit]
        );
    }
    
    public function getLevelDistribution(): array {
        return $this->db->fetchAll(
            "SELECT level, COUNT(*) as user_count
             FROM users 
             GROUP BY level 
             ORDER BY level ASC"
        );
    }
    
    public function getPrestigeDistribution(): array {
        return $this->db->fetchAll(
            "SELECT prestige_level, COUNT(*) as user_count
             FROM users 
             GROUP BY prestige_level 
             ORDER BY prestige_level ASC"
        );
    }
    
    public function getLevelStats(): array {
        return [
            'total_users' => $this->db->fetchColumn("SELECT COUNT(*) FROM users"),
            'average_level' => $this->db->fetchColumn("SELECT AVG(level) FROM users"),
            'highest_level' => $this->db->fetchColumn("SELECT MAX(level) FROM users"),
            'highest_prestige' => $this->db->fetchColumn("SELECT MAX(prestige_level) FROM users"),
            'level_distribution' => $this->getLevelDistribution(),
            'prestige_distribution' => $this->getPrestigeDistribution(),
            'recent_level_ups' => $this->getRecentLevelUps()
        ];
    }
    
    private function getRecentLevelUps(int $limit = 10): array {
        return $this->db->fetchAll(
            "SELECT ull.*, u.username, u.avatar
             FROM level_up_logs ull
             JOIN users u ON ull.user_id = u.id
             ORDER BY ull.created_at DESC
             LIMIT :limit",
            ['limit' => $limit]
        );
    }
    
    public function getLevelRewards(int $level): array {
        $rewards = [];
        
        // Base rewards for each level
        $rewards[] = [
            'type' => 'points',
            'amount' => $level * 10,
            'description' => "Level {$level} bonus points"
        ];
        
        // Special rewards for milestone levels
        if ($level % 10 == 0) {
            $rewards[] = [
                'type' => 'badge',
                'badge_id' => "level_{$level}",
                'description' => "Level {$level} milestone badge"
            ];
        }
        
        if ($level % 25 == 0) {
            $rewards[] = [
                'type' => 'achievement',
                'achievement_id' => "level_{$level}_milestone",
                'description' => "Level {$level} milestone achievement"
            ];
        }
        
        if ($level >= 50 && $this->levelConfig['prestige_enabled']) {
            $rewards[] = [
                'type' => 'prestige',
                'amount' => 1,
                'description' => "Prestige level unlocked"
            ];
        }
        
        return $rewards;
    }
    
    public function awardLevelRewards(int $userId, int $level): bool {
        $rewards = $this->getLevelRewards($level);
        
        try {
            $this->db->beginTransaction();
            
            foreach ($rewards as $reward) {
                switch ($reward['type']) {
                    case 'points':
                        $this->db->query(
                            "UPDATE users SET points = points + :points WHERE id = :user_id",
                            ['points' => $reward['amount'], 'user_id' => $userId]
                        );
                        break;
                        
                    case 'badge':
                        $this->db->insert('user_badges', [
                            'user_id' => $userId,
                            'badge_id' => $reward['badge_id'],
                            'earned_at' => date('Y-m-d H:i:s')
                        ]);
                        break;
                        
                    case 'achievement':
                        $this->db->insert('user_achievements', [
                            'user_id' => $userId,
                            'achievement_id' => $reward['achievement_id'],
                            'earned_at' => date('Y-m-d H:i:s')
                        ]);
                        break;
                }
            }
            
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            error_log("Error awarding level rewards: " . $e->getMessage());
            return false;
        }
    }
    
    public function getLevelConfig(): array {
        return $this->levelConfig;
    }
    
    public function updateLevelConfig(array $config): bool {
        try {
            $this->levelConfig = array_merge($this->levelConfig, $config);
            
            // Update in database if needed
            $this->db->update(
                'level_config',
                [
                    'config' => json_encode($this->levelConfig),
                    'updated_at' => date('Y-m-d H:i:s')
                ],
                'id = 1'
            );
            
            return true;
        } catch (\Exception $e) {
            error_log("Error updating level config: " . $e->getMessage());
            return false;
        }
    }
    
    public function getLevelComparison(int $userId1, int $userId2): array {
        $user1 = $this->getUserLevel($userId1);
        $user2 = $this->getUserLevel($userId2);
        
        return [
            'user1' => $user1,
            'user2' => $user2,
            'comparison' => [
                'level_difference' => $user1['level'] - $user2['level'],
                'points_difference' => $user1['points'] - $user2['points'],
                'prestige_difference' => $user1['prestige_level'] - $user2['prestige_level'],
                'higher_level_user' => $user1['level'] > $user2['level'] ? $userId1 : $userId2,
                'higher_points_user' => $user1['points'] > $user2['points'] ? $userId1 : $userId2
            ]
        ];
    }
    
    public function getLevelHistory(int $userId, int $limit = 50): array {
        return $this->db->fetchAll(
            "SELECT * FROM level_up_logs 
             WHERE user_id = :user_id 
             ORDER BY created_at DESC 
             LIMIT :limit",
            ['user_id' => $userId, 'limit' => $limit]
        );
    }
}