<?php
declare(strict_types=1);

namespace Middleware;

use Core\Session;
use Core\Logger;

class CSRFMiddleware
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
        // Skip CSRF for GET requests and API requests
        if ($_SERVER['REQUEST_METHOD'] === 'GET' || $this->isApiRequest()) {
            return $next($request);
        }

        $token = $this->getTokenFromRequest();
        $sessionToken = $this->session->get(CSRF_TOKEN_NAME);

        if (!$token || !$sessionToken || !hash_equals($sessionToken, $token)) {
            $this->logger->warning('CSRF token validation failed', [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                'url' => $_SERVER['REQUEST_URI'] ?? 'unknown',
                'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
                'provided_token' => $token ? substr($token, 0, 10) . '...' : 'none',
                'session_token' => $sessionToken ? substr($sessionToken, 0, 10) . '...' : 'none'
            ]);

            return $this->csrfError();
        }

        return $next($request);
    }

    private function getTokenFromRequest(): ?string
    {
        // Check POST data first
        if (isset($_POST[CSRF_TOKEN_NAME])) {
            return $_POST[CSRF_TOKEN_NAME];
        }

        // Check headers
        $headers = getallheaders();
        if (isset($headers['X-CSRF-Token'])) {
            return $headers['X-CSRF-Token'];
        }

        // Check JSON body
        if ($this->isJsonRequest()) {
            $input = json_decode(file_get_contents('php://input'), true);
            if (isset($input[CSRF_TOKEN_NAME])) {
                return $input[CSRF_TOKEN_NAME];
            }
        }

        return null;
    }

    private function isJsonRequest(): bool
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        return strpos($contentType, 'application/json') !== false;
    }

    private function isApiRequest(): bool
    {
        return strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') === 0;
    }

    private function csrfError()
    {
        if ($this->isApiRequest()) {
            return $this->jsonResponse(['error' => 'CSRF token mismatch'], 419);
        }

        http_response_code(419);
        include VIEWS_PATH . '/errors/419.php';
        exit;
    }

    private function jsonResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    public static function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    public static function getTokenField(): string
    {
        $token = self::generateToken();
        Session::getInstance()->set(CSRF_TOKEN_NAME, $token);
        
        return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . htmlspecialchars($token) . '">';
    }
}