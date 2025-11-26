<?php
/**
 * Drawers Layout - Main layout with sidebar drawers
 *
 * This is the main Boost theme layout with navigation drawers.
 * Replicates Moodle 4.x Boost theme layout structure.
 *
 * @package    theme_boost
 * @copyright  NexoSupport
 * @license    Proprietary - NexoSupport
 */

defined('NEXOSUPPORT_INTERNAL') || die();

// Include theme library
require_once(__DIR__ . '/../lib.php');

global $OUTPUT, $PAGE, $USER, $CFG;

// Get base template context
$templatecontext = theme_boost_get_template_context($PAGE);

// Get regions for this layout
$regions = [];
if (isset($PAGE->layout_options['regions'])) {
    $regions = $PAGE->layout_options['regions'];
}

// Block regions
$sidepreblocks = '';
$hassidepreblocks = false;

if (in_array('side-pre', $regions) || true) { // Default to having side-pre
    if (method_exists($PAGE, 'blocks_for_region')) {
        $sidepreblocks = $PAGE->blocks_for_region('side-pre');
        $hassidepreblocks = !empty($sidepreblocks);
    }
}

// Drawer state from user preferences
$draweropen = get_user_preferences('drawer-open-nav', true);
$blockdraweropen = get_user_preferences('drawer-open-block', $hassidepreblocks);

// Check if drawer should open
$draweropenontop = get_config('theme_boost', 'draweropenontop') ?? false;

// Build body classes
$bodyclasses = [];
if ($draweropen) {
    $bodyclasses[] = 'drawer-open-left';
}
if ($hassidepreblocks && $blockdraweropen) {
    $bodyclasses[] = 'drawer-open-index';
}

// Get primary navigation
$primarynav = null;
if (class_exists('\\core\\navigation\\primary_navigation')) {
    $primarynav = new \core\navigation\primary_navigation($PAGE);
}

// Get secondary navigation
$secondarynav = null;
if (isset($PAGE->secondarynav)) {
    $secondarynav = $PAGE->secondarynav;
}

// Build complete template context
$templatecontext = array_merge($templatecontext, [
    // Drawer state
    'draweropen' => $draweropen,
    'draweropenright' => $hassidepreblocks && $blockdraweropen,
    'draweropenblockleft' => false,
    'draweropenblockright' => $hassidepreblocks && $blockdraweropen,

    // Block regions
    'sidepreblocks' => $sidepreblocks,
    'hassidepreblocks' => $hassidepreblocks,
    'hasblocks' => $hassidepreblocks,

    // Navigation
    'primarynav' => $primarynav ? $primarynav->export_for_template($OUTPUT) : null,
    'secondarynav' => $secondarynav,
    'hassecondarynav' => !empty($secondarynav),

    // Page content
    'maincontent' => $OUTPUT->main_content ?? '',
    'courseheader' => $OUTPUT->course_header ?? '',
    'coursecontentfooter' => $OUTPUT->course_content_footer ?? '',

    // Breadcrumbs
    'breadcrumbs' => $PAGE->breadcrumbs ?? [],
    'hasnavbar' => !($PAGE->layout_options['nonavbar'] ?? false),

    // Body classes
    'additionalbodyclasses' => implode(' ', $bodyclasses),

    // Layout options
    'nonavbar' => $PAGE->layout_options['nonavbar'] ?? false,
    'nofooter' => $PAGE->layout_options['nofooter'] ?? false,
    'langmenu' => $PAGE->layout_options['langmenu'] ?? false,
]);

// Render template
echo $OUTPUT->render_from_template('theme_boost/drawers', $templatecontext);
