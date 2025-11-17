<?php
/**
 * NexoSupport - Component Helper Class
 *
 * Provides helper methods for component operations (Frankenstyle plugins)
 * Manages component paths, loading, and discovery
 *
 * @package    ISER\Core\Component
 * @copyright  2024 ISER
 * @license    Proprietary
 */

namespace ISER\Core\Component;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Component Helper - Component management operations
 *
 * Provides convenient methods for working with Frankenstyle components
 */
class ComponentHelper
{
    /** @var array|null Component configuration cache */
    private static ?array $components = null;

    /** @var ComponentHelper|null Singleton instance */
    private static ?ComponentHelper $instance = null;

    /**
     * Private constructor for singleton pattern
     */
    private function __construct()
    {
        $this->loadComponentsMap();
    }

    /**
     * Get singleton instance
     *
     * @return ComponentHelper
     */
    public static function getInstance(): ComponentHelper
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Load components map from JSON file
     */
    private function loadComponentsMap(): void
    {
        if (self::$components !== null) {
            return;
        }

        $componentsFile = LIB_DIR . '/components.json';
        if (file_exists($componentsFile)) {
            $json = file_get_contents($componentsFile);
            self::$components = json_decode($json, true) ?? [];
        } else {
            self::$components = [];
        }
    }

    /**
     * Get component directory path
     *
     * @param string $component Component name (e.g., 'auth_manual', 'tool_uploaduser')
     * @return string|null Path to component directory or null if not found
     */
    public function getPath(string $component): ?string
    {
        // Parse component name (e.g., 'auth_manual' => type: 'auth', name: 'manual')
        if (strpos($component, '_') === false) {
            return null;
        }

        list($type, $name) = explode('_', $component, 2);

        // Check if type exists in plugintypes
        if (isset(self::$components['plugintypes'][$type])) {
            $basePath = BASE_DIR . '/' . self::$components['plugintypes'][$type];
            $componentPath = $basePath . '/' . $name;

            if (is_dir($componentPath)) {
                return $componentPath;
            }
        }

        return null;
    }

    /**
     * Load component's lib.php file if exists
     *
     * @param string $component Component name (e.g., 'auth_manual', 'tool_uploaduser')
     * @return bool True if loaded, false otherwise
     */
    public function requireLib(string $component): bool
    {
        $componentPath = $this->getPath($component);

        if ($componentPath === null) {
            return false;
        }

        $libfile = $componentPath . '/lib.php';

        if (file_exists($libfile)) {
            require_once $libfile;
            return true;
        }

        return false;
    }

    /**
     * Get list of all installed components of a specific type
     *
     * @param string $type Component type (e.g., 'auth', 'tool', 'theme')
     * @return array Array of component names
     */
    public function getComponentsByType(string $type): array
    {
        if (!isset(self::$components['plugintypes'][$type])) {
            return [];
        }

        $basePath = BASE_DIR . '/' . self::$components['plugintypes'][$type];

        if (!is_dir($basePath)) {
            return [];
        }

        $dirs = array_diff(scandir($basePath), ['.', '..']);
        $result = [];

        foreach ($dirs as $dir) {
            if (is_dir($basePath . '/' . $dir)) {
                $result[] = $type . '_' . $dir;
            }
        }

        return $result;
    }

    /**
     * Get all plugin types
     *
     * @return array Associative array of plugin types and their paths
     */
    public function getPluginTypes(): array
    {
        return self::$components['plugintypes'] ?? [];
    }

    /**
     * Get all installed components
     *
     * @return array Array of all component names
     */
    public function getAllComponents(): array
    {
        $components = [];

        foreach ($this->getPluginTypes() as $type => $path) {
            $components = array_merge($components, $this->getComponentsByType($type));
        }

        return $components;
    }

    /**
     * Check if component exists
     *
     * @param string $component Component name
     * @return bool True if component exists
     */
    public function componentExists(string $component): bool
    {
        return $this->getPath($component) !== null;
    }

    /**
     * Parse component name into type and name
     *
     * @param string $component Component name (e.g., 'auth_manual')
     * @return array|null Array with 'type' and 'name' keys, or null if invalid
     */
    public function parseComponent(string $component): ?array
    {
        if (strpos($component, '_') === false) {
            return null;
        }

        list($type, $name) = explode('_', $component, 2);

        return [
            'type' => $type,
            'name' => $name
        ];
    }

    /**
     * Clear component cache
     */
    public function clearCache(): void
    {
        self::$components = null;
        $this->loadComponentsMap();
    }

    /**
     * Reload components map
     */
    public function reload(): void
    {
        $this->clearCache();
    }
}
