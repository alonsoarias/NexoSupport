<?php
/**
 * Dashboard / Home Page
 *
 * @package NexoSupport
 */

defined('NEXOSUPPORT_INTERNAL') || die();

require_login();

global $USER, $DB;

// Get stats
$totalusers = $DB->count_records('users', ['deleted' => 0]);
$activeusers = $DB->count_records('users', ['deleted' => 0, 'suspended' => 0]);
$totalroles = $DB->count_records('roles');
$activesessions = \core\session\manager::count_active_sessions();

// Get recent logins
$recentlogins = $DB->get_records_sql('SELECT * FROM {users} WHERE deleted = 0 AND lastlogin > 0 ORDER BY lastlogin DESC LIMIT 5');

// Format recent logins for template
$recentloginsformatted = [];
foreach ($recentlogins as $login) {
    $recentloginsformatted[] = [
        'fullname' => htmlspecialchars($login->firstname . ' ' . $login->lastname),
        'username' => htmlspecialchars($login->username),
        'lastloginformatted' => date('d/m/Y H:i', $login->lastlogin),
    ];
}

// Prepare context for template
$context = [
    'user' => $USER,
    'showadmin' => has_capability('nexosupport/admin:viewdashboard'),
    'fullname' => $USER->firstname . ' ' . $USER->lastname,
    'totalusers' => $totalusers,
    'activeusers' => $activeusers,
    'totalroles' => $totalroles,
    'activesessions' => $activesessions,
    'showquickactions' => has_capability('nexosupport/admin:viewdashboard'),
    'canmanageusers' => has_capability('nexosupport/admin:manageusers'),
    'canmanageroles' => has_capability('nexosupport/admin:manageroles'),
    'canmanageconfig' => has_capability('nexosupport/admin:manageconfig'),
    'recentlogins' => $recentloginsformatted,
    'hasrecentlogins' => !empty($recentloginsformatted),
];

// Render and output
echo render_template('core/dashboard', $context);
