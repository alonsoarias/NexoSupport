<?php
/**
 * Role Management Interface
 *
 * @package NexoSupport
 */

require_once(__DIR__ . '/../../config.php');

require_login();
require_capability('nexosupport/admin:manageroles');

global $USER, $DB;

use core\rbac\role;
use core\rbac\context;

$returnurl = '/admin/roles';

// Get action parameters
$moveup = optional_param('moveup', 0, PARAM_INT);
$movedown = optional_param('movedown', 0, PARAM_INT);
$delete = optional_param('delete', 0, PARAM_INT);
$confirm = optional_param('confirm', '', PARAM_ALPHANUM);

// Process moveup action
if ($moveup && confirm_sesskey()) {
    $role = role::get_by_id($moveup);

    if ($role) {
        if ($role->move_up()) {
            redirect($returnurl, get_string('roleupdated', 'core'), 'success');
        } else {
            redirect($returnurl, 'Could not move role up', 'error');
        }
    } else {
        redirect($returnurl, get_string('rolenotfound', 'core'), 'error');
    }
}

// Process movedown action
if ($movedown && confirm_sesskey()) {
    $role = role::get_by_id($movedown);

    if ($role) {
        if ($role->move_down()) {
            redirect($returnurl, get_string('roleupdated', 'core'), 'success');
        } else {
            redirect($returnurl, 'Could not move role down', 'error');
        }
    } else {
        redirect($returnurl, get_string('rolenotfound', 'core'), 'error');
    }
}

// Process delete action
if ($delete && confirm_sesskey()) {
    $role = role::get_by_id($delete);

    if (!$role) {
        redirect($returnurl, get_string('rolenotfound', 'core'), 'error');
    }

    // Check if it's a system role
    if ($role->is_system_role()) {
        redirect($returnurl, get_string('cannotdeletesystemrole', 'core'), 'error');
    }

    if ($confirm != md5($delete)) {
        // Show confirmation page
        $syscontext = context::system();
        $users = $role->get_users($syscontext);

        $context = [
            'user' => $USER,
            'showadmin' => true,
            'fullname' => htmlspecialchars($USER->firstname . ' ' . $USER->lastname),
            'targetrole' => [
                'id' => $role->id,
                'name' => htmlspecialchars($role->name),
                'shortname' => htmlspecialchars($role->shortname),
                'description' => htmlspecialchars($role->description),
                'usercount' => count($users),
            ],
            'confirmhash' => md5($delete),
            'returnurl' => $returnurl,
            'sesskey' => sesskey(),
            'has_navigation' => true,
            'navigation_html' => get_navigation_html(),
        ];

        echo render_template('admin/role_delete_confirm', $context);
        exit;
    } else {
        // Delete confirmed
        if ($role->delete()) {
            redirect($returnurl, get_string('roledeleted', 'core'), 'success');
        } else {
            redirect($returnurl, get_string('errordeletingrole', 'core'), 'error');
        }
    }
}

// Get all roles
$roles = role::get_all();
$syscontext = context::system();

// Format roles for template
$rolesformatted = [];
$totalroles = count($roles);

foreach ($roles as $index => $role) {
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

    $issystemrole = $role->is_system_role();

    $rolesformatted[] = [
        'id' => $role->id,
        'name' => htmlspecialchars($role->name),
        'shortname' => htmlspecialchars($role->shortname),
        'description' => htmlspecialchars($role->description),
        'hasdescription' => !empty($role->description),
        'capabilities' => $capsformatted,
        'hascapabilities' => !empty($capsformatted),
        'usercount' => count($users),
        'issystemrole' => $issystemrole,
        'candelete' => !$issystemrole,
        'canmoveup' => $index > 0,
        'canmovedown' => $index < ($totalroles - 1),
        'sesskey' => sesskey(),
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
    'has_navigation' => true,
    'navigation_html' => get_navigation_html(),
];

// Render and output
echo render_template('admin/role_list', $context);
