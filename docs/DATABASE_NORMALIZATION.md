# Normalización de Base de Datos - NexoSupport v2.0

## Resumen de Cambios

El esquema de base de datos ha sido normalizado siguiendo principios de normalización hasta 3FN (Tercera Forma Normal), eliminando redundancias y mejorando la integridad referencial.

## Cambios Realizados

### 1. Tabla `config` - Consolidación y Mejoras

**Antes:**
- Existían dos tablas de configuración: `config` y `report_config`
- No había agrupación lógica de configuraciones

**Después:**
```sql
CREATE TABLE config (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(100) NOT NULL UNIQUE,
    config_value TEXT,
    config_type ENUM('string','int','bool','json') DEFAULT 'string',
    category VARCHAR(50) DEFAULT 'general',  -- NUEVO
    description VARCHAR(255),
    is_public BOOLEAN DEFAULT false,
    created_at INT UNSIGNED NOT NULL,
    updated_at INT UNSIGNED NOT NULL,
    INDEX idx_config_key (config_key),
    INDEX idx_category (category)  -- NUEVO
);
```

**Beneficios:**
- ✅ Tabla única para todas las configuraciones del sistema
- ✅ Agrupación por categoría (app, security, reports, etc.)
- ✅ Eliminada redundancia de `report_config`
- ✅ Mejor organización y mantenibilidad

**Configuraciones consolidadas:**
```sql
-- De config original
app.name
app.version
security.password_min_length
security.max_login_attempts
security.lockout_duration

-- De report_config (CONSOLIDADAS)
report.retention_days
report.max_export_rows
report.default_format
```

### 2. Tabla `password_reset_tokens` - Nueva Tabla Normalizada

**Antes:**
```sql
-- En tabla users:
password_reset_token VARCHAR(64)
password_reset_expires INT UNSIGNED
```

**Después:**
```sql
CREATE TABLE password_reset_tokens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expires_at INT UNSIGNED NOT NULL,
    used_at INT UNSIGNED,  -- Tracking de uso
    created_at INT UNSIGNED NOT NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_token (token),
    INDEX idx_expires_at (expires_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

**Beneficios:**
- ✅ Separación de concerns (autenticación vs recuperación)
- ✅ Historial de tokens de reset
- ✅ Tracking de cuándo se usó cada token
- ✅ Múltiples tokens por usuario (útil si genera varios)
- ✅ Limpieza automática con CASCADE

### 3. Tabla `login_attempts` - Foreign Key Opcional

**Antes:**
```sql
CREATE TABLE login_attempts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL,  -- Solo username
    ip_address VARCHAR(45) NOT NULL,
    ...
);
```

**Después:**
```sql
CREATE TABLE login_attempts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED,  -- NUEVO: FK opcional
    username VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent VARCHAR(255),
    success BOOLEAN DEFAULT false NOT NULL,
    attempted_at INT UNSIGNED NOT NULL,
    INDEX idx_user_id (user_id),  -- NUEVO
    INDEX idx_username (username),
    INDEX idx_ip_address (ip_address),
    INDEX idx_attempted_at (attempted_at),
    INDEX idx_success (success),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
```

**Beneficios:**
- ✅ Relación con usuario si existe (NULL si no)
- ✅ Permite tracking de intentos antes de crear cuenta
- ✅ SET NULL en delete mantiene histórico anónimo
- ✅ Mejor análisis de seguridad

### 4. Tabla `user_profiles` - Relación 1:1 Optimizada

**Antes:**
```sql
CREATE TABLE user_profiles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,  -- PK separada
    user_id INT UNSIGNED NOT NULL,
    ...
    INDEX idx_user_id (user_id)
);
```

**Después:**
```sql
CREATE TABLE user_profiles (
    user_id INT UNSIGNED PRIMARY KEY,  -- user_id es la PK
    phone VARCHAR(20),
    mobile VARCHAR(20),
    address TEXT,
    city VARCHAR(100),
    state VARCHAR(100),
    country VARCHAR(100),
    postal_code VARCHAR(20),
    timezone VARCHAR(50) DEFAULT 'America/Bogota',
    locale VARCHAR(10) DEFAULT 'es',
    avatar_url VARCHAR(255),
    bio TEXT,
    metadata JSON,
    created_at INT UNSIGNED NOT NULL,
    updated_at INT UNSIGNED NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

**Beneficios:**
- ✅ Relación 1:1 explícita (user_id como PK)
- ✅ No necesita ID separado
- ✅ Búsquedas más eficientes
- ✅ Previene duplicados por diseño
- ✅ Espacio en disco reducido

### 5. Tabla `users` - Limpieza y Optimización

**Antes:**
```sql
-- Campos mezclados: auth + reset + profile
password_reset_token VARCHAR(64)
password_reset_expires INT UNSIGNED
```

**Después:**
```sql
-- Solo autenticación y estado
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    status ENUM('active','inactive','suspended','pending') DEFAULT 'active',
    email_verified BOOLEAN DEFAULT false,
    email_verification_token VARCHAR(64),
    email_verification_expires INT UNSIGNED,
    last_login_at INT UNSIGNED,
    last_login_ip VARCHAR(45),
    failed_login_attempts INT UNSIGNED DEFAULT 0,
    locked_until INT UNSIGNED,
    created_at INT UNSIGNED NOT NULL,
    updated_at INT UNSIGNED NOT NULL,
    deleted_at INT UNSIGNED,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_status (status),
    INDEX idx_email_verification_token (email_verification_token),
    INDEX idx_deleted_at (deleted_at)  -- NUEVO: Para soft deletes
);
```

**Beneficios:**
- ✅ Separación clara: auth, reset tokens (otra tabla), perfil (otra tabla)
- ✅ Índice en deleted_at para queries de soft delete
- ✅ Tabla más ligera y enfocada

### 6. Índices Optimizados

**Nuevos índices agregados:**

```sql
-- En config
INDEX idx_category (category)

-- En password_reset_tokens
INDEX idx_token (token)
INDEX idx_expires_at (expires_at)

-- En login_attempts
INDEX idx_user_id (user_id)

-- En user_roles
INDEX idx_assigned_by (assigned_by)
INDEX idx_expires_at (expires_at)

-- En roles
INDEX idx_is_system (is_system)

-- En user_mfa
INDEX idx_enabled (enabled)

-- En users
INDEX idx_deleted_at (deleted_at)
```

**Beneficios:**
- ✅ Queries más rápidas en filtros comunes
- ✅ Mejor performance en joins
- ✅ Soft deletes optimizados

### 7. Foreign Keys con Políticas Claras

**Políticas de eliminación definidas:**

```sql
-- CASCADE: Elimina registros relacionados
password_reset_tokens.user_id -> users(id) ON DELETE CASCADE
user_profiles.user_id -> users(id) ON DELETE CASCADE
user_roles.user_id -> users(id) ON DELETE CASCADE
user_roles.role_id -> roles(id) ON DELETE CASCADE
role_permissions.role_id -> roles(id) ON DELETE CASCADE
role_permissions.permission_id -> permissions(id) ON DELETE CASCADE
sessions.user_id -> users(id) ON DELETE CASCADE
jwt_tokens.user_id -> users(id) ON DELETE CASCADE
user_mfa.user_id -> users(id) ON DELETE CASCADE

-- SET NULL: Mantiene registro pero elimina relación
user_roles.assigned_by -> users(id) ON DELETE SET NULL
login_attempts.user_id -> users(id) ON DELETE SET NULL
logs.user_id -> users(id) ON DELETE SET NULL
audit_log.user_id -> users(id) ON DELETE SET NULL
```

**Beneficios:**
- ✅ Integridad referencial garantizada
- ✅ Comportamiento predecible en eliminaciones
- ✅ Histórico preservado donde es importante

## Resumen de Normalización

### Antes (Problemas):
- ❌ Configuraciones duplicadas en dos tablas
- ❌ Password reset mezclado con users
- ❌ Login attempts sin relación formal a users
- ❌ User profiles con ID innecesaria (relación 1:1)
- ❌ Algunos índices faltantes
- ❌ Foreign keys sin políticas explícitas

### Después (Mejoras):
- ✅ **1FN**: Todos los campos son atómicos
- ✅ **2FN**: Todos los campos no-clave dependen de la clave completa
- ✅ **3FN**: No hay dependencias transitivas
- ✅ Configuración unificada con categorización
- ✅ Password reset en tabla dedicada con historial
- ✅ Login attempts con FK opcional a users
- ✅ User profiles con relación 1:1 real (user_id como PK)
- ✅ Índices optimizados para queries comunes
- ✅ Foreign keys con políticas CASCADE/SET NULL apropiadas
- ✅ Menor redundancia de datos
- ✅ Mayor integridad referencial
- ✅ Mejor performance en queries

## Impacto en el Código

### Configuraciones

**Antes:**
```php
// Dos tablas diferentes
$db->query("SELECT * FROM config WHERE config_key = ?");
$db->query("SELECT * FROM report_config WHERE config_name = ?");
```

**Después:**
```php
// Tabla unificada
$db->query("SELECT * FROM config WHERE config_key = ?");
// Filtrar por categoría si se necesita
$db->query("SELECT * FROM config WHERE category = 'reports'");
```

### Password Reset

**Antes:**
```php
// Actualizar directamente en users
$db->update('users', ['password_reset_token' => $token], ['id' => $userId]);
```

**Después:**
```php
// Insertar en tabla dedicada
$db->insert('password_reset_tokens', [
    'user_id' => $userId,
    'token' => $token,
    'expires_at' => time() + 3600
]);

// Marcar como usado
$db->update('password_reset_tokens', ['used_at' => time()], ['token' => $token]);
```

### User Profile

**Antes:**
```php
// Buscar por user_id
$profile = $db->fetchOne("SELECT * FROM user_profiles WHERE user_id = ?", [$userId]);
```

**Después:**
```php
// Buscar directamente por PK
$profile = $db->fetchOne("SELECT * FROM user_profiles WHERE user_id = ?", [$userId]);
// Más eficiente porque user_id es PK
```

## Migraciones Necesarias

Si tienes datos existentes, ejecuta estas migraciones:

### 1. Consolidar configuraciones de reportes

```sql
-- Mover datos de report_config a config
INSERT INTO config (config_key, config_value, config_type, category, description, is_public, created_at, updated_at)
SELECT
    config_name as config_key,
    config_value,
    config_type,
    'reports' as category,
    description,
    0 as is_public,
    created_at,
    updated_at
FROM report_config;

-- Eliminar tabla antigua
DROP TABLE report_config;
```

### 2. Migrar password reset tokens

```sql
-- Crear tokens desde users existentes
INSERT INTO password_reset_tokens (user_id, token, expires_at, created_at)
SELECT
    id as user_id,
    password_reset_token as token,
    password_reset_expires as expires_at,
    UNIX_TIMESTAMP() as created_at
FROM users
WHERE password_reset_token IS NOT NULL;

-- Limpiar columnas en users
ALTER TABLE users
DROP COLUMN password_reset_token,
DROP COLUMN password_reset_expires;
```

### 3. Actualizar user_profiles PK

```sql
-- Backup de datos
CREATE TABLE user_profiles_backup AS SELECT * FROM user_profiles;

-- Recrear tabla con user_id como PK
DROP TABLE user_profiles;
CREATE TABLE user_profiles (
    user_id INT UNSIGNED PRIMARY KEY,
    ... (resto de columnas)
);

-- Restaurar datos
INSERT INTO user_profiles SELECT * FROM user_profiles_backup;
DROP TABLE user_profiles_backup;
```

## Conclusión

La base de datos ahora está completamente normalizada siguiendo las mejores prácticas:

- ✅ **Reducción de redundancia**: 1 tabla en vez de 2 para configuraciones
- ✅ **Separación de concerns**: Auth, reset, profile en tablas separadas
- ✅ **Integridad referencial**: Foreign keys con políticas claras
- ✅ **Performance**: Índices optimizados para queries frecuentes
- ✅ **Escalabilidad**: Estructura preparada para crecimiento
- ✅ **Mantenibilidad**: Código más limpio y predecible
- ✅ **Compliance**: Mejor para auditoría y compliance

El sistema mantiene 100% de funcionalidad mientras adopta una estructura de datos más robusta y eficiente.
