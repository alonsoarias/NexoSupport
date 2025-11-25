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

// Format roles for template
$rolesformatted = [];
foreach ($roles as $role) {
    // Count users with this role
    $usercount = $DB->count_records('role_assignments', ['roleid' => $role->id]);

    $rolesformatted[] = [
        'id' => $role->id,
        'name' => htmlspecialchars($role->name),
        'shortname' => htmlspecialchars($role->shortname),
        'description' => htmlspecialchars($role->description ?? ''),
        'archetype' => htmlspecialchars($role->archetype ?? ''),
        'usercount' => $usercount,
    ];
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
    'cancreate' => has_capability('nexosupport/admin:manageroles'),
];

echo render_template('admin/roles', $context);
