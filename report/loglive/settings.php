<?php
/**
 * Settings for report_loglive.
 *
 * @package    report_loglive
 * @copyright  2025 NexoSupport
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('INTERNAL_ACCESS') || die();

// Register the report in the admin tree.
$ADMIN->add(
    'reports',
    new admin_externalpage(
        'reportloglive',
        get_string('pluginname', 'report_loglive'),
        new moodle_url('/report/loglive/index.php'),
        'report/loglive:view'
    )
);
