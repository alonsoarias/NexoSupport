<?php
/**
 * NexoSupport - Multi-Factor Authentication Tool Library
 *
 * @package    tool_mfa
 * @copyright  2024 ISER
 * @license    Proprietary
 */

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Get tool capabilities
 *
 * @return array Capabilities
 */
function tool_mfa_get_capabilities(): array
{
    return [
        'tool/mfa:manage' => [
            'name' => 'Manage MFA',
            'description' => 'Configure multi-factor authentication settings',
            'module' => 'tool_mfa',
        ],
        'tool/mfa:configure_factors' => [
            'name' => 'Configure factors',
            'description' => 'Enable/disable MFA factors',
            'module' => 'tool_mfa',
        ],
    ];
}

/**
 * Get tool title
 *
 * @return string Tool title
 */
function tool_mfa_get_title(): string
{
    return __('Multi-Factor Authentication');
}

/**
 * Get tool description
 *
 * @return string Tool description
 */
function tool_mfa_get_description(): string
{
    return __('Configure and manage multi-factor authentication settings');
}

/**
 * Get available MFA factors
 *
 * @return array Available factors
 */
function tool_mfa_get_available_factors(): array
{
    return [
        'email' => 'Email Verification',
        'iprange' => 'IP Range Restriction',
    ];
}

/**
 * Get menu items for this tool
 *
 * @return array Menu items
 */
function tool_mfa_get_menu_items(): array
{
    $items = [];

    if (has_capability('tool/mfa:manage')) {
        $items[] = [
            'title' => 'MFA Settings',
            'url' => '/admin/tool/mfa',
            'icon' => 'shield',
            'active' => strpos($_SERVER['REQUEST_URI'] ?? '', '/admin/tool/mfa') === 0,
        ];
    }

    return $items;
}
