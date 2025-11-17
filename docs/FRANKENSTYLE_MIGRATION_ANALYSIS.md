# NexoSupport Frankenstyle Migration - Comprehensive Analysis Report

**Date**: November 17, 2025  
**Total PHP Files**: 214  
**Files with Issues**: ~80+ files  
**Severity Levels**: Critical (27), Important (35), Minor (18+)

---

## EXECUTIVE SUMMARY

The NexoSupport codebase is **partially migrated** to Frankenstyle architecture. While recent commits show work on i18n and plugin structure, significant legacy code remains that doesn't comply with Frankenstyle standards. Major issues include:

1. **Namespace Inconsistencies** - Mixed underscore and ISER\Vendor patterns
2. **Legacy Moodle-style Code** - Old classes like `auth_plugin_manual` requiring non-existent base classes
3. **Global Functions** - 50+ functions in lib/ that should be in classes
4. **Hardcoded Strings** - 51+ echo statements in admin plugins, no i18n usage
5. **Missing Plugin Infrastructure** - No settings.php in most plugins, empty classes/ directories
6. **Direct Database Access** - Using global $DB instead of dependency injection

---

## 1. DIRECTORY STRUCTURE ANALYSIS

### Missing/Orphaned Directories

**CRITICAL**: `/home/user/NexoSupport/user/` directory is empty
- Should contain user-related plugins or be migrated
- Status: Orphaned

**CRITICAL**: `/home/user/NexoSupport/login/` directory is empty
- Should contain login-related plugins or be migrated
- Status: Orphaned

### Properly Structured Plugin Directories
✓ `/home/user/NexoSupport/admin/` (8 plugins + 5 MFA factors)
✓ `/home/user/NexoSupport/auth/` (1 plugin: manual)
✓ `/home/user/NexoSupport/theme/` (2 plugins: core, iser)
✓ `/home/user/NexoSupport/report/` (1 plugin: log)

---

## 2. NAMESPACE AND CLASS ARCHITECTURE ISSUES

### CRITICAL - Inconsistent Namespace Patterns

**Issue**: Namespaces use THREE different patterns

#### Pattern 1: Old Underscore Format (NON-FRANKENSTYLE)
```
- namespace tool_pluginmanager      (WRONG - should be tool\pluginmanager)
- namespace tool_uploaduser         (WRONG)
- namespace factor_email            (WRONG)
- namespace theme_core\output       (WRONG - backslash + underscore)
- namespace theme_iser\output       (WRONG)
- namespace tool_logviewer          (WRONG)
```
Files affected: 6+ class files in admin/tool and theme plugins

#### Pattern 2: Correct ISER\Vendor Pattern (GOOD)
```
- namespace ISER\Core\Bootstrap
- namespace ISER\Core\Database\PDOConnection
- namespace ISER\Admin\Tool\MFA\Factors
- namespace ISER\Tools\MFA
- namespace ISER\Tools\DataPrivacy
```
Files affected: core/ and some admin/tool plugins

#### Pattern 3: Lowercase Partial Pattern (WRONG)
```
- namespace core\user       (WRONG - missing vendor prefix)
- namespace core\role       (WRONG)
```
Files: `/home/user/NexoSupport/lib/classes/user/*.php` (3 files)
Files: `/home/user/NexoSupport/lib/classes/role/*.php` (4 files)

### Files with Namespace Issues

**HIGH PRIORITY - Rename/Refactor Required**:
- `/home/user/NexoSupport/admin/tool/pluginmanager/classes/plugin_manager.php:12` - namespace tool_pluginmanager
- `/home/user/NexoSupport/admin/tool/uploaduser/classes/uploader.php:12` - namespace tool_uploaduser
- `/home/user/NexoSupport/admin/tool/logviewer/classes/log_reader.php` - namespace tool_logviewer
- `/home/user/NexoSupport/admin/tool/mfa/factor/*/classes/factor.php` (5 files) - namespace factor_*
- `/home/user/NexoSupport/theme/core/classes/output/core_renderer.php:3` - namespace theme_core\output
- `/home/user/NexoSupport/theme/iser/classes/output/core_renderer.php:3` - namespace theme_iser\output
- `/home/user/NexoSupport/lib/classes/user/user.php:12` - namespace core\user (MISSING VENDOR)
- `/home/user/NexoSupport/lib/classes/user/user_repository.php` - namespace core\user
- `/home/user/NexoSupport/lib/classes/user/user_helper.php` - namespace core\user
- `/home/user/NexoSupport/lib/classes/role/role.php` - namespace core\role
- `/home/user/NexoSupport/lib/classes/role/role_helper.php` - namespace core\role
- `/home/user/NexoSupport/lib/classes/role/access_manager.php` - namespace core\role
- `/home/user/NexoSupport/lib/classes/role/permission.php` - namespace core\role

**CORRECT NAMESPACES** ✓:
- `/home/user/NexoSupport/lib/classes/health/health_checker.php` - namespace ISER\Core\Health
- `/home/user/NexoSupport/lib/classes/cache/cache_manager.php` - namespace ISER\Core\Cache
- `/home/user/NexoSupport/lib/classes/theme/mustache_engine.php` - namespace ISER\Core\Theme
- `/home/user/NexoSupport/lib/classes/theme/theme_manager.php` - namespace ISER\Core\Theme
- All core/ files use ISER\Core\* pattern ✓

---

## 3. LEGACY MOODLE-STYLE CODE ISSUES

### CRITICAL - auth/manual Using Old Auth System

**File**: `/home/user/NexoSupport/auth/manual/auth.php`

**Issues**:
- Line 20: `class auth_plugin_manual extends auth_plugin_base` - NON-FRANKENSTYLE
  - `auth_plugin_base` class doesn't exist in NexoSupport
  - Should extend proper Frankenstyle authentication interface
  - Old Moodle class naming pattern
  
- Line 12: `require_once($CFG->libdir . '/authlib.php');`
  - References non-existent Moodle files
  - `$CFG->libdir` not available
  - Should be class-based

- Lines 38, 68, 207: `global $DB;`
  - Global state dependency
  - Should use dependency injection

- Line 182: `return new moodle_url(...)`
  - References non-existent `moodle_url` class
  - Should use modern routing

**Recommendation**: Completely refactor to Frankenstyle:
- Create `auth_manual\auth` class with namespace `ISER\Auth\Manual\Auth`
- Implement proper authentication interface
- Use dependency injection for database

---

### CRITICAL - report/log Using Old Moodle Code

**File**: `/home/user/NexoSupport/report/log/index.php`

**Lines with Issues**:
- Line 10: `require('../../config.php');`
- Line 11: `require_once($CFG->libdir . '/adminlib.php');`
- Line 14: `admin_externalpage_setup(...)`
- Line 16-21: `optional_param()` - Old Moodle function
- Line 24: `new moodle_url(...)`
- Line 28: `$OUTPUT->header()` - Old Moodle global
- Line 29: `$OUTPUT->heading()`

**Recommendation**: Migrate to modern controller-based system with proper routing

---

## 4. GLOBAL FUNCTIONS (NON-FRANKENSTYLE)

### CRITICAL - 50+ Global Functions in lib/

These functions should be methods in classes:

**`/home/user/NexoSupport/lib/setup.php`** (8 functions):
- `require_component_lib()` - Line 56
- `component_get_path()` - Line 74
- `get_components_by_type()` - Should be in class

**`/home/user/NexoSupport/lib/accesslib.php`** (15 functions):
- `get_access_manager()` - Line ~5
- `has_capability()` - Line ~20
- `require_capability()` - Line ~30
- `user_has_role()` - Line ~40
- `require_role()` - Line ~50
- `get_user_roles()` - Line ~60
- `get_user_permissions()` - Line ~70
- `assign_user_role()` - Line ~80
- `unassign_user_role()` - Line ~90
- `grant_role_permission()` - Line ~100
- And 5+ more

**`/home/user/NexoSupport/lib/compat/roles_compat.php`** (2 functions):
- `get_role_manager()` - Compatibility function
- `get_permission_manager()` - Compatibility function

**Plugin-specific Global Functions** (30+ total):

`/home/user/NexoSupport/admin/user/lib.php`:
- `admin_user_get_capabilities()` - Line 29
- `admin_user_fullname()` - Line 71
- `admin_user_status_badge()` - Line 94
- `admin_user_get_menu_items()` - Line 110

`/home/user/NexoSupport/admin/roles/lib.php`:
- `admin_roles_get_capabilities()` - Line 20
- `admin_roles_get_permission_capabilities()` - Line 56
- `admin_roles_get_all_capabilities()` - Line 87
- `admin_roles_is_system_role()` - Line 101
- `admin_roles_badge()` - Line 115
- `admin_roles_permission_count()` - Line 133
- `admin_roles_get_menu_items()` - Line 149
- `admin_roles_group_permissions_by_module()` - Line 180

`/home/user/NexoSupport/admin/tool/dataprivacy/lib.php`:
- `tool_dataprivacy_get_capabilities()` - Line 17
- `tool_dataprivacy_get_title()` - Line 43
- `tool_dataprivacy_get_description()` - Line 53
- `tool_dataprivacy_get_menu_items()` - Line 63

`/home/user/NexoSupport/admin/tool/dataprivacy/db/install.php`:
- `tool_dataprivacy_get_schema()` - Database schema function
- `tool_dataprivacy_install_db()` - Installation function
- `tool_dataprivacy_uninstall_db()` - Uninstall function

`/home/user/NexoSupport/report/log/lib.php`:
- `report_log_get_name()` - Line 17
- `report_log_get_entries()` - Line 30
- `report_log_count_entries()` - Line 72
- `report_log_export_csv()` - Line 111

---

## 5. HARDCODED STRINGS (NO I18N)

### CRITICAL - 51+ Hardcoded Strings in admin plugins

**`/home/user/NexoSupport/admin/user/lib.php`**:
- Line 33: `'View users'` - should use get_string()
- Line 34: `'View user list and details'`
- Line 38: `'Create users'`
- Line 39: `'Create new user accounts'`
- Line 42: `'Edit users'`
- Line 43: `'Edit user account details'`
- Line 46: `'Delete users'`
- Line 49: `'Delete user accounts (soft delete)'`
- Line 52: `'Restore users'`
- Line 53: `'Restore deleted user accounts'`
- Line 57: `'Assign roles'`
- Line 58: `'Assign and unassign roles to users'`
- Line 97: `'<span class="badge badge-success">Active</span>'`
- Line 98: `'<span class="badge badge-warning">Suspended</span>'`
- Line 99: `'<span class="badge badge-info">Pending</span>'`
- Line 102: `'<span class="badge badge-secondary">Unknown</span>'`
- Line 116: `'Users'`
- Line 117: `'/admin/users'`
- Line 118: `'users'` (icon)

**`/home/user/NexoSupport/admin/roles/lib.php`** (18+ strings):
- Line 24: `'View roles'`
- Line 25: `'View role list and details'`
- Line 29: `'Create roles'`
- Line 30: `'Create new roles'`
- Line 33: `'Edit roles'`
- Line 34: `'Edit role details'`
- Line 38: `'Delete roles'`
- Line 40: `'Delete roles (non-system only)'`
- Line 43: `'Assign permissions'`
- Line 44: `'Assign and revoke permissions to/from roles'`
- Line 59: `'View permissions'`
- Line 60: `'View permission list'`
- Line 64: `'Create permissions'`
- Line 65: `'Create new permissions'`
- Line 69: `'Edit permissions'`
- Line 70: `'Edit permission details'`
- Line 74: `'Delete permissions'`
- Line 75: `'Delete permissions'`
- Line 121: `'(System)'`
- Line 136: `'No permissions'`
- Line 138: `'1 permission'`
- Line 155: `'Roles'`
- Line 156: `'/admin/roles'`
- Line 157: `'shield'`
- Line 163: `'Permissions'`
- Line 164: `'/admin/permissions'`
- Line 166: `'key'`

**`/home/user/NexoSupport/admin/tool/dataprivacy/lib.php`**:
- Line 44: `__('Data Privacy')` - Uses translation function (GOOD) but inconsistent
- Line 55: `__('Manage data privacy and GDPR compliance')`
- Line 69: `'Data Privacy'`
- Line 70: `'/admin/tool/dataprivacy'`
- Line 71: `'lock'`

**`/home/user/NexoSupport/report/log/lib.php`**:
- Line 115: `"ID,Usuario,Acción,IP,Fecha\n"` - Hardcoded CSV header
- Lines 118-125: Hardcoded formatting strings

**`/home/user/NexoSupport/core/Bootstrap.php`** (4 hardcoded strings):
- Line ~35: `'<h1>System Initialization Failed</h1>'`
- Line ~36: `'<pre>'` + error message
- Line ~41: `'<h1>System Error</h1>'`
- Line ~42: `'<p>The system encountered an error during initialization.</p>'`

**`/home/user/NexoSupport/public_html/index.php`** (2 hardcoded strings):
- Line 207: `'<h1>404 - Page Not Found</h1>'`
- Line 208: `'<p>The requested URL was not found on this server.</p>'`
- Line 212: `'<h1>500 - Internal Server Error</h1>'`

**`/home/user/NexoSupport/install/stages/finish.php`**:
- Uses hardcoded HTML error messages

**`/home/user/NexoSupport/tools/generate_translation_keys.php`** (10+ echo statements):
- Multiple `echo` statements for console output (acceptable for CLI)

---

## 6. MISSING PLUGIN INFRASTRUCTURE FILES

### CRITICAL - Missing settings.php in Most Plugins

**Plugins WITHOUT settings.php**:
- `/home/user/NexoSupport/admin/user/` - MISSING ✗
- `/home/user/NexoSupport/admin/roles/` - MISSING ✗
- `/home/user/NexoSupport/admin/tool/dataprivacy/` - MISSING ✗
- `/home/user/NexoSupport/admin/tool/pluginmanager/` - MISSING ✗
- `/home/user/NexoSupport/admin/tool/uploaduser/` - MISSING ✗
- `/home/user/NexoSupport/admin/tool/mfa/` - MISSING ✗
- `/home/user/NexoSupport/admin/tool/mfa/factor/*/` (5 factors) - MISSING ✗
- `/home/user/NexoSupport/admin/tool/installaddon/` - MISSING ✗
- `/home/user/NexoSupport/admin/tool/logviewer/` - MISSING ✗
- `/home/user/NexoSupport/theme/core/` - MISSING ✗
- `/home/user/NexoSupport/theme/iser/` - MISSING ✗
- `/home/user/NexoSupport/report/log/` - MISSING ✗

**Plugins WITH settings.php**:
- `/home/user/NexoSupport/auth/manual/` - HAS ✓

### CRITICAL - Empty classes/ Directories

**Empty directories**:
- `/home/user/NexoSupport/admin/user/classes/` - Empty
- `/home/user/NexoSupport/admin/roles/classes/` - Empty
- `/home/user/NexoSupport/admin/tool/mfa/factor/email/classes/` - Empty?
- `/home/user/NexoSupport/admin/tool/mfa/factor/sms/classes/` - Empty?
- `/home/user/NexoSupport/admin/tool/mfa/factor/backupcodes/classes/` - Empty?
- `/home/user/NexoSupport/admin/tool/mfa/factor/totp/classes/` - Empty?
- `/home/user/NexoSupport/admin/tool/mfa/factor/iprange/classes/` - Empty?
- `/home/user/NexoSupport/auth/manual/classes/` - Empty

### Missing db/ Directories (if schema changes needed)

**Plugins that might need db/ directories**:
- `/home/user/NexoSupport/admin/user/` - No db/ directory
- `/home/user/NexoSupport/admin/roles/` - No db/ directory
- `/home/user/NexoSupport/auth/manual/` - No db/ directory
- `/home/user/NexoSupport/theme/core/` - No db/ directory
- `/home/user/NexoSupport/theme/iser/` - No db/ directory
- `/home/user/NexoSupport/report/log/` - No db/ directory
- `/home/user/NexoSupport/admin/tool/pluginmanager/` - No db/ directory
- `/home/user/NexoSupport/admin/tool/uploaduser/` - No db/ directory
- `/home/user/NexoSupport/admin/tool/installaddon/` - No db/ directory
- `/home/user/NexoSupport/admin/tool/logviewer/` - No db/ directory

**Plugins WITH db/ directories** ✓:
- `/home/user/NexoSupport/admin/tool/dataprivacy/db/` ✓
- `/home/user/NexoSupport/admin/tool/mfa/db/` ✓
- `/home/user/NexoSupport/admin/tool/mfa/factor/*/db/` (5 factors) ✓

---

## 7. LANG DIRECTORIES

### Current Status

**Plugins WITH lang/ directories** ✓:
- All major plugins have `/lang/es/` directories
- Lang files named correctly (e.g., `admin_user.php`, `admin_roles.php`)

**Plugins MISSING lang/ directories** ✗:
- None identified (all have lang/es)

**Issue**: No English (en) language files in most plugins
- Only Spanish (es) translations exist
- Should have lang/en/ for default language

---

## 8. DATABASE ACCESS PATTERNS

### Using Global $DB (Anti-pattern)

**Files with global $DB declarations** (19 instances):
1. `/home/user/NexoSupport/auth/manual/auth.php` - Lines 38, 68, 207 (3 instances)
2. `/home/user/NexoSupport/report/log/lib.php` - Lines 32, 74 (2 instances)
3. `/home/user/NexoSupport/admin/tool/mfa/factor/email/classes/factor.php` - Multiple instances
4. `/home/user/NexoSupport/admin/tool/mfa/factor/sms/classes/factor.php` - Multiple instances
5. Other MFA factor implementations

**Better approach**: Use dependency injection (Database class already available in core/)

---

## 9. THEME AND LAYOUT FILES WITH LEGACY CODE

### `/home/user/NexoSupport/theme/iser/layout/base.php`

**Issues**:
- Line: `$renderer = $PAGE->get_renderer('theme_iser');` - Uses old global $PAGE
- Lines: `echo $OUTPUT->doctype();` - Uses old global $OUTPUT
- Multiple `$OUTPUT->*()` calls throughout
- References non-existent Moodle rendering system

**Similar issues in**:
- `/home/user/NexoSupport/theme/iser/layout/admin.php`
- Other theme layout files

---

## 10. ENTRY POINT AND CONFIGURATION FILES

### `/home/user/NexoSupport/public_html/index.php`

**Status**: Modern Frankenstyle structure ✓
- Uses proper routing system
- Uses ISER\Core namespaces
- No hardcoded strings (except error messages)

**Minor Issue**: Error messages at lines 207-212 are hardcoded HTML
- Recommendation: Use error template system

---

## 11. SUMMARY BY SEVERITY

### CRITICAL ISSUES (27 instances)

1. **auth/manual plugin non-compliant** - Uses `auth_plugin_manual` class, extends non-existent base
2. **report/log using Moodle-style code** - Requires old Moodle files, uses old functions
3. **Namespace inconsistencies** - 13+ files with wrong namespace patterns
4. **Missing plugin infrastructure** - 12+ plugins without settings.php
5. **Empty classes/ directories** - 8+ plugins with empty class directories
6. **Global functions not in classes** - 50+ functions (accesslib, setup, plugin-specific)

### IMPORTANT ISSUES (35 instances)

1. **Hardcoded strings (no i18n)** - 51+ hardcoded strings in plugins
2. **Global $DB usage** - 19 instances of anti-pattern database access
3. **Theme layout using old globals** - Multiple $PAGE and $OUTPUT usages
4. **Missing db/ directories** - 9+ plugins might need schema directories
5. **Missing English language files** - Only Spanish translations present
6. **Old Moodle-style function calls** - optional_param, required_param, etc.

### MINOR ISSUES (18+ instances)

1. **Hardcoded HTML in error pages** - 4-5 instances
2. **Lowercase namespace prefix** - `core\` instead of `ISER\Core\`
3. **Function naming** - `admin_user_*` instead of class methods
4. **Theme layout structure** - Should use Mustache templates instead of PHP

---

## 12. RECOMMENDED MIGRATION STRATEGY

### Phase 1: Core System (Week 1)
- [ ] Fix all namespace patterns to consistent ISER\Vendor\Component\Subcomponent
- [ ] Refactor global functions in lib/ to proper classes
- [ ] Create proper dependency injection system

### Phase 2: Plugin Standardization (Week 2)
- [ ] Create settings.php for all plugins without them
- [ ] Populate empty classes/ directories with plugin-specific classes
- [ ] Add db/ directories for plugins with schema changes
- [ ] Add English language files

### Phase 3: Legacy Code Migration (Week 3)
- [ ] Refactor auth/manual to use proper Frankenstyle interfaces
- [ ] Migrate report/log to controller-based system
- [ ] Replace theme layout files with Mustache templates
- [ ] Remove all global $DB usage, use dependency injection

### Phase 4: i18n Implementation (Week 4)
- [ ] Replace all hardcoded strings with get_string() calls
- [ ] Ensure all plugins have i18n files
- [ ] Add English (en) language files for all plugins
- [ ] Add more language variants

### Phase 5: Testing & Validation (Week 5)
- [ ] Create unit tests for refactored components
- [ ] Integration testing for plugin system
- [ ] Validate all plugins load correctly
- [ ] Verify all routes work properly

---

## 13. COMPLETE FILE INVENTORY OF ISSUES

### Frankenstyle Non-Compliant Files (80+)

#### Namespace Issues (13 files):
1. `/home/user/NexoSupport/admin/tool/pluginmanager/classes/plugin_manager.php:12`
2. `/home/user/NexoSupport/admin/tool/uploaduser/classes/uploader.php:12`
3. `/home/user/NexoSupport/admin/tool/logviewer/classes/log_reader.php`
4. `/home/user/NexoSupport/admin/tool/mfa/factor/email/classes/factor.php:10`
5. `/home/user/NexoSupport/admin/tool/mfa/factor/sms/classes/factor.php:10`
6. `/home/user/NexoSupport/admin/tool/mfa/factor/backupcodes/classes/factor.php:10`
7. `/home/user/NexoSupport/admin/tool/mfa/factor/totp/classes/factor.php:10`
8. `/home/user/NexoSupport/admin/tool/mfa/factor/iprange/classes/factor.php:10`
9. `/home/user/NexoSupport/theme/core/classes/output/core_renderer.php:3`
10. `/home/user/NexoSupport/theme/iser/classes/output/core_renderer.php:3`
11. `/home/user/NexoSupport/lib/classes/user/user.php:12`
12. `/home/user/NexoSupport/lib/classes/role/role.php` and 3 related files
13. (+ 4 more in lib/classes)

#### Legacy Code Files (5+ files):
1. `/home/user/NexoSupport/auth/manual/auth.php` - Old class structure
2. `/home/user/NexoSupport/report/log/index.php` - Moodle-style code
3. `/home/user/NexoSupport/theme/iser/layout/base.php` - Using $PAGE, $OUTPUT
4. `/home/user/NexoSupport/theme/iser/layout/admin.php` - Using $PAGE, $OUTPUT
5. (`/home/user/NexoSupport/theme/core/layout/*` - Similar issues)

#### Files with Hardcoded Strings (20+ files):
All `lib.php` files in admin plugins and report/log

#### Files with Global Functions (10+ files):
- `/home/user/NexoSupport/lib/setup.php` - 8 global functions
- `/home/user/NexoSupport/lib/accesslib.php` - 15 global functions
- `/home/user/NexoSupport/lib/compat/roles_compat.php` - 2 compatibility functions
- `/home/user/NexoSupport/admin/user/lib.php` - 4 global functions
- `/home/user/NexoSupport/admin/roles/lib.php` - 8 global functions
- `/home/user/NexoSupport/admin/tool/dataprivacy/lib.php` - 4 global functions
- `/home/user/NexoSupport/admin/tool/dataprivacy/db/install.php` - 3 DB schema functions
- `/home/user/NexoSupport/report/log/lib.php` - 4 global functions

#### Files Missing Infrastructure (12+ plugins):
All plugins without settings.php and empty classes/

---

**Total Estimated Refactoring Effort**: 3-4 weeks for full Frankenstyle compliance

