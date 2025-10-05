<?php
$content = ob_get_clean();
ob_start();
?>

<div class="row">
    <div class="col-lg-4">
        <!-- User Profile Card -->
        <div class="card mb-4">
            <div class="card-body text-center">
                <img src="<?= asset('images/default-avatar.png') ?>" alt="Avatar" class="user-avatar mb-3" style="width: 100px; height: 100px;">
                <h4 class="mb-1"><?= e($user['display_name'] ?: $user['username']) ?></h4>
                <p class="text-muted">@<?= e($user['username']) ?></p>
                
                <div class="mb-3">
                    <span class="badge bg-<?= $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'moderator' ? 'warning' : 'primary') ?>">
                        <?= ucfirst($user['role']) ?>
                    </span>
                </div>
                
                <div class="text-muted small">
                    <div>Joined <?= time_ago($user['created_at']) ?></div>
                    <?php if ($user['last_login']): ?>
                        <div>Last active <?= time_ago($user['last_login']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- User Stats -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Statistics</h6>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6 mb-3">
                        <div class="h4 text-primary"><?= $stats['post_count'] ?? 0 ?></div>
                        <small class="text-muted">Posts</small>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="h4 text-success"><?= $stats['thread_count'] ?? 0 ?></div>
                        <small class="text-muted">Threads</small>
                    </div>
                    <div class="col-6">
                        <div class="h4 text-info"><?= $stats['reaction_count'] ?? 0 ?></div>
                        <small class="text-muted">Reactions</small>
                    </div>
                    <div class="col-6">
                        <div class="h4 text-warning"><?= $user['view_count'] ?? 0 ?></div>
                        <small class="text-muted">Profile Views</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-8">
        <!-- User Activity -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Recent Activity</h6>
            </div>
            <div class="card-body">
                <div class="activity-timeline">
                    <div class="timeline-item">
                        <div class="timeline-marker bg-primary"></div>
                        <div class="timeline-content">
                            <h6 class="mb-1">Joined the forum</h6>
                            <small class="text-muted"><?= time_ago($user['created_at']) ?></small>
                        </div>
                    </div>
                    
                    <?php if ($user['last_login']): ?>
                        <div class="timeline-item">
                            <div class="timeline-marker bg-success"></div>
                            <div class="timeline-content">
                                <h6 class="mb-1">Last login</h6>
                                <small class="text-muted"><?= time_ago($user['last_login']) ?></small>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Recent Posts -->
        <div class="card mt-4">
            <div class="card-header">
                <h6 class="mb-0">Recent Posts</h6>
            </div>
            <div class="card-body">
                <p class="text-muted">Recent posts by this user will appear here.</p>
            </div>
        </div>
    </div>
</div>

<style>
.timeline-item {
    position: relative;
    padding-left: 2rem;
    margin-bottom: 1rem;
}

.timeline-marker {
    position: absolute;
    left: 0;
    top: 0.5rem;
    width: 12px;
    height: 12px;
    border-radius: 50%;
}

.timeline-content h6 {
    margin-bottom: 0.25rem;
}

.user-avatar {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid #f8f9fa;
}
</style>

<?php
$content = ob_get_clean();
include 'layouts/app.php';
?>