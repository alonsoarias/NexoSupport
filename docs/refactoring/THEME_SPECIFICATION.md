# NEXOSUPPORT - THEME SYSTEM SPECIFICATION

**Document Type:** Technical Specification
**Phase:** 9 - Theme Configurable del Core
**Version:** 1.0.0
**Date:** 2025-11-13
**Status:** 🎯 Ready for Implementation

---

## EXECUTIVE SUMMARY

### Purpose

This document specifies the **Configurable Core Theme System** for NexoSupport, enabling administrators to customize the application's appearance through an intuitive admin interface without modifying code. The system provides color customization, typography settings, logo management, dark mode support, and layout configuration while maintaining a plugin architecture for extensibility.

### Current Status: 20% Complete

**What Exists:**
- ✅ ThemeConfigurator class with database-backed configuration
- ✅ ThemeIser class with basic theme rendering
- ✅ Color palette system with validation
- ✅ Font configuration with allowed fonts list
- ✅ User theme preferences (dark mode flag, sidebar state)
- ✅ Multiple layout definitions (6 layouts)
- ✅ ThemePreviewController and AppearanceController

**What's Missing:**
- ❌ Admin UI for theme configuration
- ❌ Real-time color customization interface
- ❌ Logo and favicon upload functionality
- ❌ Functional dark mode implementation
- ❌ Layout switcher UI
- ❌ Theme export/import functionality
- ❌ Enhanced theme plugin architecture
- ❌ Real-time preview system
- ❌ CSS variable injection system

### Goals

1. **Administrator Empowerment**: Enable admins to customize branding without developer intervention
2. **Consistency**: Maintain visual consistency across the entire application
3. **Performance**: Implement efficient caching and CSS generation
4. **Extensibility**: Support theme plugins that override core theme elements
5. **User Experience**: Provide per-user theme preferences (dark mode, font size)
6. **Accessibility**: Ensure all customizations maintain WCAG 2.1 AA compliance

---

## 1. CURRENT STATE ANALYSIS

### 1.1 Existing Infrastructure

#### ThemeConfigurator Class (`modules/Theme/ThemeConfigurator.php`)

**Capabilities:**
- Stores theme configurations in `config` table (category='theme')
- In-memory caching of configurations
- HEX color validation
- Font validation against allowed fonts list
- Default color palette (8 colors)
- Default fonts (heading, body, mono)
- Reset to defaults functionality

**Limitations:**
- No batch update capability
- No validation for RGB/RGBA colors
- Limited font options (17 fonts)
- No theme versioning
- No backup/restore functionality
- No validation for layout options
- No support for custom CSS variables

#### ThemeIser Class (`modules/Theme/Iser/ThemeIser.php`)

**Capabilities:**
- Loads configuration from files and database
- Manages assets (CSS/JS)
- Manages layouts (6 predefined layouts)
- Manages navigation
- User theme preferences storage
- Logo and favicon URL retrieval
- Global template data injection

**Limitations:**
- Hard-coded theme name ('iser')
- Limited color customization propagation
- No dark mode CSS generation
- No real-time preview support
- User preferences stored in settings table (not normalized)
- No theme inheritance/override system

#### Configuration Files

**`modules/Theme/Iser/config/theme_settings.php`:**
- Complete theme metadata
- 6 layout definitions
- 8 base colors
- Typography settings
- Responsive breakpoints
- Feature flags (dark_mode, accessibility, etc.)

**`modules/Theme/Iser/config/color_palette.php`:**
- Extended color palette with variants (light, dark, contrast)
- Academic-specific colors
- User role colors
- Gradient definitions

### 1.2 Identified Gaps

| Area | Current State | Target State | Priority |
|------|---------------|--------------|----------|
| **Admin UI** | No interface | Full configuration panel | P0 |
| **Color Customization** | Database storage only | Real-time preview + CSS generation | P0 |
| **Logo Upload** | URL storage only | File upload + management | P0 |
| **Dark Mode** | Flag only | Full implementation with CSS | P1 |
| **Layout Selection** | Hard-coded | Selectable from admin | P1 |
| **Theme Export/Import** | None | JSON export/import | P2 |
| **CSS Variables** | None | Dynamic CSS variable injection | P0 |
| **Theme Plugins** | Basic structure | Full plugin override system | P2 |
| **Preview System** | Basic | Real-time side-by-side preview | P2 |

---

## 2. ARCHITECTURE DESIGN

### 2.1 System Components

```
┌─────────────────────────────────────────────────────────────────┐
│                     THEME SYSTEM ARCHITECTURE                     │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  ┌──────────────────┐         ┌──────────────────┐              │
│  │   Admin Panel    │────────▶│ ThemeController  │              │
│  │   (UI Layer)     │         │  (HTTP Layer)    │              │
│  └──────────────────┘         └────────┬─────────┘              │
│                                         │                         │
│                                         ▼                         │
│                              ┌──────────────────────┐            │
│                              │  ThemeConfigurator   │            │
│                              │  (Business Logic)    │            │
│                              └──────────┬───────────┘            │
│                                         │                         │
│            ┌────────────────────────────┼────────────────┐       │
│            ▼                            ▼                ▼       │
│  ┌─────────────────┐         ┌─────────────────┐  ┌─────────┐  │
│  │   ColorManager  │         │   AssetManager  │  │ Plugins │  │
│  │ (Color System)  │         │  (CSS/JS Gen)   │  │ System  │  │
│  └─────────────────┘         └─────────────────┘  └─────────┘  │
│            │                            │                │       │
│            └────────────────────────────┼────────────────┘       │
│                                         ▼                         │
│                              ┌──────────────────────┐            │
│                              │    Database          │            │
│                              │  (config table)      │            │
│                              └──────────────────────┘            │
│                                         │                         │
│                                         ▼                         │
│                              ┌──────────────────────┐            │
│                              │  Cache Layer         │            │
│                              │  (Redis/File)        │            │
│                              └──────────────────────┘            │
└─────────────────────────────────────────────────────────────────┘
```

### 2.2 Data Flow

#### Configuration Update Flow

```
User → Admin UI → POST /admin/appearance/save
           ↓
    ThemeController::save()
           ↓
    Validate inputs
           ↓
    ThemeConfigurator::setMultiple()
           ↓
    Database::update(config table)
           ↓
    AssetManager::regenerateCSS()
           ↓
    Cache::invalidate('theme_css')
           ↓
    Response (success + new CSS URL)
```

#### Theme Rendering Flow

```
Request → Router → Controller
                      ↓
               BaseController::render()
                      ↓
               ThemeIser::renderLayout()
                      ↓
          ┌────────────┴────────────┐
          ▼                         ▼
    Load Config              Load User Preferences
    (from cache)             (from cache)
          │                         │
          └────────────┬────────────┘
                       ▼
            Merge configurations
                       ▼
            Generate template data
                       ▼
            MustacheRenderer::render()
                       ▼
            Inject CSS variables
                       ▼
            HTML Response
```

### 2.3 Database Schema

#### Existing `config` Table
```sql
config
├── id (INT, PK, AUTO_INCREMENT)
├── category (VARCHAR 50) -- 'theme' for theme configs
├── key (VARCHAR 100) -- e.g., 'primary', 'font_heading'
├── value (TEXT) -- Configuration value (can be JSON)
├── created_at (INT)
└── updated_at (INT)

INDEX: idx_config_category_key (category, key)
```

#### New `theme_configs` Table (Normalized - Optional Enhancement)
```sql
theme_configs
├── id (INT, PK, AUTO_INCREMENT)
├── config_group (VARCHAR 50) -- 'colors', 'fonts', 'layout'
├── config_key (VARCHAR 100)
├── config_value (TEXT)
├── value_type (ENUM: 'string', 'json', 'file')
├── is_default (BOOLEAN)
├── version (INT) -- For versioning
├── created_at (INT)
├── updated_at (INT)
└── created_by (INT, FK → users.id)

INDEX: idx_theme_group_key (config_group, config_key)
INDEX: idx_theme_version (version)
```

#### Theme Backups Table (For Export/Import)
```sql
theme_backups
├── id (INT, PK, AUTO_INCREMENT)
├── backup_name (VARCHAR 100)
├── backup_data (LONGTEXT) -- JSON of all theme configs
├── created_by (INT, FK → users.id)
├── created_at (INT)
└── is_system_backup (BOOLEAN) -- Auto backups before changes

INDEX: idx_backup_created (created_at)
```

### 2.4 Configuration Structure

#### Complete Theme Configuration (JSON)
```json
{
  "colors": {
    "primary": "#2c7be5",
    "secondary": "#6e84a3",
    "success": "#00d97e",
    "danger": "#e63757",
    "warning": "#f6c343",
    "info": "#39afd1",
    "light": "#f9fafd",
    "dark": "#0b1727"
  },
  "typography": {
    "font_heading": "Montserrat, sans-serif",
    "font_body": "Open Sans, sans-serif",
    "font_mono": "Courier New, monospace",
    "base_font_size": "16px",
    "line_height": 1.6
  },
  "branding": {
    "logo_url": "/uploads/theme/logo.png",
    "logo_dark_url": "/uploads/theme/logo-dark.png",
    "favicon_url": "/uploads/theme/favicon.ico",
    "site_name": "NexoSupport",
    "site_tagline": "Sistema de Gestión"
  },
  "layout": {
    "default_layout": "dashboard",
    "sidebar_position": "left",
    "sidebar_width": "280px",
    "navbar_position": "top",
    "container_width": "fluid"
  },
  "features": {
    "dark_mode_enabled": true,
    "dark_mode_default": false,
    "user_theme_preferences": true,
    "custom_css_enabled": false
  },
  "advanced": {
    "custom_css": "",
    "custom_js": "",
    "head_injection": ""
  }
}
```

---

## 3. FEATURE SPECIFICATIONS

### 3.1 Color Customization

#### Requirements

**FR-COL-001**: Administrators can customize 8 base colors
- Primary, Secondary, Success, Danger, Warning, Info, Light, Dark
- HEX color input with color picker
- Real-time preview
- Validation: Valid HEX (#RGB or #RRGGBB)

**FR-COL-002**: System generates color variants automatically
- Light variant (+20% lightness)
- Dark variant (-20% lightness)
- Contrast color (auto-calculated for accessibility)

**FR-COL-003**: Color changes propagate to all UI elements
- Buttons, badges, alerts, cards, etc.
- Charts and data visualizations
- Navigation elements
- Form controls

**FR-COL-004**: WCAG 2.1 AA compliance validation
- Automatic contrast ratio calculation
- Warning if contrast ratio < 4.5:1 (normal text)
- Warning if contrast ratio < 3:1 (large text)
- Suggest alternative colors if compliance fails

#### Implementation Approach

**CSS Variables Injection:**
Generate dynamic CSS with variables:

```css
:root {
  --color-primary: #2c7be5;
  --color-primary-light: #5a9df5;
  --color-primary-dark: #1a5fcd;
  --color-primary-contrast: #ffffff;

  /* ... all other colors ... */
}

[data-theme="dark"] {
  --color-primary: #5a9df5;  /* Adjusted for dark mode */
  /* ... */
}
```

**CSS File Generation:**
- Generate `/public/theme/custom-colors.css` on save
- Versioned filename: `custom-colors-{hash}.css` for cache busting
- Load after main theme CSS

### 3.2 Typography Customization

#### Requirements

**FR-TYP-001**: Administrators can select fonts for:
- Headings (H1-H6)
- Body text (paragraphs, lists)
- Monospace (code blocks)

**FR-TYP-002**: Font selection from curated list
- 20+ web-safe and Google Fonts
- Font preview in dropdown
- Load Google Fonts dynamically if selected

**FR-TYP-003**: Font size and line height customization
- Base font size: 14px - 18px (default: 16px)
- Line height: 1.3 - 2.0 (default: 1.6)

**FR-TYP-004**: Typography preset system
- "Compact" preset (smaller fonts, tighter spacing)
- "Comfortable" preset (default)
- "Spacious" preset (larger fonts, more spacing)

#### Allowed Fonts List (Expanded)

**Serif:**
- Georgia, serif
- Times New Roman, serif
- Merriweather, serif
- Playfair Display, serif

**Sans-Serif:**
- Arial, sans-serif
- Helvetica, sans-serif
- Verdana, sans-serif
- Montserrat, sans-serif
- Open Sans, sans-serif
- Roboto, sans-serif
- Lato, sans-serif
- Source Sans Pro, sans-serif
- Raleway, sans-serif
- Poppins, sans-serif
- Ubuntu, sans-serif
- Segoe UI, sans-serif
- Noto Sans, sans-serif
- Inter, sans-serif
- Work Sans, sans-serif

**Monospace:**
- Courier New, monospace
- Monaco, monospace
- Consolas, monospace
- Fira Code, monospace
- Source Code Pro, monospace

### 3.3 Branding (Logo & Favicon)

#### Requirements

**FR-BRA-001**: Logo upload functionality
- Upload logo for light theme
- Upload logo for dark theme (optional, falls back to light)
- Supported formats: PNG, JPG, SVG, WebP
- Maximum file size: 2MB
- Recommended dimensions: 200x60px (width x height)
- Auto-resize if too large

**FR-BRA-002**: Favicon upload functionality
- Upload .ico, .png, or .svg
- Maximum file size: 100KB
- Recommended size: 32x32px or 64x64px
- Auto-generate multiple sizes (16x16, 32x32, 180x180 for Apple)

**FR-BRA-003**: Logo position and sizing options
- Alignment: Left, Center
- Size: Small (120px), Medium (160px), Large (200px)
- Custom height override

**FR-BRA-004**: File management
- Store uploads in `/public/uploads/theme/`
- Generate unique filenames (hash-based)
- Delete old files when replaced
- Preview before applying

### 3.4 Dark Mode

#### Requirements

**FR-DRK-001**: System-wide dark mode support
- Toggle in admin panel (enable/disable feature)
- User preference per account
- Auto-detect system preference (media query)
- Remember user choice in session/cookie

**FR-DRK-002**: Dark mode color palette
- Inverted color scheme
- Adjusted contrast ratios
- Maintain brand colors (adjust brightness)
- Dark backgrounds, light text

**FR-DRK-003**: Smooth theme switching
- No page reload required
- Animate transition (0.3s ease)
- Preserve scroll position
- Update all dynamic elements

**FR-DRK-004**: Dark mode customization
- Customize dark mode colors separately
- Auto-generate dark variants from light colors (default)
- Manual override option

#### Dark Mode Implementation

**CSS Approach:**
```css
/* Light mode (default) */
:root {
  --bg-primary: #ffffff;
  --text-primary: #212529;
  /* ... */
}

/* Dark mode */
[data-theme="dark"] {
  --bg-primary: #0b1727;
  --text-primary: #e9ecef;
  /* ... */
}
```

**JavaScript Toggle:**
```javascript
function toggleDarkMode() {
  const html = document.documentElement;
  const currentTheme = html.getAttribute('data-theme');
  const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

  html.setAttribute('data-theme', newTheme);
  localStorage.setItem('theme', newTheme);

  // Save to server
  fetch('/api/preferences/theme', {
    method: 'POST',
    body: JSON.stringify({ theme: newTheme })
  });
}
```

### 3.5 Layout Customization

#### Requirements

**FR-LAY-001**: Layout selection
- Select default layout for authenticated users
- Options: Dashboard, Admin, Full Width
- Preview each layout

**FR-LAY-002**: Sidebar configuration
- Position: Left, Right
- Width: Narrow (240px), Normal (280px), Wide (320px)
- Collapsible by default: Yes/No
- Show/hide sidebar icons

**FR-LAY-003**: Navbar configuration
- Position: Top, Fixed Top
- Height: Compact (50px), Normal (60px), Large (70px)
- Show/hide search bar
- Show/hide notifications

**FR-LAY-004**: Container width
- Fluid (100% width)
- Boxed (max-width: 1320px)
- Custom max-width

### 3.6 Theme Export/Import

#### Requirements

**FR-EXP-001**: Export theme configuration
- Export as JSON file
- Include all colors, fonts, branding, layout settings
- Exclude uploaded files (logos), include URLs
- Add metadata (export date, version, author)

**FR-EXP-002**: Import theme configuration
- Upload JSON file
- Validate structure before applying
- Preview changes before confirming
- Option to backup current theme first

**FR-EXP-003**: Theme marketplace (Future)
- Browse community themes
- One-click install
- Rating and reviews
- Preview before installation

#### Export Format

```json
{
  "theme_export": {
    "version": "1.0.0",
    "exported_at": "2025-11-13T10:00:00Z",
    "exported_by": "admin@example.com",
    "app_version": "1.0.0"
  },
  "configuration": {
    "colors": { /* ... */ },
    "typography": { /* ... */ },
    "branding": {
      "site_name": "NexoSupport",
      "logo_url": "https://example.com/logo.png",
      "favicon_url": "https://example.com/favicon.ico"
    },
    "layout": { /* ... */ },
    "features": { /* ... */ }
  }
}
```

---

## 4. ADMIN UI DESIGN

### 4.1 Appearance Settings Page

**Route:** `/admin/appearance`
**Permission:** `manage_appearance` or `admin` role

#### Page Structure

```
┌─────────────────────────────────────────────────────────────────┐
│  APPEARANCE SETTINGS                                   [Save]    │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  [Colors] [Typography] [Branding] [Layout] [Advanced]            │
│  ========                                                         │
│                                                                   │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │  Color Palette                                           │   │
│  │                                                           │   │
│  │  Primary Color    [#2c7be5] [🎨]  Preview: ███          │   │
│  │  Secondary Color  [#6e84a3] [🎨]  Preview: ███          │   │
│  │  Success Color    [#00d97e] [🎨]  Preview: ███          │   │
│  │  Danger Color     [#e63757] [🎨]  Preview: ███          │   │
│  │  Warning Color    [#f6c343] [🎨]  Preview: ███          │   │
│  │  Info Color       [#39afd1] [🎨]  Preview: ███          │   │
│  │                                                           │   │
│  │  [Reset to Defaults]                   [Apply Colors]    │   │
│  └──────────────────────────────────────────────────────────┘   │
│                                                                   │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │  Preview                                                  │   │
│  │  ┌────────────────────────────────────────────────────┐  │   │
│  │  │  [Dashboard Preview with current colors]           │  │   │
│  │  │                                                     │  │   │
│  │  │  [Button Primary] [Button Success] [Button Danger] │  │   │
│  │  │                                                     │  │   │
│  │  └────────────────────────────────────────────────────┘  │   │
│  └──────────────────────────────────────────────────────────┘   │
│                                                                   │
│  [< Back to Dashboard]                              [Save All]   │
└─────────────────────────────────────────────────────────────────┘
```

### 4.2 Typography Tab

```
┌─────────────────────────────────────────────────────────────────┐
│  [Colors] [Typography] [Branding] [Layout] [Advanced]            │
│           ============                                            │
│                                                                   │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │  Font Selection                                           │   │
│  │                                                           │   │
│  │  Heading Font       [Montserrat ▼]                       │   │
│  │                     Preview: The quick brown fox...       │   │
│  │                                                           │   │
│  │  Body Font          [Open Sans ▼]                        │   │
│  │                     Preview: The quick brown fox...       │   │
│  │                                                           │   │
│  │  Monospace Font     [Courier New ▼]                      │   │
│  │                     Preview: console.log('hello');        │   │
│  └──────────────────────────────────────────────────────────┘   │
│                                                                   │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │  Typography Settings                                      │   │
│  │                                                           │   │
│  │  Base Font Size     [16px] (14-18px)                     │   │
│  │                     [═══════●══════]                      │   │
│  │                                                           │   │
│  │  Line Height        [1.6] (1.3-2.0)                      │   │
│  │                     [═════●════════]                      │   │
│  │                                                           │   │
│  │  Preset:  (•) Comfortable  ( ) Compact  ( ) Spacious    │   │
│  └──────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────┘
```

### 4.3 Branding Tab

```
┌─────────────────────────────────────────────────────────────────┐
│  [Colors] [Typography] [Branding] [Layout] [Advanced]            │
│                        ==========                                 │
│                                                                   │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │  Logo                                                     │   │
│  │                                                           │   │
│  │  Light Theme Logo                                        │   │
│  │  ┌────────────┐                                          │   │
│  │  │  [LOGO]    │  Current: logo.png (45 KB)              │   │
│  │  │            │                                          │   │
│  │  └────────────┘  [Upload New] [Remove]                  │   │
│  │                                                           │   │
│  │  Dark Theme Logo                                         │   │
│  │  ┌────────────┐                                          │   │
│  │  │  [LOGO]    │  Current: logo-dark.png (48 KB)         │   │
│  │  │            │                                          │   │
│  │  └────────────┘  [Upload New] [Remove]                  │   │
│  │                                                           │   │
│  │  Logo Height    ( ) Small  (•) Medium  ( ) Large        │   │
│  └──────────────────────────────────────────────────────────┘   │
│                                                                   │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │  Favicon                                                  │   │
│  │                                                           │   │
│  │  ┌────┐                                                   │   │
│  │  │ F  │  Current: favicon.ico (12 KB)                    │   │
│  │  └────┘                                                   │   │
│  │         [Upload New] [Remove]                            │   │
│  │                                                           │   │
│  │  Supported: .ico, .png, .svg (max 100KB, 32x32 or 64x64)│   │
│  └──────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────┘
```

### 4.4 Layout Tab

```
┌─────────────────────────────────────────────────────────────────┐
│  [Colors] [Typography] [Branding] [Layout] [Advanced]            │
│                                    ========                       │
│                                                                   │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │  Default Layout                                           │   │
│  │                                                           │   │
│  │  (•) Dashboard    ( ) Admin      ( ) Full Width          │   │
│  │                                                           │   │
│  │  Preview:                                                 │   │
│  │  ┌──────────────────────────────┐                        │   │
│  │  │ [Layout visualization]       │                        │   │
│  │  └──────────────────────────────┘                        │   │
│  └──────────────────────────────────────────────────────────┘   │
│                                                                   │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │  Sidebar Configuration                                    │   │
│  │                                                           │   │
│  │  Position       (•) Left      ( ) Right                  │   │
│  │  Width          ( ) Narrow  (•) Normal  ( ) Wide         │   │
│  │                                                           │   │
│  │  ☑ Collapsible by default                                │   │
│  │  ☑ Show sidebar icons                                    │   │
│  └──────────────────────────────────────────────────────────┘   │
│                                                                   │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │  Navbar Configuration                                     │   │
│  │                                                           │   │
│  │  Position       (•) Top       ( ) Fixed Top              │   │
│  │  Height         ( ) Compact  (•) Normal  ( ) Large       │   │
│  │                                                           │   │
│  │  ☑ Show search bar                                       │   │
│  │  ☑ Show notifications                                    │   │
│  └──────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────┘
```

### 4.5 Advanced Tab

```
┌─────────────────────────────────────────────────────────────────┐
│  [Colors] [Typography] [Branding] [Layout] [Advanced]            │
│                                             ==========            │
│                                                                   │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │  Dark Mode                                                │   │
│  │                                                           │   │
│  │  ☑ Enable dark mode feature                              │   │
│  │  ☐ Use dark mode by default (new users)                  │   │
│  │  ☑ Allow users to toggle dark mode                       │   │
│  │                                                           │   │
│  │  [Preview Dark Mode]                                      │   │
│  └──────────────────────────────────────────────────────────┘   │
│                                                                   │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │  Theme Management                                         │   │
│  │                                                           │   │
│  │  [Export Theme Configuration]                            │   │
│  │  Download current theme as JSON file                     │   │
│  │                                                           │   │
│  │  [Import Theme Configuration]                            │   │
│  │  Upload JSON file to restore theme                       │   │
│  │                                                           │   │
│  │  [Reset to System Defaults]                              │   │
│  │  ⚠️ This will discard all customizations                 │   │
│  └──────────────────────────────────────────────────────────┘   │
│                                                                   │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │  Custom CSS (Advanced Users Only)                        │   │
│  │                                                           │   │
│  │  ☐ Enable custom CSS injection                           │   │
│  │                                                           │   │
│  │  ┌────────────────────────────────────────────────────┐  │   │
│  │  │ /* Custom CSS */                                   │  │   │
│  │  │ .custom-class {                                    │  │   │
│  │  │   /* Your styles here */                           │  │   │
│  │  │ }                                                  │  │   │
│  │  └────────────────────────────────────────────────────┘  │   │
│  │                                                           │   │
│  │  ⚠️ Warning: Custom CSS can break the layout            │   │
│  └──────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────┘
```

---

## 5. TECHNICAL IMPLEMENTATION

### 5.1 Enhanced ThemeConfigurator

**New Methods:**

```php
class ThemeConfigurator
{
    /**
     * Set multiple configuration values at once
     * @param array $configs Key-value pairs
     * @return array Success status for each key
     */
    public function setMultiple(array $configs): array;

    /**
     * Get configuration by group
     * @param string $group 'colors', 'typography', 'branding', 'layout'
     * @return array Configuration group
     */
    public function getGroup(string $group): array;

    /**
     * Validate color contrast ratio for accessibility
     * @param string $foreground Foreground color (HEX)
     * @param string $background Background color (HEX)
     * @return float Contrast ratio
     */
    public function calculateContrastRatio(string $foreground, string $background): float;

    /**
     * Generate color variants (light, dark, contrast)
     * @param string $baseColor HEX color
     * @return array Variants array
     */
    public function generateColorVariants(string $baseColor): array;

    /**
     * Export theme configuration as JSON
     * @return string JSON string
     */
    public function exportConfiguration(): string;

    /**
     * Import theme configuration from JSON
     * @param string $json JSON configuration
     * @param bool $validate Validate before applying
     * @return bool Success status
     */
    public function importConfiguration(string $json, bool $validate = true): bool;

    /**
     * Create backup of current configuration
     * @param string $backupName Backup name
     * @return int Backup ID
     */
    public function createBackup(string $backupName): int;

    /**
     * Restore configuration from backup
     * @param int $backupId Backup ID
     * @return bool Success status
     */
    public function restoreBackup(int $backupId): bool;

    /**
     * Validate RGB color
     * @param string $color RGB/RGBA color
     * @return bool Valid status
     */
    private function validateRGBColor(string $color): bool;
}
```

### 5.2 AssetManager (New Component)

**Purpose:** Generate dynamic CSS files with theme customizations

**Location:** `modules/Theme/AssetManager.php`

```php
namespace ISER\Theme;

use ISER\Core\Database\Database;

class AssetManager
{
    private Database $db;
    private ThemeConfigurator $configurator;
    private string $publicPath;

    /**
     * Generate custom CSS file with theme variables
     * @return string Generated CSS file path
     */
    public function generateCustomCSS(): string;

    /**
     * Generate CSS variables from configuration
     * @param array $config Theme configuration
     * @return string CSS content
     */
    private function generateCSSVariables(array $config): string;

    /**
     * Generate dark mode CSS
     * @param array $config Theme configuration
     * @return string CSS content
     */
    private function generateDarkModeCSS(array $config): string;

    /**
     * Minify CSS content
     * @param string $css CSS content
     * @return string Minified CSS
     */
    private function minifyCSS(string $css): string;

    /**
     * Get CSS file hash for cache busting
     * @param string $content CSS content
     * @return string Hash string
     */
    private function generateCSSHash(string $content): string;

    /**
     * Delete old CSS files
     * @return int Number of files deleted
     */
    public function cleanupOldFiles(): int;
}
```

**Generated CSS Example:**

```css
/* Generated: 2025-11-13 10:00:00 */
/* DO NOT EDIT - Auto-generated by ThemeConfigurator */

:root {
  /* Colors */
  --color-primary: #2c7be5;
  --color-primary-rgb: 44, 123, 229;
  --color-primary-light: #5a9df5;
  --color-primary-dark: #1a5fcd;
  --color-primary-contrast: #ffffff;

  --color-secondary: #6e84a3;
  --color-secondary-rgb: 110, 132, 163;
  --color-secondary-light: #8ba1bc;
  --color-secondary-dark: #566785;
  --color-secondary-contrast: #ffffff;

  /* ... all colors ... */

  /* Typography */
  --font-heading: 'Montserrat', sans-serif;
  --font-body: 'Open Sans', sans-serif;
  --font-mono: 'Courier New', monospace;
  --font-size-base: 16px;
  --line-height-base: 1.6;

  /* Layout */
  --sidebar-width: 280px;
  --navbar-height: 60px;
  --container-max-width: 1320px;
}

/* Dark Mode */
[data-theme="dark"] {
  --color-primary: #5a9df5;
  --color-bg-primary: #0b1727;
  --color-text-primary: #e9ecef;
  /* ... adjusted colors for dark mode ... */
}

/* Apply CSS Variables */
body {
  font-family: var(--font-body);
  font-size: var(--font-size-base);
  line-height: var(--line-height-base);
  color: var(--color-text-primary);
  background-color: var(--color-bg-primary);
}

h1, h2, h3, h4, h5, h6 {
  font-family: var(--font-heading);
}

.btn-primary {
  background-color: var(--color-primary);
  border-color: var(--color-primary);
  color: var(--color-primary-contrast);
}

.btn-primary:hover {
  background-color: var(--color-primary-dark);
  border-color: var(--color-primary-dark);
}

/* ... all other component styles ... */
```

### 5.3 ColorManager (New Component)

**Purpose:** Advanced color manipulation and accessibility validation

**Location:** `modules/Theme/ColorManager.php`

```php
namespace ISER\Theme;

class ColorManager
{
    /**
     * Convert HEX color to RGB
     * @param string $hex HEX color
     * @return array [r, g, b]
     */
    public static function hexToRgb(string $hex): array;

    /**
     * Convert RGB to HEX
     * @param int $r Red (0-255)
     * @param int $g Green (0-255)
     * @param int $b Blue (0-255)
     * @return string HEX color
     */
    public static function rgbToHex(int $r, int $g, int $b): string;

    /**
     * Convert RGB to HSL
     * @param int $r Red (0-255)
     * @param int $g Green (0-255)
     * @param int $b Blue (0-255)
     * @return array [h, s, l]
     */
    public static function rgbToHsl(int $r, int $g, int $b): array;

    /**
     * Convert HSL to RGB
     * @param float $h Hue (0-360)
     * @param float $s Saturation (0-100)
     * @param float $l Lightness (0-100)
     * @return array [r, g, b]
     */
    public static function hslToRgb(float $h, float $s, float $l): array;

    /**
     * Lighten color by percentage
     * @param string $hex HEX color
     * @param int $percent Percentage (0-100)
     * @return string Lightened HEX color
     */
    public static function lighten(string $hex, int $percent): string;

    /**
     * Darken color by percentage
     * @param string $hex HEX color
     * @param int $percent Percentage (0-100)
     * @return string Darkened HEX color
     */
    public static function darken(string $hex, int $percent): string;

    /**
     * Calculate relative luminance (WCAG formula)
     * @param string $hex HEX color
     * @return float Luminance (0-1)
     */
    public static function getLuminance(string $hex): float;

    /**
     * Calculate contrast ratio between two colors
     * @param string $color1 First HEX color
     * @param string $color2 Second HEX color
     * @return float Contrast ratio (1-21)
     */
    public static function getContrastRatio(string $color1, string $color2): float;

    /**
     * Check if contrast ratio meets WCAG AA standards
     * @param string $foreground Foreground HEX color
     * @param string $background Background HEX color
     * @param string $level 'AA' or 'AAA'
     * @param string $size 'normal' or 'large'
     * @return bool Passes standards
     */
    public static function meetsWCAG(
        string $foreground,
        string $background,
        string $level = 'AA',
        string $size = 'normal'
    ): bool;

    /**
     * Get best contrast color (black or white)
     * @param string $hex Background HEX color
     * @return string '#000000' or '#ffffff'
     */
    public static function getContrastColor(string $hex): string;

    /**
     * Generate color variants (light, dark, contrast)
     * @param string $hex Base HEX color
     * @return array Variants
     */
    public static function generateVariants(string $hex): array;
}
```

### 5.4 Theme Plugin Architecture

#### Plugin Structure

```
plugins/
└── themes/
    └── my-theme/
        ├── plugin.json
        ├── theme.php (ThemePlugin class)
        ├── assets/
        │   ├── css/
        │   │   └── custom.css
        │   └── js/
        │       └── custom.js
        ├── templates/
        │   └── layouts/
        │       └── custom-layout.mustache
        ├── config/
        │   ├── colors.php
        │   └── settings.php
        └── lang/
            ├── es/
            └── en/
```

#### plugin.json

```json
{
  "name": "my-theme",
  "type": "theme",
  "version": "1.0.0",
  "author": "Author Name",
  "description": "Custom theme for NexoSupport",
  "extends": "iser",
  "overrides": {
    "colors": true,
    "layouts": ["dashboard", "admin"],
    "templates": ["navbar", "sidebar"]
  },
  "requires": {
    "core": ">=1.0.0"
  }
}
```

#### ThemePlugin Interface

```php
namespace ISER\Core\Theme;

interface ThemePluginInterface
{
    /**
     * Get theme name
     */
    public function getName(): string;

    /**
     * Get theme version
     */
    public function getVersion(): string;

    /**
     * Get theme metadata
     */
    public function getMetadata(): array;

    /**
     * Initialize theme
     */
    public function initialize(): void;

    /**
     * Get custom colors
     */
    public function getColors(): ?array;

    /**
     * Get custom layouts
     */
    public function getLayouts(): ?array;

    /**
     * Get template overrides
     */
    public function getTemplateOverrides(): array;

    /**
     * Get asset URLs
     */
    public function getAssets(): array;
}
```

### 5.5 Theme Loader (Enhanced)

**Purpose:** Load core theme + plugin theme overrides

```php
namespace ISER\Theme;

use ISER\Core\Database\Database;
use ISER\Core\Plugin\PluginManager;
use ISER\Theme\Iser\ThemeIser;

class ThemeLoader
{
    private Database $db;
    private PluginManager $pluginManager;
    private ThemeConfigurator $configurator;
    private ?ThemePluginInterface $activeThemePlugin = null;

    /**
     * Load active theme
     * @return ThemeInterface
     */
    public function loadTheme(): ThemeInterface;

    /**
     * Load theme plugin (if any)
     * @return ThemePluginInterface|null
     */
    private function loadThemePlugin(): ?ThemePluginInterface;

    /**
     * Merge core theme with plugin overrides
     * @param ThemeIser $coreTheme
     * @param ThemePluginInterface $plugin
     * @return void
     */
    private function applyPluginOverrides(ThemeIser $coreTheme, ThemePluginInterface $plugin): void;

    /**
     * Get available themes (core + plugins)
     * @return array Theme list
     */
    public function getAvailableThemes(): array;

    /**
     * Set active theme
     * @param string $themeName
     * @return bool Success status
     */
    public function setActiveTheme(string $themeName): bool;
}
```

---

## 6. SECURITY CONSIDERATIONS

### 6.1 File Upload Security

**Logo/Favicon Upload:**

1. **File Type Validation**
   - Check MIME type using `finfo_file()`
   - Verify file extension
   - Allowed types: image/png, image/jpeg, image/svg+xml, image/x-icon

2. **File Size Validation**
   - Maximum 2MB for logos
   - Maximum 100KB for favicons

3. **File Name Sanitization**
   - Generate unique filenames: `hash('sha256', uniqid()) . '.' . $ext`
   - No user-provided filenames

4. **Storage Location**
   - Store in `/public/uploads/theme/` (publicly accessible)
   - Set proper permissions (644)

5. **SVG Sanitization**
   - Strip JavaScript from SVG files
   - Use library: `enshrined/svg-sanitize`

### 6.2 Custom CSS Injection

**Risks:**
- XSS attacks via malicious CSS
- Breaking layout
- Overriding security-critical styles

**Mitigations:**

1. **Disabled by Default**
   - Custom CSS feature disabled unless explicitly enabled
   - Require `manage_custom_css` permission

2. **CSS Sanitization**
   - Strip `<script>` tags
   - Remove `javascript:` URLs
   - Remove `-moz-binding` and similar unsafe properties
   - Use library: `sabberworm/php-css-parser`

3. **Sandboxing**
   - Inject custom CSS after all core CSS
   - Scope custom CSS to `.custom-theme-styles` container
   - Limit `!important` usage

4. **Validation**
   - Parse CSS before saving
   - Reject invalid CSS
   - Log all custom CSS changes

### 6.3 Configuration Access Control

**Permissions Required:**

- `manage_appearance` - Can edit theme settings
- `manage_custom_css` - Can inject custom CSS
- `admin` - Full access to all theme features

**Audit Logging:**

Log all theme configuration changes:
- User ID
- Timestamp
- Changed settings
- Old values
- New values

### 6.4 SQL Injection Prevention

All database operations use prepared statements:

```php
$this->db->update('config', [
    'value' => $value,
    'updated_at' => time()
], [
    'category' => 'theme',
    'key' => $key
]);
```

### 6.5 CSRF Protection

All POST requests require CSRF token:

```html
<form method="POST" action="/admin/appearance/save">
    <input type="hidden" name="csrf_token" value="{{csrf_token}}">
    <!-- ... -->
</form>
```

---

## 7. PERFORMANCE CONSIDERATIONS

### 7.1 Caching Strategy

#### Configuration Cache

**Level 1: In-Memory (Request-Level)**
- ThemeConfigurator stores config in `$cache` array
- Loaded once per request
- No disk/network I/O after first load

**Level 2: File Cache (Persistent)**
- Cache configuration as PHP file: `/cache/theme_config.php`
- Invalidate on configuration change
- TTL: Indefinite (manual invalidation)

**Level 3: CSS File Cache**
- Generated CSS file: `/public/theme/custom-colors-{hash}.css`
- Browser cache: 1 year (immutable)
- Regenerate on configuration change

#### Cache Invalidation

```php
public function invalidateCache(): void
{
    // Clear in-memory cache
    $this->clearCache();

    // Delete file cache
    @unlink($this->cachePath . '/theme_config.php');

    // Regenerate CSS
    $this->assetManager->generateCustomCSS();

    // Clear CDN cache (if using CDN)
    $this->cdn->purge('/theme/custom-colors-*.css');
}
```

### 7.2 Asset Optimization

#### CSS Generation

- Generate CSS only when configuration changes
- Minify CSS (remove whitespace, comments)
- Combine theme CSS into single file
- Use HTTP/2 for parallel loading

#### Image Optimization

- Auto-resize uploaded logos
- Convert to WebP format (with fallback)
- Generate multiple sizes (responsive images)
- Lazy load logos below the fold

### 7.3 Database Optimization

#### Indexes

```sql
CREATE INDEX idx_config_category_key ON config (category, key);
CREATE INDEX idx_theme_backups_created ON theme_backups (created_at);
```

#### Query Optimization

- Load all theme configs in single query
- Use prepared statements
- Limit result sets

### 7.4 Performance Metrics

**Target Metrics:**
- Theme configuration load: <10ms
- CSS generation: <100ms
- Theme switch (user): <50ms (no page reload)
- Admin panel load: <500ms

---

## 8. IMPLEMENTATION PLAN

### 8.1 Phase 1: Core Enhancements (Week 1)

**Estimated Time:** 15 hours

#### Tasks

1. **Enhance ThemeConfigurator** (4 hours)
   - Add `setMultiple()` method
   - Add `getGroup()` method
   - Add validation for RGB colors
   - Add `exportConfiguration()` method
   - Add `importConfiguration()` method
   - Add backup/restore methods

2. **Create ColorManager** (3 hours)
   - Implement color conversion methods (HEX ↔ RGB ↔ HSL)
   - Implement `lighten()` and `darken()` methods
   - Implement WCAG contrast ratio calculation
   - Implement `generateVariants()` method

3. **Create AssetManager** (4 hours)
   - Implement CSS variable generation
   - Implement dark mode CSS generation
   - Implement CSS minification
   - Implement file cleanup

4. **Database Schema** (2 hours)
   - Create `theme_backups` table migration
   - Add indexes to `config` table
   - Test migrations

5. **Testing** (2 hours)
   - Unit tests for ColorManager
   - Unit tests for ThemeConfigurator enhancements
   - Integration tests

### 8.2 Phase 2: Admin UI - Colors & Typography (Week 2)

**Estimated Time:** 16 hours

#### Tasks

1. **Create AppearanceController Routes** (2 hours)
   - `/admin/appearance` - Index page
   - `/admin/appearance/save` - Save configuration
   - `/admin/appearance/export` - Export theme
   - `/admin/appearance/import` - Import theme
   - `/admin/appearance/reset` - Reset to defaults

2. **Colors Tab UI** (6 hours)
   - Create Mustache template (`admin/appearance/index.mustache`)
   - Color picker integration (JavaScript)
   - Real-time preview
   - Color validation (client-side)
   - WCAG contrast warnings

3. **Typography Tab UI** (4 hours)
   - Font selection dropdown with preview
   - Font size slider
   - Line height slider
   - Typography presets

4. **JavaScript Components** (3 hours)
   - Color picker component (`appearance-colors.js`)
   - Preview component (`appearance-preview.js`)
   - Form validation

5. **Testing** (1 hour)
   - Manual testing of UI
   - Cross-browser testing

### 8.3 Phase 3: Admin UI - Branding & Layout (Week 2)

**Estimated Time:** 14 hours

#### Tasks

1. **Branding Tab UI** (6 hours)
   - Logo upload component
   - Favicon upload component
   - File validation (client + server)
   - Image preview
   - Remove/replace functionality

2. **Layout Tab UI** (4 hours)
   - Layout selection radio buttons
   - Layout preview images
   - Sidebar configuration
   - Navbar configuration
   - Container width options

3. **File Upload Handler** (3 hours)
   - Server-side file upload processing
   - Image resizing (GD/Imagick)
   - SVG sanitization
   - File storage and cleanup

4. **Testing** (1 hour)
   - File upload testing
   - Security testing (malicious files)

### 8.4 Phase 4: Advanced Features (Week 3)

**Estimated Time:** 12 hours

#### Tasks

1. **Dark Mode Implementation** (5 hours)
   - Generate dark mode CSS variables
   - Dark mode toggle button (frontend)
   - Save user preference
   - Auto-detect system preference
   - Smooth transition animation

2. **Advanced Tab UI** (3 hours)
   - Dark mode settings
   - Theme export button
   - Theme import form
   - Reset button with confirmation

3. **Theme Export/Import** (3 hours)
   - Export configuration as JSON
   - Import validation
   - Preview before applying
   - Automatic backup before import

4. **Testing** (1 hour)
   - Dark mode testing
   - Export/import testing

### 8.5 Phase 5: Theme Plugin Architecture (Week 3)

**Estimated Time:** 10 hours

#### Tasks

1. **ThemePluginInterface** (2 hours)
   - Define interface
   - Define plugin structure
   - Document plugin API

2. **ThemeLoader Enhancement** (4 hours)
   - Load theme plugins
   - Apply plugin overrides
   - Merge configurations
   - Template override system

3. **Example Theme Plugin** (2 hours)
   - Create sample plugin
   - Document plugin development

4. **Testing** (2 hours)
   - Plugin loading tests
   - Override tests

### 8.6 Phase 6: Testing & Documentation (Week 4)

**Estimated Time:** 13 hours

#### Tasks

1. **Unit Tests** (4 hours)
   - ThemeConfigurator tests
   - ColorManager tests
   - AssetManager tests

2. **Integration Tests** (3 hours)
   - Theme configuration flow
   - File upload flow
   - Dark mode flow

3. **End-to-End Tests** (2 hours)
   - Complete appearance configuration
   - Theme export/import
   - Plugin loading

4. **Documentation** (4 hours)
   - Update THEME_SPECIFICATION.md (this document)
   - Create THEME_DEVELOPMENT_GUIDE.md
   - Admin user guide for appearance settings
   - API documentation

---

## 9. SUCCESS CRITERIA

### 9.1 Functional Requirements

✅ **Administrators can customize 8 base colors** via admin panel
✅ **Real-time color preview** without page reload
✅ **WCAG AA compliance warnings** for insufficient contrast
✅ **Typography customization** (fonts, sizes, line height)
✅ **Logo and favicon upload** with validation and preview
✅ **Dark mode toggle** with smooth transitions
✅ **Layout selection** (dashboard, admin, full width)
✅ **Sidebar and navbar configuration**
✅ **Theme export** as JSON file
✅ **Theme import** with validation and preview
✅ **Theme reset** to system defaults
✅ **Theme plugins** can override core theme
✅ **User theme preferences** (dark mode, font size)

### 9.2 Non-Functional Requirements

✅ **Performance:**
- Theme configuration load: <10ms
- CSS generation: <100ms
- Theme switch: <50ms
- Admin panel load: <500ms

✅ **Security:**
- File upload validation (type, size, content)
- CSRF protection on all forms
- SQL injection prevention (prepared statements)
- Custom CSS sanitization
- Access control (permissions)
- Audit logging

✅ **Accessibility:**
- WCAG 2.1 AA compliance
- Keyboard navigation
- Screen reader support
- Focus indicators
- Contrast ratio validation

✅ **Browser Compatibility:**
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

✅ **Responsive Design:**
- Mobile-friendly admin panel
- Touch-friendly color picker
- Responsive preview

### 9.3 Code Quality

✅ **PSR-12 Compliance:** All code follows PSR-12 coding standards
✅ **PSR-4 Autoloading:** Proper namespaces and directory structure
✅ **PHPDoc Comments:** All classes and public methods documented
✅ **Type Hints:** Strict types declared, all parameters and returns typed
✅ **No Dead Code:** All code actively used
✅ **DRY Principle:** No significant code duplication
✅ **SOLID Principles:** Classes have single responsibility
✅ **Test Coverage:** ≥70% code coverage

### 9.4 Testing

✅ **Unit Tests:** All managers and utilities tested
✅ **Integration Tests:** Configuration flow tested end-to-end
✅ **Security Tests:** File upload, XSS, SQL injection tested
✅ **Performance Tests:** Load times measured and optimized
✅ **Accessibility Tests:** WCAG compliance verified

---

## 10. MIGRATION PLAN

### 10.1 Database Migration

**Migration File:** `migrations/2025_11_13_create_theme_backups_table.php`

```php
public function up(): void
{
    $this->db->execute("
        CREATE TABLE IF NOT EXISTS theme_backups (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            backup_name VARCHAR(100) NOT NULL,
            backup_data LONGTEXT NOT NULL,
            created_by INT UNSIGNED NOT NULL,
            created_at INT UNSIGNED NOT NULL,
            is_system_backup TINYINT(1) DEFAULT 0,
            INDEX idx_backup_created (created_at),
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
}
```

### 10.2 Existing Data Migration

**Preserve existing theme configurations:**

```php
// No data migration needed - existing config table rows preserved
// color_* and font_* keys in config table will continue to work
```

### 10.3 CSS Migration

**Generate initial custom CSS file:**

```bash
php bin/console theme:generate-css
```

### 10.4 Rollback Plan

1. Drop `theme_backups` table
2. Remove custom CSS files
3. Revert code changes (Git)
4. Clear cache

---

## 11. TESTING STRATEGY

### 11.1 Unit Tests

**ColorManager Tests:**
- Test HEX to RGB conversion
- Test RGB to HSL conversion
- Test color lightening/darkening
- Test contrast ratio calculation
- Test WCAG compliance check

**ThemeConfigurator Tests:**
- Test get/set single configuration
- Test get/set multiple configurations
- Test validation (colors, fonts)
- Test export/import
- Test backup/restore

**AssetManager Tests:**
- Test CSS variable generation
- Test dark mode CSS generation
- Test CSS minification
- Test file cleanup

### 11.2 Integration Tests

**Theme Configuration Flow:**
1. Load appearance settings page
2. Change colors
3. Save configuration
4. Verify database update
5. Verify CSS regeneration
6. Verify cache invalidation

**File Upload Flow:**
1. Upload logo
2. Verify file validation
3. Verify file storage
4. Verify image resizing
5. Verify old file deletion

**Dark Mode Flow:**
1. Enable dark mode
2. Verify CSS generation
3. Toggle dark mode (user)
4. Verify preference saved
5. Verify CSS applied

### 11.3 Security Tests

**File Upload:**
- Upload PHP file (should reject)
- Upload oversized file (should reject)
- Upload SVG with JavaScript (should sanitize)
- Upload image with XSS in metadata (should strip)

**Custom CSS:**
- Inject JavaScript (should sanitize)
- Inject XSS (should sanitize)
- Break layout (should not affect core)

**SQL Injection:**
- Inject SQL in color value (should escape)
- Inject SQL in font value (should escape)

### 11.4 Performance Tests

**Load Testing:**
- Measure theme configuration load time
- Measure CSS generation time
- Measure admin panel load time
- Test under concurrent requests

**Optimization:**
- Verify caching works
- Verify CSS file served from disk
- Verify no N+1 queries

### 11.5 Accessibility Tests

**WCAG Compliance:**
- Run axe-core accessibility scanner
- Test keyboard navigation
- Test screen reader (NVDA/JAWS)
- Verify focus indicators
- Test color contrast

---

## 12. FUTURE ENHANCEMENTS (Post-V1.0)

### 12.1 Theme Marketplace (V1.2)

- Browse community themes
- One-click installation
- Theme ratings and reviews
- Author profiles
- Automatic updates

### 12.2 Visual Theme Builder (V1.3)

- Drag-and-drop layout editor
- Live preview mode
- Component library
- Template editor

### 12.3 Advanced Customization (V1.4)

- CSS Grid layout builder
- Animation customization
- Responsive breakpoint editor
- Component-level customization

### 12.4 A/B Testing (V1.5)

- Test different color schemes
- Measure user engagement
- Statistical analysis
- Auto-select winning variant

---

## 13. DEPENDENCIES

### 13.1 PHP Libraries

**Required:**
- `guzzlehttp/guzzle` (existing) - HTTP client
- `monolog/monolog` (existing) - Logging

**New:**
- `enshrined/svg-sanitize` - SVG sanitization
- `sabberworm/php-css-parser` - CSS parsing and validation
- `intervention/image` - Image manipulation

```bash
composer require enshrined/svg-sanitize
composer require sabberworm/php-css-parser
composer require intervention/image
```

### 13.2 JavaScript Libraries

**Existing:**
- Bootstrap 5 (UI framework)
- FontAwesome (icons)

**New:**
- `@simonwep/pickr` - Modern color picker
- `notyf` - Toast notifications

```html
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@simonwep/pickr/dist/themes/nano.min.css">
<script src="https://cdn.jsdelivr.net/npm/@simonwep/pickr/dist/pickr.min.js"></script>
```

### 13.3 System Requirements

- **PHP:** ≥ 8.1
- **MySQL:** ≥ 5.7 or MariaDB ≥ 10.3
- **Disk Space:** +50MB for uploaded logos and generated CSS
- **GD or Imagick:** For image manipulation

---

## 14. RISKS & MITIGATION

### 14.1 Identified Risks

| Risk | Impact | Probability | Mitigation |
|------|--------|-------------|------------|
| **Breaking existing themes** | HIGH | LOW | Thorough testing, backward compatibility |
| **Performance degradation** | MEDIUM | MEDIUM | Caching, optimization, profiling |
| **Security vulnerabilities (file upload)** | CRITICAL | LOW | Strict validation, sanitization, testing |
| **WCAG non-compliance** | MEDIUM | MEDIUM | Automated checks, manual testing |
| **Browser compatibility issues** | MEDIUM | LOW | Cross-browser testing, polyfills |
| **Complex UI confuses users** | LOW | MEDIUM | User testing, documentation, tooltips |

### 14.2 Contingency Plans

**If CSS generation is too slow:**
- Generate CSS asynchronously (background job)
- Use CDN for caching
- Pre-generate common color combinations

**If file uploads are abused:**
- Rate limiting
- File size quotas per user
- Admin approval for logo changes

**If theme plugins cause conflicts:**
- Sandboxing
- Version compatibility checks
- Disable conflicting plugins automatically

---

## 15. APPENDIX

### 15.1 Color Palette Reference

**Default ISER Colors:**
- Primary: `#2c7be5` (Blue)
- Secondary: `#6e84a3` (Gray-Blue)
- Success: `#00d97e` (Green)
- Danger: `#e63757` (Red)
- Warning: `#f6c343` (Yellow)
- Info: `#39afd1` (Cyan)
- Light: `#f9fafd` (Light Gray)
- Dark: `#0b1727` (Dark Blue)

### 15.2 WCAG Contrast Ratios

**Standards:**
- **AA Normal Text:** 4.5:1
- **AA Large Text:** 3:1
- **AAA Normal Text:** 7:1
- **AAA Large Text:** 4.5:1

**Large Text:** ≥18pt (24px) or ≥14pt (18.66px) bold

### 15.3 Font Loading Strategy

**System Fonts (No Loading):**
- Arial, Helvetica, Verdana, Georgia, Times New Roman, Courier New, Trebuchet MS, Segoe UI

**Google Fonts (Dynamic Loading):**
```html
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Open+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
```

### 15.4 Useful Resources

**Documentation:**
- [WCAG 2.1 Guidelines](https://www.w3.org/WAI/WCAG21/quickref/)
- [CSS Variables MDN](https://developer.mozilla.org/en-US/docs/Web/CSS/Using_CSS_custom_properties)
- [Color Contrast Checker](https://webaim.org/resources/contrastchecker/)

**Tools:**
- [Coolors.co](https://coolors.co/) - Color palette generator
- [Google Fonts](https://fonts.google.com/) - Font library
- [Pickr](https://github.com/Simonwep/pickr) - Color picker

---

## 16. APPROVAL & SIGN-OFF

**Prepared By:** ISER Development Team
**Date:** 2025-11-13
**Version:** 1.0.0
**Status:** 🟢 **READY FOR IMPLEMENTATION**

---

## 17. DOCUMENT HISTORY

| Version | Date | Changes | Author |
|---------|------|---------|--------|
| 1.0.0 | 2025-11-13 | Initial specification created | Development Team |

---

**End of Theme System Specification**

**Next Steps:**
1. Review and approve this specification
2. Begin Phase 1 implementation (Core Enhancements)
3. Create THEME_DEVELOPMENT_GUIDE.md after implementation

---
