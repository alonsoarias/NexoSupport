<?php
/**
 * Login page
 *
 * @package NexoSupport
 */

defined('NEXOSUPPORT_INTERNAL') || die();

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

    // Authenticate user using the auth plugin system
    // If no specific auth method is set for the user, manual auth will be used
    $user = authenticate_user_login($username, $password);

    if ($user) {
        // Login successful - create session
        $_SESSION['USER'] = $user;
        $USER = $user;

        // Redirect to home
        redirect('/');
    } else {
        $error = get_string('invalidlogin', 'core');
    }
}

// Prepare context for template
$context = [
    'error' => $error ? htmlspecialchars($error) : null,
];

// Render and output
echo render_template('core/login', $context);
