<?php

//controllers/MessageController.php

class MessageController {
    private $messageModel;
    private $userModel;
    
    public function __construct() {
        $this->messageModel = new Message();
        $this->userModel = new User();
        Middleware::auth();
    }
    
    public function index() {
        $user = Auth::getUser();
        $conversations = $this->messageModel->getConversations($user['id']);
        
        include VIEWS_PATH . '/user/messages.php';
    }
    
    public function conversation($userId) {
        $user = Auth::getUser();
        $otherUser = $this->userModel->findById($userId);
        
        if (!$otherUser) {
            include VIEWS_PATH . '/error.php';
            return;
        }
        
        // Mark messages as read
        $this->messageModel->markConversationAsRead($user['id'], $userId);
        
        $messages = $this->messageModel->findByUsers($user['id'], $userId);
        
        include VIEWS_PATH . '/user/conversation.php';
    }
    
    public function send() {
        $user = Auth::getUser();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $receiverId = (int)$_POST['receiver_id'];
            $content = sanitize($_POST['content']);
            
            if (empty($content)) {
                $_SESSION['error'] = 'Message content is required';
                header('Location: ' . $_SERVER['HTTP_REFERER']);
                exit;
            }
            
            $messageId = $this->messageModel->create([
                'sender_id' => $user['id'],
                'receiver_id' => $receiverId,
                'content' => $content
            ]);
            
            if ($messageId) {
                $_SESSION['success'] = 'Message sent successfully';
            } else {
                $_SESSION['error'] = 'Failed to send message';
            }
            
            header('Location: /messages/conversation/' . $receiverId);
            exit;
        }
    }
    
    public function delete($messageId) {
        $user = Auth::getUser();
        $message = $this->messageModel->findById($messageId);
        
        if (!$message) {
            include VIEWS_PATH . '/error.php';
            return;
        }
        
        // Check if user owns the message
        if ($message['sender_id'] !== $user['id'] && $message['receiver_id'] !== $user['id']) {
            $_SESSION['error'] = 'You do not have permission to delete this message';
            header('Location: /messages');
            exit;
        }
        
        $success = $this->messageModel->delete($messageId);
        
        if ($success) {
            $_SESSION['success'] = 'Message deleted successfully';
        } else {
            $_SESSION['error'] = 'Failed to delete message';
        }
        
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }
}
?>