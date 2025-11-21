<?php
/**
 * Settings for report_log.
 *
 * @package    report_log
 * @copyright  2025 NexoSupport
 * @license    Proprietary - NexoSupport
 */

defined('NEXOSUPPORT_INTERNAL') || die();

// Register the report in the admin tree.
$ADMIN->add(
    'reports',
    new admin_externalpage(
        'reportlog',
        get_string('pluginname', 'report_log'),
        new \core\nexo_url('/report/log/index.php'),
        'report/log:view'
    )
);
