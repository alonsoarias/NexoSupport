<?php
/**
 * User Management - List Users
 *
 * @package NexoSupport
 */

require_once(__DIR__ . '/../../config.php');

require_login();
require_capability('nexosupport/admin:manageusers');

global $USER, $CFG, $DB, $PAGE, $OUTPUT;

// Get pagination parameters
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 20, PARAM_INT);
$search = optional_param('search', '', PARAM_TEXT);

// Get users
$users = $DB->get_records('users', ['deleted' => 0], 'lastname ASC, firstname ASC');

// Format users for template
$usersformatted = [];
foreach ($users as $user) {
    if (!empty($search) && stripos($user->username . $user->firstname . $user->lastname . $user->email, $search) === false) {
        continue;
    }
    $usersformatted[] = [
        'id' => $user->id,
        'username' => htmlspecialchars($user->username),
        'fullname' => htmlspecialchars($user->firstname . ' ' . $user->lastname),
        'email' => htmlspecialchars($user->email),
        'suspended' => !empty($user->suspended),
        'lastlogin' => $user->lastlogin ? date('d/m/Y H:i', $user->lastlogin) : get_string('never', 'core'),
    ];
}

// Prepare context
$context = [
    'pagetitle' => get_string('users', 'core'),
    'showadmin' => true,
    'has_navigation' => true,
    'navigation_html' => get_navigation_html(),
    'users' => $usersformatted,
    'hasusers' => !empty($usersformatted),
    'totalusers' => count($usersformatted),
    'search' => htmlspecialchars($search ?? ''),
    'sesskey' => sesskey(),
    'cancreate' => has_capability('nexosupport/user:create'),
];

echo render_template('admin/user_list', $context);
