# Frankenstyle Migration - QUICK REFERENCE CHECKLIST

## CRITICAL ISSUES BY PLUGIN

### ❌ auth/manual - COMPLETE REFACTOR NEEDED
- [ ] Class: `auth_plugin_manual` extends non-existent `auth_plugin_base` (Line 20)
- [ ] Requires non-existent: `require_once($CFG->libdir . '/authlib.php')` (Line 12)
- [ ] Namespace missing - add `ISER\Auth\Manual` 
- [ ] Global $DB usage (Lines 38, 68, 207)
- [ ] References non-existent `moodle_url` class (Line 182)
- [ ] Missing classes/ implementation
- [ ] ADD: settings.php
- [ ] Expected effort: 2-3 days

### ❌ report/log - MAJOR REWRITE NEEDED  
- [ ] Old Moodle code: require('../../config.php') (Line 10)
- [ ] Requires: $CFG->libdir . '/adminlib.php' (Line 11)
- [ ] Uses: optional_param() function (Lines 16-21)
- [ ] Uses: moodle_url() class (Line 24)
- [ ] Uses: $OUTPUT->header() (Line 28)
- [ ] Uses: $PAGE->set_url() (Line 24)
- [ ] Hardcoded strings throughout
- [ ] Global $DB usage (lib.php Lines 32, 74)
- [ ] ADD: settings.php
- [ ] ADD: db/ directory if needed
- [ ] Expected effort: 2-3 days

### ❌ admin/tool plugins - NAMESPACE FIX + STRINGS
**pluginmanager**: 
- [ ] Fix namespace: tool_pluginmanager → ISER\Admin\Tool\PluginManager
- [ ] ADD: settings.php
- [ ] Remove hardcoded strings

**uploaduser**:
- [ ] Fix namespace: tool_uploaduser → ISER\Admin\Tool\UploadUser  
- [ ] ADD: settings.php
- [ ] Replace hardcoded strings (lib.php Lines 19+)
- [ ] Global $DB in classes/uploader.php

**dataprivacy**:
- [ ] Fix MixedCase in functions
- [ ] ADD: settings.php
- [ ] Add db/install.php schema functions as proper class

**mfa** + **mfa/factor/**:
- [ ] Fix all factor namespaces: factor_* → ISER\Admin\Tool\MFA\Factor\*
- [ ] Empty classes/ directories (5 factors) - populate with proper classes
- [ ] ADD: settings.php for each
- [ ] Global $DB in factor classes

**installaddon**:
- [ ] ADD: settings.php
- [ ] Remove hardcoded strings

**logviewer**:
- [ ] Fix namespace: tool_logviewer → ISER\Admin\Tool\LogViewer
- [ ] ADD: settings.php
- [ ] Remove hardcoded strings

### ❌ admin/user & admin/roles - REFACTOR TO CLASSES
**admin/user**:
- [ ] Move functions to proper class (admin_user_* functions)
- [ ] Empty classes/ directory - populate
- [ ] ADD: settings.php
- [ ] Replace 19 hardcoded strings (lib.php Lines 33-118)

**admin/roles**:
- [ ] Move functions to proper class (admin_roles_* functions)
- [ ] Empty classes/ directory - populate  
- [ ] ADD: settings.php
- [ ] Replace 26 hardcoded strings (lib.php Lines 24-166)

### ⚠️ theme/core & theme/iser - MULTIPLE ISSUES
- [ ] Fix namespaces: theme_core\output, theme_iser\output
- [ ] Layout files use $PAGE global (base.php, admin.php, etc.)
- [ ] Layout files use $OUTPUT global
- [ ] ADD: settings.php for each
- [ ] Consider Mustache migration instead of PHP layouts
- [ ] Config files using old patterns

---

## CRITICAL ISSUES BY CATEGORY

### 🔴 NAMESPACE ISSUES (13+ files)

| File | Current Namespace | Should Be |
|------|-------------------|-----------|
| admin/tool/pluginmanager/classes/plugin_manager.php | `tool_pluginmanager` | `ISER\Admin\Tool\PluginManager` |
| admin/tool/uploaduser/classes/uploader.php | `tool_uploaduser` | `ISER\Admin\Tool\UploadUser` |
| admin/tool/logviewer/classes/log_reader.php | `tool_logviewer` | `ISER\Admin\Tool\LogViewer` |
| admin/tool/mfa/factor/email/classes/factor.php | `factor_email` | `ISER\Admin\Tool\MFA\Factor\Email` |
| admin/tool/mfa/factor/sms/classes/factor.php | `factor_sms` | `ISER\Admin\Tool\MFA\Factor\SMS` |
| admin/tool/mfa/factor/backupcodes/classes/factor.php | `factor_backupcodes` | `ISER\Admin\Tool\MFA\Factor\BackupCodes` |
| admin/tool/mfa/factor/totp/classes/factor.php | `factor_totp` | `ISER\Admin\Tool\MFA\Factor\TOTP` |
| admin/tool/mfa/factor/iprange/classes/factor.php | `factor_iprange` | `ISER\Admin\Tool\MFA\Factor\IPRange` |
| theme/core/classes/output/core_renderer.php | `theme_core\output` | `ISER\Theme\Core\Output` |
| theme/iser/classes/output/core_renderer.php | `theme_iser\output` | `ISER\Theme\ISER\Output` |
| lib/classes/user/user.php | `core\user` | `ISER\Core\User` |
| lib/classes/user/user_repository.php | `core\user` | `ISER\Core\User` |
| lib/classes/role/role.php | `core\role` | `ISER\Core\Role` |

### 🔴 MISSING PLUGIN INFRASTRUCTURE (12+ plugins)

| Plugin | settings.php | classes/ | db/ |
|--------|:----:|:----:|:--:|
| auth/manual | ✗ | ✗ | ✗ |
| admin/user | ✗ | ✗ | ? |
| admin/roles | ✗ | ✗ | ? |
| admin/tool/dataprivacy | ✗ | ✓ | ✓ |
| admin/tool/pluginmanager | ✗ | ✓ | ? |
| admin/tool/uploaduser | ✗ | ✓ | ? |
| admin/tool/mfa | ✗ | ✓ | ✓ |
| admin/tool/mfa/factor/* (5) | ✗ | ✗ | ✓ |
| admin/tool/installaddon | ✗ | ✓ | ? |
| admin/tool/logviewer | ✗ | ✓ | ? |
| theme/core | ✗ | ✓ | ? |
| theme/iser | ✗ | ✓ | ? |
| report/log | ✗ | ? | ? |

### 🔴 HARDCODED STRINGS (51+ instances)

**Most Critical Files**:
- admin/roles/lib.php - 26 strings
- admin/user/lib.php - 19 strings  
- admin/tool/dataprivacy/lib.php - 5 strings
- report/log/lib.php - 5 strings
- core/Bootstrap.php - 4 strings
- public_html/index.php - 3 strings

### 🔴 GLOBAL FUNCTIONS (50+ functions)

**Scope**: Convert to class methods
- lib/setup.php - 8 functions → ComponentManager class
- lib/accesslib.php - 15 functions → AccessManager class
- lib/compat/roles_compat.php - 2 functions → Compatibility layer (keep for now)
- admin/user/lib.php - 4 functions → UserManager class
- admin/roles/lib.php - 8 functions → RoleManager class
- admin/tool/dataprivacy/lib.php - 4 functions → PrivacyManager class
- admin/tool/dataprivacy/db/install.php - 3 functions → Schema class
- report/log/lib.php - 4 functions → LogManager class

---

## ORPHANED DIRECTORIES

- `/home/user/NexoSupport/user/` - Empty, no content
- `/home/user/NexoSupport/login/` - Empty, no content

**Action**: Remove or populate with content

---

## DEPENDENCY INJECTION ISSUES

**Global $DB usage** (19 instances):
- auth/manual/auth.php - Lines 38, 68, 207
- report/log/lib.php - Lines 32, 74
- admin/tool/mfa/factor/*/classes/factor.php - Multiple
- Others in plugins

**Fix**: Use dependency injection via constructor

---

## LANGUAGE FILES STATUS

| Plugin | en/ | es/ | Status |
|--------|:---:|:---:|--------|
| auth/manual | ✗ | ✓ | Missing English |
| admin/user | ✗ | ✓ | Missing English |
| admin/roles | ✗ | ✓ | Missing English |
| admin/tool/* | ✗ | ✓ | Missing English |
| theme/* | ✗ | ✓ | Missing English |
| report/log | ✗ | ✓ | Missing English |

---

## QUICK MIGRATION PRIORITY

### Phase 1 - CRITICAL (Week 1-2)
1. Fix all 13 namespace issues
2. Refactor auth/manual completely
3. Refactor report/log completely
4. Add settings.php to 12 plugins

### Phase 2 - IMPORTANT (Week 2-3)
1. Replace 51 hardcoded strings
2. Convert 50 global functions to classes
3. Fix global $DB usage patterns
4. Add English language files

### Phase 3 - NICE TO HAVE (Week 3-4)
1. Migrate theme layouts to Mustache
2. Populate empty classes/ directories
3. Add db/ directories where needed
4. Add comprehensive unit tests

---

## FILES TO CREATE

### New settings.php files (12 needed)
Template:
```php
<?php
defined('NEXOSUPPORT_INTERNAL') || die();

$settings = new admin_settingpage('pluginsettings_[component]', new lang_string('settings', '[component]'));
// Add settings here

// Register the setting page
$ADMIN->add('[admin area]', $settings);
```

### New class files
- admin/user/classes/ - User management classes
- admin/roles/classes/ - Role management classes
- admin/tool/mfa/factor/*/classes/ - Factor implementations
- And more

---

**TOTAL ESTIMATED EFFORT**: 3-4 weeks
**Team size**: 2-3 developers
**Testing**: 1 week

