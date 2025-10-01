<?php
declare(strict_types=1);

/**
 * Modern Forum - Base Controller
 * Provides common functionality for all controllers
 */

namespace Core;

use Core\Database;
use Core\Session;
use Core\Mail;
use Core\Logger;
use Core\Auth;
use Core\View;

abstract class Controller
{
    protected Database $db;
    protected Session $session;
    protected Mail $mail;
    protected Logger $logger;
    protected Auth $auth;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->session = new Session();
        $this->mail = new Mail();
        $this->logger = new Logger();
        $this->auth = new Auth();
        
        // Start session
        $this->session->start();
        
        // Share common data with views
        $this->shareCommonData();
    }
    
    /**
     * Share common data with all views
     */
    private function shareCommonData(): void
    {
        View::share('app_name', APP_NAME);
        View::share('app_url', APP_URL);
        View::share('current_user', $this->auth->user());
        View::share('is_logged_in', $this->auth->isLoggedIn());
        View::share('is_admin', $this->auth->isAdmin());
        View::share('is_moderator', $this->auth->isModerator());
        View::share('flash_messages', $this->session->getFlashMessages());
    }
    
    /**
     * Validate CSRF token
     */
    protected function validateCSRFToken(string $token): bool
    {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Redirect to a URL
     */
    protected function redirect(string $url, int $status = 302): void
    {
        View::redirect($url, $status);
    }
    
    /**
     * Redirect back to previous page
     */
    protected function back(): void
    {
        View::back();
    }
    
    /**
     * Redirect to login page
     */
    protected function redirectToLogin(): void
    {
        $this->session->setFlash('error', 'You must be logged in to access this page.');
        $this->redirect('/login');
    }
    
    /**
     * Redirect to home page
     */
    protected function redirectToHome(): void
    {
        $this->redirect('/');
    }
    
    /**
     * Redirect to admin dashboard
     */
    protected function redirectToAdmin(): void
    {
        $this->redirect('/admin');
    }
    
    /**
     * Redirect to user dashboard
     */
    protected function redirectToDashboard(): void
    {
        $this->redirect('/dashboard');
    }
    
    /**
     * Check if user is authenticated
     */
    protected function requireAuth(): void
    {
        if (!$this->auth->isLoggedIn()) {
            $this->redirectToLogin();
        }
    }
    
    /**
     * Check if user is admin
     */
    protected function requireAdmin(): void
    {
        $this->requireAuth();
        
        if (!$this->auth->isAdmin()) {
            $this->session->setFlash('error', 'You do not have administrative privileges.');
            $this->redirectToHome();
        }
    }
    
    /**
     * Check if user is moderator or admin
     */
    protected function requireModerator(): void
    {
        $this->requireAuth();
        
        if (!$this->auth->isModerator() && !$this->auth->isAdmin()) {
            $this->session->setFlash('error', 'You do not have moderator privileges.');
            $this->redirectToHome();
        }
    }
    
    /**
     * Validate request method
     */
    protected function requireMethod(string $method): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== strtoupper($method)) {
            $this->session->setFlash('error', 'Invalid request method.');
            $this->redirectToHome();
        }
    }
    
    /**
     * Validate POST request
     */
    protected function requirePost(): void
    {
        $this->requireMethod('POST');
    }
    
    /**
     * Validate GET request
     */
    protected function requireGet(): void
    {
        $this->requireMethod('GET');
    }
    
    /**
     * Validate PUT request
     */
    protected function requirePut(): void
    {
        $this->requireMethod('PUT');
    }
    
    /**
     * Validate DELETE request
     */
    protected function requireDelete(): void
    {
        $this->requireMethod('DELETE');
    }
    
    /**
     * Get input data
     */
    protected function input(string $key = null, $default = null)
    {
        if ($key === null) {
            return array_merge($_GET, $_POST);
        }
        
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }
    
    /**
     * Get POST data
     */
    protected function post(string $key = null, $default = null)
    {
        if ($key === null) {
            return $_POST;
        }
        
        return $_POST[$key] ?? $default;
    }
    
    /**
     * Get GET data
     */
    protected function get(string $key = null, $default = null)
    {
        if ($key === null) {
            return $_GET;
        }
        
        return $_GET[$key] ?? $default;
    }
    
    /**
     * Validate required fields
     */
    protected function validateRequired(array $fields, array $data): array
    {
        $errors = [];
        
        foreach ($fields as $field) {
            if (empty($data[$field])) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
            }
        }
        
        return $errors;
    }
    
    /**
     * Validate email
     */
    protected function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validate password strength
     */
    protected function validatePassword(string $password): array
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
     * Sanitize input
     */
    protected function sanitize(string $input): string
    {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Generate slug from string
     */
    protected function generateSlug(string $string): string
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $string)));
        $slug = trim($slug, '-');
        
        return $slug;
    }
    
    /**
     * Upload file
     */
    protected function uploadFile(array $file, string $directory, array $allowedTypes = []): array
    {
        $errors = [];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'File upload failed.';
            return ['success' => false, 'errors' => $errors];
        }
        
        $fileName = $file['name'];
        $fileSize = $file['size'];
        $fileTmp = $file['tmp_name'];
        $fileType = $file['type'];
        
        // Check file size (default 10MB)
        $maxSize = 10 * 1024 * 1024;
        if ($fileSize > $maxSize) {
            $errors[] = 'File size exceeds maximum allowed size.';
        }
        
        // Check file type
        if (!empty($allowedTypes)) {
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            if (!in_array($fileExtension, $allowedTypes)) {
                $errors[] = 'File type not allowed.';
            }
        }
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Generate unique filename
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        $newFileName = uniqid() . '.' . $extension;
        $uploadPath = $directory . '/' . $newFileName;
        
        // Create directory if it doesn't exist
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        
        // Move uploaded file
        if (move_uploaded_file($fileTmp, $uploadPath)) {
            return [
                'success' => true,
                'filename' => $newFileName,
                'path' => $uploadPath,
                'size' => $fileSize,
                'type' => $fileType
            ];
        } else {
            $errors[] = 'Failed to move uploaded file.';
            return ['success' => false, 'errors' => $errors];
        }
    }
    
    /**
     * Delete file
     */
    protected function deleteFile(string $filePath): bool
    {
        if (file_exists($filePath)) {
            return unlink($filePath);
        }
        
        return true;
    }
    
    /**
     * Send email
     */
    protected function sendEmail(string $to, string $subject, string $body, array $options = []): bool
    {
        try {
            $this->mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
            $this->mail->addAddress($to);
            $this->mail->setSubject($subject);
            $this->mail->setBody($body);
            
            if (isset($options['is_html']) && $options['is_html']) {
                $this->mail->isHTML(true);
            }
            
            if (isset($options['attachments'])) {
                foreach ($options['attachments'] as $attachment) {
                    $this->mail->addAttachment($attachment);
                }
            }
            
            return $this->mail->send();
        } catch (Exception $e) {
            $this->logger->error('Email sending failed', [
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * Log activity
     */
    protected function logActivity(string $action, array $data = []): void
    {
        $this->logger->info("User activity: $action", array_merge([
            'user_id' => $this->auth->user()['id'] ?? null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ], $data));
    }
    
    /**
     * Handle AJAX request
     */
    protected function isAjax(): bool
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Return JSON response
     */
    protected function jsonResponse(array $data, int $status = 200): void
    {
        View::json($data, $status);
    }
    
    /**
     * Return success response
     */
    protected function successResponse(string $message, array $data = []): void
    {
        $this->jsonResponse([
            'success' => true,
            'message' => $message,
            'data' => $data
        ]);
    }
    
    /**
     * Return error response
     */
    protected function errorResponse(string $message, array $data = [], int $status = 400): void
    {
        $this->jsonResponse([
            'success' => false,
            'message' => $message,
            'data' => $data
        ], $status);
    }
    
    /**
     * Return validation error response
     */
    protected function validationErrorResponse(array $errors): void
    {
        $this->errorResponse('Validation failed', ['errors' => $errors], 422);
    }
    
    /**
     * Get pagination data
     */
    protected function getPagination(int $currentPage, int $totalItems, int $itemsPerPage = 15): array
    {
        $totalPages = ceil($totalItems / $itemsPerPage);
        $offset = ($currentPage - 1) * $itemsPerPage;
        
        return [
            'current_page' => $currentPage,
            'total_pages' => $totalPages,
            'total_items' => $totalItems,
            'items_per_page' => $itemsPerPage,
            'offset' => $offset,
            'has_previous' => $currentPage > 1,
            'has_next' => $currentPage < $totalPages,
            'previous_page' => $currentPage > 1 ? $currentPage - 1 : null,
            'next_page' => $currentPage < $totalPages ? $currentPage + 1 : null
        ];
    }
    
    /**
     * Get current page from request
     */
    protected function getCurrentPage(): int
    {
        $page = (int) ($_GET['page'] ?? 1);
        return max(1, $page);
    }
    
    /**
     * Get items per page from request
     */
    protected function getItemsPerPage(): int
    {
        $perPage = (int) ($_GET['per_page'] ?? 15);
        return max(1, min(100, $perPage));
    }
    
    /**
     * Get search query from request
     */
    protected function getSearchQuery(): string
    {
        return trim($_GET['q'] ?? $_GET['search'] ?? '');
    }
    
    /**
     * Get sort parameters from request
     */
    protected function getSortParams(): array
    {
        $sort = $_GET['sort'] ?? 'created_at';
        $order = $_GET['order'] ?? 'desc';
        
        // Validate order
        if (!in_array(strtolower($order), ['asc', 'desc'])) {
            $order = 'desc';
        }
        
        return [
            'sort' => $sort,
            'order' => $order
        ];
    }
    
    /**
     * Get filter parameters from request
     */
    protected function getFilterParams(): array
    {
        $filters = [];
        
        // Common filters
        if (!empty($_GET['status'])) {
            $filters['status'] = $_GET['status'];
        }
        
        if (!empty($_GET['category'])) {
            $filters['category'] = $_GET['category'];
        }
        
        if (!empty($_GET['user'])) {
            $filters['user'] = $_GET['user'];
        }
        
        if (!empty($_GET['date_from'])) {
            $filters['date_from'] = $_GET['date_from'];
        }
        
        if (!empty($_GET['date_to'])) {
            $filters['date_to'] = $_GET['date_to'];
        }
        
        return $filters;
    }
}