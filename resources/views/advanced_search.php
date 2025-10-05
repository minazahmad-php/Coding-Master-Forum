<?php
$content = ob_get_clean();
ob_start();
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h1 class="h4 mb-0">Advanced Search</h1>
            </div>
            <div class="card-body">
                <form method="GET" action="<?= url('/search') ?>">
                    <div class="mb-3">
                        <label for="q" class="form-label">Search Query</label>
                        <input type="text" class="form-control" id="q" name="q" 
                               value="<?= e($_GET['q'] ?? '') ?>" placeholder="Enter search terms...">
                    </div>
                    
                    <div class="mb-3">
                        <label for="type" class="form-label">Search In</label>
                        <select class="form-select" id="type" name="type">
                            <option value="all" <?= ($_GET['type'] ?? '') === 'all' ? 'selected' : '' ?>>All Content</option>
                            <option value="threads" <?= ($_GET['type'] ?? '') === 'threads' ? 'selected' : '' ?>>Threads Only</option>
                            <option value="posts" <?= ($_GET['type'] ?? '') === 'posts' ? 'selected' : '' ?>>Posts Only</option>
                            <option value="users" <?= ($_GET['type'] ?? '') === 'users' ? 'selected' : '' ?>>Users Only</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="forum" class="form-label">Forum</label>
                        <select class="form-select" id="forum" name="forum">
                            <option value="">All Forums</option>
                            <?php if (!empty($forums)): ?>
                                <?php foreach ($forums as $forum): ?>
                                    <option value="<?= $forum['id'] ?>" <?= ($_GET['forum'] ?? '') == $forum['id'] ? 'selected' : '' ?>>
                                        <?= e($forum['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="author" class="form-label">Author</label>
                        <input type="text" class="form-control" id="author" name="author" 
                               value="<?= e($_GET['author'] ?? '') ?>" placeholder="Search by author username...">
                    </div>
                    
                    <div class="mb-3">
                        <label for="date_from" class="form-label">Date From</label>
                        <input type="date" class="form-control" id="date_from" name="date_from" 
                               value="<?= e($_GET['date_from'] ?? '') ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="date_to" class="form-label">Date To</label>
                        <input type="date" class="form-control" id="date_to" name="date_to" 
                               value="<?= e($_GET['date_to'] ?? '') ?>">
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="<?= url('/search') ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Simple Search
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-1"></i>Search
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include 'layouts/app.php';
?>