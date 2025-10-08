<?php
/**
 * Free Hosting Configuration for Forum Project
 * Optimized for free hosting services like InfinityFree, 000webhost, etc.
 */

class FreeHostingConfig {
    private $config = [];
    
    public function __construct() {
        $this->initializeConfig();
    }
    
    private function initializeConfig() {
        $this->config = [
            'free_hosting' => [
                'enabled' => true,
                'domain' => 'coding-master.infy.uk',
                'subdomain' => 'coding-master',
                'tld' => 'infy.uk',
                'php_version' => '7.4',
                'mysql_enabled' => true,
                'mysql_host' => 'localhost',
                'mysql_port' => '3306',
                'mysql_user' => 'root',
                'mysql_password' => '',
                'mysql_database' => 'u123456789_forum',
                'file_upload_limit' => '2M',
                'memory_limit' => '128M',
                'max_execution_time' => '30',
                'max_input_vars' => '1000'
            ],
            'optimization' => [
                'minify_html' => true,
                'minify_css' => true,
                'minify_js' => true,
                'compress_images' => true,
                'lazy_loading' => true,
                'caching' => true,
                'gzip_compression' => true,
                'browser_caching' => true
            ],
            'security' => [
                'https_required' => false,
                'csrf_protection' => true,
                'xss_protection' => true,
                'sql_injection_protection' => true,
                'file_upload_security' => true,
                'rate_limiting' => true,
                'input_validation' => true,
                'output_escaping' => true
            ],
            'features' => [
                'responsive_design' => true,
                'mobile_optimized' => true,
                'pwa_support' => true,
                'offline_support' => false, // Disabled for free hosting
                'push_notifications' => false, // Disabled for free hosting
                'real_time_chat' => false, // Disabled for free hosting
                'file_uploads' => true,
                'user_registration' => true,
                'email_verification' => false, // Disabled for free hosting
                'admin_panel' => true,
                'moderation' => true,
                'search' => true,
                'themes' => true,
                'plugins' => false, // Disabled for free hosting
                'api' => true,
                'analytics' => true
            ]
        ];
    }
    
    public function getConfig() {
        return $this->config;
    }
    
    public function generateFreeHostingEnv() {
        $config = $this->config;
        
        $env = "# Forum Project - Free Hosting Environment\n";
        $env .= "# Generated on " . date('Y-m-d H:i:s') . "\n";
        $env .= "# Domain: {$config['free_hosting']['domain']}\n";
        $env .= "# Free Hosting: Yes\n\n";
        
        // App Configuration
        $env .= "APP_NAME=\"Forum Project - Free Hosting\"\n";
        $env .= "APP_ENV=production\n";
        $env .= "APP_DEBUG=false\n";
        $env .= "APP_URL=https://{$config['free_hosting']['domain']}\n";
        $env .= "APP_TIMEZONE=Asia/Dhaka\n\n";
        
        // Free Hosting Configuration
        $env .= "FREE_HOSTING_ENABLED=true\n";
        $env .= "FREE_HOSTING_DOMAIN={$config['free_hosting']['domain']}\n";
        $env .= "FREE_HOSTING_SUBDOMAIN={$config['free_hosting']['subdomain']}\n";
        $env .= "FREE_HOSTING_TLD={$config['free_hosting']['tld']}\n";
        $env .= "FREE_HOSTING_PHP_VERSION={$config['free_hosting']['php_version']}\n";
        $env .= "FREE_HOSTING_MYSQL_ENABLED={$config['free_hosting']['mysql_enabled']}\n";
        $env .= "FREE_HOSTING_FILE_UPLOAD_LIMIT={$config['free_hosting']['file_upload_limit']}\n";
        $env .= "FREE_HOSTING_MEMORY_LIMIT={$config['free_hosting']['memory_limit']}\n";
        $env .= "FREE_HOSTING_MAX_EXECUTION_TIME={$config['free_hosting']['max_execution_time']}\n";
        $env .= "FREE_HOSTING_MAX_INPUT_VARS={$config['free_hosting']['max_input_vars']}\n\n";
        
        // Database Configuration
        $env .= "DB_CONNECTION=mysql\n";
        $env .= "DB_HOST={$config['free_hosting']['mysql_host']}\n";
        $env .= "DB_PORT={$config['free_hosting']['mysql_port']}\n";
        $env .= "DB_DATABASE={$config['free_hosting']['mysql_database']}\n";
        $env .= "DB_USERNAME={$config['free_hosting']['mysql_user']}\n";
        $env .= "DB_PASSWORD={$config['free_hosting']['mysql_password']}\n\n";
        
        // Cache Configuration (File-based for free hosting)
        $env .= "CACHE_DRIVER=file\n";
        $env .= "CACHE_PREFIX=free_forum_\n";
        $env .= "CACHE_TTL=3600\n\n";
        
        // Session Configuration (File-based for free hosting)
        $env .= "SESSION_DRIVER=file\n";
        $env .= "SESSION_LIFETIME=120\n";
        $env .= "SESSION_ENCRYPT=false\n";
        $env .= "SESSION_PATH=/\n";
        $env .= "SESSION_DOMAIN={$config['free_hosting']['domain']}\n";
        $env .= "SESSION_SECURE_COOKIE=false\n";
        $env .= "SESSION_HTTP_ONLY=true\n";
        $env .= "SESSION_SAME_SITE=lax\n\n";
        
        // Free Hosting Optimizations
        $env .= "FREE_HOSTING_OPTIMIZATION=true\n";
        $env .= "FREE_HOSTING_MINIFY_HTML=true\n";
        $env .= "FREE_HOSTING_MINIFY_CSS=true\n";
        $env .= "FREE_HOSTING_MINIFY_JS=true\n";
        $env .= "FREE_HOSTING_COMPRESS_IMAGES=true\n";
        $env .= "FREE_HOSTING_LAZY_LOADING=true\n";
        $env .= "FREE_HOSTING_CACHING=true\n";
        $env .= "FREE_HOSTING_GZIP_COMPRESSION=true\n";
        $env .= "FREE_HOSTING_BROWSER_CACHING=true\n\n";
        
        // Security (Free hosting optimized)
        $env .= "APP_KEY=" . $this->generateAppKey() . "\n";
        $env .= "JWT_SECRET=" . $this->generateJWTSecret() . "\n";
        $env .= "ENCRYPTION_KEY=" . $this->generateEncryptionKey() . "\n\n";
        
        // Rate Limiting (Free hosting optimized)
        $env .= "RATE_LIMIT_ENABLED=true\n";
        $env .= "RATE_LIMIT_MAX_ATTEMPTS=10\n";
        $env .= "RATE_LIMIT_DECAY_MINUTES=1\n\n";
        
        // Features (Free hosting compatible)
        $env .= "FEATURES_RESPONSIVE_DESIGN=true\n";
        $env .= "FEATURES_MOBILE_OPTIMIZED=true\n";
        $env .= "FEATURES_PWA_SUPPORT=true\n";
        $env .= "FEATURES_OFFLINE_SUPPORT=false\n";
        $env .= "FEATURES_PUSH_NOTIFICATIONS=false\n";
        $env .= "FEATURES_REAL_TIME_CHAT=false\n";
        $env .= "FEATURES_FILE_UPLOADS=true\n";
        $env .= "FEATURES_USER_REGISTRATION=true\n";
        $env .= "FEATURES_EMAIL_VERIFICATION=false\n";
        $env .= "FEATURES_ADMIN_PANEL=true\n";
        $env .= "FEATURES_MODERATION=true\n";
        $env .= "FEATURES_SEARCH=true\n";
        $env .= "FEATURES_THEMES=true\n";
        $env .= "FEATURES_PLUGINS=false\n";
        $env .= "FEATURES_API=true\n";
        $env .= "FEATURES_ANALYTICS=true\n\n";
        
        // Email Configuration (Disabled for free hosting)
        $env .= "MAIL_MAILER=log\n";
        $env .= "MAIL_HOST=localhost\n";
        $env .= "MAIL_PORT=587\n";
        $env .= "MAIL_USERNAME=null\n";
        $env .= "MAIL_PASSWORD=null\n";
        $env .= "MAIL_ENCRYPTION=null\n";
        $env .= "MAIL_FROM_ADDRESS=noreply@{$config['free_hosting']['domain']}\n";
        $env .= "MAIL_FROM_NAME=\"{$config['free_hosting']['domain']}\"\n\n";
        
        // Logging (File-based for free hosting)
        $env .= "LOG_CHANNEL=file\n";
        $env .= "LOG_LEVEL=error\n";
        $env .= "LOG_FILE=storage/logs/forum.log\n\n";
        
        return $env;
    }
    
    private function generateAppKey() {
        return 'base64:' . base64_encode(random_bytes(32));
    }
    
    private function generateJWTSecret() {
        return bin2hex(random_bytes(32));
    }
    
    private function generateEncryptionKey() {
        return bin2hex(random_bytes(16));
    }
    
    public function generateFreeHostingHtaccess() {
        $config = $this->config;
        
        $htaccess = "# Free Hosting Optimized .htaccess for Forum Project\n";
        $htaccess .= "# Generated on " . date('Y-m-d H:i:s') . "\n";
        $htaccess .= "# Domain: {$config['free_hosting']['domain']}\n\n";
        
        // Basic Rewrite Rules
        $htaccess .= "RewriteEngine On\n";
        $htaccess .= "RewriteCond %{REQUEST_FILENAME} !-f\n";
        $htaccess .= "RewriteCond %{REQUEST_FILENAME} !-d\n";
        $htaccess .= "RewriteRule ^(.*)$ index.php [QSA,L]\n\n";
        
        // Free Hosting PHP Settings
        $htaccess .= "# Free Hosting PHP Settings\n";
        $htaccess .= "php_value upload_max_filesize {$config['free_hosting']['file_upload_limit']}\n";
        $htaccess .= "php_value post_max_size {$config['free_hosting']['file_upload_limit']}\n";
        $htaccess .= "php_value memory_limit {$config['free_hosting']['memory_limit']}\n";
        $htaccess .= "php_value max_execution_time {$config['free_hosting']['max_execution_time']}\n";
        $htaccess .= "php_value max_input_vars {$config['free_hosting']['max_input_vars']}\n";
        $htaccess .= "php_value date.timezone Asia/Dhaka\n";
        $htaccess .= "php_value session.gc_maxlifetime 7200\n";
        $htaccess .= "php_value session.cookie_lifetime 0\n\n";
        
        // Security Headers
        $htaccess .= "# Security Headers\n";
        $htaccess .= "<IfModule mod_headers.c>\n";
        $htaccess .= "    Header always set X-Content-Type-Options nosniff\n";
        $htaccess .= "    Header always set X-Frame-Options SAMEORIGIN\n";
        $htaccess .= "    Header always set X-XSS-Protection \"1; mode=block\"\n";
        $htaccess .= "    Header always set Referrer-Policy \"strict-origin-when-cross-origin\"\n";
        $htaccess .= "    Header always set Permissions-Policy \"geolocation=(), microphone=(), camera=()\"\n";
        $htaccess .= "</IfModule>\n\n";
        
        // Gzip Compression
        $htaccess .= "# Gzip Compression\n";
        $htaccess .= "<IfModule mod_deflate.c>\n";
        $htaccess .= "    AddOutputFilterByType DEFLATE text/plain\n";
        $htaccess .= "    AddOutputFilterByType DEFLATE text/html\n";
        $htaccess .= "    AddOutputFilterByType DEFLATE text/xml\n";
        $htaccess .= "    AddOutputFilterByType DEFLATE text/css\n";
        $htaccess .= "    AddOutputFilterByType DEFLATE application/xml\n";
        $htaccess .= "    AddOutputFilterByType DEFLATE application/xhtml+xml\n";
        $htaccess .= "    AddOutputFilterByType DEFLATE application/rss+xml\n";
        $htaccess .= "    AddOutputFilterByType DEFLATE application/javascript\n";
        $htaccess .= "    AddOutputFilterByType DEFLATE application/x-javascript\n";
        $htaccess .= "</IfModule>\n\n";
        
        // Browser Caching
        $htaccess .= "# Browser Caching\n";
        $htaccess .= "<IfModule mod_expires.c>\n";
        $htaccess .= "    ExpiresActive On\n";
        $htaccess .= "    ExpiresByType text/css \"access plus 1 week\"\n";
        $htaccess .= "    ExpiresByType application/javascript \"access plus 1 week\"\n";
        $htaccess .= "    ExpiresByType image/png \"access plus 1 month\"\n";
        $htaccess .= "    ExpiresByType image/jpg \"access plus 1 month\"\n";
        $htaccess .= "    ExpiresByType image/jpeg \"access plus 1 month\"\n";
        $htaccess .= "    ExpiresByType image/gif \"access plus 1 month\"\n";
        $htaccess .= "    ExpiresByType image/svg+xml \"access plus 1 month\"\n";
        $htaccess .= "    ExpiresByType image/x-icon \"access plus 1 month\"\n";
        $htaccess .= "    ExpiresByType font/woff \"access plus 1 month\"\n";
        $htaccess .= "    ExpiresByType font/woff2 \"access plus 1 month\"\n";
        $htaccess .= "</IfModule>\n\n";
        
        // File Access Restrictions
        $htaccess .= "# File Access Restrictions\n";
        $htaccess .= "<Files \".env\">\n";
        $htaccess .= "    Order allow,deny\n";
        $htaccess .= "    Deny from all\n";
        $htaccess .= "</Files>\n\n";
        
        $htaccess .= "<Files \"*.log\">\n";
        $htaccess .= "    Order allow,deny\n";
        $htaccess .= "    Deny from all\n";
        $htaccess .= "</Files>\n\n";
        
        $htaccess .= "<Files \"composer.json\">\n";
        $htaccess .= "    Order allow,deny\n";
        $htaccess .= "    Deny from all\n";
        $htaccess .= "</Files>\n\n";
        
        $htaccess .= "<Files \"composer.lock\">\n";
        $htaccess .= "    Order allow,deny\n";
        $htaccess .= "    Deny from all\n";
        $htaccess .= "</Files>\n\n";
        
        // Directory Restrictions
        $htaccess .= "# Directory Restrictions\n";
        $htaccess .= "<Directory \"storage\">\n";
        $htaccess .= "    Order allow,deny\n";
        $htaccess .= "    Deny from all\n";
        $htaccess .= "</Directory>\n\n";
        
        $htaccess .= "<Directory \"database\">\n";
        $htaccess .= "    Order allow,deny\n";
        $htaccess .= "    Deny from all\n";
        $htaccess .= "</Directory>\n\n";
        
        $htaccess .= "<Directory \"config\">\n";
        $htaccess .= "    Order allow,deny\n";
        $htaccess .= "    Deny from all\n";
        $htaccess .= "</Directory>\n\n";
        
        return $htaccess;
    }
    
    public function generateFreeHostingInstallScript() {
        $script = "#!/bin/bash
# Free Hosting Installation Script for Forum Project
# Complete setup for free hosting services

echo \"🚀 Setting up Forum Project on Free Hosting...\"
echo \"============================================\"

# Check if running on free hosting
if [ ! -d \"public_html\" ] && [ ! -d \"htdocs\" ]; then
    echo \"❌ This script is designed for free hosting services\"
    echo \"Please run this on a free hosting service like InfinityFree, 000webhost, etc.\"
    exit 1
fi

# Determine hosting directory
if [ -d \"public_html\" ]; then
    HOSTING_DIR=\"public_html\"
elif [ -d \"htdocs\" ]; then
    HOSTING_DIR=\"htdocs\"
else
    HOSTING_DIR=\".\"
fi

echo \"📁 Using hosting directory: $HOSTING_DIR\"
cd $HOSTING_DIR

# Download and extract project
echo \"📥 Downloading Forum Project...\"
wget -O forum-project.zip https://github.com/minazahmad-php/Coding-Master-Forum/archive/cursor/forum-project-structure-and-auto-install-60fd.zip

if [ $? -ne 0 ]; then
    echo \"❌ Failed to download project. Please check your internet connection.\"
    exit 1
fi

echo \"📦 Extracting project...\"
unzip forum-project.zip
mv Coding-Master-Forum-cursor-forum-project-structure-and-auto-install-60fd/* .
rm -rf Coding-Master-Forum-cursor-forum-project-structure-and-auto-install-60fd
rm forum-project.zip

# Set permissions
echo \"🔒 Setting permissions...\"
chmod -R 755 .
chmod -R 777 storage
chmod -R 777 public/uploads

# Create free hosting optimized .htaccess
echo \"📝 Creating free hosting configuration...\"
cat > .htaccess << 'EOF'
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# Free Hosting PHP Settings
php_value upload_max_filesize 2M
php_value post_max_size 2M
php_value memory_limit 128M
php_value max_execution_time 30
php_value max_input_vars 1000
php_value date.timezone Asia/Dhaka
php_value session.gc_maxlifetime 7200
php_value session.cookie_lifetime 0

# Security Headers
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options SAMEORIGIN
    Header always set X-XSS-Protection \"1; mode=block\"
    Header always set Referrer-Policy \"strict-origin-when-cross-origin\"
</IfModule>

# Gzip Compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/plain text/html text/xml text/css application/xml application/xhtml+xml application/rss+xml application/javascript application/x-javascript
</IfModule>

# Browser Caching
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css \"access plus 1 week\"
    ExpiresByType application/javascript \"access plus 1 week\"
    ExpiresByType image/png \"access plus 1 month\"
    ExpiresByType image/jpg \"access plus 1 month\"
    ExpiresByType image/jpeg \"access plus 1 month\"
    ExpiresByType image/gif \"access plus 1 month\"
    ExpiresByType image/svg+xml \"access plus 1 month\"
    ExpiresByType image/x-icon \"access plus 1 month\"
    ExpiresByType font/woff \"access plus 1 month\"
    ExpiresByType font/woff2 \"access plus 1 month\"
</IfModule>

# File Access Restrictions
<Files \".env\">
    Order allow,deny
    Deny from all
</Files>

<Files \"*.log\">
    Order allow,deny
    Deny from all
</Files>

<Directory \"storage\">
    Order allow,deny
    Deny from all
</Directory>

<Directory \"database\">
    Order allow,deny
    Deny from all
</Directory>

<Directory \"config\">
    Order allow,deny
    Deny from all
</Directory>
EOF

# Create free hosting manifest
echo \"📱 Creating free hosting manifest...\"
cat > public/manifest.json << 'EOF'
{
    \"name\": \"Forum Project - Free Hosting\",
    \"short_name\": \"Forum\",
    \"description\": \"Complete Forum System for Free Hosting\",
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
    ],
    \"features\": [
        \"responsive_design\",
        \"mobile_optimized\",
        \"free_hosting_optimized\",
        \"pwa_support\",
        \"admin_panel\",
        \"user_registration\",
        \"moderation\",
        \"search\",
        \"themes\",
        \"api\",
        \"analytics\"
    ]
}
EOF

# Create free hosting service worker
echo \"⚙️ Creating free hosting service worker...\"
cat > public/sw.js << 'EOF'
const CACHE_NAME = 'forum-project-free-hosting-v1';
const urlsToCache = [
    '/',
    '/public/css/style.css',
    '/public/js/app.js',
    '/public/images/logo.png',
    '/public/images/icon-192x192.png',
    '/public/images/icon-512x512.png'
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

# Create free hosting installation completion file
echo \"✅ Creating free hosting installation completion file...\"
cat > .free-hosting-installed << 'EOF'
Free Hosting Installation Complete
==================================
Installed on: $(date)
Location: $HOSTING_DIR
Free hosting optimized: Yes
Mobile optimized: Yes
PWA enabled: Yes
Free hosting compatible: Yes

Access URLs:
- Main Forum: https://coding-master.infy.uk
- Admin Panel: https://coding-master.infy.uk/admin
- Installation: https://coding-master.infy.uk/free-hosting-install.php

Free Hosting Features:
- Free hosting optimized
- Mobile responsive
- PWA support
- Admin panel
- User registration
- Moderation
- Search
- Themes
- API
- Analytics

Next Steps:
1. Open https://coding-master.infy.uk/free-hosting-install.php
2. Follow the free hosting installation wizard
3. Enjoy your free hosting forum!
EOF

echo \"✅ Free Hosting Forum Project setup complete!\"
echo \"\"
echo \"🌐 Access URLs:\"
echo \"   Main Forum: https://coding-master.infy.uk\"
echo \"   Admin Panel: https://coding-master.infy.uk/admin\"
echo \"   Installation: https://coding-master.infy.uk/free-hosting-install.php\"
echo \"\"
echo \"📱 Free Hosting Features:\"
echo \"   ✅ Free Hosting Optimized\"
echo \"   ✅ Mobile Responsive\"
echo \"   ✅ PWA Support\"
echo \"   ✅ Admin Panel\"
echo \"   ✅ User Registration\"
echo \"   ✅ Moderation\"
echo \"   ✅ Search\"
echo \"   ✅ Themes\"
echo \"   ✅ API\"
echo \"   ✅ Analytics\"
echo \"\"
echo \"📋 Next steps:\"
echo \"1. Open https://coding-master.infy.uk/free-hosting-install.php\"
echo \"2. Follow the free hosting installation wizard\"
echo \"3. Enjoy your free hosting forum!\"
echo \"\"
echo \"🎉 Free hosting installation complete! Your forum is ready!\"
";
        
        return $script;
    }
}

// Run if called directly
if (php_sapi_name() === 'cli') {
    $freeHosting = new FreeHostingConfig();
    
    echo "Free Hosting Configuration Generator\n";
    echo "===================================\n\n";
    
    echo "Configuration:\n";
    print_r($freeHosting->getConfig());
    
    echo "\nFree Hosting Env:\n";
    echo $freeHosting->generateFreeHostingEnv();
    
    echo "\nFree Hosting .htaccess:\n";
    echo $freeHosting->generateFreeHostingHtaccess();
    
    echo "\nFree Hosting Install Script:\n";
    echo $freeHosting->generateFreeHostingInstallScript();
}
?>