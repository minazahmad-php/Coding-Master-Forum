<?php

namespace App\Core\Middleware;

/**
 * Authentication Middleware
 */
class Auth
{
    public function handle($params)
    {
        global $app;
        $session = $app->get('session');
        
        if (!$session->has('user_id')) {
            $session->set('redirect_after_login', $_SERVER['REQUEST_URI']);
            header('Location: /login');
            exit;
        }
        
        return true;
    }
}