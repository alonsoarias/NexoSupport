<?php
/**
 * Debugging Settings
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

    $debug = optional_param('debug', 0, PARAM_INT);
    $debugdisplay = optional_param('debugdisplay', 0, PARAM_INT);

    set_config('debug', $debug);
    set_config('debugdisplay', $debugdisplay);

    $success = get_string('changessaved', 'admin');
}

// Get current settings
$debug = get_config('core', 'debug') ?? 0;
$debugdisplay = get_config('core', 'debugdisplay') ?? 0;

// Debug levels
$debuglevels = [
    ['value' => 0, 'name' => get_string('debugnone', 'admin'), 'selected' => $debug == 0],
    ['value' => 5, 'name' => get_string('debugminimal', 'admin'), 'selected' => $debug == 5],
    ['value' => 15, 'name' => get_string('debugnormal', 'admin'), 'selected' => $debug == 15],
    ['value' => 6143, 'name' => get_string('debugall', 'admin'), 'selected' => $debug == 6143],
    ['value' => 32767, 'name' => get_string('debugdeveloper', 'admin'), 'selected' => $debug == 32767],
];

// Prepare context
$context = [
    'pagetitle' => get_string('debugging', 'admin'),
    'showadmin' => true,
    'has_navigation' => true,
    'navigation_html' => get_navigation_html(),
    'debuglevels' => $debuglevels,
    'debugdisplay' => $debugdisplay,
    'success' => $success,
    'errors' => $errors,
    'haserrors' => !empty($errors),
    'sesskey' => sesskey(),
];

echo render_template('admin/settings_debugging', $context);
