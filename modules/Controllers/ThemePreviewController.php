<?php

declare(strict_types=1);

/**
 * ISER - Theme Preview Controller (FASE 8)
 *
 * Handles theme preview system with side-by-side comparison,
 * live preview via iframe, and session-based theme switching
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
 * ThemePreviewController Class (REFACTORIZADO con BaseController)
 *
 * Manages theme preview, switching, and application
 * Provides side-by-side theme comparison and live preview
 *
 * Extiende BaseController para reducir código duplicado.
 */
class ThemePreviewController extends BaseController
{
    /**
     * Theme configurator instance
     */
    private ThemeConfigurator $themeConfig;

    /**
     * Session key for preview theme
     */
    private const SESSION_PREVIEW_KEY = 'theme_preview';

    /**
     * Session key for original theme
     */
    private const SESSION_ORIGINAL_KEY = 'theme_original';

    /**
     * Available themes directory
     */
    private const THEMES_DIR = __DIR__ . '/../../modules/Theme';

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
     * Display theme preview page with side-by-side comparison
     *
     * GET /admin/theme/preview
     *
     * @param ServerRequestInterface $request HTTP request
     * @return ResponseInterface HTTP response
     */
    public function preview(ServerRequestInterface $request): ResponseInterface
    {
        // Verify authentication
        if (!$this->isAuthenticated()) {
            return $this->redirect('/login');
        }

        // Verify admin/moderator permissions
        if (!$this->hasAdminPermission()) {
            return $this->jsonError($this->translator->translate('theme.errors.unauthorized'), [], 403);
        }

        try {
            // Get available themes
            $themes = $this->getAvailableThemes();

            // Get current preview theme from session or use default
            $currentPreviewTheme = $_SESSION[self::SESSION_PREVIEW_KEY] ?? 'iser';
            $originalTheme = $_SESSION[self::SESSION_ORIGINAL_KEY] ?? 'iser';

            // Get theme metadata
            $themeMetadata = [];
            foreach ($themes as $theme) {
                $themeMetadata[$theme] = $this->getThemeMetadata($theme);
            }

            // Get current theme configuration
            $currentConfig = $this->themeConfig->getAll();

            // Prepare data for view
            $data = [
                'page_title' => $this->translator->translate('theme.preview.title'),
                'header_title' => $this->translator->translate('theme.preview.header'),
                'locale' => $this->translator->getLocale(),

                // Available themes
                'themes' => array_map(function($theme) use ($themeMetadata, $currentPreviewTheme) {
                    return [
                        'id' => $theme,
                        'name' => $themeMetadata[$theme]['name'] ?? $theme,
                        'description' => $themeMetadata[$theme]['description'] ?? '',
                        'author' => $themeMetadata[$theme]['author'] ?? '',
                        'version' => $themeMetadata[$theme]['version'] ?? '1.0.0',
                        'is_current' => $theme === $currentPreviewTheme,
                        'thumbnail' => $this->getThemeThumbnailUrl($theme),
                    ];
                }, $themes),

                // Current configuration
                'colors' => $this->formatColorConfig($currentConfig),
                'fonts' => $this->formatFontConfig($currentConfig),
                'default_colors' => array_map(function($name, $hex) {
                    return [
                        'name' => $name,
                        'hex' => $hex
                    ];
                }, array_keys($this->themeConfig->getDefaultColors()),
                   array_values($this->themeConfig->getDefaultColors())),

                // Preview information
                'current_preview_theme' => $currentPreviewTheme,
                'original_theme' => $originalTheme,
                'preview_url' => "/theme-preview-render",
                'has_unsaved_changes' => $currentPreviewTheme !== $originalTheme,

                // Messages
                'message' => null,
                'error' => null,
            ];

            return $this->render('admin/theme/preview', $data, '/admin/theme/preview');

        } catch (\Exception $e) {
            error_log("Error in ThemePreviewController::preview: " . $e->getMessage());
            return $this->jsonError($this->translator->translate('theme.errors.internal'), [], 500);
        }
    }

    /**
     * Switch to different theme for preview (session-based)
     *
     * POST /admin/theme/switch
     *
     * @param ServerRequestInterface $request HTTP request
     * @return ResponseInterface HTTP response (JSON)
     */
    public function switch(ServerRequestInterface $request): ResponseInterface
    {
        // Verify authentication
        if (!$this->isAuthenticated()) {
            return $this->jsonError($this->translator->translate('theme.errors.unauthorized'), [], 401);
        }

        // Verify admin/moderator permissions
        if (!$this->hasAdminPermission()) {
            return $this->jsonError($this->translator->translate('theme.errors.forbidden'), [], 403);
        }

        try {
            // Get request body
            $body = (string)$request->getBody();
            $data = json_decode($body, true);

            if (!is_array($data) || !isset($data['theme'])) {
                return $this->jsonError($this->translator->translate('theme.errors.invalid_request'), [], 400);
            }

            $themeName = trim((string)$data['theme']);

            // Validate theme exists
            $themes = $this->getAvailableThemes();
            if (!in_array($themeName, $themes)) {
                return $this->jsonError($this->translator->translate('theme.errors.not_found'), [], 404);
            }

            // Store original theme on first switch
            if (!isset($_SESSION[self::SESSION_ORIGINAL_KEY])) {
                $_SESSION[self::SESSION_ORIGINAL_KEY] = $themeName;
            }

            // Switch preview theme
            $_SESSION[self::SESSION_PREVIEW_KEY] = $themeName;

            // Get theme metadata
            $metadata = $this->getThemeMetadata($themeName);

            return $this->jsonSuccess(
                $this->translator->translate('theme.switch_success'),
                [
                    'theme' => [
                        'id' => $themeName,
                        'name' => $metadata['name'] ?? $themeName,
                        'description' => $metadata['description'] ?? '',
                        'author' => $metadata['author'] ?? '',
                        'version' => $metadata['version'] ?? '1.0.0',
                        'thumbnail' => $this->getThemeThumbnailUrl($themeName),
                    ]
                ]
            );

        } catch (\Exception $e) {
            error_log("Error in ThemePreviewController::switch: " . $e->getMessage());
            return $this->jsonError($this->translator->translate('theme.errors.internal'), [], 500);
        }
    }

    /**
     * Apply selected preview theme permanently to account
     *
     * POST /admin/theme/apply
     *
     * @param ServerRequestInterface $request HTTP request
     * @return ResponseInterface HTTP response (JSON)
     */
    public function apply(ServerRequestInterface $request): ResponseInterface
    {
        // Verify authentication
        if (!$this->isAuthenticated()) {
            return $this->jsonError($this->translator->translate('theme.errors.unauthorized'), [], 401);
        }

        // Verify admin/moderator permissions
        if (!$this->hasAdminPermission()) {
            return $this->jsonError($this->translator->translate('theme.errors.forbidden'), [], 403);
        }

        try {
            // Get request body
            $body = (string)$request->getBody();
            $data = json_decode($body, true);

            if (!is_array($data)) {
                return $this->jsonError($this->translator->translate('theme.errors.invalid_request'), [], 400);
            }

            // Get current preview theme
            $previewTheme = $_SESSION[self::SESSION_PREVIEW_KEY] ?? null;

            if (!$previewTheme) {
                return $this->jsonError($this->translator->translate('theme.errors.no_preview'), [], 400);
            }

            // Validate theme exists
            $themes = $this->getAvailableThemes();
            if (!in_array($previewTheme, $themes)) {
                return $this->jsonError($this->translator->translate('theme.errors.not_found'), [], 404);
            }

            // Get user ID
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                return $this->jsonError($this->translator->translate('theme.errors.no_user'), [], 401);
            }

            // Save theme preference to user table
            $result = $this->db->update('users', [
                'theme_preference' => $previewTheme,
                'updated_at' => time()
            ], ['id' => $userId]);

            if ($result === false || $result <= 0) {
                return $this->jsonError($this->translator->translate('theme.errors.save_failed'), [], 500);
            }

            // Also save color/font configurations if provided
            if (isset($data['colors']) && is_array($data['colors'])) {
                foreach ($data['colors'] as $colorName => $colorValue) {
                    $this->themeConfig->set($colorName, $colorValue);
                }
            }

            if (isset($data['fonts']) && is_array($data['fonts'])) {
                foreach ($data['fonts'] as $fontName => $fontValue) {
                    $this->themeConfig->set($fontName, $fontValue);
                }
            }

            // Clear preview session data
            unset($_SESSION[self::SESSION_PREVIEW_KEY]);
            unset($_SESSION[self::SESSION_ORIGINAL_KEY]);

            // Update session theme
            $_SESSION['theme_preference'] = $previewTheme;

            return $this->jsonSuccess(
                $this->translator->translate('theme.apply_success'),
                ['theme' => $previewTheme]
            );

        } catch (\Exception $e) {
            error_log("Error in ThemePreviewController::apply: " . $e->getMessage());
            return $this->jsonError($this->translator->translate('theme.errors.internal'), [], 500);
        }
    }

    /**
     * Reset preview to original theme
     *
     * POST /admin/theme/reset-preview
     *
     * @param ServerRequestInterface $request HTTP request
     * @return ResponseInterface HTTP response (JSON)
     */
    public function resetPreview(ServerRequestInterface $request): ResponseInterface
    {
        // Verify authentication
        if (!$this->isAuthenticated()) {
            return $this->jsonError($this->translator->translate('theme.errors.unauthorized'), [], 401);
        }

        // Verify admin/moderator permissions
        if (!$this->hasAdminPermission()) {
            return $this->jsonError($this->translator->translate('theme.errors.forbidden'), [], 403);
        }

        try {
            // Get original theme
            $originalTheme = $_SESSION[self::SESSION_ORIGINAL_KEY] ?? 'iser';

            // Reset preview to original
            $_SESSION[self::SESSION_PREVIEW_KEY] = $originalTheme;

            return $this->jsonSuccess(
                $this->translator->translate('theme.reset_success'),
                ['theme' => $originalTheme]
            );

        } catch (\Exception $e) {
            error_log("Error in ThemePreviewController::resetPreview: " . $e->getMessage());
            return $this->jsonError($this->translator->translate('theme.errors.internal'), [], 500);
        }
    }

    /**
     * Get list of available themes
     *
     * @return array List of theme names
     */
    private function getAvailableThemes(): array
    {
        $themes = [];

        if (is_dir(self::THEMES_DIR)) {
            $items = scandir(self::THEMES_DIR);

            foreach ($items as $item) {
                if ($item === '.' || $item === '..' || $item === 'ThemeConfigurator.php') {
                    continue;
                }

                $themePath = self::THEMES_DIR . '/' . $item;

                // Check if it's a directory with proper theme structure
                if (is_dir($themePath)) {
                    // Check for version.php or theme config
                    if (file_exists($themePath . '/version.php') ||
                        file_exists($themePath . '/ThemeIser.php')) {
                        $themes[] = strtolower($item);
                    }
                }
            }
        }

        // Return default themes if none found
        return !empty($themes) ? array_unique($themes) : ['iser', 'default'];
    }

    /**
     * Get theme metadata
     *
     * @param string $themeName Theme name
     * @return array Theme metadata
     */
    private function getThemeMetadata(string $themeName): array
    {
        $themePath = self::THEMES_DIR . '/' . ucfirst($themeName);
        $versionFile = $themePath . '/version.php';

        $metadata = [
            'name' => ucfirst($themeName),
            'author' => 'ISER Development Team',
            'version' => '1.0.0',
            'description' => 'Theme for NexoSupport'
        ];

        // Load version file if exists
        if (file_exists($versionFile)) {
            $version = include $versionFile;
            if (is_array($version)) {
                $metadata = array_merge($metadata, $version);
            }
        }

        return $metadata;
    }

    /**
     * Get theme thumbnail URL
     *
     * @param string $themeName Theme name
     * @return string Thumbnail URL
     */
    private function getThemeThumbnailUrl(string $themeName): string
    {
        $baseUrl = $_ENV['APP_URL'] ?? '';
        return rtrim($baseUrl, '/') . "/theme/" . strtolower($themeName) . "/assets/images/thumbnail.png";
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
}
