<?php
/**
 * MFA plugin capabilities.
 *
 * @package    tool_mfa
 * @copyright  NexoSupport
 * @license    Proprietary - NexoSupport
 */

defined('NEXOSUPPORT_INTERNAL') || die();

$capabilities = [
    // Capability for users to be subject to MFA
    'tool/mfa:mfaaccess' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_USER,
        'archetypes' => [
            'user' => CAP_ALLOW,
        ],
    ],

    // Capability to manage MFA factors for other users (admin)
    'tool/mfa:manageuserfactors' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'write',
        'contextlevel' => CONTEXT_USER,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],
];
