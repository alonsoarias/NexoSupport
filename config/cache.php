<?php
/**
 * NexoSupport - Cache Configuration
 *
 * Configuration for caching system including TTL values,
 * cache directories, and enabled/disabled caches
 *
 * @package    ISER\Config
 * @copyright  2025 ISER
 * @license    Proprietary
 */

return [
    /**
     * Enable/disable caching globally
     */
    'enabled' => true,

    /**
     * Cache directory (relative to project root)
     */
    'cache_dir' => 'var/cache',

    /**
     * Template caching configuration
     */
    'templates' => [
        'enabled' => true,
        'namespace' => 'templates',
        'ttl' => 86400, // 24 hours
        'invalidate_on_file_change' => true,
        'cache_dir' => 'var/cache/templates',
    ],

    /**
     * Component path caching
     */
    'components' => [
        'enabled' => true,
        'namespace' => 'components',
        'ttl' => 3600, // 1 hour
        'invalidate_on_json_change' => true,
        'cache_key_prefix' => 'component_path_',
    ],

    /**
     * Default TTL values for different cache namespaces
     */
    'ttl' => [
        'default' => 3600,      // 1 hour
        'short' => 300,         // 5 minutes
        'medium' => 3600,       // 1 hour
        'long' => 86400,        // 24 hours
        'permanent' => 604800,  // 7 days
    ],

    /**
     * Cache namespaces
     * Define all cache namespaces used in the application
     */
    'namespaces' => [
        'default' => [
            'ttl' => 3600,
            'description' => 'Default cache namespace',
        ],
        'templates' => [
            'ttl' => 86400,
            'description' => 'Compiled template cache',
        ],
        'components' => [
            'ttl' => 3600,
            'description' => 'Component path cache',
        ],
        'translations' => [
            'ttl' => 86400,
            'description' => 'Translation strings cache',
        ],
        'config' => [
            'ttl' => 604800,
            'description' => 'Configuration cache',
        ],
    ],

    /**
     * File-based cache settings
     */
    'file' => [
        'enabled' => true,
        'directory' => 'var/cache',
        'permissions' => 0755,
        'extension' => '.cache',
        'use_directory_level' => false,
    ],

    /**
     * APCu cache settings (if available)
     */
    'apcu' => [
        'enabled' => function_exists('apcu_fetch') && apcu_enabled(),
        'ttl_multiplier' => 0.9, // Use 90% of PHP TTL
    ],

    /**
     * Automatic cleanup on expiration
     */
    'cleanup' => [
        'auto_cleanup_on_get' => true,
        'cleanup_probability' => 0.01, // 1% chance to cleanup on each operation
    ],
];
