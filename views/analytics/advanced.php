<?php $this->extend('layouts/analytics') ?>

<?php $this->section('title', 'Advanced Analytics Dashboard') ?>

<?php $this->section('content') ?>
<div class="analytics-dashboard">
    <div class="dashboard-header">
        <h1>Advanced Analytics Dashboard</h1>
        <div class="date-filters">
            <input type="date" id="start-date" value="<?= date('Y-m-01') ?>">
            <input type="date" id="end-date" value="<?= date('Y-m-d') ?>">
            <button id="apply-filters" class="btn btn-primary">Apply Filters</button>
        </div>
    </div>

    <div class="analytics-tabs">
        <ul class="nav nav-tabs" id="analytics-tabs">
            <li class="nav-item">
                <a class="nav-link active" data-tab="user-analytics" href="#user-analytics">User Analytics</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-tab="content-analytics" href="#content-analytics">Content Analytics</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-tab="traffic-analytics" href="#traffic-analytics">Traffic Analytics</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-tab="engagement-analytics" href="#engagement-analytics">Engagement</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-tab="revenue-analytics" href="#revenue-analytics">Revenue</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-tab="performance-analytics" href="#performance-analytics">Performance</a>
            </li>
        </ul>
    </div>

    <div class="tab-content">
        <!-- User Analytics Tab -->
        <div id="user-analytics" class="tab-pane active">
            <div class="analytics-grid">
                <div class="analytics-card">
                    <h3>Total Users</h3>
                    <div class="metric" id="total-users">Loading...</div>
                </div>
                <div class="analytics-card">
                    <h3>New Users</h3>
                    <div class="metric" id="new-users">Loading...</div>
                </div>
                <div class="analytics-card">
                    <h3>Active Users</h3>
                    <div class="metric" id="active-users">Loading...</div>
                </div>
                <div class="analytics-card">
                    <h3>User Growth</h3>
                    <canvas id="user-growth-chart"></canvas>
                </div>
                <div class="analytics-card">
                    <h3>User Retention</h3>
                    <canvas id="user-retention-chart"></canvas>
                </div>
                <div class="analytics-card">
                    <h3>Top Users</h3>
                    <div id="top-users-list">Loading...</div>
                </div>
            </div>
        </div>

        <!-- Content Analytics Tab -->
        <div id="content-analytics" class="tab-pane">
            <div class="analytics-grid">
                <div class="analytics-card">
                    <h3>Total Posts</h3>
                    <div class="metric" id="total-posts">Loading...</div>
                </div>
                <div class="analytics-card">
                    <h3>New Posts</h3>
                    <div class="metric" id="new-posts">Loading...</div>
                </div>
                <div class="analytics-card">
                    <h3>Popular Content</h3>
                    <div id="popular-content-list">Loading...</div>
                </div>
                <div class="analytics-card">
                    <h3>Content by Category</h3>
                    <canvas id="content-category-chart"></canvas>
                </div>
                <div class="analytics-card">
                    <h3>Content Engagement</h3>
                    <canvas id="content-engagement-chart"></canvas>
                </div>
                <div class="analytics-card">
                    <h3>Content Quality Metrics</h3>
                    <div id="content-quality">Loading...</div>
                </div>
            </div>
        </div>

        <!-- Traffic Analytics Tab -->
        <div id="traffic-analytics" class="tab-pane">
            <div class="analytics-grid">
                <div class="analytics-card">
                    <h3>Page Views</h3>
                    <div class="metric" id="page-views">Loading...</div>
                </div>
                <div class="analytics-card">
                    <h3>Unique Visitors</h3>
                    <div class="metric" id="unique-visitors">Loading...</div>
                </div>
                <div class="analytics-card">
                    <h3>Traffic Sources</h3>
                    <canvas id="traffic-sources-chart"></canvas>
                </div>
                <div class="analytics-card">
                    <h3>Device Types</h3>
                    <canvas id="device-types-chart"></canvas>
                </div>
                <div class="analytics-card">
                    <h3>Browser Statistics</h3>
                    <canvas id="browser-stats-chart"></canvas>
                </div>
                <div class="analytics-card">
                    <h3>Traffic Patterns</h3>
                    <canvas id="traffic-patterns-chart"></canvas>
                </div>
            </div>
        </div>

        <!-- Engagement Analytics Tab -->
        <div id="engagement-analytics" class="tab-pane">
            <div class="analytics-grid">
                <div class="analytics-card">
                    <h3>Session Duration</h3>
                    <div id="session-duration">Loading...</div>
                </div>
                <div class="analytics-card">
                    <h3>Bounce Rate</h3>
                    <div class="metric" id="bounce-rate">Loading...</div>
                </div>
                <div class="analytics-card">
                    <h3>Interaction Rate</h3>
                    <canvas id="interaction-rate-chart"></canvas>
                </div>
                <div class="analytics-card">
                    <h3>Engagement Score</h3>
                    <canvas id="engagement-score-chart"></canvas>
                </div>
                <div class="analytics-card">
                    <h3>User Behavior</h3>
                    <canvas id="user-behavior-chart"></canvas>
                </div>
                <div class="analytics-card">
                    <h3>Conversion Funnel</h3>
                    <canvas id="conversion-funnel-chart"></canvas>
                </div>
            </div>
        </div>

        <!-- Revenue Analytics Tab -->
        <div id="revenue-analytics" class="tab-pane">
            <div class="analytics-grid">
                <div class="analytics-card">
                    <h3>Total Revenue</h3>
                    <div class="metric" id="total-revenue">Loading...</div>
                </div>
                <div class="analytics-card">
                    <h3>Revenue by Source</h3>
                    <canvas id="revenue-source-chart"></canvas>
                </div>
                <div class="analytics-card">
                    <h3>Subscription Analytics</h3>
                    <div id="subscription-analytics">Loading...</div>
                </div>
                <div class="analytics-card">
                    <h3>Payment Analytics</h3>
                    <canvas id="payment-analytics-chart"></canvas>
                </div>
                <div class="analytics-card">
                    <h3>Revenue Trends</h3>
                    <canvas id="revenue-trends-chart"></canvas>
                </div>
                <div class="analytics-card">
                    <h3>Customer Lifetime Value</h3>
                    <div id="customer-clv">Loading...</div>
                </div>
            </div>
        </div>

        <!-- Performance Analytics Tab -->
        <div id="performance-analytics" class="tab-pane">
            <div class="analytics-grid">
                <div class="analytics-card">
                    <h3>Page Load Times</h3>
                    <div id="page-load-times">Loading...</div>
                </div>
                <div class="analytics-card">
                    <h3>Database Performance</h3>
                    <div id="db-performance">Loading...</div>
                </div>
                <div class="analytics-card">
                    <h3>Server Resources</h3>
                    <div id="server-resources">Loading...</div>
                </div>
                <div class="analytics-card">
                    <h3>Error Rates</h3>
                    <canvas id="error-rates-chart"></canvas>
                </div>
                <div class="analytics-card">
                    <h3>Uptime Statistics</h3>
                    <div id="uptime-stats">Loading...</div>
                </div>
                <div class="analytics-card">
                    <h3>Performance Trends</h3>
                    <canvas id="performance-trends-chart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="export-section">
        <h3>Export Analytics</h3>
        <div class="export-controls">
            <select id="export-type">
                <option value="user">User Analytics</option>
                <option value="content">Content Analytics</option>
                <option value="traffic">Traffic Analytics</option>
                <option value="engagement">Engagement Analytics</option>
                <option value="revenue">Revenue Analytics</option>
                <option value="performance">Performance Analytics</option>
            </select>
            <select id="export-format">
                <option value="json">JSON</option>
                <option value="csv">CSV</option>
            </select>
            <button id="export-btn" class="btn btn-secondary">Export Data</button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="/assets/js/advanced-analytics.js"></script>
<?php $this->endSection() ?>