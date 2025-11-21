<?php
/**
 * Log report page.
 *
 * Displays filtered log entries from the log store.
 *
 * @package    report_log
 * @copyright  2025 NexoSupport
 * @license    Proprietary - NexoSupport
 */

// Load config if not already loaded (direct access vs router)
if (!defined('NEXOSUPPORT_INTERNAL')) {
    require(__DIR__ . '/../../config.php');
}

global $CFG, $DB, $USER, $PAGE, $OUTPUT;
require_once($CFG->libdir . '/adminlib.php');

// Parameters.
$courseid = optional_param('id', 0, PARAM_INT);
$userid = optional_param('user', 0, PARAM_INT);
$date = optional_param('date', 0, PARAM_INT);
$modid = optional_param('modid', 0, PARAM_ALPHANUMEXT);
$modaction = optional_param('modaction', '', PARAM_ALPHAEXT);
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 100, PARAM_INT);
$download = optional_param('download', '', PARAM_ALPHA);
$edulevel = optional_param('edulevel', -1, PARAM_INT);
$origin = optional_param('origin', '', PARAM_TEXT);

// Setup page.
if ($courseid && $courseid != SITEID) {
    $course = $DB->get_record('courses', ['id' => $courseid]);
    if (!$course) {
        throw new nexo_exception('invalidcourseid');
    }
    require_login($course);
    $context = context_course::instance($courseid);
    $PAGE->set_context($context);
} else {
    admin_externalpage_setup('reportlog', '', null, '', ['pagelayout' => 'report']);
    $context = context_system::instance();
    $courseid = SITEID;
}

// Check capability.
require_capability('report/log:view', $context);

// Page URL with filters.
$url = new nexo_url('/report/log/index.php', [
    'id' => $courseid,
    'user' => $userid,
    'date' => $date,
    'modid' => $modid,
    'modaction' => $modaction,
    'edulevel' => $edulevel,
    'origin' => $origin,
]);
$PAGE->set_url($url);
$PAGE->set_title(get_string('pluginname', 'report_log'));
$PAGE->set_heading(get_string('pluginname', 'report_log'));

// Create renderable.
$renderable = new \report_log\renderable(
    $courseid,
    $userid,
    $modid,
    $modaction,
    $date,
    $edulevel,
    $origin,
    $page,
    $perpage,
    $url
);

// Handle downloads.
if (!empty($download)) {
    $renderable->download($download);
    exit;
}

// Output.
$output = $PAGE->get_renderer('report_log');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'report_log'));

// Render filters and table.
echo $output->render($renderable);

echo $OUTPUT->footer();

// Trigger event.
$event = \report_log\event\report_viewed::create([
    'context' => $context,
    'other' => ['courseid' => $courseid],
]);
$event->trigger();
