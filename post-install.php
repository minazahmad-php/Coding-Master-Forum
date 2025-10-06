<?php
/**
 * Post-Installation Script
 * Runs after successful installation to secure the system
 */

// Check if installation is complete
if (!file_exists('.installed')) {
    die('❌ Installation not completed yet!');
}

// Secure installation files
function secureInstallation() {
    try {
        // Rename install.php to prevent re-installation
        if (file_exists('install.php')) {
            rename('install.php', 'install.php.disabled');
        }
        
        // Rename .htaccess_install to .htaccess
        if (file_exists('.htaccess_install')) {
            rename('.htaccess_install', '.htaccess');
        }
        
        // Set proper file permissions
        chmod('.installed', 0644);
        chmod('.env', 0644);
        chmod('storage', 0755);
        chmod('public/uploads', 0755);
        
        // Create .gitignore if not exists
        if (!file_exists('.gitignore')) {
            $gitignore = "# Environment files\n.env\n.installed\n\n# Logs\nstorage/logs/*\nstorage/cache/*\nstorage/sessions/*\nstorage/backups/*\nstorage/temp/*\n\n# Database\n*.sqlite\n*.sql\n\n# IDE\n.vscode/\n.idea/\n*.swp\n*.swo\n*~\n\n# OS\n.DS_Store\nThumbs.db\n\n# Dependencies\nvendor/\nnode_modules/\n\n# Build\npublic/build/*\npublic/hot\n\n# Install files\ninstall.php.disabled\npost-install.php\n";
            file_put_contents('.gitignore', $gitignore);
        }
        
        // Create robots.txt if not exists
        if (!file_exists('robots.txt')) {
            $robots = "User-agent: *\nAllow: /\n\n# Disallow admin areas\nDisallow: /admin/\nDisallow: /storage/\nDisallow: /database/\nDisallow: /install.php\nDisallow: /post-install.php\n\n# Sitemap\nSitemap: " . (isset($_SERVER['HTTP_HOST']) ? 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] : '') . "/sitemap.xml";
            file_put_contents('robots.txt', $robots);
        }
        
        // Create security.txt if not exists
        if (!file_exists('public/security.txt')) {
            $security = "Contact: mailto:security@example.com\nExpires: 2025-12-31T23:59:59.000Z\nPreferred-Languages: en, bn\nCanonical: " . (isset($_SERVER['HTTP_HOST']) ? 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] : '') . "/security.txt\n";
            file_put_contents('public/security.txt', $security);
        }
        
        // Create default admin user if not exists
        createDefaultAdminUser();
        
        // Create sample data
        createSampleData();
        
        return true;
        
    } catch (Exception $e) {
        error_log("Post-installation error: " . $e->getMessage());
        return false;
    }
}

function createDefaultAdminUser() {
    try {
        // Load environment
        if (file_exists('.env')) {
            $env = parse_ini_file('.env');
        } else {
            return false;
        }
        
        // Connect to database
        if ($env['DB_CONNECTION'] === 'sqlite') {
            $pdo = new PDO('sqlite:' . $env['DB_DATABASE']);
        } else {
            $dsn = "mysql:host={$env['DB_HOST']};port={$env['DB_PORT']};dbname={$env['DB_DATABASE']};charset=utf8mb4";
            $pdo = new PDO($dsn, $env['DB_USERNAME'], $env['DB_PASSWORD']);
        }
        
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Check if admin user exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
        $stmt->execute();
        $adminCount = $stmt->fetchColumn();
        
        if ($adminCount == 0) {
            // Create default admin user
            $stmt = $pdo->prepare("
                INSERT INTO users (username, email, password, first_name, last_name, role, status, email_verified_at, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, 'admin', 'active', NOW(), NOW(), NOW())
            ");
            
            $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
            $stmt->execute([
                'admin',
                'admin@example.com',
                $hashedPassword,
                'Admin',
                'User'
            ]);
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log("Error creating default admin: " . $e->getMessage());
        return false;
    }
}

function createSampleData() {
    try {
        // Load environment
        if (file_exists('.env')) {
            $env = parse_ini_file('.env');
        } else {
            return false;
        }
        
        // Connect to database
        if ($env['DB_CONNECTION'] === 'sqlite') {
            $pdo = new PDO('sqlite:' . $env['DB_DATABASE']);
        } else {
            $dsn = "mysql:host={$env['DB_HOST']};port={$env['DB_PORT']};dbname={$env['DB_DATABASE']};charset=utf8mb4";
            $pdo = new PDO($dsn, $env['DB_USERNAME'], $env['DB_PASSWORD']);
        }
        
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create sample forums
        $forums = [
            [
                'name' => 'General Discussion',
                'description' => 'General topics and discussions',
                'slug' => 'general-discussion',
                'icon' => 'fas fa-comments',
                'color' => '#007bff'
            ],
            [
                'name' => 'Technical Support',
                'description' => 'Get help with technical issues',
                'slug' => 'technical-support',
                'icon' => 'fas fa-tools',
                'color' => '#28a745'
            ],
            [
                'name' => 'Announcements',
                'description' => 'Important announcements and news',
                'slug' => 'announcements',
                'icon' => 'fas fa-bullhorn',
                'color' => '#ffc107'
            ]
        ];
        
        foreach ($forums as $forum) {
            $stmt = $pdo->prepare("
                INSERT OR IGNORE INTO forums (name, description, slug, icon, color, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([
                $forum['name'],
                $forum['description'],
                $forum['slug'],
                $forum['icon'],
                $forum['color']
            ]);
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log("Error creating sample data: " . $e->getMessage());
        return false;
    }
}

// Run post-installation if accessed directly
if (php_sapi_name() === 'cli' || (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'GET')) {
    $result = secureInstallation();
    
    if ($result) {
        echo "✅ Post-installation completed successfully!\n";
        echo "🔒 Installation files secured\n";
        echo "👤 Default admin user created (admin/admin123)\n";
        echo "📊 Sample data created\n";
        echo "🚀 Forum is ready to use!\n";
    } else {
        echo "❌ Post-installation failed!\n";
        exit(1);
    }
}
?>