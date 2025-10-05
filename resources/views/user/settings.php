<?php
$content = ob_get_clean();
ob_start();
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h1 class="h4 mb-0">Account Settings</h1>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= url('/settings') ?>">
                    <?= csrf_field() ?>
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" value="<?= e($user['username']) ?>" disabled>
                        <div class="form-text">Username cannot be changed.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="display_name" class="form-label">Display Name</label>
                        <input type="text" class="form-control" id="display_name" name="display_name" 
                               value="<?= e($user['display_name'] ?: $user['username']) ?>">
                        <div class="form-text">This is how your name appears to other users.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?= e($user['email']) ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="role" class="form-label">Role</label>
                        <input type="text" class="form-control" id="role" value="<?= ucfirst($user['role']) ?>" disabled>
                        <div class="form-text">Role is assigned by administrators.</div>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="<?= url('/profile/' . $user['id']) ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Back to Profile
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Save Changes
                        </button>
                    </div>
                </form>
                
                <hr class="my-4">
                
                <div class="row">
                    <div class="col-md-6">
                        <h5>Change Password</h5>
                        <p class="text-muted">Update your password for better security.</p>
                        <a href="<?= url('/change-password') ?>" class="btn btn-outline-primary">
                            <i class="fas fa-key me-1"></i>Change Password
                        </a>
                    </div>
                    
                    <div class="col-md-6">
                        <h5>Account Actions</h5>
                        <p class="text-muted">Manage your account preferences.</p>
                        <div class="btn-group-vertical">
                            <a href="<?= url('/messages') ?>" class="btn btn-outline-info btn-sm">
                                <i class="fas fa-envelope me-1"></i>Messages
                            </a>
                            <a href="<?= url('/notifications') ?>" class="btn btn-outline-info btn-sm">
                                <i class="fas fa-bell me-1"></i>Notifications
                            </a>
                            <a href="<?= url('/subscriptions') ?>" class="btn btn-outline-info btn-sm">
                                <i class="fas fa-bookmark me-1"></i>Subscriptions
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include 'layouts/app.php';
?>