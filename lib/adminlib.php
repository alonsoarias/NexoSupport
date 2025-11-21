<?php
/**
 * Admin Settings Library
 *
 * Functions for building and managing the admin settings tree.
 * Similar to Moodle's adminlib.php structure.
 *
 * @package NexoSupport
 */

defined('NEXOSUPPORT_INTERNAL') || die();

/** @var \core\admin\admin_root|null Global admin tree root */
$ADMIN = null;

/**
 * Build and return the admin settings tree
 *
 * Creates the complete hierarchy of categories and settings pages
 * following Moodle's pattern with separate settings files.
 *
 * @param bool $reload Force reload of the tree
 * @param bool $requirefulltree Whether to load full tree
 * @return \core\admin\admin_root Root of admin tree
 */
function admin_get_root(bool $reload = false, bool $requirefulltree = true): \core\admin\admin_root {
    global $ADMIN, $CFG;

    // Return cached tree if exists and not reloading
    if (!$reload && isset($ADMIN) && $ADMIN instanceof \core\admin\admin_root && $ADMIN->loaded) {
        return $ADMIN;
    }

    // Create new admin root
    $ADMIN = new \core\admin\admin_root($requirefulltree);

    // Check if user has site config capability
    $hassiteconfig = has_capability('nexosupport/admin:manageconfig');

    // Define settings directory
    $settingsdir = BASE_DIR . '/admin/settings';

    // ========================================================
    // LOAD SETTINGS FILES IN ORDER
    // ========================================================

    // 1. Load top.php FIRST - creates all main categories
    $topfile = $settingsdir . '/top.php';
    if (file_exists($topfile)) {
        include($topfile);
    }

    // 2. Load all other settings files (except top.php and plugins.php)
    $settingsfiles = glob($settingsdir . '/*.php');
    foreach ($settingsfiles as $file) {
        $filename = basename($file);

        // Skip top.php (already loaded) and plugins.php (load last)
        if ($filename === 'top.php' || $filename === 'plugins.php') {
            continue;
        }

        // Skip index.php and debugging.php (they are controller pages, not settings definitions)
        if ($filename === 'index.php' || $filename === 'debugging.php') {
            continue;
        }

        // Skip files that aren't settings definitions
        if (in_array($filename, ['http.php', 'maintenancemode.php', 'sessionhandling.php', 'systempaths.php'])) {
            continue;
        }

        include($file);
    }

    // 3. Load plugins.php LAST - loads all plugin settings
    $pluginsfile = $settingsdir . '/plugins.php';
    if (file_exists($pluginsfile)) {
        include($pluginsfile);
    }

    // Mark tree as loaded
    $ADMIN->loaded = true;

    return $ADMIN;
}

/**
 * Find a settings page by name
 *
 * @param string $name Page name
 * @return \core\admin\admin_settingpage|null Found page or null
 */
function admin_find_page(string $name): ?\core\admin\admin_settingpage {
    $root = admin_get_root();
    return $root->find_page($name);
}

/**
 * Find any node in the admin tree by name
 *
 * @param string $name Node name
 * @return \core\admin\part_of_admin_tree|null Found node or null
 */
function admin_locate(string $name): ?\core\admin\part_of_admin_tree {
    $root = admin_get_root();
    return $root->locate($name);
}

/**
 * Get all top-level categories
 *
 * @return \core\admin\admin_category[] Array of categories
 */
function admin_get_categories(): array {
    $root = admin_get_root();
    return $root->get_categories();
}

/**
 * Search the admin tree for a query
 *
 * @param string $query Search query
 * @return array Matching nodes
 */
function admin_search(string $query): array {
    $root = admin_get_root();
    return $root->search($query);
}

/**
 * Save all settings from form data for a page
 *
 * @param string $pagename Settings page name
 * @param array $data Form data
 * @return array Errors (empty if successful)
 */
function admin_save_settings(string $pagename, array $data): array {
    $page = admin_find_page($pagename);
    if (!$page) {
        return [get_string('pagenotfound', 'core')];
    }

    return $page->save_settings($data);
}

/**
 * Write a single setting value
 *
 * @param string $name Setting name (format: plugin/setting or just setting)
 * @param mixed $value Value to write
 * @return bool Success
 */
function admin_write_setting(string $name, $value): bool {
    // Parse plugin/name format
    if (strpos($name, '/') !== false) {
        list($plugin, $settingname) = explode('/', $name, 2);
    } else {
        $plugin = 'core';
        $settingname = $name;
    }

    return set_config($settingname, $value, $plugin);
}

/**
 * Read a single setting value
 *
 * @param string $name Setting name (format: plugin/setting or just setting)
 * @param mixed $default Default value if not set
 * @return mixed Setting value
 */
function admin_read_setting(string $name, $default = null) {
    // Parse plugin/name format
    if (strpos($name, '/') !== false) {
        list($plugin, $settingname) = explode('/', $name, 2);
    } else {
        $plugin = 'core';
        $settingname = $name;
    }

    $value = get_config($plugin, $settingname);
    return ($value !== null) ? $value : $default;
}

/**
 * Get navigation data for admin tree rendering
 *
 * @return array Navigation tree data for templates
 */
function admin_get_navigation_data(): array {
    $root = admin_get_root();
    return $root->get_template_data();
}

/**
 * Check if a page exists in the admin tree
 *
 * @param string $name Page name
 * @return bool True if exists
 */
function admin_page_exists(string $name): bool {
    return admin_find_page($name) !== null;
}

/**
 * Apply config defaults from admin tree
 *
 * Useful after installation to set initial config values.
 *
 * @return void
 */
function admin_apply_default_settings(): void {
    $root = admin_get_root();

    // Iterate through all categories and pages
    $apply_defaults = function($node) use (&$apply_defaults) {
        if ($node instanceof \core\admin\admin_settingpage) {
            foreach ($node->get_settings() as $setting) {
                // Skip headings
                if ($setting instanceof \core\admin\admin_setting_heading) {
                    continue;
                }

                // Check if setting already has a value
                $current = $setting->get_setting();
                if ($current === $setting->defaultsetting) {
                    // Write default to ensure it's in database
                    $setting->write_setting($setting->defaultsetting);
                }
            }
        } elseif ($node instanceof \core\admin\admin_category || $node instanceof \core\admin\admin_root) {
            if (method_exists($node, 'get_children')) {
                foreach ($node->get_children() as $child) {
                    $apply_defaults($child);
                }
            }
        }
    };

    $apply_defaults($root);
}


/**
 * Get available languages
 *
 * Helper function for language settings.
 *
 * @return array Language code => Language name
 */
function get_available_languages(): array {
    $langdir = BASE_DIR . '/lang';
    $languages = [];

    if (is_dir($langdir)) {
        $dirs = scandir($langdir);
        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..') {
                continue;
            }

            $langfile = $langdir . '/' . $dir . '/langconfig.php';
            if (file_exists($langfile)) {
                $string = [];
                include($langfile);
                $languages[$dir] = $string['thislanguage'] ?? $dir;
            } else {
                // Fallback to directory name
                $languages[$dir] = $dir;
            }
        }
    }

    // Ensure at least Spanish and English
    if (empty($languages)) {
        $languages = [
            'es' => 'Español',
            'en' => 'English',
        ];
    }

    return $languages;
}

/**
 * Setup an external admin page
 *
 * This function sets up the page for admin external pages like reports.
 * Similar to Moodle's admin_externalpage_setup().
 *
 * @param string $section The name of the admin page
 * @param string|array|null $extrabuttons Additional URL parameters
 * @param array|null $actualurl The actual URL if different from $PAGE->url
 * @param string|array $extraurlparams Extra URL parameters
 * @param array $options Additional options (pagelayout, etc.)
 * @return void
 */
function admin_externalpage_setup(string $section, $extrabuttons = null, $actualurl = null, $extraurlparams = '', array $options = []): void {
    global $CFG, $PAGE, $USER, $OUTPUT;

    // Require login first
    require_login();

    // Check user is admin
    if (!is_siteadmin()) {
        throw new \nexo_exception('accessdenied', 'core');
    }

    // Set up page layout
    $pagelayout = $options['pagelayout'] ?? 'admin';

    // Initialize PAGE if needed
    if (!isset($PAGE) || !($PAGE instanceof NexoPage)) {
        $PAGE = new NexoPage();
    }

    // Set page properties
    $PAGE->set_context(\core\rbac\context_system::instance());

    // Set URL if provided
    if ($actualurl !== null) {
        if (is_array($actualurl)) {
            $PAGE->set_url(new \core\nexo_url($actualurl[0], $actualurl[1] ?? []));
        } else {
            $PAGE->set_url(new \core\nexo_url($actualurl));
        }
    }

    // Set page section
    $PAGE->section = $section;
    $PAGE->pagelayout = $pagelayout;

    // Initialize OUTPUT if needed
    if (!isset($OUTPUT) || !($OUTPUT instanceof NexoOutput)) {
        $OUTPUT = new NexoOutput($PAGE);
    }
}

/**
 * Simple page object for NexoSupport
 */
class NexoPage {
    public $url;
    public $context;
    public $section;
    public $pagelayout;
    public $title = '';
    public $heading = '';

    public function set_url($url): void {
        $this->url = $url;
    }

    public function set_context($context): void {
        $this->context = $context;
    }

    public function set_title(string $title): void {
        $this->title = $title;
    }

    public function set_heading(string $heading): void {
        $this->heading = $heading;
    }
}

/**
 * Simple output object for NexoSupport
 */
class NexoOutput {
    private NexoPage $page;

    public function __construct(NexoPage $page) {
        $this->page = $page;
    }

    public function header(): string {
        $html = '<!DOCTYPE html><html><head>';
        $html .= '<meta charset="UTF-8">';
        $html .= '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
        $html .= '<title>' . htmlspecialchars($this->page->title ?: 'NexoSupport') . '</title>';
        $html .= '<style>
            body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
            .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
            h1, h2 { color: #333; }
            .lead { color: #666; font-size: 1.1em; }
            table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
            th { background: #f8f9fa; font-weight: 600; }
            .badge { padding: 4px 8px; border-radius: 4px; font-size: 0.85em; }
            .badge-success { background: #d4edda; color: #155724; }
            .badge-warning { background: #fff3cd; color: #856404; }
            .badge-danger { background: #f8d7da; color: #721c24; }
            .badge-info { background: #d1ecf1; color: #0c5460; }
        </style>';
        $html .= '</head><body><div class="container">';
        return $html;
    }

    public function footer(): string {
        return '</div></body></html>';
    }

    public function heading(string $text, int $level = 2): string {
        return "<h{$level}>" . htmlspecialchars($text) . "</h{$level}>";
    }

    public function notification(string $message, string $type = 'info'): string {
        return '<div class="alert alert-' . $type . '">' . htmlspecialchars($message) . '</div>';
    }
}

/**
 * Get system context instance
 *
 * Helper function to get system context.
 *
 * @return object System context object
 */
function context_system_instance(): object {
    return context_system::instance();
}
