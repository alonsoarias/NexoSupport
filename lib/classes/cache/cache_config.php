<?php
namespace core\cache;

defined('NEXOSUPPORT_INTERNAL') || die();

use core\cache\stores\cachestore_file;
use core\cache\stores\cachestore_redis;
use core\cache\stores\cachestore_apcu;

/**
 * Cache Configuration Manager
 *
 * Manages cache store selection and configuration.
 * Determines which store to use based on environment config.
 *
 * Configuration in .env:
 *   CACHE_DRIVER=file|redis|apcu|auto
 *
 * @package    core\cache
 * @copyright  NexoSupport
 * @license    Proprietary - NexoSupport
 */
class cache_config {

    /** @var string File store */
    const STORE_FILE = 'file';

    /** @var string Redis store */
    const STORE_REDIS = 'redis';

    /** @var string APCu store */
    const STORE_APCU = 'apcu';

    /** @var string Auto-detect best available store */
    const STORE_AUTO = 'auto';

    /** @var array Available store classes */
    protected static array $store_classes = [
        self::STORE_FILE => cachestore_file::class,
        self::STORE_REDIS => cachestore_redis::class,
        self::STORE_APCU => cachestore_apcu::class,
    ];

    /** @var string|null Cached driver selection */
    protected static ?string $driver = null;

    /**
     * Get the configured cache driver
     *
     * @return string Driver name
     */
    public static function get_driver(): string {
        if (self::$driver !== null) {
            return self::$driver;
        }

        $driver = $_ENV['CACHE_DRIVER'] ?? getenv('CACHE_DRIVER') ?: self::STORE_AUTO;

        if ($driver === self::STORE_AUTO) {
            $driver = self::detect_best_store();
        }

        // Validate driver exists
        if (!isset(self::$store_classes[$driver])) {
            debugging("Invalid cache driver: {$driver}, falling back to file", DEBUG_DEVELOPER);
            $driver = self::STORE_FILE;
        }

        // Validate driver is available
        $class = self::$store_classes[$driver];
        if (!$class::is_available()) {
            debugging("Cache driver {$driver} is not available, falling back to file", DEBUG_DEVELOPER);
            $driver = self::STORE_FILE;
        }

        self::$driver = $driver;
        return $driver;
    }

    /**
     * Detect the best available cache store
     *
     * Priority: Redis > APCu > File
     *
     * @return string Driver name
     */
    protected static function detect_best_store(): string {
        // Redis is preferred for distributed environments
        if (cachestore_redis::is_available()) {
            return self::STORE_REDIS;
        }

        // APCu is great for single-server deployments
        if (cachestore_apcu::is_available()) {
            return self::STORE_APCU;
        }

        // File is always available
        return self::STORE_FILE;
    }

    /**
     * Get a store instance for the given mode and definition
     *
     * @param int $mode Cache mode
     * @param array $definition Cache definition
     * @param string $prefix Key prefix
     * @return cache_store Store instance
     */
    public static function get_store(int $mode, array $definition, string $prefix): cache_store {
        // Check for definition-specific store override
        if (!empty($definition['store'])) {
            $store_name = $definition['store'];
            if (isset(self::$store_classes[$store_name])) {
                $class = self::$store_classes[$store_name];
                if ($class::is_available() && $class::supports_mode($mode)) {
                    $store = new $class();
                    $store->initialize($definition, $prefix);
                    return $store;
                }
            }
        }

        // Get default driver
        $driver = self::get_driver();
        $class = self::$store_classes[$driver];

        // Check if driver supports this mode
        if (!$class::supports_mode($mode)) {
            // Fall back to file store for APPLICATION mode
            if ($mode === cache::MODE_APPLICATION) {
                $class = cachestore_file::class;
            } else {
                // For SESSION/REQUEST modes, return null (handled in cache class)
                return new cachestore_file();
            }
        }

        $store = new $class();
        $store->initialize($definition, $prefix);
        return $store;
    }

    /**
     * Get available stores and their status
     *
     * @return array Store information
     */
    public static function get_available_stores(): array {
        $stores = [];

        foreach (self::$store_classes as $name => $class) {
            $stores[$name] = [
                'name' => $name,
                'class' => $class,
                'available' => $class::is_available(),
                'supports_application' => $class::supports_mode(cache::MODE_APPLICATION),
                'supports_session' => $class::supports_mode(cache::MODE_SESSION),
            ];
        }

        return $stores;
    }

    /**
     * Get store statistics
     *
     * @return array Statistics for each store
     */
    public static function get_store_stats(): array {
        $stats = [];

        foreach (self::$store_classes as $name => $class) {
            if (!$class::is_available()) {
                $stats[$name] = ['available' => false];
                continue;
            }

            try {
                $store = new $class();
                $store->initialize(
                    ['mode' => cache::MODE_APPLICATION],
                    'stats_check'
                );

                if (method_exists($store, 'get_stats')) {
                    $stats[$name] = $store->get_stats();
                    $stats[$name]['available'] = true;
                } else {
                    $stats[$name] = ['available' => true];
                }
            } catch (\Exception $e) {
                $stats[$name] = ['available' => false, 'error' => $e->getMessage()];
            }
        }

        return $stats;
    }

    /**
     * Reset driver cache (for testing)
     *
     * @return void
     */
    public static function reset(): void {
        self::$driver = null;
    }

    /**
     * Set driver explicitly (for testing)
     *
     * @param string $driver Driver name
     * @return void
     */
    public static function set_driver(string $driver): void {
        self::$driver = $driver;
    }
}
