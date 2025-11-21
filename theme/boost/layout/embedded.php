<?php
/**
 * Embedded Layout - Minimal layout for embedded content (iframes, etc)
 *
 * @package    theme_boost
 * @copyright  NexoSupport
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('NEXOSUPPORT_INTERNAL') || die();

global $OUTPUT, $PAGE, $CFG;

// Body attributes
$bodyattributes = $OUTPUT->body_attributes(['pagelayout-embedded']);

// Build context
$templatecontext = [
    'output' => $OUTPUT,
    'bodyattributes' => $bodyattributes,
];

// Render template
echo $OUTPUT->render_from_template('theme_boost/embedded', $templatecontext);
