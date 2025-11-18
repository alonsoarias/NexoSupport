<?php
/**
 * Manual Authentication Settings
 *
 * @package auth_manual
 * @copyright NexoSupport
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('NEXOSUPPORT_INTERNAL') || die();

require_login();
require_capability('nexosupport/admin:manageconfig');

global $CFG;

$errors = [];
$success = null;

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();

    $minpasswordlength = required_param('minpasswordlength', PARAM_INT);
    $requireuppercase = optional_param('requireuppercase', 0, PARAM_BOOL);
    $requirelowercase = optional_param('requirelowercase', 0, PARAM_BOOL);
    $requirenumbers = optional_param('requirenumbers', 0, PARAM_BOOL);
    $requirespecialchars = optional_param('requirespecialchars', 0, PARAM_BOOL);

    // Validate minimum password length
    if ($minpasswordlength < 6) {
        $errors[] = get_string('minpasswordlengtherror', 'core');
    } elseif ($minpasswordlength > 64) {
        $errors[] = get_string('minpasswordlengthmaxerror', 'core');
    }

    if (empty($errors)) {
        try {
            set_config('minpasswordlength', $minpasswordlength, 'auth_manual');
            set_config('requireuppercase', $requireuppercase, 'auth_manual');
            set_config('requirelowercase', $requirelowercase, 'auth_manual');
            set_config('requirenumbers', $requirenumbers, 'auth_manual');
            set_config('requirespecialchars', $requirespecialchars, 'auth_manual');

            $success = get_string('configsaved');
        } catch (Exception $e) {
            $errors[] = get_string('errorconfig', 'core', $e->getMessage());
        }
    }
}

// Load current configuration values
$minpasswordlength = get_config('auth_manual', 'minpasswordlength') ?? 8;
$requireuppercase = get_config('auth_manual', 'requireuppercase') ?? 0;
$requirelowercase = get_config('auth_manual', 'requirelowercase') ?? 0;
$requirenumbers = get_config('auth_manual', 'requirenumbers') ?? 0;
$requirespecialchars = get_config('auth_manual', 'requirespecialchars') ?? 0;

// Prepare template context
$context = [
    'lang' => \core\string_manager::get_language(),
    'errors' => $errors,
    'has_errors' => !empty($errors),
    'success' => $success,
    'sesskey' => sesskey(),
    'minpasswordlength' => $minpasswordlength,
    'requireuppercase' => $requireuppercase,
    'requirelowercase' => $requirelowercase,
    'requirenumbers' => $requirenumbers,
    'requirespecialchars' => $requirespecialchars,
    'requireuppercase_checked' => $requireuppercase ? 'checked' : '',
    'requirelowercase_checked' => $requirelowercase ? 'checked' : '',
    'requirenumbers_checked' => $requirenumbers ? 'checked' : '',
    'requirespecialchars_checked' => $requirespecialchars ? 'checked' : '',
];

echo render_template('auth/manual_settings', $context);
