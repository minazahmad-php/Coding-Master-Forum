<?php
declare(strict_types=1);

namespace Services;

class PointsService {
    private Database $db;
    private array $pointRules;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->pointRules = $this->getPointRules();
    }
    
    private function getPointRules(): array {
        return [
            'post_created' => [
                'points' => 5,
                'description' => 'Points for creating a post',
                'category' => 'posting',
                'daily_limit' => 50
            ],
            'thread_created' => [
                'points' => 10,
                'description' => 'Points for creating a thread',
                'category' => 'posting',
                'daily_limit' => 20
            ],
            'post_liked' => [
                'points' => 2,
                'description' => 'Points for receiving a like',
                'category' => 'social',
                'daily_limit' => 100
            ],
            'post_helpful' => [
                'points' => 5,
                'description' => 'Points for receiving a helpful mark',
                'category' => 'helpful',
                'daily_limit' => 50
            ],
            'user_followed' => [
                'points' => 3,
                'description' => 'Points for gaining a follower',
                'category' => 'social',
                'daily_limit' => 30
            ],
            'daily_login' => [
                'points' => 1,
                'description' => 'Points for daily login',
                'category' => 'activity',
                'daily_limit' => 1
            ],
            'login_streak_7' => [
                'points' => 10,
                'description' => 'Bonus points for 7-day login streak',
                'category' => 'activity',
                'daily_limit' => 1
            ],
            'login_streak_30' => [
                'points' => 50,
                'description' => 'Bonus points for 30-day login streak',
                'category' => 'activity',
                'daily_limit' => 1
            ],
            'achievement_earned' => [
                'points' => 0, // Points are defined in achievement
                'description' => 'Points from achievements',
                'category' => 'achievement',
                'daily_limit' => 0
            ],
            'badge_earned' => [
                'points' => 0, // No direct points for badges
                'description' => 'Points from badges',
                'category' => 'badge',
                'daily_limit' => 0
            ],
            'first_post' => [
                'points' => 25,
                'description' => 'Bonus points for first post',
                'category' => 'milestone',
                'daily_limit' => 1
            ],
            'first_thread' => [
                'points' => 50,
                'description' => 'Bonus points for first thread',
                'category' => 'milestone',
                'daily_limit' => 1
            ],
            'post_milestone_10' => [
                'points' => 100,
                'description' => 'Bonus points for 10 posts',
                'category' => 'milestone',
                'daily_limit' => 1
            ],
            'post_milestone_50' => [
                'points' => 250,
                'description' => 'Bonus points for 50 posts',
                'category' => 'milestone',
                'daily_limit' => 1
            ],
            'post_milestone_100' => [
                'points' => 500,
                'description' => 'Bonus points for 100 posts',
                'category' => 'milestone',
                'daily_limit' => 1
            ],
            'helpful_answer' => [
                'points' => 10,
                'description' => 'Points for providing helpful answer',
                'category' => 'helpful',
                'daily_limit' => 20
            ],
            'question_asked' => [
                'points' => 3,
                'description' => 'Points for asking a question',
                'category' => 'posting',
                'daily_limit' => 10
            ],
            'answer_provided' => [
                'points' => 2,
                'description' => 'Points for providing an answer',
                'category' => 'posting',
                'daily_limit' => 30
            ],
            'profile_completed' => [
                'points' => 20,
                'description' => 'Points for completing profile',
                'category' => 'profile',
                'daily_limit' => 1
            ],
            'avatar_uploaded' => [
                'points' => 10,
                'description' => 'Points for uploading avatar',
                'category' => 'profile',
                'daily_limit' => 1
            ],
            'bio_added' => [
                'points' => 5,
                'description' => 'Points for adding bio',
                'category' => 'profile',
                'daily_limit' => 1
            ],
            'location_added' => [
                'points' => 5,
                'description' => 'Points for adding location',
                'category' => 'profile',
                'daily_limit' => 1
            ],
            'website_added' => [
                'points' => 5,
                'description' => 'Points for adding website',
                'category' => 'profile',
                'daily_limit' => 1
            ],
            'social_links_added' => [
                'points' => 10,
                'description' => 'Points for adding social links',
                'category' => 'profile',
                'daily_limit' => 1
            ],
            'referral_signup' => [
                'points' => 50,
                'description' => 'Points for referring a new user',
                'category' => 'referral',
                'daily_limit' => 10
            ],
            'referral_active' => [
                'points' => 25,
                'description' => 'Points when referral becomes active',
                'category' => 'referral',
                'daily_limit' => 10
            ],
            'moderation_action' => [
                'points' => 5,
                'description' => 'Points for moderation actions',
                'category' => 'moderation',
                'daily_limit' => 50
            ],
            'report_submitted' => [
                'points' => 2,
                'description' => 'Points for submitting reports',
                'category' => 'moderation',
                'daily_limit' => 10
            ],
            'bug_report' => [
                'points' => 15,
                'description' => 'Points for reporting bugs',
                'category' => 'feedback',
                'daily_limit' => 5
            ],
            'feature_request' => [
                'points' => 10,
                'description' => 'Points for feature requests',
                'category' => 'feedback',
                'daily_limit' => 5
            ],
            'feedback_provided' => [
                'points' => 5,
                'description' => 'Points for providing feedback',
                'category' => 'feedback',
                'daily_limit' => 10
            ]
        ];
    }
    
    public function awardPoints(int $userId, string $action, array $data = []): bool {
        if (!isset($this->pointRules[$action])) {
            return false;
        }
        
        $rule = $this->pointRules[$action];
        $points = $rule['points'];
        
        // Check daily limit
        if ($rule['daily_limit'] > 0 && !$this->canAwardPoints($userId, $action, $rule['daily_limit'])) {
            return false;
        }
        
        try {
            $this->db->beginTransaction();
            
            // Award points
            $this->db->insert('point_transactions', [
                'user_id' => $userId,
                'action' => $action,
                'points' => $points,
                'description' => $rule['description'],
                'category' => $rule['category'],
                'data' => json_encode($data),
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            // Update user points
            $this->db->query(
                "UPDATE users SET points = points + :points WHERE id = :user_id",
                ['points' => $points, 'user_id' => $userId]
            );
            
            // Update user level
            $this->updateUserLevel($userId);
            
            $this->db->commit();
            
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            error_log("Error awarding points: " . $e->getMessage());
            return false;
        }
    }
    
    private function canAwardPoints(int $userId, string $action, int $dailyLimit): bool {
        $today = date('Y-m-d');
        
        $count = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM point_transactions 
             WHERE user_id = :user_id AND action = :action AND DATE(created_at) = :today",
            ['user_id' => $userId, 'action' => $action, 'today' => $today]
        );
        
        return $count < $dailyLimit;
    }
    
    private function updateUserLevel(int $userId): void {
        $user = $this->db->fetch(
            "SELECT points FROM users WHERE id = :user_id",
            ['user_id' => $userId]
        );
        
        if (!$user) {
            return;
        }
        
        $newLevel = $this->calculateLevel($user['points']);
        
        $this->db->update(
            'users',
            ['level' => $newLevel],
            'id = :user_id',
            ['user_id' => $userId]
        );
    }
    
    private function calculateLevel(int $points): int {
        // Level calculation: Level = floor(points / 100) + 1
        return floor($points / 100) + 1;
    }
    
    public function getUserPoints(int $userId): int {
        $user = $this->db->fetch(
            "SELECT points FROM users WHERE id = :user_id",
            ['user_id' => $userId]
        );
        
        return $user ? (int) $user['points'] : 0;
    }
    
    public function getUserLevel(int $userId): int {
        $user = $this->db->fetch(
            "SELECT level FROM users WHERE id = :user_id",
            ['user_id' => $userId]
        );
        
        return $user ? (int) $user['level'] : 1;
    }
    
    public function getUserPointHistory(int $userId, int $limit = 50): array {
        return $this->db->fetchAll(
            "SELECT * FROM point_transactions 
             WHERE user_id = :user_id 
             ORDER BY created_at DESC 
             LIMIT :limit",
            ['user_id' => $userId, 'limit' => $limit]
        );
    }
    
    public function getUserPointsByCategory(int $userId): array {
        return $this->db->fetchAll(
            "SELECT category, SUM(points) as total_points, COUNT(*) as transaction_count
             FROM point_transactions 
             WHERE user_id = :user_id 
             GROUP BY category 
             ORDER BY total_points DESC",
            ['user_id' => $userId]
        );
    }
    
    public function getUserDailyPoints(int $userId, int $days = 30): array {
        return $this->db->fetchAll(
            "SELECT DATE(created_at) as date, SUM(points) as daily_points
             FROM point_transactions 
             WHERE user_id = :user_id 
             AND created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
             GROUP BY DATE(created_at) 
             ORDER BY date DESC",
            ['user_id' => $userId, 'days' => $days]
        );
    }
    
    public function getUserRank(int $userId): int {
        return $this->db->fetchColumn(
            "SELECT COUNT(*) + 1 FROM users WHERE points > (SELECT points FROM users WHERE id = :user_id)",
            ['user_id' => $userId]
        );
    }
    
    public function getTopEarners(int $limit = 10): array {
        return $this->db->fetchAll(
            "SELECT u.id, u.username, u.avatar, u.points, u.level,
                    COUNT(pt.id) as transaction_count,
                    SUM(pt.points) as total_earned
             FROM users u
             LEFT JOIN point_transactions pt ON u.id = pt.user_id
             GROUP BY u.id, u.username, u.avatar, u.points, u.level
             ORDER BY u.points DESC
             LIMIT :limit",
            ['limit' => $limit]
        );
    }
    
    public function getPointsLeaderboard(int $limit = 10, int $offset = 0): array {
        return $this->db->fetchAll(
            "SELECT u.id, u.username, u.avatar, u.points, u.level,
                    COUNT(pt.id) as transaction_count,
                    COUNT(ua.id) as achievement_count,
                    COUNT(ub.id) as badge_count
             FROM users u
             LEFT JOIN point_transactions pt ON u.id = pt.user_id
             LEFT JOIN user_achievements ua ON u.id = ua.user_id
             LEFT JOIN user_badges ub ON u.id = ub.user_id
             GROUP BY u.id, u.username, u.avatar, u.points, u.level
             ORDER BY u.points DESC, achievement_count DESC, badge_count DESC
             LIMIT :offset, :limit",
            ['offset' => $offset, 'limit' => $limit]
        );
    }
    
    public function getPointsStats(): array {
        return [
            'total_points_awarded' => $this->db->fetchColumn(
                "SELECT SUM(points) FROM point_transactions"
            ),
            'total_transactions' => $this->db->fetchColumn(
                "SELECT COUNT(*) FROM point_transactions"
            ),
            'average_points_per_user' => $this->db->fetchColumn(
                "SELECT AVG(points) FROM users"
            ),
            'points_by_category' => $this->getPointsByCategory(),
            'points_by_action' => $this->getPointsByAction(),
            'daily_points_trend' => $this->getDailyPointsTrend()
        ];
    }
    
    private function getPointsByCategory(): array {
        return $this->db->fetchAll(
            "SELECT category, SUM(points) as total_points, COUNT(*) as transaction_count
             FROM point_transactions 
             GROUP BY category 
             ORDER BY total_points DESC"
        );
    }
    
    private function getPointsByAction(): array {
        return $this->db->fetchAll(
            "SELECT action, SUM(points) as total_points, COUNT(*) as transaction_count
             FROM point_transactions 
             GROUP BY action 
             ORDER BY total_points DESC"
        );
    }
    
    private function getDailyPointsTrend(int $days = 30): array {
        return $this->db->fetchAll(
            "SELECT DATE(created_at) as date, SUM(points) as daily_points, COUNT(*) as transaction_count
             FROM point_transactions 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
             GROUP BY DATE(created_at) 
             ORDER BY date DESC",
            ['days' => $days]
        );
    }
    
    public function deductPoints(int $userId, int $points, string $reason, array $data = []): bool {
        if ($points <= 0) {
            return false;
        }
        
        $userPoints = $this->getUserPoints($userId);
        if ($userPoints < $points) {
            return false;
        }
        
        try {
            $this->db->beginTransaction();
            
            // Deduct points
            $this->db->insert('point_transactions', [
                'user_id' => $userId,
                'action' => 'points_deducted',
                'points' => -$points,
                'description' => $reason,
                'category' => 'deduction',
                'data' => json_encode($data),
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            // Update user points
            $this->db->query(
                "UPDATE users SET points = points - :points WHERE id = :user_id",
                ['points' => $points, 'user_id' => $userId]
            );
            
            // Update user level
            $this->updateUserLevel($userId);
            
            $this->db->commit();
            
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            error_log("Error deducting points: " . $e->getMessage());
            return false;
        }
    }
    
    public function transferPoints(int $fromUserId, int $toUserId, int $points, string $reason): bool {
        if ($points <= 0) {
            return false;
        }
        
        $fromUserPoints = $this->getUserPoints($fromUserId);
        if ($fromUserPoints < $points) {
            return false;
        }
        
        try {
            $this->db->beginTransaction();
            
            // Deduct from sender
            $this->db->insert('point_transactions', [
                'user_id' => $fromUserId,
                'action' => 'points_transferred_out',
                'points' => -$points,
                'description' => "Transferred to user {$toUserId}: {$reason}",
                'category' => 'transfer',
                'data' => json_encode(['to_user_id' => $toUserId, 'reason' => $reason]),
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            // Add to receiver
            $this->db->insert('point_transactions', [
                'user_id' => $toUserId,
                'action' => 'points_transferred_in',
                'points' => $points,
                'description' => "Received from user {$fromUserId}: {$reason}",
                'category' => 'transfer',
                'data' => json_encode(['from_user_id' => $fromUserId, 'reason' => $reason]),
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            // Update user points
            $this->db->query(
                "UPDATE users SET points = points - :points WHERE id = :from_user_id",
                ['points' => $points, 'from_user_id' => $fromUserId]
            );
            
            $this->db->query(
                "UPDATE users SET points = points + :points WHERE id = :to_user_id",
                ['points' => $points, 'to_user_id' => $toUserId]
            );
            
            // Update user levels
            $this->updateUserLevel($fromUserId);
            $this->updateUserLevel($toUserId);
            
            $this->db->commit();
            
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            error_log("Error transferring points: " . $e->getMessage());
            return false;
        }
    }
    
    public function getPointRules(): array {
        return $this->pointRules;
    }
    
    public function updatePointRule(string $action, array $rule): bool {
        if (!isset($this->pointRules[$action])) {
            return false;
        }
        
        $this->pointRules[$action] = array_merge($this->pointRules[$action], $rule);
        
        // Update in database if needed
        try {
            $this->db->update(
                'point_rules',
                [
                    'points' => $rule['points'],
                    'description' => $rule['description'],
                    'category' => $rule['category'],
                    'daily_limit' => $rule['daily_limit'],
                    'updated_at' => date('Y-m-d H:i:s')
                ],
                'action = :action',
                ['action' => $action]
            );
            
            return true;
        } catch (\Exception $e) {
            error_log("Error updating point rule: " . $e->getMessage());
            return false;
        }
    }
    
    public function createCustomPointRule(string $action, array $rule): bool {
        try {
            $this->db->insert('point_rules', [
                'action' => $action,
                'points' => $rule['points'],
                'description' => $rule['description'],
                'category' => $rule['category'],
                'daily_limit' => $rule['daily_limit'],
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            $this->pointRules[$action] = $rule;
            
            return true;
        } catch (\Exception $e) {
            error_log("Error creating custom point rule: " . $e->getMessage());
            return false;
        }
    }
    
    public function getLevelRequirements(): array {
        $requirements = [];
        
        for ($level = 1; $level <= 100; $level++) {
            $requirements[$level] = [
                'level' => $level,
                'points_required' => ($level - 1) * 100,
                'points_for_next' => $level * 100,
                'title' => $this->getLevelTitle($level),
                'benefits' => $this->getLevelBenefits($level)
            ];
        }
        
        return $requirements;
    }
    
    private function getLevelTitle(int $level): string {
        if ($level >= 50) return 'Legend';
        if ($level >= 40) return 'Master';
        if ($level >= 30) return 'Expert';
        if ($level >= 20) return 'Veteran';
        if ($level >= 10) return 'Advanced';
        if ($level >= 5) return 'Intermediate';
        return 'Beginner';
    }
    
    private function getLevelBenefits(int $level): array {
        $benefits = [];
        
        if ($level >= 5) {
            $benefits[] = 'Custom avatar border';
        }
        
        if ($level >= 10) {
            $benefits[] = 'Priority support';
        }
        
        if ($level >= 20) {
            $benefits[] = 'Exclusive badges';
        }
        
        if ($level >= 30) {
            $benefits[] = 'Beta feature access';
        }
        
        if ($level >= 40) {
            $benefits[] = 'Moderator consideration';
        }
        
        if ($level >= 50) {
            $benefits[] = 'Admin consideration';
        }
        
        return $benefits;
    }
    
    public function getUserNextLevel(int $userId): array {
        $userLevel = $this->getUserLevel($userId);
        $userPoints = $this->getUserPoints($userId);
        
        $nextLevel = $userLevel + 1;
        $pointsRequired = $nextLevel * 100;
        $pointsNeeded = $pointsRequired - $userPoints;
        
        return [
            'current_level' => $userLevel,
            'next_level' => $nextLevel,
            'current_points' => $userPoints,
            'points_required' => $pointsRequired,
            'points_needed' => $pointsNeeded,
            'progress_percentage' => round(($userPoints / $pointsRequired) * 100, 2),
            'level_title' => $this->getLevelTitle($nextLevel),
            'benefits' => $this->getLevelBenefits($nextLevel)
        ];
    }
}