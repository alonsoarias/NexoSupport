<?php
namespace core\navigation;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Navigation Manager - v1.1.9 Redesign
 *
 * Modern navigation system using navigation_builder, navigation_tree,
 * and navigation_renderer with Mustache templates and Font Awesome icons.
 *
 * Maintains backward compatibility with v1.1.8 API while adding:
 * - Template-based rendering
 * - Font Awesome icon support
 * - Permission-based filtering
 * - Better extensibility
 *
 * @package core\navigation
 */
class nav_manager {

    /** @var navigation_builder Navigation builder instance */
    private static ?navigation_builder $builder = null;

    /** @var navigation_tree Built navigation tree */
    private static ?navigation_tree $tree = null;

    /** @var bool Whether navigation has been initialized */
    private static bool $initialized = false;

    /**
     * Initialize navigation tree
     *
     * Builds the complete site navigation structure.
     * Called automatically by render() if not already initialized.
     *
     * @return void
     */
    public static function init(): void {
        global $USER;

        // Avoid double initialization
        if (self::$initialized) {
            return;
        }

        // Create new builder
        self::$builder = new navigation_builder();

        // Main navigation for all logged-in users
        if (isset($USER->id) && $USER->id > 0) {
            self::$builder->add_item('home', [
                'text' => get_string('home', 'core'),
                'url' => '/',
                'icon' => 'fa-home',
                'order' => 1
            ]);

            self::$builder->add_item('dashboard', [
                'text' => get_string('dashboard', 'core'),
                'url' => '/',
                'icon' => 'fa-chart-line',
                'order' => 2
            ]);
        }

        // Site administration menu (with permission checking)
        if (is_siteadmin()) {
            self::$builder->add_category('siteadmin', [
                'text' => get_string('administration', 'core'),
                'icon' => 'fa-cogs',
                'order' => 100,
                'expanded' => self::is_admin_page(),
            ]);

            // Users submenu
            self::$builder->add_category('siteadmin_users', [
                'text' => get_string('users', 'core'),
                'icon' => 'fa-users',
                'parent' => 'siteadmin',
                'order' => 10,
            ]);

            self::$builder->add_item('siteadmin_users_browse', [
                'text' => get_string('browselistofusers', 'core'),
                'url' => '/admin/user',
                'parent' => 'siteadmin_users',
                'order' => 1,
            ]);

            self::$builder->add_item('siteadmin_users_add', [
                'text' => get_string('addnewuser', 'core'),
                'url' => '/admin/user/edit',
                'parent' => 'siteadmin_users',
                'order' => 2,
            ]);

            // Roles submenu
            self::$builder->add_category('siteadmin_roles', [
                'text' => get_string('roles', 'core'),
                'icon' => 'fa-shield-alt',
                'parent' => 'siteadmin',
                'order' => 20,
            ]);

            self::$builder->add_item('siteadmin_roles_manage', [
                'text' => get_string('manageroles', 'core'),
                'url' => '/admin/roles',
                'parent' => 'siteadmin_roles',
                'order' => 1,
            ]);

            self::$builder->add_item('siteadmin_roles_define', [
                'text' => get_string('defineroles', 'core'),
                'url' => '/admin/roles/define',
                'parent' => 'siteadmin_roles',
                'order' => 2,
            ]);

            self::$builder->add_item('siteadmin_roles_assign', [
                'text' => get_string('assignroles', 'core'),
                'url' => '/admin/roles/assign',
                'parent' => 'siteadmin_roles',
                'order' => 3,
            ]);

            // Settings submenu
            self::$builder->add_item('siteadmin_settings', [
                'text' => get_string('settings', 'core'),
                'url' => '/admin/settings',
                'icon' => 'fa-sliders-h',
                'parent' => 'siteadmin',
                'order' => 30,
            ]);

            // Cache management
            self::$builder->add_item('siteadmin_cache', [
                'text' => get_string('managecaches', 'core'),
                'url' => '/admin/cache/purge',
                'icon' => 'fa-sync-alt',
                'parent' => 'siteadmin',
                'order' => 40,
            ]);
        }

        self::$initialized = true;
    }

    /**
     * Add a navigation category
     *
     * Backward compatibility wrapper for add_category().
     *
     * @param string $key Unique key for the category
     * @param array $data Category data (text, icon, parent, order, capability, etc.)
     * @return void
     */
    public static function add_category(string $key, array $data): void {
        self::ensure_initialized();

        // Convert legacy emoji icons to Font Awesome
        if (isset($data['icon'])) {
            $data['icon'] = self::convert_icon($data['icon']);
        }

        self::$builder->add_category($key, $data);
    }

    /**
     * Add a navigation item
     *
     * Backward compatibility wrapper for add_item().
     *
     * @param string $key Unique key for the item
     * @param array $data Item data (text, url, icon, parent, order, capability, etc.)
     * @return void
     */
    public static function add_item(string $key, array $data): void {
        self::ensure_initialized();

        // Convert legacy emoji icons to Font Awesome
        if (isset($data['icon'])) {
            $data['icon'] = self::convert_icon($data['icon']);
        }

        self::$builder->add_item($key, $data);
    }

    /**
     * Add a separator
     *
     * New in v1.1.9: Add visual separators between navigation groups.
     *
     * @param string $key Unique key for the separator
     * @param array $data Separator data (parent, order)
     * @return void
     */
    public static function add_separator(string $key, array $data = []): void {
        self::ensure_initialized();
        self::$builder->add_separator($key, $data);
    }

    /**
     * Get navigation tree
     *
     * Builds and returns the navigation tree.
     * Maintains backward compatibility but now uses new tree structure.
     *
     * @return navigation_tree Navigation tree
     */
    public static function get_tree(): navigation_tree {
        self::ensure_initialized();

        if (self::$tree === null) {
            self::$tree = self::$builder->build(true); // With permission filtering
        }

        return self::$tree;
    }

    /**
     * Render navigation as HTML
     *
     * Uses new template-based rendering system.
     * Maintains backward compatibility with v1.1.8.
     *
     * @param string $style Render style (sidebar, breadcrumbs, horizontal)
     * @param array $options Rendering options
     * @return string HTML navigation
     */
    public static function render(string $style = navigation_renderer::STYLE_SIDEBAR, array $options = []): string {
        self::init();

        $tree = self::get_tree();
        $renderer = new navigation_renderer();

        return $renderer->render($tree, $style, $options);
    }

    /**
     * Render breadcrumbs
     *
     * New in v1.1.9: Dedicated breadcrumb rendering.
     *
     * @param array $options Breadcrumb options
     * @return string HTML breadcrumbs
     */
    public static function render_breadcrumbs(array $options = []): string {
        return self::render(navigation_renderer::STYLE_BREADCRUMBS, $options);
    }

    /**
     * Get breadcrumb trail
     *
     * New in v1.1.9: Get breadcrumbs as array.
     *
     * @return array Breadcrumb nodes
     */
    public static function get_breadcrumbs(): array {
        $tree = self::get_tree();
        return $tree->get_breadcrumbs();
    }

    /**
     * Convert legacy icon names to Font Awesome classes
     *
     * Maps old emoji-based icon names to Font Awesome classes.
     *
     * @param string $icon Legacy icon name or Font Awesome class
     * @return string Font Awesome class
     */
    private static function convert_icon(string $icon): string {
        // If already Font Awesome class, return as-is
        if (str_starts_with($icon, 'fa-')) {
            return $icon;
        }

        // Map legacy emoji names to Font Awesome
        $icon_map = [
            'home' => 'fa-home',
            'dashboard' => 'fa-chart-line',
            'settings' => 'fa-cogs',
            'users' => 'fa-users',
            'shield' => 'fa-shield-alt',
            'cog' => 'fa-cog',
            'refresh' => 'fa-sync-alt',
            'user' => 'fa-user',
            'edit' => 'fa-edit',
            'delete' => 'fa-trash',
            'add' => 'fa-plus',
            'save' => 'fa-save',
            'cancel' => 'fa-times',
            'search' => 'fa-search',
            'filter' => 'fa-filter',
            'calendar' => 'fa-calendar',
            'clock' => 'fa-clock',
            'file' => 'fa-file',
            'folder' => 'fa-folder',
            'download' => 'fa-download',
            'upload' => 'fa-upload',
        ];

        return $icon_map[$icon] ?? 'fa-circle';
    }

    /**
     * Check if we're on an admin page
     *
     * Used to auto-expand admin category when on admin pages.
     *
     * @return bool True if on admin page
     */
    private static function is_admin_page(): bool {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        return str_starts_with($path, '/admin/');
    }

    /**
     * Ensure navigation is initialized
     *
     * @return void
     */
    private static function ensure_initialized(): void {
        if (!self::$initialized) {
            self::init();
        }
    }

    /**
     * Reset navigation (for testing)
     *
     * @return void
     */
    public static function reset(): void {
        self::$builder = null;
        self::$tree = null;
        self::$initialized = false;
    }
}
