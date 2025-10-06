<?php $this->layout('layouts.admin', ['title' => 'Analytics Dashboard']) ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1 class="h3 mb-4">Analytics Dashboard</h1>
        </div>
    </div>

    <!-- Overview Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Users</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="total-users">-</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Threads</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="total-threads">-</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-comments fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Posts</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="total-posts">-</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-comment-dots fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Active Users</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="active-users">-</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row">
        <!-- User Growth Chart -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">User Growth</h6>
                    <div class="dropdown no-arrow">
                        <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-bs-toggle="dropdown">
                            <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right shadow">
                            <a class="dropdown-item" href="#" onclick="updateChart('7d')">Last 7 days</a>
                            <a class="dropdown-item" href="#" onclick="updateChart('30d')">Last 30 days</a>
                            <a class="dropdown-item" href="#" onclick="updateChart('90d')">Last 90 days</a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="userGrowthChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Activity Distribution -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Activity Distribution</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4 pb-2">
                        <canvas id="activityChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Analytics -->
    <div class="row">
        <!-- Top Forums -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Top Forums</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="topForumsTable">
                            <thead>
                                <tr>
                                    <th>Forum</th>
                                    <th>Threads</th>
                                    <th>Posts</th>
                                    <th>Views</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data will be loaded via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Activity</h6>
                </div>
                <div class="card-body">
                    <div id="recentActivity">
                        <!-- Data will be loaded via AJAX -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Real-time Stats -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Real-time Statistics</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 text-center">
                            <div class="h4 text-primary" id="online-users">-</div>
                            <div class="text-muted">Online Users</div>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="h4 text-success" id="active-sessions">-</div>
                            <div class="text-muted">Active Sessions</div>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="h4 text-info" id="memory-usage">-</div>
                            <div class="text-muted">Memory Usage</div>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="h4 text-warning" id="response-time">-</div>
                            <div class="text-muted">Response Time (ms)</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    loadAnalytics();
    loadRealTimeStats();
    
    // Update real-time stats every 30 seconds
    setInterval(loadRealTimeStats, 30000);
});

function loadAnalytics() {
    fetch('/api/admin/analytics')
        .then(response => response.json())
        .then(data => {
            updateOverviewCards(data.overview);
            updateUserGrowthChart(data.growth);
            updateActivityChart(data.engagement);
            updateTopForums(data.top_content.top_forums);
            updateRecentActivity(data.user_activity);
        })
        .catch(error => console.error('Error loading analytics:', error));
}

function loadRealTimeStats() {
    fetch('/api/admin/realtime')
        .then(response => response.json())
        .then(data => {
            document.getElementById('online-users').textContent = data.online_users || 0;
            document.getElementById('active-sessions').textContent = data.active_sessions || 0;
            document.getElementById('memory-usage').textContent = formatBytes(data.system_status?.memory_usage?.current || 0);
            document.getElementById('response-time').textContent = Math.round(data.system_status?.response_time || 0);
        })
        .catch(error => console.error('Error loading real-time stats:', error));
}

function updateOverviewCards(overview) {
    document.getElementById('total-users').textContent = overview.total_users || 0;
    document.getElementById('total-threads').textContent = overview.total_threads || 0;
    document.getElementById('total-posts').textContent = overview.total_posts || 0;
    document.getElementById('active-users').textContent = overview.active_users || 0;
}

function updateUserGrowthChart(growthData) {
    // Chart.js implementation would go here
    console.log('User growth data:', growthData);
}

function updateActivityChart(activityData) {
    // Chart.js implementation would go here
    console.log('Activity data:', activityData);
}

function updateTopForums(forums) {
    const tbody = document.querySelector('#topForumsTable tbody');
    tbody.innerHTML = '';
    
    forums.forEach(forum => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${forum.name}</td>
            <td>${forum.thread_count || 0}</td>
            <td>${forum.post_count || 0}</td>
            <td>${forum.view_count || 0}</td>
        `;
        tbody.appendChild(row);
    });
}

function updateRecentActivity(activity) {
    const container = document.getElementById('recentActivity');
    container.innerHTML = '';
    
    activity.forEach(item => {
        const div = document.createElement('div');
        div.className = 'd-flex align-items-center mb-3';
        div.innerHTML = `
            <div class="flex-shrink-0">
                <i class="fas fa-circle text-primary"></i>
            </div>
            <div class="flex-grow-1 ms-3">
                <div class="text-sm">${item.description}</div>
                <div class="text-xs text-muted">${item.time}</div>
            </div>
        `;
        container.appendChild(div);
    });
}

function updateChart(period) {
    // Update chart based on selected period
    console.log('Updating chart for period:', period);
}

function formatBytes(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}
</script>