<?php
/**
 * Role Capabilities Definition
 *
 * @package NexoSupport
 */

require_once(__DIR__ . '/../../config.php');

require_login();
require_capability('nexosupport/admin:manageroles');

global $USER, $CFG, $DB, $PAGE, $OUTPUT;

$roleid = required_param('id', PARAM_INT);

// Load role
$role = $DB->get_record('roles', ['id' => $roleid]);
if (!$role) {
    redirect('/admin/roles/', get_string('rolenotfound', 'core'));
}

$success = null;
$errors = [];

// Process form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();

    // Get all capabilities
    $capabilities = $DB->get_records('capabilities');

    // Clear existing permissions for this role
    $DB->delete_records('role_capabilities', ['roleid' => $roleid]);

    // Set new permissions
    foreach ($capabilities as $cap) {
        $permission = optional_param('cap_' . $cap->id, 0, PARAM_INT);
        if ($permission != 0) {
            $DB->insert_record('role_capabilities', [
                'roleid' => $roleid,
                'capability' => $cap->name,
                'permission' => $permission,
                'contextid' => 1, // System context
                'timemodified' => time(),
                'modifierid' => $USER->id,
            ]);
        }
    }

    $success = get_string('permissionsupdated', 'core');
}

// Get all capabilities grouped by component
$capabilities = $DB->get_records('capabilities', [], 'component ASC, name ASC');

// Get current role permissions
$currentperms = $DB->get_records('role_capabilities', ['roleid' => $roleid], '', 'capability, permission');
$permsbyname = [];
foreach ($currentperms as $perm) {
    $permsbyname[$perm->capability] = $perm->permission;
}

// Format capabilities for template
$capgroups = [];
foreach ($capabilities as $cap) {
    $component = $cap->component ?: 'core';
    if (!isset($capgroups[$component])) {
        $capgroups[$component] = [
            'component' => $component,
            'capabilities' => [],
        ];
    }
    $capgroups[$component]['capabilities'][] = [
        'id' => $cap->id,
        'name' => $cap->name,
        'displayname' => str_replace('/', ' / ', $cap->name),
        'permission' => $permsbyname[$cap->name] ?? 0,
        'allowed' => ($permsbyname[$cap->name] ?? 0) == 1,
        'prohibited' => ($permsbyname[$cap->name] ?? 0) == -1,
    ];
}

// Prepare context
$context = [
    'pagetitle' => get_string('definepermissions', 'core') . ': ' . $role->name,
    'showadmin' => true,
    'has_navigation' => true,
    'navigation_html' => get_navigation_html(),
    'role' => [
        'id' => $role->id,
        'name' => htmlspecialchars($role->name),
        'shortname' => htmlspecialchars($role->shortname),
    ],
    'capgroups' => array_values($capgroups),
    'success' => $success,
    'errors' => $errors,
    'haserrors' => !empty($errors),
    'sesskey' => sesskey(),
];

echo render_template('admin/role_define', $context);
