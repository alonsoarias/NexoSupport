<?php
/**
 * Logout page
 *
 * @package core
 */

// Load configuration first (this defines NEXOSUPPORT_INTERNAL)
require_once(__DIR__ . '/../config.php');

global $USER, $CFG, $DB, $PAGE, $OUTPUT;

// Perform logout
require_once(BASE_DIR . '/lib/authlib.php');

if (isloggedin()) {
    // Clear session
    $_SESSION = [];

    // Destroy session cookie if set
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }

    // Destroy session
    session_destroy();
}

// Redirect to login page
redirect($CFG->wwwroot . '/login/');
