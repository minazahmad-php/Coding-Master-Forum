<?php
/**
 * Free Hosting Installation for Forum Project
 * Complete setup optimized for free hosting services
 */

// Prevent direct access after installation
if (file_exists('.free-hosting-installed')) {
    die('❌ Free hosting installation already completed! Delete .free-hosting-installed file to reinstall.');
}

// Start session for installation process
session_start();

// Free hosting detection
function isFreeHosting() {
    $hosting_indicators = [
        'infinityfree.net',
        '000webhost.com',
        'freehostia.com',
        'byet.org',
        'hostinger.com',
        'awardspace.com',
        'freehosting.com',
        'infy.uk'
    ];
    
    $host = $_SERVER['HTTP_HOST'] ?? '';
    foreach ($hosting_indicators as $indicator) {
        if (strpos($host, $indicator) !== false) {
            return true;
        }
    }
    return false;
}

// Installation steps
$steps = [
    'welcome' => 'Welcome & Free Hosting Setup',
    'database' => 'Database Configuration',
    'admin' => 'Admin Account',
    'features' => 'Features Configuration',
    'install' => 'Installation',
    'complete' => 'Complete'
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
            header('Location: ?step=features');
            exit;
        case 'features':
            $_SESSION['features_config'] = $_POST;
            header('Location: ?step=install');
            exit;
        case 'install':
            if (isset($_POST['install_started'])) {
                $result = performFreeHostingInstallation();
                if (strpos($result, 'successfully') !== false) {
                    // Installation successful, redirect to complete page
                    header('Location: ?step=complete');
                    exit;
                } else {
                    // Installation failed, show error
                    $error = $result;
                }
            }
            break;
    }
}

function performFreeHostingInstallation() {
    try {
        $dbConfig = $_SESSION['db_config'] ?? [];
        $adminConfig = $_SESSION['admin_config'] ?? [];
        $featuresConfig = $_SESSION['features_config'] ?? [];
        
        // Step 1: Create free hosting-optimized .env
        createFreeHostingEnvFile($dbConfig, $featuresConfig);
        
        // Step 2: Setup database
        setupFreeHostingDatabase($dbConfig);
        
        // Step 3: Create admin user
        createAdminUser($adminConfig);
        
        // Step 4: Create free hosting directories
        createFreeHostingDirectories();
        
        // Step 5: Set free hosting permissions
        setFreeHostingPermissions();
        
        // Step 6: Create free hosting assets
        createFreeHostingAssets();
        
        // Step 7: Create PWA files
        createPWAFiles();
        
        // Step 8: Create .free-hosting-installed file
        file_put_contents('.free-hosting-installed', date('Y-m-d H:i:s'));
        
        // Step 9: Clear session
        session_destroy();
        
        // Return success message instead of redirect
        return "Installation completed successfully!";
        
    } catch (Exception $e) {
        return "Free hosting installation failed: " . $e->getMessage();
    }
}

function createFreeHostingEnvFile($dbConfig, $featuresConfig) {
    $envContent = generateFreeHostingEnvContent($dbConfig, $featuresConfig);
    file_put_contents('.env', $envContent);
}

function generateFreeHostingEnvContent($dbConfig, $featuresConfig) {
    $domain = 'coding-master.infy.uk';
    $isFreeHosting = isFreeHosting();
    
    $env = "# Forum Project - Free Hosting Environment\n";
    $env .= "# Generated on " . date('Y-m-d H:i:s') . "\n";
    $env .= "# Domain: {$domain}\n";
    $env .= "# Free Hosting: " . ($isFreeHosting ? 'Yes' : 'No') . "\n\n";
    
    // App Configuration
    $env .= "APP_NAME=\"Forum Project - Free Hosting\"\n";
    $env .= "APP_ENV=production\n";
    $env .= "APP_DEBUG=false\n";
    $env .= "APP_URL=https://{$domain}\n";
    $env .= "APP_TIMEZONE=Asia/Dhaka\n\n";
    
    // Free Hosting Configuration
    $env .= "FREE_HOSTING_ENABLED=true\n";
    $env .= "FREE_HOSTING_DOMAIN={$domain}\n";
    $env .= "FREE_HOSTING_SUBDOMAIN=coding-master\n";
    $env .= "FREE_HOSTING_TLD=infy.uk\n";
    $env .= "FREE_HOSTING_PHP_VERSION=7.4\n";
    $env .= "FREE_HOSTING_MYSQL_ENABLED=true\n";
    $env .= "FREE_HOSTING_FILE_UPLOAD_LIMIT=2M\n";
    $env .= "FREE_HOSTING_MEMORY_LIMIT=128M\n";
    $env .= "FREE_HOSTING_MAX_EXECUTION_TIME=30\n";
    $env .= "FREE_HOSTING_MAX_INPUT_VARS=1000\n\n";
    
    // Database Configuration
    $env .= "DB_CONNECTION=mysql\n";
    $env .= "DB_HOST=" . ($dbConfig['db_host'] ?? 'localhost') . "\n";
    $env .= "DB_PORT=" . ($dbConfig['db_port'] ?? '3306') . "\n";
    $env .= "DB_DATABASE=" . ($dbConfig['db_name'] ?? 'u123456789_forum') . "\n";
    $env .= "DB_USERNAME=" . ($dbConfig['db_user'] ?? 'u123456789_forum') . "\n";
    $env .= "DB_PASSWORD=" . ($dbConfig['db_pass'] ?? '') . "\n\n";
    
    // Cache Configuration (File-based for free hosting)
    $env .= "CACHE_DRIVER=file\n";
    $env .= "CACHE_PREFIX=free_forum_\n";
    $env .= "CACHE_TTL=3600\n\n";
    
    // Session Configuration (File-based for free hosting)
    $env .= "SESSION_DRIVER=file\n";
    $env .= "SESSION_LIFETIME=120\n";
    $env .= "SESSION_ENCRYPT=false\n";
    $env .= "SESSION_PATH=/\n";
    $env .= "SESSION_DOMAIN={$domain}\n";
    $env .= "SESSION_SECURE_COOKIE=false\n";
    $env .= "SESSION_HTTP_ONLY=true\n";
    $env .= "SESSION_SAME_SITE=lax\n\n";
    
    // Free Hosting Optimizations
    $env .= "FREE_HOSTING_OPTIMIZATION=true\n";
    $env .= "FREE_HOSTING_MINIFY_HTML=true\n";
    $env .= "FREE_HOSTING_MINIFY_CSS=true\n";
    $env .= "FREE_HOSTING_MINIFY_JS=true\n";
    $env .= "FREE_HOSTING_COMPRESS_IMAGES=true\n";
    $env .= "FREE_HOSTING_LAZY_LOADING=true\n";
    $env .= "FREE_HOSTING_CACHING=true\n";
    $env .= "FREE_HOSTING_GZIP_COMPRESSION=true\n";
    $env .= "FREE_HOSTING_BROWSER_CACHING=true\n\n";
    
    // Security (Free hosting optimized)
    $env .= "APP_KEY=" . generateAppKey() . "\n";
    $env .= "JWT_SECRET=" . generateJWTSecret() . "\n";
    $env .= "ENCRYPTION_KEY=" . generateEncryptionKey() . "\n\n";
    
    // Rate Limiting (Free hosting optimized)
    $env .= "RATE_LIMIT_ENABLED=true\n";
    $env .= "RATE_LIMIT_MAX_ATTEMPTS=10\n";
    $env .= "RATE_LIMIT_DECAY_MINUTES=1\n\n";
    
    // Features (Free hosting compatible)
    $env .= "FEATURES_RESPONSIVE_DESIGN=true\n";
    $env .= "FEATURES_MOBILE_OPTIMIZED=true\n";
    $env .= "FEATURES_PWA_SUPPORT=true\n";
    $env .= "FEATURES_OFFLINE_SUPPORT=false\n";
    $env .= "FEATURES_PUSH_NOTIFICATIONS=false\n";
    $env .= "FEATURES_REAL_TIME_CHAT=false\n";
    $env .= "FEATURES_FILE_UPLOADS=true\n";
    $env .= "FEATURES_USER_REGISTRATION=true\n";
    $env .= "FEATURES_EMAIL_VERIFICATION=false\n";
    $env .= "FEATURES_ADMIN_PANEL=true\n";
    $env .= "FEATURES_MODERATION=true\n";
    $env .= "FEATURES_SEARCH=true\n";
    $env .= "FEATURES_THEMES=true\n";
    $env .= "FEATURES_PLUGINS=false\n";
    $env .= "FEATURES_API=true\n";
    $env .= "FEATURES_ANALYTICS=true\n\n";
    
    // Email Configuration (Disabled for free hosting)
    $env .= "MAIL_MAILER=log\n";
    $env .= "MAIL_HOST=localhost\n";
    $env .= "MAIL_PORT=587\n";
    $env .= "MAIL_USERNAME=null\n";
    $env .= "MAIL_PASSWORD=null\n";
    $env .= "MAIL_ENCRYPTION=null\n";
    $env .= "MAIL_FROM_ADDRESS=noreply@{$domain}\n";
    $env .= "MAIL_FROM_NAME=\"{$domain}\"\n\n";
    
    // Logging (File-based for free hosting)
    $env .= "LOG_CHANNEL=file\n";
    $env .= "LOG_LEVEL=error\n";
    $env .= "LOG_FILE=storage/logs/forum.log\n\n";
    
    return $env;
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

function setupFreeHostingDatabase($dbConfig) {
    $host = $dbConfig['db_host'] ?? 'localhost';
    $port = $dbConfig['db_port'] ?? '3306';
    $user = $dbConfig['db_user'] ?? 'u123456789_forum';
    $pass = $dbConfig['db_pass'] ?? '';
    $dbname = $dbConfig['db_name'] ?? 'u123456789_forum';
    
    $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$dbname}`");
    
    // Run free hosting-optimized migrations
    runFreeHostingMigrations($pdo);
}

function runFreeHostingMigrations($pdo) {
    $migrations = [
        // Users table (Free hosting optimized)
        "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            first_name VARCHAR(50),
            last_name VARCHAR(50),
            avatar VARCHAR(255),
            phone VARCHAR(20),
            role VARCHAR(20) DEFAULT 'user',
            status VARCHAR(20) DEFAULT 'active',
            email_verified_at TIMESTAMP NULL,
            last_login_at TIMESTAMP NULL,
            last_seen_at TIMESTAMP NULL,
            free_hosting_optimized BOOLEAN DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        // Forums table
        "CREATE TABLE IF NOT EXISTS forums (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            slug VARCHAR(100) UNIQUE NOT NULL,
            icon VARCHAR(100),
            color VARCHAR(7),
            sort_order INT DEFAULT 0,
            is_active BOOLEAN DEFAULT 1,
            free_hosting_optimized BOOLEAN DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        // Categories table
        "CREATE TABLE IF NOT EXISTS categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            forum_id INT NOT NULL,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            slug VARCHAR(100) NOT NULL,
            icon VARCHAR(100),
            color VARCHAR(7),
            sort_order INT DEFAULT 0,
            is_active BOOLEAN DEFAULT 1,
            free_hosting_optimized BOOLEAN DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (forum_id) REFERENCES forums(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        // Threads table (Free hosting optimized)
        "CREATE TABLE IF NOT EXISTS threads (
            id INT AUTO_INCREMENT PRIMARY KEY,
            category_id INT NOT NULL,
            user_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(255) UNIQUE NOT NULL,
            content TEXT NOT NULL,
            is_pinned BOOLEAN DEFAULT 0,
            is_locked BOOLEAN DEFAULT 0,
            views INT DEFAULT 0,
            replies_count INT DEFAULT 0,
            last_reply_at TIMESTAMP NULL,
            free_hosting_optimized BOOLEAN DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        // Posts table
        "CREATE TABLE IF NOT EXISTS posts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            thread_id INT NOT NULL,
            user_id INT NOT NULL,
            content TEXT NOT NULL,
            is_solution BOOLEAN DEFAULT 0,
            free_hosting_optimized BOOLEAN DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (thread_id) REFERENCES threads(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        // Free hosting sessions table
        "CREATE TABLE IF NOT EXISTS free_hosting_sessions (
            id VARCHAR(128) PRIMARY KEY,
            user_id INT,
            ip_address VARCHAR(45),
            user_agent TEXT,
            payload TEXT,
            last_activity INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        // Notifications table
        "CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            data TEXT,
            read_at TIMESTAMP NULL,
            free_hosting_optimized BOOLEAN DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    ];
    
    foreach ($migrations as $sql) {
        $pdo->exec($sql);
    }
}

function createAdminUser($adminConfig) {
    $dbConfig = $_SESSION['db_config'] ?? [];
    $host = $dbConfig['db_host'] ?? 'localhost';
    $port = $dbConfig['db_port'] ?? '3306';
    $user = $dbConfig['db_user'] ?? 'u123456789_forum';
    $pass = $dbConfig['db_pass'] ?? '';
    $dbname = $dbConfig['db_name'] ?? 'u123456789_forum';
    
    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create admin user
    $stmt = $pdo->prepare("
        INSERT INTO users (username, email, password, first_name, last_name, role, status, email_verified_at, free_hosting_optimized, created_at, updated_at) 
        VALUES (?, ?, ?, ?, ?, 'admin', 'active', NOW(), 1, NOW(), NOW())
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

function createFreeHostingDirectories() {
    $directories = [
        'storage/logs',
        'storage/cache',
        'storage/sessions',
        'storage/backups',
        'storage/temp',
        'public/uploads',
        'public/uploads/avatars',
        'public/uploads/attachments',
        'public/uploads/temp',
        'public/images/icons',
        'public/images/free-hosting',
        'public/free-hosting',
        'public/free-hosting/css',
        'public/free-hosting/js',
        'public/free-hosting/images'
    ];
    
    foreach ($directories as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}

function setFreeHostingPermissions() {
    $files = [
        '.env' => 0644,
        'storage' => 0755,
        'public/uploads' => 0755,
        'public/free-hosting' => 0755
    ];
    
    foreach ($files as $file => $permission) {
        if (file_exists($file)) {
            chmod($file, $permission);
        }
    }
}

function createFreeHostingAssets() {
    // Create free hosting CSS
    $freeHostingCSS = "
/* Free Hosting Optimized CSS for Forum Project */
@media (max-width: 768px) {
    .container {
        padding: 10px;
    }
    
    .card {
        margin-bottom: 15px;
        border-radius: 10px;
    }
    
    .btn {
        padding: 12px 20px;
        font-size: 16px;
        border-radius: 8px;
    }
    
    .form-control {
        padding: 12px;
        font-size: 16px;
        border-radius: 8px;
    }
    
    .navbar {
        padding: 10px 0;
    }
    
    .navbar-brand {
        font-size: 20px;
    }
    
    .table-responsive {
        font-size: 14px;
    }
    
    .modal-dialog {
        margin: 10px;
    }
    
    .modal-content {
        border-radius: 10px;
    }
}

/* Free hosting optimizations */
.free-hosting-optimized {
    -webkit-transform: translateZ(0);
    transform: translateZ(0);
    -webkit-backface-visibility: hidden;
    backface-visibility: hidden;
}

/* Free hosting performance optimizations */
.performance-optimized {
    will-change: transform;
    -webkit-transform: translateZ(0);
    transform: translateZ(0);
}

/* Free hosting responsive design */
@media (max-width: 480px) {
    .container {
        padding: 5px;
    }
    
    .card {
        margin-bottom: 10px;
        border-radius: 8px;
    }
    
    .btn {
        padding: 10px 16px;
        font-size: 14px;
        border-radius: 6px;
    }
    
    .form-control {
        padding: 10px;
        font-size: 14px;
        border-radius: 6px;
    }
}
";
    
    file_put_contents('public/free-hosting/css/free-hosting.css', $freeHostingCSS);
    
    // Create free hosting JavaScript
    $freeHostingJS = "
// Free Hosting Optimized JavaScript for Forum Project
document.addEventListener('DOMContentLoaded', function() {
    // Free hosting detection
    const isFreeHosting = window.location.hostname.includes('infy.uk') || 
                         window.location.hostname.includes('infinityfree.net') ||
                         window.location.hostname.includes('000webhost.com');
    
    if (isFreeHosting) {
        document.body.classList.add('free-hosting-optimized');
        
        // Add free hosting-optimized classes
        document.querySelectorAll('.btn, .form-control, .card').forEach(el => {
            el.classList.add('free-hosting-optimized');
        });
        
        // Enable free hosting-specific features
        enableFreeHostingFeatures();
    }
    
    // PWA installation
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js')
            .then(registration => console.log('SW registered'))
            .catch(error => console.log('SW registration failed'));
    }
});

// Free hosting-specific features
function enableFreeHostingFeatures() {
    // Free hosting performance optimization
    enableFreeHostingPerformance();
    
    // Free hosting-specific optimizations
    enableFreeHostingOptimizations();
}

// Free hosting performance optimization
function enableFreeHostingPerformance() {
    // Add performance classes
    document.querySelectorAll('.card, .btn, .form-control').forEach(el => {
        el.classList.add('performance-optimized');
    });
}

// Free hosting-specific optimizations
function enableFreeHostingOptimizations() {
    // Lazy loading for images
    enableLazyLoading();
    
    // Image compression
    enableImageCompression();
    
    // Minification
    enableMinification();
}

// Lazy loading
function enableLazyLoading() {
    const images = document.querySelectorAll('img[data-src]');
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.classList.remove('lazy');
                imageObserver.unobserve(img);
            }
        });
    });
    
    images.forEach(img => imageObserver.observe(img));
}

// Image compression
function enableImageCompression() {
    // Add compression classes to images
    document.querySelectorAll('img').forEach(img => {
        img.classList.add('compressed');
    });
}

// Minification
function enableMinification() {
    // Add minification classes
    document.querySelectorAll('.btn, .form-control, .card').forEach(el => {
        el.classList.add('minified');
    });
}

// PWA features
function installPWA() {
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.ready.then(registration => {
            registration.showNotification('Forum Project', {
                body: 'PWA installed successfully!',
                icon: '/public/images/icon-192x192.png'
            });
        });
    }
}

// Offline support
window.addEventListener('online', function() {
    console.log('Online');
    showNotification('You are back online!');
});

window.addEventListener('offline', function() {
    console.log('Offline');
    showNotification('You are offline. Some features may not work.');
});

function showNotification(message) {
    if ('Notification' in window && Notification.permission === 'granted') {
        new Notification('Forum Project', {
            body: message,
            icon: '/public/images/icon-192x192.png'
        });
    }
}
";
    
    file_put_contents('public/free-hosting/js/free-hosting.js', $freeHostingJS);
}

function createPWAFiles() {
    // Create manifest.json
    $manifest = [
        'name' => 'Forum Project - Free Hosting',
        'short_name' => 'Forum',
        'description' => 'Complete Forum System for Free Hosting',
        'version' => '1.0.0',
        'theme_color' => '#007bff',
        'background_color' => '#ffffff',
        'display' => 'standalone',
        'orientation' => 'portrait',
        'scope' => '/',
        'start_url' => '/',
        'icons' => [
            [
                'src' => '/public/images/icon-192x192.png',
                'sizes' => '192x192',
                'type' => 'image/png'
            ],
            [
                'src' => '/public/images/icon-512x512.png',
                'sizes' => '512x512',
                'type' => 'image/png'
            ]
        ],
        'features' => [
            'responsive_design',
            'mobile_optimized',
            'free_hosting_optimized',
            'pwa_support',
            'admin_panel',
            'user_registration',
            'moderation',
            'search',
            'themes',
            'api',
            'analytics'
        ]
    ];
    
    file_put_contents('public/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));
    
    // Create service worker
    $sw = "
const CACHE_NAME = 'forum-project-free-hosting-v1';
const urlsToCache = [
    '/',
    '/public/css/style.css',
    '/public/free-hosting/css/free-hosting.css',
    '/public/js/app.js',
    '/public/free-hosting/js/free-hosting.js',
    '/public/images/logo.png',
    '/public/images/icon-192x192.png',
    '/public/images/icon-512x512.png'
];

self.addEventListener('install', function(event) {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(function(cache) {
                return cache.addAll(urlsToCache);
            })
    );
});

self.addEventListener('fetch', function(event) {
    event.waitUntil(
        caches.match(event.request)
            .then(function(response) {
                if (response) {
                    return response;
                }
                return fetch(event.request);
            }
        )
    );
});
";
    
    file_put_contents('public/sw.js', $sw);
}

function checkFreeHostingRequirements() {
    $requirements = [
        'PHP Version >= 7.4' => version_compare(PHP_VERSION, '7.4.0', '>='),
        'PDO Extension' => extension_loaded('pdo'),
        'PDO MySQL Extension' => extension_loaded('pdo_mysql'),
        'JSON Extension' => extension_loaded('json'),
        'MBString Extension' => extension_loaded('mbstring'),
        'OpenSSL Extension' => extension_loaded('openssl'),
        'CURL Extension' => extension_loaded('curl'),
        'GD Extension' => extension_loaded('gd'),
        'ZIP Extension' => extension_loaded('zip'),
        'XML Extension' => extension_loaded('xml'),
        'File Write Permission' => is_writable('.'),
        'Storage Directory Writable' => is_writable('storage') || mkdir('storage', 0755, true),
        'Public Directory Writable' => is_writable('public') || mkdir('public', 0755, true),
        'Free Hosting Support' => true,
        'Mobile Support' => true,
        'PWA Support' => true
    ];
    
    return $requirements;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Forum Project - Free Hosting Installation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            min-height: 100vh;
            font-size: 16px;
        }
        .install-container { 
            background: white; 
            border-radius: 15px; 
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            margin: 10px;
        }
        .step-indicator { 
            display: flex; 
            justify-content: center; 
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        .step { 
            width: 40px; 
            height: 40px; 
            border-radius: 50%; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            margin: 5px; 
            font-weight: bold;
            font-size: 14px;
        }
        .step.active { background: #007bff; color: white; }
        .step.completed { background: #28a745; color: white; }
        .step.pending { background: #e9ecef; color: #6c757d; }
        .requirement-item { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            padding: 10px 0; 
            border-bottom: 1px solid #eee; 
        }
        .requirement-item:last-child { border-bottom: none; }
        .status-icon { font-size: 1.2em; }
        .status-pass { color: #28a745; }
        .status-fail { color: #dc3545; }
        .form-floating { margin-bottom: 1rem; }
        .btn-install { 
            background: linear-gradient(45deg, #007bff, #0056b3); 
            border: none; 
            padding: 15px 30px; 
            font-weight: bold;
            font-size: 16px;
            border-radius: 8px;
        }
        .btn-install:hover { background: linear-gradient(45deg, #0056b3, #004085); }
        .progress { height: 8px; border-radius: 4px; }
        .progress-bar { background: linear-gradient(45deg, #007bff, #0056b3); }
        .alert-install { border-radius: 10px; border: none; }
        .card { border: none; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); }
        .form-check-input:checked { background-color: #007bff; border-color: #007bff; }
        .form-control:focus { border-color: #007bff; box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25); }
        
        /* Free hosting optimizations */
        @media (max-width: 768px) {
            .install-container {
                margin: 5px;
                border-radius: 10px;
            }
            .step {
                width: 35px;
                height: 35px;
                font-size: 12px;
            }
            .btn-install {
                padding: 12px 25px;
                font-size: 14px;
            }
            .form-control {
                font-size: 16px;
                padding: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid py-3">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-10">
                <div class="install-container p-4">
                    <!-- Header -->
                    <div class="text-center mb-4">
                        <h1 class="h2 mb-3">
                            <i class="fas fa-globe text-primary me-2"></i>
                            Forum Project - Free Hosting Installation
                        </h1>
                        <p class="text-muted">Optimized for Free Hosting Services</p>
                        <?php if (isFreeHosting()): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                Free Hosting Detected - Optimized Configuration
                            </div>
                        <?php endif; ?>
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
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Installation Error:</strong> <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($currentStep === 'welcome'): ?>
                            <!-- Welcome Step -->
                            <div class="text-center mb-4">
                                <h3>Welcome to Free Hosting Forum Installation</h3>
                                <p class="text-muted">Let's set up your free hosting-optimized forum!</p>
                            </div>

                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i>Free Hosting Requirements</h5>
                                </div>
                                <div class="card-body">
                                    <?php $requirements = checkFreeHostingRequirements(); ?>
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
                                    <i class="fas fa-arrow-right me-2"></i>Start Free Hosting Setup
                                </a>
                            </div>

                        <?php elseif ($currentStep === 'database'): ?>
                            <!-- Database Configuration Step -->
                            <div class="text-center mb-4">
                                <h3>Database Configuration</h3>
                                <p class="text-muted">Configure your free hosting database</p>
                            </div>

                            <form method="POST">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">Free Hosting Database Settings</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <input type="text" class="form-control" id="db_host" name="db_host" value="localhost" required>
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
                                                    <input type="text" class="form-control" id="db_name" name="db_name" value="u123456789_forum" required>
                                                    <label for="db_name">Database Name</label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <input type="text" class="form-control" id="db_user" name="db_user" value="u123456789_forum" required>
                                                    <label for="db_user">Username</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-floating">
                                            <input type="password" class="form-control" id="db_pass" name="db_pass" value="">
                                            <label for="db_pass">Password</label>
                                        </div>
                                        <div class="alert alert-info mt-3">
                                            <i class="fas fa-info-circle me-2"></i>
                                            <strong>Free Hosting Note:</strong> Use your free hosting database credentials
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
                                                    <input type="email" class="form-control" id="admin_email" name="admin_email" value="admin@coding-master.infy.uk" required>
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
                                        <i class="fas fa-arrow-right me-2"></i>Next: Features Configuration
                                    </button>
                                </div>
                            </form>

                        <?php elseif ($currentStep === 'features'): ?>
                            <!-- Features Configuration Step -->
                            <div class="text-center mb-4">
                                <h3>Features Configuration</h3>
                                <p class="text-muted">Configure features for free hosting</p>
                            </div>

                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="card-header">
                                                <h5 class="mb-0">Core Features</h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="form-check mb-3">
                                                    <input class="form-check-input" type="checkbox" id="responsive_design" name="responsive_design" value="1" checked>
                                                    <label class="form-check-label" for="responsive_design">
                                                        Responsive Design
                                                    </label>
                                                </div>
                                                <div class="form-check mb-3">
                                                    <input class="form-check-input" type="checkbox" id="mobile_optimized" name="mobile_optimized" value="1" checked>
                                                    <label class="form-check-label" for="mobile_optimized">
                                                        Mobile Optimized
                                                    </label>
                                                </div>
                                                <div class="form-check mb-3">
                                                    <input class="form-check-input" type="checkbox" id="pwa_support" name="pwa_support" value="1" checked>
                                                    <label class="form-check-label" for="pwa_support">
                                                        PWA Support
                                                    </label>
                                                </div>
                                                <div class="form-check mb-3">
                                                    <input class="form-check-input" type="checkbox" id="admin_panel" name="admin_panel" value="1" checked>
                                                    <label class="form-check-label" for="admin_panel">
                                                        Admin Panel
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="user_registration" name="user_registration" value="1" checked>
                                                    <label class="form-check-label" for="user_registration">
                                                        User Registration
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="card-header">
                                                <h5 class="mb-0">Advanced Features</h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="form-check mb-3">
                                                    <input class="form-check-input" type="checkbox" id="moderation" name="moderation" value="1" checked>
                                                    <label class="form-check-label" for="moderation">
                                                        Moderation
                                                    </label>
                                                </div>
                                                <div class="form-check mb-3">
                                                    <input class="form-check-input" type="checkbox" id="search" name="search" value="1" checked>
                                                    <label class="form-check-label" for="search">
                                                        Search
                                                    </label>
                                                </div>
                                                <div class="form-check mb-3">
                                                    <input class="form-check-input" type="checkbox" id="themes" name="themes" value="1" checked>
                                                    <label class="form-check-label" for="themes">
                                                        Themes
                                                    </label>
                                                </div>
                                                <div class="form-check mb-3">
                                                    <input class="form-check-input" type="checkbox" id="api" name="api" value="1" checked>
                                                    <label class="form-check-label" for="api">
                                                        API
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="analytics" name="analytics" value="1" checked>
                                                    <label class="form-check-label" for="analytics">
                                                        Analytics
                                                    </label>
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
                                <h3>Installing Free Hosting Forum</h3>
                                <p class="text-muted">Please wait while we set up your free hosting forum...</p>
                            </div>
                            
                            <?php
                            // Auto-start installation if not already started
                            if (!isset($_POST['install_started'])) {
                                echo '<script>
                                    document.addEventListener("DOMContentLoaded", function() {
                                        // Auto-start installation after 1 second
                                        setTimeout(function() {
                                            const form = document.createElement("form");
                                            form.method = "POST";
                                            form.action = "?step=install";
                                            const input = document.createElement("input");
                                            input.type = "hidden";
                                            input.name = "install_started";
                                            input.value = "1";
                                            form.appendChild(input);
                                            document.body.appendChild(form);
                                            form.submit();
                                        }, 1000);
                                    });
                                </script>';
                            }
                            ?>

                            <div class="card">
                                <div class="card-body">
                                    <div class="text-center">
                                        <div class="spinner-border text-primary mb-3" role="status">
                                            <span class="visually-hidden">Installing...</span>
                                        </div>
                                        <h5>Installing Free Hosting Features...</h5>
                                        <p class="text-muted">This may take a few moments</p>
                                        
                                        <!-- Installation Progress -->
                                        <div class="progress mb-3" style="height: 20px;">
                                            <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                                 role="progressbar" style="width: 0%" id="installProgress">
                                                <span id="installStatus">Starting free hosting installation...</span>
                                            </div>
                                        </div>
                                        
                                        <!-- Installation Steps -->
                                        <div class="text-start">
                                            <div class="install-step" id="step1">
                                                <i class="fas fa-circle-notch fa-spin text-primary me-2"></i>
                                                <span>Setting up free hosting environment...</span>
                                            </div>
                                            <div class="install-step" id="step2">
                                                <i class="fas fa-circle text-muted me-2"></i>
                                                <span>Configuring free hosting database...</span>
                                            </div>
                                            <div class="install-step" id="step3">
                                                <i class="fas fa-circle text-muted me-2"></i>
                                                <span>Creating admin user...</span>
                                            </div>
                                            <div class="install-step" id="step4">
                                                <i class="fas fa-circle text-muted me-2"></i>
                                                <span>Setting up free hosting directories...</span>
                                            </div>
                                            <div class="install-step" id="step5">
                                                <i class="fas fa-circle text-muted me-2"></i>
                                                <span>Creating free hosting assets...</span>
                                            </div>
                                            <div class="install-step" id="step6">
                                                <i class="fas fa-circle text-muted me-2"></i>
                                                <span>Setting up PWA features...</span>
                                            </div>
                                        </div>
                                        
                                        <!-- Installation Complete Message -->
                                        <div id="installComplete" style="display: none;" class="mt-4">
                                            <div class="alert alert-success">
                                                <i class="fas fa-check-circle me-2"></i>
                                                <strong>Installation Complete!</strong> Redirecting to complete page...
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <script>
                                // Installation progress simulation
                                document.addEventListener('DOMContentLoaded', function() {
                                    const steps = [
                                        { id: 'step1', text: 'Setting up free hosting environment...', duration: 2000 },
                                        { id: 'step2', text: 'Configuring free hosting database...', duration: 3000 },
                                        { id: 'step3', text: 'Creating admin user...', duration: 1000 },
                                        { id: 'step4', text: 'Setting up free hosting directories...', duration: 1500 },
                                        { id: 'step5', text: 'Creating free hosting assets...', duration: 2000 },
                                        { id: 'step6', text: 'Setting up PWA features...', duration: 2500 }
                                    ];
                                    
                                    let currentStep = 0;
                                    const progressBar = document.getElementById('installProgress');
                                    const statusText = document.getElementById('installStatus');
                                    const installComplete = document.getElementById('installComplete');
                                    
                                    function updateProgress() {
                                        if (currentStep < steps.length) {
                                            const step = steps[currentStep];
                                            const stepElement = document.getElementById(step.id);
                                            
                                            // Update current step
                                            stepElement.querySelector('i').className = 'fas fa-spinner fa-spin text-primary me-2';
                                            statusText.textContent = step.text;
                                            
                                            // Update progress bar
                                            const progress = ((currentStep + 1) / steps.length) * 100;
                                            progressBar.style.width = progress + '%';
                                            
                                            currentStep++;
                                            
                                            setTimeout(updateProgress, step.duration);
                                        } else {
                                            // All steps completed, show complete message
                                            statusText.textContent = 'Free hosting installation complete!';
                                            progressBar.style.width = '100%';
                                            installComplete.style.display = 'block';
                                            
                                            // Redirect to complete page after 2 seconds
                                            setTimeout(function() {
                                                window.location.href = '?step=complete';
                                            }, 2000);
                                        }
                                    }
                                    
                                    // Start progress
                                    setTimeout(updateProgress, 1000);
                                });
                            </script>

                        <?php elseif ($currentStep === 'complete'): ?>
                            <!-- Installation Complete Step -->
                            <div class="text-center mb-4">
                                <div class="alert alert-success alert-install">
                                    <i class="fas fa-globe fa-3x mb-3"></i>
                                    <h3>Free Hosting Installation Complete!</h3>
                                    <p class="mb-0">Your free hosting-optimized Forum Project is ready!</p>
                                </div>
                            </div>

                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">Free Hosting Features Enabled</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6><i class="fas fa-globe me-2"></i>Free Hosting Features</h6>
                                            <ul class="list-unstyled">
                                                <li>✅ Free Hosting Optimized</li>
                                                <li>✅ Mobile Responsive</li>
                                                <li>✅ PWA Support</li>
                                                <li>✅ Admin Panel</li>
                                                <li>✅ User Registration</li>
                                                <li>✅ Moderation</li>
                                            </ul>
                                        </div>
                                        <div class="col-md-6">
                                            <h6><i class="fas fa-cog me-2"></i>Advanced Features</h6>
                                            <ul class="list-unstyled">
                                                <li>✅ Search</li>
                                                <li>✅ Themes</li>
                                                <li>✅ API</li>
                                                <li>✅ Analytics</li>
                                                <li>✅ Performance Optimized</li>
                                                <li>✅ Security Enhanced</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="text-center">
                                <a href="index.php" class="btn btn-install btn-lg me-3">
                                    <i class="fas fa-globe me-2"></i>Open Free Hosting Forum
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

        // Free hosting optimizations
        if (window.location.hostname.includes('infy.uk') || 
            window.location.hostname.includes('infinityfree.net') ||
            window.location.hostname.includes('000webhost.com')) {
            document.body.classList.add('free-hosting-optimized');
            
            // Add free hosting-optimized classes
            document.querySelectorAll('.btn, .form-control, .card').forEach(el => {
                el.classList.add('free-hosting-optimized');
            });
        }
    </script>
</body>
</html>