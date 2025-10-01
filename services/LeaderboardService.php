<?php
declare(strict_types=1);

namespace Services;

class LeaderboardService {
    private Database $db;
    private array $leaderboardTypes;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->leaderboardTypes = [
            'points' => 'Points',
            'posts' => 'Posts',
            'reputation' => 'Reputation',
            'likes_received' => 'Likes Received',
            'helpful_posts' => 'Helpful Posts',
            'followers' => 'Followers',
            'achievements' => 'Achievements',
            'badges' => 'Badges',
            'activity' => 'Activity',
            'weekly' => 'Weekly Activity',
            'monthly' => 'Monthly Activity'
        ];
    }
    
    public function getLeaderboard(string $type = 'points', int $limit = 10, int $offset = 0): array {
        switch ($type) {
            case 'points':
                return $this->getPointsLeaderboard($limit, $offset);
            case 'posts':
                return $this->getPostsLeaderboard($limit, $offset);
            case 'reputation':
                return $this->getReputationLeaderboard($limit, $offset);
            case 'likes_received':
                return $this->getLikesReceivedLeaderboard($limit, $offset);
            case 'helpful_posts':
                return $this->getHelpfulPostsLeaderboard($limit, $offset);
            case 'followers':
                return $this->getFollowersLeaderboard($limit, $offset);
            case 'achievements':
                return $this->getAchievementsLeaderboard($limit, $offset);
            case 'badges':
                return $this->getBadgesLeaderboard($limit, $offset);
            case 'activity':
                return $this->getActivityLeaderboard($limit, $offset);
            case 'weekly':
                return $this->getWeeklyLeaderboard($limit, $offset);
            case 'monthly':
                return $this->getMonthlyLeaderboard($limit, $offset);
            default:
                return $this->getPointsLeaderboard($limit, $offset);
        }
    }
    
    private function getPointsLeaderboard(int $limit, int $offset): array {
        return $this->db->fetchAll(
            "SELECT u.id, u.username, u.avatar, u.points, u.level,
                    COUNT(ua.id) as achievement_count,
                    COUNT(ub.id) as badge_count
             FROM users u
             LEFT JOIN user_achievements ua ON u.id = ua.user_id
             LEFT JOIN user_badges ub ON u.id = ub.user_id
             GROUP BY u.id, u.username, u.avatar, u.points, u.level
             ORDER BY u.points DESC, achievement_count DESC, badge_count DESC
             LIMIT :offset, :limit",
            ['offset' => $offset, 'limit' => $limit]
        );
    }
    
    private function getPostsLeaderboard(int $limit, int $offset): array {
        return $this->db->fetchAll(
            "SELECT u.id, u.username, u.avatar,
                    COUNT(p.id) as post_count,
                    COUNT(DISTINCT t.id) as thread_count,
                    COUNT(pl.id) as likes_received
             FROM users u
             LEFT JOIN posts p ON u.id = p.user_id
             LEFT JOIN threads t ON p.thread_id = t.id
             LEFT JOIN post_likes pl ON p.id = pl.post_id
             GROUP BY u.id, u.username, u.avatar
             ORDER BY post_count DESC, likes_received DESC
             LIMIT :offset, :limit",
            ['offset' => $offset, 'limit' => $limit]
        );
    }
    
    private function getReputationLeaderboard(int $limit, int $offset): array {
        return $this->db->fetchAll(
            "SELECT u.id, u.username, u.avatar, u.reputation,
                    COUNT(p.id) as post_count,
                    COUNT(pl.id) as likes_received
             FROM users u
             LEFT JOIN posts p ON u.id = p.user_id
             LEFT JOIN post_likes pl ON p.id = pl.post_id
             GROUP BY u.id, u.username, u.avatar, u.reputation
             ORDER BY u.reputation DESC, likes_received DESC
             LIMIT :offset, :limit",
            ['offset' => $offset, 'limit' => $limit]
        );
    }
    
    private function getLikesReceivedLeaderboard(int $limit, int $offset): array {
        return $this->db->fetchAll(
            "SELECT u.id, u.username, u.avatar,
                    COUNT(pl.id) as likes_received,
                    COUNT(p.id) as post_count,
                    ROUND(COUNT(pl.id) / COUNT(p.id), 2) as likes_per_post
             FROM users u
             LEFT JOIN posts p ON u.id = p.user_id
             LEFT JOIN post_likes pl ON p.id = pl.post_id
             GROUP BY u.id, u.username, u.avatar
             HAVING post_count > 0
             ORDER BY likes_received DESC, likes_per_post DESC
             LIMIT :offset, :limit",
            ['offset' => $offset, 'limit' => $limit]
        );
    }
    
    private function getHelpfulPostsLeaderboard(int $limit, int $offset): array {
        return $this->db->fetchAll(
            "SELECT u.id, u.username, u.avatar,
                    COUNT(pl.id) as helpful_posts,
                    COUNT(p.id) as total_posts,
                    ROUND(COUNT(pl.id) / COUNT(p.id) * 100, 2) as helpful_percentage
             FROM users u
             LEFT JOIN posts p ON u.id = p.user_id
             LEFT JOIN post_likes pl ON p.id = pl.post_id AND pl.is_helpful = 1
             GROUP BY u.id, u.username, u.avatar
             HAVING total_posts > 0
             ORDER BY helpful_posts DESC, helpful_percentage DESC
             LIMIT :offset, :limit",
            ['offset' => $offset, 'limit' => $limit]
        );
    }
    
    private function getFollowersLeaderboard(int $limit, int $offset): array {
        return $this->db->fetchAll(
            "SELECT u.id, u.username, u.avatar,
                    COUNT(f1.id) as followers,
                    COUNT(f2.id) as following,
                    COUNT(p.id) as post_count
             FROM users u
             LEFT JOIN user_follows f1 ON u.id = f1.following_id
             LEFT JOIN user_follows f2 ON u.id = f2.follower_id
             LEFT JOIN posts p ON u.id = p.user_id
             GROUP BY u.id, u.username, u.avatar
             ORDER BY followers DESC, post_count DESC
             LIMIT :offset, :limit",
            ['offset' => $offset, 'limit' => $limit]
        );
    }
    
    private function getAchievementsLeaderboard(int $limit, int $offset): array {
        return $this->db->fetchAll(
            "SELECT u.id, u.username, u.avatar,
                    COUNT(ua.id) as achievement_count,
                    SUM(ua.points) as total_achievement_points,
                    COUNT(ub.id) as badge_count
             FROM users u
             LEFT JOIN user_achievements ua ON u.id = ua.user_id
             LEFT JOIN user_badges ub ON u.id = ub.user_id
             GROUP BY u.id, u.username, u.avatar
             ORDER BY achievement_count DESC, total_achievement_points DESC
             LIMIT :offset, :limit",
            ['offset' => $offset, 'limit' => $limit]
        );
    }
    
    private function getBadgesLeaderboard(int $limit, int $offset): array {
        return $this->db->fetchAll(
            "SELECT u.id, u.username, u.avatar,
                    COUNT(ub.id) as badge_count,
                    COUNT(DISTINCT ub.category) as category_count,
                    COUNT(ua.id) as achievement_count
             FROM users u
             LEFT JOIN user_badges ub ON u.id = ub.user_id
             LEFT JOIN user_achievements ua ON u.id = ua.user_id
             GROUP BY u.id, u.username, u.avatar
             ORDER BY badge_count DESC, category_count DESC
             LIMIT :offset, :limit",
            ['offset' => $offset, 'limit' => $limit]
        );
    }
    
    private function getActivityLeaderboard(int $limit, int $offset): array {
        return $this->db->fetchAll(
            "SELECT u.id, u.username, u.avatar,
                    COUNT(DISTINCT DATE(p.created_at)) as active_days,
                    COUNT(p.id) as post_count,
                    MAX(p.created_at) as last_activity
             FROM users u
             LEFT JOIN posts p ON u.id = p.user_id
             GROUP BY u.id, u.username, u.avatar
             ORDER BY active_days DESC, post_count DESC
             LIMIT :offset, :limit",
            ['offset' => $offset, 'limit' => $limit]
        );
    }
    
    private function getWeeklyLeaderboard(int $limit, int $offset): array {
        return $this->db->fetchAll(
            "SELECT u.id, u.username, u.avatar,
                    COUNT(p.id) as weekly_posts,
                    COUNT(pl.id) as weekly_likes_received,
                    COUNT(DISTINCT DATE(p.created_at)) as active_days
             FROM users u
             LEFT JOIN posts p ON u.id = p.user_id 
                 AND p.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
             LEFT JOIN post_likes pl ON p.id = pl.post_id
             GROUP BY u.id, u.username, u.avatar
             ORDER BY weekly_posts DESC, weekly_likes_received DESC
             LIMIT :offset, :limit",
            ['offset' => $offset, 'limit' => $limit]
        );
    }
    
    private function getMonthlyLeaderboard(int $limit, int $offset): array {
        return $this->db->fetchAll(
            "SELECT u.id, u.username, u.avatar,
                    COUNT(p.id) as monthly_posts,
                    COUNT(pl.id) as monthly_likes_received,
                    COUNT(DISTINCT DATE(p.created_at)) as active_days
             FROM users u
             LEFT JOIN posts p ON u.id = p.user_id 
                 AND p.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             LEFT JOIN post_likes pl ON p.id = pl.post_id
             GROUP BY u.id, u.username, u.avatar
             ORDER BY monthly_posts DESC, monthly_likes_received DESC
             LIMIT :offset, :limit",
            ['offset' => $offset, 'limit' => $limit]
        );
    }
    
    public function getUserRank(int $userId, string $type = 'points'): int {
        $leaderboard = $this->getLeaderboard($type, 1000, 0);
        
        foreach ($leaderboard as $index => $user) {
            if ($user['id'] == $userId) {
                return $index + 1;
            }
        }
        
        return 0;
    }
    
    public function getUserPosition(int $userId, string $type = 'points'): array {
        $rank = $this->getUserRank($userId, $type);
        
        if ($rank === 0) {
            return ['rank' => 0, 'total_users' => 0];
        }
        
        $totalUsers = $this->db->fetchColumn("SELECT COUNT(*) FROM users");
        
        return [
            'rank' => $rank,
            'total_users' => $totalUsers,
            'percentile' => round((($totalUsers - $rank + 1) / $totalUsers) * 100, 2)
        ];
    }
    
    public function getLeaderboardTypes(): array {
        return $this->leaderboardTypes;
    }
    
    public function getLeaderboardStats(string $type = 'points'): array {
        $leaderboard = $this->getLeaderboard($type, 100, 0);
        
        if (empty($leaderboard)) {
            return [];
        }
        
        $values = [];
        foreach ($leaderboard as $user) {
            switch ($type) {
                case 'points':
                    $values[] = $user['points'];
                    break;
                case 'posts':
                    $values[] = $user['post_count'];
                    break;
                case 'reputation':
                    $values[] = $user['reputation'];
                    break;
                case 'likes_received':
                    $values[] = $user['likes_received'];
                    break;
                case 'helpful_posts':
                    $values[] = $user['helpful_posts'];
                    break;
                case 'followers':
                    $values[] = $user['followers'];
                    break;
                case 'achievements':
                    $values[] = $user['achievement_count'];
                    break;
                case 'badges':
                    $values[] = $user['badge_count'];
                    break;
            }
        }
        
        return [
            'total_users' => count($leaderboard),
            'max_value' => max($values),
            'min_value' => min($values),
            'avg_value' => round(array_sum($values) / count($values), 2),
            'median_value' => $this->calculateMedian($values)
        ];
    }
    
    private function calculateMedian(array $values): float {
        sort($values);
        $count = count($values);
        
        if ($count % 2 === 0) {
            return ($values[$count / 2 - 1] + $values[$count / 2]) / 2;
        } else {
            return $values[floor($count / 2)];
        }
    }
    
    public function getTopPerformers(string $type = 'points', int $limit = 5): array {
        return $this->getLeaderboard($type, $limit, 0);
    }
    
    public function getRisingStars(string $type = 'points', int $limit = 5): array {
        // Users who have gained the most points in the last 30 days
        return $this->db->fetchAll(
            "SELECT u.id, u.username, u.avatar,
                    COUNT(p.id) as recent_posts,
                    COUNT(pl.id) as recent_likes,
                    COUNT(ua.id) as recent_achievements
             FROM users u
             LEFT JOIN posts p ON u.id = p.user_id 
                 AND p.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             LEFT JOIN post_likes pl ON p.id = pl.post_id
             LEFT JOIN user_achievements ua ON u.id = ua.user_id 
                 AND ua.awarded_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             GROUP BY u.id, u.username, u.avatar
             ORDER BY recent_posts DESC, recent_likes DESC, recent_achievements DESC
             LIMIT :limit",
            ['limit' => $limit]
        );
    }
    
    public function getMostActiveUsers(int $limit = 10): array {
        return $this->db->fetchAll(
            "SELECT u.id, u.username, u.avatar,
                    COUNT(p.id) as total_posts,
                    COUNT(DISTINCT DATE(p.created_at)) as active_days,
                    MAX(p.created_at) as last_activity,
                    DATEDIFF(NOW(), MAX(p.created_at)) as days_since_last_activity
             FROM users u
             LEFT JOIN posts p ON u.id = p.user_id
             GROUP BY u.id, u.username, u.avatar
             HAVING total_posts > 0
             ORDER BY active_days DESC, total_posts DESC
             LIMIT :limit",
            ['limit' => $limit]
        );
    }
    
    public function getMostHelpfulUsers(int $limit = 10): array {
        return $this->db->fetchAll(
            "SELECT u.id, u.username, u.avatar,
                    COUNT(pl.id) as helpful_posts,
                    COUNT(p.id) as total_posts,
                    ROUND(COUNT(pl.id) / COUNT(p.id) * 100, 2) as helpful_percentage
             FROM users u
             LEFT JOIN posts p ON u.id = p.user_id
             LEFT JOIN post_likes pl ON p.id = pl.post_id AND pl.is_helpful = 1
             GROUP BY u.id, u.username, u.avatar
             HAVING total_posts > 0
             ORDER BY helpful_posts DESC, helpful_percentage DESC
             LIMIT :limit",
            ['limit' => $limit]
        );
    }
    
    public function getNewestMembers(int $limit = 10): array {
        return $this->db->fetchAll(
            "SELECT u.id, u.username, u.avatar, u.created_at,
                    COUNT(p.id) as post_count,
                    COUNT(ua.id) as achievement_count
             FROM users u
             LEFT JOIN posts p ON u.id = p.user_id
             LEFT JOIN user_achievements ua ON u.id = ua.user_id
             GROUP BY u.id, u.username, u.avatar, u.created_at
             ORDER BY u.created_at DESC
             LIMIT :limit",
            ['limit' => $limit]
        );
    }
    
    public function getLeaderboardHistory(string $type = 'points', int $days = 30): array {
        return $this->db->fetchAll(
            "SELECT DATE(created_at) as date,
                    COUNT(*) as new_achievements,
                    SUM(points) as total_points_awarded
             FROM user_achievements
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
             GROUP BY DATE(created_at)
             ORDER BY date DESC",
            ['days' => $days]
        );
    }
    
    public function getCompetitionLeaderboard(int $competitionId, int $limit = 10): array {
        return $this->db->fetchAll(
            "SELECT u.id, u.username, u.avatar,
                    SUM(cp.points) as competition_points,
                    COUNT(cp.id) as competition_activities
             FROM users u
             JOIN competition_participants cp ON u.id = cp.user_id
             WHERE cp.competition_id = :competition_id
             GROUP BY u.id, u.username, u.avatar
             ORDER BY competition_points DESC, competition_activities DESC
             LIMIT :limit",
            ['competition_id' => $competitionId, 'limit' => $limit]
        );
    }
    
    public function createCustomLeaderboard(string $name, string $query, array $params = []): array {
        try {
            return $this->db->fetchAll($query, $params);
        } catch (\Exception $e) {
            error_log("Error creating custom leaderboard: " . $e->getMessage());
            return [];
        }
    }
    
    public function getLeaderboardComparison(int $userId1, int $userId2, string $type = 'points'): array {
        $user1 = $this->getUserLeaderboardData($userId1, $type);
        $user2 = $this->getUserLeaderboardData($userId2, $type);
        
        return [
            'user1' => $user1,
            'user2' => $user2,
            'comparison' => [
                'user1_better' => $user1['value'] > $user2['value'],
                'user2_better' => $user2['value'] > $user1['value'],
                'difference' => abs($user1['value'] - $user2['value']),
                'percentage_difference' => $user1['value'] > 0 ? 
                    round((abs($user1['value'] - $user2['value']) / $user1['value']) * 100, 2) : 0
            ]
        ];
    }
    
    private function getUserLeaderboardData(int $userId, string $type): array {
        $user = $this->db->fetch(
            "SELECT u.id, u.username, u.avatar, u.points, u.reputation
             FROM users u WHERE u.id = :user_id",
            ['user_id' => $userId]
        );
        
        if (!$user) {
            return [];
        }
        
        $value = 0;
        switch ($type) {
            case 'points':
                $value = $user['points'];
                break;
            case 'reputation':
                $value = $user['reputation'];
                break;
            case 'posts':
                $value = $this->db->fetchColumn(
                    "SELECT COUNT(*) FROM posts WHERE user_id = :user_id",
                    ['user_id' => $userId]
                );
                break;
            case 'likes_received':
                $value = $this->db->fetchColumn(
                    "SELECT COUNT(*) FROM post_likes pl
                     JOIN posts p ON pl.post_id = p.id
                     WHERE p.user_id = :user_id",
                    ['user_id' => $userId]
                );
                break;
        }
        
        return [
            'id' => $user['id'],
            'username' => $user['username'],
            'avatar' => $user['avatar'],
            'value' => $value,
            'rank' => $this->getUserRank($userId, $type)
        ];
    }
}