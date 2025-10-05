<?php
$content = ob_get_clean();
ob_start();
?>

<div class="row">
    <div class="col-12">
        <h1 class="h3 mb-4">Messages</h1>
        
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Inbox</h6>
                    <a href="<?= url('/new-message') ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus me-1"></i>New Message
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="text-center py-5">
                    <i class="fas fa-envelope fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">No Messages</h4>
                    <p class="text-muted">You don't have any messages yet.</p>
                    <a href="<?= url('/new-message') ?>" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Send First Message
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include 'layouts/app.php';
?>