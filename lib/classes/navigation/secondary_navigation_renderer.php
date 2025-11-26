<?php
namespace core\navigation;

defined('NEXOSUPPORT_INTERNAL') || die();

use core\output\renderer_base;

/**
 * Secondary Navigation Renderer
 *
 * Renders the secondary navigation (contextual tabs) using HTML.
 *
 * @package core\navigation
 */
class secondary_navigation_renderer extends renderer_base {

    /**
     * Render the secondary navigation
     *
     * @param secondary_navigation $nav
     * @return string HTML
     */
    public function render(secondary_navigation $nav): string {
        $data = $nav->export_for_template();

        // Don't render if no tabs
        if (empty($data['tabs'])) {
            return '';
        }

        $html = '<nav class="nexo-nav-secondary" role="navigation" aria-label="' . get_string('secondarynavigation', 'core') . '">';
        $html .= '<ul class="nexo-nav-secondary-tabs">';

        // Render visible tabs
        foreach ($data['tabs'] as $tab) {
            $activeClass = !empty($tab['active']) ? ' active' : '';
            $html .= '<li class="nexo-nav-secondary-tab' . $activeClass . '">';
            $html .= '<a href="' . htmlspecialchars($tab['url'] ?? '#') . '"';
            if (!empty($tab['active'])) {
                $html .= ' aria-current="page"';
            }
            $html .= '>';

            if (!empty($tab['icon_class'])) {
                $html .= '<i class="' . htmlspecialchars($tab['icon_class']) . '" aria-hidden="true"></i>';
            }

            $html .= '<span>' . htmlspecialchars($tab['text'] ?? '') . '</span>';
            $html .= '</a>';
            $html .= '</li>';
        }

        $html .= '</ul>';

        // Render "More" menu if there are overflow tabs
        if (!empty($data['more_tabs'])) {
            $html .= '<div class="nexo-nav-secondary-more">';
            $html .= '<button class="nexo-nav-secondary-more-btn" aria-expanded="false" aria-haspopup="true">';
            $html .= get_string('more', 'core') . ' <i class="fas fa-chevron-down"></i>';
            $html .= '</button>';
            $html .= '<div class="nexo-nav-secondary-more-menu" role="menu">';

            foreach ($data['more_tabs'] as $tab) {
                $html .= '<a href="' . htmlspecialchars($tab['url'] ?? '#') . '" role="menuitem">';
                if (!empty($tab['icon_class'])) {
                    $html .= '<i class="' . htmlspecialchars($tab['icon_class']) . '" aria-hidden="true"></i>';
                }
                $html .= htmlspecialchars($tab['text'] ?? '');
                $html .= '</a>';
            }

            $html .= '</div>';
            $html .= '</div>';
        }

        $html .= '</nav>';

        return $html;
    }
}
