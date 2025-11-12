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

use ISER\Controllers\Traits\NavigationTrait;
use ISER\Core\Database\Database;
use ISER\Core\View\MustacheRenderer;
use ISER\Core\Http\Response;
use ISER\Theme\ThemeConfigurator;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * AppearanceController Class
 *
 * Handles theme and appearance configuration management
 * Provides endpoints for viewing and updating theme settings
 */
class AppearanceController
{
    use NavigationTrait;

    /**
     * Database instance
     */
    private Database $db;

    /**
     * Mustache renderer instance
     */
    private MustacheRenderer $renderer;

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
        $this->db = $db;
        $this->renderer = MustacheRenderer::getInstance();
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
            return Response::redirect('/login');
        }

        // Verify admin/moderator permissions
        if (!$this->hasAdminPermission()) {
            return Response::json(
                ['error' => 'Unauthorized access'],
                403
            );
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

            // Enrich with navigation
            $data = $this->enrichWithNavigation($data, '/admin/appearance');

            // Render with layout
            $html = $this->renderer->render('admin/appearance', $data, 'layouts/app');
            return Response::html($html);
        } catch (\Exception $e) {
            error_log("Error in AppearanceController::index: " . $e->getMessage());
            return Response::json(
                ['error' => 'Internal server error'],
                500
            );
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
            return Response::json(
                ['error' => 'Unauthorized access'],
                401
            );
        }

        // Verify admin/moderator permissions
        if (!$this->hasAdminPermission()) {
            return Response::json(
                ['error' => 'Insufficient permissions'],
                403
            );
        }

        try {
            // Get request body
            $body = (string)$request->getBody();
            $data = json_decode($body, true);

            if (!is_array($data)) {
                return Response::json(
                    ['error' => 'Invalid request data'],
                    400
                );
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
                return Response::json([
                    'success' => false,
                    'message' => "Saved {$savedCount} settings with " . count($errors) . " errors",
                    'errors' => $errors,
                    'saved' => $savedCount
                ], 200);
            }

            return Response::json([
                'success' => true,
                'message' => "Configuration saved successfully ({$savedCount} items)",
                'saved' => $savedCount
            ], 200);

        } catch (\Exception $e) {
            error_log("Error in AppearanceController::save: " . $e->getMessage());
            return Response::json(
                ['error' => 'Failed to save configuration: ' . $e->getMessage()],
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
            return Response::json(
                ['error' => 'Unauthorized access'],
                401
            );
        }

        // Verify admin/moderator permissions
        if (!$this->hasAdminPermission()) {
            return Response::json(
                ['error' => 'Insufficient permissions'],
                403
            );
        }

        try {
            // Reset to defaults
            if ($this->themeConfig->reset()) {
                return Response::json([
                    'success' => true,
                    'message' => 'Theme configuration reset to defaults successfully'
                ], 200);
            } else {
                return Response::json([
                    'error' => 'Failed to reset configuration'
                ], 500);
            }
        } catch (\Exception $e) {
            error_log("Error in AppearanceController::reset: " . $e->getMessage());
            return Response::json(
                ['error' => 'Failed to reset configuration: ' . $e->getMessage()],
                500
            );
        }
    }

    /**
     * Check if user is authenticated
     *
     * @return bool True if authenticated
     */
    private function isAuthenticated(): bool
    {
        return isset($_SESSION['user_id'])
            && isset($_SESSION['authenticated'])
            && $_SESSION['authenticated'] === true;
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
