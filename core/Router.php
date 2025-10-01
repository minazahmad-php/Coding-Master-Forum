<?php
declare(strict_types=1);

namespace Core;

class Router {
    private array $routes = [];
    private string $path;
    private string $method;
    private array $middleware = [];
    
    public function __construct() {
        $this->path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $this->method = $_SERVER['REQUEST_METHOD'];
        
        // Remove base path dynamically
        $basePath = $this->getBasePath();
        if ($basePath && strpos($this->path, $basePath) === 0) {
            $this->path = substr($this->path, strlen($basePath));
        }
        
        $this->path = $this->path ?: '/';
    }
    
    private function getBasePath(): string {
        $scriptName = $_SERVER['SCRIPT_NAME'];
        $scriptDir = dirname($scriptName);
        
        if ($scriptDir === '/' || $scriptDir === '\\') {
            return '';
        }
        
        return $scriptDir;
    }
    
    public function add(string $route, string $controller, string $method = 'GET', bool $authRequired = false, bool $adminRequired = false, array $middleware = []): void {
        $this->routes[] = [
            'route' => $route,
            'controller' => $controller,
            'method' => $method,
            'auth' => $authRequired,
            'admin' => $adminRequired,
            'middleware' => $middleware
        ];
    }
    
    public function get(string $route, string $controller, bool $authRequired = false, bool $adminRequired = false, array $middleware = []): void {
        $this->add($route, $controller, 'GET', $authRequired, $adminRequired, $middleware);
    }
    
    public function post(string $route, string $controller, bool $authRequired = false, bool $adminRequired = false, array $middleware = []): void {
        $this->add($route, $controller, 'POST', $authRequired, $adminRequired, $middleware);
    }
    
    public function put(string $route, string $controller, bool $authRequired = false, bool $adminRequired = false, array $middleware = []): void {
        $this->add($route, $controller, 'PUT', $authRequired, $adminRequired, $middleware);
    }
    
    public function delete(string $route, string $controller, bool $authRequired = false, bool $adminRequired = false, array $middleware = []): void {
        $this->add($route, $controller, 'DELETE', $authRequired, $adminRequired, $middleware);
    }
    
    public function group(array $middleware, callable $callback): void {
        $originalMiddleware = $this->middleware;
        $this->middleware = array_merge($this->middleware, $middleware);
        
        $callback($this);
        
        $this->middleware = $originalMiddleware;
    }
    
    public function run(): void {
        foreach ($this->routes as $route) {
            if ($this->method !== $route['method']) {
                continue;
            }
            
            $pattern = $this->convertRouteToRegex($route['route']);
            
            if (preg_match($pattern, $this->path, $matches)) {
                // Extract parameters
                array_shift($matches);
                
                // Apply middleware
                if (!$this->applyMiddleware($route['middleware'])) {
                    return;
                }
                
                // Check authentication
                if ($route['auth'] && !$this->checkAuth()) {
                    $this->redirectToLogin();
                    return;
                }
                
                // Check admin access
                if ($route['admin'] && !$this->checkAdmin()) {
                    $this->redirectToHome();
                    return;
                }
                
                // Execute controller
                $this->executeController($route['controller'], $matches);
                return;
            }
        }
        
        $this->notFound();
    }
    
    private function convertRouteToRegex(string $route): string {
        // Convert {param} to regex pattern
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([a-zA-Z0-9_-]+)', $route);
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\?\}/', '([a-zA-Z0-9_-]*)', $pattern);
        
        return "#^$pattern$#";
    }
    
    private function applyMiddleware(array $middleware): bool {
        $allMiddleware = array_merge($this->middleware, $middleware);
        
        foreach ($allMiddleware as $middlewareClass) {
            if (class_exists($middlewareClass)) {
                $middlewareInstance = new $middlewareClass();
                if (!$middlewareInstance->handle()) {
                    return false;
                }
            }
        }
        
        return true;
    }
    
    private function checkAuth(): bool {
        return \Core\Auth::isLoggedIn();
    }
    
    private function checkAdmin(): bool {
        return \Core\Auth::isAdmin();
    }
    
    private function redirectToLogin(): void {
        $this->redirect('/login');
    }
    
    private function redirectToHome(): void {
        $this->redirect('/');
    }
    
    private function redirect(string $path): void {
        $basePath = $this->getBasePath();
        header("Location: {$basePath}{$path}");
        exit;
    }
    
    private function executeController(string $controller, array $params): void {
        if (!strpos($controller, '@')) {
            throw new \InvalidArgumentException("Controller must be in format 'Controller@method'");
        }
        
        [$controllerClass, $methodName] = explode('@', $controller);
        
        // Add namespace if not present
        if (strpos($controllerClass, '\\') === false) {
            $controllerClass = "Controllers\\{$controllerClass}";
        }
        
        $controllerFile = CONTROLLERS_PATH . '/' . basename($controllerClass) . '.php';
        
        if (!file_exists($controllerFile)) {
            throw new \RuntimeException("Controller file not found: {$controllerFile}");
        }
        
        require_once $controllerFile;
        
        if (!class_exists($controllerClass)) {
            throw new \RuntimeException("Controller class not found: {$controllerClass}");
        }
        
        $controllerInstance = new $controllerClass();
        
        if (!method_exists($controllerInstance, $methodName)) {
            throw new \RuntimeException("Method not found: {$controllerClass}@{$methodName}");
        }
        
        call_user_func_array([$controllerInstance, $methodName], $params);
    }
    
    private function notFound(): void {
        http_response_code(404);
        
        if (file_exists(VIEWS_PATH . '/error.php')) {
            include VIEWS_PATH . '/error.php';
        } else {
            echo '<h1>404 - Page Not Found</h1>';
        }
        
        exit;
    }
    
    public function url(string $route, array $params = []): string {
        $basePath = $this->getBasePath();
        
        foreach ($params as $key => $value) {
            $route = str_replace("{{$key}}", $value, $route);
        }
        
        return $basePath . $route;
    }
    
    public function redirectTo(string $route, array $params = []): void {
        $this->redirect($this->url($route, $params));
    }
}
?>