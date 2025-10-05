<?php

namespace App\Core;

/**
 * Router Class
 * Handles URL routing and controller dispatching
 */
class Router
{
    private $routes = [];
    private $config;
    private $middleware = [];

    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->loadRoutes();
    }

    /**
     * Load routes from route files
     */
    private function loadRoutes()
    {
        $routeFiles = [
            'web.php',
            'admin.php',
            'api.php'
        ];

        foreach ($routeFiles as $file) {
            $filePath = APP_ROOT . '/routes/' . $file;
            if (file_exists($filePath)) {
                $this->loadRouteFile($filePath);
            }
        }
    }

    /**
     * Load individual route file
     */
    private function loadRouteFile($file)
    {
        $router = $this;
        include $file;
    }

    /**
     * Add GET route
     */
    public function get($path, $handler, $middleware = [])
    {
        $this->addRoute('GET', $path, $handler, $middleware);
    }

    /**
     * Add POST route
     */
    public function post($path, $handler, $middleware = [])
    {
        $this->addRoute('POST', $path, $handler, $middleware);
    }

    /**
     * Add PUT route
     */
    public function put($path, $handler, $middleware = [])
    {
        $this->addRoute('PUT', $path, $handler, $middleware);
    }

    /**
     * Add DELETE route
     */
    public function delete($path, $handler, $middleware = [])
    {
        $this->addRoute('DELETE', $path, $handler, $middleware);
    }

    /**
     * Add route with any method
     */
    public function any($path, $handler, $middleware = [])
    {
        $this->addRoute('*', $path, $handler, $middleware);
    }

    /**
     * Add route
     */
    private function addRoute($method, $path, $handler, $middleware = [])
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
            'middleware' => $middleware
        ];
    }

    /**
     * Dispatch request
     */
    public function dispatch()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Remove trailing slash
        $uri = rtrim($uri, '/');
        if (empty($uri)) {
            $uri = '/';
        }

        foreach ($this->routes as $route) {
            if ($this->matchRoute($route, $method, $uri)) {
                return $this->executeRoute($route, $uri);
            }
        }

        // No route found
        $this->handle404();
    }

    /**
     * Check if route matches
     */
    private function matchRoute($route, $method, $uri)
    {
        // Check method
        if ($route['method'] !== '*' && $route['method'] !== $method) {
            return false;
        }

        // Convert route pattern to regex
        $pattern = $this->convertToRegex($route['path']);
        
        return preg_match($pattern, $uri);
    }

    /**
     * Convert route pattern to regex
     */
    private function convertToRegex($path)
    {
        // Escape forward slashes
        $pattern = str_replace('/', '\/', $path);
        
        // Convert parameters {param} to named groups
        $pattern = preg_replace('/\{([^}]+)\}/', '(?P<$1>[^\/]+)', $pattern);
        
        // Add start and end anchors
        $pattern = '/^' . $pattern . '$/';
        
        return $pattern;
    }

    /**
     * Execute matched route
     */
    private function executeRoute($route, $uri)
    {
        // Extract parameters
        $params = $this->extractParams($route['path'], $uri);
        
        // Run middleware
        foreach ($route['middleware'] as $middleware) {
            if (!$this->runMiddleware($middleware, $params)) {
                return;
            }
        }

        // Execute handler
        $handler = $route['handler'];
        
        if (is_string($handler)) {
            // Controller@method format
            if (strpos($handler, '@') !== false) {
                list($controller, $method) = explode('@', $handler);
                $this->callController($controller, $method, $params);
            } else {
                // Just controller name
                $this->callController($handler, 'index', $params);
            }
        } elseif (is_callable($handler)) {
            // Callable function
            call_user_func($handler, $params);
        }
    }

    /**
     * Extract parameters from URI
     */
    private function extractParams($path, $uri)
    {
        $params = [];
        
        // Convert route pattern to regex
        $pattern = $this->convertToRegex($path);
        
        if (preg_match($pattern, $uri, $matches)) {
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = $value;
                }
            }
        }
        
        return $params;
    }

    /**
     * Call controller method
     */
    private function callController($controller, $method, $params)
    {
        $controllerClass = "App\\Controllers\\{$controller}";
        
        if (!class_exists($controllerClass)) {
            throw new \Exception("Controller {$controllerClass} not found");
        }
        
        $controllerInstance = new $controllerClass();
        
        if (!method_exists($controllerInstance, $method)) {
            throw new \Exception("Method {$method} not found in {$controllerClass}");
        }
        
        call_user_func_array([$controllerInstance, $method], $params);
    }

    /**
     * Run middleware
     */
    private function runMiddleware($middleware, $params)
    {
        $middlewareClass = "App\\Core\\Middleware\\{$middleware}";
        
        if (class_exists($middlewareClass)) {
            $middlewareInstance = new $middlewareClass();
            return $middlewareInstance->handle($params);
        }
        
        return true;
    }

    /**
     * Handle 404 error
     */
    private function handle404()
    {
        http_response_code(404);
        
        $view = new View($this->config);
        echo $view->render('error/404', []);
    }

    /**
     * Generate URL for route
     */
    public function url($name, $params = [])
    {
        // This would be implemented with named routes
        // For now, return the route name
        return $name;
    }
}