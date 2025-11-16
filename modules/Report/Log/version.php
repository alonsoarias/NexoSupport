<?php
/**
 * NexoSupport - Log Report Version
 *
 * @package    report_log
 * @copyright  2024 ISER
 * @license    Proprietary
 */

defined('NEXOSUPPORT_INTERNAL') || die();

$plugin = new stdClass();
$plugin->component = 'report_log';
$plugin->version = 2024111602;  // YYYYMMDDXX
$plugin->requires = 2024111600; // Requires NexoSupport core version
$plugin->maturity = MATURITY_STABLE;
$plugin->release = '2.0.0';
$plugin->description = 'Comprehensive system logging and reporting';
