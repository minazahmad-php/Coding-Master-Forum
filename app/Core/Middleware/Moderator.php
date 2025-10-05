<?php

namespace App\Core\Middleware;

/**
 * Moderator Middleware
 */
class Moderator
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
        if (!in_array($userRole, ['admin', 'moderator'])) {
            http_response_code(403);
            echo "Access denied. Moderator privileges required.";
            exit;
        }
        
        return true;
    }
}