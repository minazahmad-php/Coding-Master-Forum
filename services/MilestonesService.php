<?php
declare(strict_types=1);

namespace Services;

class MilestonesService {
    private Database $db;
    private array $milestoneTypes;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->milestoneTypes = $this->getMilestoneTypes();
    }
    
    private function getMilestoneTypes(): array {
        return [
            'posts' => [
                'name' => 'Post Milestones',
                'description' => 'Milestones based on post count',
                'icon' => 'fas fa-edit',
                'color' => '#2196F3',
                'milestones' => [1, 5, 10, 25, 50, 100, 250, 500, 1000, 2500, 5000, 10000]
            ],
            'threads' => [
                'name' => 'Thread Milestones',
                'description' => 'Milestones based on thread count',
                'icon' => 'fas fa-comments',
                'color' => '#4CAF50',
                'milestones' => [1, 5, 10, 25, 50, 100, 250, 500, 1000]
            ],
            'likes_received' => [
                'name' => 'Likes Received Milestones',
                'description' => 'Milestones based on likes received',
                'icon' => 'fas fa-heart',
                'color' => '#E91E63',
                'milestones' => [1, 5, 10, 25, 50, 100, 250, 500, 1000, 2500, 5000]
            ],
            'likes_given' => [
                'name' => 'Likes Given Milestones',
                'description' => 'Milestones based on likes given',
                'icon' => 'fas fa-thumbs-up',
                'color' => '#FF9800',
                'milestones' => [1, 5, 10, 25, 50, 100, 250, 500, 1000, 2500, 5000]
            ],
            'helpful_posts' => [
                'name' => 'Helpful Posts Milestones',
                'description' => 'Milestones based on helpful posts',
                'icon' => 'fas fa-star',
                'color' => '#FFD700',
                'milestones' => [1, 5, 10, 25, 50, 100, 250, 500, 1000]
            ],
            'followers' => [
                'name' => 'Followers Milestones',
                'description' => 'Milestones based on follower count',
                'icon' => 'fas fa-users',
                'color' => '#9C27B0',
                'milestones' => [1, 5, 10, 25, 50, 100, 250, 500, 1000, 2500, 5000]
            ],
            'following' => [
                'name' => 'Following Milestones',
                'description' => 'Milestones based on following count',
                'icon' => 'fas fa-user-plus',
                'color' => '#00BCD4',
                'milestones' => [1, 5, 10, 25, 50, 100, 250, 500, 1000]
            ],
            'points' => [
                'name' => 'Points Milestones',
                'description' => 'Milestones based on point count',
                'icon' => 'fas fa-coins',
                'color' => '#FFD700',
                'milestones' => [100, 250, 500, 1000, 2500, 5000, 10000, 25000, 50000, 100000]
            ],
            'level' => [
                'name' => 'Level Milestones',
                'description' => 'Milestones based on level',
                'icon' => 'fas fa-trophy',
                'color' => '#FF6B6B',
                'milestones' => [5, 10, 15, 20, 25, 30, 35, 40, 45, 50, 60, 70, 80, 90, 100]
            ],
            'achievements' => [
                'name' => 'Achievement Milestones',
                'description' => 'Milestones based on achievement count',
                'icon' => 'fas fa-medal',
                'color' => '#4ECDC4',
                'milestones' => [1, 5, 10, 25, 50, 100, 250, 500]
            ],
            'badges' => [
                'name' => 'Badge Milestones',
                'description' => 'Milestones based on badge count',
                'icon' => 'fas fa-award',
                'color' => '#95A5A6',
                'milestones' => [1, 5, 10, 25, 50, 100, 250, 500]
            ],
            'account_age' => [
                'name' => 'Account Age Milestones',
                'description' => 'Milestones based on account age in days',
                'icon' => 'fas fa-calendar',
                'color' => '#34495E',
                'milestones' => [7, 30, 90, 180, 365, 730, 1095, 1460, 1825, 2190]
            ]
        ];
    }
    
    public function checkMilestones(int $userId, string $type, int $currentValue): array {
        if (!isset($this->milestoneTypes[$type])) {
            return [];
        }
        
        $milestones = $this->milestoneTypes[$type]['milestones'];
        $achievedMilestones = [];
        
        foreach ($milestones as $milestone) {
            if ($currentValue >= $milestone) {
                // Check if milestone is already achieved
                $existingMilestone = $this->db->fetch(
                    "SELECT * FROM user_milestones 
                     WHERE user_id = :user_id AND type = :type AND milestone = :milestone",
                    ['user_id' => $userId, 'type' => $type, 'milestone' => $milestone]
                );
                
                if (!$existingMilestone) {
                    $achievedMilestones[] = $this->awardMilestone($userId, $type, $milestone, $currentValue);
                }
            }
        }
        
        return $achievedMilestones;
    }
    
    private function awardMilestone(int $userId, string $type, int $milestone, int $currentValue): array {
        try {
            $this->db->beginTransaction();
            
            // Create milestone record
            $this->db->insert('user_milestones', [
                'user_id' => $userId,
                'type' => $type,
                'milestone' => $milestone,
                'current_value' => $currentValue,
                'achieved_at' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            // Award points based on milestone
            $points = $this->calculateMilestonePoints($type, $milestone);
            if ($points > 0) {
                $this->db->query(
                    "UPDATE users SET points = points + :points WHERE id = :user_id",
                    ['points' => $points, 'user_id' => $userId]
                );
            }
            
            // Award badge if applicable
            $badgeId = $this->getMilestoneBadge($type, $milestone);
            if ($badgeId) {
                $this->db->insert('user_badges', [
                    'user_id' => $userId,
                    'badge_id' => $badgeId,
                    'earned_at' => date('Y-m-d H:i:s')
                ]);
            }
            
            // Award achievement if applicable
            $achievementId = $this->getMilestoneAchievement($type, $milestone);
            if ($achievementId) {
                $this->db->insert('user_achievements', [
                    'user_id' => $userId,
                    'achievement_id' => $achievementId,
                    'earned_at' => date('Y-m-d H:i:s')
                ]);
            }
            
            $this->db->commit();
            
            return [
                'type' => $type,
                'milestone' => $milestone,
                'current_value' => $currentValue,
                'points_awarded' => $points,
                'badge_id' => $badgeId,
                'achievement_id' => $achievementId,
                'achieved_at' => date('Y-m-d H:i:s')
            ];
            
        } catch (\Exception $e) {
            $this->db->rollback();
            error_log("Error awarding milestone: " . $e->getMessage());
            return [];
        }
    }
    
    private function calculateMilestonePoints(string $type, int $milestone): int {
        $pointMultipliers = [
            'posts' => 5,
            'threads' => 10,
            'likes_received' => 2,
            'likes_given' => 1,
            'helpful_posts' => 10,
            'followers' => 3,
            'following' => 1,
            'points' => 0, // No points for point milestones
            'level' => 25,
            'achievements' => 15,
            'badges' => 10,
            'account_age' => 5
        ];
        
        $multiplier = $pointMultipliers[$type] ?? 1;
        return $milestone * $multiplier;
    }
    
    private function getMilestoneBadge(string $type, int $milestone): ?string {
        $badgeMap = [
            'posts' => [
                1 => 'first_post',
                10 => 'poster_10',
                50 => 'poster_50',
                100 => 'poster_100',
                500 => 'poster_500',
                1000 => 'poster_1000'
            ],
            'threads' => [
                1 => 'first_thread',
                10 => 'threader_10',
                50 => 'threader_50',
                100 => 'threader_100'
            ],
            'likes_received' => [
                10 => 'liked_10',
                50 => 'liked_50',
                100 => 'liked_100',
                500 => 'liked_500'
            ],
            'helpful_posts' => [
                5 => 'helpful_5',
                25 => 'helpful_25',
                50 => 'helpful_50',
                100 => 'helpful_100'
            ],
            'followers' => [
                10 => 'popular_10',
                50 => 'popular_50',
                100 => 'popular_100',
                500 => 'popular_500'
            ],
            'level' => [
                10 => 'level_10',
                25 => 'level_25',
                50 => 'level_50',
                100 => 'level_100'
            ]
        ];
        
        return $badgeMap[$type][$milestone] ?? null;
    }
    
    private function getMilestoneAchievement(string $type, int $milestone): ?string {
        $achievementMap = [
            'posts' => [
                100 => 'century_poster',
                500 => 'half_millennium_poster',
                1000 => 'millennium_poster'
            ],
            'threads' => [
                50 => 'thread_master',
                100 => 'thread_legend'
            ],
            'likes_received' => [
                100 => 'beloved_member',
                500 => 'community_favorite',
                1000 => 'forum_celebrity'
            ],
            'helpful_posts' => [
                50 => 'helpful_member',
                100 => 'helpful_legend'
            ],
            'followers' => [
                100 => 'influencer',
                500 => 'community_leader',
                1000 => 'forum_celebrity'
            ],
            'level' => [
                50 => 'level_master',
                100 => 'level_legend'
            ]
        ];
        
        return $achievementMap[$type][$milestone] ?? null;
    }
    
    public function getUserMilestones(int $userId): array {
        return $this->db->fetchAll(
            "SELECT * FROM user_milestones 
             WHERE user_id = :user_id 
             ORDER BY type, milestone",
            ['user_id' => $userId]
        );
    }
    
    public function getUserMilestonesByType(int $userId, string $type): array {
        return $this->db->fetchAll(
            "SELECT * FROM user_milestones 
             WHERE user_id = :user_id AND type = :type 
             ORDER BY milestone",
            ['user_id' => $userId, 'type' => $type]
        );
    }
    
    public function getMilestoneProgress(int $userId, string $type): array {
        $milestones = $this->milestoneTypes[$type]['milestones'];
        $achievedMilestones = $this->getUserMilestonesByType($userId, $type);
        
        $achievedValues = array_column($achievedMilestones, 'milestone');
        $progress = [];
        
        foreach ($milestones as $milestone) {
            $progress[] = [
                'milestone' => $milestone,
                'achieved' => in_array($milestone, $achievedValues),
                'achieved_at' => $this->getMilestoneAchievedAt($achievedMilestones, $milestone)
            ];
        }
        
        return $progress;
    }
    
    private function getMilestoneAchievedAt(array $achievedMilestones, int $milestone): ?string {
        foreach ($achievedMilestones as $achieved) {
            if ($achieved['milestone'] === $milestone) {
                return $achieved['achieved_at'];
            }
        }
        return null;
    }
    
    public function getMilestoneStats(): array {
        return [
            'total_milestones' => $this->db->fetchColumn("SELECT COUNT(*) FROM user_milestones"),
            'milestones_by_type' => $this->getMilestonesByTypeStats(),
            'top_milestones' => $this->getTopMilestones(),
            'milestone_distribution' => $this->getMilestoneDistribution()
        ];
    }
    
    private function getMilestonesByTypeStats(): array {
        return $this->db->fetchAll(
            "SELECT type, COUNT(*) as count, COUNT(DISTINCT user_id) as unique_users
             FROM user_milestones 
             GROUP BY type 
             ORDER BY count DESC"
        );
    }
    
    private function getTopMilestones(int $limit = 10): array {
        return $this->db->fetchAll(
            "SELECT um.*, u.username, u.avatar
             FROM user_milestones um
             JOIN users u ON um.user_id = u.id
             ORDER BY um.milestone DESC, um.achieved_at DESC
             LIMIT :limit",
            ['limit' => $limit]
        );
    }
    
    private function getMilestoneDistribution(): array {
        return $this->db->fetchAll(
            "SELECT type, milestone, COUNT(*) as count
             FROM user_milestones 
             GROUP BY type, milestone 
             ORDER BY type, milestone"
        );
    }
    
    public function getMilestoneTypes(): array {
        return $this->milestoneTypes;
    }
    
    public function getMilestoneLeaderboard(string $type, int $limit = 10): array {
        return $this->db->fetchAll(
            "SELECT u.id, u.username, u.avatar, MAX(um.milestone) as highest_milestone,
                    COUNT(um.id) as milestone_count, MAX(um.achieved_at) as last_milestone
             FROM user_milestones um
             JOIN users u ON um.user_id = u.id
             WHERE um.type = :type
             GROUP BY u.id, u.username, u.avatar
             ORDER BY highest_milestone DESC, milestone_count DESC
             LIMIT :limit",
            ['type' => $type, 'limit' => $limit]
        );
    }
    
    public function getMilestoneHistory(int $userId, int $limit = 50): array {
        return $this->db->fetchAll(
            "SELECT * FROM user_milestones 
             WHERE user_id = :user_id 
             ORDER BY achieved_at DESC 
             LIMIT :limit",
            ['user_id' => $userId, 'limit' => $limit]
        );
    }
    
    public function getMilestoneComparison(int $userId1, int $userId2, string $type): array {
        $milestones1 = $this->getUserMilestonesByType($userId1, $type);
        $milestones2 = $this->getUserMilestonesByType($userId2, $type);
        
        $highest1 = $milestones1 ? max(array_column($milestones1, 'milestone')) : 0;
        $highest2 = $milestones2 ? max(array_column($milestones2, 'milestone')) : 0;
        
        return [
            'user1_highest' => $highest1,
            'user2_highest' => $highest2,
            'difference' => $highest1 - $highest2,
            'higher_milestone_user' => $highest1 > $highest2 ? $userId1 : $userId2,
            'type' => $type
        ];
    }
    
    public function getMilestoneAnalytics(int $userId): array {
        $milestones = $this->getUserMilestones($userId);
        $totalMilestones = count($milestones);
        
        $milestonesByType = [];
        foreach ($milestones as $milestone) {
            $type = $milestone['type'];
            if (!isset($milestonesByType[$type])) {
                $milestonesByType[$type] = 0;
            }
            $milestonesByType[$type]++;
        }
        
        $highestMilestone = 0;
        $highestMilestoneType = null;
        
        foreach ($milestones as $milestone) {
            if ($milestone['milestone'] > $highestMilestone) {
                $highestMilestone = $milestone['milestone'];
                $highestMilestoneType = $milestone['type'];
            }
        }
        
        return [
            'total_milestones' => $totalMilestones,
            'milestones_by_type' => $milestonesByType,
            'highest_milestone' => $highestMilestone,
            'highest_milestone_type' => $highestMilestoneType,
            'milestones' => $milestones
        ];
    }
    
    public function getMilestoneRewards(int $userId, string $type): array {
        $milestones = $this->milestoneTypes[$type]['milestones'];
        $achievedMilestones = $this->getUserMilestonesByType($userId, $type);
        
        $rewards = [];
        foreach ($milestones as $milestone) {
            $achieved = false;
            $achievedAt = null;
            
            foreach ($achievedMilestones as $achievedMilestone) {
                if ($achievedMilestone['milestone'] === $milestone) {
                    $achieved = true;
                    $achievedAt = $achievedMilestone['achieved_at'];
                    break;
                }
            }
            
            $rewards[] = [
                'milestone' => $milestone,
                'points' => $this->calculateMilestonePoints($type, $milestone),
                'badge_id' => $this->getMilestoneBadge($type, $milestone),
                'achievement_id' => $this->getMilestoneAchievement($type, $milestone),
                'achieved' => $achieved,
                'achieved_at' => $achievedAt
            ];
        }
        
        return $rewards;
    }
    
    public function getMilestoneSummary(int $userId): array {
        $milestones = $this->getUserMilestones($userId);
        $summary = [];
        
        foreach ($this->milestoneTypes as $type => $typeData) {
            $typeMilestones = array_filter($milestones, function($milestone) use ($type) {
                return $milestone['type'] === $type;
            });
            
            $summary[$type] = [
                'name' => $typeData['name'],
                'description' => $typeData['description'],
                'icon' => $typeData['icon'],
                'color' => $typeData['color'],
                'achieved_count' => count($typeMilestones),
                'total_milestones' => count($typeData['milestones']),
                'highest_milestone' => $typeMilestones ? max(array_column($typeMilestones, 'milestone')) : 0,
                'progress_percentage' => count($typeData['milestones']) > 0 ? 
                    round((count($typeMilestones) / count($typeData['milestones'])) * 100, 2) : 0
            ];
        }
        
        return $summary;
    }
}