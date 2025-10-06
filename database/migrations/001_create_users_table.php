<?php
/**
 * Create users table migration
 * Supports both MySQL and SQLite
 */

function createUsersTable($pdo) {
    $sql = "
    CREATE TABLE IF NOT EXISTS users (
        id " . (isSQLite($pdo) ? "INTEGER PRIMARY KEY AUTOINCREMENT" : "INT AUTO_INCREMENT PRIMARY KEY") . ",
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        first_name VARCHAR(50),
        last_name VARCHAR(50),
        avatar VARCHAR(255),
        role " . (isSQLite($pdo) ? "VARCHAR(20) DEFAULT 'user'" : "ENUM('user', 'moderator', 'admin') DEFAULT 'user'") . ",
        status " . (isSQLite($pdo) ? "VARCHAR(20) DEFAULT 'active'" : "ENUM('active', 'inactive', 'banned') DEFAULT 'active'") . ",
        email_verified_at " . (isSQLite($pdo) ? "TIMESTAMP NULL" : "TIMESTAMP NULL") . ",
        last_login_at " . (isSQLite($pdo) ? "TIMESTAMP NULL" : "TIMESTAMP NULL") . ",
        created_at " . (isSQLite($pdo) ? "TIMESTAMP DEFAULT CURRENT_TIMESTAMP" : "TIMESTAMP DEFAULT CURRENT_TIMESTAMP") . ",
        updated_at " . (isSQLite($pdo) ? "TIMESTAMP DEFAULT CURRENT_TIMESTAMP" : "TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP") . "
    )" . (isSQLite($pdo) ? "" : " ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    $pdo->exec($sql);
    
    // Create indexes
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_users_email ON users(email)",
        "CREATE INDEX IF NOT EXISTS idx_users_username ON users(username)",
        "CREATE INDEX IF NOT EXISTS idx_users_role ON users(role)",
        "CREATE INDEX IF NOT EXISTS idx_users_status ON users(status)"
    ];
    
    foreach ($indexes as $index) {
        $pdo->exec($index);
    }
}

function isSQLite($pdo) {
    return $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite';
}

// Run migration if called directly
if (php_sapi_name() === 'cli') {
    require_once 'config.php';
    
    try {
        $pdo = new PDO($dsn, $username, $password, $options);
        createUsersTable($pdo);
        echo "✅ Users table created successfully!\n";
    } catch (Exception $e) {
        echo "❌ Error creating users table: " . $e->getMessage() . "\n";
        exit(1);
    }
}
?>