<?php
$content = ob_get_clean();
ob_start();
?>

<div class="row">
    <div class="col-12">
        <h1 class="h3 mb-4">Admin Dashboard</h1>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Users</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['total_users'] ?></div>
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
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['total_threads'] ?></div>
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
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['total_posts'] ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-comment fa-2x text-gray-300"></i>
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
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Online Users</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['online_users'] ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-user-check fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="row">
    <div class="col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Recent Users</h6>
            </div>
            <div class="card-body">
                <?php if (!empty($stats['recent_users'])): ?>
                    <?php foreach ($stats['recent_users'] as $user): ?>
                        <div class="d-flex align-items-center mb-3">
                            <img src="<?= asset('images/default-avatar.png') ?>" alt="Avatar" class="user-avatar me-3">
                            <div>
                                <div class="font-weight-bold"><?= e($user['display_name'] ?: $user['username']) ?></div>
                                <div class="text-muted small"><?= time_ago($user['created_at']) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted">No recent users</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Recent Threads</h6>
            </div>
            <div class="card-body">
                <?php if (!empty($stats['recent_threads'])): ?>
                    <?php foreach ($stats['recent_threads'] as $thread): ?>
                        <div class="mb-3">
                            <div class="font-weight-bold">
                                <a href="<?= url('/thread/' . $thread['id']) ?>" class="text-decoration-none">
                                    <?= e($thread['title']) ?>
                                </a>
                            </div>
                            <div class="text-muted small">
                                by <?= e($thread['username']) ?> • <?= time_ago($thread['created_at']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted">No recent threads</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row">
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <a href="<?= url('/admin/users') ?>" class="btn btn-primary btn-block">
                            <i class="fas fa-users me-2"></i>Manage Users
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="<?= url('/admin/forums') ?>" class="btn btn-success btn-block">
                            <i class="fas fa-comments me-2"></i>Manage Forums
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="<?= url('/admin/settings') ?>" class="btn btn-info btn-block">
                            <i class="fas fa-cog me-2"></i>Site Settings
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="<?= url('/admin/reports') ?>" class="btn btn-warning btn-block">
                            <i class="fas fa-flag me-2"></i>View Reports
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include 'layouts/admin.php';
?>