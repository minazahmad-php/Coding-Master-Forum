<?php
declare(strict_types=1);

namespace Services;

class TutorialService {
    private Database $db;
    private array $tutorialTypes;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->tutorialTypes = $this->getTutorialTypes();
    }
    
    private function getTutorialTypes(): array {
        return [
            'getting_started' => [
                'name' => 'Getting Started',
                'description' => 'Basic forum navigation and features',
                'icon' => 'fas fa-play-circle',
                'color' => '#4CAF50',
                'duration' => 10,
                'difficulty' => 'beginner'
            ],
            'posting' => [
                'name' => 'Posting Guide',
                'description' => 'How to create posts and threads',
                'icon' => 'fas fa-edit',
                'color' => '#2196F3',
                'duration' => 15,
                'difficulty' => 'beginner'
            ],
            'social_features' => [
                'name' => 'Social Features',
                'description' => 'Likes, follows, and social interactions',
                'icon' => 'fas fa-users',
                'color' => '#9C27B0',
                'duration' => 12,
                'difficulty' => 'beginner'
            ],
            'advanced_features' => [
                'name' => 'Advanced Features',
                'description' => 'Advanced forum features and tools',
                'icon' => 'fas fa-cogs',
                'color' => '#FF9800',
                'duration' => 20,
                'difficulty' => 'intermediate'
            ],
            'moderation' => [
                'name' => 'Moderation Guide',
                'description' => 'How to moderate content and users',
                'icon' => 'fas fa-shield-alt',
                'color' => '#E91E63',
                'duration' => 25,
                'difficulty' => 'advanced'
            ],
            'admin_tools' => [
                'name' => 'Admin Tools',
                'description' => 'Administrative tools and features',
                'icon' => 'fas fa-tools',
                'color' => '#795548',
                'duration' => 30,
                'difficulty' => 'expert'
            ]
        ];
    }
    
    public function createTutorial(array $tutorialData): bool {
        try {
            $this->db->insert('tutorials', [
                'name' => $tutorialData['name'],
                'description' => $tutorialData['description'],
                'type' => $tutorialData['type'],
                'content' => json_encode($tutorialData['content']),
                'steps' => json_encode($tutorialData['steps']),
                'duration' => $tutorialData['duration'] ?? 10,
                'difficulty' => $tutorialData['difficulty'] ?? 'beginner',
                'is_active' => $tutorialData['is_active'] ?? true,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            return true;
        } catch (\Exception $e) {
            error_log("Error creating tutorial: " . $e->getMessage());
            return false;
        }
    }
    
    public function getTutorial(int $tutorialId): ?array {
        $tutorial = $this->db->fetch(
            "SELECT * FROM tutorials WHERE id = :tutorial_id",
            ['tutorial_id' => $tutorialId]
        );
        
        if (!$tutorial) {
            return null;
        }
        
        $tutorial['content'] = json_decode($tutorial['content'], true);
        $tutorial['steps'] = json_decode($tutorial['steps'], true);
        
        return $tutorial;
    }
    
    public function getTutorialsByType(string $type): array {
        return $this->db->fetchAll(
            "SELECT * FROM tutorials 
             WHERE type = :type AND is_active = 1 
             ORDER BY created_at ASC",
            ['type' => $type]
        );
    }
    
    public function getTutorialsByDifficulty(string $difficulty): array {
        return $this->db->fetchAll(
            "SELECT * FROM tutorials 
             WHERE difficulty = :difficulty AND is_active = 1 
             ORDER BY created_at ASC",
            ['difficulty' => $difficulty]
        );
    }
    
    public function getAvailableTutorials(int $userId): array {
        $completedTutorials = $this->getCompletedTutorials($userId);
        $completedIds = array_column($completedTutorials, 'tutorial_id');
        
        $tutorials = $this->db->fetchAll(
            "SELECT * FROM tutorials 
             WHERE is_active = 1 
             ORDER BY difficulty, created_at ASC"
        );
        
        $availableTutorials = [];
        foreach ($tutorials as $tutorial) {
            if (!in_array($tutorial['id'], $completedIds)) {
                $tutorial['content'] = json_decode($tutorial['content'], true);
                $tutorial['steps'] = json_decode($tutorial['steps'], true);
                $availableTutorials[] = $tutorial;
            }
        }
        
        return $availableTutorials;
    }
    
    public function startTutorial(int $userId, int $tutorialId): bool {
        $tutorial = $this->getTutorial($tutorialId);
        if (!$tutorial || !$tutorial['is_active']) {
            return false;
        }
        
        // Check if user already started this tutorial
        $existingProgress = $this->db->fetch(
            "SELECT * FROM user_tutorial_progress 
             WHERE user_id = :user_id AND tutorial_id = :tutorial_id",
            ['user_id' => $userId, 'tutorial_id' => $tutorialId]
        );
        
        if ($existingProgress) {
            return false;
        }
        
        try {
            $this->db->insert('user_tutorial_progress', [
                'user_id' => $userId,
                'tutorial_id' => $tutorialId,
                'current_step' => 0,
                'completed_steps' => json_encode([]),
                'started_at' => date('Y-m-d H:i:s'),
                'is_completed' => false,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            return true;
        } catch (\Exception $e) {
            error_log("Error starting tutorial: " . $e->getMessage());
            return false;
        }
    }
    
    public function completeTutorialStep(int $userId, int $tutorialId, int $stepIndex): bool {
        $tutorial = $this->getTutorial($tutorialId);
        if (!$tutorial) {
            return false;
        }
        
        $steps = $tutorial['steps'];
        if (!isset($steps[$stepIndex])) {
            return false;
        }
        
        try {
            $this->db->beginTransaction();
            
            // Get current progress
            $progress = $this->db->fetch(
                "SELECT * FROM user_tutorial_progress 
                 WHERE user_id = :user_id AND tutorial_id = :tutorial_id",
                ['user_id' => $userId, 'tutorial_id' => $tutorialId]
            );
            
            if (!$progress) {
                $this->db->rollback();
                return false;
            }
            
            $completedSteps = json_decode($progress['completed_steps'], true);
            
            // Check if step is already completed
            if (in_array($stepIndex, $completedSteps)) {
                $this->db->rollback();
                return false;
            }
            
            // Add step to completed steps
            $completedSteps[] = $stepIndex;
            
            // Update progress
            $this->db->update(
                'user_tutorial_progress',
                [
                    'current_step' => $stepIndex + 1,
                    'completed_steps' => json_encode($completedSteps),
                    'updated_at' => date('Y-m-d H:i:s')
                ],
                'user_id = :user_id AND tutorial_id = :tutorial_id',
                ['user_id' => $userId, 'tutorial_id' => $tutorialId]
            );
            
            // Check if tutorial is completed
            if (count($completedSteps) === count($steps)) {
                $this->completeTutorial($userId, $tutorialId);
            }
            
            $this->db->commit();
            
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            error_log("Error completing tutorial step: " . $e->getMessage());
            return false;
        }
    }
    
    private function completeTutorial(int $userId, int $tutorialId): void {
        try {
            // Mark tutorial as completed
            $this->db->update(
                'user_tutorial_progress',
                [
                    'is_completed' => true,
                    'completed_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ],
                'user_id = :user_id AND tutorial_id = :tutorial_id',
                ['user_id' => $userId, 'tutorial_id' => $tutorialId]
            );
            
            // Award completion points
            $tutorial = $this->getTutorial($tutorialId);
            $points = $this->calculateTutorialPoints($tutorial);
            
            if ($points > 0) {
                $this->db->query(
                    "UPDATE users SET points = points + :points WHERE id = :user_id",
                    ['points' => $points, 'user_id' => $userId]
                );
            }
            
            // Award completion badge
            $this->db->insert('user_badges', [
                'user_id' => $userId,
                'badge_id' => 'tutorial_complete_' . $tutorial['type'],
                'earned_at' => date('Y-m-d H:i:s')
            ]);
            
        } catch (\Exception $e) {
            error_log("Error completing tutorial: " . $e->getMessage());
        }
    }
    
    private function calculateTutorialPoints(array $tutorial): int {
        $basePoints = 50;
        $difficultyMultiplier = [
            'beginner' => 1,
            'intermediate' => 1.5,
            'advanced' => 2,
            'expert' => 3
        ];
        
        $multiplier = $difficultyMultiplier[$tutorial['difficulty']] ?? 1;
        $durationMultiplier = $tutorial['duration'] / 10; // 10 minutes = 1x
        
        return (int) ($basePoints * $multiplier * $durationMultiplier);
    }
    
    public function getUserTutorialProgress(int $userId, int $tutorialId): array {
        $progress = $this->db->fetch(
            "SELECT * FROM user_tutorial_progress 
             WHERE user_id = :user_id AND tutorial_id = :tutorial_id",
            ['user_id' => $userId, 'tutorial_id' => $tutorialId]
        );
        
        if (!$progress) {
            return [
                'is_started' => false,
                'is_completed' => false,
                'current_step' => 0,
                'completed_steps' => [],
                'progress_percentage' => 0
            ];
        }
        
        $tutorial = $this->getTutorial($tutorialId);
        $totalSteps = count($tutorial['steps']);
        $completedSteps = json_decode($progress['completed_steps'], true);
        $progressPercentage = $totalSteps > 0 ? 
            round((count($completedSteps) / $totalSteps) * 100, 2) : 0;
        
        return [
            'is_started' => true,
            'is_completed' => (bool) $progress['is_completed'],
            'current_step' => $progress['current_step'],
            'completed_steps' => $completedSteps,
            'progress_percentage' => $progressPercentage,
            'started_at' => $progress['started_at'],
            'completed_at' => $progress['completed_at']
        ];
    }
    
    public function getUserTutorials(int $userId): array {
        return $this->db->fetchAll(
            "SELECT t.*, utp.current_step, utp.completed_steps, utp.is_completed, 
                    utp.started_at, utp.completed_at
             FROM user_tutorial_progress utp
             JOIN tutorials t ON utp.tutorial_id = t.id
             WHERE utp.user_id = :user_id
             ORDER BY utp.started_at DESC",
            ['user_id' => $userId]
        );
    }
    
    public function getCompletedTutorials(int $userId): array {
        return $this->db->fetchAll(
            "SELECT t.*, utp.completed_at
             FROM user_tutorial_progress utp
             JOIN tutorials t ON utp.tutorial_id = t.id
             WHERE utp.user_id = :user_id AND utp.is_completed = 1
             ORDER BY utp.completed_at DESC",
            ['user_id' => $userId]
        );
    }
    
    public function getTutorialStats(): array {
        return [
            'total_tutorials' => $this->db->fetchColumn("SELECT COUNT(*) FROM tutorials"),
            'active_tutorials' => $this->db->fetchColumn("SELECT COUNT(*) FROM tutorials WHERE is_active = 1"),
            'total_completions' => $this->db->fetchColumn("SELECT COUNT(*) FROM user_tutorial_progress WHERE is_completed = 1"),
            'completion_rate' => $this->getCompletionRate(),
            'tutorials_by_type' => $this->getTutorialsByTypeStats(),
            'tutorials_by_difficulty' => $this->getTutorialsByDifficultyStats(),
            'top_tutorials' => $this->getTopTutorials()
        ];
    }
    
    private function getCompletionRate(): float {
        $total = $this->db->fetchColumn("SELECT COUNT(*) FROM user_tutorial_progress");
        $completed = $this->db->fetchColumn("SELECT COUNT(*) FROM user_tutorial_progress WHERE is_completed = 1");
        
        return $total > 0 ? round(($completed / $total) * 100, 2) : 0;
    }
    
    private function getTutorialsByTypeStats(): array {
        return $this->db->fetchAll(
            "SELECT type, COUNT(*) as count, COUNT(utp.id) as completions
             FROM tutorials t
             LEFT JOIN user_tutorial_progress utp ON t.id = utp.tutorial_id AND utp.is_completed = 1
             GROUP BY type
             ORDER BY count DESC"
        );
    }
    
    private function getTutorialsByDifficultyStats(): array {
        return $this->db->fetchAll(
            "SELECT difficulty, COUNT(*) as count, COUNT(utp.id) as completions
             FROM tutorials t
             LEFT JOIN user_tutorial_progress utp ON t.id = utp.tutorial_id AND utp.is_completed = 1
             GROUP BY difficulty
             ORDER BY count DESC"
        );
    }
    
    private function getTopTutorials(int $limit = 10): array {
        return $this->db->fetchAll(
            "SELECT t.*, COUNT(utp.id) as completions
             FROM tutorials t
             LEFT JOIN user_tutorial_progress utp ON t.id = utp.tutorial_id AND utp.is_completed = 1
             GROUP BY t.id
             ORDER BY completions DESC
             LIMIT :limit",
            ['limit' => $limit]
        );
    }
    
    public function getTutorialTypes(): array {
        return $this->tutorialTypes;
    }
    
    public function updateTutorial(int $tutorialId, array $data): bool {
        try {
            $data['updated_at'] = date('Y-m-d H:i:s');
            
            $this->db->update(
                'tutorials',
                $data,
                'id = :tutorial_id',
                ['tutorial_id' => $tutorialId]
            );
            
            return true;
        } catch (\Exception $e) {
            error_log("Error updating tutorial: " . $e->getMessage());
            return false;
        }
    }
    
    public function deleteTutorial(int $tutorialId): bool {
        try {
            $this->db->beginTransaction();
            
            // Delete user progress
            $this->db->delete('user_tutorial_progress', 'tutorial_id = :tutorial_id', ['tutorial_id' => $tutorialId]);
            
            // Delete the tutorial
            $this->db->delete('tutorials', 'id = :tutorial_id', ['tutorial_id' => $tutorialId]);
            
            $this->db->commit();
            
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            error_log("Error deleting tutorial: " . $e->getMessage());
            return false;
        }
    }
    
    public function getTutorialLeaderboard(int $tutorialId, int $limit = 10): array {
        return $this->db->fetchAll(
            "SELECT u.id, u.username, u.avatar, utp.completed_at, utp.started_at
             FROM user_tutorial_progress utp
             JOIN users u ON utp.user_id = u.id
             WHERE utp.tutorial_id = :tutorial_id AND utp.is_completed = 1
             ORDER BY utp.completed_at ASC
             LIMIT :limit",
            ['tutorial_id' => $tutorialId, 'limit' => $limit]
        );
    }
    
    public function getTutorialHistory(int $userId, int $limit = 50): array {
        return $this->db->fetchAll(
            "SELECT t.*, utp.completed_at, utp.started_at, utp.is_completed
             FROM user_tutorial_progress utp
             JOIN tutorials t ON utp.tutorial_id = t.id
             WHERE utp.user_id = :user_id
             ORDER BY utp.started_at DESC
             LIMIT :limit",
            ['user_id' => $userId, 'limit' => $limit]
        );
    }
    
    public function getTutorialAnalytics(int $userId): array {
        $tutorials = $this->getUserTutorials($userId);
        $completedTutorials = $this->getCompletedTutorials($userId);
        
        $totalTutorials = count($tutorials);
        $completedCount = count($completedTutorials);
        $completionRate = $totalTutorials > 0 ? round(($completedCount / $totalTutorials) * 100, 2) : 0;
        
        $tutorialsByType = [];
        foreach ($completedTutorials as $tutorial) {
            $type = $tutorial['type'];
            if (!isset($tutorialsByType[$type])) {
                $tutorialsByType[$type] = 0;
            }
            $tutorialsByType[$type]++;
        }
        
        $tutorialsByDifficulty = [];
        foreach ($completedTutorials as $tutorial) {
            $difficulty = $tutorial['difficulty'];
            if (!isset($tutorialsByDifficulty[$difficulty])) {
                $tutorialsByDifficulty[$difficulty] = 0;
            }
            $tutorialsByDifficulty[$difficulty]++;
        }
        
        return [
            'total_tutorials' => $totalTutorials,
            'completed_tutorials' => $completedCount,
            'completion_rate' => $completionRate,
            'tutorials_by_type' => $tutorialsByType,
            'tutorials_by_difficulty' => $tutorialsByDifficulty,
            'tutorials' => $tutorials
        ];
    }
    
    public function getTutorialInsights(int $userId): array {
        $analytics = $this->getTutorialAnalytics($userId);
        $insights = [];
        
        if ($analytics['completion_rate'] >= 80) {
            $insights[] = [
                'type' => 'high_completion',
                'message' => 'You have completed most of the available tutorials!',
                'icon' => 'fas fa-trophy',
                'color' => '#FFD700'
            ];
        } elseif ($analytics['completion_rate'] >= 50) {
            $insights[] = [
                'type' => 'good_progress',
                'message' => 'You are making good progress through the tutorials.',
                'icon' => 'fas fa-thumbs-up',
                'color' => '#4CAF50'
            ];
        } else {
            $insights[] = [
                'type' => 'get_started',
                'message' => 'Complete more tutorials to learn about forum features.',
                'icon' => 'fas fa-play-circle',
                'color' => '#2196F3'
            ];
        }
        
        return $insights;
    }
    
    public function getRecommendedTutorials(int $userId): array {
        $completedTutorials = $this->getCompletedTutorials($userId);
        $completedTypes = array_column($completedTutorials, 'type');
        
        $recommendations = [];
        
        // Recommend next difficulty level
        $difficultyOrder = ['beginner', 'intermediate', 'advanced', 'expert'];
        $currentDifficulty = 'beginner';
        
        foreach ($completedTutorials as $tutorial) {
            $difficulty = $tutorial['difficulty'];
            $currentIndex = array_search($currentDifficulty, $difficultyOrder);
            $tutorialIndex = array_search($difficulty, $difficultyOrder);
            
            if ($tutorialIndex > $currentIndex) {
                $currentDifficulty = $difficulty;
            }
        }
        
        $nextDifficultyIndex = array_search($currentDifficulty, $difficultyOrder) + 1;
        if ($nextDifficultyIndex < count($difficultyOrder)) {
            $nextDifficulty = $difficultyOrder[$nextDifficultyIndex];
            
            $nextTutorials = $this->getTutorialsByDifficulty($nextDifficulty);
            foreach ($nextTutorials as $tutorial) {
                if (!in_array($tutorial['id'], array_column($completedTutorials, 'id'))) {
                    $recommendations[] = [
                        'tutorial' => $tutorial,
                        'reason' => 'Next difficulty level',
                        'priority' => 'high'
                    ];
                }
            }
        }
        
        return $recommendations;
    }
}