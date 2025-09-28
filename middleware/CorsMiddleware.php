<?php
declare(strict_types=1);

namespace Middleware;

use Core\Logger;

class CorsMiddleware
{
    private Logger $logger;

    public function __construct()
    {
        $this->logger = Logger::getInstance();
    }

    public function handle($request, $next)
    {
        $this->setCorsHeaders();
        
        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            $this->handlePreflightRequest();
            return;
        }
        
        return $next($request);
    }

    private function setCorsHeaders(): void
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
        
        // Validate origin
        if ($this->isAllowedOrigin($origin)) {
            header('Access-Control-Allow-Origin: ' . $origin);
        } else {
            header('Access-Control-Allow-Origin: *');
        }
        
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-CSRF-Token, X-API-Key');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400'); // 24 hours
        
        // Expose headers
        header('Access-Control-Expose-Headers: X-Total-Count, X-Page-Count, X-Current-Page, X-Per-Page');
    }

    private function isAllowedOrigin(string $origin): bool
    {
        $allowedOrigins = $this->getAllowedOrigins();
        
        if (empty($allowedOrigins)) {
            return true; // Allow all if no restrictions
        }
        
        return in_array($origin, $allowedOrigins);
    }

    private function getAllowedOrigins(): array
    {
        // Get from environment or configuration
        $origins = getenv('CORS_ALLOWED_ORIGINS');
        
        if (!$origins) {
            return [];
        }
        
        return array_map('trim', explode(',', $origins));
    }

    private function handlePreflightRequest(): void
    {
        $this->logger->info('CORS Preflight Request', [
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
            'origin' => $_SERVER['HTTP_ORIGIN'] ?? 'unknown',
            'access_control_request_method' => $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'] ?? 'unknown',
            'access_control_request_headers' => $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'] ?? 'unknown'
        ]);
        
        http_response_code(200);
        exit;
    }
}