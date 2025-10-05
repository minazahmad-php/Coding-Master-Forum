<?php $this->layout('layouts.app', ['title' => 'Popular Threads']) ?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8">
            <h2>Popular Threads</h2>
            <p class="text-muted">Most viewed and discussed threads</p>
            
            <?php if (!empty($popular_threads)): ?>
                <div class="list-group">
                    <?php foreach ($popular_threads as $thread): ?>
                        <div class="list-group-item">
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
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <h4 class="alert-heading">No Popular Threads</h4>
                    <p>There are no popular threads at the moment. Be the first to start a discussion!</p>
                    <hr>
                    <a href="/forums" class="btn btn-primary">Browse Forums</a>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Popularity Criteria</h5>
                </div>
                <div class="card-body">
                    <p class="small text-muted">
                        Threads are ranked by a combination of:
                    </p>
                    <ul class="small">
                        <li>View count</li>
                        <li>Number of replies</li>
                        <li>User reactions</li>
                        <li>Recent activity</li>
                    </ul>
                </div>
            </div>
            
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <a href="/forums" class="btn btn-outline-primary btn-sm w-100 mb-2">Browse All Forums</a>
                    <a href="/recent" class="btn btn-outline-secondary btn-sm w-100 mb-2">Recent Activity</a>
                    <a href="/trending" class="btn btn-outline-success btn-sm w-100">Trending Topics</a>
                </div>
            </div>
        </div>
    </div>
</div>