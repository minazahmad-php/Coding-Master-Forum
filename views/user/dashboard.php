<?php include '../header.php'; ?>

//views/user/dashboard.php

<div class="row">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <img src="<?php echo get_gravatar($user['email'], 100); ?>" class="rounded-circle mb-3" alt="Avatar">
                <h4><?php echo $user['username']; ?></h4>
                <p class="text-muted">Member since <?php echo format_date($user['created_at'], 'F Y'); ?></p>
                
                <div class="d-flex justify-content-around mb-3">
                    <div>
                        <h5><?php echo $user['threads_count']; ?></h5>
                        <small>Threads</small>
                    </div>
                    <div>
                        <h5><?php echo $user['posts_count']; ?></h5>
                        <small>Posts</small>
                    </div>
                    <div>
                        <h5><?php echo $user['reputation']; ?></h5>
                        <small>Reputation</small>
                    </div>
                </div>
                
                <a href="/user/profile" class="btn btn-outline-primary btn-sm">Edit Profile</a>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0">Quick Links</h6>
            </div>
            <div class="list-group list-group-flush">
                <a href="/user/profile" class="list-group-item list-group-item-action">Profile</a>
                <a href="/user/settings" class="list-group-item list-group-item-action">Settings</a>
                <a href="/messages" class="list-group-item list-group-item-action">
                    Messages
                    <?php if ($unreadMessages > 0): ?>
                    <span class="badge bg-danger float-end"><?php echo $unreadMessages; ?></span>
                    <?php endif; ?>
                </a>
                <a href="/user/threads" class="list-group-item list-group-item-action">My Threads</a>
                <a href="/user/posts" class="list-group-item list-group-item-action">My Posts</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-9">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Recent Threads</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($threads)): ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($threads as $thread): ?>
                    <div class="list-group-item">
                        <h6 class="mb-1">
                            <a href="/thread/<?php echo $thread['id']; ?>"><?php echo $thread['title']; ?></a>
                        </h6>
                        <small class="text-muted">
                            In <a href="/forum/<?php echo $thread['forum_slug']; ?>"><?php echo $thread['forum_name']; ?></a>
                            • <?php echo format_date($thread['created_at']); ?>
                        </small>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="text-muted">You haven't created any threads yet.</p>
                <?php endif; ?>
            </div>
            <div class="card-footer">
                <a href="/user/threads" class="btn btn-sm btn-outline-primary">View All Threads</a>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Recent Posts</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($posts)): ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($posts as $post): ?>
                    <div class="list-group-item">
                        <div class="mb-1">
                            <?php echo truncate($post['content'], 150); ?>
                        </div>
                        <small class="text-muted">
                            In <a href="/thread/<?php echo $post['thread_id']; ?>#post-<?php echo $post['id']; ?>"><?php echo $post['thread_title']; ?></a>
                            • <?php echo format_date($post['created_at']); ?>
                        </small>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="text-muted">You haven't made any posts yet.</p>
                <?php endif; ?>
            </div>
            <div class="card-footer">
                <a href="/user/posts" class="btn btn-sm btn-outline-primary">View All Posts</a>
            </div>
        </div>
    </div>
</div>

<?php include '../footer.php'; ?>