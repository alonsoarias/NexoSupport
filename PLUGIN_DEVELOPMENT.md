# NexoSupport Plugin Development Guide

**Version:** 1.0.0
**Last Updated:** 2025-11-12
**Target Audience:** Plugin Developers

---

## Table of Contents

1. [Introduction](#introduction)
2. [Plugin Architecture](#plugin-architecture)
3. [Plugin Structure](#plugin-structure)
4. [Manifest Specification (plugin.json)](#manifest-specification)
5. [Plugin Types](#plugin-types)
6. [Hooks System](#hooks-system)
7. [Permissions System](#permissions-system)
8. [Database Schema (install.xml)](#database-schema-installxml)
9. [Assets Management](#assets-management)
10. [Configuration Schema](#configuration-schema)
11. [Plugin Lifecycle](#plugin-lifecycle)
12. [Best Practices](#best-practices)
13. [Testing Your Plugin](#testing-your-plugin)
14. [Publishing Your Plugin](#publishing-your-plugin)
15. [Examples](#examples)

---

## Introduction

NexoSupport features a powerful, extensible plugin system that allows developers to add new functionality without modifying core code. Plugins are self-contained modules that can:

- Add new tools and features
- Integrate with external services
- Customize authentication methods
- Create custom themes
- Generate reports
- Extend core functionality through hooks

This guide covers everything you need to create, test, and publish plugins for NexoSupport.

---

## Plugin Architecture

### Core Concepts

**Plugins** in NexoSupport follow these principles:

1. **Self-Contained**: Each plugin is an isolated module with its own namespace
2. **Dynamic Loading**: Plugins are loaded on-demand based on type and status
3. **Dependency Management**: Plugins can depend on other plugins
4. **Versioned**: Semantic versioning ensures compatibility
5. **Transactional**: Installation/updates are atomic with automatic rollback
6. **Type-Based Organization**: Plugins are organized by type (tools, auth, themes, etc.)

### Plugin Lifecycle

```
┌─────────────┐
│   Upload    │ ──> ZIP file uploaded via admin interface
└──────┬──────┘
       │
       ▼
┌─────────────┐
│  Validation │ ──> Manifest validation, dependency check
└──────┬──────┘
       │
       ▼
┌─────────────┐
│ Installation│ ──> Extract, install schema, register hooks
└──────┬──────┘
       │
       ▼
┌─────────────┐
│   Enable    │ ──> Activate plugin functionality
└──────┬──────┘
       │
       ▼
┌─────────────┐
│   Runtime   │ ──> Hook execution, feature availability
└──────┬──────┘
       │
       ▼
┌─────────────┐
│   Update    │ ──> Version upgrade with backup/rollback
└──────┬──────┘
       │
       ▼
┌─────────────┐
│ Uninstall   │ ──> Remove files, drop tables, cleanup
└─────────────┘
```

---

## Plugin Structure

### Directory Layout

```
my-plugin/
├── plugin.json              # Manifest (REQUIRED)
├── install.xml              # Database schema (optional)
├── README.md                # Documentation (recommended)
├── LICENSE                  # License file (recommended)
├── src/                     # PHP source code
│   ├── Plugin.php          # Main plugin class
│   ├── Controllers/        # HTTP controllers
│   ├── Models/             # Data models
│   ├── Services/           # Business logic
│   └── Helpers/            # Utility functions
├── assets/                  # Frontend resources
│   ├── css/
│   │   └── styles.css
│   ├── js/
│   │   └── script.js
│   └── images/
│       └── icon.png
├── views/                   # Mustache templates
│   └── index.mustache
├── lang/                    # Translations
│   ├── es/
│   │   └── messages.php
│   └── en/
│       └── messages.php
└── tests/                   # Unit tests (recommended)
    └── PluginTest.php
```

### Minimum Required Files

A minimal plugin needs only:

```
minimal-plugin/
├── plugin.json              # Manifest with metadata
└── src/
    └── Plugin.php          # Main class implementing plugin logic
```

---

## Manifest Specification

The `plugin.json` file is the heart of your plugin. It defines metadata, dependencies, hooks, permissions, and configuration.

### Required Fields

```json
{
  "slug": "my-plugin",
  "name": "My Plugin",
  "type": "tool",
  "version": "1.0.0",
  "description": "A brief description of what this plugin does",
  "author": "Your Name",
  "requires": "1.0.0"
}
```

### Full Specification

```json
{
  // === REQUIRED FIELDS ===

  "slug": "my-plugin",
  // Unique identifier (lowercase, hyphens only)
  // Must be unique across all plugins
  // Used in URLs, file paths, database tables

  "name": "My Awesome Plugin",
  // Human-readable name
  // Displayed in admin interface

  "type": "tool",
  // Plugin type: tool, auth, theme, report, module, integration
  // Determines where plugin files are stored

  "version": "1.0.0",
  // Semantic version (MAJOR.MINOR.PATCH)
  // Used for update compatibility checking

  "description": "This plugin adds X functionality to NexoSupport",
  // Brief description (1-2 sentences)
  // Displayed in plugin list

  "author": "Your Name or Company",
  // Plugin author/creator

  "requires": "1.0.0",
  // Minimum NexoSupport version required
  // Checked during installation

  // === OPTIONAL FIELDS ===

  "author_url": "https://yourwebsite.com",
  // Author website or profile

  "plugin_url": "https://yourwebsite.com/plugins/my-plugin",
  // Plugin homepage or documentation

  "namespace": "MyPlugin",
  // PSR-4 namespace for plugin classes
  // Default: slug converted to PascalCase

  "main_class": "MyPlugin\\Plugin",
  // Fully qualified main class name
  // Default: {namespace}\\Plugin

  "dependencies": ["other-plugin", "another-plugin"],
  // Array of plugin slugs this plugin depends on
  // Dependencies must be installed and enabled

  "hooks": [
    {
      "name": "admin.tools.menu",
      "callback": "MyPlugin\\Plugin::registerMenuItem",
      "priority": 10
    }
  ],
  // Hooks to register (see Hooks System section)

  "permissions": [
    {
      "name": "tools.myplugin.view",
      "description": "View My Plugin"
    },
    {
      "name": "tools.myplugin.manage",
      "description": "Manage My Plugin settings"
    }
  ],
  // Custom permissions for RBAC

  "assets": {
    "css": ["assets/css/styles.css"],
    "js": ["assets/js/script.js"]
  },
  // Frontend assets to load

  "config_schema": [
    {
      "name": "api_key",
      "type": "text",
      "label": "API Key",
      "description": "Enter your API key",
      "default": "",
      "required": true
    },
    {
      "name": "enabled_features",
      "type": "multiselect",
      "label": "Enabled Features",
      "description": "Select which features to enable",
      "default": ["feature1"],
      "options": [
        {"value": "feature1", "label": "Feature 1"},
        {"value": "feature2", "label": "Feature 2"}
      ]
    }
  ]
  // Configuration fields (see Configuration Schema section)
}
```

### Field Validation Rules

| Field | Type | Pattern | Max Length |
|-------|------|---------|------------|
| `slug` | string | `^[a-z0-9-]+$` | 100 chars |
| `name` | string | Any | 255 chars |
| `type` | enum | `tool\|auth\|theme\|report\|module\|integration` | - |
| `version` | string | Semantic versioning | 20 chars |
| `description` | string | Any | 1000 chars |
| `author` | string | Any | 255 chars |
| `requires` | string | Semantic versioning | 20 chars |
| `namespace` | string | Valid PHP namespace | 255 chars |

---

## Plugin Types

NexoSupport organizes plugins by type. Choose the type that best fits your plugin's purpose:

### 1. **tool** (Tools & Utilities)

**Purpose**: Add new tools, utilities, or standalone features

**Storage**: `modules/plugins/tools/{slug}/`

**Examples**:
- Ticket export tools
- Bulk operations
- Dashboard widgets
- Custom calculators

**Hooks Available**:
- `admin.tools.menu` - Add menu item
- `admin.dashboard.widgets` - Add dashboard widget

### 2. **auth** (Authentication)

**Purpose**: Custom authentication methods

**Storage**: `modules/plugins/auth/{slug}/`

**Examples**:
- LDAP integration
- OAuth providers (Google, GitHub, etc.)
- SAML authentication
- Custom SSO

**Hooks Available**:
- `auth.providers` - Register auth provider
- `auth.login.before` - Pre-login hook
- `auth.login.after` - Post-login hook

### 3. **theme** (Themes)

**Purpose**: Visual customization and branding

**Storage**: `modules/plugins/themes/{slug}/`

**Examples**:
- Custom color schemes
- Layout modifications
- Custom templates
- White-label branding

**Hooks Available**:
- `theme.head` - Inject into `<head>`
- `theme.footer` - Inject into footer
- `theme.css` - Add custom styles

### 4. **report** (Reports & Analytics)

**Purpose**: Data reporting and analytics

**Storage**: `modules/plugins/reports/{slug}/`

**Examples**:
- Custom reports
- Data exports
- Analytics dashboards
- KPI tracking

**Hooks Available**:
- `admin.reports.menu` - Add report menu item
- `reports.generate` - Custom report generation

### 5. **module** (Core Extensions)

**Purpose**: Extend core functionality

**Storage**: `modules/plugins/modules/{slug}/`

**Examples**:
- Custom fields
- Workflow automation
- API extensions
- Core enhancements

**Hooks Available**:
- `core.init` - Core initialization
- `core.routes` - Register routes
- `core.middleware` - Add middleware

### 6. **integration** (External Integrations)

**Purpose**: Third-party service integrations

**Storage**: `modules/plugins/integrations/{slug}/`

**Examples**:
- Slack notifications
- Zapier webhooks
- Email service providers
- CRM integrations

**Hooks Available**:
- `integrations.connect` - Connection setup
- `integrations.sync` - Data synchronization
- `webhook.received` - Process webhooks

---

## Hooks System

NexoSupport uses an event-driven hook system allowing plugins to execute code at specific points in the application lifecycle.

### Hook Registration

Hooks are registered in `plugin.json`:

```json
{
  "hooks": [
    {
      "name": "admin.tools.menu",
      "callback": "MyPlugin\\Plugin::addMenuItem",
      "priority": 10
    }
  ]
}
```

### Hook Properties

- **name**: Hook identifier (dot-notation)
- **callback**: Static method to execute (Class::method)
- **priority**: Execution order (lower = earlier, default: 10)

### Available Core Hooks

#### Application Lifecycle

| Hook | When | Parameters | Return |
|------|------|------------|--------|
| `app.init` | Application startup | `[]` | void |
| `app.request.before` | Before request processing | `[$request]` | void |
| `app.request.after` | After request processing | `[$request, $response]` | void |

#### Authentication

| Hook | When | Parameters | Return |
|------|------|------------|--------|
| `auth.login.before` | Before login attempt | `[$username]` | void |
| `auth.login.after` | After successful login | `[$user]` | void |
| `auth.logout` | User logout | `[$user]` | void |
| `auth.providers` | Register auth providers | `[$providers]` | `array` |

#### Admin Interface

| Hook | When | Parameters | Return |
|------|------|------------|--------|
| `admin.menu` | Admin menu render | `[$menuItems]` | `array` |
| `admin.tools.menu` | Tools submenu | `[$toolItems]` | `array` |
| `admin.dashboard.widgets` | Dashboard widgets | `[$widgets]` | `array` |
| `admin.sidebar` | Sidebar render | `[$items]` | `array` |

#### Plugins

| Hook | When | Parameters | Return |
|------|------|------------|--------|
| `plugin.installed` | After plugin install | `[$pluginSlug]` | void |
| `plugin.enabled` | Plugin enabled | `[$pluginSlug]` | void |
| `plugin.disabled` | Plugin disabled | `[$pluginSlug]` | void |
| `plugin.uninstalled` | Before uninstall | `[$pluginSlug]` | void |

### Implementing Hook Callbacks

```php
<?php

namespace MyPlugin;

class Plugin
{
    /**
     * Add menu item to admin tools
     *
     * @param array $menuItems Existing menu items
     * @return array Modified menu items
     */
    public static function addMenuItem(array $menuItems): array
    {
        $menuItems[] = [
            'label' => 'My Plugin Tool',
            'url' => '/admin/tools/my-plugin',
            'icon' => 'bi-plugin',
            'permission' => 'tools.myplugin.view'
        ];

        return $menuItems;
    }

    /**
     * Add custom authentication provider
     *
     * @param array $providers Existing providers
     * @return array Modified providers
     */
    public static function registerAuthProvider(array $providers): array
    {
        $providers['my-auth'] = [
            'name' => 'My Custom Auth',
            'class' => 'MyPlugin\\Auth\\CustomAuthProvider',
            'enabled' => true
        ];

        return $providers;
    }
}
```

### Hook Execution Order

When multiple plugins register the same hook:

1. Hooks are sorted by `priority` (ascending)
2. Same priority: alphabetical by plugin slug
3. Each callback receives the output of the previous callback

```
Priority 5:  PluginA::hook() → result1
Priority 10: PluginB::hook(result1) → result2
Priority 15: PluginC::hook(result2) → final result
```

---

## Permissions System

NexoSupport uses Role-Based Access Control (RBAC) with granular permissions. Plugins can define custom permissions.

### Defining Permissions

In `plugin.json`:

```json
{
  "permissions": [
    {
      "name": "tools.myplugin.view",
      "description": "View My Plugin interface"
    },
    {
      "name": "tools.myplugin.manage",
      "description": "Manage My Plugin settings"
    },
    {
      "name": "tools.myplugin.delete",
      "description": "Delete My Plugin data"
    }
  ]
}
```

### Permission Naming Convention

Use dot-notation with 3-4 levels:

```
{type}.{plugin_slug}.{action}
{type}.{plugin_slug}.{resource}.{action}
```

Examples:
- `tools.myplugin.view`
- `tools.myplugin.settings.manage`
- `reports.analytics.export.csv`

### Checking Permissions

```php
<?php

namespace MyPlugin\Controllers;

use Core\Auth\PermissionChecker;

class PluginController
{
    private PermissionChecker $permissions;

    public function index()
    {
        // Check single permission
        if (!$this->permissions->has('tools.myplugin.view')) {
            throw new \Exception('Access denied', 403);
        }

        // Check multiple permissions (OR logic)
        if (!$this->permissions->hasAny([
            'tools.myplugin.view',
            'admin.full'
        ])) {
            throw new \Exception('Access denied', 403);
        }

        // Check multiple permissions (AND logic)
        if (!$this->permissions->hasAll([
            'tools.myplugin.view',
            'tools.myplugin.manage'
        ])) {
            throw new \Exception('Insufficient permissions', 403);
        }

        // Your code here
    }
}
```

### Default Admin Permissions

Administrators automatically have these permissions:
- `admin.full` - Full system access
- `admin.plugins.manage` - Install/uninstall plugins
- `admin.users.manage` - User management
- `admin.settings.manage` - System settings

---

## Database Schema (install.xml)

Plugins can define database tables using XML schema definition. Tables are automatically created during installation.

### Basic install.xml

```xml
<?xml version="1.0" encoding="UTF-8"?>
<database>
  <table name="my_data">
    <column name="id" type="INT" autoincrement="1" primarykey="1">
      <unsigned/>
    </column>
    <column name="title" type="VARCHAR" length="255">
      <notnull/>
    </column>
    <column name="content" type="TEXT">
      <null/>
    </column>
    <column name="created_at" type="TIMESTAMP">
      <default value="CURRENT_TIMESTAMP"/>
    </column>
    <index name="idx_title">
      <col>title</col>
    </index>
  </table>
</database>
```

### Table Naming

The system automatically prefixes plugin tables:

```
plugin_{slug}_{table_name}
```

Example:
- Plugin slug: `my-plugin`
- Table in XML: `my_data`
- Actual table: `plugin_my_plugin_my_data`

### Column Types

| XML Type | MySQL | PostgreSQL | SQLite |
|----------|-------|------------|--------|
| `INT` | `INT` | `INTEGER` | `INTEGER` |
| `BIGINT` | `BIGINT` | `BIGINT` | `INTEGER` |
| `VARCHAR` | `VARCHAR(n)` | `VARCHAR(n)` | `TEXT` |
| `TEXT` | `TEXT` | `TEXT` | `TEXT` |
| `LONGTEXT` | `LONGTEXT` | `TEXT` | `TEXT` |
| `DECIMAL` | `DECIMAL(m,d)` | `DECIMAL(m,d)` | `REAL` |
| `FLOAT` | `FLOAT` | `REAL` | `REAL` |
| `BOOLEAN` | `TINYINT(1)` | `BOOLEAN` | `INTEGER` |
| `DATE` | `DATE` | `DATE` | `TEXT` |
| `DATETIME` | `DATETIME` | `TIMESTAMP` | `TEXT` |
| `TIMESTAMP` | `TIMESTAMP` | `TIMESTAMP` | `TEXT` |

### Column Modifiers

```xml
<column name="id" type="INT" autoincrement="1" primarykey="1">
  <unsigned/>          <!-- Unsigned integer -->
  <notnull/>          <!-- NOT NULL -->
  <null/>             <!-- NULL (default) -->
  <default value="0"/> <!-- Default value -->
  <unique/>           <!-- UNIQUE constraint -->
</column>
```

### Indexes

```xml
<!-- Simple index -->
<index name="idx_column_name">
  <col>column_name</col>
</index>

<!-- Composite index -->
<index name="idx_multi">
  <col>column1</col>
  <col>column2</col>
</index>

<!-- Unique index -->
<unique name="uniq_email">
  <col>email</col>
</unique>
```

### Foreign Keys

```xml
<foreignkey name="fk_user" table="users" ondelete="CASCADE" onupdate="CASCADE">
  <col local="user_id" foreign="id"/>
</foreignkey>
```

### Complete Example

```xml
<?xml version="1.0" encoding="UTF-8"?>
<database>
  <!-- Main data table -->
  <table name="tasks">
    <column name="id" type="INT" autoincrement="1" primarykey="1">
      <unsigned/>
    </column>
    <column name="user_id" type="INT">
      <unsigned/>
      <notnull/>
    </column>
    <column name="title" type="VARCHAR" length="255">
      <notnull/>
    </column>
    <column name="description" type="TEXT">
      <null/>
    </column>
    <column name="status" type="VARCHAR" length="50">
      <notnull/>
      <default value="pending"/>
    </column>
    <column name="priority" type="INT">
      <default value="0"/>
    </column>
    <column name="completed_at" type="TIMESTAMP">
      <null/>
    </column>
    <column name="created_at" type="TIMESTAMP">
      <default value="CURRENT_TIMESTAMP"/>
    </column>
    <column name="updated_at" type="TIMESTAMP">
      <null/>
    </column>

    <!-- Indexes -->
    <index name="idx_user">
      <col>user_id</col>
    </index>
    <index name="idx_status">
      <col>status</col>
    </index>
    <index name="idx_priority">
      <col>priority</col>
    </index>

    <!-- Foreign key -->
    <foreignkey name="fk_task_user" table="users" ondelete="CASCADE">
      <col local="user_id" foreign="id"/>
    </foreignkey>
  </table>

  <!-- Related tags table -->
  <table name="task_tags">
    <column name="id" type="INT" autoincrement="1" primarykey="1">
      <unsigned/>
    </column>
    <column name="task_id" type="INT">
      <unsigned/>
      <notnull/>
    </column>
    <column name="tag" type="VARCHAR" length="100">
      <notnull/>
    </column>

    <!-- Unique constraint -->
    <unique name="uniq_task_tag">
      <col>task_id</col>
      <col>tag</col>
    </unique>

    <!-- Foreign key -->
    <foreignkey name="fk_tag_task" table="plugin_my_plugin_tasks" ondelete="CASCADE">
      <col local="task_id" foreign="id"/>
    </foreignkey>
  </table>
</database>
```

---

## Assets Management

Plugins can include CSS, JavaScript, and images.

### Defining Assets

In `plugin.json`:

```json
{
  "assets": {
    "css": [
      "assets/css/styles.css",
      "assets/css/theme.css"
    ],
    "js": [
      "assets/js/main.js",
      "assets/js/components.js"
    ]
  }
}
```

### Asset Loading

Assets are automatically loaded when the plugin is enabled:

- **CSS**: Injected into `<head>` on all admin pages
- **JS**: Loaded before closing `</body>` tag

### Asset Structure

```
my-plugin/
└── assets/
    ├── css/
    │   ├── styles.css           # Main styles
    │   └── components.css       # Component styles
    ├── js/
    │   ├── main.js              # Main script
    │   ├── components.js        # Components
    │   └── lib/
    │       └── vendor.js        # Third-party libraries
    └── images/
        ├── icon.png             # Plugin icon
        ├── logo.svg             # Logo
        └── screenshots/
            └── preview.png      # Screenshots
```

### CSS Best Practices

```css
/* Prefix all classes with plugin slug */
.myplugin-container {
  /* Your styles */
}

.myplugin-button {
  /* Your styles */
}

/* Use CSS custom properties for theming */
.myplugin-panel {
  background: var(--card-bg, #fff);
  color: var(--text-primary, #333);
  border: 1px solid var(--border-color, #ddd);
}

/* Responsive design */
@media (max-width: 768px) {
  .myplugin-container {
    flex-direction: column;
  }
}
```

### JavaScript Best Practices

```javascript
// Wrap in IIFE to avoid global namespace pollution
(function() {
  'use strict';

  // Plugin namespace
  window.MyPlugin = window.MyPlugin || {};

  // Initialize on DOMContentLoaded
  document.addEventListener('DOMContentLoaded', function() {
    MyPlugin.init();
  });

  // Plugin methods
  MyPlugin.init = function() {
    console.log('MyPlugin initialized');
    this.bindEvents();
  };

  MyPlugin.bindEvents = function() {
    // Event handlers
  };

})();
```

---

## Configuration Schema

Plugins can define user-configurable settings that appear in the admin interface.

### Configuration in plugin.json

```json
{
  "config_schema": [
    {
      "name": "api_key",
      "type": "text",
      "label": "API Key",
      "description": "Enter your service API key",
      "default": "",
      "required": true,
      "placeholder": "sk-xxxxxxxxxxxxx"
    },
    {
      "name": "api_secret",
      "type": "password",
      "label": "API Secret",
      "description": "Enter your API secret (will be encrypted)",
      "default": "",
      "required": true
    },
    {
      "name": "enabled",
      "type": "boolean",
      "label": "Enable Integration",
      "description": "Toggle integration on/off",
      "default": true
    },
    {
      "name": "mode",
      "type": "select",
      "label": "Operation Mode",
      "description": "Select the operation mode",
      "default": "production",
      "options": [
        {"value": "development", "label": "Development"},
        {"value": "staging", "label": "Staging"},
        {"value": "production", "label": "Production"}
      ]
    },
    {
      "name": "features",
      "type": "multiselect",
      "label": "Enabled Features",
      "description": "Select which features to enable",
      "default": ["feature1", "feature2"],
      "options": [
        {"value": "feature1", "label": "Feature 1"},
        {"value": "feature2", "label": "Feature 2"},
        {"value": "feature3", "label": "Feature 3"}
      ]
    },
    {
      "name": "max_retries",
      "type": "number",
      "label": "Maximum Retries",
      "description": "Number of retry attempts",
      "default": 3,
      "min": 1,
      "max": 10
    },
    {
      "name": "custom_css",
      "type": "textarea",
      "label": "Custom CSS",
      "description": "Add custom CSS styles",
      "default": "",
      "rows": 10
    },
    {
      "name": "webhook_url",
      "type": "url",
      "label": "Webhook URL",
      "description": "Enter webhook endpoint URL",
      "default": "",
      "placeholder": "https://example.com/webhook"
    },
    {
      "name": "contact_email",
      "type": "email",
      "label": "Contact Email",
      "description": "Notification email address",
      "default": "",
      "placeholder": "admin@example.com"
    }
  ]
}
```

### Field Types

| Type | HTML Input | Validation | Storage |
|------|-----------|------------|---------|
| `text` | `<input type="text">` | String | VARCHAR |
| `password` | `<input type="password">` | String (encrypted) | VARCHAR |
| `email` | `<input type="email">` | Email format | VARCHAR |
| `url` | `<input type="url">` | URL format | VARCHAR |
| `number` | `<input type="number">` | Integer/Float | INT/DECIMAL |
| `boolean` | `<input type="checkbox">` | true/false | TINYINT |
| `textarea` | `<textarea>` | String | TEXT |
| `select` | `<select>` | Value in options | VARCHAR |
| `multiselect` | `<select multiple>` | Array of values | JSON |
| `date` | `<input type="date">` | Date format | DATE |
| `datetime` | `<input type="datetime-local">` | Datetime format | DATETIME |
| `color` | `<input type="color">` | Hex color | VARCHAR |
| `file` | `<input type="file">` | File path | VARCHAR |

### Accessing Configuration

```php
<?php

namespace MyPlugin;

use Core\Plugin\PluginConfig;

class Plugin
{
    private PluginConfig $config;

    public function __construct()
    {
        $this->config = new PluginConfig('my-plugin');
    }

    public function doSomething()
    {
        // Get single value
        $apiKey = $this->config->get('api_key');

        // Get with default fallback
        $maxRetries = $this->config->get('max_retries', 3);

        // Get boolean
        $enabled = $this->config->getBool('enabled');

        // Get integer
        $timeout = $this->config->getInt('timeout');

        // Get array (for multiselect)
        $features = $this->config->getArray('features');

        // Check if value exists
        if ($this->config->has('api_key')) {
            // Use API
        }

        // Get all configuration
        $allConfig = $this->config->getAll();
    }
}
```

---

## Plugin Lifecycle

### Installation

**Triggered by**: Admin uploads plugin ZIP

**Process**:
1. Extract ZIP to temp directory
2. Validate `plugin.json` structure
3. Check slug uniqueness
4. Verify dependencies
5. Extract to `modules/plugins/{type}/{slug}/`
6. Install database schema (if `install.xml` exists)
7. Register hooks
8. Create permissions
9. Insert plugin record into `plugins` table
10. Log installation

**Rollback on failure**: All steps are transactional

### Enabling

**Triggered by**: Admin clicks "Enable"

**Process**:
1. Verify dependencies are enabled
2. Update `enabled` column to `1`
3. Load plugin assets
4. Execute `plugin.enabled` hook
5. Clear caches

### Runtime

**Triggered by**: Each request (for enabled plugins)

**Process**:
1. Load plugin class
2. Register hooks
3. Execute hook callbacks as triggered
4. Load assets (CSS/JS)

### Disabling

**Triggered by**: Admin clicks "Disable"

**Process**:
1. Check no enabled plugins depend on this
2. Update `enabled` column to `0`
3. Unload assets
4. Execute `plugin.disabled` hook
5. Clear caches

### Updating

**Triggered by**: Admin uploads new plugin version

**Process**:
1. Validate new version is higher
2. Temporarily disable plugin
3. Backup current version (rename to `.backup`)
4. Extract new version
5. Update database schema (if `install.xml` changed)
6. Update `version` and `manifest` in database
7. Re-enable if was enabled
8. Remove backup
9. Execute `plugin.updated` hook

**Rollback on failure**: Restore from backup

### Uninstallation

**Triggered by**: Admin clicks "Uninstall"

**Process**:
1. Check no enabled plugins depend on this
2. Disable plugin if enabled
3. Execute `plugin.uninstalled` hook
4. Drop all plugin database tables
5. Delete plugin files
6. Delete permissions
7. Delete configuration
8. Remove from `plugins` table
9. Log uninstallation

---

## Best Practices

### Code Organization

1. **Follow PSR Standards**
   - PSR-1: Basic coding standard
   - PSR-4: Autoloading
   - PSR-12: Extended coding style

2. **Use Namespaces**
   ```php
   namespace MyPlugin\Controllers;
   namespace MyPlugin\Services;
   namespace MyPlugin\Models;
   ```

3. **Dependency Injection**
   ```php
   class MyService {
       public function __construct(
           private Database $db,
           private Logger $logger
       ) {}
   }
   ```

### Security

1. **Validate All Input**
   ```php
   $slug = filter_var($_POST['slug'], FILTER_SANITIZE_STRING);
   if (!preg_match('/^[a-z0-9-]+$/', $slug)) {
       throw new \Exception('Invalid slug format');
   }
   ```

2. **Use Prepared Statements**
   ```php
   $stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
   $stmt->execute(['id' => $userId]);
   ```

3. **Check Permissions**
   ```php
   if (!$permissions->has('tools.myplugin.manage')) {
       throw new \Exception('Access denied', 403);
   }
   ```

4. **Escape Output**
   ```php
   echo htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8');
   ```

5. **CSRF Protection**
   ```html
   <input type="hidden" name="csrf_token" value="{{csrf_token}}">
   ```

### Performance

1. **Cache Expensive Operations**
   ```php
   $cache = new Cache();
   $data = $cache->remember('my-data', 3600, function() {
       return $this->fetchExpensiveData();
   });
   ```

2. **Lazy Load Dependencies**
   ```php
   private ?HeavyService $heavyService = null;

   private function getHeavyService(): HeavyService {
       if ($this->heavyService === null) {
           $this->heavyService = new HeavyService();
       }
       return $this->heavyService;
   }
   ```

3. **Database Indexing**
   - Index foreign keys
   - Index columns used in WHERE clauses
   - Avoid over-indexing

### Compatibility

1. **Check Version Requirements**
   ```json
   {
     "requires": "1.0.0"
   }
   ```

2. **Handle Missing Dependencies**
   ```php
   if (!class_exists('RequiredClass')) {
       throw new \Exception('Required dependency not found');
   }
   ```

3. **Database Abstraction**
   - Use the provided Database class
   - Don't write database-specific SQL
   - Use install.xml for schema

### Internationalization

1. **Use Translation Functions**
   ```php
   echo __('myplugin.welcome_message');
   ```

2. **Provide Multiple Languages**
   ```
   lang/
   ├── es/
   │   └── messages.php
   └── en/
       └── messages.php
   ```

3. **Don't Hardcode Strings**
   ```php
   // BAD
   echo "Welcome to My Plugin";

   // GOOD
   echo __('myplugin.welcome');
   ```

### Error Handling

1. **Use Try-Catch Blocks**
   ```php
   try {
       $this->dangerousOperation();
   } catch (\Exception $e) {
       Logger::error('Operation failed', [
           'error' => $e->getMessage(),
           'trace' => $e->getTraceAsString()
       ]);
       throw $e;
   }
   ```

2. **Log Important Events**
   ```php
   Logger::info('Plugin action executed', [
       'plugin' => 'my-plugin',
       'action' => 'process_data'
   ]);
   ```

3. **Provide Helpful Error Messages**
   ```php
   if (!$apiKey) {
       throw new \Exception(
           'API key is required. Please configure it in plugin settings.'
       );
   }
   ```

---

## Testing Your Plugin

### Manual Testing Checklist

- [ ] Installation completes without errors
- [ ] Database tables created correctly
- [ ] Hooks execute at expected times
- [ ] Permissions work as intended
- [ ] Configuration saves and loads
- [ ] Assets load properly
- [ ] Enable/disable works
- [ ] Update preserves data
- [ ] Uninstall removes all data
- [ ] No PHP errors or warnings
- [ ] Works with dependencies
- [ ] Compatible with core version

### Unit Testing

Create tests in `tests/` directory:

```php
<?php

namespace MyPlugin\Tests;

use PHPUnit\Framework\TestCase;
use MyPlugin\Plugin;

class PluginTest extends TestCase
{
    public function testPluginInitialization()
    {
        $plugin = new Plugin();
        $this->assertInstanceOf(Plugin::class, $plugin);
    }

    public function testConfigurationLoading()
    {
        $config = new PluginConfig('my-plugin');
        $value = $config->get('api_key');
        $this->assertIsString($value);
    }
}
```

Run tests:
```bash
./vendor/bin/phpunit plugins/my-plugin/tests
```

---

## Publishing Your Plugin

### Packaging

1. **Create ZIP Archive**
   ```bash
   cd my-plugin
   zip -r my-plugin-1.0.0.zip . -x "*.git*" -x "tests/*" -x "*.DS_Store"
   ```

2. **Verify Contents**
   - `plugin.json` at root
   - All source files
   - Assets (CSS/JS/images)
   - README.md
   - LICENSE

3. **Test Installation**
   - Upload via admin interface
   - Verify all features work
   - Test with clean installation

### Documentation

Include a comprehensive README.md:

```markdown
# My Plugin

Brief description of what the plugin does.

## Features

- Feature 1
- Feature 2
- Feature 3

## Installation

1. Download the latest release ZIP
2. Go to Admin → Plugins → Upload
3. Select the ZIP file
4. Click "Install Plugin"
5. Click "Enable" to activate

## Configuration

1. Go to Admin → Plugins
2. Click on "My Plugin"
3. Configure the following settings:
   - API Key: Your service API key
   - Mode: Select operation mode

## Usage

Instructions on how to use the plugin...

## Requirements

- NexoSupport >= 1.0.0
- PHP >= 8.1
- MySQL >= 5.7 or PostgreSQL >= 12

## Support

For issues or questions, please visit:
https://github.com/yourname/my-plugin/issues

## License

MIT License
```

---

## Examples

### Example 1: Simple Tool Plugin

**Purpose**: Add a "System Info" tool to display PHP/server information

```json
{
  "slug": "system-info",
  "name": "System Information Tool",
  "type": "tool",
  "version": "1.0.0",
  "description": "Display PHP and server information",
  "author": "NexoSupport Team",
  "requires": "1.0.0",
  "namespace": "SystemInfo",
  "hooks": [
    {
      "name": "admin.tools.menu",
      "callback": "SystemInfo\\Plugin::addMenuItem",
      "priority": 10
    }
  ],
  "permissions": [
    {
      "name": "tools.systeminfo.view",
      "description": "View system information"
    }
  ]
}
```

```php
<?php
// src/Plugin.php

namespace SystemInfo;

class Plugin
{
    public static function addMenuItem(array $items): array
    {
        $items[] = [
            'label' => 'System Info',
            'url' => '/admin/tools/system-info',
            'icon' => 'bi-info-circle',
            'permission' => 'tools.systeminfo.view'
        ];
        return $items;
    }

    public function displayInfo(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize')
        ];
    }
}
```

### Example 2: Database Plugin with install.xml

**Purpose**: Task management plugin with database tables

```xml
<!-- install.xml -->
<?xml version="1.0" encoding="UTF-8"?>
<database>
  <table name="tasks">
    <column name="id" type="INT" autoincrement="1" primarykey="1">
      <unsigned/>
    </column>
    <column name="user_id" type="INT">
      <unsigned/>
      <notnull/>
    </column>
    <column name="title" type="VARCHAR" length="255">
      <notnull/>
    </column>
    <column name="completed" type="BOOLEAN">
      <default value="0"/>
    </column>
    <column name="created_at" type="TIMESTAMP">
      <default value="CURRENT_TIMESTAMP"/>
    </column>
    <index name="idx_user">
      <col>user_id</col>
    </index>
  </table>
</database>
```

```php
<?php
// src/Models/Task.php

namespace TaskManager\Models;

class Task
{
    private \PDO $db;
    private string $tableName = 'plugin_task_manager_tasks';

    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }

    public function create(int $userId, string $title): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO {$this->tableName} (user_id, title)
            VALUES (:user_id, :title)
        ");

        $stmt->execute([
            'user_id' => $userId,
            'title' => $title
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function getByUser(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->tableName}
            WHERE user_id = :user_id
            ORDER BY created_at DESC
        ");

        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
```

### Example 3: Plugin with Configuration

**Purpose**: Email notification plugin with configurable SMTP settings

```json
{
  "config_schema": [
    {
      "name": "smtp_host",
      "type": "text",
      "label": "SMTP Host",
      "description": "SMTP server hostname",
      "default": "smtp.gmail.com",
      "required": true
    },
    {
      "name": "smtp_port",
      "type": "number",
      "label": "SMTP Port",
      "description": "SMTP server port",
      "default": 587,
      "required": true
    },
    {
      "name": "smtp_encryption",
      "type": "select",
      "label": "Encryption",
      "description": "Encryption method",
      "default": "tls",
      "options": [
        {"value": "none", "label": "None"},
        {"value": "ssl", "label": "SSL"},
        {"value": "tls", "label": "TLS"}
      ]
    },
    {
      "name": "smtp_username",
      "type": "text",
      "label": "Username",
      "description": "SMTP authentication username",
      "default": "",
      "required": true
    },
    {
      "name": "smtp_password",
      "type": "password",
      "label": "Password",
      "description": "SMTP authentication password",
      "default": "",
      "required": true
    }
  ]
}
```

```php
<?php
// src/Services/EmailService.php

namespace EmailNotifier\Services;

use Core\Plugin\PluginConfig;

class EmailService
{
    private PluginConfig $config;

    public function __construct()
    {
        $this->config = new PluginConfig('email-notifier');
    }

    public function sendEmail(string $to, string $subject, string $body): bool
    {
        $transport = (new \Swift_SmtpTransport(
            $this->config->get('smtp_host'),
            $this->config->getInt('smtp_port'),
            $this->config->get('smtp_encryption')
        ))
            ->setUsername($this->config->get('smtp_username'))
            ->setPassword($this->config->get('smtp_password'));

        $mailer = new \Swift_Mailer($transport);

        $message = (new \Swift_Message($subject))
            ->setFrom([$this->config->get('from_email')])
            ->setTo([$to])
            ->setBody($body, 'text/html');

        return $mailer->send($message) > 0;
    }
}
```

---

## Appendix: Complete Plugin Template

A minimal, working plugin template to get started:

```
my-plugin/
├── plugin.json
└── src/
    └── Plugin.php
```

**plugin.json**:
```json
{
  "slug": "my-plugin",
  "name": "My Plugin",
  "type": "tool",
  "version": "1.0.0",
  "description": "A simple example plugin",
  "author": "Your Name",
  "requires": "1.0.0",
  "namespace": "MyPlugin",
  "main_class": "MyPlugin\\Plugin",
  "hooks": [
    {
      "name": "admin.tools.menu",
      "callback": "MyPlugin\\Plugin::registerMenu",
      "priority": 10
    }
  ],
  "permissions": [
    {
      "name": "tools.myplugin.view",
      "description": "View My Plugin"
    }
  ]
}
```

**src/Plugin.php**:
```php
<?php

namespace MyPlugin;

class Plugin
{
    /**
     * Register menu item in admin tools
     */
    public static function registerMenu(array $menuItems): array
    {
        $menuItems[] = [
            'label' => 'My Plugin',
            'url' => '/admin/tools/my-plugin',
            'icon' => 'bi-plugin',
            'permission' => 'tools.myplugin.view'
        ];

        return $menuItems;
    }

    /**
     * Main plugin functionality
     */
    public function execute(): string
    {
        return 'Hello from My Plugin!';
    }
}
```

---

## Support & Resources

- **Documentation**: https://docs.nexosupport.com/plugins
- **GitHub**: https://github.com/nexosupport/nexosupport
- **Community Forum**: https://community.nexosupport.com
- **Issue Tracker**: https://github.com/nexosupport/nexosupport/issues

---

**Document Version**: 1.0.0
**Last Updated**: 2025-11-12
**License**: MIT
