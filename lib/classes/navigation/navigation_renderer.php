<?php
namespace core\navigation;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Navigation Renderer
 *
 * Renders navigation trees in various formats using Mustache templates.
 * Supports sidebar, breadcrumbs, and other navigation layouts.
 *
 * @package core\navigation
 */
class navigation_renderer {

    /** Render styles */
    const STYLE_SIDEBAR = 'sidebar';
    const STYLE_BREADCRUMBS = 'breadcrumbs';
    const STYLE_HORIZONTAL = 'horizontal';

    /**
     * Render navigation tree
     *
     * @param navigation_tree $tree Navigation tree
     * @param string $style Render style
     * @param array $options Additional options
     * @return string HTML
     */
    public function render(navigation_tree $tree, string $style = self::STYLE_SIDEBAR, array $options = []): string {
        switch ($style) {
            case self::STYLE_SIDEBAR:
                return $this->render_sidebar($tree, $options);

            case self::STYLE_BREADCRUMBS:
                return $this->render_breadcrumbs($tree, $options);

            case self::STYLE_HORIZONTAL:
                return $this->render_horizontal($tree, $options);

            default:
                debugging("Unknown navigation style: $style", DEBUG_DEVELOPER);
                return '';
        }
    }

    /**
     * Render sidebar navigation
     *
     * @param navigation_tree $tree
     * @param array $options
     * @return string HTML
     */
    private function render_sidebar(navigation_tree $tree, array $options): string {
        $context = $tree->to_array();

        // Add options
        $context['collapsible'] = $options['collapsible'] ?? true;
        $context['show_icons'] = $options['show_icons'] ?? true;
        $context['compact'] = $options['compact'] ?? false;

        // Add strings
        $context['strings'] = $this->get_navigation_strings();

        return render_template('navigation/sidebar', $context);
    }

    /**
     * Render breadcrumbs
     *
     * @param navigation_tree $tree
     * @param array $options
     * @return string HTML
     */
    private function render_breadcrumbs(navigation_tree $tree, array $options): string {
        $breadcrumbs = $tree->get_breadcrumbs();

        if (empty($breadcrumbs)) {
            return '';
        }

        $context = [
            'breadcrumbs' => array_map(fn($node) => $node->to_array(false), $breadcrumbs),
            'has_breadcrumbs' => true,
            'show_home' => $options['show_home'] ?? true,
            'separator' => $options['separator'] ?? '/',
            'strings' => $this->get_navigation_strings(),
        ];

        return render_template('navigation/breadcrumbs', $context);
    }

    /**
     * Render horizontal navigation
     *
     * @param navigation_tree $tree
     * @param array $options
     * @return string HTML
     */
    private function render_horizontal(navigation_tree $tree, array $options): string {
        $context = $tree->to_array();

        // Add options
        $context['show_icons'] = $options['show_icons'] ?? true;
        $context['dropdowns'] = $options['dropdowns'] ?? true;

        // Add strings
        $context['strings'] = $this->get_navigation_strings();

        return render_template('navigation/horizontal', $context);
    }

    /**
     * Get navigation-related strings
     *
     * @return object
     */
    private function get_navigation_strings(): object {
        return (object)[
            'home' => get_string('home', 'core'),
            'navigation' => get_string('navigation', 'core'),
            'collapse' => get_string('collapse', 'core'),
            'expand' => get_string('expand', 'core'),
            'breadcrumbs' => get_string('breadcrumbs', 'core'),
        ];
    }

    /**
     * Render just the navigation HTML (for AJAX updates)
     *
     * @param navigation_tree $tree
     * @return string HTML
     */
    public function render_navigation_only(navigation_tree $tree): string {
        return $this->render_sidebar($tree, []);
    }
}
