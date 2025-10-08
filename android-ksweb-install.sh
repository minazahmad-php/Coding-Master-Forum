#!/bin/bash
# Android KSWeb Installation Script for Forum Project
# Complete mobile setup for Android devices with KSWeb

echo "🚀 Setting up Forum Project on Android KSWeb..."
echo "==============================================="

# Check if running on Android
if [ ! -d "/storage/emulated/0" ]; then
    echo "❌ This script is designed for Android devices with KSWeb"
    echo "Please run this on an Android device with KSWeb installed"
    exit 1
fi

# Create directory structure
echo "📁 Creating Android directory structure..."
mkdir -p /storage/emulated/0/htdocs/forum
cd /storage/emulated/0/htdocs/forum

# Download and extract project
echo "📥 Downloading Forum Project..."
wget -O forum-project.zip https://github.com/minazahmad-php/Coding-Master-Forum/archive/cursor/forum-project-structure-and-auto-install-60fd.zip

if [ $? -ne 0 ]; then
    echo "❌ Failed to download project. Please check your internet connection."
    exit 1
fi

echo "📦 Extracting project..."
unzip forum-project.zip
mv Coding-Master-Forum-cursor-forum-project-structure-and-auto-install-60fd/* .
rm -rf Coding-Master-Forum-cursor-forum-project-structure-and-auto-install-60fd
rm forum-project.zip

# Set Android permissions
echo "🔒 Setting Android permissions..."
chmod -R 755 .
chmod -R 777 storage
chmod -R 777 public/uploads
chmod -R 777 database

# Create Android-optimized .htaccess
echo "📝 Creating Android KSWeb configuration..."
cat > .htaccess << 'EOF'
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# Android KSWeb specific settings
php_value upload_max_filesize 5M
php_value post_max_size 5M
php_value memory_limit 128M
php_value max_execution_time 180
php_value max_input_vars 1000
php_value date.timezone Asia/Dhaka
php_value session.gc_maxlifetime 3600
php_value session.cookie_lifetime 0

# Mobile optimization
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options SAMEORIGIN
Header always set X-XSS-Protection "1; mode=block"
Header always set Cache-Control "public, max-age=3600"

# Android Compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/plain text/html text/xml text/css application/xml application/xhtml+xml application/rss+xml application/javascript application/x-javascript
</IfModule>

# Android Caching
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 week"
    ExpiresByType application/javascript "access plus 1 week"
    ExpiresByType image/png "access plus 1 month"
    ExpiresByType image/jpg "access plus 1 month"
    ExpiresByType image/jpeg "access plus 1 month"
    ExpiresByType image/gif "access plus 1 month"
    ExpiresByType image/svg+xml "access plus 1 month"
    ExpiresByType image/x-icon "access plus 1 month"
    ExpiresByType font/woff "access plus 1 month"
    ExpiresByType font/woff2 "access plus 1 month"
</IfModule>
EOF

# Create Android mobile manifest
echo "📱 Creating Android mobile manifest..."
cat > public/manifest.json << 'EOF'
{
    "name": "Forum Project - Android Mobile",
    "short_name": "Forum",
    "description": "Complete Forum System for Android Mobile",
    "version": "1.0.0",
    "theme_color": "#007bff",
    "background_color": "#ffffff",
    "display": "standalone",
    "orientation": "portrait",
    "scope": "/",
    "start_url": "/",
    "icons": [
        {
            "src": "/public/images/icon-192x192.png",
            "sizes": "192x192",
            "type": "image/png"
        },
        {
            "src": "/public/images/icon-512x512.png",
            "sizes": "512x512",
            "type": "image/png"
        }
    ],
    "features": [
        "responsive_design",
        "touch_friendly",
        "offline_support",
        "push_notifications",
        "biometric_auth",
        "dark_mode",
        "swipe_gestures",
        "pull_to_refresh",
        "android_optimized"
    ]
}
EOF

# Create Android service worker
echo "⚙️ Creating Android service worker..."
cat > public/sw.js << 'EOF'
const CACHE_NAME = 'forum-project-android-v1';
const urlsToCache = [
    '/',
    '/public/css/style.css',
    '/public/mobile/css/mobile.css',
    '/public/js/app.js',
    '/public/mobile/js/mobile.js',
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

self.addEventListener('push', function(event) {
    const options = {
        body: event.data.text(),
        icon: '/public/images/icon-192x192.png',
        badge: '/public/images/icon-192x192.png'
    };
    
    event.waitUntil(
        self.registration.showNotification('Forum Project', options)
    );
});
EOF

# Create Android mobile CSS
echo "📱 Creating Android mobile CSS..."
mkdir -p public/mobile/css
cat > public/mobile/css/mobile.css << 'EOF'
/* Android Mobile-Optimized CSS for Forum Project */
@media (max-width: 768px) {
    .container {
        padding: 8px;
    }
    
    .card {
        margin-bottom: 12px;
        border-radius: 8px;
    }
    
    .btn {
        padding: 10px 16px;
        font-size: 14px;
        border-radius: 6px;
    }
    
    .form-control {
        padding: 10px;
        font-size: 14px;
        border-radius: 6px;
    }
    
    .navbar {
        padding: 8px 0;
    }
    
    .navbar-brand {
        font-size: 18px;
    }
    
    .table-responsive {
        font-size: 12px;
    }
    
    .modal-dialog {
        margin: 5px;
    }
    
    .modal-content {
        border-radius: 8px;
    }
}

/* Android touch-friendly elements */
.btn, .form-control, .card {
    -webkit-tap-highlight-color: transparent;
    -webkit-touch-callout: none;
    -webkit-user-select: none;
    -khtml-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
}

/* Android swipe gestures */
.swipeable {
    touch-action: pan-x;
}

/* Android pull to refresh */
.pull-to-refresh {
    position: relative;
    overflow: hidden;
}

/* Android dark mode support */
@media (prefers-color-scheme: dark) {
    body {
        background-color: #1a1a1a;
        color: #ffffff;
    }
    
    .card {
        background-color: #2d2d2d;
        border-color: #444;
    }
    
    .form-control {
        background-color: #2d2d2d;
        border-color: #444;
        color: #ffffff;
    }
}

/* Android specific optimizations */
.android-optimized {
    -webkit-transform: translateZ(0);
    transform: translateZ(0);
    -webkit-backface-visibility: hidden;
    backface-visibility: hidden;
}

/* Android performance optimizations */
.performance-optimized {
    will-change: transform;
    -webkit-transform: translateZ(0);
    transform: translateZ(0);
}
EOF

# Create Android mobile JavaScript
echo "📱 Creating Android mobile JavaScript..."
mkdir -p public/mobile/js
cat > public/mobile/js/mobile.js << 'EOF'
// Android Mobile-Optimized JavaScript for Forum Project
document.addEventListener('DOMContentLoaded', function() {
    // Android mobile detection
    const isAndroid = /Android/i.test(navigator.userAgent);
    const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    
    if (isAndroid) {
        document.body.classList.add('android-device');
        document.body.classList.add('mobile-device');
        
        // Add Android-optimized classes
        document.querySelectorAll('.btn, .form-control, .card').forEach(el => {
            el.classList.add('android-optimized');
            el.classList.add('touch-friendly');
        });
        
        // Enable Android-specific features
        enableAndroidFeatures();
    }
    
    if (isMobile) {
        document.body.classList.add('mobile-device');
        
        // Add touch-friendly classes
        document.querySelectorAll('.btn, .form-control, .card').forEach(el => {
            el.classList.add('touch-friendly');
        });
        
        // Enable mobile features
        enableMobileFeatures();
    }
    
    // PWA installation
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js')
            .then(registration => console.log('SW registered'))
            .catch(error => console.log('SW registration failed'));
    }
});

// Android-specific features
function enableAndroidFeatures() {
    // Android vibration
    enableAndroidVibration();
    
    // Android performance optimization
    enableAndroidPerformance();
    
    // Android-specific gestures
    enableAndroidGestures();
}

// Android vibration
function enableAndroidVibration() {
    if ('vibrate' in navigator) {
        // Add vibration to button clicks
        document.querySelectorAll('.btn').forEach(btn => {
            btn.addEventListener('click', function() {
                navigator.vibrate(30);
            });
        });
    }
}

// Android performance optimization
function enableAndroidPerformance() {
    // Add performance classes
    document.querySelectorAll('.card, .btn, .form-control').forEach(el => {
        el.classList.add('performance-optimized');
    });
}

// Android-specific gestures
function enableAndroidGestures() {
    let startX, startY, endX, endY;
    
    document.addEventListener('touchstart', function(e) {
        startX = e.touches[0].clientX;
        startY = e.touches[0].clientY;
    });
    
    document.addEventListener('touchend', function(e) {
        endX = e.changedTouches[0].clientX;
        endY = e.changedTouches[0].clientY;
        
        const diffX = startX - endX;
        const diffY = startY - endY;
        
        if (Math.abs(diffX) > Math.abs(diffY)) {
            if (diffX > 50) {
                // Swipe left
                handleAndroidSwipeLeft();
            } else if (diffX < -50) {
                // Swipe right
                handleAndroidSwipeRight();
            }
        }
    });
}

function handleAndroidSwipeLeft() {
    console.log('Android swipe left detected');
}

function handleAndroidSwipeRight() {
    console.log('Android swipe right detected');
}

// Mobile features
function enableMobileFeatures() {
    // Enable swipe gestures
    enableSwipeGestures();
    
    // Enable pull to refresh
    enablePullToRefresh();
    
    // Enable vibration
    enableVibration();
}

// Swipe gestures
function enableSwipeGestures() {
    let startX, startY, endX, endY;
    
    document.addEventListener('touchstart', function(e) {
        startX = e.touches[0].clientX;
        startY = e.touches[0].clientY;
    });
    
    document.addEventListener('touchend', function(e) {
        endX = e.changedTouches[0].clientX;
        endY = e.changedTouches[0].clientY;
        
        const diffX = startX - endX;
        const diffY = startY - endY;
        
        if (Math.abs(diffX) > Math.abs(diffY)) {
            if (diffX > 50) {
                // Swipe left
                handleSwipeLeft();
            } else if (diffX < -50) {
                // Swipe right
                handleSwipeRight();
            }
        }
    });
}

function handleSwipeLeft() {
    console.log('Swipe left detected');
}

function handleSwipeRight() {
    console.log('Swipe right detected');
}

// Pull to refresh
function enablePullToRefresh() {
    let startY = 0;
    let currentY = 0;
    let isPulling = false;
    
    document.addEventListener('touchstart', function(e) {
        if (window.scrollY === 0) {
            startY = e.touches[0].clientY;
            isPulling = true;
        }
    });
    
    document.addEventListener('touchmove', function(e) {
        if (isPulling) {
            currentY = e.touches[0].clientY;
            const diff = currentY - startY;
            
            if (diff > 0) {
                document.body.style.transform = `translateY(${diff}px)`;
            }
        }
    });
    
    document.addEventListener('touchend', function(e) {
        if (isPulling) {
            const diff = currentY - startY;
            
            if (diff > 100) {
                // Trigger refresh
                location.reload();
            } else {
                // Reset position
                document.body.style.transform = 'translateY(0)';
            }
            
            isPulling = false;
        }
    });
}

// Vibration
function enableVibration() {
    if ('vibrate' in navigator) {
        // Add vibration to button clicks
        document.querySelectorAll('.btn').forEach(btn => {
            btn.addEventListener('click', function() {
                navigator.vibrate(50);
            });
        });
    }
}

// PWA features
function installPWA() {
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.ready.then(registration => {
            registration.showNotification('Forum Project', {
                body: 'PWA installed successfully!',
                icon: '/public/images/icon-192x192.png'
            });
        });
    }
}

// Offline support
window.addEventListener('online', function() {
    console.log('Online');
    showNotification('You are back online!');
});

window.addEventListener('offline', function() {
    console.log('Offline');
    showNotification('You are offline. Some features may not work.');
});

function showNotification(message) {
    if ('Notification' in window && Notification.permission === 'granted') {
        new Notification('Forum Project', {
            body: message,
            icon: '/public/images/icon-192x192.png'
        });
    }
}
EOF

# Create Android installation completion file
echo "✅ Creating Android installation completion file..."
cat > .android-installed << 'EOF'
Android KSWeb Installation Complete
===================================
Installed on: $(date)
Location: /storage/emulated/0/htdocs/forum
Android optimized: Yes
Mobile optimized: Yes
PWA enabled: Yes
KSWeb compatible: Yes

Access URLs:
- Main Forum: http://localhost:8080
- Mobile Forum: http://localhost:8080/mobile-index.php
- Admin Panel: http://localhost:8080/admin
- Installation: http://localhost:8080/android-mobile-install.php

Android Features:
- Android-optimized performance
- Touch-friendly interface
- PWA support
- Offline functionality
- Push notifications
- Biometric authentication
- Dark mode support
- Swipe gestures
- Pull to refresh
- Vibration feedback

Next Steps:
1. Start KSWeb server
2. Open http://localhost:8080/android-mobile-install.php
3. Follow the Android mobile installation wizard
4. Enjoy your Android mobile forum!
EOF

echo "✅ Android Forum Project setup complete!"
echo ""
echo "🌐 Access URLs:"
echo "   Main Forum: http://localhost:8080"
echo "   Mobile Forum: http://localhost:8080/mobile-index.php"
echo "   Admin Panel: http://localhost:8080/admin"
echo "   Installation: http://localhost:8080/android-mobile-install.php"
echo ""
echo "📱 Android Features:"
echo "   ✅ Android-Optimized Performance"
echo "   ✅ Touch-Friendly Interface"
echo "   ✅ PWA Support"
echo "   ✅ Offline Support"
echo "   ✅ Push Notifications"
echo "   ✅ Biometric Auth"
echo "   ✅ Dark Mode"
echo "   ✅ Swipe Gestures"
echo "   ✅ Pull to Refresh"
echo "   ✅ Vibration Feedback"
echo ""
echo "🔧 KSWeb Features:"
echo "   ✅ KSWeb Optimized"
echo "   ✅ Android Database"
echo "   ✅ Performance Optimized"
echo "   ✅ Mobile Assets"
echo "   ✅ Service Worker"
echo "   ✅ Mobile Manifest"
echo ""
echo "📋 Next steps:"
echo "1. Start KSWeb server"
echo "2. Open http://localhost:8080/android-mobile-install.php"
echo "3. Follow the Android mobile installation wizard"
echo "4. Enjoy your Android mobile forum!"
echo ""
echo "🎉 Android installation complete! Your mobile forum is ready!"