<?php
/**
 * Single Column Layout - No sidebar
 *
 * @package    theme_boost
 * @copyright  NexoSupport
 * @license    Proprietary - NexoSupport
 */

defined('NEXOSUPPORT_INTERNAL') || die();

global $OUTPUT, $PAGE, $USER, $CFG;

// Body attributes
$bodyattributes = $OUTPUT->body_attributes([]);

// Build context
$templatecontext = [
    'sitename' => format_string($CFG->sitename ?? 'NexoSupport'),
    'output' => $OUTPUT,
    'bodyattributes' => $bodyattributes,
    'wwwroot' => $CFG->wwwroot,
    'isloggedin' => isloggedin() && !isguestuser(),
    'isadmin' => is_siteadmin(),
    'userfullname' => fullname($USER) ?? '',
];

// Render template
echo $OUTPUT->render_from_template('theme_boost/columns1', $templatecontext);
