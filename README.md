# NexoSupport - Sistema de Gestión con Arquitectura Frankenstyle

**Versión:** 1.1.3 (User & Role Management)
**Fecha:** Enero 2025
**Licencia:** GNU GPL v3 or later

## Equipo de Desarrollo

- **Alonso Arias** - Desarrollador Principal y Arquitecto
  Email: soporteplataformas@iser.edu.co

- **Yulian Moreno** - Desarrollador
  Email: nexo.operativo@iser.edu.co

- **Mauricio Zafra** - Vicerrector Académico (Supervisor)
  Email: vicerrectoria@iser.edu.co

## Descripción

NexoSupport es un sistema de gestión moderno construido **desde cero** utilizando la **arquitectura Frankenstyle de Moodle**. El sistema está diseñado para ser:

- **Extensible**: Sistema de plugins con descubrimiento automático
- **Seguro**: RBAC completo, MFA, y validación estricta de inputs
- **Mantenible**: Código limpio siguiendo PSR-4 y mejores prácticas
- **Escalable**: Arquitectura modular que permite crecer según necesidades

## Estado Actual (v1.1.3)

### ✅ Funcionalidades Implementadas

**Gestión de Usuarios:**
- Crear, editar, eliminar usuarios (soft delete con anonimización)
- Suspender/reactivar cuentas
- Confirmar emails de usuarios
- Desbloquear cuentas
- Protección: No se puede eliminar/suspender administradores o a uno mismo

**Gestión de Roles:**
- Crear, editar, eliminar roles personalizados
- Mover roles arriba/abajo (reordenamiento)
- Asignar capabilities a roles
- Asignar roles a usuarios
- Protección: Roles del sistema no pueden eliminarse

**Sistema de Autenticación:**
- Plugin auth_manual implementado
- Validación de políticas de contraseñas
- Hooks de autenticación (pre/post login)
- Actualización de lastlogin y lastip

**Internacionalización (i18n):**
- Soporte completo multi-idioma
- 300+ strings en Español e Inglés
- Sistema string_manager siguiendo patrón Moodle
- Plugin i18n (auth_manual/lang/*/auth_manual.php)

**Templates Mustache:**
- 13+ templates core/admin migradas
- Separación completa lógica/presentación
- Sistema template_manager
- Soporte para helpers y partials

**Base de Datos:**
- Sistema XMLDB para gestión de schema
- DDL Manager para modificaciones
- Sistema de upgrade automático
- Transacciones y rollback

### 📋 Roadmap

**v1.2.0** - Operaciones Masivas
- Bulk delete, suspend, confirm
- Selección de usuarios
- Acciones en lote

**v1.3.0** - Autenticación Avanzada
- OAuth2 support
- Two-factor authentication
- Multiple auth plugins

**v1.4.0** - RBAC Avanzado
- Context-aware permissions
- Role inheritance
- Custom contexts

## Filosofía del Proyecto

### Principio Fundamental

**NexoSupport sigue el patrón exacto de Moodle:**

1. **Plugins NO son carpetas independientes** → Son **extensiones del core**
2. **Cada tipo de plugin tiene una clase base abstracta** en el core
3. **Los plugins DEBEN extender estas clases base** e implementar métodos obligatorios
4. **Descubrimiento automático** por namespace y convenciones
5. **Factory pattern** para instanciación dinámica

### Nomenclatura Frankenstyle

```
[tipo]_[nombre]

Ejemplos:
- auth_manual     → Plugin de autenticación manual
- tool_uploaduser → Herramienta para carga masiva de usuarios
- theme_iser      → Tema corporativo ISER
- report_log      → Reporte de logs del sistema
- factor_email    → Factor MFA por email
```

## Arquitectura

### Estructura de Directorios

```
nexosupport/
│
├── public_html/              # ⚠️ SOLO FRONT CONTROLLER
│   └── index.php             # ÚNICO archivo permitido
│
├── admin/                    # Panel administrativo
│   ├── tool/                 # Herramientas administrativas (plugins)
│   └── user/                 # Gestión de usuarios
│
├── auth/                     # Métodos de autenticación (plugins)
│   └── manual/               # auth_manual
│
├── theme/                    # Temas visuales (plugins)
│   └── core/                 # theme_core (base)
│
├── report/                   # Reportes (plugins)
│
├── lib/                      # Core del sistema
│   ├── classes/              # Clases core PSR-4
│   │   ├── plugininfo/       # ⭐ Clases base de plugins
│   │   ├── plugin/           # Sistema de gestión de plugins
│   │   ├── db/               # Sistema de base de datos
│   │   └── routing/          # Sistema de routing
│   │
│   ├── db/                   # Schema del core
│   │   └── install.xml
│   │
│   ├── lang/                 # Strings de idioma
│   │   ├── es/
│   │   └── en/
│   │
│   ├── components.json       # ⭐ Mapeo de tipos de plugins
│   ├── setup.php             # Inicialización del sistema
│   ├── functions.php         # Funciones globales
│   └── version.php           # Versión del core
│
├── install/                  # Instalador
│   └── stages/               # Etapas del instalador
│
├── var/                      # Datos variables
│   ├── cache/
│   ├── logs/
│   └── sessions/
│
├── .env                      # Configuración (no en git)
├── composer.json             # Dependencias y autoloading
└── README.md
```

## Sistema de Plugins

### Clases Base Abstractas

Cada tipo de plugin tiene una clase base en `lib/classes/plugininfo/`:

| Tipo     | Clase Base                    | Métodos Abstractos Clave                    |
|----------|-------------------------------|---------------------------------------------|
| `auth`   | `\core\plugininfo\auth`       | `authenticate()`, `can_change_password()`   |
| `tool`   | `\core\plugininfo\tool`       | `has_capabilities()`                        |
| `factor` | `\core\plugininfo\factor`     | `get_state()`, `verify()`                   |
| `theme`  | `\core\plugininfo\theme`      | `get_layouts()`, `get_scss()`               |
| `report` | `\core\plugininfo\report`     | `get_datasource()`, `get_columns()`         |

### Ejemplo: Plugin de Autenticación

```php
// lib/classes/plugininfo/auth.php (CORE)
namespace core\plugininfo;
abstract class auth extends base {
    abstract public function authenticate(string $username, string $password): bool|object;
    abstract public function can_change_password(): bool;
}

// auth/manual/classes/plugin.php (PLUGIN)
namespace auth_manual;
class plugin extends \core\plugininfo\auth {
    public function authenticate(string $username, string $password): bool|object {
        // Implementación específica
    }

    public function can_change_password(): bool {
        return true;
    }
}
```

### Descubrimiento Automático

El Plugin Manager (`\core\plugin\manager`) descubre plugins mediante:

1. **Escaneo de directorios** según `lib/components.json`
2. **Verificación de namespace** `[tipo]_[nombre]`
3. **Verificación de clase** `[tipo]_[nombre]\plugin`
4. **Verificación de herencia** `instanceof \core\plugininfo\[tipo]`

## Instalación

### Requisitos

- PHP >= 8.1
- MySQL 5.7+ o MariaDB 10.2+
- Apache 2.4+ (con mod_rewrite) o Nginx 1.18+
- Extensiones PHP:
  - PDO
  - pdo_mysql
  - json
  - mbstring

### Instalación Rápida

1. **Clonar el repositorio:**

```bash
git clone <repo-url> nexosupport
cd nexosupport
```

2. **Instalar dependencias:** ⚠️ **CRÍTICO**

```bash
composer install
```

> **Importante:** Este paso es OBLIGATORIO. Sin él, el sistema no funcionará.

3. **Configurar permisos:**

```bash
chmod -R 755 var/
```

4. **Configurar servidor web:**

**Apache:** El document root debe apuntar a `public_html/`. El archivo `.htaccess` está incluido.

**Nginx:** Ver configuración completa en `INSTALL.md`

5. **Acceder al instalador:**

Navegar a `http://localhost/install` y seguir el asistente.

### 📖 Guías de Instalación

- **[QUICKSTART.md](QUICKSTART.md)** - Inicio rápido en 5 pasos (⚠️ EMPIEZA AQUÍ)
- **[INSTALL.md](INSTALL.md)** - Guía completa de instalación y configuración
- **check.php** - Script de verificación de requisitos (`php check.php`)

## Uso del Sistema

### Iniciar sesión

```
URL: http://localhost/login
Usuario: admin (o el que creaste)
Contraseña: <tu contraseña>
```

### Panel de Administración

```
URL: http://localhost/admin
```

## Actualización del Sistema

NexoSupport incluye un sistema de actualización automática similar a Moodle.

### Actualización Automática (Recomendado)

1. **Actualizar el código:**
   ```bash
   git pull origin main
   composer dump-autoload
   ```

2. **Acceder al sistema:**
   Al acceder a cualquier página del sistema, serás **redirigido automáticamente** a:
   ```
   http://localhost/admin/upgrade.php
   ```

3. **Ejecutar actualización:**
   - La página mostrará la versión actual y la nueva versión
   - Mostrará los cambios que se aplicarán
   - Hacer clic en "Actualizar Ahora"

4. **Completar:**
   - El sistema ejecutará los scripts de upgrade necesarios
   - Te mostrará un mensaje de confirmación
   - Podrás acceder al sistema actualizado

### Actualización Manual (Avanzado)

Si prefieres ejecutar la actualización desde línea de comandos:

```bash
# 1. Actualizar código
git pull origin main
composer dump-autoload

# 2. Crear script temporal: upgrade_manual.php
<?php
define('NEXOSUPPORT_INTERNAL', true);
require_once('lib/setup.php');
require_once('lib/upgrade.php');

$dbversion = get_core_version_from_db();
$codeversion = get_core_version_from_code();

echo "Versión BD: $dbversion\n";
echo "Versión Código: $codeversion\n";

if (core_upgrade_required()) {
    echo "Ejecutando upgrade...\n";
    if (xmldb_core_upgrade($dbversion ?? 0)) {
        echo "✅ Upgrade completado\n";
    } else {
        echo "❌ Error en upgrade\n";
    }
} else {
    echo "✅ Sistema actualizado\n";
}
?>

# 3. Ejecutar
php upgrade_manual.php
```

### Historial de Versiones

- **v1.1.0 (2025011800)** - Sistema RBAC Completo
  - Roles y permisos granulares
  - Contextos jerárquicos
  - 3 roles predefinidos
  - 13 capabilities del sistema

- **v1.0.0 (2025011700)** - Sistema Base
  - Core Frankenstyle
  - Sistema de plugins
  - Instalador web
  - Autenticación básica

## Características Implementadas (Fase 1)

### ✅ Core del Sistema

- [x] Front Controller único en `public_html/index.php`
- [x] Sistema de setup y configuración (`lib/setup.php`)
- [x] Funciones helper globales (`lib/functions.php`)
- [x] Sistema de routing simple
- [x] Internacionalización (get_string)
- [x] Gestión de sesiones
- [x] Variables globales ($CFG, $DB, $USER, $LANG)

### ✅ Sistema de Plugins

- [x] Clases base abstractas para todos los tipos de plugins
- [x] Plugin Manager con descubrimiento automático
- [x] Component Resolver para validación Frankenstyle
- [x] Sistema de dependencias
- [x] Versionado y actualización de plugins

### ✅ Base de Datos

- [x] Wrapper de PDO (`\core\db\database`)
- [x] DDL Manager para operaciones de schema
- [x] Parser XML para `install.xml`
- [x] Schema Installer
- [x] Clases XMLDB (table, field, key, index)

### ✅ Instalador

- [x] Instalador web con stages
- [x] Verificación de requisitos
- [x] Configuración de base de datos
- [x] Instalación automática de tablas
- [x] Creación de usuario administrador

### ✅ Plugin auth_manual

- [x] Autenticación contra BD local
- [x] Hash de contraseñas (password_hash)
- [x] Cambio de contraseña
- [x] Hooks post-login
- [x] Actualización de último acceso

### ✅ Subsistemas Básicos

- [x] Login/Logout
- [x] Perfil de usuario
- [x] Panel administrativo
- [x] Gestión básica de usuarios

## Base de Datos

### Tablas del Core

- `users` - Usuarios del sistema
- `config` - Configuración
- `roles` - Roles del sistema
- `capabilities` - Capabilities disponibles
- `role_assignments` - Asignación de roles a usuarios
- `role_capabilities` - Permisos de roles
- `contexts` - Contextos para RBAC
- `sessions` - Sesiones
- `logs` - Logs del sistema

## Seguridad

### Medidas Implementadas

1. **Validación de Inputs:** Todas las entradas se validan con `clean_param()`
2. **Protección CSRF:** Sistema de `sesskey()` para formularios
3. **Passwords:** Hash con `password_hash()` (bcrypt)
4. **SQL Injection:** Uso de PDO con parámetros preparados
5. **Path Traversal:** Validación estricta en servicio de assets
6. **XSS:** `htmlspecialchars()` en todas las salidas

## Roadmap

### Fase 2: RBAC Completo (v1.1.0)

- Sistema completo de Roles, Permisos y Contextos
- Gestión avanzada de roles
- Verificación de capabilities funcional
- Asignación de permisos granular

### Fase 3: Herramienta de Carga Masiva (v1.2.0)

- Plugin `tool_uploaduser`
- Parser CSV
- Importador de usuarios
- Validación y reporte de errores

### Fase 4: Sistema MFA (v1.3.0)

- Plugin `tool_mfa`
- Factor de email (`factor_email`)
- Factor de rango IP (`factor_iprange`)
- Sistema de pesos de factores

### Fase 5: Sistema de Reportes (v1.4.0)

- Plugin `report_log`
- Plugin `report_security`
- Sistema de datasources
- Exportación a CSV/Excel/PDF

### Fase 6: Sistema de Temas (v1.5.0)

- Plugin `theme_iser`
- Compilación de SCSS
- Sistema de layouts
- Servicio de assets optimizado

## API y Funciones Principales

### Funciones Globales

```php
// Configuración
get_config($component, $name);
set_config($name, $value, $component);

// Idioma
get_string($identifier, $component, $a);

// Seguridad
require_login();
require_capability($capability);
has_capability($capability);
sesskey();

// Request
required_param($name, $type);
optional_param($name, $default, $type);

// Navegación
redirect($url, $message, $delay);
```

### Plugin Manager

```php
use core\plugin\manager;

// Obtener plugin de autenticación
$authplugin = manager::get_auth_plugin('manual');

// Obtener todos los plugins de un tipo
$authplugins = manager::get_plugins_of_type('auth');

// Verificar si necesita actualización
if ($plugin->needs_upgrade()) {
    // Actualizar
}
```

## Desarrollo

### Crear un Nuevo Plugin

#### 1. Crear estructura de directorios

```bash
mkdir -p [tipo]/[nombre]/classes
mkdir -p [tipo]/[nombre]/lang/es
mkdir -p [tipo]/[nombre]/lang/en
mkdir -p [tipo]/[nombre]/db
```

#### 2. Crear clase principal

**`[tipo]/[nombre]/classes/plugin.php`:**

```php
<?php
namespace [tipo]_[nombre];

defined('NEXOSUPPORT_INTERNAL') || die();

class plugin extends \core\plugininfo\[tipo] {
    // Implementar métodos abstractos obligatorios
}
```

#### 3. Crear version.php

**`[tipo]/[nombre]/version.php`:**

```php
<?php
defined('NEXOSUPPORT_INTERNAL') || die();

$plugin = new stdClass();
$plugin->component = '[tipo]_[nombre]';
$plugin->version  = YYYYMMDDXX;
$plugin->requires  = 2025011700;
$plugin->release  = '1.0.0';
$plugin->maturity = MATURITY_STABLE;
```

#### 4. Crear strings de idioma

**`[tipo]/[nombre]/lang/es/[tipo]_[nombre].php`:**

```php
<?php
defined('NEXOSUPPORT_INTERNAL') || die();

$string['pluginname'] = 'Nombre del Plugin';
```

#### 5. Agregar al autoloader

**`composer.json`:**

```json
{
  "autoload": {
    "psr-4": {
      "[tipo]_[nombre]\\": "[ruta]/classes/"
    }
  }
}
```

```bash
composer dump-autoload
```

## Testing

(Por implementar en fases futuras)

```bash
./vendor/bin/phpunit
```

## Contribuir

Este es un proyecto privado del ISER. Para contribuir, contactar a:
- soporteplataformas@iser.edu.co

## Licencia

Propietaria - Instituto Superior de Educación Rural (ISER)
Todos los derechos reservados.

## Contacto y Soporte

- **Soporte Técnico:** soporteplataformas@iser.edu.co
- **Nexo Operativo:** nexo.operativo@iser.edu.co
- **Vicerrectoría:** vicerrectoria@iser.edu.co

---

**NexoSupport** - Sistema de Gestión con Arquitectura Frankenstyle
Desarrollado con ❤️ por el equipo ISER
