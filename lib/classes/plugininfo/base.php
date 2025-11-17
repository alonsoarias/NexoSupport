<?php
namespace core\plugininfo;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Clase base de todos los plugins
 *
 * Todos los tipos de plugins heredan de esta clase.
 * Proporciona funcionalidad común para gestión de versiones,
 * instalación y actualización.
 *
 * @package core\plugininfo
 */
abstract class base {

    /** @var string Tipo de plugin (auth, tool, etc.) */
    protected string $type;

    /** @var string Nombre del plugin */
    protected string $name;

    /** @var string Component name (tipo_nombre) */
    protected string $component;

    /** @var string Ruta del plugin */
    protected string $path;

    /** @var int|null Versión en disco */
    protected ?int $diskversion = null;

    /** @var int|null Versión en BD */
    protected ?int $dbversion = null;

    /**
     * Constructor
     *
     * @param string $type Tipo de plugin
     * @param string $name Nombre del plugin
     */
    public function __construct(string $type, string $name) {
        $this->type = $type;
        $this->name = $name;
        $this->component = "{$type}_{$name}";
        $this->path = $this->get_plugin_path();
    }

    /**
     * Obtener tipo del plugin
     *
     * @return string
     */
    public function get_type(): string {
        return $this->type;
    }

    /**
     * Obtener nombre del plugin
     *
     * @return string
     */
    public function get_name(): string {
        return $this->name;
    }

    /**
     * Obtener nombre del componente (tipo_nombre)
     *
     * @return string
     */
    public function get_component(): string {
        return $this->component;
    }

    /**
     * Obtener ruta del plugin
     *
     * @return string
     */
    public function get_path(): string {
        return $this->path;
    }

    /**
     * Obtener versión del disco
     *
     * @return int|null
     */
    public function get_disk_version(): ?int {
        if ($this->diskversion !== null) {
            return $this->diskversion;
        }

        $versionfile = $this->path . '/version.php';

        if (!file_exists($versionfile)) {
            return null;
        }

        $plugin = new \stdClass();
        include($versionfile);

        $this->diskversion = $plugin->version ?? null;

        return $this->diskversion;
    }

    /**
     * Obtener versión instalada en BD
     *
     * @return int|null
     */
    public function get_db_version(): ?int {
        if ($this->dbversion !== null) {
            return $this->dbversion;
        }

        $this->dbversion = get_config($this->component, 'version');

        return $this->dbversion;
    }

    /**
     * ¿Está instalado el plugin?
     *
     * @return bool
     */
    public function is_installed(): bool {
        return $this->get_db_version() !== null;
    }

    /**
     * ¿Necesita actualización?
     *
     * @return bool
     */
    public function needs_upgrade(): bool {
        $diskversion = $this->get_disk_version();
        $dbversion = $this->get_db_version();

        if ($diskversion === null) {
            return false;
        }

        if ($dbversion === null) {
            return false; // Necesita instalación, no upgrade
        }

        return $diskversion > $dbversion;
    }

    /**
     * ¿Está habilitado el plugin?
     *
     * @return bool
     */
    public function is_enabled(): bool {
        $enabled = get_config($this->component, 'enabled');
        return $enabled === true || $enabled === '1';
    }

    /**
     * Habilitar el plugin
     *
     * @return void
     */
    public function enable(): void {
        set_config('enabled', true, $this->component);
    }

    /**
     * Deshabilitar el plugin
     *
     * @return void
     */
    public function disable(): void {
        set_config('enabled', false, $this->component);
    }

    /**
     * Obtener ruta del plugin (debe ser implementado por subclases)
     *
     * @return string
     */
    protected function get_plugin_path(): string {
        global $CFG;

        // Obtener mapeo de tipos desde components.json
        $components = $this->load_components_map();

        if (!isset($components['plugintypes'][$this->type])) {
            throw new \coding_exception("Unknown plugin type: {$this->type}");
        }

        $typedir = $components['plugintypes'][$this->type];

        return $CFG->dirroot . '/' . $typedir . '/' . $this->name;
    }

    /**
     * Cargar mapeo de componentes desde components.json
     *
     * @return array
     */
    protected function load_components_map(): array {
        global $CFG;

        static $components = null;

        if ($components === null) {
            $file = $CFG->dirroot . '/lib/components.json';

            if (!file_exists($file)) {
                throw new \coding_exception('components.json not found');
            }

            $json = file_get_contents($file);
            $components = json_decode($json, true);

            if ($components === null) {
                throw new \coding_exception('Invalid components.json');
            }
        }

        return $components;
    }

    /**
     * Obtener información del plugin desde version.php
     *
     * @return \stdClass|null
     */
    public function get_version_info(): ?\stdClass {
        $versionfile = $this->path . '/version.php';

        if (!file_exists($versionfile)) {
            return null;
        }

        $plugin = new \stdClass();
        include($versionfile);

        return $plugin;
    }

    /**
     * Obtener dependencias del plugin
     *
     * @return array
     */
    public function get_dependencies(): array {
        $info = $this->get_version_info();

        if ($info === null || !isset($info->dependencies)) {
            return [];
        }

        return $info->dependencies;
    }
}
