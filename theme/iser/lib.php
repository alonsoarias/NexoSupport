<?php
/**
 * NexoSupport - ISER Theme Library
 *
 * @package    theme_iser
 * @copyright  2024 ISER
 * @license    Proprietary
 */

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Get theme capabilities
 *
 * @return array Capabilities
 */
function theme_iser_get_capabilities(): array
{
    return [
        'theme/iser:view' => [
            'name' => 'Use ISER theme',
            'description' => 'Allow users to use the ISER branded theme',
            'module' => 'theme_iser',
        ],
        'theme/iser:edit' => [
            'name' => 'Edit ISER theme settings',
            'description' => 'Configure and customize ISER theme settings',
            'module' => 'theme_iser',
        ],
        'theme/iser:customize' => [
            'name' => 'Customize ISER theme',
            'description' => 'Advanced customization including branding, logos, and custom CSS',
            'module' => 'theme_iser',
        ],
    ];
}

/**
 * Get theme title
 *
 * @return string Theme title
 */
function theme_iser_get_title(): string
{
    return __('ISER Theme');
}

/**
 * Get theme description
 *
 * @return string Theme description
 */
function theme_iser_get_description(): string
{
    return __('Official ISER branded theme with advanced customization options');
}

/**
 * Get theme configuration options
 *
 * @return array Configuration options
 */
function theme_iser_get_config_options(): array
{
    return [
        'primary_color' => [
            'name' => 'Primary color',
            'description' => 'Main ISER brand color',
            'type' => 'color',
            'default' => '#1e3a8a', // ISER Blue
        ],
        'secondary_color' => [
            'name' => 'Secondary color',
            'description' => 'Secondary brand color',
            'type' => 'color',
            'default' => '#059669', // ISER Green
        ],
        'accent_color' => [
            'name' => 'Accent color',
            'description' => 'Accent color for highlights',
            'type' => 'color',
            'default' => '#dc2626',
        ],
        'logo' => [
            'name' => 'Logo',
            'description' => 'Upload custom logo',
            'type' => 'file',
            'accept' => 'image/png,image/jpeg,image/svg+xml',
            'default' => '',
        ],
        'logo_height' => [
            'name' => 'Logo height',
            'description' => 'Logo height in pixels',
            'type' => 'int',
            'default' => 50,
            'min' => 20,
            'max' => 200,
        ],
        'favicon' => [
            'name' => 'Favicon',
            'description' => 'Upload custom favicon',
            'type' => 'file',
            'accept' => 'image/x-icon,image/png',
            'default' => '',
        ],
        'font_family' => [
            'name' => 'Font family',
            'description' => 'Base font family for the theme',
            'type' => 'select',
            'options' => [
                'inter' => 'Inter',
                'roboto' => 'Roboto',
                'open-sans' => 'Open Sans',
                'lato' => 'Lato',
                'system' => 'System Default',
            ],
            'default' => 'inter',
        ],
        'enable_dark_mode' => [
            'name' => 'Enable dark mode',
            'description' => 'Allow users to switch to dark mode',
            'type' => 'bool',
            'default' => true,
        ],
        'custom_css' => [
            'name' => 'Custom CSS',
            'description' => 'Add custom CSS for advanced styling',
            'type' => 'textarea',
            'default' => '',
        ],
        'custom_header_html' => [
            'name' => 'Custom header HTML',
            'description' => 'Additional HTML to inject in header',
            'type' => 'textarea',
            'default' => '',
        ],
        'custom_footer_html' => [
            'name' => 'Custom footer HTML',
            'description' => 'Additional HTML to inject in footer',
            'type' => 'textarea',
            'default' => '',
        ],
        'show_breadcrumbs' => [
            'name' => 'Show breadcrumbs',
            'description' => 'Display breadcrumb navigation',
            'type' => 'bool',
            'default' => true,
        ],
        'compact_navigation' => [
            'name' => 'Compact navigation',
            'description' => 'Use compact navigation menu',
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
function theme_iser_get_features(): array
{
    return [
        'responsive' => 'Responsive design for all devices',
        'accessible' => 'WCAG 2.1 Level AA compliant',
        'branded' => 'ISER official branding and colors',
        'customizable' => 'Extensive customization options',
        'dark_mode' => 'Built-in dark mode support',
        'custom_css' => 'Support for custom CSS injection',
        'logo_upload' => 'Custom logo and favicon support',
    ];
}

/**
 * Get theme regions
 *
 * @return array Theme regions
 */
function theme_iser_get_regions(): array
{
    return [
        'header' => 'Header',
        'navigation' => 'Navigation',
        'sidebar_left' => 'Left Sidebar',
        'sidebar_right' => 'Right Sidebar',
        'content' => 'Main Content',
        'footer' => 'Footer',
        'footer_secondary' => 'Secondary Footer',
    ];
}

/**
 * Get supported page layouts
 *
 * @return array Page layouts
 */
function theme_iser_get_layouts(): array
{
    return [
        'base' => [
            'name' => 'Base layout',
            'description' => 'Minimal layout with header and footer',
            'regions' => ['header', 'content', 'footer'],
        ],
        'standard' => [
            'name' => 'Standard layout',
            'description' => 'Standard layout with left sidebar',
            'regions' => ['header', 'navigation', 'sidebar_left', 'content', 'footer'],
        ],
        'fullwidth' => [
            'name' => 'Full width layout',
            'description' => 'Full width layout without sidebars',
            'regions' => ['header', 'navigation', 'content', 'footer'],
        ],
        'two_column' => [
            'name' => 'Two column layout',
            'description' => 'Layout with left and right sidebars',
            'regions' => ['header', 'navigation', 'sidebar_left', 'content', 'sidebar_right', 'footer'],
        ],
        'landing' => [
            'name' => 'Landing page layout',
            'description' => 'Clean layout for landing pages',
            'regions' => ['header', 'content', 'footer', 'footer_secondary'],
        ],
    ];
}

/**
 * Get color schemes
 *
 * @return array Available color schemes
 */
function theme_iser_get_color_schemes(): array
{
    return [
        'default' => [
            'name' => 'ISER Default',
            'primary' => '#1e3a8a',
            'secondary' => '#059669',
            'accent' => '#dc2626',
        ],
        'ocean' => [
            'name' => 'Ocean Blue',
            'primary' => '#0284c7',
            'secondary' => '#0891b2',
            'accent' => '#06b6d4',
        ],
        'forest' => [
            'name' => 'Forest Green',
            'primary' => '#047857',
            'secondary' => '#059669',
            'accent' => '#10b981',
        ],
        'sunset' => [
            'name' => 'Sunset Orange',
            'primary' => '#ea580c',
            'secondary' => '#f97316',
            'accent' => '#fb923c',
        ],
    ];
}

/**
 * Validate custom CSS
 *
 * @param string $css Custom CSS to validate
 * @return array Validation errors (empty if valid)
 */
function theme_iser_validate_custom_css(string $css): array
{
    $errors = [];

    // Check for potentially dangerous CSS
    $dangerous_patterns = [
        'javascript:',
        'expression(',
        'behavior:',
        '@import',
    ];

    foreach ($dangerous_patterns as $pattern) {
        if (stripos($css, $pattern) !== false) {
            $errors[] = "Potentially dangerous CSS detected: {$pattern}";
        }
    }

    // Check CSS length
    if (strlen($css) > 50000) {
        $errors[] = 'Custom CSS exceeds maximum length of 50,000 characters';
    }

    return $errors;
}

/**
 * Sanitize custom HTML
 *
 * @param string $html HTML to sanitize
 * @return string Sanitized HTML
 */
function theme_iser_sanitize_html(string $html): string
{
    // Remove potentially dangerous tags
    $html = strip_tags($html, '<div><span><p><a><b><i><strong><em><ul><ol><li><br>');

    // Remove javascript: and other dangerous protocols
    $html = preg_replace('/javascript:/i', '', $html);
    $html = preg_replace('/on\w+\s*=\s*["\'][^"\']*["\']/i', '', $html);

    return $html;
}
