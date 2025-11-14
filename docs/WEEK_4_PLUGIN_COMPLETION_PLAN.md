# Week 4: Plugin System Completion Plan

**Project:** NexoSupport
**Phase:** Week 4 - Plugin System Completion (75% → 100%)
**Date:** 2025-11-14
**Estimated Time:** 20 hours (5 days × 4 hours/day)

---

## CURRENT STATUS ANALYSIS

### What's Implemented ✅ (75%)

**Core Infrastructure:**
- ✅ **HookManager** - Event system fully functional
- ✅ **PluginLoader** - Plugin discovery and loading works
- ✅ **PluginManager** - CRUD operations complete
- ✅ **PluginInstaller** - ZIP installation works
- ✅ **Database Tables** - 5 tables properly designed:
  - `plugins` - Plugin registry
  - `plugin_hooks` - Hook registrations
  - `plugin_routes` - Custom routes
  - `plugin_permissions` - Permission requirements
  - `plugin_config` - Configuration storage

**Dependency Features (Partially Implemented):**
- ✅ `checkDependencies()` - Checks if dependencies are satisfied
- ✅ `getDependents()` - Finds plugins that depend on current plugin
- ✅ `isVersionCompatible()` - Version constraint validation
- ✅ Prevents uninstalling plugins with dependents
- ✅ Prevents enabling plugins with unsatisfied dependencies

**Manifest Support:**
- ✅ `plugin.json` parsing works
- ✅ Support for `requires.plugins` in manifest
- ✅ Support for version constraints (exact, >=, >, <=, <)
- ✅ Support for `depends_on`, `recommends`, `conflicts_with`
- ✅ Support for `config_schema` (parsed but not used yet)

**Admin UI:**
- ✅ Plugin list view
- ✅ Enable/disable functionality
- ✅ Install from ZIP
- ✅ Uninstall functionality
- ✅ Plugin details view

### What's Missing ❌ (25%)

**Dependency Resolution:**
- ❌ **Automatic dependency installation** - No auto-install of missing dependencies
- ❌ **Dependency order installation** - No ordering by dependency graph
- ❌ **Circular dependency detection** - Basic check exists but needs enhancement
- ❌ **Conflict resolution** - Doesn't check `conflicts_with` field
- ❌ **Recommendation handling** - `recommends` field is ignored

**Plugin Configuration:**
- ❌ **Configuration UI** - No UI to configure plugins via admin panel
- ❌ **Settings form generation** - config_schema not used to generate forms
- ❌ **Settings validation** - No validation of config values
- ❌ **Settings persistence** - plugin_config table exists but no API to use it

**Update System:**
- ❌ **Plugin updates** - No way to update installed plugins
- ❌ **Update checking** - No check for available updates
- ❌ **Upgrade scripts** - upgrade/ directory supported but not executed

**Documentation:**
- ❌ **Plugin Development Guide** - No comprehensive guide for developers
- ❌ **Configuration Guide** - No guide on how to configure plugins
- ❌ **API Documentation** - Limited inline documentation

---

## WEEK 4 IMPLEMENTATION PLAN

### Days 1-2: Dependency Resolution (8 hours)

#### Goal: Automatic installation of plugin dependencies

**Tasks:**

1. **Create DependencyResolver Class** (3 hours)
   - `resolveDependencies(string $slug): array` - Build dependency tree
   - `getInstallOrder(array $plugins): array` - Topological sort
   - `detectCircularDependencies(array $tree): bool` - Cycle detection
   - `checkConflicts(array $plugins): array` - Conflict checking

2. **Enhance PluginInstaller** (3 hours)
   - Add `installWithDependencies(string $zipPath, bool $autoInstallDeps = true): array`
   - Integrate DependencyResolver
   - Install dependencies in correct order
   - Handle installation failures gracefully

3. **Add Conflict Checking** (2 hours)
   - Check `conflicts_with` field during installation
   - Prevent installing conflicting plugins
   - Show clear error messages about conflicts

**Deliverables:**
- `/core/Plugin/DependencyResolver.php` (new file, ~300 lines)
- Updated `/modules/Plugin/PluginInstaller.php` (+100 lines)
- Updated `/modules/Plugin/PluginManager.php` (+50 lines for conflict checking)

#### Technical Details:

**DependencyResolver Algorithm:**
```
1. Start with target plugin
2. Parse its dependencies from manifest
3. For each dependency:
   a. Check if already installed (if yes, skip)
   b. Check if available in repository (for now, just check)
   c. Add to dependency tree
   d. Recursively resolve its dependencies
4. Perform topological sort to get install order
5. Check for circular dependencies (DFS cycle detection)
6. Check for conflicts
7. Return ordered list of plugins to install
```

**Conflict Detection:**
```
1. For each plugin to install:
   a. Check its conflicts_with list
   b. Check if any conflicting plugin is installed
   c. If conflict found, return error
2. Return list of conflicts or empty if none
```

---

### Days 3-4: Plugin Configuration UI (8 hours)

#### Goal: Admin UI for plugin configuration

**Tasks:**

1. **Create PluginConfigurator Class** (2 hours)
   - `getConfig(string $slug): array` - Get plugin configuration
   - `setConfig(string $slug, array $config): bool` - Save configuration
   - `validateConfig(string $slug, array $config): array` - Validate against schema
   - `getDefaultConfig(string $slug): array` - Get defaults from manifest

2. **Create Configuration Form Generator** (3 hours)
   - Parse `config_schema` from plugin manifest
   - Generate HTML form based on schema
   - Support types: string, int, bool, select, textarea, email, url
   - Apply validation rules (required, min, max, pattern)
   - Generate JavaScript for client-side validation

3. **Create Configuration UI Views** (2 hours)
   - Plugin settings page template
   - AJAX form submission
   - Success/error message handling
   - Configuration preview/reset functionality

4. **Create API Endpoints** (1 hour)
   - `POST /admin/plugins/{slug}/configure` - Save configuration
   - `GET /admin/plugins/{slug}/configure` - Get current configuration
   - `POST /admin/plugins/{slug}/configure/reset` - Reset to defaults

**Deliverables:**
- `/modules/Plugin/PluginConfigurator.php` (new file, ~250 lines)
- `/core/Plugin/ConfigFormGenerator.php` (new file, ~400 lines)
- `/resources/views/admin/plugins/configure.mustache` (new file, ~150 lines)
- `/modules/Controllers/PluginController.php` (updated, +150 lines)
- `/public/assets/js/plugin-config.js` (new file, ~200 lines)

#### Technical Details:

**Config Schema Example:**
```json
{
  "config_schema": {
    "api_key": {
      "type": "string",
      "required": true,
      "label": "API Key",
      "description": "Your service API key",
      "placeholder": "Enter your API key"
    },
    "enable_feature": {
      "type": "bool",
      "default": true,
      "label": "Enable Feature X"
    },
    "max_items": {
      "type": "int",
      "default": 10,
      "min": 1,
      "max": 100,
      "label": "Maximum Items"
    },
    "theme_color": {
      "type": "select",
      "default": "blue",
      "options": ["blue", "red", "green"],
      "label": "Theme Color"
    }
  }
}
```

**Form Generation Logic:**
```
1. Load plugin manifest
2. Extract config_schema
3. For each config field:
   a. Determine field type
   b. Generate appropriate HTML input
   c. Add validation attributes
   d. Add label and description
4. Add CSRF token
5. Add submit button
6. Generate JavaScript validation
```

---

### Day 5: Testing & Documentation (4 hours)

#### Goal: Comprehensive testing and documentation

**Tasks:**

1. **Create Test Plugins** (1.5 hours)
   - Test plugin with dependencies (plugin-a depends on plugin-b)
   - Test plugin with configuration
   - Test plugin with conflicts
   - Test circular dependency scenario

2. **Integration Testing** (1.5 hours)
   - Test dependency installation flow
   - Test configuration save/load/reset
   - Test conflict detection
   - Test error handling

3. **Update Documentation** (1 hour)
   - Update PLUGIN_SYSTEM_SPECIFICATION.md to 100% complete
   - Create PLUGIN_DEVELOPMENT_GUIDE.md
   - Add configuration examples
   - Add dependency examples

**Deliverables:**
- `/modules/plugins/tools/test-plugin-a/` (test plugin)
- `/modules/plugins/tools/test-plugin-b/` (dependency)
- Updated `/PLUGIN_SYSTEM_SPECIFICATION.md`
- New `/docs/PLUGIN_DEVELOPMENT_GUIDE.md` (~1,500 lines)

---

## SUCCESS CRITERIA

### Dependency Resolution
- [ ] Plugins with dependencies install automatically
- [ ] Dependencies install in correct order
- [ ] Circular dependencies are detected and prevented
- [ ] Conflicting plugins cannot be installed together
- [ ] Clear error messages for all failure scenarios

### Plugin Configuration
- [ ] Plugin settings page accessible from admin
- [ ] Form generates correctly from config_schema
- [ ] Configuration saves to database
- [ ] Configuration loads on plugin init
- [ ] Validation works (client-side and server-side)
- [ ] Reset to defaults works
- [ ] All field types supported (string, int, bool, select)

### Testing
- [ ] Test plugins created and working
- [ ] All scenarios tested
- [ ] No regressions in existing functionality
- [ ] Error handling robust

### Documentation
- [ ] PLUGIN_SYSTEM_SPECIFICATION.md updated to 100%
- [ ] PLUGIN_DEVELOPMENT_GUIDE.md complete
- [ ] Code examples provided
- [ ] API documented

---

## IMPLEMENTATION DETAILS

### File Structure (New Files)

```
/core/Plugin/
├── DependencyResolver.php         (NEW - 300 lines)
└── ConfigFormGenerator.php        (NEW - 400 lines)

/modules/Plugin/
└── PluginConfigurator.php         (NEW - 250 lines)

/modules/Controllers/
└── PluginController.php           (UPDATED +150 lines)

/resources/views/admin/plugins/
└── configure.mustache             (NEW - 150 lines)

/public/assets/js/
└── plugin-config.js               (NEW - 200 lines)

/modules/plugins/tools/
├── test-plugin-a/                 (NEW - test plugin)
└── test-plugin-b/                 (NEW - dependency)

/docs/
└── PLUGIN_DEVELOPMENT_GUIDE.md    (NEW - 1,500 lines)
```

### Total Lines of Code to Add/Modify

- New code: ~2,950 lines
- Modified code: ~200 lines
- **Total: ~3,150 lines**

---

## RISK MITIGATION

### Potential Risks

1. **Circular Dependency Hell**
   - Mitigation: Robust cycle detection using DFS
   - Fallback: Manual dependency specification

2. **Configuration Schema Complexity**
   - Mitigation: Start with basic types, expand later
   - Fallback: JSON editor as alternative

3. **Performance Impact**
   - Mitigation: Cache dependency trees
   - Fallback: Optional auto-install, manual by default

4. **Breaking Changes**
   - Mitigation: Thorough testing before deployment
   - Fallback: Git rollback available

---

## TIMELINE

| Day | Tasks | Hours | Status |
|-----|-------|-------|--------|
| Day 1 | DependencyResolver class | 4h | Pending |
| Day 2 | Enhance PluginInstaller, Conflict checking | 4h | Pending |
| Day 3 | PluginConfigurator, ConfigFormGenerator | 4h | Pending |
| Day 4 | Configuration UI, API endpoints | 4h | Pending |
| Day 5 | Testing, Documentation | 4h | Pending |
| **Total** | **Week 4 Complete** | **20h** | **0%** |

---

## NEXT STEPS

**Immediate Action:**
1. Create DependencyResolver class
2. Implement topological sort algorithm
3. Add circular dependency detection
4. Integrate with PluginInstaller

**After Week 4:**
- Week 5-6: Theme System Implementation
- Week 7-8: Installer Redesign
- Week 9-11: Update System Implementation

---

**Status:** 📋 Ready to execute
**Approval:** ✅ Approved for implementation
**Start Date:** 2025-11-14

---

**Document End**
