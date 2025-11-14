<?php

/**
 * Test Plugin Config
 *
 * Demonstrates the configuration system with all supported field types.
 * Shows how plugins can use configuration values at runtime.
 *
 * @package    TestPluginConfig
 * @version    1.5.0
 */

class TestPluginConfig
{
    /**
     * Plugin configuration
     */
    private static array $config = [];

    /**
     * Plugin initialization
     *
     * Called when the plugin is loaded.
     * Loads configuration from database.
     */
    public static function init(): void
    {
        // Load configuration (in real implementation, this would load from database)
        self::loadConfiguration();

        // Validate required configuration
        self::validateConfiguration();

        // Initialize features based on config
        if (self::getConfig('enable_feature', false)) {
            self::initializeAdvancedFeatures();
        }

        if (self::getConfig('enable_logging', false)) {
            self::enableDebugLogging();
        }
    }

    /**
     * Add admin menu items
     *
     * Hook: admin_menu
     */
    public static function addAdminMenu(): void
    {
        $welcomeMessage = self::getConfig('welcome_message', 'Welcome!');
        // Menu item would include the custom welcome message
    }

    /**
     * Load configuration from database
     *
     * In a real implementation, this would use PluginConfigurator.
     */
    private static function loadConfiguration(): void
    {
        // Mock configuration loading
        // Real implementation: $configurator = new PluginConfigurator($db, $pm);
        //                      self::$config = $configurator->getConfig('test-plugin-config');

        self::$config = [
            'api_key' => 'test_key_1234567890abcdef',
            'api_endpoint' => 'https://api.example.com',
            'admin_email' => 'admin@example.com',
            'enable_feature' => true,
            'enable_logging' => false,
            'max_items' => 10,
            'cache_timeout' => 3600,
            'theme_color' => 'blue',
            'notification_type' => 'email',
            'custom_css' => '',
            'welcome_message' => 'Welcome to Test Plugin Config!',
            'api_secret' => ''
        ];
    }

    /**
     * Validate required configuration
     *
     * Ensures all required fields are set.
     */
    private static function validateConfiguration(): void
    {
        $requiredFields = ['api_key', 'api_endpoint', 'admin_email'];

        foreach ($requiredFields as $field) {
            if (empty(self::$config[$field])) {
                throw new \RuntimeException(
                    "Test Plugin Config: Required field '{$field}' is not configured. " .
                    "Please configure the plugin at /admin/plugins/test-plugin-config/configure"
                );
            }
        }
    }

    /**
     * Get configuration value
     *
     * @param string $key Configuration key
     * @param mixed $default Default value if not set
     * @return mixed Configuration value
     */
    public static function getConfig(string $key, $default = null)
    {
        return self::$config[$key] ?? $default;
    }

    /**
     * Initialize advanced features
     *
     * Called when enable_feature is true.
     */
    private static function initializeAdvancedFeatures(): void
    {
        // Initialize advanced functionality
        // Uses: api_key, api_endpoint, api_secret
    }

    /**
     * Enable debug logging
     *
     * Called when enable_logging is true.
     */
    private static function enableDebugLogging(): void
    {
        // Enable detailed logging
        // Logs to: /var/log/test-plugin-config.log
    }

    /**
     * Make API request using configured endpoint
     *
     * Example of using configuration at runtime.
     *
     * @param string $path API path
     * @return array Response data
     */
    public static function apiRequest(string $path): array
    {
        $endpoint = self::getConfig('api_endpoint');
        $apiKey = self::getConfig('api_key');
        $cacheTimeout = self::getConfig('cache_timeout', 3600);

        // Make request (simplified example)
        $url = rtrim($endpoint, '/') . '/' . ltrim($path, '/');

        // Cache for configured timeout
        // Return cached response if available and not expired

        return [
            'url' => $url,
            'cache_timeout' => $cacheTimeout,
            'authenticated' => !empty($apiKey)
        ];
    }

    /**
     * Get plugin status and configuration summary
     *
     * @return array Status information
     */
    public static function getStatus(): array
    {
        return [
            'plugin_name' => 'Test Plugin Config',
            'version' => '1.5.0',
            'configured' => !empty(self::$config['api_key']),
            'api_endpoint' => self::$config['api_endpoint'] ?? 'not configured',
            'advanced_features' => self::$config['enable_feature'] ?? false,
            'debug_logging' => self::$config['enable_logging'] ?? false,
            'theme_color' => self::$config['theme_color'] ?? 'blue',
            'max_items_per_page' => self::$config['max_items'] ?? 10,
            'cache_timeout' => self::$config['cache_timeout'] ?? 3600,
            'notification_method' => self::$config['notification_type'] ?? 'email'
        ];
    }

    /**
     * Demonstrate all configuration types
     *
     * Shows how each config type can be used.
     */
    public static function demonstrateConfigUsage(): array
    {
        return [
            // String validation (api_key: 32-128 chars, alphanumeric)
            'api_auth' => 'Bearer ' . self::getConfig('api_key'),

            // URL validation
            'api_base' => self::getConfig('api_endpoint'),

            // Email validation
            'notify' => self::getConfig('admin_email'),

            // Boolean/Checkbox
            'features_enabled' => self::getConfig('enable_feature', false),
            'debug_mode' => self::getConfig('enable_logging', false),

            // Integer with range (1-100)
            'pagination' => self::getConfig('max_items', 10),

            // Number with range (60-86400)
            'cache_ttl' => self::getConfig('cache_timeout', 3600),

            // Select dropdown
            'ui_theme' => self::getConfig('theme_color', 'blue'),

            // Radio buttons
            'alert_method' => self::getConfig('notification_type', 'email'),

            // Textarea (0-5000 chars)
            'custom_styles' => self::getConfig('custom_css', ''),

            // Text (0-200 chars)
            'greeting' => self::getConfig('welcome_message', 'Welcome!'),

            // Password (16-64 chars, optional)
            'webhook_signature' => !empty(self::getConfig('api_secret'))
        ];
    }
}
