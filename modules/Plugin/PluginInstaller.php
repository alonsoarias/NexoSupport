<?php

/**
 * ISER - Plugin Installer
 *
 * Handles plugin installation from ZIP files, extraction, validation,
 * and database registration. Manages the complete plugin lifecycle.
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
use ISER\Core\Database\SchemaInstaller;
use ISER\Core\Plugin\DependencyResolver;
use ISER\Core\Utils\Logger;
use ZipArchive;

/**
 * PluginInstaller Class
 *
 * Manages plugin installation from ZIP files including:
 * - ZIP extraction and validation
 * - plugin.json manifest validation
 * - Dependency checking
 * - Database registration
 * - File extraction to proper locations
 */
class PluginInstaller
{
    /**
     * Database instance
     */
    private Database $db;

    /**
     * PluginManager instance
     */
    private PluginManager $pluginManager;

    /**
     * PluginLoader instance
     */
    private PluginLoader $pluginLoader;

    /**
     * DependencyResolver instance
     */
    private DependencyResolver $dependencyResolver;

    /**
     * Base plugins directory
     */
    private string $pluginsDir;

    /**
     * Temporary extraction directory
     */
    private string $tempDir;

    /**
     * Valid plugin types
     */
    private const VALID_TYPES = [
        'tool',
        'auth',
        'theme',
        'report',
        'module',
        'integration'
    ];

    /**
     * Maximum plugin file size (100MB)
     */
    private const MAX_FILE_SIZE = 104857600;

    /**
     * Constructor
     *
     * @param Database $db Database instance
     * @param PluginManager $pluginManager Plugin manager instance
     * @param PluginLoader $pluginLoader Plugin loader instance
     * @param string $pluginsDir Base plugins directory (optional)
     */
    public function __construct(
        Database $db,
        PluginManager $pluginManager,
        PluginLoader $pluginLoader,
        string $pluginsDir = ''
    ) {
        $this->db = $db;
        $this->pluginManager = $pluginManager;
        $this->pluginLoader = $pluginLoader;
        $this->dependencyResolver = new DependencyResolver($db);

        // Set plugins directory
        if (empty($pluginsDir)) {
            $pluginsDir = dirname(dirname(__DIR__)) . '/modules/plugins';
        }
        $this->pluginsDir = rtrim($pluginsDir, '/');

        // Set temporary directory
        $this->tempDir = sys_get_temp_dir() . '/iser_plugins_' . uniqid();
    }

    /**
     * Install plugin from ZIP file
     *
     * Returns:
     * ```php
     * [
     *     'success' => bool,
     *     'message' => string,
     *     'plugin' => array|null  // Installed plugin data
     * ]
     * ```
     *
     * @param string $zipPath Path to ZIP file
     * @return array Installation result
     */
    public function install(string $zipPath): array
    {
        try {
            // Validate ZIP file
            if (!file_exists($zipPath)) {
                return [
                    'success' => false,
                    'message' => 'ZIP file not found',
                    'plugin' => null
                ];
            }

            if (filesize($zipPath) > self::MAX_FILE_SIZE) {
                return [
                    'success' => false,
                    'message' => 'Plugin file exceeds maximum size (' . self::MAX_FILE_SIZE . ' bytes)',
                    'plugin' => null
                ];
            }

            if (!is_readable($zipPath)) {
                return [
                    'success' => false,
                    'message' => 'ZIP file is not readable',
                    'plugin' => null
                ];
            }

            // Extract ZIP
            $extracted = $this->extractZip($zipPath);
            if (!$extracted['success']) {
                return [
                    'success' => false,
                    'message' => $extracted['message'],
                    'plugin' => null
                ];
            }

            $extractedPath = $extracted['path'];

            // Load and validate manifest
            $manifest = $this->loadManifest($extractedPath);
            if (!$manifest) {
                $this->cleanupTempDirectory($extractedPath);
                return [
                    'success' => false,
                    'message' => 'Invalid or missing plugin.json manifest',
                    'plugin' => null
                ];
            }

            // Validate manifest structure
            $validation = $this->validateManifest($manifest);
            if (!$validation['valid']) {
                $this->cleanupTempDirectory($extractedPath);
                return [
                    'success' => false,
                    'message' => 'Manifest validation failed: ' . $validation['message'],
                    'plugin' => null
                ];
            }

            // Check if plugin already exists
            $existingPlugin = $this->pluginManager->getBySlug($manifest['slug']);
            if ($existingPlugin) {
                $this->cleanupTempDirectory($extractedPath);
                return [
                    'success' => false,
                    'message' => 'Plugin already installed: ' . $manifest['slug'],
                    'plugin' => null
                ];
            }

            // Validate plugin structure
            if (!$this->pluginLoader->validatePluginStructure($extractedPath)) {
                $this->cleanupTempDirectory($extractedPath);
                return [
                    'success' => false,
                    'message' => 'Invalid plugin structure - missing required files',
                    'plugin' => null
                ];
            }

            // Check for conflicts
            $conflictCheck = $this->checkConflicts($manifest);
            if ($conflictCheck['has_conflicts']) {
                $this->cleanupTempDirectory($extractedPath);
                $conflictNames = array_column($conflictCheck['conflicts'], 'name');
                $conflictList = implode(', ', $conflictNames);
                return [
                    'success' => false,
                    'message' => 'Plugin conflicts with enabled plugins: ' . $conflictList,
                    'plugin' => null
                ];
            }

            // Check dependencies
            $depCheck = $this->checkDependencies($manifest);
            if (!$depCheck['satisfied']) {
                $this->cleanupTempDirectory($extractedPath);
                $missingDeps = implode(', ', $depCheck['missing']);
                return [
                    'success' => false,
                    'message' => 'Missing dependencies: ' . $missingDeps,
                    'plugin' => null
                ];
            }

            // Move plugin to proper location
            $targetPath = $this->pluginsDir . '/' . $manifest['type'] . '/' . $manifest['slug'];

            // Create target directory structure
            if (!$this->createPluginDirectory($targetPath)) {
                $this->cleanupTempDirectory($extractedPath);
                return [
                    'success' => false,
                    'message' => 'Failed to create plugin directory: ' . $targetPath,
                    'plugin' => null
                ];
            }

            // Copy plugin files
            if (!$this->copyPluginFiles($extractedPath, $targetPath)) {
                $this->cleanupTempDirectory($extractedPath);
                $this->removePluginDirectory($targetPath);
                return [
                    'success' => false,
                    'message' => 'Failed to copy plugin files',
                    'plugin' => null
                ];
            }

            // Install plugin database schema if install.xml exists
            $schemaResult = $this->installPluginSchema($targetPath, $manifest['slug']);
            if (!$schemaResult['success']) {
                $this->removePluginDirectory($targetPath);
                $this->cleanupTempDirectory($extractedPath);
                return [
                    'success' => false,
                    'message' => 'Failed to install plugin database schema: ' . $schemaResult['message'],
                    'plugin' => null
                ];
            }

            // Register in database
            $registered = $this->registerPluginInDatabase($manifest, $targetPath);
            if (!$registered) {
                $this->removePluginDirectory($targetPath);
                $this->cleanupTempDirectory($extractedPath);
                return [
                    'success' => false,
                    'message' => 'Failed to register plugin in database',
                    'plugin' => null
                ];
            }

            // Get registered plugin data
            $pluginData = $this->pluginManager->getBySlug($manifest['slug']);

            // Cleanup temporary files
            $this->cleanupTempDirectory($extractedPath);

            Logger::info('Plugin installed successfully', [
                'slug' => $manifest['slug'],
                'name' => $manifest['name'],
                'version' => $manifest['version'],
                'type' => $manifest['type']
            ]);

            return [
                'success' => true,
                'message' => 'Plugin installed successfully: ' . $manifest['name'],
                'plugin' => $pluginData
            ];

        } catch (\Exception $e) {
            Logger::error('Plugin installation failed', [
                'zipPath' => $zipPath,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Installation error: ' . $e->getMessage(),
                'plugin' => null
            ];
        }
    }

    /**
     * Install plugin with automatic dependency resolution
     *
     * This method automatically installs all required dependencies
     * before installing the target plugin. Dependencies are installed
     * in the correct order based on dependency graph analysis.
     *
     * @param string $zipPath Path to plugin ZIP file
     * @param bool $autoInstallDeps Whether to automatically install dependencies (default: true)
     * @param array $availablePlugins List of available plugins for dependency resolution (optional)
     * @return array Installation result with detailed dependency information
     */
    public function installWithDependencies(
        string $zipPath,
        bool $autoInstallDeps = true,
        array $availablePlugins = []
    ): array {
        $result = [
            'success' => true,
            'message' => '',
            'plugin' => null,
            'dependencies_installed' => [],
            'dependencies_skipped' => [],
            'errors' => [],
            'warnings' => []
        ];

        try {
            // First, extract and validate the target plugin
            // We need to get its manifest to resolve dependencies
            $targetManifest = $this->extractManifestFromZip($zipPath);

            if (!$targetManifest) {
                return [
                    'success' => false,
                    'message' => 'Failed to extract plugin manifest from ZIP',
                    'plugin' => null,
                    'dependencies_installed' => [],
                    'dependencies_skipped' => [],
                    'errors' => ['Invalid ZIP or missing plugin.json'],
                    'warnings' => []
                ];
            }

            $targetSlug = $targetManifest['slug'] ?? 'unknown';

            Logger::info('Starting plugin installation with dependencies', [
                'target_plugin' => $targetSlug,
                'auto_install_deps' => $autoInstallDeps
            ]);

            // Resolve dependencies
            $depResolution = $this->dependencyResolver->resolveDependencies(
                $targetSlug,
                array_merge($availablePlugins, [$targetManifest])
            );

            if (!$depResolution['success']) {
                return [
                    'success' => false,
                    'message' => 'Dependency resolution failed: ' . implode(', ', $depResolution['errors']),
                    'plugin' => null,
                    'dependencies_installed' => [],
                    'dependencies_skipped' => [],
                    'errors' => $depResolution['errors'],
                    'warnings' => $depResolution['warnings']
                ];
            }

            $result['warnings'] = $depResolution['warnings'];

            // Get list of dependencies to install (excluding target plugin itself)
            $depsToInstall = array_filter(
                $depResolution['dependencies'],
                fn($slug) => $slug !== $targetSlug
            );

            Logger::info('Dependencies resolved', [
                'target_plugin' => $targetSlug,
                'dependencies_count' => count($depsToInstall),
                'dependencies' => $depsToInstall
            ]);

            // Install dependencies if auto-install is enabled
            if ($autoInstallDeps && !empty($depsToInstall)) {
                foreach ($depsToInstall as $depSlug) {
                    // Check if dependency is already installed
                    $existingDep = $this->pluginManager->getBySlug($depSlug);
                    if ($existingDep) {
                        Logger::info('Dependency already installed, skipping', [
                            'dependency' => $depSlug
                        ]);
                        $result['dependencies_skipped'][] = $depSlug;
                        continue;
                    }

                    // Find dependency in available plugins
                    $depZipPath = $this->findPluginZip($depSlug, $availablePlugins);

                    if (!$depZipPath) {
                        $error = "Dependency not available for installation: {$depSlug}";
                        $result['errors'][] = $error;
                        $result['warnings'][] = "Cannot auto-install {$depSlug} - ZIP file not found. Please install manually.";
                        Logger::warning($error);
                        continue;
                    }

                    // Install dependency
                    Logger::info('Installing dependency', ['dependency' => $depSlug]);
                    $depInstall = $this->install($depZipPath);

                    if ($depInstall['success']) {
                        $result['dependencies_installed'][] = $depSlug;
                        Logger::info('Dependency installed successfully', [
                            'dependency' => $depSlug
                        ]);
                    } else {
                        $error = "Failed to install dependency {$depSlug}: {$depInstall['message']}";
                        $result['errors'][] = $error;
                        $result['success'] = false;
                        Logger::error($error);

                        // Stop installation if a required dependency fails
                        return $result;
                    }
                }
            } elseif (!$autoInstallDeps && !empty($depsToInstall)) {
                // Auto-install disabled but dependencies required
                $result['warnings'][] = 'Auto-install disabled. The following dependencies must be installed manually: ' .
                    implode(', ', $depsToInstall);
            }

            // Install the target plugin
            Logger::info('Installing target plugin', ['plugin' => $targetSlug]);
            $targetInstall = $this->install($zipPath);

            if ($targetInstall['success']) {
                $result['success'] = true;
                $result['message'] = "Plugin installed successfully";
                if (!empty($result['dependencies_installed'])) {
                    $result['message'] .= " (with " . count($result['dependencies_installed']) . " dependencies)";
                }
                $result['plugin'] = $targetInstall['plugin'];

                Logger::info('Plugin installation with dependencies completed', [
                    'plugin' => $targetSlug,
                    'dependencies_installed' => count($result['dependencies_installed']),
                    'dependencies_skipped' => count($result['dependencies_skipped'])
                ]);
            } else {
                $result['success'] = false;
                $result['message'] = "Failed to install plugin: {$targetInstall['message']}";
                $result['errors'][] = $targetInstall['message'];

                Logger::error('Failed to install target plugin', [
                    'plugin' => $targetSlug,
                    'error' => $targetInstall['message']
                ]);
            }

        } catch (\Exception $e) {
            $result['success'] = false;
            $result['message'] = 'Installation error: ' . $e->getMessage();
            $result['errors'][] = $e->getMessage();

            Logger::error('Plugin installation with dependencies failed', [
                'zipPath' => $zipPath,
                'error' => $e->getMessage()
            ]);
        }

        return $result;
    }

    /**
     * Extract manifest from ZIP file without installing
     *
     * @param string $zipPath Path to ZIP file
     * @return array|null Manifest data or null on failure
     */
    private function extractManifestFromZip(string $zipPath): ?array
    {
        try {
            if (!file_exists($zipPath) || !is_readable($zipPath)) {
                return null;
            }

            $zip = new ZipArchive();
            if ($zip->open($zipPath) !== true) {
                return null;
            }

            // Look for plugin.json in ZIP
            $manifestContent = null;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                if (basename($filename) === 'plugin.json') {
                    $manifestContent = $zip->getFromIndex($i);
                    break;
                }
            }

            $zip->close();

            if (!$manifestContent) {
                return null;
            }

            $manifest = json_decode($manifestContent, true);
            return $manifest ?: null;

        } catch (\Exception $e) {
            Logger::error('Failed to extract manifest from ZIP', [
                'zipPath' => $zipPath,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Find plugin ZIP file by slug
     *
     * @param string $slug Plugin slug
     * @param array $availablePlugins Available plugins with ZIP paths
     * @return string|null ZIP path or null if not found
     */
    private function findPluginZip(string $slug, array $availablePlugins): ?string
    {
        foreach ($availablePlugins as $plugin) {
            if (isset($plugin['slug']) && $plugin['slug'] === $slug && isset($plugin['zip_path'])) {
                return $plugin['zip_path'];
            }
        }

        return null;
    }

    /**
     * Uninstall plugin
     *
     * @param string $slug Plugin slug identifier
     * @return bool True on success
     */
    public function uninstall(string $slug): bool
    {
        try {
            $plugin = $this->pluginManager->getBySlug($slug);

            if (!$plugin) {
                Logger::warning('Plugin not found for uninstallation', ['slug' => $slug]);
                return false;
            }

            // Don't allow uninstalling core plugins
            if (!empty($plugin['is_core']) && $plugin['is_core'] == 1) {
                Logger::warning('Cannot uninstall core plugin', ['slug' => $slug]);
                return false;
            }

            // Check if other plugins depend on this one
            $dependents = $this->pluginManager->getDependents($slug);
            if (!empty($dependents)) {
                Logger::warning('Cannot uninstall plugin with dependents', [
                    'slug' => $slug,
                    'dependents' => array_column($dependents, 'slug')
                ]);
                return false;
            }

            // Uninstall plugin database schema (drop plugin tables)
            $this->uninstallPluginSchema($slug);

            // Remove plugin files from filesystem
            $pluginPath = $this->pluginLoader->getPluginPath($plugin['type'], $slug);
            if (is_dir($pluginPath)) {
                if (!$this->removePluginDirectory($pluginPath)) {
                    Logger::warning('Failed to remove plugin files', ['path' => $pluginPath]);
                }
            }

            // Use PluginManager to delete from database
            $result = $this->pluginManager->uninstall($slug);

            if ($result) {
                Logger::info('Plugin uninstalled successfully', [
                    'slug' => $slug,
                    'name' => $plugin['name']
                ]);
            }

            return $result;

        } catch (\Exception $e) {
            Logger::error('Failed to uninstall plugin', [
                'slug' => $slug,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Update an existing plugin
     *
     * Updates a plugin from a new ZIP file, preserving configuration and data.
     * Validates version compatibility and handles schema updates.
     *
     * @param string $slug Plugin slug to update
     * @param string $zipPath Path to new plugin ZIP file
     * @return array Result with success status, message, and plugin data
     */
    public function update(string $slug, string $zipPath): array
    {
        try {
            // Get existing plugin
            $existingPlugin = $this->pluginManager->getBySlug($slug);

            if (!$existingPlugin) {
                return [
                    'success' => false,
                    'message' => 'Plugin not found: ' . $slug,
                    'plugin' => null
                ];
            }

            // Validate ZIP file
            if (!file_exists($zipPath)) {
                return [
                    'success' => false,
                    'message' => 'ZIP file not found: ' . $zipPath,
                    'plugin' => null
                ];
            }

            // Extract ZIP
            $extractResult = $this->extractZip($zipPath);
            if (!$extractResult['success']) {
                return $extractResult;
            }

            $extractedPath = $extractResult['path'];

            // Find plugin directory
            $pluginDir = $this->findPluginDirectory($extractedPath);
            if (!$pluginDir) {
                $this->cleanupTempDirectory($extractedPath);
                return [
                    'success' => false,
                    'message' => 'No valid plugin directory found in ZIP',
                    'plugin' => null
                ];
            }

            // Load and validate new manifest
            $manifest = $this->loadManifest($pluginDir);
            if (!$manifest) {
                $this->cleanupTempDirectory($extractedPath);
                return [
                    'success' => false,
                    'message' => 'Invalid or missing plugin.json',
                    'plugin' => null
                ];
            }

            // Validate slug matches
            if ($manifest['slug'] !== $slug) {
                $this->cleanupTempDirectory($extractedPath);
                return [
                    'success' => false,
                    'message' => 'Plugin slug mismatch. Expected: ' . $slug . ', Got: ' . $manifest['slug'],
                    'plugin' => null
                ];
            }

            // Validate version is newer
            if (version_compare($manifest['version'], $existingPlugin['version'], '<=')) {
                $this->cleanupTempDirectory($extractedPath);
                return [
                    'success' => false,
                    'message' => 'Update version must be newer than current version (' . $existingPlugin['version'] . ')',
                    'plugin' => null
                ];
            }

            // Temporarily disable plugin if enabled
            $wasEnabled = (bool)$existingPlugin['enabled'];
            if ($wasEnabled) {
                $this->pluginManager->disable($slug);
            }

            // Get plugin path
            $targetPath = $this->pluginsDir . '/' . $manifest['type'] . '/' . $manifest['slug'];

            // Backup current plugin (optional - rename to .backup)
            $backupPath = $targetPath . '.backup';
            if (is_dir($targetPath)) {
                if (is_dir($backupPath)) {
                    $this->removePluginDirectory($backupPath);
                }
                if (!rename($targetPath, $backupPath)) {
                    $this->cleanupTempDirectory($extractedPath);
                    if ($wasEnabled) {
                        $this->pluginManager->enable($slug);
                    }
                    return [
                        'success' => false,
                        'message' => 'Failed to backup current plugin version',
                        'plugin' => null
                    ];
                }
            }

            // Copy new plugin files
            if (!$this->copyPluginFiles($pluginDir, $targetPath)) {
                // Restore backup on failure
                if (is_dir($backupPath)) {
                    rename($backupPath, $targetPath);
                }
                $this->cleanupTempDirectory($extractedPath);
                if ($wasEnabled) {
                    $this->pluginManager->enable($slug);
                }
                return [
                    'success' => false,
                    'message' => 'Failed to copy updated plugin files',
                    'plugin' => null
                ];
            }

            // Update plugin database schema if install.xml exists
            $schemaResult = $this->installPluginSchema($targetPath, $manifest['slug']);
            if (!$schemaResult['success']) {
                // Restore backup on schema failure
                $this->removePluginDirectory($targetPath);
                if (is_dir($backupPath)) {
                    rename($backupPath, $targetPath);
                }
                $this->cleanupTempDirectory($extractedPath);
                if ($wasEnabled) {
                    $this->pluginManager->enable($slug);
                }
                return [
                    'success' => false,
                    'message' => 'Failed to update plugin database schema: ' . $schemaResult['message'],
                    'plugin' => null
                ];
            }

            // Update plugin metadata in database
            $updateResult = $this->pluginManager->updateVersion($slug, $manifest['version'], json_encode($manifest));
            if (!$updateResult) {
                // Restore backup on database update failure
                $this->removePluginDirectory($targetPath);
                if (is_dir($backupPath)) {
                    rename($backupPath, $targetPath);
                }
                $this->cleanupTempDirectory($extractedPath);
                if ($wasEnabled) {
                    $this->pluginManager->enable($slug);
                }
                return [
                    'success' => false,
                    'message' => 'Failed to update plugin metadata in database',
                    'plugin' => null
                ];
            }

            // Re-enable plugin if it was enabled
            if ($wasEnabled) {
                $this->pluginManager->enable($slug);
            }

            // Remove backup after successful update
            if (is_dir($backupPath)) {
                $this->removePluginDirectory($backupPath);
            }

            // Cleanup temporary files
            $this->cleanupTempDirectory($extractedPath);

            // Get updated plugin data
            $updatedPlugin = $this->pluginManager->getBySlug($slug);

            Logger::info('Plugin updated successfully', [
                'slug' => $slug,
                'old_version' => $existingPlugin['version'],
                'new_version' => $manifest['version']
            ]);

            return [
                'success' => true,
                'message' => 'Plugin updated successfully from ' . $existingPlugin['version'] . ' to ' . $manifest['version'],
                'plugin' => $updatedPlugin
            ];

        } catch (\Exception $e) {
            Logger::error('Plugin update failed', [
                'slug' => $slug,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Update error: ' . $e->getMessage(),
                'plugin' => null
            ];
        }
    }

    /**
     * Extract ZIP file to temporary directory
     *
     * @param string $zipPath Path to ZIP file
     * @return array Result array with 'success' and 'path' keys
     */
    private function extractZip(string $zipPath): array
    {
        try {
            $zip = new ZipArchive();
            $openResult = $zip->open($zipPath);

            if ($openResult !== true) {
                return [
                    'success' => false,
                    'message' => 'Failed to open ZIP file: ' . $this->getZipErrorMessage($openResult),
                    'path' => null
                ];
            }

            // Create extraction directory
            if (!@mkdir($this->tempDir, 0755, true) && !is_dir($this->tempDir)) {
                $zip->close();
                return [
                    'success' => false,
                    'message' => 'Failed to create temporary directory',
                    'path' => null
                ];
            }

            // Extract all files
            if (!$zip->extractTo($this->tempDir)) {
                $zip->close();
                return [
                    'success' => false,
                    'message' => 'Failed to extract ZIP file contents',
                    'path' => null
                ];
            }

            $zip->close();

            // Find the actual plugin directory (it might be nested)
            $pluginPath = $this->findPluginDirectory($this->tempDir);

            if (!$pluginPath) {
                return [
                    'success' => false,
                    'message' => 'No valid plugin directory found in ZIP',
                    'path' => null
                ];
            }

            return [
                'success' => true,
                'message' => '',
                'path' => $pluginPath
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'ZIP extraction error: ' . $e->getMessage(),
                'path' => null
            ];
        }
    }

    /**
     * Find the actual plugin directory in extracted ZIP
     *
     * Handles cases where the plugin might be in a subdirectory
     *
     * @param string $searchPath Path to search
     * @return string|null Path to plugin directory or null
     */
    private function findPluginDirectory(string $searchPath): ?string
    {
        // Check if manifest exists in current directory
        if (file_exists($searchPath . '/plugin.json')) {
            return $searchPath;
        }

        // Check subdirectories (usually just one level deep)
        $items = @scandir($searchPath);

        if ($items === false) {
            return null;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $itemPath = $searchPath . '/' . $item;

            if (is_dir($itemPath) && file_exists($itemPath . '/plugin.json')) {
                return $itemPath;
            }
        }

        return null;
    }

    /**
     * Load plugin manifest from plugin.json
     *
     * @param string $pluginPath Path to plugin directory
     * @return array|null Manifest data or null on failure
     */
    private function loadManifest(string $pluginPath): ?array
    {
        try {
            $manifestPath = $pluginPath . '/plugin.json';

            if (!file_exists($manifestPath) || !is_readable($manifestPath)) {
                return null;
            }

            $content = file_get_contents($manifestPath);

            if ($content === false) {
                return null;
            }

            $manifest = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return null;
            }

            return $manifest;

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Validate plugin manifest structure
     *
     * @param array $manifest Plugin manifest
     * @return array Validation result with 'valid' and 'message' keys
     */
    private function validateManifest(array $manifest): array
    {
        // Required fields
        $required = ['name', 'slug', 'type', 'version', 'author', 'description'];

        foreach ($required as $field) {
            if (empty($manifest[$field])) {
                return [
                    'valid' => false,
                    'message' => "Missing required field: $field"
                ];
            }
        }

        // Validate slug format (alphanumeric and hyphens only)
        if (!preg_match('/^[a-z0-9-]+$/', $manifest['slug'])) {
            return [
                'valid' => false,
                'message' => 'Invalid slug format. Use only lowercase letters, numbers, and hyphens.'
            ];
        }

        // Validate type
        if (!in_array($manifest['type'], self::VALID_TYPES)) {
            $validTypes = implode(', ', self::VALID_TYPES);
            return [
                'valid' => false,
                'message' => "Invalid type. Valid types are: $validTypes"
            ];
        }

        // Validate version format (semantic versioning)
        if (!preg_match('/^\d+\.\d+\.\d+/', $manifest['version'])) {
            return [
                'valid' => false,
                'message' => 'Invalid version format. Use semantic versioning (e.g., 1.0.0)'
            ];
        }

        return [
            'valid' => true,
            'message' => ''
        ];
    }

    /**
     * Check plugin dependencies
     *
     * @param array $manifest Plugin manifest
     * @return array Dependency check result
     */
    private function checkDependencies(array $manifest): array
    {
        $result = [
            'satisfied' => true,
            'missing' => [],
            'incompatible' => []
        ];

        try {
            // Check required plugins
            if (!empty($manifest['requires']['plugins'])) {
                foreach ($manifest['requires']['plugins'] as $requiredPlugin) {
                    $requiredSlug = $requiredPlugin['slug'] ?? null;
                    $requiredVersion = $requiredPlugin['version'] ?? '*';

                    if (!$requiredSlug) {
                        continue;
                    }

                    $depPlugin = $this->pluginManager->getBySlug($requiredSlug);

                    if (!$depPlugin || empty($depPlugin['enabled']) || $depPlugin['enabled'] != 1) {
                        $result['satisfied'] = false;
                        $result['missing'][] = $requiredSlug;
                        continue;
                    }

                    // Check version compatibility
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

        } catch (\Exception $e) {
            Logger::warning('Dependency check error', [
                'error' => $e->getMessage()
            ]);
        }

        return $result;
    }

    /**
     * Check for plugin conflicts
     *
     * Checks if the plugin conflicts with any currently enabled plugins.
     *
     * @param array $manifest Plugin manifest
     * @return array Conflict check results [
     *   'has_conflicts' => bool,
     *   'conflicts' => array
     * ]
     */
    private function checkConflicts(array $manifest): array
    {
        $result = [
            'has_conflicts' => false,
            'conflicts' => []
        ];

        try {
            // Check conflicts_with field
            if (!empty($manifest['conflicts_with'])) {
                foreach ($manifest['conflicts_with'] as $conflictSlug) {
                    $conflictPlugin = $this->pluginManager->getBySlug($conflictSlug);

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

        } catch (\Exception $e) {
            Logger::warning('Conflict check error', [
                'error' => $e->getMessage()
            ]);
        }

        return $result;
    }

    /**
     * Check if a version satisfies a constraint
     *
     * @param string $installedVersion Installed version
     * @param string $constraint Version constraint
     * @return bool True if compatible
     */
    private function isVersionCompatible(string $installedVersion, string $constraint): bool
    {
        $constraint = trim($constraint);

        if ($constraint === '*') {
            return true;
        }

        if (preg_match('/^([><=]+)(.+)$/', $constraint, $matches)) {
            $operator = $matches[1];
            $requiredVersion = trim($matches[2]);
        } else {
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
     * Create plugin directory structure
     *
     * @param string $path Path to create
     * @return bool True on success
     */
    private function createPluginDirectory(string $path): bool
    {
        try {
            if (is_dir($path)) {
                return true;
            }

            return @mkdir($path, 0755, true);

        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Copy plugin files from source to target
     *
     * @param string $source Source path
     * @param string $target Target path
     * @return bool True on success
     */
    private function copyPluginFiles(string $source, string $target): bool
    {
        try {
            $source = rtrim($source, '/');
            $target = rtrim($target, '/');

            // Get all files and directories
            $items = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(
                    $source,
                    \RecursiveDirectoryIterator::SKIP_DOTS
                ),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($items as $item) {
                $relativePath = substr($item->getPathname(), strlen($source) + 1);
                $targetItem = $target . '/' . $relativePath;

                if ($item->isDir()) {
                    if (!is_dir($targetItem)) {
                        @mkdir($targetItem, 0755, true);
                    }
                } else {
                    $targetDir = dirname($targetItem);
                    if (!is_dir($targetDir)) {
                        @mkdir($targetDir, 0755, true);
                    }

                    if (!@copy($item->getPathname(), $targetItem)) {
                        return false;
                    }
                }
            }

            return true;

        } catch (\Exception $e) {
            Logger::error('Failed to copy plugin files', [
                'source' => $source,
                'target' => $target,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Register plugin in database
     *
     * @param array $manifest Plugin manifest
     * @param string $pluginPath Plugin path
     * @return bool True on success
     */
    private function registerPluginInDatabase(array $manifest, string $pluginPath): bool
    {
        try {
            $now = time();

            // Prepare plugin data
            $pluginData = [
                'slug' => $manifest['slug'],
                'type' => $manifest['type'],
                'name' => $manifest['name'],
                'version' => $manifest['version'],
                'description' => $manifest['description'] ?? '',
                'author' => $manifest['author'] ?? '',
                'author_url' => $manifest['author_url'] ?? '',
                'plugin_url' => $manifest['plugin_url'] ?? '',
                'enabled' => 1,
                'is_core' => 0,
                'priority' => $manifest['priority'] ?? 50,
                'manifest' => json_encode($manifest),
                'created_at' => $now,
                'updated_at' => $now
            ];

            $this->db->insert('plugins', $pluginData);

            return true;

        } catch (\Exception $e) {
            Logger::error('Failed to register plugin in database', [
                'slug' => $manifest['slug'],
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Remove plugin directory recursively
     *
     * @param string $path Directory path
     * @return bool True on success
     */
    private function removePluginDirectory(string $path): bool
    {
        try {
            if (!is_dir($path)) {
                return true;
            }

            $items = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(
                    $path,
                    \RecursiveDirectoryIterator::SKIP_DOTS
                ),
                \RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($items as $item) {
                if ($item->isDir()) {
                    @rmdir($item->getPathname());
                } else {
                    @unlink($item->getPathname());
                }
            }

            return @rmdir($path);

        } catch (\Exception $e) {
            Logger::warning('Failed to remove plugin directory', [
                'path' => $path,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Cleanup temporary directory
     *
     * @param string $path Temporary directory path
     * @return void
     */
    private function cleanupTempDirectory(string $path): void
    {
        if (is_dir($path)) {
            $this->removePluginDirectory($path);
        }
    }

    /**
     * Install plugin database schema from install.xml
     *
     * Checks if the plugin has an install.xml file and uses SchemaInstaller
     * to create the necessary database tables with plugin-specific prefix.
     *
     * @param string $pluginPath Path to plugin directory
     * @param string $slug Plugin slug for table prefix
     * @return array Result with success status and message
     */
    private function installPluginSchema(string $pluginPath, string $slug): array
    {
        $installXmlPath = $pluginPath . '/install.xml';

        // Check if install.xml exists
        if (!file_exists($installXmlPath)) {
            // No schema file - this is OK, not all plugins need database tables
            Logger::info('Plugin has no install.xml, skipping schema installation', [
                'slug' => $slug
            ]);

            return [
                'success' => true,
                'message' => 'No database schema required'
            ];
        }

        try {
            Logger::info('Installing plugin database schema', [
                'slug' => $slug,
                'xml_path' => $installXmlPath
            ]);

            // Get PDO connection from database
            $pdo = $this->db->getConnection();

            // Create table prefix for plugin (format: plugin_slugname_)
            $tablePrefix = 'plugin_' . str_replace('-', '_', $slug) . '_';

            // Initialize SchemaInstaller with plugin prefix
            $schemaInstaller = new SchemaInstaller($pdo, $tablePrefix, true); // silent mode

            // Install schema from XML
            $result = $schemaInstaller->installFromXML($installXmlPath);

            if (!$result) {
                Logger::error('Plugin schema installation failed', [
                    'slug' => $slug,
                    'errors' => $schemaInstaller->getErrors()
                ]);

                return [
                    'success' => false,
                    'message' => 'Schema installation failed: ' . implode(', ', $schemaInstaller->getErrors())
                ];
            }

            $createdTables = $schemaInstaller->getCreatedTables();
            Logger::info('Plugin schema installed successfully', [
                'slug' => $slug,
                'tables_created' => count($createdTables),
                'tables' => $createdTables
            ]);

            return [
                'success' => true,
                'message' => 'Schema installed successfully',
                'tables' => $createdTables
            ];

        } catch (\Exception $e) {
            Logger::error('Plugin schema installation exception', [
                'slug' => $slug,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Schema installation error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Uninstall plugin database schema
     *
     * Drops all tables created by the plugin (identified by prefix).
     * Called during plugin uninstallation.
     *
     * @param string $slug Plugin slug
     * @return bool True on success
     */
    private function uninstallPluginSchema(string $slug): bool
    {
        try {
            $tablePrefix = 'plugin_' . str_replace('-', '_', $slug) . '_';

            Logger::info('Uninstalling plugin database schema', [
                'slug' => $slug,
                'prefix' => $tablePrefix
            ]);

            $pdo = $this->db->getConnection();

            // Get all tables with plugin prefix
            $driver = $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);

            if ($driver === 'mysql') {
                $stmt = $pdo->query("SHOW TABLES LIKE '{$tablePrefix}%'");
                $tables = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            } elseif ($driver === 'pgsql') {
                $stmt = $pdo->query("
                    SELECT tablename FROM pg_tables
                    WHERE schemaname = 'public'
                    AND tablename LIKE '{$tablePrefix}%'
                ");
                $tables = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            } elseif ($driver === 'sqlite') {
                $stmt = $pdo->query("
                    SELECT name FROM sqlite_master
                    WHERE type='table'
                    AND name LIKE '{$tablePrefix}%'
                ");
                $tables = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            } else {
                Logger::warning('Unsupported database driver for schema cleanup', [
                    'driver' => $driver
                ]);
                return true; // Continue with uninstall even if we can't cleanup
            }

            // Drop each table
            foreach ($tables as $table) {
                try {
                    if ($driver === 'mysql') {
                        $pdo->exec("DROP TABLE IF EXISTS `{$table}`");
                    } else {
                        $pdo->exec("DROP TABLE IF EXISTS \"{$table}\"");
                    }

                    Logger::info('Dropped plugin table', [
                        'slug' => $slug,
                        'table' => $table
                    ]);
                } catch (\PDOException $e) {
                    Logger::warning('Failed to drop plugin table', [
                        'slug' => $slug,
                        'table' => $table,
                        'error' => $e->getMessage()
                    ]);
                    // Continue with other tables
                }
            }

            Logger::info('Plugin schema uninstalled', [
                'slug' => $slug,
                'tables_dropped' => count($tables)
            ]);

            return true;

        } catch (\Exception $e) {
            Logger::error('Plugin schema uninstallation failed', [
                'slug' => $slug,
                'error' => $e->getMessage()
            ]);

            // Don't fail uninstall if schema cleanup fails
            return true;
        }
    }

    /**
     * Get ZIP error message from error code
     *
     * @param int $errorCode ZIP error code
     * @return string Error message
     */
    private function getZipErrorMessage(int $errorCode): string
    {
        return match ($errorCode) {
            ZipArchive::ER_OK => 'No error',
            ZipArchive::ER_MULTIDISK => 'Multi-disk zip not supported',
            ZipArchive::ER_RENAME => 'Renaming temporary file failed',
            ZipArchive::ER_CLOSE => 'Closing zip archive failed',
            ZipArchive::ER_SEEK => 'Seek error',
            ZipArchive::ER_READ => 'Read error',
            ZipArchive::ER_WRITE => 'Write error',
            ZipArchive::ER_CRC => 'CRC error',
            ZipArchive::ER_ZIPCLOSED => 'Containing zip archive was closed',
            ZipArchive::ER_NOENT => 'No such file',
            ZipArchive::ER_EXISTS => 'File already exists',
            ZipArchive::ER_OPEN => 'Cannot open file',
            ZipArchive::ER_TMPOPEN => 'Failure to create temporary file',
            ZipArchive::ER_ZLIB => 'Zlib error',
            ZipArchive::ER_MEMORY => 'Malloc failure',
            ZipArchive::ER_CHANGED => 'Entry has been changed',
            ZipArchive::ER_COMPNOTSUPP => 'Compression method not supported',
            ZipArchive::ER_EOF => 'Premature EOF',
            ZipArchive::ER_INVAL => 'Invalid argument',
            ZipArchive::ER_NOZIP => 'Not a zip archive',
            ZipArchive::ER_INTERNAL => 'Internal error',
            ZipArchive::ER_INCONS => 'Zip archive inconsistent',
            ZipArchive::ER_REMOVE => 'Cannot remove file',
            ZipArchive::ER_DELETED => 'Entry has been deleted',
            default => 'Unknown error (' . $errorCode . ')'
        };
    }
}
