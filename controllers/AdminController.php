<?php

//controllers/AdminController.php

class AdminController {
    private $userModel;
    private $forumModel;
    private $threadModel;
    private $postModel;
    
    public function __construct() {
        Middleware::admin();
        
        $this->userModel = new User();
        $this->forumModel = new Forum();
        $this->threadModel = new Thread();
        $this->postModel = new Post();
    }
    
    public function dashboard() {
        $stats = [
            'users_count' => $this->userModel->countAll(),
            'forums_count' => $this->forumModel->countAll(),
            'threads_count' => $this->threadModel->countAll(),
            'posts_count' => $this->postModel->countAll(),
            'latest_users' => $this->userModel->findAll(5, 0),
            'latest_threads' => $this->threadModel->findAll(5, 0)
        ];
        
        include VIEWS_PATH . '/admin/dashboard.php';
    }
    
    public function users() {
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        $users = $this->userModel->findAll($limit, $offset);
        $totalUsers = $this->userModel->countAll();
        $pagination = paginate($page, $totalUsers, $limit, "/admin/users?page={page}");
        
        include VIEWS_PATH . '/admin/users.php';
    }
    
    public function editUser($id) {
        $user = $this->userModel->findById($id);
        
        if (!$user) {
            include VIEWS_PATH . '/error.php';
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = sanitize($_POST['username']);
            $email = sanitize($_POST['email']);
            $role = sanitize($_POST['role']);
            $status = sanitize($_POST['status']);
            
            $data = [
                'username' => $username,
                'email' => $email,
                'role' => $role,
                'status' => $status
            ];
            
            $success = $this->userModel->update($id, $data);
            
            if ($success) {
                $_SESSION['success'] = 'User updated successfully';
                header('Location: /admin/users');
                exit;
            } else {
                $_SESSION['error'] = 'Failed to update user';
            }
        }
        
        include VIEWS_PATH . '/admin/user_edit.php';
    }
    
    public function deleteUser($id) {
        $user = $this->userModel->findById($id);
        
        if (!$user) {
            include VIEWS_PATH . '/error.php';
            return;
        }
        
        // Prevent admin from deleting themselves
        if ($user['id'] == Auth::getUser()['id']) {
            $_SESSION['error'] = 'You cannot delete your own account';
            header('Location: /admin/users');
            exit;
        }
        
        $success = $this->userModel->delete($id);
        
        if ($success) {
            $_SESSION['success'] = 'User deleted successfully';
        } else {
            $_SESSION['error'] = 'Failed to delete user';
        }
        
        header('Location: /admin/users');
        exit;
    }
    
    public function forums() {
        $forums = $this->forumModel->findAll();
        include VIEWS_PATH . '/admin/forums.php';
    }
    
    public function createForum() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = sanitize($_POST['name']);
            $description = sanitize($_POST['description']);
            $icon = sanitize($_POST['icon']);
            
            $data = [
                'name' => $name,
                'description' => $description,
                'icon' => $icon
            ];
            
            $forumId = $this->forumModel->create($data);
            
            if ($forumId) {
                $_SESSION['success'] = 'Forum created successfully';
                header('Location: /admin/forums');
                exit;
            } else {
                $_SESSION['error'] = 'Failed to create forum';
            }
        }
        
        include VIEWS_PATH . '/admin/forum_create.php';
    }
    
    public function editForum($id) {
        $forum = $this->forumModel->findById($id);
        
        if (!$forum) {
            include VIEWS_PATH . '/error.php';
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = sanitize($_POST['name']);
            $description = sanitize($_POST['description']);
            $icon = sanitize($_POST['icon']);
            
            $data = [
                'name' => $name,
                'description' => $description,
                'icon' => $icon
            ];
            
            $success = $this->forumModel->update($id, $data);
            
            if ($success) {
                $_SESSION['success'] = 'Forum updated successfully';
                header('Location: /admin/forums');
                exit;
            } else {
                $_SESSION['error'] = 'Failed to update forum';
            }
        }
        
        include VIEWS_PATH . '/admin/forum_edit.php';
    }
    
    public function deleteForum($id) {
        $forum = $this->forumModel->findById($id);
        
        if (!$forum) {
            include VIEWS_PATH . '/error.php';
            return;
        }
        
        $success = $this->forumModel->delete($id);
        
        if ($success) {
            $_SESSION['success'] = 'Forum deleted successfully';
        } else {
            $_SESSION['error'] = 'Failed to delete forum';
        }
        
        header('Location: /admin/forums');
        exit;
    }
    
    public function threads() {
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        $threads = $this->threadModel->findAll($limit, $offset);
        $totalThreads = $this->threadModel->countAll();
        $pagination = paginate($page, $totalThreads, $limit, "/admin/threads?page={page}");
        
        include VIEWS_PATH . '/admin/threads.php';
    }
    
    public function deleteThread($id) {
        $thread = $this->threadModel->findById($id);
        
        if (!$thread) {
            include VIEWS_PATH . '/error.php';
            return;
        }
        
        $success = $this->threadModel->delete($id);
        
        if ($success) {
            $_SESSION['success'] = 'Thread deleted successfully';
        } else {
            $_SESSION['error'] = 'Failed to delete thread';
        }
        
        header('Location: /admin/threads');
        exit;
    }
    
    public function posts() {
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        $posts = $this->postModel->findAll($limit, $offset);
        $totalPosts = $this->postModel->countAll();
        $pagination = paginate($page, $totalPosts, $limit, "/admin/posts?page={page}");
        
        include VIEWS_PATH . '/admin/posts.php';
    }
    
    public function deletePost($id) {
        $post = $this->postModel->findById($id);
        
        if (!$post) {
            include VIEWS_PATH . '/error.php';
            return;
        }
        
        $success = $this->postModel->delete($id);
        
        if ($success) {
            $_SESSION['success'] = 'Post deleted successfully';
        } else {
            $_SESSION['error'] = 'Failed to delete post';
        }
        
        header('Location: /admin/posts');
        exit;
    }
    
    public function settings() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $siteName = sanitize($_POST['site_name']);
            $siteUrl = sanitize($_POST['site_url']);
            $defaultLang = sanitize($_POST['default_lang']);
            
            // Update settings in database
            $db = Database::getInstance();
            
            $db->update('settings', ['value' => $siteName], 'name = :name', ['name' => 'site_name']);
            $db->update('settings', ['value' => $siteUrl], 'name = :name', ['name' => 'site_url']);
            $db->update('settings', ['value' => $defaultLang], 'name = :name', ['name' => 'default_lang']);
            
            $_SESSION['success'] = 'Settings updated successfully';
            header('Location: /admin/settings');
            exit;
        }
        
        // Get current settings
        $db = Database::getInstance();
        $settings = $db->fetchAll("SELECT * FROM settings");
        
        $settingsMap = [];
        foreach ($settings as $setting) {
            $settingsMap[$setting['name']] = $setting['value'];
        }
        
        include VIEWS_PATH . '/admin/settings.php';
    }
}
?>