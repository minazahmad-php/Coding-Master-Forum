<?php
$content = ob_get_clean();
ob_start();
?>

<div class="row">
    <div class="col-12">
        <!-- Forum Header -->
        <div class="card mb-4">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h4 mb-1"><?= e($forum['name']) ?></h1>
                        <p class="text-muted mb-0"><?= e($forum['description']) ?></p>
                    </div>
                    <div>
                        <a href="<?= url('/create-thread/' . $forum['id']) ?>" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i>New Thread
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Threads List -->
        <?php if (!empty($forum['threads'])): ?>
            <div class="card">
                <div class="card-header">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="mb-0">Threads (<?= $forum['thread_count'] ?>)</h6>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <small class="text-muted">Last Post</small>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php foreach ($forum['threads'] as $thread): ?>
                        <div class="border-bottom p-3">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <div class="d-flex align-items-start">
                                        <div class="me-3">
                                            <?php if ($thread['is_pinned']): ?>
                                                <i class="fas fa-thumbtack text-warning" title="Pinned"></i>
                                            <?php endif; ?>
                                            <?php if ($thread['is_locked']): ?>
                                                <i class="fas fa-lock text-danger" title="Locked"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <h6 class="mb-1">
                                                <a href="<?= url('/thread/' . $thread['id']) ?>" class="text-decoration-none">
                                                    <?= e($thread['title']) ?>
                                                </a>
                                            </h6>
                                            <small class="text-muted">
                                                by <a href="<?= url('/profile/' . $thread['user_id']) ?>"><?= e($thread['username']) ?></a>
                                                • <?= time_ago($thread['created_at']) ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 text-md-end">
                                    <div class="row text-center">
                                        <div class="col-4">
                                            <div class="small text-muted"><?= $thread['post_count'] ?></div>
                                            <small>Replies</small>
                                        </div>
                                        <div class="col-4">
                                            <div class="small text-muted"><?= $thread['view_count'] ?></div>
                                            <small>Views</small>
                                        </div>
                                        <div class="col-4">
                                            <div class="small text-muted">
                                                <?= $thread['last_post_at'] ? time_ago($thread['last_post_at']) : 'Never' ?>
                                            </div>
                                            <small>Last Post</small>
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
                    <h4 class="text-muted">No Threads Yet</h4>
                    <p class="text-muted">Be the first to start a discussion in this forum.</p>
                    <a href="<?= url('/create-thread/' . $forum['id']) ?>" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Start First Thread
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
include 'layouts/app.php';
?>