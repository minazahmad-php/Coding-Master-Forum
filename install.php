<?php
/**
 * Forum Project - Complete Auto-Installation System
 * সর্বোচ্চ এবং সর্বশেষ সংস্করণের অটোমেটিক ইনস্টলেশন
 */

// Prevent direct access after installation
if (file_exists('.installed')) {
    die('❌ Installation already completed! Delete .installed file to reinstall.');
}

// Set error reporting for installation
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session for installation process
session_start();

// Installation steps
$steps = [
    'welcome' => 'Welcome & Requirements Check',
    'database' => 'Database Configuration',
    'admin' => 'Admin Account Setup',
    'config' => 'Environment Configuration',
    'install' => 'Installation Process',
    'complete' => 'Installation Complete'
];

$currentStep = $_GET['step'] ?? 'welcome';

// Handle form submissions
if ($_POST) {
    switch ($currentStep) {
        case 'database':
            $_SESSION['db_config'] = $_POST;
            header('Location: ?step=admin');
            exit;
        case 'admin':
            $_SESSION['admin_config'] = $_POST;
            header('Location: ?step=config');
            exit;
        case 'config':
            $_SESSION['env_config'] = $_POST;
            header('Location: ?step=install');
            exit;
        case 'install':
            performInstallation();
            break;
    }
}

function performInstallation() {
    try {
        $dbConfig = $_SESSION['db_config'];
        $adminConfig = $_SESSION['admin_config'];
        $envConfig = $_SESSION['env_config'];
        
        // Step 1: Create .env file
        createEnvFile($dbConfig, $envConfig);
        
        // Step 2: Setup database
        setupDatabase($dbConfig);
        
        // Step 3: Create admin user
        createAdminUser($adminConfig);
        
        // Step 4: Create necessary directories
        createDirectories();
        
        // Step 5: Set file permissions
        setFilePermissions();
        
        // Step 6: Create .installed file
        file_put_contents('.installed', date('Y-m-d H:i:s'));
        
        // Step 7: Clear session
        session_destroy();
        
        header('Location: ?step=complete');
        exit;
        
    } catch (Exception $e) {
        $error = "Installation failed: " . $e->getMessage();
    }
}

function createEnvFile($dbConfig, $envConfig) {
    $envContent = generateEnvContent($dbConfig, $envConfig);
    file_put_contents('.env', $envContent);
}

function generateEnvContent($dbConfig, $envConfig) {
    $isLocalhost = isLocalhost();
    $appUrl = $isLocalhost ? 'http://localhost' : ($envConfig['app_url'] ?? 'https://yourdomain.com');
    $dbType = $dbConfig['db_type'] ?? 'mysql';
    
    $env = "# Forum Project Environment Configuration\n";
    $env .= "# Generated on " . date('Y-m-d H:i:s') . "\n\n";
    
    // App Configuration
    $env .= "APP_NAME=\"Forum Project\"\n";
    $env .= "APP_ENV=" . ($isLocalhost ? 'local' : 'production') . "\n";
    $env .= "APP_DEBUG=" . ($isLocalhost ? 'true' : 'false') . "\n";
    $env .= "APP_URL={$appUrl}\n";
    $env .= "APP_TIMEZONE=UTC\n\n";
    
    // Database Configuration
    if ($dbType === 'sqlite') {
        $env .= "DB_CONNECTION=sqlite\n";
        $env .= "DB_DATABASE=" . __DIR__ . "/database/forum.sqlite\n";
    } else {
        $env .= "DB_CONNECTION=mysql\n";
        $env .= "DB_HOST={$dbConfig['db_host']}\n";
        $env .= "DB_PORT={$dbConfig['db_port']}\n";
        $env .= "DB_DATABASE={$dbConfig['db_name']}\n";
        $env .= "DB_USERNAME={$dbConfig['db_user']}\n";
        $env .= "DB_PASSWORD={$dbConfig['db_pass']}\n";
    }
    $env .= "\n";
    
    // Cache Configuration
    $env .= "CACHE_DRIVER=file\n";
    $env .= "CACHE_PREFIX=forum_\n";
    $env .= "CACHE_TTL=3600\n\n";
    
    // Session Configuration
    $env .= "SESSION_DRIVER=file\n";
    $env .= "SESSION_LIFETIME=120\n";
    $env .= "SESSION_ENCRYPT=false\n";
    $env .= "SESSION_PATH=/\n";
    $env .= "SESSION_DOMAIN=" . ($isLocalhost ? 'localhost' : parse_url($appUrl, PHP_URL_HOST)) . "\n";
    $env .= "SESSION_SECURE_COOKIE=" . ($isLocalhost ? 'false' : 'true') . "\n";
    $env .= "SESSION_HTTP_ONLY=true\n";
    $env .= "SESSION_SAME_SITE=strict\n\n";
    
    // Mail Configuration
    $env .= "MAIL_MAILER=smtp\n";
    $env .= "MAIL_HOST={$envConfig['mail_host']}\n";
    $env .= "MAIL_PORT={$envConfig['mail_port']}\n";
    $env .= "MAIL_USERNAME={$envConfig['mail_username']}\n";
    $env .= "MAIL_PASSWORD={$envConfig['mail_password']}\n";
    $env .= "MAIL_ENCRYPTION={$envConfig['mail_encryption']}\n";
    $env .= "MAIL_FROM_ADDRESS={$envConfig['mail_from']}\n";
    $env .= "MAIL_FROM_NAME=\"Forum Project\"\n\n";
    
    // Security Configuration
    $env .= "APP_KEY=" . generateAppKey() . "\n";
    $env .= "JWT_SECRET=" . generateJWTSecret() . "\n";
    $env .= "ENCRYPTION_KEY=" . generateEncryptionKey() . "\n\n";
    
    // Rate Limiting
    $env .= "RATE_LIMIT_ENABLED=true\n";
    $env .= "RATE_LIMIT_MAX_ATTEMPTS=60\n";
    $env .= "RATE_LIMIT_DECAY_MINUTES=1\n\n";
    
    // Performance
    $env .= "PERFORMANCE_CACHE_ENABLED=true\n";
    $env .= "PERFORMANCE_COMPRESSION_ENABLED=true\n";
    $env .= "PERFORMANCE_MINIFICATION_ENABLED=true\n\n";
    
    // Monitoring
    $env .= "MONITORING_ENABLED=true\n";
    $env .= "LOG_LEVEL=" . ($isLocalhost ? 'debug' : 'error') . "\n\n";
    
    // CDN
    $env .= "CDN_ENABLED=false\n";
    $env .= "CDN_URL=\n";
    $env .= "CDN_VERSION=1.0.0\n\n";
    
    // Social Login
    $env .= "GOOGLE_CLIENT_ID=\n";
    $env .= "GOOGLE_CLIENT_SECRET=\n";
    $env .= "FACEBOOK_CLIENT_ID=\n";
    $env .= "FACEBOOK_CLIENT_SECRET=\n";
    $env .= "TWITTER_CLIENT_ID=\n";
    $env .= "TWITTER_CLIENT_SECRET=\n\n";
    
    // Payment Gateways
    $env .= "STRIPE_PUBLIC_KEY=\n";
    $env .= "STRIPE_SECRET_KEY=\n";
    $env .= "PAYPAL_CLIENT_ID=\n";
    $env .= "PAYPAL_CLIENT_SECRET=\n\n";
    
    // Redis (Optional)
    $env .= "REDIS_HOST=127.0.0.1\n";
    $env .= "REDIS_PASSWORD=null\n";
    $env .= "REDIS_PORT=6379\n\n";
    
    return $env;
}

function isLocalhost() {
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return in_array($host, ['localhost', '127.0.0.1', '::1']) || 
           strpos($host, 'localhost') !== false || 
           strpos($host, '127.0.0.1') !== false;
}

function generateAppKey() {
    return 'base64:' . base64_encode(random_bytes(32));
}

function generateJWTSecret() {
    return bin2hex(random_bytes(32));
}

function generateEncryptionKey() {
    return bin2hex(random_bytes(16));
}

function setupDatabase($dbConfig) {
    $dbType = $dbConfig['db_type'] ?? 'mysql';
    
    if ($dbType === 'sqlite') {
        setupSQLiteDatabase();
    } else {
        setupMySQLDatabase($dbConfig);
    }
}

function setupSQLiteDatabase() {
    $dbPath = __DIR__ . '/database/forum.sqlite';
    
    // Create database directory if not exists
    if (!is_dir(__DIR__ . '/database')) {
        mkdir(__DIR__ . '/database', 0755, true);
    }
    
    // Create SQLite database
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Run migrations for SQLite
    runSQLiteMigrations($pdo);
}

function setupMySQLDatabase($dbConfig) {
    $dsn = "mysql:host={$dbConfig['db_host']};port={$dbConfig['db_port']};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbConfig['db_user'], $dbConfig['db_pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbConfig['db_name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$dbConfig['db_name']}`");
    
    // Run migrations for MySQL
    runMySQLMigrations($pdo);
}

function runSQLiteMigrations($pdo) {
    $migrations = [
        // Users table
        "CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            first_name VARCHAR(50),
            last_name VARCHAR(50),
            avatar VARCHAR(255),
            role ENUM('user', 'moderator', 'admin') DEFAULT 'user',
            status ENUM('active', 'inactive', 'banned') DEFAULT 'active',
            email_verified_at TIMESTAMP NULL,
            last_login_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        // Forums table
        "CREATE TABLE IF NOT EXISTS forums (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            slug VARCHAR(100) UNIQUE NOT NULL,
            icon VARCHAR(100),
            color VARCHAR(7),
            sort_order INTEGER DEFAULT 0,
            is_active BOOLEAN DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        // Categories table
        "CREATE TABLE IF NOT EXISTS categories (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            forum_id INTEGER NOT NULL,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            slug VARCHAR(100) NOT NULL,
            icon VARCHAR(100),
            color VARCHAR(7),
            sort_order INTEGER DEFAULT 0,
            is_active BOOLEAN DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (forum_id) REFERENCES forums(id) ON DELETE CASCADE
        )",
        
        // Threads table
        "CREATE TABLE IF NOT EXISTS threads (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            category_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(255) UNIQUE NOT NULL,
            content TEXT NOT NULL,
            is_pinned BOOLEAN DEFAULT 0,
            is_locked BOOLEAN DEFAULT 0,
            views INTEGER DEFAULT 0,
            replies_count INTEGER DEFAULT 0,
            last_reply_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )",
        
        // Posts table
        "CREATE TABLE IF NOT EXISTS posts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            thread_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            content TEXT NOT NULL,
            is_solution BOOLEAN DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (thread_id) REFERENCES threads(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )",
        
        // Sessions table
        "CREATE TABLE IF NOT EXISTS sessions (
            id VARCHAR(128) PRIMARY KEY,
            user_id INTEGER,
            ip_address VARCHAR(45),
            user_agent TEXT,
            payload TEXT,
            last_activity INTEGER,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )"
    ];
    
    foreach ($migrations as $sql) {
        $pdo->exec($sql);
    }
}

function runMySQLMigrations($pdo) {
    // Include all migration files
    $migrationFiles = glob(__DIR__ . '/database/migrations/*.php');
    sort($migrationFiles);
    
    foreach ($migrationFiles as $file) {
        include $file;
    }
}

function createAdminUser($adminConfig) {
    $dbConfig = $_SESSION['db_config'];
    $dbType = $dbConfig['db_type'] ?? 'mysql';
    
    if ($dbType === 'sqlite') {
        $pdo = new PDO('sqlite:' . __DIR__ . '/database/forum.sqlite');
    } else {
        $dsn = "mysql:host={$dbConfig['db_host']};port={$dbConfig['db_port']};dbname={$dbConfig['db_name']};charset=utf8mb4";
        $pdo = new PDO($dsn, $dbConfig['db_user'], $dbConfig['db_pass']);
    }
    
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create admin user
    $stmt = $pdo->prepare("
        INSERT INTO users (username, email, password, first_name, last_name, role, status, email_verified_at, created_at, updated_at) 
        VALUES (?, ?, ?, ?, ?, 'admin', 'active', NOW(), NOW(), NOW())
    ");
    
    $hashedPassword = password_hash($adminConfig['admin_password'], PASSWORD_DEFAULT);
    $stmt->execute([
        $adminConfig['admin_username'],
        $adminConfig['admin_email'],
        $hashedPassword,
        $adminConfig['admin_first_name'] ?? 'Admin',
        $adminConfig['admin_last_name'] ?? 'User'
    ]);
}

function createDirectories() {
    $directories = [
        'storage/logs',
        'storage/cache',
        'storage/sessions',
        'storage/backups',
        'storage/temp',
        'public/uploads',
        'public/uploads/avatars',
        'public/uploads/attachments',
        'public/uploads/temp'
    ];
    
    foreach ($directories as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}

function setFilePermissions() {
    $files = [
        '.env' => 0644,
        'storage' => 0755,
        'public/uploads' => 0755,
        'database' => 0755
    ];
    
    foreach ($files as $file => $permission) {
        if (file_exists($file)) {
            chmod($file, $permission);
        }
    }
}

function checkRequirements() {
    $requirements = [
        'PHP Version >= 7.4' => version_compare(PHP_VERSION, '7.4.0', '>='),
        'PDO Extension' => extension_loaded('pdo'),
        'PDO MySQL Extension' => extension_loaded('pdo_mysql'),
        'PDO SQLite Extension' => extension_loaded('pdo_sqlite'),
        'JSON Extension' => extension_loaded('json'),
        'MBString Extension' => extension_loaded('mbstring'),
        'OpenSSL Extension' => extension_loaded('openssl'),
        'CURL Extension' => extension_loaded('curl'),
        'GD Extension' => extension_loaded('gd'),
        'ZIP Extension' => extension_loaded('zip'),
        'XML Extension' => extension_loaded('xml'),
        'File Write Permission' => is_writable('.'),
        'Storage Directory Writable' => is_writable('storage') || mkdir('storage', 0755, true),
        'Public Directory Writable' => is_writable('public') || mkdir('public', 0755, true)
    ];
    
    return $requirements;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forum Project - Installation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .install-container { background: white; border-radius: 15px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); }
        .step-indicator { display: flex; justify-content: center; margin-bottom: 2rem; }
        .step { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 10px; font-weight: bold; }
        .step.active { background: #007bff; color: white; }
        .step.completed { background: #28a745; color: white; }
        .step.pending { background: #e9ecef; color: #6c757d; }
        .requirement-item { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #eee; }
        .requirement-item:last-child { border-bottom: none; }
        .status-icon { font-size: 1.2em; }
        .status-pass { color: #28a745; }
        .status-fail { color: #dc3545; }
        .form-floating { margin-bottom: 1rem; }
        .btn-install { background: linear-gradient(45deg, #007bff, #0056b3); border: none; padding: 12px 30px; font-weight: bold; }
        .btn-install:hover { background: linear-gradient(45deg, #0056b3, #004085); }
        .progress { height: 8px; border-radius: 4px; }
        .progress-bar { background: linear-gradient(45deg, #007bff, #0056b3); }
        .alert-install { border-radius: 10px; border: none; }
        .card { border: none; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); }
        .form-check-input:checked { background-color: #007bff; border-color: #007bff; }
        .form-control:focus { border-color: #007bff; box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25); }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="install-container p-5">
                    <!-- Header -->
                    <div class="text-center mb-4">
                        <h1 class="h2 mb-3">
                            <i class="fas fa-rocket text-primary me-2"></i>
                            Forum Project Installation
                        </h1>
                        <p class="text-muted">Complete Auto-Installation System</p>
                    </div>

                    <!-- Step Indicator -->
                    <div class="step-indicator">
                        <?php foreach ($steps as $key => $name): ?>
                            <div class="step <?php 
                                if ($key === $currentStep) echo 'active';
                                elseif (array_search($key, array_keys($steps)) < array_search($currentStep, array_keys($steps))) echo 'completed';
                                else echo 'pending';
                            ?>">
                                <?php echo array_search($key, array_keys($steps)) + 1; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Progress Bar -->
                    <div class="progress mb-4">
                        <div class="progress-bar" style="width: <?php echo ((array_search($currentStep, array_keys($steps)) + 1) / count($steps)) * 100; ?>%"></div>
                    </div>

                    <!-- Content -->
                    <div class="content">
                        <?php if ($currentStep === 'welcome'): ?>
                            <!-- Welcome Step -->
                            <div class="text-center mb-4">
                                <h3>Welcome to Forum Project Installation</h3>
                                <p class="text-muted">Let's set up your forum in just a few steps!</p>
                            </div>

                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i>System Requirements</h5>
                                </div>
                                <div class="card-body">
                                    <?php $requirements = checkRequirements(); ?>
                                    <?php foreach ($requirements as $requirement => $status): ?>
                                        <div class="requirement-item">
                                            <span><?php echo $requirement; ?></span>
                                            <i class="fas fa-<?php echo $status ? 'check' : 'times'; ?> status-icon status-<?php echo $status ? 'pass' : 'fail'; ?>"></i>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="text-center">
                                <a href="?step=database" class="btn btn-install btn-lg">
                                    <i class="fas fa-arrow-right me-2"></i>Start Installation
                                </a>
                            </div>

                        <?php elseif ($currentStep === 'database'): ?>
                            <!-- Database Configuration Step -->
                            <div class="text-center mb-4">
                                <h3>Database Configuration</h3>
                                <p class="text-muted">Choose your database type and configure connection</p>
                            </div>

                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="card-header">
                                                <h5 class="mb-0">Database Type</h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="form-check mb-3">
                                                    <input class="form-check-input" type="radio" name="db_type" id="sqlite" value="sqlite" checked onchange="toggleDatabaseFields()">
                                                    <label class="form-check-label" for="sqlite">
                                                        <i class="fas fa-database me-2"></i>SQLite (Recommended for localhost)
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="db_type" id="mysql" value="mysql" onchange="toggleDatabaseFields()">
                                                    <label class="form-check-label" for="mysql">
                                                        <i class="fas fa-server me-2"></i>MySQL (For production)
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="card-header">
                                                <h5 class="mb-0">Auto-Detection</h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="alert alert-info">
                                                    <i class="fas fa-info-circle me-2"></i>
                                                    <strong>Environment:</strong> <?php echo isLocalhost() ? 'Localhost' : 'Remote Host'; ?><br>
                                                    <strong>Auto-Config:</strong> <?php echo isLocalhost() ? 'SQLite + Local Settings' : 'MySQL + Production Settings'; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- MySQL Configuration (Hidden by default) -->
                                <div id="mysql-config" style="display: none;">
                                    <div class="card mt-4">
                                        <div class="card-header">
                                            <h5 class="mb-0">MySQL Configuration</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-floating">
                                                        <input type="text" class="form-control" id="db_host" name="db_host" value="<?php echo isLocalhost() ? 'localhost' : 'localhost'; ?>" required>
                                                        <label for="db_host">Database Host</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-floating">
                                                        <input type="number" class="form-control" id="db_port" name="db_port" value="3306" required>
                                                        <label for="db_port">Port</label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-floating">
                                                        <input type="text" class="form-control" id="db_name" name="db_name" value="forum_db" required>
                                                        <label for="db_name">Database Name</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-floating">
                                                        <input type="text" class="form-control" id="db_user" name="db_user" value="root" required>
                                                        <label for="db_user">Username</label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-floating">
                                                <input type="password" class="form-control" id="db_pass" name="db_pass" value="">
                                                <label for="db_pass">Password</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-center mt-4">
                                    <button type="submit" class="btn btn-install btn-lg">
                                        <i class="fas fa-arrow-right me-2"></i>Next: Admin Setup
                                    </button>
                                </div>
                            </form>

                        <?php elseif ($currentStep === 'admin'): ?>
                            <!-- Admin Account Setup Step -->
                            <div class="text-center mb-4">
                                <h3>Admin Account Setup</h3>
                                <p class="text-muted">Create your administrator account</p>
                            </div>

                            <form method="POST">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">Administrator Information</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <input type="text" class="form-control" id="admin_username" name="admin_username" value="admin" required>
                                                    <label for="admin_username">Username</label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <input type="email" class="form-control" id="admin_email" name="admin_email" value="admin@example.com" required>
                                                    <label for="admin_email">Email Address</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <input type="password" class="form-control" id="admin_password" name="admin_password" required>
                                                    <label for="admin_password">Password</label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <input type="password" class="form-control" id="admin_password_confirm" name="admin_password_confirm" required>
                                                    <label for="admin_password_confirm">Confirm Password</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <input type="text" class="form-control" id="admin_first_name" name="admin_first_name" value="Admin">
                                                    <label for="admin_first_name">First Name</label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <input type="text" class="form-control" id="admin_last_name" name="admin_last_name" value="User">
                                                    <label for="admin_last_name">Last Name</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-center mt-4">
                                    <button type="submit" class="btn btn-install btn-lg">
                                        <i class="fas fa-arrow-right me-2"></i>Next: Environment Config
                                    </button>
                                </div>
                            </form>

                        <?php elseif ($currentStep === 'config'): ?>
                            <!-- Environment Configuration Step -->
                            <div class="text-center mb-4">
                                <h3>Environment Configuration</h3>
                                <p class="text-muted">Configure your application settings</p>
                            </div>

                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="card-header">
                                                <h5 class="mb-0">Application Settings</h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="form-floating">
                                                    <input type="url" class="form-control" id="app_url" name="app_url" value="<?php echo isLocalhost() ? 'http://localhost' : 'https://yourdomain.com'; ?>" required>
                                                    <label for="app_url">Application URL</label>
                                                </div>
                                                <div class="form-floating">
                                                    <input type="text" class="form-control" id="app_name" name="app_name" value="Forum Project">
                                                    <label for="app_name">Application Name</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="card-header">
                                                <h5 class="mb-0">Mail Configuration</h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="form-floating">
                                                    <input type="text" class="form-control" id="mail_host" name="mail_host" value="smtp.gmail.com">
                                                    <label for="mail_host">SMTP Host</label>
                                                </div>
                                                <div class="form-floating">
                                                    <input type="number" class="form-control" id="mail_port" name="mail_port" value="587">
                                                    <label for="mail_port">Port</label>
                                                </div>
                                                <div class="form-floating">
                                                    <input type="email" class="form-control" id="mail_username" name="mail_username" value="">
                                                    <label for="mail_username">Username</label>
                                                </div>
                                                <div class="form-floating">
                                                    <input type="password" class="form-control" id="mail_password" name="mail_password" value="">
                                                    <label for="mail_password">Password</label>
                                                </div>
                                                <div class="form-floating">
                                                    <input type="email" class="form-control" id="mail_from" name="mail_from" value="noreply@example.com">
                                                    <label for="mail_from">From Email</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-center mt-4">
                                    <button type="submit" class="btn btn-install btn-lg">
                                        <i class="fas fa-arrow-right me-2"></i>Next: Installation
                                    </button>
                                </div>
                            </form>

                        <?php elseif ($currentStep === 'install'): ?>
                            <!-- Installation Process Step -->
                            <div class="text-center mb-4">
                                <h3>Installing Forum Project</h3>
                                <p class="text-muted">Please wait while we set up your forum...</p>
                            </div>

                            <div class="card">
                                <div class="card-body">
                                    <div class="text-center">
                                        <div class="spinner-border text-primary mb-3" role="status">
                                            <span class="visually-hidden">Installing...</span>
                                        </div>
                                        <h5>Installing...</h5>
                                        <p class="text-muted">This may take a few moments</p>
                                    </div>
                                </div>
                            </div>

                            <script>
                                // Auto-submit form to start installation
                                document.addEventListener('DOMContentLoaded', function() {
                                    setTimeout(function() {
                                        const form = document.createElement('form');
                                        form.method = 'POST';
                                        form.action = '?step=install';
                                        document.body.appendChild(form);
                                        form.submit();
                                    }, 2000);
                                });
                            </script>

                        <?php elseif ($currentStep === 'complete'): ?>
                            <!-- Installation Complete Step -->
                            <div class="text-center mb-4">
                                <div class="alert alert-success alert-install">
                                    <i class="fas fa-check-circle fa-3x mb-3"></i>
                                    <h3>Installation Complete!</h3>
                                    <p class="mb-0">Your Forum Project has been successfully installed and configured.</p>
                                </div>
                            </div>

                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">What's Next?</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6><i class="fas fa-user-shield me-2"></i>Admin Access</h6>
                                            <p class="text-muted">Login with your admin credentials to manage the forum.</p>
                                        </div>
                                        <div class="col-md-6">
                                            <h6><i class="fas fa-cog me-2"></i>Configuration</h6>
                                            <p class="text-muted">Customize settings, themes, and features from the admin panel.</p>
                                        </div>
                                    </div>
                                    <div class="row mt-3">
                                        <div class="col-md-6">
                                            <h6><i class="fas fa-users me-2"></i>User Management</h6>
                                            <p class="text-muted">Invite users and manage permissions and roles.</p>
                                        </div>
                                        <div class="col-md-6">
                                            <h6><i class="fas fa-chart-line me-2"></i>Analytics</h6>
                                            <p class="text-muted">Monitor forum activity and user engagement.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="text-center">
                                <a href="index.php" class="btn btn-install btn-lg me-3">
                                    <i class="fas fa-home me-2"></i>Go to Forum
                                </a>
                                <a href="admin" class="btn btn-outline-primary btn-lg">
                                    <i class="fas fa-cog me-2"></i>Admin Panel
                                </a>
                            </div>

                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleDatabaseFields() {
            const sqlite = document.getElementById('sqlite');
            const mysqlConfig = document.getElementById('mysql-config');
            
            if (sqlite.checked) {
                mysqlConfig.style.display = 'none';
            } else {
                mysqlConfig.style.display = 'block';
            }
        }

        // Password confirmation validation
        document.addEventListener('DOMContentLoaded', function() {
            const password = document.getElementById('admin_password');
            const confirmPassword = document.getElementById('admin_password_confirm');
            
            if (password && confirmPassword) {
                function validatePassword() {
                    if (password.value !== confirmPassword.value) {
                        confirmPassword.setCustomValidity("Passwords don't match");
                    } else {
                        confirmPassword.setCustomValidity('');
                    }
                }
                
                password.addEventListener('change', validatePassword);
                confirmPassword.addEventListener('keyup', validatePassword);
            }
        });
    </script>
</body>
</html>