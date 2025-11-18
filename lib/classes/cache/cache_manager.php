<?php
namespace core\cache;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Cache Manager
 *
 * Manages different types of caches in NexoSupport:
 * - OPcache (PHP bytecode cache)
 * - Application cache (RBAC, config, etc.)
 * - Session cache
 *
 * @package core\cache
 */
class cache_manager {

    /**
     * Purge all caches
     *
     * @return array Results of purge operations
     */
    public static function purge_all(): array {
        $results = [];

        $results['opcache'] = self::purge_opcache();
        $results['application'] = self::purge_application_cache();
        $results['rbac'] = self::purge_rbac_cache();

        return $results;
    }

    /**
     * Purge PHP OPcache
     *
     * This clears the bytecode cache which can cause stale code to be executed
     *
     * @return array Status and message
     */
    public static function purge_opcache(): array {
        if (!function_exists('opcache_reset')) {
            return [
                'success' => false,
                'message' => 'OPcache extension not available',
            ];
        }

        if (!opcache_get_status()) {
            return [
                'success' => false,
                'message' => 'OPcache is not enabled',
            ];
        }

        $result = opcache_reset();

        return [
            'success' => $result,
            'message' => $result ? 'OPcache purged successfully' : 'Failed to purge OPcache',
        ];
    }

    /**
     * Purge application-level cache
     *
     * Clears PHP static caches in various classes
     *
     * @return array Status and message
     */
    public static function purge_application_cache(): array {
        $cleared = [];

        // Clear RBAC cache
        if (class_exists('\core\rbac\access')) {
            \core\rbac\access::clear_all_cache();
            $cleared[] = 'RBAC access cache';
        }

        // Clear config cache (if we implement one)
        // ...

        return [
            'success' => true,
            'message' => 'Application cache purged: ' . implode(', ', $cleared),
            'items' => $cleared,
        ];
    }

    /**
     * Purge RBAC cache specifically
     *
     * @return array Status and message
     */
    public static function purge_rbac_cache(): array {
        if (class_exists('\core\rbac\access')) {
            \core\rbac\access::clear_all_cache();

            return [
                'success' => true,
                'message' => 'RBAC cache cleared',
            ];
        }

        return [
            'success' => false,
            'message' => 'RBAC class not found',
        ];
    }

    /**
     * Get cache status information
     *
     * @return array Status of different caches
     */
    public static function get_status(): array {
        $status = [];

        // OPcache status
        if (function_exists('opcache_get_status')) {
            $opcache = opcache_get_status();
            if ($opcache) {
                $status['opcache'] = [
                    'enabled' => true,
                    'memory_used' => $opcache['memory_usage']['used_memory'] ?? 0,
                    'memory_free' => $opcache['memory_usage']['free_memory'] ?? 0,
                    'memory_wasted' => $opcache['memory_usage']['wasted_memory'] ?? 0,
                    'num_cached_scripts' => $opcache['opcache_statistics']['num_cached_scripts'] ?? 0,
                    'hits' => $opcache['opcache_statistics']['hits'] ?? 0,
                    'misses' => $opcache['opcache_statistics']['misses'] ?? 0,
                ];
            } else {
                $status['opcache'] = ['enabled' => false];
            }
        } else {
            $status['opcache'] = ['enabled' => false, 'available' => false];
        }

        return $status;
    }

    /**
     * Format bytes for human reading
     *
     * @param int $bytes
     * @return string
     */
    public static function format_bytes(int $bytes): string {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }
}
