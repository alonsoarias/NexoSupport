# Test Plugin Conflict

**Version:** 1.0.0
**Type:** Tool
**Status:** Test Plugin
**Conflicts:** test-plugin-a

## Purpose

Test plugin designed to demonstrate conflict detection and prevention in the NexoSupport plugin system.

## Conflict Definition

This plugin declares a conflict with **Test Plugin A** via the `conflicts_with` field in `plugin.json`:

```json
{
  "conflicts_with": ["test-plugin-a"]
}
```

## Why Conflicts Exist

Plugins may conflict for several reasons:
1. **Function Name Conflicts**: Both register global functions with the same names
2. **Resource Conflicts**: Both try to use exclusive resources
3. **Feature Incompatibility**: Provide mutually exclusive functionality
4. **API Conflicts**: Both hook into the same endpoints incompatibly

## Testing Scenarios

### Installation Conflicts

**Scenario 1: Install Conflict Plugin when Plugin A is active**
1. Install and enable Test Plugin A
2. Try to install Test Plugin Conflict
3. **Expected Result**: Installation fails with conflict error
4. **Error Message**: "Plugin conflicts with enabled plugins: Test Plugin A"

**Scenario 2: Install Plugin A when Conflict Plugin is active**
1. Install and enable Test Plugin Conflict
2. Try to install Test Plugin A
3. **Expected Result**: Installation fails with conflict error
4. **Error Message**: "Plugin conflicts with enabled plugins: Test Plugin Conflict"

### Enable/Disable Conflicts

**Scenario 3: Enable Conflict Plugin when Plugin A is active**
1. Install both plugins (both disabled)
2. Enable Test Plugin A
3. Try to enable Test Plugin Conflict
4. **Expected Result**: Enable fails with conflict warning
5. **Log Message**: "Plugin has conflicts with enabled plugins"

**Scenario 4: Enable Plugin A when Conflict Plugin is active**
1. Install both plugins (both disabled)
2. Enable Test Plugin Conflict
3. Try to enable Test Plugin A
4. **Expected Result**: Enable fails with conflict warning

### Conflict Resolution

**Scenario 5: Resolve conflict by disabling**
1. Test Plugin A is active, Conflict Plugin is installed but disabled
2. Disable Test Plugin A
3. Enable Test Plugin Conflict
4. **Expected Result**: Success - no conflict as Plugin A is now disabled

**Scenario 6: Uninstall to resolve conflict**
1. Test Plugin Conflict is active
2. Disable Test Plugin Conflict
3. Uninstall Test Plugin Conflict
4. Install and enable Test Plugin A
5. **Expected Result**: Success - conflict removed

## Conflict Detection Implementation

The plugin system checks for conflicts in:

### PluginManager::enable()
```php
// Check for conflicts before enabling
$conflicts = $this->checkPluginConflicts($slug);
if ($conflicts['has_conflicts']) {
    Logger::warning('Plugin has conflicts with enabled plugins', [
        'slug' => $slug,
        'conflicts' => array_column($conflicts['conflicts'], 'slug')
    ]);
    return false;
}
```

### PluginInstaller::install()
```php
// Check for conflicts
$conflictCheck = $this->checkConflicts($manifest);
if ($conflictCheck['has_conflicts']) {
    return [
        'success' => false,
        'message' => 'Plugin conflicts with enabled plugins: ' . $conflictList
    ];
}
```

## Bidirectional Conflict Detection

Note that conflicts should be declared **bidirectionally** for best results:

**Test Plugin A** declares:
```json
{
  "conflicts_with": ["test-plugin-conflict"]
}
```

**Test Plugin Conflict** declares:
```json
{
  "conflicts_with": ["test-plugin-a"]
}
```

This ensures conflict detection works regardless of installation order.

## Development Guidelines

When creating plugins that conflict:

1. **Declare Explicitly**: Use `conflicts_with` in both plugins
2. **Document Reasons**: Explain why plugins conflict in README
3. **Test Both Directions**: Verify A→B and B→A conflict detection
4. **Provide Alternatives**: Suggest which plugin to use for which use case
5. **Clear Error Messages**: Help users understand and resolve conflicts

## Expected Behavior

✅ **Should Work:**
- Install both plugins (but not enable both)
- Enable one, then the other (must disable first one)
- Uninstall either plugin when disabled

❌ **Should Fail:**
- Enable both plugins simultaneously
- Install one when the other is enabled
- Enable one while the other is active

## Use Cases

This test plugin demonstrates conflict handling for:
- Alternative authentication methods
- Mutually exclusive payment gateways
- Incompatible theme engines
- Competing analytics providers
- Exclusive feature implementations

## Integration Testing

Test checklist:
- [ ] Cannot install when conflicting plugin is active
- [ ] Cannot enable when conflicting plugin is active
- [ ] Can install when conflicting plugin is disabled
- [ ] Can enable after disabling conflicting plugin
- [ ] Conflict is bidirectional (works both ways)
- [ ] Clear error messages displayed
- [ ] Conflicts logged appropriately
