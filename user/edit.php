<?php
/**
 * Edit User Profile
 *
 * Allows users to edit their own profile information.
 *
 * @package core
 * @subpackage user
 */

require_once('../lib/setup.php');

// Require login
require_login();

// Get current user
global $USER, $DB, $OUTPUT;

// Page setup
$PAGE->set_url('/user/edit');
$PAGE->set_title(get_string('editprofile', 'core'));
$PAGE->set_heading(get_string('editprofile', 'core'));
$PAGE->set_context(CONTEXT_SYSTEM);

$success = null;
$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validate_sesskey()) {
    $firstname = required_param('firstname', PARAM_TEXT);
    $lastname = required_param('lastname', PARAM_TEXT);
    $email = required_param('email', PARAM_EMAIL);

    // Validate email uniqueness (except for current user)
    $existing = $DB->get_record('users', ['email' => $email]);
    if ($existing && $existing->id != $USER->id) {
        $errors[] = get_string('emailexists', 'core');
    } else {
        // Update user
        $DB->update_record('users', [
            'id' => $USER->id,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'email' => $email,
            'timemodified' => time(),
        ]);

        // Reload user
        $USER = $DB->get_record('users', ['id' => $USER->id]);

        $success = get_string('userupdated', 'core');

        // Redirect to profile
        redirect('/user/profile');
    }
}

// Get user data
$userdata = $DB->get_record('users', ['id' => $USER->id], '*', MUST_EXIST);

// Prepare context for template
$context = [
    'user' => [
        'firstname' => s($userdata->firstname),
        'lastname' => s($userdata->lastname),
        'username' => s($userdata->username),
        'email' => s($userdata->email),
    ],
    'sesskey' => sesskey(),
    'success' => $success,
    'errors' => $errors,
    'haserrors' => !empty($errors),
];

// Render and output
echo render_template('user/edit_profile', $context);
