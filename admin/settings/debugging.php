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
$debug = (int)(get_config('core', 'debug') ?? 0);
$debugdisplay = (int)(get_config('core', 'debugdisplay') ?? 0);

// Debug level names mapping
$debug_names = [
    0 => get_string('debugnone', 'admin'),
    5 => get_string('debugminimal', 'admin'),
    15 => get_string('debugnormal', 'admin'),
    6143 => get_string('debugall', 'admin'),
    32767 => get_string('debugdeveloper', 'admin'),
];

// Debug levels for template
$debug_levels = [
    ['key' => 'none', 'value' => 0, 'selected' => $debug == 0],
    ['key' => 'minimal', 'value' => 5, 'selected' => $debug == 5],
    ['key' => 'normal', 'value' => 15, 'selected' => $debug == 15],
    ['key' => 'all', 'value' => 6143, 'selected' => $debug == 6143, 'badge' => 'development', 'badge_type' => 'warning'],
    ['key' => 'developer', 'value' => 32767, 'selected' => $debug == 32767, 'badge' => 'development', 'badge_type' => 'danger'],
];

// Prepare context
$context = [
    'pagetitle' => get_string('debugging', 'admin'),
    'showadmin' => true,
    'has_navigation' => true,
    'navigation_html' => get_navigation_html(),
    'debug_levels' => $debug_levels,
    'debugdisplay_checked' => $debugdisplay == 1,
    'current_debug' => $debug,
    'current_debug_name' => $debug_names[$debug] ?? get_string('unknown', 'core'),
    'current_debugdisplay' => $debugdisplay == 1,
    'php_error_reporting' => error_reporting(),
    'php_display_errors' => ini_get('display_errors') ? 'On' : 'Off',
    'success' => $success,
    'errors' => $errors,
    'haserrors' => !empty($errors),
    'sesskey' => sesskey(),
];

echo render_template('admin/debugging', $context);
