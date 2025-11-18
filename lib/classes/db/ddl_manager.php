<?php
namespace core\db;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * DDL Manager
 *
 * Gestiona operaciones de DDL (Data Definition Language)
 * como crear, modificar y eliminar tablas.
 *
 * @package core\db
 */
class ddl_manager {

    /** @var database Database instance */
    private database $db;

    /**
     * Constructor
     *
     * @param database $db
     */
    public function __construct(database $db) {
        $this->db = $db;
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
            $stmt = $pdo->query("SHOW TABLES LIKE '$tablename'");
            return $stmt->rowCount() > 0;
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

        $sql .= "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        try {
            $this->db->get_pdo()->exec($sql);

            // Crear índices
            foreach ($table->get_indexes() as $index) {
                $this->create_index($table->get_name(), $index);
            }

            return true;
        } catch (\PDOException $e) {
            throw new \coding_exception("Error creating table: " . $e->getMessage());
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

        $unique = $index->is_unique() ? 'UNIQUE' : '';
        $fields = implode(', ', $index->get_fields());

        $sql = "CREATE {$unique} INDEX {$index->get_name()} ON $tablename ($fields)";

        try {
            $this->db->get_pdo()->exec($sql);
            return true;
        } catch (\PDOException $e) {
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
        $sql .= $field->get_sql_type('mysql');

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
                return "UNIQUE KEY {$key->get_name()} ($fields)";

            case xmldb_key::TYPE_FOREIGN:
                $reftable = $this->db->get_prefix() . $key->get_reftable();
                $reffields = implode(', ', $key->get_reffields());
                return "FOREIGN KEY {$key->get_name()} ($fields) REFERENCES $reftable ($reffields)";

            default:
                return '';
        }
    }

    /**
     * Verificar si un campo existe en una tabla
     *
     * @param xmldb_table $table Table object or table name
     * @param string|xmldb_field $field Field name or field object
     * @return bool
     */
    public function field_exists($table, $field): bool {
        $tablename = is_object($table) ? $table->get_name() : $table;
        $fieldname = is_object($field) ? $field->get_name() : $field;

        $tablename = $this->db->get_prefix() . $tablename;

        try {
            $pdo = $this->db->get_pdo();
            $stmt = $pdo->query("SHOW COLUMNS FROM $tablename LIKE '$fieldname'");
            return $stmt->rowCount() > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Agregar un campo a una tabla existente
     *
     * @param xmldb_table $table Table object or table name
     * @param xmldb_field $field Field object to add
     * @param string|null $after Add after this field (optional)
     * @return bool
     */
    public function add_field($table, xmldb_field $field, $after = null): bool {
        $tablename = is_object($table) ? $table->get_name() : $table;
        $tablename = $this->db->get_prefix() . $tablename;

        $fieldsql = $this->get_field_sql($field);

        $sql = "ALTER TABLE $tablename ADD COLUMN $fieldsql";

        if ($after !== null) {
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
     * @param xmldb_table $table Table object or table name
     * @param xmldb_field $field Field object or field name
     * @return bool
     */
    public function drop_field($table, $field): bool {
        $tablename = is_object($table) ? $table->get_name() : $table;
        $fieldname = is_object($field) ? $field->get_name() : $field;

        $tablename = $this->db->get_prefix() . $tablename;

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
     * @param xmldb_table $table Table object or table name
     * @param xmldb_field $field Field object with new definition
     * @return bool
     */
    public function change_field_type($table, xmldb_field $field): bool {
        $tablename = is_object($table) ? $table->get_name() : $table;
        $tablename = $this->db->get_prefix() . $tablename;

        $fieldsql = $this->get_field_sql($field);

        $sql = "ALTER TABLE $tablename MODIFY COLUMN $fieldsql";

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
}
