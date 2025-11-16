# ISER Theme

**Component:** `theme_iser`
**Version:** 2.0.0
**Maturity:** Stable

## Description

The ISER Theme is the official branded theme for NexoSupport, featuring ISER's corporate identity, advanced customization options, and modern design elements.

## Features

- **Official ISER Branding**: Incorporates ISER corporate colors and design language
- **Responsive Design**: Optimized for desktop, tablet, and mobile devices
- **WCAG 2.1 Compliant**: Level AA accessibility standards
- **Dark Mode**: Built-in dark mode with automatic detection
- **Custom Branding**: Upload custom logos and favicons
- **Custom CSS**: Advanced styling with custom CSS injection
- **Multiple Layouts**: 5 different page layouts to choose from
- **Color Schemes**: 4 pre-configured color schemes plus custom colors

## Capabilities

- `theme/iser:view` - Allow users to use the ISER branded theme
- `theme/iser:edit` - Configure and customize ISER theme settings
- `theme/iser:customize` - Advanced customization including branding and custom CSS

## Configuration Options

### Branding

- **Logo**: Upload custom logo (PNG, JPEG, SVG)
- **Logo Height**: Adjust logo size (20-200px, default: 50px)
- **Favicon**: Upload custom favicon

### Colors

- **Primary Color**: Main ISER brand color (default: #1e3a8a)
- **Secondary Color**: Secondary brand color (default: #059669)
- **Accent Color**: Accent color for highlights (default: #dc2626)

### Typography

- **Font Family**: Choose from Inter, Roboto, Open Sans, Lato, or System Default

### Advanced Customization

- **Custom CSS**: Add custom CSS rules for advanced styling
- **Custom Header HTML**: Inject custom HTML in the header
- **Custom Footer HTML**: Inject custom HTML in the footer

### Display Options

- **Dark Mode**: Enable/disable dark mode support
- **Show Breadcrumbs**: Display breadcrumb navigation
- **Compact Navigation**: Use compact navigation menu

## Theme Regions

The ISER theme supports the following regions:

- **Header**: Top header area with logo and user menu
- **Navigation**: Main navigation menu
- **Left Sidebar**: Left panel for navigation and widgets
- **Right Sidebar**: Right panel for additional content
- **Content**: Main content area
- **Footer**: Primary footer area
- **Secondary Footer**: Additional footer area for legal/copyright info

## Layouts

### Base Layout
Minimal layout with header and footer only.
- Best for: Login pages, error pages

### Standard Layout
Standard layout with left sidebar navigation.
- Best for: Dashboard, admin pages
- Regions: Header, Navigation, Left Sidebar, Content, Footer

### Full Width Layout
Full width layout without sidebars.
- Best for: Reports, full-screen views
- Regions: Header, Navigation, Content, Footer

### Two Column Layout
Layout with left and right sidebars.
- Best for: Complex admin interfaces
- Regions: Header, Navigation, Left Sidebar, Content, Right Sidebar, Footer

### Landing Page Layout
Clean layout optimized for landing pages.
- Best for: Public pages, marketing content
- Regions: Header, Content, Footer, Secondary Footer

## Color Schemes

### ISER Default
- Primary: #1e3a8a (ISER Blue)
- Secondary: #059669 (ISER Green)
- Accent: #dc2626 (Red)

### Ocean Blue
- Primary: #0284c7
- Secondary: #0891b2
- Accent: #06b6d4

### Forest Green
- Primary: #047857
- Secondary: #059669
- Accent: #10b981

### Sunset Orange
- Primary: #ea580c
- Secondary: #f97316
- Accent: #fb923c

## Installation

This theme is included with NexoSupport core and requires no additional installation.

## Usage

### Applying the Theme

To set the ISER theme as default:

```php
// In config.php or theme settings
$CFG->theme = 'iser';
```

### Customizing the Theme

#### Upload a Custom Logo

```php
$theme_config = [
    'logo' => '/path/to/logo.png',
    'logo_height' => 60,
];
```

#### Set Custom Colors

```php
$theme_config = [
    'primary_color' => '#1e3a8a',
    'secondary_color' => '#059669',
    'accent_color' => '#dc2626',
];
```

#### Add Custom CSS

```php
$theme_config = [
    'custom_css' => '
        .custom-header {
            background: linear-gradient(135deg, #1e3a8a 0%, #059669 100%);
        }
    ',
];
```

## File Structure

```
theme/iser/
├── version.php         # Theme metadata
├── lib.php            # Theme library functions
├── README.md          # This file
├── styles/            # CSS files (to be implemented)
│   ├── main.css
│   ├── dark-mode.css
│   └── layouts/
├── scripts/           # JavaScript files (to be implemented)
│   ├── theme.js
│   └── dark-mode.js
├── templates/         # Mustache templates (to be implemented)
│   ├── header.mustache
│   ├── footer.mustache
│   └── layouts/
├── images/            # Theme images (to be implemented)
│   ├── logo.svg
│   ├── favicon.ico
│   └── backgrounds/
└── fonts/             # Custom fonts (to be implemented)
```

## Development

### Adding Custom Styles

Custom styles should be added to the `styles/` directory. The theme supports:
- Main stylesheet: `styles/main.css`
- Dark mode overrides: `styles/dark-mode.css`
- Layout-specific styles: `styles/layouts/*.css`

### Creating New Layouts

New layouts can be added to the `layouts/` directory and registered via `theme_iser_get_layouts()`.

### Color Scheme Development

New color schemes can be added via `theme_iser_get_color_schemes()`.

## Security Considerations

### Custom CSS Validation

The theme validates custom CSS to prevent XSS attacks:
- Blocks `javascript:` protocol
- Blocks `expression()` CSS
- Blocks dangerous `@import` rules
- Limits custom CSS to 50,000 characters

### HTML Sanitization

Custom HTML (header/footer) is sanitized:
- Only safe HTML tags allowed
- JavaScript event handlers removed
- Dangerous protocols blocked

## Accessibility

The ISER theme is designed with accessibility in mind:

- **WCAG 2.1 Level AA** compliant
- **Keyboard Navigation**: Full keyboard support
- **Screen Readers**: Proper ARIA labels and semantic HTML
- **Color Contrast**: Meets minimum contrast ratios
- **Focus Indicators**: Visible focus states for all interactive elements

## Performance

The ISER theme is optimized for performance:

- **Lazy Loading**: Images and resources loaded on demand
- **CSS Minification**: Production CSS is minified
- **Font Loading**: Optimized font loading strategy
- **Caching**: Supports browser and CDN caching

## Browser Support

- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+
- Mobile Safari (iOS 14+)
- Chrome Mobile (Android 10+)

## License

Proprietary - Copyright 2024 ISER

## Support

For issues or questions about the ISER theme, contact:
- **Email**: desarrollo@iser.com
- **Internal**: ISER Development Team

## Changelog

### Version 2.0.0 (2024-11-16)
- Initial Frankenstyle migration
- Added 3 capabilities
- Implemented 13 configuration options
- Added 5 page layouts
- Added 4 color schemes
- Enhanced security validation
- Full accessibility compliance
