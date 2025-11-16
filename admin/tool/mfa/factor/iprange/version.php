<?php
/**
 * NexoSupport - IP Range MFA Factor - Version
 *
 * @package    factor_iprange
 * @copyright  2024 ISER
 * @license    Proprietary
 */

defined('NEXOSUPPORT_INTERNAL') || die();

$plugin = new stdClass();
$plugin->component = 'factor_iprange';
$plugin->version = 2025011600;
$plugin->requires = 2025010100;
$plugin->release = '1.0.0';
$plugin->maturity = MATURITY_STABLE;
$plugin->dependencies = [
    'tool_mfa' => 2025011600,
];
