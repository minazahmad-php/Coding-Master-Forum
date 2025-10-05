<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\User;

/**
 * API Authentication Controller
 */
class AuthApiController extends BaseController
{
    /**
     * API Login
     */
    public function login()
    {
        $email = $this->sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        $errors = $this->validateRequired(['email', 'password'], $_POST);
        
        if (empty($errors)) {
            $userModel = new User();
            $user = $userModel->findByEmail($email);
            
            if ($user && $userModel->verifyPassword($password, $user['password'])) {
                if ($user['status'] === 'banned') {
                    $this->error('Account is banned', 403);
                }
                
                // Login successful
                $this->session->set('user_id', $user['id']);
                $this->session->set('user_role', $user['role']);
                
                // Update last login
                $userModel->updateLastLogin($user['id']);
                
                $this->success('Login successful', [
                    'user' => [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'email' => $user['email'],
                        'display_name' => $user['display_name'],
                        'role' => $user['role']
                    ]
                ]);
            } else {
                $this->error('Invalid credentials', 401);
            }
        } else {
            $this->error(implode(', ', $errors), 400);
        }
    }

    /**
     * API Register
     */
    public function register()
    {
        $username = $this->sanitize($_POST['username'] ?? '');
        $email = $this->sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $displayName = $this->sanitize($_POST['display_name'] ?? '');
        
        $errors = $this->validateRequired(['username', 'email', 'password'], $_POST);
        
        if (!$this->validateEmail($email)) {
            $errors[] = 'Invalid email format';
        }
        
        $passwordErrors = $this->validatePassword($password);
        $errors = array_merge($errors, $passwordErrors);
        
        if (empty($errors)) {
            $userModel = new User();
            
            // Check if email already exists
            if ($userModel->findByEmail($email)) {
                $this->error('Email already registered', 400);
            }
            
            // Check if username already exists
            if ($userModel->findByUsername($username)) {
                $this->error('Username already taken', 400);
            }
            
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
                $this->success('Registration successful', [
                    'user_id' => $userId
                ]);
            } else {
                $this->error('Registration failed', 500);
            }
        } else {
            $this->error(implode(', ', $errors), 400);
        }
    }

    /**
     * API Logout
     */
    public function logout()
    {
        $this->requireAuth();
        
        $this->session->destroy();
        $this->success('Logout successful');
    }

    /**
     * Get current user
     */
    public function user()
    {
        $this->requireAuth();
        
        $user = $this->getCurrentUser();
        unset($user['password']); // Remove password from response
        
        $this->success('User data retrieved', ['user' => $user]);
    }
}