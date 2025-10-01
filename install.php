<?php
declare(strict_types=1);

/**
 * Modern Forum - Complete Installation Script
 * One-click installation for the most advanced forum platform
 */

// Prevent direct access
if (!defined('INSTALLATION_MODE')) {
    define('INSTALLATION_MODE', true);
}

// Set error reporting for installation
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start output buffering for progress display
ob_start();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modern Forum - Installation</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .install-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px;
            max-width: 800px;
            width: 100%;
        }
        .install-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2d3748;
            text-align: center;
            margin-bottom: 20px;
        }
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e2e8f0;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea, #764ba2);
            width: 0%;
            transition: width 0.3s ease;
        }
        .install-button {
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            margin-bottom: 20px;
        }
        .log-container {
            background: #f7fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 20px;
            max-height: 300px;
            overflow-y: auto;
            font-family: monospace;
            font-size: 0.9rem;
        }
        .log-success { color: #38a169; }
        .log-error { color: #e53e3e; }
        .log-info { color: #3182ce; }
        .hidden { display: none; }
    </style>
</head>
<body>
    <div class="install-container">
        <h1 class="install-title">🚀 Modern Forum Installation</h1>
        
        <div class="progress-bar">
            <div class="progress-fill" id="progressFill"></div>
        </div>
        
        <button class="install-button" id="installButton" onclick="startInstallation()">
            Install Modern Forum
        </button>
        
        <div class="log-container" id="logContainer">
            <div class="log-info">Ready to install Modern Forum...</div>
        </div>
    </div>

    <script>
        async function startInstallation() {
            const button = document.getElementById('installButton');
            const progressFill = document.getElementById('progressFill');
            const logContainer = document.getElementById('logContainer');
            
            button.disabled = true;
            button.textContent = 'Installing...';
            
            function addLog(message, type = 'info') {
                const logEntry = document.createElement('div');
                logEntry.className = `log-${type}`;
                logEntry.textContent = message;
                logContainer.appendChild(logEntry);
                logContainer.scrollTop = logContainer.scrollHeight;
            }
            
            function updateProgress(percent) {
                progressFill.style.width = percent + '%';
            }
            
            try {
                // Step 1: Check requirements
                updateProgress(20);
                addLog('Checking requirements...', 'info');
                
                const req = await fetch('/install.php?step=requirements');
                const reqResult = await req.json();
                
                if (!reqResult.success) {
                    throw new Error(reqResult.message);
                }
                addLog('✓ Requirements check passed', 'success');
                
                // Step 2: Create directories
                updateProgress(40);
                addLog('Creating directories...', 'info');
                
                const dir = await fetch('/install.php?step=directories');
                const dirResult = await dir.json();
                
                if (!dirResult.success) {
                    throw new Error(dirResult.message);
                }
                addLog('✓ Directories created', 'success');
                
                // Step 3: Setup database
                updateProgress(60);
                addLog('Setting up database...', 'info');
                
                const db = await fetch('/install.php?step=database');
                const dbResult = await db.json();
                
                if (!dbResult.success) {
                    throw new Error(dbResult.message);
                }
                addLog('✓ Database setup completed', 'success');
                
                // Step 4: Create admin user
                updateProgress(80);
                addLog('Creating admin user...', 'info');
                
                const admin = await fetch('/install.php?step=admin');
                const adminResult = await admin.json();
                
                if (!adminResult.success) {
                    throw new Error(adminResult.message);
                }
                addLog('✓ Admin user created', 'success');
                
                // Step 5: Finalize
                updateProgress(100);
                addLog('Finalizing installation...', 'info');
                
                const final = await fetch('/install.php?step=finalize');
                const finalResult = await final.json();
                
                if (!finalResult.success) {
                    throw new Error(finalResult.message);
                }
                addLog('✓ Installation completed!', 'success');
                
                button.textContent = 'Installation Complete!';
                button.onclick = () => window.location.href = '/admin';
                
            } catch (error) {
                addLog('✗ Installation failed: ' + error.message, 'error');
                button.disabled = false;
                button.textContent = 'Installation Failed - Try Again';
            }
        }
    </script>
</body>
</html>
<?php

// Handle AJAX requests
if (isset($_GET['step'])) {
    header('Content-Type: application/json');
    
    try {
        $installer = new ModernForumInstaller();
        $result = $installer->handleStep($_GET['step']);
        echo json_encode($result);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

class ModernForumInstaller
{
    private array $config = [];
    private string $basePath;
    
    public function __construct()
    {
        $this->basePath = dirname(__DIR__);
        $this->loadConfig();
    }
    
    private function loadConfig(): void
    {
        $this->config = [
            'app_name' => 'Modern Forum',
            'app_url' => $this->getBaseUrl(),
            'app_key' => $this->generateAppKey(),
            'db_database' => $this->basePath . '/storage/forum.sqlite',
            'admin_username' => 'admin',
            'admin_password' => 'admin123',
            'admin_email' => 'admin@example.com',
        ];
    }
    
    public function handleStep(string $step): array
    {
        switch ($step) {
            case 'requirements':
                return $this->checkRequirements();
            case 'directories':
                return $this->createDirectories();
            case 'database':
                return $this->setupDatabase();
            case 'admin':
                return $this->createAdminUser();
            case 'finalize':
                return $this->finalizeInstallation();
            default:
                throw new Exception('Invalid installation step');
        }
    }
    
    private function checkRequirements(): array
    {
        $requirements = [
            'PHP Version >= 8.1' => version_compare(PHP_VERSION, '8.1.0', '>='),
            'PDO Extension' => extension_loaded('pdo'),
            'PDO SQLite' => extension_loaded('pdo_sqlite'),
            'JSON Extension' => extension_loaded('json'),
            'File System Write Access' => is_writable($this->basePath),
        ];
        
        $failed = array_filter($requirements, fn($check) => !$check);
        
        if (!empty($failed)) {
            throw new Exception('Requirements check failed: ' . implode(', ', array_keys($failed)));
        }
        
        return ['success' => true];
    }
    
    private function createDirectories(): array
    {
        $directories = [
            $this->basePath . '/storage',
            $this->basePath . '/storage/database',
            $this->basePath . '/storage/logs',
            $this->basePath . '/storage/cache',
            $this->basePath . '/storage/uploads',
            $this->basePath . '/storage/uploads/avatars',
            $this->basePath . '/storage/uploads/posts',
        ];
        
        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                if (!mkdir($dir, 0755, true)) {
                    throw new Exception("Failed to create directory: $dir");
                }
            }
        }
        
        return ['success' => true];
    }
    
    private function setupDatabase(): array
    {
        $dbPath = $this->config['db_database'];
        
        if (!file_exists($dbPath)) {
            touch($dbPath);
            chmod($dbPath, 0644);
        }
        
        require_once $this->basePath . '/migrations/create_core_tables.php';
        
        return ['success' => true];
    }
    
    private function createAdminUser(): array
    {
        $dbPath = $this->config['db_database'];
        $pdo = new PDO("sqlite:$dbPath");
        
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$this->config['admin_username']]);
        
        if ($stmt->fetch()) {
            return ['success' => true, 'message' => 'Admin user already exists'];
        }
        
        $hashedPassword = password_hash($this->config['admin_password'], PASSWORD_ARGON2ID);
        
        $stmt = $pdo->prepare("
            INSERT INTO users (
                username, email, password, first_name, last_name, 
                role, status, email_verified_at, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $now = date('Y-m-d H:i:s');
        $result = $stmt->execute([
            $this->config['admin_username'],
            $this->config['admin_email'],
            $hashedPassword,
            'Admin',
            'User',
            'admin',
            'active',
            $now,
            $now,
            $now
        ]);
        
        if (!$result) {
            throw new Exception('Failed to create admin user');
        }
        
        return ['success' => true];
    }
    
    private function finalizeInstallation(): array
    {
        $envContent = "APP_NAME=\"{$this->config['app_name']}\"
APP_ENV=production
APP_DEBUG=false
APP_URL={$this->config['app_url']}
APP_KEY={$this->config['app_key']}

DB_CONNECTION=sqlite
DB_DATABASE={$this->config['db_database']}

SESSION_NAME=FORUM_SESSION
SESSION_LIFETIME=86400

MAIL_DRIVER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=hello@example.com
MAIL_FROM_NAME=\"Modern Forum\"

ADMIN_USERNAME={$this->config['admin_username']}
ADMIN_EMAIL={$this->config['admin_email']}
";
        
        $envFile = $this->basePath . '/.env';
        
        if (!file_put_contents($envFile, $envContent)) {
            throw new Exception('Failed to create .env file');
        }
        
        chmod($envFile, 0600);
        
        return ['success' => true];
    }
    
    private function generateAppKey(): string
    {
        return base64_encode(random_bytes(32));
    }
    
    private function getBaseUrl(): string
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $path = dirname($_SERVER['SCRIPT_NAME']);
        
        return $protocol . '://' . $host . $path;
    }
}