# Creación del Site Administrator - NexoSupport

## Análisis del Sistema Actual

He analizado completamente el flujo de creación del administrador del sistema durante la instalación. El sistema **YA ESTÁ CORRECTAMENTE CONFIGURADO** para crear un site administrator con todos los permisos necesarios.

## Flujo de Instalación del Site Administrator

### Fase 1: Creación del Usuario (Stage: admin)

**Archivo:** `install/stages/admin.php`

```php
// Líneas 50-61
$userid = $DB->insert_record('users', [
    'auth' => 'manual',
    'username' => $username,        // ej: "admin"
    'password' => password_hash($password, PASSWORD_DEFAULT),
    'firstname' => $firstname,      // ej: "Administrador"
    'lastname' => $lastname,        // ej: "Sistema"
    'email' => $email,              // ej: "soporteplataformas@iser.edu.co"
    'suspended' => 0,
    'deleted' => 0,
    'timecreated' => time(),
    'timemodified' => time()
]);

// El ID se guarda en sesión para la fase 2
$_SESSION['admin_userid'] = $userid;
```

**Estado después de esta fase:**
- ✅ Usuario existe en la tabla `users`
- ❌ Aún NO tiene rol asignado
- ❌ Aún NO es site administrator

### Fase 2: Instalación del Sistema RBAC (Stage: finish)

**Archivo:** `install/stages/finish.php` (líneas 34-52)

```php
require_once(BASE_DIR . '/lib/install_rbac.php');

// Paso 1: Instalar sistema RBAC completo
if (install_rbac_system()) {
    $rbac_installed = true;

    // Paso 2: Asignar rol administrator al usuario
    if (isset($_SESSION['admin_userid'])) {
        $userid = $_SESSION['admin_userid'];

        // Obtener contexto de sistema
        $syscontext = \core\rbac\context::system();

        // Obtener rol de administrator
        $adminrole = \core\rbac\role::get_by_shortname('administrator');

        if ($adminrole) {
            // Asignar rol en contexto de sistema
            \core\rbac\access::assign_role(
                $adminrole->id,
                $userid,
                $syscontext
            );
            $admin_role_assigned = true;
        }
    }
}
```

**Estado después de esta fase:**
- ✅ Usuario existe en la tabla `users`
- ✅ Sistema RBAC instalado (roles, capabilities, contexts)
- ✅ Usuario tiene rol "administrator" en contexto de sistema
- ✅ Usuario ES site administrator

### Fase 3: Instalación del Sistema RBAC Completo

**Archivo:** `lib/install_rbac.php`

#### 3.1 Creación del Rol Administrator

```php
// Líneas 56-65
$admin = role::create(
    'administrator',                          // shortname
    'Administrador',                          // name
    'Acceso completo al sistema',            // description
    1                                         // sortorder
);
```

#### 3.2 Asignación de TODAS las Capabilities

```php
// Líneas 93-100
// Administrator: ALL capabilities
foreach ($capabilities as $cap) {
    $admin->assign_capability(
        $cap['name'],
        access::PERMISSION_ALLOW,
        $syscontext
    );
}
```

**Capabilities asignadas al administrator:**

| Capability | Tipo | Descripción |
|------------|------|-------------|
| `nexosupport/admin:viewdashboard` | read | Ver dashboard administrativo |
| `nexosupport/admin:manageconfig` | write | Gestionar configuración del sistema |
| `nexosupport/admin:manageusers` | write | Gestionar usuarios |
| `nexosupport/admin:manageroles` | write | Gestionar roles |
| `nexosupport/admin:assignroles` | write | Asignar roles a usuarios |
| `nexosupport/user:view` | read | Ver usuarios |
| `nexosupport/user:viewown` | read | Ver perfil propio |
| `nexosupport/user:create` | write | Crear usuarios |
| `nexosupport/user:update` | write | Actualizar usuarios |
| `nexosupport/user:updateown` | write | Actualizar perfil propio |
| `nexosupport/user:delete` | write | Eliminar usuarios |
| `nexosupport/role:view` | read | Ver roles |
| `nexosupport/role:manage` | write | Gestionar roles |
| `nexosupport/role:assign` | write | Asignar roles |
| `nexosupport/log:view` | read | Ver logs del sistema |
| `nexosupport/system:manage` | write | Gestionar sistema |

**Total: 16 capabilities - TODAS asignadas al rol administrator**

## Verificación de Site Administrator

### Función `is_siteadmin()`

**Archivo:** `lib/functions.php` (líneas 627-651)

```php
function is_siteadmin(?int $userid = null): bool {
    global $USER, $DB;

    if ($userid === null) {
        $userid = $USER->id ?? 0;
    }

    if ($userid == 0) {
        return false;
    }

    // Verificar si el usuario tiene el rol administrator en contexto de sistema
    $syscontext = \core\rbac\context::system();

    $sql = "SELECT COUNT(*)
            FROM {role_assignments} ra
            JOIN {roles} r ON r.id = ra.roleid
            WHERE ra.userid = ?
            AND ra.contextid = ?
            AND r.shortname = 'administrator'";

    $count = $DB->count_records_sql($sql, [$userid, $syscontext->id]);

    return $count > 0;
}
```

**Lógica:**
1. Obtiene el contexto de sistema
2. Busca en `role_assignments` si el usuario tiene asignado el rol con `shortname = 'administrator'`
3. En el contexto de sistema (`contextid = syscontext->id`)
4. Retorna `true` si encuentra el registro

## Estructura de Datos en BD

### Tabla `users`

```
id | auth   | username | password    | firstname      | lastname | email
---|--------|----------|-------------|----------------|----------|------------------
1  | manual | admin    | $2y$10$...  | Administrador  | Sistema  | soporte@iser...
```

### Tabla `roles`

```
id | shortname     | name           | description                  | sortorder
---|---------------|----------------|------------------------------|----------
1  | administrator | Administrador  | Acceso completo al sistema   | 1
2  | manager       | Gestor         | Puede gestionar usuarios...  | 2
3  | user          | Usuario        | Usuario estándar del sistema | 3
```

### Tabla `contexts`

```
id | contextlevel | instanceid | path | depth
---|--------------|------------|------|------
1  | 10           | 0          | /1   | 1
```

**Nota:** `contextlevel = 10` es `CONTEXT_SYSTEM`

### Tabla `role_assignments`

```
id | roleid | contextid | userid | timemodified
---|--------|-----------|--------|-------------
1  | 1      | 1         | 1      | 1731930000
```

**Traducción:** Usuario 1 tiene rol 1 (administrator) en contexto 1 (system)

### Tabla `role_capabilities`

```
id | roleid | capability                          | permission | contextid
---|--------|-------------------------------------|------------|----------
1  | 1      | nexosupport/admin:viewdashboard     | 1          | 1
2  | 1      | nexosupport/admin:manageconfig      | 1          | 1
3  | 1      | nexosupport/admin:manageusers       | 1          | 1
... (16 capabilities en total)
```

**Nota:** `permission = 1` significa `PERMISSION_ALLOW`

## Diagrama de Flujo Completo

```
INSTALACIÓN INICIA
        ↓
[Stage: database]
  - Crea tablas
  - Crea contexto raíz (CONTEXT_SYSTEM)
        ↓
[Stage: admin]
  - Usuario ingresa datos del administrador
  - Crea registro en tabla `users`
  - Guarda user ID en sesión
        ↓
[Stage: finish]
  - Ejecuta install_rbac_system()
        ↓
[install_rbac_system()]
  - Crea capabilities (16 total)
  - Crea roles:
    * administrator (shortname='administrator')
    * manager (shortname='manager')
    * user (shortname='user')
  - Asigna TODAS las capabilities a administrator
  - Asigna capabilities seleccionadas a manager
  - Asigna capabilities básicas a user
        ↓
[Vuelve a finish.php]
  - Obtiene contexto de sistema
  - Obtiene rol administrator
  - Asigna rol administrator al usuario en contexto sistema
  - Inserta en role_assignments:
    (userid=1, roleid=1, contextid=1)
        ↓
✅ INSTALACIÓN COMPLETA
   Usuario es SITE ADMINISTRATOR
```

## Verificación Post-Instalación

### Para verificar que el usuario es site administrator:

```php
// Opción 1: Verificar directamente
$is_admin = is_siteadmin(1);  // true

// Opción 2: Verificar desde sesión
if (is_siteadmin()) {
    echo "Eres site administrator";
}

// Opción 3: Consulta SQL directa
$sql = "SELECT r.shortname, ra.userid
        FROM {role_assignments} ra
        JOIN {roles} r ON r.id = ra.roleid
        JOIN {contexts} c ON c.id = ra.contextid
        WHERE ra.userid = ?
        AND c.contextlevel = 10
        AND r.shortname = 'administrator'";

$result = $DB->get_records_sql($sql, [1]);
// Debería retornar un registro
```

## Protección de Páginas Administrativas

### Ejemplo de uso correcto:

```php
// admin/upgrade.php
require_once(__DIR__ . '/../config.php');
require_login();

// Verificar que el usuario está logueado
if (!isset($USER->id) || $USER->id == 0) {
    redirect("/login?returnurl=" . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Verificar que es site administrator
if (!is_siteadmin($USER->id)) {
    print_error('nopermissions', 'core');
}

// Usuario es site administrator, continuar...
```

## Roles Creados Durante la Instalación

### 1. Administrator (Site Administrator)

- **Shortname:** `administrator`
- **Sortorder:** 1
- **Capabilities:** TODAS (16 capabilities)
- **Uso:** Usuario principal del sistema con acceso completo
- **Asignado a:** Usuario creado durante instalación

### 2. Manager

- **Shortname:** `manager`
- **Sortorder:** 2
- **Capabilities:** 7 capabilities de gestión
  - `nexosupport/admin:viewdashboard`
  - `nexosupport/user:view`
  - `nexosupport/user:create`
  - `nexosupport/user:update`
  - `nexosupport/role:view`
  - `nexosupport/role:assign`
  - `nexosupport/log:view`
- **Uso:** Gestores que pueden administrar usuarios pero no configuración del sistema

### 3. User

- **Shortname:** `user`
- **Sortorder:** 3
- **Capabilities:** 2 capabilities básicas
  - `nexosupport/user:viewown`
  - `nexosupport/user:updateown`
- **Uso:** Usuarios estándar del sistema sin permisos administrativos

## Conclusión

### ✅ Estado Actual

El sistema **YA ESTÁ CORRECTAMENTE CONFIGURADO** para crear un site administrator durante la instalación:

1. ✅ Usuario se crea en la base de datos
2. ✅ Sistema RBAC se instala completamente
3. ✅ Rol "administrator" se crea con TODAS las capabilities
4. ✅ Rol "administrator" se asigna al usuario en contexto de sistema
5. ✅ Función `is_siteadmin()` verifica correctamente el rol
6. ✅ Páginas administrativas protegidas con verificación de `is_siteadmin()`

### ✅ No Se Requieren Cambios

El flujo de creación del site administrator funciona perfectamente y sigue las mejores prácticas de Moodle:

- Usuario tiene rol explícito en contexto de sistema
- No es hardcoded (userid=1), sino verificación por rol
- Sistema extensible: se pueden asignar más administrators después
- RBAC completo y funcional desde la instalación

### 📋 Recomendaciones

1. **Durante la instalación:**
   - Usar credenciales fuertes para el administrador
   - Anotar las credenciales de forma segura
   - Verificar que el email sea válido (se usará para recuperación)

2. **Después de la instalación:**
   - Iniciar sesión inmediatamente con el usuario administrador
   - Verificar acceso al panel de administración
   - Cambiar la contraseña si se usó una temporal

3. **Para crear más administrators:**
   - Ir a Admin > Usuarios
   - Crear nuevo usuario
   - Ir a Admin > Roles > Asignar roles
   - Asignar rol "Administrador" al usuario en contexto de sistema

## Contacto y Soporte

- **Alonso Arias** (Arquitecto): soporteplataformas@iser.edu.co
- **Yulian Moreno** (Desarrollador): nexo.operativo@iser.edu.co
- **Mauricio Zafra** (Supervisor): vicerrectoria@iser.edu.co

---

**Última actualización:** 2025-11-18
**Versión analizada:** 1.1.7
**Estado:** Sistema funcionando correctamente ✅
