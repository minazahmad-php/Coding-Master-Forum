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
            if (!mkdir($logPath, 0755, true)) {
                error_log('Failed to create logs directory: ' . $logPath);
                throw new \Exception('Failed to create logs directory');
            }
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
        try {
            $this->logger->debug($message, $context);
        } catch (\Exception $e) {
            error_log('Logger debug failed: ' . $e->getMessage());
        }
    }

    /**
     * Log info message
     */
    public function info($message, $context = [])
    {
        try {
            $this->logger->info($message, $context);
        } catch (\Exception $e) {
            error_log('Logger info failed: ' . $e->getMessage());
        }
    }

    /**
     * Log warning message
     */
    public function warning($message, $context = [])
    {
        try {
            $this->logger->warning($message, $context);
        } catch (\Exception $e) {
            error_log('Logger warning failed: ' . $e->getMessage());
        }
    }

    /**
     * Log error message
     */
    public function error($message, $context = [])
    {
        try {
            $this->logger->error($message, $context);
        } catch (\Exception $e) {
            error_log('Logger error failed: ' . $e->getMessage());
        }
    }

    /**
     * Log critical message
     */
    public function critical($message, $context = [])
    {
        try {
            $this->logger->critical($message, $context);
        } catch (\Exception $e) {
            error_log('Logger critical failed: ' . $e->getMessage());
        }
    }

    /**
     * Log with custom level
     */
    public function log($level, $message, $context = [])
    {
        try {
            $this->logger->log($level, $message, $context);
        } catch (\Exception $e) {
            error_log('Logger log failed: ' . $e->getMessage());
        }
    }

    /**
     * Get Monolog instance
     */
    public function getMonolog()
    {
        return $this->logger;
    }
}