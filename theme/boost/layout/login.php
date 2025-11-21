<?php
/**
 * Login Layout - Special layout for login page
 *
 * @package    theme_boost
 * @copyright  NexoSupport
 * @license    Proprietary - NexoSupport
 */

defined('NEXOSUPPORT_INTERNAL') || die();

global $OUTPUT, $PAGE, $CFG;

// Body attributes
$bodyattributes = $OUTPUT->body_attributes(['pagelayout-login']);

// Build context
$templatecontext = [
    'sitename' => format_string($CFG->sitename ?? 'NexoSupport'),
    'output' => $OUTPUT,
    'bodyattributes' => $bodyattributes,
    'wwwroot' => $CFG->wwwroot,
    'currentlang' => current_language(),
];

// Render template
echo $OUTPUT->render_from_template('theme_boost/login', $templatecontext);
