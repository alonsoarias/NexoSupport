<?php
/**
 * Email factor settings.
 *
 * @package    factor_email
 * @copyright  NexoSupport
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('NEXOSUPPORT_INTERNAL') || die();

// This file defines the admin settings for factor_email

$settings = [];

// Enable factor
$settings['factor_email/enabled'] = [
    'type' => 'checkbox',
    'name' => get_string('settings:enablefactor', 'tool_mfa'),
    'description' => get_string('settings:enablefactor_help', 'tool_mfa'),
    'default' => 0,
];

// Weight
$settings['factor_email/weight'] = [
    'type' => 'text',
    'name' => get_string('settings:weight', 'tool_mfa'),
    'description' => get_string('settings:weight_help', 'tool_mfa'),
    'default' => 100,
];

// Code duration (in seconds)
$settings['factor_email/duration'] = [
    'type' => 'duration',
    'name' => get_string('settings:duration', 'factor_email'),
    'description' => get_string('settings:duration_help', 'factor_email'),
    'default' => 1800, // 30 minutes
];

// Suspend account on block
$settings['factor_email/suspend'] = [
    'type' => 'checkbox',
    'name' => get_string('settings:suspend', 'factor_email'),
    'description' => get_string('settings:suspend_help', 'factor_email'),
    'default' => 0,
];

// Order
$settings['factor_email/order'] = [
    'type' => 'text',
    'name' => 'Order',
    'description' => 'Display order for this factor',
    'default' => 100,
];

return $settings;
