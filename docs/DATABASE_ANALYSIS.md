# ANÁLISIS DE BASE DE DATOS - NEXOSUPPORT

**Fecha**: 2025-11-12
**Versión**: 1.0
**Proyecto**: NexoSupport Authentication System
**Schema**: `/database/schema/schema.xml`

---

## 1. OVERVIEW DEL SCHEMA

### 1.1 Información General

- **Total de Tablas**: 14
- **Drivers Soportados**: MySQL, PostgreSQL, SQLite
- **Charset**: utf8mb4
- **Collation**: utf8mb4_unicode_ci
- **Engine (MySQL)**: InnoDB
- **Formato de Timestamps**: Unix timestamps (INTEGER)

### 1.2 Listado de Tablas

| # | Tabla | Propósito | Tipo de Datos |
|---|-------|-----------|---------------|
| 1 | `config` | Configuración del sistema (key-value) | Transaccional |
| 2 | `users` | Usuarios del sistema | Master Data |
| 3 | `password_reset_tokens` | Tokens de recuperación de contraseña | Transaccional |
| 4 | `login_attempts` | Historial de intentos de login | Auditoría |
| 5 | `user_profiles` | Perfiles extendidos de usuarios (1:1) | Master Data |
| 6 | `roles` | Roles del sistema RBAC | Master Data |
| 7 | `permissions` | Permisos granulares (35 permisos) | Master Data |
| 8 | `user_roles` | Relación N:M usuarios-roles | Relacional |
| 9 | `role_permissions` | Relación N:M roles-permisos | Relacional |
| 10 | `sessions` | Sesiones activas de usuarios | Transaccional |
| 11 | `jwt_tokens` | Tokens JWT (preparado para blacklist) | Transaccional |
| 12 | `user_mfa` | Configuración MFA por usuario | Master Data |
| 13 | `logs` | Logs del sistema | Auditoría |
| 14 | `audit_log` | Auditoría de acciones críticas | Auditoría |

---

## 2. DIAGRAMA ENTIDAD-RELACIÓN

### 2.1 Diagrama ER Simplificado

```
┌──────────────┐
│    users     │
│──────────────│
│ PK id        │
│    username  │
│    email     │
│    password  │───────┐
│    status    │       │
│    ...       │       │
└──────────────┘       │
       │               │
       │ 1:1           │ 1:N
       ▼               ▼
┌────────────────┐  ┌─────────────────────┐
│ user_profiles  │  │ password_reset_     │
│────────────────│  │      tokens         │
│ PK id          │  │─────────────────────│
│ FK user_id     │  │ PK id               │
│    first_name  │  │ FK user_id          │
│    last_name   │  │    token            │
│    ...         │  │    expires_at       │
└────────────────┘  └─────────────────────┘

┌──────────────┐       ┌──────────────┐       ┌───────────────┐
│    users     │       │  user_roles  │       │     roles     │
│──────────────│       │──────────────│       │───────────────│
│ PK id        │───┐   │ PK id        │   ┌───│ PK id         │
└──────────────┘   │   │ FK user_id   │───│   │    name       │
                   └───│ FK role_id   │───┘   │    shortname  │
                   N:M └──────────────┘   1:N │    ...        │
                                               └───────────────┘
                                                      │
                                                      │ 1:N
                                                      ▼
┌──────────────┐       ┌────────────────────┐   ┌─────────────────┐
│ permissions  │       │ role_permissions   │   │     roles       │
│──────────────│       │────────────────────│   │─────────────────│
│ PK id        │───┐   │ PK id              │───│ PK id           │
│    name      │   │   │ FK role_id         │   └─────────────────┘
│    module    │   └───│ FK permission_id   │
│    ...       │   N:M │    permission      │
└──────────────┘       └────────────────────┘

┌──────────────┐       ┌──────────────────┐
│    users     │       │  login_attempts  │
│──────────────│       │──────────────────│
│ PK id        │───┐   │ PK id            │
└──────────────┘   │   │    username      │
                   └───│    ip_address    │
                   1:N │    success       │
                       │    attempted_at  │
                       └──────────────────┘

┌──────────────┐       ┌──────────────┐
│    users     │       │  user_mfa    │
│──────────────│       │──────────────│
│ PK id        │───┐   │ PK id        │
└──────────────┘   │   │ FK user_id   │
                   └───│    factor    │
                   1:N │    secret    │
                       │    enabled   │
                       └──────────────┘
```

---

## 3. ANÁLISIS DE NORMALIZACIÓN

### 3.1 Primera Forma Normal (1FN)

**Definición**: Una tabla está en 1FN si:
1. Todos los valores de atributo son atómicos (indivisibles)
2. No hay grupos repetitivos
3. Existe una clave primaria

#### Verificación tabla por tabla

**✅ CUMPLE 1FN:**

| Tabla | Verificación |
|-------|--------------|
| `config` | ✅ Todos los campos atómicos (name, value) |
| `users` | ✅ Campos atómicos, PK `id` |
| `password_reset_tokens` | ✅ Campos atómicos, PK `id` |
| `login_attempts` | ✅ Campos atómicos, PK `id` |
| `user_profiles` | ✅ Campos atómicos, PK `id` |
| `roles` | ✅ Campos atómicos, PK `id` |
| `permissions` | ✅ Campos atómicos, PK `id` |
| `user_roles` | ✅ Campos atómicos, PK `id` |
| `role_permissions` | ✅ Campos atómicos, PK `id` |
| `sessions` | ✅ Campos atómicos, PK `id` |
| `jwt_tokens` | ✅ Campos atómicos, PK `id` |
| `user_mfa` | ✅ Campos atómicos, PK `id` |
| `logs` | ✅ Campos atómicos, PK `id` |
| `audit_log` | ✅ Campos atómicos, PK `id` |

**Conclusión**: ✅ Todas las tablas cumplen 1FN.

---

### 3.2 Segunda Forma Normal (2FN)

**Definición**: Una tabla está en 2FN si:
1. Está en 1FN
2. Todos los atributos no-clave dependen completamente de la clave primaria (no hay dependencias parciales)

**Nota**: Solo aplica a tablas con claves primarias compuestas. En este schema, TODAS las tablas usan PK simple (`id`), por lo que no pueden existir dependencias parciales.

#### Verificación

**✅ CUMPLE 2FN:**
- Todas las tablas tienen PK simple (`id`)
- No existen dependencias parciales por definición
- Atributos no-clave dependen completamente de `id`

**Conclusión**: ✅ Todas las tablas cumplen 2FN.

---

### 3.3 Tercera Forma Normal (3FN)

**Definición**: Una tabla está en 3FN si:
1. Está en 2FN
2. No existen dependencias transitivas (atributo no-clave → atributo no-clave)

#### Análisis tabla por tabla

**✅ CUMPLE 3FN:**

1. **`config`**
   - ✅ Key-value pura, sin dependencias transitivas

2. **`password_reset_tokens`**
   - ✅ Todos los atributos dependen directamente de `id`, no de `user_id`

3. **`login_attempts`**
   - ✅ Historial puro, sin dependencias transitivas

4. **`user_profiles`**
   - ⚠️ **PARCIALMENTE CUMPLE** (ver análisis detallado abajo)

5. **`roles`**
   - ✅ Atributos descriptivos del rol, sin dependencias transitivas

6. **`permissions`**
   - ✅ Atributos descriptivos del permiso, sin dependencias transitivas

7. **`user_roles`**
   - ✅ Tabla relacional pura

8. **`role_permissions`**
   - ✅ Tabla relacional pura

9. **`sessions`**
   - ✅ Datos de sesión directamente dependientes de `id`

10. **`jwt_tokens`**
    - ✅ Datos del token directamente dependientes de `id`

11. **`user_mfa`**
    - ✅ Configuración MFA directamente dependiente de `id`

12. **`logs`**
    - ✅ Log entries independientes

13. **`audit_log`**
    - ✅ Audit entries independientes

#### ⚠️ TABLA `users` - Violaciones detectadas

**Estructura actual**:
```sql
users (
    id,
    username,
    email,
    password,
    status,                        -- OK
    last_login_at,                 -- ⚠️ VIOLACIÓN
    last_login_ip,                 -- ⚠️ VIOLACIÓN
    failed_login_attempts,         -- ⚠️ VIOLACIÓN
    locked_until,                  -- ⚠️ VIOLACIÓN
    created_at,
    updated_at,
    deleted_at
)
```

**Dependencias transitivas identificadas**:

1. **Login tracking** (`last_login_at`, `last_login_ip`)
   - `id` → `last_login_at` → `last_login_ip`
   - **Problema**: `last_login_ip` depende transitivamente de `last_login_at` (es el IP del último login)
   - **Solución**: Crear tabla `login_history` separada

2. **Account security state** (`failed_login_attempts`, `locked_until`)
   - Estos campos representan un **estado derivado** basado en login_attempts
   - **Problema**: `failed_login_attempts` se calcula contando `login_attempts` donde `success=0`
   - **Problema**: `locked_until` se deriva de `failed_login_attempts >= 5`
   - **Solución**: Mover a tabla `account_security` o calcular dinámicamente

**Impacto**: ⚠️ Moderado
- Los datos se pueden calcular/recalcular desde `login_attempts`
- No causa redundancia crítica pero viola 3FN estrictamente

#### ⚠️ TABLA `user_profiles` - Violaciones menores

**Estructura actual**:
```sql
user_profiles (
    id,
    user_id,
    first_name,
    last_name,
    bio,
    avatar,
    timezone,                      -- ⚠️ PUEDE SER VIOLACIÓN
    locale,                        -- ⚠️ PUEDE SER VIOLACIÓN
    created_at,
    updated_at
)
```

**Posibles dependencias**:
- `timezone` y `locale` podrían considerarse **preferencias** en lugar de **datos de perfil**
- Si las preferencias crecen (theme, notifications, etc.), viola SRP (Single Responsibility)
- **Recomendación**: Crear tabla `user_preferences` (key-value)

**Impacto**: ⚠️ Leve
- No es una violación estricta de 3FN
- Más un issue de diseño semántico

---

### 3.4 Resumen de Normalización

| Forma Normal | Estado | Tablas Afectadas |
|--------------|--------|------------------|
| **1FN** | ✅ CUMPLE | Todas (14/14) |
| **2FN** | ✅ CUMPLE | Todas (14/14) |
| **3FN** | ⚠️ PARCIAL | `users` (violación moderada), `user_profiles` (violación leve) |

---

## 4. PROPUESTA DE NORMALIZACIÓN ESTRICTA A 3FN

### 4.1 Refactorización de Tabla `users`

#### Antes (Actual)

```sql
users (
    id,
    username,
    email,
    password,
    status,
    last_login_at,            -- ⚠️ MOVER
    last_login_ip,            -- ⚠️ MOVER
    failed_login_attempts,    -- ⚠️ MOVER
    locked_until,             -- ⚠️ MOVER
    created_at,
    updated_at,
    deleted_at
)
```

#### Después (Normalizado 3FN)

**Tabla `users` (limpia)**:
```sql
users (
    id INTEGER PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at INTEGER NOT NULL,
    updated_at INTEGER NOT NULL,
    deleted_at INTEGER NULL
)
```

**Nueva tabla `login_history`**:
```sql
login_history (
    id INTEGER PRIMARY KEY,
    user_id INTEGER NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    login_at INTEGER NOT NULL,
    logout_at INTEGER NULL,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_login (user_id, login_at DESC)
)
```

**Nueva tabla `account_security`**:
```sql
account_security (
    id INTEGER PRIMARY KEY,
    user_id INTEGER UNIQUE NOT NULL,
    failed_login_attempts INTEGER DEFAULT 0,
    locked_until INTEGER NULL,
    last_failed_attempt INTEGER NULL,
    updated_at INTEGER NOT NULL,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)
```

**Beneficios**:
1. ✅ **3FN estricta**: Sin dependencias transitivas
2. ✅ **Historial completo**: No se pierde información de logins anteriores
3. ✅ **Escalabilidad**: `login_history` puede crecer sin afectar `users`
4. ✅ **Auditoría**: Acceso al historial completo de sesiones
5. ✅ **Performance**: `users` más ligera, queries más rápidas

---

### 4.2 Refactorización de Tabla `user_profiles`

#### Antes (Actual)

```sql
user_profiles (
    id,
    user_id,
    first_name,
    last_name,
    bio,
    avatar,
    timezone,                  -- ⚠️ PREFERENCIA
    locale,                    -- ⚠️ PREFERENCIA
    created_at,
    updated_at
)
```

#### Después (Normalizado 3FN)

**Tabla `user_profiles` (solo datos de perfil)**:
```sql
user_profiles (
    id INTEGER PRIMARY KEY,
    user_id INTEGER UNIQUE NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    bio TEXT NULL,
    avatar VARCHAR(255) NULL,
    date_of_birth DATE NULL,
    phone VARCHAR(20) NULL,
    created_at INTEGER NOT NULL,
    updated_at INTEGER NOT NULL,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)
```

**Nueva tabla `user_preferences`**:
```sql
user_preferences (
    id INTEGER PRIMARY KEY,
    user_id INTEGER NOT NULL,
    preference_key VARCHAR(50) NOT NULL,
    preference_value TEXT NOT NULL,
    preference_type ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
    updated_at INTEGER NOT NULL,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uk_user_pref (user_id, preference_key),
    INDEX idx_user_key (user_id, preference_key)
)
```

**Datos migrados a `user_preferences`**:
```sql
INSERT INTO user_preferences (user_id, preference_key, preference_value, preference_type)
VALUES
    (1, 'timezone', 'America/Argentina/Buenos_Aires', 'string'),
    (1, 'locale', 'es', 'string'),
    (1, 'theme_mode', 'dark', 'string'),
    (1, 'sidebar_collapsed', 'true', 'boolean'),
    (1, 'items_per_page', '50', 'integer');
```

**Beneficios**:
1. ✅ **Extensibilidad**: Agregar nuevas preferencias sin ALTER TABLE
2. ✅ **Flexibilidad**: Preferencias personalizadas por usuario
3. ✅ **3FN estricta**: Preferencias no dependen transitivamente

---

### 4.3 Schema Normalizado Completo a 3FN

**Resumen de cambios**:

| Tabla Original | Acción | Nuevas Tablas |
|----------------|--------|---------------|
| `users` | Refactorizar | `users` (limpia) + `login_history` + `account_security` |
| `user_profiles` | Refactorizar | `user_profiles` (solo perfil) + `user_preferences` |
| `login_attempts` | Mantener | - (ya normalizada) |
| Resto | Mantener | - (ya normalizadas) |

**Total de tablas después de normalización**: 17 tablas (+3)

---

## 5. ÍNDICES Y OPTIMIZACIÓN

### 5.1 Índices Actuales

**Tabla `users`**:
```sql
PRIMARY KEY (id)
UNIQUE INDEX uk_username (username)
UNIQUE INDEX uk_email (email)
INDEX idx_status (status)
INDEX idx_deleted (deleted_at)
```

**Tabla `user_roles`**:
```sql
PRIMARY KEY (id)
INDEX idx_user_id (user_id)
INDEX idx_role_id (role_id)
UNIQUE KEY uk_user_role (user_id, role_id)
```

**Tabla `role_permissions`**:
```sql
PRIMARY KEY (id)
INDEX idx_role_id (role_id)
INDEX idx_permission_id (permission_id)
UNIQUE KEY uk_role_permission (role_id, permission_id)
```

**Tabla `sessions`**:
```sql
PRIMARY KEY (id)
UNIQUE INDEX uk_session_id (session_id)
INDEX idx_user_id (user_id)
INDEX idx_expires (expires_at)
```

### 5.2 Índices Recomendados Adicionales

**Para `login_history` (nueva tabla)**:
```sql
INDEX idx_user_login (user_id, login_at DESC)  -- Para obtener último login
INDEX idx_ip_address (ip_address)              -- Para detectar ataques por IP
INDEX idx_login_at (login_at)                  -- Para queries de rango temporal
```

**Para `account_security` (nueva tabla)**:
```sql
INDEX idx_locked (locked_until)                -- Para queries de cuentas bloqueadas
```

**Para `user_preferences` (nueva tabla)**:
```sql
UNIQUE KEY uk_user_pref (user_id, preference_key)
INDEX idx_user_key (user_id, preference_key)   -- Para búsqueda rápida
```

**Para `jwt_tokens`**:
```sql
INDEX idx_token (token(255))                   -- Para blacklist lookup
INDEX idx_expires (expires_at)                 -- Para limpieza de tokens expirados
INDEX idx_user_type (user_id, token_type)      -- Para queries de tokens por usuario
```

**Para `audit_log`**:
```sql
INDEX idx_user_action (user_id, action)        -- Para auditoría por usuario
INDEX idx_created_at (created_at DESC)         -- Para queries temporales
INDEX idx_entity (entity_type, entity_id)      -- Para auditoría por entidad
```

---

## 6. FOREIGN KEYS Y RELACIONES

### 6.1 Foreign Keys Definidas

**Tabla `user_roles`**:
```sql
FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
```

**Tabla `role_permissions`**:
```sql
FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
```

**Tabla `user_profiles`**:
```sql
FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
```

**Tabla `password_reset_tokens`**:
```sql
FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
```

**Tabla `sessions`**:
```sql
FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
```

**Tabla `jwt_tokens`**:
```sql
FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
```

**Tabla `user_mfa`**:
```sql
FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
```

### 6.2 Estrategia de Borrado

**`ON DELETE CASCADE`**: Usado en todas las relaciones `user_id`
- ✅ Al eliminar usuario, se eliminan automáticamente sus roles, tokens, sesiones, etc.
- ✅ Mantiene integridad referencial
- ⚠️ Riesgo: Borrado accidental elimina todo (mitigado con soft delete)

**Recomendación**: Mantener `ON DELETE CASCADE` pero usar **soft delete** en `users` (campo `deleted_at`).

---

## 7. DATOS INICIALES (SEEDS)

### 7.1 Roles del Sistema

```sql
INSERT INTO roles (id, name, shortname, description) VALUES
(1, 'Administrador', 'admin', 'Acceso completo al sistema'),
(2, 'Usuario', 'user', 'Usuario estándar del sistema'),
(3, 'Invitado', 'guest', 'Acceso limitado de solo lectura');
```

### 7.2 Permisos (35 permisos en 9 módulos)

**Usuarios (7 permisos)**:
- `users.view`, `users.create`, `users.update`, `users.delete`
- `users.restore`, `users.assign_roles`, `users.view_profile`

**Roles (5 permisos)**:
- `roles.view`, `roles.create`, `roles.update`, `roles.delete`, `roles.assign_permissions`

**Permisos (4 permisos)**:
- `permissions.view`, `permissions.create`, `permissions.update`, `permissions.delete`

**Dashboard (3 permisos)**:
- `dashboard.view`, `dashboard.stats`, `dashboard.charts`

**Settings (3 permisos)**:
- `settings.view`, `settings.update`, `settings.critical`

**Logs (3 permisos)**:
- `logs.view`, `logs.delete`, `logs.export`

**Audit (2 permisos)**:
- `audit.view`, `audit.export`

**Reports (3 permisos)**:
- `reports.view`, `reports.generate`, `reports.export`

**Sessions (2 permisos)**:
- `sessions.view`, `sessions.terminate`

**Otros (3 permisos)**:
- `system.config`, `system.plugins`, `system.maintenance`

### 7.3 Asignación Inicial

**Rol Admin recibe TODOS los permisos** (35 permisos)
**Rol User recibe permisos básicos**: `dashboard.view`, `users.view_profile`
**Rol Guest recibe permisos de solo lectura**: `dashboard.view`

---

## 8. ESTRATEGIA DE MIGRACIÓN

### 8.1 Plan de Migración a Schema Normalizado

**Fase 1: Crear Nuevas Tablas**
```sql
CREATE TABLE login_history (...);
CREATE TABLE account_security (...);
CREATE TABLE user_preferences (...);
```

**Fase 2: Migrar Datos de `users`**
```sql
-- Migrar último login a login_history
INSERT INTO login_history (user_id, ip_address, login_at)
SELECT id, last_login_ip, last_login_at
FROM users
WHERE last_login_at IS NOT NULL;

-- Migrar estado de seguridad a account_security
INSERT INTO account_security (user_id, failed_login_attempts, locked_until)
SELECT id, failed_login_attempts, locked_until
FROM users;
```

**Fase 3: Migrar Datos de `user_profiles`**
```sql
-- Migrar preferencias a user_preferences
INSERT INTO user_preferences (user_id, preference_key, preference_value)
SELECT user_id, 'timezone', timezone FROM user_profiles WHERE timezone IS NOT NULL
UNION ALL
SELECT user_id, 'locale', locale FROM user_profiles WHERE locale IS NOT NULL;
```

**Fase 4: Limpiar Tablas Originales**
```sql
ALTER TABLE users
DROP COLUMN last_login_at,
DROP COLUMN last_login_ip,
DROP COLUMN failed_login_attempts,
DROP COLUMN locked_until;

ALTER TABLE user_profiles
DROP COLUMN timezone,
DROP COLUMN locale;
```

**Fase 5: Actualizar Código**
- Actualizar `UserManager` para consultar `account_security` y `login_history`
- Actualizar `AuthController` para insertar en `login_history` en cada login
- Crear `PreferencesManager` para gestionar `user_preferences`

---

## 9. PERFORMANCE Y ESCALABILIDAD

### 9.1 Análisis de Queries Frecuentes

**Query 1: Login de usuario**
```sql
-- Actual
SELECT * FROM users WHERE username = ? AND deleted_at IS NULL;

-- Impacto: ✅ Optimizado (índice en username)
```

**Query 2: Verificar permisos de usuario**
```sql
-- Actual
SELECT p.name
FROM permissions p
JOIN role_permissions rp ON p.id = rp.permission_id
JOIN user_roles ur ON rp.role_id = ur.role_id
WHERE ur.user_id = ?;

-- Impacto: ✅ Optimizado (índices en FK)
```

**Query 3: Obtener último login de usuario**
```sql
-- Actual (schema actual)
SELECT last_login_at, last_login_ip FROM users WHERE id = ?;

-- Propuesto (schema normalizado)
SELECT login_at, ip_address FROM login_history
WHERE user_id = ?
ORDER BY login_at DESC
LIMIT 1;

-- Impacto: ⚠️ Ligeramente más lento (requiere JOIN)
-- Mitigación: INDEX idx_user_login (user_id, login_at DESC)
```

**Query 4: Obtener preferencias de usuario**
```sql
-- Propuesto (nuevo)
SELECT preference_key, preference_value
FROM user_preferences
WHERE user_id = ?;

-- Impacto: ✅ Optimizado con índice uk_user_pref
```

### 9.2 Estrategias de Optimización

**1. Cache de permisos**
- Cachear permisos en memoria (Redis) con TTL de 1h
- Invalidar cache al cambiar roles/permisos

**2. Particionamiento de tablas grandes**
- `login_history`: Particionar por rango temporal (mensual)
- `logs`: Particionar por rango temporal (mensual)
- `audit_log`: Particionar por rango temporal (mensual)

**3. Archiving de datos antiguos**
- Archivar `login_history` > 6 meses a tabla `login_history_archive`
- Archivar `logs` > 30 días a `logs_archive`

**4. Materializar vistas frecuentes**
- Vista materializada: `user_with_last_login` (refresh cada 5 min)
- Vista materializada: `user_permission_matrix` (refresh al cambiar permisos)

---

## 10. CONCLUSIONES Y RECOMENDACIONES

### 10.1 Estado Actual

**Fortalezas** ✅:
1. Schema bien estructurado con 14 tablas
2. Cumple 1FN y 2FN completamente
3. RBAC granular con 35 permisos
4. Soft delete implementado
5. Índices en columnas críticas
6. Foreign keys para integridad referencial

**Debilidades** ⚠️:
1. Viola 3FN en tabla `users` (login tracking y security state)
2. Viola 3FN parcialmente en `user_profiles` (preferences)
3. `login_attempts` no está optimizada (sin índices suficientes)
4. Sin particionamiento de tablas de auditoría
5. Sin estrategia de archiving de datos históricos

### 10.2 Recomendaciones Prioritarias

**Prioridad ALTA** 🔴:
1. **Normalizar `users`**: Crear `login_history` y `account_security`
2. **Agregar índices faltantes**: En `login_attempts`, `jwt_tokens`, `audit_log`
3. **Implementar cache de permisos**: Redis con TTL de 1h

**Prioridad MEDIA** 🟡:
4. **Normalizar `user_profiles`**: Crear `user_preferences`
5. **Particionamiento**: Para `login_history`, `logs`, `audit_log`
6. **Archiving**: Jobs programados para archivar datos > 6 meses

**Prioridad BAJA** 🟢:
7. **Materializar vistas**: Para queries frecuentes
8. **Monitoring**: Slow query log y análisis de performance

### 10.3 Impacto de la Normalización

**Beneficios**:
- ✅ 3FN estricta sin dependencias transitivas
- ✅ Historial completo de logins (no solo el último)
- ✅ Preferencias extensibles sin ALTER TABLE
- ✅ Mejor performance en queries de `users` (tabla más ligera)
- ✅ Escalabilidad mejorada (tables de auditoría separadas)

**Costos**:
- ⚠️ 3 tablas adicionales (+21% más tablas)
- ⚠️ Queries ligeramente más complejas (JOINs adicionales)
- ⚠️ Migración de datos requiere downtime (estimado: 1-2 horas)

**Recomendación final**: **IMPLEMENTAR normalización a 3FN** para cumplir estrictamente con el objetivo del proyecto.

---

**Siguiente paso**: Revisar `FLOWS.md` para flujos funcionales completos y diseñar las especificaciones de refactorización.
