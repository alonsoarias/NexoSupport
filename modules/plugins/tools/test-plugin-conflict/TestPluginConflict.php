<?php

/**
 * Test Plugin Conflict
 *
 * Plugin that conflicts with Test Plugin A.
 * Used to test conflict detection and prevention.
 *
 * @package    TestPluginConflict
 * @version    1.0.0
 * @conflicts  test-plugin-a
 */

class TestPluginConflict
{
    /**
     * Plugin initialization
     *
     * Called when the plugin is loaded.
     */
    public static function init(): void
    {
        // Check for conflicts (should be handled by plugin system)
        if (self::isConflictingPluginActive()) {
            throw new \RuntimeException(
                'Test Plugin Conflict cannot run simultaneously with Test Plugin A. ' .
                'Please disable Test Plugin A before enabling this plugin.'
            );
        }

        // Register functionality
        self::registerFeatures();
    }

    /**
     * Check if conflicting plugin is active
     *
     * @return bool True if Test Plugin A is active
     */
    private static function isConflictingPluginActive(): bool
    {
        // In a real implementation, this would check the plugin_manager
        return function_exists('test_plugin_a_get_info');
    }

    /**
     * Register plugin features
     */
    private static function registerFeatures(): void
    {
        // Register global function that conflicts with Test Plugin A
        if (!function_exists('test_plugin_conflict_is_active')) {
            function test_plugin_conflict_is_active(): bool {
                return true;
            }
        }

        // This function name would conflict if both plugins are loaded
        if (!function_exists('test_conflict_marker')) {
            function test_conflict_marker(): string {
                return 'conflict-plugin-active';
            }
        }
    }

    /**
     * Get plugin information
     *
     * @return array Plugin metadata
     */
    public static function getInfo(): array
    {
        return [
            'name' => 'Test Plugin Conflict',
            'version' => '1.0.0',
            'description' => 'Conflicts with Test Plugin A',
            'conflicts_with' => ['test-plugin-a'],
            'status' => 'active'
        ];
    }

    /**
     * Explain why this plugin conflicts
     *
     * @return string Explanation
     */
    public static function getConflictReason(): string
    {
        return 'Test Plugin Conflict and Test Plugin A both provide similar functionality ' .
               'and cannot be active simultaneously. They register conflicting global ' .
               'functions and hooks. Only one should be active at a time.';
    }
}
