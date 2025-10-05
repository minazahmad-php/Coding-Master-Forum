<?php
$content = ob_get_clean();
ob_start();
?>

<div class="row justify-content-center">
    <div class="col-md-6 text-center">
        <div class="card">
            <div class="card-body py-5">
                <i class="fas fa-tools text-warning" style="font-size: 4rem;"></i>
                <h1 class="display-1 text-muted">503</h1>
                <h2 class="mb-3">Service Unavailable</h2>
                <p class="text-muted mb-4">
                    <?= isset($message) ? e($message) : 'The service is temporarily unavailable. Please try again later.' ?>
                </p>
                <a href="<?= url('/') ?>" class="btn btn-primary">
                    <i class="fas fa-home me-2"></i>Go Home
                </a>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include 'layouts/app.php';
?>