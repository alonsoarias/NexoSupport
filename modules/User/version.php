<?php
/**
 * ISER User Management Module - Version Information
 *
 * @package    ISER\Modules\User
 * @copyright  2024 ISER
 * @license    Proprietary
 */

defined('ISER_BASE_DIR') or die('Direct access not allowed');

$module = [
    'name' => 'User Management',
    'component' => 'user',
    'version' => '3.0.0',
    'requires' => '1.0.0', // Minimum ISER core version
    'maturity' => 'STABLE',
    'release' => '3.0.0',
    'dependencies' => [
        'auth' => '2.0.0',
    ],
];
