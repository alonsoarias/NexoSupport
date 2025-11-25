<?php
/**
 * HTTP Settings
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

    $wwwroot = required_param('wwwroot', PARAM_URL);
    $sslproxy = optional_param('sslproxy', 0, PARAM_INT);

    set_config('wwwroot', $wwwroot);
    set_config('sslproxy', $sslproxy);

    $success = get_string('changessaved', 'admin');
}

// Get current settings
$wwwroot = get_config('core', 'wwwroot') ?? $CFG->wwwroot;
$sslproxy = get_config('core', 'sslproxy') ?? 0;

// Prepare context
$context = [
    'pagetitle' => get_string('http', 'admin'),
    'showadmin' => true,
    'has_navigation' => true,
    'navigation_html' => get_navigation_html(),
    'wwwroot' => htmlspecialchars($wwwroot),
    'sslproxy' => $sslproxy,
    'success' => $success,
    'errors' => $errors,
    'haserrors' => !empty($errors),
    'sesskey' => sesskey(),
];

echo render_template('admin/settings_http', $context);
