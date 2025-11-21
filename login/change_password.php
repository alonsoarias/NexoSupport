<?php
/**
 * Change password page.
 *
 * @package    core
 * @subpackage auth
 */

require_once(__DIR__ . '/../config.php');

require_login();

global $USER, $DB, $CFG;

$success = null;
$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && confirm_sesskey()) {
    $currentpassword = required_param('currentpassword', PARAM_RAW);
    $newpassword = required_param('newpassword', PARAM_RAW);
    $confirmpassword = required_param('confirmpassword', PARAM_RAW);

    // Verify current password
    if (!password_verify($currentpassword, $USER->password)) {
        $errors[] = get_string('wrongpassword', 'core');
    }

    // Check new passwords match
    if ($newpassword !== $confirmpassword) {
        $errors[] = get_string('passwordmismatch', 'core');
    }

    // Check password policy
    if (strlen($newpassword) < 6) {
        $errors[] = get_string('passwordtooshort', 'core');
    }

    if (empty($errors)) {
        // Update password
        $DB->update_record('users', [
            'id' => $USER->id,
            'password' => password_hash($newpassword, PASSWORD_DEFAULT),
            'timemodified' => time(),
        ]);

        $success = get_string('passwordchanged', 'core');

        // Redirect after success
        redirect('/user/profile', $success);
    }
}

// Prepare context for template
$context = [
    'pagetitle' => get_string('changepassword', 'core'),
    'has_navigation' => true,
    'navigation_html' => get_navigation_html(),
    'sesskey' => sesskey(),
    'success' => $success,
    'errors' => $errors,
    'haserrors' => !empty($errors),
];

// Render template
echo render_template('login/change_password', $context);
