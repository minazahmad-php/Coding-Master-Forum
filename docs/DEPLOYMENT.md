# Deployment Guide

## Production Deployment

This guide covers deploying the Forum application to production servers.

## Prerequisites

### Server Requirements

- **PHP**: 7.4 or higher
- **MySQL**: 5.7 or higher
- **Redis**: 6.0 or higher (optional but recommended)
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **SSL Certificate**: Required for production
- **Memory**: Minimum 2GB RAM
- **Storage**: Minimum 10GB SSD

### PHP Extensions

```bash
php -m | grep -E "(pdo|pdo_mysql|json|mbstring|openssl|curl|gd|zip|xml|redis)"
```

Required extensions:
- pdo
- pdo_mysql
- json
- mbstring
- openssl
- curl
- gd
- zip
- xml
- redis (optional)

## Installation Steps

### 1. Server Setup

#### Ubuntu/Debian
```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install PHP and extensions
sudo apt install php7.4-fpm php7.4-mysql php7.4-curl php7.4-gd php7.4-mbstring php7.4-xml php7.4-zip php7.4-redis -y

# Install MySQL
sudo apt install mysql-server -y

# Install Redis
sudo apt install redis-server -y

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Node.js
curl -fsSL https://deb.nodesource.com/setup_16.x | sudo -E bash -
sudo apt install nodejs -y
```

#### CentOS/RHEL
```bash
# Install EPEL repository
sudo yum install epel-release -y

# Install PHP and extensions
sudo yum install php74-php-fpm php74-php-mysql php74-php-curl php74-php-gd php74-php-mbstring php74-php-xml php74-php-zip -y

# Install MySQL
sudo yum install mysql-server -y

# Install Redis
sudo yum install redis -y

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Node.js
curl -fsSL https://rpm.nodesource.com/setup_16.x | sudo bash -
sudo yum install nodejs -y
```

### 2. Database Setup

```bash
# Start MySQL service
sudo systemctl start mysql
sudo systemctl enable mysql

# Secure MySQL installation
sudo mysql_secure_installation

# Create database and user
mysql -u root -p
```

```sql
CREATE DATABASE forum_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'forum_user'@'localhost' IDENTIFIED BY 'strong_password_here';
GRANT ALL PRIVILEGES ON forum_db.* TO 'forum_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 3. Application Deployment

```bash
# Create application directory
sudo mkdir -p /var/www/forum
sudo chown -R www-data:www-data /var/www/forum

# Clone or upload application files
cd /var/www/forum
# Upload your forum-project-enterprise.zip and extract

# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Install Node.js dependencies
npm install --production

# Build assets
npm run production

# Set permissions
sudo chown -R www-data:www-data /var/www/forum
sudo chmod -R 755 /var/www/forum
sudo chmod -R 777 /var/www/forum/storage
sudo chmod -R 777 /var/www/forum/public/uploads
```

### 4. Environment Configuration

```bash
# Copy environment file
cp .env.example .env

# Edit environment file
nano .env
```

```env
APP_NAME="Forum"
APP_URL="https://yourdomain.com"
APP_DEBUG=false
APP_ENV=production

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=forum_db
DB_USERNAME=forum_user
DB_PASSWORD=strong_password_here

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="Forum"

# Add your API keys
OPENAI_API_KEY=your-openai-key
STRIPE_SECRET_KEY=your-stripe-secret-key
PAYPAL_CLIENT_ID=your-paypal-client-id
PAYPAL_CLIENT_SECRET=your-paypal-client-secret
```

### 5. Web Server Configuration

#### Apache Configuration

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    DocumentRoot /var/www/forum/public
    
    <Directory /var/www/forum/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/forum_error.log
    CustomLog ${APACHE_LOG_DIR}/forum_access.log combined
</VirtualHost>

<VirtualHost *:443>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    DocumentRoot /var/www/forum/public
    
    SSLEngine on
    SSLCertificateFile /path/to/your/certificate.crt
    SSLCertificateKeyFile /path/to/your/private.key
    
    <Directory /var/www/forum/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/forum_ssl_error.log
    CustomLog ${APACHE_LOG_DIR}/forum_ssl_access.log combined
</VirtualHost>
```

#### Nginx Configuration

```nginx
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name yourdomain.com www.yourdomain.com;
    root /var/www/forum/public;
    index index.php;

    ssl_certificate /path/to/your/certificate.crt;
    ssl_certificate_key /path/to/your/private.key;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }

    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

### 6. SSL Certificate Setup

#### Let's Encrypt (Recommended)
```bash
# Install Certbot
sudo apt install certbot python3-certbot-apache -y

# Get certificate
sudo certbot --apache -d yourdomain.com -d www.yourdomain.com

# Auto-renewal
sudo crontab -e
# Add: 0 12 * * * /usr/bin/certbot renew --quiet
```

#### Manual SSL Certificate
```bash
# Upload your certificate files
sudo cp your-certificate.crt /etc/ssl/certs/
sudo cp your-private.key /etc/ssl/private/
sudo chmod 600 /etc/ssl/private/your-private.key
```

### 7. Application Initialization

```bash
# Run installation
cd /var/www/forum
php install.php

# Or run manually:
# 1. Create database tables
php migrate.php

# 2. Seed initial data
php seed.php

# 3. Set up admin user
php setup-admin.php
```

### 8. Service Configuration

#### PHP-FPM Configuration
```bash
# Edit PHP-FPM pool configuration
sudo nano /etc/php/7.4/fpm/pool.d/www.conf
```

```ini
[www]
user = www-data
group = www-data
listen = /var/run/php/php7.4-fpm.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.max_requests = 1000
```

#### Redis Configuration
```bash
# Edit Redis configuration
sudo nano /etc/redis/redis.conf
```

```conf
bind 127.0.0.1
port 6379
timeout 0
tcp-keepalive 300
maxmemory 256mb
maxmemory-policy allkeys-lru
```

### 9. Cron Jobs

```bash
# Edit crontab
sudo crontab -e
```

```cron
# Forum maintenance tasks
* * * * * cd /var/www/forum && php artisan schedule:run >> /dev/null 2>&1
0 0 * * * cd /var/www/forum && php artisan backup:run >> /dev/null 2>&1
0 2 * * * cd /var/www/forum && php artisan cache:clear >> /dev/null 2>&1
```

### 10. Monitoring Setup

#### Log Rotation
```bash
# Create logrotate configuration
sudo nano /etc/logrotate.d/forum
```

```
/var/www/forum/storage/logs/*.log {
    daily
    missingok
    rotate 52
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
}
```

#### System Monitoring
```bash
# Install monitoring tools
sudo apt install htop iotop nethogs -y

# Monitor PHP-FPM
sudo systemctl status php7.4-fpm

# Monitor MySQL
sudo systemctl status mysql

# Monitor Redis
sudo systemctl status redis
```

## Performance Optimization

### 1. PHP Optimization

```bash
# Edit PHP configuration
sudo nano /etc/php/7.4/fpm/php.ini
```

```ini
memory_limit = 256M
max_execution_time = 30
max_input_time = 60
post_max_size = 50M
upload_max_filesize = 50M
opcache.enable = 1
opcache.memory_consumption = 128
opcache.max_accelerated_files = 4000
opcache.revalidate_freq = 2
```

### 2. MySQL Optimization

```bash
# Edit MySQL configuration
sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf
```

```ini
[mysqld]
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT
query_cache_type = 1
query_cache_size = 64M
max_connections = 200
```

### 3. Redis Optimization

```bash
# Edit Redis configuration
sudo nano /etc/redis/redis.conf
```

```conf
maxmemory 512mb
maxmemory-policy allkeys-lru
save 900 1
save 300 10
save 60 10000
```

## Security Hardening

### 1. Firewall Configuration

```bash
# Install UFW
sudo apt install ufw -y

# Configure firewall
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow ssh
sudo ufw allow 80
sudo ufw allow 443
sudo ufw enable
```

### 2. File Permissions

```bash
# Set secure permissions
sudo chown -R www-data:www-data /var/www/forum
sudo chmod -R 755 /var/www/forum
sudo chmod -R 777 /var/www/forum/storage
sudo chmod -R 777 /var/www/forum/public/uploads
sudo chmod 600 /var/www/forum/.env
```

### 3. Database Security

```sql
-- Remove test databases
DROP DATABASE IF EXISTS test;

-- Remove anonymous users
DELETE FROM mysql.user WHERE User='';

-- Remove root remote access
DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');

-- Flush privileges
FLUSH PRIVILEGES;
```

## Backup Strategy

### 1. Database Backup

```bash
# Create backup script
sudo nano /usr/local/bin/forum-backup.sh
```

```bash
#!/bin/bash
BACKUP_DIR="/var/backups/forum"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="forum_db"
DB_USER="forum_user"
DB_PASS="strong_password_here"

mkdir -p $BACKUP_DIR

# Database backup
mysqldump -u$DB_USER -p$DB_PASS $DB_NAME > $BACKUP_DIR/database_$DATE.sql

# Application backup
tar -czf $BACKUP_DIR/application_$DATE.tar.gz /var/www/forum

# Keep only last 7 days
find $BACKUP_DIR -name "*.sql" -mtime +7 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +7 -delete
```

```bash
# Make executable
sudo chmod +x /usr/local/bin/forum-backup.sh

# Add to crontab
sudo crontab -e
# Add: 0 2 * * * /usr/local/bin/forum-backup.sh
```

### 2. Automated Backups

```bash
# Install backup tools
sudo apt install duplicity -y

# Configure backup
sudo nano /etc/duplicity/forum.conf
```

## Troubleshooting

### Common Issues

1. **Permission Denied**
   ```bash
   sudo chown -R www-data:www-data /var/www/forum
   sudo chmod -R 755 /var/www/forum
   ```

2. **Database Connection Failed**
   ```bash
   # Check MySQL status
   sudo systemctl status mysql
   
   # Check database credentials
   mysql -u forum_user -p forum_db
   ```

3. **PHP Errors**
   ```bash
   # Check PHP logs
   sudo tail -f /var/log/php7.4-fpm.log
   
   # Check application logs
   tail -f /var/www/forum/storage/logs/forum.log
   ```

4. **Memory Issues**
   ```bash
   # Check memory usage
   free -h
   
   # Check PHP memory limit
   php -i | grep memory_limit
   ```

### Log Files

- **Application Logs**: `/var/www/forum/storage/logs/`
- **PHP-FPM Logs**: `/var/log/php7.4-fpm.log`
- **Apache Logs**: `/var/log/apache2/`
- **Nginx Logs**: `/var/log/nginx/`
- **MySQL Logs**: `/var/log/mysql/`
- **System Logs**: `/var/log/syslog`

## Maintenance

### Regular Tasks

1. **Update System**
   ```bash
   sudo apt update && sudo apt upgrade -y
   ```

2. **Clear Caches**
   ```bash
   cd /var/www/forum
   php artisan cache:clear
   php artisan config:clear
   php artisan view:clear
   ```

3. **Optimize Database**
   ```bash
   mysql -u root -p
   OPTIMIZE TABLE users, forums, threads, posts;
   ```

4. **Monitor Disk Space**
   ```bash
   df -h
   du -sh /var/www/forum/storage/logs/*
   ```

## Support

For deployment support:
- **Documentation**: https://yourdomain.com/docs
- **Support Email**: support@yourdomain.com
- **Status Page**: https://status.yourdomain.com