<?php
declare(strict_types=1);

namespace Middleware;

use Core\Database;
use Core\Logger;

class ApiMiddleware
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
        // Set API response headers
        $this->setApiHeaders();
        
        // Log API request
        $this->logApiRequest();
        
        // Process request
        $response = $next($request);
        
        // Log API response
        $this->logApiResponse();
        
        return $response;
    }

    private function setApiHeaders(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('X-API-Version: ' . API_VERSION);
        header('X-Powered-By: ' . SITE_NAME . ' API');
        
        // Add CORS headers for API
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-API-Key');
    }

    private function logApiRequest(): void
    {
        $requestData = [
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
            'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'unknown',
            'content_length' => $_SERVER['CONTENT_LENGTH'] ?? 0,
            'query_string' => $_SERVER['QUERY_STRING'] ?? null,
            'timestamp' => date('Y-m-d H:i:s'),
            'api_key' => $this->getApiKey()
        ];

        $this->logger->info('API Request', $requestData);
    }

    private function logApiResponse(): void
    {
        $responseData = [
            'status_code' => http_response_code(),
            'timestamp' => date('Y-m-d H:i:s'),
            'api_key' => $this->getApiKey()
        ];

        $this->logger->info('API Response', $responseData);
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
}