<?php
/**
 * Security overview report.
 *
 * This report displays security checks and their status.
 *
 * @package    report_security
 * @copyright  2025 NexoSupport
 * @license    Proprietary - NexoSupport
 */

// No output buffering - allows streaming for long operations.
if (!defined('NO_OUTPUT_BUFFERING')) {
    define('NO_OUTPUT_BUFFERING', true);
}

// Load config if not already loaded (direct access vs router)
if (!defined('NEXOSUPPORT_INTERNAL')) {
    require(__DIR__ . '/../../config.php');
}

global $CFG, $DB, $USER, $PAGE, $OUTPUT;
require_once($CFG->libdir . '/adminlib.php');

// This page requires admin access.
admin_externalpage_setup('reportsecurity', '', null, '', ['pagelayout' => 'report']);

// Check capability.
$context = context_system::instance();
require_capability('report/security:view', $context);

// Get detail parameter for specific check view.
$detail = optional_param('detail', '', PARAM_TEXT);

// Page URL.
$url = new nexo_url('/report/security/index.php');
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
