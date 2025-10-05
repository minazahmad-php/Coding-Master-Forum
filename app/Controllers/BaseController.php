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
        
        try {
            $this->view = $app->get('view');
            $this->session = $app->get('session');
            $this->db = $app->get('database');
            $this->logger = $app->get('logger');
            
            // Share common data with views
            if ($this->view) {
                $this->view->share('user', $this->getCurrentUser());
                $this->view->share('csrf_token', $this->session ? $this->session->getCsrfToken() : '');
            }
        } catch (\Exception $e) {
            error_log('BaseController initialization error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get current authenticated user
     */
    protected function getCurrentUser()
    {
        try {
            if (!$this->session) {
                return null;
            }
            
            $userId = $this->session->get('user_id');
            
            if ($userId) {
                $userModel = new \App\Models\User();
                return $userModel->find($userId);
            }
            
            return null;
        } catch (\Exception $e) {
            error_log('getCurrentUser error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if user is authenticated
     */
    protected function isAuthenticated()
    {
        try {
            return $this->session && $this->session->has('user_id');
        } catch (\Exception $e) {
            error_log('isAuthenticated error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if user has specific role
     */
    protected function hasRole($role)
    {
        try {
            $user = $this->getCurrentUser();
            return $user && isset($user['role']) && $user['role'] === $role;
        } catch (\Exception $e) {
            error_log('hasRole error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if user is admin
     */
    protected function isAdmin()
    {
        try {
            return $this->hasRole('admin');
        } catch (\Exception $e) {
            error_log('isAdmin error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if user is moderator
     */
    protected function isModerator()
    {
        try {
            return $this->hasRole('moderator') || $this->isAdmin();
        } catch (\Exception $e) {
            error_log('isModerator error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Require authentication
     */
    protected function requireAuth()
    {
        try {
            if (!$this->isAuthenticated()) {
                if ($this->session) {
                    $this->session->set('redirect_after_login', $_SERVER['REQUEST_URI'] ?? '/');
                }
                redirect('/login');
            }
        } catch (\Exception $e) {
            error_log('requireAuth error: ' . $e->getMessage());
            redirect('/login');
        }
    }

    /**
     * Require admin role
     */
    protected function requireAdmin()
    {
        try {
            $this->requireAuth();
            
            if (!$this->isAdmin()) {
                if ($this->view) {
                    $this->view->error(403, 'Access denied. Admin privileges required.');
                } else {
                    http_response_code(403);
                    echo 'Access denied. Admin privileges required.';
                    exit;
                }
            }
        } catch (\Exception $e) {
            error_log('requireAdmin error: ' . $e->getMessage());
            http_response_code(403);
            echo 'Access denied.';
            exit;
        }
    }

    /**
     * Require moderator role
     */
    protected function requireModerator()
    {
        try {
            $this->requireAuth();
            
            if (!$this->isModerator()) {
                if ($this->view) {
                    $this->view->error(403, 'Access denied. Moderator privileges required.');
                } else {
                    http_response_code(403);
                    echo 'Access denied. Moderator privileges required.';
                    exit;
                }
            }
        } catch (\Exception $e) {
            error_log('requireModerator error: ' . $e->getMessage());
            http_response_code(403);
            echo 'Access denied.';
            exit;
        }
    }

    /**
     * Validate CSRF token
     */
    protected function validateCsrf()
    {
        try {
            $token = $_POST['_token'] ?? '';
            
            if (!$this->session || !$this->session->verifyCsrfToken($token)) {
                if ($this->view) {
                    $this->view->error(419, 'CSRF token mismatch.');
                } else {
                    http_response_code(419);
                    echo 'CSRF token mismatch.';
                    exit;
                }
            }
        } catch (\Exception $e) {
            error_log('validateCsrf error: ' . $e->getMessage());
            http_response_code(419);
            echo 'CSRF token mismatch.';
            exit;
        }
    }

    /**
     * Validate required fields
     */
    protected function validateRequired($fields, $data)
    {
        try {
            $errors = [];
            
            if (!is_array($fields) || !is_array($data)) {
                return ['Invalid input data'];
            }
            
            foreach ($fields as $field) {
                if (empty($data[$field])) {
                    $errors[] = ucfirst($field) . ' is required.';
                }
            }
            
            return $errors;
        } catch (\Exception $e) {
            error_log('validateRequired error: ' . $e->getMessage());
            return ['Validation error occurred'];
        }
    }

    /**
     * Validate email format
     */
    protected function validateEmail($email)
    {
        try {
            if (!is_string($email)) {
                return false;
            }
            return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
        } catch (\Exception $e) {
            error_log('validateEmail error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Validate password strength
     */
    protected function validatePassword($password)
    {
        try {
            $errors = [];
            
            if (!is_string($password)) {
                return ['Password must be a string'];
            }
            
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
        } catch (\Exception $e) {
            error_log('validatePassword error: ' . $e->getMessage());
            return ['Password validation error occurred'];
        }
    }

    /**
     * Sanitize input data
     */
    protected function sanitize($data)
    {
        try {
            if (is_array($data)) {
                return array_map([$this, 'sanitize'], $data);
            }
            
            if (!is_string($data)) {
                return $data;
            }
            
            return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
        } catch (\Exception $e) {
            error_log('sanitize error: ' . $e->getMessage());
            return is_string($data) ? htmlspecialchars($data, ENT_QUOTES, 'UTF-8') : $data;
        }
    }

    /**
     * Set flash message
     */
    protected function setFlash($type, $message)
    {
        try {
            if ($this->session) {
                $this->session->flash($type, $message);
            }
        } catch (\Exception $e) {
            error_log('setFlash error: ' . $e->getMessage());
        }
    }

    /**
     * Get flash message
     */
    protected function getFlash($type)
    {
        try {
            if ($this->session) {
                return $this->session->flash($type);
            }
            return null;
        } catch (\Exception $e) {
            error_log('getFlash error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Redirect with flash message
     */
    protected function redirectWithMessage($url, $type, $message)
    {
        try {
            $this->setFlash($type, $message);
            redirect($url);
        } catch (\Exception $e) {
            error_log('redirectWithMessage error: ' . $e->getMessage());
            redirect($url);
        }
    }

    /**
     * Return JSON response
     */
    protected function json($data, $status = 200)
    {
        try {
            if ($this->view) {
                $this->view->json($data, $status);
            } else {
                http_response_code($status);
                header('Content-Type: application/json');
                echo json_encode($data);
                exit;
            }
        } catch (\Exception $e) {
            error_log('json error: ' . $e->getMessage());
            http_response_code($status);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Internal server error']);
            exit;
        }
    }

    /**
     * Return error response
     */
    protected function error($message, $status = 400)
    {
        try {
            $this->json(['error' => $message], $status);
        } catch (\Exception $e) {
            error_log('error method error: ' . $e->getMessage());
            http_response_code($status);
            echo json_encode(['error' => $message]);
            exit;
        }
    }

    /**
     * Return success response
     */
    protected function success($message, $data = [])
    {
        try {
            $response = ['success' => true, 'message' => $message];
            
            if (!empty($data)) {
                $response['data'] = $data;
            }
            
            $this->json($response);
        } catch (\Exception $e) {
            error_log('success method error: ' . $e->getMessage());
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => $message, 'data' => $data]);
            exit;
        }
    }

    /**
     * Log activity
     */
    protected function logActivity($action, $details = [])
    {
        try {
            $user = $this->getCurrentUser();
            $userId = $user ? $user['id'] : null;
            
            if ($this->logger) {
                $this->logger->info("User activity: {$action}", array_merge($details, [
                    'user_id' => $userId,
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                ]));
            }
        } catch (\Exception $e) {
            error_log('logActivity error: ' . $e->getMessage());
        }
    }

    /**
     * Check if user has permission
     */
    protected function hasPermission($permission)
    {
        $user = $this->getCurrentUser();
        
        if (!$user) {
            return false;
        }
        
        // Admin has all permissions
        if ($user['role'] === 'admin') {
            return true;
        }
        
        // Check specific permissions based on role
        $permissions = $this->getUserPermissions($user['role']);
        
        return in_array($permission, $permissions);
    }

    /**
     * Get user permissions based on role
     */
    protected function getUserPermissions($role)
    {
        $permissions = [
            'user' => [
                'create_thread',
                'create_post',
                'edit_own_thread',
                'edit_own_post',
                'delete_own_thread',
                'delete_own_post',
                'react_to_post',
                'subscribe_thread',
                'send_message',
                'view_profile'
            ],
            'moderator' => [
                'create_thread',
                'create_post',
                'edit_own_thread',
                'edit_own_post',
                'delete_own_thread',
                'delete_own_post',
                'react_to_post',
                'subscribe_thread',
                'send_message',
                'view_profile',
                'moderate_posts',
                'moderate_threads',
                'ban_users',
                'view_reports',
                'manage_forums'
            ],
            'admin' => [
                'all'
            ]
        ];
        
        return $permissions[$role] ?? [];
    }

    /**
     * Require specific permission
     */
    protected function requirePermission($permission)
    {
        $this->requireAuth();
        
        if (!$this->hasPermission($permission)) {
            $this->view->error(403, 'Access denied. Insufficient permissions.');
        }
    }

    /**
     * Validate file upload
     */
    protected function validateFileUpload($file, $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'], $maxSize = 5242880)
    {
        $errors = [];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'File upload failed.';
            return $errors;
        }
        
        $fileSize = $file['size'];
        if ($fileSize > $maxSize) {
            $errors[] = 'File size too large. Maximum size: ' . ($maxSize / 1024 / 1024) . 'MB';
        }
        
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExtension, $allowedTypes)) {
            $errors[] = 'Invalid file type. Allowed types: ' . implode(', ', $allowedTypes);
        }
        
        return $errors;
    }

    /**
     * Upload file
     */
    protected function uploadFile($file, $directory = 'uploads')
    {
        $uploadDir = storage_path($directory);
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileName = uniqid() . '_' . time() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
        $filePath = $uploadDir . '/' . $fileName;
        
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            return $directory . '/' . $fileName;
        }
        
        return false;
    }

    /**
     * Generate slug from string
     */
    protected function generateSlug($string)
    {
        $slug = strtolower(trim($string));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        
        return $slug;
    }

    /**
     * Check if slug exists
     */
    protected function slugExists($slug, $table, $excludeId = null)
    {
        $sql = "SELECT id FROM {$table} WHERE slug = ?";
        $params = [$slug];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $result = $this->db->fetch($sql, $params);
        return !empty($result);
    }

    /**
     * Generate unique slug
     */
    protected function generateUniqueSlug($string, $table, $excludeId = null)
    {
        $baseSlug = $this->generateSlug($string);
        $slug = $baseSlug;
        $counter = 1;
        
        while ($this->slugExists($slug, $table, $excludeId)) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }

    /**
     * Format date for display
     */
    protected function formatDate($date, $format = 'M j, Y')
    {
        return date($format, strtotime($date));
    }

    /**
     * Format relative time
     */
    protected function timeAgo($date)
    {
        $time = time() - strtotime($date);
        
        if ($time < 60) {
            return 'just now';
        } elseif ($time < 3600) {
            $minutes = floor($time / 60);
            return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
        } elseif ($time < 86400) {
            $hours = floor($time / 3600);
            return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        } elseif ($time < 2592000) {
            $days = floor($time / 86400);
            return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        } else {
            return $this->formatDate($date);
        }
    }

    /**
     * Paginate results
     */
    protected function paginate($total, $page, $perPage)
    {
        $totalPages = ceil($total / $perPage);
        $offset = ($page - 1) * $perPage;
        
        return [
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => $totalPages,
            'offset' => $offset,
            'has_prev' => $page > 1,
            'has_next' => $page < $totalPages,
            'prev_page' => $page > 1 ? $page - 1 : null,
            'next_page' => $page < $totalPages ? $page + 1 : null
        ];
    }

    /**
     * Get pagination links
     */
    protected function getPaginationLinks($pagination, $baseUrl)
    {
        $links = [];
        
        if ($pagination['has_prev']) {
            $links['prev'] = $baseUrl . '?page=' . $pagination['prev_page'];
        }
        
        if ($pagination['has_next']) {
            $links['next'] = $baseUrl . '?page=' . $pagination['next_page'];
        }
        
        return $links;
    }

    /**
     * Send notification
     */
    protected function sendNotification($userId, $type, $message, $data = [])
    {
        $notificationData = [
            'user_id' => $userId,
            'type' => $type,
            'message' => $message,
            'data' => json_encode($data),
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        return $this->db->insert('notifications', $notificationData);
    }

    /**
     * Send email
     */
    protected function sendEmail($to, $subject, $message, $isHtml = true)
    {
        $headers = [
            'From: ' . config('mail.from.address'),
            'Reply-To: ' . config('mail.from.address'),
            'X-Mailer: PHP/' . phpversion()
        ];
        
        if ($isHtml) {
            $headers[] = 'MIME-Version: 1.0';
            $headers[] = 'Content-type: text/html; charset=UTF-8';
        }
        
        return mail($to, $subject, $message, implode("\r\n", $headers));
    }

    /**
     * Get client IP address
     */
    protected function getClientIp()
    {
        $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    /**
     * Rate limiting
     */
    protected function rateLimit($key, $maxAttempts = 60, $decayMinutes = 1)
    {
        $cacheKey = 'rate_limit_' . $key . '_' . $this->getClientIp();
        $attempts = $this->session->get($cacheKey, 0);
        
        if ($attempts >= $maxAttempts) {
            $this->view->error(429, 'Too many requests. Please try again later.');
        }
        
        $this->session->set($cacheKey, $attempts + 1);
        $this->session->set($cacheKey . '_time', time() + ($decayMinutes * 60));
    }

    /**
     * Clear rate limit
     */
    protected function clearRateLimit($key)
    {
        $cacheKey = 'rate_limit_' . $key . '_' . $this->getClientIp();
        $this->session->forget($cacheKey);
        $this->session->forget($cacheKey . '_time');
    }
}