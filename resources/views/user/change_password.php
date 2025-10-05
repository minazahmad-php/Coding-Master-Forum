<?php
$content = ob_get_clean();
ob_start();
?>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h1 class="h4 mb-0">Change Password</h1>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= url('/change-password') ?>">
                    <?= csrf_field() ?>
                    
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                        <div class="form-text">Password must be at least 8 characters long with uppercase, lowercase, and numbers.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="<?= url('/settings') ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Back to Settings
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-key me-1"></i>Change Password
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