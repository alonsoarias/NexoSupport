<?php
namespace core\navigation;

defined('NEXOSUPPORT_INTERNAL') || die();

use core\output\renderer_base;

/**
 * Sidebar Navigation Renderer
 *
 * Renders the sidebar navigation tree with categories, items, and separators.
 * Follows ISER branding guidelines.
 *
 * @package core\navigation
 */
class sidebar_navigation_renderer extends renderer_base {

    /**
     * Render the sidebar navigation
     *
     * @param navigation_tree $nav
     * @return string HTML
     */
    public function render(navigation_tree $nav): string {
        $data = $nav->to_array();

        if (empty($data['nodes'])) {
            return '';
        }

        $html = '<nav class="nexo-sidebar-nav" role="navigation" aria-label="' . get_string('sidebarnavigation', 'core') . '">';
        $html .= '<ul class="nexo-sidebar-list">';

        foreach ($data['nodes'] as $node) {
            $html .= $this->render_node($node);
        }

        $html .= '</ul>';
        $html .= '</nav>';

        return $html;
    }

    /**
     * Render a single navigation node
     *
     * @param array $node
     * @param int $level Nesting level
     * @return string HTML
     */
    protected function render_node(array $node, int $level = 0): string {
        if (!($node['visible'] ?? true)) {
            return '';
        }

        // Handle separator
        if ($node['is_separator'] ?? false) {
            return '<li class="nexo-sidebar-separator"></li>';
        }

        // Handle category
        if ($node['is_category'] ?? false) {
            return $this->render_category($node, $level);
        }

        // Handle item
        return $this->render_item($node, $level);
    }

    /**
     * Render a category node
     *
     * @param array $node
     * @param int $level
     * @return string HTML
     */
    protected function render_category(array $node, int $level): string {
        $expandedClass = !empty($node['expanded']) ? ' expanded' : '';
        $key = htmlspecialchars($node['key'] ?? '');

        $html = '<li class="nexo-sidebar-category' . $expandedClass . '" data-key="' . $key . '">';

        // Category header
        $html .= '<div class="nexo-sidebar-category-header" role="button" tabindex="0" aria-expanded="' . (!empty($node['expanded']) ? 'true' : 'false') . '">';

        // Icon
        if (!empty($node['icon_class'])) {
            $html .= '<i class="' . htmlspecialchars($node['icon_class']) . '" aria-hidden="true"></i>';
        } elseif (!empty($node['icon_emoji'])) {
            $html .= '<span class="icon-emoji" aria-hidden="true">' . htmlspecialchars($node['icon_emoji']) . '</span>';
        }

        // Text
        $html .= '<span class="nexo-sidebar-category-text">' . htmlspecialchars($node['text'] ?? '') . '</span>';

        // Arrow
        $html .= '<i class="fas fa-chevron-down nexo-sidebar-category-arrow" aria-hidden="true"></i>';

        $html .= '</div>';

        // Children
        if (!empty($node['children'])) {
            $html .= '<ul class="nexo-sidebar-category-items">';
            foreach ($node['children'] as $child) {
                $html .= $this->render_node($child, $level + 1);
            }
            $html .= '</ul>';
        }

        $html .= '</li>';

        return $html;
    }

    /**
     * Render an item node
     *
     * @param array $node
     * @param int $level
     * @return string HTML
     */
    protected function render_item(array $node, int $level): string {
        $activeClass = !empty($node['active']) ? ' active' : '';
        $key = htmlspecialchars($node['key'] ?? '');
        $url = htmlspecialchars($node['url'] ?? '#');

        $html = '<li class="nexo-sidebar-item' . $activeClass . '" data-key="' . $key . '">';
        $html .= '<a href="' . $url . '"';

        if (!empty($node['active'])) {
            $html .= ' aria-current="page"';
        }

        $html .= '>';

        // Icon
        if (!empty($node['icon_class'])) {
            $html .= '<i class="' . htmlspecialchars($node['icon_class']) . '" aria-hidden="true"></i>';
        } elseif (!empty($node['icon_emoji'])) {
            $html .= '<span class="icon-emoji" aria-hidden="true">' . htmlspecialchars($node['icon_emoji']) . '</span>';
        }

        // Text
        $html .= '<span class="nexo-sidebar-item-text">' . htmlspecialchars($node['text'] ?? '') . '</span>';

        // Badge (if present)
        if (!empty($node['data_badge'])) {
            $badgeType = $node['data_badge_type'] ?? 'default';
            $html .= '<span class="nexo-sidebar-badge nexo-badge-' . htmlspecialchars($badgeType) . '">' .
                     htmlspecialchars($node['data_badge']) . '</span>';
        }

        $html .= '</a>';
        $html .= '</li>';

        return $html;
    }
}
