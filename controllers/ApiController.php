<?php

//controllers/ApiController.php

class ApiController {
    private $userModel;
    private $threadModel;
    private $postModel;
    private $notificationModel;
    private $messageModel;
    
    public function __construct() {
        $this->userModel = new User();
        $this->threadModel = new Thread();
        $this->postModel = new Post();
        $this->notificationModel = new Notification();
        $this->messageModel = new Message();
    }
    
    public function search() {
        header('Content-Type: application/json');
        
        $query = isset($_GET['q']) ? sanitize($_GET['q']) : '';
        $type = isset($_GET['type']) ? sanitize($_GET['type']) : 'all';
        
        if (empty($query)) {
            echo json_encode(['success' => false, 'message' => 'Search query is required']);
            return;
        }
        
        $results = [];
        
        if ($type === 'all' || $type === 'threads') {
            $threads = $this->threadModel->search($query, 5);
            foreach ($threads as $thread) {
                $results[] = [
                    'type' => 'thread',
                    'title' => $thread['title'],
                    'content' => truncate($thread['content'], 100),
                    'url' => '/thread/' . $thread['id'],
                    'author' => $thread['username'],
                    'date' => format_date($thread['created_at'])
                ];
            }
        }
        
        if ($type === 'all' || $type === 'users') {
            $users = $this->userModel->search($query, 5);
            foreach ($users as $user) {
                $results[] = [
                    'type' => 'user',
                    'title' => $user['username'],
                    'content' => $user['email'],
                    'url' => '/user/' . $user['username'],
                    'author' => null,
                    'date' => format_date($user['created_at'])
                ];
            }
        }
        
        echo json_encode(['success' => true, 'results' => $results]);
    }
    
    public function notifications() {
        header('Content-Type: application/json');
        Middleware::auth();
        
        $user = Auth::getUser();
        $notifications = $this->notificationModel->findByUser($user['id'], 10);
        
        echo json_encode(['success' => true, 'notifications' => $notifications]);
    }
    
    public function markNotificationRead($id) {
        header('Content-Type: application/json');
        Middleware::auth();
        
        $success = $this->notificationModel->markAsRead($id);
        
        if ($success) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to mark notification as read']);
        }
    }
    
    public function unreadNotificationsCount() {
        header('Content-Type: application/json');
        Middleware::auth();
        
        $user = Auth::getUser();
        $count = $this->notificationModel->getUnreadCount($user['id']);
        
        echo json_encode(['success' => true, 'count' => $count]);
    }
    
    public function userAutocomplete() {
        header('Content-Type: application/json');
        
        $query = isset($_GET['q']) ? sanitize($_GET['q']) : '';
        
        if (empty($query)) {
            echo json_encode(['success' => true, 'users' => []]);
            return;
        }
        
        $users = $this->userModel->search($query, 10);
        $result = [];
        
        foreach ($users as $user) {
            $result[] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'avatar' => get_gravatar($user['email'], 40)
            ];
        }
        
        echo json_encode(['success' => true, 'users' => $result]);
    }
    
    public function likePost($postId) {
        header('Content-Type: application/json');
        Middleware::auth();
        
        $user = Auth::getUser();
        $post = $this->postModel->findById($postId);
        
        if (!$post) {
            echo json_encode(['success' => false, 'message' => 'Post not found']);
            return;
        }
        
        // Check if user already liked this post
        $db = Database::getInstance();
        $existingLike = $db->fetch(
            "SELECT * FROM reactions WHERE user_id = :user_id AND target_type = 'post' AND target_id = :target_id",
            ['user_id' => $user['id'], 'target_id' => $postId]
        );
        
        if ($existingLike) {
            // Unlike the post
            $db->delete('reactions', 'id = :id', ['id' => $existingLike['id']]);
            
            // Update post likes count
            $this->postModel->update($postId, ['likes_count' => max(0, $post['likes_count'] - 1)]);
            
            echo json_encode(['success' => true, 'action' => 'unlike', 'count' => max(0, $post['likes_count'] - 1)]);
        } else {
            // Like the post
            $db->insert('reactions', [
                'user_id' => $user['id'],
                'target_type' => 'post',
                'target_id' => $postId,
                'reaction_type' => 'like'
            ]);
            
            // Update post likes count
            $this->postModel->update($postId, ['likes_count' => $post['likes_count'] + 1]);
            
            // Create notification for post owner
            if ($post['user_id'] != $user['id']) {
                $this->notificationModel->create([
                    'user_id' => $post['user_id'],
                    'message' => $user['username'] . ' liked your post',
                    'link' => '/thread/' . $post['thread_id'] . '#post-' . $postId
                ]);
            }
            
            echo json_encode(['success' => true, 'action' => 'like', 'count' => $post['likes_count'] + 1]);
        }
    }
    
    public function getThreadReplies($threadId) {
        header('Content-Type: application/json');
        
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;
        
        $posts = $this->postModel->findByThread($threadId, $limit, $offset);
        $totalPosts = $this->postModel->countByThread($threadId);
        
        echo json_encode([
            'success' => true,
            'posts' => $posts,
            'pagination' => [
                'current' => $page,
                'pages' => ceil($totalPosts / $limit),
                'hasMore' => ($page * $limit) < $totalPosts
            ]
        ]);
    }
    
    public function uploadFile() {
        header('Content-Type: application/json');
        Middleware::auth();
        
        if (!isset($_FILES['file'])) {
            echo json_encode(['success' => false, 'message' => 'No file uploaded']);
            return;
        }
        
        $file = $_FILES['file'];
        $user = Auth::getUser();
        
        // Check for errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'File upload error']);
            return;
        }
        
        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'text/plain'];
        if (!in_array($file['type'], $allowedTypes)) {
            echo json_encode(['success' => false, 'message' => 'File type not allowed']);
            return;
        }
        
        // Validate file size (max 5MB)
        $maxSize = 5 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            echo json_encode(['success' => false, 'message' => 'File size too large (max 5MB)']);
            return;
        }
        
        // Create uploads directory if it doesn't exist
        $uploadDir = UPLOADS_PATH . '/attachments/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . $user['id'] . '.' . $extension;
        $destination = $uploadDir . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            // Save to database
            $db = Database::getInstance();
            $attachmentId = $db->insert('attachments', [
                'user_id' => $user['id'],
                'file_path' => $filename,
                'file_type' => $file['type'],
                'file_size' => $file['size']
            ]);
            
            echo json_encode([
                'success' => true,
                'file' => [
                    'id' => $attachmentId,
                    'name' => $file['name'],
                    'url' => '/uploads/attachments/' . $filename,
                    'size' => $file['size']
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
        }
    }
    
    public function getOnlineUsers() {
        header('Content-Type: application/json');
        
        // Get users active in the last 5 minutes
        $db = Database::getInstance();
        $onlineUsers = $db->fetchAll(
            "SELECT u.id, u.username, u.avatar, u.role, s.last_active 
             FROM users u 
             INNER JOIN sessions s ON u.id = s.user_id 
             WHERE s.last_active > datetime('now', '-5 minutes') 
             ORDER BY s.last_active DESC 
             LIMIT 20"
        );
        
        echo json_encode(['success' => true, 'users' => $onlineUsers]);
    }
    
    public function getForumStats() {
        header('Content-Type: application/json');
        
        $stats = [
            'users_count' => $this->userModel->countAll(),
            'threads_count' => $this->threadModel->countAll(),
            'posts_count' => $this->postModel->countAll(),
            'online_users' => count($this->getOnlineUsers())
        ];
        
        echo json_encode(['success' => true, 'stats' => $stats]);
    }
}
?>