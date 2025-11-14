<?php

/**
 * ISER - Admin Theme Controller
 *
 * HTTP controller for theme configuration management in admin panel.
 * Handles theme settings, color customization, layout options, and asset uploads.
 *
 * @package    ISER\Controllers
 * @category   Modules
 * @author     ISER Development Team
 * @copyright  2024 ISER
 * @license    Proprietary
 * @version    1.0.0
 * @since      Week 5-6 - Theme System Implementation
 */

namespace ISER\Controllers;

use ISER\Core\Controllers\BaseController;
use ISER\Core\Database\Database;
use ISER\Core\Theme\ThemeManager;
use ISER\Core\Theme\ThemeConfigurator;
use ISER\Core\Theme\ColorSchemeGenerator;
use ISER\Plugin\PluginManager;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * AdminThemeController Class
 *
 * HTTP endpoints for theme management:
 * - Show theme configuration UI
 * - Save theme settings
 * - Upload logo/favicon
 * - Preview themes
 * - Reset to defaults
 * - Export/Import themes
 * - Toggle dark mode
 */
class AdminThemeController extends BaseController
{
    /**
     * Theme manager instance
     */
    private ThemeManager $themeManager;

    /**
     * Theme configurator instance
     */
    private ThemeConfigurator $themeConfigurator;

    /**
     * Color scheme generator instance
     */
    private ColorSchemeGenerator $colorGenerator;

    /**
     * Plugin manager instance
     */
    private ?PluginManager $pluginManager;

    /**
     * Upload directory for theme assets
     */
    private string $uploadDir;

    /**
     * Constructor
     *
     * @param Database $db Database instance
     */
    public function __construct(Database $db)
    {
        parent::__construct($db);

        $this->pluginManager = new PluginManager($db);
        $this->themeManager = new ThemeManager($db, $this->pluginManager);
        $this->themeConfigurator = new ThemeConfigurator($db, $this->themeManager);
        $this->colorGenerator = new ColorSchemeGenerator();

        $this->uploadDir = dirname(dirname(__DIR__)) . '/public_html/assets/theme';

        // Create upload directory if doesn't exist
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    /**
     * Show theme configuration page
     *
     * GET /admin/appearance/theme
     *
     * @param ServerRequestInterface $request HTTP request
     * @return ResponseInterface HTTP response
     */
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->isAuthenticated()) {
            return $this->redirect('/login');
        }

        // Get current theme settings
        $settings = $this->themeManager->getThemeSettings();

        // Get theme statistics
        $stats = $this->themeConfigurator->getStatistics();

        // Get active theme plugin
        $activePlugin = $this->themeManager->getActiveThemePlugin();

        // Prepare data for view
        $data = [
            'locale' => $this->translator->getLocale(),
            'page_title' => 'Theme Configuration',
            'header_title' => 'Appearance Settings',
            'settings' => $settings,
            'stats' => $stats,
            'active_plugin' => $activePlugin,
            'csrf_token' => $this->generateCsrfToken(),

            // Color categories
            'colors' => $settings['colors'] ?? [],
            'typography' => $settings['typography'] ?? [],
            'layout' => $settings['layout'] ?? [],
            'branding' => $settings['branding'] ?? [],
            'dark_mode' => $settings['dark_mode'] ?? [],

            // Available theme plugins
            'theme_plugins' => $this->pluginManager->getByType('theme')
        ];

        return $this->render('admin/appearance/theme', $data, '/admin');
    }

    /**
     * Save theme configuration
     *
     * POST /admin/appearance/theme/save
     *
     * @param ServerRequestInterface $request HTTP request
     * @return ResponseInterface HTTP response
     */
    public function save(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->isAuthenticated()) {
            return $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }

        try {
            $data = $this->getPostData($request);

            // Verify CSRF token
            if (!$this->verifyCsrfToken($data['csrf_token'] ?? '')) {
                return $this->jsonResponse(['error' => 'Invalid CSRF token'], 403);
            }

            unset($data['csrf_token']);

            // Organize settings by category
            $settingsByCategory = $this->organizeSettingsByCategory($data);

            $totalSaved = 0;
            $totalFailed = 0;
            $errors = [];

            // Save each category
            foreach ($settingsByCategory as $category => $settings) {
                $result = $this->themeConfigurator->setMultiple($settings, $category);
                $totalSaved += $result['saved'];
                $totalFailed += $result['failed'];
            }

            if ($totalFailed === 0) {
                return $this->jsonResponse([
                    'success' => true,
                    'message' => 'Theme settings saved successfully',
                    'saved' => $totalSaved
                ]);
            } else {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Some settings failed to save',
                    'saved' => $totalSaved,
                    'failed' => $totalFailed
                ], 400);
            }

        } catch (\Exception $e) {
            return $this->jsonResponse([
                'error' => 'Failed to save theme settings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload logo
     *
     * POST /admin/appearance/theme/upload-logo
     *
     * @param ServerRequestInterface $request HTTP request
     * @return ResponseInterface HTTP response
     */
    public function uploadLogo(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->isAuthenticated()) {
            return $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }

        return $this->handleAssetUpload('logo', [
            'allowed_types' => ['image/png', 'image/jpeg', 'image/svg+xml'],
            'max_size' => 2 * 1024 * 1024, // 2MB
            'max_width' => 1000,
            'max_height' => 500
        ]);
    }

    /**
     * Upload favicon
     *
     * POST /admin/appearance/theme/upload-favicon
     *
     * @param ServerRequestInterface $request HTTP request
     * @return ResponseInterface HTTP response
     */
    public function uploadFavicon(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->isAuthenticated()) {
            return $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }

        return $this->handleAssetUpload('favicon', [
            'allowed_types' => ['image/x-icon', 'image/vnd.microsoft.icon', 'image/png'],
            'max_size' => 100 * 1024, // 100KB
            'max_width' => 256,
            'max_height' => 256
        ]);
    }

    /**
     * Handle asset upload
     *
     * @param string $assetType Type of asset
     * @param array $options Upload options
     * @return ResponseInterface HTTP response
     */
    private function handleAssetUpload(string $assetType, array $options): ResponseInterface
    {
        try {
            if (empty($_FILES['file'])) {
                return $this->jsonResponse(['error' => 'No file uploaded'], 400);
            }

            $file = $_FILES['file'];

            // Check upload errors
            if ($file['error'] !== UPLOAD_ERR_OK) {
                return $this->jsonResponse([
                    'error' => 'Upload failed: ' . $this->getUploadErrorMessage($file['error'])
                ], 400);
            }

            // Validate file type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mimeType, $options['allowed_types'])) {
                return $this->jsonResponse([
                    'error' => 'Invalid file type. Allowed: ' . implode(', ', $options['allowed_types'])
                ], 400);
            }

            // Validate file size
            if ($file['size'] > $options['max_size']) {
                $maxSizeMB = $options['max_size'] / (1024 * 1024);
                return $this->jsonResponse([
                    'error' => "File too large. Max size: {$maxSizeMB}MB"
                ], 400);
            }

            // Validate dimensions for images
            if (strpos($mimeType, 'image/') === 0 && $mimeType !== 'image/svg+xml') {
                list($width, $height) = getimagesize($file['tmp_name']);

                if ($width > $options['max_width'] || $height > $options['max_height']) {
                    return $this->jsonResponse([
                        'error' => "Image too large. Max dimensions: {$options['max_width']}x{$options['max_height']}"
                    ], 400);
                }
            }

            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = $assetType . '_' . time() . '.' . $extension;
            $filepath = $this->uploadDir . '/' . $filename;

            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                return $this->jsonResponse(['error' => 'Failed to save file'], 500);
            }

            // Save to database
            $relativePath = '/assets/theme/' . $filename;

            $this->db->insert('theme_assets', [
                'asset_type' => $assetType,
                'file_path' => $relativePath,
                'file_name' => $file['name'],
                'mime_type' => $mimeType,
                'file_size' => $file['size'],
                'width' => $width ?? null,
                'height' => $height ?? null,
                'is_active' => 1,
                'uploaded_at' => time(),
                'uploaded_by' => $_SESSION['user_id'] ?? null
            ]);

            // Deactivate other assets of same type
            $this->db->query("
                UPDATE theme_assets
                SET is_active = 0
                WHERE asset_type = ? AND file_path != ?
            ", [$assetType, $relativePath]);

            // Update theme setting
            $settingKey = "branding.{$assetType}_url";
            $this->themeConfigurator->setSetting($settingKey, $relativePath, 'branding');

            return $this->jsonResponse([
                'success' => true,
                'message' => ucfirst($assetType) . ' uploaded successfully',
                'file_path' => $relativePath,
                'file_name' => $filename
            ]);

        } catch (\Exception $e) {
            return $this->jsonResponse([
                'error' => 'Upload failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reset theme to defaults
     *
     * POST /admin/appearance/theme/reset
     *
     * @param ServerRequestInterface $request HTTP request
     * @return ResponseInterface HTTP response
     */
    public function reset(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->isAuthenticated()) {
            return $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }

        try {
            $data = $this->getPostData($request);

            // Verify CSRF token
            if (!$this->verifyCsrfToken($data['csrf_token'] ?? '')) {
                return $this->jsonResponse(['error' => 'Invalid CSRF token'], 403);
            }

            $category = $data['category'] ?? null;

            if ($category) {
                // Reset specific category
                $result = $this->themeConfigurator->resetCategory($category);
                $message = "Theme {$category} settings reset to defaults";
            } else {
                // Reset all
                $result = $this->themeConfigurator->resetToDefaults();
                $message = "All theme settings reset to defaults";
            }

            if ($result) {
                return $this->jsonResponse([
                    'success' => true,
                    'message' => $message
                ]);
            } else {
                return $this->jsonResponse([
                    'success' => false,
                    'error' => 'Failed to reset theme settings'
                ], 500);
            }

        } catch (\Exception $e) {
            return $this->jsonResponse([
                'error' => 'Reset failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export theme configuration
     *
     * GET /admin/appearance/theme/export
     *
     * @param ServerRequestInterface $request HTTP request
     * @return ResponseInterface HTTP response
     */
    public function export(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->isAuthenticated()) {
            return $this->redirect('/login');
        }

        try {
            $themeData = $this->themeConfigurator->exportTheme();

            $filename = 'nexosupport-theme-' . date('Y-m-d-His') . '.json';

            $response = $this->response->withHeader('Content-Type', 'application/json');
            $response = $response->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
            $response->getBody()->write(json_encode($themeData, JSON_PRETTY_PRINT));

            return $response;

        } catch (\Exception $e) {
            return $this->jsonResponse([
                'error' => 'Export failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Import theme configuration
     *
     * POST /admin/appearance/theme/import
     *
     * @param ServerRequestInterface $request HTTP request
     * @return ResponseInterface HTTP response
     */
    public function import(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->isAuthenticated()) {
            return $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }

        try {
            if (empty($_FILES['theme_file'])) {
                return $this->jsonResponse(['error' => 'No file uploaded'], 400);
            }

            $file = $_FILES['theme_file'];

            if ($file['error'] !== UPLOAD_ERR_OK) {
                return $this->jsonResponse([
                    'error' => 'Upload failed: ' . $this->getUploadErrorMessage($file['error'])
                ], 400);
            }

            // Read and parse JSON
            $json = file_get_contents($file['tmp_name']);
            $themeData = json_decode($json, true);

            if (!$themeData) {
                return $this->jsonResponse(['error' => 'Invalid JSON file'], 400);
            }

            // Import theme
            $result = $this->themeConfigurator->importTheme($themeData, true);

            if ($result['success']) {
                return $this->jsonResponse([
                    'success' => true,
                    'message' => 'Theme imported successfully',
                    'imported' => $result['imported']
                ]);
            } else {
                return $this->jsonResponse([
                    'success' => false,
                    'errors' => $result['errors']
                ], 400);
            }

        } catch (\Exception $e) {
            return $this->jsonResponse([
                'error' => 'Import failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle dark mode
     *
     * POST /api/theme/toggle-dark-mode
     *
     * @param ServerRequestInterface $request HTTP request
     * @return ResponseInterface HTTP response
     */
    public function toggleDarkMode(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $data = $this->getPostData($request);
            $enabled = $data['enabled'] ?? false;

            // Set cookie for client-side
            setcookie('theme_mode', $enabled ? 'dark' : 'light', [
                'expires' => time() + (365 * 24 * 60 * 60), // 1 year
                'path' => '/',
                'secure' => true,
                'httponly' => false,
                'samesite' => 'Lax'
            ]);

            return $this->jsonResponse([
                'success' => true,
                'mode' => $enabled ? 'dark' : 'light'
            ]);

        } catch (\Exception $e) {
            return $this->jsonResponse([
                'error' => 'Toggle failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get CSS variables
     *
     * GET /api/theme/css-variables
     *
     * @param ServerRequestInterface $request HTTP request
     * @return ResponseInterface HTTP response
     */
    public function getCssVariables(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $params = $request->getQueryParams();
            $darkMode = isset($params['dark']) && $params['dark'] === '1';

            $css = $this->themeManager->generateCSSVariables($darkMode);

            $response = $this->response->withHeader('Content-Type', 'text/css');
            $response->getBody()->write($css);

            return $response;

        } catch (\Exception $e) {
            return $this->jsonResponse([
                'error' => 'Failed to generate CSS: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Organize settings by category
     *
     * @param array $data Raw POST data
     * @return array Settings organized by category
     */
    private function organizeSettingsByCategory(array $data): array
    {
        $organized = [
            'colors' => [],
            'typography' => [],
            'layout' => [],
            'branding' => [],
            'dark_mode' => []
        ];

        foreach ($data as $key => $value) {
            $category = explode('.', $key)[0] ?? 'general';

            if (isset($organized[$category])) {
                $organized[$category][$key] = $value;
            }
        }

        return array_filter($organized); // Remove empty categories
    }

    /**
     * Get POST data from request
     *
     * @param ServerRequestInterface $request HTTP request
     * @return array POST data
     */
    private function getPostData(ServerRequestInterface $request): array
    {
        $contentType = $request->getHeaderLine('Content-Type');

        if (strpos($contentType, 'application/json') !== false) {
            $body = $request->getBody()->getContents();
            return json_decode($body, true) ?? [];
        }

        return $request->getParsedBody() ?? [];
    }

    /**
     * Get upload error message
     *
     * @param int $errorCode PHP upload error code
     * @return string Error message
     */
    private function getUploadErrorMessage(int $errorCode): string
    {
        return match($errorCode) {
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload',
            default => 'Unknown upload error'
        };
    }
}
