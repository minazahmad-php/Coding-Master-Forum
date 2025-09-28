<?php
$pageTitle = 'Analytics Dashboard';
$pageDescription = 'Comprehensive overview of your forum analytics';
$currentPage = 'dashboard';
?>

<div class="row">
    <!-- Key Metrics Cards -->
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="card-title"><?= formatNumber($user_analytics['total_activities'] ?? 0) ?></h4>
                        <p class="card-text">Total Activities</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-users fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="card-title"><?= formatNumber($content_analytics['total_content'] ?? 0) ?></h4>
                        <p class="card-text">Total Content</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-file-alt fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="card-title"><?= formatNumber($traffic_analytics['total_page_views'] ?? 0) ?></h4>
                        <p class="card-text">Page Views</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-eye fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="card-title"><?= formatNumber($engagement_analytics['total_events'] ?? 0) ?></h4>
                        <p class="card-text">Engagement Events</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-heart fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Traffic Overview Chart -->
    <div class="col-lg-8 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-line me-2"></i>
                    Traffic Overview (<?= $days ?> Days)
                </h5>
            </div>
            <div class="card-body">
                <canvas id="trafficChart" height="100"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Engagement Metrics -->
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-heart me-2"></i>
                    Engagement Metrics
                </h5>
            </div>
            <div class="card-body">
                <canvas id="engagementChart"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Content Performance -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-file-alt me-2"></i>
                    Content Performance
                </h5>
            </div>
            <div class="card-body">
                <canvas id="contentChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- User Activity -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-users me-2"></i>
                    User Activity
                </h5>
            </div>
            <div class="card-body">
                <canvas id="userActivityChart"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Performance Metrics -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-tachometer-alt me-2"></i>
                    Performance Metrics
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-6">
                        <div class="metric-item">
                            <h6>Page Load Time</h6>
                            <h4 class="text-primary"><?= $performance_analytics['avg_page_load_time'] ?? 'N/A' ?>ms</h4>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="metric-item">
                            <h6>Server Response</h6>
                            <h4 class="text-success"><?= $performance_analytics['avg_server_response'] ?? 'N/A' ?>ms</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Revenue Overview -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-dollar-sign me-2"></i>
                    Revenue Overview
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-6">
                        <div class="metric-item">
                            <h6>Total Revenue</h6>
                            <h4 class="text-success"><?= formatCurrency($revenue_analytics['total_revenue'] ?? 0) ?></h4>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="metric-item">
                            <h6>Avg Transaction</h6>
                            <h4 class="text-info"><?= formatCurrency($revenue_analytics['avg_transaction_value'] ?? 0) ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Recent Activity -->
    <div class="col-lg-8 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-clock me-2"></i>
                    Recent Activity
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recent_activities)): ?>
                                <?php foreach ($recent_activities as $activity): ?>
                                    <tr>
                                        <td><?= formatTime($activity['created_at']) ?></td>
                                        <td><?= htmlspecialchars($activity['username']) ?></td>
                                        <td>
                                            <span class="badge bg-primary"><?= htmlspecialchars($activity['action']) ?></span>
                                        </td>
                                        <td><?= htmlspecialchars($activity['details']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted">No recent activity</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Stats -->
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-bar me-2"></i>
                    Quick Stats
                </h5>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span>Active Users</span>
                        <span class="badge bg-primary rounded-pill"><?= formatNumber($traffic_analytics['unique_users'] ?? 0) ?></span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span>New Posts</span>
                        <span class="badge bg-success rounded-pill"><?= formatNumber($content_analytics['new_posts'] ?? 0) ?></span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span>Comments</span>
                        <span class="badge bg-info rounded-pill"><?= formatNumber($content_analytics['new_comments'] ?? 0) ?></span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span>Likes</span>
                        <span class="badge bg-warning rounded-pill"><?= formatNumber($engagement_analytics['total_likes'] ?? 0) ?></span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span>Shares</span>
                        <span class="badge bg-danger rounded-pill"><?= formatNumber($engagement_analytics['total_shares'] ?? 0) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Traffic Chart
    createChart('trafficChart', {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($traffic_analytics['trends'] ?? [], 'date')) ?>,
            datasets: [{
                label: 'Page Views',
                data: <?= json_encode(array_column($traffic_analytics['trends'] ?? [], 'page_views')) ?>,
                borderColor: '#007bff',
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                tension: 0.4
            }, {
                label: 'Unique Visitors',
                data: <?= json_encode(array_column($traffic_analytics['trends'] ?? [], 'unique_visitors')) ?>,
                borderColor: '#28a745',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
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
    
    // Engagement Chart
    createChart('engagementChart', {
        type: 'doughnut',
        data: {
            labels: ['Likes', 'Comments', 'Shares', 'Bookmarks'],
            datasets: [{
                data: [
                    <?= $engagement_analytics['total_likes'] ?? 0 ?>,
                    <?= $engagement_analytics['total_comments'] ?? 0 ?>,
                    <?= $engagement_analytics['total_shares'] ?? 0 ?>,
                    <?= $engagement_analytics['total_bookmarks'] ?? 0 ?>
                ],
                backgroundColor: [
                    '#dc3545',
                    '#007bff',
                    '#28a745',
                    '#ffc107'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
    
    // Content Chart
    createChart('contentChart', {
        type: 'bar',
        data: {
            labels: ['Posts', 'Comments', 'Replies'],
            datasets: [{
                label: 'Content Count',
                data: [
                    <?= $content_analytics['posts_count'] ?? 0 ?>,
                    <?= $content_analytics['comments_count'] ?? 0 ?>,
                    <?= $content_analytics['replies_count'] ?? 0 ?>
                ],
                backgroundColor: [
                    '#007bff',
                    '#28a745',
                    '#ffc107'
                ]
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
    
    // User Activity Chart
    createChart('userActivityChart', {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($user_analytics['activity_trends'] ?? [], 'date')) ?>,
            datasets: [{
                label: 'Active Users',
                data: <?= json_encode(array_column($user_analytics['activity_trends'] ?? [], 'active_users')) ?>,
                borderColor: '#6f42c1',
                backgroundColor: 'rgba(111, 66, 193, 0.1)',
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
});
</script>

<?php
function formatNumber($num) {
    if ($num >= 1000000) {
        return (num / 1000000).toFixed(1) + 'M';
    } else if ($num >= 1000) {
        return (num / 1000).toFixed(1) + 'K';
    }
    return $num;
}

function formatCurrency($num, $currency = 'USD') {
    return '$' . number_format($num, 2);
}

function formatTime($date) {
    return date('H:i', strtotime($date));
}
?>