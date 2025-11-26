<?php
/**
 * Login Layout
 *
 * Special layout for the login page with ISER branding.
 * Replicates Moodle's theme/boost/layout/login.php
 *
 * @package    theme_boost
 * @copyright  NexoSupport
 * @license    Proprietary - NexoSupport
 */

defined('NEXOSUPPORT_INTERNAL') || die();

// Include theme library
require_once(__DIR__ . '/../lib.php');

global $OUTPUT, $PAGE, $CFG;

// Build body classes for login page
$bodyclasses = ['pagelayout-login'];

// Login background
$loginbg = get_config('theme_boost', 'loginbackgroundimage');

// Get logo
$logo = get_config('theme_boost', 'logo');
if (empty($logo)) {
    $logo = $CFG->wwwroot . '/pix/logo.svg';
}

// Build template context
$templatecontext = [
    // Site info
    'sitename' => format_string($CFG->sitename ?? 'NexoSupport'),
    'wwwroot' => $CFG->wwwroot,

    // Body attributes
    'bodyattributes' => 'class="' . implode(' ', $bodyclasses) . '"',

    // Logo
    'logourl' => $logo,
    'haslogo' => !empty($logo),

    // Background
    'loginbackgroundimage' => $loginbg,
    'hasloginbackground' => !empty($loginbg),

    // Language
    'currentlang' => current_language(),
    'langdir' => get_string_direction(),

    // Language menu
    'langmenu' => true,

    // Output
    'output' => $OUTPUT,

    // Main content (login form)
    'maincontent' => $OUTPUT->main_content ?? '',

    // Additional info
    'canyoucreateaccount' => !empty($CFG->enableselfregistration),
    'forgotpasswordurl' => $CFG->wwwroot . '/login/forgot_password.php',
    'signupurl' => $CFG->wwwroot . '/login/signup.php',

    // ISER branding colors
    'brandcolor' => get_config('theme_boost', 'brandcolor') ?? '#1B9E88',
];

// Render template
echo $OUTPUT->render_from_template('theme_boost/login', $templatecontext);
