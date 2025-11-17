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
     * @param xmldb_field $field
     * @return void
     */
    public function add_field(xmldb_field $field): void {
        $this->fields[$field->get_name()] = $field;
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
     * @param xmldb_key $key
     * @return void
     */
    public function add_key(xmldb_key $key): void {
        $this->keys[$key->get_name()] = $key;
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
     * @param xmldb_index $index
     * @return void
     */
    public function add_index(xmldb_index $index): void {
        $this->indexes[$index->get_name()] = $index;
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
