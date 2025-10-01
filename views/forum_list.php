<?php include 'header.php'; ?>

//views/forum_list.php

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><?php echo $forum['name']; ?></h2>
    <?php if (Auth::isLoggedIn()): ?>
    <a href="/forum/<?php echo $forum['slug']; ?>/create-thread" class="btn btn-primary">
        <i class="fas fa-plus"></i> New Thread
    </a>
    <?php endif; ?>
</div>

<p class="text-muted mb-4"><?php echo $forum['description']; ?></p>

<?php if (!empty($threads)): ?>
<div class="card">
    <div class="card-header bg-light">
        <div class="row">
            <div class="col-md-6">Thread</div>
            <div class="col-md-2 text-center">Replies</div>
            <div class="col-md-2 text-center">Views</div>
            <div class="col-md-2">Last Post</div>
        </div>
    </div>
    <div class="list-group list-group-flush">
        <?php foreach ($threads as $thread): ?>
        <div class="list-group-item">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="d-flex align-items-center">
                        <?php if ($thread['is_pinned']): ?>
                        <span class="badge bg-warning me-2"><i class="fas fa-thumbtack"></i> Pinned</span>
                        <?php endif; ?>
                        <?php if ($thread['is_locked']): ?>
                        <span class="badge bg-danger me-2"><i class="fas fa-lock"></i> Locked</span>
                        <?php endif; ?>
                        <div>
                            <h5 class="mb-1">
                                <a href="/thread/<?php echo $thread['id']; ?>"><?php echo $thread['title']; ?></a>
                            </h5>
                            <small class="text-muted">
                                Started by <a href="/user/<?php echo $thread['username']; ?>"><?php echo $thread['username']; ?></a>
                                • <?php echo format_date($thread['created_at']); ?>
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 text-center">
                    <span class="badge bg-primary rounded-pill"><?php echo $thread['post_count']; ?></span>
                </div>
                <div class="col-md-2 text-center">
                    <span class="text-muted"><?php echo $thread['views']; ?></span>
                </div>
                <div class="col-md-2">
                    <small class="text-muted">
                        <?php echo format_date($thread['updated_at']); ?>
                    </small>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

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
        <h4>No threads yet</h4>
        <p class="text-muted">Be the first to start a discussion in this forum.</p>
        <?php if (Auth::isLoggedIn()): ?>
        <a href="/forum/<?php echo $forum['slug']; ?>/create-thread" class="btn btn-primary">Create Thread</a>
        <?php else: ?>
        <a href="/login" class="btn btn-primary">Login to Create Thread</a>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php include 'footer.php'; ?>