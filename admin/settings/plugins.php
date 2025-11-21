<?php
/**
 * Plugin settings loader
 *
 * This file loads settings from all installed plugins.
 * It MUST be loaded LAST after all core settings files.
 *
 * Following Moodle's pattern: /admin/settings/plugins.php
 *
 * @package NexoSupport
 */

defined('NEXOSUPPORT_INTERNAL') || die();

// Define $hassiteconfig if not already defined (should be set by calling script)
if (!isset($hassiteconfig)) {
    $hassiteconfig = is_siteadmin();
}

// Only load if user has config capability
if (!$hassiteconfig) {
    return;
}

// ========================================================
// AUTHENTICATION PLUGINS
// ========================================================
$authplugins = get_enabled_auth_plugins();

foreach ($authplugins as $authplugin) {
    $settingsfile = BASE_DIR . "/auth/{$authplugin}/settings.php";

    if (file_exists($settingsfile)) {
        // Create settings page for this plugin
        $settings = new \core\admin\admin_settingpage(
            "auth_{$authplugin}",
            get_string('pluginname', "auth_{$authplugin}"),
            'nexosupport/admin:manageconfig'
        );

        // Set fulltree flag (Moodle compatibility)
        $fulltree = true;

        // Load the settings file
        // Plugin's settings.php should add settings to $settings object
        include($settingsfile);

        // Only add the page if it has settings
        if (!empty($settings->settings)) {
            $ADMIN->add('authsettings', $settings);
        }
    }
}

// ========================================================
// LOCAL PLUGINS
// ========================================================
$localpath = BASE_DIR . '/local';
if (is_dir($localpath)) {
    $plugins = scandir($localpath);

    foreach ($plugins as $plugin) {
        if ($plugin === '.' || $plugin === '..') {
            continue;
        }

        $plugindir = $localpath . '/' . $plugin;
        $settingsfile = $plugindir . '/settings.php';

        if (is_dir($plugindir) && file_exists($settingsfile)) {
            // Create settings page for this plugin
            $settings = new \core\admin\admin_settingpage(
                "local_{$plugin}",
                get_string('pluginname', "local_{$plugin}"),
                'nexosupport/admin:manageconfig'
            );

            // Set variables for plugin's settings.php
            $fulltree = true;

            // Load the settings file
            include($settingsfile);

            // Only add the page if it has settings
            if (!empty($settings->settings)) {
                $ADMIN->add('localplugins', $settings);
            }
        }
    }
}

// ========================================================
// BLOCK PLUGINS
// ========================================================
$blockpath = BASE_DIR . '/blocks';
if (is_dir($blockpath)) {

    // Create blocks category if needed
    $blockscat = $ADMIN->locate('blocksettings');
    if (!$blockscat) {
        $ADMIN->add('plugins', new \core\admin\admin_category(
            'blocksettings',
            get_string('blocks', 'admin')
        ));
    }

    $plugins = scandir($blockpath);

    foreach ($plugins as $plugin) {
        if ($plugin === '.' || $plugin === '..') {
            continue;
        }

        $plugindir = $blockpath . '/' . $plugin;
        $settingsfile = $plugindir . '/settings.php';

        if (is_dir($plugindir) && file_exists($settingsfile)) {
            $settings = new \core\admin\admin_settingpage(
                "block_{$plugin}",
                get_string('pluginname', "block_{$plugin}"),
                'nexosupport/admin:manageconfig'
            );

            $fulltree = true;
            include($settingsfile);

            if (!empty($settings->settings)) {
                $ADMIN->add('blocksettings', $settings);
            }
        }
    }
}

// ========================================================
// THEME PLUGINS
// ========================================================
$themepath = BASE_DIR . '/theme';
if (is_dir($themepath)) {

    // Create themes category if needed
    $themescat = $ADMIN->locate('themes');
    if (!$themescat) {
        $ADMIN->add('plugins', new \core\admin\admin_category(
            'themes',
            get_string('themes', 'admin')
        ));
    }

    $plugins = scandir($themepath);

    foreach ($plugins as $plugin) {
        if ($plugin === '.' || $plugin === '..') {
            continue;
        }

        $plugindir = $themepath . '/' . $plugin;
        $settingsfile = $plugindir . '/settings.php';

        if (is_dir($plugindir) && file_exists($settingsfile)) {
            $settings = new \core\admin\admin_settingpage(
                "theme_{$plugin}",
                get_string('pluginname', "theme_{$plugin}"),
                'nexosupport/admin:manageconfig'
            );

            $fulltree = true;
            include($settingsfile);

            if (!empty($settings->settings)) {
                $ADMIN->add('themes', $settings);
            }
        }
    }
}
