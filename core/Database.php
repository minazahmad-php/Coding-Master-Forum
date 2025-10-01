<?php
declare(strict_types=1);

namespace Core;

use PDO;
use PDOException;
use PDOStatement;

class Database {
    private PDO $pdo;
    private static ?Database $instance = null;
    private array $queryLog = [];
    private bool $loggingEnabled = false;

    private function __construct() {
        try {
            $this->pdo = new PDO(DB_DSN);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            
            // SQLite optimizations
            $this->pdo->exec('PRAGMA foreign_keys = ON;');
            $this->pdo->exec('PRAGMA journal_mode = WAL;');
            $this->pdo->exec('PRAGMA synchronous = NORMAL;');
            $this->pdo->exec('PRAGMA cache_size = 10000;');
            $this->pdo->exec('PRAGMA temp_store = MEMORY;');
            
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new \RuntimeException("Database connection failed: " . $e->getMessage());
        }
    }

    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection(): PDO {
        return $this->pdo;
    }

    public function enableQueryLogging(bool $enabled = true): void {
        $this->loggingEnabled = $enabled;
    }

    public function getQueryLog(): array {
        return $this->queryLog;
    }

    public function clearQueryLog(): void {
        $this->queryLog = [];
    }

    private function logQuery(string $sql, array $params = [], float $executionTime = 0): void {
        if ($this->loggingEnabled) {
            $this->queryLog[] = [
                'sql' => $sql,
                'params' => $params,
                'execution_time' => $executionTime,
                'timestamp' => microtime(true)
            ];
        }
    }

    public function query(string $sql, array $params = []): PDOStatement {
        $startTime = microtime(true);
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            $executionTime = microtime(true) - $startTime;
            $this->logQuery($sql, $params, $executionTime);
            
            return $stmt;
        } catch (PDOException $e) {
            error_log("Query failed: {$sql} - " . $e->getMessage());
            throw $e;
        }
    }

    public function fetch(string $sql, array $params = []): ?array {
        $stmt = $this->query($sql, $params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function fetchAll(string $sql, array $params = []): array {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    public function fetchColumn(string $sql, array $params = []): mixed {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchColumn();
    }

    public function insert(string $table, array $data): int {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->query($sql, $data);
        
        return (int) $this->pdo->lastInsertId();
    }

    public function update(string $table, array $data, string $where, array $whereParams = []): int {
        $setClause = [];
        foreach ($data as $key => $value) {
            $setClause[] = "{$key} = :{$key}";
        }
        $setClause = implode(', ', $setClause);
        
        $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";
        $params = array_merge($data, $whereParams);
        
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }

    public function delete(string $table, string $where, array $params = []): int {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }

    public function exists(string $table, string $where, array $params = []): bool {
        $sql = "SELECT 1 FROM {$table} WHERE {$where} LIMIT 1";
        $stmt = $this->query($sql, $params);
        return $stmt->fetchColumn() !== false;
    }

    public function count(string $table, string $where = '1=1', array $params = []): int {
        $sql = "SELECT COUNT(*) FROM {$table} WHERE {$where}";
        return (int) $this->fetchColumn($sql, $params);
    }

    public function beginTransaction(): bool {
        return $this->pdo->beginTransaction();
    }

    public function commit(): bool {
        return $this->pdo->commit();
    }

    public function rollback(): bool {
        return $this->pdo->rollBack();
    }

    public function inTransaction(): bool {
        return $this->pdo->inTransaction();
    }

    public function lastInsertId(): int {
        return (int) $this->pdo->lastInsertId();
    }

    public function getTableInfo(string $table): array {
        $sql = "PRAGMA table_info({$table})";
        return $this->fetchAll($sql);
    }

    public function getIndexes(string $table): array {
        $sql = "PRAGMA index_list({$table})";
        return $this->fetchAll($sql);
    }

    public function optimize(): void {
        $this->pdo->exec('VACUUM');
        $this->pdo->exec('ANALYZE');
    }

    public function backup(string $backupPath): bool {
        try {
            $backup = new PDO(DB_DSN);
            $backup->exec("BACKUP TO '{$backupPath}'");
            return true;
        } catch (PDOException $e) {
            error_log("Backup failed: " . $e->getMessage());
            return false;
        }
    }

    public function getStats(): array {
        $stats = [
            'total_queries' => count($this->queryLog),
            'slow_queries' => 0,
            'average_execution_time' => 0
        ];

        if (!empty($this->queryLog)) {
            $totalTime = array_sum(array_column($this->queryLog, 'execution_time'));
            $stats['average_execution_time'] = $totalTime / count($this->queryLog);
            $stats['slow_queries'] = count(array_filter($this->queryLog, function($query) {
                return $query['execution_time'] > 0.1; // Queries slower than 100ms
            }));
        }

        return $stats;
    }
}
?>