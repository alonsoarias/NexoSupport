<?php
/**
 * Settings for report_security.
 *
 * @package    report_security
 * @copyright  2025 NexoSupport
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('INTERNAL_ACCESS') || die();

// Register the report in the admin tree.
$ADMIN->add(
    'reports',
    new admin_externalpage(
        'reportsecurity',
        get_string('pluginname', 'report_security'),
        new moodle_url('/report/security/index.php'),
        'report/security:view'
    )
);
