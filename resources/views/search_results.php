<?php
$content = ob_get_clean();
ob_start();
?>

<div class="row">
    <div class="col-12">
        <h1 class="h3 mb-4">Search Results</h1>
        
        <?php if (!empty($query)): ?>
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="mb-3">Search Results for: "<?= e($query) ?>"</h5>
                    <p class="text-muted">Found <?= $total ?> results</p>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($results)): ?>
            <!-- Threads Results -->
            <?php if (!empty($results['threads'])): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">Threads (<?= count($results['threads']) ?>)</h6>
                    </div>
                    <div class="card-body p-0">
                        <?php foreach ($results['threads'] as $thread): ?>
                            <div class="border-bottom p-3">
                                <h6 class="mb-1">
                                    <a href="<?= url('/thread/' . $thread['id']) ?>" class="text-decoration-none">
                                        <?= e($thread['title']) ?>
                                    </a>
                                </h6>
                                <small class="text-muted">
                                    by <a href="<?= url('/profile/' . $thread['user_id']) ?>"><?= e($thread['username']) ?></a>
                                    in <a href="<?= url('/forum/' . $thread['forum_id']) ?>"><?= e($thread['forum_name']) ?></a>
                                    • <?= time_ago($thread['created_at']) ?>
                                </small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Posts Results -->
            <?php if (!empty($results['posts'])): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">Posts (<?= count($results['posts']) ?>)</h6>
                    </div>
                    <div class="card-body p-0">
                        <?php foreach ($results['posts'] as $post): ?>
                            <div class="border-bottom p-3">
                                <div class="post-content mb-2">
                                    <?= str_limit(strip_tags($post['content']), 200) ?>
                                </div>
                                <small class="text-muted">
                                    by <a href="<?= url('/profile/' . $post['user_id']) ?>"><?= e($post['username']) ?></a>
                                    in <a href="<?= url('/thread/' . $post['thread_id']) ?>"><?= e($post['thread_title']) ?></a>
                                    • <?= time_ago($post['created_at']) ?>
                                </small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Users Results -->
            <?php if (!empty($results['users'])): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">Users (<?= count($results['users']) ?>)</h6>
                    </div>
                    <div class="card-body p-0">
                        <?php foreach ($results['users'] as $user): ?>
                            <div class="border-bottom p-3">
                                <div class="d-flex align-items-center">
                                    <img src="<?= asset('images/default-avatar.png') ?>" alt="Avatar" class="user-avatar me-3">
                                    <div>
                                        <h6 class="mb-1">
                                            <a href="<?= url('/profile/' . $user['id']) ?>" class="text-decoration-none">
                                                <?= e($user['display_name'] ?: $user['username']) ?>
                                            </a>
                                        </h6>
                                        <small class="text-muted">@<?= e($user['username']) ?></small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">No Results Found</h4>
                    <p class="text-muted">Try different keywords or check your spelling.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
include 'layouts/app.php';
?>