# Forum Project - Complete Installation Guide
## সর্বোচ্চ এবং সর্বশেষ সংস্করণের ইনস্টলেশন গাইড

### 🚀 **Quick Start (Recommended)**

#### **Step 1: Download & Extract**
```bash
# Download the project
wget https://github.com/minazahmad-php/Coding-Master-Forum/archive/cursor/forum-project-structure-and-auto-install-60fd.zip

# Extract
unzip cursor/forum-project-structure-and-auto-install-60fd.zip
cd Coding-Master-Forum-cursor-forum-project-structure-and-auto-install-60fd
```

#### **Step 2: Run Installation**
```bash
# Open in browser
http://localhost/your-project-folder/install.php
```

#### **Step 3: Follow Installation Wizard**
1. **Welcome** - System requirements check
2. **Database** - Choose SQLite (localhost) or MySQL (production)
3. **Admin** - Create admin account
4. **Config** - Environment settings
5. **Install** - Automatic installation
6. **Complete** - Ready to use!

### 🔧 **Detailed Installation**

#### **Prerequisites (Auto-Installed)**
- **PHP 7.4+** with extensions:
  - PDO, PDO_MySQL, PDO_SQLite
  - JSON, MBString, OpenSSL
  - CURL, GD, ZIP, XML
- **Composer** (Auto-downloaded & installed)
- **Node.js 14+** (Auto-installed on Linux)
- **NPM 6+** (Auto-installed with Node.js)
- **MySQL 5.7+** (for production)
- **SQLite 3** (for localhost)
- **Web Server** (Apache/Nginx - Auto-configured)
- **System Packages** (Auto-installed: unzip, curl, wget, git)

#### **Installation Options**

##### **Option 1: SQLite (Localhost)**
- **Best for:** Development, testing, small sites
- **Auto-configured:** Yes
- **Setup time:** 2 minutes
- **Requirements:** PHP with SQLite support

##### **Option 2: MySQL (Production)**
- **Best for:** Production, large sites
- **Auto-configured:** Yes (with credentials)
- **Setup time:** 5 minutes
- **Requirements:** MySQL server + credentials

### 📋 **Installation Process**

#### **Step 1: System Requirements Check**
The installer automatically checks:
- ✅ PHP Version (7.4+)
- ✅ Required Extensions
- ✅ File Permissions
- ✅ Directory Structure

#### **Step 2: Database Configuration**

##### **SQLite (Recommended for localhost):**
- **Auto-detected:** Yes
- **File location:** `database/forum.sqlite`
- **No credentials needed**
- **Perfect for:** Development, testing

##### **MySQL (For production):**
- **Host:** `localhost` (default)
- **Port:** `3306` (default)
- **Database:** `forum_db` (default)
- **Username:** `root` (default)
- **Password:** Your MySQL password

#### **Step 3: Admin Account Setup**
- **Username:** `admin` (default)
- **Email:** `admin@example.com` (default)
- **Password:** Choose strong password
- **First Name:** `Admin` (default)
- **Last Name:** `User` (default)

#### **Step 4: Environment Configuration**
- **App URL:** Auto-detected based on host
- **App Name:** `Forum Project` (default)
- **Mail Settings:** Configure SMTP (optional)

#### **Step 5: Automatic Installation**
The system automatically:
- ✅ Creates `.env` file
- ✅ Sets up database
- ✅ Creates admin user
- ✅ Creates directories
- ✅ Sets file permissions
- ✅ Creates sample data
- ✅ Secures installation files

### 🔒 **Post-Installation Security**

#### **Automatic Security Measures:**
- ✅ `install.php` → `install.php.disabled`
- ✅ `.htaccess_install` → `.htaccess`
- ✅ File permissions set correctly
- ✅ Sensitive files protected
- ✅ Security headers enabled

#### **Manual Security (Optional):**
```bash
# Remove installation files
rm install.php.disabled
rm post-install.php
rm check-installation.php

# Set proper permissions
chmod 644 .env
chmod 755 storage
chmod 755 public/uploads
```

### 🎯 **Installation Features**

#### **Auto-Detection:**
- **Environment:** Localhost vs Remote
- **Database:** SQLite vs MySQL
- **Settings:** Development vs Production
- **URLs:** Auto-configured

#### **Smart Defaults:**
- **Localhost:** SQLite + Development settings
- **Remote:** MySQL + Production settings
- **Security:** Appropriate for environment
- **Performance:** Optimized for environment

#### **Error Handling:**
- **Validation:** All inputs validated
- **Rollback:** Installation can be restarted
- **Logging:** All errors logged
- **Recovery:** Automatic recovery attempts

### 📊 **Installation Statistics**

#### **Time Required:**
- **SQLite:** 2-3 minutes
- **MySQL:** 3-5 minutes
- **Manual:** 10-15 minutes

#### **Files Created:**
- **Configuration:** `.env`, `.installed`
- **Database:** `forum.sqlite` or MySQL tables
- **Directories:** `storage/`, `public/uploads/`
- **Security:** `.htaccess`, `.gitignore`

#### **Features Enabled:**
- **User Management:** Complete
- **Forum System:** Complete
- **Admin Panel:** Complete
- **Security:** Complete
- **Performance:** Optimized

### 🚨 **Troubleshooting**

#### **Common Issues:**

##### **"Installation already completed"**
```bash
# Solution: Remove installation lock
rm .installed
```

##### **"Database connection failed"**
- Check MySQL credentials
- Ensure MySQL service is running
- Verify database exists

##### **"File permissions error"**
```bash
# Solution: Set correct permissions
chmod 755 storage
chmod 755 public/uploads
chmod 644 .env
```

##### **"Requirements not met"**
- Install missing PHP extensions
- Update PHP version
- Check file permissions

### 🔄 **Reinstallation**

#### **Complete Reinstall:**
```bash
# Remove installation files
rm .installed
rm .env
rm -rf storage/logs/*
rm -rf storage/cache/*
rm -rf storage/sessions/*

# Run installation again
http://localhost/your-project/install.php
```

#### **Update Installation:**
```bash
# Keep data, update system
# Just run the installer again
# It will detect existing installation
```

### 📱 **Mobile Installation**

#### **React Native App:**
```bash
cd mobile-app
npm install
npx react-native run-android
# or
npx react-native run-ios
```

#### **PWA Installation:**
- Open forum in mobile browser
- Tap "Add to Home Screen"
- Install as app

### 🎉 **After Installation**

#### **Access Points:**
- **Forum:** `http://your-domain/`
- **Admin:** `http://your-domain/admin`
- **API:** `http://your-domain/api/v1`

#### **Default Credentials:**
- **Username:** `admin`
- **Password:** Your chosen password
- **Email:** Your chosen email

#### **Next Steps:**
1. **Login** to admin panel
2. **Configure** settings
3. **Create** forums/categories
4. **Invite** users
5. **Customize** themes
6. **Monitor** analytics

### 🏆 **Installation Complete!**

**Your Forum Project is now ready to use with:**
- ✅ Complete auto-installation
- ✅ Smart environment detection
- ✅ Automatic security setup
- ✅ Production-ready configuration
- ✅ All features enabled
- ✅ Mobile app support
- ✅ API ready
- ✅ Analytics enabled

**🚀 Start building your community today!**