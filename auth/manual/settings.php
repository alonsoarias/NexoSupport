<?php
/**
 * Admin settings and defaults for auth_manual
 *
 * This file defines the settings for the manual authentication plugin.
 * Following Moodle's pattern, this is a SETTINGS DEFINITION FILE, not a web page.
 *
 * In Moodle, this file is loaded by admin/settings/plugins.php and provides
 * the $settings object where configuration options are added.
 *
 * @package auth_manual
 * @copyright NexoSupport
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('NEXOSUPPORT_INTERNAL') || die();

// ============================================================================
// IMPORTANT: This is a settings definition file, NOT a web page
// ============================================================================
// This file should NOT:
// - Output any HTML directly
// - Process POST data
// - Have its own URL route
//
// This file SHOULD:
// - Define configuration options using $settings->add()
// - Set default values
// - Provide lang string references
//
// Pattern from Moodle's auth/manual/settings.php:
//
// if ($ADMIN->fulltree) {
//     $settings->add(new admin_setting_heading(...));
//     $settings->add(new admin_setting_configtext(...));
//     $settings->add(new admin_setting_configcheckbox(...));
// }
// ============================================================================

// Set default configuration values if not already set
// This ensures the plugin works even before settings are configured
if (!get_config('auth_manual', 'minpasswordlength')) {
    set_config('minpasswordlength', 8, 'auth_manual');
}
if (!get_config('auth_manual', 'requireuppercase')) {
    set_config('requireuppercase', 0, 'auth_manual');
}
if (!get_config('auth_manual', 'requirelowercase')) {
    set_config('requirelowercase', 0, 'auth_manual');
}
if (!get_config('auth_manual', 'requirenumbers')) {
    set_config('requirenumbers', 0, 'auth_manual');
}
if (!get_config('auth_manual', 'requirespecialchars')) {
    set_config('requirespecialchars', 0, 'auth_manual');
}

// ============================================================================
// Future implementation with admin_setting_* classes (Phase 3)
// ============================================================================
// When we implement the admin settings tree system in Phase 3, this section
// will be uncommented and will work exactly like Moodle:
/*
if ($ADMIN->fulltree) {

    // Password policy settings heading
    $settings->add(new admin_setting_heading(
        'auth_manual/passwordpolicy',
        new lang_string('passwordpolicy', 'auth_manual'),
        new lang_string('auth_manualdescription', 'auth_manual')
    ));

    // Minimum password length
    $settings->add(new admin_setting_configtext(
        'auth_manual/minpasswordlength',
        new lang_string('minpasswordlength', 'auth_manual'),
        new lang_string('minpasswordlength_help', 'auth_manual'),
        8,
        PARAM_INT
    ));

    // Require uppercase letters
    $settings->add(new admin_setting_configcheckbox(
        'auth_manual/requireuppercase',
        new lang_string('requireuppercase', 'auth_manual'),
        new lang_string('requireuppercase_help', 'auth_manual'),
        0
    ));

    // Require lowercase letters
    $settings->add(new admin_setting_configcheckbox(
        'auth_manual/requirelowercase',
        new lang_string('requirelowercase', 'auth_manual'),
        new lang_string('requirelowercase_help', 'auth_manual'),
        0
    ));

    // Require numbers
    $settings->add(new admin_setting_configcheckbox(
        'auth_manual/requirenumbers',
        new lang_string('requirenumbers', 'auth_manual'),
        new lang_string('requirenumbers_help', 'auth_manual'),
        0
    ));

    // Require special characters
    $settings->add(new admin_setting_configcheckbox(
        'auth_manual/requirespecialchars',
        new lang_string('requirespecialchars', 'auth_manual'),
        new lang_string('requirespecialchars_help', 'auth_manual'),
        0
    ));

    // Display locking / mapping of profile fields
    // This is a Moodle feature to control which fields can be updated locally
    // vs synced from external auth source
    // $authplugin = get_auth_plugin('manual');
    // display_auth_lock_options($settings, $authplugin->authtype,
    //     $authplugin->userfields, get_string('auth_fieldlocks_help', 'auth'),
    //     false, false);
}
*/
