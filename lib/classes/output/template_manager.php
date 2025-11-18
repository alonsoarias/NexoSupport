<?php
namespace core\output;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Template Manager
 *
 * Gestiona el renderizado de templates usando Mustache.
 * Similar a Moodle's core_renderer y template system.
 *
 * @package core\output
 */
class template_manager {

    /** @var \Mustache_Engine Mustache engine instance */
    private static ?\Mustache_Engine $engine = null;

    /** @var array Template cache */
    private static array $cache = [];

    /**
     * Get Mustache engine instance
     *
     * @return \Mustache_Engine
     */
    private static function get_engine(): \Mustache_Engine {
        if (self::$engine === null) {
            global $CFG;

            $options = [
                'cache' => $CFG->cachedir . '/mustache',
                'escape' => function($value) {
                    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                },
                'strict_callables' => true,
                'pragmas' => [\Mustache_Engine::PRAGMA_BLOCKS],
            ];

            // Add template loader
            $options['loader'] = new \Mustache_Loader_FilesystemLoader(
                BASE_DIR . '/templates',
                ['extension' => '.mustache']
            );

            // Add partials loader (for {{> partial }})
            $options['partials_loader'] = new \Mustache_Loader_FilesystemLoader(
                BASE_DIR . '/templates',
                ['extension' => '.mustache']
            );

            // Create cache directory if not exists
            if (!file_exists($options['cache'])) {
                mkdir($options['cache'], 0755, true);
            }

            self::$engine = new \Mustache_Engine($options);
        }

        return self::$engine;
    }

    /**
     * Render a template
     *
     * @param string $templatename Template name (component/templatename or core/templatename)
     * @param array|object $context Data context for template
     * @return string Rendered HTML
     */
    public static function render(string $templatename, $context = []): string {
        $engine = self::get_engine();

        // Convert object to array if needed
        if (is_object($context)) {
            $context = (array)$context;
        }

        // Add common context variables
        $context = array_merge([
            'wwwroot' => self::get_wwwroot(),
            'sesskey' => sesskey(),
            'currentlang' => \core\string_manager::get_language(),
        ], $context);

        // Add string helper for i18n
        $context['str'] = function($text) {
            // Parse {{#str}}identifier,component{{/str}}
            $parts = explode(',', trim($text));
            $identifier = trim($parts[0]);
            $component = isset($parts[1]) ? trim($parts[1]) : 'core';
            return get_string($identifier, $component);
        };

        try {
            return $engine->render($templatename, $context);
        } catch (\Exception $e) {
            debugging('Error rendering template ' . $templatename . ': ' . $e->getMessage());
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
            $context = (array)$context;
        }

        try {
            return $engine->render($template, $context);
        } catch (\Exception $e) {
            debugging('Error rendering template from string: ' . $e->getMessage());
            return '<div class="alert alert-danger">Template error</div>';
        }
    }

    /**
     * Check if a template exists
     *
     * @param string $templatename Template name
     * @return bool True if template exists
     */
    public static function template_exists(string $templatename): bool {
        $filepath = BASE_DIR . '/templates/' . $templatename . '.mustache';
        return file_exists($filepath);
    }

    /**
     * Get template source code (for debugging)
     *
     * @param string $templatename Template name
     * @return string|null Template source or null if not found
     */
    public static function get_template_source(string $templatename): ?string {
        $filepath = BASE_DIR . '/templates/' . $templatename . '.mustache';

        if (file_exists($filepath)) {
            return file_get_contents($filepath);
        }

        return null;
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
            mkdir($cachedir, 0755, true);
        }

        self::$cache = [];
    }

    /**
     * Get wwwroot URL
     *
     * @return string WWW root URL
     */
    private static function get_wwwroot(): string {
        global $CFG;
        return $CFG->wwwroot ?? '';
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
            is_dir($path) ? self::delete_directory($path) : unlink($path);
        }

        rmdir($dir);
    }

    /**
     * Add helper function to templates
     *
     * @param string $name Helper name
     * @param callable $helper Helper function
     * @return void
     */
    public static function add_helper(string $name, callable $helper): void {
        $engine = self::get_engine();
        $engine->addHelper($name, $helper);
    }
}
