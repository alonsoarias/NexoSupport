# ESPECIFICACIÓN: NORMALIZACIÓN DB A 3FN

**Fecha**: 2025-11-12  
**Proyecto**: NexoSupport - Fase 6

---

## 1. OBJETIVO

Normalizar estrictamente la base de datos a **Tercera Forma Normal (3FN)** eliminando todas las dependencias transitivas detectadas en el análisis.

## 2. VIOLACIONES DETECTADAS

###2.1 Tabla `users` (VIOLACIÓN MODERADA)

**Campos problemáticos**:
- `last_login_at`, `last_login_ip` → Dependencia transitiva
- `failed_login_attempts`, `locked_until` → Estados derivados

**Problema**: Estos campos deberían estar en tablas separadas.

### 2.2 Tabla `user_profiles` (VIOLACIÓN LEVE)

**Campos problemáticos**:
- `timezone`, `locale` → Son preferencias, no datos de perfil

**Problema**: Mezcla datos de perfil con preferencias del usuario.

## 3. DISEÑO NORMALIZADO

### 3.1 Nuevas Tablas

**Tabla: login_history** (NUEVA):
```
- id (PK)
- user_id (FK → users.id)
- ip_address
- user_agent
- login_at
- logout_at
- session_id (FK → sessions.id)
```

**Tabla: account_security** (NUEVA):
```
- id (PK)
- user_id (UNIQUE FK → users.id)
- failed_login_attempts
- locked_until
- last_failed_attempt_at
- updated_at
```

**Tabla: user_preferences** (NUEVA):
```
- id (PK)
- user_id (FK → users.id)
- preference_key
- preference_value
- preference_type (string, int, bool, json)
- updated_at
UNIQUE (user_id, preference_key)
```

### 3.2 Tablas Modificadas

**Tabla `users` (LIMPIA)**:
```
REMOVER columnas:
- last_login_at
- last_login_ip
- failed_login_attempts
- locked_until

MANTENER:
- id, username, email, password
- status, created_at, updated_at, deleted_at
```

**Tabla `user_profiles` (LIMPIA)**:
```
REMOVER columnas:
- timezone
- locale

MANTENER:
- id, user_id, first_name, last_name
- bio, avatar, created_at, updated_at
```

## 4. PLAN DE MIGRACIÓN

### Fase 1: Crear Tablas Nuevas
- CREATE TABLE login_history
- CREATE TABLE account_security  
- CREATE TABLE user_preferences

### Fase 2: Migrar Datos
```
-- Migrar último login a login_history
INSERT INTO login_history (user_id, ip_address, login_at)
SELECT id, last_login_ip, last_login_at FROM users WHERE last_login_at IS NOT NULL;

-- Migrar estado de seguridad
INSERT INTO account_security (user_id, failed_login_attempts, locked_until)
SELECT id, failed_login_attempts, locked_until FROM users;

-- Migrar preferencias
INSERT INTO user_preferences (user_id, preference_key, preference_value)
SELECT user_id, 'timezone', timezone FROM user_profiles WHERE timezone IS NOT NULL
UNION ALL
SELECT user_id, 'locale', locale FROM user_profiles WHERE locale IS NOT NULL;
```

### Fase 3: Actualizar Código
- **UserManager**: Consultar `account_security` para estados
- **AuthController**: Insertar en `login_history` en cada login
- **PreferencesManager** (NUEVO): Gestionar `user_preferences`

### Fase 4: Limpiar Tablas Originales
- ALTER TABLE users DROP COLUMN last_login_at, last_login_ip, failed_login_attempts, locked_until
- ALTER TABLE user_profiles DROP COLUMN timezone, locale

## 5. IMPACTO

**Ventajas**:
- ✅ 3FN estricta (sin dependencias transitivas)
- ✅ Historial completo de logins (no solo el último)
- ✅ Preferencias extensibles sin ALTER TABLE
- ✅ Mejor performance (tabla users más ligera)

**Costos**:
- ⚠️ 3 tablas adicionales (+21%)
- ⚠️ Queries más complejas (requieren JOINs)
- ⚠️ Migración requiere downtime (1-2 horas estimadas)

## 6. CRITERIOS DE ÉXITO

✅ Verificar:
1. Todas las tablas cumplen 1FN, 2FN, 3FN
2. No hay dependencias transitivas
3. Login functionality sigue funcionando igual
4. Datos migrados correctamente
5. Performance queries igual o mejor

---

**FIN ESPECIFICACIÓN FASE 6**
