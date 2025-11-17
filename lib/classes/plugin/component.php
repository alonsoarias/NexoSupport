<?php
namespace core\plugin;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Component Resolver
 *
 * Resuelve y valida nombres de componentes Frankenstyle.
 * Proporciona utilidades para trabajar con nombres de componentes.
 *
 * @package core\plugin
 */
class component {

    /**
     * Validar nombre de componente Frankenstyle
     *
     * Un nombre válido debe ser: tipo_nombre
     * - tipo: solo letras minúsculas
     * - nombre: letras minúsculas y números, guiones bajos permitidos
     *
     * @param string $component
     * @return bool
     */
    public static function is_valid(string $component): bool {
        // Patrón: tipo_nombre
        return preg_match('/^[a-z]+_[a-z][a-z0-9_]*$/', $component) === 1;
    }

    /**
     * Separar componente en tipo y nombre
     *
     * @param string $component
     * @return array|null [tipo, nombre] o null si inválido
     */
    public static function split(string $component): ?array {
        if (!self::is_valid($component)) {
            return null;
        }

        $parts = explode('_', $component, 2);

        return [
            'type' => $parts[0],
            'name' => $parts[1]
        ];
    }

    /**
     * Obtener tipo del componente
     *
     * @param string $component
     * @return string|null
     */
    public static function get_type(string $component): ?string {
        $parts = self::split($component);

        return $parts['type'] ?? null;
    }

    /**
     * Obtener nombre del componente
     *
     * @param string $component
     * @return string|null
     */
    public static function get_name(string $component): ?string {
        $parts = self::split($component);

        return $parts['name'] ?? null;
    }

    /**
     * Combinar tipo y nombre en componente
     *
     * @param string $type
     * @param string $name
     * @return string
     */
    public static function combine(string $type, string $name): string {
        return "{$type}_{$name}";
    }

    /**
     * Obtener directorio del componente
     *
     * @param string $component
     * @return string|null
     */
    public static function get_directory(string $component): ?string {
        global $CFG;

        $parts = self::split($component);

        if ($parts === null) {
            return null;
        }

        $components = manager::load_components();

        if (!isset($components['plugintypes'][$parts['type']])) {
            return null;
        }

        $typedir = $components['plugintypes'][$parts['type']];

        return $CFG->dirroot . '/' . $typedir . '/' . $parts['name'];
    }

    /**
     * Obtener namespace del componente
     *
     * @param string $component
     * @return string
     */
    public static function get_namespace(string $component): string {
        return str_replace('_', '\\', $component);
    }

    /**
     * Obtener clase principal del plugin
     *
     * @param string $component
     * @return string
     */
    public static function get_plugin_class(string $component): string {
        return $component . '\\plugin';
    }

    /**
     * Verificar si el componente existe en el disco
     *
     * @param string $component
     * @return bool
     */
    public static function exists(string $component): bool {
        $dir = self::get_directory($component);

        if ($dir === null) {
            return false;
        }

        return is_dir($dir) && file_exists($dir . '/version.php');
    }

    /**
     * Obtener todos los tipos de plugins disponibles
     *
     * @return array
     */
    public static function get_plugin_types(): array {
        $components = manager::load_components();

        return array_keys($components['plugintypes']);
    }

    /**
     * Verificar si un tipo de plugin existe
     *
     * @param string $type
     * @return bool
     */
    public static function is_valid_type(string $type): bool {
        $components = manager::load_components();

        return isset($components['plugintypes'][$type]);
    }

    /**
     * Obtener directorio del tipo de plugin
     *
     * @param string $type
     * @return string|null
     */
    public static function get_type_directory(string $type): ?string {
        global $CFG;

        $components = manager::load_components();

        if (!isset($components['plugintypes'][$type])) {
            return null;
        }

        return $CFG->dirroot . '/' . $components['plugintypes'][$type];
    }

    /**
     * Normalizar nombre de componente
     *
     * Convierte variaciones a formato estándar.
     *
     * @param string $component
     * @return string|null
     */
    public static function normalize(string $component): ?string {
        $component = strtolower(trim($component));

        if (!self::is_valid($component)) {
            return null;
        }

        return $component;
    }

    /**
     * Obtener subsistemas del core
     *
     * @return array
     */
    public static function get_subsystems(): array {
        $components = manager::load_components();

        return $components['subsystems'] ?? [];
    }

    /**
     * Verificar si un nombre es un subsistema
     *
     * @param string $name
     * @return bool
     */
    public static function is_subsystem(string $name): bool {
        $subsystems = self::get_subsystems();

        return isset($subsystems[$name]);
    }
}
