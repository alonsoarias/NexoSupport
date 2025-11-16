<?php
/**
 * NexoSupport - Install Addon Tool Version
 *
 * @package    tool_installaddon
 * @copyright  2024 ISER
 * @license    Proprietary
 */

defined('NEXOSUPPORT_INTERNAL') || die();

$plugin = new stdClass();
$plugin->component = 'tool_installaddon';
$plugin->version = 2024111601;  // YYYYMMDDXX
$plugin->requires = 2024111600; // Requires NexoSupport core version
$plugin->maturity = MATURITY_ALPHA;
$plugin->release = '0.5.0';
$plugin->description = 'Install plugins and addons from ZIP files';
