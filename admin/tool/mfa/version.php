<?php
/**
 * NexoSupport - Multi-Factor Authentication Tool Version
 *
 * @package    tool_mfa
 * @copyright  2024 ISER
 * @license    Proprietary
 */

defined('NEXOSUPPORT_INTERNAL') || die();

$plugin = new stdClass();
$plugin->component = 'tool_mfa';
$plugin->version = 2024111601;  // YYYYMMDDXX
$plugin->requires = 2024111600; // Requires NexoSupport core version
$plugin->maturity = MATURITY_BETA;
$plugin->release = '0.9.0';
$plugin->description = 'Multi-Factor Authentication system with pluggable factors';
