<?php
/**
 * Security Report
 *
 * @package report_security
 */

require_once(__DIR__ . '/../../config.php');

require_login();
require_capability('nexosupport/admin:viewreports');

global $USER, $CFG, $DB;

// Security checks
$checks = [];

// Check 1: Debug mode
$debug = get_config('core', 'debug') ?? 0;
$checks[] = [
    'name' => get_string('debugging', 'admin'),
    'status' => $debug == 0 ? 'ok' : 'warning',
    'status_ok' => $debug == 0,
    'status_warning' => $debug > 0 && $debug < 32767,
    'status_critical' => $debug == 32767,
    'message' => $debug == 0 ? get_string('debuggingoff', 'admin') : get_string('debuggingon', 'admin'),
];

// Check 2: HTTPS
$ishttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
$checks[] = [
    'name' => 'HTTPS',
    'status' => $ishttps ? 'ok' : 'critical',
    'status_ok' => $ishttps,
    'status_critical' => !$ishttps,
    'message' => $ishttps ? get_string('httpsenabled', 'admin') : get_string('httpsdisabled', 'admin'),
];

// Check 3: Session security
$sessiontimeout = get_config('core', 'sessiontimeout') ?? 7200;
$checks[] = [
    'name' => get_string('sessiontimeout', 'admin'),
    'status' => $sessiontimeout <= 7200 ? 'ok' : 'warning',
    'status_ok' => $sessiontimeout <= 7200,
    'status_warning' => $sessiontimeout > 7200,
    'message' => sprintf('%d %s', $sessiontimeout / 60, get_string('minutes', 'core')),
];

// Check 4: Admin accounts
$admincount = $DB->count_records_sql(
    "SELECT COUNT(DISTINCT ra.userid) FROM {role_assignments} ra
     JOIN {roles} r ON r.id = ra.roleid WHERE r.shortname = 'admin'"
);
$checks[] = [
    'name' => get_string('adminaccounts', 'admin'),
    'status' => $admincount <= 3 ? 'ok' : 'warning',
    'status_ok' => $admincount <= 3,
    'status_warning' => $admincount > 3,
    'message' => $admincount . ' ' . get_string('accounts', 'core'),
];

// Count statuses
$okcount = count(array_filter($checks, fn($c) => $c['status'] === 'ok'));
$warningcount = count(array_filter($checks, fn($c) => $c['status'] === 'warning'));
$criticalcount = count(array_filter($checks, fn($c) => $c['status'] === 'critical'));

$context = [
    'pagetitle' => get_string('securityreport', 'core'),
    'showadmin' => true,
    'has_navigation' => true,
    'navigation_html' => get_navigation_html(),
    'checks' => $checks,
    'haschecks' => !empty($checks),
    'okcount' => $okcount,
    'warningcount' => $warningcount,
    'criticalcount' => $criticalcount,
    'haswarnings' => $warningcount > 0,
    'hascritical' => $criticalcount > 0,
    'sesskey' => sesskey(),
];

echo render_template('report/security', $context);
