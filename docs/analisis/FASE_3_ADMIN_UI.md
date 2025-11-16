# FASE 3: Interfaz de Administración RBAC

**Fecha:** 2024-11-16
**Responsable:** Claude (Frankenstyle Refactoring)
**Estado:** ✅ COMPLETADO

---

## 📋 Resumen Ejecutivo

La Fase 3 implementa las interfaces administrativas para el sistema RBAC (Role-Based Access Control) siguiendo la arquitectura Frankenstyle. Esta fase conecta el core RBAC implementado en Fase 2 con las interfaces de usuario existentes.

### Objetivos Cumplidos

1. ✅ Crear páginas de entrada Frankenstyle para admin/user y admin/roles
2. ✅ Implementar bootstrap.php como bootstrap principal del sistema
3. ✅ Crear clases helper para facilitar operaciones de usuario y roles
4. ✅ Crear archivos version.php y lib.php según patrón Frankenstyle
5. ✅ Integrar nuevo sistema RBAC con controladores existentes

---

## 🏗️ Arquitectura Implementada

### 1. Páginas de Entrada Administrativas

#### admin/user/index.php
```php
- Punto de entrada Frankenstyle para gestión de usuarios
- Verifica permisos usando require_capability('users.view')
- Integra con UserManagementController existente
- Maneja acciones: index, create, edit
- Genera respuesta PSR-7 compliant
```

#### admin/roles/index.php
```php
- Punto de entrada Frankenstyle para gestión de roles
- Verifica permisos usando require_capability('roles.view')
- Integra con RoleController existente
- Maneja acciones: index, create, edit, permissions
- Genera respuesta PSR-7 compliant
```

### 2. Bootstrap del Sistema

#### bootstrap.php
```php
Archivo de inicialización principal que:
- Define constantes del sistema (BASE_DIR, NEXOSUPPORT_INTERNAL)
- Carga variables de entorno (.env)
- Inicializa Composer autoloader
- Inicia sesión PHP
- Carga lib/setup.php (sistema de componentes)
- Carga lib/accesslib.php (funciones RBAC)
- Carga lib/compat/roles_compat.php (compatibilidad)
- Configura error reporting según APP_DEBUG
- Establece timezone
```

**Propósito:** Centralizar la inicialización del sistema para ser usado por:
- Front controller (public_html/index.php)
- Páginas admin directas
- Scripts CLI
- Tests unitarios

### 3. Clases Helper

#### lib/classes/user/user_helper.php
```php
namespace core\user;

class user_helper {
    // Gestión de usuarios
    - get_user(int $id): ?user
    - get_user_by_username(string $username): ?user
    - get_user_by_email(string $email): ?user
    - create_user(array $data): ?int
    - update_user(int $id, array $data): bool
    - delete_user(int $id): bool
    - restore_user(int $id): bool

    // Validación
    - username_exists(string $username, ?int $excludeId): bool
    - email_exists(string $email, ?int $excludeId): bool
    - validate_user_data(array $data, bool $isUpdate): array

    // Operaciones de listado (usa legacy temporalmente)
    - get_users_list(int $limit, int $offset, array $filters): array
    - count_users(array $filters): int
    - get_user_roles(int $userId): array

    // Usuario actual
    - get_current_user(): ?user
}
```

#### lib/classes/role/role_helper.php
```php
namespace core\role;

class role_helper {
    // Gestión de roles
    - get_role(int $id): ?array
    - get_role_by_slug(string $slug): ?array
    - get_roles_list(int $limit, int $offset, array $filters): array
    - count_roles(array $filters): int
    - delete_role(int $roleId): bool

    // Permisos de roles (usa access_manager)
    - get_role_permissions(int $roleId): array
    - assign_permission(int $roleId, int $permissionId): bool
    - remove_permission(int $roleId, int $permissionId): bool
    - sync_permissions(int $roleId, array $permissionIds): bool

    // Asignación de roles a usuarios (usa access_manager)
    - assign_role_to_user(int $userId, int $roleId, ...): bool
    - unassign_role_from_user(int $userId, int $roleId): bool
    - user_has_role(int $userId, string $roleSlug): bool
    - user_has_permission(int $userId, string $permission): bool

    // Usuarios con roles
    - get_role_users(int $roleId): array
    - get_user_roles(int $userId): array
    - get_user_permissions(int $userId): array

    // Validación y utilidades
    - validate_role_data(array $data, bool $isUpdate): array
    - can_delete_role(int $roleId): bool
    - clear_caches(): void
}
```

### 4. Funciones Globales Helper

Añadidas a `lib/accesslib.php`:

```php
// Obtener instancias singleton de helpers
function get_user_helper(): \core\user\user_helper
function get_role_helper(): \core\role\role_helper
```

Uso:
```php
$userHelper = get_user_helper();
$user = $userHelper->get_user(5);

$roleHelper = get_role_helper();
$roles = $roleHelper->get_user_roles(5);
```

### 5. Archivos Frankenstyle

#### Componente admin/user/

**version.php**
```php
$plugin->component = 'admin_user';
$plugin->version = 2024111601;
$plugin->requires = 2024111600;
$plugin->maturity = MATURITY_STABLE;
$plugin->release = '1.0.0';
```

**lib.php - Funciones de biblioteca:**
- `admin_user_get_capabilities()` - Retorna capabilities del componente
- `admin_user_fullname($user)` - Formatea nombre completo
- `admin_user_status_badge($status)` - HTML badge de estado
- `admin_user_get_menu_items()` - Items de menú admin

**Capabilities definidas:**
- users.view
- users.create
- users.edit
- users.delete
- users.restore
- users.assign_roles

#### Componente admin/roles/

**version.php**
```php
$plugin->component = 'admin_roles';
$plugin->version = 2024111601;
$plugin->requires = 2024111600;
$plugin->maturity = MATURITY_STABLE;
$plugin->release = '1.0.0';
```

**lib.php - Funciones de biblioteca:**
- `admin_roles_get_capabilities()` - Capabilities de roles
- `admin_roles_get_permission_capabilities()` - Capabilities de permisos
- `admin_roles_get_all_capabilities()` - Todas las capabilities
- `admin_roles_is_system_role($role)` - Verifica si es role de sistema
- `admin_roles_badge($role)` - HTML badge de role
- `admin_roles_permission_count($count)` - Formato de conteo
- `admin_roles_get_menu_items()` - Items de menú admin
- `admin_roles_group_permissions_by_module($permissions)` - Agrupa por módulo

**Capabilities definidas:**
- roles.view
- roles.create
- roles.edit
- roles.delete
- roles.assign_permissions
- permissions.view
- permissions.create
- permissions.edit
- permissions.delete

---

## 🔗 Integración con Sistema Existente

### Flujo de Ejecución

#### Acceso Directo a Admin UI
```
1. Usuario accede a /admin/user/index.php
2. bootstrap.php se carga (bootstrap)
3. require_login() verifica autenticación
4. require_capability('users.view') verifica permisos
5. UserManagementController se instancia
6. Acción se ejecuta (index, create, edit)
7. Respuesta PSR-7 se genera
8. HTML se renderiza
```

#### Acceso via Router (Recomendado)
```
1. Usuario accede a /admin/users
2. Front controller (public_html/index.php) recibe request
3. Router carga config/routes/admin.php
4. Middleware verifica autenticación y permisos
5. UserManagementController se instancia
6. Acción se ejecuta
7. Respuesta se retorna
```

### Compatibilidad Dual

El sistema mantiene compatibilidad con:

1. **Legacy ISER\User\UserManager** → Usado por controladores actuales
2. **Nuevo core\user\user_repository** → Usado por helpers

**Estrategia de migración gradual:**
```php
// Código actual (sigue funcionando)
$userManager = new UserManager($db);
$users = $userManager->getUsers(20, 0);

// Nuevo código (disponible ahora)
$userHelper = get_user_helper();
$user = $userHelper->get_user(5);
```

---

## 📊 Métricas de Implementación

### Archivos Creados

| Archivo | Líneas | Propósito |
|---------|--------|-----------|
| bootstrap.php | 73 | Bootstrap principal |
| admin/user/index.php | 67 | Entrada admin usuarios |
| admin/user/version.php | 17 | Versión componente |
| admin/user/lib.php | 118 | Funciones biblioteca |
| admin/roles/index.php | 73 | Entrada admin roles |
| admin/roles/version.php | 17 | Versión componente |
| admin/roles/lib.php | 173 | Funciones biblioteca |
| lib/classes/user/user_helper.php | 272 | Helper usuarios |
| lib/classes/role/role_helper.php | 294 | Helper roles |
| lib/accesslib.php (actualizado) | +28 | Funciones helper globales |
| **TOTAL** | **1,132 líneas** | **10 archivos** |

### Funciones Agregadas

- **Helper Functions:** 45+ funciones públicas
- **Capabilities Definidas:** 15 capabilities RBAC
- **Métodos de Clase:** 35+ métodos en helpers

---

## 🎯 Beneficios de la Fase 3

### 1. Separación de Responsabilidades
- **bootstrap.php:** Bootstrap centralizado
- **Helpers:** Lógica de negocio reutilizable
- **Controllers:** Manejo de HTTP únicamente
- **lib.php:** Funciones utilitarias del componente

### 2. Seguridad Mejorada
```php
// Verificación de permisos en cada página admin
require_login();
require_capability('users.view');
```

### 3. Frankenstyle Compliance
```
admin/
├── user/
│   ├── index.php        ✅ Punto de entrada
│   ├── version.php      ✅ Información de versión
│   └── lib.php          ✅ Funciones de biblioteca
└── roles/
    ├── index.php        ✅ Punto de entrada
    ├── version.php      ✅ Información de versión
    └── lib.php          ✅ Funciones de biblioteca
```

### 4. Migración Gradual
- Código legacy sigue funcionando
- Nuevo código disponible para uso
- Sin breaking changes
- Path claro para migración completa

### 5. Reutilización de Código
```php
// Helpers singleton - instancia única compartida
$userHelper = get_user_helper();
$roleHelper = get_role_helper();

// Validación reutilizable
$errors = $userHelper->validate_user_data($data);
$errors = $roleHelper->validate_role_data($data);
```

---

## 🔄 Próxima Fase: Fase 4

### Objetivos de Fase 4
1. Implementar herramientas administrativas (admin/tool/*)
2. Crear tool_uploaduser para carga masiva de usuarios
3. Crear tool_installaddon para gestión de plugins
4. Migrar tool_mfa a estructura Frankenstyle
5. Implementar admin/tool/logviewer
6. Crear admin/tool/pluginmanager

### Estructura Esperada
```
admin/tool/
├── uploaduser/
│   ├── index.php
│   ├── version.php
│   ├── lib.php
│   └── classes/
├── installaddon/
│   ├── index.php
│   ├── version.php
│   └── classes/
├── mfa/
│   ├── index.php
│   ├── version.php
│   ├── lib.php
│   ├── classes/
│   └── factor/
│       ├── email/
│       └── iprange/
└── logviewer/
    ├── index.php
    ├── version.php
    └── classes/
```

---

## ✅ Checklist de Completitud

- [x] bootstrap.php creado y funcional
- [x] admin/user/index.php implementado
- [x] admin/roles/index.php implementado
- [x] user_helper creado con 15+ métodos
- [x] role_helper creado con 18+ métodos
- [x] Funciones globales helper añadidas
- [x] version.php para ambos componentes
- [x] lib.php con capabilities y funciones utilitarias
- [x] Integración con controladores existentes
- [x] Compatibilidad backward mantenida
- [x] Documentación completa

---

## 📝 Notas de Implementación

### Decisiones de Diseño

1. **Helper Pattern:** Se eligió el patrón helper sobre repository directo para facilitar la migración gradual y proporcionar una API más amigable.

2. **Singleton Pattern:** Los helpers usan singleton para evitar múltiples instancias de Database y mejorar performance.

3. **Dual System:** Mantener ambos sistemas (legacy + nuevo) temporalmente permite migración sin riesgos.

4. **Capability-Based Security:** Todas las páginas admin verifican permisos usando el nuevo sistema RBAC.

### Consideraciones de Performance

- **Caching:** access_manager implementa caching de permisos
- **Lazy Loading:** Database y managers se instancian solo cuando se necesitan
- **Singleton:** Evita instancias múltiples innecesarias

### Testing

Para probar la Fase 3:
```php
// Verificar bootstrap
require_once __DIR__ . '/bootstrap.php';
assert(defined('NEXOSUPPORT_INTERNAL'));
assert(function_exists('has_capability'));

// Verificar helpers
$userHelper = get_user_helper();
assert($userHelper instanceof \core\user\user_helper);

$roleHelper = get_role_helper();
assert($roleHelper instanceof \core\role\role_helper);

// Verificar capabilities
assert(is_array(admin_user_get_capabilities()));
assert(count(admin_user_get_capabilities()) === 6);

assert(is_array(admin_roles_get_all_capabilities()));
assert(count(admin_roles_get_all_capabilities()) === 9);
```

---

## 🎓 Lecciones Aprendidas

1. **Importancia del Bootstrap:** Un archivo bootstrap.php centralizado simplifica enormemente el desarrollo.

2. **Helpers vs Managers:** Los helpers proporcionan una API más simple para operaciones comunes.

3. **Frankenstyle Gradual:** Implementar Frankenstyle gradualmente (version.php, lib.php) facilita la adopción.

4. **Compatibilidad Primero:** Mantener código legacy funcionando reduce riesgos durante refactoring.

---

**Fase 3 Completada:** 2024-11-16
**Próxima Fase:** Fase 4 - Herramientas Administrativas (admin/tool/*)
