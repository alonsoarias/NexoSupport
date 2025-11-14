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
use ISER\Core\View\MustacheRenderer;
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
     * Plugin configurator instance
     */
    private \ISER\Plugin\PluginConfigurator $pluginConfigurator;

    /**
     * Form generator instance
     */
    private \ISER\Core\Plugin\ConfigFormGenerator $formGenerator;

    /**
     * Constructor
     *
     * @param Database $db Database instance
     * @param PluginManager|null $pluginManager Plugin manager instance (optional)
     * @param PluginLoader|null $pluginLoader Plugin loader instance (optional)
     * @param MustacheRenderer|null $renderer Mustache renderer instance (optional)
     * @param string $pluginsDir Base plugins directory (optional)
     */
    public function __construct(
        Database $db,
        ?PluginManager $pluginManager = null,
        ?PluginLoader $pluginLoader = null,
        ?MustacheRenderer $renderer = null,
        string $pluginsDir = ''
    ) {
        $this->db = $db;

        // Create dependencies if not provided
        $this->pluginManager = $pluginManager ?? new PluginManager($db);
        $this->pluginLoader = $pluginLoader ?? new PluginLoader($db, $pluginsDir);
        $this->renderer = $renderer ?? new MustacheRenderer();

        if (empty($pluginsDir)) {
            $pluginsDir = dirname(dirname(__DIR__)) . '/modules/plugins';
        }
        $this->pluginsDir = rtrim($pluginsDir, '/');

        $this->pluginInstaller = new PluginInstaller(
            $db,
            $this->pluginManager,
            $this->pluginLoader,
            $pluginsDir
        );

        $this->pluginConfigurator = new \ISER\Plugin\PluginConfigurator($db, $this->pluginManager);
        $this->formGenerator = new \ISER\Core\Plugin\ConfigFormGenerator();
    }

    /**
     * List all plugins with optional filters
     *
     * GET /admin/plugins
     * GET /admin/plugins?type=auth&enabled=1
     *
     * Supports both HTML (browser) and JSON (API) responses based on Accept header
     *
     * @param array $filters Filter parameters (type, enabled, search)
     * @return Response HTML view or JSON response with plugins list
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

            // Check if HTML is requested (browser) or JSON (API)
            if ($this->isHtmlRequested()) {
                // Prepare data for HTML view with type icons
                $pluginsForView = array_map(function($plugin) {
                    $plugin['type_icon'] = $this->getPluginTypeIcon($plugin['type']);
                    $plugin['type_' . $plugin['type']] = true;
                    return $plugin;
                }, array_values($plugins));

                return Response::html(
                    $this->renderer->render(
                        'admin/plugins/index',
                        [
                            'plugins' => $pluginsForView,
                            'plugins_count' => count($pluginsForView),
                            'stats' => $stats,
                            'filters' => $filters
                        ]
                    )
                );
            }

            // Return JSON for API requests
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

            // Return appropriate response based on request type
            if ($this->isHtmlRequested()) {
                return Response::html(
                    $this->renderer->render('errors/500', [
                        'message' => 'Error al cargar los plugins',
                        'error' => $e->getMessage()
                    ]),
                    500
                );
            }

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
     * Show plugin upload form
     *
     * GET /admin/plugins/upload
     *
     * Displays the plugin upload interface where administrators can
     * upload new plugins via ZIP file.
     *
     * @return Response HTML view with upload form
     */
    public function showUploadForm(): Response
    {
        try {
            Logger::info('Plugin upload form accessed');

            return Response::html(
                $this->renderer->render(
                    'admin/plugins/upload',
                    [
                        'csrf_token' => $this->generateCsrfToken()
                    ]
                )
            );

        } catch (\Exception $e) {
            Logger::error('Failed to load plugin upload form', [
                'error' => $e->getMessage()
            ]);

            return Response::html(
                $this->renderer->render('errors/500', [
                    'message' => 'Error al cargar el formulario de subida',
                    'error' => $e->getMessage()
                ]),
                500
            );
        }
    }

    /**
     * Handle plugin upload from form
     *
     * POST /admin/plugins/upload
     * Expects: plugin_file (ZIP archive via multipart/form-data)
     *
     * Handles the uploaded plugin ZIP file, validates it, and installs it.
     *
     * @return Response JSON response with upload/installation result
     */
    public function handleUpload(): Response
    {
        try {
            // Verify CSRF token
            if (!$this->verifyCsrfToken($_POST['csrf_token'] ?? '')) {
                return Response::json([
                    'success' => false,
                    'message' => 'Invalid CSRF token'
                ], 403);
            }

            // Validate file upload
            if (!isset($_FILES['plugin_file']) || $_FILES['plugin_file']['error'] !== UPLOAD_ERR_OK) {
                $errorMessage = $this->getUploadErrorMessage($_FILES['plugin_file']['error'] ?? UPLOAD_ERR_NO_FILE);

                Logger::warning('Plugin upload failed', [
                    'error_code' => $_FILES['plugin_file']['error'] ?? 'NO_FILE',
                    'error_message' => $errorMessage
                ]);

                return Response::json([
                    'success' => false,
                    'message' => $errorMessage
                ], 400);
            }

            $uploadedFile = $_FILES['plugin_file'];

            // Validate file type
            if (!str_ends_with(strtolower($uploadedFile['name']), '.zip')) {
                return Response::json([
                    'success' => false,
                    'message' => 'Invalid file type. Only ZIP files are allowed.'
                ], 400);
            }

            // Validate file size (100MB max)
            $maxSize = 100 * 1024 * 1024;
            if ($uploadedFile['size'] > $maxSize) {
                return Response::json([
                    'success' => false,
                    'message' => 'File size exceeds maximum of 100MB'
                ], 400);
            }

            // Move uploaded file to temporary location
            $tempDir = sys_get_temp_dir();
            $tempFile = $tempDir . '/plugin_' . uniqid() . '.zip';

            if (!move_uploaded_file($uploadedFile['tmp_name'], $tempFile)) {
                Logger::error('Failed to move uploaded file', [
                    'temp_file' => $tempFile
                ]);

                return Response::json([
                    'success' => false,
                    'message' => 'Failed to process uploaded file'
                ], 500);
            }

            Logger::info('Plugin uploaded successfully', [
                'filename' => $uploadedFile['name'],
                'size' => $uploadedFile['size'],
                'temp_path' => $tempFile
            ]);

            // Install plugin using existing install() method
            $result = $this->pluginInstaller->install($tempFile);

            // Clean up temporary file
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }

            if (!$result['success']) {
                Logger::warning('Plugin installation failed after upload', [
                    'message' => $result['message']
                ]);

                return Response::json([
                    'success' => false,
                    'message' => $result['message']
                ], 400);
            }

            Logger::info('Plugin installed successfully from upload', [
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
            // Clean up temporary file on error
            if (isset($tempFile) && file_exists($tempFile)) {
                unlink($tempFile);
            }

            Logger::error('Plugin upload error', [
                'error' => $e->getMessage()
            ]);

            return Response::json([
                'success' => false,
                'message' => 'Upload error: ' . $e->getMessage()
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
     * Update a plugin from uploaded ZIP file
     *
     * POST /admin/plugins/{slug}/update
     * Expects: plugin_file (ZIP archive via multipart/form-data)
     *
     * Updates an existing plugin to a new version, validating compatibility
     * and preserving data.
     *
     * @param string $slug Plugin slug identifier
     * @return Response JSON response with update result
     */
    public function update(string $slug): Response
    {
        try {
            // Verify CSRF token
            if (!$this->verifyCsrfToken($_POST['csrf_token'] ?? '')) {
                return Response::json([
                    'success' => false,
                    'message' => 'Invalid CSRF token'
                ], 403);
            }

            // Validate plugin exists
            $plugin = $this->pluginManager->getBySlug($slug);

            if (!$plugin) {
                return Response::json([
                    'success' => false,
                    'message' => 'Plugin not found: ' . $slug
                ], 404);
            }

            // Validate file upload
            if (!isset($_FILES['plugin_file']) || $_FILES['plugin_file']['error'] !== UPLOAD_ERR_OK) {
                $errorMessage = $this->getUploadErrorMessage($_FILES['plugin_file']['error'] ?? UPLOAD_ERR_NO_FILE);

                Logger::warning('Plugin update upload failed', [
                    'slug' => $slug,
                    'error_code' => $_FILES['plugin_file']['error'] ?? 'NO_FILE',
                    'error_message' => $errorMessage
                ]);

                return Response::json([
                    'success' => false,
                    'message' => $errorMessage
                ], 400);
            }

            $uploadedFile = $_FILES['plugin_file'];

            // Validate file type
            if (!str_ends_with(strtolower($uploadedFile['name']), '.zip')) {
                return Response::json([
                    'success' => false,
                    'message' => 'Invalid file type. Only ZIP files are allowed.'
                ], 400);
            }

            // Validate file size (100MB max)
            $maxSize = 100 * 1024 * 1024;
            if ($uploadedFile['size'] > $maxSize) {
                return Response::json([
                    'success' => false,
                    'message' => 'File size exceeds maximum of 100MB'
                ], 400);
            }

            // Move uploaded file to temporary location
            $tempDir = sys_get_temp_dir();
            $tempFile = $tempDir . '/plugin_update_' . uniqid() . '.zip';

            if (!move_uploaded_file($uploadedFile['tmp_name'], $tempFile)) {
                Logger::error('Failed to move uploaded update file', [
                    'slug' => $slug,
                    'temp_file' => $tempFile
                ]);

                return Response::json([
                    'success' => false,
                    'message' => 'Failed to process uploaded file'
                ], 500);
            }

            Logger::info('Plugin update file uploaded', [
                'slug' => $slug,
                'filename' => $uploadedFile['name'],
                'size' => $uploadedFile['size'],
                'temp_path' => $tempFile
            ]);

            // Update plugin using PluginInstaller
            $result = $this->pluginInstaller->update($slug, $tempFile);

            // Clean up temporary file
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }

            if (!$result['success']) {
                Logger::warning('Plugin update failed', [
                    'slug' => $slug,
                    'message' => $result['message']
                ]);

                return Response::json([
                    'success' => false,
                    'message' => $result['message']
                ], 400);
            }

            Logger::info('Plugin updated successfully', [
                'slug' => $slug,
                'name' => $result['plugin']['name'],
                'version' => $result['plugin']['version']
            ]);

            return Response::json([
                'success' => true,
                'message' => $result['message'],
                'plugin' => $result['plugin']
            ], 200);

        } catch (\Exception $e) {
            // Clean up temporary file on error
            if (isset($tempFile) && file_exists($tempFile)) {
                unlink($tempFile);
            }

            Logger::error('Plugin update error', [
                'slug' => $slug,
                'error' => $e->getMessage()
            ]);

            return Response::json([
                'success' => false,
                'message' => 'Update error: ' . $e->getMessage()
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
     * Supports both HTML (browser) and JSON (API) responses based on Accept header
     *
     * @param string $slug Plugin slug identifier
     * @return Response HTML view or JSON response with plugin details
     */
    public function show(string $slug): Response
    {
        try {
            $plugin = $this->pluginManager->getBySlug($slug);

            if (!$plugin) {
                if ($this->isHtmlRequested()) {
                    return Response::html(
                        $this->renderer->render('errors/404', [
                            'message' => 'Plugin no encontrado'
                        ]),
                        404
                    );
                }

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

            Logger::info('Plugin details retrieved', [
                'slug' => $slug,
                'name' => $plugin['name']
            ]);

            // Check if HTML is requested (browser) or JSON (API)
            if ($this->isHtmlRequested()) {
                // Prepare plugin data for HTML view
                $plugin['type_icon'] = $this->getPluginTypeIcon($plugin['type']);
                $plugin['type_' . $plugin['type']] = true;

                return Response::html(
                    $this->renderer->render(
                        'admin/plugins/show',
                        [
                            'plugin' => $plugin,
                            'manifest' => $manifest,
                            'dependents' => $dependents,
                            'csrf_token' => $this->generateCsrfToken()
                        ]
                    )
                );
            }

            // Return JSON for API requests
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

            // Return appropriate response based on request type
            if ($this->isHtmlRequested()) {
                return Response::html(
                    $this->renderer->render('errors/500', [
                        'message' => 'Error al cargar los detalles del plugin',
                        'error' => $e->getMessage()
                    ]),
                    500
                );
            }

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

    /**
     * Check if HTML is requested based on Accept header
     *
     * @return bool True if HTML is requested, false for JSON/API
     */
    private function isHtmlRequested(): bool
    {
        $acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? '';

        // If Accept header contains HTML, prioritize it
        if (stripos($acceptHeader, 'text/html') !== false) {
            return true;
        }

        // If Accept header contains JSON, return JSON
        if (stripos($acceptHeader, 'application/json') !== false) {
            return false;
        }

        // Default to HTML for browser requests (common Accept headers)
        // Most browsers send Accept: text/html,application/xhtml+xml,...
        return stripos($acceptHeader, 'text/html') !== false ||
               stripos($acceptHeader, 'application/xhtml+xml') !== false ||
               empty($acceptHeader); // Default to HTML if no specific header
    }

    /**
     * Get Bootstrap icon class for plugin type
     *
     * @param string $type Plugin type
     * @return string Bootstrap icon class
     */
    private function getPluginTypeIcon(string $type): string
    {
        $iconMap = [
            'auth' => 'bi-shield-lock',
            'theme' => 'bi-palette',
            'tool' => 'bi-wrench',
            'module' => 'bi-box',
            'integration' => 'bi-arrow-left-right',
            'report' => 'bi-bar-chart'
        ];

        return $iconMap[$type] ?? 'bi-puzzle';
    }

    /**
     * Generate CSRF token for forms
     *
     * @return string CSRF token
     */
    private function generateCsrfToken(): string
    {
        if (!isset($_SESSION)) {
            session_start();
        }

        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    /**
     * Verify CSRF token
     *
     * @param string $token Token to verify
     * @return bool True if valid, false otherwise
     */
    private function verifyCsrfToken(string $token): bool
    {
        if (!isset($_SESSION)) {
            session_start();
        }

        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Get human-readable upload error message
     *
     * @param int $errorCode PHP upload error code
     * @return string Error message
     */
    private function getUploadErrorMessage(int $errorCode): string
    {
        return match($errorCode) {
            UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
            UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive',
            UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload',
            default => 'Unknown upload error'
        };
    }

    /**
     * Show plugin configuration form
     *
     * GET /admin/plugins/{slug}/configure
     *
     * @param string $slug Plugin slug
     * @return Response HTML view with configuration form
     */
    public function showConfigureForm(string $slug): Response
    {
        try {
            // Get plugin
            $plugin = $this->pluginManager->getBySlug($slug);
            if (!$plugin) {
                Logger::warning('Plugin not found for configuration', ['slug' => $slug]);
                return $this->jsonResponse(['error' => 'Plugin not found'], 404);
            }

            // Parse manifest
            $manifest = [];
            if (!empty($plugin['manifest'])) {
                $manifest = json_decode($plugin['manifest'], true) ?? [];
            }

            // Check if plugin has configuration schema
            $configSchema = $manifest['config_schema'] ?? [];
            if (empty($configSchema)) {
                Logger::info('Plugin has no configuration schema', ['slug' => $slug]);
            }

            // Get current configuration
            $currentConfig = $this->pluginConfigurator->getConfig($slug);

            // Generate form HTML
            $formHtml = $this->formGenerator->generateForm($configSchema, $currentConfig);

            // Generate validation JavaScript
            $validationJs = $this->formGenerator->generateValidationJS($configSchema);

            // Render view
            $data = [
                'plugin' => $plugin,
                'slug' => $slug,
                'name' => $plugin['name'],
                'version' => $plugin['version'],
                'description' => $plugin['description'] ?? '',
                'has_config' => !empty($configSchema),
                'form_html' => $formHtml,
                'validation_js' => $validationJs,
                'csrf_token' => $this->generateCsrfToken()
            ];

            return $this->htmlResponse(
                $this->renderer->render('admin/plugins/configure', $data)
            );

        } catch (\Exception $e) {
            Logger::error('Failed to show configuration form', [
                'slug' => $slug,
                'error' => $e->getMessage()
            ]);
            return $this->jsonResponse([
                'error' => 'Failed to load configuration form: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Save plugin configuration
     *
     * POST /admin/plugins/{slug}/configure
     *
     * @param string $slug Plugin slug
     * @param array $postData POST data with configuration values
     * @return Response JSON response with result
     */
    public function saveConfiguration(string $slug, array $postData): Response
    {
        try {
            // Verify CSRF token
            $csrfToken = $postData['csrf_token'] ?? '';
            if (!$this->verifyCsrfToken($csrfToken)) {
                return $this->jsonResponse(['error' => 'Invalid CSRF token'], 403);
            }

            // Remove CSRF token from config data
            unset($postData['csrf_token']);

            // Get plugin
            $plugin = $this->pluginManager->getBySlug($slug);
            if (!$plugin) {
                return $this->jsonResponse(['error' => 'Plugin not found'], 404);
            }

            // Save configuration
            $result = $this->pluginConfigurator->setConfig($slug, $postData);

            if ($result['success']) {
                Logger::info('Plugin configuration saved', [
                    'slug' => $slug,
                    'keys' => array_keys($result['saved'])
                ]);

                return $this->jsonResponse([
                    'success' => true,
                    'message' => 'Configuration saved successfully',
                    'saved' => $result['saved']
                ]);
            } else {
                return $this->jsonResponse([
                    'success' => false,
                    'errors' => $result['errors']
                ], 400);
            }

        } catch (\Exception $e) {
            Logger::error('Failed to save configuration', [
                'slug' => $slug,
                'error' => $e->getMessage()
            ]);
            return $this->jsonResponse([
                'error' => 'Failed to save configuration: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get plugin configuration (API endpoint)
     *
     * GET /admin/plugins/{slug}/config
     *
     * @param string $slug Plugin slug
     * @return Response JSON response with configuration
     */
    public function getConfiguration(string $slug): Response
    {
        try {
            $config = $this->pluginConfigurator->getConfig($slug);

            return $this->jsonResponse([
                'success' => true,
                'config' => $config
            ]);

        } catch (\Exception $e) {
            Logger::error('Failed to get configuration', [
                'slug' => $slug,
                'error' => $e->getMessage()
            ]);
            return $this->jsonResponse([
                'error' => 'Failed to get configuration: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reset plugin configuration to defaults
     *
     * POST /admin/plugins/{slug}/configure/reset
     *
     * @param string $slug Plugin slug
     * @param array $postData POST data (for CSRF token)
     * @return Response JSON response with result
     */
    public function resetConfiguration(string $slug, array $postData): Response
    {
        try {
            // Verify CSRF token
            $csrfToken = $postData['csrf_token'] ?? '';
            if (!$this->verifyCsrfToken($csrfToken)) {
                return $this->jsonResponse(['error' => 'Invalid CSRF token'], 403);
            }

            // Get plugin
            $plugin = $this->pluginManager->getBySlug($slug);
            if (!$plugin) {
                return $this->jsonResponse(['error' => 'Plugin not found'], 404);
            }

            // Reset configuration
            $result = $this->pluginConfigurator->resetConfig($slug);

            if ($result) {
                $defaults = $this->pluginConfigurator->getDefaultConfig($slug);

                Logger::info('Plugin configuration reset to defaults', ['slug' => $slug]);

                return $this->jsonResponse([
                    'success' => true,
                    'message' => 'Configuration reset to defaults',
                    'config' => $defaults
                ]);
            } else {
                return $this->jsonResponse([
                    'success' => false,
                    'error' => 'Failed to reset configuration'
                ], 500);
            }

        } catch (\Exception $e) {
            Logger::error('Failed to reset configuration', [
                'slug' => $slug,
                'error' => $e->getMessage()
            ]);
            return $this->jsonResponse([
                'error' => 'Failed to reset configuration: ' . $e->getMessage()
            ], 500);
        }
    }
}
