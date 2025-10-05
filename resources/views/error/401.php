<?php
$content = ob_get_clean();
ob_start();
?>

<div class="row justify-content-center">
    <div class="col-md-6 text-center">
        <div class="card">
            <div class="card-body py-5">
                <i class="fas fa-lock text-warning" style="font-size: 4rem;"></i>
                <h1 class="display-1 text-muted">401</h1>
                <h2 class="mb-3">Unauthorized</h2>
                <p class="text-muted mb-4">
                    <?= isset($message) ? e($message) : 'You must be logged in to access this resource.' ?>
                </p>
                <a href="<?= url('/login') ?>" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt me-2"></i>Login
                </a>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include 'layouts/app.php';
?>