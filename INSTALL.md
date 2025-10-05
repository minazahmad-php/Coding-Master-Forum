# Forum Installation Guide

## Quick Start

1. **Upload Files**: Upload all files to your web server
2. **Set Permissions**: Make sure the following directories are writable:
   - `storage/`
   - `public/uploads/`
   - `storage/logs/`
   - `storage/cache/`
   - `storage/sessions/`
   - `storage/backups/`
   - `storage/temp/`

3. **Run Installation**: Navigate to `https://your-domain.com/install.php`
4. **Follow the wizard** to complete setup

## Manual Installation

### Requirements
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Composer
- Node.js & NPM (for asset compilation)
- Web server (Apache/Nginx)

### Step 1: Database Setup
1. Create a MySQL database
2. Note down database credentials

### Step 2: Install Dependencies
```bash
# Install PHP dependencies
composer install

# Install Node.js dependencies
npm install

# Compile assets
npm run dev
```

### Step 3: Environment Configuration
1. Copy `.env.example` to `.env`
2. Update database credentials in `.env`
3. Configure other settings as needed

### Step 4: Run Installation
1. Navigate to `https://your-domain.com/install.php`
2. Follow the installation wizard
3. Complete the setup process

## Post-Installation

### Default Admin Account
- **Username**: admin
- **Email**: admin@coding-master.infy.uk
- **Password**: (set during installation)

### First Steps
1. Login to admin panel: `https://your-domain.com/admin`
2. Update site settings
3. Create additional forums
4. Configure user roles and permissions

## File Permissions

Make sure these directories are writable:
```bash
chmod 755 storage/
chmod 755 storage/logs/
chmod 755 storage/cache/
chmod 755 storage/sessions/
chmod 755 storage/backups/
chmod 755 storage/temp/
chmod 755 public/uploads/
```

## Web Server Configuration

### Apache
The included `.htaccess` file handles URL rewriting and security headers.

### Nginx
Add the following configuration:
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
    fastcgi_index index.php;
    fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    include fastcgi_params;
}
```

## Troubleshooting

### Common Issues

1. **500 Internal Server Error**
   - Check file permissions
   - Enable error reporting in PHP
   - Check web server error logs

2. **Database Connection Failed**
   - Verify database credentials
   - Check if MySQL service is running
   - Ensure database exists

3. **Permission Denied**
   - Check directory permissions
   - Ensure web server can write to storage directories

4. **CSS/JS Not Loading**
   - Run `npm run dev` to compile assets
   - Check if public directory is accessible
   - Verify web server configuration

### Getting Help

If you encounter issues:
1. Check the error logs in `storage/logs/`
2. Enable debug mode in `.env` (set `APP_DEBUG=true`)
3. Check web server error logs
4. Contact support at support@coding-master.infy.uk

## Security Notes

1. **Change default admin password** immediately after installation
2. **Update database credentials** in production
3. **Set proper file permissions** (755 for directories, 644 for files)
4. **Enable HTTPS** in production
5. **Regular backups** of database and files
6. **Keep software updated** regularly

## Features

- ✅ User registration and authentication
- ✅ Forum categories and threads
- ✅ Post reactions and subscriptions
- ✅ Private messaging system
- ✅ Admin panel with full control
- ✅ Multi-language support (English/Bengali)
- ✅ Responsive design
- ✅ SEO friendly URLs
- ✅ Real-time notifications
- ✅ Search functionality
- ✅ User roles and permissions
- ✅ Content moderation tools
- ✅ File upload support
- ✅ Mobile responsive

## Support

For technical support or questions:
- Email: support@coding-master.infy.uk
- Website: https://coding-master.infy.uk
- Documentation: Check the docs/ directory

## License

This project is licensed under the MIT License.