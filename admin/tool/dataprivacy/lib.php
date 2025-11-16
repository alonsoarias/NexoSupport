<?php
/**
 * NexoSupport - Data Privacy Tool Library
 *
 * @package    tool_dataprivacy
 * @copyright  2024 ISER
 * @license    Proprietary
 */

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Get tool capabilities
 *
 * @return array Capabilities
 */
function tool_dataprivacy_get_capabilities(): array
{
    return [
        'tool/dataprivacy:manage' => [
            'name' => 'Manage data privacy',
            'description' => 'Configure data privacy and GDPR settings',
            'module' => 'tool_dataprivacy',
        ],
        'tool/dataprivacy:export' => [
            'name' => 'Export user data',
            'description' => 'Export user data for GDPR compliance',
            'module' => 'tool_dataprivacy',
        ],
        'tool/dataprivacy:delete' => [
            'name' => 'Delete user data',
            'description' => 'Permanently delete user data',
            'module' => 'tool_dataprivacy',
        ],
    ];
}

/**
 * Get tool title
 *
 * @return string Tool title
 */
function tool_dataprivacy_get_title(): string
{
    return __('Data Privacy');
}

/**
 * Get tool description
 *
 * @return string Tool description
 */
function tool_dataprivacy_get_description(): string
{
    return __('Manage data privacy and GDPR compliance');
}

/**
 * Get menu items for this tool
 *
 * @return array Menu items
 */
function tool_dataprivacy_get_menu_items(): array
{
    $items = [];

    if (has_capability('tool/dataprivacy:manage')) {
        $items[] = [
            'title' => 'Data Privacy',
            'url' => '/admin/tool/dataprivacy',
            'icon' => 'lock',
            'active' => strpos($_SERVER['REQUEST_URI'] ?? '', '/admin/tool/dataprivacy') === 0,
        ];
    }

    return $items;
}
