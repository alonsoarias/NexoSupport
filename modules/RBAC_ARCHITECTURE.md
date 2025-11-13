# RBAC Architecture Documentation
**Sistema de Control de Acceso Basado en Roles de NexoSupport**

## Overview

NexoSupport utiliza un **sistema RBAC dual** con separación clara de responsabilidades:

1. **Permission\PermissionManager** - CRUD administrativo
2. **Roles\PermissionManager** - Autorización en runtime

## Arquitectura

```
┌─────────────────────────────────────────────────────┐
│                   NexoSupport RBAC                  │
├─────────────────────────────────────────────────────┤
│                                                      │
│  ┌──────────────────────┐  ┌───────────────────┐  │
│  │  Permission\         │  │  Roles\           │  │
│  │  PermissionManager   │  │  PermissionManager│  │
│  │                      │  │                   │  │
│  │  (CRUD Admin)        │  │  (Authorization)  │  │
│  └──────────────────────┘  └───────────────────┘  │
│           │                          │             │
│           │                          │             │
│  ┌────────▼──────────┐    ┌─────────▼──────────┐ │
│  │  Controllers       │    │  Middleware        │ │
│  │  - Permission      │    │  - Permission      │ │
│  │  - Role            │    │  - Admin           │ │
│  └────────────────────┘    └────────────────────┘ │
│                                                     │
└─────────────────────────────────────────────────────┘
```

## 1. Permission\PermissionManager (CRUD)

**Ubicación:** `/modules/Permission/PermissionManager.php`

### Propósito
Gestión administrativa de permisos: crear, leer, actualizar, eliminar registros de la tabla `permissions`.

### Métodos Principales
```php
// Listado y consultas
getPermissions(int $limit, int $offset, array $filters): array
getPermissionsGroupedByModule(): array
getPermissionById(int $id): ?array
getPermissionBySlug(string $slug): ?array
countPermissions(array $filters): int

// CRUD operations
create(array $data): int
update(int $id, array $data): bool
delete(int $id): bool

// Relaciones
getPermissionRoles(int $permissionId): array
getModules(): array

// Verificación simple
userHasPermission(int $userId, string $permissionSlug): bool
```

### Usado En
- `modules/Controllers/PermissionController.php` (línea 34)
- `modules/Controllers/RoleController.php` (línea 37)

### Ejemplo de Uso
```php
use ISER\Permission\PermissionManager;

$permManager = new PermissionManager($db);

// Crear nuevo permiso
$id = $permManager->create([
    'name' => 'Crear usuarios',
    'slug' => 'user.create',
    'module' => 'users',
    'description' => 'Permite crear nuevos usuarios'
]);

// Listar permisos por módulo
$grouped = $permManager->getPermissionsGroupedByModule();

// Actualizar permiso
$permManager->update($id, [
    'description' => 'Nueva descripción'
]);
```

## 2. Roles\PermissionManager (Authorization)

**Ubicación:** `/modules/Roles/PermissionManager.php`

### Propósito
Sistema de autorización en runtime tipo Moodle con capabilities y contextos.

### Constantes
```php
CAP_INHERIT  = 0      // Heredar de rol padre
CAP_ALLOW    = 1      // Permitir explícitamente
CAP_PREVENT  = -1     // Prevenir (puede ser override)
CAP_PROHIBIT = -1000  // Prohibir (no override)
```

### Métodos Principales
```php
// Verificación de permisos
hasCapability(int $userId, string $capability, int $contextId = 1): bool
requireCapability(int $userId, string $capability, int $contextId = 1): void
isAdmin(int $userId): bool

// Gestión de capabilities
getUserCapabilities(int $userId, int $contextId = 1): array
assignCapability(int $roleId, string $capability, int $permission, int $contextId): bool
getRoleCapabilities(int $roleId, int $contextId = 1): array

// Cache
clearUserCache(int $userId): void
```

### Usado En
- `core/Middleware/PermissionMiddleware.php` (línea 18)
- `core/Middleware/AdminMiddleware.php`
- `app/Admin/plugins.php` (línea 23)
- `app/Admin/settings.php` (línea 23)

### Ejemplo de Uso
```php
use ISER\Roles\PermissionManager;

$authService = new PermissionManager($db);

// Verificar si usuario puede crear otros usuarios
if ($authService->hasCapability($userId, 'moodle/user:create')) {
    // Permitir acción
}

// Requerir permiso (lanza excepción si no tiene)
$authService->requireCapability($userId, 'moodle/course:delete');

// Verificar si es administrador
if ($authService->isAdmin($userId)) {
    // Acceso total
}
```

## Formato de Capabilities

Inspirado en Moodle, las capabilities siguen el formato:

```
moodle/[módulo]:[acción]
```

### Ejemplos:
- `moodle/user:create` - Crear usuarios
- `moodle/user:update` - Actualizar usuarios
- `moodle/user:delete` - Eliminar usuarios
- `moodle/role:assign` - Asignar roles
- `moodle/site:config` - Configurar sistema (admin)

## Base de Datos

### Tablas Principales

```sql
-- Definición de roles
CREATE TABLE roles (
    id INT PRIMARY KEY,
    name VARCHAR(100),
    slug VARCHAR(50) UNIQUE,
    description TEXT,
    is_system BOOLEAN,
    created_at INT,
    updated_at INT
);

-- Definición de permisos
CREATE TABLE permissions (
    id INT PRIMARY KEY,
    name VARCHAR(100),
    slug VARCHAR(50) UNIQUE,
    module VARCHAR(50),
    description TEXT,
    created_at INT,
    updated_at INT
);

-- Relación role-permission
CREATE TABLE role_permissions (
    role_id INT,
    permission_id INT,
    granted_at INT,
    PRIMARY KEY (role_id, permission_id)
);

-- Asignación de roles a usuarios
CREATE TABLE user_roles (
    user_id INT,
    role_id INT,
    assigned_at INT,
    assigned_by INT,
    PRIMARY KEY (user_id, role_id)
);

-- Contextos (para sistema de capabilities)
CREATE TABLE contexts (
    id INT PRIMARY KEY,
    context_level INT,
    instance_id INT,
    path VARCHAR(255),
    depth INT
);
```

## Flujo de Trabajo

### Crear y Asignar Permisos

```php
// 1. Crear permiso (CRUD)
use ISER\Permission\PermissionManager;
$permManager = new PermissionManager($db);
$permId = $permManager->create([
    'name' => 'Exportar reportes',
    'slug' => 'reports.export',
    'module' => 'reports'
]);

// 2. Asignar permiso a rol
use ISER\Roles\RoleManager;
$roleManager = new RoleManager($db);
$roleManager->assignPermission($roleId, $permId);
```

### Verificar Permisos en Runtime

```php
// En middleware o controlador
use ISER\Roles\PermissionManager;
$authService = new PermissionManager($db);

if (!$authService->hasCapability($userId, 'moodle/reports:export')) {
    throw new \Exception('Acceso denegado');
}
```

## Middleware Integration

### PermissionMiddleware

```php
// En rutas protegidas
$router->get('/admin/users', [UserController::class, 'index'])
    ->middleware('permission:moodle/user:view');
```

El middleware usa `Roles\PermissionManager::hasCapability()` para verificar permisos.

### AdminMiddleware

```php
// Protección de área administrativa
$adminMiddleware = new AdminMiddleware($jwt, $userManager, $permissionManager, $roleAssignment);
$adminMiddleware->requireAdmin();
```

Usa `Roles\PermissionManager::isAdmin()` internamente.

## Mejores Prácticas

### ✅ DO

1. **Usa Permission\PermissionManager para:**
   - Crear/editar/eliminar permisos en admin UI
   - Listar permisos para asignar a roles
   - Consultas de relaciones permission-role

2. **Usa Roles\PermissionManager para:**
   - Verificar permisos antes de ejecutar acciones
   - Proteger rutas con middleware
   - Verificar si usuario es admin

3. **Naming:**
   - Capabilities: formato `moodle/module:action`
   - Permission slugs: formato `module.action`

### ❌ DON'T

1. No uses `Permission\PermissionManager` para verificar permisos en runtime
2. No uses `Roles\PermissionManager` para operaciones CRUD de la tabla permissions
3. No mezcles los sistemas - cada uno tiene su propósito

## Testing

### Test Permission CRUD
```bash
# Verificar que el controller de permisos funciona
curl -X GET http://localhost/permissions
```

### Test Authorization
```bash
# Verificar que el middleware bloquea acceso sin permisos
curl -X GET http://localhost/admin/users -H "Authorization: Bearer <token>"
```

## Roadmap Future

### Potenciales Mejoras (Opcional)

**Opción A: Renombrar para Mayor Claridad**
- `Permission\PermissionManager` → `Permission\PermissionRepository`
- `Roles\PermissionManager` → `Roles\AuthorizationService`

**Beneficios:** Nombres más descriptivos, menos confusión
**Esfuerzo:** 2-3 horas
**Riesgo:** Bajo

**Opción B: Merge into Unified System**
- Combinar ambos en un solo `PermissionManager` con todos los métodos
- Refactorizar para tener una API unificada

**Beneficios:** Single source of truth
**Esfuerzo:** 6-8 horas
**Riesgo:** Medio-Alto

## Referencias

- RBAC_AUDIT_REPORT.md - Auditoría completa del sistema
- CODE_CLEANUP_REPORT.md - Plan de limpieza general
- [Moodle Roles and Capabilities](https://docs.moodle.org/dev/Roles_and_capabilities)

---

**Última actualización:** 2025-11-13
**Estado:** Sistema funcional y documentado
