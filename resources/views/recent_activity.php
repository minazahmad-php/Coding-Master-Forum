<?php $this->layout('layouts.app', ['title' => 'Recent Activity']) ?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8">
            <h2>Recent Activity</h2>
            
            <div class="card">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs" id="activityTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="threads-tab" data-bs-toggle="tab" data-bs-target="#threads" type="button" role="tab">
                                Recent Threads
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="posts-tab" data-bs-toggle="tab" data-bs-target="#posts" type="button" role="tab">
                                Recent Posts
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content" id="activityTabsContent">
                        <div class="tab-pane fade show active" id="threads" role="tabpanel">
                            <?php if (!empty($recent_threads)): ?>
                                <?php foreach ($recent_threads as $thread): ?>
                                    <div class="d-flex align-items-center mb-3 p-3 border rounded">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1">
                                                <a href="/thread/<?= $thread['id'] ?>" class="text-decoration-none">
                                                    <?= htmlspecialchars($thread['title']) ?>
                                                </a>
                                            </h6>
                                            <small class="text-muted">
                                                by <a href="/user/<?= $thread['user_id'] ?>"><?= htmlspecialchars($thread['username']) ?></a>
                                                in <a href="/forum/<?= $thread['forum_id'] ?>"><?= htmlspecialchars($thread['forum_name']) ?></a>
                                                • <?= timeAgo($thread['created_at']) ?>
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <small class="text-muted">
                                                <?= $thread['post_count'] ?? 0 ?> replies
                                            </small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted">No recent threads found.</p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="tab-pane fade" id="posts" role="tabpanel">
                            <?php if (!empty($recent_posts)): ?>
                                <?php foreach ($recent_posts as $post): ?>
                                    <div class="d-flex align-items-center mb-3 p-3 border rounded">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1">
                                                <a href="/thread/<?= $post['thread_id'] ?>#post-<?= $post['id'] ?>" class="text-decoration-none">
                                                    Re: <?= htmlspecialchars($post['thread_title']) ?>
                                                </a>
                                            </h6>
                                            <p class="mb-1 text-muted small">
                                                <?= htmlspecialchars(substr($post['content'], 0, 150)) ?><?= strlen($post['content']) > 150 ? '...' : '' ?>
                                            </p>
                                            <small class="text-muted">
                                                by <a href="/user/<?= $post['user_id'] ?>"><?= htmlspecialchars($post['username']) ?></a>
                                                • <?= timeAgo($post['created_at']) ?>
                                            </small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted">No recent posts found.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Quick Stats</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <h4 class="text-primary"><?= count($recent_threads) ?></h4>
                            <small class="text-muted">Recent Threads</small>
                        </div>
                        <div class="col-6">
                            <h4 class="text-success"><?= count($recent_posts) ?></h4>
                            <small class="text-muted">Recent Posts</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>