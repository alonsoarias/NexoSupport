<?php
/**
 * ISER Roles and Permissions Module - Version Information
 *
 * @package    ISER\Modules\Roles
 * @copyright  2024 ISER
 * @license    Proprietary
 */

defined('ISER_BASE_DIR') or die('Direct access not allowed');

$module = [
    'name' => 'Roles and Permissions',
    'component' => 'roles',
    'version' => '4.0.0',
    'requires' => '3.0.0', // Minimum ISER core version
    'maturity' => 'STABLE',
    'release' => '4.0.0',
    'dependencies' => [
        'user' => '3.0.0',
        'auth' => '2.0.0',
    ],
];
