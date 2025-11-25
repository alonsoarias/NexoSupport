<?php
namespace core\routing;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Route Manager
 *
 * Responsible for loading routes from core and plugins.
 * Plugins can register routes by implementing register_routes()
 * in their plugin class.
 *
 * @package core\routing
 */
class route_manager {

    /** @var route_collection|null Cached routes */
    private static ?route_collection $routes = null;

    /**
     * Load all routes from core and plugins
     *
     * @param bool $force Force reload even if cached
     * @return route_collection
     */
    public static function load_all_routes(bool $force = false): route_collection {
        if (self::$routes !== null && !$force) {
            return self::$routes;
        }

        $collection = new route_collection();

        // Load core routes
        self::load_core_routes($collection);

        // Load plugin routes
        self::load_plugin_routes($collection);

        self::$routes = $collection;

        return $collection;
    }

    /**
     * Load core system routes
     *
     * @param route_collection $routes Route collection to add to
     * @return void
     */
    private static function load_core_routes(route_collection $routes): void {
        // Core routes are defined in lib/routing/routes.php
        $routesFile = BASE_DIR . '/lib/routing/routes.php';

        if (file_exists($routesFile)) {
            // routes.php should return a closure that accepts route_collection
            $definer = require($routesFile);

            if (is_callable($definer)) {
                $definer($routes);
            }
        }
    }

    /**
     * Load routes from installed plugins
     *
     * Plugins can define routes by implementing the register_routes()
     * method in their plugin class.
     *
     * @param route_collection $routes Route collection to add to
     * @return void
     */
    private static function load_plugin_routes(route_collection $routes): void {
        global $DB;

        // Skip if database not available
        if ($DB === null) {
            return;
        }

        // Load component mapping
        $componentsFile = BASE_DIR . '/lib/components.json';
        if (!file_exists($componentsFile)) {
            return;
        }

        $components = json_decode(file_get_contents($componentsFile), true);
        if (!isset($components['plugintypes'])) {
            return;
        }

        // Scan each plugin type directory
        foreach ($components['plugintypes'] as $type => $typedir) {
            $pluginDir = BASE_DIR . '/' . $typedir;

            if (!is_dir($pluginDir)) {
                continue;
            }

            // Scan for plugins
            $plugins = scandir($pluginDir);

            foreach ($plugins as $pluginname) {
                if ($pluginname === '.' || $pluginname === '..') {
                    continue;
                }

                $pluginPath = $pluginDir . '/' . $pluginname;

                if (!is_dir($pluginPath)) {
                    continue;
                }

                // Check for version.php (valid plugin)
                if (!file_exists($pluginPath . '/version.php')) {
                    continue;
                }

                // Try to load plugin class and call register_routes
                self::load_plugin_routes_from($type, $pluginname, $routes);
            }
        }
    }

    /**
     * Load routes from a specific plugin
     *
     * @param string $type Plugin type
     * @param string $name Plugin name
     * @param route_collection $routes Route collection
     * @return void
     */
    private static function load_plugin_routes_from(string $type, string $name, route_collection $routes): void {
        $component = "{$type}_{$name}";

        // Try to find routes.php in plugin directory
        $components = json_decode(file_get_contents(BASE_DIR . '/lib/components.json'), true);
        $typedir = $components['plugintypes'][$type] ?? $type;
        $pluginDir = BASE_DIR . '/' . $typedir . '/' . $name;

        // Method 1: Check for routes.php in plugin
        $routesFile = $pluginDir . '/routes.php';
        if (file_exists($routesFile)) {
            $routes->group(['component' => $component], function($routes) use ($routesFile) {
                $definer = require($routesFile);
                if (is_callable($definer)) {
                    $definer($routes);
                }
            });
            return;
        }

        // Method 2: Check if plugin class has register_routes method
        $className = "{$component}\\plugin";

        if (class_exists($className)) {
            try {
                // Instantiate plugin
                $plugin = new $className($type, $name);

                // Check for register_routes method
                if (method_exists($plugin, 'register_routes')) {
                    $routes->group(['component' => $component], function($routes) use ($plugin) {
                        $plugin->register_routes($routes);
                    });
                }
            } catch (\Exception $e) {
                // Skip plugins that fail to load
                debugging("Failed to load routes from {$component}: " . $e->getMessage());
            }
        }
    }

    /**
     * Clear cached routes
     *
     * @return void
     */
    public static function clear_cache(): void {
        self::$routes = null;
    }

    /**
     * Get URL for a named route
     *
     * @param string $name Route name
     * @param array $params Parameters
     * @return string|null URL or null if not found
     */
    public static function url(string $name, array $params = []): ?string {
        $routes = self::load_all_routes();
        return $routes->url($name, $params);
    }

    /**
     * Register routes programmatically
     *
     * This is useful for adding routes at runtime.
     *
     * @param callable $callback Function that receives route_collection
     * @return void
     */
    public static function register(callable $callback): void {
        $routes = self::load_all_routes();
        $callback($routes);
    }
}
