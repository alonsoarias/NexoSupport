<?php
/**
 * Security overview report.
 *
 * This report displays security checks and their status.
 *
 * @package    report_security
 * @copyright  2025 NexoSupport
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// No output buffering - allows streaming for long operations.
define('NO_OUTPUT_BUFFERING', true);

require('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

// This page requires admin access.
admin_externalpage_setup('reportsecurity', '', null, '', ['pagelayout' => 'report']);

// Check capability.
$context = context_system::instance();
require_capability('report/security:view', $context);

// Get detail parameter for specific check view.
$detail = optional_param('detail', '', PARAM_TEXT);

// Page URL.
$url = new moodle_url('/report/security/index.php');
$PAGE->set_url($url);

// Create the check table.
$table = new core\check\table('security', $url, $detail);

// Output.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'report_security'));

echo '<p class="lead">' . get_string('securityreportdesc', 'report_security') . '</p>';

// Render the table.
echo $table->render($OUTPUT);

echo $OUTPUT->footer();

// Trigger event.
$event = \report_security\event\report_viewed::create(['context' => $context]);
$event->trigger();
