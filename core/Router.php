<?php

//core/Router.php

class Router {
    private $routes = [];
    private $path;
    
    public function __construct() {
        $this->path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $this->path = str_replace('/my_forum', '', $this->path); // Remove subdirectory if exists
    }
    
    public function add($route, $controller, $method = 'GET', $authRequired = false, $adminRequired = false) {
        $this->routes[] = [
            'route' => $route,
            'controller' => $controller,
            'method' => $method,
            'auth' => $authRequired,
            'admin' => $adminRequired
        ];
    }
    
    public function run() {
        foreach ($this->routes as $route) {
            // Check method
            if ($_SERVER['REQUEST_METHOD'] !== $route['method']) {
                continue;
            }
            
            // Convert route to regex pattern
            $pattern = preg_replace('/\{[a-zA-Z0-9_]+\}/', '([a-zA-Z0-9_-]+)', $route['route']);
            $pattern = "#^$pattern$#";
            
            // Check if path matches pattern
            if (preg_match($pattern, $this->path, $matches)) {
                // Check authentication
                if ($route['auth'] && !Auth::isLoggedIn()) {
                    header('Location: /my_forum/login.php');
                    exit;
                }
                
                // Check admin access
                if ($route['admin'] && !Auth::isAdmin()) {
                    header('Location: /my_forum/');
                    exit;
                }
                
                // Extract parameters
                array_shift($matches);
                
                // Parse controller and method
                list($controllerClass, $methodName) = explode('@', $route['controller']);
                
                // Include controller file
                $controllerFile = CONTROLLERS_PATH . '/' . $controllerClass . '.php';
                if (!file_exists($controllerFile)) {
                    die("Controller file not found: $controllerFile");
                }
                
                require_once $controllerFile;
                
                // Create controller instance and call method
                $controllerInstance = new $controllerClass();
                call_user_func_array([$controllerInstance, $methodName], $matches);
                
                return;
            }
        }
        
        // No route found
        $this->notFound();
    }
    
    private function notFound() {
        http_response_code(404);
        include VIEWS_PATH . '/error.php';
        exit;
    }
}
?>