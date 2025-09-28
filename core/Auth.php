<?php
declare(strict_types=1);

namespace Core;

use Models\User;

class Auth {
    private static ?User $currentUser = null;
    private static Database $db;
    
    public static function init(): void {
        self::$db = Database::getInstance();
    }
    
    public static function login(string $identifier, string $password, bool $remember = false): array {
        self::init();
        
        // Rate limiting check
        if (!self::checkRateLimit()) {
            return [
                'success' => false,
                'message' => 'Too many login attempts. Please try again later.'
            ];
        }
        
        // Find user by email or username
        $user = self::$db->fetch(
            "SELECT * FROM users WHERE (email = :identifier OR username = :identifier) AND status = 'active'",
            ['identifier' => $identifier]
        );
        
        if (!$user) {
            self::recordFailedAttempt();
            return [
                'success' => false,
                'message' => 'Invalid credentials'
            ];
        }
        
        // Verify password
        if (!password_verify($password, $user['password'])) {
            self::recordFailedAttempt();
            return [
                'success' => false,
                'message' => 'Invalid credentials'
            ];
        }
        
        // Check if account is locked
        if (self::isAccountLocked($user['id'])) {
            return [
                'success' => false,
                'message' => 'Account is temporarily locked due to too many failed attempts'
            ];
        }
        
        // Update last login
        self::$db->update('users', [
            'last_login' => date('Y-m-d H:i:s'),
            'login_count' => $user['login_count'] + 1
        ], 'id = :id', ['id' => $user['id']]);
        
        // Clear failed attempts
        self::clearFailedAttempts($user['id']);
        
        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['login_time'] = time();
        
        // Remember me functionality
        if ($remember) {
            self::setRememberToken($user['id']);
        }
        
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        return [
            'success' => true,
            'message' => 'Login successful',
            'user' => $user
        ];
    }
    
    public static function register(array $data): array {
        self::init();
        
        // Validate input
        $validation = self::validateRegistration($data);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'message' => $validation['message']
            ];
        }
        
        // Check if user already exists
        if (self::$db->exists('users', 'email = :email OR username = :username', [
            'email' => $data['email'],
            'username' => $data['username']
        ])) {
            return [
                'success' => false,
                'message' => 'User already exists with this email or username'
            ];
        }
        
        // Hash password
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Create user
        $userId = self::$db->insert('users', [
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => $hashedPassword,
            'full_name' => $data['full_name'] ?? null,
            'role' => 'user',
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'email_verified' => 0
        ]);
        
        if ($userId) {
            // Auto login after registration
            $_SESSION['user_id'] = $userId;
            $_SESSION['username'] = $data['username'];
            $_SESSION['role'] = 'user';
            $_SESSION['login_time'] = time();
            
            return [
                'success' => true,
                'message' => 'Registration successful',
                'user_id' => $userId
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Registration failed'
        ];
    }
    
    public static function logout(): void {
        // Clear remember token
        if (isset($_SESSION['user_id'])) {
            self::clearRememberToken($_SESSION['user_id']);
        }
        
        // Destroy session
        session_destroy();
        
        // Clear remember cookie
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/');
        }
    }
    
    public static function isLoggedIn(): bool {
        // Check session
        if (isset($_SESSION['user_id'])) {
            return true;
        }
        
        // Check remember token
        if (isset($_COOKIE['remember_token'])) {
            return self::validateRememberToken($_COOKIE['remember_token']);
        }
        
        return false;
    }
    
    public static function isAdmin(): bool {
        return self::isLoggedIn() && ($_SESSION['role'] ?? '') === 'admin';
    }
    
    public static function isModerator(): bool {
        return self::isLoggedIn() && in_array($_SESSION['role'] ?? '', ['admin', 'moderator']);
    }
    
    public static function getCurrentUser(): ?User {
        if (self::$currentUser === null && self::isLoggedIn()) {
            self::$currentUser = new User();
            self::$currentUser = self::$currentUser->findById($_SESSION['user_id']);
        }
        
        return self::$currentUser;
    }
    
    public static function getUserId(): ?int {
        return $_SESSION['user_id'] ?? null;
    }
    
    public static function getUsername(): ?string {
        return $_SESSION['username'] ?? null;
    }
    
    public static function getRole(): ?string {
        return $_SESSION['role'] ?? null;
    }
    
    public static function requireLogin(): void {
        if (!self::isLoggedIn()) {
            header('Location: /login');
            exit;
        }
    }
    
    public static function requireAdmin(): void {
        self::requireLogin();
        if (!self::isAdmin()) {
            header('Location: /');
            exit;
        }
    }
    
    public static function requireModerator(): void {
        self::requireLogin();
        if (!self::isModerator()) {
            header('Location: /');
            exit;
        }
    }
    
    public static function changePassword(int $userId, string $currentPassword, string $newPassword): array {
        self::init();
        
        $user = self::$db->fetch('SELECT password FROM users WHERE id = :id', ['id' => $userId]);
        
        if (!$user || !password_verify($currentPassword, $user['password'])) {
            return [
                'success' => false,
                'message' => 'Current password is incorrect'
            ];
        }
        
        if (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
            return [
                'success' => false,
                'message' => 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long'
            ];
        }
        
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $updated = self::$db->update('users', [
            'password' => $hashedPassword,
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = :id', ['id' => $userId]);
        
        if ($updated) {
            return [
                'success' => true,
                'message' => 'Password changed successfully'
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Failed to change password'
        ];
    }
    
    public static function resetPassword(string $email): array {
        self::init();
        
        $user = self::$db->fetch('SELECT id, username FROM users WHERE email = :email', ['email' => $email]);
        
        if (!$user) {
            return [
                'success' => false,
                'message' => 'Email not found'
            ];
        }
        
        // Generate reset token
        $resetToken = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hour
        
        // Store reset token
        self::$db->insert('password_resets', [
            'user_id' => $user['id'],
            'token' => $resetToken,
            'expires_at' => $expiresAt,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        // TODO: Send email with reset link
        // For now, just return the token (in production, send via email)
        
        return [
            'success' => true,
            'message' => 'Password reset instructions sent to your email',
            'token' => $resetToken // Remove this in production
        ];
    }
    
    private static function validateRegistration(array $data): array {
        $errors = [];
        
        if (empty($data['username']) || strlen($data['username']) < 3) {
            $errors[] = 'Username must be at least 3 characters long';
        }
        
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email address';
        }
        
        if (empty($data['password']) || strlen($data['password']) < PASSWORD_MIN_LENGTH) {
            $errors[] = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long';
        }
        
        if ($data['password'] !== ($data['confirm_password'] ?? '')) {
            $errors[] = 'Passwords do not match';
        }
        
        return [
            'valid' => empty($errors),
            'message' => implode(', ', $errors)
        ];
    }
    
    private static function checkRateLimit(): bool {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $key = "login_attempts_{$ip}";
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 0, 'last_attempt' => time()];
        }
        
        $attempts = $_SESSION[$key];
        
        // Reset if enough time has passed
        if (time() - $attempts['last_attempt'] > LOGIN_LOCKOUT_TIME) {
            $_SESSION[$key] = ['count' => 0, 'last_attempt' => time()];
            return true;
        }
        
        return $attempts['count'] < MAX_LOGIN_ATTEMPTS;
    }
    
    private static function recordFailedAttempt(): void {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $key = "login_attempts_{$ip}";
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 0, 'last_attempt' => time()];
        }
        
        $_SESSION[$key]['count']++;
        $_SESSION[$key]['last_attempt'] = time();
    }
    
    private static function isAccountLocked(int $userId): bool {
        $attempts = self::$db->fetch(
            "SELECT COUNT(*) as count FROM login_attempts WHERE user_id = :user_id AND created_at > :time",
            [
                'user_id' => $userId,
                'time' => date('Y-m-d H:i:s', time() - LOGIN_LOCKOUT_TIME)
            ]
        );
        
        return ($attempts['count'] ?? 0) >= MAX_LOGIN_ATTEMPTS;
    }
    
    private static function clearFailedAttempts(int $userId): void {
        self::$db->delete('login_attempts', 'user_id = :user_id', ['user_id' => $userId]);
    }
    
    private static function setRememberToken(int $userId): void {
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + (30 * 24 * 3600)); // 30 days
        
        self::$db->insert('remember_tokens', [
            'user_id' => $userId,
            'token' => hash('sha256', $token),
            'expires_at' => $expiresAt,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        setcookie('remember_token', $token, time() + (30 * 24 * 3600), '/', '', true, true);
    }
    
    private static function validateRememberToken(string $token): bool {
        $hashedToken = hash('sha256', $token);
        
        $rememberToken = self::$db->fetch(
            "SELECT rt.*, u.status FROM remember_tokens rt 
             JOIN users u ON rt.user_id = u.id 
             WHERE rt.token = :token AND rt.expires_at > :now AND u.status = 'active'",
            [
                'token' => $hashedToken,
                'now' => date('Y-m-d H:i:s')
            ]
        );
        
        if ($rememberToken) {
            // Set session
            $_SESSION['user_id'] = $rememberToken['user_id'];
            $_SESSION['login_time'] = time();
            
            // Get user details
            $user = self::$db->fetch('SELECT username, role FROM users WHERE id = :id', [
                'id' => $rememberToken['user_id']
            ]);
            
            if ($user) {
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
            }
            
            return true;
        }
        
        return false;
    }
    
    private static function clearRememberToken(int $userId): void {
        self::$db->delete('remember_tokens', 'user_id = :user_id', ['user_id' => $userId]);
    }
}
?>