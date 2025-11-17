<?php
namespace core\plugin;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Plugin Manager
 *
 * Gestiona el descubrimiento, instalación y actualización de plugins.
 * Implementa el patrón Factory para instanciar plugins dinámicamente.
 *
 * @package core\plugin
 */
class manager {

    /** @var array Cache de plugins descubiertos */
    private static array $plugins_cache = [];

    /** @var array Mapeo de componentes */
    private static ?array $components = null;

    /**
     * Obtener instancia de plugin de autenticación
     *
     * @param string $name Nombre del plugin (sin prefijo 'auth_')
     * @return \core\plugininfo\auth|null
     */
    public static function get_auth_plugin(string $name): ?\core\plugininfo\auth {
        return self::get_plugin('auth', $name);
    }

    /**
     * Obtener instancia de plugin de herramienta
     *
     * @param string $name Nombre del plugin (sin prefijo 'tool_')
     * @return \core\plugininfo\tool|null
     */
    public static function get_tool_plugin(string $name): ?\core\plugininfo\tool {
        return self::get_plugin('tool', $name);
    }

    /**
     * Obtener instancia de plugin de factor MFA
     *
     * @param string $name Nombre del plugin (sin prefijo 'factor_')
     * @return \core\plugininfo\factor|null
     */
    public static function get_factor_plugin(string $name): ?\core\plugininfo\factor {
        return self::get_plugin('factor', $name);
    }

    /**
     * Obtener instancia de plugin de tema
     *
     * @param string $name Nombre del plugin (sin prefijo 'theme_')
     * @return \core\plugininfo\theme|null
     */
    public static function get_theme_plugin(string $name): ?\core\plugininfo\theme {
        return self::get_plugin('theme', $name);
    }

    /**
     * Obtener instancia de plugin de reporte
     *
     * @param string $name Nombre del plugin (sin prefijo 'report_')
     * @return \core\plugininfo\report|null
     */
    public static function get_report_plugin(string $name): ?\core\plugininfo\report {
        return self::get_plugin('report', $name);
    }

    /**
     * Obtener instancia de plugin genérico
     *
     * @param string $type Tipo de plugin
     * @param string $name Nombre del plugin
     * @return \core\plugininfo\base|null
     */
    public static function get_plugin(string $type, string $name): ?\core\plugininfo\base {
        $cachekey = "{$type}_{$name}";

        if (isset(self::$plugins_cache[$cachekey])) {
            return self::$plugins_cache[$cachekey];
        }

        $classname = "{$type}_{$name}\\plugin";

        if (!class_exists($classname)) {
            return null;
        }

        try {
            $instance = new $classname($type, $name);
        } catch (\Exception $e) {
            debugging("Error creating plugin instance: {$e->getMessage()}");
            return null;
        }

        // Verificar que extiende la clase base correcta
        $baseclass = "\\core\\plugininfo\\{$type}";

        if (!class_exists($baseclass)) {
            throw new \coding_exception("Base class not found for type: {$type}");
        }

        if (!$instance instanceof $baseclass) {
            throw new \coding_exception(
                "Plugin {$classname} must extend {$baseclass}"
            );
        }

        self::$plugins_cache[$cachekey] = $instance;

        return $instance;
    }

    /**
     * Obtener todos los plugins de un tipo
     *
     * @param string $type Tipo de plugin
     * @return array Array de instancias de plugins
     */
    public static function get_plugins_of_type(string $type): array {
        $plugins = [];
        $components = self::load_components();

        if (!isset($components['plugintypes'][$type])) {
            return $plugins;
        }

        $typedir = $components['plugintypes'][$type];
        $fullpath = self::get_dirroot() . '/' . $typedir;

        if (!is_dir($fullpath)) {
            return $plugins;
        }

        $dirs = scandir($fullpath);

        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..') {
                continue;
            }

            $pluginpath = $fullpath . '/' . $dir;

            if (!is_dir($pluginpath)) {
                continue;
            }

            // Verificar que existe version.php
            if (!file_exists($pluginpath . '/version.php')) {
                continue;
            }

            $plugin = self::get_plugin($type, $dir);

            if ($plugin !== null) {
                $plugins[$dir] = $plugin;
            }
        }

        return $plugins;
    }

    /**
     * Obtener todos los plugins instalados
     *
     * @return array
     */
    public static function get_all_plugins(): array {
        $allplugins = [];
        $components = self::load_components();

        foreach ($components['plugintypes'] as $type => $dir) {
            $plugins = self::get_plugins_of_type($type);
            $allplugins[$type] = $plugins;
        }

        return $allplugins;
    }

    /**
     * Obtener plugin por nombre de componente
     *
     * @param string $component Nombre del componente (ej: auth_manual)
     * @return \core\plugininfo\base|null
     */
    public static function get_plugin_by_component(string $component): ?\core\plugininfo\base {
        list($type, $name) = explode('_', $component, 2);

        if (empty($type) || empty($name)) {
            return null;
        }

        return self::get_plugin($type, $name);
    }

    /**
     * Verificar si un plugin está instalado
     *
     * @param string $component
     * @return bool
     */
    public static function is_plugin_installed(string $component): bool {
        $plugin = self::get_plugin_by_component($component);

        if ($plugin === null) {
            return false;
        }

        return $plugin->is_installed();
    }

    /**
     * Obtener plugins que necesitan actualización
     *
     * @return array
     */
    public static function get_plugins_needing_upgrade(): array {
        $needupgrade = [];
        $allplugins = self::get_all_plugins();

        foreach ($allplugins as $type => $plugins) {
            foreach ($plugins as $name => $plugin) {
                if ($plugin->needs_upgrade()) {
                    $needupgrade[$plugin->get_component()] = $plugin;
                }
            }
        }

        return $needupgrade;
    }

    /**
     * Obtener plugins no instalados
     *
     * @return array
     */
    public static function get_uninstalled_plugins(): array {
        $uninstalled = [];
        $allplugins = self::get_all_plugins();

        foreach ($allplugins as $type => $plugins) {
            foreach ($plugins as $name => $plugin) {
                if (!$plugin->is_installed()) {
                    $uninstalled[$plugin->get_component()] = $plugin;
                }
            }
        }

        return $uninstalled;
    }

    /**
     * Cargar mapeo de componentes desde components.json
     *
     * @return array
     */
    public static function load_components(): array {
        if (self::$components !== null) {
            return self::$components;
        }

        $file = self::get_dirroot() . '/lib/components.json';

        if (!file_exists($file)) {
            throw new \coding_exception('components.json not found');
        }

        $json = file_get_contents($file);
        self::$components = json_decode($json, true);

        if (self::$components === null) {
            throw new \coding_exception('Invalid components.json');
        }

        return self::$components;
    }

    /**
     * Obtener directorio raíz del sistema
     *
     * @return string
     */
    private static function get_dirroot(): string {
        global $CFG;

        if (isset($CFG->dirroot)) {
            return $CFG->dirroot;
        }

        return dirname(dirname(dirname(__DIR__)));
    }

    /**
     * Limpiar cache de plugins
     *
     * @return void
     */
    public static function clear_cache(): void {
        self::$plugins_cache = [];
        self::$components = null;
    }

    /**
     * Verificar dependencias de un plugin
     *
     * @param \core\plugininfo\base $plugin
     * @return array Array de dependencias no satisfechas
     */
    public static function check_dependencies(\core\plugininfo\base $plugin): array {
        $unsatisfied = [];
        $dependencies = $plugin->get_dependencies();

        foreach ($dependencies as $component => $requiredversion) {
            $deplugin = self::get_plugin_by_component($component);

            if ($deplugin === null) {
                $unsatisfied[] = [
                    'component' => $component,
                    'required' => $requiredversion,
                    'actual' => null,
                    'reason' => 'notinstalled'
                ];
                continue;
            }

            $actualversion = $deplugin->get_db_version();

            if ($actualversion === null) {
                $unsatisfied[] = [
                    'component' => $component,
                    'required' => $requiredversion,
                    'actual' => null,
                    'reason' => 'notinstalled'
                ];
                continue;
            }

            if ($actualversion < $requiredversion) {
                $unsatisfied[] = [
                    'component' => $component,
                    'required' => $requiredversion,
                    'actual' => $actualversion,
                    'reason' => 'versionmismatch'
                ];
            }
        }

        return $unsatisfied;
    }
}
