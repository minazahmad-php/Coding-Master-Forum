<?php

namespace App\Core;

/**
 * Error Handler
 * Handles application errors and exceptions
 */
class ErrorHandler
{
    private $logger;
    private $debug;

    public function __construct(Logger $logger = null, $debug = false)
    {
        $this->logger = $logger;
        $this->debug = $debug;
        
        // Set error handlers
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);
    }

    /**
     * Handle PHP errors
     */
    public function handleError($level, $message, $file, $line)
    {
        // Don't handle errors that are suppressed with @
        if (!(error_reporting() & $level)) {
            return false;
        }

        $error = [
            'type' => 'Error',
            'level' => $level,
            'message' => $message,
            'file' => $file,
            'line' => $line,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        $this->logError($error);

        // Don't execute PHP internal error handler
        return true;
    }

    /**
     * Handle uncaught exceptions
     */
    public function handleException($exception)
    {
        $error = [
            'type' => 'Exception',
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'timestamp' => date('Y-m-d H:i:s')
        ];

        $this->logError($error);

        $this->displayError($error);
    }

    /**
     * Handle fatal errors
     */
    public function handleShutdown()
    {
        $error = error_get_last();
        
        if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
            $errorData = [
                'type' => 'Fatal Error',
                'level' => $error['type'],
                'message' => $error['message'],
                'file' => $error['file'],
                'line' => $error['line'],
                'timestamp' => date('Y-m-d H:i:s')
            ];

            $this->logError($errorData);
            $this->displayError($errorData);
        }
    }

    /**
     * Log error
     */
    private function logError($error)
    {
        if ($this->logger) {
            try {
                $this->logger->error($error['message'], $error);
            } catch (\Exception $e) {
                error_log('Logger failed: ' . $e->getMessage());
            }
        }

        // Fallback logging
        error_log(json_encode($error));
    }

    /**
     * Display error to user
     */
    private function displayError($error)
    {
        // Clear any previous output
        if (ob_get_level()) {
            ob_clean();
        }

        http_response_code(500);

        if ($this->debug) {
            // Show detailed error in debug mode
            echo $this->getDebugErrorHtml($error);
        } else {
            // Show user-friendly error in production
            echo $this->getProductionErrorHtml();
        }

        exit;
    }

    /**
     * Get debug error HTML
     */
    private function getDebugErrorHtml($error)
    {
        $html = '<!DOCTYPE html>
<html>
<head>
    <title>Application Error</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
        .error-container { background: white; padding: 20px; border-radius: 5px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .error-header { color: #d32f2f; border-bottom: 2px solid #d32f2f; padding-bottom: 10px; margin-bottom: 20px; }
        .error-details { background: #f8f8f8; padding: 15px; border-radius: 3px; margin: 10px 0; }
        .error-trace { background: #f0f0f0; padding: 15px; border-radius: 3px; font-family: monospace; white-space: pre-wrap; }
    </style>
</head>
<body>
    <div class="error-container">
        <h1 class="error-header">Application Error</h1>
        <div class="error-details">
            <strong>Type:</strong> ' . htmlspecialchars($error['type']) . '<br>
            <strong>Message:</strong> ' . htmlspecialchars($error['message']) . '<br>
            <strong>File:</strong> ' . htmlspecialchars($error['file']) . '<br>
            <strong>Line:</strong> ' . htmlspecialchars($error['line']) . '<br>
            <strong>Time:</strong> ' . htmlspecialchars($error['timestamp']) . '
        </div>';

        if (isset($error['trace'])) {
            $html .= '<div class="error-trace">' . htmlspecialchars($error['trace']) . '</div>';
        }

        $html .= '</div></body></html>';

        return $html;
    }

    /**
     * Get production error HTML
     */
    private function getProductionErrorHtml()
    {
        return '<!DOCTYPE html>
<html>
<head>
    <title>Server Error</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; text-align: center; }
        .error-container { background: white; padding: 40px; border-radius: 5px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); display: inline-block; }
        .error-code { font-size: 72px; color: #d32f2f; margin: 0; }
        .error-message { font-size: 24px; color: #666; margin: 20px 0; }
        .error-description { color: #888; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="error-container">
        <h1 class="error-code">500</h1>
        <h2 class="error-message">Internal Server Error</h2>
        <p class="error-description">Something went wrong. Please try again later.</p>
    </div>
</body>
</html>';
    }
}