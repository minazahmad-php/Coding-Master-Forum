<?php

namespace App\Core;

/**
 * Session Manager
 * Handles PHP sessions securely
 */
class Session
{
    private $started = false;

    public function __construct()
    {
        $this->start();
    }

    /**
     * Start session
     */
    public function start()
    {
        if (!$this->started) {
            // Configure session settings
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
            
            // Set session name
            session_name('FORUM_SESSION');
            
            // Start session
            session_start();
            $this->started = true;
        }
    }

    /**
     * Get session value
     */
    public function get($key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Set session value
     */
    public function set($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Check if session key exists
     */
    public function has($key)
    {
        return isset($_SESSION[$key]);
    }

    /**
     * Remove session value
     */
    public function remove($key)
    {
        unset($_SESSION[$key]);
    }

    /**
     * Flash data (show once then remove)
     */
    public function flash($key, $value = null)
    {
        if ($value !== null) {
            $_SESSION['_flash'][$key] = $value;
        } else {
            $value = $_SESSION['_flash'][$key] ?? null;
            unset($_SESSION['_flash'][$key]);
            return $value;
        }
    }

    /**
     * Check if flash data exists
     */
    public function hasFlash($key)
    {
        return isset($_SESSION['_flash'][$key]);
    }

    /**
     * Regenerate session ID
     */
    public function regenerate()
    {
        session_regenerate_id(true);
    }

    /**
     * Destroy session
     */
    public function destroy()
    {
        if ($this->started) {
            session_destroy();
            $this->started = false;
        }
    }

    /**
     * Get all session data
     */
    public function all()
    {
        return $_SESSION;
    }

    /**
     * Clear all session data
     */
    public function clear()
    {
        $_SESSION = [];
    }

    /**
     * Get session ID
     */
    public function getId()
    {
        return session_id();
    }

    /**
     * Set CSRF token
     */
    public function setCsrfToken()
    {
        if (!$this->has('csrf_token')) {
            $this->set('csrf_token', bin2hex(random_bytes(32)));
        }
    }

    /**
     * Get CSRF token
     */
    public function getCsrfToken()
    {
        $this->setCsrfToken();
        return $this->get('csrf_token');
    }

    /**
     * Verify CSRF token
     */
    public function verifyCsrfToken($token)
    {
        return hash_equals($this->get('csrf_token', ''), $token);
    }
}