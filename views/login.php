<?php include 'header.php'; ?>

//views/login.php

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">Login</h4>
            </div>
            <div class="card-body">
                <form action="/login" method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    
                    <div class="mb-3">
                        <label for="identifier" class="form-label">Username or Email</label>
                        <input type="text" class="form-control" id="identifier" name="identifier" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label" for="remember">Remember me</label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Login</button>
                    <a href="/register" class="btn btn-link">Don't have an account? Register</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>