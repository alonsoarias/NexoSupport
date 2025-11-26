<?php
namespace core\navigation;

defined('NEXOSUPPORT_INTERNAL') || die();

use core\output\renderer_base;
use core\output\template_manager;

/**
 * Primary Navigation Renderer
 *
 * Renders the primary navigation (header/top bar) using Mustache templates.
 *
 * @package core\navigation
 */
class primary_navigation_renderer extends renderer_base {

    /**
     * Render the primary navigation
     *
     * @param primary_navigation $nav
     * @return string HTML
     */
    public function render(primary_navigation $nav): string {
        global $USER, $CFG;

        $data = $nav->export_for_template();

        // Add user data
        $data['user'] = $this->get_user_data();
        $data['has_user'] = isset($USER->id) && $USER->id > 0;

        // Add site info
        $data['site_name'] = $CFG->sitename ?? 'NexoSupport';
        $data['logo_url'] = $CFG->logo ?? null;
        $data['has_logo'] = !empty($CFG->logo);

        // Try to use template, fallback to direct HTML
        try {
            $template_manager = template_manager::get_instance();
            return $template_manager->render('navigation/primary_navigation', $data);
        } catch (\Exception $e) {
            // Fallback to direct HTML rendering
            return $this->render_html($data);
        }
    }

    /**
     * Get user data for template
     *
     * @return array
     */
    protected function get_user_data(): array {
        global $USER;

        if (!isset($USER->id) || $USER->id <= 0) {
            return [
                'logged_in' => false,
            ];
        }

        $fullname = trim(($USER->firstname ?? '') . ' ' . ($USER->lastname ?? ''));
        if (empty($fullname)) {
            $fullname = $USER->username ?? 'Usuario';
        }

        // Generate initials for avatar
        $parts = explode(' ', $fullname);
        $initials = '';
        foreach ($parts as $part) {
            if (!empty($part)) {
                $initials .= strtoupper(mb_substr($part, 0, 1));
            }
            if (strlen($initials) >= 2) {
                break;
            }
        }

        return [
            'logged_in' => true,
            'id' => $USER->id,
            'username' => $USER->username ?? '',
            'fullname' => $fullname,
            'firstname' => $USER->firstname ?? '',
            'lastname' => $USER->lastname ?? '',
            'email' => $USER->email ?? '',
            'initials' => $initials ?: 'U',
            'avatar_url' => $USER->avatar ?? null,
            'has_avatar' => !empty($USER->avatar),
            'profile_url' => '/user/profile',
            'preferences_url' => '/user/preferences',
            'logout_url' => '/logout',
            'is_siteadmin' => is_siteadmin($USER->id),
        ];
    }

    /**
     * Render HTML directly (fallback when template not available)
     *
     * @param array $data
     * @return string HTML
     */
    protected function render_html(array $data): string {
        $html = '<header class="nexo-header-primary">';
        $html .= '<div class="nexo-header-container">';

        // Hamburger menu for mobile
        $html .= '<button class="nexo-hamburger" id="nexoHamburger" aria-label="Menu">';
        $html .= '<span></span><span></span><span></span>';
        $html .= '</button>';

        // Logo
        $html .= '<a href="/" class="nexo-logo">';
        if (!empty($data['logo_url'])) {
            $html .= '<img src="' . htmlspecialchars($data['logo_url']) . '" alt="' . htmlspecialchars($data['site_name']) . '">';
        }
        $html .= '<span>' . htmlspecialchars($data['site_name']) . '</span>';
        $html .= '</a>';

        // Primary navigation menu
        $html .= '<nav class="nexo-nav-primary-menu" id="nexoPrimaryNav">';
        if (!empty($data['nodes'])) {
            foreach ($data['nodes'] as $node) {
                $activeClass = !empty($node['active']) ? ' active' : '';
                $html .= '<div class="nexo-nav-primary-item' . $activeClass . '">';
                $html .= '<a href="' . htmlspecialchars($node['url'] ?? '#') . '">';
                if (!empty($node['icon_class'])) {
                    $html .= '<i class="' . htmlspecialchars($node['icon_class']) . '"></i>';
                }
                $html .= htmlspecialchars($node['text'] ?? '');
                $html .= '</a>';
                $html .= '</div>';
            }
        }
        $html .= '</nav>';

        // User menu
        if (!empty($data['has_user']) && !empty($data['user']['logged_in'])) {
            $html .= $this->render_user_menu($data['user']);
        } else {
            $html .= '<div class="nexo-user-menu">';
            $html .= '<a href="/login" class="nexo-login-btn">' . get_string('login', 'core') . '</a>';
            $html .= '</div>';
        }

        $html .= '</div>';
        $html .= '</header>';

        return $html;
    }

    /**
     * Render user menu
     *
     * @param array $user
     * @return string HTML
     */
    protected function render_user_menu(array $user): string {
        $html = '<div class="nexo-user-menu">';

        // Notifications icon (placeholder)
        $html .= '<div class="nexo-notifications">';
        $html .= '<button class="nexo-notification-icon" aria-label="Notificaciones">';
        $html .= '<i class="fas fa-bell"></i>';
        $html .= '</button>';
        $html .= '</div>';

        // User dropdown
        $html .= '<div class="nexo-user-dropdown" id="nexoUserDropdown">';
        $html .= '<button class="nexo-user-trigger" aria-expanded="false">';

        // Avatar
        if (!empty($user['has_avatar'])) {
            $html .= '<img src="' . htmlspecialchars($user['avatar_url']) . '" alt="" class="nexo-user-avatar">';
        } else {
            $html .= '<div class="nexo-user-avatar">' . htmlspecialchars($user['initials']) . '</div>';
        }

        $html .= '<span class="nexo-user-name">' . htmlspecialchars($user['fullname']) . '</span>';
        $html .= '<i class="fas fa-chevron-down nexo-dropdown-arrow"></i>';
        $html .= '</button>';

        // Dropdown menu
        $html .= '<div class="nexo-dropdown-menu" id="nexoUserDropdownMenu">';
        $html .= '<a href="' . htmlspecialchars($user['profile_url']) . '" class="nexo-dropdown-item">';
        $html .= '<i class="fas fa-user"></i>' . get_string('profile', 'core');
        $html .= '</a>';
        $html .= '<a href="/user/edit" class="nexo-dropdown-item">';
        $html .= '<i class="fas fa-edit"></i>' . get_string('editprofile', 'core');
        $html .= '</a>';
        $html .= '<a href="/login/change_password" class="nexo-dropdown-item">';
        $html .= '<i class="fas fa-key"></i>' . get_string('changepassword', 'core');
        $html .= '</a>';
        $html .= '<div class="nexo-dropdown-divider"></div>';
        $html .= '<a href="' . htmlspecialchars($user['logout_url']) . '" class="nexo-dropdown-item nexo-dropdown-item-danger">';
        $html .= '<i class="fas fa-sign-out-alt"></i>' . get_string('logout', 'core');
        $html .= '</a>';
        $html .= '</div>';

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }
}
