<?php
declare(strict_types=1);

namespace Middleware;

abstract class Middleware {
    abstract public function handle(): bool;
    
    protected function redirect(string $url): void {
        header("Location: $url");
        exit;
    }
    
    protected function jsonResponse(array $data, int $statusCode = 200): void {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}

class AuthMiddleware extends Middleware {
    public function handle(): bool {
        if (!\Core\Auth::isLoggedIn()) {
            if ($this->isApiRequest()) {
                $this->jsonResponse(['error' => 'Unauthorized'], 401);
            } else {
                $this->redirect('/login');
            }
            return false;
        }
        return true;
    }
    
    private function isApiRequest(): bool {
        return strpos($_SERVER['REQUEST_URI'], '/api/') === 0;
    }
}

class AdminMiddleware extends Middleware {
    public function handle(): bool {
        if (!\Core\Auth::isAdmin()) {
            if ($this->isApiRequest()) {
                $this->jsonResponse(['error' => 'Forbidden'], 403);
            } else {
                $this->redirect('/');
            }
            return false;
        }
        return true;
    }
    
    private function isApiRequest(): bool {
        return strpos($_SERVER['REQUEST_URI'], '/api/') === 0;
    }
}

class ModeratorMiddleware extends Middleware {
    public function handle(): bool {
        if (!\Core\Auth::isModerator()) {
            if ($this->isApiRequest()) {
                $this->jsonResponse(['error' => 'Forbidden'], 403);
            } else {
                $this->redirect('/');
            }
            return false;
        }
        return true;
    }
    
    private function isApiRequest(): bool {
        return strpos($_SERVER['REQUEST_URI'], '/api/') === 0;
    }
}

class CsrfMiddleware extends Middleware {
    public function handle(): bool {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PUT' || $_SERVER['REQUEST_METHOD'] === 'DELETE') {
            $token = $_POST[CSRF_TOKEN_NAME] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            
            if (!verifyCsrfToken($token)) {
                if ($this->isApiRequest()) {
                    $this->jsonResponse(['error' => 'Invalid CSRF token'], 403);
                } else {
                    $this->redirect('/');
                }
                return false;
            }
        }
        return true;
    }
    
    private function isApiRequest(): bool {
        return strpos($_SERVER['REQUEST_URI'], '/api/') === 0;
    }
}

class RateLimitMiddleware extends Middleware {
    private int $maxRequests;
    private int $timeWindow;
    
    public function __construct(int $maxRequests = 100, int $timeWindow = 3600) {
        $this->maxRequests = $maxRequests;
        $this->timeWindow = $timeWindow;
    }
    
    public function handle(): bool {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $key = "rate_limit_{$ip}";
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 0, 'reset_time' => time() + $this->timeWindow];
        }
        
        $rateLimit = $_SESSION[$key];
        
        // Reset if time window has passed
        if (time() > $rateLimit['reset_time']) {
            $_SESSION[$key] = ['count' => 0, 'reset_time' => time() + $this->timeWindow];
            $rateLimit = $_SESSION[$key];
        }
        
        // Check if limit exceeded
        if ($rateLimit['count'] >= $this->maxRequests) {
            if ($this->isApiRequest()) {
                $this->jsonResponse(['error' => 'Rate limit exceeded'], 429);
            } else {
                http_response_code(429);
                echo '<h1>Too Many Requests</h1><p>Please try again later.</p>';
                exit;
            }
            return false;
        }
        
        // Increment counter
        $_SESSION[$key]['count']++;
        
        return true;
    }
    
    private function isApiRequest(): bool {
        return strpos($_SERVER['REQUEST_URI'], '/api/') === 0;
    }
}

class MaintenanceMiddleware extends Middleware {
    public function handle(): bool {
        $maintenanceFile = STORAGE_PATH . '/maintenance.lock';
        
        if (file_exists($maintenanceFile)) {
            // Allow admin users to access during maintenance
            if (\Core\Auth::isAdmin()) {
                return true;
            }
            
            http_response_code(503);
            header('Retry-After: 3600');
            
            if ($this->isApiRequest()) {
                $this->jsonResponse(['error' => 'Service temporarily unavailable'], 503);
            } else {
                echo '<h1>Maintenance Mode</h1><p>We are currently performing maintenance. Please check back later.</p>';
                exit;
            }
            return false;
        }
        
        return true;
    }
    
    private function isApiRequest(): bool {
        return strpos($_SERVER['REQUEST_URI'], '/api/') === 0;
    }
}

class LoggingMiddleware extends Middleware {
    public function handle(): bool {
        $this->logRequest();
        return true;
    }
    
    private function logRequest(): void {
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'method' => $_SERVER['REQUEST_METHOD'],
            'uri' => $_SERVER['REQUEST_URI'],
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'user_id' => \Core\Auth::getUserId(),
            'referer' => $_SERVER['HTTP_REFERER'] ?? ''
        ];
        
        $logMessage = json_encode($logData) . PHP_EOL;
        file_put_contents(LOGS_PATH . '/access.log', $logMessage, FILE_APPEND | LOCK_EX);
    }
}

class SecurityHeadersMiddleware extends Middleware {
    public function handle(): bool {
        $this->setSecurityHeaders();
        return true;
    }
    
    private function setSecurityHeaders(): void {
        if (!headers_sent()) {
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: SAMEORIGIN');
            header('X-XSS-Protection: 1; mode=block');
            header('Referrer-Policy: strict-origin-when-cross-origin');
            
            // Remove server information
            header_remove('X-Powered-By');
            header_remove('Server');
        }
    }
}

class CorsMiddleware extends Middleware {
    public function handle(): bool {
        $this->handleCors();
        return true;
    }
    
    private function handleCors(): void {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
        $allowedOrigins = ['http://localhost:3000', 'https://yourdomain.com'];
        
        if (in_array($origin, $allowedOrigins) || $origin === '*') {
            header("Access-Control-Allow-Origin: $origin");
        }
        
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization, X-CSRF-Token');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 3600');
        
        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }
}
?>