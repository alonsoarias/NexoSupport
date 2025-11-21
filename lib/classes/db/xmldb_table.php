<?php
namespace core\db;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Representación de una tabla en XMLDB
 *
 * @package core\db
 */
class xmldb_table {

    /** @var string Nombre de la tabla */
    private string $name;

    /** @var array Campos de la tabla */
    private array $fields = [];

    /** @var array Claves de la tabla */
    private array $keys = [];

    /** @var array Índices de la tabla */
    private array $indexes = [];

    /** @var string|null Comentario de la tabla */
    private ?string $comment = null;

    /**
     * Constructor
     *
     * @param string $name Nombre de la tabla
     */
    public function __construct(string $name) {
        $this->name = $name;
    }

    /**
     * Obtener nombre de la tabla
     *
     * @return string
     */
    public function get_name(): string {
        return $this->name;
    }

    /**
     * Agregar campo
     *
     * Supports two calling styles:
     * 1. Moodle-style: add_field($name, $type, $length, $notnull, $sequence, $default)
     * 2. Object-style: add_field($field) where $field is xmldb_field
     *
     * @param string|xmldb_field $name Field name or xmldb_field object
     * @param string|null $type Field type (TYPE_INT, TYPE_CHAR, etc.)
     * @param int|null $length Field length
     * @param bool $notnull Whether field is NOT NULL
     * @param bool $sequence Whether field is auto-increment
     * @param mixed $default Default value
     * @return void
     */
    public function add_field(string|xmldb_field $name, ?string $type = null, ?int $length = null,
            bool $notnull = false, bool $sequence = false, mixed $default = null): void {
        if ($name instanceof xmldb_field) {
            $this->fields[$name->get_name()] = $name;
        } else {
            $field = new xmldb_field($name, $type ?? 'char');
            if ($length !== null) {
                $field->set_length($length);
            }
            $field->set_notnull($notnull);
            $field->set_sequence($sequence);
            if ($default !== null) {
                $field->set_default($default);
            }
            $this->fields[$name] = $field;
        }
    }

    /**
     * Obtener campos
     *
     * @return array
     */
    public function get_fields(): array {
        return $this->fields;
    }

    /**
     * Obtener campo por nombre
     *
     * @param string $name
     * @return xmldb_field|null
     */
    public function get_field(string $name): ?xmldb_field {
        return $this->fields[$name] ?? null;
    }

    /**
     * Agregar clave
     *
     * Supports two calling styles:
     * 1. Moodle-style: add_key($name, $type, $fields, $reftable, $reffields)
     * 2. Object-style: add_key($key) where $key is xmldb_key
     *
     * @param string|xmldb_key $name Key name or xmldb_key object
     * @param string|null $type Key type (TYPE_PRIMARY, TYPE_UNIQUE, TYPE_FOREIGN)
     * @param array|null $fields Fields in the key
     * @param string|null $reftable Referenced table (for foreign keys)
     * @param array|null $reffields Referenced fields (for foreign keys)
     * @return void
     */
    public function add_key(string|xmldb_key $name, ?string $type = null, ?array $fields = null,
            ?string $reftable = null, ?array $reffields = null): void {
        if ($name instanceof xmldb_key) {
            $this->keys[$name->get_name()] = $name;
        } else {
            $key = new xmldb_key($name, $type ?? xmldb_key::TYPE_PRIMARY, $fields ?? []);
            if ($reftable !== null) {
                $key->set_reftable($reftable);
            }
            if ($reffields !== null) {
                $key->set_reffields($reffields);
            }
            $this->keys[$name] = $key;
        }
    }

    /**
     * Obtener claves
     *
     * @return array
     */
    public function get_keys(): array {
        return $this->keys;
    }

    /**
     * Agregar índice
     *
     * Supports two calling styles:
     * 1. Moodle-style: add_index($name, $unique, $fields)
     * 2. Object-style: add_index($index) where $index is xmldb_index
     *
     * @param string|xmldb_index $name Index name or xmldb_index object
     * @param bool $unique Whether index is unique
     * @param array|null $fields Fields in the index
     * @return void
     */
    public function add_index(string|xmldb_index $name, bool $unique = false, ?array $fields = null): void {
        if ($name instanceof xmldb_index) {
            $this->indexes[$name->get_name()] = $name;
        } else {
            $index = new xmldb_index($name, $unique, $fields ?? []);
            $this->indexes[$name] = $index;
        }
    }

    /**
     * Obtener índices
     *
     * @return array
     */
    public function get_indexes(): array {
        return $this->indexes;
    }

    /**
     * Establecer comentario
     *
     * @param string $comment
     * @return void
     */
    public function set_comment(string $comment): void {
        $this->comment = $comment;
    }

    /**
     * Obtener comentario
     *
     * @return string|null
     */
    public function get_comment(): ?string {
        return $this->comment;
    }
}
