<?php

namespace App\Core;

/**
 * View Engine
 * Handles template rendering
 */
class View
{
    private $config;
    private $data = [];
    private $viewPath;

    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->viewPath = APP_ROOT . '/resources/views';
    }

    /**
     * Render a view
     */
    public function render($view, $data = [])
    {
        $this->data = array_merge($this->data, $data);
        
        $file = $this->viewPath . '/' . $view . '.php';
        
        if (!file_exists($file)) {
            error_log("View file not found: {$view} at {$file}");
            throw new \Exception("View file not found: {$view}");
        }
        
        // Extract data to variables
        extract($this->data);
        
        // Start output buffering
        ob_start();
        
        // Include the view file
        include $file;
        
        // Get the content
        $content = ob_get_clean();
        
        return $content;
    }

    /**
     * Share data with all views
     */
    public function share($key, $value)
    {
        $this->data[$key] = $value;
    }

    /**
     * Check if view exists
     */
    public function exists($view)
    {
        $file = $this->viewPath . '/' . $view . '.php';
        return file_exists($file);
    }

    /**
     * Get view path
     */
    public function getViewPath()
    {
        return $this->viewPath;
    }

    /**
     * Set view path
     */
    public function setViewPath($path)
    {
        $this->viewPath = $path;
    }

    /**
     * Include partial view
     */
    public function include($view, $data = [])
    {
        $file = $this->viewPath . '/' . $view . '.php';
        
        if (!file_exists($file)) {
            error_log("Partial view file not found: {$view} at {$file}");
            return '';
        }
        
        extract(array_merge($this->data, $data));
        include $file;
    }

    /**
     * Render JSON response
     */
    public function json($data, $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Render error page
     */
    public function error($code, $message = '')
    {
        http_response_code($code);
        
        $errorView = "error/{$code}";
        
        if ($this->exists($errorView)) {
            echo $this->render($errorView, ['message' => $message]);
        } else {
            echo "<h1>Error {$code}</h1>";
            if ($message) {
                echo "<p>" . htmlspecialchars($message) . "</p>";
            }
        }
        
        exit;
    }
}