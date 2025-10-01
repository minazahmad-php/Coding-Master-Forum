<?php

//controllers/AuthController.php

class AuthController {
    private $auth;
    private $userModel;
    
    public function __construct() {
        $this->auth = new Auth();
        $this->userModel = new User();
    }
    
    public function login() {
        if ($this->auth->isLoggedIn()) {
            header('Location: /');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $identifier = sanitize($_POST['identifier']);
            $password = $_POST['password'];
            $remember = isset($_POST['remember']);
            
            $result = $this->auth->login($identifier, $password);
            
            if ($result['success']) {
                header('Location: /');
                exit;
            } else {
                $error = $result['message'];
                include VIEWS_PATH . '/login.php';
            }
        } else {
            include VIEWS_PATH . '/login.php';
        }
    }
    
    public function register() {
        if ($this->auth->isLoggedIn()) {
            header('Location: /');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = sanitize($_POST['username']);
            $email = sanitize($_POST['email']);
            $password = $_POST['password'];
            $confirmPassword = $_POST['confirm_password'];
            
            // Validate input
            $errors = [];
            
            if (empty($username)) {
                $errors[] = 'Username is required';
            } elseif (strlen($username) < 3) {
                $errors[] = 'Username must be at least 3 characters';
            }
            
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Valid email is required';
            }
            
            if (empty($password) || strlen($password) < 6) {
                $errors[] = 'Password must be at least 6 characters';
            }
            
            if ($password !== $confirmPassword) {
                $errors[] = 'Passwords do not match';
            }
            
            if (empty($errors)) {
                $result = $this->auth->register($username, $email, $password);
                
                if ($result['success']) {
                    header('Location: /');
                    exit;
                } else {
                    $errors[] = $result['message'];
                }
            }
            
            include VIEWS_PATH . '/register.php';
        } else {
            include VIEWS_PATH . '/register.php';
        }
    }
    
    public function logout() {
        $this->auth->logout();
        header('Location: /');
        exit;
    }
    
    public function profile($username) {
        $user = $this->userModel->findByUsername($username);
        
        if (!$user) {
            include VIEWS_PATH . '/error.php';
            return;
        }
        
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;
        
        $threads = $this->threadModel->findByUser($user['id'], $limit, $offset);
        $posts = $this->postModel->findByUser($user['id'], $limit, $offset);
        
        include VIEWS_PATH . '/profile.php';
    }
}
?>