<?php
namespace core\cache;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Cache Definition Manager
 *
 * Manages cache definitions loaded from db/caches.php files.
 * Similar to Moodle's cache definition system.
 *
 * @package    core\cache
 * @copyright  NexoSupport
 * @license    Proprietary - NexoSupport
 */
class cache_definition {

    /** @var array Loaded definitions */
    private static $definitions = null;

    /**
     * Load all cache definitions
     *
     * Loads definitions from:
     * - /lib/db/caches.php (core)
     * - /auth/{plugin}/db/caches.php (auth plugins)
     * - /local/{plugin}/db/caches.php (local plugins)
     * - etc.
     *
     * @param bool $reload Force reload
     * @return array All definitions
     */
    public static function load_definitions(bool $reload = false): array {
        global $CFG;

        if (self::$definitions !== null && !$reload) {
            return self::$definitions;
        }

        self::$definitions = [];

        // Load core definitions
        $corefile = $CFG->dirroot . '/lib/db/caches.php';
        if (file_exists($corefile)) {
            $definitions = [];
            include($corefile);
            foreach ($definitions as $area => $definition) {
                $definition['component'] = 'core';
                $definition['area'] = $area;
                self::$definitions['core/' . $area] = $definition;
            }
        }

        // Load plugin definitions
        $pluginman = \core\plugin\plugin_manager::instance();
        $types = $pluginman->get_plugin_types();

        foreach ($types as $type => $typedir) {
            if (!is_dir($typedir)) {
                continue;
            }

            $plugins = $pluginman->get_plugin_list($type);
            foreach ($plugins as $name => $dir) {
                $cachefile = $dir . '/db/caches.php';
                if (file_exists($cachefile)) {
                    $definitions = [];
                    include($cachefile);
                    foreach ($definitions as $area => $definition) {
                        $component = $type . '_' . $name;
                        $definition['component'] = $component;
                        $definition['area'] = $area;
                        self::$definitions[$component . '/' . $area] = $definition;
                    }
                }
            }
        }

        return self::$definitions;
    }

    /**
     * Get a specific cache definition
     *
     * @param string $component Component name
     * @param string $area Area name
     * @return array Definition array
     * @throws \coding_exception If definition not found
     */
    public static function get_definition(string $component, string $area): array {
        $definitions = self::load_definitions();
        $key = $component . '/' . $area;

        if (!isset($definitions[$key])) {
            // Return default definition for ad-hoc caches
            debugging("Cache definition not found: {$key}, using defaults", DEBUG_DEVELOPER);
            return [
                'mode' => cache::MODE_APPLICATION,
                'component' => $component,
                'area' => $area,
                'simplekeys' => true,
                'simpledata' => false,
            ];
        }

        return $definitions[$key];
    }

    /**
     * Get all definitions
     *
     * @return array All definitions
     */
    public static function get_all_definitions(): array {
        return self::load_definitions();
    }

    /**
     * Reset definitions cache
     *
     * @return void
     */
    public static function reset(): void {
        self::$definitions = null;
    }

    /**
     * Get definitions by mode
     *
     * @param int $mode Cache mode
     * @return array Definitions with matching mode
     */
    public static function get_definitions_by_mode(int $mode): array {
        $definitions = self::load_definitions();
        return array_filter($definitions, function($def) use ($mode) {
            return ($def['mode'] ?? cache::MODE_APPLICATION) === $mode;
        });
    }

    /**
     * Get definitions with invalidation events
     *
     * @param string $event Event name
     * @return array Definitions triggered by this event
     */
    public static function get_definitions_by_event(string $event): array {
        $definitions = self::load_definitions();
        return array_filter($definitions, function($def) use ($event) {
            $events = $def['invalidationevents'] ?? [];
            return in_array($event, $events);
        });
    }
}
