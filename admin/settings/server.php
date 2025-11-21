<?php
/**
 * Server settings
 *
 * @package NexoSupport
 */

defined('NEXOSUPPORT_INTERNAL') || die();

if ($hassiteconfig) {

    // ========== SYSTEM PATHS ==========
    $systempaths = new \core\admin\admin_settingpage(
        'systempaths',
        get_string('systempaths', 'admin'),
        'nexosupport/admin:manageconfig'
    );

    $systempaths->add(new \core\admin\admin_setting_heading(
        'systempathsheading',
        get_string('systempaths', 'admin'),
        get_string('systempathshelp', 'admin')
    ));

    global $CFG;
    $systempaths->add(new \core\admin\admin_setting_configtext(
        'dataroot',
        get_string('dataroot', 'admin'),
        get_string('dataroothelp', 'admin'),
        $CFG->dataroot ?? '',
        100
    ));

    $systempaths->add(new \core\admin\admin_setting_configtext(
        'tempdir',
        get_string('tempdir', 'admin'),
        get_string('tempdirhelp', 'admin'),
        '',
        100
    ));

    $systempaths->add(new \core\admin\admin_setting_configtext(
        'cachedir',
        get_string('cachedir', 'admin'),
        get_string('cachedirhelp', 'admin'),
        '',
        100
    ));

    $ADMIN->add('server', $systempaths);

    // ========== HTTP SETTINGS ==========
    $httpsettings = new \core\admin\admin_settingpage(
        'httpsettings',
        get_string('http', 'admin'),
        'nexosupport/admin:manageconfig'
    );

    $httpsettings->add(new \core\admin\admin_setting_heading(
        'httpsettingsheading',
        get_string('http', 'admin'),
        get_string('httphelp', 'admin')
    ));

    $httpsettings->add(new \core\admin\admin_setting_configtext(
        'wwwroot',
        get_string('wwwroot', 'admin'),
        get_string('wwwroothelp', 'admin'),
        $CFG->wwwroot ?? '',
        100,
        'url'
    ));

    $httpsettings->add(new \core\admin\admin_setting_configcheckbox(
        'sslproxy',
        get_string('sslproxy', 'admin'),
        get_string('sslproxyhelp', 'admin'),
        '0'
    ));

    // Proxy settings
    $httpsettings->add(new \core\admin\admin_setting_heading(
        'proxyheading',
        get_string('proxysettings', 'admin'),
        get_string('proxysettingshelp', 'admin')
    ));

    $httpsettings->add(new \core\admin\admin_setting_configtext(
        'proxyhost',
        get_string('proxyhost', 'admin'),
        get_string('proxyhosthelp', 'admin'),
        '',
        50
    ));

    $httpsettings->add(new \core\admin\admin_setting_confignumber(
        'proxyport',
        get_string('proxyport', 'admin'),
        get_string('proxypor'.'thelp', 'admin'),
        0,
        10,
        0,
        65535
    ));

    $ADMIN->add('server', $httpsettings);

    // ========== MAINTENANCE MODE ==========
    $maintenance = new \core\admin\admin_settingpage(
        'maintenancemode',
        get_string('maintenancemode', 'admin'),
        'nexosupport/admin:manageconfig'
    );

    $maintenance->add(new \core\admin\admin_setting_heading(
        'maintenancemodeheading',
        get_string('maintenancemode', 'admin'),
        get_string('maintenancemodehelp', 'admin')
    ));

    $maintenance->add(new \core\admin\admin_setting_configcheckbox(
        'maintenance_enabled',
        get_string('enablemaintenancemode', 'admin'),
        get_string('enablemaintenancemodehelp', 'admin'),
        '0'
    ));

    $maintenance->add(new \core\admin\admin_setting_configtextarea(
        'maintenance_message',
        get_string('maintenancemessage', 'admin'),
        get_string('maintenancemessagehelp', 'admin'),
        get_string('sitemaintenancewarning', 'admin'),
        PARAM_RAW,
        60,
        4
    ));

    $ADMIN->add('server', $maintenance);

    // ========== EXTERNAL PAGES ==========
    $ADMIN->add('server', new \core\admin\admin_externalpage(
        'phpinfo',
        get_string('phpinfo', 'admin'),
        '/admin/phpinfo',
        'nexosupport/admin:manageconfig'
    ));

    $ADMIN->add('server', new \core\admin\admin_externalpage(
        'environment',
        get_string('environment', 'admin'),
        '/admin/environment',
        'nexosupport/admin:manageconfig'
    ));

    $ADMIN->add('server', new \core\admin\admin_externalpage(
        'purgecaches',
        get_string('purgecaches', 'admin'),
        '/admin/cache/purge',
        'nexosupport/admin:manageconfig'
    ));
}
