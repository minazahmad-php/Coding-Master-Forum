<?php
declare(strict_types=1);

namespace Middleware;

use Core\Session;
use Core\Logger;

class AdminMiddleware
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
            return $this->unauthorized('You must be logged in to access this page.');
        }

        $userRole = $this->session->get('role', 'user');
        
        if ($userRole !== 'admin') {
            $this->logger->warning('Non-admin access attempt to admin area', [
                'user_id' => $this->session->getUserId(),
                'role' => $userRole,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'url' => $_SERVER['REQUEST_URI'] ?? 'unknown'
            ]);

            return $this->forbidden('Access denied. Admin privileges required.');
        }

        return $next($request);
    }

    private function unauthorized(string $message)
    {
        if ($this->isApiRequest()) {
            return $this->jsonResponse(['error' => $message], 401);
        }

        $this->session->flash('error', $message);
        header('Location: /login');
        exit;
    }

    private function forbidden(string $message)
    {
        if ($this->isApiRequest()) {
            return $this->jsonResponse(['error' => $message], 403);
        }

        http_response_code(403);
        include VIEWS_PATH . '/errors/403.php';
        exit;
    }

    private function isApiRequest(): bool
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