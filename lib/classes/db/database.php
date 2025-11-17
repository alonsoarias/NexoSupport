<?php
namespace core\db;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Database Wrapper
 *
 * Wrapper simple de PDO para operaciones de base de datos.
 * Similar a $DB de Moodle.
 *
 * @package core\db
 */
class database {

    /** @var \PDO Conexión PDO */
    private \PDO $pdo;

    /** @var string Prefijo de tablas */
    private string $prefix;

    /** @var string Driver (mysql, pgsql, etc.) */
    private string $driver;

    /**
     * Constructor
     *
     * @param \PDO $pdo
     * @param string $prefix
     * @param string $driver
     */
    public function __construct(\PDO $pdo, string $prefix = '', string $driver = 'mysql') {
        $this->pdo = $pdo;
        $this->prefix = $prefix;
        $this->driver = $driver;

        // Configurar PDO
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_OBJ);
    }

    /**
     * Obtener conexión PDO
     *
     * @return \PDO
     */
    public function get_pdo(): \PDO {
        return $this->pdo;
    }

    /**
     * Obtener prefijo de tablas
     *
     * @return string
     */
    public function get_prefix(): string {
        return $this->prefix;
    }

    /**
     * Agregar prefijo a nombre de tabla
     *
     * @param string $table
     * @return string
     */
    private function add_prefix(string $table): string {
        if (empty($this->prefix)) {
            return $table;
        }
        return $this->prefix . $table;
    }

    /**
     * Ejecutar query SQL
     *
     * @param string $sql
     * @param array $params
     * @return \PDOStatement
     */
    public function execute(string $sql, array $params = []): \PDOStatement {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Obtener un solo registro
     *
     * @param string $table
     * @param array $conditions
     * @return object|null
     */
    public function get_record(string $table, array $conditions): ?object {
        $table = $this->add_prefix($table);

        $where = [];
        $params = [];

        foreach ($conditions as $field => $value) {
            $where[] = "$field = ?";
            $params[] = $value;
        }

        $sql = "SELECT * FROM $table WHERE " . implode(' AND ', $where) . " LIMIT 1";

        $stmt = $this->execute($sql, $params);
        $result = $stmt->fetch();

        return $result !== false ? $result : null;
    }

    /**
     * Obtener múltiples registros
     *
     * @param string $table
     * @param array $conditions
     * @return array
     */
    public function get_records(string $table, array $conditions = []): array {
        $table = $this->add_prefix($table);

        $sql = "SELECT * FROM $table";

        if (!empty($conditions)) {
            $where = [];
            $params = [];

            foreach ($conditions as $field => $value) {
                $where[] = "$field = ?";
                $params[] = $value;
            }

            $sql .= " WHERE " . implode(' AND ', $where);

            $stmt = $this->execute($sql, $params);
        } else {
            $stmt = $this->execute($sql);
        }

        return $stmt->fetchAll();
    }

    /**
     * Insertar registro
     *
     * @param string $table
     * @param object|array $record
     * @return int ID insertado
     */
    public function insert_record(string $table, object|array $record): int {
        $table = $this->add_prefix($table);

        $record = (array)$record;

        $fields = array_keys($record);
        $placeholders = array_fill(0, count($fields), '?');

        $sql = "INSERT INTO $table (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";

        $this->execute($sql, array_values($record));

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Actualizar registro
     *
     * @param string $table
     * @param object|array $record (debe tener 'id')
     * @return bool
     */
    public function update_record(string $table, object|array $record): bool {
        $table = $this->add_prefix($table);

        $record = (array)$record;

        if (!isset($record['id'])) {
            throw new \coding_exception('update_record requires id field');
        }

        $id = $record['id'];
        unset($record['id']);

        $set = [];
        $params = [];

        foreach ($record as $field => $value) {
            $set[] = "$field = ?";
            $params[] = $value;
        }

        $params[] = $id;

        $sql = "UPDATE $table SET " . implode(', ', $set) . " WHERE id = ?";

        $stmt = $this->execute($sql, $params);

        return $stmt->rowCount() > 0;
    }

    /**
     * Eliminar registro(s)
     *
     * @param string $table
     * @param array $conditions
     * @return bool
     */
    public function delete_records(string $table, array $conditions): bool {
        $table = $this->add_prefix($table);

        $where = [];
        $params = [];

        foreach ($conditions as $field => $value) {
            $where[] = "$field = ?";
            $params[] = $value;
        }

        $sql = "DELETE FROM $table WHERE " . implode(' AND ', $where);

        $stmt = $this->execute($sql, $params);

        return $stmt->rowCount() > 0;
    }

    /**
     * Verificar si existe un registro
     *
     * @param string $table
     * @param array $conditions
     * @return bool
     */
    public function record_exists(string $table, array $conditions): bool {
        return $this->get_record($table, $conditions) !== null;
    }

    /**
     * Contar registros
     *
     * @param string $table
     * @param array $conditions
     * @return int
     */
    public function count_records(string $table, array $conditions = []): int {
        $table = $this->add_prefix($table);

        $sql = "SELECT COUNT(*) as count FROM $table";

        if (!empty($conditions)) {
            $where = [];
            $params = [];

            foreach ($conditions as $field => $value) {
                $where[] = "$field = ?";
                $params[] = $value;
            }

            $sql .= " WHERE " . implode(' AND ', $where);

            $stmt = $this->execute($sql, $params);
        } else {
            $stmt = $this->execute($sql);
        }

        $result = $stmt->fetch();

        return (int)$result->count;
    }

    /**
     * Obtener manager de DDL
     *
     * @return ddl_manager
     */
    public function get_manager(): ddl_manager {
        return new ddl_manager($this);
    }
}
