<?php

namespace App\Core;

use App\Models\User;

/**
 * Authentication Manager
 * Handles user authentication and authorization
 */
class Auth
{
    private $session;
    private $user = null;

    public function __construct()
    {
        global $app;
        $this->session = $app->get('session');
        $this->loadUser();
    }

    /**
     * Load current user from session
     */
    private function loadUser()
    {
        $userId = $this->session->get('user_id');
        
        if ($userId) {
            $userModel = new User();
            $this->user = $userModel->find($userId);
        }
    }

    /**
     * Check if user is authenticated
     */
    public function check()
    {
        return $this->user !== null;
    }

    /**
     * Get current user
     */
    public function user()
    {
        return $this->user;
    }

    /**
     * Get current user ID
     */
    public function id()
    {
        return $this->user ? $this->user['id'] : null;
    }

    /**
     * Login user
     */
    public function login($user)
    {
        $this->session->set('user_id', $user['id']);
        $this->session->set('user_role', $user['role']);
        $this->user = $user;
        
        // Update last login
        $userModel = new User();
        $userModel->updateLastLogin($user['id']);
    }

    /**
     * Logout user
     */
    public function logout()
    {
        $this->session->remove('user_id');
        $this->session->remove('user_role');
        $this->user = null;
    }

    /**
     * Check if user has specific role
     */
    public function hasRole($role)
    {
        return $this->user && $this->user['role'] === $role;
    }

    /**
     * Check if user is admin
     */
    public function isAdmin()
    {
        return $this->hasRole('admin');
    }

    /**
     * Check if user is moderator
     */
    public function isModerator()
    {
        return $this->hasRole('moderator') || $this->isAdmin();
    }

    /**
     * Check if user can perform action
     */
    public function can($action, $resource = null)
    {
        if (!$this->check()) {
            return false;
        }

        // Admin can do everything
        if ($this->isAdmin()) {
            return true;
        }

        // Moderator permissions
        if ($this->isModerator()) {
            $moderatorActions = [
                'moderate_posts',
                'moderate_threads',
                'ban_users',
                'view_reports',
                'manage_forums'
            ];
            
            return in_array($action, $moderatorActions);
        }

        // Regular user permissions
        $userActions = [
            'create_posts',
            'create_threads',
            'edit_own_posts',
            'edit_own_threads',
            'delete_own_posts',
            'delete_own_threads',
            'send_messages',
            'report_content'
        ];
        
        return in_array($action, $userActions);
    }

    /**
     * Check if user owns resource
     */
    public function owns($resource)
    {
        if (!$this->check() || !$resource) {
            return false;
        }

        $userId = $this->id();
        
        if (is_array($resource)) {
            return isset($resource['user_id']) && $resource['user_id'] == $userId;
        }
        
        if (is_object($resource)) {
            return isset($resource->user_id) && $resource->user_id == $userId;
        }
        
        return false;
    }

    /**
     * Require authentication
     */
    public function requireAuth()
    {
        if (!$this->check()) {
            $this->session->set('redirect_after_login', $_SERVER['REQUEST_URI']);
            redirect('/login');
        }
    }

    /**
     * Require specific role
     */
    public function requireRole($role)
    {
        $this->requireAuth();
        
        if (!$this->hasRole($role)) {
            http_response_code(403);
            echo "Access denied. {$role} privileges required.";
            exit;
        }
    }

    /**
     * Require permission
     */
    public function requirePermission($action, $resource = null)
    {
        $this->requireAuth();
        
        if (!$this->can($action, $resource)) {
            http_response_code(403);
            echo "Access denied. Insufficient permissions.";
            exit;
        }
    }
}