<?php
declare(strict_types=1);

namespace Middleware;

use Core\Session;
use Core\Logger;

class AuthMiddleware
{
    private Session $session;
    private Logger $logger;

    public function __construct()
    {
        $this->session = Session::getInstance();
        $this->logger = Logger::getInstance();
    }

    public function handle($request, $next)
    {
        if (!$this->session->isLoggedIn()) {
            $this->logger->warning('Unauthorized access attempt', [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                'url' => $_SERVER['REQUEST_URI'] ?? 'unknown'
            ]);

            if ($this->isApiRequest($request)) {
                return $this->jsonResponse(['error' => 'Unauthorized'], 401);
            }

            $this->session->flash('error', 'You must be logged in to access this page.');
            header('Location: /login');
            exit;
        }

        return $next($request);
    }

    private function isApiRequest($request): bool
    {
        return strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') === 0;
    }

    private function jsonResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}