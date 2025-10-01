<?php
declare(strict_types=1);

namespace Services;

class StreaksService {
    private Database $db;
    private array $streakTypes;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->streakTypes = $this->getStreakTypes();
    }
    
    private function getStreakTypes(): array {
        return [
            'login' => [
                'name' => 'Login Streak',
                'description' => 'Consecutive days logged in',
                'icon' => 'fas fa-sign-in-alt',
                'color' => '#4CAF50',
                'bonus_points' => [1, 2, 5, 10, 25, 50, 100]
            ],
            'posting' => [
                'name' => 'Posting Streak',
                'description' => 'Consecutive days with posts',
                'icon' => 'fas fa-edit',
                'color' => '#2196F3',
                'bonus_points' => [2, 5, 10, 20, 50, 100, 200]
            ],
            'helpful' => [
                'name' => 'Helpful Streak',
                'description' => 'Consecutive days with helpful posts',
                'icon' => 'fas fa-thumbs-up',
                'color' => '#FF9800',
                'bonus_points' => [5, 10, 25, 50, 100, 200, 500]
            ],
            'participation' => [
                'name' => 'Participation Streak',
                'description' => 'Consecutive days with forum participation',
                'icon' => 'fas fa-comments',
                'color' => '#9C27B0',
                'bonus_points' => [1, 3, 7, 15, 30, 60, 120]
            ],
            'achievement' => [
                'name' => 'Achievement Streak',
                'description' => 'Consecutive days with achievements',
                'icon' => 'fas fa-trophy',
                'color' => '#E91E63',
                'bonus_points' => [10, 25, 50, 100, 250, 500, 1000]
            ],
            'badge' => [
                'name' => 'Badge Streak',
                'description' => 'Consecutive days with badges',
                'icon' => 'fas fa-medal',
                'color' => '#FFD700',
                'bonus_points' => [5, 15, 30, 60, 120, 250, 500]
            ]
        ];
    }
    
    public function updateStreak(int $userId, string $streakType, array $data = []): bool {
        if (!isset($this->streakTypes[$streakType])) {
            return false;
        }
        
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        
        try {
            $this->db->beginTransaction();
            
            // Get current streak
            $currentStreak = $this->getUserStreak($userId, $streakType);
            
            if ($currentStreak) {
                $lastActivity = $currentStreak['last_activity'];
                $streakCount = $currentStreak['streak_count'];
                
                if ($lastActivity === $yesterday) {
                    // Continue streak
                    $streakCount++;
                } elseif ($lastActivity === $today) {
                    // Already updated today
                    $this->db->commit();
                    return true;
                } else {
                    // Reset streak
                    $streakCount = 1;
                }
                
                // Update existing streak
                $this->db->update(
                    'user_streaks',
                    [
                        'streak_count' => $streakCount,
                        'last_activity' => $today,
                        'updated_at' => date('Y-m-d H:i:s')
                    ],
                    'user_id = :user_id AND streak_type = :streak_type',
                    ['user_id' => $userId, 'streak_type' => $streakType]
                );
            } else {
                // Create new streak
                $streakCount = 1;
                
                $this->db->insert('user_streaks', [
                    'user_id' => $userId,
                    'streak_type' => $streakType,
                    'streak_count' => $streakCount,
                    'last_activity' => $today,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            }
            
            // Award bonus points for milestone streaks
            $this->awardStreakBonus($userId, $streakType, $streakCount);
            
            // Log streak activity
            $this->logStreakActivity($userId, $streakType, $streakCount, $data);
            
            $this->db->commit();
            
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            error_log("Error updating streak: " . $e->getMessage());
            return false;
        }
    }
    
    private function awardStreakBonus(int $userId, string $streakType, int $streakCount): void {
        $bonusPoints = $this->streakTypes[$streakType]['bonus_points'];
        
        foreach ($bonusPoints as $milestone => $points) {
            if ($streakCount === $milestone + 1) {
                // Award bonus points
                $this->db->query(
                    "UPDATE users SET points = points + :points WHERE id = :user_id",
                    ['points' => $points, 'user_id' => $userId]
                );
                
                // Log bonus award
                $this->db->insert('streak_bonuses', [
                    'user_id' => $userId,
                    'streak_type' => $streakType,
                    'streak_count' => $streakCount,
                    'bonus_points' => $points,
                    'awarded_at' => date('Y-m-d H:i:s')
                ]);
                
                break;
            }
        }
    }
    
    private function logStreakActivity(int $userId, string $streakType, int $streakCount, array $data): void {
        $this->db->insert('streak_activities', [
            'user_id' => $userId,
            'streak_type' => $streakType,
            'streak_count' => $streakCount,
            'activity_data' => json_encode($data),
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    public function getUserStreak(int $userId, string $streakType): ?array {
        $streak = $this->db->fetch(
            "SELECT * FROM user_streaks 
             WHERE user_id = :user_id AND streak_type = :streak_type",
            ['user_id' => $userId, 'streak_type' => $streakType]
        );
        
        return $streak ?: null;
    }
    
    public function getUserStreaks(int $userId): array {
        return $this->db->fetchAll(
            "SELECT * FROM user_streaks 
             WHERE user_id = :user_id 
             ORDER BY streak_count DESC",
            ['user_id' => $userId]
        );
    }
    
    public function getStreakLeaderboard(string $streakType, int $limit = 10): array {
        return $this->db->fetchAll(
            "SELECT u.id, u.username, u.avatar, us.streak_count, us.last_activity,
                    COUNT(sb.id) as bonus_count, SUM(sb.bonus_points) as total_bonus
             FROM user_streaks us
             JOIN users u ON us.user_id = u.id
             LEFT JOIN streak_bonuses sb ON us.user_id = sb.user_id AND us.streak_type = sb.streak_type
             WHERE us.streak_type = :streak_type
             GROUP BY u.id, u.username, u.avatar, us.streak_count, us.last_activity
             ORDER BY us.streak_count DESC, us.last_activity DESC
             LIMIT :limit",
            ['streak_type' => $streakType, 'limit' => $limit]
        );
    }
    
    public function getStreakStats(): array {
        return [
            'total_streaks' => $this->db->fetchColumn("SELECT COUNT(*) FROM user_streaks"),
            'active_streaks' => $this->db->fetchColumn("SELECT COUNT(*) FROM user_streaks WHERE last_activity >= DATE_SUB(NOW(), INTERVAL 1 DAY)"),
            'streaks_by_type' => $this->getStreaksByTypeStats(),
            'top_streaks' => $this->getTopStreaks(),
            'streak_milestones' => $this->getStreakMilestones()
        ];
    }
    
    private function getStreaksByTypeStats(): array {
        return $this->db->fetchAll(
            "SELECT streak_type, 
                    COUNT(*) as total_streaks,
                    AVG(streak_count) as average_streak,
                    MAX(streak_count) as max_streak,
                    COUNT(CASE WHEN last_activity >= DATE_SUB(NOW(), INTERVAL 1 DAY) THEN 1 END) as active_streaks
             FROM user_streaks 
             GROUP BY streak_type 
             ORDER BY total_streaks DESC"
        );
    }
    
    private function getTopStreaks(int $limit = 10): array {
        return $this->db->fetchAll(
            "SELECT u.id, u.username, u.avatar, us.streak_type, us.streak_count, us.last_activity
             FROM user_streaks us
             JOIN users u ON us.user_id = u.id
             ORDER BY us.streak_count DESC, us.last_activity DESC
             LIMIT :limit",
            ['limit' => $limit]
        );
    }
    
    private function getStreakMilestones(): array {
        return $this->db->fetchAll(
            "SELECT streak_type, streak_count, COUNT(*) as user_count
             FROM user_streaks 
             GROUP BY streak_type, streak_count 
             ORDER BY streak_type, streak_count"
        );
    }
    
    public function getStreakTypes(): array {
        return $this->streakTypes;
    }
    
    public function getStreakHistory(int $userId, string $streakType, int $limit = 30): array {
        return $this->db->fetchAll(
            "SELECT * FROM streak_activities 
             WHERE user_id = :user_id AND streak_type = :streak_type 
             ORDER BY created_at DESC 
             LIMIT :limit",
            ['user_id' => $userId, 'streak_type' => $streakType, 'limit' => $limit]
        );
    }
    
    public function getStreakBonuses(int $userId, string $streakType = null): array {
        $query = "SELECT * FROM streak_bonuses WHERE user_id = :user_id";
        $params = ['user_id' => $userId];
        
        if ($streakType) {
            $query .= " AND streak_type = :streak_type";
            $params['streak_type'] = $streakType;
        }
        
        $query .= " ORDER BY awarded_at DESC";
        
        return $this->db->fetchAll($query, $params);
    }
    
    public function resetStreak(int $userId, string $streakType): bool {
        try {
            $this->db->update(
                'user_streaks',
                [
                    'streak_count' => 0,
                    'last_activity' => null,
                    'updated_at' => date('Y-m-d H:i:s')
                ],
                'user_id = :user_id AND streak_type = :streak_type',
                ['user_id' => $userId, 'streak_type' => $streakType]
            );
            
            return true;
        } catch (\Exception $e) {
            error_log("Error resetting streak: " . $e->getMessage());
            return false;
        }
    }
    
    public function getStreakMilestone(int $streakCount): array {
        $milestones = [
            1 => ['name' => 'Getting Started', 'description' => 'First day streak'],
            3 => ['name' => 'Building Momentum', 'description' => '3-day streak'],
            7 => ['name' => 'Weekly Warrior', 'description' => '7-day streak'],
            14 => ['name' => 'Two Week Champion', 'description' => '14-day streak'],
            30 => ['name' => 'Monthly Master', 'description' => '30-day streak'],
            60 => ['name' => 'Two Month Legend', 'description' => '60-day streak'],
            90 => ['name' => 'Quarterly King', 'description' => '90-day streak'],
            180 => ['name' => 'Half Year Hero', 'description' => '180-day streak'],
            365 => ['name' => 'Yearly Titan', 'description' => '365-day streak']
        ];
        
        $highestMilestone = null;
        foreach ($milestones as $days => $milestone) {
            if ($streakCount >= $days) {
                $highestMilestone = $milestone;
            }
        }
        
        return $highestMilestone ?: ['name' => 'Streak Master', 'description' => 'Beyond all milestones'];
    }
    
    public function getStreakProgress(int $userId, string $streakType): array {
        $streak = $this->getUserStreak($userId, $streakType);
        
        if (!$streak) {
            return [
                'streak_count' => 0,
                'next_milestone' => 1,
                'progress_percentage' => 0,
                'milestone_name' => 'Getting Started',
                'milestone_description' => 'First day streak'
            ];
        }
        
        $streakCount = $streak['streak_count'];
        $milestones = [1, 3, 7, 14, 30, 60, 90, 180, 365];
        
        $nextMilestone = null;
        foreach ($milestones as $milestone) {
            if ($streakCount < $milestone) {
                $nextMilestone = $milestone;
                break;
            }
        }
        
        if (!$nextMilestone) {
            $nextMilestone = $streakCount + 1;
        }
        
        $progressPercentage = $nextMilestone > 0 ? round(($streakCount / $nextMilestone) * 100, 2) : 0;
        
        return [
            'streak_count' => $streakCount,
            'next_milestone' => $nextMilestone,
            'progress_percentage' => $progressPercentage,
            'milestone_name' => $this->getStreakMilestone($streakCount)['name'],
            'milestone_description' => $this->getStreakMilestone($streakCount)['description']
        ];
    }
    
    public function getStreakComparison(int $userId1, int $userId2, string $streakType): array {
        $streak1 = $this->getUserStreak($userId1, $streakType);
        $streak2 = $this->getUserStreak($userId2, $streakType);
        
        $count1 = $streak1 ? $streak1['streak_count'] : 0;
        $count2 = $streak2 ? $streak2['streak_count'] : 0;
        
        return [
            'user1_streak' => $count1,
            'user2_streak' => $count2,
            'difference' => $count1 - $count2,
            'higher_streak_user' => $count1 > $count2 ? $userId1 : $userId2,
            'streak_type' => $streakType
        ];
    }
    
    public function getStreakAnalytics(int $userId): array {
        $streaks = $this->getUserStreaks($userId);
        $totalStreaks = count($streaks);
        $totalStreakDays = array_sum(array_column($streaks, 'streak_count'));
        $averageStreak = $totalStreaks > 0 ? round($totalStreakDays / $totalStreaks, 2) : 0;
        
        $longestStreak = 0;
        $longestStreakType = null;
        
        foreach ($streaks as $streak) {
            if ($streak['streak_count'] > $longestStreak) {
                $longestStreak = $streak['streak_count'];
                $longestStreakType = $streak['streak_type'];
            }
        }
        
        return [
            'total_streaks' => $totalStreaks,
            'total_streak_days' => $totalStreakDays,
            'average_streak' => $averageStreak,
            'longest_streak' => $longestStreak,
            'longest_streak_type' => $longestStreakType,
            'streaks' => $streaks
        ];
    }
    
    public function getStreakRewards(int $userId, string $streakType): array {
        $streak = $this->getUserStreak($userId, $streakType);
        $streakCount = $streak ? $streak['streak_count'] : 0;
        
        $bonusPoints = $this->streakTypes[$streakType]['bonus_points'];
        $rewards = [];
        
        foreach ($bonusPoints as $milestone => $points) {
            $rewards[] = [
                'milestone' => $milestone + 1,
                'points' => $points,
                'achieved' => $streakCount >= $milestone + 1,
                'next' => $streakCount === $milestone
            ];
        }
        
        return $rewards;
    }
}