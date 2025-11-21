<?php
/**
 * Maintenance Mode Settings - NexoSupport
 *
 * Configuration page for site maintenance mode.
 *
 * @package core
 * @subpackage admin
 */

require_once(__DIR__ . '/../../config.php');

require_login();
require_capability('nexosupport/admin:manageconfig');

global $USER, $CFG;

$success = null;
$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && confirm_sesskey()) {
    $maintenance_enabled = optional_param('maintenance_enabled', 0, PARAM_INT);
    $maintenance_message = optional_param('maintenance_message', '', PARAM_RAW);

    set_config('maintenance_enabled', $maintenance_enabled, 'core');
    set_config('maintenance_message', $maintenance_message, 'core');

    $success = get_string('configsaved', 'core');
    redirect('/admin/settings/maintenancemode', $success);
}

// Get current settings
$current_enabled = get_config('core', 'maintenance_enabled') ?? 0;
$current_message = get_config('core', 'maintenance_message') ?? '';

// Prepare context for template
$context = [
    'sesskey' => sesskey(),
    'success' => $success,
    'errors' => $errors,
    'haserrors' => !empty($errors),
    'maintenance_enabled' => (bool)$current_enabled,
    'maintenance_message' => htmlspecialchars($current_message),
    'default_message' => get_string('sitemaintenancewarning', 'admin'),
    'pagetitle' => get_string('maintenancemode', 'admin'),
    'has_navigation' => true,
    'navigation_html' => get_navigation_html(),
];

echo render_template('admin/settings_maintenancemode', $context);
