<?php
declare(strict_types=1);

namespace Middleware;

use Core\Database;
use Core\Logger;

class RateLimitMiddleware
{
    private Database $db;
    private Logger $logger;
    private int $maxRequests;
    private int $windowSeconds;
    private string $identifier;

    public function __construct(int $maxRequests = 100, int $windowSeconds = 3600)
    {
        $this->db = Database::getInstance();
        $this->logger = Logger::getInstance();
        $this->maxRequests = $maxRequests;
        $this->windowSeconds = $windowSeconds;
        $this->identifier = $this->getIdentifier();
    }

    public function handle($request, $next)
    {
        if (!$this->isRateLimitEnabled()) {
            return $next($request);
        }

        $currentCount = $this->getCurrentRequestCount();
        
        if ($currentCount >= $this->maxRequests) {
            $this->logger->warning('Rate limit exceeded', [
                'identifier' => $this->identifier,
                'current_count' => $currentCount,
                'max_requests' => $this->maxRequests,
                'window_seconds' => $this->windowSeconds,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                'url' => $_SERVER['REQUEST_URI'] ?? 'unknown'
            ]);

            return $this->rateLimitExceeded();
        }

        $this->incrementRequestCount();
        
        return $next($request);
    }

    private function getIdentifier(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        // Use user ID if logged in, otherwise use IP + User Agent
        $session = \Core\Session::getInstance();
        if ($session->isLoggedIn()) {
            return 'user_' . $session->getUserId();
        }
        
        return 'ip_' . hash('sha256', $ip . $userAgent);
    }

    private function getCurrentRequestCount(): int
    {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count 
                FROM rate_limit_logs 
                WHERE identifier = ? 
                AND created_at > datetime('now', '-' || ? || ' seconds')
            ");
            
            $stmt->execute([$this->identifier, $this->windowSeconds]);
            $result = $stmt->fetch();
            
            return (int) ($result['count'] ?? 0);
        } catch (\Exception $e) {
            $this->logger->error('Failed to get rate limit count', [
                'error' => $e->getMessage(),
                'identifier' => $this->identifier
            ]);
            return 0;
        }
    }

    private function incrementRequestCount(): void
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO rate_limit_logs (identifier, ip_address, user_agent, url, method, created_at)
                VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
            ");
            
            $stmt->execute([
                $this->identifier,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                $_SERVER['REQUEST_URI'] ?? 'unknown',
                $_SERVER['REQUEST_METHOD'] ?? 'unknown'
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to increment rate limit count', [
                'error' => $e->getMessage(),
                'identifier' => $this->identifier
            ]);
        }
    }

    private function isRateLimitEnabled(): bool
    {
        return RATE_LIMIT_ENABLED;
    }

    private function rateLimitExceeded()
    {
        if ($this->isApiRequest()) {
            return $this->jsonResponse([
                'error' => 'Rate limit exceeded',
                'retry_after' => $this->windowSeconds
            ], 429);
        }

        http_response_code(429);
        include VIEWS_PATH . '/errors/429.php';
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

    public static function cleanup(): void
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                DELETE FROM rate_limit_logs 
                WHERE created_at < datetime('now', '-24 hours')
            ");
            $stmt->execute();
        } catch (\Exception $e) {
            Logger::getInstance()->error('Failed to cleanup rate limit logs', [
                'error' => $e->getMessage()
            ]);
        }
    }
}