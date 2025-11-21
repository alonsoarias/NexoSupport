<?php
/**
 * Maintenance Layout - Used during maintenance mode
 *
 * @package    theme_boost
 * @copyright  NexoSupport
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('NEXOSUPPORT_INTERNAL') || die();

global $OUTPUT, $PAGE, $CFG;

// Body attributes
$bodyattributes = $OUTPUT->body_attributes(['pagelayout-maintenance']);

// Build context
$templatecontext = [
    'sitename' => format_string($CFG->sitename ?? 'NexoSupport'),
    'output' => $OUTPUT,
    'bodyattributes' => $bodyattributes,
    'wwwroot' => $CFG->wwwroot,
];

// Render template
echo $OUTPUT->render_from_template('theme_boost/maintenance', $templatecontext);
