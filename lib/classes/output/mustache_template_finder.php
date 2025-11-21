<?php
namespace core\output;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Mustache Template Finder
 *
 * Finds templates with support for theme overrides.
 * Similar to Moodle's mustache_template_finder.
 *
 * Search order:
 * 1. /theme/{current_theme}/templates/{component}/
 * 2. /theme/{parent_theme}/templates/{component}/ (if parent exists)
 * 3. /{component_path}/templates/
 *
 * @package    core\output
 * @copyright  NexoSupport
 * @license    Proprietary - NexoSupport
 */
class mustache_template_finder {

    /**
     * Get the full path to a template file
     *
     * @param string $name Template name (e.g., 'core/notification' or 'mod_forum/post')
     * @param string $themename Theme name (optional, uses current theme if empty)
     * @return string Full path to the template file
     * @throws \nexo_exception If template not found
     */
    public static function get_template_filepath(string $name, string $themename = ''): string {
        global $CFG;

        // Get theme name
        if (empty($themename)) {
            $themename = $CFG->theme ?? 'standard';
        }

        // Parse template name into component and template
        list($component, $templatename) = self::parse_template_name($name);

        // Get directories to search
        $directories = self::get_template_directories_for_component($component, $themename);

        // Search for template
        foreach ($directories as $dir) {
            $filepath = $dir . '/' . $templatename . '.mustache';
            if (file_exists($filepath)) {
                return $filepath;
            }
        }

        throw new \nexo_exception('templatenotfound', 'core', '', $name);
    }

    /**
     * Get all directories where templates for a component might be found
     *
     * @param string $component Component name (e.g., 'core', 'mod_forum')
     * @param string $themename Theme name
     * @return array Array of directory paths
     */
    public static function get_template_directories_for_component(string $component, string $themename = ''): array {
        global $CFG;

        $directories = [];

        // Get theme name
        if (empty($themename)) {
            $themename = $CFG->theme ?? 'standard';
        }

        // 1. Current theme templates
        $themedir = $CFG->dirroot . '/theme/' . $themename . '/templates';
        if ($component !== 'core') {
            $directories[] = $themedir . '/' . $component;
        } else {
            $directories[] = $themedir . '/core';
        }

        // 2. Parent theme templates (if any)
        $parentthemes = self::get_parent_themes($themename);
        foreach ($parentthemes as $parent) {
            $parentdir = $CFG->dirroot . '/theme/' . $parent . '/templates';
            if ($component !== 'core') {
                $directories[] = $parentdir . '/' . $component;
            } else {
                $directories[] = $parentdir . '/core';
            }
        }

        // 3. Component's own templates directory
        $componentdir = self::get_component_directory($component);
        if ($componentdir !== null) {
            $directories[] = $componentdir . '/templates';
        }

        // 4. Core templates directory (fallback)
        $directories[] = $CFG->dirroot . '/templates/' . $component;

        // 5. Legacy templates directory (without component subfolder)
        $directories[] = $CFG->dirroot . '/templates';

        return array_filter($directories, 'is_dir');
    }

    /**
     * Parse template name into component and template parts
     *
     * @param string $name Template name
     * @return array [component, templatename]
     */
    public static function parse_template_name(string $name): array {
        $parts = explode('/', $name, 2);

        if (count($parts) === 2) {
            return [$parts[0], $parts[1]];
        }

        // No component specified, assume core
        return ['core', $parts[0]];
    }

    /**
     * Get parent themes for a theme
     *
     * @param string $themename Theme name
     * @return array Array of parent theme names
     */
    public static function get_parent_themes(string $themename): array {
        global $CFG;

        $parents = [];
        $configfile = $CFG->dirroot . '/theme/' . $themename . '/config.php';

        if (!file_exists($configfile)) {
            return $parents;
        }

        // Load theme config
        $THEME = new \stdClass();
        include($configfile);

        if (!empty($THEME->parents)) {
            $parents = $THEME->parents;

            // Recursively get parents of parents
            foreach ($THEME->parents as $parent) {
                $parentparents = self::get_parent_themes($parent);
                $parents = array_merge($parents, $parentparents);
            }
        }

        return array_unique($parents);
    }

    /**
     * Get the directory for a component
     *
     * @param string $component Component name
     * @return string|null Directory path or null if not found
     */
    public static function get_component_directory(string $component): ?string {
        global $CFG;

        if ($component === 'core') {
            return $CFG->dirroot . '/lib';
        }

        // Parse component name (e.g., 'mod_forum' -> type='mod', name='forum')
        $parts = explode('_', $component, 2);
        $type = $parts[0];
        $name = $parts[1] ?? null;

        if ($name === null) {
            // Single-word component, check if it's a plugin type
            $typedir = $CFG->dirroot . '/' . $type;
            if (is_dir($typedir)) {
                return $typedir;
            }
            return null;
        }

        // Build path based on plugin type
        $plugindir = $CFG->dirroot . '/' . $type . '/' . $name;
        if (is_dir($plugindir)) {
            return $plugindir;
        }

        return null;
    }

    /**
     * Check if a template exists
     *
     * @param string $name Template name
     * @param string $themename Theme name (optional)
     * @return bool True if template exists
     */
    public static function template_exists(string $name, string $themename = ''): bool {
        try {
            self::get_template_filepath($name, $themename);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get all available templates for a component
     *
     * @param string $component Component name
     * @param string $themename Theme name (optional)
     * @return array Array of template names
     */
    public static function get_templates_for_component(string $component, string $themename = ''): array {
        $directories = self::get_template_directories_for_component($component, $themename);
        $templates = [];

        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                continue;
            }

            $files = glob($dir . '/*.mustache');
            foreach ($files as $file) {
                $name = basename($file, '.mustache');
                if (!in_array($name, $templates)) {
                    $templates[] = $name;
                }
            }
        }

        sort($templates);
        return $templates;
    }
}
