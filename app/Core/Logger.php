<?php

namespace App\Core;

use Monolog\Logger as MonologLogger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;

/**
 * Logger Class
 * Handles application logging
 */
class Logger
{
    private $logger;
    private $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->initializeLogger();
    }

    /**
     * Initialize Monolog logger
     */
    private function initializeLogger()
    {
        $this->logger = new MonologLogger('forum');
        
        // Create logs directory if it doesn't exist
        $logPath = STORAGE_PATH . '/logs';
        if (!is_dir($logPath)) {
            mkdir($logPath, 0755, true);
        }
        
        // Add file handler
        $fileHandler = new RotatingFileHandler($logPath . '/forum.log', 0, MonologLogger::DEBUG);
        $fileHandler->setFormatter(new LineFormatter(
            "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
            'Y-m-d H:i:s'
        ));
        
        $this->logger->pushHandler($fileHandler);
        
        // Add error handler for errors and above
        $errorHandler = new RotatingFileHandler($logPath . '/error.log', 0, MonologLogger::ERROR);
        $errorHandler->setFormatter(new LineFormatter(
            "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
            'Y-m-d H:i:s'
        ));
        
        $this->logger->pushHandler($errorHandler);
    }

    /**
     * Log debug message
     */
    public function debug($message, $context = [])
    {
        $this->logger->debug($message, $context);
    }

    /**
     * Log info message
     */
    public function info($message, $context = [])
    {
        $this->logger->info($message, $context);
    }

    /**
     * Log warning message
     */
    public function warning($message, $context = [])
    {
        $this->logger->warning($message, $context);
    }

    /**
     * Log error message
     */
    public function error($message, $context = [])
    {
        $this->logger->error($message, $context);
    }

    /**
     * Log critical message
     */
    public function critical($message, $context = [])
    {
        $this->logger->critical($message, $context);
    }

    /**
     * Log with custom level
     */
    public function log($level, $message, $context = [])
    {
        $this->logger->log($level, $message, $context);
    }

    /**
     * Get Monolog instance
     */
    public function getMonolog()
    {
        return $this->logger;
    }
}