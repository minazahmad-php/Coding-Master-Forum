<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= View::csrfToken() ?>">
    <meta name="description" content="<?= View::escape($meta_description ?? 'Modern Forum - Connect, Discuss, Share') ?>">
    <meta name="keywords" content="<?= View::escape($meta_keywords ?? 'forum, community, discussion, modern') ?>">
    <meta name="author" content="<?= View::escape(APP_NAME) ?>">
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="<?= View::escape($title ?? APP_NAME) ?>">
    <meta property="og:description" content="<?= View::escape($meta_description ?? 'Modern Forum - Connect, Discuss, Share') ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= View::escape(APP_URL . $_SERVER['REQUEST_URI']) ?>">
    <meta property="og:image" content="<?= View::escape(APP_URL . '/assets/images/og-image.jpg') ?>">
    
    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= View::escape($title ?? APP_NAME) ?>">
    <meta name="twitter:description" content="<?= View::escape($meta_description ?? 'Modern Forum - Connect, Discuss, Share') ?>">
    <meta name="twitter:image" content="<?= View::escape(APP_URL . '/assets/images/twitter-image.jpg') ?>">
    
    <title><?= View::escape($title ?? APP_NAME) ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/assets/images/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/images/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/images/favicon-16x16.png">
    <link rel="manifest" href="/manifest.json">
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/admin.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#3b82f6">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="<?= View::escape(APP_NAME) ?>">
    
    <!-- Preload Critical Resources -->
    <link rel="preload" href="/assets/css/main.css" as="style">
    <link rel="preload" href="/assets/js/main.js" as="script">
</head>
<body>
    <!-- Skip to main content for accessibility -->
    <a href="#main-content" class="sr-only">Skip to main content</a>
    
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <div class="navbar-content">
                <!-- Brand -->
                <a href="/" class="navbar-brand">
                    <i class="fas fa-comments"></i>
                    <?= View::escape(APP_NAME) ?>
                </a>
                
                <!-- Search -->
                <div class="navbar-search" data-search-container>
                    <form action="/search" method="GET" data-search-form>
                        <div class="search-input-group">
                            <input type="text" 
                                   name="q" 
                                   placeholder="Search forums..." 
                                   class="search-input"
                                   data-search-input
                                   value="<?= View::escape($_GET['q'] ?? '') ?>">
                            <button type="submit" class="search-button">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Navigation Links -->
                <div class="navbar-nav">
                    <a href="/" class="nav-link <?= $_SERVER['REQUEST_URI'] === '/' ? 'active' : '' ?>">
                        <i class="fas fa-home"></i>
                        <span>Home</span>
                    </a>
                    
                    <a href="/forums" class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/forums') === 0 ? 'active' : '' ?>">
                        <i class="fas fa-th-list"></i>
                        <span>Forums</span>
                    </a>
                    
                    <?php if ($is_logged_in): ?>
                        <a href="/dashboard" class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/dashboard') === 0 ? 'active' : '' ?>">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                        
                        <a href="/messages" class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/messages') === 0 ? 'active' : '' ?>">
                            <i class="fas fa-envelope"></i>
                            <span>Messages</span>
                            <?php if (isset($unread_messages) && $unread_messages > 0): ?>
                                <span class="nav-badge"><?= $unread_messages ?></span>
                            <?php endif; ?>
                        </a>
                        
                        <a href="/notifications" class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/notifications') === 0 ? 'active' : '' ?>">
                            <i class="fas fa-bell"></i>
                            <span>Notifications</span>
                            <?php if (isset($unread_notifications) && $unread_notifications > 0): ?>
                                <span class="nav-badge"><?= $unread_notifications ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endif; ?>
                </div>
                
                <!-- User Menu -->
                <div class="navbar-user">
                    <?php if ($is_logged_in): ?>
                        <!-- Theme Toggle -->
                        <button class="theme-toggle" data-theme-toggle title="Toggle theme">
                            <i class="fas fa-moon"></i>
                        </button>
                        
                        <!-- User Dropdown -->
                        <div class="user-dropdown" data-dropdown-trigger>
                            <div class="user-avatar">
                                <img src="<?= View::escape($current_user['avatar'] ?? '/assets/images/default-avatar.png') ?>" 
                                     alt="<?= View::escape($current_user['username'] ?? 'User') ?>">
                                <span class="user-name"><?= View::escape($current_user['username'] ?? 'User') ?></span>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            
                            <div class="dropdown-menu" data-dropdown-menu>
                                <a href="/profile" class="dropdown-item">
                                    <i class="fas fa-user"></i>
                                    Profile
                                </a>
                                <a href="/settings" class="dropdown-item">
                                    <i class="fas fa-cog"></i>
                                    Settings
                                </a>
                                <a href="/bookmarks" class="dropdown-item">
                                    <i class="fas fa-bookmark"></i>
                                    Bookmarks
                                </a>
                                
                                <?php if ($is_admin): ?>
                                    <div class="dropdown-divider"></div>
                                    <a href="/admin" class="dropdown-item">
                                        <i class="fas fa-shield-alt"></i>
                                        Admin Panel
                                    </a>
                                <?php endif; ?>
                                
                                <div class="dropdown-divider"></div>
                                <a href="/logout" class="dropdown-item">
                                    <i class="fas fa-sign-out-alt"></i>
                                    Logout
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Guest Menu -->
                        <div class="guest-menu">
                            <a href="/login" class="btn btn-outline btn-sm">Login</a>
                            <a href="/register" class="btn btn-primary btn-sm">Register</a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Mobile Menu Toggle -->
                <button class="mobile-menu-toggle" data-mobile-menu-toggle>
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
    </nav>
    
    <!-- Mobile Menu -->
    <div class="mobile-menu" data-mobile-menu>
        <div class="mobile-menu-content">
            <div class="mobile-menu-header">
                <h3>Menu</h3>
                <button class="mobile-menu-close" data-mobile-menu-close>
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="mobile-menu-nav">
                <a href="/" class="mobile-nav-link">
                    <i class="fas fa-home"></i>
                    Home
                </a>
                <a href="/forums" class="mobile-nav-link">
                    <i class="fas fa-th-list"></i>
                    Forums
                </a>
                
                <?php if ($is_logged_in): ?>
                    <a href="/dashboard" class="mobile-nav-link">
                        <i class="fas fa-tachometer-alt"></i>
                        Dashboard
                    </a>
                    <a href="/messages" class="mobile-nav-link">
                        <i class="fas fa-envelope"></i>
                        Messages
                    </a>
                    <a href="/notifications" class="mobile-nav-link">
                        <i class="fas fa-bell"></i>
                        Notifications
                    </a>
                    <a href="/profile" class="mobile-nav-link">
                        <i class="fas fa-user"></i>
                        Profile
                    </a>
                    <a href="/settings" class="mobile-nav-link">
                        <i class="fas fa-cog"></i>
                        Settings
                    </a>
                    
                    <?php if ($is_admin): ?>
                        <a href="/admin" class="mobile-nav-link">
                            <i class="fas fa-shield-alt"></i>
                            Admin Panel
                        </a>
                    <?php endif; ?>
                    
                    <a href="/logout" class="mobile-nav-link">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                <?php else: ?>
                    <a href="/login" class="mobile-nav-link">
                        <i class="fas fa-sign-in-alt"></i>
                        Login
                    </a>
                    <a href="/register" class="mobile-nav-link">
                        <i class="fas fa-user-plus"></i>
                        Register
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Flash Messages -->
    <?php if (!empty($flash_messages)): ?>
        <div class="flash-messages">
            <?php foreach ($flash_messages as $type => $messages): ?>
                <?php foreach ($messages as $message): ?>
                    <div class="alert alert-<?= $type ?>">
                        <i class="fas fa-<?= $type === 'success' ? 'check-circle' : ($type === 'error' ? 'exclamation-circle' : 'info-circle') ?>"></i>
                        <?= View::escape($message) ?>
                        <button class="alert-close" onclick="this.parentElement.remove()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <!-- Main Content -->
    <main id="main-content" class="main-content">
        <?= $content ?? '' ?>
    </main>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h4><?= View::escape(APP_NAME) ?></h4>
                    <p>Connect, discuss, and share with our modern forum community.</p>
                    <div class="social-links">
                        <a href="#" class="social-link" title="Facebook">
                            <i class="fab fa-facebook"></i>
                        </a>
                        <a href="#" class="social-link" title="Twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="social-link" title="LinkedIn">
                            <i class="fab fa-linkedin"></i>
                        </a>
                        <a href="#" class="social-link" title="GitHub">
                            <i class="fab fa-github"></i>
                        </a>
                    </div>
                </div>
                
                <div class="footer-section">
                    <h5>Community</h5>
                    <ul class="footer-links">
                        <li><a href="/forums">Forums</a></li>
                        <li><a href="/users">Members</a></li>
                        <li><a href="/leaderboard">Leaderboard</a></li>
                        <li><a href="/badges">Badges</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h5>Support</h5>
                    <ul class="footer-links">
                        <li><a href="/help">Help Center</a></li>
                        <li><a href="/contact">Contact Us</a></li>
                        <li><a href="/faq">FAQ</a></li>
                        <li><a href="/guidelines">Guidelines</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h5>Legal</h5>
                    <ul class="footer-links">
                        <li><a href="/privacy">Privacy Policy</a></li>
                        <li><a href="/terms">Terms of Service</a></li>
                        <li><a href="/cookies">Cookie Policy</a></li>
                        <li><a href="/dmca">DMCA</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <div class="footer-copyright">
                    <p>&copy; <?= date('Y') ?> <?= View::escape(APP_NAME) ?>. All rights reserved.</p>
                </div>
                
                <div class="footer-stats">
                    <span>Online: <strong><?= $online_users ?? 0 ?></strong></span>
                    <span>Members: <strong><?= $total_members ?? 0 ?></strong></span>
                    <span>Posts: <strong><?= $total_posts ?? 0 ?></strong></span>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Scroll to Top Button -->
    <button class="scroll-to-top" data-scroll-top title="Scroll to top">
        <i class="fas fa-chevron-up"></i>
    </button>
    
    <!-- JavaScript -->
    <script src="/assets/js/main.js"></script>
    <script src="/assets/js/pwa.js"></script>
    
    <!-- Service Worker Registration -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(registration => {
                        console.log('SW registered: ', registration);
                    })
                    .catch(registrationError => {
                        console.log('SW registration failed: ', registrationError);
                    });
            });
        }
    </script>
    
    <!-- Real-time Updates -->
    <script>
        // Initialize real-time features
        if (window.modernForum) {
            window.modernForum.initRealTime();
        }
    </script>
</body>
</html>