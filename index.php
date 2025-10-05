<?php
/**
 * Forum Application Entry Point
 * Main entry point for the forum application
 */

// Start output buffering
ob_start();

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define application constants
define('APP_ROOT', __DIR__);
define('APP_PATH', APP_ROOT . '/app');
define('CONFIG_PATH', APP_PATH . '/Config');
define('STORAGE_PATH', APP_ROOT . '/storage');
define('PUBLIC_PATH', APP_ROOT . '/public');

// Check if installation is required
if (!file_exists(CONFIG_PATH . '/database.php')) {
    header('Location: install.php');
    exit;
}

// Load Composer autoloader
require_once APP_ROOT . '/vendor/autoload.php';

// Load application bootstrap
require_once APP_ROOT . '/bootstrap/app.php';

// Initialize application
$app = new App\Core\Application();

// Handle the request
$app->run();