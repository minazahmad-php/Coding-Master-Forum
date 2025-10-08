<?php
/**
 * KSWeb Configuration for Forum Project
 * Optimized for mobile development and local testing
 */

class KSWebConfig {
    private $config = [];
    
    public function __construct() {
        $this->initializeConfig();
    }
    
    private function initializeConfig() {
        $this->config = [
            'ksweb' => [
                'enabled' => true,
                'port' => 8080,
                'document_root' => '/storage/emulated/0/htdocs/forum',
                'php_version' => '7.4',
                'mysql_enabled' => true,
                'mysql_port' => 3306,
                'mysql_host' => 'localhost',
                'mysql_user' => 'root',
                'mysql_password' => '',
                'mysql_database' => 'forum_ksweb'
            ],
            'mobile' => [
                'responsive' => true,
                'touch_friendly' => true,
                'pwa_enabled' => true,
                'offline_support' => true,
                'push_notifications' => true,
                'biometric_auth' => true
            ],
            'performance' => [
                'cache_enabled' => true,
                'minify_css' => true,
                'minify_js' => true,
                'compress_images' => true,
                'lazy_loading' => true,
                'cdn_enabled' => false
            ],
            'security' => [
                'https_required' => false,
                'csrf_protection' => true,
                'xss_protection' => true,
                'sql_injection_protection' => true,
                'file_upload_security' => true
            ]
        ];
    }
    
    public function getConfig() {
        return $this->config;
    }
    
    public function generateKSWebConfig() {
        $config = $this->config;
        
        $kswebConfig = "
# KSWeb Configuration for Forum Project
# Generated on " . date('Y-m-d H:i:s') . "

# Server Configuration
DocumentRoot {$config['ksweb']['document_root']}
ServerPort {$config['ksweb']['port']}
ServerName coding-master.infy.uk

# PHP Configuration
PHPVersion {$config['ksweb']['php_version']}
PHPIniDir /system/etc/php.ini

# MySQL Configuration
MySQLEnabled {$config['ksweb']['mysql_enabled']}
MySQLPort {$config['ksweb']['mysql_port']}
MySQLHost {$config['ksweb']['mysql_host']}
MySQLUser {$config['ksweb']['mysql_user']}
MySQLPassword {$config['ksweb']['mysql_password']}
MySQLDatabase {$config['ksweb']['mysql_database']}

# Performance Settings
KeepAlive On
MaxKeepAliveRequests 100
KeepAliveTimeout 5

# Security Settings
ServerTokens Prod
ServerSignature Off

# Directory Settings
<Directory \"{$config['ksweb']['document_root']}\">
    AllowOverride All
    Require all granted
    Options -Indexes
</Directory>

# PHP Settings
<IfModule mod_php7.c>
    php_value upload_max_filesize 10M
    php_value post_max_size 10M
    php_value memory_limit 256M
    php_value max_execution_time 300
    php_value max_input_vars 3000
    php_value date.timezone Asia/Dhaka
</IfModule>

# Mobile Optimization
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options SAMEORIGIN
    Header always set X-XSS-Protection \"1; mode=block\"
    Header always set Referrer-Policy \"strict-origin-when-cross-origin\"
</IfModule>

# Compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/xml
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE application/xml
    AddOutputFilterByType DEFLATE application/xhtml+xml
    AddOutputFilterByType DEFLATE application/rss+xml
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/x-javascript
</IfModule>

# Caching
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css \"access plus 1 month\"
    ExpiresByType application/javascript \"access plus 1 month\"
    ExpiresByType image/png \"access plus 1 month\"
    ExpiresByType image/jpg \"access plus 1 month\"
    ExpiresByType image/jpeg \"access plus 1 month\"
    ExpiresByType image/gif \"access plus 1 month\"
    ExpiresByType image/svg+xml \"access plus 1 month\"
    ExpiresByType image/x-icon \"access plus 1 month\"
    ExpiresByType font/woff \"access plus 1 month\"
    ExpiresByType font/woff2 \"access plus 1 month\"
</IfModule>
";
        
        return $kswebConfig;
    }
    
    public function generateMobileConfig() {
        $config = $this->config;
        
        $mobileConfig = [
            'app_name' => 'Forum Project',
            'app_short_name' => 'Forum',
            'app_description' => 'Complete Forum System for Mobile',
            'app_version' => '1.0.0',
            'app_theme_color' => '#007bff',
            'app_background_color' => '#ffffff',
            'app_display' => 'standalone',
            'app_orientation' => 'portrait',
            'app_scope' => '/',
            'app_start_url' => '/',
            'app_icons' => [
                [
                    'src' => '/public/images/icon-192x192.png',
                    'sizes' => '192x192',
                    'type' => 'image/png'
                ],
                [
                    'src' => '/public/images/icon-512x512.png',
                    'sizes' => '512x512',
                    'type' => 'image/png'
                ]
            ],
            'features' => [
                'responsive_design' => true,
                'touch_friendly' => true,
                'offline_support' => true,
                'push_notifications' => true,
                'biometric_auth' => true,
                'dark_mode' => true,
                'swipe_gestures' => true,
                'pull_to_refresh' => true
            ]
        ];
        
        return $mobileConfig;
    }
    
    public function generateInstallationScript() {
        $script = "#!/bin/bash
# KSWeb Installation Script for Forum Project
# Run this script to set up the forum on KSWeb

echo \"🚀 Setting up Forum Project on KSWeb...\"

# Create directory structure
mkdir -p /storage/emulated/0/htdocs/forum
cd /storage/emulated/0/htdocs/forum

# Download and extract project
echo \"📥 Downloading Forum Project...\"
wget -O forum-project.zip https://github.com/minazahmad-php/Coding-Master-Forum/archive/cursor/forum-project-structure-and-auto-install-60fd.zip
unzip forum-project.zip
mv Coding-Master-Forum-cursor-forum-project-structure-and-auto-install-60fd/* .
rm -rf Coding-Master-Forum-cursor-forum-project-structure-and-auto-install-60fd
rm forum-project.zip

# Set permissions
echo \"🔒 Setting permissions...\"
chmod -R 755 .
chmod -R 777 storage
chmod -R 777 public/uploads

# Create .htaccess for KSWeb
echo \"📝 Creating KSWeb configuration...\"
cat > .htaccess << 'EOF'
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# KSWeb specific settings
php_value upload_max_filesize 10M
php_value post_max_size 10M
php_value memory_limit 256M
php_value max_execution_time 300
php_value date.timezone Asia/Dhaka

# Mobile optimization
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options SAMEORIGIN
Header always set X-XSS-Protection \"1; mode=block\"

# Compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/plain text/html text/xml text/css application/xml application/xhtml+xml application/rss+xml application/javascript application/x-javascript
</IfModule>
EOF

# Create mobile manifest
echo \"📱 Creating mobile manifest...\"
cat > public/manifest.json << 'EOF'
{
    \"name\": \"Forum Project\",
    \"short_name\": \"Forum\",
    \"description\": \"Complete Forum System for Mobile\",
    \"version\": \"1.0.0\",
    \"theme_color\": \"#007bff\",
    \"background_color\": \"#ffffff\",
    \"display\": \"standalone\",
    \"orientation\": \"portrait\",
    \"scope\": \"/\",
    \"start_url\": \"/\",
    \"icons\": [
        {
            \"src\": \"/public/images/icon-192x192.png\",
            \"sizes\": \"192x192\",
            \"type\": \"image/png\"
        },
        {
            \"src\": \"/public/images/icon-512x512.png\",
            \"sizes\": \"512x512\",
            \"type\": \"image/png\"
        }
    ]
}
EOF

# Create service worker
echo \"⚙️ Creating service worker...\"
cat > public/sw.js << 'EOF'
const CACHE_NAME = 'forum-project-v1';
const urlsToCache = [
    '/',
    '/public/css/style.css',
    '/public/js/app.js',
    '/public/images/logo.png'
];

self.addEventListener('install', function(event) {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(function(cache) {
                return cache.addAll(urlsToCache);
            })
    );
});

self.addEventListener('fetch', function(event) {
    event.waitUntil(
        caches.match(event.request)
            .then(function(response) {
                if (response) {
                    return response;
                }
                return fetch(event.request);
            }
        )
    );
});
EOF

echo \"✅ Forum Project setup complete!\"
echo \"🌐 Access your forum at: http://coding-master.infy.uk:8080\"
echo \"📱 Mobile app available at: http://coding-master.infy.uk:8080\"
echo \"🔧 Admin panel: http://coding-master.infy.uk:8080/admin\"
echo \"\"
echo \"📋 Next steps:\"
echo \"1. Start KSWeb server\"
echo \"2. Open http://coding-master.infy.uk:8080/install.php\"
echo \"3. Follow the installation wizard\"
echo \"4. Enjoy your forum!\"
";
        
        return $script;
    }
}

// Run if called directly
if (php_sapi_name() === 'cli') {
    $ksweb = new KSWebConfig();
    
    echo "KSWeb Configuration Generator\n";
    echo "============================\n\n";
    
    echo "Configuration:\n";
    print_r($ksweb->getConfig());
    
    echo "\nKSWeb Config:\n";
    echo $ksweb->generateKSWebConfig();
    
    echo "\nMobile Config:\n";
    print_r($ksweb->generateMobileConfig());
    
    echo "\nInstallation Script:\n";
    echo $ksweb->generateInstallationScript();
}
?>