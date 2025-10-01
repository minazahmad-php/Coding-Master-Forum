<?php
declare(strict_types=1);

namespace Services;

class DailyLoginRewardsService {
    private Database $db;
    private array $rewardTiers;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->rewardTiers = $this->getRewardTiers();
    }
    
    private function getRewardTiers(): array {
        return [
            1 => [
                'name' => 'Day 1',
                'points' => 10,
                'description' => 'Welcome back!',
                'icon' => 'fas fa-gift',
                'color' => '#4CAF50'
            ],
            2 => [
                'name' => 'Day 2',
                'points' => 15,
                'description' => 'Keep it up!',
                'icon' => 'fas fa-gift',
                'color' => '#2196F3'
            ],
            3 => [
                'name' => 'Day 3',
                'points' => 20,
                'description' => 'Three days strong!',
                'icon' => 'fas fa-gift',
                'color' => '#FF9800'
            ],
            4 => [
                'name' => 'Day 4',
                'points' => 25,
                'description' => 'Almost a week!',
                'icon' => 'fas fa-gift',
                'color' => '#9C27B0'
            ],
            5 => [
                'name' => 'Day 5',
                'points' => 30,
                'description' => 'Halfway there!',
                'icon' => 'fas fa-gift',
                'color' => '#E91E63'
            ],
            6 => [
                'name' => 'Day 6',
                'description' => 'One more day!',
                'icon' => 'fas fa-gift',
                'color' => '#00BCD4'
            ],
            7 => [
                'name' => 'Day 7',
                'points' => 50,
                'description' => 'Weekly bonus!',
                'icon' => 'fas fa-star',
                'color' => '#FFD700',
                'bonus' => true
            ],
            14 => [
                'name' => 'Day 14',
                'points' => 100,
                'description' => 'Two weeks!',
                'icon' => 'fas fa-star',
                'color' => '#FFD700',
                'bonus' => true
            ],
            21 => [
                'name' => 'Day 21',
                'points' => 150,
                'description' => 'Three weeks!',
                'icon' => 'fas fa-star',
                'color' => '#FFD700',
                'bonus' => true
            ],
            30 => [
                'name' => 'Day 30',
                'points' => 250,
                'description' => 'Monthly milestone!',
                'icon' => 'fas fa-crown',
                'color' => '#FFD700',
                'bonus' => true
            ],
            60 => [
                'name' => 'Day 60',
                'points' => 500,
                'description' => 'Two months!',
                'icon' => 'fas fa-crown',
                'color' => '#FFD700',
                'bonus' => true
            ],
            90 => [
                'name' => 'Day 90',
                'points' => 750,
                'description' => 'Three months!',
                'icon' => 'fas fa-crown',
                'color' => '#FFD700',
                'bonus' => true
            ],
            180 => [
                'name' => 'Day 180',
                'points' => 1000,
                'description' => 'Half year!',
                'icon' => 'fas fa-crown',
                'color' => '#FFD700',
                'bonus' => true
            ],
            365 => [
                'name' => 'Day 365',
                'points' => 2500,
                'description' => 'One year!',
                'icon' => 'fas fa-crown',
                'color' => '#FFD700',
                'bonus' => true
            ]
        ];
    }
    
    public function claimDailyReward(int $userId): array {
        $today = date('Y-m-d');
        
        // Check if user already claimed today
        $alreadyClaimed = $this->db->fetch(
            "SELECT * FROM daily_login_rewards 
             WHERE user_id = :user_id AND DATE(claimed_at) = :today",
            ['user_id' => $userId, 'today' => $today]
        );
        
        if ($alreadyClaimed) {
            return [
                'success' => false,
                'message' => 'You have already claimed your daily reward today',
                'reward' => null
            ];
        }
        
        try {
            $this->db->beginTransaction();
            
            // Get current streak
            $streak = $this->getCurrentStreak($userId);
            $streakDay = $streak['streak_day'];
            
            // Get reward for current streak day
            $reward = $this->getRewardForDay($streakDay);
            
            if (!$reward) {
                $this->db->rollback();
                return [
                    'success' => false,
                    'message' => 'No reward available for this day',
                    'reward' => null
                ];
            }
            
            // Award points
            if (isset($reward['points']) && $reward['points'] > 0) {
                $this->db->query(
                    "UPDATE users SET points = points + :points WHERE id = :user_id",
                    ['points' => $reward['points'], 'user_id' => $userId]
                );
            }
            
            // Award bonus items if applicable
            $bonusItems = $this->getBonusItems($streakDay);
            foreach ($bonusItems as $item) {
                $this->awardBonusItem($userId, $item);
            }
            
            // Record the claim
            $this->db->insert('daily_login_rewards', [
                'user_id' => $userId,
                'streak_day' => $streakDay,
                'points_awarded' => $reward['points'] ?? 0,
                'bonus_items' => json_encode($bonusItems),
                'claimed_at' => date('Y-m-d H:i:s')
            ]);
            
            // Update streak
            $this->updateStreak($userId, $streakDay);
            
            $this->db->commit();
            
            return [
                'success' => true,
                'message' => 'Daily reward claimed successfully!',
                'reward' => $reward,
                'streak_day' => $streakDay,
                'bonus_items' => $bonusItems
            ];
            
        } catch (\Exception $e) {
            $this->db->rollback();
            error_log("Error claiming daily reward: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error claiming daily reward',
                'reward' => null
            ];
        }
    }
    
    private function getCurrentStreak(int $userId): array {
        $streak = $this->db->fetch(
            "SELECT * FROM user_login_streaks WHERE user_id = :user_id",
            ['user_id' => $userId]
        );
        
        if (!$streak) {
            // Create new streak
            $this->db->insert('user_login_streaks', [
                'user_id' => $userId,
                'streak_day' => 1,
                'last_login' => date('Y-m-d'),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            return [
                'streak_day' => 1,
                'last_login' => date('Y-m-d')
            ];
        }
        
        $lastLogin = $streak['last_login'];
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        
        if ($lastLogin === $yesterday) {
            // Continue streak
            $newStreakDay = $streak['streak_day'] + 1;
        } elseif ($lastLogin === $today) {
            // Already logged in today
            $newStreakDay = $streak['streak_day'];
        } else {
            // Reset streak
            $newStreakDay = 1;
        }
        
        return [
            'streak_day' => $newStreakDay,
            'last_login' => $lastLogin
        ];
    }
    
    private function updateStreak(int $userId, int $streakDay): void {
        $this->db->update(
            'user_login_streaks',
            [
                'streak_day' => $streakDay,
                'last_login' => date('Y-m-d'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            'user_id = :user_id',
            ['user_id' => $userId]
        );
    }
    
    private function getRewardForDay(int $day): ?array {
        return $this->rewardTiers[$day] ?? null;
    }
    
    private function getBonusItems(int $day): array {
        $bonusItems = [];
        
        // Weekly bonus items
        if ($day % 7 === 0) {
            $bonusItems[] = [
                'type' => 'badge',
                'badge_id' => 'weekly_login_' . floor($day / 7),
                'name' => 'Weekly Login Badge',
                'description' => 'Earned for ' . floor($day / 7) . ' weeks of consecutive login'
            ];
        }
        
        // Monthly bonus items
        if ($day % 30 === 0) {
            $bonusItems[] = [
                'type' => 'achievement',
                'achievement_id' => 'monthly_login_' . floor($day / 30),
                'name' => 'Monthly Login Achievement',
                'description' => 'Earned for ' . floor($day / 30) . ' months of consecutive login'
            ];
        }
        
        // Special milestone items
        if ($day === 100) {
            $bonusItems[] = [
                'type' => 'title',
                'title' => 'Century Login',
                'description' => 'Special title for 100 days of consecutive login'
            ];
        }
        
        if ($day === 365) {
            $bonusItems[] = [
                'type' => 'title',
                'title' => 'Yearly Login',
                'description' => 'Special title for 365 days of consecutive login'
            ];
        }
        
        return $bonusItems;
    }
    
    private function awardBonusItem(int $userId, array $item): void {
        switch ($item['type']) {
            case 'badge':
                $this->db->insert('user_badges', [
                    'user_id' => $userId,
                    'badge_id' => $item['badge_id'],
                    'earned_at' => date('Y-m-d H:i:s')
                ]);
                break;
                
            case 'achievement':
                $this->db->insert('user_achievements', [
                    'user_id' => $userId,
                    'achievement_id' => $item['achievement_id'],
                    'earned_at' => date('Y-m-d H:i:s')
                ]);
                break;
                
            case 'title':
                $this->db->update(
                    'users',
                    ['custom_title' => $item['title']],
                    'id = :user_id',
                    ['user_id' => $userId]
                );
                break;
        }
    }
    
    public function getUserStreak(int $userId): array {
        $streak = $this->db->fetch(
            "SELECT * FROM user_login_streaks WHERE user_id = :user_id",
            ['user_id' => $userId]
        );
        
        if (!$streak) {
            return [
                'streak_day' => 0,
                'last_login' => null,
                'is_active' => false
            ];
        }
        
        $lastLogin = $streak['last_login'];
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        
        $isActive = $lastLogin === $today || $lastLogin === $yesterday;
        
        return [
            'streak_day' => $streak['streak_day'],
            'last_login' => $lastLogin,
            'is_active' => $isActive
        ];
    }
    
    public function getUserRewardHistory(int $userId, int $limit = 30): array {
        return $this->db->fetchAll(
            "SELECT * FROM daily_login_rewards 
             WHERE user_id = :user_id 
             ORDER BY claimed_at DESC 
             LIMIT :limit",
            ['user_id' => $userId, 'limit' => $limit]
        );
    }
    
    public function getRewardTiers(): array {
        return $this->rewardTiers;
    }
    
    public function getNextReward(int $userId): array {
        $streak = $this->getUserStreak($userId);
        $nextDay = $streak['streak_day'] + 1;
        
        $nextReward = $this->getRewardForDay($nextDay);
        
        return [
            'next_day' => $nextDay,
            'reward' => $nextReward,
            'bonus_items' => $this->getBonusItems($nextDay)
        ];
    }
    
    public function getStreakLeaderboard(int $limit = 10): array {
        return $this->db->fetchAll(
            "SELECT u.id, u.username, u.avatar, uls.streak_day, uls.last_login,
                    COUNT(dlr.id) as total_claims, SUM(dlr.points_awarded) as total_points
             FROM user_login_streaks uls
             JOIN users u ON uls.user_id = u.id
             LEFT JOIN daily_login_rewards dlr ON uls.user_id = dlr.user_id
             GROUP BY u.id, u.username, u.avatar, uls.streak_day, uls.last_login
             ORDER BY uls.streak_day DESC, uls.last_login DESC
             LIMIT :limit",
            ['limit' => $limit]
        );
    }
    
    public function getDailyRewardStats(): array {
        return [
            'total_claims' => $this->db->fetchColumn("SELECT COUNT(*) FROM daily_login_rewards"),
            'total_points_awarded' => $this->db->fetchColumn("SELECT SUM(points_awarded) FROM daily_login_rewards"),
            'active_streaks' => $this->db->fetchColumn(
                "SELECT COUNT(*) FROM user_login_streaks 
                 WHERE last_login >= DATE_SUB(NOW(), INTERVAL 1 DAY)"
            ),
            'average_streak' => $this->db->fetchColumn("SELECT AVG(streak_day) FROM user_login_streaks"),
            'longest_streak' => $this->db->fetchColumn("SELECT MAX(streak_day) FROM user_login_streaks"),
            'claims_by_day' => $this->getClaimsByDay(),
            'streak_distribution' => $this->getStreakDistribution()
        ];
    }
    
    private function getClaimsByDay(): array {
        return $this->db->fetchAll(
            "SELECT DATE(claimed_at) as date, COUNT(*) as claims, SUM(points_awarded) as points
             FROM daily_login_rewards 
             WHERE claimed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             GROUP BY DATE(claimed_at) 
             ORDER BY date DESC"
        );
    }
    
    private function getStreakDistribution(): array {
        return $this->db->fetchAll(
            "SELECT streak_day, COUNT(*) as user_count
             FROM user_login_streaks 
             GROUP BY streak_day 
             ORDER BY streak_day"
        );
    }
    
    public function getRewardCalendar(int $userId): array {
        $streak = $this->getUserStreak($userId);
        $currentDay = $streak['streak_day'];
        
        $calendar = [];
        for ($day = 1; $day <= 30; $day++) {
            $reward = $this->getRewardForDay($day);
            $bonusItems = $this->getBonusItems($day);
            
            $calendar[] = [
                'day' => $day,
                'reward' => $reward,
                'bonus_items' => $bonusItems,
                'is_achieved' => $day < $currentDay,
                'is_current' => $day === $currentDay,
                'is_available' => $day <= $currentDay + 1
            ];
        }
        
        return $calendar;
    }
    
    public function getStreakMilestones(int $userId): array {
        $streak = $this->getUserStreak($userId);
        $currentDay = $streak['streak_day'];
        
        $milestones = [
            7 => ['name' => 'Weekly', 'description' => '7 days streak'],
            14 => ['name' => 'Bi-weekly', 'description' => '14 days streak'],
            30 => ['name' => 'Monthly', 'description' => '30 days streak'],
            60 => ['name' => 'Bi-monthly', 'description' => '60 days streak'],
            90 => ['name' => 'Quarterly', 'description' => '90 days streak'],
            180 => ['name' => 'Half-yearly', 'description' => '180 days streak'],
            365 => ['name' => 'Yearly', 'description' => '365 days streak']
        ];
        
        $achievedMilestones = [];
        foreach ($milestones as $day => $milestone) {
            $achievedMilestones[] = [
                'day' => $day,
                'name' => $milestone['name'],
                'description' => $milestone['description'],
                'achieved' => $currentDay >= $day,
                'progress' => min(100, round(($currentDay / $day) * 100, 2))
            ];
        }
        
        return $achievedMilestones;
    }
    
    public function resetStreak(int $userId): bool {
        try {
            $this->db->update(
                'user_login_streaks',
                [
                    'streak_day' => 0,
                    'last_login' => null,
                    'updated_at' => date('Y-m-d H:i:s')
                ],
                'user_id = :user_id',
                ['user_id' => $userId]
            );
            
            return true;
        } catch (\Exception $e) {
            error_log("Error resetting streak: " . $e->getMessage());
            return false;
        }
    }
    
    public function getStreakAnalytics(int $userId): array {
        $streak = $this->getUserStreak($userId);
        $history = $this->getUserRewardHistory($userId, 365);
        
        $totalClaims = count($history);
        $totalPoints = array_sum(array_column($history, 'points_awarded'));
        $averagePoints = $totalClaims > 0 ? round($totalPoints / $totalClaims, 2) : 0;
        
        $claimsByMonth = [];
        foreach ($history as $claim) {
            $month = date('Y-m', strtotime($claim['claimed_at']));
            if (!isset($claimsByMonth[$month])) {
                $claimsByMonth[$month] = 0;
            }
            $claimsByMonth[$month]++;
        }
        
        return [
            'current_streak' => $streak['streak_day'],
            'is_active' => $streak['is_active'],
            'total_claims' => $totalClaims,
            'total_points' => $totalPoints,
            'average_points' => $averagePoints,
            'claims_by_month' => $claimsByMonth,
            'longest_streak' => $this->getLongestStreak($userId)
        ];
    }
    
    private function getLongestStreak(int $userId): int {
        $history = $this->getUserRewardHistory($userId, 365);
        $longestStreak = 0;
        $currentStreak = 0;
        $lastDate = null;
        
        foreach ($history as $claim) {
            $claimDate = date('Y-m-d', strtotime($claim['claimed_at']));
            
            if ($lastDate === null || $claimDate === date('Y-m-d', strtotime($lastDate . ' +1 day'))) {
                $currentStreak++;
            } else {
                $longestStreak = max($longestStreak, $currentStreak);
                $currentStreak = 1;
            }
            
            $lastDate = $claimDate;
        }
        
        return max($longestStreak, $currentStreak);
    }
}