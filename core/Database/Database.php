<?php

/**
 * ISER Authentication System - Database Abstraction Layer
 *
 * High-level database operations abstraction.
 *
 * @package    ISER\Core\Database
 * @category   Core
 * @author     ISER Development Team
 * @copyright  2024 ISER
 * @license    Proprietary
 * @version    1.0.0
 * @since      Phase 1
 */

namespace ISER\Core\Database;

use PDO;
use RuntimeException;

/**
 * Database Class
 *
 * Provides high-level database operations using PDOConnection.
 */
class Database
{
    /**
     * PDO connection instance
     */
    private PDOConnection $connection;

    /**
     * Table prefix
     */
    private string $prefix;

    /**
     * Query log
     */
    private array $queryLog = [];

    /**
     * Enable query logging
     */
    private bool $enableQueryLog = false;

    /**
     * Constructor
     *
     * @param PDOConnection $connection PDO connection instance
     */
    public function __construct(PDOConnection $connection)
    {
        $this->connection = $connection;
        $this->prefix = $connection->getPrefix();
    }

    /**
     * Get prefixed table name
     *
     * @param string $table Table name
     * @return string Prefixed table name
     */
    public function table(string $table): string
    {
        return $this->prefix . $table;
    }

    /**
     * Insert a record into the database
     *
     * @param string $table Table name
     * @param array $data Data to insert
     * @return int|string Last insert ID
     */
    public function insert(string $table, array $data): int|string
    {
        $table = $this->table($table);
        $columns = array_keys($data);
        $placeholders = array_map(fn($col) => ':' . $col, $columns);

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

        $this->logQuery($sql, $params);
        $this->connection->execute($sql, $params);

        return $this->connection->lastInsertId();
    }

    /**
     * Update records in the database
     *
     * @param string $table Table name
     * @param array $data Data to update
     * @param array $where Where conditions
     * @return int Number of affected rows
     */
    public function update(string $table, array $data, array $where): int
    {
        $table = $this->table($table);
        $setClause = [];
        $params = [];

        foreach ($data as $key => $value) {
            $setClause[] = "{$key} = :set_{$key}";
            $params[':set_' . $key] = $value;
        }

        $whereClause = [];
        foreach ($where as $key => $value) {
            $whereClause[] = "{$key} = :where_{$key}";
            $params[':where_' . $key] = $value;
        }

        $sql = sprintf(
            'UPDATE %s SET %s WHERE %s',
            $table,
            implode(', ', $setClause),
            implode(' AND ', $whereClause)
        );

        $this->logQuery($sql, $params);
        return $this->connection->execute($sql, $params);
    }

    /**
     * Delete records from the database
     *
     * @param string $table Table name
     * @param array $where Where conditions
     * @return int Number of affected rows
     */
    public function delete(string $table, array $where): int
    {
        $table = $this->table($table);
        $whereClause = [];
        $params = [];

        foreach ($where as $key => $value) {
            $whereClause[] = "{$key} = :{$key}";
            $params[':' . $key] = $value;
        }

        $sql = sprintf(
            'DELETE FROM %s WHERE %s',
            $table,
            implode(' AND ', $whereClause)
        );

        $this->logQuery($sql, $params);
        return $this->connection->execute($sql, $params);
    }

    /**
     * Select records from the database
     *
     * @param string $table Table name
     * @param array $where Where conditions
     * @param array $columns Columns to select
     * @param array $options Additional options (order, limit, offset)
     * @return array Array of records
     */
    public function select(
        string $table,
        array $where = [],
        array $columns = ['*'],
        array $options = []
    ): array {
        $table = $this->table($table);
        $params = [];

        $sql = sprintf(
            'SELECT %s FROM %s',
            implode(', ', $columns),
            $table
        );

        if (!empty($where)) {
            $whereClause = [];
            foreach ($where as $key => $value) {
                $whereClause[] = "{$key} = :{$key}";
                $params[':' . $key] = $value;
            }
            $sql .= ' WHERE ' . implode(' AND ', $whereClause);
        }

        if (isset($options['order'])) {
            $sql .= ' ORDER BY ' . $options['order'];
        }

        if (isset($options['limit'])) {
            $sql .= ' LIMIT ' . (int) $options['limit'];
        }

        if (isset($options['offset'])) {
            $sql .= ' OFFSET ' . (int) $options['offset'];
        }

        $this->logQuery($sql, $params);
        return $this->connection->fetchAll($sql, $params);
    }

    /**
     * Select a single record
     *
     * @param string $table Table name
     * @param array $where Where conditions
     * @param array $columns Columns to select
     * @return array|false Single record or false
     */
    public function selectOne(
        string $table,
        array $where,
        array $columns = ['*']
    ): array|false {
        $results = $this->select($table, $where, $columns, ['limit' => 1]);
        return $results[0] ?? false;
    }

    /**
     * Count records in a table
     *
     * @param string $table Table name
     * @param array $where Where conditions
     * @return int Count of records
     */
    public function count(string $table, array $where = []): int
    {
        $table = $this->table($table);
        $params = [];

        $sql = "SELECT COUNT(*) as count FROM {$table}";

        if (!empty($where)) {
            $whereClause = [];
            foreach ($where as $key => $value) {
                $whereClause[] = "{$key} = :{$key}";
                $params[':' . $key] = $value;
            }
            $sql .= ' WHERE ' . implode(' AND ', $whereClause);
        }

        $this->logQuery($sql, $params);
        $result = $this->connection->fetchOne($sql, $params);

        return (int) ($result['count'] ?? 0);
    }

    /**
     * Check if a record exists
     *
     * @param string $table Table name
     * @param array $where Where conditions
     * @return bool True if record exists
     */
    public function exists(string $table, array $where): bool
    {
        return $this->count($table, $where) > 0;
    }

    /**
     * Execute raw SQL query
     *
     * @param string $sql SQL query
     * @param array $params Query parameters
     * @return array Query results
     */
    public function query(string $sql, array $params = []): array
    {
        $this->logQuery($sql, $params);
        return $this->connection->fetchAll($sql, $params);
    }

    /**
     * Execute raw SQL statement (INSERT, UPDATE, DELETE)
     *
     * @param string $sql SQL statement
     * @param array $params Statement parameters
     * @return int Number of affected rows
     */
    public function execute(string $sql, array $params = []): int
    {
        $this->logQuery($sql, $params);
        return $this->connection->execute($sql, $params);
    }

    /**
     * Begin transaction
     *
     * @return bool
     */
    public function beginTransaction(): bool
    {
        return $this->connection->beginTransaction();
    }

    /**
     * Commit transaction
     *
     * @return bool
     */
    public function commit(): bool
    {
        return $this->connection->commit();
    }

    /**
     * Rollback transaction
     *
     * @return bool
     */
    public function rollback(): bool
    {
        return $this->connection->rollback();
    }

    /**
     * Execute a transaction
     *
     * @param callable $callback Callback to execute in transaction
     * @throws RuntimeException If transaction fails
     * @return mixed Callback result
     */
    public function transaction(callable $callback): mixed
    {
        $this->beginTransaction();

        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (\Exception $e) {
            $this->rollback();
            throw new RuntimeException(
                'Transaction failed: ' . $e->getMessage(),
                (int) $e->getCode(),
                $e
            );
        }
    }

    /**
     * Get the last insert ID
     *
     * @return string
     */
    public function lastInsertId(): string
    {
        return $this->connection->lastInsertId();
    }

    /**
     * Enable query logging
     *
     * @param bool $enable Enable or disable
     * @return void
     */
    public function enableQueryLog(bool $enable = true): void
    {
        $this->enableQueryLog = $enable;
    }

    /**
     * Log a query
     *
     * @param string $sql SQL query
     * @param array $params Query parameters
     * @return void
     */
    private function logQuery(string $sql, array $params = []): void
    {
        if ($this->enableQueryLog) {
            $this->queryLog[] = [
                'sql' => $sql,
                'params' => $params,
                'time' => microtime(true),
            ];
        }
    }

    /**
     * Get query log
     *
     * @return array Query log
     */
    public function getQueryLog(): array
    {
        return $this->queryLog;
    }

    /**
     * Clear query log
     *
     * @return void
     */
    public function clearQueryLog(): void
    {
        $this->queryLog = [];
    }

    /**
     * Get underlying PDO connection
     *
     * @return PDO
     */
    public function getPdo(): PDO
    {
        return $this->connection->getConnection();
    }

    /**
     * Get PDOConnection instance
     *
     * @return PDOConnection
     */
    public function getConnection(): PDOConnection
    {
        return $this->connection;
    }

    /**
     * Test database connection
     *
     * @return bool
     */
    public function testConnection(): bool
    {
        return $this->connection->testConnection();
    }

    /**
     * Get database server version
     *
     * @return string
     */
    public function getServerVersion(): string
    {
        return $this->connection->getServerVersion();
    }

    /**
     * Get table prefix
     *
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }
}
