<?php
namespace core\db;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Representación de una clave en XMLDB
 *
 * @package core\db
 */
class xmldb_key {

    /** Tipos de claves */
    const TYPE_PRIMARY = 'primary';
    const TYPE_UNIQUE = 'unique';
    const TYPE_FOREIGN = 'foreign';

    /** @var string Nombre de la clave */
    private string $name;

    /** @var string Tipo de clave */
    private string $type;

    /** @var array Campos de la clave */
    private array $fields = [];

    /** @var string|null Tabla referenciada (para foreign keys) */
    private ?string $reftable = null;

    /** @var array Campos referenciados (para foreign keys) */
    private array $reffields = [];

    /**
     * Constructor
     *
     * @param string $name Nombre de la clave
     * @param string $type Tipo de clave
     * @param array $fields Campos
     */
    public function __construct(string $name, string $type, array $fields = []) {
        $this->name = $name;
        $this->type = $type;
        $this->fields = $fields;
    }

    /**
     * Obtener nombre de la clave
     *
     * @return string
     */
    public function get_name(): string {
        return $this->name;
    }

    /**
     * Obtener tipo de clave
     *
     * @return string
     */
    public function get_type(): string {
        return $this->type;
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
     * Establecer tabla referenciada
     *
     * @param string $table
     * @return self
     */
    public function set_reftable(string $table): self {
        $this->reftable = $table;
        return $this;
    }

    /**
     * Obtener tabla referenciada
     *
     * @return string|null
     */
    public function get_reftable(): ?string {
        return $this->reftable;
    }

    /**
     * Establecer campos referenciados
     *
     * @param array $fields
     * @return self
     */
    public function set_reffields(array $fields): self {
        $this->reffields = $fields;
        return $this;
    }

    /**
     * Obtener campos referenciados
     *
     * @return array
     */
    public function get_reffields(): array {
        return $this->reffields;
    }
}
