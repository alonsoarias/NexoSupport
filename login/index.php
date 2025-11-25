<?php
/**
 * Login page
 *
 * This is an entry point script - it should be accessed directly.
 *
 * @package core
 */

// Load configuration first (this defines NEXOSUPPORT_INTERNAL)
require_once(__DIR__ . '/../config.php');

global $USER, $CFG, $DB, $PAGE, $OUTPUT;

// Include auth library
require_once(BASE_DIR . '/lib/authlib.php');

// Check if user is already logged in
if (isloggedin() && !isguestuser()) {
    redirect($CFG->wwwroot . '/admin');
}

// Initialize variables
$error = '';
$username = '';

// Handle POST request (login attempt)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = get_string('invalidlogin', 'core');
    } else {
        // Try to authenticate
        $user = authenticate_user_login($username, $password);

        if ($user) {
            // Check if user is suspended
            if (!empty($user->suspended)) {
                $error = get_string('accountsuspended', 'core');
            } else if (!empty($user->deleted)) {
                $error = get_string('invalidlogin', 'core');
            } else {
                // Complete login
                complete_user_login($user);

                // Redirect to admin or requested page
                $returnurl = $_GET['returnurl'] ?? $_POST['returnurl'] ?? '/admin';
                redirect($CFG->wwwroot . $returnurl);
            }
        } else {
            $error = get_string('invalidlogin', 'core');

            // Try to get user for lockout tracking
            $existinguser = $DB->get_record('users', ['username' => $username, 'deleted' => 0]);
            if ($existinguser) {
                login_attempt_failed($existinguser);

                // Check if now locked out
                if (login_is_lockedout($existinguser)) {
                    $error = get_string('toomanyloginattempts', 'core');
                }
            }
        }
    }
}

// Prepare context for template
$context = [
    'error' => $error,
    'username' => htmlspecialchars($username),
    'currentlang' => $CFG->lang ?? 'es',
    'returnurl' => htmlspecialchars($_GET['returnurl'] ?? '')
];

// Load mustache engine and render template
require_once(BASE_DIR . '/lib/classes/output/mustache_engine.php');
$mustache = new \core\output\mustache_engine();

echo $mustache->render('core/login', $context);
