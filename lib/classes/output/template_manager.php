<?php
namespace core\output;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Template Manager
 *
 * Manages Mustache template rendering with Moodle-style helpers.
 * Similar to Moodle's core_renderer template system.
 *
 * Features:
 * - Theme template overrides
 * - Moodle-compatible helpers (str, pix, js, quote, shortentext, userdate, uniqid, cleanstr)
 * - Automatic context injection
 * - Template caching
 *
 * @package    core\output
 * @copyright  NexoSupport
 * @license    Proprietary - NexoSupport
 */
class template_manager {

    /** @var \Mustache\Engine Mustache engine instance */
    private static ?\Mustache\Engine $engine = null;

    /** @var array Registered helpers */
    private static array $helpers = [];

    /** @var bool Whether helpers have been initialized */
    private static bool $helpers_initialized = false;

    /**
     * Get Mustache engine instance
     *
     * @return \Mustache\Engine
     */
    private static function get_engine(): \Mustache\Engine {
        if (self::$engine === null) {
            global $CFG;

            // Initialize helpers first
            self::init_helpers();

            $options = [
                'cache' => $CFG->cachedir . '/mustache',
                'escape' => function($value) {
                    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
                },
                'strict_callables' => true,
                'pragmas' => [\Mustache\Engine::PRAGMA_BLOCKS],
            ];

            // Create custom loader that uses template finder
            $options['loader'] = new mustache_filesystem_loader();
            $options['partials_loader'] = new mustache_filesystem_loader();

            // Create cache directory if not exists
            if (!file_exists($options['cache'])) {
                @mkdir($options['cache'], 0755, true);
            }

            self::$engine = new \Mustache\Engine($options);

            // Add all helpers
            foreach (self::$helpers as $name => $helper) {
                self::$engine->addHelper($name, $helper);
            }
        }

        return self::$engine;
    }

    /**
     * Initialize all Mustache helpers
     *
     * @return void
     */
    private static function init_helpers(): void {
        if (self::$helpers_initialized) {
            return;
        }

        // String helper (i18n)
        self::$helpers['str'] = function($text, $helper) {
            $text = $helper->render($text);
            $parts = array_map('trim', explode(',', $text));
            $identifier = $parts[0] ?? '';
            $component = $parts[1] ?? 'core';
            $param = $parts[2] ?? null;

            if (empty($identifier)) {
                return '';
            }

            return get_string($identifier, $component, $param);
        };

        // Pix icon helper
        self::$helpers['pix'] = new mustache_pix_helper();

        // JavaScript helper
        self::$helpers['js'] = new mustache_javascript_helper();

        // Quote helper (for JS strings)
        self::$helpers['quote'] = new mustache_quote_helper();

        // Shorten text helper
        self::$helpers['shortentext'] = new mustache_shortentext_helper();

        // User date helper
        self::$helpers['userdate'] = new mustache_userdate_helper();

        // Clean string helper
        self::$helpers['cleanstr'] = new mustache_cleanstr_helper();

        self::$helpers_initialized = true;
    }

    /**
     * Render a template
     *
     * @param string $templatename Template name (component/templatename)
     * @param array|object $context Data context for template
     * @return string Rendered HTML
     */
    public static function render(string $templatename, $context = []): string {
        $engine = self::get_engine();

        // Convert object to array if needed
        if (is_object($context)) {
            $context = self::object_to_array($context);
        }

        // Add common context variables
        $context = array_merge(self::get_common_context(), $context);

        // Add unique ID helper (fresh instance for each render)
        $context['uniqid'] = new mustache_uniqid_helper();

        try {
            $html = $engine->render($templatename, $context);

            // Append any collected JavaScript
            if (mustache_javascript_helper::has_javascript()) {
                $html .= mustache_javascript_helper::get_javascript_html();
            }

            return $html;
        } catch (\Exception $e) {
            debugging('Error rendering template ' . $templatename . ': ' . $e->getMessage(), DEBUG_DEVELOPER);
            return '<div class="alert alert-danger">Template error: ' . htmlspecialchars($templatename) . '</div>';
        }
    }

    /**
     * Render a template from string
     *
     * @param string $template Template string
     * @param array|object $context Data context
     * @return string Rendered HTML
     */
    public static function render_from_string(string $template, $context = []): string {
        $engine = self::get_engine();

        if (is_object($context)) {
            $context = self::object_to_array($context);
        }

        $context = array_merge(self::get_common_context(), $context);
        $context['uniqid'] = new mustache_uniqid_helper();

        try {
            return $engine->render($template, $context);
        } catch (\Exception $e) {
            debugging('Error rendering template from string: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return '<div class="alert alert-danger">Template error</div>';
        }
    }

    /**
     * Get common context variables
     *
     * @return array Common context data
     */
    private static function get_common_context(): array {
        global $CFG, $USER;

        return [
            'wwwroot' => $CFG->wwwroot ?? '',
            'sesskey' => sesskey(),
            'currentlang' => \core\string_manager::get_language(),
            'isloggedin' => !empty($USER->id),
            'userid' => $USER->id ?? 0,
            'userfullname' => isset($USER->firstname) ? ($USER->firstname . ' ' . ($USER->lastname ?? '')) : '',
            'isadmin' => function_exists('is_siteadmin') && !empty($USER->id) ? is_siteadmin($USER->id) : false,
            'debug' => !empty($CFG->debug),
        ];
    }

    /**
     * Convert object to array recursively
     *
     * @param mixed $data Data to convert
     * @return mixed Converted data
     */
    private static function object_to_array($data) {
        if (is_object($data)) {
            $data = get_object_vars($data);
        }

        if (is_array($data)) {
            return array_map([self::class, 'object_to_array'], $data);
        }

        return $data;
    }

    /**
     * Check if a template exists
     *
     * @param string $templatename Template name
     * @return bool True if template exists
     */
    public static function template_exists(string $templatename): bool {
        return mustache_template_finder::template_exists($templatename);
    }

    /**
     * Get template source code (for debugging)
     *
     * @param string $templatename Template name
     * @return string|null Template source or null if not found
     */
    public static function get_template_source(string $templatename): ?string {
        try {
            $filepath = mustache_template_finder::get_template_filepath($templatename);
            return file_get_contents($filepath);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Clear template cache
     *
     * @return void
     */
    public static function clear_cache(): void {
        global $CFG;

        $cachedir = $CFG->cachedir . '/mustache';

        if (file_exists($cachedir)) {
            self::delete_directory($cachedir);
            @mkdir($cachedir, 0755, true);
        }

        // Reset engine to force reload
        self::$engine = null;
    }

    /**
     * Delete directory recursively
     *
     * @param string $dir Directory path
     * @return void
     */
    private static function delete_directory(string $dir): void {
        if (!file_exists($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);

        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? self::delete_directory($path) : @unlink($path);
        }

        @rmdir($dir);
    }

    /**
     * Add a custom helper function
     *
     * @param string $name Helper name
     * @param callable $helper Helper function
     * @return void
     */
    public static function add_helper(string $name, callable $helper): void {
        self::$helpers[$name] = $helper;

        // If engine already exists, add helper to it
        if (self::$engine !== null) {
            self::$engine->addHelper($name, $helper);
        }
    }

    /**
     * Get list of all registered helpers
     *
     * @return array Helper names
     */
    public static function get_helper_names(): array {
        self::init_helpers();
        return array_keys(self::$helpers);
    }
}


/**
 * Custom Filesystem Loader that uses template finder
 *
 * @package    core\output
 */
class mustache_filesystem_loader implements \Mustache\Loader {

    /**
     * Load a template by name
     *
     * @param string $name Template name
     * @return string Template source
     */
    public function load($name): string {
        try {
            $filepath = mustache_template_finder::get_template_filepath($name);
            return file_get_contents($filepath);
        } catch (\Exception $e) {
            // Try legacy path as fallback
            $legacypath = BASE_DIR . '/templates/' . $name . '.mustache';

            if (file_exists($legacypath)) {
                return file_get_contents($legacypath);
            }

            throw new \Mustache\Exception\UnknownTemplateException($name);
        }
    }
}
