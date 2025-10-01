// Advanced Analytics Dashboard JavaScript
class AdvancedAnalyticsDashboard {
    constructor() {
        this.currentTab = 'user-analytics';
        this.charts = {};
        this.filters = {
            start_date: document.getElementById('start-date').value,
            end_date: document.getElementById('end-date').value
        };
        
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.loadAnalyticsData();
    }

    setupEventListeners() {
        // Tab switching
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                this.switchTab(link.dataset.tab);
            });
        });

        // Date filters
        document.getElementById('apply-filters').addEventListener('click', () => {
            this.updateFilters();
            this.loadAnalyticsData();
        });

        // Export functionality
        document.getElementById('export-btn').addEventListener('click', () => {
            this.exportData();
        });

        // Real-time updates every 30 seconds
        setInterval(() => {
            if (this.currentTab === 'performance-analytics') {
                this.loadPerformanceAnalytics();
            }
        }, 30000);
    }

    switchTab(tabId) {
        // Hide all tabs
        document.querySelectorAll('.tab-pane').forEach(pane => {
            pane.classList.remove('active');
        });

        // Remove active class from all nav links
        document.querySelectorAll('.nav-link').forEach(link => {
            link.classList.remove('active');
        });

        // Show selected tab
        document.getElementById(tabId).classList.add('active');
        document.querySelector(`[data-tab="${tabId}"]`).classList.add('active');

        this.currentTab = tabId;
        this.loadTabData(tabId);
    }

    updateFilters() {
        this.filters.start_date = document.getElementById('start-date').value;
        this.filters.end_date = document.getElementById('end-date').value;
    }

    async loadAnalyticsData() {
        this.showLoading();
        await this.loadTabData(this.currentTab);
    }

    async loadTabData(tabId) {
        switch (tabId) {
            case 'user-analytics':
                await this.loadUserAnalytics();
                break;
            case 'content-analytics':
                await this.loadContentAnalytics();
                break;
            case 'traffic-analytics':
                await this.loadTrafficAnalytics();
                break;
            case 'engagement-analytics':
                await this.loadEngagementAnalytics();
                break;
            case 'revenue-analytics':
                await this.loadRevenueAnalytics();
                break;
            case 'performance-analytics':
                await this.loadPerformanceAnalytics();
                break;
        }
    }

    async loadUserAnalytics() {
        try {
            const response = await fetch(`/admin/analytics/user?${new URLSearchParams(this.filters)}`);
            const data = await response.json();

            // Update metrics
            document.getElementById('total-users').textContent = this.formatNumber(data.total_users);
            document.getElementById('new-users').textContent = this.formatNumber(data.new_users);
            document.getElementById('active-users').textContent = this.formatNumber(data.active_users);

            // Create charts
            this.createUserGrowthChart(data.user_growth);
            this.createUserRetentionChart(data.user_retention);
            this.displayTopUsers(data.top_users);

        } catch (error) {
            console.error('Error loading user analytics:', error);
            this.showError('Failed to load user analytics');
        }
    }

    async loadContentAnalytics() {
        try {
            const response = await fetch(`/admin/analytics/content?${new URLSearchParams(this.filters)}`);
            const data = await response.json();

            // Update metrics
            document.getElementById('total-posts').textContent = this.formatNumber(data.total_posts);
            document.getElementById('new-posts').textContent = this.formatNumber(data.new_posts);

            // Display popular content
            this.displayPopularContent(data.popular_content);

            // Create charts
            this.createContentCategoryChart(data.content_categories);
            this.createContentEngagementChart(data.content_engagement);
            this.displayContentQuality(data.content_quality);

        } catch (error) {
            console.error('Error loading content analytics:', error);
            this.showError('Failed to load content analytics');
        }
    }

    async loadTrafficAnalytics() {
        try {
            const response = await fetch(`/admin/analytics/traffic?${new URLSearchParams(this.filters)}`);
            const data = await response.json();

            // Update metrics
            document.getElementById('page-views').textContent = this.formatNumber(data.page_views);
            document.getElementById('unique-visitors').textContent = this.formatNumber(data.unique_visitors);

            // Create charts
            this.createTrafficSourcesChart(data.traffic_sources);
            this.createDeviceTypesChart(data.device_types);
            this.createBrowserStatsChart(data.browser_stats);
            this.createTrafficPatternsChart(data.traffic_patterns);

        } catch (error) {
            console.error('Error loading traffic analytics:', error);
            this.showError('Failed to load traffic analytics');
        }
    }

    async loadEngagementAnalytics() {
        try {
            const response = await fetch(`/admin/analytics/engagement?${new URLSearchParams(this.filters)}`);
            const data = await response.json();

            // Display session duration
            this.displaySessionDuration(data.session_duration);
            
            // Update bounce rate
            document.getElementById('bounce-rate').textContent = data.bounce_rate + '%';

            // Create charts
            this.createInteractionRateChart(data.interaction_rate);
            this.createEngagementScoreChart(data.engagement_score);
            this.createUserBehaviorChart(data.user_behavior);
            this.createConversionFunnelChart(data.conversion_funnel);

        } catch (error) {
            console.error('Error loading engagement analytics:', error);
            this.showError('Failed to load engagement analytics');
        }
    }

    async loadRevenueAnalytics() {
        try {
            const response = await fetch(`/admin/analytics/revenue?${new URLSearchParams(this.filters)}`);
            const data = await response.json();

            // Update total revenue
            document.getElementById('total-revenue').textContent = this.formatCurrency(data.total_revenue);

            // Create charts
            this.createRevenueSourceChart(data.revenue_by_source);
            this.displaySubscriptionAnalytics(data.subscription_analytics);
            this.createPaymentAnalyticsChart(data.payment_analytics);
            this.createRevenueTrendsChart(data.revenue_trends);
            this.displayCustomerCLV(data.customer_lifetime_value);

        } catch (error) {
            console.error('Error loading revenue analytics:', error);
            this.showError('Failed to load revenue analytics');
        }
    }

    async loadPerformanceAnalytics() {
        try {
            const response = await fetch('/admin/analytics/performance');
            const data = await response.json();

            // Display performance metrics
            this.displayPageLoadTimes(data.page_load_times);
            this.displayDatabasePerformance(data.database_performance);
            this.displayServerResources(data.server_resources);
            this.displayUptimeStats(data.uptime_stats);

            // Create charts
            this.createErrorRatesChart(data.error_rates);
            this.createPerformanceTrendsChart(data.performance_trends);

        } catch (error) {
            console.error('Error loading performance analytics:', error);
            this.showError('Failed to load performance analytics');
        }
    }

    // Chart creation methods
    createUserGrowthChart(data) {
        const ctx = document.getElementById('user-growth-chart').getContext('2d');
        
        if (this.charts.userGrowth) {
            this.charts.userGrowth.destroy();
        }

        this.charts.userGrowth = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.map(item => item.date),
                datasets: [{
                    label: 'New Users',
                    data: data.map(item => item.count),
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
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
                }
            }
        });
    }

    createUserRetentionChart(data) {
        const ctx = document.getElementById('user-retention-chart').getContext('2d');
        
        if (this.charts.userRetention) {
            this.charts.userRetention.destroy();
        }

        this.charts.userRetention = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.map(item => item.cohort_date),
                datasets: [
                    {
                        label: 'Total Users',
                        data: data.map(item => item.total_users),
                        backgroundColor: 'rgba(0, 123, 255, 0.5)'
                    },
                    {
                        label: 'Retained Users',
                        data: data.map(item => item.retained_users),
                        backgroundColor: 'rgba(40, 167, 69, 0.5)'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    createTrafficSourcesChart(data) {
        const ctx = document.getElementById('traffic-sources-chart').getContext('2d');
        
        if (this.charts.trafficSources) {
            this.charts.trafficSources.destroy();
        }

        this.charts.trafficSources = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.map(item => item.source),
                datasets: [{
                    data: data.map(item => item.visits),
                    backgroundColor: [
                        '#007bff',
                        '#28a745',
                        '#ffc107',
                        '#dc3545',
                        '#6c757d',
                        '#17a2b8'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    // Display methods
    displayTopUsers(users) {
        const container = document.getElementById('top-users-list');
        
        if (!users || users.length === 0) {
            container.innerHTML = '<p>No user data available</p>';
            return;
        }

        const html = users.map(user => `
            <div class="user-item">
                <div class="user-info">
                    <strong>${user.username}</strong>
                    <div class="user-stats">
                        Posts: ${user.posts_count} | Comments: ${user.comments_count} | Likes: ${user.total_likes}
                    </div>
                </div>
            </div>
        `).join('');

        container.innerHTML = html;
    }

    displayPopularContent(content) {
        const container = document.getElementById('popular-content-list');
        
        if (!content || content.length === 0) {
            container.innerHTML = '<p>No content data available</p>';
            return;
        }

        const html = content.map(post => `
            <div class="content-item">
                <div class="content-title">${post.title}</div>
                <div class="content-stats">
                    Views: ${post.views_count} | Likes: ${post.likes_count} | Comments: ${post.comments_count}
                </div>
                <div class="content-author">by ${post.username}</div>
            </div>
        `).join('');

        container.innerHTML = html;
    }

    displaySessionDuration(data) {
        const container = document.getElementById('session-duration');
        
        if (!data) {
            container.innerHTML = '<p>No session data available</p>';
            return;
        }

        const html = `
            <div class="duration-stats">
                <div class="stat-item">
                    <label>Average Duration:</label>
                    <span>${this.formatDuration(data.avg_duration)}</span>
                </div>
                <div class="stat-item">
                    <label>Min Duration:</label>
                    <span>${this.formatDuration(data.min_duration)}</span>
                </div>
                <div class="stat-item">
                    <label>Max Duration:</label>
                    <span>${this.formatDuration(data.max_duration)}</span>
                </div>
                <div class="stat-item">
                    <label>Total Sessions:</label>
                    <span>${this.formatNumber(data.total_sessions)}</span>
                </div>
            </div>
        `;

        container.innerHTML = html;
    }

    displayPageLoadTimes(data) {
        const container = document.getElementById('page-load-times');
        
        if (!data) {
            container.innerHTML = '<p>No performance data available</p>';
            return;
        }

        const html = Object.entries(data).map(([page, times]) => `
            <div class="performance-item">
                <div class="page-name">${page}</div>
                <div class="performance-metrics">
                    <span>Avg: ${times.avg}s</span>
                    <span>P95: ${times.p95}s</span>
                    <span>P99: ${times.p99}s</span>
                </div>
            </div>
        `).join('');

        container.innerHTML = html;
    }

    // Export functionality
    async exportData() {
        const type = document.getElementById('export-type').value;
        const format = document.getElementById('export-format').value;

        try {
            const params = new URLSearchParams({
                ...this.filters,
                format: format
            });

            const response = await fetch(`/admin/analytics/export/${type}?${params}`);
            const data = await response.text();

            // Create download link
            const blob = new Blob([data], { 
                type: format === 'csv' ? 'text/csv' : 'application/json' 
            });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `${type}-analytics-${new Date().toISOString().split('T')[0]}.${format}`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);

        } catch (error) {
            console.error('Error exporting data:', error);
            this.showError('Failed to export data');
        }
    }

    // Utility methods
    formatNumber(num) {
        if (num === null || num === undefined) return 'N/A';
        return new Intl.NumberFormat().format(num);
    }

    formatCurrency(amount) {
        if (amount === null || amount === undefined) return '$0.00';
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD'
        }).format(amount);
    }

    formatDuration(seconds) {
        if (!seconds) return '0s';
        
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const secs = Math.floor(seconds % 60);

        if (hours > 0) {
            return `${hours}h ${minutes}m ${secs}s`;
        } else if (minutes > 0) {
            return `${minutes}m ${secs}s`;
        } else {
            return `${secs}s`;
        }
    }

    showLoading() {
        document.querySelectorAll('.metric, .analytics-card div[id]').forEach(element => {
            if (!element.classList.contains('metric')) {
                element.innerHTML = '<div class="loading">Loading...</div>';
            } else {
                element.textContent = 'Loading...';
            }
        });
    }

    showError(message) {
        console.error(message);
        // Could implement a toast notification system here
    }
}

// Initialize the dashboard when the page loads
document.addEventListener('DOMContentLoaded', () => {
    new AdvancedAnalyticsDashboard();
});