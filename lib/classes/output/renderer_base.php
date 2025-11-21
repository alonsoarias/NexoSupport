<?php
namespace core\output;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Renderer Base Class
 *
 * Base class for all renderers. Provides template rendering and renderable support.
 * Similar to Moodle's renderer_base.
 *
 * @package    core\output
 * @copyright  NexoSupport
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer_base {

    /** @var page Page object */
    protected $page;

    /** @var string Target for output (usually 'standard' or 'AJAX') */
    protected $target;

    /**
     * Constructor
     *
     * @param page|null $page The page object
     * @param string $target Target for rendering
     */
    public function __construct(?page $page = null, string $target = 'standard') {
        $this->page = $page ?? new page();
        $this->target = $target;
    }

    /**
     * Get the page object
     *
     * @return page
     */
    public function get_page(): page {
        return $this->page;
    }

    /**
     * Render a renderable object
     *
     * This method will:
     * 1. Look for a render_{classname}() method on this renderer
     * 2. If the object implements named_templatable, use its template
     * 3. If the object implements templatable, try to deduce the template name
     *
     * @param renderable $widget The object to render
     * @return string The rendered HTML
     */
    public function render(renderable $widget): string {
        // Get the short class name (without namespace)
        $classname = get_class($widget);
        if (($pos = strrpos($classname, '\\')) !== false) {
            $classname = substr($classname, $pos + 1);
        }

        // Check for a specific render method
        $rendermethod = 'render_' . $classname;
        if (method_exists($this, $rendermethod)) {
            return $this->$rendermethod($widget);
        }

        // Check for named_templatable interface
        if ($widget instanceof named_templatable) {
            $templatename = $widget->get_template_name($this);
            return $this->render_from_template($templatename, $widget->export_for_template($this));
        }

        // Check for templatable interface
        if ($widget instanceof templatable) {
            // Try to deduce template name from class name
            $component = $this->get_component_from_classname(get_class($widget));
            $templatename = $component . '/' . $classname;
            return $this->render_from_template($templatename, $widget->export_for_template($this));
        }

        throw new \coding_exception("Unable to render object of class: " . get_class($widget));
    }

    /**
     * Render a Mustache template with the given context
     *
     * @param string $templatename The template name (e.g., 'core/notification')
     * @param mixed $context The data context (stdClass or array)
     * @return string The rendered HTML
     */
    public function render_from_template(string $templatename, $context): string {
        return template_manager::render($templatename, $context);
    }

    /**
     * Shorthand for rendering notifications
     *
     * @param string $message The notification message
     * @param string $type The notification type (success, error, warning, info)
     * @return string The rendered HTML
     */
    public function notification(string $message, string $type = 'info'): string {
        return $this->render_from_template('core/notification', [
            'message' => $message,
            'type' => $type,
            'is' . $type => true,
        ]);
    }

    /**
     * Render a pix icon
     *
     * @param string $icon The icon identifier
     * @param string $component The component (e.g., 'core', 'mod_forum')
     * @param string $alt Alternative text
     * @param array $attributes Additional HTML attributes
     * @return string The rendered HTML
     */
    public function pix_icon(string $icon, string $component = 'core', string $alt = '', array $attributes = []): string {
        global $CFG;

        // Build icon path
        $iconpath = $this->get_icon_path($icon, $component);

        // Build attributes
        $attrs = array_merge([
            'src' => $iconpath,
            'alt' => $alt,
            'class' => 'icon',
            'role' => empty($alt) ? 'presentation' : 'img',
        ], $attributes);

        $html = '<img';
        foreach ($attrs as $name => $value) {
            $html .= ' ' . $name . '="' . htmlspecialchars($value) . '"';
        }
        $html .= '>';

        return $html;
    }

    /**
     * Get the path to an icon
     *
     * @param string $icon Icon name
     * @param string $component Component name
     * @return string URL to the icon
     */
    protected function get_icon_path(string $icon, string $component = 'core'): string {
        global $CFG;

        // Check theme first
        $themeiconpath = $CFG->dirroot . '/theme/' . ($CFG->theme ?? 'standard') . '/pix/' . $icon . '.svg';
        if (file_exists($themeiconpath)) {
            return $CFG->wwwroot . '/theme/' . ($CFG->theme ?? 'standard') . '/pix/' . $icon . '.svg';
        }

        // Check component pix directory
        if ($component === 'core') {
            $componentpath = $CFG->dirroot . '/pix/' . $icon . '.svg';
            $componenturl = $CFG->wwwroot . '/pix/' . $icon . '.svg';
        } else {
            $parts = explode('_', $component, 2);
            $type = $parts[0];
            $name = $parts[1] ?? $type;
            $componentpath = $CFG->dirroot . '/' . $type . '/' . $name . '/pix/' . $icon . '.svg';
            $componenturl = $CFG->wwwroot . '/' . $type . '/' . $name . '/pix/' . $icon . '.svg';
        }

        if (file_exists($componentpath)) {
            return $componenturl;
        }

        // Fallback to PNG
        $pngpath = str_replace('.svg', '.png', $componentpath);
        $pngurl = str_replace('.svg', '.png', $componenturl);
        if (file_exists($pngpath)) {
            return $pngurl;
        }

        // Return default icon
        return $CFG->wwwroot . '/pix/i/icon.svg';
    }

    /**
     * Get component name from class name
     *
     * @param string $classname Full class name with namespace
     * @return string Component name
     */
    protected function get_component_from_classname(string $classname): string {
        // Extract component from namespace
        // e.g., 'mod_forum\output\post' -> 'mod_forum'
        // e.g., 'core\output\notification' -> 'core'
        $parts = explode('\\', $classname);

        if (count($parts) >= 2) {
            return $parts[0];
        }

        return 'core';
    }

    /**
     * Add JavaScript to be executed after the page loads
     *
     * @param string $code JavaScript code
     * @return void
     */
    public function add_js_call(string $code): void {
        $this->page->require_js_code($code);
    }

    /**
     * Require an AMD module
     *
     * @param string $module Module name (e.g., 'core/modal')
     * @param string $function Function to call
     * @param array $params Parameters to pass
     * @return void
     */
    public function require_amd(string $module, string $function = 'init', array $params = []): void {
        $paramsJson = json_encode($params);
        $code = "require(['{$module}'], function(M) { M.{$function}.apply(null, {$paramsJson}); });";
        $this->add_js_call($code);
    }
}
