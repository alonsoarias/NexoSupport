# COMPREHENSIVE CODEBASE ANALYSIS - NexoSupport

**Project:** NexoSupport Authentication System
**Analysis Date:** 2025-11-13
**Analyst:** Claude Code (Comprehensive Refactoring Initiative)
**Version:** 1.0.0
**Status:** In Development

---

## EXECUTIVE SUMMARY

### Codebase Metrics
- **Total PHP Files:** 190 files
- **Total Mustache Templates:** 50+ templates
- **Core Code Size:** ~12,200 lines
- **Total Estimated Code:** ~20,000-25,000 lines
- **Modules Count:** 10+ major modules
- **Languages Supported:** Spanish (es), English (en)

### Architecture Overview
- **Style:** Modular MVC-inspired architecture
- **Autoloading:** PSR-4 compliant (Composer + custom autoloader)
- **Database:** Multi-driver support (MySQL, PostgreSQL, SQLite)
- **Templating:** Mustache templates
- **Authentication:** JWT-based sessions
- **Authorization:** RBAC (Role-Based Access Control)
- **Plugin System:** ~75% implemented
- **Internationalization:** Infrastructure 90% complete, usage 40%

### Health Assessment
- **Overall Score:** 7/10
- **Architecture Quality:** 8/10
- **Code Duplication:** 5/10 (multiple duplications found)
- **Test Coverage:** 2/10 (~5% coverage)
- **Documentation:** 6/10
- **Security:** 8/10 (JWT, RBAC, prepared statements)
- **i18n Compliance:** 4/10 (infrastructure good, usage poor)

### Critical Issues Identified
1. ❌ **Case-sensitivity bug:** `/modules/Report/` vs `/modules/report/`
2. ❌ **Dead code:** `/core/Log/Logger.php` (unused)
3. ⚠️ **Router duplication:** `/core/Router/` vs `/core/Routing/`
4. ⚠️ **Renderer duplication:** `/core/Render/` vs `/core/View/`
5. ⚠️ **RBAC duplication:** `/modules/Role/` vs `/modules/Roles/`
6. ⚠️ **Hardcoded strings:** 500-1000 strings not internationalized

---

## 1. CORE SYSTEM ARCHITECTURE

### 1.1 Directory Structure

```
/home/user/NexoSupport/
├── core/                    # Core framework (37 PHP files)
│   ├── Autoloader.php
│   ├── Bootstrap.php        # System initialization
│   ├── Config/              # Configuration management
│   ├── Database/            # DB abstraction layer
│   ├── Http/                # PSR-7 HTTP layer
│   ├── I18n/                # Internationalization
│   ├── Interfaces/          # Core interfaces
│   ├── Log/                 # ⚠️ UNUSED Logger
│   ├── Middleware/          # Auth, Admin, Permission middleware
│   ├── Plugin/              # Plugin system core
│   ├── Render/              # ⚠️ Legacy renderer
│   ├── Router/              # ⚠️ Legacy router
│   ├── Routing/             # ✅ Active router
│   ├── Session/             # JWT session management
│   ├── Utils/               # Helpers, Logger, Mailer, etc.
│   └── View/                # ✅ Active renderer
│
├── modules/                 # Application modules (89 PHP files)
│   ├── Admin/               # Admin panel & tools
│   ├── Auth/                # Authentication providers
│   ├── Controllers/         # 19 controller files
│   ├── Permission/          # ⚠️ Simple permission system
│   ├── Plugin/              # Plugin management
│   ├── Report/              # ⚠️ Case conflict
│   ├── report/              # ⚠️ Case conflict
│   ├── Role/                # ⚠️ Legacy role system
│   ├── Roles/               # ✅ Full RBAC system
│   ├── Theme/               # Theme system + ISER theme
│   ├── User/                # User management
│   └── plugins/             # Installed plugins directory
│
├── database/
│   └── schema/
│       └── schema.xml       # Complete DB schema (942 lines)
│
├── resources/
│   ├── lang/                # Translation files
│   │   ├── en/              # 21 English files
│   │   └── es/              # 21 Spanish files
│   └── views/               # Mustache templates (50+ files)
│
├── public_html/             # Web root
│   ├── index.php            # Main entry point
│   ├── install.php          # Installer entry
│   └── assets/              # CSS, JS, images
│
├── install/                 # Installation system
│   ├── index.php
│   ├── stages/              # 7 installation stages
│   └── assets/
│
├── tests/                   # Test suite (limited)
│   ├── Unit/
│   └── Integration/
│
└── var/
    ├── cache/               # Cache directory
    └── logs/                # Application logs
```

### 1.2 Bootstrap Process

**File:** `core/Bootstrap.php` (560 lines)

**Initialization Sequence:**
1. Load configuration (ConfigManager)
2. Setup environment (Environment class)
3. Initialize logging (Monolog integration)
4. Setup autoloader (PSR-4)
5. Initialize database (PDOConnection + Database)
6. Initialize JWT session (JWTSession)
7. Initialize i18n (Translator + LocaleDetector)
8. **Initialize plugin system** (HookManager + PluginLoader)
9. Initialize router (Router class)
10. Discover and register modules

**Dependencies:**
- ConfigManager (loads .env and config files)
- Environment (validates PHP version, extensions)
- PDOConnection (multi-driver DB connection)
- JWTSession (token-based authentication)
- Translator (i18n system)
- HookManager (plugin hooks)
- Router (HTTP routing)

---

## 2. DATABASE ARCHITECTURE

### 2.1 Schema Overview

**File:** `/database/schema/schema.xml`
**Total Tables:** 23 tables
**Lines:** 942 lines of XML

**Table Categories:**

#### Core Configuration (1 table)
- `config` - Key-value configuration storage (normalized)
  - Groups: app, security, reports, theme
  - Types: string, int, bool, json

#### User Management (8 tables)
- `users` - Main user table
- `user_profiles` - 1:1 profile information
- `user_preferences` - EAV user preferences
- `user_roles` - Many-to-many user-role assignment
- `account_security` - Security state (login attempts, lockout)
- `login_history` - Complete login tracking
- `login_attempts` - Failed login attempts
- `password_reset_tokens` - Password reset tokens

#### RBAC System (4 tables)
- `roles` - User roles (admin, moderator, user, guest)
- `permissions` - Granular permissions (35 permissions across 9 modules)
- `role_permissions` - Many-to-many role-permission assignment

#### Session & Auth (3 tables)
- `sessions` - User sessions
- `jwt_tokens` - JWT token tracking
- `user_mfa` - Multi-factor authentication

#### Logging & Audit (2 tables)
- `logs` - System logs (Monolog integration)
- `audit_log` - Audit trail

#### Plugin System (5 tables)
- `plugins` - Installed plugins registry
- `plugin_dependencies` - Plugin dependency graph
- `plugin_hooks` - Plugin hook registrations
- `plugin_settings` - Plugin configuration
- `plugin_assets` - Plugin static assets

#### Email Queue (1 table)
- `email_queue` - Asynchronous email delivery

### 2.2 Normalization Analysis

**Current Status:** Mostly in 3NF (Third Normal Form)

**Well-Normalized:**
✅ User information separated across tables (users, user_profiles, user_preferences)
✅ Security data separated (account_security, login_attempts)
✅ Password reset tokens in separate table
✅ Login history tracked separately
✅ Plugin system fully normalized
✅ EAV pattern for extensible preferences

**Potential Issues:**
⚠️ `config` table mixes different configuration categories (acceptable for key-value)
⚠️ `user_mfa` has method-specific columns (secret, backup_codes, phone)
⚠️ `sessions` stores payload as TEXT (serialized data)

**Recommendations:**
- Consider separating MFA methods into separate tables if more factors added
- Sessions table is acceptable for current use case
- Config table normalization is appropriate for settings storage

**Overall Assessment:** Database schema is **well-designed and properly normalized** ✅

---

## 3. MODULE ANALYSIS

### 3.1 Core Modules

#### Admin Module (`/modules/Admin/`)

**Files:** 5 main classes + 3 sub-tools
**Responsibilities:**
- Admin dashboard
- System settings management
- Plugin management
- Reporting tools
- Administrative tools

**Sub-Tools:**
1. **MFA Tool** (`/Admin/Tool/Mfa/`)
   - Multi-factor authentication implementation
   - 4 factors: TOTP, Email, SMS (phone), Backup codes
   - Database tables: `user_mfa`
   - Classes: MfaManager, MfaUserConfig, 4 factor implementations

2. **Upload User Tool** (`/Admin/Tool/UploadUser/`)
   - Bulk user import functionality
   - CSV/Excel upload support
   - Validation and error reporting

3. **Install Addon Tool** (`/Admin/Tool/InstallAddon/`)
   - Plugin installation UI
   - Upload .zip packages
   - Validation and installation

**Templates:** 6 Mustache files in `/modules/Admin/templates/`

---

#### Auth Module (`/modules/Auth/`)

**Structure:**
- PasswordResetTokenManager.php - Token management
- `/Auth/Manual/` - Manual (local) authentication
  - AuthManual.php - Authentication implementation
  - LoginManager.php - Login flow management
  - Database install script
  - Templates for login forms

**Extensibility:** Designed to support multiple auth providers
- Current: Manual (local database)
- Future: OAuth, LDAP, SAML, Social login

---

#### Controllers Module (`/modules/Controllers/`)

**Total Controllers:** 19 files

**Categories:**

**Core Navigation:**
- HomeController.php - Homepage
- AdminController.php - Admin panel entry

**User Management:**
- UserManagementController.php - User CRUD
- UserProfileController.php - Profile editing
- UserPreferencesController.php - User settings

**RBAC:**
- RoleController.php - Role management
- PermissionController.php - Permission management

**Security & Audit:**
- LoginHistoryController.php - Login tracking
- AuditLogController.php - Audit trail viewer
- PasswordResetController.php - Password reset flow
- AuthController.php - Authentication endpoints

**Admin Tools:**
- AdminSettingsController.php - System settings
- AdminBackupController.php - Database backups
- AdminEmailQueueController.php - Email queue management
- LogViewerController.php - System logs viewer

**Other:**
- SearchController.php - Global search
- ThemePreviewController.php - Theme previews
- AppearanceController.php - Theme settings
- I18nApiController.php - Translation API

**Common Pattern:** All controllers use `View\MustacheRenderer` for rendering

**Trait:** NavigationTrait.php - Shared navigation building logic

---

#### User Module (`/modules/User/`)

**Files:** 7 classes

**Core Classes:**
- UserManager.php - User CRUD operations
- UserProfile.php - Profile management
- UserAvatar.php - Avatar handling
- UserSearch.php - User search functionality

**Additional Managers:**
- UserPreferencesManager.php - Preference management
- AccountSecurityManager.php - Security settings
- LoginHistoryManager.php - Login tracking

**Database:** Has `db/install.php` for initial setup

---

#### Theme Module (`/modules/Theme/`)

**Structure:**
- ThemeConfigurator.php - Theme configuration
- `/Theme/Iser/` - ISER corporate theme (default)

**ISER Theme Components:**
- ThemeIser.php - Main theme class
- ThemeRenderer.php - Rendering logic
- ThemeLayouts.php - Layout management
- ThemeNavigation.php - Navigation building
- ThemeAssets.php - Asset management

**Configuration:**
- `config/theme_settings.php` - Theme settings
- `config/color_palette.php` - Color schemes
- `config/layout_config.php` - Layout options
- `config/navigation_config.php` - Navigation structure

**Templates:** Extensive template library
- Layouts: base, admin, dashboard, login, popup, fullwidth
- Pages: home, dashboard, profile
- Partials: header, footer, sidebar, navbar, breadcrumb, alerts, modals
- Components: cards, forms, tables

**Assets:**
- CSS files in `/assets/css/`
- JavaScript in `/assets/js/`

**Testing:** Only module with tests ✅
- ThemeIserTest.php
- ThemeRendererTest.php
- ThemeAssetsTest.php
- ThemeIntegrationTest.php

**Localization:** Has `lang/es/theme_iser.php`

---

#### Plugin Module (`/modules/Plugin/`)

**Files:** 3 classes

1. **PluginManager.php**
   - CRUD operations on `plugins` table
   - Enable/disable plugins
   - Uninstall plugins
   - Query plugin metadata

2. **PluginLoader.php** (640 lines - largest class)
   - Discover plugins in `/modules/plugins/`
   - Validate plugin manifests (plugin.json)
   - Load enabled plugins
   - Initialize plugin hooks
   - Dependency resolution (partial)
   - Error handling and logging

3. **PluginInstaller.php**
   - Install plugin packages (.zip)
   - Validate plugin structure
   - Extract and copy files
   - Run installation scripts
   - Register in database
   - Handle installation errors

**Plugin Types Supported:**
- `tools` - Administrative tools
- `auth` - Authentication providers
- `themes` - Visual themes
- `reports` - Reporting modules
- `modules` - Feature modules
- `integrations` - External integrations

**Example Plugin:** `/modules/plugins/tools/hello-world/`
- Demonstrates plugin structure
- Includes Plugin.php, plugin.json, lang files, assets

---

#### Report Module (`/modules/Report/`)

**Structure:**
- `/Report/Log/` - Logging and reporting
  - ReportLog.php - Report generation
  - SecurityReport.php - Security-focused reports
  - LogManager.php - Log management
  - LogExporter.php - Export logs (CSV, PDF)
  - `/Handlers/` - DatabaseHandler, SecurityAlertHandler

**Functionality:**
- Generate system reports
- Export logs in multiple formats
- Security audit reports
- Activity tracking

**⚠️ Case Issue:** Also exists as `/modules/report/` (lowercase) - **CRITICAL BUG**

---

### 3.2 Duplicate Modules (To Be Resolved)

#### Role vs Roles

**`/modules/Role/`**
- Single file: RoleManager.php
- Namespace: `ISER\Role`
- 204 lines
- Simple CRUD operations
- Methods: getRoles, getRole, create, update, delete, assignPermission
- **Status:** Legacy implementation

**`/modules/Roles/`**
- 4 files: RoleManager.php, PermissionManager.php, RoleAssignment.php, RoleContext.php
- Namespace: `ISER\Roles`
- Full Moodle-inspired capability system
- Context-based permissions
- Capabilities: CAP_ALLOW, CAP_PREVENT, CAP_PROHIBIT
- Database install scripts
- **Status:** Active, full-featured RBAC

**Decision Needed:** Delete `/modules/Role/`, migrate to `/modules/Roles/`

---

#### Permission Module

**`/modules/Permission/`**
- Single file: PermissionManager.php
- Namespace: `ISER\Permission`
- 183 lines
- Simple permission CRUD
- Methods: getPermissions, create, update, delete, userHasPermission
- **Status:** Simplified version

**Conflicts with:** `/modules/Roles/PermissionManager.php` (more comprehensive)

**Decision Needed:** Choose one approach - simple or complex

---

## 4. INTERNATIONALIZATION (i18n) SYSTEM

### 4.1 Infrastructure ✅

**Core Implementation:**
- `/core/I18n/Translator.php` (275 lines)
  - Singleton pattern
  - Locale management
  - Translation loading (PHP arrays)
  - Variable replacement: `__('welcome_message', ['name' => 'John'])`
  - Fallback locale support
  - Available locales: en, es

- `/core/I18n/LocaleDetector.php`
  - Auto-detect from:
    1. User preferences (database)
    2. Browser Accept-Language header
    3. System default
  - Apply detected locale automatically

**Helper Functions:**
- `__($key, $params = [])` - Translate key
- `trans_choice($key, $count, $params = [])` - Pluralization (planned)

**View Integration:**
- Mustache helper: `{{#__}}translation.key{{/__}}`
- Locale-aware date/time formatting
- Number formatting by locale

### 4.2 Translation Files

**Location:** `/resources/lang/{locale}/`

**English (en/):** 21 files
- admin.php, auth.php, common.php, dashboard.php
- users.php, roles.php, permissions.php, settings.php
- plugins.php, theme.php, search.php, profile.php
- security.php, backup.php, audit.php, errors.php
- validation.php, logs.php, reports.php
- email_queue.php, installer.php

**Spanish (es/):** 21 files (mirrors English)

**Module-Specific:**
- `/modules/Theme/Iser/lang/es/theme_iser.php`
- `/modules/plugins/tools/hello-world/lang/en/` and `/es/`

**Total Translation Keys:** Estimated 500-800 keys defined

### 4.3 Critical Issue: Hardcoded Strings

**Problem:** Templates have hardcoded Spanish strings instead of using translation keys

**Example from** `/resources/views/admin/users/index.mustache`:

```mustache
<!-- WRONG (Hardcoded Spanish) -->
<h1>Gestión de Usuarios</h1>
<p>Administra usuarios, roles y permisos del sistema</p>
<button>Nuevo Usuario</button>
<input placeholder="Buscar por usuario, email o nombre...">

<!-- CORRECT (Should be) -->
<h1>{{#__}}admin.users_management{{/__}}</h1>
<p>{{#__}}admin.users_description{{/__}}</p>
<button>{{#__}}common.new_user{{/__}}</button>
<input placeholder="{{#__}}common.search_placeholder{{/__}}">
```

**Affected Areas:**
- Admin templates: `/modules/Admin/templates/` (6 files)
- Resource views: `/resources/views/` (60+ files)
- Theme templates: `/modules/Theme/Iser/templates/` (30+ files)

**Estimated Count:** 500-1000 hardcoded strings across all templates

**Impact:**
- English users see Spanish text
- Cannot switch languages dynamically
- Violates i18n best practices

**Recommendation:**
1. Create string extraction tool
2. Systematically replace hardcoded strings
3. Add missing keys to translation files
4. Test with both locales

---

## 5. PLUGIN SYSTEM STATUS

### 5.1 Implementation Status: ~75% Complete ✅

**What's Implemented:**

#### Core Infrastructure ✅
- `/core/Plugin/HookManager.php` (318 lines)
  - Hook registration: `HookManager::register($hookName, $callback, $priority)`
  - Hook execution: `HookManager::run($hookName, $data)`
  - Priority-based execution order
  - Multiple listeners per hook
  - Singleton pattern

- `/core/Plugin/PluginInterface.php`
  - Contract for plugin classes
  - Methods: init(), activate(), deactivate(), uninstall()

#### Module Infrastructure ✅
- `/modules/Plugin/PluginManager.php`
  - Database CRUD for `plugins` table
  - Enable/disable operations
  - Metadata retrieval
  - Installation status tracking

- `/modules/Plugin/PluginLoader.php` (640 lines)
  - Auto-discovery in `/modules/plugins/`
  - Manifest validation (plugin.json)
  - Dependency checking
  - Load enabled plugins on bootstrap
  - Register hooks
  - Error handling with detailed logging

- `/modules/Plugin/PluginInstaller.php`
  - Upload and extract .zip packages
  - Validate plugin structure
  - Copy files to correct location
  - Run installation scripts
  - Register in database

#### Admin UI ✅
- `/modules/Admin/AdminPlugins.php` - Controller
- `/modules/Admin/Tool/InstallAddon/` - Installation interface
- `/resources/views/admin/plugins/` - Templates
  - index.mustache - List all plugins
  - show.mustache - Plugin details
  - upload.mustache - Upload new plugin

#### Database Tables ✅
- `plugins` - Registry (type, slug, name, version, enabled, etc.)
- `plugin_dependencies` - Dependency graph
- `plugin_hooks` - Hook registrations
- `plugin_settings` - Configuration storage
- `plugin_assets` - Static asset registry

#### Example Plugin ✅
- `/modules/plugins/tools/hello-world/`
  - Plugin.php - Main class implementing PluginInterface
  - plugin.json - Manifest (name, version, type, author, requires)
  - lang/en/ and lang/es/ - Translations
  - assets/ - CSS, JS
  - README.md - Documentation

**Supported Plugin Types:**
- `tools` - Administrative tools (MFA, backups, etc.)
- `auth` - Authentication providers (OAuth, LDAP, SAML)
- `themes` - Visual themes
- `reports` - Custom reports
- `modules` - Feature modules
- `integrations` - External service integrations

### 5.2 What's Missing ⚠️

1. **Dependency Resolution**
   - Plugin A requires Plugin B
   - Automatic installation of dependencies
   - Version compatibility checking

2. **Plugin Update System**
   - Check for updates
   - Download and install updates
   - Migration/upgrade scripts

3. **Plugin Configuration UI**
   - Plugin-specific settings pages
   - Form generation from config schema
   - Settings validation

4. **Plugin Marketplace**
   - Remote repository integration
   - Browse available plugins
   - One-click installation

5. **Plugin Developer Tools**
   - Plugin scaffolding CLI
   - Development mode
   - Debug logging

6. **Comprehensive Documentation**
   - Plugin API reference
   - Hook catalog
   - Development guide

### 5.3 Plugin Architecture

**Directory Structure for Plugins:**

```
/modules/plugins/{type}/{plugin-slug}/
├── Plugin.php                # Main class (implements PluginInterface)
├── plugin.json               # Manifest
├── install.xml               # Database schema (optional)
├── routes.php                # Additional routes (optional)
├── lang/                     # Translations
│   ├── en/
│   └── es/
├── views/                    # Templates (optional)
├── assets/                   # Static files
│   ├── css/
│   ├── js/
│   └── images/
├── config/                   # Configuration files
├── src/                      # Additional PHP classes
└── README.md
```

**Manifest Structure (plugin.json):**

```json
{
  "name": "My Plugin",
  "slug": "my-plugin",
  "type": "tools",
  "version": "1.0.0",
  "description": "Plugin description",
  "author": "Developer Name",
  "author_url": "https://example.com",
  "requires": {
    "nexosupport": ">=1.0.0",
    "php": ">=8.1"
  },
  "depends_on": ["another-plugin"],
  "provides": ["feature-name"]
}
```

---

## 6. ROUTING SYSTEM

### 6.1 Current Implementation

**Active Router:** `/core/Routing/Router.php` (354 lines) ✅
- Namespace: `ISER\Core\Routing`
- Used in: `public_html/index.php` (main entry point)
- Features:
  - PSR-7 compatible (Request/Response)
  - Route groups with middleware
  - Named routes
  - Route parameters: `/user/{id}`
  - HTTP method routing: GET, POST, PUT, DELETE, PATCH
  - Middleware pipeline
  - 404 and error handlers
- **Status:** Production router

**Legacy Router:** `/core/Router/Router.php` (460 lines) ⚠️
- Namespace: `ISER\Core\Router`
- Used in: `core/Bootstrap.php` only (for fallback handlers)
- Features: Similar but older implementation
- **Status:** Deprecated, should be removed

**Additional File:** `/core/Router/Route.php` (part of legacy system)

### 6.2 Route Registration Pattern

**Example Routes (from public_html/index.php):**

```php
// Home
$router->get('/', [HomeController::class, 'index']);

// Auth
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout']);

// Admin (with middleware)
$router->group('/admin', function($router) {
    $router->get('/users', [UserController::class, 'index']);
    $router->post('/users', [UserController::class, 'store']);
}, [AuthMiddleware::class, AdminMiddleware::class]);

// Dynamic routes
$router->get('/user/{id}', [UserController::class, 'show']);
```

### 6.3 Middleware System

**Available Middleware:**
1. `AuthMiddleware` - Requires authentication
2. `AdminMiddleware` - Requires admin role
3. `PermissionMiddleware` - Requires specific permission

**Pipeline Execution:**
Request → Middleware Chain → Controller → Response → Middleware Chain (reverse)

---

## 7. VIEW/RENDERING SYSTEM

### 7.1 Current Implementation

**Active Renderer:** `/core/View/MustacheRenderer.php` (252 lines) ✅
- Namespace: `ISER\Core\View`
- Used in: 18 controller files
- Features:
  - Singleton pattern
  - Multiple template paths
  - Layout support
  - Partials support
  - Helpers (date, number formatting, etc.)
  - **i18n helper:** `{{#__}}key{{/__}}`
  - Data inheritance from layouts
- **Status:** Primary renderer

**Legacy Renderer:** `/core/Render/MustacheRenderer.php` (247 lines) ⚠️
- Namespace: `ISER\Core\Render`
- Used in: Only 3 files
  - `/modules/Admin/AdminPlugins.php`
  - `/modules/Theme/Iser/ThemeIser.php`
  - `/modules/Theme/Iser/ThemeRenderer.php`
- Features: Similar to active renderer
- **Status:** Should be migrated

### 7.2 Template Organization

**Layouts:** `/resources/views/layouts/`
- base.mustache - Base HTML structure
- admin.mustache - Admin panel layout
- dashboard.mustache - Dashboard layout
- login.mustache - Authentication layout
- popup.mustache - Modal/popup layout

**Pages:** `/resources/views/{section}/`
- dashboard/
- home/
- auth/
- admin/users/, admin/roles/, admin/permissions/, admin/settings/, etc.
- profile/
- search/

**Components:** `/resources/views/components/`
- Reusable UI components
- Navigation components

**Theme Templates:** `/modules/Theme/Iser/templates/`
- Extensive component library
- partials/, components/, layouts/, pages/

### 7.3 Rendering Pattern

**Example Controller Method:**

```php
public function index()
{
    $data = [
        'title' => 'User Management',
        'users' => $this->userManager->getAllUsers(),
        'permissions' => $this->getCurrentUserPermissions(),
    ];

    return MustacheRenderer::getInstance()
        ->renderWithLayout('admin/users/index', $data, 'admin');
}
```

---

## 8. DATABASE LAYER

### 8.1 Architecture

**Connection:** `/core/Database/PDOConnection.php`
- Singleton PDO wrapper
- Multi-driver support (MySQL, PostgreSQL, SQLite)
- Connection pooling
- Error handling
- Transaction support

**Abstraction:** `/core/Database/Database.php`
- Facade over PDOConnection
- Query builder methods
- Prepared statement helpers
- Result formatting

**Adapter:** `/core/Database/DatabaseAdapter.php`
- Converts schema definitions to SQL
- Driver-specific syntax handling
- Type mapping per database

**Driver Detection:** `/core/Database/DatabaseDriverDetector.php`
- Auto-detect database type from DSN
- Return appropriate adapter

### 8.2 Schema Installation

**Installer:** `/core/Database/SchemaInstaller.php` (650 lines)

**Process:**
1. Parse `/database/schema/schema.xml` with DOMDocument
2. Extract metadata (charset, collation, engine)
3. For each table:
   - Build CREATE TABLE statement via DatabaseAdapter
   - Execute table creation
   - Create indexes
   - Create foreign keys
   - Insert initial data
4. Log progress and errors
5. Return installation report

**Features:**
- Supports all schema elements (columns, indexes, foreign keys, data)
- Multi-database compatibility
- Error handling with rollback
- Progress logging
- Validation of schema structure

**XML Parser:** `/core/Utils/XMLParser.php`
- Generic XML parsing utility
- Used by SchemaInstaller

### 8.3 Manager Pattern

**Example:** UserManager.php

```php
class UserManager
{
    private Database $db;

    public function getAllUsers(): array
    {
        return $this->db->query("SELECT * FROM users WHERE deleted_at IS NULL");
    }

    public function createUser(array $data): int
    {
        return $this->db->insert('users', $data);
    }

    public function updateUser(int $id, array $data): bool
    {
        return $this->db->update('users', $data, ['id' => $id]);
    }
}
```

**Pattern Used:** Repository-like pattern with Database facade

---

## 9. AUTHENTICATION & AUTHORIZATION

### 9.1 Authentication (JWT-based)

**Implementation:** `/core/Session/JWTSession.php`

**Flow:**
1. User submits credentials
2. AuthController validates against database
3. Generate JWT token with user data
4. Store token hash in `jwt_tokens` table
5. Return token to client
6. Client includes token in Authorization header
7. Middleware validates token on each request
8. Retrieve user from token payload

**Token Structure:**
```json
{
  "user_id": 123,
  "username": "john",
  "roles": ["admin"],
  "exp": 1234567890,
  "jti": "unique-token-id"
}
```

**Security Features:**
- Token expiration
- Token revocation (via database)
- Token blacklisting
- IP address tracking
- User agent tracking

### 9.2 Authorization (RBAC)

**System:** Role-Based Access Control with granular permissions

**Tables:**
- `roles` - 4 default roles (admin, moderator, user, guest)
- `permissions` - 35 granular permissions across 9 modules
- `user_roles` - Many-to-many assignment
- `role_permissions` - Many-to-many assignment

**Permission Modules:**
1. `users` - 7 permissions (view, create, update, delete, restore, assign_roles, view_profile)
2. `roles` - 5 permissions (view, create, update, delete, assign_permissions)
3. `permissions` - 4 permissions (view, create, update, delete)
4. `dashboard` - 3 permissions (view, stats, charts)
5. `settings` - 3 permissions (view, update, critical)
6. `logs` - 3 permissions (view, delete, export)
7. `audit` - 2 permissions (view, export)
8. `reports` - 3 permissions (view, generate, export)
9. `sessions` - 2 permissions (view, terminate)
10. `email_queue` - 4 permissions (view, retry, delete, clear)

**Permission Checking:**
- Middleware: PermissionMiddleware::class
- Helper: `userHasPermission($userId, $permission)`
- Controllers call permission checks before actions

**Advanced RBAC (in /modules/Roles/):**
- Context-based permissions (Moodle-inspired)
- Capability levels: ALLOW, PREVENT, PROHIBIT
- Permission inheritance
- Role context awareness

---

## 10. INSTALLATION SYSTEM

### 10.1 Web Installer

**Entry Point:** `/public_html/install.php` → `/install/index.php`

**Stages (7 total):**

1. **Welcome** (`stages/welcome.php`)
   - Select installation language
   - Display welcome message
   - Show system information

2. **Requirements** (`stages/requirements.php`)
   - Check PHP version (≥8.1)
   - Check required extensions:
     - PDO, pdo_mysql/pdo_pgsql/pdo_sqlite
     - JSON, mbstring, openssl, session, ctype, hash
   - Check writable directories:
     - /var/logs/, /var/cache/, /modules/plugins/
   - Display warnings for optional extensions

3. **Database Configuration** (`stages/database.php`)
   - Select database driver (MySQL, PostgreSQL, SQLite)
   - Enter connection details (host, port, database, username, password)
   - Test connection (via test-connection.php AJAX endpoint)
   - Create database if doesn't exist

4. **Database Installation** (`stages/install_db.php`)
   - Run SchemaInstaller on `/database/schema/schema.xml`
   - Create all 23 tables
   - Create indexes and foreign keys
   - Insert initial data (roles, permissions, config)
   - Display progress in real-time
   - Show detailed error messages on failure

5. **Admin User Creation** (`stages/admin.php`)
   - Enter admin username
   - Enter admin email
   - Enter admin password (with validation)
   - Enter first name and last name
   - Create user with admin role

6. **Basic Configuration** (`stages/basic_config.php`)
   - Generate JWT secret key
   - Select timezone
   - Select default locale (en, es)
   - Set debug mode (development/production)

7. **Finish** (`stages/finish.php`)
   - Generate `.env` file with all configuration
   - Mark system as installed (INSTALLED=true)
   - Display success message
   - Show next steps:
     - Delete or protect /install/ directory
     - Access admin panel
     - Configure additional settings
   - Provide login link

**Installer Assets:**
- `/install/assets/css/installer.css` - Styled installer UI
- `/install/assets/js/installer.js` - AJAX, validation, progress

**Post-Installation:**
- Redirect to /install/ if system not installed
- Prevent access to /install/ if already installed (security)

---

## 11. LOGGING SYSTEM

### 11.1 Implementation

**Active Logger:** `/core/Utils/Logger.php` (430 lines) ✅

**Framework:** Monolog integration

**Features:**
- Multiple log channels: app, database, security, auth, audit
- Log levels: debug, info, notice, warning, error, critical, alert, emergency
- Rotating file handler (daily rotation)
- Database handler (writes to `logs` table)
- Context data support
- User ID tracking
- IP address tracking
- Static method API: `Logger::info($message, $context)`

**Channels:**
- `app` - General application logs
- `database` - Database queries and errors
- `security` - Security events (failed logins, etc.)
- `auth` - Authentication events
- `audit` - Audit trail for important actions

**Configuration:**
- Log path: `/var/logs/`
- Max files: 30 days retention
- Level: Configured via .env (LOG_LEVEL)

**Usage Example:**

```php
Logger::info('User created', [
    'user_id' => $userId,
    'username' => $username,
]);

Logger::security('Failed login attempt', [
    'username' => $username,
    'ip_address' => $_SERVER['REMOTE_ADDR'],
]);

Logger::error('Database connection failed', [
    'error' => $e->getMessage(),
]);
```

### 11.2 Dead Logger ❌

**File:** `/core/Log/Logger.php` (224 lines)
- Namespace: `ISER\Core\Log`
- **Not used anywhere** - 0 imports
- PSR-3 compliant implementation
- Instance-based (not static)
- **Action:** DELETE - completely unused

---

## 12. UTILITIES

### 12.1 Core Utilities (`/core/Utils/`)

**Logger.php** (430 lines) ✅
- Already covered in Logging section

**XMLParser.php**
- Parse XML files to arrays
- SimpleXML wrapper
- Used by SchemaInstaller

**Mailer.php**
- PHPMailer integration
- Send emails via SMTP
- Template-based emails
- Queue support (writes to `email_queue` table)

**FileManager.php**
- File upload handling
- File validation (size, type, extension)
- Secure file storage
- Delete files

**Helpers.php**
- Global helper functions
- String manipulation
- Array utilities
- Date/time helpers
- Security helpers (sanitize, escape)

**Paginator.php**
- Pagination logic
- Calculate offsets, pages
- Generate pagination data for views

**Recaptcha.php**
- Google reCAPTCHA integration
- Validate reCAPTCHA responses
- Used in login forms (optional)

---

## 13. TESTING

### 13.1 Test Infrastructure

**Directory:** `/tests/`

**Structure:**
- `/tests/Unit/Core/` - Core unit tests
- `/tests/Integration/` - Integration tests
- `tests/bootstrap.php` - Test bootstrap
- `phpunit.xml` - PHPUnit configuration

**Existing Tests:**

1. **Core Tests:**
   - `Unit/Core/HelpersTest.php` - Test helper functions
   - `Unit/Core/EnvironmentTest.php` - Test environment setup
   - `Integration/CoreSystemTest.php` - Test system initialization

2. **Theme Tests:**
   - `/modules/Theme/Iser/Tests/ThemeIserTest.php`
   - `/modules/Theme/Iser/Tests/ThemeRendererTest.php`
   - `/modules/Theme/Iser/Tests/ThemeAssetsTest.php`
   - `/modules/Theme/Iser/Tests/ThemeIntegrationTest.php`

**Total Test Files:** ~7 files

**Coverage Estimate:** ~5% of codebase

### 13.2 Testing Gaps ❌

**Not Tested:**
- Controllers (all 19 controllers)
- Managers (UserManager, RoleManager, etc.)
- Database layer
- Authentication/Authorization
- Plugin system
- Installation system
- Routing system
- Middleware
- Most utilities

**Critical Modules Without Tests:**
- User management
- RBAC system
- Plugin loader/installer
- SchemaInstaller
- JWT session management
- i18n system

**Recommendation:** Expand test coverage to at least 70%

---

## 14. IDENTIFIED ISSUES & RECOMMENDATIONS

### 14.1 Critical Issues 🔴

#### ISSUE #1: Case-Sensitivity Conflict
**Files:**
- `/modules/Report/` (uppercase)
- `/modules/report/` (lowercase)

**Problem:** On case-sensitive filesystems (Linux production servers), these are different directories. On case-insensitive systems (Windows, macOS), they conflict.

**Impact:**
- Potential autoloading failures
- Namespace conflicts
- Database install scripts may fail

**Recommendation:**
1. Standardize to `/modules/Report/` (uppercase)
2. Move any files from `/modules/report/` to `/modules/Report/`
3. Update any references
4. Delete `/modules/report/`

**Priority:** CRITICAL - Fix immediately

---

#### ISSUE #2: Dead Code - Unused Logger
**File:** `/core/Log/Logger.php` (224 lines)

**Problem:** Completely unused - 0 imports anywhere in codebase

**Impact:**
- Code clutter
- Potential confusion for developers
- Maintenance burden

**Recommendation:**
1. Confirm no dynamic usage (reflection, string-based calls)
2. Delete file
3. Delete `/core/Log/` directory if empty

**Priority:** CRITICAL - Easy win, remove immediately

**Savings:** 224 lines of code

---

#### ISSUE #3: Router Duplication
**Files:**
- `/core/Router/Router.php` (460 lines) - Legacy
- `/core/Router/Route.php` - Supporting class
- `/core/Routing/Router.php` (354 lines) - Active

**Problem:** Two complete router implementations

**Usage:**
- `Routing\Router` used in production (public_html/index.php)
- `Router\Router` only used in Bootstrap for fallback handlers

**Recommendation:**
1. Update `core/Bootstrap.php` to use `Routing\Router`
2. Test thoroughly
3. Delete `/core/Router/` directory

**Priority:** HIGH

**Savings:** 460+ lines

---

#### ISSUE #4: Renderer Duplication
**Files:**
- `/core/Render/MustacheRenderer.php` (247 lines) - Legacy
- `/core/View/MustacheRenderer.php` (252 lines) - Active

**Usage:**
- `View\MustacheRenderer` used in 18 controllers ✅
- `Render\MustacheRenderer` used in only 3 files:
  1. `/modules/Admin/AdminPlugins.php`
  2. `/modules/Theme/Iser/ThemeIser.php`
  3. `/modules/Theme/Iser/ThemeRenderer.php`

**Recommendation:**
1. Update 3 legacy files to use `View\MustacheRenderer`
2. Test rendering works
3. Delete `/core/Render/MustacheRenderer.php`
4. Delete `/core/Render/` directory if empty

**Priority:** HIGH

**Savings:** 247 lines

---

### 14.2 High Priority Issues 🟡

#### ISSUE #5: RBAC Duplication
**Modules:**
- `/modules/Role/` (1 file, 204 lines) - Simple
- `/modules/Roles/` (4 files, complex) - Full RBAC
- `/modules/Permission/` (1 file, 183 lines) - Simple

**Problem:** Multiple permission/role systems

**Recommendation:**
1. Audit current usage - which is used in production?
2. Choose: Simple (`Role/` + `Permission/`) or Complex (`Roles/`)
3. If using complex: Delete `/modules/Role/` and `/modules/Permission/`
4. If using simple: Delete `/modules/Roles/`
5. Create migration guide if needed

**Priority:** HIGH

**Savings:** 400+ lines (depending on choice)

---

#### ISSUE #6: Hardcoded Strings (i18n)
**Location:** All templates (50+ files)

**Problem:** Spanish strings hardcoded instead of using `{{#__}}key{{/__}}` syntax

**Examples:**
```mustache
<!-- Wrong -->
<h1>Gestión de Usuarios</h1>

<!-- Correct -->
<h1>{{#__}}admin.users_title{{/__}}</h1>
```

**Affected:**
- `/modules/Admin/templates/` (6 files)
- `/resources/views/` (60+ files)
- `/modules/Theme/Iser/templates/` (30+ files)

**Impact:**
- English users see Spanish
- Cannot switch languages
- i18n system not fully utilized

**Recommendation:**
1. Create string extraction script (grep for Spanish text)
2. Generate list of strings to translate
3. Add keys to `/resources/lang/es/` files
4. Replace hardcoded strings with translation keys
5. Test both locales
6. Phase approach:
   - Week 1: Admin panel (6 files)
   - Week 2-3: Main views (60 files)
   - Week 4: Theme templates (30 files)

**Priority:** HIGH

**Effort:** 20-40 hours

---

### 14.3 Medium Priority Issues 🟢

#### ISSUE #7: Limited Test Coverage
**Current:** ~5% coverage (7 test files)

**Missing Tests:**
- Controllers (0 tests)
- Managers (0 tests)
- Database layer (0 tests)
- Auth/Authorization (0 tests)
- Plugin system (0 tests)

**Recommendation:**
1. Start with critical paths:
   - Authentication flow
   - RBAC permission checking
   - User CRUD operations
   - Plugin loading
2. Add integration tests for main flows
3. Target 70% coverage

**Priority:** MEDIUM

**Effort:** 40-80 hours

---

#### ISSUE #8: TODO Comments
**Count:** 9 TODOs across 8 files

**Files:**
- core/I18n/LocaleDetector.php
- resources/lang/es/users.php
- resources/lang/en/users.php
- modules/Controllers/Traits/NavigationTrait.php
- modules/Auth/Manual/AuthManual.php (2 TODOs)
- core/Session/JWTSession.php
- modules/Admin/Tool/UploadUser/UploadUser.php
- modules/Admin/Tool/Mfa/Factors/BackupFactor.php

**Recommendation:**
1. Review each TODO
2. Create GitHub issues for important ones
3. Fix quick ones
4. Remove outdated ones

**Priority:** MEDIUM (Good code hygiene)

---

### 14.4 Low Priority Issues 🔵

#### ISSUE #9: Large Classes
**Examples:**
- `PluginLoader.php` (640 lines)
- `SchemaInstaller.php` (650 lines)
- `Bootstrap.php` (560 lines)

**Recommendation:**
- Consider splitting into smaller classes
- Use composition
- Extract responsibilities

**Priority:** LOW (Not urgent, works fine)

---

#### ISSUE #10: Mixed Language Comments
**Problem:** Some comments in Spanish, some in English

**Recommendation:**
- Standardize on English for code/comments
- Keep Spanish in UI/templates

**Priority:** LOW

---

## 15. REFACTORING ROADMAP

### Phase 1: Critical Cleanup (Week 1) 🔴

**Tasks:**
1. ✅ Fix Report/report case conflict
   - Move files from lowercase to uppercase
   - Update imports
   - Delete lowercase directory
   - **Effort:** 2 hours

2. ✅ Delete dead Logger
   - Verify no usage
   - Delete `/core/Log/Logger.php`
   - **Effort:** 30 minutes

3. ✅ Plan router consolidation
   - Identify all usage points
   - Create migration plan
   - **Effort:** 2 hours

**Total Week 1:** 4.5 hours

---

### Phase 2: Code Consolidation (Week 2-3) 🟡

**Tasks:**
1. ✅ Consolidate routers
   - Update Bootstrap.php
   - Test all routes
   - Delete `/core/Router/`
   - **Effort:** 4 hours

2. ✅ Consolidate renderers
   - Update 3 legacy files
   - Test rendering
   - Delete `/core/Render/`
   - **Effort:** 3 hours

3. ✅ Consolidate RBAC
   - Audit current usage
   - Choose simple or complex
   - Delete unused modules
   - **Effort:** 8 hours

**Total Week 2-3:** 15 hours

---

### Phase 3: Internationalization (Week 4-7) 🟡

**Tasks:**
1. Extract hardcoded strings
   - Create extraction script
   - Generate string inventory
   - **Effort:** 4 hours

2. Add translation keys
   - Add to lang files
   - Organize by category
   - **Effort:** 8 hours

3. Replace in templates
   - Admin panel (6 files)
   - Main views (60 files)
   - Theme templates (30 files)
   - **Effort:** 20 hours

4. Test both locales
   - Manual testing
   - Create checklist
   - **Effort:** 4 hours

**Total Week 4-7:** 36 hours

---

### Phase 4: Testing (Week 8-11) 🟢

**Tasks:**
1. Core tests
   - Authentication
   - Authorization
   - RBAC
   - **Effort:** 12 hours

2. Manager tests
   - UserManager
   - RoleManager
   - PluginManager
   - **Effort:** 12 hours

3. Controller tests
   - Main controllers
   - Admin controllers
   - **Effort:** 16 hours

4. Integration tests
   - User flows
   - Admin flows
   - Plugin flows
   - **Effort:** 12 hours

**Total Week 8-11:** 52 hours

---

### Phase 5: Documentation (Week 12) 🟢

**Tasks:**
1. Architecture documentation
2. API documentation
3. Plugin development guide
4. Deployment guide

**Total Week 12:** 16 hours

---

### Total Estimated Effort
**Critical (Phase 1):** 4.5 hours
**High Priority (Phase 2-3):** 51 hours
**Medium Priority (Phase 4-5):** 68 hours

**Grand Total:** 123.5 hours (~15-16 days of focused work)

---

## 16. TECHNICAL DEBT ASSESSMENT

### 16.1 Code Quality Metrics

**Duplicated Code:**
- 6 major duplications identified
- Estimated duplicated lines: ~1,500
- After cleanup: Save ~1,000 lines

**Complexity:**
- Average file size: 200-300 lines
- Largest files: 640 lines (PluginLoader)
- Most files well-structured

**Maintainability:**
- Good: Namespaced, typed, documented
- Needs work: Test coverage, consolidation

### 16.2 Security Assessment

**Strengths:** ✅
- Prepared statements (SQL injection prevention)
- JWT authentication
- Password hashing
- RBAC system
- Audit logging
- MFA support

**Verify:** ⚠️
- CSRF protection in forms
- XSS prevention in templates
- Rate limiting on sensitive endpoints
- Input validation consistency
- File upload security

### 16.3 Performance Considerations

**Current:**
- No query caching
- No application caching (Redis/Memcached)
- Potential N+1 queries in managers

**Recommendations:**
- Add query result caching
- Implement Redis for sessions
- Profile slow queries
- Add indexes where needed

---

## 17. CONCLUSION

### 17.1 Summary

NexoSupport is a **well-architected authentication system** with solid foundations:
- ✅ Modular architecture
- ✅ Proper separation of concerns
- ✅ Security-conscious design
- ✅ Extensive RBAC system
- ✅ Plugin system (75% complete)
- ✅ i18n infrastructure (90% complete)
- ✅ Multi-database support
- ✅ Modern PHP 8+ code

**However**, it needs consolidation and cleanup:
- ❌ Multiple duplicate implementations (6 areas)
- ❌ Dead code (224 lines minimum)
- ❌ Inconsistent i18n usage (500-1000 hardcoded strings)
- ❌ Limited test coverage (~5%)
- ❌ Case-sensitivity bug (Report/report)

### 17.2 Overall Assessment

**Grade:** 7/10 - **Good foundation, needs refinement**

**Production Readiness:** 75%

**Technical Debt:** Medium (manageable with focused effort)

### 17.3 Next Steps

1. **Immediate (Week 1):**
   - Fix case-sensitivity issue
   - Delete dead code
   - Create detailed refactoring plan

2. **Short-term (Month 1):**
   - Consolidate duplicate code
   - Expand test coverage to critical paths
   - Start i18n string extraction

3. **Medium-term (Month 2-3):**
   - Complete i18n migration
   - Achieve 70% test coverage
   - Complete plugin system
   - Comprehensive documentation

4. **Long-term (Month 4+):**
   - Performance optimization
   - Plugin marketplace
   - Advanced features

### 17.4 Success Criteria

**Refactoring will be successful when:**
- ✅ No duplicate code (routers, renderers, RBAC)
- ✅ No dead code
- ✅ 100% i18n compliance (no hardcoded strings)
- ✅ 70%+ test coverage
- ✅ No case-sensitivity issues
- ✅ Comprehensive documentation
- ✅ All existing functionality preserved

---

**Document Version:** 1.0
**Last Updated:** 2025-11-13
**Next Review:** After Phase 1 completion

---

**Appendices:**
- [Appendix A: Dead Code Inventory](#) (to be created)
- [Appendix B: Hardcoded Strings Catalog](#) (to be created)
- [Appendix C: Database Schema Diagram](#) (to be created)
- [Appendix D: Plugin Development Guide](#) (to be created)
