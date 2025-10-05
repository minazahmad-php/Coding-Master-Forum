<?php
$content = ob_get_clean();
ob_start();
?>

<div class="row justify-content-center">
    <div class="col-md-6 text-center">
        <div class="card">
            <div class="card-body py-5">
                <i class="fas fa-exclamation-triangle text-warning" style="font-size: 4rem;"></i>
                <h1 class="display-1 text-muted">422</h1>
                <h2 class="mb-3">Unprocessable Entity</h2>
                <p class="text-muted mb-4">
                    <?= isset($message) ? e($message) : 'The request was well-formed but contains semantic errors.' ?>
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