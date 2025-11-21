<?php
/**
 * Development/debugging settings
 *
 * @package NexoSupport
 */

defined('NEXOSUPPORT_INTERNAL') || die();

if ($hassiteconfig) {

    // ========== DEBUG SETTINGS ==========
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

    // Debug mode select
    $debugoptions = [
        '0' => get_string('debugnone', 'admin'),
        '1' => get_string('debugminimal', 'admin'),
        '2' => get_string('debugnormal', 'admin'),
        '3' => get_string('debugall', 'admin'),
        '4' => get_string('debugdeveloper', 'admin'),
    ];

    $debugsettings->add(new \core\admin\admin_setting_configselect(
        'debug',
        get_string('debug', 'admin'),
        get_string('debughelp', 'admin'),
        '0',
        $debugoptions
    ));

    $debugsettings->add(new \core\admin\admin_setting_configcheckbox(
        'debugdisplay',
        get_string('debugdisplay', 'core'),
        get_string('debugdisplayhelp', 'core'),
        '0'
    ));

    $debugsettings->add(new \core\admin\admin_setting_configcheckbox(
        'perfdebug',
        get_string('perfdebug', 'admin'),
        get_string('perfdebughelp', 'admin'),
        '0'
    ));

    $ADMIN->add('development', $debugsettings);

    // ========== EXTERNAL DEBUGGING PAGE ==========
    $ADMIN->add('development', new \core\admin\admin_externalpage(
        'debuggingpage',
        get_string('debugging', 'core'),
        '/admin/settings/debugging',
        'nexosupport/admin:manageconfig'
    ));
}
