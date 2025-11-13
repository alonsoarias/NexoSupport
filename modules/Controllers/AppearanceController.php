<?php

declare(strict_types=1);

/**
 * ISER - Appearance Controller
 *
 * Handles theme and appearance configuration through HTTP requests
 * Manages configuration page display and settings updates
 *
 * @package ISER\Controllers
 * @author ISER Development Team
 * @copyright 2024 ISER
 * @license Proprietary
 */

namespace ISER\Controllers;

use ISER\Core\Controllers\BaseController;
use ISER\Core\Database\Database;
use ISER\Theme\ThemeConfigurator;
use ISER\Theme\AssetManager;
use ISER\Theme\ColorManager;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * AppearanceController Class (REFACTORIZADO con BaseController)
 *
 * Handles theme and appearance configuration management
 * Provides endpoints for viewing and updating theme settings
 *
 * Extiende BaseController para reducir código duplicado.
 */
class AppearanceController extends BaseController
{
    /**
     * Theme configurator instance
     */
    private ThemeConfigurator $themeConfig;

    /**
     * Asset manager instance
     */
    private AssetManager $assetManager;

    /**
     * Constructor
     *
     * @param Database $db Database instance
     */
    public function __construct(Database $db)
    {
        parent::__construct($db);
        $this->themeConfig = new ThemeConfigurator($db);
        $this->assetManager = new AssetManager($db, $this->themeConfig);
    }

    /**
     * Display theme appearance configuration page
     *
     * GET /admin/appearance
     *
     * @param ServerRequestInterface $request HTTP request
     * @return ResponseInterface HTTP response
     */
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        // Verify authentication
        if (!$this->isAuthenticated()) {
            return $this->redirect('/login');
        }

        // Verify admin/moderator permissions
        if (!$this->hasAdminPermission()) {
            return $this->jsonError('Unauthorized access', [], 403);
        }

        try {
            // Get all current theme configurations
            $currentConfig = $this->themeConfig->getAll();

            // Get allowed fonts and colors for form
            $allowedFonts = $this->themeConfig->getAllowedFonts();
            $defaultColors = $this->themeConfig->getDefaultColors();

            // Prepare data for view
            $data = [
                'page_title' => 'Apariencia y Tema',
                'header_title' => 'Configuración de Apariencia',
                'locale' => 'es',

                // Current configuration
                'colors' => $this->formatColorConfig($currentConfig),
                'fonts' => $this->formatFontConfig($currentConfig),

                // Available options
                'available_fonts' => array_map(function($font) use ($currentConfig) {
                    return [
                        'value' => $font,
                        'label' => $font,
                        'selected' => false
                    ];
                }, $allowedFonts),

                'default_colors' => array_map(function($name, $hex) {
                    return [
                        'name' => $name,
                        'hex' => $hex
                    ];
                }, array_keys($defaultColors), array_values($defaultColors)),

                // Form messages
                'message' => null,
                'error' => null,
            ];

            return $this->render('admin/appearance', $data, '/admin/appearance');
        } catch (\Exception $e) {
            error_log("Error in AppearanceController::index: " . $e->getMessage());
            return $this->jsonError('Internal server error', [], 500);
        }
    }

    /**
     * Save theme configuration
     *
     * POST /admin/appearance/save
     *
     * @param ServerRequestInterface $request HTTP request
     * @return ResponseInterface HTTP response (JSON)
     */
    public function save(ServerRequestInterface $request): ResponseInterface
    {
        // Verify authentication
        if (!$this->isAuthenticated()) {
            return $this->jsonError('Unauthorized access', [], 401);
        }

        // Verify admin/moderator permissions
        if (!$this->hasAdminPermission()) {
            return $this->jsonError('Insufficient permissions', [], 403);
        }

        try {
            // Get request body
            $body = (string)$request->getBody();
            $data = json_decode($body, true);

            if (!is_array($data)) {
                return $this->jsonError('Invalid request data', [], 400);
            }

            $savedCount = 0;
            $errors = [];

            // Save color configurations
            if (isset($data['colors']) && is_array($data['colors'])) {
                foreach ($data['colors'] as $colorName => $colorValue) {
                    if ($this->themeConfig->set($colorName, $colorValue)) {
                        $savedCount++;
                    } else {
                        $errors[] = "Failed to save color: {$colorName}";
                    }
                }
            }

            // Save font configurations
            if (isset($data['fonts']) && is_array($data['fonts'])) {
                foreach ($data['fonts'] as $fontName => $fontValue) {
                    if ($this->themeConfig->set($fontName, $fontValue)) {
                        $savedCount++;
                    } else {
                        $errors[] = "Failed to save font: {$fontName}";
                    }
                }
            }

            // Return response
            if (!empty($errors)) {
                return $this->json([
                    'success' => false,
                    'message' => "Saved {$savedCount} settings with " . count($errors) . " errors",
                    'errors' => $errors,
                    'saved' => $savedCount
                ], 200);
            }

            return $this->jsonSuccess(
                "Configuration saved successfully ({$savedCount} items)",
                ['saved' => $savedCount]
            );

        } catch (\Exception $e) {
            error_log("Error in AppearanceController::save: " . $e->getMessage());
            return $this->jsonError(
                'Failed to save configuration: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    /**
     * Reset theme configuration to defaults
     *
     * POST /admin/appearance/reset
     *
     * @param ServerRequestInterface $request HTTP request
     * @return ResponseInterface HTTP response (JSON)
     */
    public function reset(ServerRequestInterface $request): ResponseInterface
    {
        // Verify authentication
        if (!$this->isAuthenticated()) {
            return $this->jsonError('Unauthorized access', [], 401);
        }

        // Verify admin/moderator permissions
        if (!$this->hasAdminPermission()) {
            return $this->jsonError('Insufficient permissions', [], 403);
        }

        try {
            // Reset to defaults
            if ($this->themeConfig->reset()) {
                return $this->jsonSuccess('Theme configuration reset to defaults successfully');
            } else {
                return $this->jsonError('Failed to reset configuration', [], 500);
            }
        } catch (\Exception $e) {
            error_log("Error in AppearanceController::reset: " . $e->getMessage());
            return $this->jsonError(
                'Failed to reset configuration: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    /**
     * Check if user has admin permission
     *
     * @return bool True if user has admin permission
     */
    private function hasAdminPermission(): bool
    {
        // Check if user has admin or moderator role
        $roles = $_SESSION['roles'] ?? [];

        if (is_array($roles)) {
            foreach ($roles as $role) {
                if (isset($role['slug']) && in_array($role['slug'], ['admin', 'moderator'])) {
                    return true;
                }
            }
        }

        // Fallback: check role_name
        $roleName = $_SESSION['role_name'] ?? '';
        return in_array($roleName, ['admin', 'moderator', 'administrator']);
    }

    /**
     * Format color configuration for view
     *
     * @param array $config Current configuration
     * @return array Formatted color configuration
     */
    private function formatColorConfig(array $config): array
    {
        $colors = [];
        $defaultColors = $this->themeConfig->getDefaultColors();

        foreach (array_keys($defaultColors) as $colorName) {
            $colors[$colorName] = $config[$colorName] ?? $defaultColors[$colorName];
        }

        return $colors;
    }

    /**
     * Format font configuration for view
     *
     * @param array $config Current configuration
     * @return array Formatted font configuration
     */
    private function formatFontConfig(array $config): array
    {
        $fonts = [];
        $defaultFonts = $this->themeConfig->getDefaultFonts();

        foreach (array_keys($defaultFonts) as $fontName) {
            $fonts[$fontName] = $config[$fontName] ?? $defaultFonts[$fontName];
        }

        return $fonts;
    }

    /**
     * Export theme configuration as JSON
     *
     * GET /admin/appearance/export
     *
     * @param ServerRequestInterface $request HTTP request
     * @return ResponseInterface HTTP response (JSON download)
     */
    public function export(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->isAuthenticated()) {
            return $this->jsonError('Unauthorized access', [], 401);
        }

        if (!$this->hasAdminPermission()) {
            return $this->jsonError('Insufficient permissions', [], 403);
        }

        try {
            $json = $this->themeConfig->exportConfiguration();

            // Create response with JSON download
            $response = new \ISER\Core\Http\Response();
            $response->getBody()->write($json);

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withHeader('Content-Disposition', 'attachment; filename="theme-export-' . date('Y-m-d') . '.json"')
                ->withHeader('Cache-Control', 'no-cache, no-store, must-revalidate');
        } catch (\Exception $e) {
            error_log("Error in AppearanceController::export: " . $e->getMessage());
            return $this->jsonError('Failed to export configuration', [], 500);
        }
    }

    /**
     * Import theme configuration from JSON
     *
     * POST /admin/appearance/import
     *
     * @param ServerRequestInterface $request HTTP request
     * @return ResponseInterface HTTP response (JSON)
     */
    public function import(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->isAuthenticated()) {
            return $this->jsonError('Unauthorized access', [], 401);
        }

        if (!$this->hasAdminPermission()) {
            return $this->jsonError('Insufficient permissions', [], 403);
        }

        try {
            $body = (string)$request->getBody();
            $data = json_decode($body, true);

            if (!isset($data['json'])) {
                return $this->jsonError('Missing JSON data', [], 400);
            }

            if ($this->themeConfig->importConfiguration($data['json'])) {
                // Regenerate CSS after import
                $this->assetManager->regenerate();

                return $this->jsonSuccess('Theme configuration imported successfully');
            } else {
                return $this->jsonError('Failed to import configuration', [], 500);
            }
        } catch (\Exception $e) {
            error_log("Error in AppearanceController::import: " . $e->getMessage());
            return $this->jsonError('Import failed: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Create backup of current theme configuration
     *
     * POST /admin/appearance/backup/create
     *
     * @param ServerRequestInterface $request HTTP request
     * @return ResponseInterface HTTP response (JSON)
     */
    public function createBackup(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->isAuthenticated()) {
            return $this->jsonError('Unauthorized access', [], 401);
        }

        if (!$this->hasAdminPermission()) {
            return $this->jsonError('Insufficient permissions', [], 403);
        }

        try {
            $body = (string)$request->getBody();
            $data = json_decode($body, true);

            $backupName = $data['name'] ?? 'Backup ' . date('Y-m-d H:i:s');

            $backupId = $this->themeConfig->createBackup($backupName);

            if ($backupId > 0) {
                return $this->jsonSuccess('Backup created successfully', [
                    'backup_id' => $backupId,
                    'backup_name' => $backupName
                ]);
            } else {
                return $this->jsonError('Failed to create backup', [], 500);
            }
        } catch (\Exception $e) {
            error_log("Error in AppearanceController::createBackup: " . $e->getMessage());
            return $this->jsonError('Backup failed: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Restore theme configuration from backup
     *
     * POST /admin/appearance/backup/restore
     *
     * @param ServerRequestInterface $request HTTP request
     * @return ResponseInterface HTTP response (JSON)
     */
    public function restoreBackup(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->isAuthenticated()) {
            return $this->jsonError('Unauthorized access', [], 401);
        }

        if (!$this->hasAdminPermission()) {
            return $this->jsonError('Insufficient permissions', [], 403);
        }

        try {
            $body = (string)$request->getBody();
            $data = json_decode($body, true);

            if (!isset($data['backup_id'])) {
                return $this->jsonError('Missing backup_id', [], 400);
            }

            $backupId = (int)$data['backup_id'];

            if ($this->themeConfig->restoreBackup($backupId)) {
                // Regenerate CSS after restore
                $this->assetManager->regenerate();

                return $this->jsonSuccess('Theme configuration restored successfully');
            } else {
                return $this->jsonError('Failed to restore backup', [], 500);
            }
        } catch (\Exception $e) {
            error_log("Error in AppearanceController::restoreBackup: " . $e->getMessage());
            return $this->jsonError('Restore failed: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * List all theme backups
     *
     * GET /admin/appearance/backups
     *
     * @param ServerRequestInterface $request HTTP request
     * @return ResponseInterface HTTP response (JSON)
     */
    public function listBackups(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->isAuthenticated()) {
            return $this->jsonError('Unauthorized access', [], 401);
        }

        if (!$this->hasAdminPermission()) {
            return $this->jsonError('Insufficient permissions', [], 403);
        }

        try {
            $backups = $this->themeConfig->getBackups(50);

            return $this->json([
                'success' => true,
                'data' => $backups
            ]);
        } catch (\Exception $e) {
            error_log("Error in AppearanceController::listBackups: " . $e->getMessage());
            return $this->jsonError('Failed to list backups', [], 500);
        }
    }

    /**
     * Delete a theme backup
     *
     * DELETE /admin/appearance/backup/{id}
     *
     * @param ServerRequestInterface $request HTTP request
     * @return ResponseInterface HTTP response (JSON)
     */
    public function deleteBackup(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->isAuthenticated()) {
            return $this->jsonError('Unauthorized access', [], 401);
        }

        if (!$this->hasAdminPermission()) {
            return $this->jsonError('Insufficient permissions', [], 403);
        }

        try {
            // Get backup ID from route parameters
            $params = $request->getAttribute('route_params', []);
            $backupId = (int)($params['id'] ?? 0);

            if ($backupId <= 0) {
                return $this->jsonError('Invalid backup ID', [], 400);
            }

            if ($this->themeConfig->deleteBackup($backupId)) {
                return $this->jsonSuccess('Backup deleted successfully');
            } else {
                return $this->jsonError('Failed to delete backup', [], 500);
            }
        } catch (\Exception $e) {
            error_log("Error in AppearanceController::deleteBackup: " . $e->getMessage());
            return $this->jsonError('Delete failed: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Regenerate CSS file
     *
     * POST /admin/appearance/regenerate-css
     *
     * @param ServerRequestInterface $request HTTP request
     * @return ResponseInterface HTTP response (JSON)
     */
    public function regenerateCss(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->isAuthenticated()) {
            return $this->jsonError('Unauthorized access', [], 401);
        }

        if (!$this->hasAdminPermission()) {
            return $this->jsonError('Insufficient permissions', [], 403);
        }

        try {
            $cssUrl = $this->assetManager->regenerate();

            if (!empty($cssUrl)) {
                return $this->jsonSuccess('CSS regenerated successfully', [
                    'css_url' => $cssUrl,
                    'timestamp' => time()
                ]);
            } else {
                return $this->jsonError('Failed to regenerate CSS', [], 500);
            }
        } catch (\Exception $e) {
            error_log("Error in AppearanceController::regenerateCss: " . $e->getMessage());
            return $this->jsonError('Regeneration failed: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Validate color contrast (WCAG)
     *
     * POST /admin/appearance/validate-contrast
     *
     * @param ServerRequestInterface $request HTTP request
     * @return ResponseInterface HTTP response (JSON)
     */
    public function validateContrast(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->isAuthenticated()) {
            return $this->jsonError('Unauthorized access', [], 401);
        }

        try {
            $body = (string)$request->getBody();
            $data = json_decode($body, true);

            if (!isset($data['foreground']) || !isset($data['background'])) {
                return $this->jsonError('Missing color parameters', [], 400);
            }

            $foreground = $data['foreground'];
            $background = $data['background'];

            // Validate hex colors
            if (!ColorManager::isValidHex($foreground) || !ColorManager::isValidHex($background)) {
                return $this->jsonError('Invalid color format', [], 400);
            }

            // Calculate contrast ratio
            $ratio = ColorManager::getContrastRatio($foreground, $background);

            // Check WCAG compliance
            $meetsAA = ColorManager::meetsWCAG($foreground, $background, 'AA', 'normal');
            $meetsAAA = ColorManager::meetsWCAG($foreground, $background, 'AAA', 'normal');
            $meetsAALarge = ColorManager::meetsWCAG($foreground, $background, 'AA', 'large');

            return $this->json([
                'success' => true,
                'data' => [
                    'ratio' => $ratio,
                    'meets_aa' => $meetsAA,
                    'meets_aaa' => $meetsAAA,
                    'meets_aa_large' => $meetsAALarge,
                    'rating' => $this->getContrastRating($ratio)
                ]
            ]);
        } catch (\Exception $e) {
            error_log("Error in AppearanceController::validateContrast: " . $e->getMessage());
            return $this->jsonError('Validation failed: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Get contrast rating description
     *
     * @param float $ratio Contrast ratio
     * @return string Rating description
     */
    private function getContrastRating(float $ratio): string
    {
        if ($ratio >= 7.0) {
            return 'excellent';
        } elseif ($ratio >= 4.5) {
            return 'good';
        } elseif ($ratio >= 3.0) {
            return 'fair';
        } else {
            return 'poor';
        }
    }
}
