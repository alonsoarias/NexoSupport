<?php

declare(strict_types=1);

namespace ISER\Core\Database;

use PDO;
use Exception;

/**
 * Database Adapter
 *
 * Maneja las diferencias de SQL entre diferentes motores de bases de datos
 *
 * @package ISER\Core\Database
 */
class DatabaseAdapter
{
    private string $driver;
    private PDO $pdo;

    /**
     * Constructor
     *
     * @param PDO $pdo Conexión PDO
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    /**
     * Obtener el driver actual
     *
     * @return string
     */
    public function getDriver(): string
    {
        return $this->driver;
    }

    /**
     * Verificar si es MySQL/MariaDB
     *
     * @return bool
     */
    public function isMySQL(): bool
    {
        return $this->driver === 'mysql';
    }

    /**
     * Verificar si es PostgreSQL
     *
     * @return bool
     */
    public function isPostgreSQL(): bool
    {
        return $this->driver === 'pgsql';
    }

    /**
     * Verificar si es SQLite
     *
     * @return bool
     */
    public function isSQLite(): bool
    {
        return $this->driver === 'sqlite';
    }

    /**
     * Crear base de datos
     *
     * @param string $dbName Nombre de la base de datos
     * @param string $charset Charset (solo MySQL)
     * @param string $collation Collation (solo MySQL)
     * @return bool
     * @throws Exception
     */
    public function createDatabase(string $dbName, string $charset = 'utf8mb4', string $collation = 'utf8mb4_unicode_ci'): bool
    {
        $dbName = $this->quoteIdentifier($dbName);

        switch ($this->driver) {
            case 'mysql':
                $sql = "CREATE DATABASE IF NOT EXISTS {$dbName}
                        CHARACTER SET {$charset}
                        COLLATE {$collation}";
                break;

            case 'pgsql':
                // PostgreSQL usa UTF8 por defecto
                $sql = "CREATE DATABASE {$dbName}
                        ENCODING 'UTF8'
                        LC_COLLATE = 'en_US.UTF-8'
                        LC_CTYPE = 'en_US.UTF-8'";
                break;

            case 'sqlite':
                // SQLite no requiere CREATE DATABASE
                return true;

            default:
                throw new Exception("Driver no soportado: {$this->driver}");
        }

        return $this->pdo->exec($sql) !== false;
    }

    /**
     * Mapear tipo de dato al tipo específico del motor
     *
     * @param string $type Tipo genérico
     * @param int|null $length Longitud
     * @return string Tipo específico del motor
     */
    public function mapDataType(string $type, ?int $length = null): string
    {
        $type = strtoupper($type);

        // Mapeos específicos por driver
        $mappings = [
            'mysql' => [
                'SERIAL' => 'BIGINT UNSIGNED AUTO_INCREMENT',
                'BIGSERIAL' => 'BIGINT UNSIGNED AUTO_INCREMENT',
                'BOOLEAN' => 'TINYINT(1)',
                'BYTEA' => 'BLOB',
                'TEXT' => $length ? "VARCHAR({$length})" : 'TEXT',
            ],
            'pgsql' => [
                'TINYINT' => 'SMALLINT',
                'DATETIME' => 'TIMESTAMP',
                'BLOB' => 'BYTEA',
                'LONGTEXT' => 'TEXT',
                'MEDIUMTEXT' => 'TEXT',
                'TINYTEXT' => 'TEXT',
                'AUTO_INCREMENT' => '', // Se maneja con SERIAL
            ],
            'sqlite' => [
                'TINYINT' => 'INTEGER',
                'SMALLINT' => 'INTEGER',
                'BIGINT' => 'INTEGER',
                'DATETIME' => 'TEXT',
                'BLOB' => 'BLOB',
                'BOOLEAN' => 'INTEGER',
            ]
        ];

        // Aplicar mapeo si existe
        if (isset($mappings[$this->driver][$type])) {
            return $mappings[$this->driver][$type];
        }

        // Si tiene longitud, agregarla
        if ($length && in_array($type, ['VARCHAR', 'CHAR', 'VARBINARY'])) {
            return "{$type}({$length})";
        }

        return $type;
    }

    /**
     * Construir definición de columna
     *
     * @param array $column Datos de la columna
     * @return string SQL de la columna
     */
    public function buildColumnDefinition(array $column): string
    {
        $name = $this->quoteIdentifier($column['name']);
        $type = $column['type'] ?? 'VARCHAR';
        $length = $column['length'] ?? null;

        // Manejar AUTO_INCREMENT / SERIAL
        $autoIncrement = $column['auto_increment'] ?? false;

        if ($autoIncrement) {
            if ($this->isPostgreSQL()) {
                // En PostgreSQL usar SERIAL o BIGSERIAL
                $sql = "{$name} " . ($type === 'BIGINT' ? 'BIGSERIAL' : 'SERIAL');
            } else {
                // En MySQL usar AUTO_INCREMENT
                $mappedType = $this->mapDataType($type, $length);
                $sql = "{$name} {$mappedType} AUTO_INCREMENT";
            }
        } else {
            $mappedType = $this->mapDataType($type, $length);
            $sql = "{$name} {$mappedType}";
        }

        // NULL / NOT NULL
        if (isset($column['null'])) {
            $sql .= $column['null'] ? ' NULL' : ' NOT NULL';
        }

        // DEFAULT
        if (isset($column['default'])) {
            $default = $column['default'];
            if ($default === 'NULL') {
                $sql .= ' DEFAULT NULL';
            } elseif ($default === 'CURRENT_TIMESTAMP') {
                $sql .= $this->isPostgreSQL() ? ' DEFAULT CURRENT_TIMESTAMP' : ' DEFAULT CURRENT_TIMESTAMP';
            } else {
                $sql .= " DEFAULT " . $this->pdo->quote($default);
            }
        }

        // COMMENT (solo MySQL)
        if (isset($column['comment']) && $this->isMySQL()) {
            $sql .= " COMMENT " . $this->pdo->quote($column['comment']);
        }

        return $sql;
    }

    /**
     * Construir SQL para índice
     *
     * @param string $tableName Nombre de la tabla
     * @param array $index Datos del índice
     * @return string SQL del índice
     */
    public function buildIndexSQL(string $tableName, array $index): string
    {
        $indexName = $this->quoteIdentifier($index['name']);
        $tableName = $this->quoteIdentifier($tableName);
        $columns = implode(', ', array_map([$this, 'quoteIdentifier'], $index['columns']));

        $type = strtoupper($index['type'] ?? 'INDEX');

        switch ($type) {
            case 'PRIMARY':
                return "ALTER TABLE {$tableName} ADD PRIMARY KEY ({$columns})";

            case 'UNIQUE':
                return "CREATE UNIQUE INDEX {$indexName} ON {$tableName} ({$columns})";

            case 'INDEX':
            case 'KEY':
                return "CREATE INDEX {$indexName} ON {$tableName} ({$columns})";

            case 'FULLTEXT':
                if ($this->isPostgreSQL()) {
                    // PostgreSQL no tiene FULLTEXT nativo, usar GIN
                    return "CREATE INDEX {$indexName} ON {$tableName} USING GIN(to_tsvector('english', {$columns}))";
                } else {
                    return "CREATE FULLTEXT INDEX {$indexName} ON {$tableName} ({$columns})";
                }

            default:
                return "CREATE INDEX {$indexName} ON {$tableName} ({$columns})";
        }
    }

    /**
     * Construir SQL para foreign key
     *
     * @param string $tableName Nombre de la tabla
     * @param array $fk Datos de la foreign key
     * @return string SQL de la foreign key
     */
    public function buildForeignKeySQL(string $tableName, array $fk): string
    {
        $fkName = $this->quoteIdentifier($fk['name']);
        $tableName = $this->quoteIdentifier($tableName);
        $column = $this->quoteIdentifier($fk['column']);
        $refTable = $this->quoteIdentifier($fk['references_table']);
        $refColumn = $this->quoteIdentifier($fk['references_column']);

        $sql = "ALTER TABLE {$tableName}
                ADD CONSTRAINT {$fkName}
                FOREIGN KEY ({$column})
                REFERENCES {$refTable}({$refColumn})";

        // ON DELETE
        if (isset($fk['on_delete'])) {
            $sql .= " ON DELETE " . strtoupper($fk['on_delete']);
        }

        // ON UPDATE
        if (isset($fk['on_update'])) {
            $sql .= " ON UPDATE " . strtoupper($fk['on_update']);
        }

        return $sql;
    }

    /**
     * Construir CREATE TABLE completo
     *
     * @param string $tableName Nombre de la tabla
     * @param array $columns Columnas
     * @param string|null $engine Motor (solo MySQL)
     * @param string|null $charset Charset (solo MySQL)
     * @param string|null $collation Collation (solo MySQL)
     * @return string SQL completo
     */
    public function buildCreateTableSQL(
        string $tableName,
        array $columns,
        ?string $engine = null,
        ?string $charset = null,
        ?string $collation = null
    ): string {
        $tableName = $this->quoteIdentifier($tableName);

        $columnDefinitions = [];
        foreach ($columns as $column) {
            $columnDefinitions[] = $this->buildColumnDefinition($column);
        }

        $sql = "CREATE TABLE IF NOT EXISTS {$tableName} (\n  ";
        $sql .= implode(",\n  ", $columnDefinitions);
        $sql .= "\n)";

        // Opciones específicas de MySQL
        if ($this->isMySQL()) {
            $sql .= " ENGINE=" . ($engine ?? 'InnoDB');
            $sql .= " DEFAULT CHARSET=" . ($charset ?? 'utf8mb4');
            $sql .= " COLLATE=" . ($collation ?? 'utf8mb4_unicode_ci');
        }

        return $sql;
    }

    /**
     * Escapar y citar identificador (tabla, columna, etc.)
     *
     * @param string $identifier Identificador
     * @return string Identificador escapado
     */
    public function quoteIdentifier(string $identifier): string
    {
        // Limpiar identificador
        $identifier = trim($identifier);

        switch ($this->driver) {
            case 'mysql':
                return '`' . str_replace('`', '``', $identifier) . '`';

            case 'pgsql':
                return '"' . str_replace('"', '""', $identifier) . '"';

            case 'sqlite':
                return '"' . str_replace('"', '""', $identifier) . '"';

            default:
                return $identifier;
        }
    }

    /**
     * Verificar si una tabla existe
     *
     * @param string $tableName Nombre de la tabla
     * @return bool
     */
    public function tableExists(string $tableName): bool
    {
        try {
            switch ($this->driver) {
                case 'mysql':
                    $stmt = $this->pdo->query("SHOW TABLES LIKE " . $this->pdo->quote($tableName));
                    return $stmt->rowCount() > 0;

                case 'pgsql':
                    $stmt = $this->pdo->prepare(
                        "SELECT EXISTS (
                            SELECT FROM information_schema.tables
                            WHERE table_schema = 'public'
                            AND table_name = ?
                        )"
                    );
                    $stmt->execute([$tableName]);
                    return (bool) $stmt->fetchColumn();

                case 'sqlite':
                    $stmt = $this->pdo->prepare(
                        "SELECT name FROM sqlite_master WHERE type='table' AND name=?"
                    );
                    $stmt->execute([$tableName]);
                    return $stmt->rowCount() > 0;

                default:
                    return false;
            }
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Obtener versión del motor de base de datos
     *
     * @return string
     */
    public function getVersion(): string
    {
        try {
            return $this->pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
        } catch (Exception $e) {
            return 'unknown';
        }
    }
}
