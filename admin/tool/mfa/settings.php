<?php
/**
 * MFA plugin settings.
 *
 * @package    tool_mfa
 * @copyright  NexoSupport
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('NEXOSUPPORT_INTERNAL') || die();

// This file defines the admin settings for tool_mfa
// In a full implementation, this would be loaded by the admin settings framework

$settings = [];

// Enable MFA
$settings['tool_mfa/enabled'] = [
    'type' => 'checkbox',
    'name' => get_string('settings:enabled', 'tool_mfa'),
    'description' => get_string('settings:enabled_help', 'tool_mfa'),
    'default' => 0,
];

// Lockout threshold
$settings['tool_mfa/lockout'] = [
    'type' => 'text',
    'name' => get_string('settings:lockout', 'tool_mfa'),
    'description' => get_string('settings:lockout_help', 'tool_mfa'),
    'default' => 10,
];

// Exempt administrators
$settings['tool_mfa/exemptadmins'] = [
    'type' => 'checkbox',
    'name' => get_string('settings:exemptadmins', 'tool_mfa'),
    'description' => get_string('settings:exemptadmins_help', 'tool_mfa'),
    'default' => 0,
];

// URL exclusions
$settings['tool_mfa/redir_exclusions'] = [
    'type' => 'textarea',
    'name' => get_string('settings:redir_exclusions', 'tool_mfa'),
    'description' => get_string('settings:redir_exclusions_help', 'tool_mfa'),
    'default' => '',
];

return $settings;
