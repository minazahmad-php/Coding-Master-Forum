<?php
$content = ob_get_clean();
ob_start();
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card">
            <div class="card-header text-center">
                <h4><i class="fas fa-sign-in-alt me-2"></i>Login</h4>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= url('/login') ?>">
                    <?= csrf_field() ?>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?= old('email') ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label" for="remember">
                            Remember me
                        </label>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Login</button>
                    </div>
                </form>
                
                <div class="text-center mt-3">
                    <a href="<?= url('/forgot-password') ?>" class="text-decoration-none">
                        Forgot your password?
                    </a>
                </div>
            </div>
            <div class="card-footer text-center">
                <p class="mb-0">Don't have an account? 
                    <a href="<?= url('/register') ?>" class="text-decoration-none">Register here</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include 'layouts/app.php';
?>