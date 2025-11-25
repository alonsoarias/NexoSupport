<?php
/**
 * Log Report
 *
 * @package report_log
 */

require_once(__DIR__ . '/../../config.php');

require_login();
require_capability('nexosupport/admin:viewreports');

global $USER, $CFG, $DB;

// Pagination
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 50, PARAM_INT);
$userid = optional_param('userid', 0, PARAM_INT);

// Get total count
$totalcount = $DB->count_records('logstore_standard_log');

// Get logs
$logs = $DB->get_records('logstore_standard_log', [], 'timecreated DESC', '*', $page * $perpage, $perpage);

// Format logs
$logsformatted = [];
foreach ($logs as $log) {
    $user = $log->userid ? $DB->get_record('users', ['id' => $log->userid]) : null;
    $logsformatted[] = [
        'id' => $log->id,
        'time' => date('d/m/Y H:i:s', $log->timecreated),
        'username' => $user ? htmlspecialchars($user->username) : '-',
        'fullname' => $user ? htmlspecialchars($user->firstname . ' ' . $user->lastname) : '-',
        'action' => htmlspecialchars($log->action ?? ''),
        'target' => htmlspecialchars($log->target ?? ''),
        'ip' => htmlspecialchars($log->ip ?? ''),
        'component' => htmlspecialchars($log->component ?? 'core'),
    ];
}

$totalpages = ceil($totalcount / $perpage);

$context = [
    'pagetitle' => get_string('logs', 'core'),
    'showadmin' => true,
    'has_navigation' => true,
    'navigation_html' => get_navigation_html(),
    'logs' => $logsformatted,
    'haslogs' => !empty($logsformatted),
    'totalcount' => $totalcount,
    'page' => $page,
    'totalpages' => $totalpages,
    'hasprevious' => $page > 0,
    'hasnext' => $page < ($totalpages - 1),
    'previouspage' => $page - 1,
    'nextpage' => $page + 1,
    'sesskey' => sesskey(),
];

echo render_template('report/log', $context);
