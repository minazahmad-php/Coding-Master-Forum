# My Forum

A modern, feature-rich PHP forum application built with clean architecture and modern web technologies.

## Features

- **User Management**: Registration, authentication, profiles, roles & permissions
- **Forum System**: Categories, threads, posts with rich text editing
- **Real-time Features**: Notifications, live updates via WebSockets
- **Admin Panel**: Complete administration interface
- **Multi-language Support**: English and Bengali
- **Plugin System**: Extensible architecture
- **Modern UI**: Responsive design with Bootstrap 5
- **Security**: CSRF protection, input validation, secure file uploads
- **SEO Friendly**: Clean URLs, sitemap, meta tags

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Composer
- Node.js & NPM (for asset compilation)
- Web server (Apache/Nginx)

## Installation

### Automatic Installation

1. Upload all files to your web server
2. Navigate to `https://your-domain.com/install.php`
3. Follow the installation wizard
4. Complete the setup process

### Manual Installation

1. Clone the repository:
```bash
git clone https://github.com/your-username/my-forum.git
cd my-forum
```

2. Install PHP dependencies:
```bash
composer install
```

3. Install Node.js dependencies:
```bash
npm install
```

4. Compile assets:
```bash
npm run dev
```

5. Copy environment file:
```bash
cp .env.example .env
```

6. Configure your database and other settings in `.env`

7. Run database migrations:
```bash
php install.php
```

## Configuration

### Environment Variables

Edit the `.env` file to configure your application:

```env
APP_NAME="My Forum"
APP_URL=https://coding-master.infy.uk
DB_HOST=localhost
DB_DATABASE=forum_db
DB_USERNAME=root
DB_PASSWORD=your_password
```

### Web Server Configuration

#### Apache
The included `.htaccess` file handles URL rewriting and security headers.

#### Nginx
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

## Development

### Asset Compilation

```bash
# Development build
npm run dev

# Watch for changes
npm run watch

# Production build
npm run prod
```

### Database Migrations

Migrations are located in `database/migrations/`. Run them using the installation script or manually.

### Plugin Development

Plugins are located in the `plugins/` directory. Each plugin should have:
- `plugin.json` - Plugin metadata
- Main plugin class implementing `PluginInterface`
- Views, assets, and migrations as needed

## API Documentation

The forum includes a RESTful API. Documentation is available at `/api/docs` (when implemented).

## Security

- All user inputs are validated and sanitized
- CSRF protection on all forms
- Secure file upload handling
- SQL injection prevention via prepared statements
- XSS protection with output escaping

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a pull request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For support, email support@coding-master.infy.uk or visit our forum.

## Changelog

See CHANGELOG.md for version history.