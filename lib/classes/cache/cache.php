<?php
namespace core\cache;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * MUC-style Cache Class
 *
 * Provides a Moodle Universal Cache (MUC) compatible interface for caching.
 * Supports application, session, and request scoped caches.
 *
 * @package    core\cache
 * @copyright  NexoSupport
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cache {

    // Cache modes (compatible with Moodle's cache_store constants)
    const MODE_APPLICATION = 1;
    const MODE_SESSION = 2;
    const MODE_REQUEST = 4;

    /** @var string Component name */
    protected $component;

    /** @var string Area name */
    protected $area;

    /** @var int Cache mode */
    protected $mode;

    /** @var array Static cache for request mode */
    protected static $staticcache = [];

    /** @var array Definition configuration */
    protected $definition;

    /** @var string Cache key prefix */
    protected $prefix;

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
                return $this->get_from_store($fullkey);
        }
    }

    /**
     * Get multiple values from cache
     *
     * @param array $keys Array of keys
     * @return array Array of key => value pairs
     */
    public function get_many(array $keys): array {
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
                return $this->set_in_store($fullkey, $value);
        }
    }

    /**
     * Set multiple values in cache
     *
     * @param array $keyvaluearray Array of key => value pairs
     * @return int Number of items successfully set
     */
    public function set_many(array $keyvaluearray): int {
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
                return $this->delete_from_store($fullkey);
        }
    }

    /**
     * Delete multiple values from cache
     *
     * @param array $keys Array of keys
     * @return int Number of items successfully deleted
     */
    public function delete_many(array $keys): int {
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
                return $this->has_in_store($fullkey);
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
                return $this->purge_store();
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

    // =========================================
    // FILE STORE OPERATIONS (Application mode)
    // =========================================

    /**
     * Get cache directory
     *
     * @return string Cache directory path
     */
    protected function get_cache_dir(): string {
        global $CFG;
        $dir = $CFG->cachedir . '/muc/' . str_replace('/', '_', $this->prefix);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        return $dir;
    }

    /**
     * Get cache file path for a key
     *
     * @param string $key Cache key
     * @return string File path
     */
    protected function get_cache_file(string $key): string {
        $dir = $this->get_cache_dir();
        $filename = md5($key) . '.cache';
        return $dir . '/' . $filename;
    }

    /**
     * Get value from file store
     *
     * @param string $key Cache key
     * @return mixed|false Cached value or false
     */
    protected function get_from_store(string $key) {
        $file = $this->get_cache_file($key);

        if (!file_exists($file)) {
            return false;
        }

        // Check TTL if defined
        if (!empty($this->definition['ttl'])) {
            $mtime = filemtime($file);
            if (time() - $mtime > $this->definition['ttl']) {
                @unlink($file);
                return false;
            }
        }

        $content = @file_get_contents($file);
        if ($content === false) {
            return false;
        }

        return $this->unserialize($content);
    }

    /**
     * Set value in file store
     *
     * @param string $key Cache key
     * @param mixed $value Value
     * @return bool Success
     */
    protected function set_in_store(string $key, $value): bool {
        $file = $this->get_cache_file($key);
        $content = $this->serialize($value);
        return file_put_contents($file, $content, LOCK_EX) !== false;
    }

    /**
     * Delete value from file store
     *
     * @param string $key Cache key
     * @return bool Success
     */
    protected function delete_from_store(string $key): bool {
        $file = $this->get_cache_file($key);
        if (file_exists($file)) {
            return @unlink($file);
        }
        return true;
    }

    /**
     * Check if key exists in file store
     *
     * @param string $key Cache key
     * @return bool Exists
     */
    protected function has_in_store(string $key): bool {
        $file = $this->get_cache_file($key);

        if (!file_exists($file)) {
            return false;
        }

        // Check TTL
        if (!empty($this->definition['ttl'])) {
            $mtime = filemtime($file);
            if (time() - $mtime > $this->definition['ttl']) {
                @unlink($file);
                return false;
            }
        }

        return true;
    }

    /**
     * Purge file store
     *
     * @return bool Success
     */
    protected function purge_store(): bool {
        $dir = $this->get_cache_dir();
        if (!is_dir($dir)) {
            return true;
        }

        $files = glob($dir . '/*.cache');
        foreach ($files as $file) {
            @unlink($file);
        }
        return true;
    }

    /**
     * Serialize value for storage
     *
     * @param mixed $value Value
     * @return string Serialized value
     */
    protected function serialize($value): string {
        if (!empty($this->definition['simpledata']) && is_scalar($value)) {
            return (string)$value;
        }
        return serialize($value);
    }

    /**
     * Unserialize value from storage
     *
     * @param string $content Stored content
     * @return mixed Unserialized value
     */
    protected function unserialize(string $content) {
        if (!empty($this->definition['simpledata'])) {
            // Try to detect if it's actually serialized
            $data = @unserialize($content);
            if ($data !== false || $content === 'b:0;') {
                return $data;
            }
            return $content;
        }
        return unserialize($content);
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
}
