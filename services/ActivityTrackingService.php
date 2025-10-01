<?php
declare(strict_types=1);

namespace Services;

class ActivityTrackingService {
    private Database $db;
    private array $activityTypes;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->activityTypes = $this->getActivityTypes();
    }
    
    private function getActivityTypes(): array {
        return [
            'login' => [
                'name' => 'Login',
                'description' => 'User logged in',
                'points' => 1,
                'icon' => 'fas fa-sign-in-alt',
                'color' => '#4CAF50'
            ],
            'logout' => [
                'name' => 'Logout',
                'description' => 'User logged out',
                'points' => 0,
                'icon' => 'fas fa-sign-out-alt',
                'color' => '#F44336'
            ],
            'post_created' => [
                'name' => 'Post Created',
                'description' => 'User created a post',
                'points' => 5,
                'icon' => 'fas fa-edit',
                'color' => '#2196F3'
            ],
            'post_edited' => [
                'name' => 'Post Edited',
                'description' => 'User edited a post',
                'points' => 2,
                'icon' => 'fas fa-edit',
                'color' => '#FF9800'
            ],
            'post_deleted' => [
                'name' => 'Post Deleted',
                'description' => 'User deleted a post',
                'points' => -2,
                'icon' => 'fas fa-trash',
                'color' => '#F44336'
            ],
            'thread_created' => [
                'name' => 'Thread Created',
                'description' => 'User created a thread',
                'points' => 10,
                'icon' => 'fas fa-comments',
                'color' => '#9C27B0'
            ],
            'thread_edited' => [
                'name' => 'Thread Edited',
                'description' => 'User edited a thread',
                'points' => 3,
                'icon' => 'fas fa-edit',
                'color' => '#FF9800'
            ],
            'thread_deleted' => [
                'name' => 'Thread Deleted',
                'description' => 'User deleted a thread',
                'points' => -5,
                'icon' => 'fas fa-trash',
                'color' => '#F44336'
            ],
            'like_given' => [
                'name' => 'Like Given',
                'description' => 'User gave a like',
                'points' => 1,
                'icon' => 'fas fa-thumbs-up',
                'color' => '#4CAF50'
            ],
            'like_received' => [
                'name' => 'Like Received',
                'description' => 'User received a like',
                'points' => 2,
                'icon' => 'fas fa-heart',
                'color' => '#E91E63'
            ],
            'dislike_given' => [
                'name' => 'Dislike Given',
                'description' => 'User gave a dislike',
                'points' => -1,
                'icon' => 'fas fa-thumbs-down',
                'color' => '#F44336'
            ],
            'dislike_received' => [
                'name' => 'Dislike Received',
                'description' => 'User received a dislike',
                'points' => -2,
                'icon' => 'fas fa-heart-broken',
                'color' => '#F44336'
            ],
            'helpful_marked' => [
                'name' => 'Helpful Marked',
                'description' => 'Post marked as helpful',
                'points' => 5,
                'icon' => 'fas fa-star',
                'color' => '#FFD700'
            ],
            'helpful_received' => [
                'name' => 'Helpful Received',
                'description' => 'Post marked as helpful',
                'points' => 10,
                'icon' => 'fas fa-star',
                'color' => '#FFD700'
            ],
            'comment_created' => [
                'name' => 'Comment Created',
                'description' => 'User created a comment',
                'points' => 3,
                'icon' => 'fas fa-comment',
                'color' => '#00BCD4'
            ],
            'comment_edited' => [
                'name' => 'Comment Edited',
                'description' => 'User edited a comment',
                'points' => 1,
                'icon' => 'fas fa-edit',
                'color' => '#FF9800'
            ],
            'comment_deleted' => [
                'name' => 'Comment Deleted',
                'description' => 'User deleted a comment',
                'points' => -1,
                'icon' => 'fas fa-trash',
                'color' => '#F44336'
            ],
            'follow_user' => [
                'name' => 'Follow User',
                'description' => 'User followed another user',
                'points' => 2,
                'icon' => 'fas fa-user-plus',
                'color' => '#4CAF50'
            ],
            'unfollow_user' => [
                'name' => 'Unfollow User',
                'description' => 'User unfollowed another user',
                'points' => -1,
                'icon' => 'fas fa-user-minus',
                'color' => '#F44336'
            ],
            'follower_gained' => [
                'name' => 'Follower Gained',
                'description' => 'User gained a follower',
                'points' => 3,
                'icon' => 'fas fa-users',
                'color' => '#9C27B0'
            ],
            'follower_lost' => [
                'name' => 'Follower Lost',
                'description' => 'User lost a follower',
                'points' => -2,
                'icon' => 'fas fa-user-times',
                'color' => '#F44336'
            ],
            'profile_updated' => [
                'name' => 'Profile Updated',
                'description' => 'User updated their profile',
                'points' => 2,
                'icon' => 'fas fa-user-edit',
                'color' => '#2196F3'
            ],
            'avatar_uploaded' => [
                'name' => 'Avatar Uploaded',
                'description' => 'User uploaded an avatar',
                'points' => 5,
                'icon' => 'fas fa-image',
                'color' => '#4CAF50'
            ],
            'achievement_earned' => [
                'name' => 'Achievement Earned',
                'description' => 'User earned an achievement',
                'points' => 0, // Points are defined in achievement
                'icon' => 'fas fa-trophy',
                'color' => '#FFD700'
            ],
            'badge_earned' => [
                'name' => 'Badge Earned',
                'description' => 'User earned a badge',
                'points' => 0, // No direct points for badges
                'icon' => 'fas fa-medal',
                'color' => '#FFD700'
            ],
            'level_up' => [
                'name' => 'Level Up',
                'description' => 'User leveled up',
                'points' => 0, // Points are defined in level
                'icon' => 'fas fa-level-up-alt',
                'color' => '#4CAF50'
            ],
            'search_performed' => [
                'name' => 'Search Performed',
                'description' => 'User performed a search',
                'points' => 1,
                'icon' => 'fas fa-search',
                'color' => '#2196F3'
            ],
            'report_submitted' => [
                'name' => 'Report Submitted',
                'description' => 'User submitted a report',
                'points' => 2,
                'icon' => 'fas fa-flag',
                'color' => '#FF9800'
            ],
            'moderation_action' => [
                'name' => 'Moderation Action',
                'description' => 'Moderator performed an action',
                'points' => 5,
                'icon' => 'fas fa-shield-alt',
                'color' => '#9C27B0'
            ]
        ];
    }
    
    public function trackActivity(int $userId, string $activityType, array $data = []): bool {
        if (!isset($this->activityTypes[$activityType])) {
            return false;
        }
        
        $activity = $this->activityTypes[$activityType];
        
        try {
            $this->db->beginTransaction();
            
            // Record activity
            $this->db->insert('user_activities', [
                'user_id' => $userId,
                'activity_type' => $activityType,
                'activity_name' => $activity['name'],
                'activity_description' => $activity['description'],
                'points' => $activity['points'],
                'data' => json_encode($data),
                'ip_address' => $data['ip_address'] ?? null,
                'user_agent' => $data['user_agent'] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            // Update user points if applicable
            if ($activity['points'] !== 0) {
                $this->db->query(
                    "UPDATE users SET points = points + :points WHERE id = :user_id",
                    ['points' => $activity['points'], 'user_id' => $userId]
                );
            }
            
            // Update daily activity summary
            $this->updateDailyActivitySummary($userId, $activityType, $activity['points']);
            
            // Update weekly activity summary
            $this->updateWeeklyActivitySummary($userId, $activityType, $activity['points']);
            
            // Update monthly activity summary
            $this->updateMonthlyActivitySummary($userId, $activityType, $activity['points']);
            
            $this->db->commit();
            
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            error_log("Error tracking activity: " . $e->getMessage());
            return false;
        }
    }
    
    private function updateDailyActivitySummary(int $userId, string $activityType, int $points): void {
        $today = date('Y-m-d');
        
        $summary = $this->db->fetch(
            "SELECT * FROM daily_activity_summary 
             WHERE user_id = :user_id AND date = :date",
            ['user_id' => $userId, 'date' => $today]
        );
        
        if ($summary) {
            // Update existing summary
            $this->db->query(
                "UPDATE daily_activity_summary 
                 SET activity_count = activity_count + 1, 
                     points_earned = points_earned + :points,
                     updated_at = NOW()
                 WHERE user_id = :user_id AND date = :date",
                ['points' => $points, 'user_id' => $userId, 'date' => $today]
            );
        } else {
            // Create new summary
            $this->db->insert('daily_activity_summary', [
                'user_id' => $userId,
                'date' => $today,
                'activity_count' => 1,
                'points_earned' => $points,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }
    }
    
    private function updateWeeklyActivitySummary(int $userId, string $activityType, int $points): void {
        $week = date('Y-W');
        
        $summary = $this->db->fetch(
            "SELECT * FROM weekly_activity_summary 
             WHERE user_id = :user_id AND week = :week",
            ['user_id' => $userId, 'week' => $week]
        );
        
        if ($summary) {
            // Update existing summary
            $this->db->query(
                "UPDATE weekly_activity_summary 
                 SET activity_count = activity_count + 1, 
                     points_earned = points_earned + :points,
                     updated_at = NOW()
                 WHERE user_id = :user_id AND week = :week",
                ['points' => $points, 'user_id' => $userId, 'week' => $week]
            );
        } else {
            // Create new summary
            $this->db->insert('weekly_activity_summary', [
                'user_id' => $userId,
                'week' => $week,
                'activity_count' => 1,
                'points_earned' => $points,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }
    }
    
    private function updateMonthlyActivitySummary(int $userId, string $activityType, int $points): void {
        $month = date('Y-m');
        
        $summary = $this->db->fetch(
            "SELECT * FROM monthly_activity_summary 
             WHERE user_id = :user_id AND month = :month",
            ['user_id' => $userId, 'month' => $month]
        );
        
        if ($summary) {
            // Update existing summary
            $this->db->query(
                "UPDATE monthly_activity_summary 
                 SET activity_count = activity_count + 1, 
                     points_earned = points_earned + :points,
                     updated_at = NOW()
                 WHERE user_id = :user_id AND month = :month",
                ['points' => $points, 'user_id' => $userId, 'month' => $month]
            );
        } else {
            // Create new summary
            $this->db->insert('monthly_activity_summary', [
                'user_id' => $userId,
                'month' => $month,
                'activity_count' => 1,
                'points_earned' => $points,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }
    }
    
    public function getUserActivities(int $userId, int $limit = 50): array {
        return $this->db->fetchAll(
            "SELECT * FROM user_activities 
             WHERE user_id = :user_id 
             ORDER BY created_at DESC 
             LIMIT :limit",
            ['user_id' => $userId, 'limit' => $limit]
        );
    }
    
    public function getUserActivitiesByType(int $userId, string $activityType, int $limit = 50): array {
        return $this->db->fetchAll(
            "SELECT * FROM user_activities 
             WHERE user_id = :user_id AND activity_type = :activity_type 
             ORDER BY created_at DESC 
             LIMIT :limit",
            ['user_id' => $userId, 'activity_type' => $activityType, 'limit' => $limit]
        );
    }
    
    public function getUserActivitySummary(int $userId, string $period = 'daily'): array {
        switch ($period) {
            case 'daily':
                return $this->getDailyActivitySummary($userId);
            case 'weekly':
                return $this->getWeeklyActivitySummary($userId);
            case 'monthly':
                return $this->getMonthlyActivitySummary($userId);
            default:
                return [];
        }
    }
    
    private function getDailyActivitySummary(int $userId): array {
        return $this->db->fetchAll(
            "SELECT * FROM daily_activity_summary 
             WHERE user_id = :user_id 
             ORDER BY date DESC 
             LIMIT 30",
            ['user_id' => $userId]
        );
    }
    
    private function getWeeklyActivitySummary(int $userId): array {
        return $this->db->fetchAll(
            "SELECT * FROM weekly_activity_summary 
             WHERE user_id = :user_id 
             ORDER BY week DESC 
             LIMIT 12",
            ['user_id' => $userId]
        );
    }
    
    private function getMonthlyActivitySummary(int $userId): array {
        return $this->db->fetchAll(
            "SELECT * FROM monthly_activity_summary 
             WHERE user_id = :user_id 
             ORDER BY month DESC 
             LIMIT 12",
            ['user_id' => $userId]
        );
    }
    
    public function getActivityStats(): array {
        return [
            'total_activities' => $this->db->fetchColumn("SELECT COUNT(*) FROM user_activities"),
            'activities_by_type' => $this->getActivitiesByTypeStats(),
            'daily_activities' => $this->getDailyActivitiesStats(),
            'weekly_activities' => $this->getWeeklyActivitiesStats(),
            'monthly_activities' => $this->getMonthlyActivitiesStats(),
            'top_users' => $this->getTopActiveUsers()
        ];
    }
    
    private function getActivitiesByTypeStats(): array {
        return $this->db->fetchAll(
            "SELECT activity_type, COUNT(*) as count, SUM(points) as total_points
             FROM user_activities 
             GROUP BY activity_type 
             ORDER BY count DESC"
        );
    }
    
    private function getDailyActivitiesStats(): array {
        return $this->db->fetchAll(
            "SELECT DATE(created_at) as date, COUNT(*) as count, SUM(points) as total_points
             FROM user_activities 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             GROUP BY DATE(created_at) 
             ORDER BY date DESC"
        );
    }
    
    private function getWeeklyActivitiesStats(): array {
        return $this->db->fetchAll(
            "SELECT YEARWEEK(created_at) as week, COUNT(*) as count, SUM(points) as total_points
             FROM user_activities 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 WEEK)
             GROUP BY YEARWEEK(created_at) 
             ORDER BY week DESC"
        );
    }
    
    private function getMonthlyActivitiesStats(): array {
        return $this->db->fetchAll(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count, SUM(points) as total_points
             FROM user_activities 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
             GROUP BY DATE_FORMAT(created_at, '%Y-%m') 
             ORDER BY month DESC"
        );
    }
    
    private function getTopActiveUsers(int $limit = 10): array {
        return $this->db->fetchAll(
            "SELECT u.id, u.username, u.avatar, 
                    COUNT(ua.id) as activity_count, 
                    SUM(ua.points) as total_points
             FROM user_activities ua
             JOIN users u ON ua.user_id = u.id
             WHERE ua.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             GROUP BY u.id, u.username, u.avatar
             ORDER BY activity_count DESC, total_points DESC
             LIMIT :limit",
            ['limit' => $limit]
        );
    }
    
    public function getActivityTypes(): array {
        return $this->activityTypes;
    }
    
    public function getActivityLeaderboard(int $limit = 10): array {
        return $this->db->fetchAll(
            "SELECT u.id, u.username, u.avatar, 
                    COUNT(ua.id) as activity_count, 
                    SUM(ua.points) as total_points,
                    MAX(ua.created_at) as last_activity
             FROM user_activities ua
             JOIN users u ON ua.user_id = u.id
             GROUP BY u.id, u.username, u.avatar
             ORDER BY activity_count DESC, total_points DESC
             LIMIT :limit",
            ['limit' => $limit]
        );
    }
    
    public function getActivityTrends(int $userId, int $days = 30): array {
        return $this->db->fetchAll(
            "SELECT DATE(created_at) as date, 
                    COUNT(*) as activity_count, 
                    SUM(points) as points_earned,
                    COUNT(DISTINCT activity_type) as unique_activities
             FROM user_activities 
             WHERE user_id = :user_id 
             AND created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
             GROUP BY DATE(created_at) 
             ORDER BY date DESC",
            ['user_id' => $userId, 'days' => $days]
        );
    }
    
    public function getActivityHeatmap(int $userId, int $days = 365): array {
        return $this->db->fetchAll(
            "SELECT DATE(created_at) as date, COUNT(*) as activity_count
             FROM user_activities 
             WHERE user_id = :user_id 
             AND created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
             GROUP BY DATE(created_at) 
             ORDER BY date DESC",
            ['user_id' => $userId, 'days' => $days]
        );
    }
    
    public function getActivityAnalytics(int $userId): array {
        $activities = $this->getUserActivities($userId, 1000);
        $totalActivities = count($activities);
        $totalPoints = array_sum(array_column($activities, 'points'));
        
        $activitiesByType = [];
        foreach ($activities as $activity) {
            $type = $activity['activity_type'];
            if (!isset($activitiesByType[$type])) {
                $activitiesByType[$type] = 0;
            }
            $activitiesByType[$type]++;
        }
        
        $activitiesByDay = [];
        foreach ($activities as $activity) {
            $day = date('Y-m-d', strtotime($activity['created_at']));
            if (!isset($activitiesByDay[$day])) {
                $activitiesByDay[$day] = 0;
            }
            $activitiesByDay[$day]++;
        }
        
        $mostActiveDay = array_search(max($activitiesByDay), $activitiesByDay);
        $averageActivitiesPerDay = count($activitiesByDay) > 0 ? 
            round($totalActivities / count($activitiesByDay), 2) : 0;
        
        return [
            'total_activities' => $totalActivities,
            'total_points' => $totalPoints,
            'activities_by_type' => $activitiesByType,
            'activities_by_day' => $activitiesByDay,
            'most_active_day' => $mostActiveDay,
            'average_activities_per_day' => $averageActivitiesPerDay,
            'most_common_activity' => array_search(max($activitiesByType), $activitiesByType)
        ];
    }
    
    public function getActivityComparison(int $userId1, int $userId2, int $days = 30): array {
        $activities1 = $this->db->fetchAll(
            "SELECT COUNT(*) as count, SUM(points) as points
             FROM user_activities 
             WHERE user_id = :user_id 
             AND created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)",
            ['user_id' => $userId1, 'days' => $days]
        );
        
        $activities2 = $this->db->fetchAll(
            "SELECT COUNT(*) as count, SUM(points) as points
             FROM user_activities 
             WHERE user_id = :user_id 
             AND created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)",
            ['user_id' => $userId2, 'days' => $days]
        );
        
        $count1 = $activities1[0]['count'] ?? 0;
        $count2 = $activities2[0]['count'] ?? 0;
        $points1 = $activities1[0]['points'] ?? 0;
        $points2 = $activities2[0]['points'] ?? 0;
        
        return [
            'user1_activities' => $count1,
            'user2_activities' => $count2,
            'user1_points' => $points1,
            'user2_points' => $points2,
            'activity_difference' => $count1 - $count2,
            'points_difference' => $points1 - $points2,
            'more_active_user' => $count1 > $count2 ? $userId1 : $userId2,
            'more_points_user' => $points1 > $points2 ? $userId1 : $userId2
        ];
    }
}