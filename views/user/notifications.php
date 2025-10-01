<?php include '../header.php'; ?>

//views/user/notifications.php

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Notifications</h2>
    <div>
        <a href="/user/notifications/mark-all-read" class="btn btn-outline-primary me-2">
            <i class="fas fa-check-double"></i> Mark All as Read
        </a>
        <a href="/user/notifications/clear" class="btn btn-outline-danger" onclick="return confirm('Are you sure you want to clear all notifications?')">
            <i class="fas fa-trash"></i> Clear All
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Your Notifications</h5>
    </div>
    <div class="card-body p-0">
        <?php if (!empty($notifications)): ?>
        <div class="list-group list-group-flush">
            <?php foreach ($notifications as $notification): ?>
            <a href="<?php echo $notification['link'] ?: '#'; ?>" class="list-group-item list-group-item-action <?php echo $notification['is_read'] ? '' : 'bg-light'; ?>">
                <div class="d-flex w-100 justify-content-between">
                    <h6 class="mb-1"><?php echo $notification['message']; ?></h6>
                    <small class="text-muted"><?php echo format_date($notification['created_at']); ?></small>
                </div>
                <?php if (!$notification['is_read']): ?>
                <span class="badge bg-primary">New</span>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="text-center py-5">
            <i class="fas fa-bell fa-3x text-muted mb-3"></i>
            <h5>No notifications</h5>
            <p class="text-muted">You don't have any notifications yet.</p>
        </div>
        <?php endif; ?>
    </div>
    <?php if (!empty($notifications) && $pagination['pages'] > 1): ?>
    <div class="card-footer">
        <nav>
            <ul class="pagination justify-content-center mb-0">
                <?php if ($pagination['previous']): ?>
                <li class="page-item">
                    <a class="page-link" href="<?php echo str_replace('{page}', $pagination['previous'], $pagination['urlPattern']); ?>">Previous</a>
                </li>
                <?php endif; ?>
                
                <?php foreach ($pagination['items'] as $item): ?>
                <li class="page-item <?php echo $item['active'] ? 'active' : ''; ?>">
                    <a class="page-link" href="<?php echo $item['url']; ?>"><?php echo $item['page']; ?></a>
                </li>
                <?php endforeach; ?>
                
                <?php if ($pagination['next']): ?>
                <li class="page-item">
                    <a class="page-link" href="<?php echo str_replace('{page}', $pagination['next'], $pagination['urlPattern']); ?>">Next</a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<?php include '../footer.php'; ?>