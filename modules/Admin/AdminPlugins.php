<?php

/**
 * ISER - Admin Plugins Controller
 *
 * Manages plugin installation, activation, deactivation, and discovery.
 * Handles plugin uploads, validation, and lifecycle management.
 *
 * @package    ISER\Admin
 * @category   Modules
 * @author     ISER Development Team
 * @copyright  2024 ISER
 * @license    Proprietary
 * @version    1.0.0
 * @since      Phase 6
 */

namespace ISER\Admin;

use ISER\Core\Database\Database;
use ISER\Core\Http\Response;
use ISER\Core\Render\MustacheRenderer;
use ISER\Core\Utils\Logger;
use ISER\Plugin\PluginManager;
use ISER\Plugin\PluginLoader;
use ISER\Plugin\PluginInstaller;

/**
 * AdminPlugins Controller
 *
 * HTTP controller for admin plugin management endpoints.
 * Provides REST API and HTML views for plugin administration.
 */
class AdminPlugins
{
    /**
     * Database instance
     */
    private Database $db;

    /**
     * Plugin manager instance
     */
    private PluginManager $pluginManager;

    /**
     * Plugin loader instance
     */
    private PluginLoader $pluginLoader;

    /**
     * Plugin installer instance
     */
    private PluginInstaller $pluginInstaller;

    /**
     * Mustache renderer instance
     */
    private MustacheRenderer $renderer;

    /**
     * Base plugins directory
     */
    private string $pluginsDir;

    /**
     * Constructor
     *
     * @param Database $db Database instance
     * @param PluginManager $pluginManager Plugin manager instance
     * @param PluginLoader $pluginLoader Plugin loader instance
     * @param MustacheRenderer $renderer Mustache renderer instance
     * @param string $pluginsDir Base plugins directory (optional)
     */
    public function __construct(
        Database $db,
        PluginManager $pluginManager,
        PluginLoader $pluginLoader,
        MustacheRenderer $renderer,
        string $pluginsDir = ''
    ) {
        $this->db = $db;
        $this->pluginManager = $pluginManager;
        $this->pluginLoader = $pluginLoader;
        $this->renderer = $renderer;

        if (empty($pluginsDir)) {
            $pluginsDir = dirname(dirname(__DIR__)) . '/modules/plugins';
        }
        $this->pluginsDir = rtrim($pluginsDir, '/');

        $this->pluginInstaller = new PluginInstaller(
            $db,
            $pluginManager,
            $pluginLoader,
            $pluginsDir
        );
    }

    /**
     * List all plugins with optional filters
     *
     * GET /admin/plugins
     * GET /admin/plugins?type=auth&enabled=1
     *
     * @param array $filters Filter parameters (type, enabled, search)
     * @return Response JSON response with plugins list
     */
    public function index(array $filters = []): Response
    {
        try {
            $plugins = $this->pluginManager->getAll();

            // Filter by type
            if (!empty($filters['type'])) {
                $plugins = array_filter(
                    $plugins,
                    fn($p) => $p['type'] === $filters['type']
                );
            }

            // Filter by enabled status
            if (isset($filters['enabled'])) {
                $enabled = (bool)$filters['enabled'];
                $plugins = array_filter(
                    $plugins,
                    fn($p) => (bool)$p['enabled'] === $enabled
                );
            }

            // Search by name or description
            if (!empty($filters['search'])) {
                $search = strtolower($filters['search']);
                $plugins = array_filter(
                    $plugins,
                    fn($p) => stripos($p['name'], $search) !== false
                                || stripos($p['description'] ?? '', $search) !== false
                );
            }

            // Get statistics
            $stats = [
                'total' => $this->pluginManager->getCount(),
                'enabled' => $this->pluginManager->getEnabledCount(),
                'disabled' => $this->pluginManager->getCount() - $this->pluginManager->getEnabledCount(),
                'by_type' => $this->getPluginsByTypeCount()
            ];

            Logger::info('Plugins list retrieved', [
                'count' => count($plugins),
                'filters' => $filters
            ]);

            return Response::json([
                'success' => true,
                'data' => [
                    'plugins' => array_values($plugins),
                    'stats' => $stats,
                    'filters' => $filters
                ]
            ]);

        } catch (\Exception $e) {
            Logger::error('Failed to list plugins', [
                'error' => $e->getMessage()
            ]);

            return Response::json([
                'success' => false,
                'message' => 'Failed to retrieve plugins list',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Install plugin from uploaded ZIP file
     *
     * POST /admin/plugins/install
     * Expects: file (ZIP archive)
     *
     * @param string $zipPath Path to uploaded ZIP file
     * @return Response JSON response with installation result
     */
    public function install(string $zipPath): Response
    {
        try {
            // Validate file exists
            if (!file_exists($zipPath)) {
                return Response::json([
                    'success' => false,
                    'message' => 'Upload file not found'
                ], 400);
            }

            // Install plugin
            $result = $this->pluginInstaller->install($zipPath);

            if (!$result['success']) {
                Logger::warning('Plugin installation failed', [
                    'message' => $result['message']
                ]);

                return Response::json([
                    'success' => false,
                    'message' => $result['message']
                ], 400);
            }

            Logger::info('Plugin installed via API', [
                'slug' => $result['plugin']['slug'],
                'name' => $result['plugin']['name'],
                'version' => $result['plugin']['version']
            ]);

            return Response::json([
                'success' => true,
                'message' => $result['message'],
                'plugin' => $result['plugin']
            ], 201);

        } catch (\Exception $e) {
            Logger::error('Plugin installation error', [
                'error' => $e->getMessage()
            ]);

            return Response::json([
                'success' => false,
                'message' => 'Installation error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enable a plugin
     *
     * PUT /admin/plugins/{slug}/enable
     *
     * @param string $slug Plugin slug identifier
     * @return Response JSON response with result
     */
    public function enable(string $slug): Response
    {
        try {
            // Validate plugin exists
            $plugin = $this->pluginManager->getBySlug($slug);

            if (!$plugin) {
                return Response::json([
                    'success' => false,
                    'message' => 'Plugin not found: ' . $slug
                ], 404);
            }

            // Check if already enabled
            if ($plugin['enabled'] == 1) {
                return Response::json([
                    'success' => false,
                    'message' => 'Plugin is already enabled'
                ], 400);
            }

            // Enable the plugin
            $success = $this->pluginManager->enable($slug);

            if (!$success) {
                return Response::json([
                    'success' => false,
                    'message' => 'Failed to enable plugin. Check dependencies.'
                ], 400);
            }

            $updatedPlugin = $this->pluginManager->getBySlug($slug);

            Logger::info('Plugin enabled', [
                'slug' => $slug,
                'name' => $plugin['name']
            ]);

            return Response::json([
                'success' => true,
                'message' => 'Plugin enabled successfully',
                'plugin' => $updatedPlugin
            ]);

        } catch (\Exception $e) {
            Logger::error('Failed to enable plugin', [
                'slug' => $slug,
                'error' => $e->getMessage()
            ]);

            return Response::json([
                'success' => false,
                'message' => 'Error enabling plugin: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Disable a plugin
     *
     * PUT /admin/plugins/{slug}/disable
     *
     * @param string $slug Plugin slug identifier
     * @return Response JSON response with result
     */
    public function disable(string $slug): Response
    {
        try {
            // Validate plugin exists
            $plugin = $this->pluginManager->getBySlug($slug);

            if (!$plugin) {
                return Response::json([
                    'success' => false,
                    'message' => 'Plugin not found: ' . $slug
                ], 404);
            }

            // Prevent disabling core plugins
            if (!empty($plugin['is_core']) && $plugin['is_core'] == 1) {
                return Response::json([
                    'success' => false,
                    'message' => 'Cannot disable core plugin: ' . $slug
                ], 403);
            }

            // Check if already disabled
            if ($plugin['enabled'] == 0) {
                return Response::json([
                    'success' => false,
                    'message' => 'Plugin is already disabled'
                ], 400);
            }

            // Check for dependents
            $dependents = $this->pluginManager->getDependents($slug);
            if (!empty($dependents)) {
                $dependentNames = array_column($dependents, 'name');
                return Response::json([
                    'success' => false,
                    'message' => 'Cannot disable plugin. Required by: ' . implode(', ', $dependentNames)
                ], 400);
            }

            // Disable the plugin
            $success = $this->pluginManager->disable($slug);

            if (!$success) {
                return Response::json([
                    'success' => false,
                    'message' => 'Failed to disable plugin'
                ], 400);
            }

            $updatedPlugin = $this->pluginManager->getBySlug($slug);

            Logger::info('Plugin disabled', [
                'slug' => $slug,
                'name' => $plugin['name']
            ]);

            return Response::json([
                'success' => true,
                'message' => 'Plugin disabled successfully',
                'plugin' => $updatedPlugin
            ]);

        } catch (\Exception $e) {
            Logger::error('Failed to disable plugin', [
                'slug' => $slug,
                'error' => $e->getMessage()
            ]);

            return Response::json([
                'success' => false,
                'message' => 'Error disabling plugin: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Uninstall a plugin
     *
     * DELETE /admin/plugins/{slug}
     *
     * @param string $slug Plugin slug identifier
     * @return Response JSON response with result
     */
    public function uninstall(string $slug): Response
    {
        try {
            // Validate plugin exists
            $plugin = $this->pluginManager->getBySlug($slug);

            if (!$plugin) {
                return Response::json([
                    'success' => false,
                    'message' => 'Plugin not found: ' . $slug
                ], 404);
            }

            // Prevent uninstalling core plugins
            if (!empty($plugin['is_core']) && $plugin['is_core'] == 1) {
                return Response::json([
                    'success' => false,
                    'message' => 'Cannot uninstall core plugin: ' . $slug
                ], 403);
            }

            // Uninstall the plugin
            $success = $this->pluginInstaller->uninstall($slug);

            if (!$success) {
                return Response::json([
                    'success' => false,
                    'message' => 'Failed to uninstall plugin. Check dependencies or permissions.'
                ], 400);
            }

            Logger::info('Plugin uninstalled', [
                'slug' => $slug,
                'name' => $plugin['name']
            ]);

            return Response::json([
                'success' => true,
                'message' => 'Plugin uninstalled successfully'
            ]);

        } catch (\Exception $e) {
            Logger::error('Failed to uninstall plugin', [
                'slug' => $slug,
                'error' => $e->getMessage()
            ]);

            return Response::json([
                'success' => false,
                'message' => 'Error uninstalling plugin: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Discover available plugins in filesystem
     *
     * POST /admin/plugins/discover
     *
     * Scans the plugins directory for new plugins that aren't in the database
     *
     * @return Response JSON response with discovered plugins
     */
    public function discover(): Response
    {
        try {
            // Discover plugins in filesystem
            $discovered = $this->pluginLoader->discoverPlugins();

            // Get already installed plugins
            $installed = $this->pluginManager->getAll();
            $installedSlugs = array_column($installed, 'slug');

            // Filter to only show new plugins
            $newPlugins = array_filter(
                $discovered,
                fn($p) => !in_array($p['slug'], $installedSlugs)
            );

            Logger::info('Plugin discovery completed', [
                'discovered' => count($discovered),
                'new' => count($newPlugins),
                'already_installed' => count($installed)
            ]);

            return Response::json([
                'success' => true,
                'data' => [
                    'discovered' => array_values($discovered),
                    'new' => array_values($newPlugins),
                    'already_installed' => count($installed),
                    'total_discovered' => count($discovered)
                ]
            ]);

        } catch (\Exception $e) {
            Logger::error('Plugin discovery failed', [
                'error' => $e->getMessage()
            ]);

            return Response::json([
                'success' => false,
                'message' => 'Discovery error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get plugin details
     *
     * GET /admin/plugins/{slug}
     *
     * @param string $slug Plugin slug identifier
     * @return Response JSON response with plugin details
     */
    public function show(string $slug): Response
    {
        try {
            $plugin = $this->pluginManager->getBySlug($slug);

            if (!$plugin) {
                return Response::json([
                    'success' => false,
                    'message' => 'Plugin not found: ' . $slug
                ], 404);
            }

            // Get dependencies
            $manifest = [];
            if (!empty($plugin['manifest'])) {
                try {
                    $manifest = json_decode($plugin['manifest'], true) ?? [];
                } catch (\Exception $e) {
                    // Invalid JSON manifest
                }
            }

            // Get dependents
            $dependents = $this->pluginManager->getDependents($slug);

            return Response::json([
                'success' => true,
                'plugin' => $plugin,
                'manifest' => $manifest,
                'dependents' => $dependents
            ]);

        } catch (\Exception $e) {
            Logger::error('Failed to get plugin details', [
                'slug' => $slug,
                'error' => $e->getMessage()
            ]);

            return Response::json([
                'success' => false,
                'message' => 'Error retrieving plugin details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get plugins grouped by type with count
     *
     * @return array Plugins count by type
     */
    private function getPluginsByTypeCount(): array
    {
        $types = ['tool', 'auth', 'theme', 'report', 'module', 'integration'];
        $counts = [];

        foreach ($types as $type) {
            $plugins = $this->pluginManager->getByType($type);
            $counts[$type] = count($plugins);
        }

        return $counts;
    }
}
