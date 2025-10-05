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