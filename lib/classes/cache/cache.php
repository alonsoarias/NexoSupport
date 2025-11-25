<?php
namespace core\cache;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * MUC-style Cache Class
 *
 * Provides a Moodle Universal Cache (MUC) compatible interface for caching.
 * Supports application, session, and request scoped caches.
 *
 * Stores are automatically selected based on configuration:
 *   - File store (default)
 *   - Redis store (for distributed caching)
 *   - APCu store (for high-performance single-server)
 *
 * @package    core\cache
 * @copyright  NexoSupport
 * @license    Proprietary - NexoSupport
 */
class cache {

    // Cache modes (compatible with Moodle's cache_store constants)
    const MODE_APPLICATION = 1;
    const MODE_SESSION = 2;
    const MODE_REQUEST = 4;

    /** @var string Component name */
    protected string $component;

    /** @var string Area name */
    protected string $area;

    /** @var int Cache mode */
    protected int $mode;

    /** @var array Static cache for request mode */
    protected static array $staticcache = [];

    /** @var array Definition configuration */
    protected array $definition;

    /** @var string Cache key prefix */
    protected string $prefix;

    /** @var cache_store|null Store for APPLICATION mode */
    protected ?cache_store $store = null;

    /**
     * Create a cache instance
     *
     * @param string $component Component name (e.g., 'core', 'mod_forum')
     * @param string $area Cache area (e.g., 'string', 'config')
     * @param array $identifiers Optional identifiers for the cache
     * @return cache
     */
    public static function make(string $component, string $area, array $identifiers = []): cache {
        $definition = cache_definition::get_definition($component, $area);
        return new self($component, $area, $definition, $identifiers);
    }

    /**
     * Create a cache instance for ad-hoc caching (no definition required)
     *
     * @param int $mode Cache mode
     * @param string $component Component name
     * @param string $area Area name
     * @return cache
     */
    public static function make_from_params(int $mode, string $component, string $area): cache {
        $definition = [
            'mode' => $mode,
            'component' => $component,
            'area' => $area,
            'simplekeys' => true,
            'simpledata' => true,
        ];
        return new self($component, $area, $definition, []);
    }

    /**
     * Constructor
     *
     * @param string $component Component name
     * @param string $area Area name
     * @param array $definition Definition configuration
     * @param array $identifiers Optional identifiers
     */
    protected function __construct(string $component, string $area, array $definition, array $identifiers = []) {
        $this->component = $component;
        $this->area = $area;
        $this->definition = $definition;
        $this->mode = $definition['mode'] ?? self::MODE_APPLICATION;

        // Build prefix from component, area, and identifiers
        $this->prefix = $component . '/' . $area;
        if (!empty($identifiers)) {
            $this->prefix .= '/' . implode('/', $identifiers);
        }

        // Initialize store for APPLICATION mode
        if ($this->mode === self::MODE_APPLICATION) {
            $this->store = cache_config::get_store($this->mode, $definition, $this->prefix);
        }
    }

    /**
     * Get a value from cache
     *
     * @param string $key Cache key
     * @return mixed|false Cached value or false if not found
     */
    public function get(string $key) {
        $fullkey = $this->build_key($key);

        switch ($this->mode) {
            case self::MODE_REQUEST:
                return self::$staticcache[$fullkey] ?? false;

            case self::MODE_SESSION:
                return $_SESSION['cache'][$fullkey] ?? false;

            case self::MODE_APPLICATION:
            default:
                if ($this->store !== null) {
                    return $this->store->get($key);
                }
                return false;
        }
    }

    /**
     * Get multiple values from cache
     *
     * @param array $keys Array of keys
     * @return array Array of key => value pairs
     */
    public function get_many(array $keys): array {
        if ($this->mode === self::MODE_APPLICATION && $this->store !== null) {
            return $this->store->get_many($keys);
        }

        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key);
        }
        return $result;
    }

    /**
     * Set a value in cache
     *
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @return bool Success
     */
    public function set(string $key, $value): bool {
        $fullkey = $this->build_key($key);

        switch ($this->mode) {
            case self::MODE_REQUEST:
                self::$staticcache[$fullkey] = $value;
                return true;

            case self::MODE_SESSION:
                if (!isset($_SESSION['cache'])) {
                    $_SESSION['cache'] = [];
                }
                $_SESSION['cache'][$fullkey] = $value;
                return true;

            case self::MODE_APPLICATION:
            default:
                if ($this->store !== null) {
                    return $this->store->set($key, $value);
                }
                return false;
        }
    }

    /**
     * Set multiple values in cache
     *
     * @param array $keyvaluearray Array of key => value pairs
     * @return int Number of items successfully set
     */
    public function set_many(array $keyvaluearray): int {
        if ($this->mode === self::MODE_APPLICATION && $this->store !== null) {
            return $this->store->set_many($keyvaluearray);
        }

        $count = 0;
        foreach ($keyvaluearray as $key => $value) {
            if ($this->set($key, $value)) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Delete a value from cache
     *
     * @param string $key Cache key
     * @return bool Success
     */
    public function delete(string $key): bool {
        $fullkey = $this->build_key($key);

        switch ($this->mode) {
            case self::MODE_REQUEST:
                unset(self::$staticcache[$fullkey]);
                return true;

            case self::MODE_SESSION:
                unset($_SESSION['cache'][$fullkey]);
                return true;

            case self::MODE_APPLICATION:
            default:
                if ($this->store !== null) {
                    return $this->store->delete($key);
                }
                return false;
        }
    }

    /**
     * Delete multiple values from cache
     *
     * @param array $keys Array of keys
     * @return int Number of items successfully deleted
     */
    public function delete_many(array $keys): int {
        if ($this->mode === self::MODE_APPLICATION && $this->store !== null) {
            return $this->store->delete_many($keys);
        }

        $count = 0;
        foreach ($keys as $key) {
            if ($this->delete($key)) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Check if a key exists in cache
     *
     * @param string $key Cache key
     * @return bool True if exists
     */
    public function has(string $key): bool {
        $fullkey = $this->build_key($key);

        switch ($this->mode) {
            case self::MODE_REQUEST:
                return isset(self::$staticcache[$fullkey]);

            case self::MODE_SESSION:
                return isset($_SESSION['cache'][$fullkey]);

            case self::MODE_APPLICATION:
            default:
                if ($this->store !== null) {
                    return $this->store->has($key);
                }
                return false;
        }
    }

    /**
     * Purge all values in this cache
     *
     * @return bool Success
     */
    public function purge(): bool {
        switch ($this->mode) {
            case self::MODE_REQUEST:
                // Only clear items with our prefix
                foreach (array_keys(self::$staticcache) as $key) {
                    if (strpos($key, $this->prefix) === 0) {
                        unset(self::$staticcache[$key]);
                    }
                }
                return true;

            case self::MODE_SESSION:
                if (isset($_SESSION['cache'])) {
                    foreach (array_keys($_SESSION['cache']) as $key) {
                        if (strpos($key, $this->prefix) === 0) {
                            unset($_SESSION['cache'][$key]);
                        }
                    }
                }
                return true;

            case self::MODE_APPLICATION:
            default:
                if ($this->store !== null) {
                    return $this->store->purge();
                }
                return false;
        }
    }

    /**
     * Build the full cache key
     *
     * @param string $key Base key
     * @return string Full key
     */
    protected function build_key(string $key): string {
        if (!empty($this->definition['simplekeys'])) {
            return $this->prefix . '/' . $key;
        }
        // Hash complex keys
        return $this->prefix . '/' . md5($key);
    }

    /**
     * Reset all static caches
     *
     * Used when purging all caches or at end of requests.
     *
     * @return void
     */
    public static function reset_static_caches(): void {
        self::$staticcache = [];
    }

    /**
     * Get cache mode
     *
     * @return int Cache mode
     */
    public function get_mode(): int {
        return $this->mode;
    }

    /**
     * Get cache definition
     *
     * @return array Definition
     */
    public function get_definition(): array {
        return $this->definition;
    }

    /**
     * Get component name
     *
     * @return string Component
     */
    public function get_component(): string {
        return $this->component;
    }

    /**
     * Get area name
     *
     * @return string Area
     */
    public function get_area(): string {
        return $this->area;
    }

    /**
     * Get store name (for debugging)
     *
     * @return string Store name
     */
    public function get_store_name(): string {
        if ($this->store !== null) {
            return $this->store->get_name();
        }

        switch ($this->mode) {
            case self::MODE_REQUEST:
                return 'static';
            case self::MODE_SESSION:
                return 'session';
            default:
                return 'unknown';
        }
    }

    /**
     * Get store statistics (if available)
     *
     * @return array|null Statistics or null
     */
    public function get_store_stats(): ?array {
        if ($this->store !== null && method_exists($this->store, 'get_stats')) {
            return $this->store->get_stats();
        }
        return null;
    }
}
