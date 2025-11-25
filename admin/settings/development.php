<?php
/**
 * Development Settings
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

    $themedesignermode = optional_param('themedesignermode', 0, PARAM_INT);
    $cachejs = optional_param('cachejs', 1, PARAM_INT);

    set_config('themedesignermode', $themedesignermode);
    set_config('cachejs', $cachejs);

    $success = get_string('changessaved', 'admin');
}

// Get current settings
$themedesignermode = get_config('core', 'themedesignermode') ?? 0;
$cachejs = get_config('core', 'cachejs') ?? 1;

// Prepare context
$context = [
    'pagetitle' => get_string('development', 'admin'),
    'showadmin' => true,
    'has_navigation' => true,
    'navigation_html' => get_navigation_html(),
    'themedesignermode' => $themedesignermode,
    'cachejs' => $cachejs,
    'success' => $success,
    'errors' => $errors,
    'haserrors' => !empty($errors),
    'sesskey' => sesskey(),
];

echo render_template('admin/settings_development', $context);
