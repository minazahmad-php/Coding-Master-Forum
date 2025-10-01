<?php
declare(strict_types=1);

namespace Services;

class EngagementMetricsService {
    private Database $db;
    private array $engagementTypes;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->engagementTypes = $this->getEngagementTypes();
    }
    
    private function getEngagementTypes(): array {
        return [
            'post_engagement' => [
                'name' => 'Post Engagement',
                'description' => 'Engagement with posts (likes, comments, shares)',
                'weight' => 1.0,
                'icon' => 'fas fa-comments',
                'color' => '#2196F3'
            ],
            'thread_engagement' => [
                'name' => 'Thread Engagement',
                'description' => 'Engagement with threads (views, replies, likes)',
                'weight' => 1.5,
                'icon' => 'fas fa-comments',
                'color' => '#9C27B0'
            ],
            'user_interaction' => [
                'name' => 'User Interaction',
                'description' => 'Interactions between users (follows, mentions, messages)',
                'weight' => 2.0,
                'icon' => 'fas fa-users',
                'color' => '#4CAF50'
            ],
            'content_creation' => [
                'name' => 'Content Creation',
                'description' => 'Creating new content (posts, threads, comments)',
                'weight' => 3.0,
                'icon' => 'fas fa-edit',
                'color' => '#FF9800'
            ],
            'community_participation' => [
                'name' => 'Community Participation',
                'description' => 'Participating in community activities',
                'weight' => 2.5,
                'icon' => 'fas fa-handshake',
                'color' => '#E91E63'
            ],
            'helpful_contributions' => [
                'name' => 'Helpful Contributions',
                'description' => 'Providing helpful answers and contributions',
                'weight' => 4.0,
                'icon' => 'fas fa-star',
                'color' => '#FFD700'
            ]
        ];
    }
    
    public function calculateEngagementScore(int $userId, string $period = 'monthly'): array {
        $startDate = $this->getPeriodStartDate($period);
        $endDate = date('Y-m-d H:i:s');
        
        $metrics = $this->getEngagementMetrics($userId, $startDate, $endDate);
        $score = $this->calculateScore($metrics);
        
        return [
            'user_id' => $userId,
            'period' => $period,
            'score' => $score,
            'metrics' => $metrics,
            'calculated_at' => date('Y-m-d H:i:s')
        ];
    }
    
    private function getPeriodStartDate(string $period): string {
        switch ($period) {
            case 'daily':
                return date('Y-m-d 00:00:00');
            case 'weekly':
                return date('Y-m-d 00:00:00', strtotime('-7 days'));
            case 'monthly':
                return date('Y-m-d 00:00:00', strtotime('-30 days'));
            case 'yearly':
                return date('Y-m-d 00:00:00', strtotime('-365 days'));
            default:
                return date('Y-m-d 00:00:00', strtotime('-30 days'));
        }
    }
    
    private function getEngagementMetrics(int $userId, string $startDate, string $endDate): array {
        $metrics = [];
        
        // Post engagement
        $postEngagement = $this->db->fetch(
            "SELECT 
                COUNT(DISTINCT p.id) as posts_created,
                COUNT(DISTINCT l.id) as likes_received,
                COUNT(DISTINCT c.id) as comments_received,
                COUNT(DISTINCT h.id) as helpful_marks
             FROM posts p
             LEFT JOIN likes l ON p.id = l.post_id
             LEFT JOIN comments c ON p.id = c.post_id
             LEFT JOIN helpful_marks h ON p.id = h.post_id
             WHERE p.user_id = :user_id 
             AND p.created_at BETWEEN :start_date AND :end_date",
            ['user_id' => $userId, 'start_date' => $startDate, 'end_date' => $endDate]
        );
        
        $metrics['post_engagement'] = [
            'posts_created' => $postEngagement['posts_created'] ?? 0,
            'likes_received' => $postEngagement['likes_received'] ?? 0,
            'comments_received' => $postEngagement['comments_received'] ?? 0,
            'helpful_marks' => $postEngagement['helpful_marks'] ?? 0,
            'score' => ($postEngagement['posts_created'] ?? 0) * 3 + 
                      ($postEngagement['likes_received'] ?? 0) * 1 + 
                      ($postEngagement['comments_received'] ?? 0) * 2 + 
                      ($postEngagement['helpful_marks'] ?? 0) * 5
        ];
        
        // Thread engagement
        $threadEngagement = $this->db->fetch(
            "SELECT 
                COUNT(DISTINCT t.id) as threads_created,
                SUM(t.views) as total_views,
                COUNT(DISTINCT p.id) as replies_received,
                COUNT(DISTINCT l.id) as likes_received
             FROM threads t
             LEFT JOIN posts p ON t.id = p.thread_id AND p.user_id != :user_id
             LEFT JOIN likes l ON t.id = l.thread_id
             WHERE t.user_id = :user_id 
             AND t.created_at BETWEEN :start_date AND :end_date",
            ['user_id' => $userId, 'start_date' => $startDate, 'end_date' => $endDate]
        );
        
        $metrics['thread_engagement'] = [
            'threads_created' => $threadEngagement['threads_created'] ?? 0,
            'total_views' => $threadEngagement['total_views'] ?? 0,
            'replies_received' => $threadEngagement['replies_received'] ?? 0,
            'likes_received' => $threadEngagement['likes_received'] ?? 0,
            'score' => ($threadEngagement['threads_created'] ?? 0) * 10 + 
                      ($threadEngagement['total_views'] ?? 0) * 0.1 + 
                      ($threadEngagement['replies_received'] ?? 0) * 3 + 
                      ($threadEngagement['likes_received'] ?? 0) * 2
        ];
        
        // User interaction
        $userInteraction = $this->db->fetch(
            "SELECT 
                COUNT(DISTINCT f.id) as follows_given,
                COUNT(DISTINCT f2.id) as followers_gained,
                COUNT(DISTINCT m.id) as messages_sent,
                COUNT(DISTINCT m2.id) as messages_received
             FROM users u
             LEFT JOIN follows f ON u.id = f.follower_id
             LEFT JOIN follows f2 ON u.id = f2.following_id
             LEFT JOIN messages m ON u.id = m.sender_id
             LEFT JOIN messages m2 ON u.id = m2.receiver_id
             WHERE u.id = :user_id",
            ['user_id' => $userId]
        );
        
        $metrics['user_interaction'] = [
            'follows_given' => $userInteraction['follows_given'] ?? 0,
            'followers_gained' => $userInteraction['followers_gained'] ?? 0,
            'messages_sent' => $userInteraction['messages_sent'] ?? 0,
            'messages_received' => $userInteraction['messages_received'] ?? 0,
            'score' => ($userInteraction['follows_given'] ?? 0) * 2 + 
                      ($userInteraction['followers_gained'] ?? 0) * 3 + 
                      ($userInteraction['messages_sent'] ?? 0) * 1 + 
                      ($userInteraction['messages_received'] ?? 0) * 1
        ];
        
        // Content creation
        $contentCreation = $this->db->fetch(
            "SELECT 
                COUNT(DISTINCT p.id) as posts_created,
                COUNT(DISTINCT t.id) as threads_created,
                COUNT(DISTINCT c.id) as comments_created
             FROM users u
             LEFT JOIN posts p ON u.id = p.user_id
             LEFT JOIN threads t ON u.id = t.user_id
             LEFT JOIN comments c ON u.id = c.user_id
             WHERE u.id = :user_id 
             AND (p.created_at BETWEEN :start_date AND :end_date 
                  OR t.created_at BETWEEN :start_date AND :end_date 
                  OR c.created_at BETWEEN :start_date AND :end_date)",
            ['user_id' => $userId, 'start_date' => $startDate, 'end_date' => $endDate]
        );
        
        $metrics['content_creation'] = [
            'posts_created' => $contentCreation['posts_created'] ?? 0,
            'threads_created' => $contentCreation['threads_created'] ?? 0,
            'comments_created' => $contentCreation['comments_created'] ?? 0,
            'score' => ($contentCreation['posts_created'] ?? 0) * 5 + 
                      ($contentCreation['threads_created'] ?? 0) * 10 + 
                      ($contentCreation['comments_created'] ?? 0) * 2
        ];
        
        // Community participation
        $communityParticipation = $this->db->fetch(
            "SELECT 
                COUNT(DISTINCT r.id) as reports_submitted,
                COUNT(DISTINCT v.id) as votes_cast,
                COUNT(DISTINCT p.id) as polls_participated
             FROM users u
             LEFT JOIN reports r ON u.id = r.user_id
             LEFT JOIN votes v ON u.id = v.user_id
             LEFT JOIN poll_participants p ON u.id = p.user_id
             WHERE u.id = :user_id 
             AND (r.created_at BETWEEN :start_date AND :end_date 
                  OR v.created_at BETWEEN :start_date AND :end_date 
                  OR p.created_at BETWEEN :start_date AND :end_date)",
            ['user_id' => $userId, 'start_date' => $startDate, 'end_date' => $endDate]
        );
        
        $metrics['community_participation'] = [
            'reports_submitted' => $communityParticipation['reports_submitted'] ?? 0,
            'votes_cast' => $communityParticipation['votes_cast'] ?? 0,
            'polls_participated' => $communityParticipation['polls_participated'] ?? 0,
            'score' => ($communityParticipation['reports_submitted'] ?? 0) * 3 + 
                      ($communityParticipation['votes_cast'] ?? 0) * 1 + 
                      ($communityParticipation['polls_participated'] ?? 0) * 2
        ];
        
        // Helpful contributions
        $helpfulContributions = $this->db->fetch(
            "SELECT 
                COUNT(DISTINCT h.id) as helpful_marks_received,
                COUNT(DISTINCT a.id) as answers_provided,
                COUNT(DISTINCT s.id) as solutions_provided
             FROM users u
             LEFT JOIN helpful_marks h ON u.id = h.user_id
             LEFT JOIN answers a ON u.id = a.user_id
             LEFT JOIN solutions s ON u.id = s.user_id
             WHERE u.id = :user_id 
             AND (h.created_at BETWEEN :start_date AND :end_date 
                  OR a.created_at BETWEEN :start_date AND :end_date 
                  OR s.created_at BETWEEN :start_date AND :end_date)",
            ['user_id' => $userId, 'start_date' => $startDate, 'end_date' => $endDate]
        );
        
        $metrics['helpful_contributions'] = [
            'helpful_marks_received' => $helpfulContributions['helpful_marks_received'] ?? 0,
            'answers_provided' => $helpfulContributions['answers_provided'] ?? 0,
            'solutions_provided' => $helpfulContributions['solutions_provided'] ?? 0,
            'score' => ($helpfulContributions['helpful_marks_received'] ?? 0) * 10 + 
                      ($helpfulContributions['answers_provided'] ?? 0) * 5 + 
                      ($helpfulContributions['solutions_provided'] ?? 0) * 15
        ];
        
        return $metrics;
    }
    
    private function calculateScore(array $metrics): float {
        $totalScore = 0;
        
        foreach ($metrics as $type => $metric) {
            $weight = $this->engagementTypes[$type]['weight'] ?? 1.0;
            $totalScore += $metric['score'] * $weight;
        }
        
        return round($totalScore, 2);
    }
    
    public function getEngagementLeaderboard(string $period = 'monthly', int $limit = 10): array {
        $startDate = $this->getPeriodStartDate($period);
        $endDate = date('Y-m-d H:i:s');
        
        $users = $this->db->fetchAll(
            "SELECT u.id, u.username, u.avatar, u.points, u.level
             FROM users u
             ORDER BY u.points DESC
             LIMIT :limit",
            ['limit' => $limit]
        );
        
        $leaderboard = [];
        foreach ($users as $user) {
            $engagement = $this->calculateEngagementScore($user['id'], $period);
            $leaderboard[] = [
                'user' => $user,
                'engagement_score' => $engagement['score'],
                'metrics' => $engagement['metrics']
            ];
        }
        
        // Sort by engagement score
        usort($leaderboard, function($a, $b) {
            return $b['engagement_score'] <=> $a['engagement_score'];
        });
        
        return $leaderboard;
    }
    
    public function getEngagementStats(): array {
        return [
            'total_users' => $this->db->fetchColumn("SELECT COUNT(*) FROM users"),
            'active_users_daily' => $this->getActiveUsersCount('daily'),
            'active_users_weekly' => $this->getActiveUsersCount('weekly'),
            'active_users_monthly' => $this->getActiveUsersCount('monthly'),
            'engagement_by_type' => $this->getEngagementByTypeStats(),
            'engagement_trends' => $this->getEngagementTrends(),
            'top_engagers' => $this->getTopEngagers()
        ];
    }
    
    private function getActiveUsersCount(string $period): int {
        $startDate = $this->getPeriodStartDate($period);
        
        return $this->db->fetchColumn(
            "SELECT COUNT(DISTINCT user_id) FROM user_activities 
             WHERE created_at >= :start_date",
            ['start_date' => $startDate]
        );
    }
    
    private function getEngagementByTypeStats(): array {
        return $this->db->fetchAll(
            "SELECT activity_type, COUNT(*) as count, SUM(points) as total_points
             FROM user_activities 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             GROUP BY activity_type 
             ORDER BY count DESC"
        );
    }
    
    private function getEngagementTrends(): array {
        return $this->db->fetchAll(
            "SELECT DATE(created_at) as date, 
                    COUNT(DISTINCT user_id) as active_users,
                    COUNT(*) as total_activities,
                    SUM(points) as total_points
             FROM user_activities 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             GROUP BY DATE(created_at) 
             ORDER BY date DESC"
        );
    }
    
    private function getTopEngagers(int $limit = 10): array {
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
    
    public function getEngagementTypes(): array {
        return $this->engagementTypes;
    }
    
    public function getEngagementHistory(int $userId, string $period = 'monthly', int $limit = 12): array {
        $history = [];
        
        for ($i = 0; $i < $limit; $i++) {
            $periodStart = $this->getPeriodStartDate($period, $i);
            $periodEnd = $this->getPeriodStartDate($period, $i - 1);
            
            $engagement = $this->calculateEngagementScore($userId, $period);
            $history[] = [
                'period' => $periodStart,
                'score' => $engagement['score'],
                'metrics' => $engagement['metrics']
            ];
        }
        
        return $history;
    }
    
    private function getPeriodStartDate(string $period, int $offset = 0): string {
        switch ($period) {
            case 'daily':
                return date('Y-m-d 00:00:00', strtotime("-{$offset} days"));
            case 'weekly':
                return date('Y-m-d 00:00:00', strtotime("-" . ($offset * 7) . " days"));
            case 'monthly':
                return date('Y-m-d 00:00:00', strtotime("-{$offset} months"));
            case 'yearly':
                return date('Y-m-d 00:00:00', strtotime("-{$offset} years"));
            default:
                return date('Y-m-d 00:00:00', strtotime("-{$offset} days"));
        }
    }
    
    public function getEngagementAnalytics(int $userId): array {
        $dailyEngagement = $this->calculateEngagementScore($userId, 'daily');
        $weeklyEngagement = $this->calculateEngagementScore($userId, 'weekly');
        $monthlyEngagement = $this->calculateEngagementScore($userId, 'monthly');
        
        $history = $this->getEngagementHistory($userId, 'monthly', 12);
        $scores = array_column($history, 'score');
        
        $averageScore = count($scores) > 0 ? round(array_sum($scores) / count($scores), 2) : 0;
        $highestScore = max($scores);
        $lowestScore = min($scores);
        
        $trend = 'stable';
        if (count($scores) >= 2) {
            $recent = array_slice($scores, 0, 3);
            $older = array_slice($scores, 3, 3);
            
            $recentAvg = array_sum($recent) / count($recent);
            $olderAvg = array_sum($older) / count($older);
            
            if ($recentAvg > $olderAvg * 1.1) {
                $trend = 'increasing';
            } elseif ($recentAvg < $olderAvg * 0.9) {
                $trend = 'decreasing';
            }
        }
        
        return [
            'daily_score' => $dailyEngagement['score'],
            'weekly_score' => $weeklyEngagement['score'],
            'monthly_score' => $monthlyEngagement['score'],
            'average_score' => $averageScore,
            'highest_score' => $highestScore,
            'lowest_score' => $lowestScore,
            'trend' => $trend,
            'history' => $history
        ];
    }
    
    public function getEngagementComparison(int $userId1, int $userId2, string $period = 'monthly'): array {
        $engagement1 = $this->calculateEngagementScore($userId1, $period);
        $engagement2 = $this->calculateEngagementScore($userId2, $period);
        
        return [
            'user1_score' => $engagement1['score'],
            'user2_score' => $engagement2['score'],
            'score_difference' => $engagement1['score'] - $engagement2['score'],
            'higher_score_user' => $engagement1['score'] > $engagement2['score'] ? $userId1 : $userId2,
            'period' => $period,
            'user1_metrics' => $engagement1['metrics'],
            'user2_metrics' => $engagement2['metrics']
        ];
    }
    
    public function getEngagementInsights(int $userId): array {
        $engagement = $this->calculateEngagementScore($userId, 'monthly');
        $metrics = $engagement['metrics'];
        
        $insights = [];
        
        // Find strongest engagement type
        $maxScore = 0;
        $strongestType = null;
        foreach ($metrics as $type => $metric) {
            if ($metric['score'] > $maxScore) {
                $maxScore = $metric['score'];
                $strongestType = $type;
            }
        }
        
        if ($strongestType) {
            $insights[] = [
                'type' => 'strongest_engagement',
                'message' => "Your strongest engagement area is {$this->engagementTypes[$strongestType]['name']}",
                'score' => $maxScore
            ];
        }
        
        // Find weakest engagement type
        $minScore = PHP_FLOAT_MAX;
        $weakestType = null;
        foreach ($metrics as $type => $metric) {
            if ($metric['score'] < $minScore) {
                $minScore = $metric['score'];
                $weakestType = $type;
            }
        }
        
        if ($weakestType) {
            $insights[] = [
                'type' => 'weakest_engagement',
                'message' => "Consider improving your {$this->engagementTypes[$weakestType]['name']}",
                'score' => $minScore
            ];
        }
        
        // Overall engagement level
        $totalScore = $engagement['score'];
        if ($totalScore > 1000) {
            $insights[] = [
                'type' => 'high_engagement',
                'message' => 'You are a highly engaged community member!',
                'score' => $totalScore
            ];
        } elseif ($totalScore > 500) {
            $insights[] = [
                'type' => 'moderate_engagement',
                'message' => 'You have good engagement levels',
                'score' => $totalScore
            ];
        } else {
            $insights[] = [
                'type' => 'low_engagement',
                'message' => 'Consider increasing your community participation',
                'score' => $totalScore
            ];
        }
        
        return $insights;
    }
}