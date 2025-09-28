<?php
declare(strict_types=1);

// Environment detection
$isProduction = ($_SERVER['SERVER_NAME'] ?? 'localhost') !== 'localhost';
$isDevelopment = !$isProduction;

// Database configuration
define('DB_PATH', __DIR__ . '/storage/forum.sqlite');
define('DB_DSN', 'sqlite:' . DB_PATH);

// Site settings
define('SITE_NAME', 'Universal Forum Hub');
define('SITE_DESCRIPTION', 'A comprehensive, modern forum platform for all communities');
define('SITE_URL', $isDevelopment ? 'http://localhost/my_forum' : 'https://yourdomain.com');
define('DEFAULT_LANG', 'en');
define('TIMEZONE', 'Asia/Dhaka');
define('SITE_KEYWORDS', 'forum, community, discussion, chat, social, universal');

// Advanced Security settings
define('CSRF_TOKEN_NAME', 'csrf_token');
define('SESSION_LIFETIME', 3600 * 24 * 30); // 30 days
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes
define('ENABLE_2FA', true);
define('ENABLE_CAPTCHA', true);
define('ENABLE_EMAIL_VERIFICATION', true);

// File upload settings
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('MAX_IMAGE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']);
define('ALLOWED_DOCUMENT_TYPES', ['pdf', 'doc', 'docx', 'txt', 'rtf', 'odt']);
define('ALLOWED_VIDEO_TYPES', ['mp4', 'avi', 'mov', 'wmv', 'flv']);
define('ALLOWED_AUDIO_TYPES', ['mp3', 'wav', 'ogg', 'm4a']);

// Pagination settings
define('POSTS_PER_PAGE', 25);
define('THREADS_PER_PAGE', 20);
define('USERS_PER_PAGE', 30);
define('MESSAGES_PER_PAGE', 50);
define('NOTIFICATIONS_PER_PAGE', 20);

// Cache settings
define('CACHE_ENABLED', true);
define('CACHE_LIFETIME', 3600); // 1 hour
define('CACHE_PREFIX', 'forum_');

// Real-time settings
define('ENABLE_REAL_TIME', true);
define('WEBSOCKET_PORT', 8080);
define('PUSHER_APP_ID', 'your_pusher_app_id');
define('PUSHER_KEY', 'your_pusher_key');
define('PUSHER_SECRET', 'your_pusher_secret');

// Email settings
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your_email@gmail.com');
define('SMTP_PASSWORD', 'your_app_password');
define('SMTP_ENCRYPTION', 'tls');
define('FROM_EMAIL', 'noreply@yourdomain.com');
define('FROM_NAME', 'Universal Forum Hub');

// Social login settings
define('GOOGLE_CLIENT_ID', 'your_google_client_id');
define('GOOGLE_CLIENT_SECRET', 'your_google_client_secret');
define('FACEBOOK_APP_ID', 'your_facebook_app_id');
define('FACEBOOK_APP_SECRET', 'your_facebook_app_secret');
define('TWITTER_API_KEY', 'your_twitter_api_key');
define('TWITTER_API_SECRET', 'your_twitter_api_secret');

// Payment settings (for premium features)
define('STRIPE_PUBLIC_KEY', 'your_stripe_public_key');
define('STRIPE_SECRET_KEY', 'your_stripe_secret_key');
define('PAYPAL_CLIENT_ID', 'your_paypal_client_id');
define('PAYPAL_CLIENT_SECRET', 'your_paypal_client_secret');

// Analytics settings
define('GOOGLE_ANALYTICS_ID', 'your_ga_id');
define('GOOGLE_TAG_MANAGER_ID', 'your_gtm_id');
define('FACEBOOK_PIXEL_ID', 'your_fb_pixel_id');

// Elasticsearch settings
define('ELASTICSEARCH_ENABLED', false);
define('ELASTICSEARCH_HOST', 'localhost');
define('ELASTICSEARCH_PORT', 9200);
define('ELASTICSEARCH_INDEX', 'forum');

// Path constants
define('ROOT_PATH', __DIR__);
define('CORE_PATH', ROOT_PATH . '/core');
define('MODELS_PATH', ROOT_PATH . '/models');
define('CONTROLLERS_PATH', ROOT_PATH . '/controllers');
define('VIEWS_PATH', ROOT_PATH . '/views');
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('UPLOADS_PATH', PUBLIC_PATH . '/uploads');
define('STORAGE_PATH', ROOT_PATH . '/storage');
define('ROUTES_PATH', ROOT_PATH . '/routes');
define('CACHE_PATH', STORAGE_PATH . '/cache');
define('LOGS_PATH', STORAGE_PATH . '/logs');
define('TEMP_PATH', STORAGE_PATH . '/temp');
define('BACKUP_PATH', STORAGE_PATH . '/backups');

// Create necessary directories
$directories = [
    STORAGE_PATH, CACHE_PATH, LOGS_PATH, TEMP_PATH, BACKUP_PATH,
    UPLOADS_PATH . '/avatars', UPLOADS_PATH . '/attachments',
    UPLOADS_PATH . '/images', UPLOADS_PATH . '/videos',
    UPLOADS_PATH . '/audio', UPLOADS_PATH . '/documents',
    UPLOADS_PATH . '/thumbnails', UPLOADS_PATH . '/banners'
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Set timezone
date_default_timezone_set(TIMEZONE);

// Session configuration
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure', $isProduction ? '1' : '0');
ini_set('session.use_strict_mode', '1');
ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
ini_set('session.cookie_samesite', 'Strict');

session_start();

// Error reporting
if ($isDevelopment) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ERROR | E_WARNING | E_PARSE);
}

// Check if installed
if (!file_exists(STORAGE_PATH . '/installed.lock') && basename($_SERVER['PHP_SELF']) !== 'install.php') {
    header('Location: install.php');
    exit;
}

// Auto-load classes with PSR-4 style
spl_autoload_register(function ($class_name) {
    $namespaces = [
        'Core\\' => CORE_PATH . '/',
        'Models\\' => MODELS_PATH . '/',
        'Controllers\\' => CONTROLLERS_PATH . '/',
        'Middleware\\' => ROOT_PATH . '/middleware/',
        'Services\\' => ROOT_PATH . '/services/',
        'Helpers\\' => ROOT_PATH . '/helpers/',
        'Traits\\' => ROOT_PATH . '/traits/',
    ];
    
    foreach ($namespaces as $namespace => $path) {
        if (strpos($class_name, $namespace) === 0) {
            $file = $path . str_replace($namespace, '', $class_name) . '.php';
            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }
    }
    
    // Fallback to old style
    $paths = [CORE_PATH, MODELS_PATH, CONTROLLERS_PATH];
    foreach ($paths as $path) {
        $file = $path . '/' . $class_name . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Include common functions
require_once CORE_PATH . '/Functions.php';

// Initialize database connection
try {
    $pdo = new PDO(DB_DSN);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
    // SQLite optimizations
    $pdo->exec('PRAGMA foreign_keys = ON;');
    $pdo->exec('PRAGMA journal_mode = WAL;');
    $pdo->exec('PRAGMA synchronous = NORMAL;');
    $pdo->exec('PRAGMA cache_size = 20000;');
    $pdo->exec('PRAGMA temp_store = MEMORY;');
    $pdo->exec('PRAGMA mmap_size = 268435456;'); // 256MB
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    if ($isDevelopment) {
        die("Database connection failed: " . $e->getMessage());
    } else {
        die("Service temporarily unavailable. Please try again later.");
    }
}

// CSRF Protection
if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
    $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
}

// Rate limiting
if (!isset($_SESSION['last_request'])) {
    $_SESSION['last_request'] = time();
}

// Security headers
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    
    if ($isProduction) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\' \'unsafe-eval\' https://www.google-analytics.com https://www.googletagmanager.com; style-src \'self\' \'unsafe-inline\' https://fonts.googleapis.com; font-src \'self\' https://fonts.gstatic.com; img-src \'self\' data: https:; connect-src \'self\' https://www.google-analytics.com;');
    }
}

// Initialize services
if (class_exists('Services\\EmailService')) {
    $emailService = new Services\EmailService();
}

if (class_exists('Services\\NotificationService')) {
    $notificationService = new Services\NotificationService();
}

if (class_exists('Services\\CacheService')) {
    $cacheService = new Services\CacheService();
}

// Load site settings from database
try {
    $settings = $pdo->query("SELECT * FROM site_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
    foreach ($settings as $key => $value) {
        if (!defined($key)) {
            define($key, $value);
        }
    }
} catch (PDOException $e) {
    // Settings table might not exist yet
}

// Set maintenance mode
if (file_exists(STORAGE_PATH . '/maintenance.lock')) {
    if (!Auth::isAdmin()) {
        http_response_code(503);
        header('Retry-After: 3600');
        include VIEWS_PATH . '/maintenance.php';
        exit;
    }
}
?>