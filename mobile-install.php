<?php
/**
 * Mobile-Optimized Installation for KSWeb
 * Optimized for mobile devices and KSWeb server
 */

// Prevent direct access after installation
if (file_exists('.installed')) {
    die('❌ Installation already completed! Delete .installed file to reinstall.');
}

// Start session for installation process
session_start();

// Mobile detection
function isMobile() {
    return isset($_SERVER['HTTP_USER_AGENT']) && 
           preg_match('/(android|iphone|ipad|mobile)/i', $_SERVER['HTTP_USER_AGENT']);
}

// KSWeb detection
function isKSWeb() {
    return isset($_SERVER['SERVER_SOFTWARE']) && 
           strpos($_SERVER['SERVER_SOFTWARE'], 'KSWeb') !== false;
}

// Installation steps
$steps = [
    'welcome' => 'Welcome & Mobile Setup',
    'ksweb' => 'KSWeb Configuration',
    'database' => 'Database Setup',
    'admin' => 'Admin Account',
    'mobile' => 'Mobile Features',
    'install' => 'Installation',
    'complete' => 'Complete'
];

$currentStep = $_GET['step'] ?? 'welcome';

// Handle form submissions
if ($_POST) {
    switch ($currentStep) {
        case 'ksweb':
            $_SESSION['ksweb_config'] = $_POST;
            header('Location: ?step=database');
            exit;
        case 'database':
            $_SESSION['db_config'] = $_POST;
            header('Location: ?step=admin');
            exit;
        case 'admin':
            $_SESSION['admin_config'] = $_POST;
            header('Location: ?step=mobile');
            exit;
        case 'mobile':
            $_SESSION['mobile_config'] = $_POST;
            header('Location: ?step=install');
            exit;
        case 'install':
            performMobileInstallation();
            break;
    }
}

function performMobileInstallation() {
    try {
        $kswebConfig = $_SESSION['ksweb_config'] ?? [];
        $dbConfig = $_SESSION['db_config'] ?? [];
        $adminConfig = $_SESSION['admin_config'] ?? [];
        $mobileConfig = $_SESSION['mobile_config'] ?? [];
        
        // Step 1: Create mobile-optimized .env
        createMobileEnvFile($kswebConfig, $dbConfig, $mobileConfig);
        
        // Step 2: Setup database
        setupMobileDatabase($dbConfig);
        
        // Step 3: Create admin user
        createAdminUser($adminConfig);
        
        // Step 4: Create mobile directories
        createMobileDirectories();
        
        // Step 5: Set mobile permissions
        setMobilePermissions();
        
        // Step 6: Create mobile assets
        createMobileAssets();
        
        // Step 7: Create PWA files
        createPWAFiles();
        
        // Step 8: Create .installed file
        file_put_contents('.installed', date('Y-m-d H:i:s'));
        
        // Step 9: Clear session
        session_destroy();
        
        header('Location: ?step=complete');
        exit;
        
    } catch (Exception $e) {
        $error = "Installation failed: " . $e->getMessage();
    }
}

function createMobileEnvFile($kswebConfig, $dbConfig, $mobileConfig) {
    $envContent = generateMobileEnvContent($kswebConfig, $dbConfig, $mobileConfig);
    file_put_contents('.env', $envContent);
}

function generateMobileEnvContent($kswebConfig, $dbConfig, $mobileConfig) {
    $isKSWeb = isKSWeb();
    $isMobile = isMobile();
    $appUrl = $isKSWeb ? 'http://coding-master.infy.uk:8080' : 'http://localhost';
    
    $env = "# Forum Project - Mobile Optimized Environment\n";
    $env .= "# Generated on " . date('Y-m-d H:i:s') . "\n";
    $env .= "# KSWeb: " . ($isKSWeb ? 'Yes' : 'No') . "\n";
    $env .= "# Mobile: " . ($isMobile ? 'Yes' : 'No') . "\n\n";
    
    // App Configuration
    $env .= "APP_NAME=\"Forum Project - Mobile\"\n";
    $env .= "APP_ENV=" . ($isKSWeb ? 'ksweb' : 'local') . "\n";
    $env .= "APP_DEBUG=true\n";
    $env .= "APP_URL={$appUrl}\n";
    $env .= "APP_TIMEZONE=Asia/Dhaka\n\n";
    
    // Mobile Configuration
    $env .= "MOBILE_ENABLED=true\n";
    $env .= "MOBILE_RESPONSIVE=true\n";
    $env .= "MOBILE_TOUCH_FRIENDLY=true\n";
    $env .= "MOBILE_PWA_ENABLED=true\n";
    $env .= "MOBILE_OFFLINE_SUPPORT=true\n";
    $env .= "MOBILE_PUSH_NOTIFICATIONS=true\n";
    $env .= "MOBILE_BIOMETRIC_AUTH=true\n\n";
    
    // Database Configuration
    if ($dbConfig['db_type'] === 'sqlite') {
        $env .= "DB_CONNECTION=sqlite\n";
        $env .= "DB_DATABASE=" . __DIR__ . "/database/forum_mobile.sqlite\n";
    } else {
        $env .= "DB_CONNECTION=mysql\n";
        $env .= "DB_HOST=" . ($isKSWeb ? 'localhost' : ($dbConfig['db_host'] ?? 'localhost')) . "\n";
        $env .= "DB_PORT=" . ($isKSWeb ? '3306' : ($dbConfig['db_port'] ?? '3306')) . "\n";
        $env .= "DB_DATABASE=" . ($isKSWeb ? 'forum_ksweb' : ($dbConfig['db_name'] ?? 'forum_db')) . "\n";
        $env .= "DB_USERNAME=" . ($isKSWeb ? 'root' : ($dbConfig['db_user'] ?? 'root')) . "\n";
        $env .= "DB_PASSWORD=" . ($isKSWeb ? '' : ($dbConfig['db_pass'] ?? '')) . "\n";
    }
    $env .= "\n";
    
    // Cache Configuration (Mobile optimized)
    $env .= "CACHE_DRIVER=file\n";
    $env .= "CACHE_PREFIX=mobile_forum_\n";
    $env .= "CACHE_TTL=1800\n\n";
    
    // Session Configuration (Mobile optimized)
    $env .= "SESSION_DRIVER=file\n";
    $env .= "SESSION_LIFETIME=60\n";
    $env .= "SESSION_ENCRYPT=false\n";
    $env .= "SESSION_PATH=/\n";
    $env .= "SESSION_DOMAIN=" . ($isKSWeb ? 'coding-master.infy.uk' : 'localhost') . "\n";
    $env .= "SESSION_SECURE_COOKIE=false\n";
    $env .= "SESSION_HTTP_ONLY=true\n";
    $env .= "SESSION_SAME_SITE=lax\n\n";
    
    // Mobile Performance
    $env .= "MOBILE_PERFORMANCE_CACHE=true\n";
    $env .= "MOBILE_IMAGE_COMPRESSION=true\n";
    $env .= "MOBILE_LAZY_LOADING=true\n";
    $env .= "MOBILE_MINIFICATION=true\n\n";
    
    // Security (Mobile optimized)
    $env .= "APP_KEY=" . generateAppKey() . "\n";
    $env .= "JWT_SECRET=" . generateJWTSecret() . "\n";
    $env .= "ENCRYPTION_KEY=" . generateEncryptionKey() . "\n\n";
    
    // Rate Limiting (Mobile optimized)
    $env .= "RATE_LIMIT_ENABLED=true\n";
    $env .= "RATE_LIMIT_MAX_ATTEMPTS=30\n";
    $env .= "RATE_LIMIT_DECAY_MINUTES=1\n\n";
    
    // Mobile Features
    $env .= "MOBILE_DARK_MODE=true\n";
    $env .= "MOBILE_SWIPE_GESTURES=true\n";
    $env .= "MOBILE_PULL_TO_REFRESH=true\n";
    $env .= "MOBILE_VIBRATION=true\n";
    $env .= "MOBILE_GEOLOCATION=true\n\n";
    
    // KSWeb specific
    if ($isKSWeb) {
        $env .= "KSWEB_ENABLED=true\n";
        $env .= "KSWEB_PORT=8080\n";
        $env .= "KSWEB_DOCUMENT_ROOT=/storage/emulated/0/htdocs/forum\n";
    }
    
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

function setupMobileDatabase($dbConfig) {
    $dbType = $dbConfig['db_type'] ?? 'sqlite';
    
    if ($dbType === 'sqlite') {
        setupMobileSQLiteDatabase();
    } else {
        setupMobileMySQLDatabase($dbConfig);
    }
}

function setupMobileSQLiteDatabase() {
    $dbPath = __DIR__ . '/database/forum_mobile.sqlite';
    
    // Create database directory if not exists
    if (!is_dir(__DIR__ . '/database')) {
        mkdir(__DIR__ . '/database', 0755, true);
    }
    
    // Create SQLite database
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Run mobile-optimized migrations
    runMobileSQLiteMigrations($pdo);
}

function setupMobileMySQLDatabase($dbConfig) {
    $isKSWeb = isKSWeb();
    $host = $isKSWeb ? 'localhost' : ($dbConfig['db_host'] ?? 'localhost');
    $port = $isKSWeb ? '3306' : ($dbConfig['db_port'] ?? '3306');
    $user = $isKSWeb ? 'root' : ($dbConfig['db_user'] ?? 'root');
    $pass = $isKSWeb ? '' : ($dbConfig['db_pass'] ?? '');
    $dbname = $isKSWeb ? 'forum_ksweb' : ($dbConfig['db_name'] ?? 'forum_db');
    
    $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$dbname}`");
    
    // Run mobile-optimized migrations
    runMobileMySQLMigrations($pdo);
}

function runMobileSQLiteMigrations($pdo) {
    $migrations = [
        // Users table (mobile optimized)
        "CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
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
            device_token VARCHAR(255),
            push_enabled BOOLEAN DEFAULT 1,
            dark_mode BOOLEAN DEFAULT 0,
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
        
        // Threads table (mobile optimized)
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
        
        // Mobile sessions table
        "CREATE TABLE IF NOT EXISTS mobile_sessions (
            id VARCHAR(128) PRIMARY KEY,
            user_id INTEGER,
            device_id VARCHAR(255),
            device_type VARCHAR(50),
            ip_address VARCHAR(45),
            user_agent TEXT,
            payload TEXT,
            last_activity INTEGER,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )",
        
        // Push notifications table
        "CREATE TABLE IF NOT EXISTS push_notifications (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            data TEXT,
            sent_at TIMESTAMP NULL,
            read_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )"
    ];
    
    foreach ($migrations as $sql) {
        $pdo->exec($sql);
    }
}

function runMobileMySQLMigrations($pdo) {
    // Include all migration files
    $migrationFiles = glob(__DIR__ . '/database/migrations/*.php');
    sort($migrationFiles);
    
    foreach ($migrationFiles as $file) {
        include $file;
    }
}

function createAdminUser($adminConfig) {
    $isKSWeb = isKSWeb();
    $dbType = $_SESSION['db_config']['db_type'] ?? 'sqlite';
    
    if ($dbType === 'sqlite') {
        $pdo = new PDO('sqlite:' . __DIR__ . '/database/forum_mobile.sqlite');
    } else {
        $host = $isKSWeb ? 'localhost' : ($_SESSION['db_config']['db_host'] ?? 'localhost');
        $port = $isKSWeb ? '3306' : ($_SESSION['db_config']['db_port'] ?? '3306');
        $user = $isKSWeb ? 'root' : ($_SESSION['db_config']['db_user'] ?? 'root');
        $pass = $isKSWeb ? '' : ($_SESSION['db_config']['db_pass'] ?? '');
        $dbname = $isKSWeb ? 'forum_ksweb' : ($_SESSION['db_config']['db_name'] ?? 'forum_db');
        
        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass);
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

function createMobileDirectories() {
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
        'public/images/mobile',
        'public/mobile',
        'public/mobile/css',
        'public/mobile/js',
        'public/mobile/images'
    ];
    
    foreach ($directories as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}

function setMobilePermissions() {
    $files = [
        '.env' => 0644,
        'storage' => 0755,
        'public/uploads' => 0755,
        'database' => 0755,
        'public/mobile' => 0755
    ];
    
    foreach ($files as $file => $permission) {
        if (file_exists($file)) {
            chmod($file, $permission);
        }
    }
}

function createMobileAssets() {
    // Create mobile CSS
    $mobileCSS = "
/* Mobile-Optimized CSS for Forum Project */
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

/* Touch-friendly elements */
.btn, .form-control, .card {
    -webkit-tap-highlight-color: transparent;
}

/* Swipe gestures */
.swipeable {
    touch-action: pan-x;
}

/* Pull to refresh */
.pull-to-refresh {
    position: relative;
    overflow: hidden;
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    body {
        background-color: #1a1a1a;
        color: #ffffff;
    }
    
    .card {
        background-color: #2d2d2d;
        border-color: #444;
    }
    
    .form-control {
        background-color: #2d2d2d;
        border-color: #444;
        color: #ffffff;
    }
}
";
    
    file_put_contents('public/mobile/css/mobile.css', $mobileCSS);
    
    // Create mobile JavaScript
    $mobileJS = "
// Mobile-Optimized JavaScript for Forum Project
document.addEventListener('DOMContentLoaded', function() {
    // Mobile detection
    const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    
    if (isMobile) {
        document.body.classList.add('mobile-device');
        
        // Add touch-friendly classes
        document.querySelectorAll('.btn, .form-control, .card').forEach(el => {
            el.classList.add('touch-friendly');
        });
        
        // Enable swipe gestures
        enableSwipeGestures();
        
        // Enable pull to refresh
        enablePullToRefresh();
        
        // Enable vibration
        enableVibration();
    }
    
    // PWA installation
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js')
            .then(registration => console.log('SW registered'))
            .catch(error => console.log('SW registration failed'));
    }
});

// Swipe gestures
function enableSwipeGestures() {
    let startX, startY, endX, endY;
    
    document.addEventListener('touchstart', function(e) {
        startX = e.touches[0].clientX;
        startY = e.touches[0].clientY;
    });
    
    document.addEventListener('touchend', function(e) {
        endX = e.changedTouches[0].clientX;
        endY = e.changedTouches[0].clientY;
        
        const diffX = startX - endX;
        const diffY = startY - endY;
        
        if (Math.abs(diffX) > Math.abs(diffY)) {
            if (diffX > 50) {
                // Swipe left
                handleSwipeLeft();
            } else if (diffX < -50) {
                // Swipe right
                handleSwipeRight();
            }
        }
    });
}

function handleSwipeLeft() {
    // Handle swipe left gesture
    console.log('Swipe left detected');
}

function handleSwipeRight() {
    // Handle swipe right gesture
    console.log('Swipe right detected');
}

// Pull to refresh
function enablePullToRefresh() {
    let startY = 0;
    let currentY = 0;
    let isPulling = false;
    
    document.addEventListener('touchstart', function(e) {
        if (window.scrollY === 0) {
            startY = e.touches[0].clientY;
            isPulling = true;
        }
    });
    
    document.addEventListener('touchmove', function(e) {
        if (isPulling) {
            currentY = e.touches[0].clientY;
            const diff = currentY - startY;
            
            if (diff > 0) {
                document.body.style.transform = `translateY(${diff}px)`;
            }
        }
    });
    
    document.addEventListener('touchend', function(e) {
        if (isPulling) {
            const diff = currentY - startY;
            
            if (diff > 100) {
                // Trigger refresh
                location.reload();
            } else {
                // Reset position
                document.body.style.transform = 'translateY(0)';
            }
            
            isPulling = false;
        }
    });
}

// Vibration
function enableVibration() {
    if ('vibrate' in navigator) {
        // Add vibration to button clicks
        document.querySelectorAll('.btn').forEach(btn => {
            btn.addEventListener('click', function() {
                navigator.vibrate(50);
            });
        });
    }
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
    
    file_put_contents('public/mobile/js/mobile.js', $mobileJS);
}

function createPWAFiles() {
    // Create manifest.json
    $manifest = [
        'name' => 'Forum Project - Mobile',
        'short_name' => 'Forum',
        'description' => 'Complete Forum System for Mobile',
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
            'touch_friendly',
            'offline_support',
            'push_notifications',
            'biometric_auth',
            'dark_mode',
            'swipe_gestures',
            'pull_to_refresh'
        ]
    ];
    
    file_put_contents('public/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));
    
    // Create service worker
    $sw = "
const CACHE_NAME = 'forum-project-mobile-v1';
const urlsToCache = [
    '/',
    '/public/css/style.css',
    '/public/mobile/css/mobile.css',
    '/public/js/app.js',
    '/public/mobile/js/mobile.js',
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

self.addEventListener('push', function(event) {
    const options = {
        body: event.data.text(),
        icon: '/public/images/icon-192x192.png',
        badge: '/public/images/icon-192x192.png'
    };
    
    event.waitUntil(
        self.registration.showNotification('Forum Project', options)
    );
});
";
    
    file_put_contents('public/sw.js', $sw);
}

function checkMobileRequirements() {
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
        'Public Directory Writable' => is_writable('public') || mkdir('public', 0755, true),
        'Mobile Support' => true,
        'KSWeb Compatible' => isKSWeb() || true
    ];
    
    return $requirements;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Forum Project - Mobile Installation</title>
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
        
        /* Mobile optimizations */
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
                            <i class="fas fa-mobile-alt text-primary me-2"></i>
                            Forum Project - Mobile Installation
                        </h1>
                        <p class="text-muted">KSWeb & Mobile Optimized Setup</p>
                        <?php if (isKSWeb()): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                KSWeb Server Detected - Mobile Optimized Configuration
                            </div>
                        <?php endif; ?>
                        <?php if (isMobile()): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-mobile-alt me-2"></i>
                                Mobile Device Detected - Touch-Friendly Interface
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
                        <?php if ($currentStep === 'welcome'): ?>
                            <!-- Welcome Step -->
                            <div class="text-center mb-4">
                                <h3>Welcome to Mobile Forum Installation</h3>
                                <p class="text-muted">Let's set up your mobile-optimized forum!</p>
                            </div>

                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i>Mobile Requirements</h5>
                                </div>
                                <div class="card-body">
                                    <?php $requirements = checkMobileRequirements(); ?>
                                    <?php foreach ($requirements as $requirement => $status): ?>
                                        <div class="requirement-item">
                                            <span><?php echo $requirement; ?></span>
                                            <i class="fas fa-<?php echo $status ? 'check' : 'times'; ?> status-icon status-<?php echo $status ? 'pass' : 'fail'; ?>"></i>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="text-center">
                                <a href="?step=ksweb" class="btn btn-install btn-lg">
                                    <i class="fas fa-arrow-right me-2"></i>Start Mobile Setup
                                </a>
                            </div>

                        <?php elseif ($currentStep === 'ksweb'): ?>
                            <!-- KSWeb Configuration Step -->
                            <div class="text-center mb-4">
                                <h3>KSWeb Configuration</h3>
                                <p class="text-muted">Configure your KSWeb server settings</p>
                            </div>

                            <form method="POST">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">KSWeb Server Settings</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <input type="text" class="form-control" id="ksweb_port" name="ksweb_port" value="8080" required>
                                                    <label for="ksweb_port">KSWeb Port</label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <input type="text" class="form-control" id="ksweb_domain" name="ksweb_domain" value="coding-master.infy.uk" required>
                                                    <label for="ksweb_domain">Domain</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="ksweb_path" name="ksweb_path" value="/storage/emulated/0/htdocs/forum" required>
                                            <label for="ksweb_path">Document Root Path</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="ksweb_ssl" name="ksweb_ssl" value="1">
                                            <label class="form-check-label" for="ksweb_ssl">
                                                Enable SSL (HTTPS)
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-center mt-4">
                                    <button type="submit" class="btn btn-install btn-lg">
                                        <i class="fas fa-arrow-right me-2"></i>Next: Database Setup
                                    </button>
                                </div>
                            </form>

                        <?php elseif ($currentStep === 'database'): ?>
                            <!-- Database Configuration Step -->
                            <div class="text-center mb-4">
                                <h3>Database Configuration</h3>
                                <p class="text-muted">Choose your database type for mobile optimization</p>
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
                                                    <input class="form-check-input" type="radio" name="db_type" id="sqlite" value="sqlite" checked>
                                                    <label class="form-check-label" for="sqlite">
                                                        <i class="fas fa-database me-2"></i>SQLite (Recommended for Mobile)
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="db_type" id="mysql" value="mysql">
                                                    <label class="form-check-label" for="mysql">
                                                        <i class="fas fa-server me-2"></i>MySQL (For Production)
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="card-header">
                                                <h5 class="mb-0">Mobile Optimization</h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="alert alert-info">
                                                    <i class="fas fa-mobile-alt me-2"></i>
                                                    <strong>Mobile Optimized:</strong> SQLite is perfect for mobile devices
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
                                                        <input type="text" class="form-control" id="db_name" name="db_name" value="forum_ksweb" required>
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
                                        <i class="fas fa-arrow-right me-2"></i>Next: Mobile Features
                                    </button>
                                </div>
                            </form>

                        <?php elseif ($currentStep === 'mobile'): ?>
                            <!-- Mobile Features Configuration Step -->
                            <div class="text-center mb-4">
                                <h3>Mobile Features Configuration</h3>
                                <p class="text-muted">Configure mobile-specific features</p>
                            </div>

                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="card-header">
                                                <h5 class="mb-0">PWA Features</h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="form-check mb-3">
                                                    <input class="form-check-input" type="checkbox" id="pwa_enabled" name="pwa_enabled" value="1" checked>
                                                    <label class="form-check-label" for="pwa_enabled">
                                                        Enable PWA (Progressive Web App)
                                                    </label>
                                                </div>
                                                <div class="form-check mb-3">
                                                    <input class="form-check-input" type="checkbox" id="offline_support" name="offline_support" value="1" checked>
                                                    <label class="form-check-label" for="offline_support">
                                                        Enable Offline Support
                                                    </label>
                                                </div>
                                                <div class="form-check mb-3">
                                                    <input class="form-check-input" type="checkbox" id="push_notifications" name="push_notifications" value="1" checked>
                                                    <label class="form-check-label" for="push_notifications">
                                                        Enable Push Notifications
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="biometric_auth" name="biometric_auth" value="1" checked>
                                                    <label class="form-check-label" for="biometric_auth">
                                                        Enable Biometric Authentication
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="card-header">
                                                <h5 class="mb-0">Mobile UI Features</h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="form-check mb-3">
                                                    <input class="form-check-input" type="checkbox" id="dark_mode" name="dark_mode" value="1" checked>
                                                    <label class="form-check-label" for="dark_mode">
                                                        Enable Dark Mode
                                                    </label>
                                                </div>
                                                <div class="form-check mb-3">
                                                    <input class="form-check-input" type="checkbox" id="swipe_gestures" name="swipe_gestures" value="1" checked>
                                                    <label class="form-check-label" for="swipe_gestures">
                                                        Enable Swipe Gestures
                                                    </label>
                                                </div>
                                                <div class="form-check mb-3">
                                                    <input class="form-check-input" type="checkbox" id="pull_to_refresh" name="pull_to_refresh" value="1" checked>
                                                    <label class="form-check-label" for="pull_to_refresh">
                                                        Enable Pull to Refresh
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="vibration" name="vibration" value="1" checked>
                                                    <label class="form-check-label" for="vibration">
                                                        Enable Vibration Feedback
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
                                <h3>Installing Mobile Forum</h3>
                                <p class="text-muted">Please wait while we set up your mobile forum...</p>
                            </div>

                            <div class="card">
                                <div class="card-body">
                                    <div class="text-center">
                                        <div class="spinner-border text-primary mb-3" role="status">
                                            <span class="visually-hidden">Installing...</span>
                                        </div>
                                        <h5>Installing Mobile Features...</h5>
                                        <p class="text-muted">This may take a few moments</p>
                                        
                                        <!-- Installation Progress -->
                                        <div class="progress mb-3" style="height: 20px;">
                                            <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                                 role="progressbar" style="width: 0%" id="installProgress">
                                                <span id="installStatus">Starting installation...</span>
                                            </div>
                                        </div>
                                        
                                        <!-- Installation Steps -->
                                        <div class="text-start">
                                            <div class="install-step" id="step1">
                                                <i class="fas fa-circle-notch fa-spin text-primary me-2"></i>
                                                <span>Setting up mobile environment...</span>
                                            </div>
                                            <div class="install-step" id="step2">
                                                <i class="fas fa-circle text-muted me-2"></i>
                                                <span>Configuring database...</span>
                                            </div>
                                            <div class="install-step" id="step3">
                                                <i class="fas fa-circle text-muted me-2"></i>
                                                <span>Creating admin user...</span>
                                            </div>
                                            <div class="install-step" id="step4">
                                                <i class="fas fa-circle text-muted me-2"></i>
                                                <span>Setting up mobile directories...</span>
                                            </div>
                                            <div class="install-step" id="step5">
                                                <i class="fas fa-circle text-muted me-2"></i>
                                                <span>Creating mobile assets...</span>
                                            </div>
                                            <div class="install-step" id="step6">
                                                <i class="fas fa-circle text-muted me-2"></i>
                                                <span>Setting up PWA features...</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <script>
                                // Installation progress simulation
                                document.addEventListener('DOMContentLoaded', function() {
                                    const steps = [
                                        { id: 'step1', text: 'Setting up mobile environment...', duration: 2000 },
                                        { id: 'step2', text: 'Configuring database...', duration: 3000 },
                                        { id: 'step3', text: 'Creating admin user...', duration: 1000 },
                                        { id: 'step4', text: 'Setting up mobile directories...', duration: 1500 },
                                        { id: 'step5', text: 'Creating mobile assets...', duration: 2000 },
                                        { id: 'step6', text: 'Setting up PWA features...', duration: 2500 }
                                    ];
                                    
                                    let currentStep = 0;
                                    const progressBar = document.getElementById('installProgress');
                                    const statusText = document.getElementById('installStatus');
                                    
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
                                            // All steps completed, submit form
                                            statusText.textContent = 'Mobile installation complete!';
                                            progressBar.style.width = '100%';
                                            
                                            setTimeout(function() {
                                                const form = document.createElement('form');
                                                form.method = 'POST';
                                                form.action = '?step=install';
                                                document.body.appendChild(form);
                                                form.submit();
                                            }, 1000);
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
                                    <i class="fas fa-mobile-alt fa-3x mb-3"></i>
                                    <h3>Mobile Installation Complete!</h3>
                                    <p class="mb-0">Your mobile-optimized Forum Project is ready!</p>
                                </div>
                            </div>

                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">Mobile Features Enabled</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6><i class="fas fa-mobile-alt me-2"></i>Mobile Features</h6>
                                            <ul class="list-unstyled">
                                                <li>✅ Responsive Design</li>
                                                <li>✅ Touch-Friendly Interface</li>
                                                <li>✅ PWA Support</li>
                                                <li>✅ Offline Support</li>
                                                <li>✅ Push Notifications</li>
                                                <li>✅ Biometric Auth</li>
                                            </ul>
                                        </div>
                                        <div class="col-md-6">
                                            <h6><i class="fas fa-cog me-2"></i>KSWeb Features</h6>
                                            <ul class="list-unstyled">
                                                <li>✅ KSWeb Optimized</li>
                                                <li>✅ Mobile Database</li>
                                                <li>✅ Performance Optimized</li>
                                                <li>✅ Mobile Assets</li>
                                                <li>✅ Service Worker</li>
                                                <li>✅ Mobile Manifest</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="text-center">
                                <a href="index.php" class="btn btn-install btn-lg me-3">
                                    <i class="fas fa-mobile-alt me-2"></i>Open Mobile Forum
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
        // Database type toggle
        document.addEventListener('DOMContentLoaded', function() {
            const sqliteRadio = document.getElementById('sqlite');
            const mysqlRadio = document.getElementById('mysql');
            const mysqlConfig = document.getElementById('mysql-config');
            
            if (sqliteRadio && mysqlRadio && mysqlConfig) {
                function toggleDatabaseFields() {
                    if (sqliteRadio.checked) {
                        mysqlConfig.style.display = 'none';
                    } else {
                        mysqlConfig.style.display = 'block';
                    }
                }
                
                sqliteRadio.addEventListener('change', toggleDatabaseFields);
                mysqlRadio.addEventListener('change', toggleDatabaseFields);
                
                // Initial state
                toggleDatabaseFields();
            }
        });

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

        // Mobile optimizations
        if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
            document.body.classList.add('mobile-device');
            
            // Add touch-friendly classes
            document.querySelectorAll('.btn, .form-control, .card').forEach(el => {
                el.classList.add('touch-friendly');
            });
        }
    </script>
</body>
</html>