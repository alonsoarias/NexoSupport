<?php
/**
 * NexoSupport - Cache Manager
 *
 * @package    core
 * @copyright  2024 ISER
 * @license    Proprietary
 */

namespace ISER\Core\Cache;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Cache Manager
 *
 * Multi-layer caching system (file, memory, APCu)
 */
class CacheManager
{
    /** @var array In-memory cache */
    private static $memory_cache = [];

    /** @var string Cache directory */
    private static $cache_dir = null;

    /** @var bool Use APCu if available */
    private static $use_apcu = false;

    /** @var int Default TTL (1 hour) */
    private static $default_ttl = 3600;

    /**
     * Initialize cache manager
     */
    public static function init(): void
    {
        self::$cache_dir = __DIR__ . '/../../../cache';

        // Create cache directory if not exists
        if (!is_dir(self::$cache_dir)) {
            mkdir(self::$cache_dir, 0755, true);
        }

        // Check if APCu is available
        self::$use_apcu = function_exists('apcu_fetch') && apcu_enabled();
    }

    /**
     * Get from cache
     *
     * @param string $key Cache key
     * @param mixed $default Default value if not found
     * @return mixed Cached value or default
     */
    public static function get(string $key, $default = null)
    {
        // 1. Check memory cache (fastest)
        if (isset(self::$memory_cache[$key])) {
            $data = self::$memory_cache[$key];
            if ($data['expires'] > time()) {
                return $data['value'];
            }
            unset(self::$memory_cache[$key]);
        }

        // 2. Check APCu (fast)
        if (self::$use_apcu) {
            $value = apcu_fetch($key, $success);
            if ($success) {
                self::$memory_cache[$key] = [
                    'value' => $value,
                    'expires' => time() + self::$default_ttl,
                ];
                return $value;
            }
        }

        // 3. Check file cache (slower)
        $file = self::get_cache_file($key);
        if (file_exists($file)) {
            $data = unserialize(file_get_contents($file));

            if ($data['expires'] > time()) {
                // Warm up memory and APCu
                self::$memory_cache[$key] = $data;
                if (self::$use_apcu) {
                    apcu_store($key, $data['value'], $data['expires'] - time());
                }
                return $data['value'];
            }

            // Expired - delete file
            @unlink($file);
        }

        return $default;
    }

    /**
     * Set cache
     *
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param int $ttl Time to live in seconds (default: 1 hour)
     * @return bool Success
     */
    public static function set(string $key, $value, int $ttl = null): bool
    {
        $ttl = $ttl ?? self::$default_ttl;
        $expires = time() + $ttl;

        $data = [
            'value' => $value,
            'expires' => $expires,
        ];

        // 1. Set in memory cache
        self::$memory_cache[$key] = $data;

        // 2. Set in APCu
        if (self::$use_apcu) {
            apcu_store($key, $value, $ttl);
        }

        // 3. Set in file cache
        $file = self::get_cache_file($key);
        return file_put_contents($file, serialize($data)) !== false;
    }

    /**
     * Delete from cache
     *
     * @param string $key Cache key
     * @return bool Success
     */
    public static function delete(string $key): bool
    {
        // 1. Delete from memory
        unset(self::$memory_cache[$key]);

        // 2. Delete from APCu
        if (self::$use_apcu) {
            apcu_delete($key);
        }

        // 3. Delete from file cache
        $file = self::get_cache_file($key);
        if (file_exists($file)) {
            return @unlink($file);
        }

        return true;
    }

    /**
     * Flush all cache
     *
     * @return bool Success
     */
    public static function flush(): bool
    {
        // 1. Clear memory cache
        self::$memory_cache = [];

        // 2. Clear APCu
        if (self::$use_apcu) {
            apcu_clear_cache();
        }

        // 3. Clear file cache
        if (is_dir(self::$cache_dir)) {
            $files = glob(self::$cache_dir . '/*.cache');
            foreach ($files as $file) {
                @unlink($file);
            }
        }

        return true;
    }

    /**
     * Get cache file path
     *
     * @param string $key Cache key
     * @return string File path
     */
    private static function get_cache_file(string $key): string
    {
        $hash = md5($key);
        return self::$cache_dir . '/' . $hash . '.cache';
    }

    /**
     * Get cache statistics
     *
     * @return array Statistics
     */
    public static function get_stats(): array
    {
        $stats = [
            'memory_items' => count(self::$memory_cache),
            'apcu_enabled' => self::$use_apcu,
            'file_cache_dir' => self::$cache_dir,
            'file_cache_items' => 0,
            'file_cache_size' => 0,
        ];

        if (is_dir(self::$cache_dir)) {
            $files = glob(self::$cache_dir . '/*.cache');
            $stats['file_cache_items'] = count($files);

            foreach ($files as $file) {
                $stats['file_cache_size'] += filesize($file);
            }
        }

        return $stats;
    }

    /**
     * Remember (get or set)
     *
     * @param string $key Cache key
     * @param callable $callback Callback to generate value if not cached
     * @param int $ttl Time to live
     * @return mixed Cached value
     */
    public static function remember(string $key, callable $callback, int $ttl = null)
    {
        $value = self::get($key);

        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        self::set($key, $value, $ttl);

        return $value;
    }
}

// Initialize cache manager
CacheManager::init();
