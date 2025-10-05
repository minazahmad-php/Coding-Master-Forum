<?php
$content = ob_get_clean();
ob_start();
?>

<div class="row">
    <div class="col-12">
        <h1 class="h3 mb-4">Notifications</h1>
        
        <?php if (!empty($notifications)): ?>
            <div class="card">
                <div class="card-body p-0">
                    <?php foreach ($notifications as $notification): ?>
                        <div class="border-bottom p-3 <?= !$notification['is_read'] ? 'bg-light' : '' ?>">
                            <div class="d-flex align-items-start">
                                <div class="me-3">
                                    <i class="fas fa-<?= $notification['type'] === 'like' ? 'thumbs-up' : ($notification['type'] === 'reply' ? 'reply' : 'bell') ?> text-primary"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1"><?= e($notification['title']) ?></h6>
                                    <p class="mb-1"><?= e($notification['message']) ?></p>
                                    <small class="text-muted"><?= time_ago($notification['created_at']) ?></small>
                                </div>
                                <?php if (!$notification['is_read']): ?>
                                    <div class="ms-3">
                                        <span class="badge bg-primary">New</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-bell fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">No Notifications</h4>
                    <p class="text-muted">You don't have any notifications yet.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
include 'layouts/app.php';
?>