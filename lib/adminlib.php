<?php
/**
 * Admin Settings Tree
 *
 * Functions for building and managing the admin settings tree.
 * Similar to Moodle's admin/settings/... structure.
 *
 * @package NexoSupport
 */

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Build and return the admin settings tree
 *
 * Creates the complete hierarchy of categories and settings pages
 * following Moodle's pattern.
 *
 * @return \core\admin\admin_category Root category
 */
function admin_get_root(): \core\admin\admin_category {
    global $ADMIN;

    // Return cached tree if exists
    if (isset($ADMIN) && $ADMIN instanceof \core\admin\admin_category) {
        return $ADMIN;
    }

    // Create root category
    $root = new \core\admin\admin_category('root', get_string('administration', 'core'));

    // ========== GENERAL CATEGORY ==========
    $general = new \core\admin\admin_category('general', get_string('generalsettings', 'core'));

    // General settings page
    $generalsettings = new \core\admin\admin_settingpage(
        'generalsettings',
        get_string('generalsettings', 'core'),
        'nexosupport/admin:manageconfig'
    );

    $generalsettings->add(new \core\admin\admin_setting_heading(
        'generalsettingsheading',
        get_string('generalsettings', 'core'),
        get_string('configgeneralsettings', 'core')
    ));

    $generalsettings->add(new \core\admin\admin_setting_configtext(
        'sitename',
        get_string('sitename', 'core'),
        get_string('sitenamehelp', 'core'),
        'NexoSupport',
        50
    ));

    $generalsettings->add(new \core\admin\admin_setting_configtext(
        'sitedescription',
        get_string('sitedescription', 'core'),
        get_string('sitedescriptionhelp', 'core'),
        '',
        100
    ));

    $general->add_page($generalsettings);
    $root->add_category($general);

    // ========== USERS CATEGORY ==========
    $users = new \core\admin\admin_category('users', get_string('users', 'core'));

    // User settings page
    $usersettings = new \core\admin\admin_settingpage(
        'usersettings',
        get_string('usersettings', 'core'),
        'nexosupport/admin:manageusers'
    );

    $usersettings->add(new \core\admin\admin_setting_heading(
        'usersettingsheading',
        get_string('usersettings', 'core'),
        get_string('configusersettings', 'core')
    ));

    $usersettings->add(new \core\admin\admin_setting_configtext(
        'defaultlang',
        get_string('defaultlang', 'core'),
        get_string('defaultlanghelp', 'core'),
        'es',
        10
    ));

    $usersettings->add(new \core\admin\admin_setting_configcheckbox(
        'requireconfirmemail',
        get_string('requireconfirmemail', 'core'),
        get_string('requireconfirmemailhelp', 'core'),
        '0'
    ));

    $users->add_page($usersettings);
    $root->add_category($users);

    // ========== SECURITY CATEGORY ==========
    $security = new \core\admin\admin_category('security', get_string('security', 'core'));

    // Session settings page
    $sessionsettings = new \core\admin\admin_settingpage(
        'sessionsettings',
        get_string('sessions', 'core'),
        'nexosupport/admin:manageconfig'
    );

    $sessionsettings->add(new \core\admin\admin_setting_heading(
        'sessionsettingsheading',
        get_string('sessions', 'core'),
        get_string('configsessionsettings', 'core')
    ));

    $sessionsettings->add(new \core\admin\admin_setting_confignumber(
        'sessiontimeout',
        get_string('sessiontimeout', 'core'),
        get_string('sessiontimeouthelp', 'core'),
        7200,
        10,
        600,    // min 10 minutes
        86400   // max 24 hours
    ));

    $sessionsettings->add(new \core\admin\admin_setting_confignumber(
        'sessioncookie',
        get_string('sessioncookie', 'core'),
        get_string('sessioncookiehelp', 'core'),
        86400,
        10,
        3600,
        2592000
    ));

    $security->add_page($sessionsettings);

    // Password policy page
    $passwordpolicy = new \core\admin\admin_settingpage(
        'passwordpolicy',
        get_string('passwordpolicy', 'core'),
        'nexosupport/admin:manageconfig'
    );

    $passwordpolicy->add(new \core\admin\admin_setting_heading(
        'passwordpolicyheading',
        get_string('passwordpolicy', 'core'),
        get_string('configpasswordpolicy', 'core')
    ));

    $passwordpolicy->add(new \core\admin\admin_setting_confignumber(
        'minpasswordlength',
        get_string('minpasswordlength', 'core'),
        get_string('minpasswordlengthhelp', 'core'),
        8,
        5,
        4,
        16
    ));

    $passwordpolicy->add(new \core\admin\admin_setting_configcheckbox(
        'passwordrequiredigit',
        get_string('passwordrequiredigit', 'core'),
        get_string('passwordrequiredigithelp', 'core'),
        '1'
    ));

    $passwordpolicy->add(new \core\admin\admin_setting_configcheckbox(
        'passwordrequirelower',
        get_string('passwordrequirelower', 'core'),
        get_string('passwordrequirelowerhelp', 'core'),
        '1'
    ));

    $passwordpolicy->add(new \core\admin\admin_setting_configcheckbox(
        'passwordrequireupper',
        get_string('passwordrequireupper', 'core'),
        get_string('passwordrequireupperhelp', 'core'),
        '1'
    ));

    $security->add_page($passwordpolicy);
    $root->add_category($security);

    // ========== DEVELOPMENT CATEGORY ==========
    $development = new \core\admin\admin_category('development', get_string('developmentsettings', 'core'));

    // Debug settings page
    $debugsettings = new \core\admin\admin_settingpage(
        'debugsettings',
        get_string('debugmode', 'core'),
        'nexosupport/admin:manageconfig'
    );

    $debugsettings->add(new \core\admin\admin_setting_heading(
        'debugsettingsheading',
        get_string('debugmode', 'core'),
        get_string('configdebugsettings', 'core')
    ));

    $debugsettings->add(new \core\admin\admin_setting_configcheckbox(
        'debug',
        get_string('debugmode', 'core'),
        get_string('debughelp', 'core'),
        '0'
    ));

    $debugsettings->add(new \core\admin\admin_setting_configcheckbox(
        'debugdisplay',
        get_string('debugdisplay', 'core'),
        get_string('debugdisplayhelp', 'core'),
        '0'
    ));

    $development->add_page($debugsettings);
    $root->add_category($development);

    // ========== PLUGINS CATEGORY ==========
    $plugins = new \core\admin\admin_category('plugins', get_string('plugins', 'core'));

    // Authentication plugins
    $auth = new \core\admin\admin_category('authentication', get_string('authentication', 'core'));

    // Load auth plugin settings
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
            // This file will add settings to $settings object
            include($settingsfile);

            // Only add the page if it has settings
            if (!empty($settings->settings)) {
                $auth->add_page($settings);
            }
        }
    }

    // Add authentication category if it has pages
    if (!empty($auth->get_pages())) {
        $plugins->add_category($auth);
    }

    // Add plugins category if it has content
    if (!empty($plugins->get_categories())) {
        $root->add_category($plugins);
    }

    // Cache for future calls
    $ADMIN = $root;

    return $root;
}

/**
 * Find a settings page by name
 *
 * @param string $name Page name
 * @return \core\admin\admin_settingpage|null Found page
 */
function admin_find_page(string $name): ?\core\admin\admin_settingpage {
    $root = admin_get_root();
    return $root->find_page($name);
}

/**
 * Get all categories
 *
 * @return array Array of categories
 */
function admin_get_categories(): array {
    $root = admin_get_root();
    return $root->get_categories();
}

/**
 * Save all settings from form data
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
