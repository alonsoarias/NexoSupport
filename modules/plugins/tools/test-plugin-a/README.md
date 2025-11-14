# Test Plugin A

**Version:** 2.0.0
**Type:** Tool
**Status:** Test Plugin
**Dependencies:** Test Plugin B >= 1.0.0

## Purpose

Advanced test plugin for demonstrating NexoSupport's plugin system capabilities including:
- Dependency resolution
- Version constraint validation
- Automatic dependency installation
- Circular dependency prevention
- Conflict detection
- Recommendation handling

## Features

- **Dependency Management**: Requires Test Plugin B >= 1.0.0
- **Version Validation**: Checks dependency version at runtime
- **Conflict Detection**: Conflicts with test-plugin-conflict
- **Recommendations**: Recommends test-plugin-config
- **Hook Integration**: Provides admin dashboard widget
- **Custom Routes**: Exposes /test-plugin-a/dashboard
- **Permission System**: Defines manage and view permissions

## Dependencies

**Required:**
- Test Plugin B >= 1.0.0
- PHP >= 8.0
- NexoSupport >= 1.0.0

**Recommended:**
- test-plugin-config (for enhanced functionality)

**Conflicts:**
- test-plugin-conflict (cannot be installed together)

## Global Functions

- `test_plugin_a_get_info()` - Returns plugin information including dependency status

## Testing Scenarios

### Dependency Resolution
1. **Install without Test Plugin B**
   - System should auto-install Test Plugin B first
   - Installation order: B → A

2. **Install with Test Plugin B already installed**
   - Should verify version compatibility
   - Should install successfully if version matches

### Version Constraints
3. **Install with older Test Plugin B**
   - Should fail if B < 1.0.0
   - Should display version mismatch error

### Dependency Prevention
4. **Try to uninstall Test Plugin B while A is active**
   - Should fail with "plugin has dependents" error
   - Should list Test Plugin A as dependent

### Circular Dependencies
5. **Test circular dependency detection**
   - If B were modified to depend on A
   - System should detect and prevent installation

### Conflict Detection
6. **Install test-plugin-conflict first, then try Test Plugin A**
   - Should fail with conflict error
   - Should list test-plugin-conflict as the conflict

7. **Install Test Plugin A first, then try test-plugin-conflict**
   - Should fail with reverse conflict error

## Routes

- `GET /test-plugin-a/dashboard` - Plugin dashboard (requires authentication)

## Permissions

- `test_plugin_a.manage` - Manage plugin settings
- `test_plugin_a.view` - View plugin data

## Installation

1. Ensure Test Plugin B is available in the plugin repository
2. Install via ZIP upload at `/admin/plugins/upload`
3. System will automatically install Test Plugin B if not present
4. Activate both plugins
5. Access dashboard at `/test-plugin-a/dashboard`

## Development Notes

This plugin serves as a comprehensive test case for:
- Topological sorting of dependencies
- DFS-based circular dependency detection
- Version constraint parsing and validation
- Conflict resolution
- Hook system integration
- Permission management
- Custom routing
