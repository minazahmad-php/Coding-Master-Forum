<?php
declare(strict_types=1);

namespace Middleware;

use Core\Database;
use Core\Logger;

class ApiAuthMiddleware
{
    private Database $db;
    private Logger $logger;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->logger = Logger::getInstance();
    }

    public function handle($request, $next)
    {
        $apiKey = $this->getApiKey();
        
        if (!$apiKey) {
            return $this->unauthorized('API key required');
        }
        
        $user = $this->validateApiKey($apiKey);
        
        if (!$user) {
            return $this->unauthorized('Invalid API key');
        }
        
        // Set user context for the request
        $this->setUserContext($user);
        
        // Update API key usage
        $this->updateApiKeyUsage($apiKey);
        
        return $next($request);
    }

    private function getApiKey(): ?string
    {
        // Check Authorization header
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            $auth = $headers['Authorization'];
            if (strpos($auth, 'Bearer ') === 0) {
                return substr($auth, 7);
            }
        }
        
        // Check X-API-Key header
        if (isset($headers['X-API-Key'])) {
            return $headers['X-API-Key'];
        }
        
        // Check query parameter
        if (isset($_GET['api_key'])) {
            return $_GET['api_key'];
        }
        
        return null;
    }

    private function validateApiKey(string $apiKey): ?array
    {
        try {
            $keyHash = hash('sha256', $apiKey);
            
            $stmt = $this->db->prepare("
                SELECT ak.*, u.id, u.username, u.email, u.status 
                FROM api_keys ak 
                JOIN users u ON ak.user_id = u.id 
                WHERE ak.key_hash = ? 
                AND ak.is_active = 1 
                AND u.status = 'active'
            ");
            
            $stmt->execute([$keyHash]);
            $result = $stmt->fetch();
            
            if ($result) {
                return $result;
            }
            
            return null;
        } catch (\Exception $e) {
            $this->logger->error('API key validation failed', [
                'error' => $e->getMessage(),
                'api_key' => substr($apiKey, 0, 10) . '...'
            ]);
            return null;
        }
    }

    private function setUserContext(array $user): void
    {
        // Set user context in a way that can be accessed by controllers
        $_SESSION['api_user'] = $user;
        $_SESSION['api_user_id'] = $user['id'];
        $_SESSION['api_user_role'] = $user['role'] ?? 'user';
    }

    private function updateApiKeyUsage(string $apiKey): void
    {
        try {
            $keyHash = hash('sha256', $apiKey);
            
            $stmt = $this->db->prepare("
                UPDATE api_keys 
                SET last_used_at = CURRENT_TIMESTAMP 
                WHERE key_hash = ?
            ");
            
            $stmt->execute([$keyHash]);
            
            // Log API usage
            $stmt = $this->db->prepare("
                INSERT INTO api_usage_logs (key_hash, endpoint, response_time, status_code, created_at)
                VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)
            ");
            
            $stmt->execute([
                $keyHash,
                $_SERVER['REQUEST_URI'] ?? 'unknown',
                0, // Will be updated by response middleware
                http_response_code()
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to update API key usage', [
                'error' => $e->getMessage(),
                'api_key' => substr($apiKey, 0, 10) . '...'
            ]);
        }
    }

    private function unauthorized(string $message)
    {
        $this->logger->warning('API authentication failed', [
            'message' => $message,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'url' => $_SERVER['REQUEST_URI'] ?? 'unknown'
        ]);
        
        return $this->jsonResponse(['error' => $message], 401);
    }

    private function jsonResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}