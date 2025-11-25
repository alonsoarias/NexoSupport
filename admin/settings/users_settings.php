<?php
/**
 * User Settings
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

    $defaultauth = optional_param('defaultauth', 'manual', PARAM_ALPHANUMEXT);
    $selfregistration = optional_param('selfregistration', 0, PARAM_INT);

    set_config('defaultauth', $defaultauth);
    set_config('selfregistration', $selfregistration);

    $success = get_string('changessaved', 'admin');
}

// Get current settings
$defaultauth = get_config('core', 'defaultauth') ?? 'manual';
$selfregistration = get_config('core', 'selfregistration') ?? 0;

// Available auth methods
$authmethods = [
    ['code' => 'manual', 'name' => get_string('authmanual', 'admin'), 'selected' => $defaultauth === 'manual'],
    ['code' => 'ldap', 'name' => get_string('authldap', 'admin'), 'selected' => $defaultauth === 'ldap'],
];

// Prepare context
$context = [
    'pagetitle' => get_string('usersettings', 'admin'),
    'showadmin' => true,
    'has_navigation' => true,
    'navigation_html' => get_navigation_html(),
    'authmethods' => $authmethods,
    'selfregistration' => $selfregistration,
    'success' => $success,
    'errors' => $errors,
    'haserrors' => !empty($errors),
    'sesskey' => sesskey(),
];

echo render_template('admin/settings_users', $context);
