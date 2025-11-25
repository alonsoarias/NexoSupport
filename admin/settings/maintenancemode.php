<?php
/**
 * Maintenance Mode Settings
 *
 * @package NexoSupport
 */

require_once(__DIR__ . '/../../config.php');

require_login();
require_capability('nexosupport/admin:manageconfig');

global $USER, $CFG, $DB, $PAGE, $OUTPUT;

$success = null;
$errors = [];

// Process form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();

    $maintenance_enabled = optional_param('maintenance_enabled', 0, PARAM_INT);
    $maintenance_message = optional_param('maintenance_message', '', PARAM_TEXT);

    set_config('maintenance_enabled', $maintenance_enabled);
    set_config('maintenance_message', $maintenance_message);

    $success = get_string('changessaved', 'admin');
}

// Get current settings
$maintenance_enabled = (int)(get_config('core', 'maintenance_enabled') ?? 0);
$maintenance_message = get_config('core', 'maintenance_message') ?? '';

// Prepare context
$context = [
    'pagetitle' => get_string('maintenancemode', 'admin'),
    'showadmin' => true,
    'has_navigation' => true,
    'navigation_html' => get_navigation_html(),
    'maintenance_enabled' => $maintenance_enabled == 1,
    'maintenance_message' => htmlspecialchars($maintenance_message),
    'default_message' => get_string('saboringmaintenance', 'admin'),
    'success' => $success,
    'errors' => $errors,
    'haserrors' => !empty($errors),
    'sesskey' => sesskey(),
];

echo render_template('admin/settings_maintenancemode', $context);
