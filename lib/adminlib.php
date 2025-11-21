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
 * Get enabled authentication plugins
 *
 * Helper function for plugin settings loading.
 *
 * @return array List of enabled auth plugin names
 */
function get_enabled_auth_plugins(): array {
    global $CFG;

    $plugins = [];

    // Check if auth directory exists
    $authdir = BASE_DIR . '/auth';
    if (!is_dir($authdir)) {
        return $plugins;
    }

    // Get enabled auth plugins from config
    $enabledplugins = get_config('core', 'auth') ?? 'manual';
    $enabledlist = explode(',', $enabledplugins);

    // Scan auth directory
    $dirs = scandir($authdir);
    foreach ($dirs as $dir) {
        if ($dir === '.' || $dir === '..') {
            continue;
        }

        $plugindir = $authdir . '/' . $dir;
        if (is_dir($plugindir) && in_array($dir, $enabledlist)) {
            $plugins[] = $dir;
        }
    }

    return $plugins;
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
