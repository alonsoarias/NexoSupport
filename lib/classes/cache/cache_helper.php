<?php
namespace core\cache;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Cache Helper
 *
 * Provides helper functions for cache operations, particularly for purging.
 * Similar to Moodle's cache_helper class.
 *
 * @package    core\cache
 * @copyright  NexoSupport
 * @license    Proprietary - NexoSupport
 */
class cache_helper {

    /** @var bool Flag to track if caches are being disabled */
    private static $caches_disabled = false;

    /**
     * Purge all caches
     *
     * This completely wipes all caches. Use with caution.
     *
     * @param bool $usewriter If true, use a cache store writer for safe purging
     * @return void
     */
    public static function purge_all(bool $usewriter = false): void {
        global $CFG;

        // Reset definition cache
        cache_definition::reset();

        // Purge file-based caches
        if (!empty($CFG->cachedir)) {
            self::purge_directory($CFG->cachedir);
        }

        // Also purge localcache
        if (!empty($CFG->localcachedir)) {
            self::purge_directory($CFG->localcachedir);
        }

        // Clear static caches
        cache::reset_static_caches();

        // Clear opcache if available
        if (function_exists('opcache_reset')) {
            @opcache_reset();
        }

        // Trigger event
        self::trigger_purge_event('all', null);

        debugging('All caches purged', DEBUG_DEVELOPER);
    }

    /**
     * Purge caches by definition
     *
     * Purges a specific cache definition.
     *
     * @param string $component Component name
     * @param string $area Area name
     * @param array $identifiers Optional identifiers
     * @return bool True on success
     */
    public static function purge_by_definition(string $component, string $area, array $identifiers = []): bool {
        try {
            $cache = cache::make($component, $area, $identifiers);
            $result = $cache->purge();

            self::trigger_purge_event('definition', "$component/$area");

            debugging("Cache purged: $component/$area", DEBUG_DEVELOPER);
            return $result;
        } catch (\Exception $e) {
            debugging("Failed to purge cache $component/$area: " . $e->getMessage(), DEBUG_DEVELOPER);
            return false;
        }
    }

    /**
     * Purge caches by event
     *
     * Purges all caches that are invalidated by the given event.
     *
     * @param string $event Event name
     * @return int Number of caches purged
     */
    public static function purge_by_event(string $event): int {
        $definitions = cache_definition::get_definitions_by_event($event);
        $count = 0;

        foreach ($definitions as $key => $definition) {
            $parts = explode('/', $key);
            if (count($parts) === 2) {
                if (self::purge_by_definition($parts[0], $parts[1])) {
                    $count++;
                }
            }
        }

        self::trigger_purge_event('event', $event);

        debugging("Purged $count caches for event: $event", DEBUG_DEVELOPER);
        return $count;
    }

    /**
     * Purge stores
     *
     * Purges all stores for a specific mode.
     *
     * @param int $mode Cache mode (MODE_APPLICATION, MODE_SESSION, MODE_REQUEST)
     * @return bool True on success
     */
    public static function purge_stores(int $mode): bool {
        global $CFG;

        switch ($mode) {
            case cache::MODE_APPLICATION:
                if (!empty($CFG->cachedir)) {
                    self::purge_directory($CFG->cachedir . '/application');
                }
                break;

            case cache::MODE_SESSION:
                // Session caches are typically handled by PHP session
                if (session_status() === PHP_SESSION_ACTIVE) {
                    $prefix = 'cache_';
                    foreach ($_SESSION as $key => $value) {
                        if (strpos($key, $prefix) === 0) {
                            unset($_SESSION[$key]);
                        }
                    }
                }
                break;

            case cache::MODE_REQUEST:
                // Request caches are static and will be cleared at end of request
                cache::reset_static_caches();
                break;

            default:
                return false;
        }

        return true;
    }

    /**
     * Invalidate cache by key pattern
     *
     * @param string $component Component name
     * @param string $area Area name
     * @param string $pattern Key pattern (supports wildcards)
     * @return int Number of keys invalidated
     */
    public static function invalidate_by_pattern(string $component, string $area, string $pattern): int {
        global $CFG;

        $count = 0;
        $cachedir = self::get_cache_directory($component, $area);

        if (!is_dir($cachedir)) {
            return 0;
        }

        // Convert pattern to regex
        $regex = '/^' . str_replace(['*', '?'], ['.*', '.'], preg_quote($pattern, '/')) . '$/';

        $iterator = new \DirectoryIterator($cachedir);
        foreach ($iterator as $file) {
            if ($file->isDot() || $file->isDir()) {
                continue;
            }

            $filename = $file->getFilename();
            $key = pathinfo($filename, PATHINFO_FILENAME);

            if (preg_match($regex, $key)) {
                @unlink($file->getPathname());
                $count++;
            }
        }

        return $count;
    }

    /**
     * Get cache statistics
     *
     * @return array Cache statistics
     */
    public static function get_stats(): array {
        global $CFG;

        $stats = [
            'definitions' => count(cache_definition::get_all_definitions()),
            'stores' => [
                'application' => self::get_directory_stats($CFG->cachedir . '/application'),
                'localcache' => self::get_directory_stats($CFG->localcachedir ?? ''),
            ],
        ];

        // Add memory usage if available
        if (function_exists('memory_get_usage')) {
            $stats['memory'] = [
                'current' => memory_get_usage(true),
                'peak' => memory_get_peak_usage(true),
            ];
        }

        // Add opcache stats if available
        if (function_exists('opcache_get_status')) {
            $opcache = @opcache_get_status(false);
            if ($opcache) {
                $stats['opcache'] = [
                    'enabled' => $opcache['opcache_enabled'] ?? false,
                    'memory_used' => $opcache['memory_usage']['used_memory'] ?? 0,
                    'hit_rate' => $opcache['opcache_statistics']['opcache_hit_rate'] ?? 0,
                ];
            }
        }

        return $stats;
    }

    /**
     * Check if caches are disabled
     *
     * @return bool True if caches are disabled
     */
    public static function caches_disabled(): bool {
        return self::$caches_disabled;
    }

    /**
     * Disable all caches
     *
     * Used during installation/upgrade to prevent caching issues.
     *
     * @return void
     */
    public static function disable_caches(): void {
        self::$caches_disabled = true;
    }

    /**
     * Enable caches
     *
     * @return void
     */
    public static function enable_caches(): void {
        self::$caches_disabled = false;
    }

    /**
     * Purge a directory recursively
     *
     * @param string $dir Directory to purge
     * @param bool $removedir Remove the directory itself
     * @return bool True on success
     */
    public static function purge_directory(string $dir, bool $removedir = false): bool {
        if (!is_dir($dir)) {
            return true;
        }

        $iterator = new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new \RecursiveIteratorIterator($iterator, \RecursiveIteratorIterator::CHILD_FIRST);

        foreach ($files as $file) {
            if ($file->isDir()) {
                @rmdir($file->getPathname());
            } else {
                @unlink($file->getPathname());
            }
        }

        if ($removedir) {
            @rmdir($dir);
        }

        return true;
    }

    /**
     * Get cache directory for a definition
     *
     * @param string $component Component name
     * @param string $area Area name
     * @return string Directory path
     */
    protected static function get_cache_directory(string $component, string $area): string {
        global $CFG;

        $hash = md5("$component/$area");
        return $CFG->cachedir . '/application/' . $hash;
    }

    /**
     * Get directory statistics
     *
     * @param string $dir Directory path
     * @return array Statistics
     */
    protected static function get_directory_stats(string $dir): array {
        if (!is_dir($dir)) {
            return [
                'exists' => false,
                'files' => 0,
                'size' => 0,
            ];
        }

        $files = 0;
        $size = 0;

        $iterator = new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS);
        $fileiterator = new \RecursiveIteratorIterator($iterator);

        foreach ($fileiterator as $file) {
            if ($file->isFile()) {
                $files++;
                $size += $file->getSize();
            }
        }

        return [
            'exists' => true,
            'files' => $files,
            'size' => $size,
            'size_formatted' => self::format_bytes($size),
        ];
    }

    /**
     * Format bytes to human-readable string
     *
     * @param int $bytes Bytes
     * @return string Formatted string
     */
    protected static function format_bytes(int $bytes): string {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Trigger cache purge event
     *
     * @param string $type Purge type (all, definition, event, store)
     * @param mixed $target Target (definition key, event name, etc.)
     * @return void
     */
    protected static function trigger_purge_event(string $type, $target): void {
        // Could trigger an event here if event system is loaded
        // For now, just log it
        if (defined('DEBUG_DEVELOPER') && function_exists('debugging')) {
            debugging("Cache purge: type=$type, target=$target", DEBUG_DEVELOPER);
        }
    }

    /**
     * Create a cache lock
     *
     * Prevents multiple processes from writing to the same cache simultaneously.
     *
     * @param string $component Component name
     * @param string $area Area name
     * @param string $key Cache key
     * @return resource|false Lock file handle or false on failure
     */
    public static function acquire_lock(string $component, string $area, string $key) {
        global $CFG;

        $lockdir = $CFG->cachedir . '/locks';
        if (!is_dir($lockdir)) {
            @mkdir($lockdir, 0755, true);
        }

        $lockfile = $lockdir . '/' . md5("$component/$area/$key") . '.lock';
        $handle = @fopen($lockfile, 'c');

        if ($handle && flock($handle, LOCK_EX | LOCK_NB)) {
            return $handle;
        }

        if ($handle) {
            fclose($handle);
        }

        return false;
    }

    /**
     * Release a cache lock
     *
     * @param resource $handle Lock file handle
     * @return void
     */
    public static function release_lock($handle): void {
        if (is_resource($handle)) {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}
