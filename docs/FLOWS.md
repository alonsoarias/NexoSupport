# FLUJOS FUNCIONALES - NEXOSUPPORT

**Fecha**: 2025-11-12
**Versión**: 1.0
**Proyecto**: NexoSupport Authentication System

---

## 1. FLUJO DE AUTENTICACIÓN

### 1.1 Login con Credenciales

```
┌──────────────┐
│   Usuario    │
│ (no autent.) │
└──────┬───────┘
       │
       │ HTTP GET /login
       ▼
┌────────────────────────────────────────┐
│ AuthController::showLogin()            │
│ ├─ Verificar si ya está autenticado   │
│ │  └─ Si sí → Redirect /dashboard     │
│ └─ Render login.mustache               │
└───────────┬────────────────────────────┘
            │
            │ Mostrar formulario
            ▼
┌──────────────┐
│   Usuario    │
│ Ingresa:     │
│ - username   │
│ - password   │
└──────┬───────┘
       │
       │ HTTP POST /login
       ▼
┌────────────────────────────────────────────────────────────┐
│ AuthController::processLogin()                             │
│                                                             │
│ 1. Validar datos                                           │
│    ├─ username no vacío                                    │
│    └─ password no vacío                                    │
│                                                             │
│ 2. Buscar usuario                                          │
│    ├─ UserManager::getUserByUsername(username)             │
│    │  └─ Si no existe → getUserByEmail(username)           │
│    └─ Si no existe → ERROR: Usuario no encontrado          │
│                                                             │
│ 3. Verificar estado del usuario                            │
│    ├─ status == 'active'                                   │
│    ├─ deleted_at IS NULL                                   │
│    └─ Si inactivo/eliminado → ERROR: Usuario no disponible│
│                                                             │
│ 4. Verificar bloqueo de cuenta                             │
│    ├─ locked_until > NOW()                                 │
│    └─ Si bloqueado → ERROR: Cuenta bloqueada (X minutos)  │
│                                                             │
│ 5. Verificar contraseña                                    │
│    ├─ password_verify(password, user.password)             │
│    │                                                        │
│    ├─ SI FALLA:                                            │
│    │  ├─ Incrementar failed_login_attempts++               │
│    │  ├─ UserManager::update(id, {failed_attempts})        │
│    │  ├─ Si failed_attempts >= 5:                          │
│    │  │  ├─ locked_until = NOW() + 15 minutos              │
│    │  │  └─ ERROR: Demasiados intentos, bloqueado 15 min  │
│    │  ├─ Registrar login_attempts (success=0)              │
│    │  └─ ERROR: Credenciales incorrectas                   │
│    │                                                        │
│    └─ SI ÉXITO:                                            │
│       ├─ Resetear failed_login_attempts = 0                │
│       ├─ Resetear locked_until = NULL                      │
│       ├─ Actualizar last_login_at = NOW()                  │
│       ├─ Actualizar last_login_ip = REMOTE_ADDR            │
│       ├─ Registrar login_attempts (success=1)              │
│       └─ Continuar a paso 6                                │
│                                                             │
│ 6. Crear sesión                                            │
│    ├─ session_regenerate_id(true)                          │
│    ├─ $_SESSION['user_id'] = user.id                       │
│    ├─ $_SESSION['username'] = user.username                │
│    ├─ $_SESSION['email'] = user.email                      │
│    ├─ $_SESSION['authenticated'] = true                    │
│    ├─ $_SESSION['login_time'] = NOW()                      │
│    └─ $_SESSION['last_activity'] = NOW()                   │
│                                                             │
│ 7. Redirigir a dashboard                                   │
│    └─ Response::redirect('/dashboard')                     │
└────────────────────────────────────────────────────────────┘
       │
       ▼
┌──────────────┐
│   Usuario    │
│ (autenticado)│
│ → Dashboard  │
└──────────────┘
```

### 1.2 Logout

```
┌──────────────┐
│   Usuario    │
│ (autenticado)│
└──────┬───────┘
       │
       │ Click "Cerrar Sesión"
       │ HTTP GET /logout
       ▼
┌────────────────────────────────────────┐
│ AuthController::logout()                │
│ ├─ Vaciar $_SESSION = []               │
│ ├─ Eliminar cookie de sesión            │
│ │  └─ setcookie(PHPSESSID, '', past)   │
│ └─ session_destroy()                    │
└───────────┬────────────────────────────┘
            │
            │ Response::redirect('/')
            ▼
┌──────────────┐
│   Usuario    │
│ (no autent.) │
│ → Home       │
└──────────────┘
```

---

## 2. FLUJO DE AUTORIZACIÓN (RBAC)

### 2.1 Verificación de Permisos

```
┌──────────────┐
│   Usuario    │
│ (autenticado)│
└──────┬───────┘
       │
       │ HTTP GET /admin/users
       ▼
┌────────────────────────────────────────────────────────────┐
│ UserManagementController::index()                          │
│                                                             │
│ 1. Verificar autenticación                                 │
│    ├─ isset($_SESSION['user_id'])                          │
│    ├─ isset($_SESSION['authenticated'])                    │
│    └─ Si NO → Response::redirect('/login')                 │
│                                                             │
│ 2. Verificar permiso 'users.view'                          │
│    └─ PermissionManager::hasCapability(userId, 'users.view')│
│       │                                                     │
│       ├─ Verificar cache                                   │
│       │  └─ Si existe en cache → return cached result      │
│       │                                                     │
│       ├─ Obtener roles del usuario                         │
│       │  └─ SELECT * FROM roles r                          │
│       │     JOIN user_roles ur ON r.id = ur.role_id        │
│       │     WHERE ur.user_id = ?                           │
│       │     Resultado: [role_id=1 (admin)]                 │
│       │                                                     │
│       ├─ Obtener capability ID                             │
│       │  └─ SELECT id FROM permissions                     │
│       │     WHERE name = 'users.view'                      │
│       │     Resultado: permission_id=5                     │
│       │                                                     │
│       ├─ Para cada rol, obtener permission level           │
│       │  └─ SELECT permission FROM role_permissions        │
│       │     WHERE role_id = 1 AND permission_id = 5        │
│       │     Resultado: permission = CAP_ALLOW (1)          │
│       │                                                     │
│       ├─ Aplicar algoritmo de resolución:                  │
│       │  │                                                  │
│       │  ├─ Si algún rol tiene CAP_PROHIBIT (-1000)        │
│       │  │  └─ DENY (siempre, no importa otros)            │
│       │  │                                                  │
│       │  ├─ Si algún rol tiene CAP_ALLOW (1)               │
│       │  │  └─ ALLOW (a menos que haya PROHIBIT)           │
│       │  │                                                  │
│       │  ├─ Si algún rol tiene CAP_PREVENT (-1)            │
│       │  │  └─ DENY (si no hay ALLOW)                      │
│       │  │                                                  │
│       │  └─ Por defecto CAP_INHERIT (0)                    │
│       │     └─ DENY                                         │
│       │                                                     │
│       │  En este caso: CAP_ALLOW → PERMITIR                │
│       │                                                     │
│       ├─ Cachear resultado                                 │
│       │  └─ cache["1:users.view:1"] = true                 │
│       │                                                     │
│       └─ return true                                        │
│                                                             │
│ 3. Si tiene permiso → Continuar                            │
│    └─ UserManager::getUsers(limit, offset, filters)        │
│       └─ Render admin/users/index.mustache                 │
│                                                             │
│ 4. Si NO tiene permiso                                     │
│    └─ Response::html(403, "No tienes permiso")             │
└────────────────────────────────────────────────────────────┘
       │
       ▼
┌──────────────┐
│   Usuario    │
│ → Lista de   │
│   usuarios   │
└──────────────┘
```

### 2.2 Algoritmo de Resolución de Permisos

```
function hasCapability(userId, capability, contextId):
    permissions = []

    for each role in getUserRoles(userId):
        perm = getRoleCapabilityPermission(role, capability, contextId)
        permissions.append(perm)

    // Paso 1: PROHIBIT siempre gana
    if CAP_PROHIBIT in permissions:
        return FALSE

    // Paso 2: ALLOW tiene prioridad
    if CAP_ALLOW in permissions:
        return TRUE

    // Paso 3: PREVENT bloquea si no hay ALLOW
    if CAP_PREVENT in permissions:
        return FALSE

    // Paso 4: Default INHERIT = DENY
    return FALSE
```

**Ejemplos**:

| Rol 1 | Rol 2 | Resultado | Razón |
|-------|-------|-----------|-------|
| ALLOW | ALLOW | ✅ ALLOW | Al menos un ALLOW |
| ALLOW | PREVENT | ✅ ALLOW | ALLOW tiene prioridad |
| ALLOW | PROHIBIT | ❌ DENY | PROHIBIT siempre gana |
| PREVENT | PREVENT | ❌ DENY | No hay ALLOW |
| INHERIT | ALLOW | ✅ ALLOW | Al menos un ALLOW |
| INHERIT | INHERIT | ❌ DENY | Default DENY |

---

## 3. FLUJO DE GESTIÓN DE USUARIOS (ADMIN)

### 3.1 Crear Usuario

```
┌─────────────┐
│    Admin    │
└──────┬──────┘
       │
       │ HTTP GET /admin/users/create
       ▼
┌───────────────────────────────────────────┐
│ UserManagementController::create()        │
│ ├─ Verificar permiso 'users.create'       │
│ ├─ RoleManager::getAllRoles()             │
│ └─ Render admin/users/create.mustache     │
└───────────┬───────────────────────────────┘
            │
            │ Mostrar formulario
            ▼
┌─────────────┐
│    Admin    │
│ Ingresa:    │
│ - username  │
│ - email     │
│ - password  │
│ - first_name│
│ - last_name │
│ - roles[]   │
└──────┬──────┘
       │
       │ HTTP POST /admin/users/store
       ▼
┌───────────────────────────────────────────────────┐
│ UserManagementController::store()                 │
│                                                    │
│ 1. Verificar permiso 'users.create'               │
│                                                    │
│ 2. Validar datos                                  │
│    ├─ username: required, 3-50 chars, unique      │
│    ├─ email: required, valid email, unique        │
│    ├─ password: required, 8+ chars, policy        │
│    ├─ first_name: required                        │
│    ├─ last_name: required                         │
│    └─ roles: array de role_ids válidos            │
│                                                    │
│ 3. Crear usuario                                  │
│    ├─ UserManager::create({                       │
│    │     username, email, password,               │
│    │     first_name, last_name, status='active'   │
│    │  })                                           │
│    │  └─ Database::insert('users', data)          │
│    │     └─ password = password_hash($password)   │
│    │     └─ created_at = NOW()                    │
│    │     └─ return $userId                        │
│    │                                               │
│    └─ Si falla → ERROR: "No se pudo crear usuario"│
│                                                    │
│ 4. Asignar roles                                  │
│    └─ UserManager::syncRoles(userId, roleIds)     │
│       ├─ DELETE FROM user_roles WHERE user_id = ? │
│       └─ Para cada roleId:                        │
│          └─ INSERT INTO user_roles                │
│             (user_id, role_id, assigned_at)       │
│                                                    │
│ 5. Registrar auditoría                            │
│    └─ INSERT INTO audit_log                       │
│       (user_id, action, entity_type, entity_id)   │
│       VALUES (admin_id, 'create', 'user', userId) │
│                                                    │
│ 6. Flash message + Redirect                       │
│    └─ $_SESSION['success'] = "Usuario creado"    │
│       Response::redirect('/admin/users')          │
└───────────────────────────────────────────────────┘
       │
       ▼
┌─────────────┐
│    Admin    │
│ → Lista de  │
│   usuarios  │
└─────────────┘
```

### 3.2 Editar Usuario

```
┌─────────────┐
│    Admin    │
└──────┬──────┘
       │
       │ Selecciona usuario ID=5
       │ HTTP POST /admin/users/edit (con user_id=5)
       ▼
┌───────────────────────────────────────────┐
│ UserManagementController::edit()          │
│ ├─ Verificar permiso 'users.update'       │
│ ├─ UserManager::getUserById(5)            │
│ ├─ UserManager::getUserRoles(5)           │
│ ├─ RoleManager::getAllRoles()             │
│ └─ Render admin/users/edit.mustache       │
└───────────┬───────────────────────────────┘
            │
            │ Mostrar formulario prellenado
            ▼
┌─────────────┐
│    Admin    │
│ Modifica:   │
│ - email     │
│ - status    │
│ - roles[]   │
└──────┬──────┘
       │
       │ HTTP POST /admin/users/update
       ▼
┌───────────────────────────────────────────────────┐
│ UserManagementController::update()                │
│                                                    │
│ 1. Verificar permiso 'users.update'               │
│                                                    │
│ 2. Validar datos                                  │
│    ├─ email: valid, unique (excluyendo user_id=5) │
│    ├─ status: enum('active','inactive','suspended')│
│    ├─ roles: array válidos                        │
│    └─ Si password: 8+ chars, policy               │
│                                                    │
│ 3. Actualizar usuario                             │
│    ├─ UserManager::update(5, {                    │
│    │     email, status, updated_at=NOW()          │
│    │  })                                           │
│    │  └─ Si incluye password:                     │
│    │     └─ password = password_hash($newPass)    │
│    │                                               │
│    └─ Si falla → ERROR                            │
│                                                    │
│ 4. Actualizar roles                               │
│    └─ UserManager::syncRoles(5, newRoleIds)       │
│                                                    │
│ 5. Registrar auditoría                            │
│    └─ INSERT INTO audit_log                       │
│       (user_id, action, entity_type, entity_id)   │
│       VALUES (admin_id, 'update', 'user', 5)      │
│                                                    │
│ 6. Flash message + Redirect                       │
│    └─ $_SESSION['success'] = "Usuario actualizado"│
│       Response::redirect('/admin/users')          │
└───────────────────────────────────────────────────┘
```

### 3.3 Eliminar Usuario (Soft Delete)

```
┌─────────────┐
│    Admin    │
└──────┬──────┘
       │
       │ Click "Eliminar" en usuario ID=5
       │ HTTP POST /admin/users/delete (con user_id=5)
       ▼
┌───────────────────────────────────────────────────┐
│ UserManagementController::delete()                │
│                                                    │
│ 1. Verificar permiso 'users.delete'               │
│                                                    │
│ 2. Verificar que usuario existe                   │
│    └─ UserManager::getUserById(5)                 │
│                                                    │
│ 3. Soft delete (NO elimina físicamente)           │
│    └─ UserManager::softDelete(5)                  │
│       └─ UPDATE users                             │
│          SET deleted_at = NOW(), updated_at = NOW()│
│          WHERE id = 5                              │
│                                                    │
│ 4. Cerrar sesiones activas (opcional)             │
│    └─ DELETE FROM sessions WHERE user_id = 5      │
│                                                    │
│ 5. Registrar auditoría                            │
│    └─ INSERT INTO audit_log                       │
│       (user_id, action, entity_type, entity_id)   │
│       VALUES (admin_id, 'delete', 'user', 5)      │
│                                                    │
│ 6. Flash message + Redirect                       │
│    └─ $_SESSION['success'] = "Usuario eliminado"  │
│       Response::redirect('/admin/users')          │
└───────────────────────────────────────────────────┘
       │
       ▼
┌─────────────┐
│    Admin    │
│ → Usuario   │
│   marcado   │
│   deleted   │
└─────────────┘
```

---

## 4. FLUJO DE INSTALACIÓN DEL SISTEMA

### 4.1 Instalación Completa

```
┌─────────────┐
│  Usuario    │
│ (instalador)│
└──────┬──────┘
       │
       │ HTTP GET /install.php
       ▼
┌───────────────────────────────────────────────────┐
│ Verificar instalación                             │
│ ├─ ¿Existe .env?                                  │
│ │  └─ NO → Continuar con instalación             │
│ │  └─ SÍ → ¿Contiene INSTALLED=true?             │
│ │     └─ SÍ → Mostrar "Ya instalado"             │
│ │     └─ NO → Continuar con instalación          │
│ └─ Iniciar Instalador                             │
└───────────┬───────────────────────────────────────┘
            │
            ▼
┌───────────────────────────────────────────────────────────┐
│ ETAPA 1: Verificación de Requisitos                      │
│                                                           │
│ Verificar:                                                │
│ ├─ PHP >= 8.1 ✅                                          │
│ ├─ Extensiones:                                           │
│ │  ├─ pdo ✅                                              │
│ │  ├─ pdo_mysql / pdo_pgsql / pdo_sqlite ✅              │
│ │  ├─ json ✅                                             │
│ │  ├─ mbstring ✅                                         │
│ │  ├─ openssl ✅                                          │
│ │  ├─ session ✅                                          │
│ │  ├─ ctype ✅                                            │
│ │  ├─ hash ✅                                             │
│ │  └─ curl (opcional) ⚠️                                  │
│ │                                                          │
│ └─ Permisos de escritura:                                 │
│    ├─ /var/logs/ ✅                                       │
│    ├─ /var/cache/ ✅                                      │
│    ├─ /modules/plugins/ ✅                                │
│    └─ / (raíz para crear .env) ✅                         │
│                                                           │
│ Si todos ✅ → Botón "Siguiente"                           │
│ Si alguno ❌ → Mostrar error y bloquear                   │
└───────────┬───────────────────────────────────────────────┘
            │
            ▼
┌───────────────────────────────────────────────────────────┐
│ ETAPA 2: Configuración de Base de Datos                  │
│                                                           │
│ Formulario:                                               │
│ ├─ Driver: [MySQL ▼] (opciones: MySQL, PostgreSQL, SQLite)│
│ ├─ Host: [localhost]                                      │
│ ├─ Port: [3306]                                           │
│ ├─ Database: [nexosupport]                                │
│ ├─ Username: [root]                                       │
│ ├─ Password: [••••]                                       │
│ ├─ Prefix: [iser_] (opcional)                             │
│ └─ [Botón: Probar Conexión]                               │
│                                                           │
│ Al hacer click en "Probar Conexión":                      │
│ ├─ Construir DSN según driver                             │
│ ├─ new PDO(dsn, username, password)                       │
│ ├─ Si conecta ✅ → Mostrar "Conexión exitosa"            │
│ └─ Si falla ❌ → Mostrar error detallado                  │
│                                                           │
│ Al hacer click en "Siguiente":                            │
│ ├─ Validar conexión nuevamente                            │
│ ├─ Crear base de datos si no existe:                      │
│ │  └─ CREATE DATABASE IF NOT EXISTS nexosupport           │
│ └─ Guardar config en $_SESSION                            │
└───────────┬───────────────────────────────────────────────┘
            │
            ▼
┌───────────────────────────────────────────────────────────┐
│ ETAPA 3: Instalación de Base de Datos                    │
│                                                           │
│ Mostrar barra de progreso en tiempo real                  │
│                                                           │
│ SchemaInstaller::install(schema.xml):                     │
│                                                           │
│ 1. Parsear schema.xml                                     │
│    ├─ XMLParser::parseFile('schema.xml')                  │
│    └─ Convertir a array                                   │
│                                                           │
│ 2. Crear tablas (14 tablas):                              │
│    ├─ [████░░░░░░] config                                 │
│    ├─ [████████░░] users                                  │
│    ├─ [████████░░] password_reset_tokens                  │
│    ├─ [████████░░] login_attempts                         │
│    ├─ [████████░░] user_profiles                          │
│    ├─ [████████░░] roles                                  │
│    ├─ [████████░░] permissions                            │
│    ├─ [████████░░] user_roles                             │
│    ├─ [████████░░] role_permissions                       │
│    ├─ [████████░░] sessions                               │
│    ├─ [████████░░] jwt_tokens                             │
│    ├─ [████████░░] user_mfa                               │
│    ├─ [████████░░] logs                                   │
│    └─ [██████████] audit_log ✅                           │
│                                                           │
│ 3. Crear índices y foreign keys                           │
│    └─ Para cada tabla:                                    │
│       ├─ CREATE INDEX ...                                 │
│       └─ ALTER TABLE ... ADD FOREIGN KEY ...              │
│                                                           │
│ 4. Insertar datos iniciales                               │
│    ├─ INSERT INTO roles ... (admin, user, guest)          │
│    ├─ INSERT INTO permissions ... (35 permisos)           │
│    └─ INSERT INTO role_permissions ...                    │
│       (asignar todos los permisos al rol admin)           │
│                                                           │
│ 5. Mostrar log detallado:                                 │
│    ✅ Tabla 'config' creada                               │
│    ✅ Tabla 'users' creada                                │
│    ✅ Índice 'uk_username' creado                         │
│    ✅ Índice 'uk_email' creado                            │
│    ... (14 tablas × ~3 líneas = 42+ líneas de log)        │
│    ✅ 35 permisos insertados                              │
│    ✅ 35 asignaciones a rol admin                         │
│                                                           │
│ Si error → Rollback + Mostrar error                       │
│ Si éxito → Botón "Siguiente"                              │
└───────────┬───────────────────────────────────────────────┘
            │
            ▼
┌───────────────────────────────────────────────────────────┐
│ ETAPA 4: Usuario Administrador                           │
│                                                           │
│ Formulario:                                               │
│ ├─ Username: [admin] (3-50 chars, único)                  │
│ ├─ Email: [admin@example.com] (válido, único)             │
│ ├─ Password: [••••••••] (8+ chars, debe cumplir policy)   │
│ ├─ Confirmar: [••••••••] (debe coincidir)                 │
│ ├─ First Name: [Admin]                                    │
│ └─ Last Name: [User]                                      │
│                                                           │
│ Validación en tiempo real:                                │
│ ├─ Password strength meter: [████░░░░░░] Débil            │
│ ├─ Requirements:                                           │
│ │  ├─ ✅ Mínimo 8 caracteres                              │
│ │  ├─ ❌ Al menos una mayúscula                           │
│ │  ├─ ✅ Al menos una minúscula                           │
│ │  ├─ ✅ Al menos un número                               │
│ │  └─ ❌ Al menos un carácter especial                    │
│ └─ Password match: ❌ Las contraseñas no coinciden         │
│                                                           │
│ Al hacer click en "Siguiente":                            │
│ ├─ Validar datos completos                                │
│ ├─ UserManager::create({                                  │
│ │     username, email, password, first_name, last_name,   │
│ │     status='active'                                     │
│ │  })                                                      │
│ │  └─ INSERT INTO users ... (password hasheado)           │
│ │     return userId = 1                                   │
│ │                                                          │
│ ├─ UserManager::assignRole(userId=1, roleId=1 (admin))    │
│ │  └─ INSERT INTO user_roles (user_id=1, role_id=1)       │
│ │                                                          │
│ └─ Guardar en $_SESSION                                   │
└───────────┬───────────────────────────────────────────────┘
            │
            ▼
┌───────────────────────────────────────────────────────────┐
│ ETAPA 5: Finalización                                    │
│                                                           │
│ 1. Generar archivo .env                                   │
│    ├─ Usar plantilla .env.example                         │
│    ├─ Reemplazar variables con valores ingresados:        │
│    │  ├─ APP_ENV=production                               │
│    │  ├─ APP_DEBUG=false                                  │
│    │  ├─ APP_NAME="NexoSupport"                           │
│    │  ├─ APP_URL=http://localhost                         │
│    │  ├─ DB_CONNECTION=mysql                              │
│    │  ├─ DB_HOST=localhost                                │
│    │  ├─ DB_PORT=3306                                     │
│    │  ├─ DB_DATABASE=nexosupport                          │
│    │  ├─ DB_USERNAME=root                                 │
│    │  ├─ DB_PASSWORD=secret                               │
│    │  ├─ JWT_SECRET=<generado aleatorio>                  │
│    │  └─ INSTALLED=true ← IMPORTANTE                      │
│    │                                                       │
│    └─ file_put_contents('/.env', $envContent)             │
│                                                           │
│ 2. Verificar instalación                                  │
│    ├─ Probar conexión a BD                                │
│    ├─ Verificar que .env existe                           │
│    ├─ Verificar que usuario admin existe                  │
│    └─ Todo ✅ → Instalación completada                    │
│                                                           │
│ 3. Mostrar mensaje de éxito:                              │
│    ╔════════════════════════════════════════╗             │
│    ║  ✅ Instalación Completada             ║             │
│    ║                                        ║             │
│    ║  NexoSupport se instaló correctamente ║             │
│    ║                                        ║             │
│    ║  Credenciales de administrador:        ║             │
│    ║  Username: admin                       ║             │
│    ║  Email: admin@example.com              ║             │
│    ║                                        ║             │
│    ║  IMPORTANTE:                           ║             │
│    ║  - Elimina o protege /install/         ║             │
│    ║  - Cambia la contraseña de admin       ║             │
│    ║  - Configura los settings de producción║             │
│    ║                                        ║             │
│    ║  [Botón: Ir al Panel de Admin]         ║             │
│    ╚════════════════════════════════════════╝             │
└───────────┬───────────────────────────────────────────────┘
            │
            │ Click "Ir al Panel de Admin"
            ▼
┌─────────────┐
│   Usuario   │
│ → Login     │
│ → Dashboard │
└─────────────┘
```

---

## 5. CONCLUSIÓN

Este documento mapea los 4 flujos funcionales principales de **NexoSupport**:

1. **Autenticación**: Login/logout con bloqueo de cuentas
2. **Autorización RBAC**: Verificación de permisos granulares con algoritmo de resolución
3. **Gestión de Usuarios**: CRUD completo con soft delete y asignación de roles
4. **Instalación**: Proceso de 5 etapas con instalación desde schema.xml

Todos los flujos están mapeados con:
- ✅ Pasos detallados
- ✅ Validaciones
- ✅ Queries SQL involucradas
- ✅ Mensajes de error
- ✅ Auditoría de acciones

**Siguiente paso**: Diseñar las especificaciones de refactorización basadas en el análisis completo (ANALYSIS.md, ARCHITECTURE.md, DATABASE_ANALYSIS.md y FLOWS.md).
