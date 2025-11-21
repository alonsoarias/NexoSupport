<?php
/**
 * MFA plugin version information.
 *
 * @package    tool_mfa
 * @copyright  NexoSupport
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('NEXOSUPPORT_INTERNAL') || die();

$plugin->version   = 2025012100;
$plugin->requires  = 2025011820;
$plugin->component = 'tool_mfa';
$plugin->maturity  = MATURITY_STABLE;
$plugin->release   = '1.0.0';

// Subplugins
$plugin->subplugins = [
    'factor' => 'admin/tool/mfa/factor',
];
