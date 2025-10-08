<?php
/**
 * Mobile-Optimized Home Page
 * Touch-friendly interface for mobile devices
 */

// Mobile detection
function isMobile() {
    return isset($_SERVER['HTTP_USER_AGENT']) && 
           preg_match('/(android|iphone|ipad|mobile)/i', $_SERVER['HTTP_USER_AGENT']);
}

// KSWeb detection
function isKSWeb() {
    return isset($_SERVER['SERVER_SOFTWARE']) && 
           strpos($_SERVER['SERVER_SOFTWARE'], 'KSWeb') !== false;
}

$isMobile = isMobile();
$isKSWeb = isKSWeb();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Forum Project - Mobile</title>
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="/public/manifest.json">
    
    <!-- Mobile Icons -->
    <link rel="apple-touch-icon" href="/public/images/icon-192x192.png">
    <link rel="icon" type="image/png" sizes="192x192" href="/public/images/icon-192x192.png">
    <link rel="icon" type="image/png" sizes="512x512" href="/public/images/icon-512x512.png">
    
    <!-- Bootstrap Mobile -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Mobile CSS -->
    <link href="/public/mobile/css/mobile.css" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-size: 16px;
            -webkit-tap-highlight-color: transparent;
        }
        
        .mobile-container {
            background: white;
            border-radius: 20px 20px 0 0;
            min-height: 100vh;
            margin-top: 20px;
            box-shadow: 0 -10px 30px rgba(0,0,0,0.1);
        }
        
        .mobile-header {
            background: linear-gradient(45deg, #007bff, #0056b3);
            color: white;
            padding: 20px;
            border-radius: 20px 20px 0 0;
            text-align: center;
        }
        
        .mobile-nav {
            background: white;
            padding: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-around;
            align-items: center;
        }
        
        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            color: #666;
            font-size: 12px;
            transition: color 0.3s;
        }
        
        .nav-item.active {
            color: #007bff;
        }
        
        .nav-item i {
            font-size: 20px;
            margin-bottom: 5px;
        }
        
        .mobile-content {
            padding: 20px;
            min-height: 60vh;
        }
        
        .forum-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border: none;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .forum-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .forum-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: white;
            margin-bottom: 15px;
        }
        
        .forum-stats {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
            font-size: 14px;
            color: #666;
        }
        
        .mobile-btn {
            background: linear-gradient(45deg, #007bff, #0056b3);
            border: none;
            color: white;
            padding: 15px 30px;
            border-radius: 25px;
            font-weight: bold;
            font-size: 16px;
            width: 100%;
            margin: 10px 0;
            transition: transform 0.3s;
        }
        
        .mobile-btn:hover {
            transform: translateY(-2px);
            color: white;
        }
        
        .mobile-btn:active {
            transform: translateY(0);
        }
        
        .floating-btn {
            position: fixed;
            bottom: 80px;
            right: 20px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            border: none;
            font-size: 24px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            z-index: 1000;
        }
        
        .mobile-search {
            background: #f8f9fa;
            border-radius: 25px;
            padding: 10px 20px;
            margin-bottom: 20px;
            border: none;
            font-size: 16px;
        }
        
        .mobile-search:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(0,123,255,0.25);
        }
        
        .stats-card {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            margin-bottom: 15px;
        }
        
        .stats-number {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stats-label {
            font-size: 14px;
            opacity: 0.9;
        }
        
        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            body {
                background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            }
            
            .mobile-container {
                background: #1a1a1a;
                color: white;
            }
            
            .forum-card {
                background: #2d2d2d;
                color: white;
            }
            
            .mobile-nav {
                background: #2d2d2d;
                border-bottom-color: #444;
            }
            
            .nav-item {
                color: #ccc;
            }
            
            .nav-item.active {
                color: #007bff;
            }
        }
        
        /* Touch optimizations */
        .touch-friendly {
            -webkit-tap-highlight-color: transparent;
            -webkit-touch-callout: none;
            -webkit-user-select: none;
            -khtml-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }
        
        /* Swipe gestures */
        .swipeable {
            touch-action: pan-x;
        }
        
        /* Pull to refresh */
        .pull-to-refresh {
            position: relative;
            overflow: hidden;
        }
    </style>
</head>
<body class="<?php echo $isMobile ? 'mobile-device' : ''; ?>">
    <!-- Mobile Header -->
    <div class="mobile-header">
        <h1 class="h3 mb-0">
            <i class="fas fa-mobile-alt me-2"></i>
            Forum Project
        </h1>
        <p class="mb-0">Mobile Community Forum</p>
        <?php if ($isKSWeb): ?>
            <small><i class="fas fa-server me-1"></i>KSWeb Server</small>
        <?php endif; ?>
    </div>
    
    <!-- Mobile Container -->
    <div class="mobile-container">
        <!-- Mobile Navigation -->
        <div class="mobile-nav">
            <a href="#" class="nav-item active">
                <i class="fas fa-home"></i>
                <span>Home</span>
            </a>
            <a href="#" class="nav-item">
                <i class="fas fa-comments"></i>
                <span>Forums</span>
            </a>
            <a href="#" class="nav-item">
                <i class="fas fa-search"></i>
                <span>Search</span>
            </a>
            <a href="#" class="nav-item">
                <i class="fas fa-bell"></i>
                <span>Notifications</span>
            </a>
            <a href="#" class="nav-item">
                <i class="fas fa-user"></i>
                <span>Profile</span>
            </a>
        </div>
        
        <!-- Mobile Content -->
        <div class="mobile-content">
            <!-- Search Bar -->
            <input type="text" class="mobile-search" placeholder="Search forums, threads, posts..." id="mobileSearch">
            
            <!-- Stats Cards -->
            <div class="row">
                <div class="col-4">
                    <div class="stats-card">
                        <div class="stats-number">1,234</div>
                        <div class="stats-label">Members</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="stats-card">
                        <div class="stats-number">5,678</div>
                        <div class="stats-label">Posts</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="stats-card">
                        <div class="stats-number">123</div>
                        <div class="stats-label">Forums</div>
                    </div>
                </div>
            </div>
            
            <!-- Forums List -->
            <div class="forums-list">
                <h5 class="mb-3">
                    <i class="fas fa-list me-2"></i>
                    Popular Forums
                </h5>
                
                <div class="forum-card touch-friendly">
                    <div class="d-flex align-items-start">
                        <div class="forum-icon" style="background: linear-gradient(45deg, #007bff, #0056b3);">
                            <i class="fas fa-comments"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-2">General Discussion</h6>
                            <p class="text-muted mb-2">General topics and discussions for everyone</p>
                            <div class="forum-stats">
                                <span><i class="fas fa-comments me-1"></i> 1,234 posts</span>
                                <span><i class="fas fa-eye me-1"></i> 5,678 views</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="forum-card touch-friendly">
                    <div class="d-flex align-items-start">
                        <div class="forum-icon" style="background: linear-gradient(45deg, #28a745, #20c997);">
                            <i class="fas fa-tools"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-2">Technical Support</h6>
                            <p class="text-muted mb-2">Get help with technical issues</p>
                            <div class="forum-stats">
                                <span><i class="fas fa-comments me-1"></i> 567 posts</span>
                                <span><i class="fas fa-eye me-1"></i> 2,345 views</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="forum-card touch-friendly">
                    <div class="d-flex align-items-start">
                        <div class="forum-icon" style="background: linear-gradient(45deg, #ffc107, #fd7e14);">
                            <i class="fas fa-bullhorn"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-2">Announcements</h6>
                            <p class="text-muted mb-2">Important announcements and news</p>
                            <div class="forum-stats">
                                <span><i class="fas fa-comments me-1"></i> 89 posts</span>
                                <span><i class="fas fa-eye me-1"></i> 1,234 views</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="mt-4">
                <button class="mobile-btn" onclick="showLoginModal()">
                    <i class="fas fa-sign-in-alt me-2"></i>
                    Login to Forum
                </button>
                
                <button class="mobile-btn" onclick="showRegisterModal()" style="background: linear-gradient(45deg, #28a745, #20c997);">
                    <i class="fas fa-user-plus me-2"></i>
                    Create Account
                </button>
            </div>
        </div>
    </div>
    
    <!-- Floating Action Button -->
    <button class="floating-btn" onclick="showQuickPost()">
        <i class="fas fa-plus"></i>
    </button>
    
    <!-- Mobile Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/public/mobile/js/mobile.js"></script>
    
    <script>
        // Mobile-specific JavaScript
        document.addEventListener('DOMContentLoaded', function() {
            // Add touch-friendly classes
            document.querySelectorAll('.forum-card, .mobile-btn, .nav-item').forEach(el => {
                el.classList.add('touch-friendly');
            });
            
            // Enable swipe gestures
            enableSwipeGestures();
            
            // Enable pull to refresh
            enablePullToRefresh();
            
            // Enable vibration
            enableVibration();
            
            // PWA installation
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.register('/sw.js')
                    .then(registration => console.log('SW registered'))
                    .catch(error => console.log('SW registration failed'));
            }
        });
        
        // Mobile functions
        function showLoginModal() {
            // Show login modal
            if ('vibrate' in navigator) {
                navigator.vibrate(50);
            }
            window.location.href = 'mobile-login.php';
        }
        
        function showRegisterModal() {
            // Show register modal
            if ('vibrate' in navigator) {
                navigator.vibrate(50);
            }
            window.location.href = 'mobile-register.php';
        }
        
        function showQuickPost() {
            // Show quick post modal
            if ('vibrate' in navigator) {
                navigator.vibrate(50);
            }
            alert('Quick Post feature coming soon!');
        }
        
        // Search functionality
        document.getElementById('mobileSearch').addEventListener('input', function(e) {
            const query = e.target.value;
            if (query.length > 2) {
                // Implement search
                console.log('Searching for:', query);
            }
        });
        
        // Navigation
        document.querySelectorAll('.nav-item').forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Remove active class from all items
                document.querySelectorAll('.nav-item').forEach(nav => {
                    nav.classList.remove('active');
                });
                
                // Add active class to clicked item
                this.classList.add('active');
                
                // Vibration feedback
                if ('vibrate' in navigator) {
                    navigator.vibrate(30);
                }
            });
        });
        
        // Forum card interactions
        document.querySelectorAll('.forum-card').forEach(card => {
            card.addEventListener('click', function() {
                // Vibration feedback
                if ('vibrate' in navigator) {
                    navigator.vibrate(50);
                }
                
                // Navigate to forum
                console.log('Navigating to forum');
            });
        });
    </script>
</body>
</html>