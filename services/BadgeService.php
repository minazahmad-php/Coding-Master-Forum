<?php
declare(strict_types=1);

namespace Services;

class BadgeService {
    private Database $db;
    private array $badgeTypes;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->badgeTypes = $this->getBadgeTypes();
    }
    
    private function getBadgeTypes(): array {
        return [
            'newbie' => [
                'name' => 'Newbie',
                'description' => 'Welcome to the forum!',
                'icon' => '🆕',
                'color' => '#28a745',
                'rarity' => 'common',
                'category' => 'welcome',
                'requirements' => ['min_posts' => 1]
            ],
            'active_member' => [
                'name' => 'Active Member',
                'description' => 'Regular contributor to the community',
                'icon' => '💬',
                'color' => '#17a2b8',
                'rarity' => 'common',
                'category' => 'activity',
                'requirements' => ['min_posts' => 50, 'min_days_active' => 30]
            ],
            'helpful_member' => [
                'name' => 'Helpful Member',
                'description' => 'Known for providing helpful answers',
                'icon' => '🤝',
                'color' => '#ffc107',
                'rarity' => 'uncommon',
                'category' => 'helpful',
                'requirements' => ['min_helpful_posts' => 10]
            ],
            'expert' => [
                'name' => 'Expert',
                'description' => 'Recognized expert in their field',
                'icon' => '🎓',
                'color' => '#6f42c1',
                'rarity' => 'rare',
                'category' => 'expertise',
                'requirements' => ['min_helpful_posts' => 25, 'min_reputation' => 500]
            ],
            'mentor' => [
                'name' => 'Mentor',
                'description' => 'Helps guide new members',
                'icon' => '👨‍🏫',
                'color' => '#fd7e14',
                'rarity' => 'rare',
                'category' => 'mentorship',
                'requirements' => ['min_helpful_posts' => 50, 'min_followers' => 20]
            ],
            'veteran' => [
                'name' => 'Veteran',
                'description' => 'Long-time member of the community',
                'icon' => '🏆',
                'color' => '#dc3545',
                'rarity' => 'epic',
                'category' => 'loyalty',
                'requirements' => ['min_days_member' => 365, 'min_posts' => 200]
            ],
            'legend' => [
                'name' => 'Legend',
                'description' => 'Legendary member of the community',
                'icon' => '🌟',
                'color' => '#e83e8c',
                'rarity' => 'legendary',
                'category' => 'legendary',
                'requirements' => ['min_posts' => 1000, 'min_reputation' => 2000]
            ],
            'early_bird' => [
                'name' => 'Early Bird',
                'description' => 'Joined in the first month',
                'icon' => '🐦',
                'color' => '#20c997',
                'rarity' => 'rare',
                'category' => 'special',
                'requirements' => ['joined_before' => '2024-02-01']
            ],
            'beta_tester' => [
                'name' => 'Beta Tester',
                'description' => 'Helped test new features',
                'icon' => '🧪',
                'color' => '#6c757d',
                'rarity' => 'epic',
                'category' => 'special',
                'requirements' => ['beta_tester' => true]
            ],
            'moderator' => [
                'name' => 'Moderator',
                'description' => 'Forum moderator',
                'icon' => '🛡️',
                'color' => '#007bff',
                'rarity' => 'epic',
                'category' => 'staff',
                'requirements' => ['role' => 'moderator']
            ],
            'admin' => [
                'name' => 'Administrator',
                'description' => 'Forum administrator',
                'icon' => '👑',
                'color' => '#6f42c1',
                'rarity' => 'legendary',
                'category' => 'staff',
                'requirements' => ['role' => 'admin']
            ],
            'social_butterfly' => [
                'name' => 'Social Butterfly',
                'description' => 'Very active in social features',
                'icon' => '🦋',
                'color' => '#fd7e14',
                'rarity' => 'uncommon',
                'category' => 'social',
                'requirements' => ['min_followers' => 50, 'min_following' => 50]
            ],
            'night_owl' => [
                'name' => 'Night Owl',
                'description' => 'Most active during night hours',
                'icon' => '🦉',
                'color' => '#343a40',
                'rarity' => 'uncommon',
                'category' => 'activity',
                'requirements' => ['night_activity' => 0.7]
            ],
            'early_riser' => [
                'name' => 'Early Riser',
                'description' => 'Most active during morning hours',
                'icon' => '🌅',
                'color' => '#ffc107',
                'rarity' => 'uncommon',
                'category' => 'activity',
                'requirements' => ['morning_activity' => 0.7]
            ],
            'weekend_warrior' => [
                'name' => 'Weekend Warrior',
                'description' => 'Most active on weekends',
                'icon' => '🏃‍♂️',
                'color' => '#28a745',
                'rarity' => 'uncommon',
                'category' => 'activity',
                'requirements' => ['weekend_activity' => 0.7]
            ],
            'question_master' => [
                'name' => 'Question Master',
                'description' => 'Asks great questions',
                'icon' => '❓',
                'color' => '#17a2b8',
                'rarity' => 'uncommon',
                'category' => 'questioning',
                'requirements' => ['min_questions' => 20, 'min_question_likes' => 50]
            ],
            'answer_expert' => [
                'name' => 'Answer Expert',
                'description' => 'Provides excellent answers',
                'icon' => '💡',
                'color' => '#ffc107',
                'rarity' => 'rare',
                'category' => 'answering',
                'requirements' => ['min_answers' => 50, 'min_answer_likes' => 200]
            ],
            'creative_writer' => [
                'name' => 'Creative Writer',
                'description' => 'Known for creative and engaging posts',
                'icon' => '✍️',
                'color' => '#e83e8c',
                'rarity' => 'rare',
                'category' => 'creativity',
                'requirements' => ['min_creative_posts' => 25]
            ],
            'tech_savvy' => [
                'name' => 'Tech Savvy',
                'description' => 'Expert in technology discussions',
                'icon' => '💻',
                'color' => '#6f42c1',
                'rarity' => 'uncommon',
                'category' => 'expertise',
                'requirements' => ['min_tech_posts' => 30]
            ],
            'community_builder' => [
                'name' => 'Community Builder',
                'description' => 'Helps build and grow the community',
                'icon' => '🏗️',
                'color' => '#fd7e14',
                'rarity' => 'epic',
                'category' => 'community',
                'requirements' => ['min_community_posts' => 100, 'min_followers' => 100]
            ]
        ];
    }
    
    public function checkBadges(int $userId): array {
        $newBadges = [];
        $userStats = $this->getUserStats($userId);
        
        foreach ($this->badgeTypes as $badgeKey => $badge) {
            if (!$this->hasBadge($userId, $badgeKey) && $this->meetsRequirements($userStats, $badge['requirements'])) {
                $newBadges[] = $this->awardBadge($userId, $badgeKey);
            }
        }
        
        return $newBadges;
    }
    
    private function meetsRequirements(array $userStats, array $requirements): bool {
        foreach ($requirements as $requirement => $value) {
            switch ($requirement) {
                case 'min_posts':
                    if ($userStats['post_count'] < $value) return false;
                    break;
                case 'min_days_active':
                    if ($userStats['days_active'] < $value) return false;
                    break;
                case 'min_helpful_posts':
                    if ($userStats['helpful_posts'] < $value) return false;
                    break;
                case 'min_reputation':
                    if ($userStats['reputation'] < $value) return false;
                    break;
                case 'min_followers':
                    if ($userStats['followers'] < $value) return false;
                    break;
                case 'min_days_member':
                    if ($userStats['days_member'] < $value) return false;
                    break;
                case 'joined_before':
                    if ($userStats['joined_at'] > $value) return false;
                    break;
                case 'beta_tester':
                    if (!$userStats['is_beta_tester']) return false;
                    break;
                case 'role':
                    if ($userStats['role'] !== $value) return false;
                    break;
                case 'min_following':
                    if ($userStats['following'] < $value) return false;
                    break;
                case 'night_activity':
                    if ($userStats['night_activity_ratio'] < $value) return false;
                    break;
                case 'morning_activity':
                    if ($userStats['morning_activity_ratio'] < $value) return false;
                    break;
                case 'weekend_activity':
                    if ($userStats['weekend_activity_ratio'] < $value) return false;
                    break;
                case 'min_questions':
                    if ($userStats['question_count'] < $value) return false;
                    break;
                case 'min_question_likes':
                    if ($userStats['question_likes'] < $value) return false;
                    break;
                case 'min_answers':
                    if ($userStats['answer_count'] < $value) return false;
                    break;
                case 'min_answer_likes':
                    if ($userStats['answer_likes'] < $value) return false;
                    break;
                case 'min_creative_posts':
                    if ($userStats['creative_posts'] < $value) return false;
                    break;
                case 'min_tech_posts':
                    if ($userStats['tech_posts'] < $value) return false;
                    break;
                case 'min_community_posts':
                    if ($userStats['community_posts'] < $value) return false;
                    break;
            }
        }
        
        return true;
    }
    
    private function awardBadge(int $userId, string $badgeKey): array {
        $badge = $this->badgeTypes[$badgeKey];
        
        try {
            $this->db->insert('user_badges', [
                'user_id' => $userId,
                'badge_key' => $badgeKey,
                'badge_name' => $badge['name'],
                'badge_description' => $badge['description'],
                'badge_icon' => $badge['icon'],
                'badge_color' => $badge['color'],
                'rarity' => $badge['rarity'],
                'category' => $badge['category'],
                'awarded_at' => date('Y-m-d H:i:s')
            ]);
            
            // Send notification
            $this->sendBadgeNotification($userId, $badge);
            
            return [
                'badge_key' => $badgeKey,
                'name' => $badge['name'],
                'description' => $badge['description'],
                'icon' => $badge['icon'],
                'color' => $badge['color'],
                'rarity' => $badge['rarity'],
                'category' => $badge['category']
            ];
            
        } catch (\Exception $e) {
            error_log("Error awarding badge: " . $e->getMessage());
            return [];
        }
    }
    
    private function hasBadge(int $userId, string $badgeKey): bool {
        return $this->db->exists(
            'user_badges',
            'user_id = :user_id AND badge_key = :badge_key',
            ['user_id' => $userId, 'badge_key' => $badgeKey]
        );
    }
    
    private function sendBadgeNotification(int $userId, array $badge): void {
        $notificationService = new NotificationService();
        
        $notificationService->sendNotification(
            $userId,
            'badge',
            [
                'title' => 'Badge Earned!',
                'message' => "You've earned the '{$badge['name']}' badge!",
                'badge' => $badge
            ],
            ['push', 'browser', 'in_app']
        );
    }
    
    private function getUserStats(int $userId): array {
        $user = $this->db->fetch(
            "SELECT u.*, 
                    COUNT(DISTINCT p.id) as post_count,
                    COUNT(DISTINCT pl.id) as helpful_posts,
                    COUNT(DISTINCT f1.id) as followers,
                    COUNT(DISTINCT f2.id) as following,
                    DATEDIFF(NOW(), u.created_at) as days_member
             FROM users u
             LEFT JOIN posts p ON u.id = p.user_id
             LEFT JOIN post_likes pl ON p.id = pl.post_id AND pl.is_helpful = 1
             LEFT JOIN user_follows f1 ON u.id = f1.following_id
             LEFT JOIN user_follows f2 ON u.id = f2.follower_id
             WHERE u.id = :user_id
             GROUP BY u.id",
            ['user_id' => $userId]
        );
        
        if (!$user) {
            return [];
        }
        
        // Calculate additional stats
        $user['days_active'] = $this->getUserActiveDays($userId);
        $user['night_activity_ratio'] = $this->getUserNightActivityRatio($userId);
        $user['morning_activity_ratio'] = $this->getUserMorningActivityRatio($userId);
        $user['weekend_activity_ratio'] = $this->getUserWeekendActivityRatio($userId);
        $user['question_count'] = $this->getUserQuestionCount($userId);
        $user['question_likes'] = $this->getUserQuestionLikes($userId);
        $user['answer_count'] = $this->getUserAnswerCount($userId);
        $user['answer_likes'] = $this->getUserAnswerLikes($userId);
        $user['creative_posts'] = $this->getUserCreativePosts($userId);
        $user['tech_posts'] = $this->getUserTechPosts($userId);
        $user['community_posts'] = $this->getUserCommunityPosts($userId);
        
        return $user;
    }
    
    private function getUserActiveDays(int $userId): int {
        return $this->db->fetchColumn(
            "SELECT COUNT(DISTINCT DATE(created_at)) FROM posts WHERE user_id = :user_id",
            ['user_id' => $userId]
        );
    }
    
    private function getUserNightActivityRatio(int $userId): float {
        $nightPosts = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM posts WHERE user_id = :user_id AND HOUR(created_at) BETWEEN 22 AND 6",
            ['user_id' => $userId]
        );
        
        $totalPosts = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM posts WHERE user_id = :user_id",
            ['user_id' => $userId]
        );
        
        return $totalPosts > 0 ? $nightPosts / $totalPosts : 0;
    }
    
    private function getUserMorningActivityRatio(int $userId): float {
        $morningPosts = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM posts WHERE user_id = :user_id AND HOUR(created_at) BETWEEN 6 AND 12",
            ['user_id' => $userId]
        );
        
        $totalPosts = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM posts WHERE user_id = :user_id",
            ['user_id' => $userId]
        );
        
        return $totalPosts > 0 ? $morningPosts / $totalPosts : 0;
    }
    
    private function getUserWeekendActivityRatio(int $userId): float {
        $weekendPosts = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM posts WHERE user_id = :user_id AND DAYOFWEEK(created_at) IN (1, 7)",
            ['user_id' => $userId]
        );
        
        $totalPosts = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM posts WHERE user_id = :user_id",
            ['user_id' => $userId]
        );
        
        return $totalPosts > 0 ? $weekendPosts / $totalPosts : 0;
    }
    
    private function getUserQuestionCount(int $userId): int {
        return $this->db->fetchColumn(
            "SELECT COUNT(*) FROM posts p
             JOIN threads t ON p.thread_id = t.id
             WHERE p.user_id = :user_id AND t.user_id = :user_id",
            ['user_id' => $userId]
        );
    }
    
    private function getUserQuestionLikes(int $userId): int {
        return $this->db->fetchColumn(
            "SELECT COUNT(*) FROM post_likes pl
             JOIN posts p ON pl.post_id = p.id
             JOIN threads t ON p.thread_id = t.id
             WHERE t.user_id = :user_id",
            ['user_id' => $userId]
        );
    }
    
    private function getUserAnswerCount(int $userId): int {
        return $this->db->fetchColumn(
            "SELECT COUNT(*) FROM posts p
             JOIN threads t ON p.thread_id = t.id
             WHERE p.user_id = :user_id AND t.user_id != :user_id",
            ['user_id' => $userId]
        );
    }
    
    private function getUserAnswerLikes(int $userId): int {
        return $this->db->fetchColumn(
            "SELECT COUNT(*) FROM post_likes pl
             JOIN posts p ON pl.post_id = p.id
             JOIN threads t ON p.thread_id = t.id
             WHERE p.user_id = :user_id AND t.user_id != :user_id",
            ['user_id' => $userId]
        );
    }
    
    private function getUserCreativePosts(int $userId): int {
        // This would need to be implemented based on content analysis
        return 0;
    }
    
    private function getUserTechPosts(int $userId): int {
        return $this->db->fetchColumn(
            "SELECT COUNT(*) FROM posts p
             JOIN threads t ON p.thread_id = t.id
             JOIN forums f ON t.forum_id = f.id
             WHERE p.user_id = :user_id AND f.category = 'technology'",
            ['user_id' => $userId]
        );
    }
    
    private function getUserCommunityPosts(int $userId): int {
        return $this->db->fetchColumn(
            "SELECT COUNT(*) FROM posts p
             JOIN threads t ON p.thread_id = t.id
             JOIN forums f ON t.forum_id = f.id
             WHERE p.user_id = :user_id AND f.category = 'community'",
            ['user_id' => $userId]
        );
    }
    
    public function getUserBadges(int $userId): array {
        return $this->db->fetchAll(
            "SELECT * FROM user_badges 
             WHERE user_id = :user_id 
             ORDER BY awarded_at DESC",
            ['user_id' => $userId]
        );
    }
    
    public function getUserBadgesByCategory(int $userId, string $category): array {
        return $this->db->fetchAll(
            "SELECT * FROM user_badges 
             WHERE user_id = :user_id AND category = :category 
             ORDER BY awarded_at DESC",
            ['user_id' => $userId, 'category' => $category]
        );
    }
    
    public function getBadgeStats(): array {
        return [
            'total_badges' => count($this->badgeTypes),
            'badges_by_category' => $this->getBadgesByCategory(),
            'badges_by_rarity' => $this->getBadgesByRarity(),
            'most_earned' => $this->getMostEarnedBadges(),
            'least_earned' => $this->getLeastEarnedBadges()
        ];
    }
    
    private function getBadgesByCategory(): array {
        $categories = [];
        
        foreach ($this->badgeTypes as $badge) {
            $category = $badge['category'];
            if (!isset($categories[$category])) {
                $categories[$category] = 0;
            }
            $categories[$category]++;
        }
        
        return $categories;
    }
    
    private function getBadgesByRarity(): array {
        $rarities = [];
        
        foreach ($this->badgeTypes as $badge) {
            $rarity = $badge['rarity'];
            if (!isset($rarities[$rarity])) {
                $rarities[$rarity] = 0;
            }
            $rarities[$rarity]++;
        }
        
        return $rarities;
    }
    
    private function getMostEarnedBadges(): array {
        return $this->db->fetchAll(
            "SELECT badge_key, badge_name, COUNT(*) as earned_count
             FROM user_badges
             GROUP BY badge_key, badge_name
             ORDER BY earned_count DESC
             LIMIT 10"
        );
    }
    
    private function getLeastEarnedBadges(): array {
        return $this->db->fetchAll(
            "SELECT badge_key, badge_name, COUNT(*) as earned_count
             FROM user_badges
             GROUP BY badge_key, badge_name
             ORDER BY earned_count ASC
             LIMIT 10"
        );
    }
    
    public function getBadgeLeaderboard(int $limit = 10): array {
        return $this->db->fetchAll(
            "SELECT u.id, u.username, u.avatar,
                    COUNT(ub.id) as badge_count,
                    GROUP_CONCAT(ub.badge_icon ORDER BY ub.awarded_at DESC) as badges
             FROM users u
             LEFT JOIN user_badges ub ON u.id = ub.user_id
             GROUP BY u.id, u.username, u.avatar
             ORDER BY badge_count DESC, u.username ASC
             LIMIT :limit",
            ['limit' => $limit]
        );
    }
    
    public function createCustomBadge(string $key, array $badge): bool {
        try {
            $this->db->insert('custom_badges', [
                'badge_key' => $key,
                'name' => $badge['name'],
                'description' => $badge['description'],
                'icon' => $badge['icon'],
                'color' => $badge['color'],
                'rarity' => $badge['rarity'],
                'category' => $badge['category'],
                'requirements' => json_encode($badge['requirements']),
                'created_by' => Auth::getUserId(),
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            return true;
        } catch (\Exception $e) {
            error_log("Error creating custom badge: " . $e->getMessage());
            return false;
        }
    }
    
    public function getBadgeProgress(int $userId, string $badgeKey): array {
        $badge = $this->badgeTypes[$badgeKey] ?? null;
        
        if (!$badge) {
            return [];
        }
        
        $progress = [
            'badge_key' => $badgeKey,
            'name' => $badge['name'],
            'description' => $badge['description'],
            'icon' => $badge['icon'],
            'color' => $badge['color'],
            'rarity' => $badge['rarity'],
            'category' => $badge['category'],
            'earned' => $this->hasBadge($userId, $badgeKey),
            'progress' => [],
            'requirements' => $badge['requirements']
        ];
        
        // Calculate progress for each requirement
        $userStats = $this->getUserStats($userId);
        
        foreach ($badge['requirements'] as $requirement => $value) {
            $currentValue = $userStats[$requirement] ?? 0;
            $progress['progress'][$requirement] = [
                'current' => $currentValue,
                'required' => $value,
                'percentage' => min(($currentValue / $value) * 100, 100)
            ];
        }
        
        return $progress;
    }
}