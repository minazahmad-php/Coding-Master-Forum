<?php

namespace App\Core;

use PDO;
use PDOException;

/**
 * Database Manager
 * Handles database connections and queries
 */
class Database
{
    private $pdo;
    private $config;
    private $logger;

    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->logger = new Logger($config);
        $this->connect();
    }

    /**
     * Establish database connection
     */
    private function connect()
    {
        try {
            $host = $this->config->get('database.host', 'localhost');
            $dbname = $this->config->get('database.database');
            $username = $this->config->get('database.username');
            $password = $this->config->get('database.password');
            $charset = $this->config->get('database.charset', 'utf8mb4');

            $dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset}",
                PDO::ATTR_PERSISTENT => false,
                PDO::ATTR_TIMEOUT => 30,
                PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
                PDO::ATTR_STRINGIFY_FETCHES => false,
                PDO::ATTR_CASE => PDO::CASE_NATURAL,
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                PDO::MYSQL_ATTR_LOCAL_INFILE => true
            ];

            $this->pdo = new PDO($dsn, $username, $password, $options);
            
        } catch (PDOException $e) {
            $this->logger->error('Database connection failed: ' . $e->getMessage());
            // Fallback error handling if logger fails
            error_log('Database connection failed: ' . $e->getMessage());
            throw new \Exception('Database connection failed: ' . $e->getMessage());
        }
    }

    /**
     * Get PDO instance
     */
    public function getPdo()
    {
        return $this->pdo;
    }

    /**
     * Execute a query
     */
    public function query($sql, $params = [])
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            $this->logger->error('Query failed: ' . $e->getMessage(), [
                'sql' => $sql,
                'params' => $params
            ]);
            // Fallback error handling
            error_log('Query failed: ' . $e->getMessage() . ' SQL: ' . $sql);
            throw $e;
        }
    }

    /**
     * Fetch single row
     */
    public function fetch($sql, $params = [])
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }

    /**
     * Fetch all rows
     */
    public function fetchAll($sql, $params = [])
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Insert data and return last insert ID
     */
    public function insert($table, $data)
    {
        $columns = implode(',', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        
        $this->query($sql, $data);
        return $this->pdo->lastInsertId();
    }

    /**
     * Update data
     */
    public function update($table, $data, $where, $whereParams = [])
    {
        $setClause = [];
        foreach (array_keys($data) as $column) {
            $setClause[] = "{$column} = :{$column}";
        }
        $setClause = implode(', ', $setClause);
        
        $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";
        
        $params = array_merge($data, $whereParams);
        $this->query($sql, $params);
        
        return $this->pdo->rowCount();
    }

    /**
     * Delete data
     */
    public function delete($table, $where, $params = [])
    {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $this->query($sql, $params);
        
        return $this->pdo->rowCount();
    }

    /**
     * Begin transaction
     */
    public function beginTransaction()
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit()
    {
        return $this->pdo->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback()
    {
        return $this->pdo->rollback();
    }

    /**
     * Check if table exists
     */
    public function tableExists($table)
    {
        $sql = "SHOW TABLES LIKE ?";
        $result = $this->fetch($sql, [$table]);
        return !empty($result);
    }

    /**
     * Get table structure
     */
    public function getTableStructure($table)
    {
        $sql = "DESCRIBE {$table}";
        return $this->fetchAll($sql);
    }
}