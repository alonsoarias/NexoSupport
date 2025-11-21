<?php
/**
 * Forgot password routine.
 *
 * Finds the user and calls the appropriate routine for their authentication type.
 *
 * @package    core
 * @subpackage auth
 * @copyright  NexoSupport
 * @license    Proprietary - NexoSupport
 */

require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/lib.php');
require_once('forgot_password_form.php');
require_once('set_password_form.php');

$token = optional_param('token', false, PARAM_ALPHANUM);

$PAGE->set_url('/login/forgot_password.php');
$systemcontext = \core\context\system::instance();
$PAGE->set_context($systemcontext);

// Setup text strings
$strforgotten = get_string('passwordforgotten', 'core');

$PAGE->set_pagelayout('login');
$PAGE->set_title($strforgotten);
$PAGE->set_heading(get_config('core', 'sitename'));

// If you are logged in then you shouldn't be here!
if (isloggedin() && !isguestuser()) {
    redirect($CFG->wwwroot . '/index.php', get_string('loginalready', 'core'), 5);
}

// Fetch the token from the session, if present, and unset the session var immediately
$tokeninsession = false;
if (!empty($SESSION->password_reset_token)) {
    $token = $SESSION->password_reset_token;
    unset($SESSION->password_reset_token);
    $tokeninsession = true;
}

if (empty($token)) {
    // This is a new password reset request
    core_login_process_password_reset_request();
} else {
    // A token has been found
    if (!$tokeninsession && $_SERVER['REQUEST_METHOD'] === 'GET') {
        // Store the reset token in the session and redirect to self
        $SESSION->password_reset_token = $token;
        redirect($CFG->wwwroot . '/login/forgot_password.php');
    } else {
        // Continue with the password reset process
        core_login_process_password_set($token);
    }
}
