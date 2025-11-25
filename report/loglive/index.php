<?php
/**
 * Live Log Report
 *
 * @package report_loglive
 */

require_once(__DIR__ . '/../../config.php');

require_login();
require_capability('nexosupport/admin:viewreports');

global $USER, $CFG, $DB;

// Get recent logs (last 5 minutes)
$since = time() - 300;
$logs = $DB->get_records_select(
    'logstore_standard_log',
    'timecreated > ?',
    [$since],
    'timecreated DESC',
    '*',
    0,
    100
);

// Format logs
$logsformatted = [];
foreach ($logs as $log) {
    $user = $log->userid ? $DB->get_record('users', ['id' => $log->userid]) : null;
    $logsformatted[] = [
        'id' => $log->id,
        'time' => date('H:i:s', $log->timecreated),
        'username' => $user ? htmlspecialchars($user->username) : '-',
        'action' => htmlspecialchars($log->action ?? ''),
        'target' => htmlspecialchars($log->target ?? ''),
        'ip' => htmlspecialchars($log->ip ?? ''),
    ];
}

$context = [
    'pagetitle' => get_string('livelogs', 'core'),
    'showadmin' => true,
    'has_navigation' => true,
    'navigation_html' => get_navigation_html(),
    'logs' => $logsformatted,
    'haslogs' => !empty($logsformatted),
    'totalcount' => count($logsformatted),
    'refreshurl' => '/report/loglive/',
    'sesskey' => sesskey(),
];

echo render_template('report/loglive', $context);
