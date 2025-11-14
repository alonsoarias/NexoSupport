<?php

/**
 * Test Plugin B
 *
 * Base dependency plugin for testing the plugin system.
 * Provides core functionality that other plugins can depend on.
 *
 * @package    TestPluginB
 * @version    1.0.0
 */

class TestPluginB
{
    /**
     * Plugin initialization
     *
     * Called when the plugin is loaded.
     */
    public static function init(): void
    {
        // Register core functionality
        self::registerCoreFeatures();
    }

    /**
     * Add admin menu items
     *
     * Hook: admin_menu
     */
    public static function addAdminMenu(): void
    {
        // Add menu item for Test Plugin B
        // This would normally add items to the admin navigation
    }

    /**
     * Register core features
     *
     * Provides shared functionality for dependent plugins.
     */
    private static function registerCoreFeatures(): void
    {
        // Register a global function that dependent plugins can use
        if (!function_exists('test_plugin_b_get_version')) {
            function test_plugin_b_get_version(): string {
                return '1.0.0';
            }
        }

        if (!function_exists('test_plugin_b_is_active')) {
            function test_plugin_b_is_active(): bool {
                return true;
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
            'name' => 'Test Plugin B',
            'version' => '1.0.0',
            'description' => 'Base dependency plugin for testing',
            'status' => 'active'
        ];
    }
}
