<?php
/**
 * NexoSupport - Install Addon Tool Library
 *
 * @package    tool_installaddon
 * @copyright  2024 ISER
 * @license    Proprietary
 */

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Get tool capabilities
 *
 * @return array Capabilities
 */
function tool_installaddon_get_capabilities(): array
{
    return [
        'tool/installaddon:install' => [
            'name' => 'Install addons',
            'description' => 'Install plugins from ZIP files',
            'module' => 'tool_installaddon',
        ],
        'tool/installaddon:validate' => [
            'name' => 'Validate addons',
            'description' => 'Validate plugin packages before installation',
            'module' => 'tool_installaddon',
        ],
    ];
}

/**
 * Get tool title
 *
 * @return string Tool title
 */
function tool_installaddon_get_title(): string
{
    return __('Install Plugin');
}

/**
 * Get tool description
 *
 * @return string Tool description
 */
function tool_installaddon_get_description(): string
{
    return __('Install plugins and addons from ZIP files');
}

/**
 * Get menu items for this tool
 *
 * @return array Menu items
 */
function tool_installaddon_get_menu_items(): array
{
    $items = [];

    if (has_capability('tool/installaddon:install')) {
        $items[] = [
            'title' => 'Install Plugin',
            'url' => '/admin/tool/installaddon',
            'icon' => 'download',
            'active' => strpos($_SERVER['REQUEST_URI'] ?? '', '/admin/tool/installaddon') === 0,
        ];
    }

    return $items;
}
