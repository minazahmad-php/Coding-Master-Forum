<?php include '../header.php'; ?>

//views/admin/dashboard.php

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Admin Dashboard</h2>
    <div class="btn-group">
        <a href="/admin/users" class="btn btn-outline-primary">Manage Users</a>
        <a href="/admin/forums" class="btn btn-outline-primary">Manage Forums</a>
        <a href="/admin/settings" class="btn btn-outline-primary">Settings</a>
    </div>
</div>

<div class="row">
    <div class="col-md-3">
        <div class="card text-center bg-primary text-white">
            <div class="card-body">
                <h5 class="card-title">Users</h5>
                <h2 class="card-text"><?php echo $stats['users_count']; ?></h2>
                <a href="/admin/users" class="btn btn-light btn-sm">View All</a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center bg-success text-white">
            <div class="card-body">
                <h5 class="card-title">Forums</h5>
                <h2 class="card-text"><?php echo $stats['forums_count']; ?></h2>
                <a href="/admin/forums" class="btn btn-light btn-sm">View All</a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center bg-info text-white">
            <div class="card-body">
                <h5 class="card-title">Threads</h5>
                <h2 class="card-text"><?php echo $stats['threads_count']; ?></h2>
                <a href="/admin/threads" class="btn btn-light btn-sm">View All</a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center bg-warning text-dark">
            <div class="card-body">
                <h5 class="card-title">Posts</h5>
                <h2 class="card-text"><?php echo $stats['posts_count']; ?></h2>
                <a href="/admin/posts" class="btn btn-dark btn-sm">View All</a>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Latest Users</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($stats['latest_users'])): ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($stats['latest_users'] as $user): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <img src="<?php echo get_gravatar($user['email'], 30); ?>" class="rounded-circle me-2" alt="Avatar">
                            <div>
                                <h6 class="mb-0"><?php echo $user['username']; ?></h6>
                                <small class="text-muted"><?php echo format_date($user['created_at']); ?></small>
                            </div>
                        </div>
                        <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'moderator' ? 'warning' : 'secondary'); ?>">
                            <?php echo ucfirst($user['role']); ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="text-muted">No users yet.</p>
                <?php endif; ?>
            </div>
            <div class="card-footer">
                <a href="/admin/users" class="btn btn-sm btn-outline-primary">View All Users</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Latest Threads</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($stats['latest_threads'])): ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($stats['latest_threads'] as $thread): ?>
                    <div class="list-group-item">
                        <h6 class="mb-1">
                            <a href="/thread/<?php echo $thread['id']; ?>"><?php echo $thread['title']; ?></a>
                        </h6>
                        <small class="text-muted">
                            By <?php echo $thread['username']; ?> • <?php echo format_date($thread['created_at']); ?>
                        </small>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="text-muted">No threads yet.</p>
                <?php endif; ?>
            </div>
            <div class="card-footer">
                <a href="/admin/threads" class="btn btn-sm btn-outline-primary">View All Threads</a>
            </div>
        </div>
    </div>
</div>

<?php include '../footer.php'; ?>