<?php
/**
 * Role Management Interface
 *
 * @package NexoSupport
 */

defined('NEXOSUPPORT_INTERNAL') || die();

require_login();
require_capability('nexosupport/admin:manageroles');

global $USER;

use core\rbac\role;
use core\rbac\context;

// Get all roles
$roles = role::get_all();
$syscontext = context::system();

// Format roles for template
$rolesformatted = [];
foreach ($roles as $role) {
    $capabilities = $role->get_capabilities($syscontext);
    $users = $role->get_users($syscontext);

    $capsformatted = [];
    foreach ($capabilities as $capname => $permission) {
        $capsformatted[] = [
            'name' => htmlspecialchars($capname),
            'permissionclass' => $permission == 1 ? 'allow' : 'prevent',
            'permissiontext' => $permission == 1 ? get_string('allow', 'core') : get_string('prevent', 'core'),
        ];
    }

    $rolesformatted[] = [
        'id' => $role->id,
        'name' => htmlspecialchars($role->name),
        'shortname' => htmlspecialchars($role->shortname),
        'description' => htmlspecialchars($role->description),
        'hasdescription' => !empty($role->description),
        'capabilities' => $capsformatted,
        'hascapabilities' => !empty($capsformatted),
        'usercount' => count($users),
    ];
}

// Prepare context for template
$context = [
    'user' => $USER,
    'showadmin' => true,
    'fullname' => htmlspecialchars($USER->firstname . ' ' . $USER->lastname),
    'roles' => $rolesformatted,
    'hasroles' => !empty($rolesformatted),
    'canmanageroles' => has_capability('nexosupport/admin:manageroles'),
];

// Render and output
echo render_template('admin/role_list', $context);
