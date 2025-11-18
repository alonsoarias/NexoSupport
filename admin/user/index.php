<?php
/**
 * User Management Interface
 *
 * @package NexoSupport
 */

defined('NEXOSUPPORT_INTERNAL') || die();

require_login();
require_capability('nexosupport/admin:manageusers');

global $USER;

// Obtener usuarios
$search = optional_param('search', '', 'text');
$page = optional_param('page', 0, 'int');
$perpage = 25;

$users = $search
    ? \core\user\manager::search_users($search, $page * $perpage, $perpage)
    : \core\user\manager::get_all_users(false, $page * $perpage, $perpage);

$totalusers = \core\user\manager::count_users();

// Format users for template
$usersformatted = [];
foreach ($users as $userobj) {
    $usersformatted[] = [
        'id' => $userobj->id,
        'username' => htmlspecialchars($userobj->username),
        'fullname' => htmlspecialchars($userobj->firstname . ' ' . $userobj->lastname),
        'email' => htmlspecialchars($userobj->email),
        'issuspended' => (bool)$userobj->suspended,
        'lastloginformatted' => $userobj->lastlogin ? date('d/m/Y H:i', $userobj->lastlogin) : get_string('never', 'core'),
    ];
}

// Prepare context for template
$context = [
    'user' => $USER,
    'showadmin' => true,
    'totalusers' => $totalusers,
    'search' => htmlspecialchars($search),
    'users' => $usersformatted,
    'hasusers' => !empty($usersformatted),
];

// Render and output
echo render_template('admin/user_list', $context);
