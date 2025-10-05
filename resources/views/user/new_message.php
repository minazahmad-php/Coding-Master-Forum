<?php
$content = ob_get_clean();
ob_start();
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h1 class="h4 mb-0">Send New Message</h1>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= url('/new-message') ?>">
                    <?= csrf_field() ?>
                    
                    <div class="mb-3">
                        <label for="recipient_id" class="form-label">Recipient</label>
                        <select class="form-select" id="recipient_id" name="recipient_id" required>
                            <option value="">Select a user</option>
                            <!-- This would be populated with users from the database -->
                            <option value="1">John Doe</option>
                            <option value="2">Jane Smith</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="subject" class="form-label">Subject</label>
                        <input type="text" class="form-control" id="subject" name="subject" 
                               value="<?= old('subject') ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="content" class="form-label">Message</label>
                        <textarea class="form-control" id="content" name="content" rows="8" 
                                  placeholder="Write your message here..." required><?= old('content') ?></textarea>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="<?= url('/messages') ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Back to Messages
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-1"></i>Send Message
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