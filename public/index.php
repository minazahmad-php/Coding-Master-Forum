<?php
declare(strict_types=1);

/**
 * Modern Forum - Main Entry Point
 * Handles all requests and routes them to appropriate controllers
 */

// Start session
session_start();

// Load configuration
require_once __DIR__ . '/config.php';

// Load core classes
require_once CORE_PATH . '/Router.php';
require_once CORE_PATH . '/Database.php';
require_once CORE_PATH . '/Auth.php';
require_once CORE_PATH . '/Session.php';
require_once CORE_PATH . '/Mail.php';
require_once CORE_PATH . '/Logger.php';
require_once CORE_PATH . '/Functions.php';

// Load environment variables
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

// Initialize services
use Core\Database;
use Core\Session;
use Core\Logger;
use Core\Mail;

try {
    // Initialize database
    $db = Database::getInstance();
    
    // Initialize session
    $session = new Session();
    $session->start();
    
    // Initialize logger
    $logger = new Logger();
    
    // Initialize mail service
    $mail = new Mail();
    
    // Load routes
    require_once ROUTES_PATH . '/index.php';
    
} catch (Exception $e) {
    // Log error
    error_log("Application initialization error: " . $e->getMessage());
    
    // Show error page
    http_response_code(500);
    
    if (defined('APP_DEBUG') && APP_DEBUG) {
        echo "<h1>Application Error</h1>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    } else {
        echo "<h1>Something went wrong</h1>";
        echo "<p>Please try again later.</p>";
    }
    
    exit;
}