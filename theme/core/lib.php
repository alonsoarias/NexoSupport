<?php
/**
 * NexoSupport - Core Theme Library
 *
 * @package    theme_core
 * @copyright  2024 ISER
 * @license    Proprietary
 */

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Get theme capabilities
 *
 * @return array Capabilities
 */
function theme_core_get_capabilities(): array
{
    return [
        'theme/core:view' => [
            'name' => 'Use core theme',
            'description' => 'Allow users to use the core theme',
            'module' => 'theme_core',
        ],
        'theme/core:edit' => [
            'name' => 'Edit core theme settings',
            'description' => 'Configure and customize core theme settings',
            'module' => 'theme_core',
        ],
    ];
}

/**
 * Get theme title
 *
 * @return string Theme title
 */
function theme_core_get_title(): string
{
    return __('Core Theme');
}

/**
 * Get theme description
 *
 * @return string Theme description
 */
function theme_core_get_description(): string
{
    return __('Default minimalist theme for NexoSupport');
}

/**
 * Get theme configuration options
 *
 * @return array Configuration options
 */
function theme_core_get_config_options(): array
{
    return [
        'primary_color' => [
            'name' => 'Primary color',
            'description' => 'Main theme color',
            'type' => 'color',
            'default' => '#0066cc',
        ],
        'secondary_color' => [
            'name' => 'Secondary color',
            'description' => 'Secondary theme color',
            'type' => 'color',
            'default' => '#6c757d',
        ],
        'font_family' => [
            'name' => 'Font family',
            'description' => 'Base font family for the theme',
            'type' => 'select',
            'options' => [
                'system' => 'System Default',
                'arial' => 'Arial',
                'helvetica' => 'Helvetica',
                'verdana' => 'Verdana',
            ],
            'default' => 'system',
        ],
        'enable_dark_mode' => [
            'name' => 'Enable dark mode',
            'description' => 'Allow users to switch to dark mode',
            'type' => 'bool',
            'default' => false,
        ],
    ];
}

/**
 * Get theme features
 *
 * @return array Features
 */
function theme_core_get_features(): array
{
    return [
        'responsive' => 'Responsive design for all devices',
        'accessible' => 'WCAG 2.1 Level AA compliant',
        'lightweight' => 'Minimal CSS and JS footprint',
        'customizable' => 'Configurable colors and fonts',
    ];
}

/**
 * Get theme regions
 *
 * @return array Theme regions
 */
function theme_core_get_regions(): array
{
    return [
        'header' => 'Header',
        'navigation' => 'Navigation',
        'sidebar' => 'Sidebar',
        'content' => 'Main Content',
        'footer' => 'Footer',
    ];
}

/**
 * Get supported page layouts
 *
 * @return array Page layouts
 */
function theme_core_get_layouts(): array
{
    return [
        'base' => [
            'name' => 'Base layout',
            'description' => 'Minimal layout with header and footer',
            'regions' => ['header', 'content', 'footer'],
        ],
        'standard' => [
            'name' => 'Standard layout',
            'description' => 'Standard layout with sidebar',
            'regions' => ['header', 'navigation', 'sidebar', 'content', 'footer'],
        ],
        'fullwidth' => [
            'name' => 'Full width layout',
            'description' => 'Full width layout without sidebar',
            'regions' => ['header', 'navigation', 'content', 'footer'],
        ],
    ];
}
