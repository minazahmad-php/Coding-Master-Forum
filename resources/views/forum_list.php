<?php
$content = ob_get_clean();
ob_start();
?>

<div class="row">
    <div class="col-12">
        <h1 class="h3 mb-4">Forums</h1>
        
        <?php if (!empty($forums)): ?>
            <div class="card">
                <div class="card-body p-0">
                    <?php foreach ($forums as $forum): ?>
                        <div class="forum-card border-bottom p-4">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h5 class="mb-2">
                                        <a href="<?= url('/forum/' . $forum['id']) ?>" class="text-decoration-none">
                                            <?= e($forum['name']) ?>
                                        </a>
                                    </h5>
                                    <p class="text-muted mb-0"><?= e($forum['description']) ?></p>
                                </div>
                                <div class="col-md-4 text-md-end">
                                    <div class="row text-center">
                                        <div class="col-4">
                                            <div class="h5 text-primary"><?= $forum['thread_count'] ?></div>
                                            <small class="text-muted">Threads</small>
                                        </div>
                                        <div class="col-4">
                                            <div class="h5 text-success"><?= $forum['post_count'] ?></div>
                                            <small class="text-muted">Posts</small>
                                        </div>
                                        <div class="col-4">
                                            <div class="h5 text-info"><?= $forum['unique_participants'] ?? 0 ?></div>
                                            <small class="text-muted">Users</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">No Forums Available</h4>
                    <p class="text-muted">There are no forums to display at the moment.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
include 'layouts/app.php';
?>