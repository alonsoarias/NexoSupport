<?php
/**
 * Notification Preferences
 *
 * Allows users to configure their notification preferences.
 *
 * @package core
 * @subpackage user
 */

require_once('../../lib/setup.php');

// Require login
require_login();

// Get current user
global $USER, $DB, $OUTPUT;

// Page setup
$PAGE->set_url('/user/preferences/notification');
$PAGE->set_title(get_string('notificationpreferences', 'core'));
$PAGE->set_heading(get_string('notificationpreferences', 'core'));
$PAGE->set_context(CONTEXT_SYSTEM);

$success = null;
$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validate_sesskey()) {
    // Get preferences
    $email_notifications = optional_param('email_notifications', 0, PARAM_INT);
    $digest_type = optional_param('digest_type', 'none', PARAM_ALPHA);

    // Validate digest type
    $valid_digests = ['none', 'daily', 'weekly'];
    if (!in_array($digest_type, $valid_digests)) {
        $errors[] = get_string('invaliddebug level', 'core');
    } else {
        // Save preferences
        set_user_preference('email_notifications', $email_notifications);
        set_user_preference('digest_type', $digest_type);

        $success = get_string('preferencessaved', 'core');
    }
}

// Get current preferences
$email_notifications = get_user_preference('email_notifications', 1);
$digest_type = get_user_preference('digest_type', 'none');

// Prepare context for template
$context = [
    'sesskey' => sesskey(),
    'success' => $success,
    'errors' => $errors,
    'haserrors' => !empty($errors),
    'email_notifications_checked' => ($email_notifications == 1),
    'digest_none_selected' => ($digest_type === 'none'),
    'digest_daily_selected' => ($digest_type === 'daily'),
    'digest_weekly_selected' => ($digest_type === 'weekly'),
];

// Render and output
echo render_template('user/notification_preferences', $context);
