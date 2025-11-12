<?php

/**
 * ISER - Plugin Loader
 *
 * Loads and initializes plugins from the filesystem.
 * Discovers plugins, validates manifests, and loads plugin classes.
 *
 * @package    ISER\Plugin
 * @category   Modules
 * @author     ISER Development Team
 * @copyright  2024 ISER
 * @license    Proprietary
 * @version    1.0.0
 * @since      Phase 2
 */

namespace ISER\Plugin;

use ISER\Core\Database\Database;
use ISER\Core\Plugin\HookManager;
use ISER\Core\Utils\Logger;

/**
 * PluginLoader Class
 *
 * Responsible for:
 * - Discovering plugins in the filesystem
 * - Validating plugin.json manifests
 * - Loading and initializing plugins
 * - Registering plugin autoloaders
 */
class PluginLoader
{
    /**
     * Database instance
     */
    private Database $db;

    /**
     * HookManager instance
     */
    private HookManager $hookManager;

    /**
     * Base plugins directory
     */
    private string $pluginsDir;

    /**
     * Loaded plugin instances
     */
    private array $loadedPlugins = [];

    /**
     * Discovered plugins (raw data)
     */
    private array $discoveredPlugins = [];

    /**
     * Valid plugin types
     */
    private const VALID_TYPES = [
        'tool',
        'auth',
        'theme',
        'report',
        'module',
        'integration'
    ];

    /**
     * Constructor
     *
     * @param Database $db Database instance
     * @param HookManager $hookManager Hook manager instance
     * @param string $pluginsDir Base plugins directory path
     */
    public function __construct(
        Database $db,
        HookManager $hookManager,
        string $pluginsDir = ''
    ) {
        $this->db = $db;
        $this->hookManager = $hookManager;

        // Default plugins directory if not specified
        if (empty($pluginsDir)) {
            $pluginsDir = dirname(dirname(__DIR__)) . '/modules/plugins';
        }

        $this->pluginsDir = rtrim($pluginsDir, '/');
    }

    /**
     * Load all enabled plugins
     *
     * Discovers enabled plugins from database and loads them in priority order.
     *
     * @return void
     */
    public function loadAll(): void
    {
        try {
            $pluginManager = new PluginManager($this->db);
            $enabledPlugins = $pluginManager->getEnabled();

            Logger::system('Loading enabled plugins', [
                'count' => count($enabledPlugins)
            ]);

            foreach ($enabledPlugins as $pluginData) {
                $this->load($pluginData['slug']);
            }

            Logger::system('Finished loading plugins', [
                'loaded_count' => count($this->loadedPlugins)
            ]);

        } catch (\Exception $e) {
            Logger::error('Failed to load all plugins', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Load a specific plugin by slug
     *
     * @param string $slug Plugin slug identifier
     * @return bool True on success
     */
    public function load(string $slug): bool
    {
        try {
            // Check if already loaded
            if (isset($this->loadedPlugins[$slug])) {
                Logger::warning('Plugin already loaded', ['slug' => $slug]);
                return true;
            }

            // Get plugin data from database
            $pluginManager = new PluginManager($this->db);
            $pluginData = $pluginManager->getBySlug($slug);

            if (!$pluginData) {
                Logger::warning('Plugin not found in database', ['slug' => $slug]);
                return false;
            }

            // Build plugin path
            $pluginPath = $this->getPluginPath($pluginData['type'], $slug);

            if (!is_dir($pluginPath)) {
                Logger::error('Plugin directory not found', [
                    'slug' => $slug,
                    'path' => $pluginPath
                ]);
                return false;
            }

            // Load plugin class
            $pluginClass = $this->loadPluginClass($pluginPath, $slug);

            if (!$pluginClass) {
                Logger::error('Failed to load plugin class', [
                    'slug' => $slug,
                    'path' => $pluginPath
                ]);
                return false;
            }

            // Register plugin
            $this->loadedPlugins[$slug] = [
                'class' => $pluginClass,
                'data' => $pluginData,
                'path' => $pluginPath
            ];

            Logger::info('Plugin loaded successfully', [
                'slug' => $slug,
                'type' => $pluginData['type']
            ]);

            return true;

        } catch (\Exception $e) {
            Logger::error('Failed to load plugin', [
                'slug' => $slug,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Discover all plugins in the filesystem
     *
     * Scans plugin directories and collects plugin.json files.
     *
     * @return array Discovered plugins metadata
     */
    public function discoverPlugins(): array
    {
        $discovered = [];

        try {
            if (!is_dir($this->pluginsDir)) {
                Logger::warning('Plugins directory not found', [
                    'path' => $this->pluginsDir
                ]);
                return [];
            }

            // Scan each plugin type directory
            foreach (self::VALID_TYPES as $type) {
                $typeDir = $this->pluginsDir . '/' . $type;

                if (!is_dir($typeDir)) {
                    continue;
                }

                $plugins = $this->scanTypeDirectory($typeDir, $type);
                $discovered = array_merge($discovered, $plugins);
            }

            Logger::system('Plugin discovery completed', [
                'count' => count($discovered)
            ]);

            $this->discoveredPlugins = $discovered;
            return $discovered;

        } catch (\Exception $e) {
            Logger::error('Plugin discovery failed', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Scan a type directory for plugins
     *
     * @param string $typeDir Directory path for plugin type
     * @param string $type Plugin type
     * @return array Discovered plugins of this type
     */
    private function scanTypeDirectory(string $typeDir, string $type): array
    {
        $plugins = [];

        try {
            $items = scandir($typeDir);

            if ($items === false) {
                Logger::warning('Failed to scan directory', ['path' => $typeDir]);
                return [];
            }

            foreach ($items as $item) {
                // Skip . and ..
                if ($item === '.' || $item === '..') {
                    continue;
                }

                $pluginPath = $typeDir . '/' . $item;

                if (!is_dir($pluginPath)) {
                    continue;
                }

                $manifestPath = $pluginPath . '/plugin.json';

                if (!file_exists($manifestPath)) {
                    Logger::debug('No plugin.json found', ['path' => $pluginPath]);
                    continue;
                }

                // Load and validate manifest
                $manifest = $this->loadManifest($manifestPath);

                if (!$manifest || !$this->validateManifest($manifest)) {
                    Logger::warning('Invalid plugin manifest', ['path' => $manifestPath]);
                    continue;
                }

                // Verify type matches
                if (($manifest['type'] ?? null) !== $type) {
                    Logger::warning('Plugin type mismatch', [
                        'path' => $manifestPath,
                        'declared_type' => $manifest['type'] ?? null,
                        'directory_type' => $type
                    ]);
                    continue;
                }

                $plugins[] = [
                    'slug' => $manifest['slug'],
                    'name' => $manifest['name'],
                    'type' => $type,
                    'version' => $manifest['version'],
                    'path' => $pluginPath,
                    'manifest' => $manifest
                ];
            }

        } catch (\Exception $e) {
            Logger::error('Failed to scan type directory', [
                'path' => $typeDir,
                'error' => $e->getMessage()
            ]);
        }

        return $plugins;
    }

    /**
     * Load manifest file
     *
     * @param string $manifestPath Path to plugin.json
     * @return array|null Parsed manifest or null on failure
     */
    private function loadManifest(string $manifestPath): ?array
    {
        try {
            if (!file_exists($manifestPath) || !is_readable($manifestPath)) {
                Logger::debug('Manifest file not readable', ['path' => $manifestPath]);
                return null;
            }

            $content = file_get_contents($manifestPath);

            if ($content === false) {
                Logger::warning('Failed to read manifest file', ['path' => $manifestPath]);
                return null;
            }

            $manifest = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Logger::warning('Invalid JSON in manifest', [
                    'path' => $manifestPath,
                    'error' => json_last_error_msg()
                ]);
                return null;
            }

            return $manifest;

        } catch (\Exception $e) {
            Logger::error('Failed to load manifest', [
                'path' => $manifestPath,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Validate plugin manifest structure
     *
     * Checks that manifest contains required fields and valid values.
     *
     * @param array $manifest Plugin manifest data
     * @return bool True if manifest is valid
     */
    public function validateManifest(array $manifest): bool
    {
        // Required fields
        $required = ['name', 'slug', 'type', 'version', 'author', 'description'];

        foreach ($required as $field) {
            if (empty($manifest[$field])) {
                Logger::debug('Missing required manifest field', ['field' => $field]);
                return false;
            }
        }

        // Validate slug format (alphanumeric and hyphens only)
        if (!preg_match('/^[a-z0-9-]+$/', $manifest['slug'])) {
            Logger::debug('Invalid slug format', ['slug' => $manifest['slug']]);
            return false;
        }

        // Validate type
        if (!in_array($manifest['type'], self::VALID_TYPES)) {
            Logger::debug('Invalid plugin type', [
                'type' => $manifest['type'],
                'valid_types' => self::VALID_TYPES
            ]);
            return false;
        }

        // Validate version format (semantic versioning)
        if (!preg_match('/^\d+\.\d+\.\d+/', $manifest['version'])) {
            Logger::debug('Invalid version format', ['version' => $manifest['version']]);
            return false;
        }

        return true;
    }

    /**
     * Load plugin class from Plugin.php
     *
     * Attempts to load the main plugin class and return an instance.
     *
     * @param string $pluginPath Path to plugin directory
     * @param string $slug Plugin slug
     * @return object|null Plugin instance or null on failure
     */
    private function loadPluginClass(string $pluginPath, string $slug): ?object
    {
        try {
            $pluginFile = $pluginPath . '/Plugin.php';

            if (!file_exists($pluginFile)) {
                Logger::debug('Plugin.php not found', ['path' => $pluginFile]);
                return null;
            }

            // Include the plugin file
            require_once $pluginFile;

            // Try to find the plugin class
            // Try common naming conventions
            $classNames = [
                // Namespace based on slug (e.g., mfa-auth -> MfaAuth\Plugin)
                $this->slugToNamespace($slug) . '\\Plugin',
                // Generic Plugins\{Slug}\Plugin
                'Plugins\\' . $this->slugToNamespace($slug) . '\\Plugin',
            ];

            foreach ($classNames as $className) {
                if (class_exists($className)) {
                    // Instantiate and return
                    return new $className();
                }
            }

            Logger::warning('No plugin class found', [
                'slug' => $slug,
                'tried_classes' => $classNames
            ]);

            return null;

        } catch (\Exception $e) {
            Logger::error('Failed to load plugin class', [
                'slug' => $slug,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Convert plugin slug to namespace format
     *
     * Example: 'mfa-authenticator' -> 'MfaAuthenticator'
     *
     * @param string $slug Plugin slug
     * @return string Namespaced class name
     */
    private function slugToNamespace(string $slug): string
    {
        $parts = explode('-', $slug);
        return implode('', array_map('ucfirst', $parts));
    }

    /**
     * Get the filesystem path for a plugin
     *
     * @param string $type Plugin type
     * @param string $slug Plugin slug
     * @return string Full path to plugin directory
     */
    public function getPluginPath(string $type, string $slug): string
    {
        return $this->pluginsDir . '/' . $type . '/' . $slug;
    }

    /**
     * Get all loaded plugins
     *
     * @return array Loaded plugins
     */
    public function getLoadedPlugins(): array
    {
        return $this->loadedPlugins;
    }

    /**
     * Get loaded plugin by slug
     *
     * @param string $slug Plugin slug
     * @return array|null Loaded plugin data or null
     */
    public function getLoadedPlugin(string $slug): ?array
    {
        return $this->loadedPlugins[$slug] ?? null;
    }

    /**
     * Register plugin autoloader
     *
     * Allows plugins to use PSR-4 autoloading.
     *
     * @param string $slug Plugin slug
     * @param string $namespace Plugin namespace
     * @param string $path Plugin path
     * @return void
     */
    public function registerAutoloader(string $slug, string $namespace, string $path): void
    {
        try {
            spl_autoload_register(function ($class) use ($namespace, $path) {
                if (strpos($class, $namespace) === 0) {
                    $file = $path . '/src/' . str_replace(
                        ['\\', $namespace . '\\'],
                        ['/', ''],
                        $class
                    ) . '.php';

                    if (file_exists($file)) {
                        require_once $file;
                    }
                }
            });

            Logger::debug('Plugin autoloader registered', [
                'slug' => $slug,
                'namespace' => $namespace
            ]);

        } catch (\Exception $e) {
            Logger::error('Failed to register plugin autoloader', [
                'slug' => $slug,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get discovered plugins
     *
     * @return array All discovered plugins
     */
    public function getDiscoveredPlugins(): array
    {
        return $this->discoveredPlugins;
    }

    /**
     * Get discovered plugins by type
     *
     * @param string $type Plugin type
     * @return array Discovered plugins of specified type
     */
    public function getDiscoveredPluginsByType(string $type): array
    {
        return array_filter(
            $this->discoveredPlugins,
            fn ($plugin) => $plugin['type'] === $type
        );
    }

    /**
     * Get discovered plugin by slug
     *
     * @param string $slug Plugin slug
     * @return array|null Discovered plugin or null
     */
    public function getDiscoveredPlugin(string $slug): ?array
    {
        foreach ($this->discoveredPlugins as $plugin) {
            if ($plugin['slug'] === $slug) {
                return $plugin;
            }
        }
        return null;
    }

    /**
     * Validate plugin directory structure
     *
     * Checks for required files/directories.
     *
     * @param string $pluginPath Path to plugin directory
     * @return bool True if valid structure
     */
    public function validatePluginStructure(string $pluginPath): bool
    {
        $required = [
            'plugin.json',
            'Plugin.php'
        ];

        foreach ($required as $file) {
            $filePath = $pluginPath . '/' . $file;
            if (!file_exists($filePath)) {
                Logger::debug('Required plugin file missing', [
                    'path' => $filePath
                ]);
                return false;
            }
        }

        return true;
    }

    /**
     * Get plugin manifest from loaded plugin
     *
     * @param string $slug Plugin slug
     * @return array|null Plugin manifest or null
     */
    public function getPluginManifest(string $slug): ?array
    {
        $plugin = $this->getLoadedPlugin($slug);

        if (!$plugin || !isset($plugin['data']['manifest'])) {
            return null;
        }

        return $plugin['data']['manifest'];
    }

    /**
     * Clear loaded plugins cache
     *
     * @return void
     */
    public function clearCache(): void
    {
        $this->loadedPlugins = [];
        $this->discoveredPlugins = [];
    }
}
