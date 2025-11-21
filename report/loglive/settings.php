<?php
/**
 * Settings for report_loglive.
 *
 * @package    report_loglive
 * @copyright  2025 NexoSupport
 * @license    Proprietary - NexoSupport
 */

defined('NEXOSUPPORT_INTERNAL') || die();

// Register the report in the admin tree.
$ADMIN->add(
    'reports',
    new admin_externalpage(
        'reportloglive',
        get_string('pluginname', 'report_loglive'),
        new \core\nexo_url('/report/loglive/index.php'),
        'report/loglive:view'
    )
);
