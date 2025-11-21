<?php
/**
 * Security settings
 *
 * @package NexoSupport
 */

defined('NEXOSUPPORT_INTERNAL') || die();

if ($hassiteconfig) {

    // ========== SESSION SETTINGS ==========
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

    $ADMIN->add('security', $sessionsettings);

    // ========== PASSWORD POLICY ==========
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
        32
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

    $passwordpolicy->add(new \core\admin\admin_setting_configcheckbox(
        'passwordrequirespecial',
        get_string('passwordrequirespecial', 'core'),
        get_string('passwordrequirespecialhelp', 'core'),
        '0'
    ));

    $ADMIN->add('security', $passwordpolicy);

    // ========== IP BLOCKER SETTINGS ==========
    $ipblocker = new \core\admin\admin_settingpage(
        'ipblocker',
        get_string('ipblocker', 'admin'),
        'nexosupport/admin:manageconfig'
    );

    $ipblocker->add(new \core\admin\admin_setting_heading(
        'ipblockerheading',
        get_string('ipblocker', 'admin'),
        get_string('ipblockerhelp', 'admin')
    ));

    $ipblocker->add(new \core\admin\admin_setting_configtextarea(
        'allowedips',
        get_string('allowedips', 'admin'),
        get_string('allowedipshelp', 'admin'),
        '',
        PARAM_RAW,
        60,
        5
    ));

    $ipblocker->add(new \core\admin\admin_setting_configtextarea(
        'blockedips',
        get_string('blockedips', 'admin'),
        get_string('blockedipshelp', 'admin'),
        '',
        PARAM_RAW,
        60,
        5
    ));

    $ADMIN->add('security', $ipblocker);
}
