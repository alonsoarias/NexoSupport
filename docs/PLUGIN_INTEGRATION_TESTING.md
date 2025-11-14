# Plugin System Integration Testing Guide

**Document Version:** 1.0.0
**Date:** 2025-11-14
**Status:** Complete
**Week:** 4 - Plugin System Completion

---

## Overview

Comprehensive testing guide for the NexoSupport plugin system. Covers all features implemented in Week 4 including dependency resolution, conflict detection, and configuration management.

---

## Test Environment

### Prerequisites

- NexoSupport >= 1.0.0
- PHP >= 8.0
- MySQL/MariaDB
- Admin account access
- Test plugins installed (test-plugin-a, test-plugin-b, test-plugin-config, test-plugin-conflict)

### Database Tables

Ensure all plugin tables exist:
- `plugins` - Plugin registry
- `plugin_hooks` - Hook registrations
- `plugin_routes` - Custom routes
- `plugin_permissions` - Permission requirements
- `plugin_config` - Configuration storage

---

## Test Suite 1: Dependency Resolution

### Test 1.1: Basic Dependency Installation

**Objective**: Verify auto-dependency installation

**Steps**:
1. Ensure test-plugin-b is NOT installed
2. Create ZIP for test-plugin-a
3. Upload test-plugin-a via `/admin/plugins/upload`
4. Click "Install"

**Expected Result**:
- ✅ System detects test-plugin-b dependency
- ✅ Automatically installs test-plugin-b first
- ✅ Then installs test-plugin-a
- ✅ Both plugins appear in plugin list
- ✅ Installation order logged correctly

**Validation**:
```sql
SELECT slug, name, version, enabled, created_at
FROM plugins
WHERE slug IN ('test-plugin-a', 'test-plugin-b')
ORDER BY created_at;
```

---

### Test 1.2: Dependency Version Validation

**Objective**: Verify version constraint checking

**Steps**:
1. Manually modify test-plugin-b version in database to "0.9.0"
2. Try to install test-plugin-a (requires >= 1.0.0)

**Expected Result**:
- ❌ Installation fails
- ❌ Error message: "Incompatible version: test-plugin-a requires test-plugin-b >=1.0.0, but 0.9.0 is installed"

**Validation**:
Check logs for version mismatch error

---

### Test 1.3: Dependency Uninstall Prevention

**Objective**: Prevent uninstalling plugins with dependents

**Steps**:
1. Install both test-plugin-a and test-plugin-b
2. Enable both plugins
3. Try to uninstall test-plugin-b

**Expected Result**:
- ❌ Uninstall fails
- ❌ Error: "Cannot uninstall plugin with dependents"
- ✅ Lists test-plugin-a as dependent
- ✅ test-plugin-b remains installed

---

### Test 1.4: Circular Dependency Detection

**Objective**: Detect and prevent circular dependencies

**Steps**:
1. Create test-plugin-c that depends on test-plugin-d
2. Create test-plugin-d that depends on test-plugin-c
3. Try to install test-plugin-c

**Expected Result**:
- ❌ Installation fails
- ❌ Error: "Circular dependency detected"
- ✅ Dependency graph logged
- ❌ No partial installation

---

### Test 1.5: Topological Sort Verification

**Objective**: Verify correct installation order

**Test Case**: A→B→C dependency chain

**Steps**:
1. Create 3 plugins: A depends on B, B depends on C
2. Package all 3 as ZIPs
3. Install plugin A

**Expected Result**:
- ✅ Installation order: C → B → A
- ✅ All dependencies resolved
- ✅ All plugins installed successfully

**Validation**:
Check `created_at` timestamps in database confirm C < B < A

---

## Test Suite 2: Conflict Detection

### Test 2.1: Installation Conflict (A installed, try B)

**Steps**:
1. Install and enable test-plugin-a
2. Try to install test-plugin-conflict

**Expected Result**:
- ❌ Installation fails
- ❌ Error: "Plugin conflicts with enabled plugins: Test Plugin A"
- ✅ test-plugin-conflict NOT in database
- ✅ test-plugin-a remains active

---

### Test 2.2: Installation Conflict (B installed, try A)

**Steps**:
1. Install and enable test-plugin-conflict
2. Try to install test-plugin-a

**Expected Result**:
- ❌ Installation fails
- ❌ Error: "Plugin conflicts with enabled plugins: Test Plugin Conflict"
- ✅ Bidirectional conflict detected

---

### Test 2.3: Enable Conflict Prevention

**Steps**:
1. Install both test-plugin-a and test-plugin-conflict (both disabled)
2. Enable test-plugin-a
3. Try to enable test-plugin-conflict

**Expected Result**:
- ❌ Enable fails
- ❌ Warning logged: "Plugin has conflicts with enabled plugins"
- ✅ test-plugin-conflict remains disabled
- ✅ test-plugin-a remains enabled

---

### Test 2.4: Conflict Resolution by Disabling

**Steps**:
1. test-plugin-a is enabled
2. Disable test-plugin-a
3. Enable test-plugin-conflict

**Expected Result**:
- ✅ Disable test-plugin-a succeeds
- ✅ Enable test-plugin-conflict succeeds
- ✅ No conflict (only one active)

---

## Test Suite 3: Configuration System

### Test 3.1: Configuration Form Generation

**Steps**:
1. Install test-plugin-config
2. Navigate to `/admin/plugins/test-plugin-config/configure`

**Expected Result**:
- ✅ Form displays with 12 fields
- ✅ All field types render correctly:
  - Text input (api_key, welcome_message)
  - URL input (api_endpoint)
  - Email input (admin_email)
  - Checkbox (enable_feature, enable_logging)
  - Number input (max_items, cache_timeout)
  - Select dropdown (theme_color)
  - Radio buttons (notification_type)
  - Textarea (custom_css)
  - Password input (api_secret)
- ✅ Labels, descriptions, placeholders visible
- ✅ Default values pre-filled
- ✅ Required fields marked with *

---

### Test 3.2: Client-Side Validation

**Steps**:
1. On config form, leave required fields empty
2. Click "Save Configuration"

**Expected Result**:
- ❌ Form does not submit
- ✅ Error messages appear: "This field is required"
- ✅ Invalid fields highlighted in red
- ✅ No AJAX request sent

**Test Each Field Type**:
- Email: Enter "invalid" → Error: "Please enter a valid email"
- URL: Enter "not-url" → Error: "Please enter a valid URL"
- Number: Enter "abc" → Error: "Please enter a valid number"
- Min/Max: Enter "0" for max_items → Error: "Value must be at least 1"

---

### Test 3.3: Server-Side Validation

**Steps**:
1. Use browser dev tools to bypass client validation
2. Submit invalid data via AJAX

**Expected Result**:
- ✅ Server validates and rejects
- ✅ Returns JSON with errors
```json
{
  "success": false,
  "errors": {
    "api_key": "API Key is required",
    "admin_email": "Administrator Email: must be a valid email"
  }
}
```
- ✅ Errors display next to fields

---

### Test 3.4: Configuration Save and Load

**Steps**:
1. Fill valid configuration:
   - api_key: "test_1234567890abcdefghijklmnopqrst"
   - api_endpoint: "https://api.test.com"
   - admin_email: "admin@test.com"
   - enable_feature: true
   - max_items: 25
   - theme_color: "purple"
2. Click "Save Configuration"
3. Reload page

**Expected Result**:
- ✅ Success message: "Configuration saved successfully"
- ✅ Values persisted in database
- ✅ Values reload correctly on page refresh
- ✅ All field types preserve values

**Database Validation**:
```sql
SELECT config_key, config_value
FROM plugin_config
WHERE plugin_slug = 'test-plugin-config';
```

---

### Test 3.5: Reset to Defaults

**Steps**:
1. Save custom configuration
2. Click "Reset to Defaults"
3. Confirm reset

**Expected Result**:
- ✅ Confirmation dialog appears
- ✅ Configuration reset to defaults
- ✅ Info message: "Configuration reset to defaults"
- ✅ Form fields update to default values
- ✅ Database records deleted

**Validation**:
```sql
-- Should return 0 rows after reset
SELECT COUNT(*) FROM plugin_config
WHERE plugin_slug = 'test-plugin-config';
```

---

### Test 3.6: Config Range Validation

**Field:** max_items (min: 1, max: 100)

**Test Cases**:
| Input | Expected |
|-------|----------|
| 0     | ❌ Error: "Value must be at least 1" |
| 1     | ✅ Valid |
| 50    | ✅ Valid |
| 100   | ✅ Valid |
| 101   | ❌ Error: "Value must be at most 100" |
| -5    | ❌ Error: "Value must be at least 1" |

---

### Test 3.7: Config Pattern Validation

**Field:** api_key (pattern: `^[A-Za-z0-9_-]+$`)

**Test Cases**:
| Input | Expected |
|-------|----------|
| "abc123_-" | ✅ Valid |
| "ABC-xyz" | ✅ Valid |
| "test@key" | ❌ Invalid (contains @) |
| "key with spaces" | ❌ Invalid (contains spaces) |
| "key#123" | ❌ Invalid (contains #) |

---

## Test Suite 4: Plugin Lifecycle

### Test 4.1: Discovery and Installation

**Steps**:
1. Navigate to `/admin/plugins`
2. Click "Upload Plugin"
3. Select test-plugin-b ZIP
4. Click "Upload and Install"

**Expected Result**:
- ✅ ZIP uploaded successfully
- ✅ Manifest extracted and validated
- ✅ Plugin structure validated
- ✅ Database record created
- ✅ Files copied to `/modules/plugins/tools/test-plugin-b/`
- ✅ Success message displayed

---

### Test 4.2: Enable and Disable

**Steps**:
1. Install test-plugin-b (disabled)
2. Click "Enable"
3. Verify status
4. Click "Disable"

**Expected Result**:
- ✅ Enable: `enabled` column set to 1
- ✅ Enable: `activated_at` timestamp set
- ✅ Disable: `enabled` column set to 0
- ✅ Disable: `activated_at` unchanged
- ✅ Status badge updates correctly

---

### Test 4.3: Uninstallation

**Steps**:
1. Disable test-plugin-b
2. Ensure no plugins depend on it
3. Click "Uninstall"
4. Confirm

**Expected Result**:
- ✅ Database record deleted from `plugins`
- ✅ Related config deleted from `plugin_config`
- ✅ Related hooks deleted from `plugin_hooks`
- ✅ Related routes deleted from `plugin_routes`
- ✅ Related permissions deleted from `plugin_permissions`
- ✅ Files can remain or be deleted (depends on implementation)

---

## Test Suite 5: Edge Cases and Error Handling

### Test 5.1: Invalid Plugin ZIP

**Test Cases**:

**No plugin.json**:
- Upload ZIP without plugin.json
- ❌ Error: "Invalid plugin structure - missing required files"

**Invalid JSON**:
- plugin.json contains syntax error
- ❌ Error: "Failed to parse plugin manifest"

**Missing Required Fields**:
- plugin.json missing "name" or "slug"
- ❌ Error: "Invalid manifest: missing required field 'name'"

---

### Test 5.2: Duplicate Slug Installation

**Steps**:
1. Install test-plugin-a (version 1.0.0)
2. Try to install test-plugin-a again

**Expected Result**:
- ❌ Error: "Plugin already installed: test-plugin-a"
- ✅ Existing installation unchanged

---

### Test 5.3: Missing Dependency

**Steps**:
1. Create plugin that depends on non-existent "fake-plugin"
2. Try to install

**Expected Result**:
- ❌ Error: "Plugin not found: fake-plugin"
- ❌ Installation fails
- ❌ No partial installation

---

### Test 5.4: Configuration Without Schema

**Steps**:
1. Install test-plugin-b (no config_schema)
2. Navigate to `/admin/plugins/test-plugin-b/configure`

**Expected Result**:
- ✅ Page loads successfully
- ✅ Message: "This plugin has no configuration options"
- ✅ No form displayed
- ✅ Link back to plugin list

---

## Test Suite 6: Performance and Scalability

### Test 6.1: Large Dependency Tree

**Scenario**: Install plugin with 10-level dependency chain

**Expected Result**:
- ✅ All dependencies resolved correctly
- ✅ Installation completes within reasonable time (<30s)
- ✅ Correct topological order
- ✅ No memory issues

---

### Test 6.2: Many Plugins Installed

**Scenario**: 50+ plugins in database

**Expected Result**:
- ✅ Plugin list loads quickly (<2s)
- ✅ Search/filter works efficiently
- ✅ Enable/disable operations fast (<1s)
- ✅ No pagination issues

---

## Automated Testing Commands

### Database Verification

```sql
-- Verify all test plugins
SELECT slug, name, version, type, enabled
FROM plugins
WHERE slug LIKE 'test-plugin-%';

-- Check dependencies
SELECT p1.name AS plugin, p2.name AS dependency
FROM plugins p1
JOIN plugins p2 ON JSON_CONTAINS(p1.manifest, CONCAT('"', p2.slug, '"'), '$.requires.plugins[*].slug');

-- Check conflicts
SELECT slug, JSON_EXTRACT(manifest, '$.conflicts_with') AS conflicts
FROM plugins
WHERE JSON_EXTRACT(manifest, '$.conflicts_with') IS NOT NULL;

-- Verify configuration
SELECT plugin_slug, COUNT(*) as config_count
FROM plugin_config
GROUP BY plugin_slug;
```

---

## Test Report Template

```markdown
# Plugin System Test Report

**Date**: YYYY-MM-DD
**Tester**: [Name]
**Environment**: [Production/Staging/Dev]

## Test Results

| Test Suite | Passed | Failed | Skipped | Total |
|------------|--------|--------|---------|-------|
| Dependency Resolution | X | Y | Z | 5 |
| Conflict Detection | X | Y | Z | 4 |
| Configuration System | X | Y | Z | 7 |
| Plugin Lifecycle | X | Y | Z | 3 |
| Edge Cases | X | Y | Z | 4 |
| Performance | X | Y | Z | 2 |

## Failed Tests

1. Test 2.3 - Enable Conflict Prevention
   - Expected: Enable fails
   - Actual: Enable succeeded (BUG)
   - Priority: High

## Notes

- Configuration system working perfectly
- Minor UI bug in plugin list pagination
- Performance excellent with 100+ plugins

## Recommendation

✅ PASS - System ready for production
```

---

## Checklist

Use this checklist to verify all features:

### Dependency Resolution
- [ ] Auto-dependency installation
- [ ] Version constraint validation
- [ ] Topological sorting (correct order)
- [ ] Circular dependency detection
- [ ] Dependency uninstall prevention

### Conflict Detection
- [ ] Installation conflict prevention
- [ ] Enable conflict prevention
- [ ] Bidirectional conflict detection
- [ ] Clear conflict error messages

### Configuration System
- [ ] Form generation (all 12 types)
- [ ] Client-side validation
- [ ] Server-side validation
- [ ] Configuration persistence
- [ ] Reset to defaults
- [ ] Range validation (min/max)
- [ ] Pattern validation (regex)
- [ ] Required field validation

### General
- [ ] Plugin discovery
- [ ] ZIP upload and installation
- [ ] Enable/disable functionality
- [ ] Uninstall with cleanup
- [ ] Error handling
- [ ] Logging

---

## Known Issues

Document any known issues here:

1. **Issue**: [Description]
   - **Severity**: High/Medium/Low
   - **Workaround**: [If available]
   - **Status**: Open/In Progress/Fixed

---

## Conclusion

This integration testing guide ensures comprehensive validation of all plugin system features implemented in Week 4. Execute all test suites before marking the plugin system as production-ready.

**Testing Effort**: ~8-10 hours for complete suite
**Recommended Frequency**: Before each major release
**Automation Potential**: High (80% of tests can be automated)

---

**Document End**
