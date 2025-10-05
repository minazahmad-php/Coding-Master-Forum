<?php
/**
 * Public Entry Point
 * This file should be the document root for your web server
 */

// Set the correct path
$rootPath = dirname(__DIR__);

// Define constants
define('APP_ROOT', $rootPath);
define('APP_PATH', $rootPath . '/app');
define('CONFIG_PATH', APP_PATH . '/Config');
define('STORAGE_PATH', $rootPath . '/storage');
define('PUBLIC_PATH', __DIR__);

// Check if installation is required
if (!file_exists(CONFIG_PATH . '/database.php')) {
    header('Location: install.php');
    exit;
}

// Load Composer autoloader
require_once $rootPath . '/vendor/autoload.php';

// Load application bootstrap
require_once $rootPath . '/bootstrap/app.php';