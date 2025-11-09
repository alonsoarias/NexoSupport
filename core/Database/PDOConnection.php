<?php

/**
 * ISER Authentication System - PDO Connection Manager
 *
 * Manages PDO database connections with MySQL/MariaDB.
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
use PDOException;
use RuntimeException;

/**
 * PDOConnection Class
 *
 * Handles PDO connections using the Singleton pattern.
 */
class PDOConnection
{
    /**
     * PDO instance
     */
    private ?PDO $connection = null;

    /**
     * Database configuration
     */
    private array $config;

    /**
     * Singleton instance
     */
    private static ?PDOConnection $instance = null;

    /**
     * Transaction nesting level
     */
    private int $transactionLevel = 0;

    /**
     * Private constructor (Singleton pattern)
     *
     * @param array $config Database configuration
     */
    private function __construct(array $config)
    {
        $this->config = $config;
        $this->connect();
    }

    /**
     * Get singleton instance
     *
     * @param array|null $config Database configuration
     * @throws RuntimeException If no config provided on first call
     * @return PDOConnection
     */
    public static function getInstance(?array $config = null): PDOConnection
    {
        if (self::$instance === null) {
            if ($config === null) {
                throw new RuntimeException(
                    'Configuration must be provided on first call to getInstance()'
                );
            }
            self::$instance = new self($config);
        }

        return self::$instance;
    }

    /**
     * Establish database connection
     *
     * @throws RuntimeException If connection fails
     * @return void
     */
    private function connect(): void
    {
        try {
            $dsn = $this->buildDsn();
            $username = $this->config['username'];
            $password = $this->config['password'];

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->config['charset']} COLLATE {$this->config['collation']}",
            ];

            $this->connection = new PDO($dsn, $username, $password, $options);

            // Set SQL mode for strict standards
            $this->connection->exec("SET sql_mode='STRICT_ALL_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE'");

        } catch (PDOException $e) {
            throw new RuntimeException(
                'Database connection failed: ' . $e->getMessage(),
                (int) $e->getCode(),
                $e
            );
        }
    }

    /**
     * Build DSN string
     *
     * @return string DSN string
     */
    private function buildDsn(): string
    {
        $host = $this->config['host'];
        $port = $this->config['port'] ?? 3306;
        $database = $this->config['database'];
        $charset = $this->config['charset'] ?? 'utf8mb4';

        return "mysql:host={$host};port={$port};dbname={$database};charset={$charset}";
    }

    /**
     * Get the PDO connection
     *
     * @return PDO
     */
    public function getConnection(): PDO
    {
        // Check if connection is still alive
        if ($this->connection === null) {
            $this->connect();
        }

        return $this->connection;
    }

    /**
     * Execute a query and return PDOStatement
     *
     * @param string $query SQL query
     * @param array $params Query parameters
     * @throws RuntimeException If query execution fails
     * @return \PDOStatement
     */
    public function query(string $query, array $params = []): \PDOStatement
    {
        try {
            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new RuntimeException(
                'Query execution failed: ' . $e->getMessage() . "\nQuery: " . $query,
                (int) $e->getCode(),
                $e
            );
        }
    }

    /**
     * Execute a query and return affected rows count
     *
     * @param string $query SQL query
     * @param array $params Query parameters
     * @return int Number of affected rows
     */
    public function execute(string $query, array $params = []): int
    {
        $stmt = $this->query($query, $params);
        return $stmt->rowCount();
    }

    /**
     * Fetch single row
     *
     * @param string $query SQL query
     * @param array $params Query parameters
     * @return array|false Single row or false if no results
     */
    public function fetchOne(string $query, array $params = []): array|false
    {
        $stmt = $this->query($query, $params);
        return $stmt->fetch();
    }

    /**
     * Fetch all rows
     *
     * @param string $query SQL query
     * @param array $params Query parameters
     * @return array Array of rows
     */
    public function fetchAll(string $query, array $params = []): array
    {
        $stmt = $this->query($query, $params);
        return $stmt->fetchAll();
    }

    /**
     * Get last insert ID
     *
     * @return string Last insert ID
     */
    public function lastInsertId(): string
    {
        return $this->connection->lastInsertId();
    }

    /**
     * Begin transaction
     *
     * @return bool
     */
    public function beginTransaction(): bool
    {
        if ($this->transactionLevel === 0) {
            $result = $this->connection->beginTransaction();
            if ($result) {
                $this->transactionLevel++;
            }
            return $result;
        }

        // Nested transaction using savepoints
        $this->connection->exec("SAVEPOINT LEVEL{$this->transactionLevel}");
        $this->transactionLevel++;
        return true;
    }

    /**
     * Commit transaction
     *
     * @return bool
     */
    public function commit(): bool
    {
        if ($this->transactionLevel === 0) {
            return false;
        }

        $this->transactionLevel--;

        if ($this->transactionLevel === 0) {
            return $this->connection->commit();
        }

        // Release savepoint
        $this->connection->exec("RELEASE SAVEPOINT LEVEL{$this->transactionLevel}");
        return true;
    }

    /**
     * Rollback transaction
     *
     * @return bool
     */
    public function rollback(): bool
    {
        if ($this->transactionLevel === 0) {
            return false;
        }

        $this->transactionLevel--;

        if ($this->transactionLevel === 0) {
            return $this->connection->rollBack();
        }

        // Rollback to savepoint
        $this->connection->exec("ROLLBACK TO SAVEPOINT LEVEL{$this->transactionLevel}");
        return true;
    }

    /**
     * Check if in transaction
     *
     * @return bool
     */
    public function inTransaction(): bool
    {
        return $this->transactionLevel > 0;
    }

    /**
     * Get transaction level
     *
     * @return int
     */
    public function getTransactionLevel(): int
    {
        return $this->transactionLevel;
    }

    /**
     * Test database connection
     *
     * @return bool True if connection is valid
     */
    public function testConnection(): bool
    {
        try {
            $this->connection->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Get database server version
     *
     * @return string Server version
     */
    public function getServerVersion(): string
    {
        return $this->connection->getAttribute(PDO::ATTR_SERVER_VERSION);
    }

    /**
     * Get database name
     *
     * @return string Database name
     */
    public function getDatabaseName(): string
    {
        return $this->config['database'];
    }

    /**
     * Get table prefix
     *
     * @return string Table prefix
     */
    public function getPrefix(): string
    {
        return $this->config['prefix'] ?? '';
    }

    /**
     * Close the database connection
     *
     * @return void
     */
    public function close(): void
    {
        $this->connection = null;
        $this->transactionLevel = 0;
    }

    /**
     * Reset singleton instance (useful for testing)
     *
     * @return void
     */
    public static function reset(): void
    {
        if (self::$instance !== null) {
            self::$instance->close();
            self::$instance = null;
        }
    }

    /**
     * Prevent cloning of the instance
     */
    private function __clone() {}

    /**
     * Prevent unserializing of the instance
     */
    public function __wakeup()
    {
        throw new RuntimeException('Cannot unserialize singleton');
    }
}
