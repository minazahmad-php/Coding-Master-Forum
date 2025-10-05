<?php

namespace App\Controllers;

use App\Models\User;

/**
 * Authentication Controller
 * Handles user login, registration, and authentication
 */
class AuthController extends BaseController
{
    /**
     * Show login form
     */
    public function login()
    {
        if ($this->isAuthenticated()) {
            redirect('/');
        }
        
        echo $this->view->render('auth/login');
    }

    /**
     * Handle login form submission
     */
    public function handleLogin()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/login');
        }
        
        $this->validateCsrf();
        
        $email = $this->sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);
        
        $errors = $this->validateRequired(['email', 'password'], $_POST);
        
        if (!$this->validateEmail($email)) {
            $errors[] = 'Please enter a valid email address.';
        }
        
        if (empty($errors)) {
            $userModel = new User();
            $user = $userModel->findByEmail($email);
            
            if ($user && $userModel->verifyPassword($password, $user['password'])) {
                if ($user['status'] === 'banned') {
                    $this->redirectWithMessage('/login', 'error', 'Your account has been banned.');
                    return;
                }
                
                // Login successful
                $this->session->set('user_id', $user['id']);
                $this->session->set('user_role', $user['role']);
                
                // Update last login
                $userModel->updateLastLogin($user['id']);
                
                // Set remember me cookie
                if ($remember) {
                    $this->setRememberCookie($user['id']);
                }
                
                $this->logActivity('User logged in', ['user_id' => $user['id']]);
                
                // Redirect to intended page
                $redirectUrl = $this->session->get('redirect_after_login', '/');
                $this->session->remove('redirect_after_login');
                
                $this->redirectWithMessage($redirectUrl, 'success', 'Welcome back!');
            } else {
                $this->redirectWithMessage('/login', 'error', 'Invalid email or password.');
            }
        } else {
            $this->redirectWithMessage('/login', 'error', implode('<br>', $errors));
        }
    }

    /**
     * Show registration form
     */
    public function register()
    {
        if ($this->isAuthenticated()) {
            redirect('/');
        }
        
        echo $this->view->render('auth/register');
    }

    /**
     * Handle registration form submission
     */
    public function handleRegister()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/register');
        }
        
        $this->validateCsrf();
        
        $username = $this->sanitize($_POST['username'] ?? '');
        $email = $this->sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $displayName = $this->sanitize($_POST['display_name'] ?? '');
        
        $errors = $this->validateRequired(['username', 'email', 'password', 'confirm_password'], $_POST);
        
        if (!$this->validateEmail($email)) {
            $errors[] = 'Please enter a valid email address.';
        }
        
        if ($password !== $confirmPassword) {
            $errors[] = 'Passwords do not match.';
        }
        
        $passwordErrors = $this->validatePassword($password);
        $errors = array_merge($errors, $passwordErrors);
        
        if (empty($errors)) {
            $userModel = new User();
            
            // Check if email already exists
            if ($userModel->findByEmail($email)) {
                $errors[] = 'Email address is already registered.';
            }
            
            // Check if username already exists
            if ($userModel->findByUsername($username)) {
                $errors[] = 'Username is already taken.';
            }
            
            if (empty($errors)) {
                $userData = [
                    'username' => $username,
                    'email' => $email,
                    'password' => $password,
                    'display_name' => $displayName ?: $username,
                    'role' => 'user',
                    'status' => 'active'
                ];
                
                $userId = $userModel->create($userData);
                
                if ($userId) {
                    $this->logActivity('User registered', ['user_id' => $userId]);
                    $this->redirectWithMessage('/login', 'success', 'Registration successful! Please log in.');
                } else {
                    $this->redirectWithMessage('/register', 'error', 'Registration failed. Please try again.');
                }
            }
        }
        
        if (!empty($errors)) {
            $this->redirectWithMessage('/register', 'error', implode('<br>', $errors));
        }
    }

    /**
     * Handle logout
     */
    public function logout()
    {
        $this->logActivity('User logged out', ['user_id' => $this->session->get('user_id')]);
        
        // Clear remember me cookie
        $this->clearRememberCookie();
        
        // Destroy session
        $this->session->destroy();
        
        redirect('/');
    }

    /**
     * Show forgot password form
     */
    public function forgotPassword()
    {
        if ($this->isAuthenticated()) {
            redirect('/');
        }
        
        echo $this->view->render('auth/forgot_password');
    }

    /**
     * Handle forgot password form submission
     */
    public function handleForgotPassword()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/forgot-password');
        }
        
        $this->validateCsrf();
        
        $email = $this->sanitize($_POST['email'] ?? '');
        
        if (!$this->validateEmail($email)) {
            $this->redirectWithMessage('/forgot-password', 'error', 'Please enter a valid email address.');
            return;
        }
        
        $userModel = new User();
        $user = $userModel->findByEmail($email);
        
        if ($user) {
            // Generate reset token
            $token = bin2hex(random_bytes(32));
            
            // Store reset token in database
            $this->db->query(
                "INSERT INTO password_resets (email, token, created_at) VALUES (?, ?, NOW()) 
                 ON DUPLICATE KEY UPDATE token = ?, created_at = NOW()",
                [$email, $token, $token]
            );
            
            // Send reset email
            $this->sendPasswordResetEmail($email, $token);
            
            $this->logActivity('Password reset requested', ['email' => $email]);
        }
        
        // Always show success message for security
        $this->redirectWithMessage('/forgot-password', 'success', 
            'If an account with that email exists, a password reset link has been sent.');
    }

    /**
     * Show reset password form
     */
    public function resetPassword($token)
    {
        if ($this->isAuthenticated()) {
            redirect('/');
        }
        
        // Verify token
        $reset = $this->db->fetch(
            "SELECT * FROM password_resets WHERE token = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)",
            [$token]
        );
        
        if (!$reset) {
            $this->redirectWithMessage('/forgot-password', 'error', 'Invalid or expired reset token.');
            return;
        }
        
        $data = ['token' => $token];
        echo $this->view->render('auth/reset_password', $data);
    }

    /**
     * Handle reset password form submission
     */
    public function handleResetPassword()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/forgot-password');
        }
        
        $this->validateCsrf();
        
        $token = $_POST['token'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        $errors = $this->validateRequired(['password', 'confirm_password'], $_POST);
        
        if ($password !== $confirmPassword) {
            $errors[] = 'Passwords do not match.';
        }
        
        $passwordErrors = $this->validatePassword($password);
        $errors = array_merge($errors, $passwordErrors);
        
        if (empty($errors)) {
            // Verify token
            $reset = $this->db->fetch(
                "SELECT * FROM password_resets WHERE token = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)",
                [$token]
            );
            
            if ($reset) {
                $userModel = new User();
                $user = $userModel->findByEmail($reset['email']);
                
                if ($user) {
                    $userModel->update($user['id'], ['password' => $password]);
                    
                    // Delete reset token
                    $this->db->delete('password_resets', 'token = ?', [$token]);
                    
                    $this->logActivity('Password reset completed', ['user_id' => $user['id']]);
                    $this->redirectWithMessage('/login', 'success', 'Password reset successful! Please log in.');
                } else {
                    $this->redirectWithMessage('/forgot-password', 'error', 'User not found.');
                }
            } else {
                $this->redirectWithMessage('/forgot-password', 'error', 'Invalid or expired reset token.');
            }
        } else {
            $this->redirectWithMessage('/reset-password/' . $token, 'error', implode('<br>', $errors));
        }
    }

    /**
     * Set remember me cookie
     */
    private function setRememberCookie($userId)
    {
        $token = bin2hex(random_bytes(32));
        $expires = time() + (30 * 24 * 60 * 60); // 30 days
        
        setcookie('remember_token', $token, $expires, '/', '', true, true);
        
        // Store token in database
        $this->db->query(
            "INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (?, ?, ?)",
            [$userId, $token, date('Y-m-d H:i:s', $expires)]
        );
    }

    /**
     * Clear remember me cookie
     */
    private function clearRememberCookie()
    {
        if (isset($_COOKIE['remember_token'])) {
            $token = $_COOKIE['remember_token'];
            
            // Delete token from database
            $this->db->delete('remember_tokens', 'token = ?', [$token]);
            
            // Clear cookie
            setcookie('remember_token', '', time() - 3600, '/', '', true, true);
        }
    }

    /**
     * Send password reset email
     */
    private function sendPasswordResetEmail($email, $token)
    {
        $resetUrl = url('/reset-password/' . $token);
        
        // Implementation for sending password reset email
        $this->logActivity('Password reset email sent', ['email' => $email]);
    }
}