<?php

//core/Auth.php

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function register($username, $email, $password, $data = []) {
        // Check if user already exists
        if ($this->findUserByUsername($username)) {
            return ['success' => false, 'message' => 'Username already exists'];
        }
        
        if ($this->findUserByEmail($email)) {
            return ['success' => false, 'message' => 'Email already exists'];
        }
        
        // Hash password
        $hash = password_hash($password, PASSWORD_BCRYPT);
        
        // Prepare user data
        $userData = [
            'username' => $username,
            'email' => $email,
            'password' => $hash,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // Merge additional data
        $userData = array_merge($userData, $data);
        
        // Insert user
        try {
            $userId = $this->db->insert('users', $userData);
            
            // Log the user in automatically
            $user = $this->findUserById($userId);
            unset($user['password']);
            $_SESSION['user'] = $user;
            
            return ['success' => true, 'user' => $user];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
        }
    }
    
    public function login($identifier, $password) {
        // Find user by username or email
        $user = $this->findUserByUsernameOrEmail($identifier);
        
        if (!$user) {
            return ['success' => false, 'message' => 'User not found'];
        }
        
        // Verify password
        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'Invalid password'];
        }
        
        // Update last login
        $this->updateLastLogin($user['id']);
        
        // Set session
        unset($user['password']);
        $_SESSION['user'] = $user;
        
        return ['success' => true, 'user' => $user];
    }
    
    public function logout() {
        unset($_SESSION['user']);
        session_destroy();
        return true;
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user']);
    }
    
    public function getUser() {
        return $_SESSION['user'] ?? null;
    }
    
    public function isAdmin() {
        return $this->isLoggedIn() && $_SESSION['user']['role'] === 'admin';
    }
    
    public function isModerator() {
        return $this->isLoggedIn() && ($_SESSION['user']['role'] === 'moderator' || $this->isAdmin());
    }
    
    private function findUserByUsername($username) {
        return $this->db->fetch("SELECT * FROM users WHERE username = :username", ['username' => $username]);
    }
    
    private function findUserByEmail($email) {
        return $this->db->fetch("SELECT * FROM users WHERE email = :email", ['email' => $email]);
    }
    
    private function findUserByUsernameOrEmail($identifier) {
        return $this->db->fetch(
            "SELECT * FROM users WHERE username = :identifier OR email = :identifier", 
            ['identifier' => $identifier]
        );
    }
    
    private function findUserById($id) {
        return $this->db->fetch("SELECT * FROM users WHERE id = :id", ['id' => $id]);
    }
    
    private function updateLastLogin($userId) {
        $this->db->update(
            'users', 
            ['last_login' => date('Y-m-d H:i:s'), 'login_ip' => $_SERVER['REMOTE_ADDR']], 
            'id = :id', 
            ['id' => $userId]
        );
    }
}
?>