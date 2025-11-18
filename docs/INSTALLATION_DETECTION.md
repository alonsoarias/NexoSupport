# Sistema de Detección de Instalación y Actualización

## Descripción General

NexoSupport implementa un sistema de detección de instalación y actualización siguiendo **exactamente el patrón de Moodle**, sin usar archivos de marcado como `.installed`.

## Arquitectura

### Componente Principal: `environment_checker`

**Ubicación:** `lib/classes/install/environment_checker.php`

Esta clase es responsable de verificar el estado completo del sistema y determinar:

1. ¿Está instalado el sistema?
2. ¿Necesita actualización?
3. ¿Hay errores de configuración?

### Flujo de Verificación (Patrón Moodle)

```
1. ¿Existe config.php (.env)?
   ├─ NO → Redirigir a /install
   └─ SÍ → Continuar

2. ¿Configuración válida?
   ├─ NO → Redirigir a /install
   └─ SÍ → Continuar

3. ¿Conecta a Base de Datos?
   ├─ NO → Redirigir a /install
   └─ SÍ → Continuar

4. ¿Existe tabla {prefix}config?
   ├─ NO → Redirigir a /install (instalación incompleta)
   └─ SÍ → Continuar

5. ¿Existe registro 'version' en config?
   ├─ NO → Redirigir a /install (instalación incompleta)
   └─ SÍ → Continuar

6. ¿version_bd < version_código?
   ├─ SÍ → Redirigir a /admin/upgrade.php
   └─ NO → Sistema OK, continuar
```

## Uso

### 1. En el Front Controller (`public_html/index.php`)

```php
// Cargar autoloader
require_once(BASE_DIR . '/vendor/autoload.php');

// Usar environment_checker para determinar estado del sistema
$envChecker = new \core\install\environment_checker();

// ¿Necesita instalación?
if ($envChecker->needs_install()) {
    // Redirigir a instalador
    header('Location: /install');
    exit;
}

// Sistema instalado, cargar normalmente
require_once(BASE_DIR . '/lib/setup.php');
```

### 2. En Setup (`lib/setup.php`)

```php
// Verificar si necesita actualización
if ($CFG->installed && $DB !== null) {
    require_once(__DIR__ . '/upgrade.php');

    if (core_upgrade_required()) {
        // Marcar que hay upgrade pendiente
        $CFG->upgrade_pending = true;

        // OPCIONAL: Redirección automática (como Moodle)
        // header('Location: /admin/upgrade.php');
        // exit;
    }
}
```

### 3. Script de Diagnóstico

Ejecutar `php check_environment.php` para ver el estado completo:

```bash
$ php check_environment.php

=== NexoSupport Environment Checker ===

Estado del Sistema:
--------------------------------------------------
¿Instalado?: ✓ SÍ
¿Necesita instalación?: ✗ NO
¿Necesita actualización?: ✓ SÍ

Versiones:
--------------------------------------------------
Versión en BD: 2025011806
Versión en código: 2025011807
Release: 1.1.7

Estado Detallado:
--------------------------------------------------
  config_exists: true
  config_valid: true
  db_connected: true
  tables_exist: true
  db_version: 2025011806
  code_version: 2025011807
  needs_upgrade: true
```

## Métodos de `environment_checker`

### Métodos Principales

- `is_installed(): bool` - ¿Está completamente instalado?
- `needs_install(): bool` - ¿Necesita instalación?
- `needs_upgrade(): bool` - ¿Necesita actualización?

### Métodos de Estado

- `get_state(): array` - Estado completo (para debugging)
- `get_db_version(): ?int` - Versión instalada en BD
- `get_code_version(): ?int` - Versión del código (lib/version.php)
- `get_release(): string` - Release string (ej: "1.1.7")

### Métodos de Error

- `has_errors(): bool` - ¿Hay errores?
- `get_errors(): array` - Lista de mensajes de error

### Métodos de Configuración

- `get_db_config(): array` - Configuración de BD parseada desde .env

## Comparación con Archivo `.installed`

### ❌ Método Antiguo (Archivo `.installed`)

```php
// PROBLEMA 1: Archivo puede borrarse accidentalmente
if (!file_exists(BASE_DIR . '/.installed')) {
    header('Location: /install');
    exit;
}

// PROBLEMA 2: No detecta instalaciones incompletas
// Si .installed existe pero la BD está corrupta, el sistema falla

// PROBLEMA 3: No detecta necesidad de upgrade
// Archivo solo dice "instalado" o "no instalado"
```

### ✅ Método Nuevo (Patrón Moodle)

```php
// VENTAJA 1: Verifica estado real del sistema
$checker = new \core\install\environment_checker();

// VENTAJA 2: Detecta instalaciones incompletas
// Si .env existe pero no hay tablas → needs_install() = true

// VENTAJA 3: Detecta necesidad de upgrade
// Si version_bd < version_código → needs_upgrade() = true

// VENTAJA 4: Diagnóstico completo
// Puede ver exactamente qué está mal: config, BD, tablas, versión
```

## Flujos de Usuario

### Flujo 1: Instalación Limpia

```
Usuario accede a http://localhost
    ↓
Front controller verifica estado
    ↓
environment_checker detecta: .env no existe
    ↓
Redirige a /install
    ↓
Usuario completa instalador
    ↓
Instalador crea:
  - .env
  - Tablas en BD
  - Registro 'version' en config
    ↓
Usuario redirigido a /login
    ↓
Sistema funciona normalmente
```

### Flujo 2: Instalación Interrumpida

```
Usuario interrumpe instalador (cierra browser)
    ↓
.env creado pero BD no tiene tablas
    ↓
Usuario intenta acceder nuevamente
    ↓
environment_checker detecta:
  - .env existe ✓
  - BD conecta ✓
  - Tabla config NO existe ✗
    ↓
needs_install() = true
    ↓
Redirige a /install
    ↓
Instalador detecta que BD está parcialmente creada
    ↓
Permite continuar o reiniciar
```

### Flujo 3: Actualización del Sistema

```
Administrador sube nueva versión del código
    ↓
lib/version.php ahora tiene version = 2025011807
    ↓
Administrador inicia sesión
    ↓
setup.php verifica versión:
  - version_bd = 2025011806
  - version_código = 2025011807
  - core_upgrade_required() = true
    ↓
$CFG->upgrade_pending = true
    ↓
Dashboard muestra notificación:
  "⚠ Actualización disponible. Ir a /admin/upgrade.php"
    ↓
Administrador hace clic
    ↓
/admin/upgrade.php ejecuta xmldb_core_upgrade()
    ↓
Actualización completada
    ↓
version_bd ahora = 2025011807
    ↓
Sistema actualizado
```

## Seguridad

### 1. Solo Siteadmins Ven Upgrades

```php
// lib/setup.php (línea ~193)
$is_admin = $has_logged_user && is_siteadmin($USER->id);

if (!$skip_upgrade_check && $is_admin) {
    if (core_upgrade_required()) {
        $CFG->upgrade_pending = true;
    }
}
```

**Razón:** Usuarios normales no deben ver páginas de upgrade.

### 2. Upgrade Requiere Permisos

```php
// admin/upgrade.php (línea ~27)
if (!is_siteadmin($USER->id)) {
    print_error('nopermissions', 'core');
}
```

**Razón:** Solo administradores pueden ejecutar upgrades.

### 3. No Redirección Automática en Login

```php
// lib/setup.php (línea ~189)
// Solo verificar upgrades si hay usuario logueado
$has_logged_user = isset($USER->id) && $USER->id > 0;
```

**Razón:** Evita redirecciones a upgrade.php cuando el usuario no está logueado.

## Ventajas del Nuevo Sistema

### 1. **Robusto**
- Verifica múltiples puntos: config, BD, tablas, versión
- Detecta problemas específicos

### 2. **Estándar**
- Sigue el patrón probado de Moodle
- Familiar para desarrolladores Moodle

### 3. **Mantenible**
- Lógica centralizada en `environment_checker`
- No código duplicado

### 4. **Diagnóstico**
- Script `check_environment.php` para debugging
- Mensajes de error específicos

### 5. **Seguro**
- No archivos de marcado que pueden borrarse
- Verifica estado real del sistema

## Testing

### Probar Instalación

```bash
# 1. Borrar .env (simular sistema no instalado)
rm .env

# 2. Verificar estado
php check_environment.php
# Debe mostrar: needs_install = true

# 3. Acceder vía navegador
curl http://localhost
# Debe redirigir a /install
```

### Probar Upgrade

```bash
# 1. Editar lib/version.php y aumentar versión
$plugin->version = 2025011808;

# 2. Verificar estado
php check_environment.php
# Debe mostrar: needs_upgrade = true

# 3. Iniciar sesión como admin y verificar que aparece notificación
```

### Probar Instalación Interrumpida

```bash
# 1. Crear .env manualmente
cp .env.example .env

# 2. NO crear tablas en BD

# 3. Verificar estado
php check_environment.php
# Debe mostrar: needs_install = true (BD no tiene tablas)
```

## Troubleshooting

### Problema: Sistema dice "no instalado" pero sí está instalado

**Diagnóstico:**
```bash
php check_environment.php
```

**Posibles causas:**
1. No existe `.env` → Crear desde `.env.example`
2. BD no conecta → Verificar credenciales en `.env`
3. Tabla `config` no existe → Completar instalación
4. Registro `version` no existe en `config` → Correr upgrade

### Problema: Upgrade no detecta nueva versión

**Verificar:**
```php
// lib/version.php
$plugin->version = 2025011807; // ¿Está actualizado?

// BD (tabla config)
SELECT value FROM nxs_config WHERE name = 'version';
// ¿Es menor que version.php?
```

### Problema: Redirección infinita a /install

**Verificar:**
```bash
# ¿Existe .env?
ls -la .env

# ¿BD conecta?
mysql -u root -p < .env DB_DATABASE

# ¿Existe tabla config?
mysql -u root -p DB_DATABASE -e "SHOW TABLES LIKE 'nxs_config';"
```

## Conclusión

El nuevo sistema de detección de instalación y actualización:

✅ Sigue el patrón probado de Moodle
✅ No depende de archivos de marcado
✅ Detecta instalaciones incompletas
✅ Detecta necesidad de upgrades
✅ Centraliza la lógica en una clase
✅ Proporciona herramientas de diagnóstico
✅ Es seguro y robusto

**Sin usar archivo `.installed`, solo verificando el estado real del sistema.**
