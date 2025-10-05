<?php $this->layout('layouts.app', ['title' => 'Trending Topics']) ?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8">
            <h2>Trending Topics</h2>
            <p class="text-muted">Hot topics from the last 7 days</p>
            
            <?php if (!empty($trending_threads)): ?>
                <div class="list-group">
                    <?php foreach ($trending_threads as $index => $thread): ?>
                        <div class="list-group-item">
                            <div class="d-flex align-items-start">
                                <div class="me-3">
                                    <span class="badge bg-danger fs-6">#<?= $index + 1 ?></span>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h5 class="mb-1">
                                            <a href="/thread/<?= $thread['id'] ?>" class="text-decoration-none">
                                                <?= htmlspecialchars($thread['title']) ?>
                                            </a>
                                        </h5>
                                        <small class="text-muted">
                                            <?= $thread['view_count'] ?> views
                                        </small>
                                    </div>
                                    <p class="mb-1 text-muted">
                                        <?= htmlspecialchars(substr($thread['content'], 0, 200)) ?><?= strlen($thread['content']) > 200 ? '...' : '' ?>
                                    </p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            by <a href="/user/<?= $thread['user_id'] ?>"><?= htmlspecialchars($thread['username']) ?></a>
                                            in <a href="/forum/<?= $thread['forum_id'] ?>"><?= htmlspecialchars($thread['forum_name']) ?></a>
                                            • <?= timeAgo($thread['created_at']) ?>
                                        </small>
                                        <div class="d-flex gap-2">
                                            <span class="badge bg-primary"><?= $thread['post_count'] ?> replies</span>
                                            <span class="badge bg-success"><?= $thread['reaction_count'] ?> reactions</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <h4 class="alert-heading">No Trending Topics</h4>
                    <p>There are no trending topics at the moment. Start a discussion to make it trend!</p>
                    <hr>
                    <a href="/forums" class="btn btn-primary">Browse Forums</a>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Trending Algorithm</h5>
                </div>
                <div class="card-body">
                    <p class="small text-muted">
                        Topics are ranked by recent activity within the last 7 days:
                    </p>
                    <ul class="small">
                        <li>View count (weighted 3x)</li>
                        <li>Number of replies</li>
                        <li>User reactions</li>
                        <li>Recency of activity</li>
                    </ul>
                </div>
            </div>
            
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <a href="/popular" class="btn btn-outline-primary btn-sm w-100 mb-2">Popular Threads</a>
                    <a href="/recent" class="btn btn-outline-secondary btn-sm w-100 mb-2">Recent Activity</a>
                    <a href="/forums" class="btn btn-outline-success btn-sm w-100">Browse Forums</a>
                </div>
            </div>
        </div>
    </div>
</div>