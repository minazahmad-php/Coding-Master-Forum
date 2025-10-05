<?php

/**
 * Global Helper Functions
 * Common utility functions used throughout the application
 */

if (!function_exists('config')) {
    /**
     * Get configuration value
     */
    function config($key, $default = null)
    {
        global $app;
        return $app ? $app->config($key, $default) : $default;
    }
}

if (!function_exists('view')) {
    /**
     * Render a view
     */
    function view($view, $data = [])
    {
        global $app;
        $viewEngine = $app->get('view');
        return $viewEngine->render($view, $data);
    }
}

if (!function_exists('redirect')) {
    /**
     * Redirect to URL
     */
    function redirect($url, $status = 302)
    {
        // Validate URL to prevent open redirects
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            // If not a full URL, ensure it starts with /
            if (strpos($url, '/') !== 0) {
                $url = '/' . ltrim($url, '/');
            }
        }
        
        http_response_code($status);
        header("Location: {$url}");
        exit;
    }
}

if (!function_exists('url')) {
    /**
     * Generate URL
     */
    function url($path = '')
    {
        $baseUrl = config('app.url', 'http://localhost');
        return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset')) {
    /**
     * Generate asset URL
     */
    function asset($path)
    {
        return url('public/' . ltrim($path, '/'));
    }
}

if (!function_exists('csrf_token')) {
    /**
     * Generate CSRF token
     */
    function csrf_token()
    {
        global $app;
        $session = $app->get('session');
        return $session->getCsrfToken();
    }
}

if (!function_exists('csrf_field')) {
    /**
     * Generate CSRF hidden field
     */
    function csrf_field()
    {
        return '<input type="hidden" name="_token" value="' . csrf_token() . '">';
    }
}

if (!function_exists('old')) {
    /**
     * Get old input value
     */
    function old($key, $default = '')
    {
        global $app;
        $session = $app->get('session');
        return $session->flash('old.' . $key, $default);
    }
}

if (!function_exists('session')) {
    /**
     * Get or set session value
     */
    function session($key = null, $value = null)
    {
        global $app;
        $session = $app->get('session');
        
        if ($key === null) {
            return $session;
        }
        
        if ($value === null) {
            return $session->get($key);
        }
        
        return $session->set($key, $value);
    }
}

if (!function_exists('auth')) {
    /**
     * Get authenticated user
     */
    function auth()
    {
        global $app;
        $auth = $app->get('auth');
        return $auth ? $auth->user() : null;
    }
}

if (!function_exists('db')) {
    /**
     * Get database instance
     */
    function db()
    {
        global $app;
        return $app->get('database');
    }
}

if (!function_exists('e')) {
    /**
     * Escape HTML
     */
    function e($value)
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('str_limit')) {
    /**
     * Limit string length
     */
    function str_limit($value, $limit = 100, $end = '...')
    {
        if (mb_strlen($value) <= $limit) {
            return $value;
        }
        
        return rtrim(mb_substr($value, 0, $limit)) . $end;
    }
}

if (!function_exists('time_ago')) {
    /**
     * Get human readable time difference
     */
    function time_ago($datetime)
    {
        $time = time() - strtotime($datetime);
        
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
            return date('M j, Y', strtotime($datetime));
        }
    }
}

if (!function_exists('slug')) {
    /**
     * Generate URL slug
     */
    function slug($text)
    {
        // Convert to lowercase
        $text = strtolower($text);
        
        // Replace spaces with hyphens
        $text = preg_replace('/\s+/', '-', $text);
        
        // Remove special characters
        $text = preg_replace('/[^a-z0-9\-]/', '', $text);
        
        // Remove multiple hyphens
        $text = preg_replace('/-+/', '-', $text);
        
        // Trim hyphens
        $text = trim($text, '-');
        
        return $text;
    }
}

if (!function_exists('random_string')) {
    /**
     * Generate random string
     */
    function random_string($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $string = '';
        
        for ($i = 0; $i < $length; $i++) {
            $string .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        return $string;
    }
}

if (!function_exists('format_bytes')) {
    /**
     * Format bytes to human readable format
     */
    function format_bytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}

if (!function_exists('dd')) {
    /**
     * Dump and die
     */
    function dd($data)
    {
        echo '<pre>';
        var_dump($data);
        echo '</pre>';
        die();
    }
}

if (!function_exists('dump')) {
    /**
     * Dump data
     */
    function dump($data)
    {
        echo '<pre>';
        var_dump($data);
        echo '</pre>';
    }
}

if (!function_exists('storage_path')) {
    /**
     * Get storage path
     */
    function storage_path($path = '')
    {
        return STORAGE_PATH . ($path ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('resource_path')) {
    /**
     * Get resource path
     */
    function resource_path($path = '')
    {
        return APP_ROOT . '/resources' . ($path ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('timeAgo')) {
    /**
     * Format relative time
     */
    function timeAgo($date)
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
            return date('M j, Y', strtotime($date));
        }
    }
}

if (!function_exists('formatDate')) {
    /**
     * Format date for display
     */
    function formatDate($date, $format = 'M j, Y')
    {
        return date($format, strtotime($date));
    }
}

if (!function_exists('generateSlug')) {
    /**
     * Generate slug from string
     */
    function generateSlug($string)
    {
        $slug = strtolower(trim($string));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        
        return $slug;
    }
}

if (!function_exists('truncateText')) {
    /**
     * Truncate text to specified length
     */
    function truncateText($text, $length = 100, $suffix = '...')
    {
        if (strlen($text) <= $length) {
            return $text;
        }
        
        return substr($text, 0, $length) . $suffix;
    }
}

if (!function_exists('formatNumber')) {
    /**
     * Format number with commas
     */
    function formatNumber($number)
    {
        return number_format($number);
    }
}

if (!function_exists('formatFileSize')) {
    /**
     * Format file size
     */
    function formatFileSize($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}

if (!function_exists('isValidEmail')) {
    /**
     * Validate email address
     */
    function isValidEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

if (!function_exists('isValidUrl')) {
    /**
     * Validate URL
     */
    function isValidUrl($url)
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
}

if (!function_exists('generateRandomString')) {
    /**
     * Generate random string
     */
    function generateRandomString($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        
        return $randomString;
    }
}

if (!function_exists('getClientIp')) {
    /**
     * Get client IP address
     */
    function getClientIp()
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
}

if (!function_exists('isAjax')) {
    /**
     * Check if request is AJAX
     */
    function isAjax()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}

if (!function_exists('isPost')) {
    /**
     * Check if request is POST
     */
    function isPost()
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }
}

if (!function_exists('isGet')) {
    /**
     * Check if request is GET
     */
    function isGet()
    {
        return $_SERVER['REQUEST_METHOD'] === 'GET';
    }
}

if (!function_exists('getCurrentUrl')) {
    /**
     * Get current URL
     */
    function getCurrentUrl()
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }
}

if (!function_exists('getBaseUrl')) {
    /**
     * Get base URL
     */
    function getBaseUrl()
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        return $protocol . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']);
    }
}