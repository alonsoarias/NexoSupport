<?php
/**
 * Login page
 *
 * @package NexoSupport
 */

require_once(__DIR__ . '/../config.php');

global $USER, $CFG;

// Si ya está logueado, redirigir al home
if (isset($USER->id) && $USER->id > 0) {
    redirect('/');
}

$error = null;

// Procesar login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = required_param('username', PARAM_ALPHANUMEXT);
    $password = required_param('password', PARAM_RAW);

    // Debug logging
    debugging("Login attempt for username: $username", DEBUG_DEVELOPER);

    // Authenticate user using the auth plugin system
    // If no specific auth method is set for the user, manual auth will be used
    $user = authenticate_user_login($username, $password);

    if ($user) {
        // Login successful - create session
        debugging("Login successful for user ID: " . $user->id, DEBUG_DEVELOPER);

        $_SESSION['USER'] = $user;
        $USER = $user;

        // Trigger user logged in event
        try {
            $event = \core\event\user_loggedin::create([
                'objectid' => $user->id,
                'userid' => $user->id,
                'relateduserid' => $user->id,
            ]);
            $event->trigger();
        } catch (Exception $e) {
            // Event system may not be fully initialized, continue anyway
            debugging("Event trigger failed: " . $e->getMessage(), DEBUG_DEVELOPER);
        }

        // Redirect to home
        redirect('/');
    } else {
        debugging("Login failed for username: $username", DEBUG_DEVELOPER);
        $error = get_string('invalidlogin', 'core');
    }
}

// Prepare context for template
$context = [
    'error' => $error ? htmlspecialchars($error) : null,
];

// Render and output
echo render_template('core/login', $context);
