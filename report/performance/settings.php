<?php
/**
 * Settings for report_performance.
 *
 * @package    report_performance
 * @copyright  2025 NexoSupport
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('INTERNAL_ACCESS') || die();

// Register the report in the admin tree.
$ADMIN->add(
    'reports',
    new admin_externalpage(
        'reportperformance',
        get_string('pluginname', 'report_performance'),
        new nexo_url('/report/performance/index.php'),
        'report/performance:view'
    )
);
