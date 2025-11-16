<?php
/**
 * NexoSupport - Theme Manager
 *
 * @package    core
 * @copyright  2024 ISER
 * @license    Proprietary
 */

namespace ISER\Core\Theme;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Theme Manager
 *
 * Manages theme installation, activation, and configuration
 */
class ThemeManager
{
    /** @var string Active theme */
    private static $active_theme = 'core';

    /** @var array Theme cache */
    private static $themes = null;

    /** @var string Themes directory */
    private static $themes_dir = null;

    /**
     * Initialize theme manager
     */
    public static function init(): void
    {
        self::$themes_dir = __DIR__ . '/../../../theme';

        // Load active theme from config or session
        if (isset($_SESSION['active_theme'])) {
            self::$active_theme = $_SESSION['active_theme'];
        } elseif (defined('DEFAULT_THEME')) {
            self::$active_theme = DEFAULT_THEME;
        }
    }

    /**
     * Get active theme
     *
     * @return string Theme name
     */
    public static function get_active_theme(): string
    {
        return self::$active_theme;
    }

    /**
     * Set active theme
     *
     * @param string $theme Theme name
     * @return bool Success
     */
    public static function set_active_theme(string $theme): bool
    {
        $available = self::get_available_themes();

        if (!isset($available[$theme])) {
            return false;
        }

        self::$active_theme = $theme;
        $_SESSION['active_theme'] = $theme;

        return true;
    }

    /**
     * Get available themes
     *
     * @return array Themes array
     */
    public static function get_available_themes(): array
    {
        if (self::$themes !== null) {
            return self::$themes;
        }

        self::$themes = [];

        if (!is_dir(self::$themes_dir)) {
            return self::$themes;
        }

        $dirs = scandir(self::$themes_dir);

        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..') {
                continue;
            }

            $theme_path = self::$themes_dir . '/' . $dir;

            if (!is_dir($theme_path)) {
                continue;
            }

            // Check for version.php
            $version_file = $theme_path . '/version.php';
            if (!file_exists($version_file)) {
                continue;
            }

            // Load theme info
            $plugin = null;
            require $version_file;

            if ($plugin && isset($plugin->component)) {
                $theme_name = str_replace('theme_', '', $plugin->component);

                self::$themes[$theme_name] = [
                    'name' => $theme_name,
                    'title' => self::get_theme_title($theme_name),
                    'description' => $plugin->description ?? '',
                    'version' => $plugin->version ?? 0,
                    'release' => $plugin->release ?? '1.0.0',
                    'path' => $theme_path,
                    'component' => $plugin->component,
                ];
            }
        }

        return self::$themes;
    }

    /**
     * Get theme info
     *
     * @param string $theme Theme name
     * @return array|null Theme info
     */
    public static function get_theme_info(string $theme): ?array
    {
        $themes = self::get_available_themes();
        return $themes[$theme] ?? null;
    }

    /**
     * Get theme title
     *
     * @param string $theme Theme name
     * @return string Theme title
     */
    public static function get_theme_title(string $theme): string
    {
        $lib_file = self::$themes_dir . '/' . $theme . '/lib.php';

        if (file_exists($lib_file)) {
            require_once $lib_file;

            $func = 'theme_' . $theme . '_get_title';
            if (function_exists($func)) {
                return $func();
            }
        }

        return ucfirst($theme);
    }

    /**
     * Get theme config
     *
     * @param string $theme Theme name
     * @return array Theme configuration
     */
    public static function get_theme_config(string $theme): array
    {
        $config_file = self::$themes_dir . '/' . $theme . '/config.php';

        if (file_exists($config_file)) {
            return require $config_file;
        }

        return [];
    }

    /**
     * Get theme CSS files
     *
     * @param string $theme Theme name
     * @return array CSS files
     */
    public static function get_theme_css(string $theme): array
    {
        $styles_dir = self::$themes_dir . '/' . $theme . '/styles';
        $css_files = [];

        if (!is_dir($styles_dir)) {
            return $css_files;
        }

        $files = glob($styles_dir . '/*.css');

        foreach ($files as $file) {
            $css_files[] = [
                'file' => basename($file),
                'path' => $file,
                'url' => '/theme/' . $theme . '/styles/' . basename($file),
            ];
        }

        return $css_files;
    }

    /**
     * Get theme JS files
     *
     * @param string $theme Theme name
     * @return array JS files
     */
    public static function get_theme_js(string $theme): array
    {
        $scripts_dir = self::$themes_dir . '/' . $theme . '/scripts';
        $js_files = [];

        if (!is_dir($scripts_dir)) {
            return $js_files;
        }

        $files = glob($scripts_dir . '/*.js');

        foreach ($files as $file) {
            $js_files[] = [
                'file' => basename($file),
                'path' => $file,
                'url' => '/theme/' . $theme . '/scripts/' . basename($file),
            ];
        }

        return $js_files;
    }

    /**
     * Load template
     *
     * @param string $template Template name
     * @param array $data Template data
     * @param string|null $theme Theme name (null for active)
     * @return string Rendered template
     */
    public static function load_template(string $template, array $data = [], ?string $theme = null): string
    {
        $theme = $theme ?? self::$active_theme;
        $template_file = self::$themes_dir . '/' . $theme . '/templates/' . $template;

        if (!file_exists($template_file)) {
            return "<!-- Template not found: $template -->";
        }

        $engine = new MustacheEngine();
        return $engine->render($template_file, $data);
    }

    /**
     * Get theme layouts
     *
     * @param string $theme Theme name
     * @return array Layouts
     */
    public static function get_theme_layouts(string $theme): array
    {
        $lib_file = self::$themes_dir . '/' . $theme . '/lib.php';

        if (file_exists($lib_file)) {
            require_once $lib_file;

            $func = 'theme_' . $theme . '_get_layouts';
            if (function_exists($func)) {
                return $func();
            }
        }

        return [];
    }

    /**
     * Compile and minify CSS (simplified)
     *
     * @param string $theme Theme name
     * @return string Compiled CSS path
     */
    public static function compile_css(string $theme): string
    {
        // In a full implementation, this would:
        // 1. Combine all CSS files
        // 2. Minify CSS
        // 3. Generate source maps
        // 4. Cache the result

        // For now, just return main CSS path
        return '/theme/' . $theme . '/styles/main.css';
    }

    /**
     * Clear theme cache
     *
     * @return void
     */
    public static function clear_cache(): void
    {
        self::$themes = null;
    }

    /**
     * Get theme color schemes (for ISER theme)
     *
     * @param string $theme Theme name
     * @return array Color schemes
     */
    public static function get_color_schemes(string $theme): array
    {
        $lib_file = self::$themes_dir . '/' . $theme . '/lib.php';

        if (file_exists($lib_file)) {
            require_once $lib_file;

            $func = 'theme_' . $theme . '_get_color_schemes';
            if (function_exists($func)) {
                return $func();
            }
        }

        return [];
    }
}

// Initialize theme manager
ThemeManager::init();
