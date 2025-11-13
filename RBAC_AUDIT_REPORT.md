# RBAC Audit Report
**Date:** 2025-11-13
**Status:** ✅ COMPLETED

## Executive Summary

Auditoría completa del sistema RBAC (Role-Based Access Control) en NexoSupport. Se identificó arquitectura dual de permisos que, aunque confusa en nomenclatura, **está funcionando correctamente** con separación de responsabilidades válida.

## Critical Issues Fixed

### 1. ✅ Namespace Mismatch in RoleManager
**File:** `/modules/Roles/RoleManager.php`
**Issue:** Namespace declarado como `ISER\Role` (singular) en vez de `ISER\Roles` (plural)
**Impact:** Violación PSR-4, potencial fallo de autoloading
**Fix:** Corregido a `namespace ISER\Roles;` (línea 5)

## Architecture Analysis

### Dual Permission System (BY DESIGN)

El sistema tiene **DOS** PermissionManager diferentes con propósitos distintos:

#### 1. `Permission\PermissionManager` (Simple CRUD - 182 líneas)
**Purpose:** Administrative CRUD operations for permission management
**Location:** `/modules/Permission/PermissionManager.php`
**Used in:**
- `modules/Controllers/PermissionController.php:34`
- `modules/Controllers/RoleController.php:37`

**Key Methods:**
- `getPermissions()` - List permissions with pagination
- `getPermissionsGroupedByModule()` - Group by module for UI
- `getPermissionRoles(int $permissionId)` - Get roles for permission
- `create()`, `update()`, `delete()` - CRUD operations
- `userHasPermission(int $userId, string $permissionSlug)` - Simple check

**Usage Pattern:** Managing permission data in admin UI

#### 2. `Roles\PermissionManager` (Moodle-style Capability System - 234 líneas)
**Purpose:** Runtime authorization and capability checking
**Location:** `/modules/Roles/PermissionManager.php`
**Used in:**
- `core/Middleware/PermissionMiddleware.php:18`
- `core/Middleware/AdminMiddleware.php`
- `app/Admin/plugins.php:23`
- `app/Admin/settings.php:23`

**Key Methods:**
- `hasCapability(int $userId, string $capability, int $contextId)` - Check permission
- `requireCapability()` - Enforce permission or throw exception
- `getUserCapabilities()` - Get all user capabilities
- `isAdmin()` - Admin check
- `assignCapability()`, `getRoleCapabilities()` - Capability management

**Capability Constants:**
- `CAP_INHERIT = 0`
- `CAP_ALLOW = 1`
- `CAP_PREVENT = -1`
- `CAP_PROHIBIT = -1000`

**Usage Pattern:** Moodle-inspired capability strings like `'moodle/user:create'`, `'moodle/site:config'`

## Directory Structure

```
modules/
├── Permission/
│   └── PermissionManager.php        # CRUD system
├── Roles/
│   ├── PermissionManager.php        # Capability system
│   ├── RoleManager.php              # ✅ Fixed namespace
│   └── RoleAssignment.php
└── Role/                             # ✅ Already deleted (no conflict)
```

## Import Patterns

### Controllers (use simple CRUD):
```php
use ISER\Permission\PermissionManager;  // CRUD operations
use ISER\Roles\RoleManager;             // Role management
```

### Middleware (use capability system):
```php
use ISER\Roles\PermissionManager;       // Authorization checks
use ISER\Roles\RoleAssignment;
```

## Recommendations for Task 6 (Consolidate RBAC)

### Option A: Keep Dual System with Better Naming (RECOMMENDED)
**Rationale:** Separation of concerns is architecturally sound

**Rename for clarity:**
1. `Permission\PermissionManager` → `Permission\PermissionRepository` or `Permission\PermissionCRUD`
2. `Roles\PermissionManager` → `Roles\AuthorizationService` or `Roles\CapabilityChecker`

**Effort:** 2-3 hours (rename + update imports)
**Risk:** Low (simple refactor)

### Option B: Merge into Single Manager
**Rationale:** Single source of truth, unified API

**Structure:**
```php
namespace ISER\Roles;

class PermissionManager {
    // CRUD methods from Permission\PermissionManager
    public function getPermissions() { ... }
    public function create() { ... }
    public function update() { ... }
    public function delete() { ... }

    // Capability methods from Roles\PermissionManager
    public function hasCapability() { ... }
    public function requireCapability() { ... }
    public function getUserCapabilities() { ... }
}
```

**Effort:** 6-8 hours (merge + testing)
**Risk:** Medium (complex merge, potential bugs)

### Option C: Keep As-Is with Documentation
**Rationale:** System works correctly, just needs docs

**Action:** Add comprehensive PHPDoc explaining dual system purpose
**Effort:** 30 minutes
**Risk:** None (no code changes)

## Database Tables Used

Both systems interact with:
- `roles` - Role definitions
- `permissions` - Permission definitions
- `role_permissions` - Many-to-many relationship
- `user_roles` - User role assignments
- `contexts` - Capability contexts (Moodle-style)

## Testing Recommendations

Before consolidation (Task 6):
1. Test permission CRUD operations in admin UI
2. Test middleware authorization with capabilities
3. Test role assignment/removal
4. Verify no namespace errors after RoleManager fix

## Conclusion

✅ **RBAC system is functional and well-architected**
✅ **Namespace issue fixed**
⚠️ **Naming is confusing** (two PermissionManagers)
📋 **Task 6 should focus on renaming for clarity, not merging**

**Estimated effort for Task 6:** 2-3 hours (Option A) vs 6-8 hours (Option B)
**Recommended approach:** Option A (rename for clarity)
