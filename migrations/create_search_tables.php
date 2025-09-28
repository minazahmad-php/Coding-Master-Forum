<?php
declare(strict_types=1);

// Search Database Migration
require_once 'config.php';

try {
    $pdo = new PDO(DB_DSN);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create search_logs table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS search_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            query TEXT NOT NULL,
            user_id INTEGER,
            results_count INTEGER DEFAULT 0,
            search_time REAL,
            ip_address TEXT,
            user_agent TEXT,
            session_id TEXT,
            referrer TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ");
    
    // Create search_result_logs table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS search_result_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            search_id INTEGER NOT NULL,
            result_type TEXT NOT NULL,
            result_id INTEGER NOT NULL,
            position INTEGER DEFAULT 0,
            clicked BOOLEAN DEFAULT 0,
            clicked_at DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (search_id) REFERENCES search_logs(id)
        )
    ");
    
    // Create indexes
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_search_logs_query ON search_logs(query)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_search_logs_user_id ON search_logs(user_id)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_search_logs_created_at ON search_logs(created_at)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_search_result_logs_search_id ON search_result_logs(search_id)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_search_result_logs_result_type ON search_result_logs(result_type)");
    
    echo "Search database tables created successfully!\n";
    
} catch (PDOException $e) {
    echo "Error creating search tables: " . $e->getMessage() . "\n";
}