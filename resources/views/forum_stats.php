<?php $this->layout('layouts.app', ['title' => $forum['name'] . ' - Statistics']) ?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8">
            <h2><?= htmlspecialchars($forum['name']) ?> Statistics</h2>
            <p class="text-muted"><?= htmlspecialchars($forum['description']) ?></p>
            
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card text-center">
                        <div class="card-body">
                            <h3 class="text-primary"><?= $thread_count ?></h3>
                            <p class="card-text">Total Threads</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-center">
                        <div class="card-body">
                            <h3 class="text-success"><?= $post_count ?></h3>
                            <p class="card-text">Total Posts</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-center">
                        <div class="card-body">
                            <h3 class="text-info"><?= $recent_activity ? count($recent_activity) : 0 ?></h3>
                            <p class="card-text">Recent Activity</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Recent Activity</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($recent_activity)): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($recent_activity as $activity): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-start">
                                    <div class="ms-2 me-auto">
                                        <div class="fw-bold">
                                            <a href="/thread/<?= $activity['thread_id'] ?>" class="text-decoration-none">
                                                <?= htmlspecialchars($activity['thread_title']) ?>
                                            </a>
                                        </div>
                                        <small class="text-muted">
                                            by <a href="/user/<?= $activity['user_id'] ?>"><?= htmlspecialchars($activity['username']) ?></a>
                                            • <?= timeAgo($activity['created_at']) ?>
                                        </small>
                                    </div>
                                    <span class="badge bg-primary rounded-pill">Post</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No recent activity in this forum.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Forum Information</h5>
                </div>
                <div class="card-body">
                    <p><strong>Name:</strong> <?= htmlspecialchars($forum['name']) ?></p>
                    <p><strong>Description:</strong> <?= htmlspecialchars($forum['description']) ?></p>
                    <p><strong>Status:</strong> 
                        <span class="badge bg-<?= $forum['status'] === 'active' ? 'success' : 'secondary' ?>">
                            <?= ucfirst($forum['status']) ?>
                        </span>
                    </p>
                    <p><strong>Created:</strong> <?= date('F j, Y', strtotime($forum['created_at'])) ?></p>
                </div>
            </div>
            
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <a href="/forum/<?= $forum['id'] ?>" class="btn btn-primary btn-sm w-100 mb-2">View Forum</a>
                    <a href="/thread/create?forum=<?= $forum['id'] ?>" class="btn btn-outline-primary btn-sm w-100 mb-2">Start New Thread</a>
                    <a href="/forums" class="btn btn-outline-secondary btn-sm w-100">Back to Forums</a>
                </div>
            </div>
        </div>
    </div>
</div>