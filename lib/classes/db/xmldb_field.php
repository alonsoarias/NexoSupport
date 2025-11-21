<?php
namespace core\db;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Representación de un campo en XMLDB
 *
 * @package core\db
 */
class xmldb_field {

    /** Field type constants (Moodle-compatible) */
    const TYPE_INT = 'int';
    const TYPE_NUMBER = 'number';
    const TYPE_FLOAT = 'float';
    const TYPE_CHAR = 'char';
    const TYPE_TEXT = 'text';
    const TYPE_BINARY = 'binary';
    const TYPE_DATETIME = 'datetime';

    /** @var string Nombre del campo */
    private string $name;

    /** @var string Tipo de dato */
    private string $type;

    /** @var int|null Longitud del campo */
    private ?int $length = null;

    /** @var int|null Precisión (para decimales) */
    private ?int $precision = null;

    /** @var bool ¿Es NOT NULL? */
    private bool $notnull = false;

    /** @var bool ¿Es SEQUENCE (auto-increment)? */
    private bool $sequence = false;

    /** @var mixed Valor por defecto */
    private mixed $default = null;

    /**
     * Constructor
     *
     * @param string $name Nombre del campo
     * @param string $type Tipo de dato
     */
    public function __construct(string $name, string $type = 'char') {
        $this->name = $name;
        $this->type = $type;
    }

    /**
     * Obtener nombre del campo
     *
     * @return string
     */
    public function get_name(): string {
        return $this->name;
    }

    /**
     * Obtener tipo del campo
     *
     * @return string
     */
    public function get_type(): string {
        return $this->type;
    }

    /**
     * Establecer longitud
     *
     * @param int $length
     * @return self
     */
    public function set_length(int $length): self {
        $this->length = $length;
        return $this;
    }

    /**
     * Obtener longitud
     *
     * @return int|null
     */
    public function get_length(): ?int {
        return $this->length;
    }

    /**
     * Establecer precisión
     *
     * @param int $precision
     * @return self
     */
    public function set_precision(int $precision): self {
        $this->precision = $precision;
        return $this;
    }

    /**
     * Obtener precisión
     *
     * @return int|null
     */
    public function get_precision(): ?int {
        return $this->precision;
    }

    /**
     * Establecer NOT NULL
     *
     * @param bool $notnull
     * @return self
     */
    public function set_notnull(bool $notnull): self {
        $this->notnull = $notnull;
        return $this;
    }

    /**
     * ¿Es NOT NULL?
     *
     * @return bool
     */
    public function is_notnull(): bool {
        return $this->notnull;
    }

    /**
     * Establecer SEQUENCE (auto-increment)
     *
     * @param bool $sequence
     * @return self
     */
    public function set_sequence(bool $sequence): self {
        $this->sequence = $sequence;
        return $this;
    }

    /**
     * ¿Es SEQUENCE?
     *
     * @return bool
     */
    public function is_sequence(): bool {
        return $this->sequence;
    }

    /**
     * Establecer valor por defecto
     *
     * @param mixed $default
     * @return self
     */
    public function set_default(mixed $default): self {
        $this->default = $default;
        return $this;
    }

    /**
     * Obtener valor por defecto
     *
     * @return mixed
     */
    public function get_default(): mixed {
        return $this->default;
    }

    /**
     * Obtener SQL del tipo de dato
     *
     * @param string $driver Driver de BD (mysql, pgsql, etc.)
     * @return string
     */
    public function get_sql_type(string $driver = 'mysql'): string {
        switch ($this->type) {
            case 'int':
                if ($this->sequence) {
                    return $driver === 'pgsql' ? 'SERIAL' : 'INT AUTO_INCREMENT';
                }
                return 'INT(' . ($this->length ?? 10) . ')';

            case 'char':
                return 'VARCHAR(' . ($this->length ?? 255) . ')';

            case 'text':
                return 'TEXT';

            case 'number':
                $precision = $this->precision ?? 10;
                $scale = $this->length ?? 0;
                return "DECIMAL({$precision}, {$scale})";

            case 'float':
                return 'FLOAT';

            case 'datetime':
                return $driver === 'pgsql' ? 'TIMESTAMP' : 'DATETIME';

            case 'binary':
                return 'BLOB';

            default:
                return 'VARCHAR(255)';
        }
    }
}
