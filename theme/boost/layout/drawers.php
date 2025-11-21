<?php
/**
 * Drawers Layout - Main layout with sidebar drawers
 *
 * @package    theme_boost
 * @copyright  NexoSupport
 * @license    Proprietary - NexoSupport
 */

defined('NEXOSUPPORT_INTERNAL') || die();

global $OUTPUT, $PAGE, $USER, $CFG;

// Get layout options
$hasblocks = $PAGE->blocks_for_region('side-pre') ?? '';
$hasdrawer = !empty($hasblocks);

// User preferences for drawer state
$draweropen = get_user_preferences('drawer-open-nav', true);
$blockdraweropen = get_user_preferences('drawer-open-block', true);

// Body attributes
$bodyattributes = $OUTPUT->body_attributes([]);

// Build context
$templatecontext = [
    'sitename' => format_string($CFG->sitename ?? 'NexoSupport'),
    'output' => $OUTPUT,
    'bodyattributes' => $bodyattributes,
    'sidepreblocks' => $hasblocks,
    'hasblocks' => $hasdrawer,
    'draweropen' => $draweropen,
    'blockdraweropen' => $blockdraweropen && $hasdrawer,
    'userfullname' => fullname($USER) ?? '',
    'userprofileurl' => $CFG->wwwroot . '/user/profile',
    'logouturl' => $CFG->wwwroot . '/logout',
    'wwwroot' => $CFG->wwwroot,
    'isloggedin' => isloggedin() && !isguestuser(),
    'isadmin' => is_siteadmin(),
    'currentlang' => current_language(),
];

// Get navigation if available
if (function_exists('get_navigation_html')) {
    $templatecontext['navigation'] = get_navigation_html();
    $templatecontext['hasnavigation'] = !empty($templatecontext['navigation']);
}

// Render template
echo $OUTPUT->render_from_template('theme_boost/drawers', $templatecontext);
