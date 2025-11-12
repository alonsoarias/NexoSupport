<?php

declare(strict_types=1);

namespace HelloWorld;

use ISER\Core\Plugin\PluginInterface;
use ISER\Core\Database\Database;
use ISER\Core\Logger\Logger;

/**
 * Hello World Plugin
 *
 * Example plugin demonstrating the NexoSupport plugin system.
 * This plugin shows how to implement all required PluginInterface methods.
 *
 * @package HelloWorld
 * @version 1.0.0
 */
class Plugin implements PluginInterface
{
    private Database $db;
    private array $manifest;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->manifest = $this->loadManifest();
    }

    /**
     * Load plugin manifest from plugin.json
     */
    private function loadManifest(): array
    {
        $manifestPath = __DIR__ . '/../plugin.json';
        if (!file_exists($manifestPath)) {
            return [];
        }

        $content = file_get_contents($manifestPath);
        return json_decode($content, true) ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function install(): bool
    {
        try {
            Logger::info('Installing Hello World plugin');

            // Example: Create plugin-specific table (optional)
            /*
            $this->db->execute("
                CREATE TABLE IF NOT EXISTS hello_world_data (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    user_id INT UNSIGNED NOT NULL,
                    message TEXT,
                    created_at INT UNSIGNED NOT NULL,
                    INDEX idx_user_id (user_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            */

            // Set default configuration
            $this->setDefaultConfig();

            Logger::info('Hello World plugin installed successfully');
            return true;

        } catch (\Exception $e) {
            Logger::error('Failed to install Hello World plugin', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function uninstall(): bool
    {
        try {
            Logger::info('Uninstalling Hello World plugin');

            // Example: Drop plugin-specific table
            /*
            $this->db->execute("DROP TABLE IF EXISTS hello_world_data");
            */

            // Remove plugin settings
            $this->removeConfig();

            Logger::info('Hello World plugin uninstalled successfully');
            return true;

        } catch (\Exception $e) {
            Logger::error('Failed to uninstall Hello World plugin', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function activate(): bool
    {
        try {
            Logger::info('Activating Hello World plugin');

            // Register hooks
            $this->registerHooks();

            // Initialize services
            // ...

            Logger::info('Hello World plugin activated successfully');
            return true;

        } catch (\Exception $e) {
            Logger::error('Failed to activate Hello World plugin', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deactivate(): bool
    {
        try {
            Logger::info('Deactivating Hello World plugin');

            // Unregister hooks
            // (HookManager handles this automatically when plugin is disabled)

            // Stop services
            // ...

            Logger::info('Hello World plugin deactivated successfully');
            return true;

        } catch (\Exception $e) {
            Logger::error('Failed to deactivate Hello World plugin', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function update(string $oldVersion): bool
    {
        try {
            Logger::info('Updating Hello World plugin', [
                'from' => $oldVersion,
                'to' => $this->manifest['version'] ?? 'unknown'
            ]);

            // Example: Version-specific updates
            if (version_compare($oldVersion, '1.0.0', '<')) {
                // Migrate from pre-1.0.0 to 1.0.0
                // $this->migrateToV1();
            }

            Logger::info('Hello World plugin updated successfully');
            return true;

        } catch (\Exception $e) {
            Logger::error('Failed to update Hello World plugin', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getInfo(): array
    {
        return [
            'name' => $this->manifest['name'] ?? 'Hello World Tool',
            'slug' => $this->manifest['slug'] ?? 'hello-world',
            'version' => $this->manifest['version'] ?? '1.0.0',
            'description' => $this->manifest['description'] ?? '',
            'author' => $this->manifest['author'] ?? 'NexoSupport Team',
            'author_url' => $this->manifest['author_url'] ?? '',
            'plugin_url' => $this->manifest['plugin_url'] ?? '',
            'requires' => $this->manifest['requires'] ?? '1.0.0',
            'type' => $this->manifest['type'] ?? 'tools'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigSchema(): array
    {
        return $this->manifest['config_schema'] ?? [
            [
                'name' => 'greeting_message',
                'type' => 'text',
                'label' => 'Greeting Message',
                'description' => 'The message to display to users',
                'default' => 'Hello, World!'
            ],
            [
                'name' => 'show_icon',
                'type' => 'boolean',
                'label' => 'Show Icon',
                'description' => 'Display an icon next to the greeting',
                'default' => true
            ],
            [
                'name' => 'icon_color',
                'type' => 'select',
                'label' => 'Icon Color',
                'description' => 'Color of the icon',
                'default' => 'green',
                'options' => [
                    ['value' => 'green', 'label' => 'Green'],
                    ['value' => 'blue', 'label' => 'Blue'],
                    ['value' => 'red', 'label' => 'Red'],
                    ['value' => 'yellow', 'label' => 'Yellow']
                ]
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function checkDependencies(): bool
    {
        // This plugin has no dependencies
        $dependencies = $this->manifest['dependencies'] ?? [];

        if (empty($dependencies)) {
            return true;
        }

        // Check if all dependencies are installed and enabled
        foreach ($dependencies as $dependency) {
            $plugin = $this->db->selectOne('plugins', [
                'slug' => $dependency,
                'enabled' => true
            ]);

            if (!$plugin) {
                Logger::warning('Missing dependency for Hello World plugin', [
                    'dependency' => $dependency
                ]);
                return false;
            }
        }

        return true;
    }

    /**
     * Register plugin hooks
     */
    private function registerHooks(): void
    {
        $hookManager = \ISER\Core\Plugin\HookManager::getInstance();

        // Register menu item hook
        $hookManager->register('admin.tools.menu', function () {
            return [
                'name' => 'Hello World',
                'url' => '/admin/tools/hello-world',
                'icon' => 'bi-emoji-smile',
                'permission' => 'tools.helloworld.view'
            ];
        }, 10);
    }

    /**
     * Set default configuration
     */
    private function setDefaultConfig(): void
    {
        $schema = $this->getConfigSchema();

        foreach ($schema as $field) {
            $key = $field['name'];
            $default = $field['default'] ?? null;

            if ($default !== null) {
                // Store in plugin_settings table
                $this->db->insert('plugin_settings', [
                    'plugin_id' => $this->getPluginId(),
                    'setting_key' => $key,
                    'setting_value' => is_bool($default) ? ($default ? '1' : '0') : (string)$default,
                    'setting_type' => $this->detectType($default)
                ], true); // Use REPLACE INTO to avoid duplicates
            }
        }
    }

    /**
     * Remove plugin configuration
     */
    private function removeConfig(): void
    {
        $pluginId = $this->getPluginId();
        if ($pluginId) {
            $this->db->delete('plugin_settings', ['plugin_id' => $pluginId]);
        }
    }

    /**
     * Get plugin ID from database
     */
    private function getPluginId(): ?int
    {
        $plugin = $this->db->selectOne('plugins', ['slug' => 'hello-world']);
        return $plugin['id'] ?? null;
    }

    /**
     * Detect setting type from value
     */
    private function detectType($value): string
    {
        if (is_bool($value)) {
            return 'bool';
        } elseif (is_int($value)) {
            return 'int';
        } elseif (is_array($value) || is_object($value)) {
            return 'json';
        }
        return 'string';
    }

    /**
     * Register menu item in admin tools menu
     * This is called via hook: admin.tools.menu
     */
    public static function registerMenuItem(): array
    {
        return [
            'name' => 'Hello World',
            'url' => '/admin/tools/hello-world',
            'icon' => 'bi-emoji-smile',
            'permission' => 'tools.helloworld.view',
            'order' => 100
        ];
    }
}
