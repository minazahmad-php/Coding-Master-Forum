<?php

/**
 * Application Bootstrap
 * Initialize the application and handle the request
 */

// Set error reporting based on environment
$isDebug = ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
if ($isDebug) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Set timezone
date_default_timezone_set('Asia/Dhaka');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load environment variables
if (file_exists(APP_ROOT . '/.env')) {
    $lines = file(APP_ROOT . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Remove quotes if present
            if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                $value = substr($value, 1, -1);
            }
            
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

// Load Composer autoloader
if (file_exists(APP_ROOT . '/vendor/autoload.php')) {
    require_once APP_ROOT . '/vendor/autoload.php';
} else {
    die('Composer dependencies not installed. Please run: composer install');
}

// Load helper functions
require_once APP_PATH . '/Core/Helpers.php';

// Initialize error handler
try {
    $logger = null;
    if (class_exists('App\Core\Logger')) {
        $config = new App\Core\Config();
        $logger = new App\Core\Logger($config);
    }
    $errorHandler = new App\Core\ErrorHandler($logger, $isDebug);
} catch (\Exception $e) {
    error_log('Failed to initialize error handler: ' . $e->getMessage());
}

// Initialize application
try {
    $app = new App\Core\Application();
} catch (\Exception $e) {
    error_log('Failed to initialize application: ' . $e->getMessage());
    http_response_code(500);
    echo 'Application initialization failed. Please check the logs.';
    exit;
}

// Make app globally available
$GLOBALS['app'] = $app;

// Handle the request
$app->run();