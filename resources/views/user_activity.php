<?php $this->layout('layouts.app', ['title' => $user['display_name'] . ' - Activity']) ?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <img src="<?= $user['avatar'] ?: '/images/default-avatar.png' ?>" 
                         alt="<?= htmlspecialchars($user['display_name']) ?>" 
                         class="rounded-circle mb-3" 
                         width="100" height="100">
                    <h5><?= htmlspecialchars($user['display_name']) ?></h5>
                    <p class="text-muted">@<?= htmlspecialchars($user['username']) ?></p>
                    <span class="badge bg-<?= $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'moderator' ? 'warning' : 'primary') ?>">
                        <?= ucfirst($user['role']) ?>
                    </span>
                </div>
            </div>
            
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0">Member Since</h6>
                </div>
                <div class="card-body">
                    <p class="mb-0"><?= date('F j, Y', strtotime($user['created_at'])) ?></p>
                </div>
            </div>
        </div>
        
        <div class="col-md-9">
            <h2><?= htmlspecialchars($user['display_name']) ?>'s Activity</h2>
            
            <ul class="nav nav-tabs" id="activityTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="threads-tab" data-bs-toggle="tab" data-bs-target="#threads" type="button" role="tab">
                        Threads (<?= count($user_threads) ?>)
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="posts-tab" data-bs-toggle="tab" data-bs-target="#posts" type="button" role="tab">
                        Posts (<?= count($user_posts) ?>)
                    </button>
                </li>
            </ul>
            
            <div class="tab-content" id="activityTabsContent">
                <div class="tab-pane fade show active" id="threads" role="tabpanel">
                    <div class="mt-3">
                        <?php if (!empty($user_threads)): ?>
                            <div class="list-group">
                                <?php foreach ($user_threads as $thread): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h5 class="mb-1">
                                                <a href="/thread/<?= $thread['id'] ?>" class="text-decoration-none">
                                                    <?= htmlspecialchars($thread['title']) ?>
                                                </a>
                                            </h5>
                                            <small class="text-muted">
                                                <?= timeAgo($thread['created_at']) ?>
                                            </small>
                                        </div>
                                        <p class="mb-1 text-muted">
                                            <?= htmlspecialchars(substr($thread['content'], 0, 200)) ?><?= strlen($thread['content']) > 200 ? '...' : '' ?>
                                        </p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">
                                                in <a href="/forum/<?= $thread['forum_id'] ?>"><?= htmlspecialchars($thread['forum_name']) ?></a>
                                            </small>
                                            <div class="d-flex gap-2">
                                                <span class="badge bg-primary"><?= $thread['post_count'] ?> replies</span>
                                                <span class="badge bg-secondary"><?= $thread['view_count'] ?> views</span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <p class="mb-0">This user hasn't started any threads yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="tab-pane fade" id="posts" role="tabpanel">
                    <div class="mt-3">
                        <?php if (!empty($user_posts)): ?>
                            <div class="list-group">
                                <?php foreach ($user_posts as $post): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1">
                                                <a href="/thread/<?= $post['thread_id'] ?>#post-<?= $post['id'] ?>" class="text-decoration-none">
                                                    Re: <?= htmlspecialchars($post['thread_title']) ?>
                                                </a>
                                            </h6>
                                            <small class="text-muted">
                                                <?= timeAgo($post['created_at']) ?>
                                            </small>
                                        </div>
                                        <p class="mb-1 text-muted">
                                            <?= htmlspecialchars(substr($post['content'], 0, 200)) ?><?= strlen($post['content']) > 200 ? '...' : '' ?>
                                        </p>
                                        <small class="text-muted">
                                            in <a href="/forum/<?= $post['forum_id'] ?>"><?= htmlspecialchars($post['forum_name']) ?></a>
                                        </small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <p class="mb-0">This user hasn't made any posts yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>