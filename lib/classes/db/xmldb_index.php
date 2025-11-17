<?php
namespace core\db;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Representación de un índice en XMLDB
 *
 * @package core\db
 */
class xmldb_index {

    /** @var string Nombre del índice */
    private string $name;

    /** @var bool ¿Es único? */
    private bool $unique;

    /** @var array Campos del índice */
    private array $fields = [];

    /**
     * Constructor
     *
     * @param string $name Nombre del índice
     * @param bool $unique ¿Es único?
     * @param array $fields Campos
     */
    public function __construct(string $name, bool $unique = false, array $fields = []) {
        $this->name = $name;
        $this->unique = $unique;
        $this->fields = $fields;
    }

    /**
     * Obtener nombre del índice
     *
     * @return string
     */
    public function get_name(): string {
        return $this->name;
    }

    /**
     * ¿Es único?
     *
     * @return bool
     */
    public function is_unique(): bool {
        return $this->unique;
    }

    /**
     * Obtener campos
     *
     * @return array
     */
    public function get_fields(): array {
        return $this->fields;
    }
}
