<?php

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use App\Core\Security;

/**
 * Gamification Service
 */
class GamificationService
{
    private $db;
    private $logger;

    public function __construct()
    {
        global $app;
        $this->db = $app->get('database');
        $this->logger = $app->get('logger');
    }

    /**
     * Award points to user
     */
    public function awardPoints($userId, $action, $points = null)
    {
        try {
            if ($points === null) {
                $points = $this->getPointsForAction($action);
            }

            if ($points <= 0) {
                return false;
            }

            // Add points to user
            $this->db->query(
                "INSERT INTO user_points (user_id, points, action, created_at) VALUES (?, ?, ?, NOW())",
                [$userId, $points, $action]
            );

            // Update user total points
            $this->updateUserTotalPoints($userId);

            // Check for level up
            $this->checkLevelUp($userId);

            // Check for achievements
            $this->checkAchievements($userId, $action);

            $this->logger->info("Awarded {$points} points to user {$userId} for action {$action}");
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Points awarding failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get points for action
     */
    private function getPointsForAction($action)
    {
        $pointValues = [
            'create_thread' => 10,
            'create_post' => 5,
            'receive_like' => 2,
            'receive_reply' => 3,
            'daily_login' => 5,
            'complete_profile' => 20,
            'first_post' => 15,
            'helpful_answer' => 8,
            'moderator_action' => 25,
            'report_spam' => 5,
            'share_content' => 3,
            'invite_friend' => 50,
            'week_streak' => 100,
            'month_streak' => 500,
            'year_streak' => 2000
        ];

        return $pointValues[$action] ?? 0;
    }

    /**
     * Update user total points
     */
    private function updateUserTotalPoints($userId)
    {
        $result = $this->db->fetch(
            "SELECT SUM(points) as total FROM user_points WHERE user_id = ?",
            [$userId]
        );

        $totalPoints = $result['total'] ?? 0;

        $this->db->query(
            "INSERT INTO user_stats (user_id, total_points, updated_at) 
             VALUES (?, ?, NOW()) 
             ON DUPLICATE KEY UPDATE 
             total_points = VALUES(total_points), 
             updated_at = NOW()",
            [$userId, $totalPoints]
        );
    }

    /**
     * Check for level up
     */
    private function checkLevelUp($userId)
    {
        $userStats = $this->db->fetch(
            "SELECT total_points, level FROM user_stats WHERE user_id = ?",
            [$userId]
        );

        if (!$userStats) {
            return false;
        }

        $currentLevel = $userStats['level'] ?? 1;
        $totalPoints = $userStats['total_points'] ?? 0;
        $newLevel = $this->calculateLevel($totalPoints);

        if ($newLevel > $currentLevel) {
            $this->db->query(
                "UPDATE user_stats SET level = ?, level_up_at = NOW() WHERE user_id = ?",
                [$newLevel, $userId]
            );

            // Award level up bonus
            $bonusPoints = $newLevel * 10;
            $this->awardPoints($userId, 'level_up', $bonusPoints);

            // Send level up notification
            $this->sendLevelUpNotification($userId, $newLevel);

            $this->logger->info("User {$userId} leveled up to level {$newLevel}");
            return true;
        }

        return false;
    }

    /**
     * Calculate level from points
     */
    private function calculateLevel($points)
    {
        // Level formula: level = floor(sqrt(points / 100)) + 1
        return floor(sqrt($points / 100)) + 1;
    }

    /**
     * Check achievements
     */
    private function checkAchievements($userId, $action)
    {
        $achievements = $this->getAvailableAchievements();
        
        foreach ($achievements as $achievement) {
            if ($this->isAchievementEarned($userId, $achievement, $action)) {
                $this->awardAchievement($userId, $achievement);
            }
        }
    }

    /**
     * Get available achievements
     */
    private function getAvailableAchievements()
    {
        return [
            'first_post' => [
                'name' => 'First Post',
                'description' => 'Make your first post',
                'points' => 25,
                'icon' => '🎯',
                'condition' => 'post_count >= 1'
            ],
            'thread_master' => [
                'name' => 'Thread Master',
                'description' => 'Create 10 threads',
                'points' => 100,
                'icon' => '📝',
                'condition' => 'thread_count >= 10'
            ],
            'helpful_member' => [
                'name' => 'Helpful Member',
                'description' => 'Receive 50 likes',
                'points' => 200,
                'icon' => '👍',
                'condition' => 'likes_received >= 50'
            ],
            'active_participant' => [
                'name' => 'Active Participant',
                'description' => 'Make 100 posts',
                'points' => 300,
                'icon' => '💬',
                'condition' => 'post_count >= 100'
            ],
            'early_bird' => [
                'name' => 'Early Bird',
                'description' => 'Login for 7 consecutive days',
                'points' => 150,
                'icon' => '🐦',
                'condition' => 'login_streak >= 7'
            ],
            'social_butterfly' => [
                'name' => 'Social Butterfly',
                'description' => 'Reply to 50 different threads',
                'points' => 250,
                'icon' => '🦋',
                'condition' => 'unique_threads_replied >= 50'
            ],
            'knowledge_seeker' => [
                'name' => 'Knowledge Seeker',
                'description' => 'Ask 25 questions',
                'points' => 175,
                'icon' => '❓',
                'condition' => 'questions_asked >= 25'
            ],
            'community_helper' => [
                'name' => 'Community Helper',
                'description' => 'Help 10 users with their questions',
                'points' => 400,
                'icon' => '🤝',
                'condition' => 'helpful_answers >= 10'
            ]
        ];
    }

    /**
     * Check if achievement is earned
     */
    private function isAchievementEarned($userId, $achievement, $action)
    {
        // Check if already earned
        $existing = $this->db->fetch(
            "SELECT id FROM user_achievements WHERE user_id = ? AND achievement_key = ?",
            [$userId, $achievement['name']]
        );

        if ($existing) {
            return false;
        }

        // Check condition
        $userStats = $this->getUserStats($userId);
        
        switch ($achievement['condition']) {
            case 'post_count >= 1':
                return $userStats['post_count'] >= 1;
            case 'thread_count >= 10':
                return $userStats['thread_count'] >= 10;
            case 'likes_received >= 50':
                return $userStats['likes_received'] >= 50;
            case 'post_count >= 100':
                return $userStats['post_count'] >= 100;
            case 'login_streak >= 7':
                return $userStats['login_streak'] >= 7;
            case 'unique_threads_replied >= 50':
                return $userStats['unique_threads_replied'] >= 50;
            case 'questions_asked >= 25':
                return $userStats['questions_asked'] >= 25;
            case 'helpful_answers >= 10':
                return $userStats['helpful_answers'] >= 10;
            default:
                return false;
        }
    }

    /**
     * Award achievement
     */
    private function awardAchievement($userId, $achievement)
    {
        try {
            $this->db->query(
                "INSERT INTO user_achievements (user_id, achievement_key, achievement_name, description, points, icon, earned_at) 
                 VALUES (?, ?, ?, ?, ?, ?, NOW())",
                [$userId, $achievement['name'], $achievement['name'], $achievement['description'], $achievement['points'], $achievement['icon']]
            );

            // Award achievement points
            $this->awardPoints($userId, 'achievement', $achievement['points']);

            // Send achievement notification
            $this->sendAchievementNotification($userId, $achievement);

            $this->logger->info("Achievement awarded to user {$userId}: {$achievement['name']}");
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Achievement awarding failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user stats
     */
    private function getUserStats($userId)
    {
        $stats = $this->db->fetch(
            "SELECT 
                COUNT(DISTINCT t.id) as thread_count,
                COUNT(DISTINCT p.id) as post_count,
                COUNT(DISTINCT pr.id) as likes_received,
                COUNT(DISTINCT CASE WHEN p.thread_id != t.id THEN p.thread_id END) as unique_threads_replied,
                COUNT(DISTINCT CASE WHEN t.title LIKE '%?%' OR t.title LIKE '%how%' OR t.title LIKE '%what%' THEN t.id END) as questions_asked,
                COUNT(DISTINCT CASE WHEN pr.reaction_type = 'helpful' THEN p.id END) as helpful_answers,
                MAX(DATEDIFF(NOW(), u.last_login)) as login_streak
             FROM users u
             LEFT JOIN threads t ON u.id = t.user_id
             LEFT JOIN posts p ON u.id = p.user_id
             LEFT JOIN post_reactions pr ON p.id = pr.post_id
             WHERE u.id = ?",
            [$userId]
        );

        return $stats ?: [
            'thread_count' => 0,
            'post_count' => 0,
            'likes_received' => 0,
            'unique_threads_replied' => 0,
            'questions_asked' => 0,
            'helpful_answers' => 0,
            'login_streak' => 0
        ];
    }

    /**
     * Get leaderboard
     */
    public function getLeaderboard($type = 'points', $limit = 10)
    {
        switch ($type) {
            case 'points':
                return $this->getPointsLeaderboard($limit);
            case 'posts':
                return $this->getPostsLeaderboard($limit);
            case 'threads':
                return $this->getThreadsLeaderboard($limit);
            case 'level':
                return $this->getLevelLeaderboard($limit);
            default:
                return $this->getPointsLeaderboard($limit);
        }
    }

    /**
     * Get points leaderboard
     */
    private function getPointsLeaderboard($limit)
    {
        return $this->db->fetchAll(
            "SELECT u.id, u.username, u.display_name, u.avatar, s.total_points, s.level
             FROM users u
             LEFT JOIN user_stats s ON u.id = s.user_id
             ORDER BY s.total_points DESC
             LIMIT ?",
            [$limit]
        );
    }

    /**
     * Get posts leaderboard
     */
    private function getPostsLeaderboard($limit)
    {
        return $this->db->fetchAll(
            "SELECT u.id, u.username, u.display_name, u.avatar, COUNT(p.id) as post_count
             FROM users u
             LEFT JOIN posts p ON u.id = p.user_id
             GROUP BY u.id
             ORDER BY post_count DESC
             LIMIT ?",
            [$limit]
        );
    }

    /**
     * Get threads leaderboard
     */
    private function getThreadsLeaderboard($limit)
    {
        return $this->db->fetchAll(
            "SELECT u.id, u.username, u.display_name, u.avatar, COUNT(t.id) as thread_count
             FROM users u
             LEFT JOIN threads t ON u.id = t.user_id
             GROUP BY u.id
             ORDER BY thread_count DESC
             LIMIT ?",
            [$limit]
        );
    }

    /**
     * Get level leaderboard
     */
    private function getLevelLeaderboard($limit)
    {
        return $this->db->fetchAll(
            "SELECT u.id, u.username, u.display_name, u.avatar, s.level, s.total_points
             FROM users u
             LEFT JOIN user_stats s ON u.id = s.user_id
             ORDER BY s.level DESC, s.total_points DESC
             LIMIT ?",
            [$limit]
        );
    }

    /**
     * Get user achievements
     */
    public function getUserAchievements($userId)
    {
        return $this->db->fetchAll(
            "SELECT * FROM user_achievements WHERE user_id = ? ORDER BY earned_at DESC",
            [$userId]
        );
    }

    /**
     * Get user stats
     */
    public function getUserStats($userId)
    {
        $stats = $this->db->fetch(
            "SELECT * FROM user_stats WHERE user_id = ?",
            [$userId]
        );

        if (!$stats) {
            return [
                'total_points' => 0,
                'level' => 1,
                'achievements_count' => 0,
                'rank' => 0
            ];
        }

        // Get achievements count
        $achievementsCount = $this->db->fetch(
            "SELECT COUNT(*) as count FROM user_achievements WHERE user_id = ?",
            [$userId]
        );

        // Get rank
        $rank = $this->db->fetch(
            "SELECT COUNT(*) + 1 as rank FROM user_stats WHERE total_points > ?",
            [$stats['total_points']]
        );

        $stats['achievements_count'] = $achievementsCount['count'];
        $stats['rank'] = $rank['rank'];

        return $stats;
    }

    /**
     * Send level up notification
     */
    private function sendLevelUpNotification($userId, $newLevel)
    {
        // This would integrate with the notification system
        $this->logger->info("Level up notification sent to user {$userId} for level {$newLevel}");
    }

    /**
     * Send achievement notification
     */
    private function sendAchievementNotification($userId, $achievement)
    {
        // This would integrate with the notification system
        $this->logger->info("Achievement notification sent to user {$userId} for {$achievement['name']}");
    }
}