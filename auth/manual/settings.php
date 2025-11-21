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
 * @license    Proprietary - NexoSupport
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
// Admin Settings Implementation (Phase 3 - ACTIVE)
// ============================================================================
// Settings are loaded dynamically by lib/adminlib.php
// This follows exactly the Moodle pattern, adapted to NexoSupport's architecture

if (isset($fulltree) && $fulltree) {

    // Password policy settings heading
    $settings->add(new \core\admin\admin_setting_heading(
        'auth_manual/passwordpolicy',
        get_string('passwordpolicy', 'auth_manual'),
        get_string('auth_manualdescription', 'auth_manual')
    ));

    // Minimum password length
    $settings->add(new \core\admin\admin_setting_configtext(
        'auth_manual/minpasswordlength',
        get_string('minpasswordlength', 'auth_manual'),
        get_string('minpasswordlength_help', 'auth_manual'),
        '8',
        10,
        'text'
    ));

    // Require uppercase letters
    $settings->add(new \core\admin\admin_setting_configcheckbox(
        'auth_manual/requireuppercase',
        get_string('requireuppercase', 'auth_manual'),
        get_string('requireuppercase_help', 'auth_manual'),
        0
    ));

    // Require lowercase letters
    $settings->add(new \core\admin\admin_setting_configcheckbox(
        'auth_manual/requirelowercase',
        get_string('requirelowercase', 'auth_manual'),
        get_string('requirelowercase_help', 'auth_manual'),
        0
    ));

    // Require numbers
    $settings->add(new \core\admin\admin_setting_configcheckbox(
        'auth_manual/requirenumbers',
        get_string('requirenumbers', 'auth_manual'),
        get_string('requirenumbers_help', 'auth_manual'),
        0
    ));

    // Require special characters
    $settings->add(new \core\admin\admin_setting_configcheckbox(
        'auth_manual/requirespecialchars',
        get_string('requirespecialchars', 'auth_manual'),
        get_string('requirespecialchars_help', 'auth_manual'),
        0
    ));

    // Note: Profile field locking is a Moodle feature for controlling which fields
    // can be updated locally vs synced from external auth sources.
    // This will be implemented in a future version if needed.
}
