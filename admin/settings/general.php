<?php
/**
 * General Settings
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

    // Get settings from form
    $sitename = required_param('sitename', PARAM_TEXT);
    $lang = required_param('lang', PARAM_ALPHANUMEXT);

    // Save settings
    set_config('sitename', $sitename);
    set_config('lang', $lang);

    $success = get_string('changessaved', 'admin');
}

// Get current settings
$sitename = get_config('core', 'sitename') ?: ($CFG->sitename ?? 'NexoSupport');
$lang = get_config('core', 'lang') ?: ($CFG->lang ?? 'es');

// Available languages
$languages = [
    ['code' => 'es', 'name' => 'Español', 'selected' => $lang === 'es'],
    ['code' => 'en', 'name' => 'English', 'selected' => $lang === 'en'],
];

// Prepare context
$context = [
    'pagetitle' => get_string('generalsettings', 'admin'),
    'showadmin' => true,
    'has_navigation' => true,
    'navigation_html' => get_navigation_html(),
    'sitename' => htmlspecialchars($sitename),
    'languages' => $languages,
    'success' => $success,
    'errors' => $errors,
    'haserrors' => !empty($errors),
    'sesskey' => sesskey(),
];

echo render_template('admin/settings_general', $context);
