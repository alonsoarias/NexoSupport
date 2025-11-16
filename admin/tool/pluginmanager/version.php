<?php
/**
 * NexoSupport - Plugin Manager Tool Version
 *
 * @package    tool_pluginmanager
 * @copyright  2024 ISER
 * @license    Proprietary
 */

defined('NEXOSUPPORT_INTERNAL') || die();

$plugin = new stdClass();
$plugin->component = 'tool_pluginmanager';
$plugin->version = 2024111601;  // YYYYMMDDXX
$plugin->requires = 2024111600; // Requires NexoSupport core version
$plugin->maturity = MATURITY_STABLE;
$plugin->release = '1.0.0';
