# Estructura de Plugins según Moodle

## 1. AUTH Plugins (auth/*)

Estructura estándar de un plugin de autenticación:

```
auth/pluginname/
├── auth.php              ← Clase auth_plugin_pluginname extends auth_plugin_base
├── version.php           ← Metadata del plugin
├── settings.php          ← Configuración (opcional)
├── classes/              ← Clases PSR-4
├── db/                   ← Esquemas de BD
│   ├── install.xml
│   ├── upgrade.php
│   └── access.php
├── lang/                 ← Strings de idioma
│   └── es/
│       └── auth_pluginname.php
└── tests/                ← Unit tests

IMPORTANTE: Los auth plugins NO usan lib.php, usan auth.php
```

## 2. ADMIN/TOOL Plugins (admin/tool/*)

Estructura estándar de herramientas administrativas:

```
admin/tool/pluginname/
├── version.php           ← Metadata del plugin
├── lib.php               ← Funciones públicas y callbacks
├── settings.php          ← Configuración (opcional)
├── index.php             ← Página principal (opcional)
├── classes/              ← Clases PSR-4
├── db/                   ← Esquemas de BD
│   ├── install.xml
│   ├── upgrade.php
│   └── access.php
├── lang/                 ← Strings de idioma
│   └── es/
│       └── tool_pluginname.php
└── tests/                ← Unit tests

IMPORTANTE: Los tool plugins SÍ usan lib.php para funciones públicas
```

## 3. THEME Plugins (theme/*)

Estructura estándar de temas:

```
theme/themename/
├── version.php           ← Metadata del plugin
├── lib.php               ← Callbacks del tema (theme_themename_*)
├── config.php            ← Configuración del tema (layouts, parents, etc)
├── settings.php          ← Configuración de admin (opcional)
├── classes/
│   └── output/
│       └── core_renderer.php  ← Renderer principal
├── layout/               ← Archivos de layout
│   ├── base.php
│   ├── admin.php
│   ├── course.php
│   └── ...
├── scss/                 ← Estilos SCSS
│   ├── preset/
│   └── post.scss
├── pix/                  ← Imágenes y recursos
├── templates/            ← Mustache templates
├── lang/                 ← Strings de idioma
│   └── es/
│       └── theme_themename.php
└── tests/

IMPORTANTE: config.php define layouts, parent themes, stylesheets
```

## 4. REPORT Plugins (report/*)

Estructura estándar de reportes:

```
report/reportname/
├── version.php           ← Metadata del plugin
├── lib.php               ← Funciones públicas (report_reportname_*)
├── index.php             ← Página principal del reporte
├── settings.php          ← Configuración (opcional)
├── classes/              ← Clases PSR-4
├── db/                   ← Esquemas de BD
├── lang/                 ← Strings de idioma
│   └── es/
│       └── report_reportname.php
└── tests/

IMPORTANTE: index.php es la página principal que muestra el reporte
```

## 5. FACTOR Plugins (Subplugins de tool_mfa)

Estructura de factores MFA (admin/tool/mfa/factor/*):

```
admin/tool/mfa/factor/factorname/
├── version.php           ← Metadata del plugin
├── classes/
│   └── factor.php        ← Clase \factor_factorname\factor extends \tool_mfa\local\factor\object_factor_base
├── settings.php          ← Configuración del factor (opcional)
├── lang/                 ← Strings de idioma
│   └── es/
│       └── factor_factorname.php
├── templates/            ← Templates Mustache
│   ├── setup.mustache
│   └── verification.mustache
└── tests/

IMPORTANTE: NO usan lib.php, la lógica va en classes/factor.php
El plugin padre es tool_mfa, los factores son subplugins
```

## 6. Archivos Comunes

### version.php (TODOS los plugins)

```php
<?php
defined('MOODLE_INTERNAL') || die();

$plugin = new stdClass();
$plugin->component = 'plugintype_pluginname';  // ej: auth_manual, tool_mfa, theme_core
$plugin->version = 2025011600;                 // YYYYMMDDXX
$plugin->requires = 2024042200;                // Versión mínima de Moodle
$plugin->release = '1.0.0';
$plugin->maturity = MATURITY_STABLE;

// Para subplugins:
$plugin->dependencies = [
    'tool_mfa' => 2025010100,  // Dependencia del plugin padre
];
```

### lib.php (tool, theme, report - NO auth, NO factor)

Contiene funciones públicas con prefijo del componente:

```php
<?php
defined('MOODLE_INTERNAL') || die();

// Para tool_pluginname:
function tool_pluginname_functionname() {
    // ...
}

// Para theme_themename:
function theme_themename_callback() {
    // ...
}

// Para report_reportname:
function report_reportname_get_data() {
    // ...
}
```

### auth.php (SOLO para auth plugins)

```php
<?php
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/authlib.php');

class auth_plugin_pluginname extends auth_plugin_base {

    public function __construct() {
        $this->authtype = 'pluginname';
        $this->config = get_config('auth_pluginname');
    }

    public function user_login($username, $password) {
        // Lógica de autenticación
    }

    public function can_change_password() {
        return true;
    }

    public function user_update_password($user, $newpassword) {
        // Cambio de contraseña
    }
}
```

### classes/factor.php (SOLO para factor plugins)

```php
<?php
namespace factor_factorname;

defined('MOODLE_INTERNAL') || die();

class factor extends \tool_mfa\local\factor\object_factor_base {

    public function get_weight(): int {
        return 100;
    }

    public function setup_factor_form_definition($mform) {
        // Setup form
    }

    public function verify_form_definition($mform) {
        // Verification form
    }

    public function validate_factor($data): bool {
        // Validación del factor
    }
}
```

## 7. Resumen de Diferencias

| Plugin Type | version.php | lib.php | Archivo Principal | classes/ |
|-------------|-------------|---------|-------------------|----------|
| auth        | ✅          | ❌      | auth.php          | ✅       |
| tool        | ✅          | ✅      | index.php (opc)   | ✅       |
| theme       | ✅          | ✅      | config.php        | ✅       |
| report      | ✅          | ✅      | index.php         | ✅       |
| factor      | ✅          | ❌      | classes/factor.php| ✅       |

## 8. Constantes de Entorno

Moodle usa `MOODLE_INTERNAL`, pero NexoSupport usa `NEXOSUPPORT_INTERNAL`:

```php
// Moodle:
defined('MOODLE_INTERNAL') || die();

// NexoSupport:
defined('NEXOSUPPORT_INTERNAL') || die();
```

## 9. Namespace PSR-4

Los namespaces en Moodle siguen el patrón:
- `auth_pluginname\` → `auth/pluginname/classes/`
- `tool_pluginname\` → `admin/tool/pluginname/classes/`
- `theme_themename\` → `theme/themename/classes/`
- `report_reportname\` → `report/reportname/classes/`
- `factor_factorname\` → `admin/tool/mfa/factor/factorname/classes/`

Esto ya está correctamente configurado en composer.json de NexoSupport.
