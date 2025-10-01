<?php
declare(strict_types=1);

namespace Services;

class ChallengesService {
    private Database $db;
    private array $challengeTypes;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->challengeTypes = $this->getChallengeTypes();
    }
    
    private function getChallengeTypes(): array {
        return [
            'daily' => [
                'name' => 'Daily Challenge',
                'description' => 'Complete daily tasks',
                'duration' => 1,
                'reset_time' => '00:00:00',
                'icon' => 'fas fa-calendar-day',
                'color' => '#4CAF50'
            ],
            'weekly' => [
                'name' => 'Weekly Challenge',
                'description' => 'Complete weekly tasks',
                'duration' => 7,
                'reset_time' => '00:00:00',
                'icon' => 'fas fa-calendar-week',
                'color' => '#2196F3'
            ],
            'monthly' => [
                'name' => 'Monthly Challenge',
                'description' => 'Complete monthly tasks',
                'duration' => 30,
                'reset_time' => '00:00:00',
                'icon' => 'fas fa-calendar-alt',
                'color' => '#FF9800'
            ],
            'seasonal' => [
                'name' => 'Seasonal Challenge',
                'description' => 'Complete seasonal tasks',
                'duration' => 90,
                'reset_time' => '00:00:00',
                'icon' => 'fas fa-leaf',
                'color' => '#9C27B0'
            ],
            'special' => [
                'name' => 'Special Challenge',
                'description' => 'Complete special event tasks',
                'duration' => 0,
                'reset_time' => null,
                'icon' => 'fas fa-star',
                'color' => '#E91E63'
            ],
            'achievement' => [
                'name' => 'Achievement Challenge',
                'description' => 'Complete achievement-based tasks',
                'duration' => 0,
                'reset_time' => null,
                'icon' => 'fas fa-trophy',
                'color' => '#FFD700'
            ]
        ];
    }
    
    public function createChallenge(array $challengeData): bool {
        try {
            $this->db->insert('challenges', [
                'name' => $challengeData['name'],
                'description' => $challengeData['description'],
                'type' => $challengeData['type'],
                'category' => $challengeData['category'] ?? 'general',
                'difficulty' => $challengeData['difficulty'] ?? 'easy',
                'requirements' => json_encode($challengeData['requirements'] ?? []),
                'rewards' => json_encode($challengeData['rewards'] ?? []),
                'start_date' => $challengeData['start_date'] ?? date('Y-m-d H:i:s'),
                'end_date' => $challengeData['end_date'] ?? null,
                'is_active' => $challengeData['is_active'] ?? true,
                'is_repeatable' => $challengeData['is_repeatable'] ?? false,
                'max_completions' => $challengeData['max_completions'] ?? 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            return true;
        } catch (\Exception $e) {
            error_log("Error creating challenge: " . $e->getMessage());
            return false;
        }
    }
    
    public function getActiveChallenges(int $userId = null): array {
        $query = "SELECT * FROM challenges 
                  WHERE is_active = 1 
                  AND (start_date <= NOW() OR start_date IS NULL)
                  AND (end_date >= NOW() OR end_date IS NULL)
                  ORDER BY type, difficulty, name";
        
        $params = [];
        
        if ($userId) {
            $query = "SELECT c.*, 
                             CASE WHEN uc.id IS NOT NULL THEN 1 ELSE 0 END as is_completed,
                             uc.completed_at,
                             uc.progress
                      FROM challenges c
                      LEFT JOIN user_challenges uc ON c.id = uc.challenge_id AND uc.user_id = :user_id
                      WHERE c.is_active = 1 
                      AND (c.start_date <= NOW() OR c.start_date IS NULL)
                      AND (c.end_date >= NOW() OR c.end_date IS NULL)
                      ORDER BY c.type, c.difficulty, c.name";
            
            $params['user_id'] = $userId;
        }
        
        return $this->db->fetchAll($query, $params);
    }
    
    public function getChallengesByType(string $type): array {
        return $this->db->fetchAll(
            "SELECT * FROM challenges 
             WHERE type = :type AND is_active = 1 
             ORDER BY difficulty, name",
            ['type' => $type]
        );
    }
    
    public function getChallengesByCategory(string $category): array {
        return $this->db->fetchAll(
            "SELECT * FROM challenges 
             WHERE category = :category AND is_active = 1 
             ORDER BY difficulty, name",
            ['category' => $category]
        );
    }
    
    public function getChallengesByDifficulty(string $difficulty): array {
        return $this->db->fetchAll(
            "SELECT * FROM challenges 
             WHERE difficulty = :difficulty AND is_active = 1 
             ORDER BY type, name",
            ['difficulty' => $difficulty]
        );
    }
    
    public function getUserChallenges(int $userId): array {
        return $this->db->fetchAll(
            "SELECT c.*, uc.progress, uc.completed_at, uc.is_completed, uc.started_at
             FROM user_challenges uc
             JOIN challenges c ON uc.challenge_id = c.id
             WHERE uc.user_id = :user_id
             ORDER BY uc.started_at DESC",
            ['user_id' => $userId]
        );
    }
    
    public function startChallenge(int $userId, int $challengeId): bool {
        $challenge = $this->getChallenge($challengeId);
        if (!$challenge || !$challenge['is_active']) {
            return false;
        }
        
        // Check if user already has this challenge
        if ($this->hasUserChallenge($userId, $challengeId)) {
            return false;
        }
        
        // Check if challenge is available
        if (!$this->isChallengeAvailable($challenge)) {
            return false;
        }
        
        try {
            $this->db->insert('user_challenges', [
                'user_id' => $userId,
                'challenge_id' => $challengeId,
                'progress' => 0,
                'is_completed' => false,
                'started_at' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            return true;
        } catch (\Exception $e) {
            error_log("Error starting challenge: " . $e->getMessage());
            return false;
        }
    }
    
    public function updateChallengeProgress(int $userId, int $challengeId, int $progress): bool {
        $userChallenge = $this->getUserChallenge($userId, $challengeId);
        if (!$userChallenge || $userChallenge['is_completed']) {
            return false;
        }
        
        $challenge = $this->getChallenge($challengeId);
        if (!$challenge) {
            return false;
        }
        
        $requirements = json_decode($challenge['requirements'], true);
        $maxProgress = $this->calculateMaxProgress($requirements);
        
        $newProgress = min($progress, $maxProgress);
        
        try {
            $this->db->update(
                'user_challenges',
                ['progress' => $newProgress],
                'user_id = :user_id AND challenge_id = :challenge_id',
                ['user_id' => $userId, 'challenge_id' => $challengeId]
            );
            
            // Check if challenge is completed
            if ($newProgress >= $maxProgress) {
                $this->completeChallenge($userId, $challengeId);
            }
            
            return true;
        } catch (\Exception $e) {
            error_log("Error updating challenge progress: " . $e->getMessage());
            return false;
        }
    }
    
    private function completeChallenge(int $userId, int $challengeId): bool {
        try {
            $this->db->beginTransaction();
            
            // Mark challenge as completed
            $this->db->update(
                'user_challenges',
                [
                    'is_completed' => true,
                    'completed_at' => date('Y-m-d H:i:s')
                ],
                'user_id = :user_id AND challenge_id = :challenge_id',
                ['user_id' => $userId, 'challenge_id' => $challengeId]
            );
            
            // Award rewards
            $challenge = $this->getChallenge($challengeId);
            $rewards = json_decode($challenge['rewards'], true);
            
            foreach ($rewards as $reward) {
                $this->awardReward($userId, $reward);
            }
            
            $this->db->commit();
            
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            error_log("Error completing challenge: " . $e->getMessage());
            return false;
        }
    }
    
    private function awardReward(int $userId, array $reward): void {
        switch ($reward['type']) {
            case 'points':
                $this->db->query(
                    "UPDATE users SET points = points + :points WHERE id = :user_id",
                    ['points' => $reward['amount'], 'user_id' => $userId]
                );
                break;
                
            case 'badge':
                $this->db->insert('user_badges', [
                    'user_id' => $userId,
                    'badge_id' => $reward['badge_id'],
                    'earned_at' => date('Y-m-d H:i:s')
                ]);
                break;
                
            case 'achievement':
                $this->db->insert('user_achievements', [
                    'user_id' => $userId,
                    'achievement_id' => $reward['achievement_id'],
                    'earned_at' => date('Y-m-d H:i:s')
                ]);
                break;
                
            case 'title':
                $this->db->update(
                    'users',
                    ['custom_title' => $reward['title']],
                    'id = :user_id',
                    ['user_id' => $userId]
                );
                break;
        }
    }
    
    private function calculateMaxProgress(array $requirements): int {
        $maxProgress = 0;
        
        foreach ($requirements as $requirement) {
            if (isset($requirement['target'])) {
                $maxProgress += $requirement['target'];
            }
        }
        
        return $maxProgress;
    }
    
    public function getChallenge(int $challengeId): ?array {
        $challenge = $this->db->fetch(
            "SELECT * FROM challenges WHERE id = :challenge_id",
            ['challenge_id' => $challengeId]
        );
        
        return $challenge ?: null;
    }
    
    public function getUserChallenge(int $userId, int $challengeId): ?array {
        $userChallenge = $this->db->fetch(
            "SELECT * FROM user_challenges 
             WHERE user_id = :user_id AND challenge_id = :challenge_id",
            ['user_id' => $userId, 'challenge_id' => $challengeId]
        );
        
        return $userChallenge ?: null;
    }
    
    public function hasUserChallenge(int $userId, int $challengeId): bool {
        $count = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM user_challenges 
             WHERE user_id = :user_id AND challenge_id = :challenge_id",
            ['user_id' => $userId, 'challenge_id' => $challengeId]
        );
        
        return $count > 0;
    }
    
    private function isChallengeAvailable(array $challenge): bool {
        $now = date('Y-m-d H:i:s');
        
        if ($challenge['start_date'] && $challenge['start_date'] > $now) {
            return false;
        }
        
        if ($challenge['end_date'] && $challenge['end_date'] < $now) {
            return false;
        }
        
        return true;
    }
    
    public function getChallengeStats(): array {
        return [
            'total_challenges' => $this->db->fetchColumn("SELECT COUNT(*) FROM challenges"),
            'active_challenges' => $this->db->fetchColumn("SELECT COUNT(*) FROM challenges WHERE is_active = 1"),
            'completed_challenges' => $this->db->fetchColumn("SELECT COUNT(*) FROM user_challenges WHERE is_completed = 1"),
            'challenges_by_type' => $this->getChallengesByTypeStats(),
            'challenges_by_difficulty' => $this->getChallengesByDifficultyStats(),
            'challenges_by_category' => $this->getChallengesByCategoryStats(),
            'top_challenges' => $this->getTopChallenges()
        ];
    }
    
    private function getChallengesByTypeStats(): array {
        return $this->db->fetchAll(
            "SELECT type, COUNT(*) as count, COUNT(uc.id) as completed_count
             FROM challenges c
             LEFT JOIN user_challenges uc ON c.id = uc.challenge_id AND uc.is_completed = 1
             GROUP BY type
             ORDER BY count DESC"
        );
    }
    
    private function getChallengesByDifficultyStats(): array {
        return $this->db->fetchAll(
            "SELECT difficulty, COUNT(*) as count, COUNT(uc.id) as completed_count
             FROM challenges c
             LEFT JOIN user_challenges uc ON c.id = uc.challenge_id AND uc.is_completed = 1
             GROUP BY difficulty
             ORDER BY count DESC"
        );
    }
    
    private function getChallengesByCategoryStats(): array {
        return $this->db->fetchAll(
            "SELECT category, COUNT(*) as count, COUNT(uc.id) as completed_count
             FROM challenges c
             LEFT JOIN user_challenges uc ON c.id = uc.challenge_id AND uc.is_completed = 1
             GROUP BY category
             ORDER BY count DESC"
        );
    }
    
    private function getTopChallenges(int $limit = 10): array {
        return $this->db->fetchAll(
            "SELECT c.*, COUNT(uc.id) as completed_count
             FROM challenges c
             LEFT JOIN user_challenges uc ON c.id = uc.challenge_id AND uc.is_completed = 1
             GROUP BY c.id
             ORDER BY completed_count DESC
             LIMIT :limit",
            ['limit' => $limit]
        );
    }
    
    public function getChallengeTypes(): array {
        return $this->challengeTypes;
    }
    
    public function updateChallenge(int $challengeId, array $data): bool {
        try {
            $data['updated_at'] = date('Y-m-d H:i:s');
            
            $this->db->update(
                'challenges',
                $data,
                'id = :challenge_id',
                ['challenge_id' => $challengeId]
            );
            
            return true;
        } catch (\Exception $e) {
            error_log("Error updating challenge: " . $e->getMessage());
            return false;
        }
    }
    
    public function deleteChallenge(int $challengeId): bool {
        try {
            $this->db->beginTransaction();
            
            // Delete user challenges
            $this->db->delete('user_challenges', 'challenge_id = :challenge_id', ['challenge_id' => $challengeId]);
            
            // Delete the challenge
            $this->db->delete('challenges', 'id = :challenge_id', ['challenge_id' => $challengeId]);
            
            $this->db->commit();
            
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            error_log("Error deleting challenge: " . $e->getMessage());
            return false;
        }
    }
    
    public function getChallengeLeaderboard(int $challengeId, int $limit = 10): array {
        return $this->db->fetchAll(
            "SELECT u.id, u.username, u.avatar, uc.progress, uc.completed_at, uc.started_at
             FROM user_challenges uc
             JOIN users u ON uc.user_id = u.id
             WHERE uc.challenge_id = :challenge_id
             ORDER BY uc.progress DESC, uc.completed_at ASC
             LIMIT :limit",
            ['challenge_id' => $challengeId, 'limit' => $limit]
        );
    }
    
    public function getUserChallengeHistory(int $userId, int $limit = 50): array {
        return $this->db->fetchAll(
            "SELECT c.*, uc.progress, uc.completed_at, uc.started_at, uc.is_completed
             FROM user_challenges uc
             JOIN challenges c ON uc.challenge_id = c.id
             WHERE uc.user_id = :user_id
             ORDER BY uc.started_at DESC
             LIMIT :limit",
            ['user_id' => $userId, 'limit' => $limit]
        );
    }
    
    public function getChallengeProgress(int $userId, int $challengeId): array {
        $userChallenge = $this->getUserChallenge($userId, $challengeId);
        $challenge = $this->getChallenge($challengeId);
        
        if (!$userChallenge || !$challenge) {
            return [];
        }
        
        $requirements = json_decode($challenge['requirements'], true);
        $maxProgress = $this->calculateMaxProgress($requirements);
        
        return [
            'challenge' => $challenge,
            'progress' => $userChallenge['progress'],
            'max_progress' => $maxProgress,
            'progress_percentage' => $maxProgress > 0 ? round(($userChallenge['progress'] / $maxProgress) * 100, 2) : 0,
            'is_completed' => $userChallenge['is_completed'],
            'started_at' => $userChallenge['started_at'],
            'completed_at' => $userChallenge['completed_at']
        ];
    }
    
    public function resetDailyChallenges(): bool {
        try {
            $this->db->query(
                "DELETE FROM user_challenges 
                 WHERE challenge_id IN (
                     SELECT id FROM challenges 
                     WHERE type = 'daily' AND is_active = 1
                 )"
            );
            
            return true;
        } catch (\Exception $e) {
            error_log("Error resetting daily challenges: " . $e->getMessage());
            return false;
        }
    }
    
    public function resetWeeklyChallenges(): bool {
        try {
            $this->db->query(
                "DELETE FROM user_challenges 
                 WHERE challenge_id IN (
                     SELECT id FROM challenges 
                     WHERE type = 'weekly' AND is_active = 1
                 )"
            );
            
            return true;
        } catch (\Exception $e) {
            error_log("Error resetting weekly challenges: " . $e->getMessage());
            return false;
        }
    }
    
    public function resetMonthlyChallenges(): bool {
        try {
            $this->db->query(
                "DELETE FROM user_challenges 
                 WHERE challenge_id IN (
                     SELECT id FROM challenges 
                     WHERE type = 'monthly' AND is_active = 1
                 )"
            );
            
            return true;
        } catch (\Exception $e) {
            error_log("Error resetting monthly challenges: " . $e->getMessage());
            return false;
        }
    }
}