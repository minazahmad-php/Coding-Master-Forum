<?php
declare(strict_types=1);

namespace Middleware;

use Core\Logger;

class LoggingMiddleware
{
    private Logger $logger;

    public function __construct()
    {
        $this->logger = Logger::getInstance();
    }

    public function handle($request, $next)
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();
        
        // Log request
        $this->logRequest();
        
        // Process request
        $response = $next($request);
        
        // Log response
        $this->logResponse($startTime, $startMemory);
        
        return $response;
    }

    private function logRequest(): void
    {
        $requestData = [
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
            'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'referer' => $_SERVER['HTTP_REFERER'] ?? null,
            'content_type' => $_SERVER['CONTENT_TYPE'] ?? null,
            'content_length' => $_SERVER['CONTENT_LENGTH'] ?? null,
            'query_string' => $_SERVER['QUERY_STRING'] ?? null,
            'timestamp' => date('Y-m-d H:i:s'),
            'user_id' => $this->getUserId()
        ];

        $this->logger->info('HTTP Request', $requestData);
    }

    private function logResponse(float $startTime, int $startMemory): void
    {
        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        
        $responseData = [
            'response_time' => round(($endTime - $startTime) * 1000, 2) . 'ms',
            'memory_usage' => $this->formatBytes($endMemory - $startMemory),
            'peak_memory' => $this->formatBytes(memory_get_peak_usage()),
            'status_code' => http_response_code(),
            'timestamp' => date('Y-m-d H:i:s'),
            'user_id' => $this->getUserId()
        ];

        $this->logger->info('HTTP Response', $responseData);
    }

    private function getUserId(): ?int
    {
        $session = \Core\Session::getInstance();
        return $session->isLoggedIn() ? $session->getUserId() : null;
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}