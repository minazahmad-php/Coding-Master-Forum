<?php
declare(strict_types=1);

namespace Middleware;

use Core\Logger;

class ApiModeratorMiddleware
{
    private Logger $logger;

    public function __construct()
    {
        $this->logger = Logger::getInstance();
    }

    public function handle($request, $next)
    {
        $user = $this->getApiUser();
        
        if (!$user) {
            return $this->unauthorized('Authentication required');
        }
        
        $userRole = $user['role'] ?? 'user';
        
        if (!in_array($userRole, ['moderator', 'admin'])) {
            $this->logger->warning('Non-moderator API access attempt', [
                'user_id' => $user['id'],
                'role' => $userRole,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'url' => $_SERVER['REQUEST_URI'] ?? 'unknown'
            ]);

            return $this->forbidden('Moderator privileges required');
        }

        return $next($request);
    }

    private function getApiUser(): ?array
    {
        return $_SESSION['api_user'] ?? null;
    }

    private function unauthorized(string $message)
    {
        return $this->jsonResponse(['error' => $message], 401);
    }

    private function forbidden(string $message)
    {
        return $this->jsonResponse(['error' => $message], 403);
    }

    private function jsonResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}