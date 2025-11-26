<?php
namespace core\output;

defined('NEXOSUPPORT_INTERNAL') || die();

use core\navigation\primary_navigation;
use core\navigation\secondary_navigation;
use core\navigation\navigation_tree;
use core\navigation\navigation_builder;

/**
 * Page
 *
 * Objeto que representa una página y sus metadatos.
 * Similar a Moodle's $PAGE.
 *
 * @package core\output
 */
class page {

    /** @var string Page title */
    public string $title = 'NexoSupport';

    /** @var string Page heading */
    public string $heading = '';

    /** @var array Breadcrumbs */
    public array $breadcrumbs = [];

    /** @var array CSS URLs */
    public array $css_urls = [];

    /** @var array JS URLs */
    public array $js_urls = [];

    /** @var string Inline CSS */
    public string $inline_css = '';

    /** @var string Inline JS */
    public string $inline_js = '';

    /** @var int Max width */
    public int $maxwidth = 1400;

    /** @var string Page URL */
    public string $url = '';

    /** @var string Page context (system, admin, user, etc.) */
    public string $context = 'system';

    /** @var primary_navigation|null Primary navigation */
    public ?primary_navigation $primary_nav = null;

    /** @var secondary_navigation|null Secondary navigation */
    public ?secondary_navigation $secondary_nav = null;

    /** @var navigation_tree|null Sidebar navigation */
    public ?navigation_tree $sidebar_nav = null;

    /** @var bool Navigation initialized */
    protected bool $navigation_initialized = false;

    /** @var bool Show sidebar */
    public bool $show_sidebar = true;

    /** @var bool Show secondary nav */
    public bool $show_secondary_nav = true;

    /**
     * Set page title
     *
     * @param string $title
     * @return void
     */
    public function set_title(string $title): void {
        $this->title = $title;
    }

    /**
     * Set page heading
     *
     * @param string $heading
     * @return void
     */
    public function set_heading(string $heading): void {
        $this->heading = $heading;
    }

    /**
     * Set max width
     *
     * @param int $width
     * @return void
     */
    public function set_maxwidth(int $width): void {
        $this->maxwidth = $width;
    }

    /**
     * Add breadcrumb
     *
     * @param string $text
     * @param string|null $url
     * @return void
     */
    public function add_breadcrumb(string $text, ?string $url = null): void {
        $this->breadcrumbs[] = [
            'text' => $text,
            'url' => $url
        ];
    }

    /**
     * Add CSS URL
     *
     * @param string $url
     * @return void
     */
    public function add_css(string $url): void {
        if (!in_array($url, $this->css_urls)) {
            $this->css_urls[] = $url;
        }
    }

    /**
     * Add JS URL
     *
     * @param string $url
     * @return void
     */
    public function add_js(string $url): void {
        if (!in_array($url, $this->js_urls)) {
            $this->js_urls[] = $url;
        }
    }

    /**
     * Add inline CSS
     *
     * @param string $css
     * @return void
     */
    public function add_inline_css(string $css): void {
        $this->inline_css .= $css . "\n";
    }

    /**
     * Add inline JS
     *
     * @param string $js
     * @return void
     */
    public function add_inline_js(string $js): void {
        $this->inline_js .= $js . "\n";
    }

    /**
     * Set page URL
     *
     * @param string $url
     * @return void
     */
    public function set_url(string $url): void {
        $this->url = $url;
    }

    /**
     * Set page context
     *
     * @param string $context Context identifier (system, admin, user, etc.)
     * @return void
     */
    public function set_context(string $context): void {
        $this->context = $context;
    }

    /**
     * Initialize navigation components
     *
     * Creates primary, secondary, and sidebar navigation based on current context.
     *
     * @return void
     */
    public function initialize_navigation(): void {
        if ($this->navigation_initialized) {
            return;
        }

        // Initialize primary navigation
        $this->primary_nav = new primary_navigation();

        // Initialize secondary navigation based on context
        $this->secondary_nav = secondary_navigation::for_context($this->context);

        // Initialize sidebar navigation
        $this->sidebar_nav = $this->build_sidebar_navigation();

        $this->navigation_initialized = true;
    }

    /**
     * Build sidebar navigation tree
     *
     * @return navigation_tree
     */
    protected function build_sidebar_navigation(): navigation_tree {
        $builder = new navigation_builder();

        // Dashboard
        $builder->add_item('dashboard', [
            'text' => get_string('dashboard', 'core'),
            'url' => '/',
            'icon' => 'fa-tachometer-alt',
            'order' => 10,
        ]);

        // Site Administration (only for users with admin capabilities)
        $builder->add_category('siteadmin', [
            'text' => get_string('siteadministration', 'core'),
            'icon' => 'fa-cogs',
            'order' => 100,
            'capability' => 'nexosupport/admin:view',
        ]);

        // Users subcategory
        $builder->add_category('users', [
            'text' => get_string('users', 'core'),
            'icon' => 'fa-users',
            'order' => 110,
            'parent' => 'siteadmin',
            'capability' => 'nexosupport/admin:manageusers',
        ]);

        $builder->add_item('userlist', [
            'text' => get_string('userlist', 'core'),
            'url' => '/admin/user',
            'icon' => 'fa-list',
            'order' => 111,
            'parent' => 'users',
            'capability' => 'nexosupport/admin:manageusers',
        ]);

        $builder->add_item('adduser', [
            'text' => get_string('addnewuser', 'core'),
            'url' => '/admin/user/edit',
            'icon' => 'fa-user-plus',
            'order' => 112,
            'parent' => 'users',
            'capability' => 'nexosupport/admin:manageusers',
        ]);

        // Roles subcategory
        $builder->add_category('roles', [
            'text' => get_string('roles', 'core'),
            'icon' => 'fa-user-shield',
            'order' => 120,
            'parent' => 'siteadmin',
            'capability' => 'nexosupport/admin:manageroles',
        ]);

        $builder->add_item('rolelist', [
            'text' => get_string('manageroles', 'core'),
            'url' => '/admin/roles',
            'icon' => 'fa-list',
            'order' => 121,
            'parent' => 'roles',
            'capability' => 'nexosupport/admin:manageroles',
        ]);

        $builder->add_item('defineroles', [
            'text' => get_string('definepermissions', 'core'),
            'url' => '/admin/roles/define',
            'icon' => 'fa-lock',
            'order' => 122,
            'parent' => 'roles',
            'capability' => 'nexosupport/admin:manageroles',
        ]);

        $builder->add_item('assignroles', [
            'text' => get_string('assignroles', 'core'),
            'url' => '/admin/roles/assign',
            'icon' => 'fa-user-tag',
            'order' => 123,
            'parent' => 'roles',
            'capability' => 'nexosupport/admin:assignroles',
        ]);

        // Settings subcategory
        $builder->add_category('settings', [
            'text' => get_string('settings', 'core'),
            'icon' => 'fa-sliders-h',
            'order' => 130,
            'parent' => 'siteadmin',
            'capability' => 'nexosupport/admin:managesettings',
        ]);

        $builder->add_item('generalsettings', [
            'text' => get_string('generalsettings', 'core'),
            'url' => '/admin/settings',
            'icon' => 'fa-cog',
            'order' => 131,
            'parent' => 'settings',
            'capability' => 'nexosupport/admin:managesettings',
        ]);

        // Plugins
        $builder->add_item('plugins', [
            'text' => get_string('plugins', 'core'),
            'url' => '/admin/plugins',
            'icon' => 'fa-puzzle-piece',
            'order' => 140,
            'parent' => 'siteadmin',
            'capability' => 'nexosupport/admin:manageplugins',
        ]);

        // Reports subcategory
        $builder->add_category('reports', [
            'text' => get_string('reports', 'core'),
            'icon' => 'fa-chart-bar',
            'order' => 150,
            'parent' => 'siteadmin',
            'capability' => 'nexosupport/admin:viewreports',
        ]);

        $builder->add_item('logs', [
            'text' => get_string('logs', 'core'),
            'url' => '/report/log',
            'icon' => 'fa-file-alt',
            'order' => 151,
            'parent' => 'reports',
            'capability' => 'nexosupport/admin:viewreports',
        ]);

        $builder->add_item('livelogs', [
            'text' => get_string('livelogs', 'core'),
            'url' => '/report/loglive',
            'icon' => 'fa-stream',
            'order' => 152,
            'parent' => 'reports',
            'capability' => 'nexosupport/admin:viewreports',
        ]);

        // Cache
        $builder->add_item('cache', [
            'text' => get_string('cache', 'core'),
            'url' => '/admin/cache/purge',
            'icon' => 'fa-database',
            'order' => 160,
            'parent' => 'siteadmin',
            'capabilities' => ['nexosupport/admin:managesettings'],
        ]);

        // My Profile
        $builder->add_category('myprofile', [
            'text' => get_string('myprofile', 'core'),
            'icon' => 'fa-user-circle',
            'order' => 200,
        ]);

        $builder->add_item('viewprofile', [
            'text' => get_string('viewprofile', 'core'),
            'url' => '/user/profile',
            'icon' => 'fa-id-card',
            'order' => 201,
            'parent' => 'myprofile',
        ]);

        $builder->add_item('editprofile', [
            'text' => get_string('editprofile', 'core'),
            'url' => '/user/edit',
            'icon' => 'fa-edit',
            'order' => 202,
            'parent' => 'myprofile',
        ]);

        $builder->add_item('changepassword', [
            'text' => get_string('changepassword', 'core'),
            'url' => '/login/change_password',
            'icon' => 'fa-key',
            'order' => 203,
            'parent' => 'myprofile',
        ]);

        return $builder->build(true);
    }

    /**
     * Set secondary navigation active tab
     *
     * @param string $key Tab key to activate
     * @return self
     */
    public function set_secondary_active_tab(string $key): self {
        if ($this->secondary_nav) {
            $this->secondary_nav->set_active($key);
        }
        return $this;
    }

    /**
     * Hide sidebar
     *
     * @return self
     */
    public function hide_sidebar(): self {
        $this->show_sidebar = false;
        return $this;
    }

    /**
     * Hide secondary navigation
     *
     * @return self
     */
    public function hide_secondary_nav(): self {
        $this->show_secondary_nav = false;
        return $this;
    }
}
