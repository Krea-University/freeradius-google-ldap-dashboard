<?php
/**
 * Database Helper Class
 *
 * Provides PDO database connection and query helpers
 */

class Database
{
    private static $instance = null;
    private $pdo;
    private $config;

    private function __construct()
    {
        $this->config = require __DIR__ . '/../config/database.php';
        $this->connect();
    }

    /**
     * Get singleton instance
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Establish PDO connection
     */
    private function connect()
    {
        $config = $this->config['radius'];

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        );

        try {
            error_log("Attempting DB connection: host={$config['host']}, port={$config['port']}, db={$config['database']}, user={$config['username']}");
            $this->pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
            error_log("Database connection successful");
        } catch (PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage() . ' | DSN: ' . $dsn . ' | User: ' . $config['username']);
            error_log('Config: ' . json_encode($config));
            throw new Exception('Database connection failed. Please check configuration. Error: ' . $e->getMessage());
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
     * Execute a query with optional parameters
     *
     * @param string $sql SQL query
     * @param array $params Parameters for prepared statement
     * @return PDOStatement
     */
    public function query($sql, $params = [])
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log('Query failed: ' . $e->getMessage() . ' | SQL: ' . $sql);
            throw $e;
        }
    }

    /**
     * Fetch all rows
     */
    public function fetchAll($sql, $params = [])
    {
        return $this->query($sql, $params)->fetchAll();
    }

    /**
     * Fetch single row
     */
    public function fetchOne($sql, $params = [])
    {
        return $this->query($sql, $params)->fetch();
    }

    /**
     * Fetch single column value
     */
    public function fetchColumn($sql, $params = [])
    {
        return $this->query($sql, $params)->fetchColumn();
    }

    /**
     * Insert record and return last insert ID
     */
    public function insert($table, $data)
    {
        $columns = array_keys($data);
        $placeholders = array_map(function ($col) {
            return ':' . $col;
        }, $columns);

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $params = [];
        foreach ($data as $key => $value) {
            $params[':' . $key] = $value;
        }

        $this->query($sql, $params);
        return $this->pdo->lastInsertId();
    }

    /**
     * Update records
     */
    public function update($table, $data, $where, $whereParams = [])
    {
        $setClauses = [];
        $params = [];

        foreach ($data as $key => $value) {
            $setClauses[] = "$key = :set_$key";
            $params[':set_' . $key] = $value;
        }

        // Convert positional parameters to named parameters for WHERE clause
        $whereNamedParams = [];
        if (!empty($whereParams)) {
            foreach ($whereParams as $i => $value) {
                $whereNamedParams[':where_' . $i] = $value;
                $where = preg_replace('/\?/', ':where_' . $i, $where, 1);
                $params[':where_' . $i] = $value;
            }
        }

        $sql = sprintf(
            'UPDATE %s SET %s WHERE %s',
            $table,
            implode(', ', $setClauses),
            $where
        );

        return $this->query($sql, $params)->rowCount();
    }

    /**
     * Delete records
     */
    public function delete($table, $where, $whereParams = [])
    {
        $sql = sprintf('DELETE FROM %s WHERE %s', $table, $where);
        return $this->query($sql, $whereParams)->rowCount();
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
        return $this->pdo->rollBack();
    }
}
