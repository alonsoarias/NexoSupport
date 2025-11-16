<?php
/**
 * NexoSupport - Log Viewer Tool Library
 *
 * @package    tool_logviewer
 * @copyright  2024 ISER
 * @license    Proprietary
 */

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Get tool capabilities
 *
 * @return array Capabilities
 */
function tool_logviewer_get_capabilities(): array
{
    return [
        'tool/logviewer:view' => [
            'name' => 'View logs',
            'description' => 'View system logs and activity',
            'module' => 'tool_logviewer',
        ],
        'tool/logviewer:export' => [
            'name' => 'Export logs',
            'description' => 'Export logs to CSV or other formats',
            'module' => 'tool_logviewer',
        ],
        'tool/logviewer:delete' => [
            'name' => 'Delete logs',
            'description' => 'Delete old logs',
            'module' => 'tool_logviewer',
        ],
    ];
}

/**
 * Get tool title
 *
 * @return string Tool title
 */
function tool_logviewer_get_title(): string
{
    return __('Log Viewer');
}

/**
 * Get tool description
 *
 * @return string Tool description
 */
function tool_logviewer_get_description(): string
{
    return __('View and analyze system logs');
}

/**
 * Get log level badge HTML
 *
 * @param string $level Log level
 * @return string HTML badge
 */
function tool_logviewer_level_badge(string $level): string
{
    $badges = [
        'error' => '<span class="level-error">ERROR</span>',
        'warning' => '<span class="level-warning">WARNING</span>',
        'info' => '<span class="level-info">INFO</span>',
        'debug' => '<span class="level-debug">DEBUG</span>',
    ];

    return $badges[strtolower($level)] ?? '<span class="level-info">INFO</span>';
}

/**
 * Get menu items for this tool
 *
 * @return array Menu items
 */
function tool_logviewer_get_menu_items(): array
{
    $items = [];

    if (has_capability('tool/logviewer:view')) {
        $items[] = [
            'title' => 'View Logs',
            'url' => '/admin/tool/logviewer',
            'icon' => 'file-text',
            'active' => strpos($_SERVER['REQUEST_URI'] ?? '', '/admin/tool/logviewer') === 0,
        ];
    }

    return $items;
}
