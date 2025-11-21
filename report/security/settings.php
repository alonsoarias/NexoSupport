<?php
/**
 * Settings for report_security.
 *
 * @package    report_security
 * @copyright  2025 NexoSupport
 * @license    Proprietary - NexoSupport
 */

defined('NEXOSUPPORT_INTERNAL') || die();

// Register the report in the admin tree.
$ADMIN->add(
    'reports',
    new admin_externalpage(
        'reportsecurity',
        get_string('pluginname', 'report_security'),
        new \core\nexo_url('/report/security/index.php'),
        'report/security:view'
    )
);
