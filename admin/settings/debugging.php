<?php
/**
 * Debugging Settings - NexoSupport
 *
 * Configuration page for debugging and error display settings.
 * Similar to Moodle's debugging settings but adapted to NexoSupport's architecture.
 *
 * @package core
 * @subpackage admin
 */

define('NEXOSUPPORT_INTERNAL', 1);
require_once('../../lib/setup.php');

// Require login and admin access
require_login();
require_admin();

// Get current user
global $USER, $DB, $OUTPUT, $CFG;

// Page setup
$PAGE->set_url('/admin/settings/debugging');
$PAGE->set_title(get_string('debugging', 'core'));
$PAGE->set_heading(get_string('debugging', 'core'));
$PAGE->set_context(CONTEXT_SYSTEM);

$success = null;
$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validate_sesskey()) {
    $debug_level = required_param('debug', PARAM_INT);
    $debug_display = optional_param('debugdisplay', 0, PARAM_INT);

    // Validate debug level
    $valid_levels = [DEBUG_NONE, DEBUG_MINIMAL, DEBUG_NORMAL, DEBUG_DEVELOPER, DEBUG_ALL];
    if (!in_array($debug_level, $valid_levels)) {
        $errors[] = get_string('invaliddebug level', 'core');
    } else {
        // Save to database
        $DB->set_config('debug', $debug_level, 'core');
        $DB->set_config('debugdisplay', $debug_display, 'core');

        // Update $CFG immediately
        $CFG->debug = $debug_level;
        $CFG->debugdisplay = (bool)$debug_display;

        // Apply settings immediately
        if ($debug_level !== DEBUG_NONE) {
            error_reporting($debug_level);
            ini_set('display_errors', $debug_display ? '1' : '0');
        } else {
            error_reporting(0);
            ini_set('display_errors', '0');
        }

        $success = get_string('configsaved', 'core');

        // Redirect to avoid form resubmission
        redirect('/admin/settings/debugging');
    }
}

// Get current settings
$current_debug = get_config('debug', 'core') ?? DEBUG_NONE;
$current_debugdisplay = get_config('debugdisplay', 'core') ?? 0;

// Prepare debug levels data
$debug_levels = [
    [
        'key' => 'none',
        'value' => DEBUG_NONE,
        'selected' => ($current_debug == DEBUG_NONE),
        'badge' => 'recommended_production',
        'badge_type' => 'success'
    ],
    [
        'key' => 'minimal',
        'value' => DEBUG_MINIMAL,
        'selected' => ($current_debug == DEBUG_MINIMAL),
        'badge' => null,
        'badge_type' => null
    ],
    [
        'key' => 'normal',
        'value' => DEBUG_NORMAL,
        'selected' => ($current_debug == DEBUG_NORMAL),
        'badge' => null,
        'badge_type' => null
    ],
    [
        'key' => 'developer',
        'value' => DEBUG_DEVELOPER,
        'selected' => ($current_debug == DEBUG_DEVELOPER),
        'badge' => 'developer_only',
        'badge_type' => 'warning'
    ],
    [
        'key' => 'all',
        'value' => DEBUG_ALL,
        'selected' => ($current_debug == DEBUG_ALL),
        'badge' => 'experts_only',
        'badge_type' => 'danger'
    ],
];

// Get debug level name
$debug_names = [
    DEBUG_NONE => 'NONE',
    DEBUG_MINIMAL => 'MINIMAL',
    DEBUG_NORMAL => 'NORMAL',
    DEBUG_DEVELOPER => 'DEVELOPER',
    DEBUG_ALL => 'ALL'
];
$current_debug_name = $debug_names[$current_debug] ?? 'UNKNOWN';

// Prepare context for template
$context = [
    'sesskey' => sesskey(),
    'success' => $success,
    'errors' => $errors,
    'haserrors' => !empty($errors),
    'debug_levels' => $debug_levels,
    'debugdisplay_checked' => ($current_debugdisplay == 1),
    'current_debug' => (int)$current_debug,
    'current_debug_name' => $current_debug_name,
    'current_debugdisplay' => (bool)$current_debugdisplay,
    'php_error_reporting' => error_reporting(),
    'php_display_errors' => ini_get('display_errors') ? 'On' : 'Off',
];

// Render and output
echo render_template('admin/debugging', $context);
