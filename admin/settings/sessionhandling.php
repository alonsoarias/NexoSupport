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
    $sessioncookiepath = optional_param('sessioncookiepath', '/', PARAM_PATH);
    $sessioncookiedomain = optional_param('sessioncookiedomain', '', PARAM_HOST);

    set_config('sessiontimeout', $sessiontimeout);
    set_config('sessioncookiepath', $sessioncookiepath);
    set_config('sessioncookiedomain', $sessioncookiedomain);

    $success = get_string('changessaved', 'admin');
}

// Get current settings
$sessiontimeout = (int)(get_config('core', 'sessiontimeout') ?? 7200);
$sessioncookiepath = get_config('core', 'sessioncookiepath') ?? '/';
$sessioncookiedomain = get_config('core', 'sessioncookiedomain') ?? '';

// Prepare context
$context = [
    'pagetitle' => get_string('sessionhandling', 'admin'),
    'showadmin' => true,
    'has_navigation' => true,
    'navigation_html' => get_navigation_html(),
    'sessiontimeout' => $sessiontimeout,
    'sessiontimeout_minutes' => round($sessiontimeout / 60),
    'sessioncookiepath' => $sessioncookiepath,
    'sessioncookiedomain' => $sessioncookiedomain,
    'php_session_gc_maxlifetime' => ini_get('session.gc_maxlifetime'),
    'php_session_cookie_lifetime' => ini_get('session.cookie_lifetime'),
    'success' => $success,
    'errors' => $errors,
    'haserrors' => !empty($errors),
    'sesskey' => sesskey(),
];

echo render_template('admin/settings_sessionhandling', $context);
