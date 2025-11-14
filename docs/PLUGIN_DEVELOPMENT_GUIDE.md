# NexoSupport Plugin Development Guide

**Version:** 1.0.0
**Last Updated:** November 14, 2025
**For:** NexoSupport 1.0+

---

## Table of Contents

1. [Introduction](#1-introduction)
2. [Quick Start](#2-quick-start)
3. [Plugin Structure](#3-plugin-structure)
4. [Manifest Configuration](#4-manifest-configuration)
5. [Dependencies and Conflicts](#5-dependencies-and-conflicts)
6. [Configuration System](#6-configuration-system)
7. [Hooks and Events](#7-hooks-and-events)
8. [Permissions](#8-permissions)
9. [Custom Routes](#9-custom-routes)
10. [Database Operations](#10-database-operations)
11. [Best Practices](#11-best-practices)
12. [Testing Your Plugin](#12-testing-your-plugin)
13. [Publishing](#13-publishing)

---

## 1. Introduction

### 1.1 What is a Plugin?

A NexoSupport plugin is a modular extension that adds functionality to the system without modifying core code. Plugins can:

- Add new features and tools
- Extend existing functionality
- Integrate with external services
- Customize the user interface
- Modify system behavior through hooks

### 1.2 Plugin Types

- **tools**: Utility and feature plugins
- **auth**: Authentication methods
- **themes**: Visual themes
- **reports**: Reporting and analytics
- **modules**: Major feature modules
- **integrations**: Third-party integrations

### 1.3 Development Requirements

- PHP >= 8.0
- Understanding of object-oriented PHP
- Basic knowledge of JSON
- Familiarity with MVC pattern (helpful)
- Git for version control (recommended)

---

## 2. Quick Start

### 2.1 Hello World Plugin

Let's create a minimal plugin in 5 minutes:

**Step 1: Create directory structure**

```bash
mkdir -p modules/plugins/tools/my-first-plugin
cd modules/plugins/tools/my-first-plugin
```

**Step 2: Create plugin.json**

```json
{
  "name": "My First Plugin",
  "slug": "my-first-plugin",
  "version": "1.0.0",
  "type": "tool",
  "description": "My first NexoSupport plugin!",
  "author": "Your Name",
  "author_email": "you@example.com",
  "license": "MIT",
  "requires": {
    "php": ">=8.0",
    "iser": ">=1.0.0"
  }
}
```

**Step 3: Create MyFirstPlugin.php**

```php
<?php

class MyFirstPlugin
{
    public static function init(): void
    {
        echo "My First Plugin is running!";
    }
}
```

**Step 4: Package as ZIP**

```bash
cd ..
zip -r my-first-plugin.zip my-first-plugin/
```

**Step 5: Install**

1. Go to `/admin/plugins/upload`
2. Upload `my-first-plugin.zip`
3. Click "Install"
4. Enable the plugin

Congratulations! You've created your first plugin!

---

## 3. Plugin Structure

### 3.1 Required Files

```
my-plugin/
├── plugin.json          (REQUIRED - Plugin manifest)
└── MyPlugin.php         (REQUIRED - Main class)
```

### 3.2 Recommended Structure

```
my-plugin/
├── plugin.json          # Manifest
├── MyPlugin.php         # Main class
├── README.md            # Documentation
├── LICENSE              # License file
├── config/              # Configuration files
│   └── defaults.php
├── controllers/         # HTTP controllers
│   └── AdminController.php
├── models/              # Data models
│   └── MyModel.php
├── views/               # Mustache templates
│   └── dashboard.mustache
├── assets/              # CSS, JS, images
│   ├── css/
│   ├── js/
│   └── images/
├── database/            # Database schemas
│   ├── install.xml
│   └── upgrade/
│       └── 2.0.0.xml
├── lang/                # Translations
│   ├── en/
│   └── es/
└── tests/               # Unit tests
    └── MyPluginTest.php
```

### 3.3 Naming Conventions

**Class Name**: `MyPlugin` (PascalCase, singular)
**File Name**: `MyPlugin.php` (matches class name)
**Slug**: `my-plugin` (kebab-case)
**Directory**: `my-plugin` (kebab-case, matches slug)

---

## 4. Manifest Configuration

### 4.1 Basic Manifest

The `plugin.json` file defines your plugin's metadata:

```json
{
  "name": "Plugin Display Name",
  "slug": "plugin-slug",
  "version": "1.0.0",
  "type": "tool",
  "description": "What your plugin does",
  "author": "Your Name",
  "author_email": "you@example.com",
  "author_url": "https://yourwebsite.com",
  "license": "MIT",
  "homepage": "https://plugin-site.com"
}
```

### 4.2 Requirements

Specify dependencies:

```json
{
  "requires": {
    "php": ">=8.0",
    "iser": ">=1.0.0",
    "plugins": [
      {
        "slug": "another-plugin",
        "version": ">=2.0.0"
      }
    ]
  }
}
```

**Version Operators:**
- `*` - Any version
- `1.0.0` - Exact version
- `>=1.0.0` - Greater than or equal
- `>1.0.0` - Greater than
- `<=1.0.0` - Less than or equal
- `<1.0.0` - Less than

### 4.3 Recommendations and Conflicts

```json
{
  "recommends": [
    "plugin-analytics",
    "plugin-cache"
  ],
  "conflicts_with": [
    "old-version-plugin",
    "incompatible-plugin"
  ]
}
```

### 4.4 Hooks

Register event handlers:

```json
{
  "hooks": {
    "init": "MyPlugin::init",
    "admin_menu": "MyPlugin::addAdminMenu",
    "user_login": "MyPlugin::onUserLogin",
    "before_save_ticket": "MyPlugin::beforeSaveTicket"
  }
}
```

### 4.5 Permissions

Define custom permissions:

```json
{
  "permissions": [
    {
      "name": "my_plugin.manage",
      "description": "Manage My Plugin settings"
    },
    {
      "name": "my_plugin.view",
      "description": "View My Plugin data"
    }
  ]
}
```

### 4.6 Custom Routes

```json
{
  "routes": [
    {
      "path": "/my-plugin/dashboard",
      "handler": "MyPlugin::showDashboard",
      "method": "GET"
    },
    {
      "path": "/my-plugin/save",
      "handler": "MyPlugin::saveData",
      "method": "POST"
    }
  ]
}
```

---

## 5. Dependencies and Conflicts

### 5.1 Declaring Dependencies

When your plugin requires another plugin:

```json
{
  "requires": {
    "plugins": [
      {
        "slug": "base-plugin",
        "version": ">=1.0.0"
      }
    ]
  }
}
```

**Auto-Installation:**
- System automatically installs dependencies
- Installation order determined by topological sort
- Circular dependencies are detected and prevented

### 5.2 Version Constraints

```json
{
  "requires": {
    "plugins": [
      {"slug": "exact-plugin", "version": "2.0.0"},
      {"slug": "min-plugin", "version": ">=1.5.0"},
      {"slug": "range-plugin", "version": ">=2.0.0"},
      {"slug": "any-plugin", "version": "*"}
    ]
  }
}
```

### 5.3 Declaring Conflicts

Prevent installation with incompatible plugins:

```json
{
  "conflicts_with": [
    "old-version",
    "alternative-implementation"
  ]
}
```

**Bidirectional Conflicts:**
Both plugins should declare the conflict for best results.

**Plugin A:**
```json
{"conflicts_with": ["plugin-b"]}
```

**Plugin B:**
```json
{"conflicts_with": ["plugin-a"]}
```

### 5.4 Recommendations

Suggest optional but beneficial plugins:

```json
{
  "recommends": [
    "analytics-plugin",
    "cache-plugin"
  ]
}
```

Users will see warnings if recommended plugins aren't installed.

---

## 6. Configuration System

### 6.1 Defining Configuration Schema

Add `config_schema` to `plugin.json`:

```json
{
  "config_schema": {
    "api_key": {
      "type": "string",
      "required": true,
      "label": "API Key",
      "description": "Your service API key",
      "placeholder": "Enter API key",
      "min": 32,
      "max": 128
    },
    "enable_feature": {
      "type": "bool",
      "default": true,
      "label": "Enable Advanced Features"
    },
    "max_items": {
      "type": "int",
      "default": 10,
      "min": 1,
      "max": 100,
      "label": "Items Per Page"
    }
  }
}
```

### 6.2 Field Types

**String/Text:**
```json
{
  "field_name": {
    "type": "string",
    "label": "Field Label",
    "description": "Help text",
    "placeholder": "Enter value",
    "default": "default value",
    "required": true,
    "min": 5,
    "max": 100,
    "pattern": "^[A-Za-z0-9]+$"
  }
}
```

**Integer/Number:**
```json
{
  "count": {
    "type": "int",
    "label": "Count",
    "default": 10,
    "min": 1,
    "max": 100,
    "step": 5
  }
}
```

**Boolean/Checkbox:**
```json
{
  "enabled": {
    "type": "bool",
    "label": "Enable Feature",
    "default": false
  }
}
```

**Email:**
```json
{
  "admin_email": {
    "type": "email",
    "label": "Admin Email",
    "required": true
  }
}
```

**URL:**
```json
{
  "webhook_url": {
    "type": "url",
    "label": "Webhook URL",
    "placeholder": "https://example.com/webhook"
  }
}
```

**Select Dropdown:**
```json
{
  "theme": {
    "type": "select",
    "label": "Theme",
    "options": ["light", "dark", "auto"],
    "default": "light"
  }
}
```

**Radio Buttons:**
```json
{
  "notification_method": {
    "type": "radio",
    "label": "Notification Method",
    "options": ["email", "sms", "webhook"],
    "default": "email"
  }
}
```

**Textarea:**
```json
{
  "custom_css": {
    "type": "textarea",
    "label": "Custom CSS",
    "rows": 6,
    "max": 5000,
    "placeholder": "/* Your CSS here */"
  }
}
```

**Password:**
```json
{
  "secret_key": {
    "type": "password",
    "label": "Secret Key",
    "min": 16,
    "max": 64
  }
}
```

### 6.3 Accessing Configuration in Code

```php
<?php

use ISER\Plugin\PluginConfigurator;
use ISER\Plugin\PluginManager;
use ISER\Core\Database\Database;

class MyPlugin
{
    private static ?PluginConfigurator $config = null;

    public static function init(): void
    {
        // Initialize configurator
        $db = Database::getInstance();
        $pm = new PluginManager($db);
        self::$config = new PluginConfigurator($db, $pm);

        // Load configuration
        $settings = self::$config->getConfig('my-plugin');

        // Use configuration
        $apiKey = $settings['api_key'] ?? null;
        $isEnabled = $settings['enable_feature'] ?? false;
        $maxItems = $settings['max_items'] ?? 10;

        if ($isEnabled && $apiKey) {
            self::initializeFeatures($apiKey, $maxItems);
        }
    }

    private static function initializeFeatures(string $apiKey, int $maxItems): void
    {
        // Use configuration values
    }
}
```

### 6.4 Validation

**Client-Side:**
- Automatically generated JavaScript validation
- HTML5 validation attributes
- Real-time error display

**Server-Side:**
- Type checking
- Range validation (min/max)
- Pattern matching (regex)
- Required field validation
- Options validation (for select/radio)

### 6.5 Configuration UI

Configuration form is automatically generated at:
```
/admin/plugins/{your-plugin-slug}/configure
```

Users can:
- Fill in configuration values
- See validation errors in real-time
- Save configuration
- Reset to defaults

---

## 7. Hooks and Events

### 7.1 Available Hooks

**System Hooks:**
- `init` - Plugin initialization
- `shutdown` - System shutdown
- `error` - Error occurred

**User Hooks:**
- `user_login` - User logged in
- `user_logout` - User logged out
- `user_created` - New user created
- `user_updated` - User updated
- `user_deleted` - User deleted

**Ticket Hooks:**
- `before_save_ticket` - Before saving ticket
- `after_save_ticket` - After saving ticket
- `ticket_created` - New ticket created
- `ticket_updated` - Ticket updated
- `ticket_closed` - Ticket closed

**Admin Hooks:**
- `admin_menu` - Add admin menu items
- `admin_dashboard` - Add dashboard widgets
- `admin_footer` - Add admin footer content

### 7.2 Registering Hook Handlers

**In plugin.json:**
```json
{
  "hooks": {
    "init": "MyPlugin::init",
    "user_login": "MyPlugin::onUserLogin",
    "before_save_ticket": "MyPlugin::beforeSaveTicket"
  }
}
```

**Handler Implementation:**
```php
<?php

class MyPlugin
{
    /**
     * Initialize plugin
     */
    public static function init(): void
    {
        // Setup code here
    }

    /**
     * Handle user login
     *
     * @param array $user User data
     */
    public static function onUserLogin(array $user): void
    {
        // Log login
        error_log("User {$user['username']} logged in");

        // Send notification
        self::sendLoginNotification($user);
    }

    /**
     * Modify ticket before save
     *
     * @param array $ticket Ticket data
     * @return array Modified ticket data
     */
    public static function beforeSaveTicket(array $ticket): array
    {
        // Add custom field
        $ticket['custom_field'] = 'value';

        // Validate
        if (empty($ticket['priority'])) {
            $ticket['priority'] = 'normal';
        }

        return $ticket;
    }
}
```

### 7.3 Hook Priorities

Control execution order:

```json
{
  "hooks": {
    "init": {
      "handler": "MyPlugin::init",
      "priority": 10
    }
  }
}
```

- Lower numbers execute first
- Default priority: 10
- Range: 0-100

### 7.4 Creating Custom Hooks

```php
use ISER\Core\Plugin\HookManager;

// Trigger hook
HookManager::trigger('my_custom_hook', [
    'data' => $myData,
    'context' => 'important'
]);

// Other plugins can register handlers
// in their plugin.json:
// "hooks": {"my_custom_hook": "OtherPlugin::handleMyHook"}
```

---

## 8. Permissions

### 8.1 Defining Permissions

```json
{
  "permissions": [
    {
      "name": "my_plugin.admin",
      "description": "Administer My Plugin"
    },
    {
      "name": "my_plugin.view",
      "description": "View My Plugin data"
    },
    {
      "name": "my_plugin.edit",
      "description": "Edit My Plugin data"
    },
    {
      "name": "my_plugin.delete",
      "description": "Delete My Plugin data"
    }
  ]
}
```

### 8.2 Checking Permissions

```php
<?php

use ISER\User\UserManager;
use ISER\Core\Database\Database;

class MyPlugin
{
    public static function showAdminPanel(): void
    {
        // Get current user
        $db = Database::getInstance();
        $userManager = new UserManager($db);
        $user = $userManager->getUserById($_SESSION['user_id']);

        // Check permission
        if (!self::hasPermission($user, 'my_plugin.admin')) {
            die('Access denied');
        }

        // Show admin panel
        self::renderAdminPanel();
    }

    private static function hasPermission(array $user, string $permission): bool
    {
        // Check if user has permission
        // Implementation depends on your permission system
        return in_array($permission, $user['permissions'] ?? []);
    }
}
```

---

## 9. Custom Routes

### 9.1 Defining Routes

```json
{
  "routes": [
    {
      "path": "/my-plugin/dashboard",
      "handler": "MyPlugin::showDashboard",
      "method": "GET",
      "auth_required": true
    },
    {
      "path": "/my-plugin/api/data",
      "handler": "MyPlugin::getData",
      "method": "GET"
    },
    {
      "path": "/my-plugin/api/save",
      "handler": "MyPlugin::saveData",
      "method": "POST",
      "auth_required": true
    }
  ]
}
```

### 9.2 Route Handlers

```php
<?php

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class MyPlugin
{
    /**
     * Show dashboard
     */
    public static function showDashboard(ServerRequestInterface $request): ResponseInterface
    {
        // Get query parameters
        $params = $request->getQueryParams();
        $page = $params['page'] ?? 1;

        // Prepare data
        $data = self::getDashboardData($page);

        // Render view
        $renderer = new \ISER\Core\View\MustacheRenderer();
        $html = $renderer->render('my-plugin/dashboard', $data);

        // Return response
        $response = new \GuzzleHttp\Psr7\Response();
        $response->getBody()->write($html);
        return $response;
    }

    /**
     * Get data (API endpoint)
     */
    public static function getData(ServerRequestInterface $request): ResponseInterface
    {
        $data = ['items' => self::fetchItems()];

        $response = new \GuzzleHttp\Psr7\Response();
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Save data (API endpoint)
     */
    public static function saveData(ServerRequestInterface $request): ResponseInterface
    {
        // Get POST data
        $body = $request->getBody()->getContents();
        $data = json_decode($body, true);

        // Validate
        if (!self::validateData($data)) {
            return self::jsonError('Invalid data', 400);
        }

        // Save
        self::save($data);

        // Return success
        return self::jsonSuccess(['saved' => true]);
    }
}
```

---

## 10. Database Operations

### 10.1 Database Schema

Create `database/install.xml`:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<schema>
    <table name="my_plugin_data">
        <field name="id" type="int" auto_increment="true">
            <key>primary</key>
        </field>
        <field name="user_id" type="int">
            <key>index</key>
        </field>
        <field name="data" type="text" />
        <field name="created_at" type="int" />
        <field name="updated_at" type="int" />
    </table>
</schema>
```

### 10.2 Database Queries

```php
<?php

use ISER\Core\Database\Database;

class MyPlugin
{
    private static Database $db;

    public static function init(): void
    {
        self::$db = Database::getInstance();
    }

    /**
     * Insert data
     */
    public static function insertData(array $data): int
    {
        return self::$db->insert('my_plugin_data', [
            'user_id' => $data['user_id'],
            'data' => json_encode($data['content']),
            'created_at' => time(),
            'updated_at' => time()
        ]);
    }

    /**
     * Get data by ID
     */
    public static function getData(int $id): ?array
    {
        return self::$db->selectOne('my_plugin_data', ['id' => $id]);
    }

    /**
     * Update data
     */
    public static function updateData(int $id, array $data): int
    {
        return self::$db->update('my_plugin_data', [
            'data' => json_encode($data['content']),
            'updated_at' => time()
        ], ['id' => $id]);
    }

    /**
     * Delete data
     */
    public static function deleteData(int $id): int
    {
        return self::$db->delete('my_plugin_data', ['id' => $id]);
    }

    /**
     * Get all data for user
     */
    public static function getUserData(int $userId): array
    {
        return self::$db->select('my_plugin_data',
            ['user_id' => $userId],
            'created_at DESC'
        );
    }
}
```

---

## 11. Best Practices

### 11.1 Code Quality

**Use Namespaces:**
```php
<?php

namespace MyCompany\MyPlugin;

class MyPlugin
{
    // Your code
}
```

**Type Hints:**
```php
public static function processData(array $data, int $userId): array
{
    // Type-safe code
}
```

**Error Handling:**
```php
try {
    $result = self::riskyOperation();
} catch (\Exception $e) {
    Logger::error('Operation failed', [
        'error' => $e->getMessage(),
        'plugin' => 'my-plugin'
    ]);
    return ['success' => false, 'error' => $e->getMessage()];
}
```

### 11.2 Security

**Validate Input:**
```php
public static function saveData(array $data): bool
{
    // Validate
    if (empty($data['name']) || !is_string($data['name'])) {
        throw new \InvalidArgumentException('Invalid name');
    }

    // Sanitize
    $data['name'] = filter_var($data['name'], FILTER_SANITIZE_STRING);

    // Save
    return self::$db->insert('my_table', $data);
}
```

**Escape Output:**
```php
// In Mustache templates
{{name}}  // Auto-escaped
{{{raw_html}}}  // Unescaped (use carefully!)
```

**SQL Injection Prevention:**
```php
// Use prepared statements (Database class does this automatically)
$results = self::$db->select('table', ['id' => $userInput]);
```

**CSRF Protection:**
```php
// Verify CSRF token
if (!self::verifyCsrfToken($_POST['csrf_token'])) {
    return ['error' => 'Invalid CSRF token'];
}
```

### 11.3 Performance

**Cache Results:**
```php
private static ?array $cache = null;

public static function getExpensiveData(): array
{
    if (self::$cache !== null) {
        return self::$cache;
    }

    self::$cache = self::fetchFromDatabase();
    return self::$cache;
}
```

**Lazy Loading:**
```php
private static function loadDependency(): void
{
    if (self::$dependency === null) {
        self::$dependency = new HeavyDependency();
    }
}
```

**Database Optimization:**
```php
// Use indexes
// Limit results
$data = self::$db->select('table', [], 'id DESC', 100, 0);

// Batch operations
self::$db->beginTransaction();
foreach ($items as $item) {
    self::$db->insert('table', $item);
}
self::$db->commit();
```

### 11.4 Logging

```php
use ISER\Core\Utils\Logger;

Logger::info('Plugin initialized', ['plugin' => 'my-plugin']);
Logger::warning('Configuration missing', ['field' => 'api_key']);
Logger::error('API request failed', ['error' => $e->getMessage()]);
Logger::system('System event occurred', ['context' => 'important']);
```

---

## 12. Testing Your Plugin

### 12.1 Manual Testing

**Test Checklist:**
- [ ] Installation succeeds
- [ ] Dependencies install automatically
- [ ] Configuration form displays correctly
- [ ] Configuration saves and loads
- [ ] Validation works (client & server)
- [ ] Hooks fire correctly
- [ ] Permissions enforced
- [ ] Custom routes work
- [ ] Database operations succeed
- [ ] No PHP errors in logs
- [ ] Enable/disable works
- [ ] Uninstall cleans up properly
- [ ] No conflicts with other plugins

### 12.2 Test Different Scenarios

**Dependency Testing:**
1. Install without dependencies → Should auto-install
2. Install with wrong version → Should fail with error
3. Try to uninstall dependency → Should fail if has dependents

**Conflict Testing:**
1. Install conflicting plugin → Should fail
2. Enable conflicting plugin → Should fail
3. Resolve conflict → Should work

**Configuration Testing:**
1. Save valid config → Should succeed
2. Save invalid config → Should show errors
3. Reset config → Should restore defaults

### 12.3 Unit Tests (Optional)

Create `tests/MyPluginTest.php`:

```php
<?php

use PHPUnit\Framework\TestCase;

class MyPluginTest extends TestCase
{
    public function testInitialization()
    {
        MyPlugin::init();
        $this->assertTrue(true); // Replace with real assertions
    }

    public function testConfigValidation()
    {
        $valid = MyPlugin::validateConfig([
            'api_key' => 'test123',
            'max_items' => 10
        ]);

        $this->assertTrue($valid['valid']);
    }
}
```

Run tests:
```bash
vendor/bin/phpunit tests/
```

---

## 13. Publishing

### 13.1 Prepare for Release

**1. Version Your Plugin:**
```json
{
  "version": "1.0.0"
}
```

**2. Write Documentation:**
- README.md with features and usage
- CHANGELOG.md with version history
- LICENSE file

**3. Clean Up:**
- Remove development files
- Remove test data
- Remove debug code

**4. Test Thoroughly:**
- Run all manual tests
- Test on clean installation
- Test upgrades from previous version

### 13.2 Create Release Package

```bash
# Create clean directory
mkdir my-plugin-1.0.0
cp -r my-plugin/* my-plugin-1.0.0/

# Remove unnecessary files
rm -rf my-plugin-1.0.0/tests
rm my-plugin-1.0.0/.git*

# Create ZIP
zip -r my-plugin-1.0.0.zip my-plugin-1.0.0/

# Verify ZIP contents
unzip -l my-plugin-1.0.0.zip
```

### 13.3 Distribution

**Options:**
1. **Direct Distribution**: Share ZIP file directly
2. **GitHub Releases**: Upload to GitHub releases
3. **Plugin Marketplace**: Submit to NexoSupport marketplace (when available)
4. **Private Repository**: Host on your own server

### 13.4 Versioning

Follow Semantic Versioning (semver):

- **MAJOR** (1.x.x): Breaking changes
- **MINOR** (x.1.x): New features, backward compatible
- **PATCH** (x.x.1): Bug fixes

Examples:
- `1.0.0` - Initial release
- `1.1.0` - Added new feature
- `1.1.1` - Fixed bug
- `2.0.0` - Breaking changes

### 13.5 Changelog

Maintain `CHANGELOG.md`:

```markdown
# Changelog

## [1.1.0] - 2025-11-15
### Added
- New dashboard widget
- Export to CSV feature

### Fixed
- Bug in date formatting
- Memory leak in data processing

## [1.0.0] - 2025-11-01
### Added
- Initial release
- Basic functionality
- Configuration UI
```

---

## Appendix A: Complete Example Plugin

See `test-plugin-config` for a comprehensive example demonstrating:
- All 12 configuration field types
- Dependency management
- Conflict detection
- Hooks integration
- Permission management
- Custom routes
- Database operations
- Best practices

Location: `/modules/plugins/tools/test-plugin-config/`

---

## Appendix B: Quick Reference

### Manifest Fields
| Field | Required | Description |
|-------|----------|-------------|
| name | ✅ | Plugin display name |
| slug | ✅ | Unique identifier |
| version | ✅ | Version number (semver) |
| type | ✅ | Plugin type |
| description | ✅ | Short description |
| author | ❌ | Author name |
| license | ❌ | License type |
| requires | ❌ | Dependencies |
| config_schema | ❌ | Configuration fields |
| hooks | ❌ | Event handlers |
| permissions | ❌ | Custom permissions |
| routes | ❌ | Custom routes |

### Hook Names
- `init`, `shutdown`, `error`
- `user_login`, `user_logout`, `user_created`
- `before_save_ticket`, `after_save_ticket`
- `admin_menu`, `admin_dashboard`

### Field Types
- `string`, `text`, `textarea`
- `int`, `number`
- `bool`, `checkbox`
- `email`, `url`, `password`
- `select`, `radio`

---

## Appendix C: Resources

**Documentation:**
- Plugin System Specification: `/PLUGIN_SYSTEM_SPECIFICATION.md`
- Integration Testing Guide: `/docs/PLUGIN_INTEGRATION_TESTING.md`
- API Documentation: `/docs/API.md`

**Example Plugins:**
- test-plugin-a: Dependencies example
- test-plugin-b: Base plugin example
- test-plugin-config: Configuration example
- test-plugin-conflict: Conflict detection example

**Tools:**
- Plugin Generator: `/scripts/generate-plugin.sh` (if available)
- Validator: `/scripts/validate-plugin.php` (if available)

**Community:**
- GitHub Issues: Report bugs and request features
- Forum: Get help from other developers
- Documentation: Contribute improvements

---

## Conclusion

You now have everything needed to create powerful plugins for NexoSupport!

**Next Steps:**
1. Create your first plugin using the Quick Start guide
2. Study the example plugins
3. Read the Plugin System Specification for advanced features
4. Join the community and share your creations

**Happy Plugin Development!**

---

**Document End**

**Version:** 1.0.0
**Last Updated:** November 14, 2025
**Contributors:** ISER Development Team
