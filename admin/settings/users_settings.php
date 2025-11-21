<?php
/**
 * User management settings
 *
 * @package NexoSupport
 */

defined('NEXOSUPPORT_INTERNAL') || die();

if ($hassiteconfig) {

    // External pages for user management
    $ADMIN->add('users', new \core\admin\admin_externalpage(
        'useradd',
        get_string('addnewuser', 'admin'),
        '/admin/user/edit',
        'nexosupport/admin:manageusers'
    ));

    $ADMIN->add('users', new \core\admin\admin_externalpage(
        'userbrowse',
        get_string('browselistofusers', 'admin'),
        '/admin/user',
        'nexosupport/admin:manageusers'
    ));

    // User settings page
    $settings = new \core\admin\admin_settingpage(
        'usersettings',
        get_string('usersettings', 'core'),
        'nexosupport/admin:manageconfig'
    );

    $settings->add(new \core\admin\admin_setting_heading(
        'usersettingsheading',
        get_string('usersettings', 'core'),
        get_string('configusersettings', 'core')
    ));

    // Default language
    $languages = get_available_languages();
    $settings->add(new \core\admin\admin_setting_configselect(
        'defaultlang',
        get_string('defaultlang', 'core'),
        get_string('defaultlanghelp', 'core'),
        'es',
        $languages
    ));

    // Require email confirmation
    $settings->add(new \core\admin\admin_setting_configcheckbox(
        'requireconfirmemail',
        get_string('requireconfirmemail', 'core'),
        get_string('requireconfirmemailhelp', 'core'),
        '0'
    ));

    // Allow self registration
    $settings->add(new \core\admin\admin_setting_configcheckbox(
        'allowselfregistration',
        get_string('allowselfregistration', 'admin'),
        get_string('allowselfregistrationhelp', 'admin'),
        '0'
    ));

    $ADMIN->add('users', $settings);

    // ========== ROLES MANAGEMENT ==========
    $ADMIN->add('roles', new \core\admin\admin_externalpage(
        'defineroles',
        get_string('defineroles', 'admin'),
        '/admin/roles',
        'nexosupport/admin:manageroles'
    ));

    $ADMIN->add('roles', new \core\admin\admin_externalpage(
        'assignroles',
        get_string('assignroles', 'admin'),
        '/admin/roles/assign',
        'nexosupport/admin:manageroles'
    ));
}
