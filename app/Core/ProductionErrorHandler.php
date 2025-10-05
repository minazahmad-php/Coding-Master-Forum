<?php

namespace App\Core;

/**
 * Production Error Handler
 * Handles errors in production environment
 */
class ProductionErrorHandler
{
    private $logger;
    private $app;

    public function __construct(Logger $logger = null, $app = null)
    {
        $this->logger = $logger;
        $this->app = $app;
    }

    /**
     * Handle application errors
     */
    public function handleError($e)
    {
        // Log the error
        if ($this->logger) {
            try {
                $this->logger->error('Application Error: ' . $e->getMessage(), [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]);
            } catch (\Exception $logError) {
                error_log('Logger failed: ' . $logError->getMessage());
            }
        }

        // Fallback logging
        error_log('Application Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());

        // Show user-friendly error page
        $this->showErrorPage();
    }

    /**
     * Show error page
     */
    private function showErrorPage()
    {
        // Clear any previous output
        if (ob_get_level()) {
            ob_clean();
        }

        http_response_code(500);

        // Try to show custom error page
        if ($this->app && $this->app->get('view')) {
            try {
                $this->app->get('view')->error(500, 'Something went wrong. Please try again later.');
                return;
            } catch (\Exception $e) {
                // Fall through to default error page
            }
        }

        // Default error page
        echo $this->getDefaultErrorPage();
    }

    /**
     * Get default error page HTML
     */
    private function getDefaultErrorPage()
    {
        return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Error</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .error-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            text-align: center;
            max-width: 500px;
            width: 90%;
        }
        .error-code {
            font-size: 72px;
            color: #e74c3c;
            margin: 0;
            font-weight: 300;
        }
        .error-message {
            font-size: 24px;
            color: #2c3e50;
            margin: 20px 0;
            font-weight: 400;
        }
        .error-description {
            color: #7f8c8d;
            margin: 20px 0;
            line-height: 1.6;
        }
        .error-actions {
            margin-top: 30px;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 0 10px;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #2980b9;
        }
        .btn-secondary {
            background: #95a5a6;
        }
        .btn-secondary:hover {
            background: #7f8c8d;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <h1 class="error-code">500</h1>
        <h2 class="error-message">Internal Server Error</h2>
        <p class="error-description">
            We apologize for the inconvenience. Something went wrong on our end.
            Please try again later or contact support if the problem persists.
        </p>
        <div class="error-actions">
            <a href="/" class="btn">Go Home</a>
            <a href="javascript:history.back()" class="btn btn-secondary">Go Back</a>
        </div>
    </div>
</body>
</html>';
    }
}