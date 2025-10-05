<?php

namespace App\Core\Middleware;

/**
 * Admin Middleware
 */
class Admin
{
    public function handle($params)
    {
        global $app;
        $session = $app->get('session');
        
        if (!$session->has('user_id')) {
            header('Location: /login');
            exit;
        }
        
        $userRole = $session->get('user_role');
        if ($userRole !== 'admin') {
            http_response_code(403);
            echo "Access denied. Admin privileges required.";
            exit;
        }
        
        return true;
    }
}