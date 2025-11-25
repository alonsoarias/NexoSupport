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

$roleid = required_param('roleid', PARAM_INT);

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
$components = [];
foreach ($capabilities as $cap) {
    $component = $cap->component ?: 'core';
    if (!isset($components[$component])) {
        $components[$component] = [
            'component_name' => $component,
            'capabilities' => [],
        ];
    }
    $perm = $permsbyname[$cap->name] ?? 0;
    $components[$component]['capabilities'][] = [
        'cap_id' => $cap->id,
        'cap_name' => $cap->name,
        'cap_type' => $cap->captype ?? 'write',
        'field_name' => 'cap_' . $cap->id,
        'current_perm' => $perm,
        'is_inherit' => $perm == 0,
        'is_allow' => $perm == 1,
        'is_prevent' => $perm == -1,
        'is_prohibit' => $perm == -1000,
    ];
}

// Add capability count to each component
foreach ($components as &$comp) {
    $comp['capability_count'] = count($comp['capabilities']);
}

// Prepare context
$context = [
    'pagetitle' => get_string('definepermissions', 'core') . ': ' . $role->name,
    'showadmin' => true,
    'has_navigation' => true,
    'navigation_html' => get_navigation_html(),
    'role_id' => $role->id,
    'role_name' => htmlspecialchars($role->name),
    'role_shortname' => htmlspecialchars($role->shortname),
    'role_description' => htmlspecialchars($role->description ?? ''),
    'components' => array_values($components),
    'success' => $success,
    'errors' => $errors,
    'haserrors' => !empty($errors),
    'sesskey' => sesskey(),
];

echo render_template('admin/role_define', $context);
