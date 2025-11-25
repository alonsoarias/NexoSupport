<?php
/**
 * Settings Index - Main system settings
 *
 * @package NexoSupport
 */

require_once(__DIR__ . '/../../config.php');

require_login();
require_capability('nexosupport/admin:manageconfig');

global $USER, $CFG, $DB, $PAGE, $OUTPUT;

$success = null;
$errors = [];

// Process form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();

    $sitename = required_param('sitename', PARAM_TEXT);
    $sessiontimeout = optional_param('sessiontimeout', 7200, PARAM_INT);
    $debug = optional_param('debug', 0, PARAM_INT);

    set_config('sitename', $sitename);
    set_config('sessiontimeout', $sessiontimeout);
    set_config('debug', $debug);

    $success = get_string('changessaved', 'admin');
}

// Get current settings
$sitename = get_config('core', 'sitename') ?? ($CFG->sitename ?? 'NexoSupport');
$sessiontimeout = (int)(get_config('core', 'sessiontimeout') ?? 7200);
$debug = (int)(get_config('core', 'debug') ?? 0);

// System info
$systemversion = $CFG->version ?? '1.0.0';
$phpversion = PHP_VERSION;
$dbtype = $CFG->dbdriver ?? 'mysql';
$dbprefix = $CFG->prefix ?? 'nexo_';
$currentusername = $USER->username ?? 'admin';

// Prepare context
$context = [
    'pagetitle' => get_string('settings', 'admin'),
    'showadmin' => true,
    'has_navigation' => true,
    'navigation_html' => get_navigation_html(),
    'sitename' => htmlspecialchars($sitename),
    'sessiontimeout' => $sessiontimeout,
    'debug' => $debug == 1,
    'systemversion' => $systemversion,
    'phpversion' => $phpversion,
    'dbtype' => $dbtype,
    'dbprefix' => $dbprefix,
    'currentusername' => $currentusername,
    'success' => $success,
    'errors' => $errors,
    'haserrors' => !empty($errors),
    'sesskey' => sesskey(),
];

echo render_template('admin/settings', $context);
