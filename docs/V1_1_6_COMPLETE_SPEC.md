# NexoSupport v1.1.6 - Especificación Completa

**Versión:** 1.1.6 (2025011806)
**Fase:** 2 - RBAC Completo + Site Administrators
**Estado:** FINALIZACIÓN Y CONSOLIDACIÓN

---

## OBJETIVO DE LA VERSIÓN 1.1.6

Versión **completamente funcional** del sistema de administración con:
- ✅ Sistema de autenticación seguro
- ✅ RBAC completo con roles y capabilities
- ✅ Site administrators (patrón Moodle exacto)
- ✅ Panel de administración COMPLETO
- ✅ Gestión de usuarios COMPLETA
- ✅ Gestión de roles COMPLETA
- ✅ Sistema de configuración funcional
- ✅ Sistema de actualización manual

---

## ARQUITECTURA FRANKENSTYLE

### Estructura de Directorios

```
nexosupport/
├── public_html/
│   └── index.php              # Front controller ÚNICO
│
├── admin/                     # SUBSISTEMA administrativo
│   ├── index.php              # Dashboard admin
│   ├── upgrade.php            # Sistema de actualización
│   ├── user/                  # Gestión de usuarios
│   │   ├── index.php          # Lista de usuarios
│   │   └── edit.php           # Crear/editar usuario
│   ├── roles/                 # Gestión de roles
│   │   ├── index.php          # Lista de roles
│   │   ├── edit.php           # Crear/editar rol
│   │   ├── define.php         # Definir capabilities
│   │   └── assign.php         # Asignar roles a usuarios
│   └── settings/              # Configuración
│       └── index.php          # Configuración del sistema
│
├── user/                      # SUBSISTEMA de perfil
│   └── profile.php            # Perfil de usuario
│
├── login/                     # SUBSISTEMA de autenticación
│   ├── index.php              # Login
│   ├── logout.php             # Logout
│   ├── change_password.php    # Cambiar contraseña
│   └── forgot_password.php    # Recuperar contraseña
│
├── auth/                      # PLUGINS de autenticación
│   └── manual/                # auth_manual
│       ├── classes/
│       │   ├── auth.php       # Clase principal
│       │   └── plugin.php     # Metadata del plugin
│       ├── lang/
│       └── version.php
│
├── lib/                       # CORE del sistema
│   ├── classes/               # Clases PSR-4
│   │   ├── user/
│   │   │   └── manager.php    # Gestor de usuarios
│   │   ├── rbac/
│   │   │   ├── role.php       # Clase role
│   │   │   ├── capability.php # Clase capability
│   │   │   ├── context.php    # Clase context
│   │   │   └── access.php     # Verificación de acceso
│   │   ├── plugininfo/        # Clases base de plugins
│   │   │   ├── base.php
│   │   │   └── auth.php
│   │   └── ...
│   ├── functions.php          # Funciones globales
│   ├── authlib.php            # Funciones de autenticación
│   ├── upgrade.php            # Sistema de upgrade
│   └── version.php            # Versión del core
```

---

## FUNCIONALIDADES REQUERIDAS v1.1.6

### 1. AUTENTICACIÓN Y SESIONES ✅

#### Funcionalidades:
- [x] Login con username/password
- [x] Logout seguro
- [x] Sesiones con archivos (file-based)
- [x] Protección de páginas con require_login()
- [x] Bypass de autenticación corregido (empty check)
- [x] Verificación de usuarios deleted/suspended

#### Archivos:
- `login/index.php` - Login form y procesamiento
- `login/logout.php` - Cerrar sesión
- `lib/setup.php` - Inicialización de sesiones y $USER
- `lib/functions.php` - require_login()
- `lib/authlib.php` - authenticate_user_login()
- `auth/manual/classes/auth.php` - Plugin de autenticación

---

### 2. SITE ADMINISTRATORS (Patrón Moodle) ✅

#### Funcionalidades:
- [x] config.siteadmins almacena IDs de super admins
- [x] is_siteadmin() verifica contra config
- [x] Siteadmins tienen TODAS las capabilities
- [x] Bypass RBAC para siteadmins
- [x] get_siteadmins() retorna lista
- [x] Instalador configura primer admin como siteadmin
- [x] Migración detecta admins existentes

#### Archivos:
- `lib/functions.php` - is_siteadmin(), get_siteadmins()
- `lib/classes/rbac/access.php` - Bypass en has_capability()
- `install/stages/finish.php` - Configuración inicial
- `lib/upgrade.php` - Migración v1.1.6

---

### 3. RBAC COMPLETO ✅

#### Funcionalidades:
- [x] Roles con capabilities
- [x] Contextos (system, user, etc.)
- [x] Role assignments
- [x] has_capability() con herencia de contextos
- [x] require_capability() para protección
- [x] Roles por defecto: administrator, manager, user

#### Archivos:
- `lib/classes/rbac/role.php`
- `lib/classes/rbac/capability.php`
- `lib/classes/rbac/context.php`
- `lib/classes/rbac/access.php`
- `lib/install_rbac.php`

---

### 4. PANEL DE ADMINISTRACIÓN

#### 4.1 Dashboard Admin ✅
**URL:** `/admin`

**Funcionalidades:**
- [x] Resumen del sistema
- [x] Enlaces a secciones administrativas
- [x] Información de versión
- [x] Notificación de upgrades pendientes

**Archivo:** `admin/index.php`

#### 4.2 Gestión de Usuarios ⚠️ (REQUIERE COMPLETAR)
**URLs:**
- `/admin/users` - Lista de usuarios ✅
- `/admin/user/edit?id=X` - Editar usuario ⚠️ (404)
- `/admin/user/edit` - Crear usuario ⚠️

**Funcionalidades REQUERIDAS:**
- [x] Listar usuarios con paginación
- [ ] Crear nuevo usuario
- [ ] Editar usuario existente
- [ ] Suspender/activar usuario
- [ ] Eliminar usuario (soft delete)
- [ ] Resetear contraseña
- [ ] Ver roles asignados
- [ ] Filtrar y buscar usuarios

**Archivos:**
- `admin/user/index.php` - ✅ Existe
- `admin/user/edit.php` - ⚠️ Existe pero tiene 404

**PROBLEMAS A CORREGIR:**
1. Router no encuentra la ruta /admin/user/edit?id=1
2. Verificar que optional_param funciona correctamente
3. Verificar que \core\user\manager::get_user() existe
4. Crear templates para formularios
5. Implementar validación completa
6. Implementar creación de usuarios
7. Implementar edición de usuarios

#### 4.3 Gestión de Roles ⚠️ (REQUIERE COMPLETAR)
**URLs:**
- `/admin/roles` - Lista de roles ✅
- `/admin/roles/edit?id=X` - Editar rol ⚠️
- `/admin/roles/edit` - Crear rol ⚠️
- `/admin/roles/define?id=X` - Definir capabilities ⚠️
- `/admin/roles/assign?userid=X` - Asignar roles ⚠️ (404)

**Funcionalidades REQUERIDAS:**
- [x] Listar roles
- [ ] Crear nuevo rol
- [ ] Editar rol existente
- [ ] Definir capabilities del rol (matriz)
- [ ] Asignar roles a usuarios
- [ ] Ver usuarios con rol
- [ ] Eliminar rol

**Archivos:**
- `admin/roles/index.php` - ✅ Existe
- `admin/roles/edit.php` - ⚠️ Existe pero requiere verificación
- `admin/roles/define.php` - ⚠️ Existe pero requiere verificación
- `admin/roles/assign.php` - ⚠️ Existe pero tiene 404

**PROBLEMAS A CORREGIR:**
1. Router no encuentra /admin/roles/assign?userid=X
2. Verificar que los formularios funcionan
3. Implementar matriz de capabilities
4. Implementar asignación de roles
5. Crear templates necesarios

#### 4.4 Configuración del Sistema ⚠️ (REQUIERE COMPLETAR)
**URL:** `/admin/settings`

**Funcionalidades REQUERIDAS:**
- [ ] Configuración general del sitio
- [ ] Configuración de autenticación
- [ ] Políticas de contraseñas
- [ ] Configuración de sesiones
- [ ] Configuración de idioma
- [ ] Configuración de zona horaria

**Archivo:** `admin/settings/index.php` - ⚠️ Requiere implementación completa

#### 4.5 Sistema de Actualización ✅
**URL:** `/admin/upgrade.php`

**Funcionalidades:**
- [x] Detección de versión obsoleta
- [x] Ejecución de migraciones
- [x] Verificación de siteadmin
- [x] require_login() funcionando
- [x] Actualización manual (sin redirect automático)

**Archivo:** `admin/upgrade.php` - ✅ Funcional

---

### 5. PERFIL DE USUARIO ⚠️ (REQUIERE IMPLEMENTAR)
**URL:** `/user/profile`

**Funcionalidades REQUERIDAS:**
- [ ] Ver perfil propio
- [ ] Editar información personal
- [ ] Cambiar contraseña
- [ ] Ver roles asignados
- [ ] Ver sesiones activas
- [ ] Cerrar sesiones remotas

**Archivo:** `user/profile.php` - ⚠️ Requiere implementación

---

### 6. RECUPERACIÓN DE CONTRASEÑA ⚠️ (REQUIERE IMPLEMENTAR)
**URLs:**
- `/login/forgot_password` - Solicitar reset
- `/login/change_password` - Cambiar contraseña

**Funcionalidades REQUERIDAS:**
- [ ] Solicitar reset de contraseña por email
- [ ] Token de reset seguro
- [ ] Cambiar contraseña con token válido
- [ ] Expiración de tokens

**Archivos:**
- `login/forgot_password.php` - ⚠️ Requiere implementación
- `login/change_password.php` - ⚠️ Requiere implementación

---

## COMPONENTES DEL CORE

### 7. USER MANAGER ⚠️ (REQUIERE COMPLETAR)
**Clase:** `\core\user\manager`

**Métodos REQUERIDOS:**
```php
public static function get_user(int $userid): ?object
public static function create_user(object $userdata): object
public static function update_user(object $user): bool
public static function delete_user(int $userid): bool
public static function suspend_user(int $userid): bool
public static function unsuspend_user(int $userid): bool
public static function get_users(array $conditions = [], int $limitfrom = 0, int $limitnum = 0): array
public static function count_users(array $conditions = []): int
public static function user_exists(string $username): bool
public static function email_exists(string $email): bool
```

**Archivo:** `lib/classes/user/manager.php`

---

### 8. ROLE MANAGER ⚠️ (REQUIERE COMPLETAR)
**Clase:** `\core\rbac\role`

**Métodos REQUERIDOS:**
```php
public static function create_role(string $name, string $shortname, string $description = ''): object
public static function update_role(object $role): bool
public static function delete_role(int $roleid): bool
public static function get_role(int $roleid): ?object
public static function get_role_by_shortname(string $shortname): ?object
public static function get_all_roles(): array
public static function assign_capability(int $roleid, string $capability, int $permission): bool
public static function get_role_capabilities(int $roleid): array
```

---

## ROUTING

### 9. RUTAS COMPLETAS REQUERIDAS

**Todas las rutas deben estar en `public_html/index.php`:**

```php
// ===== AUTENTICACIÓN =====
$router->get('/login', ...);           // ✅
$router->post('/login', ...);          // ✅
$router->get('/logout', ...);          // ✅
$router->get('/login/change_password', ...);  // ⚠️
$router->post('/login/change_password', ...); // ⚠️
$router->get('/login/forgot_password', ...);  // ⚠️
$router->post('/login/forgot_password', ...); // ⚠️

// ===== ADMIN =====
$router->get('/admin', ...);           // ✅
$router->get('/admin/upgrade.php', ...);      // ✅
$router->post('/admin/upgrade.php', ...);     // ✅

// Usuarios
$router->get('/admin/users', ...);     // ✅
$router->get('/admin/user/edit', ...); // ✅ (pero 404 con params)
$router->post('/admin/user/edit', ...);// ✅

// Roles
$router->get('/admin/roles', ...);     // ✅
$router->get('/admin/roles/edit', ...);       // ✅
$router->post('/admin/roles/edit', ...);      // ✅
$router->get('/admin/roles/define', ...);     // ✅
$router->post('/admin/roles/define', ...);    // ✅
$router->get('/admin/roles/assign', ...);     // ✅ (pero 404 con params)
$router->post('/admin/roles/assign', ...);    // ✅

// Configuración
$router->get('/admin/settings', ...);  // ✅
$router->post('/admin/settings', ...); // ✅

// ===== USUARIO =====
$router->get('/user/profile', ...);    // ⚠️ Requiere implementación
$router->post('/user/profile', ...);   // ⚠️ Requiere implementación

// ===== DASHBOARD =====
$router->get('/', ...);                // ✅
```

---

## TEMPLATES REQUERIDOS

### 10. SISTEMA DE TEMPLATES (Mustache)

**Templates que DEBEN existir:**

```
lib/templates/core/
├── layout.mustache          # ✅ Layout base
├── login.mustache           # ✅ Formulario login
├── dashboard.mustache       # ✅ Dashboard principal
└── error.mustache           # ⚠️ Página de error

lib/templates/admin/
├── dashboard.mustache       # ✅ Dashboard admin
├── user/
│   ├── index.mustache       # ✅ Lista usuarios
│   └── edit.mustache        # ⚠️ Formulario usuario
└── roles/
    ├── index.mustache       # ✅ Lista roles
    ├── edit.mustache        # ⚠️ Formulario rol
    ├── define.mustache      # ⚠️ Matriz capabilities
    └── assign.mustache      # ⚠️ Asignación roles
```

---

## PRUEBAS REQUERIDAS

### 11. CHECKLIST DE FUNCIONALIDADES

#### Autenticación:
- [ ] Login con credenciales válidas funciona
- [ ] Login con credenciales inválidas falla
- [ ] Logout funciona y limpia sesión
- [ ] Páginas protegidas redirigen a login
- [ ] Usuarios suspended no pueden loguear
- [ ] Usuarios deleted no pueden loguear

#### Site Administrators:
- [ ] is_siteadmin() retorna true para admin
- [ ] Siteadmins pueden acceder a todas las páginas admin
- [ ] Siteadmins pueden ejecutar upgrade
- [ ] Siteadmins tienen todas las capabilities

#### Gestión de Usuarios:
- [ ] Listar usuarios funciona
- [ ] Crear usuario funciona
- [ ] Editar usuario funciona (/admin/user/edit?id=1)
- [ ] Suspender usuario funciona
- [ ] Eliminar usuario funciona
- [ ] Buscar usuarios funciona

#### Gestión de Roles:
- [ ] Listar roles funciona
- [ ] Crear rol funciona
- [ ] Editar rol funciona
- [ ] Definir capabilities funciona
- [ ] Asignar rol a usuario funciona (/admin/roles/assign?userid=1)
- [ ] Ver usuarios con rol funciona

#### Sistema:
- [ ] Upgrade manual funciona
- [ ] Notificación de upgrade aparece
- [ ] Configuración se guarda
- [ ] Perfil de usuario funciona
- [ ] Cambio de contraseña funciona

---

## PROBLEMAS CRÍTICOS A RESOLVER

### 12. ISSUES IDENTIFICADOS

1. **404 en /admin/user/edit?id=1**
   - Ruta está definida en router
   - Archivo existe en admin/user/edit.php
   - Query params deben funcionar correctamente
   - CAUSA: Probablemente error en el archivo PHP

2. **404 en /admin/roles/assign?userid=1**
   - Ruta está definida en router
   - Archivo existe en admin/roles/assign.php
   - Query params deben funcionar correctamente
   - CAUSA: Probablemente error en el archivo PHP

3. **Templates faltantes**
   - Muchas páginas no tienen templates Mustache
   - Necesitan crearse para renderizar correctamente

4. **\core\user\manager no verificado**
   - Clase debe existir y funcionar
   - Métodos deben implementarse completamente

5. **Sistema de configuración incompleto**
   - admin/settings/index.php requiere implementación completa

---

## PLAN DE ACCIÓN PARA COMPLETAR v1.1.6

### PASO 1: Corregir Routing (INMEDIATO)
1. Verificar que query params funcionan en router
2. Debug de /admin/user/edit?id=1
3. Debug de /admin/roles/assign?userid=1
4. Agregar mejor manejo de errores

### PASO 2: Completar User Manager
1. Verificar métodos existentes
2. Implementar métodos faltantes
3. Testing completo

### PASO 3: Completar Páginas Admin
1. admin/user/edit.php - Crear/editar usuarios
2. admin/roles/assign.php - Asignar roles
3. admin/roles/define.php - Definir capabilities
4. admin/settings/index.php - Configuración

### PASO 4: Crear Templates Faltantes
1. admin/user/edit.mustache
2. admin/roles/edit.mustache
3. admin/roles/define.mustache
4. admin/roles/assign.mustache

### PASO 5: Testing Completo
1. Ejecutar checklist completo
2. Corregir bugs encontrados
3. Documentar funcionalidades

### PASO 6: Consolidación Final
1. Commit único con toda la versión 1.1.6
2. Actualizar documentación
3. Release notes completas

---

## CRITERIOS DE ACEPTACIÓN v1.1.6

La versión 1.1.6 está COMPLETA cuando:

1. ✅ TODAS las páginas de admin/* funcionan sin 404
2. ✅ Crear usuario funciona completamente
3. ✅ Editar usuario funciona completamente
4. ✅ Asignar roles funciona completamente
5. ✅ Definir capabilities funciona completamente
6. ✅ Configuración del sistema funciona
7. ✅ Perfil de usuario funciona
8. ✅ NO hay errores PHP en logs
9. ✅ Documentación completa
10. ✅ Checklist 100% completado

---

**VERSIÓN:** 1.1.6 (2025011806)
**ESTADO:** EN CONSOLIDACIÓN
**PRÓXIMA FASE:** Fase 3 - Herramientas Admin (tool_*)
