<?php
$content = ob_get_clean();
ob_start();
?>

<div class="row">
    <div class="col-12">
        <h1 class="h3 mb-4">Online Users</h1>
        
        <?php if (!empty($online_users)): ?>
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Currently Online (<?= count($online_users) ?>)</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($online_users as $user): ?>
                            <div class="col-md-6 col-lg-4 mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="position-relative me-3">
                                        <img src="<?= asset('images/default-avatar.png') ?>" alt="Avatar" class="user-avatar">
                                        <div class="online-indicator"></div>
                                    </div>
                                    <div>
                                        <h6 class="mb-1">
                                            <a href="<?= url('/profile/' . $user['id']) ?>" class="text-decoration-none">
                                                <?= e($user['display_name'] ?: $user['username']) ?>
                                            </a>
                                        </h6>
                                        <small class="text-muted">@<?= e($user['username']) ?></small>
                                        <div class="mt-1">
                                            <span class="badge bg-<?= $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'moderator' ? 'warning' : 'primary') ?>">
                                                <?= ucfirst($user['role']) ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">No Users Online</h4>
                    <p class="text-muted">There are no users currently online.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.online-indicator {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 12px;
    height: 12px;
    background: #28a745;
    border: 2px solid white;
    border-radius: 50%;
}

.user-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #f8f9fa;
}
</style>

<?php
$content = ob_get_clean();
include 'layouts/app.php';
?>