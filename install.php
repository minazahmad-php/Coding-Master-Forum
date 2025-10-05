<?php
/**
 * Forum Installation Script
 * Automated installation and setup for the forum application
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

// Check if already installed
if (file_exists(CONFIG_PATH . '/database.php')) {
    header('Location: index.php');
    exit;
}

// Installation process
$step = $_GET['step'] ?? 1;
$error = '';
$success = '';

// Handle form submissions
if ($_POST) {
    switch ($step) {
        case 1:
            // Database configuration
            $db_host = $_POST['db_host'] ?? 'localhost';
            $db_name = $_POST['db_name'] ?? '';
            $db_user = $_POST['db_user'] ?? '';
            $db_pass = $_POST['db_pass'] ?? '';
            
            if (empty($db_name) || empty($db_user)) {
                $error = 'Database name and username are required';
            } else {
                // Test database connection
                try {
                    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    
                    // Save database config
                    $config = [
                        'host' => $db_host,
                        'database' => $db_name,
                        'username' => $db_user,
                        'password' => $db_pass,
                        'charset' => 'utf8mb4',
                        'collation' => 'utf8mb4_unicode_ci'
                    ];
                    
                    if (!is_dir(CONFIG_PATH)) {
                        mkdir(CONFIG_PATH, 0755, true);
                    }
                    
                    file_put_contents(CONFIG_PATH . '/database.php', '<?php return ' . var_export($config, true) . ';');
                    
                    header('Location: install.php?step=2');
                    exit;
                } catch (PDOException $e) {
                    $error = 'Database connection failed: ' . $e->getMessage();
                }
            }
            break;
            
        case 2:
            // Site configuration
            $site_name = $_POST['site_name'] ?? 'My Forum';
            $site_url = $_POST['site_url'] ?? 'https://coding-master.infy.uk';
            $admin_email = $_POST['admin_email'] ?? '';
            $admin_username = $_POST['admin_username'] ?? 'admin';
            $admin_password = $_POST['admin_password'] ?? '';
            
            if (empty($admin_email) || empty($admin_password)) {
                $error = 'Admin email and password are required';
            } else {
                // Create app config
                $app_config = [
                    'name' => $site_name,
                    'url' => $site_url,
                    'timezone' => 'Asia/Dhaka',
                    'debug' => false,
                    'maintenance' => false
                ];
                
                file_put_contents(CONFIG_PATH . '/app.php', '<?php return ' . var_export($app_config, true) . ';');
                
                // Create other config files
                $mail_config = [
                    'driver' => 'smtp',
                    'host' => 'smtp.gmail.com',
                    'port' => 587,
                    'username' => '',
                    'password' => '',
                    'encryption' => 'tls',
                    'from' => ['address' => $admin_email, 'name' => $site_name]
                ];
                
                file_put_contents(CONFIG_PATH . '/mail.php', '<?php return ' . var_export($mail_config, true) . ';');
                
                // Create .env file
                $env_content = "APP_NAME=\"$site_name\"\n";
                $env_content .= "APP_URL=$site_url\n";
                $env_content .= "APP_DEBUG=false\n";
                $env_content .= "DB_HOST=" . ($_SESSION['db_host'] ?? 'localhost') . "\n";
                $env_content .= "DB_DATABASE=" . ($_SESSION['db_name'] ?? '') . "\n";
                $env_content .= "DB_USERNAME=" . ($_SESSION['db_user'] ?? '') . "\n";
                $env_content .= "DB_PASSWORD=" . ($_SESSION['db_pass'] ?? '') . "\n";
                
                file_put_contents(APP_ROOT . '/.env', $env_content);
                
                header('Location: install.php?step=3');
                exit;
            }
            break;
            
        case 3:
            // Run database migrations and create admin user
            try {
                // Load database config
                $db_config = include CONFIG_PATH . '/database.php';
                $pdo = new PDO("mysql:host={$db_config['host']};dbname={$db_config['database']}", 
                              $db_config['username'], $db_config['password']);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Run migrations
                $migrations = glob(APP_ROOT . '/database/migrations/*.php');
                sort($migrations);
                
                foreach ($migrations as $migration) {
                    $sql = include $migration;
                    $pdo->exec($sql);
                }
                
                // Run seeders
                $seeders = glob(APP_ROOT . '/database/seeders/*.php');
                sort($seeders);
                
                foreach ($seeders as $seeder) {
                    $sql = include $seeder;
                    $pdo->exec($sql);
                }
                
                // Create admin user
                $admin_username = $_SESSION['admin_username'] ?? 'admin';
                $admin_password = $_SESSION['admin_password'] ?? '';
                $admin_email = $_SESSION['admin_email'] ?? '';
                
                $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, status, created_at) VALUES (?, ?, ?, 'admin', 'active', NOW())");
                $stmt->execute([$admin_username, $admin_email, $hashed_password]);
                
                // Create default forum categories
                $default_categories = [
                    ['name' => 'General Discussion', 'description' => 'General forum discussions', 'sort_order' => 1],
                    ['name' => 'Announcements', 'description' => 'Important announcements', 'sort_order' => 2],
                    ['name' => 'Help & Support', 'description' => 'Get help and support', 'sort_order' => 3]
                ];
                
                foreach ($default_categories as $category) {
                    $stmt = $pdo->prepare("INSERT INTO categories (name, description, sort_order, created_at) VALUES (?, ?, ?, NOW())");
                    $stmt->execute([$category['name'], $category['description'], $category['sort_order']]);
                }
                
                $success = 'Installation completed successfully! You can now access your forum.';
                $step = 4;
                
            } catch (Exception $e) {
                $error = 'Installation failed: ' . $e->getMessage();
            }
            break;
    }
}

// Store form data in session
if ($_POST) {
    session_start();
    foreach ($_POST as $key => $value) {
        $_SESSION[$key] = $value;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forum Installation</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="email"], input[type="password"] { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #005a87; }
        .error { color: red; margin-bottom: 15px; }
        .success { color: green; margin-bottom: 15px; }
        .step { background: #f9f9f9; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <h1>Forum Installation</h1>
    
    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    
    <?php if ($step == 1): ?>
        <div class="step">
            <h2>Step 1: Database Configuration</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="db_host">Database Host:</label>
                    <input type="text" id="db_host" name="db_host" value="localhost" required>
                </div>
                <div class="form-group">
                    <label for="db_name">Database Name:</label>
                    <input type="text" id="db_name" name="db_name" required>
                </div>
                <div class="form-group">
                    <label for="db_user">Database Username:</label>
                    <input type="text" id="db_user" name="db_user" required>
                </div>
                <div class="form-group">
                    <label for="db_pass">Database Password:</label>
                    <input type="password" id="db_pass" name="db_pass">
                </div>
                <button type="submit">Test Connection & Continue</button>
            </form>
        </div>
    <?php elseif ($step == 2): ?>
        <div class="step">
            <h2>Step 2: Site Configuration</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="site_name">Site Name:</label>
                    <input type="text" id="site_name" name="site_name" value="My Forum" required>
                </div>
                <div class="form-group">
                    <label for="site_url">Site URL:</label>
                    <input type="text" id="site_url" name="site_url" value="https://coding-master.infy.uk" required>
                </div>
                <div class="form-group">
                    <label for="admin_email">Admin Email:</label>
                    <input type="email" id="admin_email" name="admin_email" required>
                </div>
                <div class="form-group">
                    <label for="admin_username">Admin Username:</label>
                    <input type="text" id="admin_username" name="admin_username" value="admin" required>
                </div>
                <div class="form-group">
                    <label for="admin_password">Admin Password:</label>
                    <input type="password" id="admin_password" name="admin_password" required>
                </div>
                <button type="submit">Continue</button>
            </form>
        </div>
    <?php elseif ($step == 3): ?>
        <div class="step">
            <h2>Step 3: Final Setup</h2>
            <p>Setting up database tables and creating admin user...</p>
            <form method="POST">
                <button type="submit">Complete Installation</button>
            </form>
        </div>
    <?php elseif ($step == 4): ?>
        <div class="step">
            <h2>Installation Complete!</h2>
            <p>Your forum has been successfully installed. You can now:</p>
            <ul>
                <li><a href="index.php">Visit your forum</a></li>
                <li><a href="admin/">Access admin panel</a></li>
            </ul>
        </div>
    <?php endif; ?>
</body>
</html>