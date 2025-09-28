<?php include '../header.php'; ?>

//views/user/conversation.php

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Conversation with <?php echo $otherUser['username']; ?></h2>
    <a href="/messages" class="btn btn-outline-primary">
        <i class="fas fa-arrow-left"></i> Back to Messages
    </a>
</div>

<div class="card mb-4">
    <div class="card-header">
        <div class="d-flex align-items-center">
            <img src="<?php echo get_gravatar($otherUser['email'], 40); ?>" class="rounded-circle me-2" alt="Avatar">
            <div>
                <h5 class="mb-0"><?php echo $otherUser['username']; ?></h5>
                <small class="text-muted">
                    <?php echo $otherUser['role'] === 'admin' ? 'Administrator' : ($otherUser['role'] === 'moderator' ? 'Moderator' : 'Member'); ?>
                    <?php if ($otherUser['last_login']): ?>
                    • Last active <?php echo format_date($otherUser['last_login']); ?>
                    <?php endif; ?>
                </small>
            </div>
        </div>
    </div>
    <div class="card-body" style="max-height: 400px; overflow-y: auto;" id="messageContainer">
        <?php if (!empty($messages)): ?>
        <div class="message-list">
            <?php foreach ($messages as $message): ?>
            <div class="message-item mb-3 <?php echo $message['sender_id'] == $user['id'] ? 'message-sent' : 'message-received'; ?>">
                <div class="d-flex <?php echo $message['sender_id'] == $user['id'] ? 'justify-content-end' : ''; ?>">
                    <?php if ($message['sender_id'] != $user['id']): ?>
                    <img src="<?php echo get_gravatar($message['sender_email'], 32); ?>" class="rounded-circle me-2" alt="Avatar">
                    <?php endif; ?>
                    
                    <div class="message-content">
                        <div class="card <?php echo $message['sender_id'] == $user['id'] ? 'bg-primary text-white' : 'bg-light'; ?>">
                            <div class="card-body py-2">
                                <p class="card-text mb-0"><?php echo nl2br(htmlspecialchars($message['content'])); ?></p>
                            </div>
                            <div class="card-footer <?php echo $message['sender_id'] == $user['id'] ? 'bg-primary border-top-0' : 'bg-light'; ?> py-1 px-2">
                                <small class="<?php echo $message['sender_id'] == $user['id'] ? 'text-white-50' : 'text-muted'; ?>">
                                    <?php echo format_date($message['created_at']); ?>
                                    
                                    <?php if ($message['sender_id'] == $user['id']): ?>
                                    <?php if ($message['is_read']): ?>
                                    <i class="fas fa-check-double ms-1" title="Read"></i>
                                    <?php else: ?>
                                    <i class="fas fa-check ms-1" title="Sent"></i>
                                    <?php endif; ?>
                                    <?php endif; ?>
                                </small>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($message['sender_id'] == $user['id']): ?>
                    <img src="<?php echo get_gravatar($user['email'], 32); ?>" class="rounded-circle ms-2" alt="Avatar">
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="text-center py-4">
            <i class="fas fa-comments fa-3x text-muted mb-3"></i>
            <p class="text-muted">No messages yet. Start the conversation!</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form action="/messages/send" method="post" id="messageForm">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <input type="hidden" name="receiver_id" value="<?php echo $otherUser['id']; ?>">
            
            <div class="mb-3">
                <textarea class="form-control" id="messageContent" name="content" rows="3" placeholder="Type your message here..." required></textarea>
            </div>
            
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="emojiButton">
                        <i class="fas fa-smile"></i>
                    </button>
                </div>
                <button type="submit" class="btn btn-primary">Send Message</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Scroll to bottom of message container
    const messageContainer = document.getElementById('messageContainer');
    messageContainer.scrollTop = messageContainer.scrollHeight;
    
    // Handle message form submission
    document.getElementById('messageForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const messageContent = document.getElementById('messageContent');
        
        fetch('/messages/send', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reload the page to show the new message
                window.location.reload();
            } else {
                alert('Failed to send message: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while sending the message');
        });
    });
    
    // Simple emoji picker
    document.getElementById('emojiButton').addEventListener('click', function() {
        const emojis = ['😀', '😂', '😍', '🤔', '👍', '❤️', '🎉', '🔥'];
        const messageContent = document.getElementById('messageContent');
        
        const emojiPicker = document.createElement('div');
        emojiPicker.className = 'emoji-picker';
        emojiPicker.style.cssText = 'position: absolute; background: white; border: 1px solid #ddd; border-radius: 4px; padding: 8px; z-index: 1000; display: grid; grid-template-columns: repeat(4, 1fr); gap: 4px;';
        
        emojis.forEach(emoji => {
            const span = document.createElement('span');
            span.textContent = emoji;
            span.style.cssText = 'cursor: pointer; font-size: 1.2em; padding: 4px;';
            span.addEventListener('click', function() {
                messageContent.value += emoji;
                emojiPicker.remove();
            });
            emojiPicker.appendChild(span);
        });
        
        document.body.appendChild(emojiPicker);
        
        // Position picker near the button
        const rect = this.getBoundingClientRect();
        emojiPicker.style.top = (rect.bottom + window.scrollY) + 'px';
        emojiPicker.style.left = (rect.left + window.scrollX) + 'px';
        
        // Close picker when clicking outside
        const closePicker = function(e) {
            if (!emojiPicker.contains(e.target) && e.target !== this) {
                emojiPicker.remove();
                document.removeEventListener('click', closePicker);
            }
        };
        
        setTimeout(() => {
            document.addEventListener('click', closePicker);
        }, 0);
    });
});
</script>

<style>
.message-sent .message-content {
    max-width: 70%;
}

.message-received .message-content {
    max-width: 70%;
}

.message-item .card {
    border-radius: 12px;
}

.message-sent .card {
    border-bottom-right-radius: 4px;
}

.message-received .card {
    border-bottom-left-radius: 4px;
}
</style>

<?php include '../footer.php'; ?>