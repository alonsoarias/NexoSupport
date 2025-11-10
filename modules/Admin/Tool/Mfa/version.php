<?php
/**
 * ISER MFA System - Version Information
 *
 * @package    ISER\Modules\Admin\Tool\Mfa
 * @copyright  2024 ISER
 * @license    Proprietary
 */

defined('ISER_BASE_DIR') or die('Direct access not allowed');

$module = [
    'name' => 'Multi-Factor Authentication',
    'component' => 'tool_mfa',
    'version' => '5.0.0',
    'requires' => '2.0.0', // Minimum ISER core version
    'maturity' => 'STABLE',
    'release' => '5.0.0',
    'dependencies' => [
        'auth' => '2.0.0',
        'user' => '3.0.0',
    ],
];
