# 🚀 Coding Master Forum

একটি আধুনিক, নিরাপদ এবং ফিচার-সমৃদ্ধ Forum সাইট যেটা তৈরি করা হয়েছে **PHP 8.1+** এবং **SQLite** দিয়ে। পুরো প্রজেক্টের স্ট্রাকচার clean **MVC প্যাটার্ন** অনুসরণ করে, ভবিষ্যতে সহজেই update/modify করা যাবে।

## ✨ **নতুন ফিচারসমূহ**

### 🔐 **সিকিউরিটি ফিচার**
- **CSRF Protection** - সব form এ CSRF token
- **Rate Limiting** - Login attempt সীমাবদ্ধতা
- **Password Hashing** - Bcrypt/Argon2 password hashing
- **Session Security** - Secure session management
- **Input Validation** - সব input sanitization
- **SQL Injection Protection** - Prepared statements
- **XSS Protection** - HTML escaping
- **Security Headers** - HSTS, CSP, X-Frame-Options

### 🎨 **আধুনিক UI/UX**
- **Responsive Design** - Mobile-friendly
- **Modern CSS** - Flexbox/Grid layout
- **Dark/Light Theme** - Theme switching
- **Real-time Notifications** - AJAX notifications
- **Rich Text Editor** - WYSIWYG editor
- **File Upload** - Image/document upload
- **Search Functionality** - Advanced search
- **Pagination** - Smart pagination

### 🔧 **টেকনিক্যাল ফিচার**
- **PHP 8.1+** - Modern PHP features
- **Type Declarations** - Strict typing
- **PSR-4 Autoloading** - Namespace support
- **Database Optimization** - Query optimization
- **Caching System** - File-based caching
- **Logging System** - Error/access logging
- **API Endpoints** - RESTful API
- **Middleware Support** - Request middleware

### 👥 **ইউজার ফিচার**
- **User Registration/Login** - Secure authentication
- **Profile Management** - Avatar, bio, settings
- **Password Reset** - Email-based reset
- **Remember Me** - Persistent login
- **User Roles** - Admin, Moderator, User
- **Reputation System** - User reputation
- **Following System** - Follow/unfollow users
- **Private Messaging** - Direct messages

### 📊 **এডমিন ফিচার**
- **Admin Dashboard** - Statistics overview
- **User Management** - Ban/unban users
- **Forum Management** - Create/edit forums
- **Content Moderation** - Moderate posts
- **Reports System** - Handle reports
- **Settings Management** - Site settings
- **Backup System** - Database backup
- **Analytics** - Usage statistics

## 🛠️ **ইনস্টলেশন**

### **প্রয়োজনীয় সফটওয়্যার**
- PHP 8.1 বা তার উপরে
- SQLite3 extension
- Apache/Nginx web server
- Composer (optional)

### **ইনস্টলেশন স্টেপ**

1. **প্রজেক্ট ডাউনলোড করুন**
```bash
git clone https://github.com/minazahmad/coding-master-forum.git
cd coding-master-forum
```

2. **PHP dependencies ইনস্টল করুন**
```bash
composer install
```

3. **Permission সেট করুন**
```bash
chmod -R 755 storage/
chmod -R 755 public/uploads/
```

4. **ইনস্টলেশন রান করুন**
```
http://yourdomain.com/install.php
```

5. **কনফিগারেশন সম্পূর্ণ করুন**
- Database settings
- Site settings
- Admin account creation

## 📁 **প্রজেক্ট স্ট্রাকচার**

```
coding-master-forum/
├── 📁 core/                 # Core framework files
│   ├── Auth.php            # Authentication system
│   ├── Database.php       # Database abstraction
│   ├── Router.php         # URL routing
│   ├── Functions.php      # Helper functions
│   └── Middleware.php     # Request middleware
├── 📁 controllers/         # MVC Controllers
│   ├── HomeController.php # Home page logic
│   ├── AuthController.php # Authentication logic
│   ├── ForumController.php# Forum logic
│   ├── UserController.php # User management
│   ├── AdminController.php# Admin panel
│   └── ApiController.php  # API endpoints
├── 📁 models/             # MVC Models
│   ├── User.php           # User model
│   ├── Forum.php         # Forum model
│   ├── Thread.php        # Thread model
│   ├── Post.php          # Post model
│   ├── Message.php       # Message model
│   └── Notification.php  # Notification model
├── 📁 views/              # MVC Views
│   ├── 📁 admin/         # Admin views
│   ├── 📁 user/          # User views
│   ├── header.php        # Site header
│   ├── footer.php        # Site footer
│   └── error.php         # Error pages
├── 📁 routes/             # Route definitions
│   ├── web.php           # Web routes
│   ├── admin.php         # Admin routes
│   └── api.php           # API routes
├── 📁 public/             # Public assets
│   ├── 📁 css/           # Stylesheets
│   ├── 📁 js/            # JavaScript
│   ├── 📁 images/        # Images
│   └── 📁 uploads/       # User uploads
├── 📁 storage/            # Storage directory
│   ├── 📁 cache/         # Cache files
│   ├── 📁 logs/          # Log files
│   └── forum.sqlite      # Database file
├── 📁 middleware/         # Middleware classes
├── 📁 services/          # Service classes
├── 📁 tests/             # Test files
├── config.php            # Configuration
├── index.php            # Entry point
├── install.php          # Installation script
├── composer.json        # Dependencies
└── .htaccess           # Apache configuration
```

## ⚙️ **কনফিগারেশন**

### **Environment Variables**
```php
// config.php
define('SITE_NAME', 'Your Forum Name');
define('SITE_URL', 'https://yourdomain.com');
define('DB_PATH', __DIR__ . '/storage/forum.sqlite');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
```

### **Database Configuration**
- SQLite database automatically created
- Tables created during installation
- Foreign key constraints enabled
- WAL mode for better performance

## 🔧 **API Documentation**

### **Public Endpoints**
```
GET  /api/forums                    # Get all forums
GET  /api/forum/{slug}/threads     # Get forum threads
GET  /api/thread/{id}/posts        # Get thread posts
GET  /api/user/{username}          # Get user profile
```

### **Authenticated Endpoints**
```
POST /api/thread/{id}/reply        # Create post
POST /api/post/{id}/like           # Like post
DELETE /api/post/{id}/like         # Unlike post
POST /api/user/{id}/follow         # Follow user
DELETE /api/user/{id}/follow       # Unfollow user
```

## 🧪 **টেস্টিং**

```bash
# Run tests
composer test

# Run with coverage
composer test-coverage

# Code analysis
composer analyse

# Code style check
composer cs-check

# Fix code style
composer cs-fix
```

## 🚀 **Deployment**

### **Production Checklist**
- [ ] Set `display_errors = Off`
- [ ] Enable HTTPS
- [ ] Set proper file permissions
- [ ] Configure web server
- [ ] Set up database backup
- [ ] Configure email settings
- [ ] Set up monitoring

### **Web Server Configuration**

#### **Apache (.htaccess)**
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

#### **Nginx**
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
    fastcgi_index index.php;
    fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    include fastcgi_params;
}
```

## 📈 **Performance Optimization**

- **Database Indexing** - Optimized queries
- **Caching** - File-based caching system
- **Compression** - Gzip compression
- **CDN Ready** - Static asset optimization
- **Lazy Loading** - Image lazy loading
- **Pagination** - Efficient pagination

## 🔒 **সিকিউরিটি**

- **Input Validation** - All inputs validated
- **SQL Injection** - Prepared statements
- **XSS Protection** - HTML escaping
- **CSRF Protection** - Token validation
- **Rate Limiting** - Request limiting
- **Secure Headers** - Security headers
- **Password Security** - Strong hashing

## 🤝 **Contributing**

1. Fork the repository
2. Create feature branch (`git checkout -b feature/amazing-feature`)
3. Commit changes (`git commit -m 'Add amazing feature'`)
4. Push to branch (`git push origin feature/amazing-feature`)
5. Open Pull Request

## 📄 **License**

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 👨‍💻 **Author**

**Minaz Ahmad**
- Email: minazahmad@gmail.com
- GitHub: [@minazahmad](https://github.com/minazahmad)

## 🙏 **Acknowledgments**

- PHP Community
- SQLite Team
- All Contributors

---

**⭐ Star this repository if you found it helpful!**