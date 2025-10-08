<?php
/**
 * Mobile-Optimized Index for Forum Project
 * Optimized for KSWeb and mobile devices
 */

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

// Check if installation is complete
if (!file_exists('.installed')) {
    // Redirect to mobile installation
    header('Location: mobile-install.php');
    exit;
}

// Load environment
if (file_exists('.env')) {
    $env = parse_ini_file('.env');
} else {
    die('❌ Environment file not found. Please run installation first.');
}

// Mobile-specific configuration
$mobileConfig = [
    'responsive' => true,
    'touch_friendly' => true,
    'pwa_enabled' => $env['MOBILE_PWA_ENABLED'] ?? true,
    'offline_support' => $env['MOBILE_OFFLINE_SUPPORT'] ?? true,
    'push_notifications' => $env['MOBILE_PUSH_NOTIFICATIONS'] ?? true,
    'biometric_auth' => $env['MOBILE_BIOMETRIC_AUTH'] ?? true,
    'dark_mode' => $env['MOBILE_DARK_MODE'] ?? true,
    'swipe_gestures' => $env['MOBILE_SWIPE_GESTURES'] ?? true,
    'pull_to_refresh' => $env['MOBILE_PULL_TO_REFRESH'] ?? true,
    'vibration' => $env['MOBILE_VIBRATION'] ?? true
];

// Simple routing for mobile
$request = $_SERVER['REQUEST_URI'];
$path = parse_url($request, PHP_URL_PATH);
$path = rtrim($path, '/');

// Remove the base path if the app is in a subdirectory
$basePath = '/forum';
if (strpos($path, $basePath) === 0) {
    $path = substr($path, strlen($basePath));
}

// Route handling
switch ($path) {
    case '':
    case '/':
        include 'mobile-home.php';
        break;
    case '/login':
        include 'mobile-login.php';
        break;
    case '/register':
        include 'mobile-register.php';
        break;
    case '/admin':
        include 'mobile-admin.php';
        break;
    case '/mobile':
        include 'mobile-dashboard.php';
        break;
    case '/api/mobile':
        include 'mobile-api.php';
        break;
    default:
        http_response_code(404);
        include 'mobile-error.php';
        break;
}
?>