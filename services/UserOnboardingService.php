<?php
declare(strict_types=1);

namespace Services;

class UserOnboardingService {
    private Database $db;
    private array $onboardingSteps;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->onboardingSteps = $this->getOnboardingSteps();
    }
    
    private function getOnboardingSteps(): array {
        return [
            'welcome' => [
                'name' => 'Welcome',
                'description' => 'Welcome to the forum',
                'order' => 1,
                'required' => true,
                'points' => 10,
                'icon' => 'fas fa-handshake',
                'color' => '#4CAF50'
            ],
            'profile_setup' => [
                'name' => 'Profile Setup',
                'description' => 'Complete your profile',
                'order' => 2,
                'required' => true,
                'points' => 25,
                'icon' => 'fas fa-user-edit',
                'color' => '#2196F3'
            ],
            'avatar_upload' => [
                'name' => 'Avatar Upload',
                'description' => 'Upload your avatar',
                'order' => 3,
                'required' => false,
                'points' => 15,
                'icon' => 'fas fa-image',
                'color' => '#FF9800'
            ],
            'first_post' => [
                'name' => 'First Post',
                'description' => 'Create your first post',
                'order' => 4,
                'required' => true,
                'points' => 50,
                'icon' => 'fas fa-edit',
                'color' => '#9C27B0'
            ],
            'first_thread' => [
                'name' => 'First Thread',
                'description' => 'Create your first thread',
                'order' => 5,
                'required' => false,
                'points' => 100,
                'icon' => 'fas fa-comments',
                'color' => '#E91E63'
            ],
            'first_like' => [
                'name' => 'First Like',
                'description' => 'Give your first like',
                'order' => 6,
                'required' => false,
                'points' => 5,
                'icon' => 'fas fa-thumbs-up',
                'color' => '#4CAF50'
            ],
            'first_follow' => [
                'name' => 'First Follow',
                'description' => 'Follow another user',
                'order' => 7,
                'required' => false,
                'points' => 10,
                'icon' => 'fas fa-user-plus',
                'color' => '#00BCD4'
            ],
            'explore_forum' => [
                'name' => 'Explore Forum',
                'description' => 'Explore different sections',
                'order' => 8,
                'required' => false,
                'points' => 20,
                'icon' => 'fas fa-compass',
                'color' => '#795548'
            ],
            'join_discussion' => [
                'name' => 'Join Discussion',
                'description' => 'Participate in a discussion',
                'order' => 9,
                'required' => false,
                'points' => 30,
                'icon' => 'fas fa-comments',
                'color' => '#607D8B'
            ],
            'complete_tutorial' => [
                'name' => 'Complete Tutorial',
                'description' => 'Complete the forum tutorial',
                'order' => 10,
                'required' => false,
                'points' => 75,
                'icon' => 'fas fa-graduation-cap',
                'color' => '#FFD700'
            ]
        ];
    }
    
    public function startOnboarding(int $userId): bool {
        try {
            $this->db->beginTransaction();
            
            // Check if user already has onboarding record
            $existingOnboarding = $this->db->fetch(
                "SELECT * FROM user_onboarding WHERE user_id = :user_id",
                ['user_id' => $userId]
            );
            
            if ($existingOnboarding) {
                $this->db->rollback();
                return false;
            }
            
            // Create onboarding record
            $this->db->insert('user_onboarding', [
                'user_id' => $userId,
                'current_step' => 'welcome',
                'completed_steps' => json_encode([]),
                'started_at' => date('Y-m-d H:i:s'),
                'is_completed' => false,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            // Award welcome points
            $this->awardOnboardingPoints($userId, 'welcome');
            
            $this->db->commit();
            
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            error_log("Error starting onboarding: " . $e->getMessage());
            return false;
        }
    }
    
    public function completeOnboardingStep(int $userId, string $step): bool {
        if (!isset($this->onboardingSteps[$step])) {
            return false;
        }
        
        try {
            $this->db->beginTransaction();
            
            // Get current onboarding status
            $onboarding = $this->db->fetch(
                "SELECT * FROM user_onboarding WHERE user_id = :user_id",
                ['user_id' => $userId]
            );
            
            if (!$onboarding) {
                $this->db->rollback();
                return false;
            }
            
            $completedSteps = json_decode($onboarding['completed_steps'], true);
            
            // Check if step is already completed
            if (in_array($step, $completedSteps)) {
                $this->db->rollback();
                return false;
            }
            
            // Add step to completed steps
            $completedSteps[] = $step;
            
            // Award points for this step
            $this->awardOnboardingPoints($userId, $step);
            
            // Update onboarding record
            $this->db->update(
                'user_onboarding',
                [
                    'completed_steps' => json_encode($completedSteps),
                    'updated_at' => date('Y-m-d H:i:s')
                ],
                'user_id = :user_id',
                ['user_id' => $userId]
            );
            
            // Check if onboarding is complete
            $this->checkOnboardingCompletion($userId);
            
            $this->db->commit();
            
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            error_log("Error completing onboarding step: " . $e->getMessage());
            return false;
        }
    }
    
    private function awardOnboardingPoints(int $userId, string $step): void {
        $stepData = $this->onboardingSteps[$step];
        $points = $stepData['points'];
        
        if ($points > 0) {
            $this->db->query(
                "UPDATE users SET points = points + :points WHERE id = :user_id",
                ['points' => $points, 'user_id' => $userId]
            );
        }
    }
    
    private function checkOnboardingCompletion(int $userId): void {
        $onboarding = $this->db->fetch(
            "SELECT * FROM user_onboarding WHERE user_id = :user_id",
            ['user_id' => $userId]
        );
        
        if (!$onboarding) {
            return;
        }
        
        $completedSteps = json_decode($onboarding['completed_steps'], true);
        $requiredSteps = array_keys(array_filter($this->onboardingSteps, function($step) {
            return $step['required'];
        }));
        
        $completedRequiredSteps = array_intersect($completedSteps, $requiredSteps);
        
        if (count($completedRequiredSteps) === count($requiredSteps)) {
            // Mark onboarding as completed
            $this->db->update(
                'user_onboarding',
                [
                    'is_completed' => true,
                    'completed_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ],
                'user_id = :user_id',
                ['user_id' => $userId]
            );
            
            // Award completion bonus
            $this->awardCompletionBonus($userId);
        }
    }
    
    private function awardCompletionBonus(int $userId): void {
        $bonusPoints = 200;
        
        $this->db->query(
            "UPDATE users SET points = points + :points WHERE id = :user_id",
            ['points' => $bonusPoints, 'user_id' => $userId]
        );
        
        // Award completion badge
        $this->db->insert('user_badges', [
            'user_id' => $userId,
            'badge_id' => 'onboarding_complete',
            'earned_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    public function getUserOnboardingStatus(int $userId): array {
        $onboarding = $this->db->fetch(
            "SELECT * FROM user_onboarding WHERE user_id = :user_id",
            ['user_id' => $userId]
        );
        
        if (!$onboarding) {
            return [
                'is_started' => false,
                'is_completed' => false,
                'current_step' => null,
                'completed_steps' => [],
                'progress_percentage' => 0,
                'next_steps' => []
            ];
        }
        
        $completedSteps = json_decode($onboarding['completed_steps'], true);
        $requiredSteps = array_keys(array_filter($this->onboardingSteps, function($step) {
            return $step['required'];
        }));
        
        $completedRequiredSteps = array_intersect($completedSteps, $requiredSteps);
        $progressPercentage = count($requiredSteps) > 0 ? 
            round((count($completedRequiredSteps) / count($requiredSteps)) * 100, 2) : 0;
        
        $nextSteps = $this->getNextSteps($completedSteps);
        
        return [
            'is_started' => true,
            'is_completed' => (bool) $onboarding['is_completed'],
            'current_step' => $onboarding['current_step'],
            'completed_steps' => $completedSteps,
            'progress_percentage' => $progressPercentage,
            'next_steps' => $nextSteps,
            'started_at' => $onboarding['started_at'],
            'completed_at' => $onboarding['completed_at']
        ];
    }
    
    private function getNextSteps(array $completedSteps): array {
        $nextSteps = [];
        
        foreach ($this->onboardingSteps as $step => $stepData) {
            if (!in_array($step, $completedSteps)) {
                $nextSteps[] = [
                    'step' => $step,
                    'name' => $stepData['name'],
                    'description' => $stepData['description'],
                    'order' => $stepData['order'],
                    'required' => $stepData['required'],
                    'points' => $stepData['points'],
                    'icon' => $stepData['icon'],
                    'color' => $stepData['color']
                ];
            }
        }
        
        // Sort by order
        usort($nextSteps, function($a, $b) {
            return $a['order'] <=> $b['order'];
        });
        
        return $nextSteps;
    }
    
    public function getOnboardingProgress(int $userId): array {
        $status = $this->getUserOnboardingStatus($userId);
        
        if (!$status['is_started']) {
            return [];
        }
        
        $progress = [];
        foreach ($this->onboardingSteps as $step => $stepData) {
            $progress[] = [
                'step' => $step,
                'name' => $stepData['name'],
                'description' => $stepData['description'],
                'order' => $stepData['order'],
                'required' => $stepData['required'],
                'points' => $stepData['points'],
                'icon' => $stepData['icon'],
                'color' => $stepData['color'],
                'completed' => in_array($step, $status['completed_steps']),
                'is_current' => $step === $status['current_step']
            ];
        }
        
        // Sort by order
        usort($progress, function($a, $b) {
            return $a['order'] <=> $b['order'];
        });
        
        return $progress;
    }
    
    public function getOnboardingStats(): array {
        return [
            'total_users' => $this->db->fetchColumn("SELECT COUNT(*) FROM users"),
            'onboarding_started' => $this->db->fetchColumn("SELECT COUNT(*) FROM user_onboarding"),
            'onboarding_completed' => $this->db->fetchColumn("SELECT COUNT(*) FROM user_onboarding WHERE is_completed = 1"),
            'completion_rate' => $this->getCompletionRate(),
            'average_completion_time' => $this->getAverageCompletionTime(),
            'steps_completion' => $this->getStepsCompletionStats(),
            'onboarding_trends' => $this->getOnboardingTrends()
        ];
    }
    
    private function getCompletionRate(): float {
        $total = $this->db->fetchColumn("SELECT COUNT(*) FROM user_onboarding");
        $completed = $this->db->fetchColumn("SELECT COUNT(*) FROM user_onboarding WHERE is_completed = 1");
        
        return $total > 0 ? round(($completed / $total) * 100, 2) : 0;
    }
    
    private function getAverageCompletionTime(): float {
        $avgTime = $this->db->fetchColumn(
            "SELECT AVG(TIMESTAMPDIFF(HOUR, started_at, completed_at)) 
             FROM user_onboarding 
             WHERE is_completed = 1 AND completed_at IS NOT NULL"
        );
        
        return round($avgTime, 2);
    }
    
    private function getStepsCompletionStats(): array {
        $stats = [];
        
        foreach ($this->onboardingSteps as $step => $stepData) {
            $completed = $this->db->fetchColumn(
                "SELECT COUNT(*) FROM user_onboarding 
                 WHERE JSON_CONTAINS(completed_steps, :step)",
                ['step' => json_encode($step)]
            );
            
            $total = $this->db->fetchColumn("SELECT COUNT(*) FROM user_onboarding");
            
            $stats[$step] = [
                'name' => $stepData['name'],
                'completed' => $completed,
                'total' => $total,
                'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 2) : 0
            ];
        }
        
        return $stats;
    }
    
    private function getOnboardingTrends(): array {
        return $this->db->fetchAll(
            "SELECT DATE(started_at) as date, 
                    COUNT(*) as started,
                    COUNT(CASE WHEN is_completed = 1 THEN 1 END) as completed
             FROM user_onboarding 
             WHERE started_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             GROUP BY DATE(started_at) 
             ORDER BY date DESC"
        );
    }
    
    public function getOnboardingSteps(): array {
        return $this->onboardingSteps;
    }
    
    public function updateOnboardingStep(int $userId, string $step): bool {
        try {
            $this->db->update(
                'user_onboarding',
                ['current_step' => $step],
                'user_id = :user_id',
                ['user_id' => $userId]
            );
            
            return true;
        } catch (\Exception $e) {
            error_log("Error updating onboarding step: " . $e->getMessage());
            return false;
        }
    }
    
    public function resetOnboarding(int $userId): bool {
        try {
            $this->db->delete('user_onboarding', 'user_id = :user_id', ['user_id' => $userId]);
            
            return true;
        } catch (\Exception $e) {
            error_log("Error resetting onboarding: " . $e->getMessage());
            return false;
        }
    }
    
    public function getOnboardingLeaderboard(int $limit = 10): array {
        return $this->db->fetchAll(
            "SELECT u.id, u.username, u.avatar, 
                    uo.completed_steps,
                    uo.is_completed,
                    uo.completed_at,
                    TIMESTAMPDIFF(HOUR, uo.started_at, uo.completed_at) as completion_time
             FROM user_onboarding uo
             JOIN users u ON uo.user_id = u.id
             ORDER BY uo.completed_at DESC, uo.started_at ASC
             LIMIT :limit",
            ['limit' => $limit]
        );
    }
    
    public function getOnboardingAnalytics(int $userId): array {
        $onboarding = $this->db->fetch(
            "SELECT * FROM user_onboarding WHERE user_id = :user_id",
            ['user_id' => $userId]
        );
        
        if (!$onboarding) {
            return [];
        }
        
        $completedSteps = json_decode($onboarding['completed_steps'], true);
        $totalSteps = count($this->onboardingSteps);
        $requiredSteps = count(array_filter($this->onboardingSteps, function($step) {
            return $step['required'];
        }));
        
        $completedRequiredSteps = array_intersect($completedSteps, array_keys(array_filter($this->onboardingSteps, function($step) {
            return $step['required'];
        })));
        
        $completionTime = null;
        if ($onboarding['completed_at']) {
            $completionTime = strtotime($onboarding['completed_at']) - strtotime($onboarding['started_at']);
        }
        
        return [
            'total_steps' => $totalSteps,
            'required_steps' => $requiredSteps,
            'completed_steps' => count($completedSteps),
            'completed_required_steps' => count($completedRequiredSteps),
            'progress_percentage' => $requiredSteps > 0 ? round((count($completedRequiredSteps) / $requiredSteps) * 100, 2) : 0,
            'is_completed' => (bool) $onboarding['is_completed'],
            'completion_time' => $completionTime,
            'started_at' => $onboarding['started_at'],
            'completed_at' => $onboarding['completed_at']
        ];
    }
    
    public function getOnboardingInsights(int $userId): array {
        $analytics = $this->getOnboardingAnalytics($userId);
        $insights = [];
        
        if ($analytics['is_completed']) {
            $insights[] = [
                'type' => 'completion',
                'message' => 'Congratulations! You have completed the onboarding process.',
                'icon' => 'fas fa-check-circle',
                'color' => '#4CAF50'
            ];
        } else {
            $progress = $analytics['progress_percentage'];
            
            if ($progress >= 80) {
                $insights[] = [
                    'type' => 'almost_complete',
                    'message' => 'You are almost done with onboarding! Just a few more steps.',
                    'icon' => 'fas fa-trophy',
                    'color' => '#FFD700'
                ];
            } elseif ($progress >= 50) {
                $insights[] = [
                    'type' => 'good_progress',
                    'message' => 'You are making good progress through the onboarding process.',
                    'icon' => 'fas fa-thumbs-up',
                    'color' => '#2196F3'
                ];
            } else {
                $insights[] = [
                    'type' => 'get_started',
                    'message' => 'Complete the onboarding steps to get the most out of the forum.',
                    'icon' => 'fas fa-play-circle',
                    'color' => '#FF9800'
                ];
            }
        }
        
        return $insights;
    }
}