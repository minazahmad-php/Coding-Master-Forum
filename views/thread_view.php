<?php include 'header.php'; ?>

//views/thread_view.php

<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/">Home</a></li>
        <li class="breadcrumb-item"><a href="/forums">Forums</a></li>
        <li class="breadcrumb-item"><a href="/forum/<?php echo $thread['forum_slug']; ?>"><?php echo $thread['forum_name']; ?></a></li>
        <li class="breadcrumb-item active"><?php echo $thread['title']; ?></li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><?php echo $thread['title']; ?></h2>
    <div>
        <?php if (Auth::isLoggedIn() && !$thread['is_locked']): ?>
        <a href="/thread/<?php echo $thread['id']; ?>/reply" class="btn btn-primary">
            <i class="fas fa-reply"></i> Reply
        </a>
        <?php endif; ?>
        
        <?php if (Auth::isModerator()): ?>
        <div class="btn-group ms-2">
            <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                <i class="fas fa-cog"></i> Mod Tools
            </button>
            <ul class="dropdown-menu">
                <li>
                    <a class="dropdown-item" href="/thread/<?php echo $thread['id']; ?>/toggle-lock">
                        <?php echo $thread['is_locked'] ? 'Unlock' : 'Lock'; ?> Thread
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="/thread/<?php echo $thread['id']; ?>/toggle-pin">
                        <?php echo $thread['is_pinned'] ? 'Unpin' : 'Pin'; ?> Thread
                    </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item text-danger" href="/thread/<?php echo $thread['id']; ?>/delete" 
                       onclick="return confirm('Are you sure you want to delete this thread?')">
                        Delete Thread
                    </a>
                </li>
            </ul>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($thread['is_locked']): ?>
<div class="alert alert-warning">
    <i class="fas fa-lock"></i> This thread is locked. You cannot reply to it.
</div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <img src="<?php echo get_gravatar($thread['email'], 40); ?>" class="rounded-circle me-2" alt="Avatar">
                <div>
                    <h5 class="mb-0"><?php echo $thread['username']; ?></h5>
                    <small class="text-muted"><?php echo format_date($thread['created_at']); ?></small>
                </div>
            </div>
            <div>
                <span class="badge bg-<?php echo $thread['role'] === 'admin' ? 'danger' : ($thread['role'] === 'moderator' ? 'warning' : 'secondary'); ?>">
                    <?php echo ucfirst($thread['role']); ?>
                </span>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="thread-content">
            <?php echo nl2br(htmlspecialchars($thread['content'])); ?>
        </div>
    </div>
    <div class="card-footer">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <?php if (Auth::isLoggedIn() && (Auth::getUser()['id'] == $thread['user_id'] || Auth::isModerator())): ?>
                <a href="/thread/<?php echo $thread['id']; ?>/edit" class="btn btn-sm btn-outline-primary">Edit</a>
                <?php endif; ?>
            </div>
            <div class="text-muted">
                <small>Views: <?php echo $thread['views']; ?> • Replies: <?php echo $thread['replies_count']; ?></small>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($posts)): ?>
<h4 class="mb-3">Replies (<?php echo $totalPosts; ?>)</h4>

<?php foreach ($posts as $post): ?>
<div class="card mb-3" id="post-<?php echo $post['id']; ?>">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <img src="<?php echo get_gravatar($post['email'], 40); ?>" class="rounded-circle me-2" alt="Avatar">
                <div>
                    <h5 class="mb-0"><?php echo $post['username']; ?></h5>
                    <small class="text-muted"><?php echo format_date($post['created_at']); ?></small>
                </div>
            </div>
            <div>
                <span class="badge bg-<?php echo $post['role'] === 'admin' ? 'danger' : ($post['role'] === 'moderator' ? 'warning' : 'secondary'); ?>">
                    <?php echo ucfirst($post['role']); ?>
                </span>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="post-content">
            <?php echo nl2br(htmlspecialchars($post['content'])); ?>
        </div>
        <?php if ($post['is_edited']): ?>
        <div class="mt-2">
            <small class="text-muted"><i>Last edited on <?php echo format_date($post['updated_at']); ?></i></small>
        </div>
        <?php endif; ?>
    </div>
    <div class="card-footer">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <a href="/thread/<?php echo $thread['id']; ?>#post-<?php echo $post['id']; ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-link"></i> Link
                </a>
                <?php if (Auth::isLoggedIn() && (Auth::getUser()['id'] == $post['user_id'] || Auth::isModerator())): ?>
                <a href="/post/<?php echo $post['id']; ?>/edit" class="btn btn-sm btn-outline-primary">Edit</a>
                <a href="/post/<?php echo $post['id']; ?>/delete" class="btn btn-sm btn-outline-danger" 
                   onclick="return confirm('Are you sure you want to delete this post?')">Delete</a>
                <?php endif; ?>
            </div>
            <div class="text-muted">
                <small>#<?php echo array_search($post, $posts) + 1 + (($page - 1) * 10); ?></small>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<?php if ($pagination['pages'] > 1): ?>
<nav class="mt-4">
    <ul class="pagination justify-content-center">
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
<?php endif; ?>

<?php else: ?>
<div class="card">
    <div class="card-body text-center py-5">
        <i class="fas fa-comments fa-3x text-muted mb-3"></i>
        <h4>No replies yet</h4>
        <p class="text-muted">Be the first to reply to this thread.</p>
        <?php if (Auth::isLoggedIn() && !$thread['is_locked']): ?>
        <a href="/thread/<?php echo $thread['id']; ?>/reply" class="btn btn-primary">Reply</a>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php if (Auth::isLoggedIn() && !$thread['is_locked']): ?>
<div class="card mt-4">
    <div class="card-header">
        <h5 class="mb-0">Post a reply</h5>
    </div>
    <div class="card-body">
        <form action="/thread/<?php echo $thread['id']; ?>/reply" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            
            <div class="mb-3">
                <textarea class="form-control" id="content" name="content" rows="5" placeholder="Write your reply here..." required></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary">Post Reply</button>
        </form>
    </div>
</div>
<?php endif; ?>

<?php include 'footer.php'; ?>