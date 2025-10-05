<?php
$content = ob_get_clean();
ob_start();
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-plus me-2"></i>Create New Thread in <?= e($forum['name']) ?>
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= url('/create-thread') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="forum_id" value="<?= $forum['id'] ?>">
                    
                    <div class="mb-3">
                        <label for="title" class="form-label">Thread Title</label>
                        <input type="text" class="form-control" id="title" name="title" 
                               value="<?= old('title') ?>" required>
                        <div class="form-text">Choose a descriptive title for your thread.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="content" class="form-label">Content</label>
                        <textarea class="form-control" id="content" name="content" rows="10" required><?= old('content') ?></textarea>
                        <div class="form-text">Use markdown formatting for better presentation.</div>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="<?= url('/forum/' . $forum['id']) ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i>Create Thread
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