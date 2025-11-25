<?php
/**
 * Security Settings
 *
 * @package NexoSupport
 */

require_once(__DIR__ . '/../../config.php');

require_login();
require_capability('nexosupport/admin:manageconfig');

global $USER, $CFG, $DB, $PAGE, $OUTPUT;

$success = null;
$errors = [];

// Process form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();

    $passwordpolicy = optional_param('passwordpolicy', 0, PARAM_INT);
    $minpasswordlength = optional_param('minpasswordlength', 8, PARAM_INT);
    $lockoutthreshold = optional_param('lockoutthreshold', 5, PARAM_INT);
    $lockoutwindow = optional_param('lockoutwindow', 30, PARAM_INT);
    $lockoutduration = optional_param('lockoutduration', 30, PARAM_INT);

    set_config('passwordpolicy', $passwordpolicy);
    set_config('minpasswordlength', $minpasswordlength);
    set_config('lockoutthreshold', $lockoutthreshold);
    set_config('lockoutwindow', $lockoutwindow);
    set_config('lockoutduration', $lockoutduration);

    $success = get_string('changessaved', 'admin');
}

// Get current settings
$passwordpolicy = get_config('core', 'passwordpolicy') ?? 1;
$minpasswordlength = get_config('core', 'minpasswordlength') ?? 8;
$lockoutthreshold = get_config('core', 'lockoutthreshold') ?? 5;
$lockoutwindow = get_config('core', 'lockoutwindow') ?? 30;
$lockoutduration = get_config('core', 'lockoutduration') ?? 30;

// Prepare context
$context = [
    'pagetitle' => get_string('security', 'admin'),
    'showadmin' => true,
    'has_navigation' => true,
    'navigation_html' => get_navigation_html(),
    'passwordpolicy' => $passwordpolicy,
    'minpasswordlength' => $minpasswordlength,
    'lockoutthreshold' => $lockoutthreshold,
    'lockoutwindow' => $lockoutwindow,
    'lockoutduration' => $lockoutduration,
    'success' => $success,
    'errors' => $errors,
    'haserrors' => !empty($errors),
    'sesskey' => sesskey(),
];

echo render_template('admin/settings_security', $context);
