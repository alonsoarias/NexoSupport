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

    $maintenance = optional_param('maintenance', 0, PARAM_INT);
    $maintenancemessage = optional_param('maintenancemessage', '', PARAM_TEXT);

    set_config('maintenance_enabled', $maintenance);
    set_config('maintenance_message', $maintenancemessage);

    $success = get_string('changessaved', 'admin');
}

// Get current settings
$maintenance = get_config('core', 'maintenance_enabled') ?? 0;
$maintenancemessage = get_config('core', 'maintenance_message') ?? '';

// Prepare context
$context = [
    'pagetitle' => get_string('maintenancemode', 'admin'),
    'showadmin' => true,
    'has_navigation' => true,
    'navigation_html' => get_navigation_html(),
    'maintenance' => $maintenance,
    'maintenancemessage' => htmlspecialchars($maintenancemessage),
    'success' => $success,
    'errors' => $errors,
    'haserrors' => !empty($errors),
    'sesskey' => sesskey(),
];

echo render_template('admin/settings_maintenance', $context);
