<?php
declare(strict_types=1);

namespace Services;

class AchievementService {
    private Database $db;
    private array $achievementTypes;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->achievementTypes = $this->getAchievementTypes();
    }
    
    private function getAchievementTypes(): array {
        return [
            'first_post' => [
                'name' => 'First Post',
                'description' => 'Create your first post',
                'icon' => '📝',
                'points' => 10,
                'rarity' => 'common',
                'category' => 'posting'
            ],
            'first_thread' => [
                'name' => 'Thread Starter',
                'description' => 'Start your first thread',
                'icon' => '🧵',
                'points' => 25,
                'rarity' => 'common',
                'category' => 'posting'
            ],
            'post_milestone_10' => [
                'name' => 'Getting Started',
                'description' => 'Make 10 posts',
                'icon' => '💬',
                'points' => 50,
                'rarity' => 'common',
                'category' => 'posting'
            ],
            'post_milestone_50' => [
                'name' => 'Active Member',
                'description' => 'Make 50 posts',
                'icon' => '🗣️',
                'points' => 100,
                'rarity' => 'uncommon',
                'category' => 'posting'
            ],
            'post_milestone_100' => [
                'name' => 'Forum Veteran',
                'description' => 'Make 100 posts',
                'icon' => '🏆',
                'points' => 200,
                'rarity' => 'rare',
                'category' => 'posting'
            ],
            'post_milestone_500' => [
                'name' => 'Forum Legend',
                'description' => 'Make 500 posts',
                'icon' => '👑',
                'points' => 500,
                'rarity' => 'epic',
                'category' => 'posting'
            ],
            'post_milestone_1000' => [
                'name' => 'Forum Master',
                'description' => 'Make 1000 posts',
                'icon' => '🌟',
                'points' => 1000,
                'rarity' => 'legendary',
                'category' => 'posting'
            ],
            'first_like' => [
                'name' => 'First Like',
                'description' => 'Receive your first like',
                'icon' => '👍',
                'points' => 5,
                'rarity' => 'common',
                'category' => 'social'
            ],
            'like_milestone_10' => [
                'name' => 'Popular',
                'description' => 'Receive 10 likes',
                'icon' => '❤️',
                'points' => 25,
                'rarity' => 'common',
                'category' => 'social'
            ],
            'like_milestone_50' => [
                'name' => 'Well Liked',
                'description' => 'Receive 50 likes',
                'icon' => '💕',
                'points' => 100,
                'rarity' => 'uncommon',
                'category' => 'social'
            ],
            'like_milestone_100' => [
                'name' => 'Community Favorite',
                'description' => 'Receive 100 likes',
                'icon' => '💖',
                'points' => 250,
                'rarity' => 'rare',
                'category' => 'social'
            ],
            'first_follower' => [
                'name' => 'First Follower',
                'description' => 'Gain your first follower',
                'icon' => '👥',
                'points' => 15,
                'rarity' => 'common',
                'category' => 'social'
            ],
            'follower_milestone_10' => [
                'name' => 'Influencer',
                'description' => 'Gain 10 followers',
                'icon' => '📈',
                'points' => 75,
                'rarity' => 'uncommon',
                'category' => 'social'
            ],
            'follower_milestone_50' => [
                'name' => 'Community Leader',
                'description' => 'Gain 50 followers',
                'icon' => '🎯',
                'points' => 200,
                'rarity' => 'rare',
                'category' => 'social'
            ],
            'daily_login_7' => [
                'name' => 'Week Warrior',
                'description' => 'Login for 7 consecutive days',
                'icon' => '📅',
                'points' => 50,
                'rarity' => 'uncommon',
                'category' => 'activity'
            ],
            'daily_login_30' => [
                'name' => 'Month Master',
                'description' => 'Login for 30 consecutive days',
                'icon' => '🗓️',
                'points' => 150,
                'rarity' => 'rare',
                'category' => 'activity'
            ],
            'helpful_answer' => [
                'name' => 'Helpful',
                'description' => 'Provide a helpful answer',
                'icon' => '🤝',
                'points' => 20,
                'rarity' => 'common',
                'category' => 'helpful'
            ],
            'helpful_milestone_10' => [
                'name' => 'Problem Solver',
                'description' => 'Provide 10 helpful answers',
                'icon' => '🔧',
                'points' => 100,
                'rarity' => 'uncommon',
                'category' => 'helpful'
            ],
            'helpful_milestone_25' => [
                'name' => 'Expert Helper',
                'description' => 'Provide 25 helpful answers',
                'icon' => '🎓',
                'points' => 250,
                'rarity' => 'rare',
                'category' => 'helpful'
            ],
            'early_adopter' => [
                'name' => 'Early Adopter',
                'description' => 'Join the forum in its first month',
                'icon' => '🚀',
                'points' => 100,
                'rarity' => 'rare',
                'category' => 'special'
            ],
            'beta_tester' => [
                'name' => 'Beta Tester',
                'description' => 'Participate in beta testing',
                'icon' => '🧪',
                'points' => 150,
                'rarity' => 'epic',
                'category' => 'special'
            ],
            'moderator' => [
                'name' => 'Moderator',
                'description' => 'Become a forum moderator',
                'icon' => '🛡️',
                'points' => 500,
                'rarity' => 'epic',
                'category' => 'special'
            ],
            'admin' => [
                'name' => 'Administrator',
                'description' => 'Become a forum administrator',
                'icon' => '👑',
                'points' => 1000,
                'rarity' => 'legendary',
                'category' => 'special'
            ]
        ];
    }
    
    public function checkAchievements(int $userId, string $action, array $data = []): array {
        $newAchievements = [];
        
        switch ($action) {
            case 'post_created':
                $newAchievements = array_merge($newAchievements, $this->checkPostAchievements($userId, $data));
                break;
            case 'post_liked':
                $newAchievements = array_merge($newAchievements, $this->checkLikeAchievements($userId, $data));
                break;
            case 'user_followed':
                $newAchievements = array_merge($newAchievements, $this->checkFollowerAchievements($userId, $data));
                break;
            case 'user_login':
                $newAchievements = array_merge($newAchievements, $this->checkLoginAchievements($userId, $data));
                break;
            case 'helpful_answer':
                $newAchievements = array_merge($newAchievements, $this->checkHelpfulAchievements($userId, $data));
                break;
        }
        
        return $newAchievements;
    }
    
    private function checkPostAchievements(int $userId, array $data): array {
        $achievements = [];
        
        // Check first post
        if (!$this->hasAchievement($userId, 'first_post')) {
            $achievements[] = $this->awardAchievement($userId, 'first_post');
        }
        
        // Check first thread
        if (isset($data['is_thread']) && $data['is_thread'] && !$this->hasAchievement($userId, 'first_thread')) {
            $achievements[] = $this->awardAchievement($userId, 'first_thread');
        }
        
        // Check post milestones
        $postCount = $this->getUserPostCount($userId);
        $milestones = [10, 50, 100, 500, 1000];
        
        foreach ($milestones as $milestone) {
            $achievementKey = "post_milestone_{$milestone}";
            if ($postCount >= $milestone && !$this->hasAchievement($userId, $achievementKey)) {
                $achievements[] = $this->awardAchievement($userId, $achievementKey);
            }
        }
        
        return $achievements;
    }
    
    private function checkLikeAchievements(int $userId, array $data): array {
        $achievements = [];
        
        // Check first like
        if (!$this->hasAchievement($userId, 'first_like')) {
            $achievements[] = $this->awardAchievement($userId, 'first_like');
        }
        
        // Check like milestones
        $likeCount = $this->getUserLikeCount($userId);
        $milestones = [10, 50, 100];
        
        foreach ($milestones as $milestone) {
            $achievementKey = "like_milestone_{$milestone}";
            if ($likeCount >= $milestone && !$this->hasAchievement($userId, $achievementKey)) {
                $achievements[] = $this->awardAchievement($userId, $achievementKey);
            }
        }
        
        return $achievements;
    }
    
    private function checkFollowerAchievements(int $userId, array $data): array {
        $achievements = [];
        
        // Check first follower
        if (!$this->hasAchievement($userId, 'first_follower')) {
            $achievements[] = $this->awardAchievement($userId, 'first_follower');
        }
        
        // Check follower milestones
        $followerCount = $this->getUserFollowerCount($userId);
        $milestones = [10, 50];
        
        foreach ($milestones as $milestone) {
            $achievementKey = "follower_milestone_{$milestone}";
            if ($followerCount >= $milestone && !$this->hasAchievement($userId, $achievementKey)) {
                $achievements[] = $this->awardAchievement($userId, $achievementKey);
            }
        }
        
        return $achievements;
    }
    
    private function checkLoginAchievements(int $userId, array $data): array {
        $achievements = [];
        
        // Check daily login streaks
        $loginStreak = $this->getUserLoginStreak($userId);
        $milestones = [7, 30];
        
        foreach ($milestones as $milestone) {
            $achievementKey = "daily_login_{$milestone}";
            if ($loginStreak >= $milestone && !$this->hasAchievement($userId, $achievementKey)) {
                $achievements[] = $this->awardAchievement($userId, $achievementKey);
            }
        }
        
        return $achievements;
    }
    
    private function checkHelpfulAchievements(int $userId, array $data): array {
        $achievements = [];
        
        // Check first helpful answer
        if (!$this->hasAchievement($userId, 'helpful_answer')) {
            $achievements[] = $this->awardAchievement($userId, 'helpful_answer');
        }
        
        // Check helpful milestones
        $helpfulCount = $this->getUserHelpfulCount($userId);
        $milestones = [10, 25];
        
        foreach ($milestones as $milestone) {
            $achievementKey = "helpful_milestone_{$milestone}";
            if ($helpfulCount >= $milestone && !$this->hasAchievement($userId, $achievementKey)) {
                $achievements[] = $this->awardAchievement($userId, $achievementKey);
            }
        }
        
        return $achievements;
    }
    
    private function awardAchievement(int $userId, string $achievementKey): array {
        if (!isset($this->achievementTypes[$achievementKey])) {
            return [];
        }
        
        $achievement = $this->achievementTypes[$achievementKey];
        
        try {
            // Award the achievement
            $this->db->insert('user_achievements', [
                'user_id' => $userId,
                'achievement_key' => $achievementKey,
                'achievement_name' => $achievement['name'],
                'achievement_description' => $achievement['description'],
                'achievement_icon' => $achievement['icon'],
                'points' => $achievement['points'],
                'rarity' => $achievement['rarity'],
                'category' => $achievement['category'],
                'awarded_at' => date('Y-m-d H:i:s')
            ]);
            
            // Update user points
            $this->addUserPoints($userId, $achievement['points']);
            
            // Send notification
            $this->sendAchievementNotification($userId, $achievement);
            
            return [
                'achievement_key' => $achievementKey,
                'name' => $achievement['name'],
                'description' => $achievement['description'],
                'icon' => $achievement['icon'],
                'points' => $achievement['points'],
                'rarity' => $achievement['rarity'],
                'category' => $achievement['category']
            ];
            
        } catch (\Exception $e) {
            error_log("Error awarding achievement: " . $e->getMessage());
            return [];
        }
    }
    
    private function hasAchievement(int $userId, string $achievementKey): bool {
        return $this->db->exists(
            'user_achievements',
            'user_id = :user_id AND achievement_key = :achievement_key',
            ['user_id' => $userId, 'achievement_key' => $achievementKey]
        );
    }
    
    private function addUserPoints(int $userId, int $points): void {
        $this->db->query(
            "UPDATE users SET points = points + :points WHERE id = :user_id",
            ['points' => $points, 'user_id' => $userId]
        );
    }
    
    private function sendAchievementNotification(int $userId, array $achievement): void {
        $notificationService = new NotificationService();
        
        $notificationService->sendNotification(
            $userId,
            'achievement',
            [
                'title' => 'Achievement Unlocked!',
                'message' => "You've earned the '{$achievement['name']}' achievement!",
                'achievement' => $achievement
            ],
            ['push', 'browser', 'in_app']
        );
    }
    
    public function getUserAchievements(int $userId): array {
        return $this->db->fetchAll(
            "SELECT * FROM user_achievements 
             WHERE user_id = :user_id 
             ORDER BY awarded_at DESC",
            ['user_id' => $userId]
        );
    }
    
    public function getUserAchievementsByCategory(int $userId, string $category): array {
        return $this->db->fetchAll(
            "SELECT * FROM user_achievements 
             WHERE user_id = :user_id AND category = :category 
             ORDER BY awarded_at DESC",
            ['user_id' => $userId, 'category' => $category]
        );
    }
    
    public function getUserPoints(int $userId): int {
        $user = $this->db->fetch(
            "SELECT points FROM users WHERE id = :user_id",
            ['user_id' => $userId]
        );
        
        return $user ? (int) $user['points'] : 0;
    }
    
    public function getUserLevel(int $userId): int {
        $points = $this->getUserPoints($userId);
        return $this->calculateLevel($points);
    }
    
    private function calculateLevel(int $points): int {
        // Level calculation: Level = floor(points / 100) + 1
        return floor($points / 100) + 1;
    }
    
    public function getUserRank(int $userId): int {
        return $this->db->fetchColumn(
            "SELECT COUNT(*) + 1 FROM users WHERE points > (SELECT points FROM users WHERE id = :user_id)",
            ['user_id' => $userId]
        );
    }
    
    public function getLeaderboard(int $limit = 10): array {
        return $this->db->fetchAll(
            "SELECT u.id, u.username, u.avatar, u.points, u.level,
                    COUNT(ua.id) as achievement_count
             FROM users u
             LEFT JOIN user_achievements ua ON u.id = ua.user_id
             GROUP BY u.id, u.username, u.avatar, u.points, u.level
             ORDER BY u.points DESC, achievement_count DESC
             LIMIT :limit",
            ['limit' => $limit]
        );
    }
    
    public function getAchievementStats(): array {
        return [
            'total_achievements' => count($this->achievementTypes),
            'achievements_by_category' => $this->getAchievementsByCategory(),
            'achievements_by_rarity' => $this->getAchievementsByRarity(),
            'most_earned' => $this->getMostEarnedAchievements(),
            'least_earned' => $this->getLeastEarnedAchievements()
        ];
    }
    
    private function getAchievementsByCategory(): array {
        $categories = [];
        
        foreach ($this->achievementTypes as $achievement) {
            $category = $achievement['category'];
            if (!isset($categories[$category])) {
                $categories[$category] = 0;
            }
            $categories[$category]++;
        }
        
        return $categories;
    }
    
    private function getAchievementsByRarity(): array {
        $rarities = [];
        
        foreach ($this->achievementTypes as $achievement) {
            $rarity = $achievement['rarity'];
            if (!isset($rarities[$rarity])) {
                $rarities[$rarity] = 0;
            }
            $rarities[$rarity]++;
        }
        
        return $rarities;
    }
    
    private function getMostEarnedAchievements(): array {
        return $this->db->fetchAll(
            "SELECT achievement_key, achievement_name, COUNT(*) as earned_count
             FROM user_achievements
             GROUP BY achievement_key, achievement_name
             ORDER BY earned_count DESC
             LIMIT 10"
        );
    }
    
    private function getLeastEarnedAchievements(): array {
        return $this->db->fetchAll(
            "SELECT achievement_key, achievement_name, COUNT(*) as earned_count
             FROM user_achievements
             GROUP BY achievement_key, achievement_name
             ORDER BY earned_count ASC
             LIMIT 10"
        );
    }
    
    private function getUserPostCount(int $userId): int {
        return $this->db->fetchColumn(
            "SELECT COUNT(*) FROM posts WHERE user_id = :user_id",
            ['user_id' => $userId]
        );
    }
    
    private function getUserLikeCount(int $userId): int {
        return $this->db->fetchColumn(
            "SELECT COUNT(*) FROM post_likes pl
             JOIN posts p ON pl.post_id = p.id
             WHERE p.user_id = :user_id",
            ['user_id' => $userId]
        );
    }
    
    private function getUserFollowerCount(int $userId): int {
        return $this->db->fetchColumn(
            "SELECT COUNT(*) FROM user_follows WHERE following_id = :user_id",
            ['user_id' => $userId]
        );
    }
    
    private function getUserLoginStreak(int $userId): int {
        // This would need to be implemented based on login tracking
        return 0;
    }
    
    private function getUserHelpfulCount(int $userId): int {
        return $this->db->fetchColumn(
            "SELECT COUNT(*) FROM post_likes pl
             JOIN posts p ON pl.post_id = p.id
             WHERE p.user_id = :user_id AND pl.is_helpful = 1",
            ['user_id' => $userId]
        );
    }
    
    public function createCustomAchievement(string $key, array $achievement): bool {
        try {
            $this->db->insert('custom_achievements', [
                'achievement_key' => $key,
                'name' => $achievement['name'],
                'description' => $achievement['description'],
                'icon' => $achievement['icon'],
                'points' => $achievement['points'],
                'rarity' => $achievement['rarity'],
                'category' => $achievement['category'],
                'created_by' => Auth::getUserId(),
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            return true;
        } catch (\Exception $e) {
            error_log("Error creating custom achievement: " . $e->getMessage());
            return false;
        }
    }
    
    public function getAchievementProgress(int $userId, string $achievementKey): array {
        $achievement = $this->achievementTypes[$achievementKey] ?? null;
        
        if (!$achievement) {
            return [];
        }
        
        $progress = [
            'achievement_key' => $achievementKey,
            'name' => $achievement['name'],
            'description' => $achievement['description'],
            'icon' => $achievement['icon'],
            'points' => $achievement['points'],
            'rarity' => $achievement['rarity'],
            'category' => $achievement['category'],
            'earned' => $this->hasAchievement($userId, $achievementKey),
            'progress' => 0,
            'max_progress' => 1
        ];
        
        // Calculate progress based on achievement type
        switch ($achievementKey) {
            case 'post_milestone_10':
                $progress['progress'] = min($this->getUserPostCount($userId), 10);
                $progress['max_progress'] = 10;
                break;
            case 'post_milestone_50':
                $progress['progress'] = min($this->getUserPostCount($userId), 50);
                $progress['max_progress'] = 50;
                break;
            case 'like_milestone_10':
                $progress['progress'] = min($this->getUserLikeCount($userId), 10);
                $progress['max_progress'] = 10;
                break;
            // Add more cases as needed
        }
        
        return $progress;
    }
}