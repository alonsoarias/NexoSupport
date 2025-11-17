<?php
/**
 * NexoSupport - Component Helper Class
 *
 * Provides helper methods for component operations (Frankenstyle plugins)
 * Manages component paths, loading, and discovery
 * Includes persistent caching for component path lookups
 *
 * @package    ISER\Core\Component
 * @copyright  2024 ISER
 * @license    Proprietary
 */

namespace ISER\Core\Component;

use ISER\Core\Cache\Cache;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Component Helper - Component management operations
 *
 * Provides convenient methods for working with Frankenstyle components
 * with persistent caching for improved performance
 */
class ComponentHelper
{
    /** @var array|null Component configuration cache */
    private static ?array $components = null;

    /** @var ComponentHelper|null Singleton instance */
    private static ?ComponentHelper $instance = null;

    /** @var Cache Path lookup cache */
    private Cache $pathCache;

    /** @var int Components.json file modification time for cache invalidation */
    private ?int $componentsJsonMtime = null;

    /**
     * Private constructor for singleton pattern
     */
    private function __construct()
    {
        // Initialize path cache with 1 hour TTL
        $this->pathCache = new Cache('components', 3600);
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
            // Track file modification time for cache invalidation
            $this->componentsJsonMtime = filemtime($componentsFile);
        } else {
            self::$components = [];
            $this->componentsJsonMtime = 0;
        }
    }

    /**
     * Check if components.json has been modified
     *
     * @return bool True if file has been modified since last load
     */
    private function isComponentsJsonModified(): bool
    {
        if ($this->componentsJsonMtime === null) {
            return true;
        }

        $componentsFile = LIB_DIR . '/components.json';
        if (!file_exists($componentsFile)) {
            return true;
        }

        $currentMtime = filemtime($componentsFile);
        return $currentMtime !== $this->componentsJsonMtime;
    }

    /**
     * Get component directory path
     *
     * Uses persistent caching for improved performance.
     * Cache is automatically invalidated when components.json changes.
     *
     * @param string $component Component name (e.g., 'auth_manual', 'tool_uploaduser')
     * @return string|null Path to component directory or null if not found
     */
    public function getPath(string $component): ?string
    {
        // If components.json has been modified, clear cache and reload
        if ($this->isComponentsJsonModified()) {
            $this->pathCache->clear();
            self::$components = null;
            $this->loadComponentsMap();
        }

        // Create cache key for this component
        $cacheKey = 'path_' . $component;

        // Try to get from cache (remember pattern)
        return $this->pathCache->remember(
            $cacheKey,
            fn() => $this->resolveComponentPath($component),
            3600
        );
    }

    /**
     * Resolve component path (actual lookup logic)
     *
     * @param string $component Component name
     * @return string|null Path or null if not found
     */
    private function resolveComponentPath(string $component): ?string
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
     * Clear component cache (both in-memory and persistent cache)
     */
    public function clearCache(): void
    {
        self::$components = null;
        $this->pathCache->clear();
        $this->loadComponentsMap();
    }

    /**
     * Reload components map
     */
    public function reload(): void
    {
        $this->clearCache();
    }

    /**
     * Get path cache statistics
     *
     * @return array Cache statistics
     */
    public function getCacheStats(): array
    {
        return $this->pathCache->getStats();
    }
}
