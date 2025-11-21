<?php
/**
 * Capability definitions for report_performance.
 *
 * @package    report_performance
 * @copyright  2025 NexoSupport
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('INTERNAL_ACCESS') || die();

$capabilities = [
    'report/performance:view' => [
        'riskbitmask' => RISK_CONFIG,
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],
];
