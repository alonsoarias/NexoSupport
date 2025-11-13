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
     * Constructor
     *
     * @param Database $db Database instance
     */
    public function __construct(Database $db)
    {
        parent::__construct($db);
        $this->themeConfig = new ThemeConfigurator($db);
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
}
