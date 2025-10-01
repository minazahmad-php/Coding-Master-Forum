<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Analytics Dashboard' ?> - <?= SITE_NAME ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="/assets/css/analytics.css" rel="stylesheet">
    
    <!-- PWA Meta Tags -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#007bff">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="<?= SITE_NAME ?>">
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="<?= $description ?? 'Advanced analytics dashboard for ' . SITE_NAME ?>">
    <meta name="keywords" content="analytics, dashboard, statistics, reports, <?= SITE_NAME ?>">
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="<?= $title ?? 'Analytics Dashboard' ?> - <?= SITE_NAME ?>">
    <meta property="og:description" content="<?= $description ?? 'Advanced analytics dashboard' ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= SITE_URL . $_SERVER['REQUEST_URI'] ?>">
    
    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= $title ?? 'Analytics Dashboard' ?> - <?= SITE_NAME ?>">
    <meta name="twitter:description" content="<?= $description ?? 'Advanced analytics dashboard' ?>">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="/">
                <i class="fas fa-chart-line me-2"></i>
                <?= SITE_NAME ?> Analytics
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>" href="/analytics">
                            <i class="fas fa-tachometer-alt me-1"></i>
                            Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'user' ? 'active' : '' ?>" href="/analytics/user">
                            <i class="fas fa-users me-1"></i>
                            Users
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'content' ? 'active' : '' ?>" href="/analytics/content">
                            <i class="fas fa-file-alt me-1"></i>
                            Content
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'traffic' ? 'active' : '' ?>" href="/analytics/traffic">
                            <i class="fas fa-globe me-1"></i>
                            Traffic
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'engagement' ? 'active' : '' ?>" href="/analytics/engagement">
                            <i class="fas fa-heart me-1"></i>
                            Engagement
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'conversion' ? 'active' : '' ?>" href="/analytics/conversion">
                            <i class="fas fa-exchange-alt me-1"></i>
                            Conversion
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'revenue' ? 'active' : '' ?>" href="/analytics/revenue">
                            <i class="fas fa-dollar-sign me-1"></i>
                            Revenue
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'performance' ? 'active' : '' ?>" href="/analytics/performance">
                            <i class="fas fa-tachometer-alt me-1"></i>
                            Performance
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="timeRangeDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-calendar me-1"></i>
                            <?= $days ?? 30 ?> Days
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="?days=7">7 Days</a></li>
                            <li><a class="dropdown-item" href="?days=30">30 Days</a></li>
                            <li><a class="dropdown-item" href="?days=90">90 Days</a></li>
                            <li><a class="dropdown-item" href="?days=365">1 Year</a></li>
                        </ul>
                    </li>
                    
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="exportDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-download me-1"></i>
                            Export
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="/analytics/export?type=user&format=csv">Export User Data</a></li>
                            <li><a class="dropdown-item" href="/analytics/export?type=content&format=csv">Export Content Data</a></li>
                            <li><a class="dropdown-item" href="/analytics/export?type=traffic&format=csv">Export Traffic Data</a></li>
                            <li><a class="dropdown-item" href="/analytics/export?type=engagement&format=csv">Export Engagement Data</a></li>
                            <li><a class="dropdown-item" href="/analytics/export?type=conversion&format=csv">Export Conversion Data</a></li>
                            <li><a class="dropdown-item" href="/analytics/export?type=revenue&format=csv">Export Revenue Data</a></li>
                            <li><a class="dropdown-item" href="/analytics/export?type=performance&format=csv">Export Performance Data</a></li>
                        </ul>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link" href="/logout">
                            <i class="fas fa-sign-out-alt me-1"></i>
                            Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container-fluid py-4">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h3 mb-0">
                            <i class="fas fa-chart-line me-2"></i>
                            <?= $pageTitle ?? 'Analytics Dashboard' ?>
                        </h1>
                        <p class="text-muted mb-0">
                            <?= $pageDescription ?? 'Comprehensive analytics and insights for your forum' ?>
                        </p>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-primary" onclick="refreshData()">
                            <i class="fas fa-sync-alt me-1"></i>
                            Refresh
                        </button>
                        
                        <button class="btn btn-outline-secondary" onclick="toggleFullscreen()">
                            <i class="fas fa-expand me-1"></i>
                            Fullscreen
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Area -->
        <div class="row">
            <div class="col-12">
                <?= $content ?? '' ?>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-light py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0 text-muted">
                        &copy; <?= date('Y') ?> <?= SITE_NAME ?>. All rights reserved.
                    </p>
                </div>
                <div class="col-md-6 text-end">
                    <p class="mb-0 text-muted">
                        <i class="fas fa-clock me-1"></i>
                        Last updated: <span id="lastUpdated"><?= date('Y-m-d H:i:s') ?></span>
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay d-none">
        <div class="loading-spinner">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading analytics data...</p>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="/assets/js/analytics.js"></script>
    
    <!-- PWA Service Worker -->
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js')
                .then(registration => console.log('SW registered'))
                .catch(error => console.log('SW registration failed'));
        }
    </script>

    <script>
        // Global variables
        let charts = {};
        let refreshInterval;
        
        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            initializeCharts();
            startAutoRefresh();
            updateLastUpdatedTime();
        });
        
        // Initialize all charts
        function initializeCharts() {
            // This will be implemented in individual page scripts
            console.log('Charts initialized');
        }
        
        // Refresh data
        function refreshData() {
            showLoading();
            
            // Reload the page to get fresh data
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        }
        
        // Auto refresh every 5 minutes
        function startAutoRefresh() {
            refreshInterval = setInterval(() => {
                refreshData();
            }, 300000); // 5 minutes
        }
        
        // Stop auto refresh
        function stopAutoRefresh() {
            if (refreshInterval) {
                clearInterval(refreshInterval);
            }
        }
        
        // Toggle fullscreen
        function toggleFullscreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen();
            } else {
                document.exitFullscreen();
            }
        }
        
        // Show loading overlay
        function showLoading() {
            document.getElementById('loadingOverlay').classList.remove('d-none');
        }
        
        // Hide loading overlay
        function hideLoading() {
            document.getElementById('loadingOverlay').classList.add('d-none');
        }
        
        // Update last updated time
        function updateLastUpdatedTime() {
            const now = new Date();
            const timeString = now.toLocaleString();
            document.getElementById('lastUpdated').textContent = timeString;
        }
        
        // Create chart
        function createChart(canvasId, config) {
            const ctx = document.getElementById(canvasId);
            if (!ctx) return null;
            
            const chart = new Chart(ctx, config);
            charts[canvasId] = chart;
            return chart;
        }
        
        // Destroy chart
        function destroyChart(canvasId) {
            if (charts[canvasId]) {
                charts[canvasId].destroy();
                delete charts[canvasId];
            }
        }
        
        // Format number
        function formatNumber(num) {
            if (num >= 1000000) {
                return (num / 1000000).toFixed(1) + 'M';
            } else if (num >= 1000) {
                return (num / 1000).toFixed(1) + 'K';
            }
            return num.toString();
        }
        
        // Format percentage
        function formatPercentage(num) {
            return num.toFixed(1) + '%';
        }
        
        // Format currency
        function formatCurrency(num, currency = 'USD') {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: currency
            }).format(num);
        }
        
        // Format date
        function formatDate(date) {
            return new Date(date).toLocaleDateString();
        }
        
        // Format time
        function formatTime(date) {
            return new Date(date).toLocaleTimeString();
        }
        
        // Get color for chart
        function getChartColor(index) {
            const colors = [
                '#007bff', '#28a745', '#dc3545', '#ffc107', '#17a2b8',
                '#6f42c1', '#fd7e14', '#20c997', '#e83e8c', '#6c757d'
            ];
            return colors[index % colors.length];
        }
        
        // Handle window resize
        window.addEventListener('resize', function() {
            Object.values(charts).forEach(chart => {
                chart.resize();
            });
        });
        
        // Handle page visibility change
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                stopAutoRefresh();
            } else {
                startAutoRefresh();
            }
        });
        
        // Handle online/offline status
        window.addEventListener('online', function() {
            console.log('Connection restored');
            refreshData();
        });
        
        window.addEventListener('offline', function() {
            console.log('Connection lost');
            stopAutoRefresh();
        });
    </script>
</body>
</html>