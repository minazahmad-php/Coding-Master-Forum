<?php
/**
 * Auto Web Server Setup
 * Automatically configures Apache/Nginx for the forum
 */

function setupWebServer() {
    $serverType = detectWebServer();
    
    if ($serverType === 'apache') {
        setupApache();
    } elseif ($serverType === 'nginx') {
        setupNginx();
    } else {
        echo "⚠️ Web server not detected, manual configuration required\n";
    }
}

function detectWebServer() {
    // Check for Apache
    if (function_exists('apache_get_version')) {
        return 'apache';
    }
    
    // Check for Nginx
    if (strpos($_SERVER['SERVER_SOFTWARE'] ?? '', 'nginx') !== false) {
        return 'nginx';
    }
    
    // Check for Apache in headers
    if (isset($_SERVER['SERVER_SOFTWARE']) && strpos($_SERVER['SERVER_SOFTWARE'], 'Apache') !== false) {
        return 'apache';
    }
    
    return 'unknown';
}

function setupApache() {
    echo "🔧 Configuring Apache...\n";
    
    // Create .htaccess file
    $htaccess = generateApacheConfig();
    file_put_contents('.htaccess', $htaccess);
    
    // Create virtual host configuration
    $vhost = generateApacheVHost();
    $vhostFile = 'apache-vhost.conf';
    file_put_contents($vhostFile, $vhost);
    
    echo "✅ Apache configuration created!\n";
    echo "📁 Virtual host config: $vhostFile\n";
    echo "📁 .htaccess file: .htaccess\n";
}

function setupNginx() {
    echo "🔧 Configuring Nginx...\n";
    
    // Create nginx configuration
    $nginx = generateNginxConfig();
    $nginxFile = 'nginx.conf';
    file_put_contents($nginxFile, $nginx);
    
    echo "✅ Nginx configuration created!\n";
    echo "📁 Nginx config: $nginxFile\n";
}

function generateApacheConfig() {
    $appUrl = $_SESSION['env_config']['app_url'] ?? 'http://localhost';
    $domain = parse_url($appUrl, PHP_URL_HOST);
    
    return "# Forum Project - Apache Configuration
# Generated on " . date('Y-m-d H:i:s') . "

RewriteEngine On

# Handle Angular and other SPA routes
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# Security headers
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection \"1; mode=block\"
Header always set Referrer-Policy \"strict-origin-when-cross-origin\"
Header always set Content-Security-Policy \"default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data:; connect-src 'self';\"
Header always set Strict-Transport-Security \"max-age=31536000; includeSubDomains; preload\"
Header always set Permissions-Policy \"geolocation=(), microphone=(), camera=(), payment=(), usb=()\"
Header always set Cross-Origin-Embedder-Policy \"require-corp\"
Header always set Cross-Origin-Opener-Policy \"same-origin\"
Header always set Cross-Origin-Resource-Policy \"same-origin\"

# Performance headers
Header always set Cache-Control \"public, max-age=31536000, immutable\" \"expr=%{REQUEST_URI} =~ m#\\.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$#\"
Header always set Vary \"Accept-Encoding\"

# Prevent access to sensitive files
<FilesMatch \"\\.(env|log|sqlite|sql|md|txt)$\">
    Order Deny,Allow
    Deny from all
</FilesMatch>

# Prevent access to directories
<DirectoryMatch \"^(storage|database|vendor|node_modules|tests|docs|scripts)\">
    Order Deny,Allow
    Deny from all
</DirectoryMatch>

# Enable compression
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

# Enable browser caching
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css \"access plus 1 year\"
    ExpiresByType application/javascript \"access plus 1 year\"
    ExpiresByType image/png \"access plus 1 year\"
    ExpiresByType image/jpg \"access plus 1 year\"
    ExpiresByType image/jpeg \"access plus 1 year\"
    ExpiresByType image/gif \"access plus 1 year\"
    ExpiresByType image/svg+xml \"access plus 1 year\"
    ExpiresByType image/x-icon \"access plus 1 year\"
    ExpiresByType font/woff \"access plus 1 year\"
    ExpiresByType font/woff2 \"access plus 1 year\"
</IfModule>
";
}

function generateApacheVHost() {
    $appUrl = $_SESSION['env_config']['app_url'] ?? 'http://localhost';
    $domain = parse_url($appUrl, PHP_URL_HOST);
    $isHttps = parse_url($appUrl, PHP_URL_SCHEME) === 'https';
    $port = $isHttps ? '443' : '80';
    $documentRoot = realpath('.');
    
    return "# Forum Project - Apache Virtual Host
# Generated on " . date('Y-m-d H:i:s') . "

<VirtualHost *:$port>
    ServerName $domain
    DocumentRoot $documentRoot
    
    <Directory $documentRoot>
        AllowOverride All
        Require all granted
        Options -Indexes
    </Directory>
    
    # Security
    ServerTokens Prod
    ServerSignature Off
    
    # Logging
    ErrorLog \${APACHE_LOG_DIR}/forum_error.log
    CustomLog \${APACHE_LOG_DIR}/forum_access.log combined
    
    # Performance
    <IfModule mod_headers.c>
        Header always set X-Content-Type-Options nosniff
        Header always set X-Frame-Options DENY
        Header always set X-XSS-Protection \"1; mode=block\"
    </IfModule>
    
    # PHP Configuration
    <IfModule mod_php7.c>
        php_value upload_max_filesize 10M
        php_value post_max_size 10M
        php_value memory_limit 256M
        php_value max_execution_time 300
        php_value max_input_vars 3000
    </IfModule>
" . ($isHttps ? "
    # SSL Configuration
    SSLEngine on
    SSLCertificateFile /path/to/your/certificate.crt
    SSLCertificateKeyFile /path/to/your/private.key
    SSLCertificateChainFile /path/to/your/chain.crt
" : "") . "
</VirtualHost>
";
}

function generateNginxConfig() {
    $appUrl = $_SESSION['env_config']['app_url'] ?? 'http://localhost';
    $domain = parse_url($appUrl, PHP_URL_HOST);
    $isHttps = parse_url($appUrl, PHP_URL_SCHEME) === 'https';
    $documentRoot = realpath('.');
    
    return "# Forum Project - Nginx Configuration
# Generated on " . date('Y-m-d H:i:s') . "

server {
    listen " . ($isHttps ? '443 ssl http2' : '80') . ";
    server_name $domain;
    root $documentRoot;
    index index.php index.html;
    
    # Security headers
    add_header X-Content-Type-Options nosniff;
    add_header X-Frame-Options DENY;
    add_header X-XSS-Protection \"1; mode=block\";
    add_header Referrer-Policy \"strict-origin-when-cross-origin\";
    add_header Content-Security-Policy \"default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data:; connect-src 'self';\";
    add_header Strict-Transport-Security \"max-age=31536000; includeSubDomains; preload\";
    add_header Permissions-Policy \"geolocation=(), microphone=(), camera=(), payment=(), usb=()\";
    add_header Cross-Origin-Embedder-Policy \"require-corp\";
    add_header Cross-Origin-Opener-Policy \"same-origin\";
    add_header Cross-Origin-Resource-Policy \"same-origin\";
    
    # Performance
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml application/xml+rss text/javascript;
    
    # Caching
    location ~* \\.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control \"public, immutable\";
    }
    
    # Security - Block sensitive files
    location ~ /\\.(env|log|sqlite|sql|md|txt)$ {
        deny all;
    }
    
    # Security - Block directories
    location ~ ^/(storage|database|vendor|node_modules|tests|docs|scripts)/ {
        deny all;
    }
    
    # Main application
    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }
    
    # PHP processing
    location ~ \\.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        
        # Security
        fastcgi_hide_header X-Powered-By;
        fastcgi_read_timeout 300;
        fastcgi_connect_timeout 300;
        fastcgi_send_timeout 300;
    }
    
    # Deny access to hidden files
    location ~ /\\. {
        deny all;
    }
" . ($isHttps ? "
    # SSL Configuration
    ssl_certificate /path/to/your/certificate.crt;
    ssl_certificate_key /path/to/your/private.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512:ECDHE-RSA-AES256-GCM-SHA384:DHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;
" : "") . "
}
";
}

// Run if called directly
if (php_sapi_name() === 'cli') {
    setupWebServer();
}
?>