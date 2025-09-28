<?php include 'header.php'; ?>

//views/home.php

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Latest Threads</h4>
            </div>
            <div class="card-body">
                <?php if (!empty($latestThreads)): ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($latestThreads as $thread): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">
                                <a href="/thread/<?php echo $thread['id']; ?>"><?php echo $thread['title']; ?></a>
                            </h6>
                            <small class="text-muted">
                                By <a href="/user/<?php echo $thread['username']; ?>"><?php echo $thread['username']; ?></a>
                                in <a href="/forum/<?php echo $thread['forum_slug']; ?>"><?php echo $thread['forum_name']; ?></a>
                                • <?php echo format_date($thread['created_at']); ?>
                            </small>
                        </div>
                        <span class="badge bg-primary rounded-pill"><?php echo $thread['replies_count']; ?> replies</span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="text-muted">No threads yet.</p>
                <?php endif; ?>
            </div>
            <div class="card-footer">
                <a href="/forums" class="btn btn-primary">View All Forums</a>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h4 class="mb-0">Latest Posts</h4>
            </div>
            <div class="card-body">
                <?php if (!empty($latestPosts)): ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($latestPosts as $post): ?>
                    <div class="list-group-item">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1">
                                <a href="/thread/<?php echo $post['thread_id']; ?>#post-<?php echo $post['id']; ?>">
                                    <?php echo truncate($post['content'], 100); ?>
                                </a>
                            </h6>
                        </div>
                        <small class="text-muted">
                            By <a href="/user/<?php echo $post['username']; ?>"><?php echo $post['username']; ?></a>
                            in <a href="/thread/<?php echo $post['thread_id']; ?>"><?php echo $post['thread_title']; ?></a>
                            • <?php echo format_date($post['created_at']); ?>
                        </small>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="text-muted">No posts yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h4 class="mb-0">Forum Statistics</h4>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-4">
                        <h5><?php echo (new Forum())->countAll(); ?></h5>
                        <small>Forums</small>
                    </div>
                    <div class="col-4">
                        <h5><?php echo (new Thread())->countAll(); ?></h5>
                        <small>Threads</small>
                    </div>
                    <div class="col-4">
                        <h5><?php echo (new Post())->countAll(); ?></h5>
                        <small>Posts</small>
                    </div>
                </div>
                <hr>
                <div class="row text-center">
                    <div class="col-12">
                        <h5><?php echo (new User())->countAll(); ?></h5>
                        <small>Members</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-warning text-dark">
                <h4 class="mb-0">Top Users</h4>
            </div>
            <div class="card-body">
                <?php if (!empty($topUsers)): ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($topUsers as $user): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <img src="<?php echo get_gravatar($user['email'], 30); ?>" class="rounded-circle me-2" alt="Avatar">
                            <a href="/user/<?php echo $user['username']; ?>"><?php echo $user['username']; ?></a>
                        </div>
                        <span class="badge bg-primary rounded-pill"><?php echo $user['reputation']; ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="text-muted">No users yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!Auth::isLoggedIn()): ?>
        <div class="card mb-4">
            <div class="card-header bg-secondary text-white">
                <h4 class="mb-0">Join Our Community</h4>
            </div>
            <div class="card-body text-center">
                <p>Sign up to participate in discussions and connect with other members.</p>
                <a href="/register" class="btn btn-primary me-2">Register</a>
                <a href="/login" class="btn btn-outline-primary">Login</a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>