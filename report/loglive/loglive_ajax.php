<?php
/**
 * AJAX endpoint for live log updates.
 *
 * Returns new log entries since a given timestamp.
 *
 * @package    report_loglive
 * @copyright  2025 NexoSupport
 * @license    Proprietary - NexoSupport
 */

define('AJAX_SCRIPT', true);

require('../../config.php');

// Parameters.
$courseid = optional_param('id', 0, PARAM_INT);
$since = optional_param('since', 0, PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);

// Setup context.
if ($courseid && $courseid != SITEID) {
    $course = $DB->get_record('courses', ['id' => $courseid]);
    if (!$course) {
        echo json_encode(['error' => 'Invalid course']);
        exit;
    }
    require_login($course);
    $context = context_course::instance($courseid);
} else {
    require_login();
    $context = context_system::instance();
    $courseid = SITEID;
}

// Check capability.
if (!has_capability('report/loglive:view', $context)) {
    echo json_encode(['error' => 'Access denied']);
    exit;
}

// Create renderable with 'since' timestamp.
$url = new nexo_url('/report/loglive/index.php', ['id' => $courseid]);
$renderable = new \report_loglive\renderable($courseid, $page, 100, $url, $since);

// Get the renderer.
$output = $PAGE->get_renderer('report_loglive');

// Output JSON response.
header('Content-Type: application/json');
echo $output->render_ajax($renderable);
