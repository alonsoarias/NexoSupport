<?php
/**
 * Session Handling Settings
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

    $sessiontimeout = optional_param('sessiontimeout', 7200, PARAM_INT);
    $sessioncookiesecure = optional_param('sessioncookiesecure', 0, PARAM_INT);

    set_config('sessiontimeout', $sessiontimeout);
    set_config('sessioncookiesecure', $sessioncookiesecure);

    $success = get_string('changessaved', 'admin');
}

// Get current settings
$sessiontimeout = get_config('core', 'sessiontimeout') ?? 7200;
$sessioncookiesecure = get_config('core', 'sessioncookiesecure') ?? 0;

// Prepare context
$context = [
    'pagetitle' => get_string('sessionhandling', 'admin'),
    'showadmin' => true,
    'has_navigation' => true,
    'navigation_html' => get_navigation_html(),
    'sessiontimeout' => $sessiontimeout,
    'sessioncookiesecure' => $sessioncookiesecure,
    'success' => $success,
    'errors' => $errors,
    'haserrors' => !empty($errors),
    'sesskey' => sesskey(),
];

echo render_template('admin/settings_session', $context);
