<?php
declare(strict_types=1);

namespace Services;

class QuestsService {
    private Database $db;
    private array $questTypes;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->questTypes = $this->getQuestTypes();
    }
    
    private function getQuestTypes(): array {
        return [
            'main' => [
                'name' => 'Main Quest',
                'description' => 'Primary storyline quests',
                'icon' => 'fas fa-book',
                'color' => '#FFD700',
                'priority' => 1
            ],
            'side' => [
                'name' => 'Side Quest',
                'description' => 'Optional side quests',
                'icon' => 'fas fa-map',
                'color' => '#4CAF50',
                'priority' => 2
            ],
            'daily' => [
                'name' => 'Daily Quest',
                'description' => 'Daily repeatable quests',
                'icon' => 'fas fa-calendar-day',
                'color' => '#2196F3',
                'priority' => 3
            ],
            'weekly' => [
                'name' => 'Weekly Quest',
                'description' => 'Weekly repeatable quests',
                'icon' => 'fas fa-calendar-week',
                'color' => '#FF9800',
                'priority' => 4
            ],
            'event' => [
                'name' => 'Event Quest',
                'description' => 'Special event quests',
                'icon' => 'fas fa-star',
                'color' => '#E91E63',
                'priority' => 5
            ],
            'achievement' => [
                'name' => 'Achievement Quest',
                'description' => 'Achievement-based quests',
                'icon' => 'fas fa-trophy',
                'color' => '#9C27B0',
                'priority' => 6
            ]
        ];
    }
    
    public function createQuest(array $questData): bool {
        try {
            $this->db->insert('quests', [
                'name' => $questData['name'],
                'description' => $questData['description'],
                'type' => $questData['type'],
                'category' => $questData['category'] ?? 'general',
                'difficulty' => $questData['difficulty'] ?? 'easy',
                'requirements' => json_encode($questData['requirements'] ?? []),
                'objectives' => json_encode($questData['objectives'] ?? []),
                'rewards' => json_encode($questData['rewards'] ?? []),
                'prerequisites' => json_encode($questData['prerequisites'] ?? []),
                'start_date' => $questData['start_date'] ?? date('Y-m-d H:i:s'),
                'end_date' => $questData['end_date'] ?? null,
                'is_active' => $questData['is_active'] ?? true,
                'is_repeatable' => $questData['is_repeatable'] ?? false,
                'max_completions' => $questData['max_completions'] ?? 1,
                'time_limit' => $questData['time_limit'] ?? null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            return true;
        } catch (\Exception $e) {
            error_log("Error creating quest: " . $e->getMessage());
            return false;
        }
    }
    
    public function getAvailableQuests(int $userId): array {
        return $this->db->fetchAll(
            "SELECT q.*, 
                    CASE WHEN uq.id IS NOT NULL THEN 1 ELSE 0 END as is_started,
                    uq.progress, uq.started_at, uq.completed_at
             FROM quests q
             LEFT JOIN user_quests uq ON q.id = uq.quest_id AND uq.user_id = :user_id
             WHERE q.is_active = 1 
             AND (q.start_date <= NOW() OR q.start_date IS NULL)
             AND (q.end_date >= NOW() OR q.end_date IS NULL)
             AND (uq.id IS NULL OR uq.is_completed = 0)
             ORDER BY q.type, q.difficulty, q.name",
            ['user_id' => $userId]
        );
    }
    
    public function getQuestsByType(string $type): array {
        return $this->db->fetchAll(
            "SELECT * FROM quests 
             WHERE type = :type AND is_active = 1 
             ORDER BY difficulty, name",
            ['type' => $type]
        );
    }
    
    public function getQuestsByCategory(string $category): array {
        return $this->db->fetchAll(
            "SELECT * FROM quests 
             WHERE category = :category AND is_active = 1 
             ORDER BY difficulty, name",
            ['category' => $category]
        );
    }
    
    public function getQuestsByDifficulty(string $difficulty): array {
        return $this->db->fetchAll(
            "SELECT * FROM quests 
             WHERE difficulty = :difficulty AND is_active = 1 
             ORDER BY type, name",
            ['difficulty' => $difficulty]
        );
    }
    
    public function getUserQuests(int $userId): array {
        return $this->db->fetchAll(
            "SELECT q.*, uq.progress, uq.completed_at, uq.is_completed, uq.started_at
             FROM user_quests uq
             JOIN quests q ON uq.quest_id = q.id
             WHERE uq.user_id = :user_id
             ORDER BY uq.started_at DESC",
            ['user_id' => $userId]
        );
    }
    
    public function startQuest(int $userId, int $questId): bool {
        $quest = $this->getQuest($questId);
        if (!$quest || !$quest['is_active']) {
            return false;
        }
        
        // Check if user already has this quest
        if ($this->hasUserQuest($userId, $questId)) {
            return false;
        }
        
        // Check prerequisites
        if (!$this->checkPrerequisites($userId, $quest)) {
            return false;
        }
        
        // Check if quest is available
        if (!$this->isQuestAvailable($quest)) {
            return false;
        }
        
        try {
            $this->db->insert('user_quests', [
                'user_id' => $userId,
                'quest_id' => $questId,
                'progress' => 0,
                'is_completed' => false,
                'started_at' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            return true;
        } catch (\Exception $e) {
            error_log("Error starting quest: " . $e->getMessage());
            return false;
        }
    }
    
    public function updateQuestProgress(int $userId, int $questId, array $progress): bool {
        $userQuest = $this->getUserQuest($userId, $questId);
        if (!$userQuest || $userQuest['is_completed']) {
            return false;
        }
        
        $quest = $this->getQuest($questId);
        if (!$quest) {
            return false;
        }
        
        $objectives = json_decode($quest['objectives'], true);
        $newProgress = $this->calculateProgress($objectives, $progress);
        
        try {
            $this->db->update(
                'user_quests',
                ['progress' => $newProgress],
                'user_id = :user_id AND quest_id = :quest_id',
                ['user_id' => $userId, 'quest_id' => $questId]
            );
            
            // Check if quest is completed
            if ($newProgress >= 100) {
                $this->completeQuest($userId, $questId);
            }
            
            return true;
        } catch (\Exception $e) {
            error_log("Error updating quest progress: " . $e->getMessage());
            return false;
        }
    }
    
    private function completeQuest(int $userId, int $questId): bool {
        try {
            $this->db->beginTransaction();
            
            // Mark quest as completed
            $this->db->update(
                'user_quests',
                [
                    'is_completed' => true,
                    'completed_at' => date('Y-m-d H:i:s')
                ],
                'user_id = :user_id AND quest_id = :quest_id',
                ['user_id' => $userId, 'quest_id' => $questId]
            );
            
            // Award rewards
            $quest = $this->getQuest($questId);
            $rewards = json_decode($quest['rewards'], true);
            
            foreach ($rewards as $reward) {
                $this->awardReward($userId, $reward);
            }
            
            $this->db->commit();
            
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            error_log("Error completing quest: " . $e->getMessage());
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
                
            case 'item':
                $this->db->insert('user_items', [
                    'user_id' => $userId,
                    'item_id' => $reward['item_id'],
                    'quantity' => $reward['quantity'] ?? 1,
                    'obtained_at' => date('Y-m-d H:i:s')
                ]);
                break;
        }
    }
    
    private function calculateProgress(array $objectives, array $progress): int {
        $totalProgress = 0;
        $completedObjectives = 0;
        
        foreach ($objectives as $objective) {
            $objectiveId = $objective['id'];
            $target = $objective['target'];
            $current = $progress[$objectiveId] ?? 0;
            
            if ($current >= $target) {
                $completedObjectives++;
            }
        }
        
        if (count($objectives) > 0) {
            $totalProgress = round(($completedObjectives / count($objectives)) * 100, 2);
        }
        
        return $totalProgress;
    }
    
    public function getQuest(int $questId): ?array {
        $quest = $this->db->fetch(
            "SELECT * FROM quests WHERE id = :quest_id",
            ['quest_id' => $questId]
        );
        
        return $quest ?: null;
    }
    
    public function getUserQuest(int $userId, int $questId): ?array {
        $userQuest = $this->db->fetch(
            "SELECT * FROM user_quests 
             WHERE user_id = :user_id AND quest_id = :quest_id",
            ['user_id' => $userId, 'quest_id' => $questId]
        );
        
        return $userQuest ?: null;
    }
    
    public function hasUserQuest(int $userId, int $questId): bool {
        $count = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM user_quests 
             WHERE user_id = :user_id AND quest_id = :quest_id",
            ['user_id' => $userId, 'quest_id' => $questId]
        );
        
        return $count > 0;
    }
    
    private function checkPrerequisites(int $userId, array $quest): bool {
        $prerequisites = json_decode($quest['prerequisites'], true);
        
        if (empty($prerequisites)) {
            return true;
        }
        
        foreach ($prerequisites as $prerequisite) {
            switch ($prerequisite['type']) {
                case 'quest':
                    $questCompleted = $this->db->fetchColumn(
                        "SELECT COUNT(*) FROM user_quests 
                         WHERE user_id = :user_id AND quest_id = :quest_id AND is_completed = 1",
                        ['user_id' => $userId, 'quest_id' => $prerequisite['quest_id']]
                    );
                    if (!$questCompleted) {
                        return false;
                    }
                    break;
                    
                case 'level':
                    $userLevel = $this->db->fetchColumn(
                        "SELECT level FROM users WHERE id = :user_id",
                        ['user_id' => $userId]
                    );
                    if ($userLevel < $prerequisite['level']) {
                        return false;
                    }
                    break;
                    
                case 'points':
                    $userPoints = $this->db->fetchColumn(
                        "SELECT points FROM users WHERE id = :user_id",
                        ['user_id' => $userId]
                    );
                    if ($userPoints < $prerequisite['points']) {
                        return false;
                    }
                    break;
                    
                case 'achievement':
                    $achievementEarned = $this->db->fetchColumn(
                        "SELECT COUNT(*) FROM user_achievements 
                         WHERE user_id = :user_id AND achievement_id = :achievement_id",
                        ['user_id' => $userId, 'achievement_id' => $prerequisite['achievement_id']]
                    );
                    if (!$achievementEarned) {
                        return false;
                    }
                    break;
                    
                case 'badge':
                    $badgeEarned = $this->db->fetchColumn(
                        "SELECT COUNT(*) FROM user_badges 
                         WHERE user_id = :user_id AND badge_id = :badge_id",
                        ['user_id' => $userId, 'badge_id' => $prerequisite['badge_id']]
                    );
                    if (!$badgeEarned) {
                        return false;
                    }
                    break;
            }
        }
        
        return true;
    }
    
    private function isQuestAvailable(array $quest): bool {
        $now = date('Y-m-d H:i:s');
        
        if ($quest['start_date'] && $quest['start_date'] > $now) {
            return false;
        }
        
        if ($quest['end_date'] && $quest['end_date'] < $now) {
            return false;
        }
        
        return true;
    }
    
    public function getQuestStats(): array {
        return [
            'total_quests' => $this->db->fetchColumn("SELECT COUNT(*) FROM quests"),
            'active_quests' => $this->db->fetchColumn("SELECT COUNT(*) FROM quests WHERE is_active = 1"),
            'completed_quests' => $this->db->fetchColumn("SELECT COUNT(*) FROM user_quests WHERE is_completed = 1"),
            'quests_by_type' => $this->getQuestsByTypeStats(),
            'quests_by_difficulty' => $this->getQuestsByDifficultyStats(),
            'quests_by_category' => $this->getQuestsByCategoryStats(),
            'top_quests' => $this->getTopQuests()
        ];
    }
    
    private function getQuestsByTypeStats(): array {
        return $this->db->fetchAll(
            "SELECT type, COUNT(*) as count, COUNT(uq.id) as completed_count
             FROM quests q
             LEFT JOIN user_quests uq ON q.id = uq.quest_id AND uq.is_completed = 1
             GROUP BY type
             ORDER BY count DESC"
        );
    }
    
    private function getQuestsByDifficultyStats(): array {
        return $this->db->fetchAll(
            "SELECT difficulty, COUNT(*) as count, COUNT(uq.id) as completed_count
             FROM quests q
             LEFT JOIN user_quests uq ON q.id = uq.quest_id AND uq.is_completed = 1
             GROUP BY difficulty
             ORDER BY count DESC"
        );
    }
    
    private function getQuestsByCategoryStats(): array {
        return $this->db->fetchAll(
            "SELECT category, COUNT(*) as count, COUNT(uq.id) as completed_count
             FROM quests q
             LEFT JOIN user_quests uq ON q.id = uq.quest_id AND uq.is_completed = 1
             GROUP BY category
             ORDER BY count DESC"
        );
    }
    
    private function getTopQuests(int $limit = 10): array {
        return $this->db->fetchAll(
            "SELECT q.*, COUNT(uq.id) as completed_count
             FROM quests q
             LEFT JOIN user_quests uq ON q.id = uq.quest_id AND uq.is_completed = 1
             GROUP BY q.id
             ORDER BY completed_count DESC
             LIMIT :limit",
            ['limit' => $limit]
        );
    }
    
    public function getQuestTypes(): array {
        return $this->questTypes;
    }
    
    public function updateQuest(int $questId, array $data): bool {
        try {
            $data['updated_at'] = date('Y-m-d H:i:s');
            
            $this->db->update(
                'quests',
                $data,
                'id = :quest_id',
                ['quest_id' => $questId]
            );
            
            return true;
        } catch (\Exception $e) {
            error_log("Error updating quest: " . $e->getMessage());
            return false;
        }
    }
    
    public function deleteQuest(int $questId): bool {
        try {
            $this->db->beginTransaction();
            
            // Delete user quests
            $this->db->delete('user_quests', 'quest_id = :quest_id', ['quest_id' => $questId]);
            
            // Delete the quest
            $this->db->delete('quests', 'id = :quest_id', ['quest_id' => $questId]);
            
            $this->db->commit();
            
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            error_log("Error deleting quest: " . $e->getMessage());
            return false;
        }
    }
    
    public function getQuestLeaderboard(int $questId, int $limit = 10): array {
        return $this->db->fetchAll(
            "SELECT u.id, u.username, u.avatar, uq.progress, uq.completed_at, uq.started_at
             FROM user_quests uq
             JOIN users u ON uq.user_id = u.id
             WHERE uq.quest_id = :quest_id
             ORDER BY uq.progress DESC, uq.completed_at ASC
             LIMIT :limit",
            ['quest_id' => $questId, 'limit' => $limit]
        );
    }
    
    public function getUserQuestHistory(int $userId, int $limit = 50): array {
        return $this->db->fetchAll(
            "SELECT q.*, uq.progress, uq.completed_at, uq.started_at, uq.is_completed
             FROM user_quests uq
             JOIN quests q ON uq.quest_id = q.id
             WHERE uq.user_id = :user_id
             ORDER BY uq.started_at DESC
             LIMIT :limit",
            ['user_id' => $userId, 'limit' => $limit]
        );
    }
    
    public function getQuestProgress(int $userId, int $questId): array {
        $userQuest = $this->getUserQuest($userId, $questId);
        $quest = $this->getQuest($questId);
        
        if (!$userQuest || !$quest) {
            return [];
        }
        
        $objectives = json_decode($quest['objectives'], true);
        
        return [
            'quest' => $quest,
            'progress' => $userQuest['progress'],
            'objectives' => $objectives,
            'is_completed' => $userQuest['is_completed'],
            'started_at' => $userQuest['started_at'],
            'completed_at' => $userQuest['completed_at']
        ];
    }
    
    public function getQuestChain(int $questId): array {
        $quest = $this->getQuest($questId);
        if (!$quest) {
            return [];
        }
        
        $chain = [$quest];
        
        // Find quests that have this quest as a prerequisite
        $nextQuests = $this->db->fetchAll(
            "SELECT * FROM quests 
             WHERE JSON_CONTAINS(prerequisites, JSON_OBJECT('type', 'quest', 'quest_id', :quest_id))
             AND is_active = 1",
            ['quest_id' => $questId]
        );
        
        foreach ($nextQuests as $nextQuest) {
            $chain = array_merge($chain, $this->getQuestChain($nextQuest['id']));
        }
        
        return $chain;
    }
    
    public function getQuestPrerequisites(int $questId): array {
        $quest = $this->getQuest($questId);
        if (!$quest) {
            return [];
        }
        
        $prerequisites = json_decode($quest['prerequisites'], true);
        $prerequisiteQuests = [];
        
        foreach ($prerequisites as $prerequisite) {
            if ($prerequisite['type'] === 'quest') {
                $prerequisiteQuest = $this->getQuest($prerequisite['quest_id']);
                if ($prerequisiteQuest) {
                    $prerequisiteQuests[] = $prerequisiteQuest;
                }
            }
        }
        
        return $prerequisiteQuests;
    }
    
    public function getQuestRewards(int $questId): array {
        $quest = $this->getQuest($questId);
        if (!$quest) {
            return [];
        }
        
        return json_decode($quest['rewards'], true);
    }
    
    public function getQuestObjectives(int $questId): array {
        $quest = $this->getQuest($questId);
        if (!$quest) {
            return [];
        }
        
        return json_decode($quest['objectives'], true);
    }
}