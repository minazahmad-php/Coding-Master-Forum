<?php
declare(strict_types=1);

namespace Services;

class DatabaseOptimizationService {
    private Database $db;
    private array $optimizationConfig;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->optimizationConfig = $this->getOptimizationConfig();
    }
    
    private function getOptimizationConfig(): array {
        return [
            'indexes' => [
                'users' => [
                    'email' => 'UNIQUE INDEX idx_users_email (email)',
                    'username' => 'UNIQUE INDEX idx_users_username (username)',
                    'created_at' => 'INDEX idx_users_created_at (created_at)',
                    'last_login' => 'INDEX idx_users_last_login (last_login)',
                    'points' => 'INDEX idx_users_points (points)',
                    'level' => 'INDEX idx_users_level (level)',
                    'status' => 'INDEX idx_users_status (status)'
                ],
                'posts' => [
                    'user_id' => 'INDEX idx_posts_user_id (user_id)',
                    'thread_id' => 'INDEX idx_posts_thread_id (thread_id)',
                    'created_at' => 'INDEX idx_posts_created_at (created_at)',
                    'updated_at' => 'INDEX idx_posts_updated_at (updated_at)',
                    'is_pinned' => 'INDEX idx_posts_is_pinned (is_pinned)',
                    'is_locked' => 'INDEX idx_posts_is_locked (is_locked)',
                    'status' => 'INDEX idx_posts_status (status)'
                ],
                'threads' => [
                    'user_id' => 'INDEX idx_threads_user_id (user_id)',
                    'category_id' => 'INDEX idx_threads_category_id (category_id)',
                    'created_at' => 'INDEX idx_threads_created_at (created_at)',
                    'updated_at' => 'INDEX idx_threads_updated_at (updated_at)',
                    'is_pinned' => 'INDEX idx_threads_is_pinned (is_pinned)',
                    'is_locked' => 'INDEX idx_threads_is_locked (is_locked)',
                    'status' => 'INDEX idx_threads_status (status)',
                    'views' => 'INDEX idx_threads_views (views)'
                ],
                'likes' => [
                    'user_id' => 'INDEX idx_likes_user_id (user_id)',
                    'post_id' => 'INDEX idx_likes_post_id (post_id)',
                    'thread_id' => 'INDEX idx_likes_thread_id (thread_id)',
                    'created_at' => 'INDEX idx_likes_created_at (created_at)',
                    'composite' => 'INDEX idx_likes_user_post (user_id, post_id)',
                    'composite_thread' => 'INDEX idx_likes_user_thread (user_id, thread_id)'
                ],
                'comments' => [
                    'user_id' => 'INDEX idx_comments_user_id (user_id)',
                    'post_id' => 'INDEX idx_comments_post_id (post_id)',
                    'created_at' => 'INDEX idx_comments_created_at (created_at)',
                    'status' => 'INDEX idx_comments_status (status)'
                ],
                'follows' => [
                    'follower_id' => 'INDEX idx_follows_follower_id (follower_id)',
                    'following_id' => 'INDEX idx_follows_following_id (following_id)',
                    'created_at' => 'INDEX idx_follows_created_at (created_at)',
                    'composite' => 'INDEX idx_follows_follower_following (follower_id, following_id)'
                ],
                'messages' => [
                    'sender_id' => 'INDEX idx_messages_sender_id (sender_id)',
                    'receiver_id' => 'INDEX idx_messages_receiver_id (receiver_id)',
                    'created_at' => 'INDEX idx_messages_created_at (created_at)',
                    'is_read' => 'INDEX idx_messages_is_read (is_read)',
                    'composite' => 'INDEX idx_messages_sender_receiver (sender_id, receiver_id)'
                ],
                'notifications' => [
                    'user_id' => 'INDEX idx_notifications_user_id (user_id)',
                    'type' => 'INDEX idx_notifications_type (type)',
                    'created_at' => 'INDEX idx_notifications_created_at (created_at)',
                    'is_read' => 'INDEX idx_notifications_is_read (is_read)',
                    'composite' => 'INDEX idx_notifications_user_read (user_id, is_read)'
                ],
                'user_activities' => [
                    'user_id' => 'INDEX idx_user_activities_user_id (user_id)',
                    'activity_type' => 'INDEX idx_user_activities_type (activity_type)',
                    'created_at' => 'INDEX idx_user_activities_created_at (created_at)',
                    'composite' => 'INDEX idx_user_activities_user_type (user_id, activity_type)'
                ],
                'user_achievements' => [
                    'user_id' => 'INDEX idx_user_achievements_user_id (user_id)',
                    'achievement_id' => 'INDEX idx_user_achievements_achievement_id (achievement_id)',
                    'earned_at' => 'INDEX idx_user_achievements_earned_at (earned_at)',
                    'composite' => 'INDEX idx_user_achievements_user_achievement (user_id, achievement_id)'
                ],
                'user_badges' => [
                    'user_id' => 'INDEX idx_user_badges_user_id (user_id)',
                    'badge_id' => 'INDEX idx_user_badges_badge_id (badge_id)',
                    'earned_at' => 'INDEX idx_user_badges_earned_at (earned_at)',
                    'composite' => 'INDEX idx_user_badges_user_badge (user_id, badge_id)'
                ]
            ],
            'pragmas' => [
                'journal_mode' => 'WAL',
                'synchronous' => 'NORMAL',
                'cache_size' => '-64000', // 64MB
                'temp_store' => 'MEMORY',
                'mmap_size' => '268435456', // 256MB
                'page_size' => '4096',
                'auto_vacuum' => 'INCREMENTAL',
                'optimize' => 'true'
            ],
            'maintenance' => [
                'vacuum_interval' => 24, // hours
                'analyze_interval' => 6, // hours
                'optimize_interval' => 12, // hours
                'backup_interval' => 24, // hours
                'cleanup_interval' => 168 // hours (1 week)
            ]
        ];
    }
    
    public function optimizeDatabase(): array {
        $results = [];
        
        try {
            $this->db->beginTransaction();
            
            // Apply pragmas
            $results['pragmas'] = $this->applyPragmas();
            
            // Create indexes
            $results['indexes'] = $this->createIndexes();
            
            // Analyze tables
            $results['analyze'] = $this->analyzeTables();
            
            // Optimize tables
            $results['optimize'] = $this->optimizeTables();
            
            // Cleanup old data
            $results['cleanup'] = $this->cleanupOldData();
            
            $this->db->commit();
            
            $results['success'] = true;
            $results['message'] = 'Database optimization completed successfully';
            
        } catch (\Exception $e) {
            $this->db->rollback();
            $results['success'] = false;
            $results['message'] = 'Database optimization failed: ' . $e->getMessage();
            error_log("Database optimization error: " . $e->getMessage());
        }
        
        return $results;
    }
    
    private function applyPragmas(): array {
        $results = [];
        $pragmas = $this->optimizationConfig['pragmas'];
        
        foreach ($pragmas as $pragma => $value) {
            try {
                $this->db->query("PRAGMA {$pragma} = {$value}");
                $results[$pragma] = ['success' => true, 'value' => $value];
            } catch (\Exception $e) {
                $results[$pragma] = ['success' => false, 'error' => $e->getMessage()];
            }
        }
        
        return $results;
    }
    
    private function createIndexes(): array {
        $results = [];
        $indexes = $this->optimizationConfig['indexes'];
        
        foreach ($indexes as $table => $tableIndexes) {
            $results[$table] = [];
            
            foreach ($tableIndexes as $indexName => $indexSql) {
                try {
                    $this->db->query($indexSql);
                    $results[$table][$indexName] = ['success' => true];
                } catch (\Exception $e) {
                    $results[$table][$indexName] = ['success' => false, 'error' => $e->getMessage()];
                }
            }
        }
        
        return $results;
    }
    
    private function analyzeTables(): array {
        $results = [];
        $tables = $this->getTables();
        
        foreach ($tables as $table) {
            try {
                $this->db->query("ANALYZE {$table}");
                $results[$table] = ['success' => true];
            } catch (\Exception $e) {
                $results[$table] = ['success' => false, 'error' => $e->getMessage()];
            }
        }
        
        return $results;
    }
    
    private function optimizeTables(): array {
        $results = [];
        $tables = $this->getTables();
        
        foreach ($tables as $table) {
            try {
                $this->db->query("REINDEX {$table}");
                $results[$table] = ['success' => true];
            } catch (\Exception $e) {
                $results[$table] = ['success' => false, 'error' => $e->getMessage()];
            }
        }
        
        return $results;
    }
    
    private function cleanupOldData(): array {
        $results = [];
        
        // Cleanup old sessions
        $results['sessions'] = $this->cleanupOldSessions();
        
        // Cleanup old logs
        $results['logs'] = $this->cleanupOldLogs();
        
        // Cleanup old notifications
        $results['notifications'] = $this->cleanupOldNotifications();
        
        // Cleanup old activities
        $results['activities'] = $this->cleanupOldActivities();
        
        return $results;
    }
    
    private function cleanupOldSessions(): array {
        try {
            $deleted = $this->db->query(
                "DELETE FROM sessions WHERE last_activity < :cutoff",
                ['cutoff' => date('Y-m-d H:i:s', strtotime('-30 days'))]
            );
            
            return ['success' => true, 'deleted' => $deleted];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    private function cleanupOldLogs(): array {
        try {
            $deleted = $this->db->query(
                "DELETE FROM logs WHERE created_at < :cutoff",
                ['cutoff' => date('Y-m-d H:i:s', strtotime('-90 days'))]
            );
            
            return ['success' => true, 'deleted' => $deleted];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    private function cleanupOldNotifications(): array {
        try {
            $deleted = $this->db->query(
                "DELETE FROM notifications WHERE created_at < :cutoff AND is_read = 1",
                ['cutoff' => date('Y-m-d H:i:s', strtotime('-30 days'))]
            );
            
            return ['success' => true, 'deleted' => $deleted];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    private function cleanupOldActivities(): array {
        try {
            $deleted = $this->db->query(
                "DELETE FROM user_activities WHERE created_at < :cutoff",
                ['cutoff' => date('Y-m-d H:i:s', strtotime('-180 days'))]
            );
            
            return ['success' => true, 'deleted' => $deleted];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    private function getTables(): array {
        $tables = $this->db->fetchAll("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
        return array_column($tables, 'name');
    }
    
    public function getDatabaseStats(): array {
        $stats = [];
        
        // Get table sizes
        $stats['table_sizes'] = $this->getTableSizes();
        
        // Get index usage
        $stats['index_usage'] = $this->getIndexUsage();
        
        // Get query performance
        $stats['query_performance'] = $this->getQueryPerformance();
        
        // Get database size
        $stats['database_size'] = $this->getDatabaseSize();
        
        // Get connection info
        $stats['connection_info'] = $this->getConnectionInfo();
        
        return $stats;
    }
    
    private function getTableSizes(): array {
        $tables = $this->getTables();
        $sizes = [];
        
        foreach ($tables as $table) {
            try {
                $count = $this->db->fetchColumn("SELECT COUNT(*) FROM {$table}");
                $sizes[$table] = $count;
            } catch (\Exception $e) {
                $sizes[$table] = 0;
            }
        }
        
        return $sizes;
    }
    
    private function getIndexUsage(): array {
        $indexes = $this->db->fetchAll("SELECT name, tbl_name FROM sqlite_master WHERE type='index' AND name NOT LIKE 'sqlite_%'");
        $usage = [];
        
        foreach ($indexes as $index) {
            $usage[$index['name']] = [
                'table' => $index['tbl_name'],
                'used' => true // SQLite doesn't provide index usage stats
            ];
        }
        
        return $usage;
    }
    
    private function getQueryPerformance(): array {
        $queries = $this->db->getQueryLog();
        $performance = [];
        
        foreach ($queries as $query) {
            $performance[] = [
                'query' => $query['query'],
                'execution_time' => $query['execution_time'],
                'timestamp' => $query['timestamp']
            ];
        }
        
        return $performance;
    }
    
    private function getDatabaseSize(): array {
        $dbFile = $this->db->getConnection()->getAttribute(\PDO::ATTR_SERVER_INFO);
        $size = filesize(DB_PATH);
        
        return [
            'size_bytes' => $size,
            'size_mb' => round($size / 1024 / 1024, 2),
            'size_gb' => round($size / 1024 / 1024 / 1024, 2)
        ];
    }
    
    private function getConnectionInfo(): array {
        $pdo = $this->db->getConnection();
        
        return [
            'driver' => $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME),
            'version' => $pdo->getAttribute(\PDO::ATTR_SERVER_VERSION),
            'connection_status' => $pdo->getAttribute(\PDO::ATTR_CONNECTION_STATUS),
            'timeout' => $pdo->getAttribute(\PDO::ATTR_TIMEOUT)
        ];
    }
    
    public function createBackup(): array {
        try {
            $backupPath = BACKUP_PATH . '/backup_' . date('Y-m-d_H-i-s') . '.sqlite';
            
            if (!is_dir(BACKUP_PATH)) {
                mkdir(BACKUP_PATH, 0755, true);
            }
            
            $source = DB_PATH;
            $destination = $backupPath;
            
            if (copy($source, $destination)) {
                return [
                    'success' => true,
                    'backup_path' => $backupPath,
                    'backup_size' => filesize($backupPath),
                    'created_at' => date('Y-m-d H:i:s')
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to create backup'
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Backup failed: ' . $e->getMessage()
            ];
        }
    }
    
    public function restoreBackup(string $backupPath): array {
        try {
            if (!file_exists($backupPath)) {
                return [
                    'success' => false,
                    'message' => 'Backup file not found'
                ];
            }
            
            // Create current backup before restore
            $currentBackup = $this->createBackup();
            
            if (!$currentBackup['success']) {
                return [
                    'success' => false,
                    'message' => 'Failed to create current backup before restore'
                ];
            }
            
            // Restore backup
            if (copy($backupPath, DB_PATH)) {
                return [
                    'success' => true,
                    'message' => 'Backup restored successfully',
                    'restored_at' => date('Y-m-d H:i:s'),
                    'current_backup' => $currentBackup['backup_path']
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to restore backup'
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Restore failed: ' . $e->getMessage()
            ];
        }
    }
    
    public function getBackups(): array {
        $backups = [];
        $backupDir = BACKUP_PATH;
        
        if (is_dir($backupDir)) {
            $files = glob($backupDir . '/backup_*.sqlite');
            
            foreach ($files as $file) {
                $backups[] = [
                    'filename' => basename($file),
                    'path' => $file,
                    'size' => filesize($file),
                    'created_at' => date('Y-m-d H:i:s', filemtime($file))
                ];
            }
            
            // Sort by creation time (newest first)
            usort($backups, function($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });
        }
        
        return $backups;
    }
    
    public function deleteBackup(string $backupPath): array {
        try {
            if (file_exists($backupPath)) {
                if (unlink($backupPath)) {
                    return [
                        'success' => true,
                        'message' => 'Backup deleted successfully'
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Failed to delete backup'
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'message' => 'Backup file not found'
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Delete failed: ' . $e->getMessage()
            ];
        }
    }
    
    public function scheduleMaintenance(): array {
        $schedule = $this->optimizationConfig['maintenance'];
        $results = [];
        
        foreach ($schedule as $task => $interval) {
            $results[$task] = [
                'interval_hours' => $interval,
                'next_run' => date('Y-m-d H:i:s', strtotime("+{$interval} hours")),
                'enabled' => true
            ];
        }
        
        return $results;
    }
    
    public function runMaintenanceTask(string $task): array {
        switch ($task) {
            case 'vacuum':
                return $this->runVacuum();
            case 'analyze':
                return $this->runAnalyze();
            case 'optimize':
                return $this->runOptimize();
            case 'backup':
                return $this->createBackup();
            case 'cleanup':
                return $this->cleanupOldData();
            default:
                return [
                    'success' => false,
                    'message' => 'Unknown maintenance task'
                ];
        }
    }
    
    private function runVacuum(): array {
        try {
            $this->db->query("VACUUM");
            return [
                'success' => true,
                'message' => 'Database vacuum completed successfully'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Vacuum failed: ' . $e->getMessage()
            ];
        }
    }
    
    private function runAnalyze(): array {
        try {
            $this->db->query("ANALYZE");
            return [
                'success' => true,
                'message' => 'Database analysis completed successfully'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Analysis failed: ' . $e->getMessage()
            ];
        }
    }
    
    private function runOptimize(): array {
        try {
            $this->db->query("PRAGMA optimize");
            return [
                'success' => true,
                'message' => 'Database optimization completed successfully'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Optimization failed: ' . $e->getMessage()
            ];
        }
    }
    
    public function getOptimizationConfig(): array {
        return $this->optimizationConfig;
    }
    
    public function updateOptimizationConfig(array $config): bool {
        try {
            $this->optimizationConfig = array_merge($this->optimizationConfig, $config);
            
            // Save to database or config file
            $this->db->update(
                'optimization_config',
                [
                    'config' => json_encode($this->optimizationConfig),
                    'updated_at' => date('Y-m-d H:i:s')
                ],
                'id = 1'
            );
            
            return true;
        } catch (\Exception $e) {
            error_log("Error updating optimization config: " . $e->getMessage());
            return false;
        }
    }
    
    public function getPerformanceMetrics(): array {
        return [
            'query_count' => count($this->db->getQueryLog()),
            'average_query_time' => $this->getAverageQueryTime(),
            'slow_queries' => $this->getSlowQueries(),
            'database_size' => $this->getDatabaseSize(),
            'table_sizes' => $this->getTableSizes(),
            'index_count' => count($this->db->fetchAll("SELECT name FROM sqlite_master WHERE type='index'")),
            'connection_status' => $this->getConnectionInfo()
        ];
    }
    
    private function getAverageQueryTime(): float {
        $queries = $this->db->getQueryLog();
        if (empty($queries)) {
            return 0;
        }
        
        $totalTime = array_sum(array_column($queries, 'execution_time'));
        return round($totalTime / count($queries), 4);
    }
    
    private function getSlowQueries(int $threshold = 1.0): array {
        $queries = $this->db->getQueryLog();
        $slowQueries = [];
        
        foreach ($queries as $query) {
            if ($query['execution_time'] > $threshold) {
                $slowQueries[] = $query;
            }
        }
        
        return $slowQueries;
    }
}