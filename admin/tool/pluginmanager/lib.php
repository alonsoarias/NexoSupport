<?php
/**
 * NexoSupport - Plugin Manager Tool Library
 *
 * @package    tool_pluginmanager
 * @copyright  2024 ISER
 * @license    Proprietary
 */

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Get tool capabilities
 *
 * @return array Capabilities
 */
function tool_pluginmanager_get_capabilities(): array
{
    return [
        'tool/pluginmanager:manage' => [
            'name' => 'Manage plugins',
            'description' => 'View and manage installed plugins',
            'module' => 'tool_pluginmanager',
        ],
        'tool/pluginmanager:install' => [
            'name' => 'Install plugins',
            'description' => 'Install new plugins',
            'module' => 'tool_pluginmanager',
        ],
        'tool/pluginmanager:uninstall' => [
            'name' => 'Uninstall plugins',
            'description' => 'Uninstall plugins',
            'module' => 'tool_pluginmanager',
        ],
    ];
}

/**
 * Get tool title
 *
 * @return string Tool title
 */
function tool_pluginmanager_get_title(): string
{
    return __('Plugin Manager');
}

/**
 * Get tool description
 *
 * @return string Tool description
 */
function tool_pluginmanager_get_description(): string
{
    return __('Manage installed plugins and components');
}

/**
 * Get menu items for this tool
 *
 * @return array Menu items
 */
function tool_pluginmanager_get_menu_items(): array
{
    $items = [];

    if (has_capability('tool/pluginmanager:manage')) {
        $items[] = [
            'title' => 'Plugins',
            'url' => '/admin/tool/pluginmanager',
            'icon' => 'package',
            'active' => strpos($_SERVER['REQUEST_URI'] ?? '', '/admin/tool/pluginmanager') === 0,
        ];
    }

    return $items;
}
