<?php
/**
 * Forum Project - Free Hosting Optimized
 * Main Entry Point
 * 
 * @author Your Name
 * @version 1.0.0
 * @license MIT
 */

// Start session
session_start();

// Check if installation is completed
if (!file_exists('.free-hosting-installed')) {
    header('Location: free-hosting-install.php');
    exit;
}

// Load environment variables
if (file_exists('.env')) {
    $lines = file('.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Database configuration
$db_config = [
    'host' => $_ENV['DB_HOST'] ?? 'localhost',
    'port' => $_ENV['DB_PORT'] ?? '3306',
    'database' => $_ENV['DB_DATABASE'] ?? 'u123456789_forum',
    'username' => $_ENV['DB_USERNAME'] ?? 'root',
    'password' => $_ENV['DB_PASSWORD'] ?? '',
    'charset' => 'utf8mb4'
];

// Database connection
try {
    $dsn = "mysql:host={$db_config['host']};port={$db_config['port']};dbname={$db_config['database']};charset={$db_config['charset']}";
    $pdo = new PDO($dsn, $db_config['username'], $db_config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Get current page
$page = $_GET['page'] ?? 'home';
$action = $_GET['action'] ?? 'index';

// Include functions
require_once 'includes/functions.php';

// Route handling
switch ($page) {
    case 'home':
        include 'pages/home.php';
        break;
    case 'login':
        include 'pages/login.php';
        break;
    case 'register':
        include 'pages/register.php';
        break;
    case 'profile':
        include 'pages/profile.php';
        break;
    case 'admin':
        include 'pages/admin.php';
        break;
    case 'topic':
        include 'pages/topic.php';
        break;
    case 'category':
        include 'pages/category.php';
        break;
    case 'create-topic':
        include 'pages/create-topic.php';
        break;
    case '500':
        include 'pages/500.php';
        break;
    case 'api':
        include 'api/index.php';
        break;
    default:
        include 'pages/404.php';
        break;
}
?>