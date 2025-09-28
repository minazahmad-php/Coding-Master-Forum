<?php
declare(strict_types=1);

// Web-based Database Migration Script
// Access this file through your web browser to run migrations

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include configuration
require_once dirname(__DIR__) . '/config.php';
require_once CORE_PATH . '/Database.php';

use Core\Database;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Migration - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .migration-log {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 1rem;
            max-height: 400px;
            overflow-y: auto;
            font-family: 'Courier New', monospace;
            font-size: 0.875rem;
        }
        .success { color: #198754; }
        .error { color: #dc3545; }
        .info { color: #0d6efd; }
        .warning { color: #fd7e14; }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="mb-0">
                            <i class="fas fa-database me-2"></i>
                            Database Migration
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php
                        $migrationLog = [];
                        
                        function logMessage($message, $type = 'info') {
                            global $migrationLog;
                            $timestamp = date('Y-m-d H:i:s');
                            $migrationLog[] = [
                                'timestamp' => $timestamp,
                                'message' => $message,
                                'type' => $type
                            ];
                        }
                        
                        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_migration'])) {
                            try {
                                logMessage("Starting database migration...", 'info');
                                
                                $db = Database::getInstance();
                                $pdo = $db->getConnection();
                                
                                logMessage("Database connection established successfully", 'success');
                                
                                // Check if tables already exist
                                $existingTables = [];
                                $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'");
                                while ($row = $stmt->fetch()) {
                                    $existingTables[] = $row['name'];
                                }
                                
                                if (!empty($existingTables)) {
                                    logMessage("Found existing tables: " . implode(', ', $existingTables), 'warning');
                                }
                                
                                // Create Users table
                                logMessage("Creating users table...", 'info');
                                $pdo->exec("
                                    CREATE TABLE IF NOT EXISTS users (
                                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                                        username VARCHAR(50) UNIQUE NOT NULL,
                                        email VARCHAR(100) UNIQUE NOT NULL,
                                        password_hash VARCHAR(255) NOT NULL,
                                        first_name VARCHAR(50),
                                        last_name VARCHAR(50),
                                        avatar VARCHAR(255),
                                        bio TEXT,
                                        website VARCHAR(255),
                                        location VARCHAR(100),
                                        birth_date DATE,
                                        gender ENUM('male', 'female', 'other'),
                                        language_code VARCHAR(10) DEFAULT 'en',
                                        timezone VARCHAR(50) DEFAULT 'UTC',
                                        reputation INTEGER DEFAULT 0,
                                        posts_count INTEGER DEFAULT 0,
                                        comments_count INTEGER DEFAULT 0,
                                        followers_count INTEGER DEFAULT 0,
                                        following_count INTEGER DEFAULT 0,
                                        is_verified BOOLEAN DEFAULT 0,
                                        is_premium BOOLEAN DEFAULT 0,
                                        last_login DATETIME,
                                        email_verified_at DATETIME,
                                        two_factor_enabled BOOLEAN DEFAULT 0,
                                        two_factor_secret VARCHAR(255),
                                        remember_token VARCHAR(255),
                                        status ENUM('active', 'inactive', 'banned', 'suspended') DEFAULT 'active',
                                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                                        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                                    )
                                ");
                                logMessage("Users table created successfully", 'success');
                                
                                // Create Categories table
                                logMessage("Creating categories table...", 'info');
                                $pdo->exec("
                                    CREATE TABLE IF NOT EXISTS categories (
                                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                                        name VARCHAR(100) NOT NULL,
                                        slug VARCHAR(100) UNIQUE NOT NULL,
                                        description TEXT,
                                        color VARCHAR(7) DEFAULT '#007bff',
                                        icon VARCHAR(50),
                                        parent_id INTEGER,
                                        sort_order INTEGER DEFAULT 0,
                                        is_active BOOLEAN DEFAULT 1,
                                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                                        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                                        FOREIGN KEY (parent_id) REFERENCES categories(id)
                                    )
                                ");
                                logMessage("Categories table created successfully", 'success');
                                
                                // Create Posts table
                                logMessage("Creating posts table...", 'info');
                                $pdo->exec("
                                    CREATE TABLE IF NOT EXISTS posts (
                                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                                        title VARCHAR(255) NOT NULL,
                                        slug VARCHAR(255) UNIQUE NOT NULL,
                                        content TEXT NOT NULL,
                                        excerpt TEXT,
                                        user_id INTEGER NOT NULL,
                                        category_id INTEGER,
                                        status ENUM('draft', 'published', 'archived', 'deleted') DEFAULT 'draft',
                                        views_count INTEGER DEFAULT 0,
                                        likes_count INTEGER DEFAULT 0,
                                        comments_count INTEGER DEFAULT 0,
                                        shares_count INTEGER DEFAULT 0,
                                        bookmarks_count INTEGER DEFAULT 0,
                                        featured_image VARCHAR(255),
                                        meta_title VARCHAR(255),
                                        meta_description TEXT,
                                        tags TEXT,
                                        is_featured BOOLEAN DEFAULT 0,
                                        is_pinned BOOLEAN DEFAULT 0,
                                        published_at DATETIME,
                                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                                        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                                        FOREIGN KEY (user_id) REFERENCES users(id),
                                        FOREIGN KEY (category_id) REFERENCES categories(id)
                                    )
                                ");
                                logMessage("Posts table created successfully", 'success');
                                
                                // Create Comments table
                                logMessage("Creating comments table...", 'info');
                                $pdo->exec("
                                    CREATE TABLE IF NOT EXISTS comments (
                                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                                        content TEXT NOT NULL,
                                        user_id INTEGER NOT NULL,
                                        post_id INTEGER NOT NULL,
                                        parent_id INTEGER,
                                        likes_count INTEGER DEFAULT 0,
                                        replies_count INTEGER DEFAULT 0,
                                        is_approved BOOLEAN DEFAULT 1,
                                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                                        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                                        FOREIGN KEY (user_id) REFERENCES users(id),
                                        FOREIGN KEY (post_id) REFERENCES posts(id),
                                        FOREIGN KEY (parent_id) REFERENCES comments(id)
                                    )
                                ");
                                logMessage("Comments table created successfully", 'success');
                                
                                // Create Sessions table
                                logMessage("Creating sessions table...", 'info');
                                $pdo->exec("
                                    CREATE TABLE IF NOT EXISTS sessions (
                                        id VARCHAR(128) PRIMARY KEY,
                                        user_id INTEGER,
                                        ip_address VARCHAR(45),
                                        user_agent TEXT,
                                        payload TEXT,
                                        last_activity INTEGER,
                                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                                        FOREIGN KEY (user_id) REFERENCES users(id)
                                    )
                                ");
                                logMessage("Sessions table created successfully", 'success');
                                
                                // Create Notifications table
                                logMessage("Creating notifications table...", 'info');
                                $pdo->exec("
                                    CREATE TABLE IF NOT EXISTS notifications (
                                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                                        user_id INTEGER NOT NULL,
                                        type VARCHAR(50) NOT NULL,
                                        title VARCHAR(255) NOT NULL,
                                        message TEXT NOT NULL,
                                        data TEXT,
                                        is_read BOOLEAN DEFAULT 0,
                                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                                        FOREIGN KEY (user_id) REFERENCES users(id)
                                    )
                                ");
                                logMessage("Notifications table created successfully", 'success');
                                
                                // Create User Activities table
                                logMessage("Creating user_activities table...", 'info');
                                $pdo->exec("
                                    CREATE TABLE IF NOT EXISTS user_activities (
                                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                                        user_id INTEGER NOT NULL,
                                        action VARCHAR(100) NOT NULL,
                                        metadata TEXT,
                                        ip_address VARCHAR(45),
                                        user_agent TEXT,
                                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                                        FOREIGN KEY (user_id) REFERENCES users(id)
                                    )
                                ");
                                logMessage("User activities table created successfully", 'success');
                                
                                // Create Page Views table
                                logMessage("Creating page_views table...", 'info');
                                $pdo->exec("
                                    CREATE TABLE IF NOT EXISTS page_views (
                                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                                        page VARCHAR(255) NOT NULL,
                                        referrer TEXT,
                                        user_id INTEGER,
                                        ip_address VARCHAR(45),
                                        user_agent TEXT,
                                        session_id VARCHAR(128),
                                        time_on_page_seconds INTEGER,
                                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                                        FOREIGN KEY (user_id) REFERENCES users(id)
                                    )
                                ");
                                logMessage("Page views table created successfully", 'success');
                                
                                // Create Content Interactions table
                                logMessage("Creating content_interactions table...", 'info');
                                $pdo->exec("
                                    CREATE TABLE IF NOT EXISTS content_interactions (
                                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                                        content_id INTEGER NOT NULL,
                                        content_type VARCHAR(50) NOT NULL,
                                        action VARCHAR(50) NOT NULL,
                                        user_id INTEGER,
                                        ip_address VARCHAR(45),
                                        user_agent TEXT,
                                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                                        FOREIGN KEY (user_id) REFERENCES users(id)
                                    )
                                ");
                                logMessage("Content interactions table created successfully", 'success');
                                
                                // Create Engagement Events table
                                logMessage("Creating engagement_events table...", 'info');
                                $pdo->exec("
                                    CREATE TABLE IF NOT EXISTS engagement_events (
                                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                                        user_id INTEGER NOT NULL,
                                        event_type VARCHAR(100) NOT NULL,
                                        metadata TEXT,
                                        ip_address VARCHAR(45),
                                        user_agent TEXT,
                                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                                        FOREIGN KEY (user_id) REFERENCES users(id)
                                    )
                                ");
                                logMessage("Engagement events table created successfully", 'success');
                                
                                // Create Security Logs table
                                logMessage("Creating security_logs table...", 'info');
                                $pdo->exec("
                                    CREATE TABLE IF NOT EXISTS security_logs (
                                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                                        threat_type VARCHAR(100) NOT NULL,
                                        threat_name VARCHAR(255) NOT NULL,
                                        description TEXT NOT NULL,
                                        severity ENUM('low', 'medium', 'high', 'critical') NOT NULL,
                                        ip_address VARCHAR(45),
                                        user_id INTEGER,
                                        user_agent TEXT,
                                        url TEXT,
                                        method VARCHAR(10),
                                        request_data TEXT,
                                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                                        FOREIGN KEY (user_id) REFERENCES users(id)
                                    )
                                ");
                                logMessage("Security logs table created successfully", 'success');
                                
                                // Create Email Logs table
                                logMessage("Creating email_logs table...", 'info');
                                $pdo->exec("
                                    CREATE TABLE IF NOT EXISTS email_logs (
                                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                                        to_email VARCHAR(255) NOT NULL,
                                        subject VARCHAR(255) NOT NULL,
                                        body TEXT NOT NULL,
                                        status ENUM('sent', 'failed', 'pending') DEFAULT 'pending',
                                        sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                                        error_message TEXT
                                    )
                                ");
                                logMessage("Email logs table created successfully", 'success');
                                
                                // Create API Keys table
                                logMessage("Creating api_keys table...", 'info');
                                $pdo->exec("
                                    CREATE TABLE IF NOT EXISTS api_keys (
                                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                                        user_id INTEGER NOT NULL,
                                        name VARCHAR(100) NOT NULL,
                                        key_hash VARCHAR(255) NOT NULL,
                                        permissions TEXT,
                                        is_active BOOLEAN DEFAULT 1,
                                        last_used_at DATETIME,
                                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                                        FOREIGN KEY (user_id) REFERENCES users(id)
                                    )
                                ");
                                logMessage("API keys table created successfully", 'success');
                                
                                // Create Themes table
                                logMessage("Creating themes table...", 'info');
                                $pdo->exec("
                                    CREATE TABLE IF NOT EXISTS themes (
                                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                                        name VARCHAR(100) NOT NULL,
                                        description TEXT,
                                        author_id INTEGER NOT NULL,
                                        version VARCHAR(20) DEFAULT '1.0.0',
                                        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
                                        preview_image VARCHAR(255),
                                        theme_file VARCHAR(255),
                                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                                        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                                        FOREIGN KEY (author_id) REFERENCES users(id)
                                    )
                                ");
                                logMessage("Themes table created successfully", 'success');
                                
                                // Create Plugins table
                                logMessage("Creating plugins table...", 'info');
                                $pdo->exec("
                                    CREATE TABLE IF NOT EXISTS plugins (
                                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                                        name VARCHAR(100) NOT NULL,
                                        description TEXT,
                                        author_id INTEGER NOT NULL,
                                        version VARCHAR(20) DEFAULT '1.0.0',
                                        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
                                        preview_image VARCHAR(255),
                                        plugin_file VARCHAR(255),
                                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                                        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                                        FOREIGN KEY (author_id) REFERENCES users(id)
                                    )
                                ");
                                logMessage("Plugins table created successfully", 'success');
                                
                                // Create Payments table
                                logMessage("Creating payments table...", 'info');
                                $pdo->exec("
                                    CREATE TABLE IF NOT EXISTS payments (
                                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                                        user_id INTEGER NOT NULL,
                                        amount DECIMAL(10,2) NOT NULL,
                                        currency VARCHAR(3) DEFAULT 'USD',
                                        payment_method VARCHAR(50) NOT NULL,
                                        status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
                                        transaction_id VARCHAR(255),
                                        metadata TEXT,
                                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                                        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                                        FOREIGN KEY (user_id) REFERENCES users(id)
                                    )
                                ");
                                logMessage("Payments table created successfully", 'success');
                                
                                // Create Languages table
                                logMessage("Creating languages table...", 'info');
                                $pdo->exec("
                                    CREATE TABLE IF NOT EXISTS languages (
                                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                                        code VARCHAR(10) UNIQUE NOT NULL,
                                        name VARCHAR(100) NOT NULL,
                                        native_name VARCHAR(100) NOT NULL,
                                        is_rtl BOOLEAN DEFAULT 0,
                                        is_active BOOLEAN DEFAULT 1,
                                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                                    )
                                ");
                                logMessage("Languages table created successfully", 'success');
                                
                                // Create Search Logs table
                                logMessage("Creating search_logs table...", 'info');
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
                                logMessage("Search logs table created successfully", 'success');
                                
                                // Create indexes for better performance
                                logMessage("Creating database indexes...", 'info');
                                $indexes = [
                                    "CREATE INDEX IF NOT EXISTS idx_users_email ON users(email)",
                                    "CREATE INDEX IF NOT EXISTS idx_users_username ON users(username)",
                                    "CREATE INDEX IF NOT EXISTS idx_users_status ON users(status)",
                                    "CREATE INDEX IF NOT EXISTS idx_posts_user_id ON posts(user_id)",
                                    "CREATE INDEX IF NOT EXISTS idx_posts_category_id ON posts(category_id)",
                                    "CREATE INDEX IF NOT EXISTS idx_posts_status ON posts(status)",
                                    "CREATE INDEX IF NOT EXISTS idx_posts_published_at ON posts(published_at)",
                                    "CREATE INDEX IF NOT EXISTS idx_comments_user_id ON comments(user_id)",
                                    "CREATE INDEX IF NOT EXISTS idx_comments_post_id ON comments(post_id)",
                                    "CREATE INDEX IF NOT EXISTS idx_sessions_user_id ON sessions(user_id)",
                                    "CREATE INDEX IF NOT EXISTS idx_notifications_user_id ON notifications(user_id)",
                                    "CREATE INDEX IF NOT EXISTS idx_notifications_is_read ON notifications(is_read)",
                                    "CREATE INDEX IF NOT EXISTS idx_user_activities_user_id ON user_activities(user_id)",
                                    "CREATE INDEX IF NOT EXISTS idx_user_activities_action ON user_activities(action)",
                                    "CREATE INDEX IF NOT EXISTS idx_page_views_user_id ON page_views(user_id)",
                                    "CREATE INDEX IF NOT EXISTS idx_page_views_page ON page_views(page)",
                                    "CREATE INDEX IF NOT EXISTS idx_content_interactions_user_id ON content_interactions(user_id)",
                                    "CREATE INDEX IF NOT EXISTS idx_content_interactions_content_id ON content_interactions(content_id)",
                                    "CREATE INDEX IF NOT EXISTS idx_engagement_events_user_id ON engagement_events(user_id)",
                                    "CREATE INDEX IF NOT EXISTS idx_engagement_events_event_type ON engagement_events(event_type)",
                                    "CREATE INDEX IF NOT EXISTS idx_security_logs_user_id ON security_logs(user_id)",
                                    "CREATE INDEX IF NOT EXISTS idx_security_logs_severity ON security_logs(severity)",
                                    "CREATE INDEX IF NOT EXISTS idx_email_logs_status ON email_logs(status)",
                                    "CREATE INDEX IF NOT EXISTS idx_api_keys_user_id ON api_keys(user_id)",
                                    "CREATE INDEX IF NOT EXISTS idx_api_keys_is_active ON api_keys(is_active)",
                                    "CREATE INDEX IF NOT EXISTS idx_themes_author_id ON themes(author_id)",
                                    "CREATE INDEX IF NOT EXISTS idx_themes_status ON themes(status)",
                                    "CREATE INDEX IF NOT EXISTS idx_plugins_author_id ON plugins(author_id)",
                                    "CREATE INDEX IF NOT EXISTS idx_plugins_status ON plugins(status)",
                                    "CREATE INDEX IF NOT EXISTS idx_payments_user_id ON payments(user_id)",
                                    "CREATE INDEX IF NOT EXISTS idx_payments_status ON payments(status)",
                                    "CREATE INDEX IF NOT EXISTS idx_languages_code ON languages(code)",
                                    "CREATE INDEX IF NOT EXISTS idx_languages_is_active ON languages(is_active)",
                                    "CREATE INDEX IF NOT EXISTS idx_search_logs_user_id ON search_logs(user_id)",
                                    "CREATE INDEX IF NOT EXISTS idx_search_logs_query ON search_logs(query)"
                                ];
                                
                                foreach ($indexes as $index) {
                                    $pdo->exec($index);
                                }
                                logMessage("Database indexes created successfully", 'success');
                                
                                // Insert default data
                                logMessage("Inserting default data...", 'info');
                                
                                // Insert default categories
                                $defaultCategories = [
                                    ['General Discussion', 'general-discussion', 'General discussions and topics', '#007bff', 'fas fa-comments'],
                                    ['Technology', 'technology', 'Technology related discussions', '#28a745', 'fas fa-laptop-code'],
                                    ['Programming', 'programming', 'Programming and coding discussions', '#dc3545', 'fas fa-code'],
                                    ['Web Development', 'web-development', 'Web development topics', '#ffc107', 'fas fa-globe'],
                                    ['Mobile Development', 'mobile-development', 'Mobile app development', '#17a2b8', 'fas fa-mobile-alt'],
                                    ['Design', 'design', 'UI/UX and graphic design', '#6f42c1', 'fas fa-palette'],
                                    ['Business', 'business', 'Business and entrepreneurship', '#fd7e14', 'fas fa-briefcase'],
                                    ['Education', 'education', 'Educational content and learning', '#20c997', 'fas fa-graduation-cap']
                                ];
                                
                                $stmt = $pdo->prepare("
                                    INSERT OR IGNORE INTO categories (name, slug, description, color, icon, sort_order, is_active)
                                    VALUES (?, ?, ?, ?, ?, ?, 1)
                                ");
                                
                                foreach ($defaultCategories as $index => $category) {
                                    $stmt->execute([
                                        $category[0], $category[1], $category[2], 
                                        $category[3], $category[4], $index + 1
                                    ]);
                                }
                                logMessage("Default categories inserted successfully", 'success');
                                
                                // Insert default languages
                                $defaultLanguages = [
                                    ['en', 'English', 'English', 0],
                                    ['bn', 'Bengali', 'বাংলা', 0],
                                    ['ar', 'Arabic', 'العربية', 1],
                                    ['hi', 'Hindi', 'हिन्दी', 0]
                                ];
                                
                                $stmt = $pdo->prepare("
                                    INSERT OR IGNORE INTO languages (code, name, native_name, is_rtl, is_active)
                                    VALUES (?, ?, ?, ?, 1)
                                ");
                                
                                foreach ($defaultLanguages as $language) {
                                    $stmt->execute($language);
                                }
                                logMessage("Default languages inserted successfully", 'success');
                                
                                // Create admin user
                                logMessage("Creating admin user...", 'info');
                                $adminPassword = password_hash('admin123', PASSWORD_ARGON2ID);
                                $stmt = $pdo->prepare("
                                    INSERT OR IGNORE INTO users (username, email, password_hash, first_name, last_name, is_verified, status, created_at)
                                    VALUES (?, ?, ?, ?, ?, 1, 'active', CURRENT_TIMESTAMP)
                                ");
                                $stmt->execute(['admin', 'admin@forum.com', $adminPassword, 'Admin', 'User']);
                                logMessage("Admin user created successfully (username: admin, password: admin123)", 'success');
                                
                                // Get final table count
                                $stmt = $pdo->query("SELECT COUNT(*) as count FROM sqlite_master WHERE type='table'");
                                $tableCount = $stmt->fetch()['count'];
                                
                                logMessage("Migration completed successfully!", 'success');
                                logMessage("Total tables created: " . $tableCount, 'info');
                                logMessage("Database is ready for use!", 'success');
                                
                            } catch (Exception $e) {
                                logMessage("Migration failed: " . $e->getMessage(), 'error');
                                logMessage("Error in file: " . $e->getFile() . " on line: " . $e->getLine(), 'error');
                            }
                        }
                        ?>
                        
                        <?php if (empty($migrationLog)): ?>
                            <div class="alert alert-info">
                                <h5><i class="fas fa-info-circle me-2"></i>Database Migration</h5>
                                <p>This tool will create all necessary database tables for your forum application.</p>
                                <p><strong>Note:</strong> Make sure your database configuration is correct before running the migration.</p>
                            </div>
                            
                            <form method="POST">
                                <div class="d-grid gap-2">
                                    <button type="submit" name="run_migration" class="btn btn-primary btn-lg">
                                        <i class="fas fa-play me-2"></i>
                                        Run Database Migration
                                    </button>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="migration-log">
                                <?php foreach ($migrationLog as $log): ?>
                                    <div class="mb-1">
                                        <span class="text-muted">[<?= $log['timestamp'] ?>]</span>
                                        <span class="<?= $log['type'] ?>"><?= htmlspecialchars($log['message']) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="mt-3">
                                <a href="?" class="btn btn-secondary">
                                    <i class="fas fa-redo me-2"></i>
                                    Run Migration Again
                                </a>
                                <a href="/" class="btn btn-success">
                                    <i class="fas fa-home me-2"></i>
                                    Go to Forum
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>