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

// Check if installation is complete
if (!file_exists('.installed')) {
    header('Location: install.php');
    exit;
}

// Check installation status and run post-installation if needed
require_once 'check-installation.php';
$installStatus = getInstallationStatus();

if ($installStatus['status'] !== 'complete') {
    // Run post-installation if needed
    if (file_exists('post-install.php')) {
        include 'post-install.php';
    }
}

// Load Composer autoloader
require_once APP_ROOT . '/vendor/autoload.php';

// Load application bootstrap
require_once APP_ROOT . '/bootstrap/app.php';

// Initialize application
$app = new App\Core\Application();

// Handle the request
$app->run();