<?php
$content = ob_get_clean();
ob_start();
?>

<div class="row">
    <div class="col-12">
        <!-- Thread Header -->
        <div class="card mb-4">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h1 class="h4 mb-1"><?= e($thread['title']) ?></h1>
                        <div class="thread-meta">
                            by <a href="<?= url('/profile/' . $thread['user_id']) ?>"><?= e($thread['username']) ?></a>
                            in <a href="<?= url('/forum/' . $thread['forum_id']) ?>"><?= e($thread['forum_name']) ?></a>
                            • <?= time_ago($thread['created_at']) ?>
                            • <?= $thread['view_count'] ?> views
                        </div>
                    </div>
                    <div class="btn-group">
                        <?php if ($user && $user['id'] == $thread['user_id']): ?>
                            <a href="<?= url('/edit-thread/' . $thread['id']) ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-edit me-1"></i>Edit
                            </a>
                        <?php endif; ?>
                        <button class="btn btn-sm btn-outline-secondary" onclick="toggleThreadSubscription(<?= $thread['id'] ?>, this)">
                            <i class="fas fa-bell me-1"></i>Subscribe
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="post-content">
                    <?= nl2br(e($thread['content'])) ?>
                </div>
            </div>
        </div>

        <!-- Posts -->
        <?php if (!empty($thread['posts'])): ?>
            <div class="posts">
                <?php foreach ($thread['posts'] as $post): ?>
                    <div class="card mb-3" id="post-<?= $post['id'] ?>">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-2">
                                    <div class="text-center">
                                        <img src="<?= asset('images/default-avatar.png') ?>" alt="Avatar" class="user-avatar mb-2">
                                        <div class="fw-bold"><?= e($post['display_name'] ?: $post['username']) ?></div>
                                        <small class="text-muted"><?= e($post['role']) ?></small>
                                    </div>
                                </div>
                                <div class="col-md-10">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <small class="text-muted">
                                            #<?= $post['id'] ?> • <?= time_ago($post['created_at']) ?>
                                            <?php if ($post['is_edited']): ?>
                                                • <em>edited</em>
                                            <?php endif; ?>
                                        </small>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary btn-sm" onclick="quotePost(<?= $post['id'] ?>)">
                                                <i class="fas fa-quote-left"></i>
                                            </button>
                                            <?php if ($user && ($user['id'] == $post['user_id'] || $user['role'] === 'admin' || $user['role'] === 'moderator')): ?>
                                                <a href="<?= url('/edit-post/' . $post['id']) ?>" class="btn btn-outline-secondary btn-sm">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            <?php endif; ?>
                                            <button class="btn btn-outline-danger btn-sm" onclick="reportPost(<?= $post['id'] ?>)">
                                                <i class="fas fa-flag"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="post-content">
                                        <?= nl2br(e($post['content'])) ?>
                                    </div>
                                    
                                    <!-- Post Reactions -->
                                    <div class="mt-3">
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary btn-sm reaction-btn" 
                                                    data-post-id="<?= $post['id'] ?>" data-type="like">
                                                <i class="fas fa-thumbs-up me-1"></i>
                                                <span data-count-type="like">0</span>
                                            </button>
                                            <button class="btn btn-outline-danger btn-sm reaction-btn" 
                                                    data-post-id="<?= $post['id'] ?>" data-type="dislike">
                                                <i class="fas fa-thumbs-down me-1"></i>
                                                <span data-count-type="dislike">0</span>
                                            </button>
                                            <button class="btn btn-outline-success btn-sm reaction-btn" 
                                                    data-post-id="<?= $post['id'] ?>" data-type="love">
                                                <i class="fas fa-heart me-1"></i>
                                                <span data-count-type="love">0</span>
                                            </button>
                                        </div>
                                        
                                        <?php if ($user && $user['id'] == $thread['user_id'] && !$post['is_solution']): ?>
                                            <button class="btn btn-outline-success btn-sm ms-2" 
                                                    onclick="markAsSolution(<?= $post['id'] ?>)">
                                                <i class="fas fa-check me-1"></i>Mark as Solution
                                            </button>
                                        <?php endif; ?>
                                        
                                        <?php if ($post['is_solution']): ?>
                                            <span class="badge bg-success ms-2">
                                                <i class="fas fa-check me-1"></i>Solution
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Reply Form -->
        <?php if ($user && !$thread['is_locked']): ?>
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Post a Reply</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= url('/create-post') ?>" id="reply-form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="thread_id" value="<?= $thread['id'] ?>">
                        
                        <div class="mb-3">
                            <textarea class="form-control" name="content" rows="5" 
                                      placeholder="Write your reply here..." required></textarea>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <div class="form-text">
                                Use markdown formatting for better presentation.
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-reply me-1"></i>Post Reply
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php elseif ($thread['is_locked']): ?>
            <div class="alert alert-warning">
                <i class="fas fa-lock me-2"></i>This thread is locked and cannot be replied to.
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-sign-in-alt me-2"></i>Please <a href="<?= url('/login') ?>">login</a> to post a reply.
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function quotePost(postId) {
    // Implementation for quoting posts
    console.log('Quote post:', postId);
}

function reportPost(postId) {
    // Implementation for reporting posts
    console.log('Report post:', postId);
}

function markAsSolution(postId) {
    if (confirm('Mark this post as the solution?')) {
        fetch(`/api/posts/${postId}/mark-solution`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + (data.message || 'Failed to mark solution'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred');
        });
    }
}
</script>

<?php
$content = ob_get_clean();
include 'layouts/app.php';
?>