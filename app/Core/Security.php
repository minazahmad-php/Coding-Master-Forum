<?php

namespace App\Core;

/**
 * Security Helper
 * Provides security-related utilities
 */
class Security
{
    /**
     * Generate secure random token
     */
    public static function generateToken($length = 32)
    {
        if (function_exists('random_bytes')) {
            return bin2hex(random_bytes($length));
        } elseif (function_exists('openssl_random_pseudo_bytes')) {
            return bin2hex(openssl_random_pseudo_bytes($length));
        } else {
            // Fallback for older PHP versions
            $token = '';
            for ($i = 0; $i < $length; $i++) {
                $token .= chr(mt_rand(0, 255));
            }
            return bin2hex($token);
        }
    }

    /**
     * Hash password securely
     */
    public static function hashPassword($password)
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Verify password
     */
    public static function verifyPassword($password, $hash)
    {
        return password_verify($password, $hash);
    }

    /**
     * Generate CSRF token
     */
    public static function generateCsrfToken()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = self::generateToken();
        }
        
        return $_SESSION['csrf_token'];
    }

    /**
     * Verify CSRF token
     */
    public static function verifyCsrfToken($token)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Sanitize input
     */
    public static function sanitize($input)
    {
        if (is_array($input)) {
            return array_map([self::class, 'sanitize'], $input);
        }
        
        if (is_string($input)) {
            return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
        }
        
        return $input;
    }

    /**
     * Validate email
     */
    public static function validateEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate URL
     */
    public static function validateUrl($url)
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Check if request is AJAX
     */
    public static function isAjax()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Get client IP address
     */
    public static function getClientIp()
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
    public static function rateLimit($key, $maxAttempts = 60, $decayMinutes = 1)
    {
        $cacheKey = 'rate_limit_' . $key . '_' . self::getClientIp();
        $attempts = $_SESSION[$cacheKey] ?? 0;
        
        if ($attempts >= $maxAttempts) {
            return false;
        }
        
        $_SESSION[$cacheKey] = $attempts + 1;
        $_SESSION[$cacheKey . '_time'] = time() + ($decayMinutes * 60);
        
        return true;
    }

    /**
     * Clear rate limit
     */
    public static function clearRateLimit($key)
    {
        $cacheKey = 'rate_limit_' . $key . '_' . self::getClientIp();
        unset($_SESSION[$cacheKey]);
        unset($_SESSION[$cacheKey . '_time']);
    }

    /**
     * Generate secure filename
     */
    public static function generateSecureFilename($originalName)
    {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $filename = self::generateToken(16);
        return $filename . '.' . $extension;
    }

    /**
     * Validate file upload
     */
    public static function validateFileUpload($file, $allowedTypes = [], $maxSize = 5242880)
    {
        $errors = [];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'File upload failed.';
        }
        
        if ($file['size'] > $maxSize) {
            $errors[] = 'File size too large.';
        }
        
        if (!empty($allowedTypes)) {
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($extension, $allowedTypes)) {
                $errors[] = 'Invalid file type.';
            }
        }
        
        return $errors;
    }

    /**
     * Generate secure random string
     */
    public static function generateRandomString($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }
        
        return $randomString;
    }

    /**
     * Check if string contains malicious content
     */
    public static function containsMaliciousContent($string)
    {
        $maliciousPatterns = [
            '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi',
            '/javascript:/i',
            '/on\w+\s*=/i',
            '/<iframe\b[^<]*(?:(?!<\/iframe>)<[^<]*)*<\/iframe>/mi',
            '/<object\b[^<]*(?:(?!<\/object>)<[^<]*)*<\/object>/mi',
            '/<embed\b[^<]*(?:(?!<\/embed>)<[^<]*)*<\/embed>/mi'
        ];
        
        foreach ($maliciousPatterns as $pattern) {
            if (preg_match($pattern, $string)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Escape output for HTML
     */
    public static function escape($string)
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Escape output for JavaScript
     */
    public static function escapeJs($string)
    {
        return json_encode($string, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    }
}