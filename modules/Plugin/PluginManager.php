<?php

/**
 * ISER - Plugin Manager
 *
 * Manages plugin lifecycle including installation, activation, and deactivation.
 * Provides querying and dependency checking functionality.
 *
 * @package    ISER\Plugin
 * @category   Modules
 * @author     ISER Development Team
 * @copyright  2024 ISER
 * @license    Proprietary
 * @version    1.0.0
 * @since      Phase 2
 */

namespace ISER\Plugin;

use ISER\Core\Database\Database;
use ISER\Core\Utils\Logger;

/**
 * PluginManager Class
 *
 * Central manager for plugin operations including:
 * - Querying plugin database
 * - Enabling/disabling plugins
 * - Uninstalling plugins
 * - Checking dependencies
 */
class PluginManager
{
    /**
     * Database instance
     */
    private Database $db;

    /**
     * Cache for plugin data
     */
    private array $pluginCache = [];

    /**
     * Cache expiration time (in seconds)
     */
    private int $cacheExpiry = 300;

    /**
     * Cache timestamp
     */
    private int $cacheTime = 0;

    /**
     * Constructor
     *
     * @param Database $db Database instance
     */
    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Get all plugins from database
     *
     * @return array All plugins
     */
    public function getAll(): array
    {
        try {
            $plugins = $this->db->select('plugins', [], 'priority ASC, name ASC');
            return $plugins ?: [];
        } catch (\Exception $e) {
            Logger::error('Failed to get all plugins', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get only enabled plugins
     *
     * @return array Enabled plugins
     */
    public function getEnabled(): array
    {
        try {
            $plugins = $this->db->select('plugins', ['enabled' => 1], 'priority ASC, name ASC');
            return $plugins ?: [];
        } catch (\Exception $e) {
            Logger::error('Failed to get enabled plugins', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get plugin by slug
     *
     * @param string $slug Plugin slug identifier
     * @return array|null Plugin data or null if not found
     */
    public function getBySlug(string $slug): ?array
    {
        try {
            $plugin = $this->db->selectOne('plugins', ['slug' => $slug]);
            return $plugin ?: null;
        } catch (\Exception $e) {
            Logger::error('Failed to get plugin by slug', [
                'slug' => $slug,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get plugins by type
     *
     * @param string $type Plugin type (tool, auth, theme, report, module, integration)
     * @return array Plugins of the specified type
     */
    public function getByType(string $type): array
    {
        try {
            $plugins = $this->db->select('plugins', ['type' => $type], 'priority ASC, name ASC');
            return $plugins ?: [];
        } catch (\Exception $e) {
            Logger::error('Failed to get plugins by type', [
                'type' => $type,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Enable a plugin
     *
     * @param string $slug Plugin slug identifier
     * @return bool True on success
     */
    public function enable(string $slug): bool
    {
        try {
            $plugin = $this->getBySlug($slug);

            if (!$plugin) {
                Logger::warning('Plugin not found for enabling', ['slug' => $slug]);
                return false;
            }

            // Check for conflicts before enabling
            $conflicts = $this->checkPluginConflicts($slug);
            if ($conflicts['has_conflicts']) {
                Logger::warning('Plugin has conflicts with enabled plugins', [
                    'slug' => $slug,
                    'conflicts' => array_column($conflicts['conflicts'], 'slug')
                ]);
                return false;
            }

            // Check dependencies before enabling
            $dependencies = $this->checkDependencies($slug);
            if (!$dependencies['satisfied']) {
                Logger::warning('Plugin dependencies not satisfied', [
                    'slug' => $slug,
                    'missing' => $dependencies['missing']
                ]);
                return false;
            }

            $result = $this->db->update('plugins', [
                'enabled' => 1,
                'activated_at' => time(),
                'updated_at' => time()
            ], ['slug' => $slug]);

            if ($result > 0) {
                Logger::info('Plugin enabled', [
                    'slug' => $slug,
                    'name' => $plugin['name']
                ]);
                $this->clearCache();
                return true;
            }

            return false;

        } catch (\Exception $e) {
            Logger::error('Failed to enable plugin', [
                'slug' => $slug,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Disable a plugin
     *
     * @param string $slug Plugin slug identifier
     * @return bool True on success
     */
    public function disable(string $slug): bool
    {
        try {
            $plugin = $this->getBySlug($slug);

            if (!$plugin) {
                Logger::warning('Plugin not found for disabling', ['slug' => $slug]);
                return false;
            }

            // Don't allow disabling core plugins
            if (!empty($plugin['is_core']) && $plugin['is_core'] == 1) {
                Logger::warning('Attempted to disable core plugin', ['slug' => $slug]);
                return false;
            }

            $result = $this->db->update('plugins', [
                'enabled' => 0,
                'updated_at' => time()
            ], ['slug' => $slug]);

            if ($result > 0) {
                Logger::info('Plugin disabled', [
                    'slug' => $slug,
                    'name' => $plugin['name']
                ]);
                $this->clearCache();
                return true;
            }

            return false;

        } catch (\Exception $e) {
            Logger::error('Failed to disable plugin', [
                'slug' => $slug,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Uninstall a plugin
     *
     * @param string $slug Plugin slug identifier
     * @return bool True on success
     */
    public function uninstall(string $slug): bool
    {
        try {
            $plugin = $this->getBySlug($slug);

            if (!$plugin) {
                Logger::warning('Plugin not found for uninstallation', ['slug' => $slug]);
                return false;
            }

            // Don't allow uninstalling core plugins
            if (!empty($plugin['is_core']) && $plugin['is_core'] == 1) {
                Logger::warning('Attempted to uninstall core plugin', ['slug' => $slug]);
                return false;
            }

            // Check if other plugins depend on this one
            $dependents = $this->getDependents($slug);
            if (!empty($dependents)) {
                Logger::warning('Cannot uninstall plugin with dependents', [
                    'slug' => $slug,
                    'dependents' => array_column($dependents, 'slug')
                ]);
                return false;
            }

            // Delete related data
            try {
                $this->db->delete('plugin_config', ['plugin_slug' => $slug]);
                $this->db->delete('plugin_hooks', ['plugin_slug' => $slug]);
                $this->db->delete('plugin_routes', ['plugin_slug' => $slug]);
                $this->db->delete('plugin_permissions', ['plugin_slug' => $slug]);
            } catch (\Exception $e) {
                Logger::warning('Failed to delete plugin related data', [
                    'slug' => $slug,
                    'error' => $e->getMessage()
                ]);
            }

            // Delete plugin
            $result = $this->db->delete('plugins', ['slug' => $slug]);

            if ($result > 0) {
                Logger::info('Plugin uninstalled', [
                    'slug' => $slug,
                    'name' => $plugin['name']
                ]);
                $this->clearCache();
                return true;
            }

            return false;

        } catch (\Exception $e) {
            Logger::error('Failed to uninstall plugin', [
                'slug' => $slug,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Update plugin version and manifest
     *
     * Updates the plugin's version number and manifest data in the database.
     * Called after successful plugin update.
     *
     * @param string $slug Plugin slug identifier
     * @param string $newVersion New version number
     * @param string $manifestJson Updated manifest as JSON string
     * @return bool True on success
     */
    public function updateVersion(string $slug, string $newVersion, string $manifestJson): bool
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE plugins
                SET version = :version,
                    manifest = :manifest,
                    updated_at = :updated_at
                WHERE slug = :slug
            ");

            $result = $stmt->execute([
                'version' => $newVersion,
                'manifest' => $manifestJson,
                'updated_at' => time(),
                'slug' => $slug
            ]);

            if ($result) {
                // Clear cache
                $this->clearCache();

                Logger::info('Plugin version updated in database', [
                    'slug' => $slug,
                    'new_version' => $newVersion
                ]);
            }

            return $result;

        } catch (\Exception $e) {
            Logger::error('Failed to update plugin version', [
                'slug' => $slug,
                'version' => $newVersion,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Check plugin dependencies
     *
     * Returns array with:
     * - 'satisfied' (bool): Whether all dependencies are met
     * - 'missing' (array): List of missing dependencies
     * - 'incompatible' (array): List of incompatible versions
     *
     * @param string $slug Plugin slug identifier
     * @return array Dependency check results
     */
    public function checkDependencies(string $slug): array
    {
        $result = [
            'satisfied' => true,
            'missing' => [],
            'incompatible' => [],
            'warnings' => []
        ];

        try {
            $plugin = $this->getBySlug($slug);

            if (!$plugin) {
                $result['satisfied'] = false;
                $result['missing'][] = 'Plugin not found';
                return $result;
            }

            // Parse manifest if available
            $manifest = [];
            if (!empty($plugin['manifest'])) {
                try {
                    $manifest = json_decode($plugin['manifest'], true) ?? [];
                } catch (\Exception $e) {
                    Logger::warning('Failed to parse plugin manifest', [
                        'slug' => $slug,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Check required plugins
            if (!empty($manifest['requires']['plugins'])) {
                foreach ($manifest['requires']['plugins'] as $requiredPlugin) {
                    $requiredSlug = $requiredPlugin['slug'] ?? null;
                    $requiredVersion = $requiredPlugin['version'] ?? '*';

                    if (!$requiredSlug) {
                        continue;
                    }

                    $depPlugin = $this->getBySlug($requiredSlug);

                    if (!$depPlugin || $depPlugin['enabled'] != 1) {
                        $result['satisfied'] = false;
                        $result['missing'][] = $requiredSlug;
                        continue;
                    }

                    // Check version compatibility if specified
                    if ($requiredVersion !== '*' && !$this->isVersionCompatible(
                        $depPlugin['version'],
                        $requiredVersion
                    )) {
                        $result['satisfied'] = false;
                        $result['incompatible'][] = [
                            'slug' => $requiredSlug,
                            'required' => $requiredVersion,
                            'installed' => $depPlugin['version']
                        ];
                    }
                }
            }

            Logger::system('Dependency check completed', [
                'slug' => $slug,
                'satisfied' => $result['satisfied']
            ]);

        } catch (\Exception $e) {
            Logger::error('Dependency check failed', [
                'slug' => $slug,
                'error' => $e->getMessage()
            ]);
            $result['satisfied'] = false;
        }

        return $result;
    }

    /**
     * Get plugins that depend on the specified plugin
     *
     * @param string $slug Plugin slug identifier
     * @return array Plugins that depend on this one
     */
    public function getDependents(string $slug): array
    {
        $dependents = [];
        $allPlugins = $this->getAll();

        foreach ($allPlugins as $plugin) {
            $manifest = [];
            if (!empty($plugin['manifest'])) {
                try {
                    $manifest = json_decode($plugin['manifest'], true) ?? [];
                } catch (\Exception $e) {
                    continue;
                }
            }

            if (!empty($manifest['requires']['plugins'])) {
                foreach ($manifest['requires']['plugins'] as $requiredPlugin) {
                    if (($requiredPlugin['slug'] ?? null) === $slug) {
                        $dependents[] = $plugin;
                        break;
                    }
                }
            }
        }

        return $dependents;
    }

    /**
     * Check if a version satisfies a version constraint
     *
     * Supports simple version constraints like:
     * - "1.0.0" (exact version)
     * - ">=1.0.0" (greater than or equal)
     * - ">1.0.0" (greater than)
     * - "<=1.0.0" (less than or equal)
     * - "<1.0.0" (less than)
     *
     * @param string $installedVersion Installed version
     * @param string $constraint Version constraint
     * @return bool True if compatible
     */
    private function isVersionCompatible(string $installedVersion, string $constraint): bool
    {
        $constraint = trim($constraint);

        // No constraint
        if ($constraint === '*') {
            return true;
        }

        // Parse operator and version
        if (preg_match('/^([><=]+)(.+)$/', $constraint, $matches)) {
            $operator = $matches[1];
            $requiredVersion = trim($matches[2]);
        } else {
            // No operator, exact match
            return $installedVersion === $constraint;
        }

        $cmp = version_compare($installedVersion, $requiredVersion);

        return match ($operator) {
            '=' => $cmp === 0,
            '==' => $cmp === 0,
            '!=' => $cmp !== 0,
            '>' => $cmp === 1,
            '>=' => $cmp === 1 || $cmp === 0,
            '<' => $cmp === -1,
            '<=' => $cmp === -1 || $cmp === 0,
            default => false
        };
    }

    /**
     * Check plugin conflicts
     *
     * Returns array with:
     * - 'has_conflicts' (bool): Whether conflicts exist
     * - 'conflicts' (array): List of conflicting plugins that are enabled
     *
     * @param string $slug Plugin slug identifier
     * @return array Conflict check results
     */
    private function checkPluginConflicts(string $slug): array
    {
        $result = [
            'has_conflicts' => false,
            'conflicts' => []
        ];

        try {
            $plugin = $this->getBySlug($slug);

            if (!$plugin) {
                return $result;
            }

            // Parse manifest if available
            $manifest = [];
            if (!empty($plugin['manifest'])) {
                try {
                    $manifest = json_decode($plugin['manifest'], true) ?? [];
                } catch (\Exception $e) {
                    Logger::warning('Failed to parse plugin manifest for conflict check', [
                        'slug' => $slug,
                        'error' => $e->getMessage()
                    ]);
                    return $result;
                }
            }

            // Check conflicts_with field
            if (!empty($manifest['conflicts_with'])) {
                foreach ($manifest['conflicts_with'] as $conflictSlug) {
                    $conflictPlugin = $this->getBySlug($conflictSlug);

                    // Check if conflicting plugin is enabled
                    if ($conflictPlugin && !empty($conflictPlugin['enabled']) && $conflictPlugin['enabled'] == 1) {
                        $result['has_conflicts'] = true;
                        $result['conflicts'][] = [
                            'slug' => $conflictSlug,
                            'name' => $conflictPlugin['name'] ?? $conflictSlug
                        ];
                    }
                }
            }

            if ($result['has_conflicts']) {
                Logger::system('Plugin conflicts detected', [
                    'slug' => $slug,
                    'conflicts' => array_column($result['conflicts'], 'slug')
                ]);
            }

        } catch (\Exception $e) {
            Logger::error('Conflict check failed', [
                'slug' => $slug,
                'error' => $e->getMessage()
            ]);
        }

        return $result;
    }

    /**
     * Clear plugin cache
     *
     * @return void
     */
    private function clearCache(): void
    {
        $this->pluginCache = [];
        $this->cacheTime = 0;
    }

    /**
     * Get plugin count
     *
     * @return int Total number of plugins
     */
    public function getCount(): int
    {
        $allPlugins = $this->getAll();
        return count($allPlugins);
    }

    /**
     * Get enabled plugin count
     *
     * @return int Number of enabled plugins
     */
    public function getEnabledCount(): int
    {
        $enabledPlugins = $this->getEnabled();
        return count($enabledPlugins);
    }

    /**
     * Check if plugin is enabled
     *
     * @param string $slug Plugin slug identifier
     * @return bool True if plugin is enabled
     */
    public function isEnabled(string $slug): bool
    {
        $plugin = $this->getBySlug($slug);
        return $plugin !== null && (!empty($plugin['enabled']) && $plugin['enabled'] == 1);
    }

    /**
     * Get plugins by multiple slugs
     *
     * @param array $slugs Array of plugin slugs
     * @return array Plugins matching the slugs
     */
    public function getBySlugList(array $slugs): array
    {
        $plugins = [];

        foreach ($slugs as $slug) {
            $plugin = $this->getBySlug($slug);
            if ($plugin) {
                $plugins[] = $plugin;
            }
        }

        return $plugins;
    }
}
