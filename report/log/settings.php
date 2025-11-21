<?php
/**
 * Settings for report_log.
 *
 * @package    report_log
 * @copyright  2025 NexoSupport
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('INTERNAL_ACCESS') || die();

// Register the report in the admin tree.
$ADMIN->add(
    'reports',
    new admin_externalpage(
        'reportlog',
        get_string('pluginname', 'report_log'),
        new moodle_url('/report/log/index.php'),
        'report/log:view'
    )
);
