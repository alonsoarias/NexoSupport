<?php
/**
 * Session Handling Settings - NexoSupport
 *
 * Configuration page for session management settings.
 *
 * @package core
 * @subpackage admin
 */

require_once(__DIR__ . '/../../config.php');

require_login();
require_capability('nexosupport/admin:manageconfig');

global $USER, $CFG;

$success = null;
$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && confirm_sesskey()) {
    $sessiontimeout = required_param('sessiontimeout', PARAM_INT);
    $sessioncookiepath = optional_param('sessioncookiepath', '/', PARAM_PATH);
    $sessioncookiedomain = optional_param('sessioncookiedomain', '', PARAM_HOST);

    // Validate session timeout (min 5 minutes, max 1 week)
    if ($sessiontimeout < 300 || $sessiontimeout > 604800) {
        $errors[] = get_string('invalidsessiontimeout', 'admin');
    }

    if (empty($errors)) {
        set_config('sessiontimeout', $sessiontimeout, 'core');
        set_config('sessioncookiepath', $sessioncookiepath, 'core');
        if (!empty($sessioncookiedomain)) {
            set_config('sessioncookiedomain', $sessioncookiedomain, 'core');
        }

        $success = get_string('configsaved', 'core');
        redirect('/admin/settings/sessionhandling', $success);
    }
}

// Get current settings
$current_timeout = get_config('core', 'sessiontimeout') ?? 7200; // 2 hours default
$current_cookiepath = get_config('core', 'sessioncookiepath') ?? '/';
$current_cookiedomain = get_config('core', 'sessioncookiedomain') ?? '';

// Prepare context for template
$context = [
    'sesskey' => sesskey(),
    'success' => $success,
    'errors' => $errors,
    'haserrors' => !empty($errors),
    'sessiontimeout' => (int)$current_timeout,
    'sessiontimeout_minutes' => round($current_timeout / 60),
    'sessioncookiepath' => htmlspecialchars($current_cookiepath),
    'sessioncookiedomain' => htmlspecialchars($current_cookiedomain),
    'php_session_gc_maxlifetime' => ini_get('session.gc_maxlifetime'),
    'php_session_cookie_lifetime' => ini_get('session.cookie_lifetime'),
    'pagetitle' => get_string('sessionhandling', 'admin'),
    'has_navigation' => true,
    'navigation_html' => get_navigation_html(),
];

echo render_template('admin/settings_sessionhandling', $context);
