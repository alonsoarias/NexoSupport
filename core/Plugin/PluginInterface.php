<?php

declare(strict_types=1);

namespace ISER\Core\Plugin;

/**
 * PluginInterface
 *
 * Contract that all NexoSupport plugins must implement.
 * Defines the lifecycle methods required for plugin installation,
 * activation, deactivation, and uninstallation.
 *
 * @package ISER\Core\Plugin
 * @version 1.0.0
 */
interface PluginInterface
{
    /**
     * Install the plugin
     *
     * Called when the plugin is first installed. Use this method to:
     * - Create database tables
     * - Create necessary directories
     * - Set default configuration values
     * - Register default hooks
     *
     * @return bool True on success, false on failure
     * @throws \Exception If installation fails
     */
    public function install(): bool;

    /**
     * Uninstall the plugin
     *
     * Called when the plugin is being permanently removed. Use this method to:
     * - Drop database tables
     * - Remove plugin directories and files
     * - Remove configuration values
     * - Clean up any plugin data
     *
     * @return bool True on success, false on failure
     * @throws \Exception If uninstallation fails
     */
    public function uninstall(): bool;

    /**
     * Activate the plugin
     *
     * Called when the plugin is enabled/activated. Use this method to:
     * - Register hooks and filters
     * - Initialize services
     * - Start background processes
     * - Enable plugin features
     *
     * @return bool True on success, false on failure
     * @throws \Exception If activation fails
     */
    public function activate(): bool;

    /**
     * Deactivate the plugin
     *
     * Called when the plugin is disabled/deactivated. Use this method to:
     * - Unregister hooks and filters
     * - Stop background processes
     * - Clean up temporary data
     * - Disable plugin features
     *
     * @return bool True on success, false on failure
     * @throws \Exception If deactivation fails
     */
    public function deactivate(): bool;

    /**
     * Update the plugin
     *
     * Called when the plugin is being updated from an old version.
     * Use this method to:
     * - Migrate database schema
     * - Update configuration values
     * - Transform old data to new format
     * - Handle breaking changes
     *
     * @param string $oldVersion The previous version number
     * @return bool True on success, false on failure
     * @throws \Exception If update fails
     */
    public function update(string $oldVersion): bool;

    /**
     * Get plugin information
     *
     * Returns metadata about the plugin including name, version, author, etc.
     * This method should return the same information as defined in plugin.json
     *
     * @return array{
     *   name: string,
     *   slug: string,
     *   version: string,
     *   description: string,
     *   author: string,
     *   author_url?: string,
     *   plugin_url?: string,
     *   requires?: string,
     *   type: string
     * } Plugin metadata
     */
    public function getInfo(): array;

    /**
     * Get plugin configuration schema
     *
     * Returns the configuration options available for this plugin.
     * Used to generate admin UI for plugin settings.
     *
     * @return array Configuration schema with keys:
     *   - name: Setting name/key
     *   - type: Input type (text, number, boolean, select, textarea)
     *   - label: Display label
     *   - description: Help text
     *   - default: Default value
     *   - options: Available options (for select type)
     */
    public function getConfigSchema(): array;

    /**
     * Validate plugin dependencies
     *
     * Check if all required dependencies are installed and enabled.
     * This method is called before installation or activation.
     *
     * @return bool True if all dependencies are met, false otherwise
     */
    public function checkDependencies(): bool;
}
