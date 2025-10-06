<?php
/**
 * Installation Checker
 * Checks if installation is complete and system is ready
 */

function checkInstallation() {
    $checks = [
        'installation_complete' => file_exists('.installed'),
        'env_file_exists' => file_exists('.env'),
        'database_accessible' => checkDatabase(),
        'storage_writable' => is_writable('storage'),
        'uploads_writable' => is_writable('public/uploads'),
        'admin_user_exists' => checkAdminUser(),
        'sample_data_exists' => checkSampleData()
    ];
    
    return $checks;
}

function checkDatabase() {
    try {
        if (!file_exists('.env')) {
            return false;
        }
        
        $env = parse_ini_file('.env');
        
        if ($env['DB_CONNECTION'] === 'sqlite') {
            $pdo = new PDO('sqlite:' . $env['DB_DATABASE']);
        } else {
            $dsn = "mysql:host={$env['DB_HOST']};port={$env['DB_PORT']};dbname={$env['DB_DATABASE']};charset=utf8mb4";
            $pdo = new PDO($dsn, $env['DB_USERNAME'], $env['DB_PASSWORD']);
        }
        
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Test query
        $stmt = $pdo->query("SELECT 1");
        return $stmt !== false;
        
    } catch (Exception $e) {
        return false;
    }
}

function checkAdminUser() {
    try {
        if (!file_exists('.env')) {
            return false;
        }
        
        $env = parse_ini_file('.env');
        
        if ($env['DB_CONNECTION'] === 'sqlite') {
            $pdo = new PDO('sqlite:' . $env['DB_DATABASE']);
        } else {
            $dsn = "mysql:host={$env['DB_HOST']};port={$env['DB_PORT']};dbname={$env['DB_DATABASE']};charset=utf8mb4";
            $pdo = new PDO($dsn, $env['DB_USERNAME'], $env['DB_PASSWORD']);
        }
        
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
        $stmt->execute();
        $adminCount = $stmt->fetchColumn();
        
        return $adminCount > 0;
        
    } catch (Exception $e) {
        return false;
    }
}

function checkSampleData() {
    try {
        if (!file_exists('.env')) {
            return false;
        }
        
        $env = parse_ini_file('.env');
        
        if ($env['DB_CONNECTION'] === 'sqlite') {
            $pdo = new PDO('sqlite:' . $env['DB_DATABASE']);
        } else {
            $dsn = "mysql:host={$env['DB_HOST']};port={$env['DB_PORT']};dbname={$env['DB_DATABASE']};charset=utf8mb4";
            $pdo = new PDO($dsn, $env['DB_USERNAME'], $env['DB_PASSWORD']);
        }
        
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM forums");
        $stmt->execute();
        $forumCount = $stmt->fetchColumn();
        
        return $forumCount > 0;
        
    } catch (Exception $e) {
        return false;
    }
}

function getInstallationStatus() {
    $checks = checkInstallation();
    $allPassed = array_reduce($checks, function($carry, $check) {
        return $carry && $check;
    }, true);
    
    return [
        'status' => $allPassed ? 'complete' : 'incomplete',
        'checks' => $checks,
        'message' => $allPassed ? 'Installation complete and system ready!' : 'Installation incomplete or system not ready.'
    ];
}

// Return JSON response if accessed via AJAX
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode(getInstallationStatus());
    exit;
}

// Return status for CLI or direct access
if (php_sapi_name() === 'cli') {
    $status = getInstallationStatus();
    
    echo "🔍 Installation Status Check\n";
    echo "============================\n\n";
    
    foreach ($status['checks'] as $check => $passed) {
        $icon = $passed ? '✅' : '❌';
        $name = ucwords(str_replace('_', ' ', $check));
        echo "{$icon} {$name}\n";
    }
    
    echo "\n" . ($status['status'] === 'complete' ? '🎉 ' : '⚠️ ') . $status['message'] . "\n";
    
    exit($status['status'] === 'complete' ? 0 : 1);
}
?>