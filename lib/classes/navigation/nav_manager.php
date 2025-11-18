<?php
namespace core\navigation;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Navigation Manager - Moodle-style Navigation
 *
 * Gestiona el menú de navegación del sistema, similar a Moodle.
 * Estructura jerárquica con categorías y elementos de menú.
 *
 * @package core\navigation
 */
class nav_manager {

    /** @var array Navigation tree */
    private static array $navigation = [];

    /** @var string Current active path */
    private static string $active_path = '';

    /**
     * Initialize navigation tree
     *
     * @return void
     */
    public static function init(): void {
        global $USER;

        // Clear existing navigation
        self::$navigation = [];

        // Determine current path
        self::$active_path = $_SERVER['REQUEST_URI'] ?? '/';

        // Main navigation for all logged-in users
        if (isset($USER->id) && $USER->id > 0) {
            self::add_item('home', [
                'text' => get_string('home', 'core'),
                'url' => '/',
                'icon' => 'home',
                'order' => 1
            ]);

            self::add_item('dashboard', [
                'text' => get_string('dashboard', 'core'),
                'url' => '/',
                'icon' => 'dashboard',
                'order' => 2
            ]);
        }

        // Site administration menu (only for siteadmins)
        if (is_siteadmin()) {
            self::add_category('siteadmin', [
                'text' => get_string('administration', 'core'),
                'icon' => 'settings',
                'order' => 100
            ]);

            // Users submenu
            self::add_category('siteadmin_users', [
                'text' => get_string('users', 'core'),
                'icon' => 'users',
                'parent' => 'siteadmin',
                'order' => 10
            ]);

            self::add_item('siteadmin_users_browse', [
                'text' => get_string('browselistofusers', 'core'),
                'url' => '/admin/users',
                'parent' => 'siteadmin_users',
                'order' => 1
            ]);

            self::add_item('siteadmin_users_add', [
                'text' => get_string('addnewuser', 'core'),
                'url' => '/admin/user/edit',
                'parent' => 'siteadmin_users',
                'order' => 2
            ]);

            // Roles submenu
            self::add_category('siteadmin_roles', [
                'text' => get_string('roles', 'core'),
                'icon' => 'shield',
                'parent' => 'siteadmin',
                'order' => 20
            ]);

            self::add_item('siteadmin_roles_manage', [
                'text' => get_string('manageroles', 'core'),
                'url' => '/admin/roles',
                'parent' => 'siteadmin_roles',
                'order' => 1
            ]);

            self::add_item('siteadmin_roles_define', [
                'text' => get_string('defineroles', 'core'),
                'url' => '/admin/roles/define',
                'parent' => 'siteadmin_roles',
                'order' => 2
            ]);

            // Settings submenu
            self::add_item('siteadmin_settings', [
                'text' => get_string('settings', 'core'),
                'url' => '/admin/settings',
                'icon' => 'cog',
                'parent' => 'siteadmin',
                'order' => 30
            ]);
        }
    }

    /**
     * Add a navigation category
     *
     * @param string $key Unique key for the category
     * @param array $data Category data (text, icon, parent, order)
     * @return void
     */
    public static function add_category(string $key, array $data): void {
        $data['key'] = $key;
        $data['type'] = 'category';
        $data['children'] = [];

        if (!isset($data['order'])) {
            $data['order'] = 999;
        }

        self::$navigation[$key] = $data;
    }

    /**
     * Add a navigation item
     *
     * @param string $key Unique key for the item
     * @param array $data Item data (text, url, icon, parent, order)
     * @return void
     */
    public static function add_item(string $key, array $data): void {
        $data['key'] = $key;
        $data['type'] = 'item';

        if (!isset($data['order'])) {
            $data['order'] = 999;
        }

        // Check if this item is active
        $data['active'] = false;
        if (isset($data['url'])) {
            $current_path = parse_url(self::$active_path, PHP_URL_PATH);
            $item_path = parse_url($data['url'], PHP_URL_PATH);

            if ($current_path === $item_path) {
                $data['active'] = true;
            }
        }

        self::$navigation[$key] = $data;
    }

    /**
     * Get navigation tree
     *
     * @return array Navigation tree
     */
    public static function get_tree(): array {
        // Build tree structure
        $tree = [];

        // First, collect root items and categories
        foreach (self::$navigation as $key => $item) {
            if (!isset($item['parent']) || empty($item['parent'])) {
                $tree[$key] = $item;
            }
        }

        // Then, attach children to their parents
        foreach (self::$navigation as $key => $item) {
            if (isset($item['parent']) && !empty($item['parent'])) {
                $parent_key = $item['parent'];

                if (isset($tree[$parent_key])) {
                    $tree[$parent_key]['children'][$key] = $item;
                } else {
                    // Parent might be a child itself, search recursively
                    self::attach_to_parent($tree, $parent_key, $key, $item);
                }
            }
        }

        // Sort by order
        uasort($tree, function($a, $b) {
            return ($a['order'] ?? 999) <=> ($b['order'] ?? 999);
        });

        // Sort children by order
        foreach ($tree as &$item) {
            if (isset($item['children']) && !empty($item['children'])) {
                uasort($item['children'], function($a, $b) {
                    return ($a['order'] ?? 999) <=> ($b['order'] ?? 999);
                });
            }
        }

        return $tree;
    }

    /**
     * Recursively attach item to parent in tree
     *
     * @param array &$tree Navigation tree
     * @param string $parent_key Parent key to find
     * @param string $child_key Child key to attach
     * @param array $child_data Child data
     * @return bool True if attached
     */
    private static function attach_to_parent(array &$tree, string $parent_key, string $child_key, array $child_data): bool {
        foreach ($tree as $key => &$item) {
            if ($key === $parent_key) {
                $item['children'][$child_key] = $child_data;
                return true;
            }

            if (isset($item['children']) && !empty($item['children'])) {
                if (self::attach_to_parent($item['children'], $parent_key, $child_key, $child_data)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Render navigation as HTML
     *
     * @return string HTML navigation
     */
    public static function render(): string {
        self::init();
        $tree = self::get_tree();

        $html = '<nav class="nexo-navigation">';
        $html .= '<div class="nexo-nav-header">';
        $html .= '<h3>' . get_string('navigation', 'core') . '</h3>';
        $html .= '</div>';
        $html .= '<ul class="nexo-nav-list">';

        foreach ($tree as $item) {
            $html .= self::render_item($item);
        }

        $html .= '</ul>';
        $html .= '</nav>';

        return $html;
    }

    /**
     * Render a single navigation item
     *
     * @param array $item Item data
     * @param int $level Depth level
     * @return string HTML
     */
    private static function render_item(array $item, int $level = 0): string {
        $html = '';
        $classes = ['nexo-nav-item', 'nexo-nav-level-' . $level];

        if ($item['type'] === 'category') {
            $classes[] = 'nexo-nav-category';
        }

        if (isset($item['active']) && $item['active']) {
            $classes[] = 'active';
        }

        $html .= '<li class="' . implode(' ', $classes) . '">';

        if ($item['type'] === 'category') {
            // Render category
            $html .= '<div class="nexo-nav-category-header">';

            if (isset($item['icon'])) {
                $html .= '<span class="nexo-nav-icon">' . self::get_icon($item['icon']) . '</span>';
            }

            $html .= '<span class="nexo-nav-text">' . htmlspecialchars($item['text']) . '</span>';
            $html .= '<span class="nexo-nav-toggle">▼</span>';
            $html .= '</div>';

            // Render children
            if (isset($item['children']) && !empty($item['children'])) {
                $html .= '<ul class="nexo-nav-children">';
                foreach ($item['children'] as $child) {
                    $html .= self::render_item($child, $level + 1);
                }
                $html .= '</ul>';
            }
        } else {
            // Render item
            $html .= '<a href="' . htmlspecialchars($item['url']) . '" class="nexo-nav-link">';

            if (isset($item['icon'])) {
                $html .= '<span class="nexo-nav-icon">' . self::get_icon($item['icon']) . '</span>';
            }

            $html .= '<span class="nexo-nav-text">' . htmlspecialchars($item['text']) . '</span>';
            $html .= '</a>';
        }

        $html .= '</li>';

        return $html;
    }

    /**
     * Get icon HTML
     *
     * @param string $icon Icon name
     * @return string HTML
     */
    private static function get_icon(string $icon): string {
        // Simple icon mapping (can be replaced with actual icon library)
        $icons = [
            'home' => '🏠',
            'dashboard' => '📊',
            'settings' => '⚙️',
            'users' => '👥',
            'shield' => '🛡️',
            'cog' => '⚙️',
        ];

        return $icons[$icon] ?? '•';
    }
}
