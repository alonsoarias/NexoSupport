<?php
/**
 * System Settings
 *
 * @package NexoSupport
 */

defined('NEXOSUPPORT_INTERNAL') || die();

require_login();
require_capability('nexosupport/admin:manageconfig');

global $USER, $CFG;

$errors = [];
$success = null;

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();

    $sitename = required_param('sitename', PARAM_TEXT);
    $debug = optional_param('debug', 0, PARAM_BOOL);
    $sessiontimeout = required_param('sessiontimeout', PARAM_INT);

    try {
        set_config('sitename', $sitename, 'core');
        set_config('debug', $debug ? 'true' : 'false', 'core');
        set_config('sessiontimeout', $sessiontimeout, 'core');

        $success = get_string('configsaved');
    } catch (Exception $e) {
        $errors[] = get_string('errorconfig', 'core', $e->getMessage());
    }
}

// Cargar configuración actual
$sitename = get_config('core', 'sitename') ?? 'NexoSupport';
$debug = get_config('core', 'debug') === 'true';
$sessiontimeout = get_config('core', 'sessiontimeout') ?? 7200;

// Prepare context for template
$context = [
    'user' => $USER,
    'showadmin' => true,
    'success' => $success ? htmlspecialchars($success) : null,
    'errors' => array_map('htmlspecialchars', $errors),
    'haserrors' => !empty($errors),
    'sitename' => htmlspecialchars($sitename),
    'debug' => $debug,
    'sessiontimeout' => $sessiontimeout,
    'sesskey' => sesskey(),
    'systemversion' => get_config('core', 'version') ?? get_string('unknown', 'core'),
    'phpversion' => phpversion(),
    'dbtype' => $CFG->dbtype,
    'dbprefix' => $CFG->dbprefix,
    'currentusername' => htmlspecialchars($USER->username ?? 'Guest'),
];

// Render and output
echo render_template('admin/settings', $context);
