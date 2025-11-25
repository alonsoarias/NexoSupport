<?php
/**
 * Role Management - List Roles
 *
 * @package NexoSupport
 */

require_once(__DIR__ . '/../../config.php');

require_login();
require_capability('nexosupport/admin:manageroles');

global $USER, $CFG, $DB, $PAGE, $OUTPUT;

// Get all roles
$roles = $DB->get_records('roles', [], 'sortorder ASC');

// Get role capabilities
function get_role_capabilities($roleid) {
    global $DB;
    $caps = $DB->get_records('role_capabilities', ['roleid' => $roleid]);
    $result = [];
    foreach ($caps as $cap) {
        $result[] = [
            'name' => $cap->capability,
            'permissionclass' => $cap->permission == 1 ? 'allow' : 'prevent',
            'permissiontext' => $cap->permission == 1 ? 'Allow' : 'Prevent',
        ];
    }
    return $result;
}

// System roles that can't be deleted
$systemroles = ['admin', 'manager', 'user', 'guest'];
$totalroles = count($roles);
$roleindex = 0;

// Format roles for template
$rolesformatted = [];
foreach ($roles as $role) {
    // Count users with this role
    $usercount = $DB->count_records('role_assignments', ['roleid' => $role->id]);
    $capabilities = get_role_capabilities($role->id);
    $issystemrole = in_array($role->shortname, $systemroles);

    $rolesformatted[] = [
        'id' => $role->id,
        'name' => htmlspecialchars($role->name),
        'shortname' => htmlspecialchars($role->shortname),
        'description' => htmlspecialchars($role->description ?? ''),
        'hasdescription' => !empty($role->description),
        'archetype' => htmlspecialchars($role->archetype ?? ''),
        'usercount' => $usercount,
        'issystemrole' => $issystemrole,
        'capabilities' => $capabilities,
        'hascapabilities' => !empty($capabilities),
        'canmoveup' => $roleindex > 0,
        'canmovedown' => $roleindex < ($totalroles - 1),
        'candelete' => !$issystemrole && $usercount == 0,
    ];
    $roleindex++;
}

// Prepare context
$context = [
    'pagetitle' => get_string('roles', 'core'),
    'showadmin' => true,
    'has_navigation' => true,
    'navigation_html' => get_navigation_html(),
    'roles' => $rolesformatted,
    'hasroles' => !empty($rolesformatted),
    'totalroles' => count($rolesformatted),
    'sesskey' => sesskey(),
    'canmanageroles' => has_capability('nexosupport/admin:manageroles'),
];

echo render_template('admin/role_list', $context);
