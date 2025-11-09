<?php

/**
 * ISER Authentication System - Module Interface
 *
 * Interface for all system modules.
 *
 * @package    ISER\Core\Interfaces
 * @category   Core
 * @author     ISER Development Team
 * @copyright  2024 ISER
 * @license    Proprietary
 * @version    1.0.0
 * @since      Phase 1
 */

namespace ISER\Core\Interfaces;

/**
 * ModuleInterface
 *
 * Defines the contract that all modules must implement.
 */
interface ModuleInterface
{
    /**
     * Initialize the module
     *
     * Called when the module is loaded by the system.
     *
     * @return void
     */
    public function init(): void;

    /**
     * Get module name
     *
     * @return string Module name
     */
    public function getName(): string;

    /**
     * Get module version
     *
     * @return string Module version
     */
    public function getVersion(): string;

    /**
     * Get module description
     *
     * @return string Module description
     */
    public function getDescription(): string;

    /**
     * Get module dependencies
     *
     * Returns an array of module names that this module depends on.
     *
     * @return array Array of module names
     */
    public function getDependencies(): array;

    /**
     * Get module routes
     *
     * Returns an array of routes that this module provides.
     * Format: ['path' => 'handler', ...]
     *
     * @return array Array of routes
     */
    public function getRoutes(): array;

    /**
     * Get module permissions
     *
     * Returns an array of permissions that this module defines.
     * Format: ['permission_name' => 'description', ...]
     *
     * @return array Array of permissions
     */
    public function getPermissions(): array;

    /**
     * Check if module is enabled
     *
     * @return bool True if module is enabled
     */
    public function isEnabled(): bool;

    /**
     * Enable the module
     *
     * @return bool True on success
     */
    public function enable(): bool;

    /**
     * Disable the module
     *
     * @return bool True on success
     */
    public function disable(): bool;

    /**
     * Install the module
     *
     * Called when the module is installed. Should create necessary
     * database tables, files, and configurations.
     *
     * @return bool True on success
     */
    public function install(): bool;

    /**
     * Uninstall the module
     *
     * Called when the module is uninstalled. Should remove database
     * tables, files, and configurations.
     *
     * @return bool True on success
     */
    public function uninstall(): bool;

    /**
     * Upgrade the module
     *
     * Called when the module needs to be upgraded to a new version.
     *
     * @param string $fromVersion Current version
     * @param string $toVersion Target version
     * @return bool True on success
     */
    public function upgrade(string $fromVersion, string $toVersion): bool;

    /**
     * Get module configuration
     *
     * Returns the module's configuration array.
     *
     * @return array Configuration array
     */
    public function getConfig(): array;

    /**
     * Set module configuration
     *
     * Updates the module's configuration.
     *
     * @param array $config Configuration array
     * @return bool True on success
     */
    public function setConfig(array $config): bool;
}
