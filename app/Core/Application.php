<?php

namespace App\Core;

use App\Core\Database;
use App\Core\Router;
use App\Core\Session;
use App\Core\View;
use App\Core\Logger;
use App\Core\Config;

/**
 * Main Application Class
 * Handles application bootstrap and request processing
 */
class Application
{
    private $config;
    private $router;
    private $database;
    private $session;
    private $view;
    private $logger;
    private $container = [];

    public function __construct()
    {
        $this->loadConfig();
        $this->initializeServices();
    }

    /**
     * Load application configuration
     */
    private function loadConfig()
    {
        $this->config = new Config();
    }

    /**
     * Initialize core services
     */
    private function initializeServices()
    {
        try {
            // Initialize logger first with optimized settings
            $this->logger = new Logger($this->config);
            
            // Initialize database
            $this->database = new Database($this->config);
            
            // Initialize session
            $this->session = new Session();
            
            // Initialize view engine
            $this->view = new View($this->config);
            
            // Initialize router
            $this->router = new Router($this->config);
            
            // Register services in container
            $this->container['config'] = $this->config;
            $this->container['database'] = $this->database;
            $this->container['session'] = $this->session;
            $this->container['view'] = $this->view;
            $this->container['logger'] = $this->logger;
            $this->container['router'] = $this->router;
            
        } catch (\Exception $e) {
            // Log initialization error
            error_log('Application initialization error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Run the application
     */
    public function run()
    {
        try {
            // Start session
            $this->session->start();
            
            // Handle the request
            $this->router->dispatch();
            
        } catch (\Exception $e) {
            $this->logger->error('Application Error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->handleError($e);
        }
    }

    /**
     * Get service from container
     */
    public function get($service)
    {
        return $this->container[$service] ?? null;
    }

    /**
     * Register service in container
     */
    public function register($name, $service)
    {
        $this->container[$name] = $service;
    }

    /**
     * Handle application errors
     */
    private function handleError(\Exception $e)
    {
        if ($this->config->get('app.debug')) {
            // Show detailed error in development
            echo "<h1>Application Error</h1>";
            echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
            echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
            echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
        } else {
            // Show generic error in production
            http_response_code(500);
            echo "<h1>Internal Server Error</h1>";
            echo "<p>Something went wrong. Please try again later.</p>";
        }
    }

    /**
     * Get configuration value
     */
    public function config($key, $default = null)
    {
        return $this->config->get($key, $default);
    }
}