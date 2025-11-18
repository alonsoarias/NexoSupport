<?php
/**
 * Admin settings and defaults
 *
 * @package auth_manual
 * @copyright NexoSupport
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('NEXOSUPPORT_INTERNAL') || die();

// NOTE: In Moodle, this file is loaded by the admin settings system
// and $settings is provided. For now, we define settings directly.
//
// TODO: Implement full admin_setting_* classes and admin settings tree
// like Moodle for better plugin configuration management.

if (!isset($settings)) {
    // If $settings doesn't exist, we're in standalone mode
    // This is a temporary implementation until we have the full admin settings system

    // For now, we store plugin config directly
    $config = [
        'minpasswordlength' => get_config('auth_manual', 'minpasswordlength') ?? 8,
        'requireuppercase' => get_config('auth_manual', 'requireuppercase') ?? 0,
        'requirelowercase' => get_config('auth_manual', 'requirelowercase') ?? 0,
        'requirenumbers' => get_config('auth_manual', 'requirenumbers') ?? 0,
        'requirespecialchars' => get_config('auth_manual', 'requirespecialchars') ?? 0,
    ];

    // In the future, this will be replaced with:
    // $settings->add(new admin_setting_configtext('auth_manual/minpasswordlength', ...));
    // $settings->add(new admin_setting_configcheckbox('auth_manual/requireuppercase', ...));
    // etc.
}

// Future implementation (like Moodle):
/*
if ($ADMIN->fulltree) {

    // Introductory explanation
    $settings->add(new admin_setting_heading('auth_manual/pluginname',
        get_string('passwordpolicy', 'auth_manual'),
        get_string('auth_manualdescription', 'auth_manual')));

    // Minimum password length
    $settings->add(new admin_setting_configtext('auth_manual/minpasswordlength',
        get_string('minpasswordlength', 'auth_manual'),
        get_string('minpasswordlength_help', 'auth_manual'),
        8, PARAM_INT));

    // Require uppercase
    $settings->add(new admin_setting_configcheckbox('auth_manual/requireuppercase',
        get_string('requireuppercase', 'auth_manual'),
        get_string('requireuppercase_help', 'auth_manual'),
        0));

    // Require lowercase
    $settings->add(new admin_setting_configcheckbox('auth_manual/requirelowercase',
        get_string('requirelowercase', 'auth_manual'),
        get_string('requirelowercase_help', 'auth_manual'),
        0));

    // Require numbers
    $settings->add(new admin_setting_configcheckbox('auth_manual/requirenumbers',
        get_string('requirenumbers', 'auth_manual'),
        get_string('requirenumbers_help', 'auth_manual'),
        0));

    // Require special characters
    $settings->add(new admin_setting_configcheckbox('auth_manual/requirespecialchars',
        get_string('requirespecialchars', 'auth_manual'),
        get_string('requirespecialchars_help', 'auth_manual'),
        0));
}
*/
