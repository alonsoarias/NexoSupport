<?php
/**
 * Performance overview report.
 *
 * This report displays performance checks and their status.
 *
 * @package    report_performance
 * @copyright  2025 NexoSupport
 * @license    Proprietary - NexoSupport
 */

// No output buffering - allows streaming for long operations.
define('NO_OUTPUT_BUFFERING', true);

require('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

// This page requires admin access.
admin_externalpage_setup('reportperformance', '', null, '', ['pagelayout' => 'report']);

// Check capability.
$context = context_system::instance();
require_capability('report/performance:view', $context);

// Get detail parameter for specific check view.
$detail = optional_param('detail', '', PARAM_TEXT);

// Page URL.
$url = new nexo_url('/report/performance/index.php');
$PAGE->set_url($url);

// Create the check table.
$table = new core\check\table('performance', $url, $detail);

// Output.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'report_performance'));

echo '<p class="lead">' . get_string('performancereportdesc', 'report_performance') . '</p>';

// Render the table.
echo $table->render($OUTPUT);

echo $OUTPUT->footer();

// Trigger event.
$event = \report_performance\event\report_viewed::create(['context' => $context]);
$event->trigger();
