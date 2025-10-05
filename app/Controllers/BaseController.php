<?php

namespace App\Controllers;

use App\Core\View;
use App\Core\Session;
use App\Core\Database;
use App\Core\Logger;

/**
 * Base Controller
 * Common functionality for all controllers
 */
class BaseController
{
    protected $view;
    protected $session;
    protected $db;
    protected $logger;

    public function __construct()
    {
        global $app;
        
        $this->view = $app->get('view');
        $this->session = $app->get('session');
        $this->db = $app->get('database');
        $this->logger = $app->get('logger');
        
        // Share common data with views
        $this->view->share('user', $this->getCurrentUser());
        $this->view->share('csrf_token', $this->session->getCsrfToken());
    }

    /**
     * Get current authenticated user
     */
    protected function getCurrentUser()
    {
        $userId = $this->session->get('user_id');
        
        if ($userId) {
            $userModel = new \App\Models\User();
            return $userModel->find($userId);
        }
        
        return null;
    }

    /**
     * Check if user is authenticated
     */
    protected function isAuthenticated()
    {
        return $this->session->has('user_id');
    }

    /**
     * Check if user has specific role
     */
    protected function hasRole($role)
    {
        $user = $this->getCurrentUser();
        return $user && $user['role'] === $role;
    }

    /**
     * Check if user is admin
     */
    protected function isAdmin()
    {
        return $this->hasRole('admin');
    }

    /**
     * Check if user is moderator
     */
    protected function isModerator()
    {
        return $this->hasRole('moderator') || $this->isAdmin();
    }

    /**
     * Require authentication
     */
    protected function requireAuth()
    {
        if (!$this->isAuthenticated()) {
            $this->session->set('redirect_after_login', $_SERVER['REQUEST_URI']);
            redirect('/login');
        }
    }

    /**
     * Require admin role
     */
    protected function requireAdmin()
    {
        $this->requireAuth();
        
        if (!$this->isAdmin()) {
            $this->view->error(403, 'Access denied. Admin privileges required.');
        }
    }

    /**
     * Require moderator role
     */
    protected function requireModerator()
    {
        $this->requireAuth();
        
        if (!$this->isModerator()) {
            $this->view->error(403, 'Access denied. Moderator privileges required.');
        }
    }

    /**
     * Validate CSRF token
     */
    protected function validateCsrf()
    {
        $token = $_POST['_token'] ?? '';
        
        if (!$this->session->verifyCsrfToken($token)) {
            $this->view->error(419, 'CSRF token mismatch.');
        }
    }

    /**
     * Validate required fields
     */
    protected function validateRequired($fields, $data)
    {
        $errors = [];
        
        foreach ($fields as $field) {
            if (empty($data[$field])) {
                $errors[] = ucfirst($field) . ' is required.';
            }
        }
        
        return $errors;
    }

    /**
     * Validate email format
     */
    protected function validateEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate password strength
     */
    protected function validatePassword($password)
    {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long.';
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter.';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter.';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number.';
        }
        
        return $errors;
    }

    /**
     * Sanitize input data
     */
    protected function sanitize($data)
    {
        if (is_array($data)) {
            return array_map([$this, 'sanitize'], $data);
        }
        
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Set flash message
     */
    protected function setFlash($type, $message)
    {
        $this->session->flash($type, $message);
    }

    /**
     * Get flash message
     */
    protected function getFlash($type)
    {
        return $this->session->flash($type);
    }

    /**
     * Redirect with flash message
     */
    protected function redirectWithMessage($url, $type, $message)
    {
        $this->setFlash($type, $message);
        redirect($url);
    }

    /**
     * Return JSON response
     */
    protected function json($data, $status = 200)
    {
        $this->view->json($data, $status);
    }

    /**
     * Return error response
     */
    protected function error($message, $status = 400)
    {
        $this->json(['error' => $message], $status);
    }

    /**
     * Return success response
     */
    protected function success($message, $data = [])
    {
        $response = ['success' => true, 'message' => $message];
        
        if (!empty($data)) {
            $response['data'] = $data;
        }
        
        $this->json($response);
    }

    /**
     * Log activity
     */
    protected function logActivity($action, $details = [])
    {
        $user = $this->getCurrentUser();
        $userId = $user ? $user['id'] : null;
        
        $this->logger->info("User activity: {$action}", array_merge($details, [
            'user_id' => $userId,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]));
    }
}