<?php
/**
 * Forum Project - Free Hosting Installation
 * Complete Installation Wizard for Free Hosting
 * 
 * @author Your Name
 * @version 1.0.0
 * @license MIT
 */

// Start session
session_start();

// Check if already installed
if (file_exists('.free-hosting-installed')) {
    header('Location: index.php');
    exit;
}

$currentStep = $_GET['step'] ?? 'welcome';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($currentStep) {
        case 'database':
            $db_config = [
                'host' => $_POST['db_host'] ?? 'localhost',
                'port' => $_POST['db_port'] ?? '3306',
                'database' => $_POST['db_database'] ?? '',
                'username' => $_POST['db_username'] ?? '',
                'password' => $_POST['db_password'] ?? ''
            ];
            
            // Test database connection
            try {
                $dsn = "mysql:host={$db_config['host']};port={$db_config['port']};charset=utf8mb4";
                $pdo = new PDO($dsn, $db_config['username'], $db_config['password'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ]);
                
                // Create database if not exists
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db_config['database']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $pdo->exec("USE `{$db_config['database']}`");
                
                $_SESSION['db_config'] = $db_config;
                header('Location: ?step=admin');
                exit;
            } catch (PDOException $e) {
                $error = "Database connection failed: " . $e->getMessage();
            }
            break;
            
        case 'admin':
            $admin_config = [
                'username' => $_POST['admin_username'] ?? '',
                'email' => $_POST['admin_email'] ?? '',
                'password' => $_POST['admin_password'] ?? '',
                'confirm_password' => $_POST['admin_confirm_password'] ?? ''
            ];
            
            if ($admin_config['password'] !== $admin_config['confirm_password']) {
                $error = "Passwords do not match";
            } elseif (strlen($admin_config['password']) < 6) {
                $error = "Password must be at least 6 characters";
            } else {
                $_SESSION['admin_config'] = $admin_config;
                header('Location: ?step=features');
                exit;
            }
            break;
            
        case 'features':
            $features = [
                'user_registration' => isset($_POST['user_registration']),
                'email_verification' => isset($_POST['email_verification']),
                'moderation' => isset($_POST['moderation']),
                'themes' => isset($_POST['themes']),
                'api' => isset($_POST['api']),
                'analytics' => isset($_POST['analytics'])
            ];
            $_SESSION['features'] = $features;
            header('Location: ?step=install');
            exit;
            break;
            
        case 'install':
            if (isset($_POST['install_started'])) {
                $result = performInstallation();
                if (strpos($result, 'successfully') !== false) {
                    header('Location: ?step=complete');
                    exit;
                } else {
                    $error = $result;
                }
            }
            break;
    }
}

function performInstallation() {
    try {
        // Step 1: Create .env file
        $env_content = "APP_NAME=\"Forum Project\"\n";
        $env_content .= "APP_ENV=production\n";
        $env_content .= "APP_DEBUG=false\n";
        $env_content .= "APP_URL=https://coding-master.infy.uk\n\n";
        $env_content .= "DB_CONNECTION=mysql\n";
        $env_content .= "DB_HOST=" . ($_SESSION['db_config']['host'] ?? 'localhost') . "\n";
        $env_content .= "DB_PORT=" . ($_SESSION['db_config']['port'] ?? '3306') . "\n";
        $env_content .= "DB_DATABASE=" . ($_SESSION['db_config']['database'] ?? 'u123456789_forum') . "\n";
        $env_content .= "DB_USERNAME=" . ($_SESSION['db_config']['username'] ?? 'root') . "\n";
        $env_content .= "DB_PASSWORD=" . ($_SESSION['db_config']['password'] ?? '') . "\n\n";
        $env_content .= "CACHE_DRIVER=file\n";
        $env_content .= "SESSION_DRIVER=file\n";
        $env_content .= "QUEUE_CONNECTION=sync\n\n";
        $env_content .= "MAIL_MAILER=smtp\n";
        $env_content .= "MAIL_HOST=localhost\n";
        $env_content .= "MAIL_PORT=587\n";
        $env_content .= "MAIL_USERNAME=null\n";
        $env_content .= "MAIL_PASSWORD=null\n";
        $env_content .= "MAIL_ENCRYPTION=null\n";
        $env_content .= "MAIL_FROM_ADDRESS=noreply@coding-master.infy.uk\n";
        $env_content .= "MAIL_FROM_NAME=\"Forum Project\"\n";
        
        file_put_contents('.env', $env_content);
        
        // Step 2: Create database tables
        $db_config = $_SESSION['db_config'];
        $dsn = "mysql:host={$db_config['host']};port={$db_config['port']};dbname={$db_config['database']};charset=utf8mb4";
        $pdo = new PDO($dsn, $db_config['username'], $db_config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
        // Create users table
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role ENUM('user', 'moderator', 'admin') DEFAULT 'user',
            status ENUM('active', 'inactive', 'banned') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        
        // Create categories table
        $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            slug VARCHAR(100) UNIQUE NOT NULL,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Create topics table
        $pdo->exec("CREATE TABLE IF NOT EXISTS topics (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            content TEXT NOT NULL,
            user_id INT NOT NULL,
            category_id INT NOT NULL,
            status ENUM('active', 'closed', 'pinned') DEFAULT 'active',
            views INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
        )");
        
        // Create replies table
        $pdo->exec("CREATE TABLE IF NOT EXISTS replies (
            id INT AUTO_INCREMENT PRIMARY KEY,
            content TEXT NOT NULL,
            user_id INT NOT NULL,
            topic_id INT NOT NULL,
            status ENUM('active', 'hidden') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (topic_id) REFERENCES topics(id) ON DELETE CASCADE
        )");
        
        // Create admin user
        $admin_config = $_SESSION['admin_config'];
        $hashed_password = password_hash($admin_config['password'], PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, status) VALUES (?, ?, ?, 'admin', 'active')");
        $stmt->execute([$admin_config['username'], $admin_config['email'], $hashed_password]);
        
        // Create default category
        $stmt = $pdo->prepare("INSERT INTO categories (name, description, slug) VALUES (?, ?, ?)");
        $stmt->execute(['General Discussion', 'General discussion topics', 'general-discussion']);
        
        // Step 3: Create directories
        $directories = [
            'uploads',
            'uploads/avatars',
            'uploads/topics',
            'cache',
            'logs',
            'includes',
            'pages',
            'api',
            'assets',
            'assets/css',
            'assets/js',
            'assets/images'
        ];
        
        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
        
        // Step 4: Create .htaccess
        $htaccess_content = "RewriteEngine On\n";
        $htaccess_content .= "RewriteCond %{REQUEST_FILENAME} !-f\n";
        $htaccess_content .= "RewriteCond %{REQUEST_FILENAME} !-d\n";
        $htaccess_content .= "RewriteRule ^(.*)$ index.php [QSA,L]\n\n";
        $htaccess_content .= "# Security\n";
        $htaccess_content .= "Options -Indexes\n";
        $htaccess_content .= "ServerSignature Off\n\n";
        $htaccess_content .= "# Cache\n";
        $htaccess_content .= "<IfModule mod_expires.c>\n";
        $htaccess_content .= "ExpiresActive On\n";
        $htaccess_content .= "ExpiresByType text/css \"access plus 1 month\"\n";
        $htaccess_content .= "ExpiresByType application/javascript \"access plus 1 month\"\n";
        $htaccess_content .= "ExpiresByType image/png \"access plus 1 month\"\n";
        $htaccess_content .= "ExpiresByType image/jpg \"access plus 1 month\"\n";
        $htaccess_content .= "ExpiresByType image/jpeg \"access plus 1 month\"\n";
        $htaccess_content .= "ExpiresByType image/gif \"access plus 1 month\"\n";
        $htaccess_content .= "</IfModule>\n\n";
        $htaccess_content .= "# Compression\n";
        $htaccess_content .= "<IfModule mod_deflate.c>\n";
        $htaccess_content .= "AddOutputFilterByType DEFLATE text/plain\n";
        $htaccess_content .= "AddOutputFilterByType DEFLATE text/html\n";
        $htaccess_content .= "AddOutputFilterByType DEFLATE text/xml\n";
        $htaccess_content .= "AddOutputFilterByType DEFLATE text/css\n";
        $htaccess_content .= "AddOutputFilterByType DEFLATE application/xml\n";
        $htaccess_content .= "AddOutputFilterByType DEFLATE application/xhtml+xml\n";
        $htaccess_content .= "AddOutputFilterByType DEFLATE application/rss+xml\n";
        $htaccess_content .= "AddOutputFilterByType DEFLATE application/javascript\n";
        $htaccess_content .= "AddOutputFilterByType DEFLATE application/x-javascript\n";
        $htaccess_content .= "</IfModule>\n";
        
        file_put_contents('.htaccess', $htaccess_content);
        
        // Step 5: Create .free-hosting-installed file
        file_put_contents('.free-hosting-installed', date('Y-m-d H:i:s'));
        
        // Step 6: Clear session
        session_destroy();
        
        return "Installation completed successfully!";
        
    } catch (Exception $e) {
        return "Installation failed: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forum Project - Free Hosting Installation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .install-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .install-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .install-content {
            padding: 2rem;
        }
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
        }
        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 10px;
            font-weight: bold;
            color: #6c757d;
        }
        .step.active {
            background: #667eea;
            color: white;
        }
        .step.completed {
            background: #28a745;
            color: white;
        }
        .progress {
            height: 8px;
            border-radius: 4px;
            background: #e9ecef;
        }
        .progress-bar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 4px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
            transform: translateY(-2px);
        }
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .alert {
            border-radius: 10px;
            border: none;
        }
        .feature-card {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        .feature-card:hover {
            border-color: #667eea;
            transform: translateY(-2px);
        }
        .feature-card input[type="checkbox"] {
            transform: scale(1.2);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="install-container">
                    <div class="install-header">
                        <h1><i class="fas fa-download me-2"></i>Forum Project Installation</h1>
                        <p class="mb-0">Free Hosting Optimized - Complete Setup Wizard</p>
                    </div>
                    
                    <div class="install-content">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Step Indicator -->
                        <div class="step-indicator">
                            <div class="step <?php echo $currentStep === 'welcome' ? 'active' : ($currentStep === 'database' || $currentStep === 'admin' || $currentStep === 'features' || $currentStep === 'install' || $currentStep === 'complete' ? 'completed' : ''); ?>">1</div>
                            <div class="step <?php echo $currentStep === 'database' ? 'active' : ($currentStep === 'admin' || $currentStep === 'features' || $currentStep === 'install' || $currentStep === 'complete' ? 'completed' : ''); ?>">2</div>
                            <div class="step <?php echo $currentStep === 'admin' ? 'active' : ($currentStep === 'features' || $currentStep === 'install' || $currentStep === 'complete' ? 'completed' : ''); ?>">3</div>
                            <div class="step <?php echo $currentStep === 'features' ? 'active' : ($currentStep === 'install' || $currentStep === 'complete' ? 'completed' : ''); ?>">4</div>
                            <div class="step <?php echo $currentStep === 'install' ? 'active' : ($currentStep === 'complete' ? 'completed' : ''); ?>">5</div>
                            <div class="step <?php echo $currentStep === 'complete' ? 'active' : ''; ?>">6</div>
                        </div>
                        
                        <?php if ($currentStep === 'welcome'): ?>
                            <div class="text-center">
                                <h2>Welcome to Forum Project Installation</h2>
                                <p class="lead">This wizard will help you set up your forum on free hosting.</p>
                                
                                <div class="row mt-4">
                                    <div class="col-md-6">
                                        <div class="feature-card">
                                            <h5><i class="fas fa-database text-primary me-2"></i>Database Setup</h5>
                                            <p class="text-muted">Configure MySQL database connection</p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="feature-card">
                                            <h5><i class="fas fa-user-shield text-primary me-2"></i>Admin Account</h5>
                                            <p class="text-muted">Create your administrator account</p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="feature-card">
                                            <h5><i class="fas fa-cogs text-primary me-2"></i>Features</h5>
                                            <p class="text-muted">Configure forum features</p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="feature-card">
                                            <h5><i class="fas fa-rocket text-primary me-2"></i>Installation</h5>
                                            <p class="text-muted">Complete the installation process</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <a href="?step=database" class="btn btn-primary btn-lg mt-4">
                                    <i class="fas fa-arrow-right me-2"></i>Start Installation
                                </a>
                            </div>
                            
                        <?php elseif ($currentStep === 'database'): ?>
                            <h2>Database Configuration</h2>
                            <p class="text-muted">Enter your free hosting database details.</p>
                            
                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Database Host</label>
                                            <input type="text" class="form-control" name="db_host" value="localhost" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Database Port</label>
                                            <input type="text" class="form-control" name="db_port" value="3306" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Database Name</label>
                                    <input type="text" class="form-control" name="db_database" placeholder="u123456789_forum" required>
                                    <div class="form-text">Usually starts with 'u' followed by numbers</div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Database Username</label>
                                            <input type="text" class="form-control" name="db_username" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Database Password</label>
                                            <input type="password" class="form-control" name="db_password">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-between">
                                    <a href="?step=welcome" class="btn btn-outline-secondary">
                                        <i class="fas fa-arrow-left me-2"></i>Back
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-arrow-right me-2"></i>Next
                                    </button>
                                </div>
                            </form>
                            
                        <?php elseif ($currentStep === 'admin'): ?>
                            <h2>Admin Account Setup</h2>
                            <p class="text-muted">Create your administrator account.</p>
                            
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Username</label>
                                    <input type="text" class="form-control" name="admin_username" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" class="form-control" name="admin_email" required>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Password</label>
                                            <input type="password" class="form-control" name="admin_password" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Confirm Password</label>
                                            <input type="password" class="form-control" name="admin_confirm_password" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-between">
                                    <a href="?step=database" class="btn btn-outline-secondary">
                                        <i class="fas fa-arrow-left me-2"></i>Back
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-arrow-right me-2"></i>Next
                                    </button>
                                </div>
                            </form>
                            
                        <?php elseif ($currentStep === 'features'): ?>
                            <h2>Features Configuration</h2>
                            <p class="text-muted">Select the features you want to enable.</p>
                            
                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="feature-card">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="user_registration" id="user_registration" checked>
                                                <label class="form-check-label" for="user_registration">
                                                    <strong>User Registration</strong>
                                                    <br><small class="text-muted">Allow users to register</small>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="feature-card">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="moderation" id="moderation" checked>
                                                <label class="form-check-label" for="moderation">
                                                    <strong>Moderation</strong>
                                                    <br><small class="text-muted">Content moderation tools</small>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="feature-card">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="themes" id="themes">
                                                <label class="form-check-label" for="themes">
                                                    <strong>Themes</strong>
                                                    <br><small class="text-muted">Multiple themes</small>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="feature-card">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="api" id="api">
                                                <label class="form-check-label" for="api">
                                                    <strong>API</strong>
                                                    <br><small class="text-muted">REST API endpoints</small>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-between">
                                    <a href="?step=admin" class="btn btn-outline-secondary">
                                        <i class="fas fa-arrow-left me-2"></i>Back
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-arrow-right me-2"></i>Next
                                    </button>
                                </div>
                            </form>
                            
                        <?php elseif ($currentStep === 'install'): ?>
                            <h2>Installing Forum Project</h2>
                            <p class="text-muted">Please wait while we set up your forum...</p>
                            
                            <div class="progress mb-3">
                                <div class="progress-bar" role="progressbar" style="width: 0%" id="installProgress"></div>
                            </div>
                            
                            <div id="installStatus">Preparing installation...</div>
                            
                            <div id="installComplete" style="display: none;" class="alert alert-success mt-3">
                                <i class="fas fa-check-circle me-2"></i>
                                <strong>Installation Complete!</strong> Redirecting to your forum...
                            </div>
                            
                            <form method="POST" id="installForm" style="display: none;">
                                <input type="hidden" name="install_started" value="1">
                            </form>
                            
                            <script>
                                // Installation progress simulation
                                document.addEventListener('DOMContentLoaded', function() {
                                    const steps = [
                                        { text: 'Creating database tables...', duration: 1000 },
                                        { text: 'Setting up admin user...', duration: 1000 },
                                        { text: 'Creating directories...', duration: 1000 },
                                        { text: 'Configuring files...', duration: 1000 },
                                        { text: 'Finalizing installation...', duration: 1000 }
                                    ];
                                    
                                    let currentStep = 0;
                                    const progressBar = document.getElementById('installProgress');
                                    const statusText = document.getElementById('installStatus');
                                    const installComplete = document.getElementById('installComplete');
                                    
                                    function updateProgress() {
                                        if (currentStep < steps.length) {
                                            const step = steps[currentStep];
                                            statusText.textContent = step.text;
                                            progressBar.style.width = ((currentStep + 1) / steps.length * 100) + '%';
                                            currentStep++;
                                            setTimeout(updateProgress, step.duration);
                                        } else {
                                            // All steps completed, show complete message
                                            statusText.textContent = 'Installation complete!';
                                            progressBar.style.width = '100%';
                                            installComplete.style.display = 'block';
                                            
                                            // Submit form to complete installation
                                            setTimeout(function() {
                                                document.getElementById('installForm').submit();
                                            }, 1000);
                                        }
                                    }
                                    
                                    // Start progress
                                    setTimeout(updateProgress, 1000);
                                });
                            </script>
                            
                        <?php elseif ($currentStep === 'complete'): ?>
                            <div class="text-center">
                                <div class="mb-4">
                                    <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                                </div>
                                
                                <h2>Installation Complete!</h2>
                                <p class="lead">Your forum has been successfully installed and configured.</p>
                                
                                <div class="row mt-4">
                                    <div class="col-md-6">
                                        <div class="feature-card">
                                            <h5><i class="fas fa-globe text-primary me-2"></i>Visit Forum</h5>
                                            <p class="text-muted">Your forum is now live and ready to use</p>
                                            <a href="index.php" class="btn btn-primary btn-sm">Go to Forum</a>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="feature-card">
                                            <h5><i class="fas fa-cog text-primary me-2"></i>Admin Panel</h5>
                                            <p class="text-muted">Manage your forum settings</p>
                                            <a href="index.php?page=admin" class="btn btn-outline-primary btn-sm">Admin Panel</a>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mt-4">
                                    <h5>What's Next?</h5>
                                    <ul class="list-unstyled">
                                        <li><i class="fas fa-check text-success me-2"></i>Customize your forum settings</li>
                                        <li><i class="fas fa-check text-success me-2"></i>Create categories and topics</li>
                                        <li><i class="fas fa-check text-success me-2"></i>Invite users to join</li>
                                        <li><i class="fas fa-check text-success me-2"></i>Configure additional features</li>
                                    </ul>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>