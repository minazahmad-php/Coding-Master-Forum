<!DOCTYPE html>
<html lang="en" data-theme="<?= $state['theme'] ?? 'light' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $data['page_title'] ?? SITE_NAME ?></title>
    <meta name="description" content="<?= $data['meta_description'] ?? SITE_DESCRIPTION ?>">
    <meta name="keywords" content="<?= SITE_KEYWORDS ?>">
    <meta name="csrf-token" content="<?= generateCsrfToken() ?>">
    <meta name="user-id" content="<?= \Core\Auth::getUserId() ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Analytics -->
    <?php if (defined('GOOGLE_ANALYTICS_ID') && GOOGLE_ANALYTICS_ID): ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?= GOOGLE_ANALYTICS_ID ?>"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '<?= GOOGLE_ANALYTICS_ID ?>');
    </script>
    <?php endif; ?>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <div class="flex items-center justify-between">
                <!-- Brand -->
                <a href="/" class="navbar-brand">
                    <span class="brand-icon">🚀</span>
                    <?= SITE_NAME ?>
                </a>
                
                <!-- Navigation Links -->
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a href="/" class="nav-link <?= is_active('/') ?>">Home</a>
                    </li>
                    <li class="nav-item">
                        <a href="/forums" class="nav-link <?= is_active('/forums') ?>">Forums</a>
                    </li>
                    <li class="nav-item">
                        <a href="/users" class="nav-link <?= is_active('/users') ?>">Users</a>
                    </li>
                    <li class="nav-item">
                        <a href="/search" class="nav-link <?= is_active('/search') ?>">Search</a>
                    </li>
                </ul>
                
                <!-- User Actions -->
                <div class="flex items-center gap-4">
                    <!-- Theme Toggle -->
                    <button class="btn btn-secondary btn-sm" data-theme-toggle>
                        <span data-theme-icon>🌙</span>
                    </button>
                    
                    <?php if (\Core\Auth::isLoggedIn()): ?>
                        <!-- Notifications -->
                        <div class="notification-container">
                            <button class="btn btn-secondary btn-sm" data-notification-toggle>
                                🔔 <span class="notification-badge" style="display: none;">0</span>
                            </button>
                            <div class="notifications-container">
                                <div class="notifications-header">
                                    <h3>Notifications</h3>
                                    <button class="btn btn-sm" data-mark-all-read>Mark All Read</button>
                                </div>
                                <div class="notifications-list"></div>
                            </div>
                        </div>
                        
                        <!-- User Menu -->
                        <div class="user-menu">
                            <button class="user-menu-toggle">
                                <img src="<?= get_gravatar(\Core\Auth::getCurrentUser()['email'] ?? '') ?>" 
                                     alt="Avatar" class="user-avatar-sm">
                                <span><?= \Core\Auth::getUsername() ?></span>
                                <span class="dropdown-arrow">▼</span>
                            </button>
                            <div class="user-menu-dropdown">
                                <a href="/dashboard" class="menu-item">Dashboard</a>
                                <a href="/profile" class="menu-item">Profile</a>
                                <a href="/settings" class="menu-item">Settings</a>
                                <a href="/messages" class="menu-item">Messages</a>
                                <div class="menu-divider"></div>
                                <?php if (\Core\Auth::isAdmin()): ?>
                                    <a href="/admin" class="menu-item">Admin Panel</a>
                                <?php endif; ?>
                                <a href="/logout" class="menu-item">Logout</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Guest Actions -->
                        <a href="/login" class="btn btn-primary btn-sm">Login</a>
                        <a href="/register" class="btn btn-secondary btn-sm">Register</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <!-- Hero Section -->
            <section class="hero-section">
                <div class="hero-content">
                    <h1 class="hero-title">Welcome to <?= SITE_NAME ?></h1>
                    <p class="hero-description"><?= SITE_DESCRIPTION ?></p>
                    <div class="hero-actions">
                        <a href="/forums" class="btn btn-primary btn-lg">Explore Forums</a>
                        <?php if (!\Core\Auth::isLoggedIn()): ?>
                            <a href="/register" class="btn btn-outline btn-lg">Join Community</a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="hero-stats">
                    <div class="stat-item">
                        <div class="stat-number"><?= number_format($data['stats']['total_users']) ?></div>
                        <div class="stat-label">Members</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?= number_format($data['stats']['total_threads']) ?></div>
                        <div class="stat-label">Threads</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?= number_format($data['stats']['total_posts']) ?></div>
                        <div class="stat-label">Posts</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?= $data['stats']['online_users'] ?></div>
                        <div class="stat-label">Online</div>
                    </div>
                </div>
            </section>

            <!-- Announcements -->
            <?php if (!empty($data['announcements'])): ?>
            <section class="announcements-section">
                <h2 class="section-title">📢 Announcements</h2>
                <div class="announcements-list">
                    <?php foreach ($data['announcements'] as $announcement): ?>
                    <div class="announcement-item">
                        <div class="announcement-icon">📢</div>
                        <div class="announcement-content">
                            <h3 class="announcement-title">
                                <a href="/thread/<?= $announcement['id'] ?>"><?= sanitize($announcement['title']) ?></a>
                            </h3>
                            <p class="announcement-excerpt"><?= excerpt($announcement['content'], 100) ?></p>
                            <div class="announcement-meta">
                                <span class="announcement-author">by <?= $announcement['username'] ?></span>
                                <span class="announcement-time"><?= timeAgo($announcement['created_at']) ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <!-- Featured Content -->
            <div class="content-grid">
                <!-- Featured Threads -->
                <section class="featured-threads">
                    <div class="section-header">
                        <h2 class="section-title">⭐ Featured Threads</h2>
                        <a href="/forums" class="section-link">View All</a>
                    </div>
                    <div class="threads-list">
                        <?php foreach ($data['featured_threads'] as $thread): ?>
                        <div class="thread-item featured">
                            <div class="thread-content">
                                <h3 class="thread-title">
                                    <a href="/thread/<?= $thread['id'] ?>"><?= sanitize($thread['title']) ?></a>
                                </h3>
                                <p class="thread-excerpt"><?= excerpt($thread['content'], 80) ?></p>
                                <div class="thread-meta">
                                    <span class="thread-author">
                                        <img src="<?= get_gravatar($thread['email'] ?? '') ?>" 
                                             alt="Avatar" class="thread-avatar">
                                        <?= $thread['username'] ?>
                                    </span>
                                    <span class="thread-forum"><?= $thread['forum_name'] ?></span>
                                    <span class="thread-time"><?= timeAgo($thread['created_at']) ?></span>
                                </div>
                            </div>
                            <div class="thread-stats">
                                <div class="stat">
                                    <span class="stat-icon">👁️</span>
                                    <span class="stat-value"><?= number_format($thread['views']) ?></span>
                                </div>
                                <div class="stat">
                                    <span class="stat-icon">💬</span>
                                    <span class="stat-value"><?= $thread['replies_count'] ?></span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <!-- Latest Threads -->
                <section class="latest-threads">
                    <div class="section-header">
                        <h2 class="section-title">🕒 Latest Discussions</h2>
                        <a href="/forums" class="section-link">View All</a>
                    </div>
                    <div class="threads-list">
                        <?php foreach ($data['latest_threads'] as $thread): ?>
                        <div class="thread-item">
                            <div class="thread-content">
                                <h3 class="thread-title">
                                    <a href="/thread/<?= $thread['id'] ?>"><?= sanitize($thread['title']) ?></a>
                                </h3>
                                <div class="thread-meta">
                                    <span class="thread-author"><?= $thread['username'] ?></span>
                                    <span class="thread-forum"><?= $thread['forum_name'] ?></span>
                                    <span class="thread-time"><?= timeAgo($thread['created_at']) ?></span>
                                </div>
                            </div>
                            <div class="thread-stats">
                                <div class="stat">
                                    <span class="stat-icon">💬</span>
                                    <span class="stat-value"><?= $thread['replies_count'] ?></span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>

            <!-- Popular Threads -->
            <section class="popular-threads">
                <div class="section-header">
                    <h2 class="section-title">🔥 Popular This Week</h2>
                    <a href="/forums?sort=popular" class="section-link">View All</a>
                </div>
                <div class="threads-grid">
                    <?php foreach ($data['popular_threads'] as $thread): ?>
                    <div class="thread-card">
                        <div class="thread-header">
                            <h3 class="thread-title">
                                <a href="/thread/<?= $thread['id'] ?>"><?= sanitize($thread['title']) ?></a>
                            </h3>
                            <div class="thread-badges">
                                <?php if ($thread['is_pinned']): ?>
                                    <span class="badge badge-pinned">📌 Pinned</span>
                                <?php endif; ?>
                                <?php if ($thread['is_locked']): ?>
                                    <span class="badge badge-locked">🔒 Locked</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="thread-content">
                            <p class="thread-excerpt"><?= excerpt($thread['content'], 120) ?></p>
                        </div>
                        <div class="thread-footer">
                            <div class="thread-author">
                                <img src="<?= get_gravatar($thread['email'] ?? '') ?>" 
                                     alt="Avatar" class="thread-avatar">
                                <span><?= $thread['username'] ?></span>
                            </div>
                            <div class="thread-stats">
                                <span class="stat">👁️ <?= number_format($thread['views']) ?></span>
                                <span class="stat">💬 <?= $thread['replies_count'] ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Top Users -->
            <section class="top-users">
                <div class="section-header">
                    <h2 class="section-title">👑 Top Contributors</h2>
                    <a href="/users" class="section-link">View All</a>
                </div>
                <div class="users-grid">
                    <?php foreach ($data['top_users'] as $user): ?>
                    <div class="user-card">
                        <div class="user-avatar">
                            <img src="<?= get_gravatar($user['email']) ?>" 
                                 alt="<?= $user['username'] ?>" class="avatar">
                        </div>
                        <div class="user-info">
                            <h3 class="user-name">
                                <a href="/user/<?= $user['username'] ?>"><?= $user['username'] ?></a>
                            </h3>
                            <div class="user-stats">
                                <span class="stat">Posts: <?= number_format($user['posts_count']) ?></span>
                                <span class="stat">Rep: <?= number_format($user['reputation']) ?></span>
                            </div>
                        </div>
                        <?php if (\Core\Auth::isLoggedIn() && $user['id'] != \Core\Auth::getUserId()): ?>
                        <div class="user-actions">
                            <button class="btn btn-sm btn-outline" 
                                    data-follow-btn 
                                    data-user-id="<?= $user['id'] ?>">
                                Follow
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Recent Activity -->
            <section class="recent-activity">
                <div class="section-header">
                    <h2 class="section-title">⚡ Recent Activity</h2>
                </div>
                <div class="activity-list">
                    <?php foreach ($data['recent_activity'] as $activity): ?>
                    <div class="activity-item">
                        <div class="activity-icon">
                            <?= $activity['type'] === 'thread' ? '📝' : '💬' ?>
                        </div>
                        <div class="activity-content">
                            <div class="activity-title">
                                <a href="/<?= $activity['type'] === 'thread' ? 'thread' : 'thread' ?>/<?= $activity['id'] ?>">
                                    <?= sanitize($activity['title']) ?>
                                </a>
                            </div>
                            <div class="activity-meta">
                                <span class="activity-author"><?= $activity['username'] ?></span>
                                <span class="activity-forum"><?= $activity['forum_name'] ?></span>
                                <span class="activity-time"><?= timeAgo($activity['created_at']) ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3 class="footer-title"><?= SITE_NAME ?></h3>
                    <p class="footer-description"><?= SITE_DESCRIPTION ?></p>
                    <div class="social-links">
                        <a href="#" class="social-link">📘 Facebook</a>
                        <a href="#" class="social-link">🐦 Twitter</a>
                        <a href="#" class="social-link">📷 Instagram</a>
                        <a href="#" class="social-link">💼 LinkedIn</a>
                    </div>
                </div>
                <div class="footer-section">
                    <h4 class="footer-subtitle">Community</h4>
                    <ul class="footer-links">
                        <li><a href="/forums">Forums</a></li>
                        <li><a href="/users">Members</a></li>
                        <li><a href="/help">Help Center</a></li>
                        <li><a href="/faq">FAQ</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4 class="footer-subtitle">Legal</h4>
                    <ul class="footer-links">
                        <li><a href="/terms">Terms of Service</a></li>
                        <li><a href="/privacy">Privacy Policy</a></li>
                        <li><a href="/contact">Contact Us</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4 class="footer-subtitle">Statistics</h4>
                    <div class="footer-stats">
                        <div class="stat">
                            <span class="stat-number"><?= number_format($data['stats']['total_users']) ?></span>
                            <span class="stat-label">Members</span>
                        </div>
                        <div class="stat">
                            <span class="stat-number"><?= number_format($data['stats']['total_posts']) ?></span>
                            <span class="stat-label">Posts</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> <?= SITE_NAME ?>. All rights reserved.</p>
                <p>Powered by Universal Forum Hub</p>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="<?= asset('js/app.js') ?>"></script>
    
    <!-- Notification Styles -->
    <style>
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 20px;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            z-index: 1000;
            transform: translateX(100%);
            transition: transform 0.3s ease;
        }
        
        .notification.show {
            transform: translateX(0);
        }
        
        .notification-success { background-color: #10b981; }
        .notification-error { background-color: #ef4444; }
        .notification-warning { background-color: #f59e0b; }
        .notification-info { background-color: #3b82f6; }
    </style>
</body>
</html>