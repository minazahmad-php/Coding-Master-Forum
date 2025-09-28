<?php
declare(strict_types=1);

namespace Core;

class Logger
{
    private static ?Logger $instance = null;
    private string $logPath;
    private int $logLevel;

    public function __construct()
    {
        $this->logPath = LOGS_PATH;
        $this->logLevel = $this->getLogLevel();
        $this->ensureLogDirectory();
    }

    public static function getInstance(): Logger
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function getLogLevel(): int
    {
        $level = strtoupper(LOG_LEVEL ?? 'INFO');
        
        return match ($level) {
            'DEBUG' => 0,
            'INFO' => 1,
            'WARNING' => 2,
            'ERROR' => 3,
            'CRITICAL' => 4,
            default => 1
        };
    }

    private function ensureLogDirectory(): void
    {
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log('DEBUG', $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('INFO', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log('WARNING', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('ERROR', $message, $context);
    }

    public function critical(string $message, array $context = []): void
    {
        $this->log('CRITICAL', $message, $context);
    }

    private function log(string $level, string $message, array $context = []): void
    {
        $levelValue = match ($level) {
            'DEBUG' => 0,
            'INFO' => 1,
            'WARNING' => 2,
            'ERROR' => 3,
            'CRITICAL' => 4,
            default => 1
        };

        if ($levelValue < $this->logLevel) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        $contextString = !empty($context) ? ' ' . json_encode($context) : '';
        $logEntry = "[{$timestamp}] {$level}: {$message}{$contextString}" . PHP_EOL;

        $this->writeToFile($logEntry, $level);
        $this->writeToErrorLog($logEntry, $level);
    }

    private function writeToFile(string $logEntry, string $level): void
    {
        $filename = $this->getLogFilename($level);
        $filepath = $this->logPath . '/' . $filename;

        file_put_contents($filepath, $logEntry, FILE_APPEND | LOCK_EX);
    }

    private function writeToErrorLog(string $logEntry, string $level): void
    {
        if (in_array($level, ['ERROR', 'CRITICAL'])) {
            error_log($logEntry);
        }
    }

    private function getLogFilename(string $level): string
    {
        $date = date('Y-m-d');
        
        return match ($level) {
            'DEBUG' => "debug-{$date}.log",
            'INFO' => "info-{$date}.log",
            'WARNING' => "warning-{$date}.log",
            'ERROR' => "error-{$date}.log",
            'CRITICAL' => "critical-{$date}.log",
            default => "app-{$date}.log"
        };
    }

    public function logRequest(string $method, string $uri, int $statusCode, float $responseTime): void
    {
        $this->info('HTTP Request', [
            'method' => $method,
            'uri' => $uri,
            'status_code' => $statusCode,
            'response_time' => $responseTime . 'ms',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
    }

    public function logUserAction(int $userId, string $action, array $context = []): void
    {
        $this->info('User Action', array_merge([
            'user_id' => $userId,
            'action' => $action
        ], $context));
    }

    public function logSecurityEvent(string $event, array $context = []): void
    {
        $this->warning('Security Event', array_merge([
            'event' => $event
        ], $context));
    }

    public function logDatabaseQuery(string $query, array $params = [], float $executionTime = 0): void
    {
        if (LOG_DATABASE_QUERIES ?? false) {
            $this->debug('Database Query', [
                'query' => $query,
                'params' => $params,
                'execution_time' => $executionTime . 'ms'
            ]);
        }
    }

    public function logPerformanceMetric(string $metric, float $value, array $context = []): void
    {
        $this->info('Performance Metric', array_merge([
            'metric' => $metric,
            'value' => $value
        ], $context));
    }

    public function logException(\Exception $exception, array $context = []): void
    {
        $this->error('Exception', array_merge([
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ], $context));
    }

    public function getLogFiles(): array
    {
        $files = [];
        $pattern = $this->logPath . '/*.log';
        
        foreach (glob($pattern) as $file) {
            $files[] = [
                'filename' => basename($file),
                'path' => $file,
                'size' => filesize($file),
                'modified' => filemtime($file)
            ];
        }
        
        return $files;
    }

    public function getLogContent(string $filename, int $lines = 100): array
    {
        $filepath = $this->logPath . '/' . $filename;
        
        if (!file_exists($filepath)) {
            return [];
        }
        
        $content = file($filepath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        return array_slice($content, -$lines);
    }

    public function cleanupOldLogs(int $days = 30): bool
    {
        try {
            $pattern = $this->logPath . '/*.log';
            $cutoffTime = time() - ($days * 24 * 60 * 60);
            
            foreach (glob($pattern) as $file) {
                if (filemtime($file) < $cutoffTime) {
                    unlink($file);
                }
            }
            
            return true;
        } catch (\Exception $e) {
            $this->error('Failed to cleanup old logs', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function getLogStats(): array
    {
        $stats = [];
        $pattern = $this->logPath . '/*.log';
        
        foreach (glob($pattern) as $file) {
            $filename = basename($file);
            $level = $this->extractLevelFromFilename($filename);
            
            if (!isset($stats[$level])) {
                $stats[$level] = [
                    'count' => 0,
                    'size' => 0,
                    'files' => 0
                ];
            }
            
            $stats[$level]['count'] += $this->countLogEntries($file);
            $stats[$level]['size'] += filesize($file);
            $stats[$level]['files']++;
        }
        
        return $stats;
    }

    private function extractLevelFromFilename(string $filename): string
    {
        if (preg_match('/^(\w+)-/', $filename, $matches)) {
            return strtoupper($matches[1]);
        }
        
        return 'UNKNOWN';
    }

    private function countLogEntries(string $filepath): int
    {
        $count = 0;
        $handle = fopen($filepath, 'r');
        
        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                if (preg_match('/^\[.*?\]/', $line)) {
                    $count++;
                }
            }
            fclose($handle);
        }
        
        return $count;
    }

    public function setLogLevel(string $level): void
    {
        $this->logLevel = $this->getLogLevel();
    }

    public function getCurrentLogLevel(): string
    {
        return match ($this->logLevel) {
            0 => 'DEBUG',
            1 => 'INFO',
            2 => 'WARNING',
            3 => 'ERROR',
            4 => 'CRITICAL',
            default => 'INFO'
        };
    }
}