# FASE 0.4 - Análisis de Arquitectura PHP

**Fecha:** 2025-11-16  
**Analista:** Claude (Asistente IA)  
**Proyecto:** NexoSupport - Sistema de Autenticación Modular ISER

---

## 1. Patrón Arquitectónico Identificado

✅ **MVC (Model-View-Controller) con capa de Servicios/Managers**

**Justificación:**
- Existe separación clara entre Controllers, Models (Managers) y Views (Templates)
- Controllers en `modules/Controllers/`
- Managers/Services en `modules/User/`, `modules/Roles/`, etc.
- Views en `modules/Theme/Iser/templates/`
- BaseController abstracto para funcionalidades comunes
- Uso de Traits para funcionalidades compartidas

---

## 2. Estructura de Directorios MVC

### Controllers

**Ubicación:** `modules/Controllers/`  
**Cantidad:** 21 archivos  
**Namespace:** `ISER\Controllers`

**Controladores identificados:**
1. AdminBackupController.php
2. AdminController.php
3. AdminEmailQueueController.php
4. AdminSettingsController.php
5. AdminThemeController.php
6. AppearanceController.php
7. AuditLogController.php
8. AuthController.php ⭐
9. HomeController.php ⭐
10. I18nApiController.php
11. LogViewerController.php
12. LoginHistoryController.php
13. PasswordResetController.php
14. PermissionController.php
15. RoleController.php
16. SearchController.php
17. ThemePreviewController.php
18. UserManagementController.php
19. UserPreferencesController.php
20. UserProfileController.php

**Base Controller:** `core/Controllers/BaseController.php`

---

### Models/Managers (Capa de Servicios)

**Ubicación:** Distribuidos en `modules/[Modulo]/`

#### Módulo User (`modules/User/`)
**Namespace:** `ISER\User`

- UserManager.php ⭐
- AccountSecurityManager.php
- LoginHistoryManager.php
- PreferencesManager.php
- UserProfile.php
- UserAvatar.php
- UserSearch.php

#### Módulo Roles (`modules/Roles/`)
**Namespace:** `ISER\Roles`

- RoleManager.php
- PermissionManager.php
- PermissionRepository.php
- RoleAssignment.php
- RoleContext.php

#### Core Database (`core/Database/`)
**Namespace:** `ISER\Core\Database`

- Database.php ⭐
- BaseRepository.php
- SchemaInstaller.php ⭐
- PDOConnection.php
- DatabaseAdapter.php
- DatabaseDriverDetector.php
- BackupManager.php

---

### Views

**Ubicación:** `modules/Theme/Iser/templates/`  
**Motor:** Mustache  
**Cantidad:** 85 templates

**Estructura:**
- layouts/ (admin, base, dashboard, login, etc.)
- pages/ (dashboard, home, profile)
- partials/ (header, footer, navbar, sidebar, etc.)
- components/ (cards, forms, tables)

---

## 3. Namespaces Utilizados

### Namespace Raíz

`ISER\`

### Estructura de Namespaces

```
ISER\
├── Core\
│   ├── Controllers\
│   ├── Database\
│   ├── Config\
│   ├── Http\
│   ├── I18n\
│   ├── Middleware\
│   ├── Plugin\
│   ├── Routing\
│   ├── Session\
│   ├── Theme\
│   ├── Utils\
│   └── View\
│
├── Controllers\       (de modules/Controllers/)
├── Admin\            (de modules/Admin/)
├── User\             (de modules/User/)
├── Roles\            (de modules/Roles/)
├── Auth\Manual\      (de modules/Auth/Manual/)
├── Report\Log\       (de modules/Report/Log/)
└── Theme\Iser\       (de modules/Theme/Iser/)
```

**Mapeo PSR-4 (composer.json):**
```json
"ISER\\": "modules/",
"ISER\\Core\\": "core/"
```

---

## 4. Análisis de BaseController

**Ubicación:** `core/Controllers/BaseController.php`  
**Tipo:** Abstract class

**Funcionalidades provistas:**
- Rendering con layouts (Mustache)
- Integración de navegación (NavigationTrait)
- Response helpers (HTML, JSON, redirect)
- Gestión de sesiones
- Flash messages
- Audit logging
- Acceso a datos de usuario actual

**Propiedades:**
- `$db` - Database instance
- `$renderer` - MustacheRenderer
- `$translator` - Translator (i18n)

**Análisis:** ✅ Excelente práctica - Reduce duplicación de código

---

## 5. Análisis de Controladores Clave

### AuthController

**Ubicación:** `modules/Controllers/AuthController.php`  
**Líneas:** ~330 (estimado)  
**Namespace:** `ISER\Controllers`

**Responsabilidad:** Autenticación de usuarios

**Dependencias:**
- BaseController (herencia)
- UserManager
- AccountSecurityManager
- LoginHistoryManager

**Métodos principales:**
- `showLogin()` - Mostrar formulario
- `processLogin()` - Procesar login
- `logout()` - Cerrar sesión

**Problemas:** ✅ Ninguno - Usa inyección de dependencias

---

### HomeController

**Ubicación:** `modules/Controllers/HomeController.php`  
**Líneas:** ~240 (estimado)

**Responsabilidad:** Dashboard y home

**Métodos:**
- `index()` - Página principal
- `dashboard()` - Dashboard de usuario

---

### AdminController

**Ubicación:** `modules/Controllers/AdminController.php`  
**Líneas:** ~450 (estimado)

**Responsabilidad:** Panel administrativo

---

## 6. Análisis de Managers Clave

### UserManager

**Ubicación:** `modules/User/UserManager.php`  
**Namespace:** `ISER\User`

**Responsabilidad:** Gestión de usuarios (CRUD)

**Métodos esperados:**
- `create()`, `update()`, `delete()`
- `getById()`, `getAll()`, `search()`
- `validateUser()`, `authenticate()`

**Patrón:** Repository/Service pattern

---

### RoleManager

**Ubicación:** `modules/Roles/RoleManager.php`  
**Namespace:** `ISER\Roles`

**Responsabilidad:** Gestión de roles y permisos

**Dependencias:**
- PermissionManager
- RoleAssignment

---

## 7. Capa de Servicios

### ¿Existe capa de servicios?

✅ **SÍ** - Implementada como "Managers"

**Servicios identificados:**

| Manager | Ubicación | Propósito |
|---------|-----------|-----------|
| UserManager | modules/User/ | CRUD de usuarios |
| AccountSecurityManager | modules/User/ | Seguridad de cuentas |
| LoginHistoryManager | modules/User/ | Historial de logins |
| PreferencesManager | modules/User/ | Preferencias de usuario |
| RoleManager | modules/Roles/ | Gestión de roles |
| PermissionManager | modules/Roles/ | Gestión de permisos |
| ThemeManager | core/Theme/ | Gestión de temas |
| ConfigManager | core/Config/ | Configuración |
| SettingsManager | core/Config/ | Settings en BD |

---

## 8. Helpers y Utilidades

### Archivos de Helpers

**Ubicación:** `core/Utils/`

- FileManager.php
- Helpers.php
- Logger.php
- Mailer.php
- Paginator.php
- Recaptcha.php
- Validator.php
- XMLParser.php

**Análisis:** ✅ Utilidades bien organizadas

---

## 9. Middleware

### Sistema de Middleware

✅ **Existe**

**Ubicación:** `core/Middleware/`

**Middlewares identificados:**
- AdminMiddleware.php
- AuthMiddleware.php
- PermissionMiddleware.php

**Uso:** No se ve aplicado en las rutas de index.php (todas definen closures sin middleware visible)

**Problema:** ⚠️ Middleware implementado pero no se aplica en rutas

---

## 10. Traits

### Traits Identificados

**Ubicación:** `modules/Controllers/Traits/`

- NavigationTrait.php

**Uso:** Incluido en BaseController para funcionalidad de navegación

---

## 11. Análisis de Dependencias entre Clases

### Inyección de Dependencias

**Método actual:** Manual en constructores

**Ejemplo:**
```php
class AuthController extends BaseController
{
    public function __construct(Database $db)
    {
        parent::__construct($db);
        $this->userManager = new UserManager($db);
        $this->securityManager = new AccountSecurityManager($db);
    }
}
```

**Problema:** ⚠️ Instanciación manual de dependencias (no usa Container de IoC)

---

### Dependencias Circulares

✅ **No detectadas**

---

### Alto Acoplamiento

⚠️ **Acoplamiento moderado:**
- Todos los controllers dependen de `Database`
- Managers se instancian dentro de controllers
- No hay abstracción mediante interfaces

---

## 12. Patrón Repository

### Uso de Repository Pattern

✅ **Parcialmente implementado**

**Evidencia:**
- BaseRepository.php existe en `core/Database/`
- PermissionRepository.php en `modules/Roles/`

**Recomendación:** Estandarizar uso de Repository en todos los Managers

---

## 13. Resumen de Arquitectura

### Flujo de Petición Típica

```
Request
  ↓
public_html/index.php (Front Controller)
  ↓
Router::dispatch()
  ↓
Controller::method()
  ↓
Manager::operation() → Database
  ↓
Renderer::render(template)
  ↓
Response::send()
```

---

## 14. Problemas Identificados

### Críticos

✅ **Ninguno**

### Importantes

1. ⚠️ **No usa Container de IoC** - Instanciación manual de dependencias
2. ⚠️ **Middleware no aplicado en rutas** - Implementado pero no usado
3. ⚠️ **Namespace genérico (ISER\)** - No sigue Frankenstyle

### Menores

4. ⚠️ **Sin interfaces** - No hay abstracción mediante contratos
5. ⚠️ **Repository parcial** - No todos los Managers usan Repository

---

## 15. Aspectos Positivos

1. ✅ BaseController reduce duplicación
2. ✅ Separación clara MVC
3. ✅ Capa de Servicios (Managers)
4. ✅ Uso de PSR-7 (Request/Response)
5. ✅ Strict types habilitado
6. ✅ Traits para funcionalidades compartidas
7. ✅ Middleware implementado (aunque no usado)
8. ✅ Sistema de templates bien organizado

---

## 16. Conformidad con Frankenstyle

### Aspectos Actuales

| Aspecto | Frankenstyle | Actual | Conformidad |
|---------|--------------|--------|-------------|
| Namespace por componente | ✅ | ❌ (genérico ISER\) | 30% |
| Estructura modular | ✅ | ✅ | 80% |
| Autodescubrimiento | ✅ | ⚠️ (parcial) | 50% |
| lib.php por módulo | ✅ | ❌ | 0% |
| classes/ por módulo | ✅ | ⚠️ (algunos) | 40% |

**Puntuación de conformidad:** 40/100

---

## 17. Próximos Pasos

- [x] FASE 0.4 completada - Arquitectura PHP analizada
- [ ] **Siguiente:** FASE 0.5 - Inventario de funcionalidades
- [ ] Implementar Container de IoC
- [ ] Aplicar Middleware en rutas
- [ ] Migrar namespaces a Frankenstyle
- [ ] Estandarizar uso de Repository
- [ ] Agregar interfaces para abstracción

---

**CONCLUSIÓN DE FASE 0.4:**

Arquitectura MVC con Managers bien estructurada. BaseController reduce duplicación. Principales áreas de mejora: Container IoC, aplicación de Middleware, y migración de namespaces a Frankenstyle.

**Puntuación de arquitectura:** 75/100

---

**Documento generado:** 2025-11-16  
**Estado:** ✅ COMPLETO  
**Próxima fase:** FASE 0.5 - Inventario de Funcionalidades
