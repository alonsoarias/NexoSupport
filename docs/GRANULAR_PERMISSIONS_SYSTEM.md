# Sistema de Permisos Granulares - NexoSupport

## Descripción General

NexoSupport implementa un sistema completo de control de acceso basado en roles (RBAC) con permisos granulares. Este sistema permite asignar permisos específicos a roles, y los usuarios heredan los permisos de sus roles asignados.

## Arquitectura del Sistema

### 1. Estructura de Tablas

```
users (usuarios del sistema)
  ↓
user_roles (relación muchos a muchos)
  ↓
roles (roles del sistema: Admin, Editor, Viewer, Support)
  ↓
role_permissions (relación muchos a muchos)
  ↓
permissions (permisos granulares del sistema)
```

### 2. Permisos Granulares (35 permisos en 9 módulos)

#### Módulo: users (7 permisos)
- `users.view` - Ver la lista de usuarios del sistema
- `users.create` - Crear nuevos usuarios
- `users.update` - Editar información de usuarios existentes
- `users.delete` - Eliminar usuarios (soft delete)
- `users.restore` - Restaurar usuarios eliminados
- `users.assign_roles` - Asignar o quitar roles de usuarios
- `users.view_profile` - Ver el perfil detallado de usuarios

#### Módulo: roles (5 permisos)
- `roles.view` - Ver la lista de roles del sistema
- `roles.create` - Crear nuevos roles
- `roles.update` - Editar roles existentes
- `roles.delete` - Eliminar roles
- `roles.assign_permissions` - Asignar o quitar permisos de roles

#### Módulo: permissions (4 permisos)
- `permissions.view` - Ver la lista de permisos del sistema
- `permissions.create` - Crear nuevos permisos
- `permissions.update` - Editar permisos existentes
- `permissions.delete` - Eliminar permisos

#### Módulo: dashboard (3 permisos)
- `dashboard.view` - Acceder al panel de administración
- `dashboard.stats` - Ver estadísticas del sistema
- `dashboard.charts` - Ver gráficas y reportes visuales

#### Módulo: settings (3 permisos)
- `settings.view` - Ver la configuración del sistema
- `settings.update` - Modificar la configuración del sistema
- `settings.critical` - Modificar configuraciones críticas

#### Módulo: logs (3 permisos)
- `logs.view` - Ver los registros de actividad
- `logs.delete` - Eliminar registros de actividad
- `logs.export` - Exportar registros de actividad

#### Módulo: audit (2 permisos)
- `audit.view` - Ver la pista de auditoría
- `audit.export` - Exportar registros de auditoría

#### Módulo: reports (3 permisos)
- `reports.view` - Ver reportes del sistema
- `reports.generate` - Generar nuevos reportes
- `reports.export` - Exportar reportes en diferentes formatos

#### Módulo: sessions (2 permisos)
- `sessions.view` - Ver sesiones activas
- `sessions.terminate` - Cerrar sesiones de otros usuarios

## Flujo de Instalación

### 1. Durante la instalación inicial:

1. **SchemaInstaller** lee `/database/schema/schema.xml`
2. Crea las 13 tablas del sistema
3. Inserta 4 roles iniciales (Admin, Editor, Viewer, Support)
4. Inserta **35 permisos granulares** organizados por módulo
5. Inserta 8 configuraciones iniciales
6. **Asigna automáticamente todos los permisos al rol Admin** (línea 530-539 de SchemaInstaller.php)

### 2. El instalador con barra de progreso muestra:

- Conexión a base de datos (10%)
- Verificación de schema.xml (15%)
- Parseo de XML (20%)
- Creación de 13 tablas (30-60%)
- Inserción de roles (70%)
- Inserción de **35 permisos granulares** (80%)
- Inserción de configuraciones (85%)
- Asignación de permisos al Admin (90%)
- Finalización (100%)

## Flujo de Gestión de Permisos

### 1. Creación de Roles con Permisos

**Ruta**: `/admin/roles/create`

**Controlador**: `RoleController::create()` (línea 111-121)
```php
public function create(ServerRequestInterface $request): ResponseInterface
{
    // Obtiene permisos agrupados por módulo
    $permissionsGrouped = $this->permissionManager->getPermissionsGroupedByModule();

    $data = [
        'permissions_grouped' => $permissionsGrouped,
        'page_title' => 'Crear Rol',
    ];

    return $this->renderWithLayout('admin/roles/create', $data);
}
```

**Vista**: `/resources/views/admin/roles/create.mustache` (línea 63-78)
```mustache
{{#permissions_grouped}}
<div class="permission-module">
    <h4><i class="bi bi-folder"></i> {{@key}}</h4>
    <div class="permissions-grid">
        {{#.}}
        <label class="permission-checkbox">
            <input type="checkbox" name="permissions[]" value="{{id}}">
            <div class="permission-info">
                <strong>{{name}}</strong>
                <small>{{description}}</small>
            </div>
        </label>
        {{/.}}
    </div>
</div>
{{/permissions_grouped}}
```

**Guardado**: `RoleController::store()` (línea 126-158)
```php
public function store(ServerRequestInterface $request): ResponseInterface
{
    $body = $request->getParsedBody();

    // Crear rol
    $roleId = $this->roleManager->create([
        'name' => $body['name'],
        'slug' => $this->generateSlug($body['name']),
        'description' => $body['description'] ?? '',
        'level' => (int)($body['level'] ?? 50),
    ]);

    // Asignar permisos si se proporcionaron
    if (isset($body['permissions']) && is_array($body['permissions'])) {
        $this->roleManager->syncPermissions($roleId, array_map('intval', $body['permissions']));
    }

    return Response::redirect('/admin/roles?success=created');
}
```

### 2. Edición de Roles y Permisos

**Ruta**: `/admin/roles/edit` (POST con role_id)

**Controlador**: `RoleController::edit()` (línea 163-205)
- Guarda `role_id` en sesión (seguridad)
- Obtiene permisos actuales del rol
- Marca permisos asignados como `is_assigned = true`
- Renderiza formulario con permisos pre-seleccionados

**Actualización**: `RoleController::update()` (línea 210-282)
- Lee `role_id` desde sesión
- Actualiza información del rol
- Sincroniza permisos mediante `syncPermissions()`

### 3. Verificación de Permisos de Usuario

**Manager**: `PermissionManager::userHasPermission()` (línea 156-170)
```php
public function userHasPermission(int $userId, string $permissionSlug): bool
{
    $sql = "SELECT COUNT(*) as count
            FROM {$this->db->table('permissions')} p
            INNER JOIN {$this->db->table('role_permissions')} rp ON p.id = rp.permission_id
            INNER JOIN {$this->db->table('user_roles')} ur ON rp.role_id = ur.role_id
            WHERE ur.user_id = :user_id AND p.slug = :slug";

    $result = $this->db->getConnection()->fetchOne($sql, [
        ':user_id' => $userId,
        ':slug' => $permissionSlug
    ]);

    return ((int)($result['count'] ?? 0)) > 0;
}
```

**Uso en Controladores**:
```php
use ISER\Permission\PermissionManager;

// Verificar si el usuario actual tiene permiso
$permissionManager = new PermissionManager($db);
$userId = $_SESSION['user_id'];

if (!$permissionManager->userHasPermission($userId, 'users.create')) {
    return Response::json(['error' => 'No tienes permiso para crear usuarios'], 403);
}

// Continuar con la lógica...
```

## Sincronización de Permisos

**Manager**: `RoleManager::syncPermissions()` (línea 177-189)
```php
public function syncPermissions(int $roleId, array $permissionIds): bool
{
    // Eliminar permisos actuales
    $sql = "DELETE FROM {$this->db->table('role_permissions')} WHERE role_id = :role_id";
    $this->db->getConnection()->execute($sql, [':role_id' => $roleId]);

    // Agregar nuevos permisos
    foreach ($permissionIds as $permissionId) {
        $this->assignPermission($roleId, (int)$permissionId);
    }

    return true;
}
```

Este método:
1. Elimina todos los permisos actuales del rol
2. Inserta los nuevos permisos seleccionados
3. Garantiza que no haya duplicados

## Patrones de Seguridad

### 1. IDs en Sesión (No en URLs)
- Los IDs de roles/permisos se guardan en `$_SESSION` durante edición
- No se exponen en URLs ni en campos hidden
- Se limpian después de completar la operación

### 2. Validación de Permisos
- Cada permiso tiene un slug único (ej: `users.create`)
- Los permisos se verifican mediante queries con JOIN
- Los usuarios heredan permisos de TODOS sus roles

### 3. Roles del Sistema
- El rol Admin es inmutable (no se puede eliminar)
- Los roles del sistema solo permiten editar descripción y permisos
- Los roles personalizados se pueden eliminar si no tienen usuarios asignados

## Interfaz de Usuario

### 1. Vista de Permisos Agrupados
Los permisos se muestran organizados por módulo con checkboxes:

```
┌─ users ──────────────────────────┐
│ ☐ Ver Usuarios                   │
│ ☐ Crear Usuarios                 │
│ ☐ Editar Usuarios                │
│ ☐ Eliminar Usuarios              │
│ ☐ Restaurar Usuarios             │
│ ☐ Asignar Roles a Usuarios       │
│ ☐ Ver Perfil de Usuario          │
└──────────────────────────────────┘

┌─ roles ──────────────────────────┐
│ ☐ Ver Roles                      │
│ ☐ Crear Roles                    │
│ ☐ Editar Roles                   │
│ ☐ Eliminar Roles                 │
│ ☐ Asignar Permisos a Roles       │
└──────────────────────────────────┘

... (7 módulos más)
```

### 2. Tabla de Roles
Muestra:
- Nombre del rol
- Slug
- Nivel de prioridad
- Número de permisos asignados
- Descripción
- Estado (Sistema/Personalizado)
- Acciones (Editar/Eliminar)

### 3. Tabla de Permisos
Muestra permisos agrupados por módulo:
- Nombre del permiso
- Slug (código único)
- Descripción
- Roles que tienen ese permiso
- Acciones (Editar/Eliminar)

## Scripts Auxiliares

### Seed de Permisos Manual
**Archivo**: `/scripts/seed_permissions.php`

Si necesitas agregar permisos después de la instalación:
```bash
php scripts/seed_permissions.php
```

Este script:
1. Crea 23 permisos base
2. Los asigna automáticamente al rol Admin
3. Omite permisos que ya existen

## Verificación de Funcionamiento

Para verificar que el sistema funciona correctamente:

1. **Instalar el sistema** - Los 35 permisos deben crearse automáticamente
2. **Verificar en `/admin/permissions`** - Deben aparecer 35 permisos en 9 módulos
3. **Crear un rol en `/admin/roles/create`** - Deben aparecer los permisos agrupados
4. **Asignar permisos** - Seleccionar algunos checkboxes y crear el rol
5. **Editar el rol** - Los permisos seleccionados deben aparecer marcados
6. **Asignar el rol a un usuario** - El usuario debe heredar esos permisos
7. **Verificar acceso** - Usar `userHasPermission()` para validar

## Ejemplo Completo de Uso

```php
// En cualquier controlador

use ISER\Permission\PermissionManager;
use ISER\Core\Database\Database;

class ArticleController
{
    private PermissionManager $permissionManager;

    public function __construct()
    {
        $db = Database::getInstance();
        $this->permissionManager = new PermissionManager($db);
    }

    public function create(ServerRequestInterface $request): ResponseInterface
    {
        // Verificar permiso
        $userId = $_SESSION['user_id'] ?? 0;

        if (!$this->permissionManager->userHasPermission($userId, 'articles.create')) {
            return Response::redirect('/admin?error=forbidden');
        }

        // Usuario tiene permiso, continuar...
        return $this->renderForm();
    }

    public function delete(ServerRequestInterface $request): ResponseInterface
    {
        $userId = $_SESSION['user_id'] ?? 0;

        if (!$this->permissionManager->userHasPermission($userId, 'articles.delete')) {
            return Response::json(['error' => 'No tienes permiso para eliminar artículos'], 403);
        }

        // Proceder con eliminación...
    }
}
```

## Conclusión

El sistema de permisos granulares de NexoSupport proporciona:

✅ **35 permisos específicos** organizados en 9 módulos funcionales
✅ **Instalación automática** durante el setup inicial
✅ **Interfaz visual intuitiva** con permisos agrupados por módulo
✅ **Asignación flexible** de permisos a roles
✅ **Herencia de permisos** de roles a usuarios
✅ **Verificación sencilla** mediante `userHasPermission()`
✅ **Seguridad robusta** con IDs en sesión
✅ **Sincronización completa** de permisos al editar roles

El sistema está completamente funcional y listo para usar después de la instalación.
