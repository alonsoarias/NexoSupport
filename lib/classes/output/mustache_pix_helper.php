<?php
namespace core\output;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Mustache Pix Helper
 *
 * Renders pix icons in Mustache templates.
 * Usage: {{#pix}}icon_name, component, alt_text{{/pix}}
 *
 * Examples:
 *   {{#pix}}t/edit, core{{/pix}}
 *   {{#pix}}t/delete, core, Delete this item{{/pix}}
 *   {{#pix}}icon, mod_forum{{/pix}}
 *
 * @package    core\output
 * @copyright  NexoSupport
 * @license    Proprietary - NexoSupport
 */
class mustache_pix_helper {

    /**
     * Render a pix icon
     *
     * @param string $text The helper arguments
     * @param \Mustache_LambdaHelper $helper The lambda helper
     * @return string Rendered icon HTML
     */
    public function __invoke(string $text, \Mustache_LambdaHelper $helper): string {
        global $CFG;

        // Render any Mustache variables in the text first
        $text = $helper->render($text);

        // Parse arguments: icon, component, alt
        $parts = array_map('trim', explode(',', $text));

        $icon = $parts[0] ?? 'i/icon';
        $component = $parts[1] ?? 'core';
        $alt = $parts[2] ?? '';

        return $this->render_icon($icon, $component, $alt);
    }

    /**
     * Render an icon to HTML
     *
     * @param string $icon Icon identifier
     * @param string $component Component name
     * @param string $alt Alternative text
     * @return string HTML
     */
    protected function render_icon(string $icon, string $component, string $alt): string {
        global $CFG;

        // Get icon URL
        $iconurl = $this->get_icon_url($icon, $component);

        // Build HTML
        $attributes = [
            'src' => $iconurl,
            'alt' => $alt,
            'class' => 'icon',
        ];

        if (empty($alt)) {
            $attributes['role'] = 'presentation';
            $attributes['aria-hidden'] = 'true';
        }

        $html = '<img';
        foreach ($attributes as $name => $value) {
            $html .= ' ' . $name . '="' . htmlspecialchars($value) . '"';
        }
        $html .= '>';

        return $html;
    }

    /**
     * Get URL for an icon
     *
     * @param string $icon Icon identifier
     * @param string $component Component name
     * @return string URL
     */
    protected function get_icon_url(string $icon, string $component): string {
        global $CFG;

        // Normalize component
        $component = trim($component);
        if (empty($component)) {
            $component = 'core';
        }

        // Determine base path
        if ($component === 'core') {
            $basepath = $CFG->dirroot . '/pix';
            $baseurl = $CFG->wwwroot . '/pix';
        } else {
            // Parse component (e.g., 'mod_forum' -> 'mod/forum')
            $parts = explode('_', $component, 2);
            $type = $parts[0];
            $name = $parts[1] ?? $type;
            $basepath = $CFG->dirroot . '/' . $type . '/' . $name . '/pix';
            $baseurl = $CFG->wwwroot . '/' . $type . '/' . $name . '/pix';
        }

        // Try SVG first, then PNG
        foreach (['.svg', '.png', '.gif'] as $ext) {
            $filepath = $basepath . '/' . $icon . $ext;
            if (file_exists($filepath)) {
                return $baseurl . '/' . $icon . $ext;
            }
        }

        // Fallback to default icon
        return $CFG->wwwroot . '/pix/i/icon.svg';
    }
}
