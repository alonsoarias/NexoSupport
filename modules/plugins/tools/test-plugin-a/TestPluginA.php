<?php

/**
 * Test Plugin A
 *
 * Advanced test plugin that depends on Test Plugin B.
 * Demonstrates dependency resolution, version constraints, and conflicts.
 *
 * @package    TestPluginA
 * @version    2.0.0
 * @requires   test-plugin-b >= 1.0.0
 */

class TestPluginA
{
    /**
     * Plugin initialization
     *
     * Called when the plugin is loaded.
     * Verifies that Test Plugin B is available.
     */
    public static function init(): void
    {
        // Verify dependency is loaded
        if (!function_exists('test_plugin_b_is_active')) {
            throw new \RuntimeException(
                'Test Plugin A requires Test Plugin B to be installed and active'
            );
        }

        // Verify dependency version
        $requiredVersion = '1.0.0';
        $installedVersion = test_plugin_b_get_version();

        if (version_compare($installedVersion, $requiredVersion, '<')) {
            throw new \RuntimeException(
                sprintf(
                    'Test Plugin A requires Test Plugin B version %s or higher (installed: %s)',
                    $requiredVersion,
                    $installedVersion
                )
            );
        }

        // Register advanced features
        self::registerAdvancedFeatures();
    }

    /**
     * Add dashboard widget
     *
     * Hook: admin_dashboard
     */
    public static function addDashboardWidget(): void
    {
        // Add widget to admin dashboard
        // This demonstrates hook integration
    }

    /**
     * Show dashboard page
     *
     * Route handler for /test-plugin-a/dashboard
     */
    public static function showDashboard(): void
    {
        // Display plugin dashboard
        echo '<h1>Test Plugin A Dashboard</h1>';
        echo '<p>Dependency: Test Plugin B v' . test_plugin_b_get_version() . '</p>';
        echo '<p>Status: ' . (test_plugin_b_is_active() ? 'Active' : 'Inactive') . '</p>';
    }

    /**
     * Register advanced features
     *
     * Extends functionality provided by Test Plugin B.
     */
    private static function registerAdvancedFeatures(): void
    {
        // Register extended functionality
        if (!function_exists('test_plugin_a_get_info')) {
            function test_plugin_a_get_info(): array {
                return [
                    'name' => 'Test Plugin A',
                    'version' => '2.0.0',
                    'dependency' => 'Test Plugin B v' . test_plugin_b_get_version(),
                    'status' => 'active'
                ];
            }
        }
    }

    /**
     * Get plugin status
     *
     * @return array Status information
     */
    public static function getStatus(): array
    {
        return [
            'plugin_name' => 'Test Plugin A',
            'version' => '2.0.0',
            'dependency_installed' => function_exists('test_plugin_b_is_active'),
            'dependency_version' => function_exists('test_plugin_b_get_version')
                ? test_plugin_b_get_version()
                : 'N/A',
            'ready' => function_exists('test_plugin_b_is_active') && test_plugin_b_is_active()
        ];
    }
}
