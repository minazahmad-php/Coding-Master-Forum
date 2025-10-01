<?php

//controllers/ForumController.php

class ForumController {
    private $forumModel;
    private $threadModel;
    
    public function __construct() {
        $this->forumModel = new Forum();
        $this->threadModel = new Thread();
        Middleware::auth();
    }
    
    public function createThread($forumSlug) {
        $forum = $this->forumModel->findBySlug($forumSlug);
        
        if (!$forum) {
            include VIEWS_PATH . '/error.php';
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = sanitize($_POST['title']);
            $content = sanitize($_POST['content']);
            
            $errors = [];
            
            if (empty($title)) {
                $errors[] = 'Thread title is required';
            }
            
            if (empty($content)) {
                $errors[] = 'Thread content is required';
            }
            
            if (empty($errors)) {
                $user = Auth::getUser();
                
                $threadId = $this->threadModel->create([
                    'forum_id' => $forum['id'],
                    'user_id' => $user['id'],
                    'title' => $title,
                    'content' => $content
                ]);
                
                if ($threadId) {
                    header("Location: /thread/$threadId");
                    exit;
                } else {
                    $errors[] = 'Failed to create thread';
                }
            }
            
            include VIEWS_PATH . '/thread_create.php';
        } else {
            include VIEWS_PATH . '/thread_create.php';
        }
    }
    
    public function createPost($threadId) {
        $thread = $this->threadModel->findById($threadId);
        
        if (!$thread) {
            include VIEWS_PATH . '/error.php';
            return;
        }
        
        if ($thread['is_locked']) {
            $_SESSION['error'] = 'This thread is locked';
            header("Location: /thread/$threadId");
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $content = sanitize($_POST['content']);
            
            if (empty($content)) {
                $_SESSION['error'] = 'Post content is required';
                header("Location: /thread/$threadId");
                exit;
            }
            
            $user = Auth::getUser();
            
            $postId = $this->postModel->create([
                'thread_id' => $threadId,
                'user_id' => $user['id'],
                'content' => $content
            ]);
            
            if ($postId) {
                header("Location: /thread/$threadId#post-$postId");
                exit;
            } else {
                $_SESSION['error'] = 'Failed to create post';
                header("Location: /thread/$threadId");
                exit;
            }
        }
    }
    
    public function editThread($threadId) {
        $thread = $this->threadModel->findById($threadId);
        
        if (!$thread) {
            include VIEWS_PATH . '/error.php';
            return;
        }
        
        $user = Auth::getUser();
        
        // Check if user owns the thread or is admin/moderator
        if ($thread['user_id'] !== $user['id'] && !Auth::isModerator()) {
            $_SESSION['error'] = 'You do not have permission to edit this thread';
            header("Location: /thread/$threadId");
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = sanitize($_POST['title']);
            $content = sanitize($_POST['content']);
            
            $errors = [];
            
            if (empty($title)) {
                $errors[] = 'Thread title is required';
            }
            
            if (empty($content)) {
                $errors[] = 'Thread content is required';
            }
            
            if (empty($errors)) {
                $success = $this->threadModel->update($threadId, [
                    'title' => $title,
                    'content' => $content
                ]);
                
                if ($success) {
                    header("Location: /thread/$threadId");
                    exit;
                } else {
                    $errors[] = 'Failed to update thread';
                }
            }
            
            include VIEWS_PATH . '/thread_edit.php';
        } else {
            include VIEWS_PATH . '/thread_edit.php';
        }
    }
    
    public function editPost($postId) {
        $post = $this->postModel->findById($postId);
        
        if (!$post) {
            include VIEWS_PATH . '/error.php';
            return;
        }
        
        $user = Auth::getUser();
        
        // Check if user owns the post or is admin/moderator
        if ($post['user_id'] !== $user['id'] && !Auth::isModerator()) {
            $_SESSION['error'] = 'You do not have permission to edit this post';
            header("Location: /thread/{$post['thread_id']}");
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $content = sanitize($_POST['content']);
            
            if (empty($content)) {
                $_SESSION['error'] = 'Post content is required';
                header("Location: /thread/{$post['thread_id']}");
                exit;
            }
            
            $success = $this->postModel->update($postId, [
                'content' => $content
            ]);
            
            if ($success) {
                header("Location: /thread/{$post['thread_id']}#post-$postId");
                exit;
            } else {
                $_SESSION['error'] = 'Failed to update post';
                header("Location: /thread/{$post['thread_id']}");
                exit;
            }
        } else {
            include VIEWS_PATH . '/post_edit.php';
        }
    }
    
    public function deleteThread($threadId) {
        $thread = $this->threadModel->findById($threadId);
        
        if (!$thread) {
            include VIEWS_PATH . '/error.php';
            return;
        }
        
        $user = Auth::getUser();
        
        // Check if user owns the thread or is admin/moderator
        if ($thread['user_id'] !== $user['id'] && !Auth::isModerator()) {
            $_SESSION['error'] = 'You do not have permission to delete this thread';
            header("Location: /thread/$threadId");
            exit;
        }
        
        $forumSlug = $thread['forum_slug'];
        $success = $this->threadModel->delete($threadId);
        
        if ($success) {
            header("Location: /forum/$forumSlug");
            exit;
        } else {
            $_SESSION['error'] = 'Failed to delete thread';
            header("Location: /thread/$threadId");
            exit;
        }
    }
    
    public function deletePost($postId) {
        $post = $this->postModel->findById($postId);
        
        if (!$post) {
            include VIEWS_PATH . '/error.php';
            return;
        }
        
        $user = Auth::getUser();
        
        // Check if user owns the post or is admin/moderator
        if ($post['user_id'] !== $user['id'] && !Auth::isModerator()) {
            $_SESSION['error'] = 'You do not have permission to delete this post';
            header("Location: /thread/{$post['thread_id']}");
            exit;
        }
        
        $threadId = $post['thread_id'];
        $success = $this->postModel->delete($postId);
        
        if ($success) {
            header("Location: /thread/$threadId");
            exit;
        } else {
            $_SESSION['error'] = 'Failed to delete post';
            header("Location: /thread/$threadId");
            exit;
        }
    }
    
    public function toggleThreadLock($threadId) {
        Middleware::moderator();
        
        $thread = $this->threadModel->findById($threadId);
        
        if (!$thread) {
            include VIEWS_PATH . '/error.php';
            return;
        }
        
        $success = $this->threadModel->toggleLock($threadId);
        
        if ($success) {
            header("Location: /thread/$threadId");
            exit;
        } else {
            $_SESSION['error'] = 'Failed to toggle thread lock';
            header("Location: /thread/$threadId");
            exit;
        }
    }
    
    public function toggleThreadPin($threadId) {
        Middleware::moderator();
        
        $thread = $this->threadModel->findById($threadId);
        
        if (!$thread) {
            include VIEWS_PATH . '/error.php';
            return;
        }
        
        $success = $this->threadModel->togglePin($threadId);
        
        if ($success) {
            header("Location: /thread/$threadId");
            exit;
        } else {
            $_SESSION['error'] = 'Failed to toggle thread pin';
            header("Location: /thread/$threadId");
            exit;
        }
    }
}
?>