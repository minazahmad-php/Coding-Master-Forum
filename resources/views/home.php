<?php
$content = ob_get_clean();
ob_start();
?>

<div class="row">
    <div class="col-lg-8">
        <!-- Welcome Message -->
        <div class="jumbotron bg-primary text-white p-4 rounded mb-4">
            <h1 class="display-4">Welcome to <?= config('app.name', 'My Forum') ?></h1>
            <p class="lead">Join our community and start discussions on various topics.</p>
            <?php if (!$user): ?>
                <a class="btn btn-light btn-lg" href="<?= url('/register') ?>" role="button">Get Started</a>
            <?php endif; ?>
        </div>

        <!-- Forums -->
        <?php if (!empty($forums)): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-comments me-2"></i>Forums</h5>
                </div>
                <div class="card-body p-0">
                    <?php foreach ($forums as $forum): ?>
                        <div class="forum-card border-bottom p-3">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h6 class="mb-1">
                                        <a href="<?= url('/forum/' . $forum['id']) ?>" class="text-decoration-none">
                                            <?= e($forum['name']) ?>
                                        </a>
                                    </h6>
                                    <p class="text-muted mb-0"><?= e($forum['description']) ?></p>
                                </div>
                                <div class="col-md-4 text-md-end">
                                    <small class="text-muted">
                                        <?= $forum['thread_count'] ?> threads<br>
                                        <?= $forum['post_count'] ?> posts
                                    </small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Recent Threads -->
        <?php if (!empty($recent_threads)): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Recent Threads</h5>
                </div>
                <div class="card-body p-0">
                    <?php foreach ($recent_threads as $thread): ?>
                        <div class="border-bottom p-3">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h6 class="mb-1">
                                        <a href="<?= url('/thread/' . $thread['id']) ?>" class="text-decoration-none">
                                            <?= e($thread['title']) ?>
                                        </a>
                                    </h6>
                                    <small class="text-muted">
                                        by <a href="<?= url('/profile/' . $thread['user_id']) ?>"><?= e($thread['username']) ?></a>
                                        in <a href="<?= url('/forum/' . $thread['forum_id']) ?>"><?= e($thread['forum_name']) ?></a>
                                    </small>
                                </div>
                                <div class="col-md-4 text-md-end">
                                    <small class="text-muted">
                                        <?= time_ago($thread['created_at']) ?><br>
                                        <?= $thread['post_count'] ?> replies
                                    </small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="card-footer">
                    <a href="<?= url('/forums') ?>" class="btn btn-outline-primary btn-sm">View All Forums</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="col-lg-4">
        <!-- Statistics -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Statistics</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6 mb-3">
                        <div class="h4 text-primary"><?= $stats['total_users'] ?></div>
                        <small class="text-muted">Members</small>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="h4 text-success"><?= $stats['total_threads'] ?></div>
                        <small class="text-muted">Threads</small>
                    </div>
                    <div class="col-6">
                        <div class="h4 text-info"><?= $stats['total_posts'] ?></div>
                        <small class="text-muted">Posts</small>
                    </div>
                    <div class="col-6">
                        <div class="h4 text-warning"><?= $stats['online_users'] ?></div>
                        <small class="text-muted">Online</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Online Users -->
        <?php if (!empty($online_users)): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-users me-2"></i>Online Users</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach (array_slice($online_users, 0, 12) as $user): ?>
                            <div class="col-6 mb-2">
                                <div class="d-flex align-items-center">
                                    <img src="<?= asset('images/default-avatar.png') ?>" alt="Avatar" class="user-avatar me-2">
                                    <div>
                                        <a href="<?= url('/profile/' . $user['id']) ?>" class="text-decoration-none">
                                            <?= e($user['display_name'] ?: $user['username']) ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($online_users) > 12): ?>
                        <div class="text-center mt-2">
                            <small class="text-muted">and <?= count($online_users) - 12 ?> more...</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Recent Posts -->
        <?php if (!empty($recent_posts)): ?>
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-comment me-2"></i>Recent Posts</h5>
                </div>
                <div class="card-body p-0">
                    <?php foreach (array_slice($recent_posts, 0, 5) as $post): ?>
                        <div class="border-bottom p-3">
                            <div class="d-flex">
                                <img src="<?= asset('images/default-avatar.png') ?>" alt="Avatar" class="user-avatar me-2">
                                <div class="flex-grow-1">
                                    <div class="post-content">
                                        <?= str_limit(strip_tags($post['content']), 100) ?>
                                    </div>
                                    <small class="text-muted">
                                        by <a href="<?= url('/profile/' . $post['user_id']) ?>"><?= e($post['username']) ?></a>
                                        <?= time_ago($post['created_at']) ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
include 'layouts/app.php';
?>