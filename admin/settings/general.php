<?php
/**
 * General site settings
 *
 * @package NexoSupport
 */

defined('NEXOSUPPORT_INTERNAL') || die();

// Only add settings if user has config capability
if ($hassiteconfig) {

    // General settings page
    $settings = new \core\admin\admin_settingpage(
        'generalsettings',
        get_string('generalsettings', 'core'),
        'nexosupport/admin:manageconfig'
    );

    // Site name
    $settings->add(new \core\admin\admin_setting_heading(
        'sitenameheading',
        get_string('sitesettings', 'core'),
        get_string('sitesettingshelp', 'core')
    ));

    $settings->add(new \core\admin\admin_setting_configtext(
        'sitename',
        get_string('sitename', 'core'),
        get_string('sitenamehelp', 'core'),
        'NexoSupport',
        50
    ));

    $settings->add(new \core\admin\admin_setting_configtextarea(
        'sitedescription',
        get_string('sitedescription', 'core'),
        get_string('sitedescriptionhelp', 'core'),
        '',
        PARAM_TEXT,
        60,
        3
    ));

    // Support contact
    $settings->add(new \core\admin\admin_setting_heading(
        'supportcontactheading',
        get_string('supportcontact', 'admin'),
        get_string('supportcontacthelp', 'admin')
    ));

    $settings->add(new \core\admin\admin_setting_configtext(
        'supportname',
        get_string('supportname', 'admin'),
        get_string('supportnamehelp', 'admin'),
        'Support',
        50
    ));

    $settings->add(new \core\admin\admin_setting_configtext(
        'supportemail',
        get_string('supportemail', 'admin'),
        get_string('supportemailhelp', 'admin'),
        '',
        50,
        'email'
    ));

    $ADMIN->add('general', $settings);
}
