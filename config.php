<?php
// Database configuration
define('DB_PATH', __DIR__ . '/storage/forum.sqlite');
define('DB_DSN', 'sqlite:' . DB_PATH);

// Site settings
define('SITE_NAME', 'Coding Master Forum');
define('SITE_URL', 'http://localhost/my_forum');
define('DEFAULT_LANG', 'en');

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

// Session and security
session_start();
ini_set('display_errors', '0');
error_reporting(E_ALL);

// Check if installed
if (!file_exists(STORAGE_PATH . '/installed.lock') && basename($_SERVER['PHP_SELF']) !== 'install.php') {
    header('Location: install.php');
    exit;
}

// Auto-load classes
spl_autoload_register(function ($class_name) {
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
    $pdo->exec('PRAGMA foreign_keys = ON;');
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>