<?php

//core/Middleware.php

class Middleware {
    public static function auth() {
        if (!Auth::isLoggedIn()) {
            header('Location: /my_forum/login.php');
            exit;
        }
    }
    
    public static function guest() {
        if (Auth::isLoggedIn()) {
            header('Location: /my_forum/');
            exit;
        }
    }
    
    public static function admin() {
        if (!Auth::isAdmin()) {
            header('Location: /my_forum/');
            exit;
        }
    }
    
    public static function moderator() {
        if (!Auth::isModerator()) {
            header('Location: /my_forum/');
            exit;
        }
    }
    
    public static function csrf($token) {
        if (!validate_csrf($token)) {
            die('CSRF token validation failed');
        }
    }
}
?>