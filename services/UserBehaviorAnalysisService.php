<?php
declare(strict_types=1);

namespace Services;

class UserBehaviorAnalysisService {
    private Database $db;
    private array $behaviorTypes;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->behaviorTypes = $this->getBehaviorTypes();
    }
    
    private function getBehaviorTypes(): array {
        return [
            'content_consumption' => [
                'name' => 'Content Consumption',
                'description' => 'How users consume content',
                'metrics' => ['views', 'time_spent', 'scroll_depth', 'click_through_rate'],
                'icon' => 'fas fa-eye',
                'color' => '#2196F3'
            ],
            'content_creation' => [
                'name' => 'Content Creation',
                'description' => 'How users create content',
                'metrics' => ['posts_created', 'threads_created', 'comments_created', 'quality_score'],
                'icon' => 'fas fa-edit',
                'color' => '#4CAF50'
            ],
            'social_interaction' => [
                'name' => 'Social Interaction',
                'description' => 'How users interact socially',
                'metrics' => ['likes_given', 'likes_received', 'follows', 'messages_sent'],
                'icon' => 'fas fa-users',
                'color' => '#9C27B0'
            ],
            'navigation_patterns' => [
                'name' => 'Navigation Patterns',
                'description' => 'How users navigate the site',
                'metrics' => ['page_views', 'session_duration', 'bounce_rate', 'return_visits'],
                'icon' => 'fas fa-route',
                'color' => '#FF9800'
            ],
            'engagement_patterns' => [
                'name' => 'Engagement Patterns',
                'description' => 'How users engage with the platform',
                'metrics' => ['daily_active_time', 'weekly_active_time', 'monthly_active_time', 'engagement_score'],
                'icon' => 'fas fa-chart-line',
                'color' => '#E91E63'
            ],
            'learning_behavior' => [
                'name' => 'Learning Behavior',
                'description' => 'How users learn and improve',
                'metrics' => ['skill_progression', 'knowledge_gaps', 'learning_velocity', 'retention_rate'],
                'icon' => 'fas fa-graduation-cap',
                'color' => '#00BCD4'
            ]
        ];
    }
    
    public function analyzeUserBehavior(int $userId, string $period = 'monthly'): array {
        $startDate = $this->getPeriodStartDate($period);
        $endDate = date('Y-m-d H:i:s');
        
        $analysis = [
            'user_id' => $userId,
            'period' => $period,
            'analysis_date' => date('Y-m-d H:i:s'),
            'behaviors' => []
        ];
        
        foreach ($this->behaviorTypes as $type => $behavior) {
            $analysis['behaviors'][$type] = $this->analyzeBehaviorType($userId, $type, $startDate, $endDate);
        }
        
        // Calculate overall behavior score
        $analysis['overall_score'] = $this->calculateOverallBehaviorScore($analysis['behaviors']);
        
        // Generate insights
        $analysis['insights'] = $this->generateBehaviorInsights($analysis['behaviors']);
        
        // Generate recommendations
        $analysis['recommendations'] = $this->generateBehaviorRecommendations($analysis['behaviors']);
        
        return $analysis;
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
    
    private function analyzeBehaviorType(int $userId, string $type, string $startDate, string $endDate): array {
        switch ($type) {
            case 'content_consumption':
                return $this->analyzeContentConsumption($userId, $startDate, $endDate);
            case 'content_creation':
                return $this->analyzeContentCreation($userId, $startDate, $endDate);
            case 'social_interaction':
                return $this->analyzeSocialInteraction($userId, $startDate, $endDate);
            case 'navigation_patterns':
                return $this->analyzeNavigationPatterns($userId, $startDate, $endDate);
            case 'engagement_patterns':
                return $this->analyzeEngagementPatterns($userId, $startDate, $endDate);
            case 'learning_behavior':
                return $this->analyzeLearningBehavior($userId, $startDate, $endDate);
            default:
                return [];
        }
    }
    
    private function analyzeContentConsumption(int $userId, string $startDate, string $endDate): array {
        $views = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM post_views 
             WHERE user_id = :user_id AND viewed_at BETWEEN :start_date AND :end_date",
            ['user_id' => $userId, 'start_date' => $startDate, 'end_date' => $endDate]
        );
        
        $timeSpent = $this->db->fetchColumn(
            "SELECT SUM(time_spent) FROM post_views 
             WHERE user_id = :user_id AND viewed_at BETWEEN :start_date AND :end_date",
            ['user_id' => $userId, 'start_date' => $startDate, 'end_date' => $endDate]
        );
        
        $scrollDepth = $this->db->fetchColumn(
            "SELECT AVG(scroll_depth) FROM post_views 
             WHERE user_id = :user_id AND viewed_at BETWEEN :start_date AND :end_date",
            ['user_id' => $userId, 'start_date' => $startDate, 'end_date' => $endDate]
        );
        
        $clickThroughRate = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM post_clicks 
             WHERE user_id = :user_id AND clicked_at BETWEEN :start_date AND :end_date",
            ['user_id' => $userId, 'start_date' => $startDate, 'end_date' => $endDate]
        );
        
        return [
            'views' => $views,
            'time_spent' => $timeSpent,
            'scroll_depth' => round($scrollDepth, 2),
            'click_through_rate' => $clickThroughRate,
            'score' => $this->calculateContentConsumptionScore($views, $timeSpent, $scrollDepth, $clickThroughRate)
        ];
    }
    
    private function analyzeContentCreation(int $userId, string $startDate, string $endDate): array {
        $postsCreated = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM posts 
             WHERE user_id = :user_id AND created_at BETWEEN :start_date AND :end_date",
            ['user_id' => $userId, 'start_date' => $startDate, 'end_date' => $endDate]
        );
        
        $threadsCreated = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM threads 
             WHERE user_id = :user_id AND created_at BETWEEN :start_date AND :end_date",
            ['user_id' => $userId, 'start_date' => $startDate, 'end_date' => $endDate]
        );
        
        $commentsCreated = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM comments 
             WHERE user_id = :user_id AND created_at BETWEEN :start_date AND :end_date",
            ['user_id' => $userId, 'start_date' => $startDate, 'end_date' => $endDate]
        );
        
        $qualityScore = $this->calculateContentQualityScore($userId, $startDate, $endDate);
        
        return [
            'posts_created' => $postsCreated,
            'threads_created' => $threadsCreated,
            'comments_created' => $commentsCreated,
            'quality_score' => $qualityScore,
            'score' => $this->calculateContentCreationScore($postsCreated, $threadsCreated, $commentsCreated, $qualityScore)
        ];
    }
    
    private function analyzeSocialInteraction(int $userId, string $startDate, string $endDate): array {
        $likesGiven = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM likes 
             WHERE user_id = :user_id AND created_at BETWEEN :start_date AND :end_date",
            ['user_id' => $userId, 'start_date' => $startDate, 'end_date' => $endDate]
        );
        
        $likesReceived = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM likes l
             JOIN posts p ON l.post_id = p.id
             WHERE p.user_id = :user_id AND l.created_at BETWEEN :start_date AND :end_date",
            ['user_id' => $userId, 'start_date' => $startDate, 'end_date' => $endDate]
        );
        
        $follows = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM follows 
             WHERE follower_id = :user_id AND created_at BETWEEN :start_date AND :end_date",
            ['user_id' => $userId, 'start_date' => $startDate, 'end_date' => $endDate]
        );
        
        $messagesSent = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM messages 
             WHERE sender_id = :user_id AND created_at BETWEEN :start_date AND :end_date",
            ['user_id' => $userId, 'start_date' => $startDate, 'end_date' => $endDate]
        );
        
        return [
            'likes_given' => $likesGiven,
            'likes_received' => $likesReceived,
            'follows' => $follows,
            'messages_sent' => $messagesSent,
            'score' => $this->calculateSocialInteractionScore($likesGiven, $likesReceived, $follows, $messagesSent)
        ];
    }
    
    private function analyzeNavigationPatterns(int $userId, string $startDate, string $endDate): array {
        $pageViews = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM page_views 
             WHERE user_id = :user_id AND viewed_at BETWEEN :start_date AND :end_date",
            ['user_id' => $userId, 'start_date' => $startDate, 'end_date' => $endDate]
        );
        
        $sessionDuration = $this->db->fetchColumn(
            "SELECT AVG(session_duration) FROM user_sessions 
             WHERE user_id = :user_id AND started_at BETWEEN :start_date AND :end_date",
            ['user_id' => $userId, 'start_date' => $startDate, 'end_date' => $endDate]
        );
        
        $bounceRate = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM user_sessions 
             WHERE user_id = :user_id AND page_views = 1 AND started_at BETWEEN :start_date AND :end_date",
            ['user_id' => $userId, 'start_date' => $startDate, 'end_date' => $endDate]
        );
        
        $returnVisits = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM user_sessions 
             WHERE user_id = :user_id AND is_return_visit = 1 AND started_at BETWEEN :start_date AND :end_date",
            ['user_id' => $userId, 'start_date' => $startDate, 'end_date' => $endDate]
        );
        
        return [
            'page_views' => $pageViews,
            'session_duration' => round($sessionDuration, 2),
            'bounce_rate' => $bounceRate,
            'return_visits' => $returnVisits,
            'score' => $this->calculateNavigationScore($pageViews, $sessionDuration, $bounceRate, $returnVisits)
        ];
    }
    
    private function analyzeEngagementPatterns(int $userId, string $startDate, string $endDate): array {
        $dailyActiveTime = $this->db->fetchColumn(
            "SELECT AVG(active_time) FROM daily_activity_summary 
             WHERE user_id = :user_id AND date BETWEEN :start_date AND :end_date",
            ['user_id' => $userId, 'start_date' => $startDate, 'end_date' => $endDate]
        );
        
        $weeklyActiveTime = $this->db->fetchColumn(
            "SELECT AVG(active_time) FROM weekly_activity_summary 
             WHERE user_id = :user_id AND week BETWEEN :start_date AND :end_date",
            ['user_id' => $userId, 'start_date' => $startDate, 'end_date' => $endDate]
        );
        
        $monthlyActiveTime = $this->db->fetchColumn(
            "SELECT AVG(active_time) FROM monthly_activity_summary 
             WHERE user_id = :user_id AND month BETWEEN :start_date AND :end_date",
            ['user_id' => $userId, 'start_date' => $startDate, 'end_date' => $endDate]
        );
        
        $engagementScore = $this->db->fetchColumn(
            "SELECT AVG(engagement_score) FROM user_engagement_scores 
             WHERE user_id = :user_id AND calculated_at BETWEEN :start_date AND :end_date",
            ['user_id' => $userId, 'start_date' => $startDate, 'end_date' => $endDate]
        );
        
        return [
            'daily_active_time' => round($dailyActiveTime, 2),
            'weekly_active_time' => round($weeklyActiveTime, 2),
            'monthly_active_time' => round($monthlyActiveTime, 2),
            'engagement_score' => round($engagementScore, 2),
            'score' => $this->calculateEngagementPatternScore($dailyActiveTime, $weeklyActiveTime, $monthlyActiveTime, $engagementScore)
        ];
    }
    
    private function analyzeLearningBehavior(int $userId, string $startDate, string $endDate): array {
        $skillProgression = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM skill_progressions 
             WHERE user_id = :user_id AND progressed_at BETWEEN :start_date AND :end_date",
            ['user_id' => $userId, 'start_date' => $startDate, 'end_date' => $endDate]
        );
        
        $knowledgeGaps = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM knowledge_gaps 
             WHERE user_id = :user_id AND identified_at BETWEEN :start_date AND :end_date",
            ['user_id' => $userId, 'start_date' => $startDate, 'end_date' => $endDate]
        );
        
        $learningVelocity = $this->db->fetchColumn(
            "SELECT AVG(learning_velocity) FROM learning_metrics 
             WHERE user_id = :user_id AND calculated_at BETWEEN :start_date AND :end_date",
            ['user_id' => $userId, 'start_date' => $startDate, 'end_date' => $endDate]
        );
        
        $retentionRate = $this->db->fetchColumn(
            "SELECT AVG(retention_rate) FROM learning_metrics 
             WHERE user_id = :user_id AND calculated_at BETWEEN :start_date AND :end_date",
            ['user_id' => $userId, 'start_date' => $startDate, 'end_date' => $endDate]
        );
        
        return [
            'skill_progression' => $skillProgression,
            'knowledge_gaps' => $knowledgeGaps,
            'learning_velocity' => round($learningVelocity, 2),
            'retention_rate' => round($retentionRate, 2),
            'score' => $this->calculateLearningBehaviorScore($skillProgression, $knowledgeGaps, $learningVelocity, $retentionRate)
        ];
    }
    
    private function calculateContentConsumptionScore(int $views, int $timeSpent, float $scrollDepth, int $clickThroughRate): float {
        return ($views * 0.1) + ($timeSpent * 0.01) + ($scrollDepth * 0.5) + ($clickThroughRate * 0.2);
    }
    
    private function calculateContentCreationScore(int $postsCreated, int $threadsCreated, int $commentsCreated, float $qualityScore): float {
        return ($postsCreated * 5) + ($threadsCreated * 10) + ($commentsCreated * 2) + ($qualityScore * 0.1);
    }
    
    private function calculateSocialInteractionScore(int $likesGiven, int $likesReceived, int $follows, int $messagesSent): float {
        return ($likesGiven * 1) + ($likesReceived * 2) + ($follows * 3) + ($messagesSent * 2);
    }
    
    private function calculateNavigationScore(int $pageViews, float $sessionDuration, int $bounceRate, int $returnVisits): float {
        return ($pageViews * 0.1) + ($sessionDuration * 0.01) - ($bounceRate * 0.5) + ($returnVisits * 2);
    }
    
    private function calculateEngagementPatternScore(float $dailyActiveTime, float $weeklyActiveTime, float $monthlyActiveTime, float $engagementScore): float {
        return ($dailyActiveTime * 0.1) + ($weeklyActiveTime * 0.05) + ($monthlyActiveTime * 0.02) + ($engagementScore * 0.01);
    }
    
    private function calculateLearningBehaviorScore(int $skillProgression, int $knowledgeGaps, float $learningVelocity, float $retentionRate): float {
        return ($skillProgression * 5) - ($knowledgeGaps * 2) + ($learningVelocity * 0.1) + ($retentionRate * 0.01);
    }
    
    private function calculateContentQualityScore(int $userId, string $startDate, string $endDate): float {
        $posts = $this->db->fetchAll(
            "SELECT p.id, p.content, p.created_at,
                    COUNT(l.id) as likes,
                    COUNT(c.id) as comments,
                    COUNT(h.id) as helpful_marks
             FROM posts p
             LEFT JOIN likes l ON p.id = l.post_id
             LEFT JOIN comments c ON p.id = c.post_id
             LEFT JOIN helpful_marks h ON p.id = h.post_id
             WHERE p.user_id = :user_id AND p.created_at BETWEEN :start_date AND :end_date
             GROUP BY p.id",
            ['user_id' => $userId, 'start_date' => $startDate, 'end_date' => $endDate]
        );
        
        if (empty($posts)) {
            return 0;
        }
        
        $totalScore = 0;
        foreach ($posts as $post) {
            $score = ($post['likes'] * 1) + ($post['comments'] * 2) + ($post['helpful_marks'] * 5);
            $totalScore += $score;
        }
        
        return round($totalScore / count($posts), 2);
    }
    
    private function calculateOverallBehaviorScore(array $behaviors): float {
        $totalScore = 0;
        $count = 0;
        
        foreach ($behaviors as $behavior) {
            if (isset($behavior['score'])) {
                $totalScore += $behavior['score'];
                $count++;
            }
        }
        
        return $count > 0 ? round($totalScore / $count, 2) : 0;
    }
    
    private function generateBehaviorInsights(array $behaviors): array {
        $insights = [];
        
        // Find strongest behavior
        $maxScore = 0;
        $strongestBehavior = null;
        foreach ($behaviors as $type => $behavior) {
            if (isset($behavior['score']) && $behavior['score'] > $maxScore) {
                $maxScore = $behavior['score'];
                $strongestBehavior = $type;
            }
        }
        
        if ($strongestBehavior) {
            $insights[] = [
                'type' => 'strength',
                'message' => "Your strongest behavior is {$this->behaviorTypes[$strongestBehavior]['name']}",
                'behavior' => $strongestBehavior,
                'score' => $maxScore
            ];
        }
        
        // Find weakest behavior
        $minScore = PHP_FLOAT_MAX;
        $weakestBehavior = null;
        foreach ($behaviors as $type => $behavior) {
            if (isset($behavior['score']) && $behavior['score'] < $minScore) {
                $minScore = $behavior['score'];
                $weakestBehavior = $type;
            }
        }
        
        if ($weakestBehavior) {
            $insights[] = [
                'type' => 'weakness',
                'message' => "Consider improving your {$this->behaviorTypes[$weakestBehavior]['name']}",
                'behavior' => $weakestBehavior,
                'score' => $minScore
            ];
        }
        
        return $insights;
    }
    
    private function generateBehaviorRecommendations(array $behaviors): array {
        $recommendations = [];
        
        foreach ($behaviors as $type => $behavior) {
            if (!isset($behavior['score'])) {
                continue;
            }
            
            $score = $behavior['score'];
            $behaviorName = $this->behaviorTypes[$type]['name'];
            
            if ($score < 10) {
                $recommendations[] = [
                    'type' => $type,
                    'priority' => 'high',
                    'message' => "Focus on improving your {$behaviorName}",
                    'suggestions' => $this->getBehaviorSuggestions($type)
                ];
            } elseif ($score < 50) {
                $recommendations[] = [
                    'type' => $type,
                    'priority' => 'medium',
                    'message' => "Your {$behaviorName} could be improved",
                    'suggestions' => $this->getBehaviorSuggestions($type)
                ];
            }
        }
        
        return $recommendations;
    }
    
    private function getBehaviorSuggestions(string $type): array {
        $suggestions = [
            'content_consumption' => [
                'Spend more time reading posts',
                'Engage with content by liking and commenting',
                'Explore different topics and categories'
            ],
            'content_creation' => [
                'Create more posts and threads',
                'Focus on quality over quantity',
                'Ask questions to encourage discussion'
            ],
            'social_interaction' => [
                'Like and comment on other users\' posts',
                'Follow users with similar interests',
                'Send messages to build relationships'
            ],
            'navigation_patterns' => [
                'Explore different sections of the forum',
                'Spend more time on each page',
                'Return regularly to stay engaged'
            ],
            'engagement_patterns' => [
                'Increase your daily active time',
                'Participate in discussions regularly',
                'Join community events and activities'
            ],
            'learning_behavior' => [
                'Focus on skill development',
                'Identify and address knowledge gaps',
                'Improve learning retention'
            ]
        ];
        
        return $suggestions[$type] ?? [];
    }
    
    public function getBehaviorTypes(): array {
        return $this->behaviorTypes;
    }
    
    public function getBehaviorStats(): array {
        return [
            'total_users_analyzed' => $this->db->fetchColumn("SELECT COUNT(DISTINCT user_id) FROM user_behavior_analysis"),
            'average_behavior_scores' => $this->getAverageBehaviorScores(),
            'behavior_trends' => $this->getBehaviorTrends(),
            'top_behaviors' => $this->getTopBehaviors()
        ];
    }
    
    private function getAverageBehaviorScores(): array {
        $scores = [];
        
        foreach ($this->behaviorTypes as $type => $behavior) {
            $avgScore = $this->db->fetchColumn(
                "SELECT AVG(JSON_EXTRACT(analysis_data, '$.behaviors.{$type}.score')) 
                 FROM user_behavior_analysis 
                 WHERE analysis_data IS NOT NULL"
            );
            
            $scores[$type] = round($avgScore, 2);
        }
        
        return $scores;
    }
    
    private function getBehaviorTrends(): array {
        return $this->db->fetchAll(
            "SELECT DATE(analyzed_at) as date, 
                    AVG(overall_score) as average_score,
                    COUNT(*) as users_analyzed
             FROM user_behavior_analysis 
             WHERE analyzed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             GROUP BY DATE(analyzed_at) 
             ORDER BY date DESC"
        );
    }
    
    private function getTopBehaviors(int $limit = 10): array {
        return $this->db->fetchAll(
            "SELECT u.id, u.username, u.avatar, uba.overall_score, uba.analyzed_at
             FROM user_behavior_analysis uba
             JOIN users u ON uba.user_id = u.id
             ORDER BY uba.overall_score DESC
             LIMIT :limit",
            ['limit' => $limit]
        );
    }
    
    public function saveBehaviorAnalysis(int $userId, array $analysis): bool {
        try {
            $this->db->insert('user_behavior_analysis', [
                'user_id' => $userId,
                'period' => $analysis['period'],
                'overall_score' => $analysis['overall_score'],
                'analysis_data' => json_encode($analysis),
                'analyzed_at' => date('Y-m-d H:i:s')
            ]);
            
            return true;
        } catch (\Exception $e) {
            error_log("Error saving behavior analysis: " . $e->getMessage());
            return false;
        }
    }
    
    public function getBehaviorHistory(int $userId, int $limit = 12): array {
        return $this->db->fetchAll(
            "SELECT * FROM user_behavior_analysis 
             WHERE user_id = :user_id 
             ORDER BY analyzed_at DESC 
             LIMIT :limit",
            ['user_id' => $userId, 'limit' => $limit]
        );
    }
    
    public function getBehaviorComparison(int $userId1, int $userId2, string $period = 'monthly'): array {
        $analysis1 = $this->analyzeUserBehavior($userId1, $period);
        $analysis2 = $this->analyzeUserBehavior($userId2, $period);
        
        return [
            'user1_score' => $analysis1['overall_score'],
            'user2_score' => $analysis2['overall_score'],
            'score_difference' => $analysis1['overall_score'] - $analysis2['overall_score'],
            'higher_score_user' => $analysis1['overall_score'] > $analysis2['overall_score'] ? $userId1 : $userId2,
            'period' => $period,
            'user1_behaviors' => $analysis1['behaviors'],
            'user2_behaviors' => $analysis2['behaviors']
        ];
    }
}