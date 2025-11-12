# i18n Hardcoded Strings Audit Report
**Date**: 2025-11-12  
**Status**: Complete  
**Total Issues Found**: 150+ hardcoded user-facing strings

---

## Executive Summary

This audit examined the NexoSupport codebase for hardcoded strings that should be internationalized (i18n). The codebase currently contains numerous user-facing strings in Spanish and English that are not wrapped in translation functions. These strings make it difficult to support multiple languages and reduce code maintainability.

### Key Findings:
- **Total Hardcoded Strings**: 150+
- **Files Affected**: 16 files across Controllers, Admin modules, and Plugin system
- **Languages Mixed**: Spanish and English strings scattered throughout
- **Translation Infrastructure**: Project has `Translator` class available but underutilized

---

## Affected Files and Issues

### 1. Controllers (modules/Controllers/)

#### AdminController.php
**Issue Count**: 12 hardcoded strings  
**Severity**: HIGH

**Strings to Internationalize:**
- Page titles: 'Panel de Administración', 'Administración del Sistema', 'Gestión de Usuarios', 'Configuración del Sistema', 'Reportes del Sistema', 'Seguridad del Sistema'
- Menu items: 'Usuarios', 'Seguridad', 'Configuración', 'Reportes'
- Status labels: 'Exitoso', 'Fallido', 'Activo', 'Inactivo', 'Suspendido', 'Pendiente'

**Lines with Issues:**
- Lines 72-73: Page titles
- Lines 83-86: Menu item titles
- Line 280: Status label
- Lines 442-445: Status labels array

**Fix Pattern:**
```php
// Before:
'page_title' => 'Panel de Administración',

// After:
'page_title' => __('admin.dashboard_title'),
```

---

#### RoleController.php
**Issue Count**: 10 hardcoded strings  
**Severity**: HIGH

**Strings to Internationalize:**
- 'Gestión de Roles', 'Crear Rol', 'Editar Rol'
- 'Rol creado correctamente', 'Rol actualizado correctamente', 'Rol eliminado correctamente'
- 'ID de rol inválido', 'Rol no encontrado', 'No se pueden modificar roles del sistema'

**Lines with Issues:**
- Line 114: Page title
- Lines 120-123: Success messages
- Lines 129-132: Error messages
- Line 269: Dynamic page title

---

#### UserManagementController.php
**Issue Count**: 10 hardcoded strings  
**Severity**: HIGH

**Strings to Internationalize:**
- 'Gestión de Usuarios', 'Crear Usuario'
- 'Usuario creado correctamente', 'Usuario actualizado correctamente', 'Usuario eliminado correctamente', 'Usuario restaurado correctamente'
- 'ID de usuario inválido', 'Usuario no encontrado'

**Lines with Issues:**
- Line 106: Page title
- Lines 112-116: Success messages
- Lines 122-125: Error messages
- Line 143: Page title

---

#### PermissionController.php
**Issue Count**: 12 hardcoded strings  
**Severity**: HIGH

**Strings to Internationalize:**
- 'Gestión de Permisos', 'Crear Permiso', 'Editar Permiso'
- 'Permiso creado correctamente', 'Permiso actualizado correctamente', 'Permiso eliminado correctamente'
- 'ID de permiso inválido', 'Permiso no encontrado'
- 'Error al crear el permiso', 'Error al actualizar el permiso', 'Error al eliminar el permiso'

**Lines with Issues:**
- Line 91: Page title
- Lines 97-99: Success messages
- Lines 106-109: Error messages
- Multiple error response lines

---

#### UserProfileController.php
**Issue Count**: 8 hardcoded validation messages  
**Severity**: MEDIUM

**Strings to Internationalize:**
- 'El teléfono no debe exceder 20 caracteres'
- 'El formato del teléfono no es válido'
- 'El código postal no debe exceder 20 caracteres'
- 'La ciudad no debe exceder 100 caracteres'
- 'El país no debe exceder 100 caracteres'
- 'El sitio web debe ser una URL válida'
- 'LinkedIn debe ser una URL válida'
- 'La biografía no debe exceder 1000 caracteres'

**Lines with Issues:**
- Lines 351, 356, 363, 369, 375, 382, 390, 397

---

#### AdminSettingsController.php
**Issue Count**: 3 hardcoded strings (rest properly internationalized)  
**Severity**: LOW

**Partially Good Practice**: This controller shows good practices by using translator:
```php
'page_title' => $this->translator->translate('settings.title'),
```

**Issues:**
- DEFAULTS array contains hardcoded default values
- Some setting descriptions lack translations

**Lines with Issues:**
- Lines 54-83: Default values

---

### 2. Admin Modules (modules/Admin/)

#### AdminPlugins.php
**Issue Count**: 18 hardcoded strings  
**Severity**: HIGH

**Strings to Internationalize:**
- Error messages: 'Upload file not found', 'Plugin not found', 'Plugin is already enabled/disabled', 'Failed to enable plugin. Check dependencies.'
- Success messages: 'Plugin enabled successfully', 'Plugin disabled successfully', 'Plugin uninstalled successfully', 'Plugin installed successfully'
- Spanish errors: 'Error al cargar los plugins', 'Plugin no encontrado', 'Error al cargar los detalles del plugin'

**Lines with Issues:**
- Lines 197, 206: Error messages
- Line 228: Upload error
- Lines 287, 295, 305: Plugin state errors
- Lines 318, 401, 464: Success messages
- Lines 552, 618: Spanish error messages

---

#### AdminSettings.php
**Issue Count**: 50+ hardcoded labels/descriptions  
**Severity**: HIGH

**Strings to Internationalize:**
- Section names: 'Configuración General', 'Métodos de Autenticación', 'Correo Saliente', etc.
- Field labels: 'Nombre del Sitio', 'Descripción del Sitio', 'Idioma Predeterminado', 'Zona Horaria', etc.
- Descriptions: Multiple setting descriptions in Spanish

**Lines with Issues:**
- Lines 26-31: Section names (SECTIONS array)
- Lines 73-100: General settings labels
- Lines 108-134: Auth settings labels
- Lines 141-187: Mail settings labels
- Lines 194-228: MFA settings labels
- Lines 236-260: Site policy settings labels
- Lines 267-292: Theme settings labels

---

#### AdminTools.php
**Issue Count**: 5 tool names + 5 descriptions = 10 hardcoded strings  
**Severity**: MEDIUM

**Strings to Internationalize:**
- 'Instalar Addon', 'Cargar Usuarios', 'Limpiar Caché', 'Mantenimiento de Base de Datos'
- Associated descriptions for each tool

**Lines with Issues:**
- Lines 37-63: Tool definitions (getTools method)

---

#### AdminReports.php
**Issue Count**: 10 hardcoded strings (5 names + 5 descriptions)  
**Severity**: MEDIUM

**Strings to Internationalize:**
- 'Reporte de Usuarios', 'Reporte de Accesos', 'Reporte de Roles', 'Reporte de MFA', 'Actividad Administrativa'
- Associated descriptions for each report

**Lines with Issues:**
- Lines 37-59: Report definitions (getReports method)

---

### 3. Plugin System (modules/Plugin/)

#### PluginInstaller.php
**Issue Count**: 20+ hardcoded error/status messages  
**Severity**: HIGH

**Strings to Internationalize:**
- Error messages: 'ZIP file not found', 'Plugin file exceeds maximum size', 'ZIP file is not readable', 'Invalid or missing plugin.json manifest', 'Plugin already installed', 'Invalid plugin structure - missing required files', 'Missing dependencies', etc.
- Success message: 'Plugin installed successfully'
- Installation errors: 'Installation error: ' concatenated with exception message

**Lines with Issues:**
- Line 128: ZIP file not found
- Line 136: File size exceeded
- Line 144: File not readable
- Line 167: Missing manifest
- Line 178: Validation failed
- Line 189: Plugin already exists
- Line 199: Invalid structure
- Line 211: Missing dependencies
- Line 224: Directory creation failed
- Line 235: File copy failed
- Line 247: Database registration failed
- Line 267: Success message
- Line 279: Installation error
- Lines 360-394: Extraction and validation errors

---

## Summary Statistics

### By Category
| Category | Count | Severity |
|----------|-------|----------|
| Page Titles | 20+ | HIGH |
| Menu Items | 5 | HIGH |
| Success Messages | 15+ | HIGH |
| Error Messages | 30+ | HIGH |
| Validation Messages | 10+ | MEDIUM |
| Status Labels | 5 | MEDIUM |
| Settings Labels/Descriptions | 50+ | HIGH |
| Tool/Report Names | 10 | MEDIUM |
| **TOTAL** | **150+** | - |

### By File
| File | Issues | Severity |
|------|--------|----------|
| AdminSettings.php | 50+ | HIGH |
| PluginInstaller.php | 20+ | HIGH |
| AdminPlugins.php | 18 | HIGH |
| AdminController.php | 12 | HIGH |
| PermissionController.php | 12 | HIGH |
| RoleController.php | 10 | HIGH |
| UserManagementController.php | 10 | HIGH |
| AdminTools.php | 10 | MEDIUM |
| AdminReports.php | 10 | MEDIUM |
| UserProfileController.php | 8 | MEDIUM |
| **TOTAL** | **160+** | - |

---

## Language Mix Issue

The codebase currently contains:
- **Spanish strings**: ~100+ (admin interfaces, labels, messages)
- **English strings**: ~50+ (plugin system, some error messages)

This inconsistency suggests the application was partially internationalized but the effort was incomplete.

---

## Recommended Translation Function

### Option 1: Use existing Translator class
```php
$this->translator->translate('admin.users.created', 'Usuario creado correctamente')
```

### Option 2: Use helper function (if available)
```php
__('admin.users.created')
```

### Recommended Key Naming Convention
```
domain.context.key_name

Examples:
- admin.dashboard.title
- admin.users.created
- admin.users.updated
- admin.users.deleted
- error.validation.email_invalid
- error.plugin.not_found
- messages.success.operation_complete
```

---

## Translation File Structure Recommendation

```
resources/lang/
├── es/
│   ├── admin.php          # Admin panel strings
│   ├── controllers.php    # Controller messages
│   ├── errors.php         # Error messages
│   ├── messages.php       # General messages
│   ├── plugins.php        # Plugin system
│   ├── validation.php     # Validation errors
│   └── tools.php          # Admin tools
├── en/
│   ├── admin.php
│   ├── controllers.php
│   ├── errors.php
│   ├── messages.php
│   ├── plugins.php
│   ├── validation.php
│   └── tools.php
└── [other languages]/
```

---

## Refactoring Priority

### Phase 1 (Critical) - Complete first
1. AdminController.php
2. AdminPlugins.php
3. AdminSettings.php
4. PluginInstaller.php

### Phase 2 (High) - Complete second
1. RoleController.php
2. UserManagementController.php
3. PermissionController.php
4. AdminTools.php
5. AdminReports.php

### Phase 3 (Medium) - Complete third
1. UserProfileController.php
2. Other controllers with hardcoded strings
3. Remaining plugin system files

---

## Implementation Checklist

- [ ] Create translation file structure
- [ ] Implement helper function __() if not present
- [ ] Start with Phase 1 files
- [ ] Create PR for each module/controller
- [ ] Add translation keys to language files
- [ ] Test all user interfaces
- [ ] Verify all messages display correctly
- [ ] Update documentation
- [ ] Add i18n linting to CI/CD pipeline
- [ ] Document translation key naming conventions

---

## Files with Good Practices

The following files already use the translation system and can serve as templates:

### AdminSettingsController.php
```php
'page_title' => $this->translator->translate('settings.title'),
'header_title' => $this->translator->translate('settings.title'),
```

### AuthController.php
```php
'page_title' => $this->translator->translate('auth.login'),
'header_title' => $this->translator->translate('auth.login'),
```

### UserProfileController.php (Partial)
```php
'roles' => $this->translator->translate('profile.roles', 'Roles'),
'mobile' => $this->translator->translate('profile.mobile', 'Móvil'),
```

These controllers demonstrate the correct pattern that should be applied throughout the codebase.

---

## Testing Strategy

### 1. Automated Detection
Create a script to detect hardcoded strings:
```bash
# Search for strings that should be translated
grep -r "=> '[A-Z].*'" modules/Controllers/ modules/Admin/ modules/Plugin/ \
  | grep -v "__(" | grep -v "translate(" | grep -v "t(" > hardcoded_strings.txt
```

### 2. Manual Review
- Review all PR changes for i18n compliance
- Ensure new features use translation functions
- Check that all user-facing strings are internationalized

### 3. User Testing
- Test with different language settings
- Verify all interface text displays correctly
- Check for any remaining hardcoded strings

---

## References

### Related Documentation
- Core/I18n/Translator.php - Main translation class
- core/I18n/LocaleDetector.php - Locale detection
- resources/lang/ - Translation files directory

### Standards
- PSR-4 Autoloading
- PSR-12 Code Style
- i18n Best Practices

---

**Report Generated**: 2025-11-12  
**Status**: Ready for Implementation  
**Next Steps**: Begin Phase 1 refactoring
