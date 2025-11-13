# THEME SYSTEM SPECIFICATION - NexoSupport

**Project:** NexoSupport Authentication System
**Document Type:** Technical Specification
**Version:** 1.0
**Status:** 📋 Specification (20% Implemented)
**Date:** 2025-11-13

---

## EXECUTIVE SUMMARY

### Current Status: 20% Complete

**What Exists:**
- ✅ Theme/Iser/ - ISER corporate theme (default theme)
- ✅ ThemeConfigurator class exists
- ✅ Basic theme configuration files (color_palette.php, layout_config.php, etc.)
- ✅ Template system with Mustache
- ✅ Multiple layouts (base, admin, dashboard, login, popup, fullwidth)
- ✅ Extensive component library
- ✅ Theme assets management

**What's Missing:**
- ❌ Configurable core theme from admin panel
- ❌ Dynamic color customization
- ❌ Typography customization UI
- ❌ Layout switcher
- ❌ Logo upload functionality
- ❌ Dark mode toggle
- ❌ Theme plugin override mechanism
- ❌ Theme export/import
- ❌ Live preview of changes

**Goal:** Transform the existing theme into a fully configurable system with admin UI

---

## 1. DESIGN PHILOSOPHY

### 1.1 Core Principles

**1. Core Theme is Sacred**
- The core theme (Theme/Iser/) **MUST always be available**
- Cannot be deleted or disabled
- Acts as **fallback** if theme plugin fails
- Highly configurable but always present

**2. Configuration over Code**
- Theme appearance controlled via **admin panel**, not code
- Changes persist in **database** (config table)
- No need to edit PHP/CSS files for common changes

**3. Plugin-Based Extension**
- Theme plugins can **completely override** the UI
- Only **ONE theme plugin active** at a time
- Theme plugins use same configuration API
- Plugins **inherit** core theme settings unless overridden

**4. User Experience First**
- **Live preview** of theme changes before saving
- **Responsive** on all devices
- **Accessible** (WCAG 2.1 AA)
- **Fast** (minimal CSS/JS, optimized delivery)

---

## 2. SYSTEM ARCHITECTURE

### 2.1 Components Overview

```
┌─────────────────────────────────────────────────┐
│         Admin Theme Configuration UI            │
│   (Color picker, typography, layout options)   │
└────────────────────┬────────────────────────────┘
                     │
                     ↓
┌─────────────────────────────────────────────────┐
│           ThemeConfigurator                     │
│  - Save/load settings from database             │
│  - Generate dynamic CSS variables               │
│  - Validate configuration                       │
└────────────────────┬────────────────────────────┘
                     │
         ┌───────────┴───────────┐
         │                       │
         ↓                       ↓
┌──────────────────┐   ┌──────────────────┐
│   Core Theme     │   │  Theme Plugin    │
│   (Theme/Iser/)  │   │  (Active)        │
│   Always present │   │  Optional        │
└────────┬─────────┘   └────────┬─────────┘
         │                       │
         └───────────┬───────────┘
                     ↓
         ┌─────────────────────┐
         │   ThemeRenderer     │
         │   (Mustache)        │
         └─────────────────────┘
                     ↓
              ┌──────────────┐
              │  HTML Output │
              └──────────────┘
```

### 2.2 Core Classes

| Class | Location | Purpose |
|-------|----------|---------|
| `ThemeConfigurator` | `/modules/Theme/ThemeConfigurator.php` | Manage theme settings |
| `ThemeIser` | `/modules/Theme/Iser/ThemeIser.php` | Core theme implementation |
| `ThemeRenderer` | `/modules/Theme/Iser/ThemeRenderer.php` | Render templates |
| `ThemeAssets` | `/modules/Theme/Iser/ThemeAssets.php` | Manage CSS/JS assets |
| `ThemeLayouts` | `/modules/Theme/Iser/ThemeLayouts.php` | Layout management |
| `ThemeNavigation` | `/modules/Theme/Iser/ThemeNavigation.php` | Navigation builder |
| `ThemeSettingsController` | `/modules/Controllers/ThemeSettingsController.php` | Admin UI controller |

---

## 3. CONFIGURABLE THEME SETTINGS

### 3.1 Color Configuration

**Primary Colors:**
- Primary color (brand color)
- Primary hover (darken/lighten)
- Primary text (contrast color)

**Secondary Colors:**
- Secondary color
- Secondary hover
- Secondary text

**State Colors:**
- Success (green)
- Warning (yellow/orange)
- Danger (red)
- Info (blue)

**Neutral Colors:**
- Background (main background)
- Surface (cards, panels)
- Text primary (main text)
- Text secondary (muted text)
- Border color
- Divider color

**Link Colors:**
- Link color
- Link hover
- Link visited

**Implementation:**
```php
// Example configuration array
$colorConfig = [
    'primary' => [
        'base' => '#4F46E5',      // Indigo-600
        'hover' => '#4338CA',     // Indigo-700
        'text' => '#FFFFFF',      // White
    ],
    'secondary' => [
        'base' => '#6B7280',      // Gray-500
        'hover' => '#4B5563',     // Gray-600
        'text' => '#FFFFFF',
    ],
    'success' => '#10B981',       // Green-500
    'warning' => '#F59E0B',       // Amber-500
    'danger' => '#EF4444',        // Red-500
    'info' => '#3B82F6',          // Blue-500
    'background' => '#F9FAFB',    // Gray-50
    'surface' => '#FFFFFF',       // White
    'text_primary' => '#111827',  // Gray-900
    'text_secondary' => '#6B7280',// Gray-500
    'border' => '#E5E7EB',        // Gray-200
    'link' => '#4F46E5',
];
```

**Storage:** `config` table with keys like:
- `theme.color.primary`
- `theme.color.secondary`
- etc.

**CSS Generation:**
```css
:root {
    --color-primary: #4F46E5;
    --color-primary-hover: #4338CA;
    --color-primary-text: #FFFFFF;
    /* ... more variables */
}
```

---

### 3.2 Typography Configuration

**Font Families:**
- Heading font (h1, h2, h3, h4, h5, h6)
- Body font (paragraphs, text)
- Monospace font (code blocks)

**Font Sizes:**
- Base font size (default: 16px)
- Scale ratio (default: 1.25 - Major Third)
- Calculated sizes:
  - xs: base / ratio²
  - sm: base / ratio
  - md: base
  - lg: base * ratio
  - xl: base * ratio²
  - 2xl: base * ratio³
  - 3xl: base * ratio⁴

**Font Weights:**
- Light (300)
- Normal (400)
- Medium (500)
- Semibold (600)
- Bold (700)

**Line Heights:**
- Tight (1.25)
- Normal (1.5)
- Relaxed (1.75)
- Loose (2)

**Letter Spacing:**
- Tight (-0.05em)
- Normal (0)
- Wide (0.05em)

**Implementation:**
```php
$typographyConfig = [
    'fonts' => [
        'heading' => 'Inter, system-ui, -apple-system, sans-serif',
        'body' => 'Inter, system-ui, -apple-system, sans-serif',
        'mono' => 'Fira Code, Consolas, Monaco, monospace',
    ],
    'sizes' => [
        'base' => 16, // px
        'ratio' => 1.25,
    ],
    'weights' => [
        'light' => 300,
        'normal' => 400,
        'medium' => 500,
        'semibold' => 600,
        'bold' => 700,
    ],
    'line_heights' => [
        'tight' => 1.25,
        'normal' => 1.5,
        'relaxed' => 1.75,
    ],
];
```

---

### 3.3 Layout Configuration

**Container Widths:**
- Full width (100%)
- Max width (1280px default)
- Narrow (960px)
- Wide (1536px)

**Sidebar:**
- Position: Left | Right | None
- Width: 240px | 280px | 320px
- Collapsible: Yes | No
- Default state: Expanded | Collapsed

**Header (Topbar):**
- Style: Fixed | Sticky | Static
- Height: 60px | 70px | 80px
- Show breadcrumbs: Yes | No
- Show user menu: Yes | No

**Footer:**
- Show: Yes | No
- Position: Static | Sticky
- Content: Configurable text

**Spacing:**
- Base spacing unit (default: 4px)
- Container padding (default: 16px)
- Section gap (default: 24px)

**Implementation:**
```php
$layoutConfig = [
    'container' => [
        'max_width' => 1280,
        'padding' => 16,
    ],
    'sidebar' => [
        'position' => 'left',      // left, right, none
        'width' => 280,
        'collapsible' => true,
        'default_state' => 'expanded',
    ],
    'header' => [
        'style' => 'sticky',       // fixed, sticky, static
        'height' => 70,
        'show_breadcrumbs' => true,
        'show_user_menu' => true,
    ],
    'footer' => [
        'show' => true,
        'position' => 'static',
    ],
    'spacing' => [
        'base_unit' => 4,
    ],
];
```

---

### 3.4 Branding Configuration

**Logo:**
- Logo image upload (PNG, SVG, JPG)
- Logo width (default: auto)
- Logo height (max: 40px)
- Logo for dark mode (optional)
- Favicon upload (ICO, PNG)

**Identity:**
- System name (displayed in header)
- Slogan/tagline (optional)
- Copyright text (footer)
- Support email (displayed in footer)
- Support URL (optional)

**Implementation:**
```php
$brandingConfig = [
    'logo' => [
        'path' => '/uploads/logo.png',
        'width' => 'auto',
        'height' => 40,
        'dark_mode_path' => '/uploads/logo-dark.png',
    ],
    'favicon' => '/uploads/favicon.ico',
    'identity' => [
        'name' => 'NexoSupport',
        'slogan' => 'Secure Authentication Platform',
        'copyright' => '© 2025 ISER. All rights reserved.',
        'support_email' => 'support@iser.edu',
        'support_url' => 'https://iser.edu/support',
    ],
];
```

---

### 3.5 Dark Mode Configuration

**Mode Options:**
- Disabled (light only)
- Enabled (user can toggle)
- Auto (based on system preference)
- Scheduled (auto switch based on time)

**Dark Mode Colors:**
- Background: #111827 (Gray-900)
- Surface: #1F2937 (Gray-800)
- Text primary: #F9FAFB (Gray-50)
- Text secondary: #D1D5DB (Gray-300)
- Border: #374151 (Gray-700)

**Toggle Location:**
- Header user menu
- Settings page
- Floating button (optional)

**Persistence:**
- User preference stored in `user_preferences` table
- Anonymous users: localStorage

**Implementation:**
```php
$darkModeConfig = [
    'enabled' => true,
    'mode' => 'user_toggle',   // disabled, user_toggle, auto, scheduled
    'schedule' => [
        'start_time' => '19:00', // 7 PM
        'end_time' => '07:00',   // 7 AM
    ],
    'colors' => [
        'background' => '#111827',
        'surface' => '#1F2937',
        'text_primary' => '#F9FAFB',
        'text_secondary' => '#D1D5DB',
        'border' => '#374151',
    ],
];
```

**CSS Implementation:**
```css
/* Light mode (default) */
:root {
    --bg: #F9FAFB;
    --text: #111827;
}

/* Dark mode */
[data-theme="dark"] {
    --bg: #111827;
    --text: #F9FAFB;
}

/* Auto dark mode based on system */
@media (prefers-color-scheme: dark) {
    :root[data-theme="auto"] {
        --bg: #111827;
        --text: #F9FAFB;
    }
}
```

---

### 3.6 Component Styles

**Buttons:**
- Border radius (0px | 4px | 8px | 16px | 9999px/pill)
- Padding (sm: 8px 16px | md: 10px 20px | lg: 12px 24px)
- Font weight (medium | semibold | bold)
- Hover effect (darken | lighten | shadow)

**Cards:**
- Border radius
- Shadow (none | sm | md | lg)
- Padding
- Border (yes | no)

**Forms:**
- Input border radius
- Input padding
- Label position (top | left)
- Focus color
- Error color

**Tables:**
- Striped rows (yes | no)
- Hover effect (yes | no)
- Border style (full | horizontal | none)
- Compact mode (yes | no)

**Implementation:**
```php
$componentConfig = [
    'buttons' => [
        'border_radius' => 8,
        'padding' => 'md',
        'font_weight' => 'semibold',
        'hover_effect' => 'darken',
    ],
    'cards' => [
        'border_radius' => 12,
        'shadow' => 'md',
        'padding' => 24,
        'border' => false,
    ],
    'forms' => [
        'border_radius' => 8,
        'padding' => 12,
        'label_position' => 'top',
    ],
    'tables' => [
        'striped' => true,
        'hover' => true,
        'border_style' => 'horizontal',
        'compact' => false,
    ],
];
```

---

## 4. ADMIN CONFIGURATION UI

### 4.1 Theme Settings Page

**Location:** `/admin/appearance/theme`

**Tabs:**
1. **Colors** - Color configuration
2. **Typography** - Font settings
3. **Layout** - Layout options
4. **Branding** - Logo and identity
5. **Components** - Component styles
6. **Dark Mode** - Dark mode settings
7. **Advanced** - CSS overrides, custom code

**Features:**
- **Live Preview** - See changes in real-time
- **Reset to Defaults** - Restore original theme
- **Export Configuration** - Download theme as JSON
- **Import Configuration** - Upload theme JSON
- **Apply Preset** - Quick theme presets (Professional, Modern, Minimal, etc.)

---

### 4.2 UI Components

#### Color Picker
```html
<!-- Color input with preview -->
<div class="color-picker">
    <label>Primary Color</label>
    <input type="color" value="#4F46E5" />
    <input type="text" value="#4F46E5" pattern="^#[0-9A-Fa-f]{6}$" />
    <div class="color-preview" style="background: #4F46E5;"></div>
</div>
```

#### Font Selector
```html
<select name="heading_font">
    <option value="Inter">Inter (Default)</option>
    <option value="Roboto">Roboto</option>
    <option value="Open Sans">Open Sans</option>
    <option value="Lato">Lato</option>
    <option value="Montserrat">Montserrat</option>
    <option value="Poppins">Poppins</option>
    <!-- Google Fonts integration -->
</select>
```

#### Layout Selector
```html
<div class="layout-selector">
    <label>Sidebar Position</label>
    <div class="layout-options">
        <button type="button" data-value="left">
            <img src="/assets/layout-left.svg" alt="Left Sidebar" />
            <span>Left</span>
        </button>
        <button type="button" data-value="right">
            <img src="/assets/layout-right.svg" alt="Right Sidebar" />
            <span>Right</span>
        </button>
        <button type="button" data-value="none">
            <img src="/assets/layout-none.svg" alt="No Sidebar" />
            <span>None</span>
        </button>
    </div>
</div>
```

#### Live Preview
```html
<div class="theme-preview">
    <iframe src="/admin/appearance/preview" id="preview-frame"></iframe>
    <div class="preview-controls">
        <button class="btn-preview-desktop">Desktop</button>
        <button class="btn-preview-tablet">Tablet</button>
        <button class="btn-preview-mobile">Mobile</button>
    </div>
</div>
```

---

### 4.3 Settings Persistence

**Database Storage:**
- Table: `config`
- Keys: `theme.*`
- Example:
  ```sql
  INSERT INTO config (config_key, config_value, config_type, category)
  VALUES
    ('theme.color.primary', '#4F46E5', 'string', 'theme'),
    ('theme.sidebar.position', 'left', 'string', 'theme'),
    ('theme.dark_mode.enabled', '1', 'bool', 'theme');
  ```

**Caching:**
- Cache theme configuration in memory (Redis if available)
- Cache generated CSS file
- Invalidate cache on settings change

---

## 5. DYNAMIC CSS GENERATION

### 5.1 CSS Variables Approach

**Generate CSS with variables:**
```css
/* File: /public_html/assets/css/theme-custom.css (dynamically generated) */

:root {
    /* Colors */
    --color-primary: #4F46E5;
    --color-primary-hover: #4338CA;
    --color-primary-text: #FFFFFF;
    --color-secondary: #6B7280;
    --color-success: #10B981;
    --color-warning: #F59E0B;
    --color-danger: #EF4444;
    --color-info: #3B82F6;
    --color-background: #F9FAFB;
    --color-surface: #FFFFFF;
    --color-text-primary: #111827;
    --color-text-secondary: #6B7280;
    --color-border: #E5E7EB;

    /* Typography */
    --font-heading: 'Inter', system-ui, sans-serif;
    --font-body: 'Inter', system-ui, sans-serif;
    --font-mono: 'Fira Code', monospace;
    --font-size-base: 16px;
    --font-weight-normal: 400;
    --font-weight-semibold: 600;
    --line-height-normal: 1.5;

    /* Layout */
    --container-max-width: 1280px;
    --sidebar-width: 280px;
    --header-height: 70px;
    --spacing-base: 4px;

    /* Components */
    --border-radius: 8px;
    --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

/* Dark mode */
[data-theme="dark"] {
    --color-background: #111827;
    --color-surface: #1F2937;
    --color-text-primary: #F9FAFB;
    --color-text-secondary: #D1D5DB;
    --color-border: #374151;
}
```

### 5.2 CSS Generation Class

```php
/**
 * File: /modules/Theme/CssGenerator.php
 */
class CssGenerator
{
    private ThemeConfigurator $config;

    public function generate(): string
    {
        $settings = $this->config->getAll();

        $css = ":root {\n";

        // Colors
        foreach ($settings['colors'] as $key => $value) {
            $css .= "    --color-{$key}: {$value};\n";
        }

        // Typography
        $css .= "    --font-heading: {$settings['typography']['fonts']['heading']};\n";
        $css .= "    --font-body: {$settings['typography']['fonts']['body']};\n";
        $css .= "    --font-size-base: {$settings['typography']['sizes']['base']}px;\n";

        // Layout
        $css .= "    --container-max-width: {$settings['layout']['container']['max_width']}px;\n";
        $css .= "    --sidebar-width: {$settings['layout']['sidebar']['width']}px;\n";

        $css .= "}\n\n";

        // Dark mode
        if ($settings['dark_mode']['enabled']) {
            $css .= "[data-theme=\"dark\"] {\n";
            foreach ($settings['dark_mode']['colors'] as $key => $value) {
                $css .= "    --color-{$key}: {$value};\n";
            }
            $css .= "}\n";
        }

        return $css;
    }

    public function saveToFile(): bool
    {
        $css = $this->generate();
        $path = PUBLIC_PATH . '/assets/css/theme-custom.css';
        return file_put_contents($path, $css) !== false;
    }
}
```

---

## 6. THEME PLUGIN SYSTEM

### 6.1 Theme Plugin Structure

```
/modules/plugins/themes/my-theme/
├── Plugin.php              # Main theme class
├── plugin.json             # Manifest
├── theme.json              # Theme configuration
├── assets/
│   ├── css/
│   │   └── theme.css      # Theme-specific CSS
│   ├── js/
│   │   └── theme.js       # Theme-specific JS
│   └── images/
│       └── screenshot.png # Theme preview
├── templates/
│   ├── layouts/
│   ├── partials/
│   ├── pages/
│   └── components/
└── lang/
    ├── es/
    └── en/
```

### 6.2 Theme Plugin Manifest (plugin.json)

```json
{
  "name": "My Awesome Theme",
  "slug": "my-awesome-theme",
  "type": "themes",
  "version": "1.0.0",
  "description": "A beautiful custom theme for NexoSupport",
  "author": "Developer Name",
  "author_url": "https://example.com",
  "screenshot": "assets/images/screenshot.png",
  "requires": {
    "nexosupport": ">=1.0.0",
    "php": ">=8.1"
  },
  "supports": {
    "dark_mode": true,
    "rtl": false,
    "custom_colors": true,
    "custom_typography": true,
    "custom_layout": true
  },
  "tags": ["modern", "clean", "professional"]
}
```

### 6.3 Theme Configuration (theme.json)

```json
{
  "colors": {
    "primary": "#FF6B6B",
    "secondary": "#4ECDC4",
    "success": "#95E1D3",
    "warning": "#FFA07A",
    "danger": "#FF6B6B",
    "info": "#4ECDC4"
  },
  "typography": {
    "fonts": {
      "heading": "'Montserrat', sans-serif",
      "body": "'Open Sans', sans-serif"
    }
  },
  "layout": {
    "sidebar": {
      "position": "left",
      "width": 300
    }
  }
}
```

### 6.4 Theme Plugin Class

```php
/**
 * File: /modules/plugins/themes/my-theme/Plugin.php
 */
namespace ISER\Plugins\Themes\MyAwesomeTheme;

use ISER\Core\Plugin\PluginInterface;
use ISER\Theme\ThemeConfigurator;

class Plugin implements PluginInterface
{
    public function init(): void
    {
        // Register theme
        $this->registerTheme();

        // Register assets
        $this->registerAssets();

        // Override templates
        $this->overrideTemplates();
    }

    private function registerTheme(): void
    {
        $config = $this->loadThemeConfig();
        ThemeConfigurator::register('my-awesome-theme', $config);
    }

    private function registerAssets(): void
    {
        // Add theme CSS
        add_action('theme_head', function() {
            echo '<link rel="stylesheet" href="/modules/plugins/themes/my-theme/assets/css/theme.css">';
        });

        // Add theme JS
        add_action('theme_footer', function() {
            echo '<script src="/modules/plugins/themes/my-theme/assets/js/theme.js"></script>';
        });
    }

    private function overrideTemplates(): void
    {
        // Override template paths
        $templatePath = __DIR__ . '/templates/';
        ThemeConfigurator::addTemplatePath($templatePath, 100); // High priority
    }

    public function activate(): void
    {
        // Set as active theme
        config_set('theme.active_plugin', 'my-awesome-theme');

        // Clear theme cache
        ThemeConfigurator::clearCache();
    }

    public function deactivate(): void
    {
        // Revert to core theme
        config_set('theme.active_plugin', null);

        // Clear theme cache
        ThemeConfigurator::clearCache();
    }

    public function uninstall(): void
    {
        // Clean up theme settings
        config_delete('theme.active_plugin');
    }
}
```

---

## 7. IMPLEMENTATION PLAN

### 7.1 Phase 1: Core Configuration (Week 1)

**Tasks:**
- [ ] Create `CssGenerator` class
- [ ] Enhance `ThemeConfigurator` class
  - Add getters/setters for all config sections
  - Add validation
  - Add caching
- [ ] Create database migrations for theme settings
- [ ] Test CSS generation

**Deliverables:**
- Dynamic CSS generation working
- Settings saved to database
- Cache invalidation on change

---

### 7.2 Phase 2: Admin UI - Colors & Typography (Week 1)

**Tasks:**
- [ ] Create `ThemeSettingsController`
- [ ] Create color picker UI
- [ ] Create typography selector UI
- [ ] Implement save functionality
- [ ] Add live preview (basic)

**Deliverables:**
- `/admin/appearance/theme` page functional
- Colors tab working
- Typography tab working
- Changes reflected immediately

---

### 7.3 Phase 3: Admin UI - Layout & Branding (Week 2)

**Tasks:**
- [ ] Create layout selector UI
- [ ] Create logo upload functionality
- [ ] Create branding form
- [ ] Implement file uploads (FileManager)
- [ ] Add validation

**Deliverables:**
- Layout tab working
- Branding tab working
- Logo upload functional
- Identity settings saved

---

### 7.4 Phase 4: Dark Mode (Week 2)

**Tasks:**
- [ ] Implement dark mode toggle
- [ ] Create dark mode color scheme
- [ ] Add user preference storage
- [ ] Add system preference detection
- [ ] Test in all pages

**Deliverables:**
- Dark mode fully functional
- Toggle in header
- Persisted preference
- Smooth transition

---

### 7.5 Phase 5: Advanced Features (Week 2)

**Tasks:**
- [ ] Component style customization
- [ ] Theme export/import
- [ ] Theme presets (5-10 presets)
- [ ] Custom CSS override field
- [ ] Reset to defaults

**Deliverables:**
- All tabs complete
- Export/import working
- Presets available
- Custom CSS supported

---

### 7.6 Phase 6: Theme Plugins (Week 3)

**Tasks:**
- [ ] Theme plugin detection
- [ ] Theme plugin activation/deactivation
- [ ] Template override mechanism
- [ ] Asset registration for theme plugins
- [ ] Theme plugin configuration inheritance

**Deliverables:**
- Theme plugins can be installed
- One theme plugin can be active
- Templates can be overridden
- Core theme is always fallback

---

### 7.7 Phase 7: Testing & Documentation (Week 3)

**Tasks:**
- [ ] Test all theme features
- [ ] Test theme plugins
- [ ] Create THEME_DEVELOPMENT_GUIDE.md
- [ ] Document admin UI usage
- [ ] Performance testing
- [ ] Cross-browser testing

**Deliverables:**
- All features tested
- Documentation complete
- Performance optimized
- Bug-free release

---

## 8. SUCCESS CRITERIA

### 8.1 Functional Requirements

**Must Have:**
- ✅ Change colors from admin panel
- ✅ Change typography from admin panel
- ✅ Upload custom logo
- ✅ Toggle dark mode
- ✅ Select layout options (sidebar position, header style)
- ✅ Settings persist in database
- ✅ Changes reflect immediately (after save)
- ✅ Core theme always available (fallback)

**Should Have:**
- ✅ Live preview of changes
- ✅ Theme export/import
- ✅ Theme presets
- ✅ Component style customization
- ✅ Custom CSS override
- ✅ Reset to defaults

**Nice to Have:**
- Theme marketplace
- Theme version control
- Theme A/B testing
- Theme scheduling (different themes at different times)

---

### 8.2 Performance Requirements

- **CSS file size:** < 50 KB (compressed)
- **Load time:** < 100ms for CSS generation
- **Cache:** 24 hour cache for CSS file
- **Optimization:** Minified and compressed CSS in production

---

### 8.3 Compatibility Requirements

- **Browsers:** Chrome, Firefox, Safari, Edge (latest 2 versions)
- **Devices:** Desktop, tablet, mobile (responsive)
- **Screen sizes:** 320px to 4K
- **Accessibility:** WCAG 2.1 AA compliant

---

## 9. TECHNICAL CONSIDERATIONS

### 9.1 Security

- **File uploads:** Validate image types (PNG, JPG, SVG, ICO)
- **File size:** Max 2 MB for logos, 100 KB for favicons
- **CSS injection:** Sanitize custom CSS input
- **XSS prevention:** Escape all user inputs
- **Path traversal:** Validate file paths

### 9.2 Performance

- **CSS caching:** Cache generated CSS for 24 hours
- **Lazy loading:** Load theme assets only when needed
- **Minification:** Minify CSS in production
- **CDN:** Serve assets from CDN if available

### 9.3 Scalability

- **Multi-tenant:** Support different themes per tenant (future)
- **A/B testing:** Support multiple themes for testing (future)
- **Theme inheritance:** Theme plugins inherit core theme (current)

---

## 10. MIGRATION STRATEGY

### 10.1 From Current to Configurable

**Current State:**
- Theme hardcoded in `/modules/Theme/Iser/`
- Colors hardcoded in `color_palette.php`
- Layout hardcoded in `layout_config.php`

**Migration Steps:**
1. Extract current theme settings to database (one-time migration)
2. Generate CSS from database settings
3. Replace hardcoded values with CSS variables
4. Test all pages

**Rollback:**
- Keep old config files as fallback
- Add feature flag: `THEME_CONFIGURABLE` (default: true)
- If disabled, use old hardcoded theme

---

## 11. DOCUMENTATION REQUIREMENTS

### 11.1 User Documentation

- **Admin Manual:** How to customize theme from admin panel
- **User Guide:** How to toggle dark mode

### 11.2 Developer Documentation

- **Theme Development Guide:** How to create a theme plugin
- **API Reference:** ThemeConfigurator API
- **Hooks Reference:** Available theme hooks

---

## 12. FUTURE ENHANCEMENTS

### 12.1 V1.1

- **Theme marketplace:** Browse and install themes
- **Theme updates:** Update installed theme plugins
- **Theme ratings:** Rate and review themes

### 12.2 V1.2

- **Advanced customization:** CSS/SASS editor
- **Theme builder:** Visual theme builder
- **Theme scheduling:** Different themes at different times

### 12.3 V2.0

- **Multi-tenant:** Different themes per tenant
- **A/B testing:** Test multiple themes
- **Theme analytics:** Track theme performance

---

## 13. CONCLUSION

The Theme System will transform NexoSupport's rigid theme into a **fully configurable, plugin-extensible** theming system that rivals major CMS platforms.

**Key Benefits:**
- ✅ **No code changes** needed for visual customization
- ✅ **Extensible** via theme plugins
- ✅ **User-friendly** admin interface
- ✅ **Production-ready** with core theme fallback
- ✅ **Future-proof** with plugin architecture

**Timeline:** 3 weeks (120 hours)
**Priority:** HIGH (required for V1.0)
**Dependencies:** None (can start immediately)

---

**Document Version:** 1.0
**Created:** 2025-11-13
**Status:** 📋 **Ready for implementation**

---

## APPENDIX A: Configuration Schema

**Complete configuration structure:**
```json
{
  "colors": {
    "primary": { "base": "#4F46E5", "hover": "#4338CA", "text": "#FFFFFF" },
    "secondary": { "base": "#6B7280", "hover": "#4B5563", "text": "#FFFFFF" },
    "success": "#10B981",
    "warning": "#F59E0B",
    "danger": "#EF4444",
    "info": "#3B82F6",
    "background": "#F9FAFB",
    "surface": "#FFFFFF",
    "text_primary": "#111827",
    "text_secondary": "#6B7280",
    "border": "#E5E7EB",
    "link": "#4F46E5"
  },
  "typography": {
    "fonts": {
      "heading": "Inter, system-ui, sans-serif",
      "body": "Inter, system-ui, sans-serif",
      "mono": "Fira Code, Consolas, monospace"
    },
    "sizes": {
      "base": 16,
      "ratio": 1.25
    },
    "weights": {
      "light": 300,
      "normal": 400,
      "medium": 500,
      "semibold": 600,
      "bold": 700
    },
    "line_heights": {
      "tight": 1.25,
      "normal": 1.5,
      "relaxed": 1.75
    }
  },
  "layout": {
    "container": {
      "max_width": 1280,
      "padding": 16
    },
    "sidebar": {
      "position": "left",
      "width": 280,
      "collapsible": true,
      "default_state": "expanded"
    },
    "header": {
      "style": "sticky",
      "height": 70,
      "show_breadcrumbs": true,
      "show_user_menu": true
    },
    "footer": {
      "show": true,
      "position": "static"
    }
  },
  "branding": {
    "logo": {
      "path": "/uploads/logo.png",
      "width": "auto",
      "height": 40,
      "dark_mode_path": "/uploads/logo-dark.png"
    },
    "favicon": "/uploads/favicon.ico",
    "identity": {
      "name": "NexoSupport",
      "slogan": "Secure Authentication Platform",
      "copyright": "© 2025 ISER. All rights reserved.",
      "support_email": "support@iser.edu"
    }
  },
  "dark_mode": {
    "enabled": true,
    "mode": "user_toggle",
    "colors": {
      "background": "#111827",
      "surface": "#1F2937",
      "text_primary": "#F9FAFB",
      "text_secondary": "#D1D5DB",
      "border": "#374151"
    }
  },
  "components": {
    "buttons": {
      "border_radius": 8,
      "padding": "md",
      "font_weight": "semibold"
    },
    "cards": {
      "border_radius": 12,
      "shadow": "md",
      "padding": 24
    },
    "forms": {
      "border_radius": 8,
      "padding": 12
    },
    "tables": {
      "striped": true,
      "hover": true
    }
  }
}
```

---

**End of Theme System Specification**
