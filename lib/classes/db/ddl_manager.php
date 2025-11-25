<?php
namespace core\db;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * DDL Manager
 *
 * Gestiona operaciones de DDL (Data Definition Language)
 * como crear, modificar y eliminar tablas.
 *
 * Soporta múltiples drivers: MySQL, PostgreSQL, SQLite
 *
 * @package core\db
 */
class ddl_manager {

    /** @var database Database instance */
    private database $db;

    /** @var string Database driver */
    private string $driver;

    /**
     * Constructor
     *
     * @param database $db
     */
    public function __construct(database $db) {
        $this->db = $db;
        // Get driver from PDO
        $this->driver = $db->get_pdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);
    }

    /**
     * Get current driver
     *
     * @return string
     */
    public function get_driver(): string {
        return $this->driver;
    }

    /**
     * Verificar si una tabla existe
     *
     * @param string $tablename
     * @return bool
     */
    public function table_exists(string $tablename): bool {
        $tablename = $this->db->get_prefix() . $tablename;

        try {
            $pdo = $this->db->get_pdo();

            switch ($this->driver) {
                case 'mysql':
                    $stmt = $pdo->query("SHOW TABLES LIKE '$tablename'");
                    return $stmt->rowCount() > 0;

                case 'pgsql':
                    $stmt = $pdo->prepare(
                        "SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_name = ?)"
                    );
                    $stmt->execute([$tablename]);
                    return (bool)$stmt->fetchColumn();

                case 'sqlite':
                    $stmt = $pdo->prepare(
                        "SELECT name FROM sqlite_master WHERE type='table' AND name = ?"
                    );
                    $stmt->execute([$tablename]);
                    return ($stmt->fetch() !== false);

                default:
                    return false;
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Crear tabla desde definición XMLDB
     *
     * @param xmldb_table $table
     * @return bool
     */
    public function create_table(xmldb_table $table): bool {
        $tablename = $this->db->get_prefix() . $table->get_name();

        $sql = "CREATE TABLE $tablename (\n";

        $fieldssql = [];

        foreach ($table->get_fields() as $field) {
            $fieldsql = $this->get_field_sql($field);
            $fieldssql[] = $fieldsql;
        }

        $sql .= "  " . implode(",\n  ", $fieldssql);

        // Agregar claves
        foreach ($table->get_keys() as $key) {
            $keysql = $this->get_key_sql($key);
            if (!empty($keysql)) {
                $sql .= ",\n  " . $keysql;
            }
        }

        // Driver-specific table options
        switch ($this->driver) {
            case 'mysql':
                $sql .= "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                break;
            case 'pgsql':
            case 'sqlite':
            default:
                $sql .= "\n)";
                break;
        }

        try {
            $this->db->get_pdo()->exec($sql);

            // Crear índices
            foreach ($table->get_indexes() as $index) {
                $this->create_index($table->get_name(), $index);
            }

            return true;
        } catch (\PDOException $e) {
            throw new \coding_exception("Error creating table: " . $e->getMessage() . "\nSQL: " . $sql);
        }
    }

    /**
     * Crear índice
     *
     * @param string $tablename
     * @param xmldb_index $index
     * @return bool
     */
    private function create_index(string $tablename, xmldb_index $index): bool {
        $tablename = $this->db->get_prefix() . $tablename;
        $indexname = $this->db->get_prefix() . $index->get_name();

        $unique = $index->is_unique() ? 'UNIQUE' : '';
        $fields = implode(', ', $index->get_fields());

        $sql = "CREATE {$unique} INDEX {$indexname} ON $tablename ($fields)";

        try {
            $this->db->get_pdo()->exec($sql);
            return true;
        } catch (\PDOException $e) {
            // Ignore duplicate index errors
            if (strpos($e->getMessage(), 'already exists') !== false ||
                strpos($e->getMessage(), 'Duplicate') !== false) {
                return true;
            }
            throw new \coding_exception("Error creating index: " . $e->getMessage());
        }
    }

    /**
     * Obtener SQL de un campo
     *
     * @param xmldb_field $field
     * @return string
     */
    private function get_field_sql(xmldb_field $field): string {
        $sql = $field->get_name() . ' ';
        $sql .= $field->get_sql_type($this->driver);

        if ($field->is_notnull()) {
            $sql .= ' NOT NULL';
        }

        $default = $field->get_default();
        if ($default !== null) {
            if (is_string($default)) {
                $sql .= " DEFAULT '$default'";
            } else {
                $sql .= " DEFAULT $default";
            }
        }

        return $sql;
    }

    /**
     * Obtener SQL de una clave
     *
     * @param xmldb_key $key
     * @return string
     */
    private function get_key_sql(xmldb_key $key): string {
        $fields = implode(', ', $key->get_fields());

        switch ($key->get_type()) {
            case xmldb_key::TYPE_PRIMARY:
                return "PRIMARY KEY ($fields)";

            case xmldb_key::TYPE_UNIQUE:
                // SQLite doesn't support named UNIQUE constraints in the same way
                if ($this->driver === 'sqlite') {
                    return "UNIQUE ($fields)";
                }
                return "UNIQUE KEY {$key->get_name()} ($fields)";

            case xmldb_key::TYPE_FOREIGN:
                $reftable = $this->db->get_prefix() . $key->get_reftable();
                $reffields = implode(', ', $key->get_reffields());
                // SQLite and standard SQL FOREIGN KEY syntax
                return "FOREIGN KEY ($fields) REFERENCES $reftable ($reffields)";

            default:
                return '';
        }
    }

    /**
     * Verificar si un campo existe en una tabla
     *
     * @param xmldb_table|string $table Table object or table name
     * @param string|xmldb_field $field Field name or field object
     * @return bool
     */
    public function field_exists($table, $field): bool {
        $tablename = is_object($table) ? $table->get_name() : $table;
        $fieldname = is_object($field) ? $field->get_name() : $field;

        $tablename = $this->db->get_prefix() . $tablename;

        try {
            $pdo = $this->db->get_pdo();

            switch ($this->driver) {
                case 'mysql':
                    $stmt = $pdo->query("SHOW COLUMNS FROM $tablename LIKE '$fieldname'");
                    return $stmt->rowCount() > 0;

                case 'pgsql':
                    $stmt = $pdo->prepare(
                        "SELECT column_name FROM information_schema.columns
                         WHERE table_name = ? AND column_name = ?"
                    );
                    $stmt->execute([$tablename, $fieldname]);
                    return ($stmt->fetch() !== false);

                case 'sqlite':
                    $stmt = $pdo->query("PRAGMA table_info($tablename)");
                    while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                        if ($row['name'] === $fieldname) {
                            return true;
                        }
                    }
                    return false;

                default:
                    return false;
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Agregar un campo a una tabla existente
     *
     * @param xmldb_table|string $table Table object or table name
     * @param xmldb_field $field Field object to add
     * @param string|null $after Add after this field (optional, MySQL only)
     * @return bool
     */
    public function add_field($table, xmldb_field $field, $after = null): bool {
        $tablename = is_object($table) ? $table->get_name() : $table;
        $tablename = $this->db->get_prefix() . $tablename;

        $fieldsql = $this->get_field_sql($field);

        $sql = "ALTER TABLE $tablename ADD COLUMN $fieldsql";

        // AFTER is MySQL-specific
        if ($after !== null && $this->driver === 'mysql') {
            $sql .= " AFTER $after";
        }

        try {
            $this->db->get_pdo()->exec($sql);
            return true;
        } catch (\PDOException $e) {
            throw new \coding_exception("Error adding field: " . $e->getMessage());
        }
    }

    /**
     * Eliminar un campo de una tabla
     *
     * Note: SQLite has limited ALTER TABLE support
     *
     * @param xmldb_table|string $table Table object or table name
     * @param xmldb_field|string $field Field object or field name
     * @return bool
     */
    public function drop_field($table, $field): bool {
        $tablename = is_object($table) ? $table->get_name() : $table;
        $fieldname = is_object($field) ? $field->get_name() : $field;

        $tablename = $this->db->get_prefix() . $tablename;

        // SQLite 3.35+ supports DROP COLUMN
        $sql = "ALTER TABLE $tablename DROP COLUMN $fieldname";

        try {
            $this->db->get_pdo()->exec($sql);
            return true;
        } catch (\PDOException $e) {
            throw new \coding_exception("Error dropping field: " . $e->getMessage());
        }
    }

    /**
     * Modificar un campo en una tabla
     *
     * Note: SQLite has very limited ALTER TABLE support
     *
     * @param xmldb_table|string $table Table object or table name
     * @param xmldb_field $field Field object with new definition
     * @return bool
     */
    public function change_field_type($table, xmldb_field $field): bool {
        $tablename = is_object($table) ? $table->get_name() : $table;
        $tablename = $this->db->get_prefix() . $tablename;

        $fieldsql = $this->get_field_sql($field);

        switch ($this->driver) {
            case 'mysql':
                $sql = "ALTER TABLE $tablename MODIFY COLUMN $fieldsql";
                break;

            case 'pgsql':
                // PostgreSQL uses ALTER COLUMN
                $sql = "ALTER TABLE $tablename ALTER COLUMN {$field->get_name()} TYPE {$field->get_sql_type('pgsql')}";
                break;

            case 'sqlite':
                // SQLite doesn't support ALTER COLUMN - would need to recreate table
                // For now, just return true (no-op)
                return true;

            default:
                $sql = "ALTER TABLE $tablename MODIFY COLUMN $fieldsql";
                break;
        }

        try {
            $this->db->get_pdo()->exec($sql);
            return true;
        } catch (\PDOException $e) {
            throw new \coding_exception("Error changing field type: " . $e->getMessage());
        }
    }

    /**
     * Eliminar tabla
     *
     * @param string $tablename
     * @return bool
     */
    public function drop_table(string $tablename): bool {
        $tablename = $this->db->get_prefix() . $tablename;

        try {
            $this->db->get_pdo()->exec("DROP TABLE IF EXISTS $tablename");
            return true;
        } catch (\PDOException $e) {
            throw new \coding_exception("Error dropping table: " . $e->getMessage());
        }
    }

    /**
     * Get list of all tables (for debugging/admin)
     *
     * @return array List of table names (without prefix)
     */
    public function get_tables(): array {
        $tables = [];
        $prefix = $this->db->get_prefix();
        $prefixLen = strlen($prefix);

        try {
            $pdo = $this->db->get_pdo();

            switch ($this->driver) {
                case 'mysql':
                    $stmt = $pdo->query("SHOW TABLES");
                    while ($row = $stmt->fetch(\PDO::FETCH_NUM)) {
                        $name = $row[0];
                        if (str_starts_with($name, $prefix)) {
                            $tables[] = substr($name, $prefixLen);
                        }
                    }
                    break;

                case 'pgsql':
                    $stmt = $pdo->query(
                        "SELECT table_name FROM information_schema.tables
                         WHERE table_schema = 'public'"
                    );
                    while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                        $name = $row['table_name'];
                        if (str_starts_with($name, $prefix)) {
                            $tables[] = substr($name, $prefixLen);
                        }
                    }
                    break;

                case 'sqlite':
                    $stmt = $pdo->query(
                        "SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'"
                    );
                    while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                        $name = $row['name'];
                        if (str_starts_with($name, $prefix)) {
                            $tables[] = substr($name, $prefixLen);
                        }
                    }
                    break;
            }
        } catch (\Exception $e) {
            // Return empty on error
        }

        return $tables;
    }
}
