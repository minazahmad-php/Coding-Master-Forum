<?php
$content = ob_get_clean();
ob_start();
?>

<div class="row">
    <div class="col-12">
        <h1 class="h3 mb-4">Members</h1>
        
        <?php if (!empty($users)): ?>
            <div class="card">
                <div class="card-body p-0">
                    <?php foreach ($users as $user): ?>
                        <div class="border-bottom p-3">
                            <div class="row align-items-center">
                                <div class="col-md-2">
                                    <img src="<?= asset('images/default-avatar.png') ?>" alt="Avatar" class="user-avatar">
                                </div>
                                <div class="col-md-6">
                                    <h6 class="mb-1">
                                        <a href="<?= url('/profile/' . $user['id']) ?>" class="text-decoration-none">
                                            <?= e($user['display_name'] ?: $user['username']) ?>
                                        </a>
                                    </h6>
                                    <small class="text-muted">@<?= e($user['username']) ?></small>
                                    <div class="mt-1">
                                        <span class="badge bg-<?= $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'moderator' ? 'warning' : 'primary') ?>">
                                            <?= ucfirst($user['role']) ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="col-md-4 text-md-end">
                                    <small class="text-muted">
                                        Joined <?= time_ago($user['created_at']) ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Pagination -->
            <?php if ($total > 20): ?>
                <nav aria-label="Members pagination" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page - 1 ?>">Previous</a>
                        </li>
                        <?php for ($i = 1; $i <= ceil($total / 20); $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= $page >= ceil($total / 20) ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page + 1 ?>">Next</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php else: ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">No Members Found</h4>
                    <p class="text-muted">There are no members to display at the moment.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
include 'layouts/app.php';
?>