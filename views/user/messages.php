<?php include '../header.php'; ?>

//views/user/messages.php

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Messages</h2>
    <a href="/messages/compose" class="btn btn-primary">
        <i class="fas fa-plus"></i> Compose
    </a>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Conversations</h5>
            </div>
            <div class="list-group list-group-flush">
                <?php if (!empty($conversations)): ?>
                <?php foreach ($conversations as $conv): ?>
                <a href="/messages/conversation/<?php echo $conv['other_user_id']; ?>" 
                   class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <img src="<?php echo get_gravatar($conv['other_user_email'], 40); ?>" class="rounded-circle me-2" alt="Avatar">
                        <div>
                            <h6 class="mb-0"><?php echo $conv['other_username']; ?></h6>
                            <small class="text-muted"><?php echo truncate($conv['content'], 30); ?></small>
                        </div>
                    </div>
                    <?php if ($conv['unread_count'] > 0): ?>
                    <span class="badge bg-primary rounded-pill"><?php echo $conv['unread_count']; ?></span>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
                <?php else: ?>
                <div class="list-group-item text-center text-muted py-4">
                    <i class="fas fa-comments fa-2x mb-2"></i>
                    <p>No conversations yet</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                <h4>Select a conversation</h4>
                <p class="text-muted">Choose a conversation from the list to start messaging</p>
            </div>
        </div>
    </div>
</div>

<?php include '../footer.php'; ?>