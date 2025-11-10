<?php
/**
 * ISER - Admin Plugins Manager
 *
 * Manages system plugins/modules.
 * Handles plugin detection, enabling/disabling, and metadata.
 *
 * @package    ISER\Modules\Admin
 * @copyright  2024 ISER
 * @license    Proprietary
 * @version    6.0.0
 * @since      Phase 6
 */

namespace ISER\Modules\Admin;

use ISER\Core\Database\Database;
use ISER\Core\Utils\Logger;

class AdminPlugins
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Get all registered plugins
     *
     * @return array Plugins
     */
    public function getPlugins(): array
    {
        return $this->db->select('config_plugins', [], 'sortorder ASC');
    }

    /**
     * Get plugin by name
     *
     * @param string $plugin Plugin name
     * @return array|false Plugin data or false
     */
    public function getPlugin(string $plugin): array|false
    {
        return $this->db->selectOne('config_plugins', ['plugin' => $plugin]);
    }

    /**
     * Enable plugin
     *
     * @param string $plugin Plugin name
     * @return bool True on success
     */
    public function enablePlugin(string $plugin): bool
    {
        $result = $this->db->update('config_plugins', [
            'enabled' => 1,
            'timemodified' => time()
        ], ['plugin' => $plugin]);

        if ($result > 0) {
            Logger::auth('Plugin enabled', ['plugin' => $plugin]);
            return true;
        }

        return false;
    }

    /**
     * Disable plugin
     *
     * @param string $plugin Plugin name
     * @return bool True on success
     */
    public function disablePlugin(string $plugin): bool
    {
        // Don't allow disabling core plugins
        $corePlugins = ['auth_manual', 'user', 'roles', 'admin'];
        if (in_array($plugin, $corePlugins)) {
            Logger::error('Attempted to disable core plugin', ['plugin' => $plugin]);
            return false;
        }

        $result = $this->db->update('config_plugins', [
            'enabled' => 0,
            'timemodified' => time()
        ], ['plugin' => $plugin]);

        if ($result > 0) {
            Logger::auth('Plugin disabled', ['plugin' => $plugin]);
            return true;
        }

        return false;
    }

    /**
     * Register new plugin
     *
     * @param string $plugin Plugin identifier
     * @param string $name Plugin name
     * @param string $version Plugin version
     * @param int $sortorder Sort order
     * @return bool True on success
     */
    public function registerPlugin(
        string $plugin,
        string $name,
        string $version,
        int $sortorder = 999
    ): bool {
        try {
            $this->db->insert('config_plugins', [
                'plugin' => $plugin,
                'name' => $name,
                'version' => $version,
                'enabled' => 1,
                'visible' => 1,
                'sortorder' => $sortorder,
                'timecreated' => time(),
                'timemodified' => time()
            ]);

            Logger::auth('Plugin registered', [
                'plugin' => $plugin,
                'version' => $version
            ]);

            return true;
        } catch (\Exception $e) {
            Logger::error('Failed to register plugin', [
                'plugin' => $plugin,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Update plugin version
     *
     * @param string $plugin Plugin name
     * @param string $version New version
     * @return bool True on success
     */
    public function updatePluginVersion(string $plugin, string $version): bool
    {
        $result = $this->db->update('config_plugins', [
            'version' => $version,
            'timemodified' => time()
        ], ['plugin' => $plugin]);

        if ($result > 0) {
            Logger::auth('Plugin version updated', [
                'plugin' => $plugin,
                'version' => $version
            ]);
            return true;
        }

        return false;
    }

    /**
     * Get enabled plugins
     *
     * @return array Enabled plugins
     */
    public function getEnabledPlugins(): array
    {
        return $this->db->select('config_plugins', ['enabled' => 1], 'sortorder ASC');
    }

    /**
     * Check if plugin is enabled
     *
     * @param string $plugin Plugin name
     * @return bool True if enabled
     */
    public function isEnabled(string $plugin): bool
    {
        $pluginData = $this->getPlugin($plugin);
        return $pluginData && $pluginData['enabled'] == 1;
    }

    /**
     * Get plugin count
     *
     * @return int Total plugins
     */
    public function getPluginCount(): int
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->db->table('config_plugins')}";
        return (int)$this->db->getConnection()->fetchColumn($sql);
    }

    /**
     * Get enabled plugin count
     *
     * @return int Enabled plugins
     */
    public function getEnabledPluginCount(): int
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->db->table('config_plugins')} WHERE enabled = 1";
        return (int)$this->db->getConnection()->fetchColumn($sql);
    }
}
