<?php
declare(strict_types=1);

// Environment detection
$isProduction = ($_SERVER['SERVER_NAME'] ?? 'localhost') !== 'localhost';
$isDevelopment = !$isProduction;

// Database configuration
define('DB_PATH', __DIR__ . '/storage/forum.sqlite');
define('DB_DSN', 'sqlite:' . DB_PATH);

// Site settings
define('SITE_NAME', 'Coding Master Forum');
define('SITE_DESCRIPTION', 'A modern, secure forum platform');
define('SITE_URL', $isDevelopment ? 'http://localhost/my_forum' : 'https://yourdomain.com');
define('DEFAULT_LANG', 'en');
define('TIMEZONE', 'Asia/Dhaka');

// Security settings
define('CSRF_TOKEN_NAME', 'csrf_token');
define('SESSION_LIFETIME', 3600 * 24 * 7); // 7 days
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// File upload settings
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('ALLOWED_DOCUMENT_TYPES', ['pdf', 'doc', 'docx', 'txt']);

// Pagination settings
define('POSTS_PER_PAGE', 20);
define('THREADS_PER_PAGE', 15);
define('USERS_PER_PAGE', 25);

// Cache settings
define('CACHE_ENABLED', true);
define('CACHE_LIFETIME', 3600); // 1 hour

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

// Create necessary directories
$directories = [STORAGE_PATH, CACHE_PATH, LOGS_PATH, UPLOADS_PATH . '/avatars', UPLOADS_PATH . '/attachments'];
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
    $pdo->exec('PRAGMA foreign_keys = ON;');
    $pdo->exec('PRAGMA journal_mode = WAL;');
    $pdo->exec('PRAGMA synchronous = NORMAL;');
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
    
    if ($isProduction) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}
?>