<?php
declare(strict_types=1);

// Database Connection Test Script
// This script tests the database connection and shows basic info

require_once dirname(__DIR__) . '/config.php';
require_once CORE_PATH . '/Database.php';

use Core\Database;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Test - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .test-result {
            padding: 1rem;
            border-radius: 0.375rem;
            margin-bottom: 1rem;
        }
        .success { background-color: #d1e7dd; border: 1px solid #badbcc; color: #0f5132; }
        .error { background-color: #f8d7da; border: 1px solid #f5c2c7; color: #842029; }
        .info { background-color: #d1ecf1; border: 1px solid #bee5eb; color: #055160; }
        .warning { background-color: #fff3cd; border: 1px solid #ffecb5; color: #664d03; }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="mb-0">
                            <i class="fas fa-database me-2"></i>
                            Database Connection Test
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php
                        $testResults = [];
                        
                        function addTestResult($message, $type = 'info') {
                            global $testResults;
                            $testResults[] = ['message' => $message, 'type' => $type];
                        }
                        
                        try {
                            // Test 1: Database Connection
                            addTestResult("Testing database connection...", 'info');
                            $db = Database::getInstance();
                            $pdo = $db->getConnection();
                            addTestResult("✓ Database connection successful!", 'success');
                            
                            // Test 2: Database File
                            $dbFile = STORAGE_PATH . '/forum.sqlite';
                            if (file_exists($dbFile)) {
                                $fileSize = filesize($dbFile);
                                addTestResult("✓ Database file exists: " . $dbFile . " (" . number_format($fileSize) . " bytes)", 'success');
                            } else {
                                addTestResult("⚠ Database file does not exist: " . $dbFile, 'warning');
                            }
                            
                            // Test 3: Check Tables
                            addTestResult("Checking existing tables...", 'info');
                            $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
                            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                            
                            if (empty($tables)) {
                                addTestResult("⚠ No tables found in database. Run migration to create tables.", 'warning');
                            } else {
                                addTestResult("✓ Found " . count($tables) . " tables: " . implode(', ', $tables), 'success');
                            }
                            
                            // Test 4: Test Query
                            addTestResult("Testing basic query...", 'info');
                            $stmt = $pdo->query("SELECT 1 as test");
                            $result = $stmt->fetch();
                            if ($result && $result['test'] == 1) {
                                addTestResult("✓ Basic query test successful!", 'success');
                            } else {
                                addTestResult("✗ Basic query test failed!", 'error');
                            }
                            
                            // Test 5: Database Info
                            addTestResult("Getting database information...", 'info');
                            $stmt = $pdo->query("PRAGMA database_list");
                            $databases = $stmt->fetchAll();
                            foreach ($databases as $dbInfo) {
                                addTestResult("Database: " . $dbInfo['name'] . " (" . $dbInfo['file'] . ")", 'info');
                            }
                            
                            // Test 6: SQLite Version
                            $stmt = $pdo->query("SELECT sqlite_version() as version");
                            $version = $stmt->fetch();
                            addTestResult("SQLite version: " . $version['version'], 'info');
                            
                            // Test 7: Storage Directory
                            if (is_dir(STORAGE_PATH)) {
                                addTestResult("✓ Storage directory exists: " . STORAGE_PATH, 'success');
                            } else {
                                addTestResult("✗ Storage directory does not exist: " . STORAGE_PATH, 'error');
                            }
                            
                            // Test 8: Writable Check
                            if (is_writable(STORAGE_PATH)) {
                                addTestResult("✓ Storage directory is writable", 'success');
                            } else {
                                addTestResult("✗ Storage directory is not writable", 'error');
                            }
                            
                            addTestResult("Database test completed successfully!", 'success');
                            
                        } catch (Exception $e) {
                            addTestResult("✗ Database test failed: " . $e->getMessage(), 'error');
                            addTestResult("Error in file: " . $e->getFile() . " on line: " . $e->getLine(), 'error');
                        }
                        ?>
                        
                        <?php foreach ($testResults as $result): ?>
                            <div class="test-result <?= $result['type'] ?>">
                                <?= htmlspecialchars($result['message']) ?>
                            </div>
                        <?php endforeach; ?>
                        
                        <div class="mt-4">
                            <a href="migrate.php" class="btn btn-primary">
                                <i class="fas fa-database me-2"></i>
                                Run Database Migration
                            </a>
                            <a href="/" class="btn btn-secondary">
                                <i class="fas fa-home me-2"></i>
                                Go to Forum
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>