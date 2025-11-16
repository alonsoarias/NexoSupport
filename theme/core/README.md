# Core Theme

**Component:** `theme_core`
**Version:** 2.0.0
**Maturity:** Stable

## Description

The Core theme is the default minimalist theme for NexoSupport. It provides a clean, accessible, and responsive interface that works across all devices.

## Features

- **Responsive Design**: Works seamlessly on desktop, tablet, and mobile devices
- **WCAG 2.1 Compliant**: Level AA accessibility standards
- **Lightweight**: Minimal CSS and JavaScript footprint for fast loading
- **Customizable**: Configurable colors, fonts, and layouts

## Capabilities

- `theme/core:view` - Allow users to use the core theme
- `theme/core:edit` - Configure and customize core theme settings

## Configuration Options

### Colors
- **Primary Color**: Main theme color (default: #0066cc)
- **Secondary Color**: Secondary theme color (default: #6c757d)

### Typography
- **Font Family**: Base font family (System Default, Arial, Helvetica, Verdana)

### Features
- **Dark Mode**: Toggle dark mode support (default: disabled)

## Theme Regions

The Core theme supports the following regions:

- **Header**: Top header area
- **Navigation**: Main navigation menu
- **Sidebar**: Side panel for widgets and navigation
- **Content**: Main content area
- **Footer**: Bottom footer area

## Layouts

### Base Layout
Minimal layout with header and footer only.
- Regions: Header, Content, Footer

### Standard Layout
Standard layout with sidebar navigation.
- Regions: Header, Navigation, Sidebar, Content, Footer

### Full Width Layout
Full width layout without sidebar.
- Regions: Header, Navigation, Content, Footer

## Installation

This theme is included with NexoSupport core and requires no additional installation.

## Usage

### Applying the Theme

The Core theme is the default theme. To manually set it:

```php
// In config.php or theme settings
$CFG->theme = 'core';
```

### Customizing Colors

```php
// Set custom theme colors
$theme_config = [
    'primary_color' => '#0066cc',
    'secondary_color' => '#6c757d',
];
```

## File Structure

```
theme/core/
├── version.php         # Theme metadata
├── lib.php            # Theme library functions
├── README.md          # This file
├── styles/            # CSS files (to be implemented)
├── scripts/           # JavaScript files (to be implemented)
├── templates/         # Mustache templates (to be implemented)
└── layouts/           # Page layouts (to be implemented)
```

## Development

### Adding Custom Styles

Custom styles should be added to the `styles/` directory and registered in the theme configuration.

### Creating New Layouts

New layouts can be added to the `layouts/` directory and registered via `theme_core_get_layouts()`.

## License

Proprietary - Copyright 2024 ISER

## Support

For issues or questions about the Core theme, contact ISER Development Team.
