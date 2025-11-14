<?php

/**
 * ISER - Theme Manager
 *
 * Central manager for theme operations including loading, applying,
 * and managing theme settings and plugins.
 *
 * @package    ISER\Core\Theme
 * @category   Core
 * @author     ISER Development Team
 * @copyright  2024 ISER
 * @license    Proprietary
 * @version    1.0.0
 * @since      Week 5-6 - Theme System Implementation
 */

namespace ISER\Core\Theme;

use ISER\Core\Database\Database;
use ISER\Core\Utils\Logger;
use ISER\Plugin\PluginManager;

/**
 * ThemeManager Class
 *
 * Handles theme operations including:
 * - Loading theme settings
 * - Applying themes to templates
 * - CSS variable generation
 * - Theme validation
 * - Theme plugin support
 * - Fallback handling
 */
class ThemeManager
{
    /**
     * Database instance
     */
    private Database $db;

    /**
     * Plugin manager instance
     */
    private ?PluginManager $pluginManager;

    /**
     * Loaded theme settings
     */
    private ?array $themeSettings = null;

    /**
     * Active theme plugin
     */
    private ?array $activeThemePlugin = null;

    /**
     * CSS variables cache
     */
    private ?string $cssVariablesCache = null;

    /**
     * Default theme settings
     */
    private const DEFAULT_THEME = [
        'colors' => [
            'primary' => '#667eea',
            'secondary' => '#764ba2',
            'success' => '#10b981',
            'warning' => '#f59e0b',
            'danger' => '#ef4444',
            'info' => '#3b82f6',
            'light' => '#f8f9fa',
            'dark' => '#212529',
            'body_bg' => '#ffffff',
            'body_text' => '#212529',
            'link' => '#667eea',
            'border' => '#dee2e6'
        ],
        'typography' => [
            'font_family_base' => 'Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
            'font_family_heading' => 'Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
            'font_family_mono' => '"JetBrains Mono", "Fira Code", Consolas, monospace',
            'font_size_base' => '16px',
            'font_size_sm' => '14px',
            'font_size_lg' => '18px',
            'line_height_base' => '1.5',
            'headings' => [
                'h1' => '2.5rem',
                'h2' => '2rem',
                'h3' => '1.75rem',
                'h4' => '1.5rem',
                'h5' => '1.25rem',
                'h6' => '1rem'
            ]
        ],
        'layout' => [
            'sidebar_position' => 'left',
            'sidebar_width' => '280px',
            'content_max_width' => '1400px',
            'container_padding' => '20px',
            'border_radius' => '8px',
            'box_shadow' => '0 1px 3px rgba(0,0,0,0.12)'
        ],
        'branding' => [
            'logo_url' => '/assets/images/logo.png',
            'favicon_url' => '/assets/images/favicon.ico',
            'app_name' => 'NexoSupport',
            'tagline' => 'Professional Support System'
        ],
        'dark_mode' => [
            'enabled' => true,
            'auto_switch' => false,
            'switch_time_start' => '18:00',
            'switch_time_end' => '06:00'
        ]
    ];

    /**
     * Constructor
     *
     * @param Database $db Database instance
     * @param PluginManager|null $pluginManager Plugin manager instance (optional)
     */
    public function __construct(Database $db, ?PluginManager $pluginManager = null)
    {
        $this->db = $db;
        $this->pluginManager = $pluginManager;
    }

    /**
     * Get theme settings
     *
     * Loads settings from database, applies theme plugin overrides,
     * and falls back to defaults.
     *
     * @param bool $forceReload Force reload from database
     * @return array Theme settings
     */
    public function getThemeSettings(bool $forceReload = false): array
    {
        if ($this->themeSettings !== null && !$forceReload) {
            return $this->themeSettings;
        }

        // Load from database
        $dbSettings = $this->loadFromDatabase();

        // Merge with defaults
        $settings = $this->mergeWithDefaults($dbSettings);

        // Apply theme plugin overrides
        if ($this->pluginManager) {
            $settings = $this->applyThemePluginOverrides($settings);
        }

        // Validate settings
        $settings = $this->validateSettings($settings);

        $this->themeSettings = $settings;

        return $settings;
    }

    /**
     * Load settings from database
     *
     * @return array Settings from database
     */
    private function loadFromDatabase(): array
    {
        try {
            $rows = $this->db->select('theme_settings', [], 'id ASC');

            if (empty($rows)) {
                return [];
            }

            $settings = [];

            foreach ($rows as $row) {
                $category = $row['category'] ?? 'general';
                $key = $row['setting_key'];
                $value = $this->unserializeValue($row['setting_value'], $row['setting_type']);

                // Build nested array structure
                if (strpos($key, '.') !== false) {
                    $parts = explode('.', $key);
                    $current = &$settings;

                    foreach ($parts as $i => $part) {
                        if ($i === count($parts) - 1) {
                            $current[$part] = $value;
                        } else {
                            if (!isset($current[$part])) {
                                $current[$part] = [];
                            }
                            $current = &$current[$part];
                        }
                    }
                } else {
                    $settings[$key] = $value;
                }
            }

            Logger::system('Theme settings loaded from database', [
                'settings_count' => count($rows)
            ]);

            return $settings;

        } catch (\Exception $e) {
            Logger::error('Failed to load theme settings from database', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Merge database settings with defaults
     *
     * @param array $dbSettings Settings from database
     * @return array Merged settings
     */
    private function mergeWithDefaults(array $dbSettings): array
    {
        return array_replace_recursive(self::DEFAULT_THEME, $dbSettings);
    }

    /**
     * Apply theme plugin overrides
     *
     * @param array $settings Current settings
     * @return array Settings with plugin overrides
     */
    private function applyThemePluginOverrides(array $settings): array
    {
        try {
            // Get active theme plugins
            $themePlugins = $this->pluginManager->getByType('theme');

            if (empty($themePlugins)) {
                return $settings;
            }

            // Find enabled theme plugin with highest priority
            $activePlugin = null;
            $highestPriority = -1;

            foreach ($themePlugins as $plugin) {
                if (!empty($plugin['enabled']) && $plugin['enabled'] == 1) {
                    $priority = $plugin['priority'] ?? 0;
                    if ($priority > $highestPriority) {
                        $highestPriority = $priority;
                        $activePlugin = $plugin;
                    }
                }
            }

            if (!$activePlugin) {
                return $settings;
            }

            // Load theme plugin settings
            $manifest = json_decode($activePlugin['manifest'] ?? '{}', true);
            $themeConfig = $manifest['theme_config'] ?? [];

            if (!empty($themeConfig)) {
                $settings = array_replace_recursive($settings, $themeConfig);

                $this->activeThemePlugin = $activePlugin;

                Logger::info('Applied theme plugin overrides', [
                    'plugin' => $activePlugin['slug'],
                    'priority' => $highestPriority
                ]);
            }

            return $settings;

        } catch (\Exception $e) {
            Logger::error('Failed to apply theme plugin overrides', [
                'error' => $e->getMessage()
            ]);
            return $settings;
        }
    }

    /**
     * Validate theme settings
     *
     * Ensures all settings are valid and safe.
     *
     * @param array $settings Settings to validate
     * @return array Validated settings
     */
    private function validateSettings(array $settings): array
    {
        // Validate colors (must be valid hex or rgb)
        if (isset($settings['colors'])) {
            foreach ($settings['colors'] as $key => $color) {
                if (!$this->isValidColor($color)) {
                    $settings['colors'][$key] = self::DEFAULT_THEME['colors'][$key] ?? '#000000';
                    Logger::warning('Invalid color value, using default', [
                        'key' => $key,
                        'value' => $color
                    ]);
                }
            }
        }

        // Validate font sizes (must have unit)
        if (isset($settings['typography'])) {
            $fontSizeKeys = ['font_size_base', 'font_size_sm', 'font_size_lg'];
            foreach ($fontSizeKeys as $key) {
                if (isset($settings['typography'][$key])) {
                    if (!preg_match('/^\d+(\.\d+)?(px|rem|em|%)$/', $settings['typography'][$key])) {
                        $settings['typography'][$key] = self::DEFAULT_THEME['typography'][$key];
                        Logger::warning('Invalid font size, using default', ['key' => $key]);
                    }
                }
            }
        }

        // Validate sidebar position
        if (isset($settings['layout']['sidebar_position'])) {
            if (!in_array($settings['layout']['sidebar_position'], ['left', 'right'])) {
                $settings['layout']['sidebar_position'] = 'left';
            }
        }

        return $settings;
    }

    /**
     * Check if color is valid
     *
     * @param string $color Color to validate
     * @return bool True if valid
     */
    private function isValidColor(string $color): bool
    {
        // Hex color
        if (preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
            return true;
        }

        // RGB/RGBA
        if (preg_match('/^rgba?\(\s*\d+\s*,\s*\d+\s*,\s*\d+\s*(,\s*[\d.]+\s*)?\)$/', $color)) {
            return true;
        }

        return false;
    }

    /**
     * Generate CSS variables from theme settings
     *
     * @param bool $forDarkMode Generate for dark mode
     * @return string CSS variables declaration
     */
    public function generateCSSVariables(bool $forDarkMode = false): string
    {
        // Check cache
        $cacheKey = $forDarkMode ? 'dark' : 'light';
        if ($this->cssVariablesCache !== null) {
            return $this->cssVariablesCache;
        }

        $settings = $this->getThemeSettings();

        $css = $forDarkMode ? '[data-theme="dark"] {' : ':root {';
        $css .= "\n";

        // Colors
        if (isset($settings['colors'])) {
            foreach ($settings['colors'] as $key => $value) {
                $varName = '--color-' . str_replace('_', '-', $key);
                $css .= "  {$varName}: {$value};\n";

                // Generate variations
                if (!$forDarkMode) {
                    $lighter = $this->lightenColor($value, 20);
                    $darker = $this->darkenColor($value, 20);
                    $css .= "  {$varName}-light: {$lighter};\n";
                    $css .= "  {$varName}-dark: {$darker};\n";
                }
            }
        }

        // Typography
        if (isset($settings['typography'])) {
            foreach ($settings['typography'] as $key => $value) {
                if ($key === 'headings') {
                    foreach ($value as $h => $size) {
                        $css .= "  --font-size-{$h}: {$size};\n";
                    }
                } else {
                    $varName = '--' . str_replace('_', '-', $key);
                    $css .= "  {$varName}: {$value};\n";
                }
            }
        }

        // Layout
        if (isset($settings['layout'])) {
            foreach ($settings['layout'] as $key => $value) {
                $varName = '--layout-' . str_replace('_', '-', $key);
                $css .= "  {$varName}: {$value};\n";
            }
        }

        $css .= "}\n";

        // Dark mode specific colors
        if ($forDarkMode && isset($settings['dark_mode'])) {
            // Invert light/dark colors
            $css .= "\n/* Dark mode color inversions */\n";
            $css .= "[data-theme=\"dark\"] {\n";
            $css .= "  --color-body-bg: #1a1a1a;\n";
            $css .= "  --color-body-text: #f0f0f0;\n";
            $css .= "  --color-border: #404040;\n";
            $css .= "  --color-light: #2a2a2a;\n";
            $css .= "  --color-dark: #e0e0e0;\n";
            $css .= "}\n";
        }

        $this->cssVariablesCache = $css;

        return $css;
    }

    /**
     * Lighten a color
     *
     * @param string $color Hex color
     * @param int $percent Percentage to lighten (0-100)
     * @return string Lightened hex color
     */
    private function lightenColor(string $color, int $percent): string
    {
        $color = ltrim($color, '#');

        if (strlen($color) !== 6) {
            return $color;
        }

        $rgb = [
            hexdec(substr($color, 0, 2)),
            hexdec(substr($color, 2, 2)),
            hexdec(substr($color, 4, 2))
        ];

        foreach ($rgb as $i => $channel) {
            $rgb[$i] = min(255, $channel + (255 - $channel) * ($percent / 100));
        }

        return sprintf('#%02x%02x%02x', $rgb[0], $rgb[1], $rgb[2]);
    }

    /**
     * Darken a color
     *
     * @param string $color Hex color
     * @param int $percent Percentage to darken (0-100)
     * @return string Darkened hex color
     */
    private function darkenColor(string $color, int $percent): string
    {
        $color = ltrim($color, '#');

        if (strlen($color) !== 6) {
            return $color;
        }

        $rgb = [
            hexdec(substr($color, 0, 2)),
            hexdec(substr($color, 2, 2)),
            hexdec(substr($color, 4, 2))
        ];

        foreach ($rgb as $i => $channel) {
            $rgb[$i] = max(0, $channel - $channel * ($percent / 100));
        }

        return sprintf('#%02x%02x%02x', $rgb[0], $rgb[1], $rgb[2]);
    }

    /**
     * Get active theme plugin
     *
     * @return array|null Active theme plugin or null
     */
    public function getActiveThemePlugin(): ?array
    {
        if ($this->activeThemePlugin === null) {
            $this->getThemeSettings(); // This will load the active plugin
        }

        return $this->activeThemePlugin;
    }

    /**
     * Get CSS file path for theme
     *
     * Returns path to custom CSS file if theme plugin provides one.
     *
     * @return string|null CSS file path or null
     */
    public function getThemeCSS(): ?string
    {
        $plugin = $this->getActiveThemePlugin();

        if (!$plugin) {
            return null;
        }

        // Check if plugin has custom CSS
        $pluginPath = $plugin['path'] ?? null;
        if ($pluginPath) {
            $cssPath = $pluginPath . '/assets/css/theme.css';
            if (file_exists($cssPath)) {
                return $cssPath;
            }
        }

        return null;
    }

    /**
     * Get default theme settings
     *
     * @return array Default theme settings
     */
    public static function getDefaultSettings(): array
    {
        return self::DEFAULT_THEME;
    }

    /**
     * Clear theme cache
     *
     * Forces reload of theme settings on next access.
     */
    public function clearCache(): void
    {
        $this->themeSettings = null;
        $this->activeThemePlugin = null;
        $this->cssVariablesCache = null;

        Logger::system('Theme cache cleared');
    }

    /**
     * Unserialize value from database
     *
     * @param string $value Serialized value
     * @param string $type Value type
     * @return mixed Unserialized value
     */
    private function unserializeValue(string $value, string $type)
    {
        switch ($type) {
            case 'boolean':
                return $value === '1' || $value === 'true';

            case 'color':
            case 'font':
            case 'size':
            case 'url':
            case 'text':
                return $value;

            default:
                // Try JSON decode for arrays/objects
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $decoded;
                }
                return $value;
        }
    }

    /**
     * Check if dark mode should be active
     *
     * Based on user preference, auto-switch settings, and system time.
     *
     * @param int|null $userId User ID (for per-user preference)
     * @return bool True if dark mode should be active
     */
    public function isDarkModeActive(?int $userId = null): bool
    {
        $settings = $this->getThemeSettings();

        // Check if dark mode is globally enabled
        if (empty($settings['dark_mode']['enabled'])) {
            return false;
        }

        // Check user preference (if userId provided)
        if ($userId !== null) {
            // TODO: Implement per-user theme preference
            // For now, use session or cookie
            if (isset($_COOKIE['theme_mode'])) {
                return $_COOKIE['theme_mode'] === 'dark';
            }
        }

        // Check auto-switch
        if (!empty($settings['dark_mode']['auto_switch'])) {
            $startTime = $settings['dark_mode']['switch_time_start'] ?? '18:00';
            $endTime = $settings['dark_mode']['switch_time_end'] ?? '06:00';

            $now = date('H:i');

            if ($startTime > $endTime) {
                // Crosses midnight (e.g., 18:00 to 06:00)
                return $now >= $startTime || $now < $endTime;
            } else {
                // Same day (e.g., 12:00 to 18:00)
                return $now >= $startTime && $now < $endTime;
            }
        }

        return false;
    }
}
