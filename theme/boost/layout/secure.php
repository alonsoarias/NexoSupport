<?php
/**
 * Secure Layout - Used for secure pages (quiz attempts, exams)
 *
 * @package    theme_boost
 * @copyright  NexoSupport
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('NEXOSUPPORT_INTERNAL') || die();

global $OUTPUT, $PAGE, $USER, $CFG;

// Body attributes
$bodyattributes = $OUTPUT->body_attributes(['pagelayout-secure']);

// Build context
$templatecontext = [
    'sitename' => format_string($CFG->sitename ?? 'NexoSupport'),
    'output' => $OUTPUT,
    'bodyattributes' => $bodyattributes,
    'wwwroot' => $CFG->wwwroot,
    'userfullname' => fullname($USER) ?? '',
];

// Render template
echo $OUTPUT->render_from_template('theme_boost/secure', $templatecontext);
