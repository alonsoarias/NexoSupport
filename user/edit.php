<?php
/**
 * Edit user profile page
 *
 * @package core
 */

// Load configuration first (this defines NEXOSUPPORT_INTERNAL)
require_once(__DIR__ . '/../config.php');

global $USER, $DB, $CFG, $PAGE, $OUTPUT;

require_login();

$success = null;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && confirm_sesskey()) {
    $firstname = required_param('firstname', PARAM_TEXT);
    $lastname = required_param('lastname', PARAM_TEXT);
    $email = required_param('email', PARAM_EMAIL);

    $existing = $DB->get_record('users', ['email' => $email]);
    if ($existing && $existing->id != $USER->id) {
        $errors[] = get_string('emailexists', 'core');
    } else {
        $DB->update_record('users', [
            'id' => $USER->id,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'email' => $email,
            'timemodified' => time(),
        ]);
        $USER = $DB->get_record('users', ['id' => $USER->id]);
        $success = get_string('userupdated', 'core');
        redirect('/user/profile');
    }
}

$userdata = $DB->get_record('users', ['id' => $USER->id], '*', MUST_EXIST);

$context = [
    'pagetitle' => get_string('editprofile', 'core'),
    'has_navigation' => true,
    'navigation_html' => get_navigation_html(),
    'user' => [
        'firstname' => htmlspecialchars($userdata->firstname ?? ''),
        'lastname' => htmlspecialchars($userdata->lastname ?? ''),
        'username' => htmlspecialchars($userdata->username ?? ''),
        'email' => htmlspecialchars($userdata->email ?? ''),
    ],
    'sesskey' => sesskey(),
    'success' => $success,
    'errors' => $errors,
    'haserrors' => !empty($errors),
];

echo render_template('user/edit_profile', $context);
