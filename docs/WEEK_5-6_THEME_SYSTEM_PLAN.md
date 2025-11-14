# Week 5-6: Theme System Implementation Plan

**Duration:** 40 hours (2 weeks)
**Status:** 📋 Planning → Implementation
**Priority:** HIGH
**Depends On:** Week 4 Plugin System (✅ Complete)

---

## Overview

Implement a comprehensive theme system allowing visual customization of NexoSupport through the admin panel and theme plugins. Transform the current static CSS into a dynamic, configurable theming engine.

---

## Current State Analysis

### What Exists (20%)
- ✅ Basic CSS framework with Bootstrap
- ✅ Theme plugin type defined in plugin system
- ✅ Basic template structure with Mustache
- ✅ Some color variables in CSS
- ⚠️ Hardcoded colors and styles
- ⚠️ No centralized theme management

### What's Missing (80%)
- ❌ ThemeManager class
- ❌ Theme configuration storage
- ❌ Admin UI for theme customization
- ❌ Dark mode support
- ❌ Dynamic color system
- ❌ Typography customization
- ❌ Layout options
- ❌ Logo/branding management
- ❌ Theme plugin override system
- ❌ Developer documentation

---

## Architecture Design

### Core Components

```
Theme System Architecture
├── ThemeManager (core/Theme/ThemeManager.php)
│   ├── Load active theme
│   ├── Apply theme settings
│   ├── Fallback to default theme
│   └── Theme validation
│
├── ThemeConfigurator (core/Theme/ThemeConfigurator.php)
│   ├── Get/Set theme settings
│   ├── Validate theme config
│   ├── Reset to defaults
│   └── Export/Import themes
│
├── ColorSchemeGenerator (core/Theme/ColorSchemeGenerator.php)
│   ├── Generate color variations
│   ├── Calculate contrast ratios
│   ├── Dark mode color conversion
│   └── Accessibility validation
│
├── AdminThemeController (modules/Controllers/AdminThemeController.php)
│   ├── Show theme settings page
│   ├── Save theme configuration
│   ├── Upload logo/favicon
│   ├── Preview themes
│   └── Reset theme
│
└── Theme Plugin Support
    ├── Theme override system
    ├── Custom CSS injection
    ├── Template overrides
    └── Asset management
```

### Database Schema

```sql
-- Theme configuration table
CREATE TABLE theme_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    setting_type ENUM('color', 'font', 'size', 'url', 'boolean', 'text') DEFAULT 'text',
    category VARCHAR(50),
    created_at INT,
    updated_at INT
);

-- Theme assets table
CREATE TABLE theme_assets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    asset_type ENUM('logo', 'favicon', 'background', 'icon') NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_name VARCHAR(255),
    mime_type VARCHAR(100),
    file_size INT,
    is_active BOOLEAN DEFAULT 0,
    uploaded_at INT,
    uploaded_by INT,
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
);
```

---

## Week 5-6 Implementation Plan

### Days 1-2: Core Theme System (8 hours)

**Tasks:**
1. **Create ThemeManager class** (3 hours)
   - Load theme settings from database
   - Apply settings to templates
   - CSS variable generation
   - Theme validation
   - Fallback handling

2. **Create ThemeConfigurator class** (3 hours)
   - CRUD operations for theme settings
   - Settings validation
   - Default values management
   - Import/export functionality

3. **Create ColorSchemeGenerator** (2 hours)
   - Generate color variations (lighter/darker)
   - Calculate accessible contrasts
   - Dark mode color inversion
   - Color palette generation

**Deliverables:**
- ✅ ThemeManager.php
- ✅ ThemeConfigurator.php
- ✅ ColorSchemeGenerator.php
- ✅ Database migration for theme tables

---

### Days 3-4: Theme Configuration Schema (8 hours)

**Default Theme Settings:**

```json
{
  "colors": {
    "primary": "#667eea",
    "secondary": "#764ba2",
    "success": "#10b981",
    "warning": "#f59e0b",
    "danger": "#ef4444",
    "info": "#3b82f6",
    "light": "#f8f9fa",
    "dark": "#212529",
    "body_bg": "#ffffff",
    "body_text": "#212529",
    "link": "#667eea",
    "border": "#dee2e6"
  },
  "typography": {
    "font_family_base": "Inter, sans-serif",
    "font_family_heading": "Inter, sans-serif",
    "font_family_mono": "JetBrains Mono, monospace",
    "font_size_base": "16px",
    "font_size_sm": "14px",
    "font_size_lg": "18px",
    "line_height_base": "1.5",
    "headings": {
      "h1": "2.5rem",
      "h2": "2rem",
      "h3": "1.75rem",
      "h4": "1.5rem",
      "h5": "1.25rem",
      "h6": "1rem"
    }
  },
  "layout": {
    "sidebar_position": "left",
    "sidebar_width": "280px",
    "content_max_width": "1400px",
    "container_padding": "20px",
    "border_radius": "8px",
    "box_shadow": "0 1px 3px rgba(0,0,0,0.12)"
  },
  "branding": {
    "logo_url": "/assets/images/logo.png",
    "favicon_url": "/assets/images/favicon.ico",
    "app_name": "NexoSupport",
    "tagline": "Professional Support System"
  },
  "dark_mode": {
    "enabled": true,
    "auto_switch": false,
    "switch_time_start": "18:00",
    "switch_time_end": "06:00"
  }
}
```

**Tasks:**
1. Define complete theme schema (2 hours)
2. Create default theme configuration (2 hours)
3. Implement theme validation rules (2 hours)
4. Create theme CSS generator (2 hours)

**Deliverables:**
- ✅ Complete theme schema definition
- ✅ Default theme values
- ✅ Theme validator
- ✅ Dynamic CSS generator

---

### Days 5-7: Admin Configuration UI (12 hours)

**UI Components:**

1. **Theme Settings Page** (`/admin/appearance/theme`)
   - Color picker widgets
   - Font selection dropdowns
   - Layout option controls
   - Real-time preview
   - Save/Reset buttons

2. **Sections:**
   - 🎨 **Colors Tab**
     - Primary color picker
     - Secondary color picker
     - State colors (success, warning, danger, info)
     - Custom color swatches
     - Dark mode colors

   - 🔤 **Typography Tab**
     - Font family selectors
     - Font size controls
     - Line height settings
     - Heading size customization
     - Font weight options

   - 📐 **Layout Tab**
     - Sidebar position (left/right)
     - Sidebar width slider
     - Content max width
     - Border radius control
     - Spacing controls

   - 🖼️ **Branding Tab**
     - Logo upload
     - Favicon upload
     - App name input
     - Tagline input
     - Custom CSS textarea

   - 🌙 **Dark Mode Tab**
     - Enable/disable toggle
     - Auto-switch settings
     - Custom dark colors
     - Preview dark mode

**Tasks:**
1. Create AdminThemeController (3 hours)
2. Build theme settings views (4 hours)
3. Implement color pickers (2 hours)
4. Add file upload for logo/favicon (2 hours)
5. Create real-time preview (1 hour)

**Deliverables:**
- ✅ AdminThemeController.php
- ✅ Theme settings views (Mustache templates)
- ✅ JavaScript for color pickers
- ✅ File upload handling
- ✅ Preview functionality

---

### Days 8-9: Dark Mode Implementation (8 hours)

**Features:**
- Toggle dark mode from admin
- Automatic color inversion
- Custom dark mode colors
- Persistent user preference
- Auto-switch based on time
- System preference detection

**Tasks:**
1. Implement dark mode CSS variables (2 hours)
2. Create color inversion logic (2 hours)
3. Add dark mode toggle UI (2 hours)
4. Implement user preference storage (1 hour)
5. Add auto-switch functionality (1 hour)

**Deliverables:**
- ✅ Dark mode CSS
- ✅ Color inversion system
- ✅ Toggle UI component
- ✅ Preference persistence
- ✅ Auto-switch feature

---

### Days 10-11: Theme Plugin Support (8 hours)

**Theme Plugin Capabilities:**
- Override default theme colors
- Add custom CSS
- Override templates
- Add custom fonts
- Provide preset themes

**Plugin Structure:**
```
my-theme-plugin/
├── plugin.json          # Manifest with theme type
├── MyThemePlugin.php    # Main class
├── theme.json           # Theme configuration
├── assets/
│   ├── css/
│   │   └── theme.css    # Custom styles
│   ├── fonts/           # Custom fonts
│   └── images/          # Theme images
└── templates/           # Template overrides
    └── layouts/
        └── main.mustache
```

**Tasks:**
1. Enhance ThemeManager for plugin support (2 hours)
2. Create theme override system (3 hours)
3. Implement asset loading for theme plugins (2 hours)
4. Create example theme plugin (1 hour)

**Deliverables:**
- ✅ Theme plugin support in ThemeManager
- ✅ Override system
- ✅ Asset loading
- ✅ Example: "Dark Purple" theme plugin

---

### Day 12: Testing & Polish (4 hours)

**Testing Checklist:**
- [ ] Theme settings save correctly
- [ ] Colors apply across all pages
- [ ] Typography changes work
- [ ] Layout options functional
- [ ] Logo/favicon upload works
- [ ] Dark mode toggles correctly
- [ ] Theme plugins load properly
- [ ] Reset to defaults works
- [ ] Preview is accurate
- [ ] Export/import themes works
- [ ] Responsive design maintained

**Tasks:**
1. Comprehensive testing (2 hours)
2. Bug fixes (1 hour)
3. UI/UX polish (1 hour)

---

### Day 13: Documentation (2 hours)

**Create THEME_DEVELOPMENT_GUIDE.md:**
- Theme system overview
- Configuration options reference
- Creating theme plugins
- Color customization guide
- Typography guide
- Layout customization
- Dark mode implementation
- Best practices
- Examples and templates

**Deliverables:**
- ✅ THEME_DEVELOPMENT_GUIDE.md
- ✅ Update REFACTORING_MASTER_PLAN.md

---

## Technical Specifications

### CSS Variables System

Generated CSS will use variables:

```css
:root {
  /* Colors */
  --color-primary: #667eea;
  --color-primary-light: #8b9ff5;
  --color-primary-dark: #4c5dbd;
  --color-secondary: #764ba2;
  --color-success: #10b981;
  --color-warning: #f59e0b;
  --color-danger: #ef4444;
  --color-info: #3b82f6;

  /* Typography */
  --font-family-base: Inter, sans-serif;
  --font-size-base: 16px;
  --line-height-base: 1.5;

  /* Layout */
  --sidebar-width: 280px;
  --content-max-width: 1400px;
  --border-radius: 8px;
}

[data-theme="dark"] {
  --color-primary: #8b9ff5;
  --color-bg: #1a1a1a;
  --color-text: #f0f0f0;
}
```

### API Endpoints

```
GET  /admin/appearance/theme          - Show theme settings
POST /admin/appearance/theme          - Save theme settings
POST /admin/appearance/theme/reset    - Reset to defaults
GET  /admin/appearance/theme/export   - Export theme
POST /admin/appearance/theme/import   - Import theme
POST /admin/appearance/theme/logo     - Upload logo
POST /admin/appearance/theme/favicon  - Upload favicon
GET  /admin/appearance/theme/preview  - Preview theme
POST /api/theme/toggle-dark-mode      - Toggle dark mode
```

---

## Success Criteria

### Must Have (Critical)
- ✅ Theme settings persist in database
- ✅ Colors customizable from admin panel
- ✅ Typography customizable
- ✅ Logo and favicon uploadable
- ✅ Dark mode functional
- ✅ Changes apply site-wide immediately
- ✅ Theme plugins can override styles

### Should Have (Important)
- ✅ Layout customization (sidebar position, widths)
- ✅ Real-time preview
- ✅ Export/import themes
- ✅ Multiple theme presets
- ✅ Accessibility validation
- ✅ Responsive design maintained

### Nice to Have (Optional)
- ⚠️ Theme marketplace
- ⚠️ Theme builder with drag-drop
- ⚠️ Per-user theme preferences
- ⚠️ Scheduled theme switching
- ⚠️ Theme analytics

---

## Risk Assessment

### Technical Risks
1. **CSS Variable Browser Support**
   - Mitigation: Fallback values for old browsers
   - Impact: Low (modern browsers widely support)

2. **Performance Impact**
   - Mitigation: Cache generated CSS, minimize recalculations
   - Impact: Medium (could slow page loads)

3. **Theme Plugin Conflicts**
   - Mitigation: Clear override priority system
   - Impact: Medium (plugins may conflict)

### Timeline Risks
1. **Scope Creep**
   - Mitigation: Stick to defined features, defer nice-to-haves
   - Impact: High (could delay completion)

2. **Integration Complexity**
   - Mitigation: Extensive testing, staged rollout
   - Impact: Medium (may need bug fixes)

---

## Dependencies

### Required Before Start
- ✅ Week 4 Plugin System complete
- ✅ Database infrastructure ready
- ✅ Admin panel structure exists

### Blocks Future Work
- ⏳ Week 7-8 Installer (needs theme system)
- ⏳ Theme marketplace (depends on theme plugins)

---

## Deliverables Summary

### Code
- ThemeManager.php (~600 lines)
- ThemeConfigurator.php (~400 lines)
- ColorSchemeGenerator.php (~300 lines)
- AdminThemeController.php (~500 lines)
- Theme settings views (~800 lines)
- JavaScript for UI (~400 lines)
- CSS for theme system (~300 lines)

### Database
- theme_settings table
- theme_assets table
- Migration scripts

### Documentation
- THEME_DEVELOPMENT_GUIDE.md (~2,000 lines)
- API documentation
- Theme schema reference

### Testing
- Theme settings test suite
- Dark mode tests
- Plugin override tests
- UI tests

---

## Timeline

```
Week 5 (Days 1-7):
├── Days 1-2: Core theme system
├── Days 3-4: Configuration schema
└── Days 5-7: Admin UI

Week 6 (Days 8-13):
├── Days 8-9: Dark mode
├── Days 10-11: Theme plugins
├── Day 12: Testing
└── Day 13: Documentation
```

---

## Next Steps After Completion

1. Create 2-3 example theme plugins
2. Add theme selection to installer
3. Implement per-user theme preferences (optional)
4. Create theme marketplace (future)

---

**Plan Status:** ✅ READY TO IMPLEMENT
**Start Date:** 2025-11-14
**Target Completion:** 2025-11-28 (2 weeks)
**Estimated Effort:** 40 hours

---

**Document Version:** 1.0
**Last Updated:** November 14, 2025
**Author:** ISER Development Team
