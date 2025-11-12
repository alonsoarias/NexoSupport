# i18n Hardcoded Strings - Detailed Examples

## Format
Each entry shows:
1. File path
2. Current code (hardcoded)
3. Recommended fix
4. Line numbers
5. Severity

---

## CONTROLLERS

### 1. modules/Controllers/AdminController.php

**Example 1: Page Title**
```php
// CURRENT (lines 72-73)
'page_title' => 'Panel de Administración',
'header_title' => 'Administración del Sistema',

// RECOMMENDED
'page_title' => __('admin.dashboard.title'),
'header_title' => __('admin.dashboard.header'),
```
**Severity**: HIGH

**Example 2: Menu Items**
```php
// CURRENT (lines 83-86)
'menu_items' => [
    ['icon' => 'people', 'title' => 'Usuarios', 'url' => '/admin/users', 'count' => $stats['total_users']],
    ['icon' => 'shield-check', 'title' => 'Seguridad', 'url' => '/admin/security', 'count' => $stats['login_attempts_today']],
    ['icon' => 'gear', 'title' => 'Configuración', 'url' => '/admin/settings', 'count' => null],
    ['icon' => 'graph-up', 'title' => 'Reportes', 'url' => '/admin/reports', 'count' => null],
],

// RECOMMENDED
'menu_items' => [
    ['icon' => 'people', 'title' => __('admin.menu.users'), 'url' => '/admin/users', 'count' => $stats['total_users']],
    ['icon' => 'shield-check', 'title' => __('admin.menu.security'), 'url' => '/admin/security', 'count' => $stats['login_attempts_today']],
    ['icon' => 'gear', 'title' => __('admin.menu.settings'), 'url' => '/admin/settings', 'count' => null],
    ['icon' => 'graph-up', 'title' => __('admin.menu.reports'), 'url' => '/admin/reports', 'count' => null],
],
```
**Severity**: HIGH

**Example 3: Status Labels**
```php
// CURRENT (lines 442-445)
$statusLabels = [
    'active' => 'Activo',
    'inactive' => 'Inactivo',
    'suspended' => 'Suspendido',
    'pending' => 'Pendiente',
];

// RECOMMENDED
$statusLabels = [
    'active' => __('status.active'),
    'inactive' => __('status.inactive'),
    'suspended' => __('status.suspended'),
    'pending' => __('status.pending'),
];
```
**Severity**: MEDIUM

---

### 2. modules/Controllers/RoleController.php

**Example 1: Page Title**
```php
// CURRENT (line 114)
'page_title' => 'Gestión de Roles',

// RECOMMENDED
'page_title' => __('admin.roles.title'),
```
**Severity**: HIGH

**Example 2: Success Messages**
```php
// CURRENT (lines 120-123)
$messages = [
    'created' => 'Rol creado correctamente',
    'updated' => 'Rol actualizado correctamente',
    'deleted' => 'Rol eliminado correctamente',
];

// RECOMMENDED
$messages = [
    'created' => __('admin.roles.messages.created'),
    'updated' => __('admin.roles.messages.updated'),
    'deleted' => __('admin.roles.messages.deleted'),
];
```
**Severity**: HIGH

**Example 3: Error Messages**
```php
// CURRENT (lines 129-132)
$errors = [
    'invalid_id' => 'ID de rol inválido',
    'not_found' => 'Rol no encontrado',
    'system_role' => 'No se pueden modificar roles del sistema',
];

// RECOMMENDED
$errors = [
    'invalid_id' => __('admin.roles.errors.invalid_id'),
    'not_found' => __('admin.roles.errors.not_found'),
    'system_role' => __('admin.roles.errors.system_role'),
];
```
**Severity**: HIGH

---

### 3. modules/Controllers/UserManagementController.php

**Example 1: Page Title**
```php
// CURRENT (line 106)
'page_title' => 'Gestión de Usuarios',

// RECOMMENDED
'page_title' => __('admin.users.title'),
```
**Severity**: HIGH

**Example 2: Success Messages**
```php
// CURRENT (lines 112-116)
$messages = [
    'created' => 'Usuario creado correctamente',
    'updated' => 'Usuario actualizado correctamente',
    'deleted' => 'Usuario eliminado correctamente',
    'restored' => 'Usuario restaurado correctamente',
];

// RECOMMENDED
$messages = [
    'created' => __('admin.users.messages.created'),
    'updated' => __('admin.users.messages.updated'),
    'deleted' => __('admin.users.messages.deleted'),
    'restored' => __('admin.users.messages.restored'),
];
```
**Severity**: HIGH

---

### 4. modules/Controllers/PermissionController.php

**Example 1: Page Title**
```php
// CURRENT (line 91)
'page_title' => 'Gestión de Permisos',

// RECOMMENDED
'page_title' => __('admin.permissions.title'),
```
**Severity**: HIGH

**Example 2: Error Message in Response**
```php
// CURRENT (line 297)
return Response::json(['error' => 'ID de permiso no proporcionado'], 400);

// RECOMMENDED
return Response::json(['error' => __('admin.permissions.errors.no_id')], 400);
```
**Severity**: HIGH

---

### 5. modules/Controllers/UserProfileController.php

**Example 1: Validation Errors**
```php
// CURRENT (lines 351-397)
$errors = [];
if (!empty($data['phone']) && strlen($data['phone']) > 20) {
    $errors[] = 'El teléfono no debe exceder 20 caracteres';
}
if (!empty($data['phone']) && !preg_match('/^[0-9\-\+\(\)\s]+$/', $data['phone'])) {
    $errors[] = 'El formato del teléfono no es válido';
}
if (!empty($data['postal_code']) && strlen($data['postal_code']) > 20) {
    $errors[] = 'El código postal no debe exceder 20 caracteres';
}

// RECOMMENDED
$errors = [];
if (!empty($data['phone']) && strlen($data['phone']) > 20) {
    $errors[] = __('validation.phone.max_length');
}
if (!empty($data['phone']) && !preg_match('/^[0-9\-\+\(\)\s]+$/', $data['phone'])) {
    $errors[] = __('validation.phone.invalid_format');
}
if (!empty($data['postal_code']) && strlen($data['postal_code']) > 20) {
    $errors[] = __('validation.postal_code.max_length');
}
```
**Severity**: MEDIUM

---

## ADMIN MODULES

### 1. modules/Admin/AdminPlugins.php

**Example 1: Error Messages**
```php
// CURRENT (lines 228, 287, 295)
return Response::json([
    'success' => false,
    'message' => 'Upload file not found'
], 400);

return Response::json([
    'success' => false,
    'message' => 'Plugin not found: ' . $slug
], 404);

return Response::json([
    'success' => false,
    'message' => 'Plugin is already enabled'
], 400);

// RECOMMENDED
return Response::json([
    'success' => false,
    'message' => __('admin.plugins.errors.upload_not_found')
], 400);

return Response::json([
    'success' => false,
    'message' => __('admin.plugins.errors.not_found', ['slug' => $slug])
], 404);

return Response::json([
    'success' => false,
    'message' => __('admin.plugins.errors.already_enabled')
], 400);
```
**Severity**: HIGH

**Example 2: Success Messages**
```php
// CURRENT (lines 318, 401, 464)
return Response::json([
    'success' => true,
    'message' => 'Plugin enabled successfully',
]);

return Response::json([
    'success' => true,
    'message' => 'Plugin disabled successfully',
]);

return Response::json([
    'success' => true,
    'message' => 'Plugin uninstalled successfully'
]);

// RECOMMENDED
return Response::json([
    'success' => true,
    'message' => __('admin.plugins.success.enabled'),
]);

return Response::json([
    'success' => true,
    'message' => __('admin.plugins.success.disabled'),
]);

return Response::json([
    'success' => true,
    'message' => __('admin.plugins.success.uninstalled')
]);
```
**Severity**: HIGH

---

### 2. modules/Admin/AdminSettings.php

**Example 1: Section Names**
```php
// CURRENT (lines 26-31)
private const SECTIONS = [
    'general' => 'Configuración General',
    'manageauths' => 'Métodos de Autenticación',
    'outgoingmailconfig' => 'Correo Saliente',
    'mfa' => 'Autenticación Multi-Factor',
    'sitepolicies' => 'Políticas del Sitio',
    'themesettingiser' => 'Tema ISER',
];

// RECOMMENDED
private const SECTIONS = [
    'general' => 'settings.sections.general',
    'manageauths' => 'settings.sections.auth',
    'outgoingmailconfig' => 'settings.sections.mail',
    'mfa' => 'settings.sections.mfa',
    'sitepolicies' => 'settings.sections.policies',
    'themesettingiser' => 'settings.sections.theme',
];

public function getSections(): array
{
    $sections = [];
    foreach (self::SECTIONS as $key => $translationKey) {
        $sections[$key] = __($translationKey);
    }
    return $sections;
}
```
**Severity**: HIGH

**Example 2: Setting Labels**
```php
// CURRENT (lines 76-86)
'sitename' => [
    'value' => $this->settings->get('sitename', 'core', 'ISER'),
    'type' => 'text',
    'label' => 'Nombre del Sitio',
    'description' => 'El nombre que aparecerá en todo el sistema',
],
'sitedescription' => [
    'value' => $this->settings->get('sitedescription', 'core', ''),
    'type' => 'textarea',
    'label' => 'Descripción del Sitio',
    'description' => 'Breve descripción del sitio',
],

// RECOMMENDED
'sitename' => [
    'value' => $this->settings->get('sitename', 'core', 'ISER'),
    'type' => 'text',
    'label' => __('settings.general.sitename.label'),
    'description' => __('settings.general.sitename.description'),
],
'sitedescription' => [
    'value' => $this->settings->get('sitedescription', 'core', ''),
    'type' => 'textarea',
    'label' => __('settings.general.sitedescription.label'),
    'description' => __('settings.general.sitedescription.description'),
],
```
**Severity**: HIGH

---

### 3. modules/Admin/AdminTools.php

**Example 1: Tool Names and Descriptions**
```php
// CURRENT (lines 37-63)
public function getTools(): array
{
    return [
        'installaddon' => [
            'name' => 'Instalar Addon',
            'description' => 'Instalar nuevos plugins o módulos en el sistema',
            'url' => '/admin/tool/installaddon/index.php',
            'icon' => 'bi-cloud-download',
            'enabled' => true,
        ],
        'uploaduser' => [
            'name' => 'Cargar Usuarios',
            'description' => 'Importar usuarios masivamente desde archivo CSV',
            'url' => '/admin/tool/uploaduser/index.php',
            'icon' => 'bi-file-earmark-spreadsheet',
            'enabled' => true,
        ],
    ];
}

// RECOMMENDED
public function getTools(): array
{
    return [
        'installaddon' => [
            'name' => __('admin.tools.installaddon.name'),
            'description' => __('admin.tools.installaddon.description'),
            'url' => '/admin/tool/installaddon/index.php',
            'icon' => 'bi-cloud-download',
            'enabled' => true,
        ],
        'uploaduser' => [
            'name' => __('admin.tools.uploaduser.name'),
            'description' => __('admin.tools.uploaduser.description'),
            'url' => '/admin/tool/uploaduser/index.php',
            'icon' => 'bi-file-earmark-spreadsheet',
            'enabled' => true,
        ],
    ];
}
```
**Severity**: MEDIUM

---

### 4. modules/Admin/AdminReports.php

**Example 1: Report Names and Descriptions**
```php
// CURRENT (lines 33-61)
public function getReports(): array
{
    return [
        'users' => [
            'name' => 'Reporte de Usuarios',
            'description' => 'Estadísticas y listado de usuarios del sistema',
            'icon' => 'bi-people',
        ],
        'logins' => [
            'name' => 'Reporte de Accesos',
            'description' => 'Historial de inicios de sesión',
            'icon' => 'bi-door-open',
        ],
    ];
}

// RECOMMENDED
public function getReports(): array
{
    return [
        'users' => [
            'name' => __('admin.reports.users.name'),
            'description' => __('admin.reports.users.description'),
            'icon' => 'bi-people',
        ],
        'logins' => [
            'name' => __('admin.reports.logins.name'),
            'description' => __('admin.reports.logins.description'),
            'icon' => 'bi-door-open',
        ],
    ];
}
```
**Severity**: MEDIUM

---

## PLUGIN SYSTEM

### 1. modules/Plugin/PluginInstaller.php

**Example 1: Validation Error Messages**
```php
// CURRENT (lines 128-144)
if (!file_exists($zipPath)) {
    return [
        'success' => false,
        'message' => 'ZIP file not found',
        'plugin' => null
    ];
}

if (filesize($zipPath) > self::MAX_FILE_SIZE) {
    return [
        'success' => false,
        'message' => 'Plugin file exceeds maximum size (' . self::MAX_FILE_SIZE . ' bytes)',
        'plugin' => null
    ];
}

if (!is_readable($zipPath)) {
    return [
        'success' => false,
        'message' => 'ZIP file is not readable',
        'plugin' => null
    ];
}

// RECOMMENDED
if (!file_exists($zipPath)) {
    return [
        'success' => false,
        'message' => __('plugins.errors.zip_not_found'),
        'plugin' => null
    ];
}

if (filesize($zipPath) > self::MAX_FILE_SIZE) {
    return [
        'success' => false,
        'message' => __('plugins.errors.file_too_large', ['size' => self::MAX_FILE_SIZE]),
        'plugin' => null
    ];
}

if (!is_readable($zipPath)) {
    return [
        'success' => false,
        'message' => __('plugins.errors.zip_not_readable'),
        'plugin' => null
    ];
}
```
**Severity**: HIGH

**Example 2: Manifest Validation Messages**
```php
// CURRENT (lines 167-178)
if (!$manifest) {
    return [
        'success' => false,
        'message' => 'Invalid or missing plugin.json manifest',
        'plugin' => null
    ];
}

if (!$validation['valid']) {
    return [
        'success' => false,
        'message' => 'Manifest validation failed: ' . $validation['message'],
        'plugin' => null
    ];
}

// RECOMMENDED
if (!$manifest) {
    return [
        'success' => false,
        'message' => __('plugins.errors.invalid_manifest'),
        'plugin' => null
    ];
}

if (!$validation['valid']) {
    return [
        'success' => false,
        'message' => __('plugins.errors.validation_failed', ['reason' => $validation['message']]),
        'plugin' => null
    ];
}
```
**Severity**: HIGH

**Example 3: Success Message**
```php
// CURRENT (line 267)
'message' => 'Plugin installed successfully: ' . $manifest['name'],

// RECOMMENDED
'message' => __('plugins.success.installed', ['name' => $manifest['name']]),
```
**Severity**: HIGH

---

## TRANSLATION FILE STRUCTURE

### resources/lang/es/admin.php
```php
return [
    'dashboard' => [
        'title' => 'Panel de Administración',
        'header' => 'Administración del Sistema',
    ],
    'menu' => [
        'users' => 'Usuarios',
        'security' => 'Seguridad',
        'settings' => 'Configuración',
        'reports' => 'Reportes',
    ],
    'roles' => [
        'title' => 'Gestión de Roles',
        'messages' => [
            'created' => 'Rol creado correctamente',
            'updated' => 'Rol actualizado correctamente',
            'deleted' => 'Rol eliminado correctamente',
        ],
        'errors' => [
            'invalid_id' => 'ID de rol inválido',
            'not_found' => 'Rol no encontrado',
            'system_role' => 'No se pueden modificar roles del sistema',
        ],
    ],
    'users' => [
        'title' => 'Gestión de Usuarios',
        'messages' => [
            'created' => 'Usuario creado correctamente',
            'updated' => 'Usuario actualizado correctamente',
            'deleted' => 'Usuario eliminado correctamente',
            'restored' => 'Usuario restaurado correctamente',
        ],
        'errors' => [
            'invalid_id' => 'ID de usuario inválido',
            'not_found' => 'Usuario no encontrado',
        ],
    ],
    'permissions' => [
        'title' => 'Gestión de Permisos',
        'messages' => [
            'created' => 'Permiso creado correctamente',
            'updated' => 'Permiso actualizado correctamente',
            'deleted' => 'Permiso eliminado correctamente',
        ],
        'errors' => [
            'no_id' => 'ID de permiso no proporcionado',
            'not_found' => 'Permiso no encontrado',
        ],
    ],
    'tools' => [
        'installaddon' => [
            'name' => 'Instalar Addon',
            'description' => 'Instalar nuevos plugins o módulos en el sistema',
        ],
        'uploaduser' => [
            'name' => 'Cargar Usuarios',
            'description' => 'Importar usuarios masivamente desde archivo CSV',
        ],
        'clearcache' => [
            'name' => 'Limpiar Caché',
            'description' => 'Limpiar el caché del sistema',
        ],
    ],
    'reports' => [
        'users' => [
            'name' => 'Reporte de Usuarios',
            'description' => 'Estadísticas y listado de usuarios del sistema',
        ],
        'logins' => [
            'name' => 'Reporte de Accesos',
            'description' => 'Historial de inicios de sesión',
        ],
    ],
    'plugins' => [
        'success' => [
            'enabled' => 'Plugin habilitado correctamente',
            'disabled' => 'Plugin deshabilitado correctamente',
            'uninstalled' => 'Plugin desinstalado correctamente',
        ],
        'errors' => [
            'upload_not_found' => 'Archivo de descarga no encontrado',
            'not_found' => 'Plugin no encontrado: :slug',
            'already_enabled' => 'El plugin ya está habilitado',
        ],
    ],
];
```

### resources/lang/es/validation.php
```php
return [
    'phone' => [
        'max_length' => 'El teléfono no debe exceder 20 caracteres',
        'invalid_format' => 'El formato del teléfono no es válido',
    ],
    'postal_code' => [
        'max_length' => 'El código postal no debe exceder 20 caracteres',
    ],
    'city' => [
        'max_length' => 'La ciudad no debe exceder 100 caracteres',
    ],
    'country' => [
        'max_length' => 'El país no debe exceder 100 caracteres',
    ],
    'website' => [
        'invalid_url' => 'El sitio web debe ser una URL válida',
    ],
    'linkedin' => [
        'invalid_url' => 'LinkedIn debe ser una URL válida',
    ],
    'bio' => [
        'max_length' => 'La biografía no debe exceder 1000 caracteres',
    ],
];
```

### resources/lang/es/plugins.php
```php
return [
    'success' => [
        'installed' => 'Plugin instalado correctamente: :name',
    ],
    'errors' => [
        'zip_not_found' => 'Archivo ZIP no encontrado',
        'file_too_large' => 'El archivo del plugin excede el tamaño máximo (:size bytes)',
        'zip_not_readable' => 'El archivo ZIP no es legible',
        'invalid_manifest' => 'Manifest plugin.json inválido o faltante',
        'validation_failed' => 'Validación de manifest fallida: :reason',
        'already_installed' => 'El plugin ya está instalado: :slug',
        'invalid_structure' => 'Estructura de plugin inválida - faltan archivos requeridos',
        'missing_dependencies' => 'Dependencias faltantes: :missing',
        'create_directory_failed' => 'Error al crear directorio del plugin: :path',
        'copy_files_failed' => 'Error al copiar archivos del plugin',
        'database_registration_failed' => 'Error al registrar el plugin en la base de datos',
    ],
];
```

---

## Summary

- **Total Examples Shown**: 25+
- **Files Covered**: 9
- **Pattern**: Consistent use of `__()` helper function with dot-notation keys
- **Next Step**: Implement these examples to fully internationalize the codebase

