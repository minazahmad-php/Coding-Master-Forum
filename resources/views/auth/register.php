<?php
$content = ob_get_clean();
ob_start();
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card">
            <div class="card-header text-center">
                <h4><i class="fas fa-user-plus me-2"></i>Create Account</h4>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= url('/register') ?>">
                    <?= csrf_field() ?>
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" 
                               value="<?= old('username') ?>" required>
                        <div class="form-text">Choose a unique username for your account.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?= old('email') ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="display_name" class="form-label">Display Name</label>
                        <input type="text" class="form-control" id="display_name" name="display_name" 
                               value="<?= old('display_name') ?>">
                        <div class="form-text">Optional. If not provided, username will be used.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <div class="form-text">Password must be at least 8 characters long with uppercase, lowercase, and numbers.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                        <label class="form-check-label" for="terms">
                            I agree to the <a href="<?= url('/rules') ?>" target="_blank">Terms of Service</a>
                        </label>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Create Account</button>
                    </div>
                </form>
            </div>
            <div class="card-footer text-center">
                <p class="mb-0">Already have an account? 
                    <a href="<?= url('/login') ?>" class="text-decoration-none">Login here</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include 'layouts/app.php';
?>