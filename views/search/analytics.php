<!DOCTYPE html>
<html lang="<?php echo DEFAULT_LANG; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Analytics - <?php echo SITE_NAME; ?></title>
    <meta name="description" content="Search analytics and insights for <?php echo SITE_NAME; ?>">
    
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/analytics.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="admin-container">
        <header class="admin-header">
            <div class="container">
                <h1>Search Analytics</h1>
                <nav class="admin-nav">
                    <a href="/admin">Dashboard</a>
                    <a href="/admin/users">Users</a>
                    <a href="/admin/forums">Forums</a>
                    <a href="/admin/analytics" class="active">Analytics</a>
                    <a href="/admin/settings">Settings</a>
                </nav>
            </div>
        </header>
        
        <main class="admin-main">
            <div class="container">
                <div class="analytics-header">
                    <div class="analytics-controls">
                        <select id="time-period" onchange="updateAnalytics()">
                            <option value="7" <?php echo $days === 7 ? 'selected' : ''; ?>>Last 7 days</option>
                            <option value="30" <?php echo $days === 30 ? 'selected' : ''; ?>>Last 30 days</option>
                            <option value="90" <?php echo $days === 90 ? 'selected' : ''; ?>>Last 90 days</option>
                        </select>
                        
                        <button onclick="exportData()" class="export-button">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="7,10 12,15 17,10"></polyline>
                                <line x1="12" y1="15" x2="12" y2="3"></line>
                            </svg>
                            Export Data
                        </button>
                    </div>
                </div>
                
                <div class="analytics-grid">
                    <!-- Summary Cards -->
                    <div class="analytics-card">
                        <h3>Search Overview</h3>
                        <div class="metric-grid">
                            <div class="metric">
                                <div class="metric-value"><?php echo number_format($insights['performance']['total_searches'] ?? 0); ?></div>
                                <div class="metric-label">Total Searches</div>
                            </div>
                            <div class="metric">
                                <div class="metric-value"><?php echo number_format($insights['performance']['unique_searchers'] ?? 0); ?></div>
                                <div class="metric-label">Unique Searchers</div>
                            </div>
                            <div class="metric">
                                <div class="metric-value"><?php echo number_format($insights['performance']['avg_results_per_search'] ?? 0, 1); ?></div>
                                <div class="metric-label">Avg Results</div>
                            </div>
                            <div class="metric">
                                <div class="metric-value"><?php echo number_format($insights['performance']['success_rate'] ?? 0, 1); ?>%</div>
                                <div class="metric-label">Success Rate</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Search Trends Chart -->
                    <div class="analytics-card">
                        <h3>Search Trends</h3>
                        <canvas id="trends-chart"></canvas>
                    </div>
                    
                    <!-- Popular Searches -->
                    <div class="analytics-card">
                        <h3>Popular Searches</h3>
                        <div class="popular-searches">
                            <?php foreach ($insights['top_searches'] as $index => $search): ?>
                                <div class="search-item">
                                    <div class="search-rank"><?php echo $index + 1; ?></div>
                                    <div class="search-details">
                                        <div class="search-query"><?php echo htmlspecialchars($search['query']); ?></div>
                                        <div class="search-stats">
                                            <span class="search-count"><?php echo number_format($search['search_count']); ?> searches</span>
                                            <span class="search-avg"><?php echo number_format($search['avg_results'], 1); ?> avg results</span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- No Results Searches -->
                    <div class="analytics-card">
                        <h3>No Results Searches</h3>
                        <div class="no-results-searches">
                            <?php foreach ($insights['no_results'] as $search): ?>
                                <div class="no-result-item">
                                    <div class="no-result-query"><?php echo htmlspecialchars($search['query']); ?></div>
                                    <div class="no-result-count"><?php echo number_format($search['count']); ?> times</div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- User Behavior -->
                    <div class="analytics-card">
                        <h3>User Behavior</h3>
                        <div class="behavior-metrics">
                            <div class="behavior-metric">
                                <div class="behavior-value"><?php echo number_format($insights['user_behavior']['avg_searches_per_user'] ?? 0, 1); ?></div>
                                <div class="behavior-label">Avg Searches per User</div>
                            </div>
                        </div>
                        
                        <div class="active-searchers">
                            <h4>Most Active Searchers</h4>
                            <?php foreach ($insights['user_behavior']['most_active_searchers'] as $searcher): ?>
                                <div class="searcher-item">
                                    <div class="searcher-name"><?php echo htmlspecialchars($searcher['username']); ?></div>
                                    <div class="searcher-count"><?php echo number_format($searcher['search_count']); ?> searches</div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Performance Metrics -->
                    <div class="analytics-card">
                        <h3>Performance Metrics</h3>
                        <div class="performance-metrics">
                            <div class="performance-metric">
                                <div class="performance-value"><?php echo number_format($insights['performance']['avg_search_time'] ?? 0, 2); ?>s</div>
                                <div class="performance-label">Avg Search Time</div>
                            </div>
                            <div class="performance-metric">
                                <div class="performance-value"><?php echo number_format($insights['performance']['click_through_rate'] ?? 0, 1); ?>%</div>
                                <div class="performance-label">Click-through Rate</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        // Initialize charts
        document.addEventListener('DOMContentLoaded', function() {
            initializeTrendsChart();
        });
        
        function initializeTrendsChart() {
            const ctx = document.getElementById('trends-chart').getContext('2d');
            const trendsData = <?php echo json_encode($insights['trends']); ?>;
            
            const chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: trendsData.map(item => item.date).reverse(),
                    datasets: [{
                        label: 'Total Searches',
                        data: trendsData.map(item => item.total_searches).reverse(),
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        tension: 0.4
                    }, {
                        label: 'Unique Searchers',
                        data: trendsData.map(item => item.unique_searchers).reverse(),
                        borderColor: '#764ba2',
                        backgroundColor: 'rgba(118, 75, 162, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                    }
                }
            });
        }
        
        function updateAnalytics() {
            const days = document.getElementById('time-period').value;
            window.location.href = `/search/analytics?days=${days}`;
        }
        
        function exportData() {
            const days = document.getElementById('time-period').value;
            const format = prompt('Export format (json/csv/xml):', 'json');
            
            if (format && ['json', 'csv', 'xml'].includes(format.toLowerCase())) {
                window.open(`/search/export?format=${format}&days=${days}`, '_blank');
            }
        }
        
        // Auto-refresh every 5 minutes
        setInterval(() => {
            updateAnalytics();
        }, 300000);
    </script>
</body>
</html>