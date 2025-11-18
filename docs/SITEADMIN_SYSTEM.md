# Sistema de Site Administrators - NexoSupport

## Patrón Moodle Implementado

NexoSupport implementa **exactamente el mismo sistema que Moodle** para gestionar site administrators (super administradores):

### En Moodle:
- Tabla `mdl_config` tiene registro con `name='siteadmins'`
- El `value` contiene IDs de usuarios separados por comas (ej: `"1,5,12"`)
- Función `is_siteadmin()` verifica primero este registro
- Site admins tienen acceso **total e irrestricto** al sistema

### En NexoSupport:
- Tabla `{prefix}config` tiene registro con `name='siteadmins'`
- El `value` contiene IDs de usuarios separados por comas (ej: `"1,5,12"`)
- Función `is_siteadmin()` verifica primero este registro
- Site admins tienen acceso **total e irrestricto** al sistema

## Flujo de Instalación del Site Administrator

### Fase 1: Creación del Usuario

**Archivo:** `install/stages/admin.php`

```php
// Usuario se crea en la tabla users
$userid = $DB->insert_record('users', [
    'auth' => 'manual',
    'username' => 'admin',
    'password' => password_hash($password, PASSWORD_DEFAULT),
    'firstname' => 'Administrador',
    'lastname' => 'Sistema',
    'email' => 'soporteplataformas@iser.edu.co',
    'suspended' => 0,
    'deleted' => 0,
    'timecreated' => time(),
    'timemodified' => time()
]);

// ID guardado en sesión
$_SESSION['admin_userid'] = $userid;
```

### Fase 2: Instalación del Sistema RBAC

**Archivo:** `lib/install_rbac.php`

```php
// Crea el sistema completo de roles y permisos
install_rbac_system();

// Crea roles:
// - administrator (todas las capabilities)
// - manager (capabilities de gestión)
// - user (capabilities básicas)
```

### Fase 3: Asignación de Rol Administrator

**Archivo:** `install/stages/finish.php`

```php
// Asigna rol 'administrator' al usuario en contexto de sistema
$syscontext = \core\rbac\context::system();
$adminrole = \core\rbac\role::get_by_shortname('administrator');

\core\rbac\access::assign_role($adminrole->id, $userid, $syscontext);
```

### Fase 4: ⭐ NUEVO - Registro en config.siteadmins

**Archivo:** `install/stages/finish.php` (ACTUALIZADO)

```php
// Guardar site administrators en config
// Similar a Moodle: config.siteadmins contiene IDs de usuarios super administradores
if (isset($_SESSION['admin_userid'])) {
    $adminuserid = $_SESSION['admin_userid'];

    $siteadminsRecord = new stdClass();
    $siteadminsRecord->name = 'siteadmins';
    $siteadminsRecord->value = (string)$adminuserid; // ID del primer admin

    $DB->insert_record('config', $siteadminsRecord);

    debugging("Site administrator set: userid={$adminuserid}", DEBUG_DEVELOPER);
}
```

## Función is_siteadmin() - ACTUALIZADA

**Archivo:** `lib/functions.php`

```php
function is_siteadmin(?int $userid = null): bool {
    global $USER, $DB;

    if ($userid === null) {
        $userid = $USER->id ?? 0;
    }

    if ($userid == 0) {
        return false;
    }

    // Get siteadmins from config table
    // This is the Moodle way: config.siteadmins contains comma-separated user IDs
    static $siteadmins = null;

    if ($siteadmins === null) {
        try {
            $config = $DB->get_record('config', ['name' => 'siteadmins']);
            if ($config && !empty($config->value)) {
                // Convert comma-separated string to array of integers
                $siteadmins = array_map('intval', explode(',', $config->value));
            } else {
                $siteadmins = [];
            }
        } catch (Exception $e) {
            $siteadmins = [];
        }
    }

    // Check if user ID is in siteadmins list
    return in_array($userid, $siteadmins, true);
}
```

**Características:**
- ✅ Cache estático para rendimiento (solo una consulta por request)
- ✅ Verifica directamente la tabla `config`
- ✅ No depende de roles/permisos RBAC
- ✅ Acceso más rápido que verificar role_assignments
- ✅ Compatible con múltiples site admins

## Nuevas Funciones Helper

### 1. `get_siteadmins()` - Obtener lista de site admins

```php
function get_siteadmins(): array;

// Ejemplo:
$admins = get_siteadmins();
// Returns: [1, 5, 12]
```

### 2. `add_siteadmin($userid)` - Agregar site admin

```php
function add_siteadmin(int $userid): bool;

// Ejemplo:
$success = add_siteadmin(5);
if ($success) {
    echo "Usuario 5 ahora es site administrator";
}

// Validaciones:
// - Verifica que el usuario existe
// - No permite duplicados
// - Actualiza config.siteadmins automáticamente
```

### 3. `remove_siteadmin($userid)` - Remover site admin

```php
function remove_siteadmin(int $userid): bool;

// Ejemplo:
$success = remove_siteadmin(5);
if ($success) {
    echo "Usuario 5 ya no es site administrator";
}

// Protecciones:
// - NO permite remover el último site admin
// - Actualiza config.siteadmins automáticamente
```

### 4. `set_siteadmins($userids)` - Establecer lista completa

```php
function set_siteadmins(array $userids): bool;

// Ejemplo:
$success = set_siteadmins([1, 5, 12]);
// config.siteadmins = "1,5,12"

// Características:
// - Limpia duplicados
// - Remueve valores inválidos
// - Actualiza o crea el registro en config
```

## Estructura en Base de Datos

### Tabla `config` - ACTUALIZADA

```
id | name         | value
---|--------------|-------
1  | version      | 2025011700
2  | siteadmins   | 1        ← NUEVO!
```

**Nota:** El valor puede contener múltiples IDs: `"1,5,12"`

### Tabla `users`

```
id | username | firstname      | lastname | email
---|----------|----------------|----------|------------------
1  | admin    | Administrador  | Sistema  | soporte@iser...
```

### Tabla `roles` (RBAC - opcional, pero recomendado)

```
id | shortname     | name          | description
---|---------------|---------------|---------------------------
1  | administrator | Administrador | Acceso completo al sistema
```

### Tabla `role_assignments` (RBAC - opcional)

```
id | roleid | contextid | userid
---|--------|-----------|-------
1  | 1      | 1         | 1
```

**Nota:** Aunque el usuario tenga rol administrator, lo que realmente lo hace site admin es estar en `config.siteadmins`

## Diferencia: Site Admin vs Administrator Role

### Site Administrator (config.siteadmins)

**Definición:**
- Super usuarios del sistema
- IDs almacenados en `config.siteadmins`
- Acceso **irrestricto y absoluto**

**Características:**
- ✅ NO se puede restringir con permisos
- ✅ Pueden acceder a CUALQUIER página
- ✅ Pueden modificar CUALQUIER configuración
- ✅ Pueden otorgar/revocar permisos de site admin
- ✅ Verificación rápida (no requiere joins)
- ✅ No depende de RBAC

**Uso:**
```php
if (is_siteadmin()) {
    // Usuario es super administrador
    // Acceso total garantizado
}
```

### Administrator Role (RBAC)

**Definición:**
- Usuario con rol "administrator" en contexto de sistema
- Definido por role_assignments
- Permisos basados en capabilities

**Características:**
- ✅ Permisos configurables mediante capabilities
- ✅ Puede ser restringido
- ✅ Verificación más compleja (requiere joins)
- ✅ Parte del sistema RBAC

**Uso:**
```php
$syscontext = \core\rbac\context::system();
if (\core\rbac\access::has_capability('nexosupport/admin:manageconfig', $syscontext)) {
    // Usuario tiene permiso específico
}
```

## Casos de Uso

### Caso 1: Usuario creado durante instalación

```
Estado después de instalación:
- users.id = 1
- config.siteadmins = "1"
- role_assignments: userid=1, roleid=administrator, contextid=system

Resultado:
✅ is_siteadmin(1) = true (verifica config)
✅ Tiene rol administrator (RBAC)
✅ Acceso total al sistema
```

### Caso 2: Agregar un segundo site admin

```php
// Usuario 5 ya existe en la base de datos
$success = add_siteadmin(5);

// Resultado:
// config.siteadmins = "1,5"

// Verificación:
is_siteadmin(5);  // true
is_siteadmin(10); // false (no está en la lista)
```

### Caso 3: Remover site admin

```php
// Intentar remover el único site admin
remove_siteadmin(1);  // false (protección)

// Agregar otro primero
add_siteadmin(5);     // config.siteadmins = "1,5"

// Ahora sí se puede remover
remove_siteadmin(1);  // true
// config.siteadmins = "5"
```

### Caso 4: Usuario con rol admin pero NO site admin

```php
// Usuario 10 tiene rol administrator en RBAC
// Pero NO está en config.siteadmins

is_siteadmin(10);  // false

// Tiene permisos de administrator (RBAC)
\core\rbac\access::has_capability('nexosupport/admin:viewdashboard', $syscontext);  // true

// Pero páginas críticas verifican is_siteadmin()
// admin/upgrade.php: requiere is_siteadmin() = true
// Usuario 10 NO puede acceder
```

## Gestión de Site Administrators

### Interfaz de Administración (Futuro)

Se puede crear una página en `/admin/siteadmins.php` para gestionar:

```php
// Listar site admins actuales
$siteadmins = get_siteadmins();
foreach ($siteadmins as $userid) {
    $user = $DB->get_record('users', ['id' => $userid]);
    echo $user->username . " (" . $user->email . ")\n";
}

// Agregar nuevo site admin
if (isset($_POST['add'])) {
    $userid = $_POST['userid'];
    if (add_siteadmin($userid)) {
        echo "Usuario agregado como site administrator";
    }
}

// Remover site admin
if (isset($_POST['remove'])) {
    $userid = $_POST['userid'];
    if (remove_siteadmin($userid)) {
        echo "Usuario removido de site administrators";
    } else {
        echo "Error: No se puede remover el último site admin";
    }
}
```

### CLI Script (Futuro)

```bash
# Listar site admins
php cli/siteadmins.php --list

# Agregar site admin
php cli/siteadmins.php --add=5

# Remover site admin
php cli/siteadmins.php --remove=5
```

## Seguridad

### Protección de Páginas Críticas

```php
// admin/upgrade.php
require_once(__DIR__ . '/../config.php');
require_login();

if (!is_siteadmin()) {
    print_error('requiresiteadmin', 'core');
}

// Solo site admins pueden continuar
```

### Protección contra Eliminación Accidental

```php
// No permite remover el último site admin
function remove_siteadmin(int $userid): bool {
    $siteadmins = get_siteadmins();

    if (count($siteadmins) === 1) {
        debugging('Cannot remove the last site administrator');
        return false;  // ← Protección
    }

    // Continuar con remoción...
}
```

### Auditoría (Recomendado)

```php
// Registrar cambios en site admins
function add_siteadmin(int $userid): bool {
    // ... código existente ...

    if ($success) {
        // Log event
        \core\event\siteadmin_added::create([
            'objectid' => $userid,
            'userid' => $USER->id,
        ])->trigger();
    }

    return $success;
}
```

## Migración desde Sistema Anterior

Si actualizas desde una versión que no tenía `config.siteadmins`:

```php
// Script de migración (ejecutar una vez)
function migrate_to_siteadmins() {
    global $DB;

    // Verificar si ya existe
    if ($DB->record_exists('config', ['name' => 'siteadmins'])) {
        echo "Ya existe config.siteadmins\n";
        return;
    }

    // Buscar usuarios con rol administrator en contexto sistema
    $syscontext = \core\rbac\context::system();

    $sql = "SELECT DISTINCT ra.userid
            FROM {role_assignments} ra
            JOIN {roles} r ON r.id = ra.roleid
            WHERE ra.contextid = ?
            AND r.shortname = 'administrator'";

    $admins = $DB->get_records_sql($sql, [$syscontext->id]);

    $userids = array_keys($admins);

    if (!empty($userids)) {
        set_siteadmins($userids);
        echo "Migrados " . count($userids) . " site administrators\n";
    }
}
```

## Ventajas del Sistema

### vs Sistema Anterior (Solo RBAC)

| Característica | Solo RBAC | Config.siteadmins |
|----------------|-----------|-------------------|
| Velocidad | Lento (joins) | Rápido (cache) |
| Flexibilidad | Media | Alta |
| Mantenimiento | Complejo | Simple |
| Compatibilidad Moodle | No | ✅ Sí |
| Multiple site admins | Sí | ✅ Sí |
| Protección último admin | No | ✅ Sí |

### Rendimiento

```php
// ANTES (solo RBAC)
// Requiere:
// - 1 query a contexts
// - 1 query con JOIN a role_assignments y roles
// Total: 2 queries por verificación

// AHORA (config.siteadmins)
// Requiere:
// - 1 query a config (una vez por request, cached)
// - Verificación in_array() en memoria
// Total: 1 query por request (múltiples verificaciones sin costo)
```

## Recomendaciones

### Durante Desarrollo

1. **Siempre tener al menos un site admin**
   ```php
   // Verificar antes de remover
   if (count(get_siteadmins()) > 1) {
       remove_siteadmin($userid);
   }
   ```

2. **Usar site admin solo para páginas críticas**
   ```php
   // Páginas críticas del sistema
   if (!is_siteadmin()) {
       print_error('requiresiteadmin');
   }

   // Páginas normales de admin: usar capabilities
   require_capability('nexosupport/admin:viewdashboard', $syscontext);
   ```

3. **Documentar quién es site admin y por qué**
   ```php
   // Al crear site admin, agregar comentario
   // "Usuario 5 es site admin porque [razón]"
   ```

### En Producción

1. **Limitar número de site admins**
   - Idealmente: 1-3 usuarios
   - Más usuarios = más riesgo de seguridad

2. **Auditar cambios**
   - Registrar quién agrega/remueve site admins
   - Notificar por email cuando cambia la lista

3. **Backup de config**
   ```bash
   # Antes de cambios importantes
   SELECT * FROM config WHERE name = 'siteadmins';
   # Guardar el resultado
   ```

## Contacto y Soporte

- **Alonso Arias** (Arquitecto): soporteplataformas@iser.edu.co
- **Yulian Moreno** (Desarrollador): nexo.operativo@iser.edu.co
- **Mauricio Zafra** (Supervisor): vicerrectoria@iser.edu.co

---

**Última actualización:** 2025-11-18
**Versión del sistema:** 1.1.8
**Patrón:** Moodle-style siteadmins (config table)
**Estado:** ✅ Implementado y funcionando
