<?php
namespace core\navigation;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Secondary Navigation
 *
 * Manages contextual tab navigation following Moodle 4.x architecture.
 * Displays tabs below the primary header based on the current page context.
 *
 * @package core\navigation
 */
class secondary_navigation {

    /** @var array Navigation tabs */
    protected array $tabs = [];

    /** @var string Context identifier */
    protected string $context = '';

    /** @var int Maximum visible tabs before "More" menu */
    protected int $max_visible_tabs = 5;

    /** @var string Active tab key */
    protected string $active_key = '';

    /** @var string Current URL path */
    protected string $current_path;

    /**
     * Constructor
     *
     * @param string $context Context identifier (admin, user, system, etc.)
     */
    public function __construct(string $context = 'system') {
        $this->context = $context;
        $this->current_path = $this->get_current_path();
    }

    /**
     * Get current URL path
     *
     * @return string
     */
    protected function get_current_path(): string {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        return parse_url($uri, PHP_URL_PATH) ?? '/';
    }

    /**
     * Add a tab
     *
     * @param navigation_node $node
     * @return self
     */
    public function add_tab(navigation_node $node): self {
        $this->tabs[$node->get_key()] = $node;

        // Check if this tab should be active based on URL
        $node_url = $node->get_url();
        if ($node_url !== null) {
            $node_path = parse_url($node_url, PHP_URL_PATH);
            if ($node_path === $this->current_path) {
                $this->set_active($node->get_key());
            }
        }

        return $this;
    }

    /**
     * Set active tab
     *
     * @param string $key Tab key
     * @return self
     */
    public function set_active(string $key): self {
        // Deactivate previous
        if (!empty($this->active_key) && isset($this->tabs[$this->active_key])) {
            $this->tabs[$this->active_key]->set_active(false);
        }

        // Activate new
        if (isset($this->tabs[$key])) {
            $this->tabs[$key]->set_active(true);
            $this->active_key = $key;
        }

        return $this;
    }

    /**
     * Get all tabs sorted by order
     *
     * @return array
     */
    protected function get_sorted_tabs(): array {
        $tabs = $this->tabs;
        uasort($tabs, function($a, $b) {
            return $a->get_order() <=> $b->get_order();
        });
        return $tabs;
    }

    /**
     * Get visible tabs (filtered by permissions)
     *
     * @return array
     */
    public function get_visible_tabs(): array {
        $visible = [];
        $count = 0;

        foreach ($this->get_sorted_tabs() as $key => $tab) {
            if ($tab->is_visible() && $tab->check_access()) {
                if ($count < $this->max_visible_tabs) {
                    $visible[$key] = $tab;
                    $count++;
                }
            }
        }

        return $visible;
    }

    /**
     * Get tabs that go into "More" menu
     *
     * @return array
     */
    public function get_more_menu_tabs(): array {
        $more = [];
        $count = 0;

        foreach ($this->get_sorted_tabs() as $key => $tab) {
            if ($tab->is_visible() && $tab->check_access()) {
                if ($count >= $this->max_visible_tabs) {
                    $more[$key] = $tab;
                }
                $count++;
            }
        }

        return $more;
    }

    /**
     * Check if there are tabs in "More" menu
     *
     * @return bool
     */
    public function has_more_tabs(): bool {
        return !empty($this->get_more_menu_tabs());
    }

    /**
     * Export for template
     *
     * @return array
     */
    public function export_for_template(): array {
        $visible_tabs = [];
        foreach ($this->get_visible_tabs() as $tab) {
            $visible_tabs[] = $tab->to_array(false);
        }

        $more_tabs = [];
        foreach ($this->get_more_menu_tabs() as $tab) {
            $more_tabs[] = $tab->to_array(false);
        }

        return [
            'tabs' => $visible_tabs,
            'has_tabs' => !empty($visible_tabs),
            'more_tabs' => $more_tabs,
            'has_more_tabs' => !empty($more_tabs),
            'context' => $this->context,
            'active_key' => $this->active_key,
        ];
    }

    /**
     * Factory method: Create navigation for a given context
     *
     * @param string $context Context identifier
     * @return self
     */
    public static function for_context(string $context): self {
        return match($context) {
            'admin' => self::for_admin_context(),
            'admin_users' => self::for_admin_users_context(),
            'admin_roles' => self::for_admin_roles_context(),
            'user' => self::for_user_context(),
            default => self::for_system_context(),
        };
    }

    /**
     * Factory method: Admin context navigation
     *
     * @return self
     */
    public static function for_admin_context(): self {
        $nav = new self('admin');

        $nav->add_tab(new navigation_node('admin_dashboard', navigation_node::TYPE_ITEM, [
            'text' => get_string('dashboard', 'core'),
            'url' => '/admin',
            'icon' => 'fa-tachometer-alt',
            'order' => 10,
        ]));

        $nav->add_tab(new navigation_node('admin_users', navigation_node::TYPE_ITEM, [
            'text' => get_string('users', 'core'),
            'url' => '/admin/user',
            'icon' => 'fa-users',
            'order' => 20,
            'capability' => 'nexosupport/admin:manageusers',
        ]));

        $nav->add_tab(new navigation_node('admin_roles', navigation_node::TYPE_ITEM, [
            'text' => get_string('roles', 'core'),
            'url' => '/admin/roles',
            'icon' => 'fa-user-shield',
            'order' => 30,
            'capability' => 'nexosupport/admin:manageroles',
        ]));

        $nav->add_tab(new navigation_node('admin_settings', navigation_node::TYPE_ITEM, [
            'text' => get_string('settings', 'core'),
            'url' => '/admin/settings',
            'icon' => 'fa-cog',
            'order' => 40,
            'capability' => 'nexosupport/admin:managesettings',
        ]));

        $nav->add_tab(new navigation_node('admin_plugins', navigation_node::TYPE_ITEM, [
            'text' => get_string('plugins', 'core'),
            'url' => '/admin/plugins',
            'icon' => 'fa-puzzle-piece',
            'order' => 50,
            'capability' => 'nexosupport/admin:manageplugins',
        ]));

        $nav->add_tab(new navigation_node('admin_reports', navigation_node::TYPE_ITEM, [
            'text' => get_string('reports', 'core'),
            'url' => '/report/log',
            'icon' => 'fa-chart-bar',
            'order' => 60,
            'capability' => 'nexosupport/admin:viewreports',
        ]));

        $nav->add_tab(new navigation_node('admin_cache', navigation_node::TYPE_ITEM, [
            'text' => get_string('cache', 'core'),
            'url' => '/admin/cache/purge',
            'icon' => 'fa-database',
            'order' => 70,
            'capability' => 'nexosupport/admin:managesettings',
        ]));

        return $nav;
    }

    /**
     * Factory method: Admin users context navigation
     *
     * @return self
     */
    public static function for_admin_users_context(): self {
        $nav = new self('admin_users');

        $nav->add_tab(new navigation_node('users_list', navigation_node::TYPE_ITEM, [
            'text' => get_string('userlist', 'core'),
            'url' => '/admin/user',
            'icon' => 'fa-list',
            'order' => 10,
            'capability' => 'nexosupport/admin:manageusers',
        ]));

        $nav->add_tab(new navigation_node('users_add', navigation_node::TYPE_ITEM, [
            'text' => get_string('addnewuser', 'core'),
            'url' => '/admin/user/edit',
            'icon' => 'fa-user-plus',
            'order' => 20,
            'capability' => 'nexosupport/admin:manageusers',
        ]));

        return $nav;
    }

    /**
     * Factory method: Admin roles context navigation
     *
     * @return self
     */
    public static function for_admin_roles_context(): self {
        $nav = new self('admin_roles');

        $nav->add_tab(new navigation_node('roles_list', navigation_node::TYPE_ITEM, [
            'text' => get_string('manageroles', 'core'),
            'url' => '/admin/roles',
            'icon' => 'fa-list',
            'order' => 10,
            'capability' => 'nexosupport/admin:manageroles',
        ]));

        $nav->add_tab(new navigation_node('roles_add', navigation_node::TYPE_ITEM, [
            'text' => get_string('addrole', 'core'),
            'url' => '/admin/roles/edit',
            'icon' => 'fa-plus',
            'order' => 20,
            'capability' => 'nexosupport/admin:manageroles',
        ]));

        $nav->add_tab(new navigation_node('roles_define', navigation_node::TYPE_ITEM, [
            'text' => get_string('definepermissions', 'core'),
            'url' => '/admin/roles/define',
            'icon' => 'fa-lock',
            'order' => 30,
            'capability' => 'nexosupport/admin:manageroles',
        ]));

        $nav->add_tab(new navigation_node('roles_assign', navigation_node::TYPE_ITEM, [
            'text' => get_string('assignroles', 'core'),
            'url' => '/admin/roles/assign',
            'icon' => 'fa-user-tag',
            'order' => 40,
            'capability' => 'nexosupport/admin:assignroles',
        ]));

        return $nav;
    }

    /**
     * Factory method: User profile context navigation
     *
     * @param int|null $userid User ID (null = current user)
     * @return self
     */
    public static function for_user_context(?int $userid = null): self {
        global $USER;
        $userid = $userid ?? ($USER->id ?? 0);

        $nav = new self('user');

        $nav->add_tab(new navigation_node('user_profile', navigation_node::TYPE_ITEM, [
            'text' => get_string('viewprofile', 'core'),
            'url' => '/user/profile' . ($userid ? '?id=' . $userid : ''),
            'icon' => 'fa-user',
            'order' => 10,
        ]));

        $nav->add_tab(new navigation_node('user_edit', navigation_node::TYPE_ITEM, [
            'text' => get_string('editprofile', 'core'),
            'url' => '/user/edit' . ($userid ? '?id=' . $userid : ''),
            'icon' => 'fa-edit',
            'order' => 20,
        ]));

        $nav->add_tab(new navigation_node('user_security', navigation_node::TYPE_ITEM, [
            'text' => get_string('security', 'core'),
            'url' => '/login/change_password',
            'icon' => 'fa-shield-alt',
            'order' => 30,
        ]));

        return $nav;
    }

    /**
     * Factory method: System context navigation (default)
     *
     * @return self
     */
    public static function for_system_context(): self {
        return new self('system');
    }
}
