<?php
namespace core\navigation;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Navigation Manager - v1.1.10 Moodle-Style Navigation
 *
 * Complete Moodle-inspired navigation system with:
 * - Full Site Administration hierarchy
 * - User menu with preferences
 * - Plugin navigation support (contextual)
 * - Template-based rendering with Font Awesome icons
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
     * Builds the complete Moodle-style site navigation structure.
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
            self::build_main_navigation();
            self::build_user_navigation();
        }

        // Site administration menu (siteadmins only)
        if (is_siteadmin()) {
            self::build_site_administration();
        }

        // Load plugin navigation
        plugin_navigation::apply_to_builder(self::$builder, plugin_navigation::CONTEXT_SYSTEM);

        self::$initialized = true;
    }

    /**
     * Build main navigation (Home, Dashboard)
     *
     * @return void
     */
    private static function build_main_navigation(): void {
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

    /**
     * Build user navigation menu
     *
     * Includes profile, preferences, messages, etc.
     *
     * @return void
     */
    private static function build_user_navigation(): void {
        global $USER;

        // User menu category
        self::$builder->add_category('user', [
            'text' => fullname($USER),
            'icon' => 'fa-user-circle',
            'order' => 90,
        ]);

        // Profile
        self::$builder->add_item('user_profile', [
            'text' => get_string('profile', 'core'),
            'url' => '/user/profile',
            'icon' => 'fa-user',
            'parent' => 'user',
            'order' => 1,
        ]);

        // Edit profile
        self::$builder->add_item('user_editprofile', [
            'text' => get_string('editprofile', 'core'),
            'url' => '/user/edit',
            'icon' => 'fa-user-edit',
            'parent' => 'user',
            'order' => 2,
        ]);

        // Preferences category
        self::$builder->add_category('user_preferences', [
            'text' => get_string('preferences', 'core'),
            'icon' => 'fa-sliders-h',
            'parent' => 'user',
            'order' => 10,
        ]);

        // Change password
        self::$builder->add_item('user_preferences_password', [
            'text' => get_string('changepassword', 'core'),
            'url' => '/login/change_password',
            'icon' => 'fa-key',
            'parent' => 'user_preferences',
            'order' => 1,
        ]);

        // Notification preferences
        self::$builder->add_item('user_preferences_notifications', [
            'text' => get_string('notificationpreferences', 'core'),
            'url' => '/user/preferences/notification',
            'icon' => 'fa-bell',
            'parent' => 'user_preferences',
            'order' => 2,
        ]);

        // Plugin preferences (loaded via plugin_navigation)
        plugin_navigation::apply_to_builder(self::$builder, plugin_navigation::CONTEXT_USER);

        // Separator before logout
        self::$builder->add_separator('user_sep', [
            'parent' => 'user',
            'order' => 90,
        ]);

        // Logout
        self::$builder->add_item('user_logout', [
            'text' => get_string('logout', 'core'),
            'url' => '/logout',
            'icon' => 'fa-sign-out-alt',
            'parent' => 'user',
            'order' => 99,
        ]);
    }

    /**
     * Build complete Site Administration navigation (Moodle-style)
     *
     * @return void
     */
    private static function build_site_administration(): void {
        // Main Site Administration category
        self::$builder->add_category('siteadmin', [
            'text' => get_string('administration', 'core'),
            'icon' => 'fa-cogs',
            'order' => 100,
            'expanded' => self::is_admin_page(),
        ]);

        // === USERS ===
        self::build_users_navigation();

        // === PLUGINS ===
        self::build_plugins_navigation();

        // === APPEARANCE ===
        self::build_appearance_navigation();

        // === SERVER ===
        self::build_server_navigation();

        // === REPORTS ===
        self::build_reports_navigation();

        // === DEVELOPMENT ===
        self::build_development_navigation();
    }

    /**
     * Build Users section of Site Administration
     *
     * @return void
     */
    private static function build_users_navigation(): void {
        self::$builder->add_category('siteadmin_users', [
            'text' => get_string('users', 'core'),
            'icon' => 'fa-users',
            'parent' => 'siteadmin',
            'order' => 10,
        ]);

        self::$builder->add_item('siteadmin_users_browse', [
            'text' => get_string('browselistofusers', 'core'),
            'url' => '/admin/users',
            'parent' => 'siteadmin_users',
            'order' => 1,
        ]);

        self::$builder->add_item('siteadmin_users_add', [
            'text' => get_string('addnewuser', 'core'),
            'url' => '/admin/user/edit',
            'parent' => 'siteadmin_users',
            'order' => 2,
        ]);

        self::$builder->add_item('siteadmin_users_bulk', [
            'text' => get_string('bulkuseractions', 'core'),
            'url' => '/admin/user/bulk',
            'parent' => 'siteadmin_users',
            'order' => 3,
        ]);

        // Permissions submenu
        self::$builder->add_category('siteadmin_users_permissions', [
            'text' => get_string('permissions', 'core'),
            'icon' => 'fa-shield-alt',
            'parent' => 'siteadmin_users',
            'order' => 20,
        ]);

        self::$builder->add_item('siteadmin_users_permissions_roles', [
            'text' => get_string('manageroles', 'core'),
            'url' => '/admin/roles',
            'parent' => 'siteadmin_users_permissions',
            'order' => 1,
        ]);

        self::$builder->add_item('siteadmin_users_permissions_define', [
            'text' => get_string('defineroles', 'core'),
            'url' => '/admin/roles/define',
            'parent' => 'siteadmin_users_permissions',
            'order' => 2,
        ]);

        self::$builder->add_item('siteadmin_users_permissions_assign', [
            'text' => get_string('assignroles', 'core'),
            'url' => '/admin/roles/assign',
            'parent' => 'siteadmin_users_permissions',
            'order' => 3,
        ]);

        self::$builder->add_item('siteadmin_users_permissions_capabilities', [
            'text' => get_string('capabilityreport', 'core'),
            'url' => '/admin/roles/check',
            'parent' => 'siteadmin_users_permissions',
            'order' => 4,
        ]);
    }

    /**
     * Build Plugins section with all plugin types
     *
     * @return void
     */
    private static function build_plugins_navigation(): void {
        self::$builder->add_category('siteadmin_plugins', [
            'text' => get_string('plugins', 'core'),
            'icon' => 'fa-puzzle-piece',
            'parent' => 'siteadmin',
            'order' => 30,
        ]);

        // Plugins overview
        self::$builder->add_item('siteadmin_plugins_overview', [
            'text' => get_string('pluginsoverview', 'core'),
            'url' => '/admin/plugins',
            'parent' => 'siteadmin_plugins',
            'order' => 1,
        ]);

        // MFA Settings (admin tool)
        self::$builder->add_item('siteadmin_plugins_mfa', [
            'text' => get_string('mfa', 'core'),
            'url' => '/admin/tool/mfa',
            'icon' => 'fa-shield-alt',
            'parent' => 'siteadmin_plugins',
            'order' => 10,
        ]);
    }

    /**
     * Build Appearance section
     *
     * @return void
     */
    private static function build_appearance_navigation(): void {
        self::$builder->add_category('siteadmin_appearance', [
            'text' => get_string('appearance', 'core'),
            'icon' => 'fa-paint-brush',
            'parent' => 'siteadmin',
            'order' => 40,
        ]);

        // General settings (site name, language)
        self::$builder->add_item('siteadmin_appearance_general', [
            'text' => get_string('generalsettings', 'core'),
            'url' => '/admin/settings/general',
            'icon' => 'fa-cog',
            'parent' => 'siteadmin_appearance',
            'order' => 1,
        ]);
    }

    /**
     * Build Server section
     *
     * @return void
     */
    private static function build_server_navigation(): void {
        self::$builder->add_category('siteadmin_server', [
            'text' => get_string('server', 'core'),
            'icon' => 'fa-server',
            'parent' => 'siteadmin',
            'order' => 50,
        ]);

        // System paths
        self::$builder->add_item('siteadmin_server_systempaths', [
            'text' => get_string('systempaths', 'core'),
            'url' => '/admin/settings/systempaths',
            'icon' => 'fa-folder-open',
            'parent' => 'siteadmin_server',
            'order' => 1,
        ]);

        // Session handling
        self::$builder->add_item('siteadmin_server_sessionhandling', [
            'text' => get_string('sessionhandling', 'core'),
            'url' => '/admin/settings/sessionhandling',
            'icon' => 'fa-clock',
            'parent' => 'siteadmin_server',
            'order' => 10,
        ]);

        // HTTP settings
        self::$builder->add_item('siteadmin_server_http', [
            'text' => get_string('httpsettings', 'core'),
            'url' => '/admin/settings/http',
            'icon' => 'fa-network-wired',
            'parent' => 'siteadmin_server',
            'order' => 20,
        ]);

        // Maintenance mode
        self::$builder->add_item('siteadmin_server_maintenance', [
            'text' => get_string('maintenancemode', 'core'),
            'url' => '/admin/settings/maintenancemode',
            'icon' => 'fa-tools',
            'parent' => 'siteadmin_server',
            'order' => 30,
        ]);

        // Environment
        self::$builder->add_item('siteadmin_server_environment', [
            'text' => get_string('environment', 'core'),
            'url' => '/admin/environment',
            'icon' => 'fa-info-circle',
            'parent' => 'siteadmin_server',
            'order' => 40,
        ]);

        // PHP info
        self::$builder->add_item('siteadmin_server_phpinfo', [
            'text' => get_string('phpinfo', 'core'),
            'url' => '/admin/phpinfo',
            'icon' => 'fa-file-alt',
            'parent' => 'siteadmin_server',
            'order' => 50,
        ]);
    }

    /**
     * Build Reports section
     *
     * @return void
     */
    private static function build_reports_navigation(): void {
        self::$builder->add_category('siteadmin_reports', [
            'text' => get_string('reports', 'core'),
            'icon' => 'fa-chart-bar',
            'parent' => 'siteadmin',
            'order' => 60,
        ]);

        // Logs
        self::$builder->add_item('siteadmin_reports_logs', [
            'text' => get_string('logs', 'core'),
            'url' => '/admin/reports/logs',
            'icon' => 'fa-list',
            'parent' => 'siteadmin_reports',
            'order' => 1,
        ]);

        // Live logs
        self::$builder->add_item('siteadmin_reports_livelogs', [
            'text' => get_string('livelogs', 'core'),
            'url' => '/admin/reports/livelogs',
            'icon' => 'fa-stream',
            'parent' => 'siteadmin_reports',
            'order' => 2,
        ]);

        // Security report
        self::$builder->add_item('siteadmin_reports_security', [
            'text' => get_string('securityreport', 'core'),
            'url' => '/admin/reports/security',
            'icon' => 'fa-shield-alt',
            'parent' => 'siteadmin_reports',
            'order' => 20,
        ]);

        // Performance
        self::$builder->add_item('siteadmin_reports_performance', [
            'text' => get_string('performance', 'core'),
            'url' => '/admin/reports/performance',
            'icon' => 'fa-tachometer-alt',
            'parent' => 'siteadmin_reports',
            'order' => 30,
        ]);
    }

    /**
     * Build Development section
     *
     * @return void
     */
    private static function build_development_navigation(): void {
        self::$builder->add_category('siteadmin_development', [
            'text' => get_string('development', 'core'),
            'icon' => 'fa-code',
            'parent' => 'siteadmin',
            'order' => 70,
        ]);

        // Debugging
        self::$builder->add_item('siteadmin_development_debugging', [
            'text' => get_string('debugging', 'core'),
            'url' => '/admin/settings/debugging',
            'icon' => 'fa-bug',
            'parent' => 'siteadmin_development',
            'order' => 1,
        ]);

        // Development settings
        self::$builder->add_item('siteadmin_development_settings', [
            'text' => get_string('developmentsettings', 'core'),
            'url' => '/admin/settings/development',
            'icon' => 'fa-flask',
            'parent' => 'siteadmin_development',
            'order' => 5,
        ]);

        // Purge caches
        self::$builder->add_item('siteadmin_development_purgecaches', [
            'text' => get_string('purgecaches', 'core'),
            'url' => '/admin/cache/purge',
            'icon' => 'fa-sync-alt',
            'parent' => 'siteadmin_development',
            'order' => 10,
        ]);
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
     * Check if we're on a user page
     *
     * @return bool True if on user page
     */
    private static function is_user_page(): bool {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        return str_starts_with($path, '/user/');
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
        plugin_navigation::reset();
    }
}
