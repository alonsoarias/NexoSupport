# FASE 0.7 - Plan de Migración a Frankenstyle

**Fecha:** 2025-11-16  
**Proyecto:** NexoSupport

---

## MAPEO: Estado Actual → Frankenstyle

### Mapeo General

| Actual | Frankenstyle | Acción |
|--------|--------------|--------|
| modules/Auth/Manual/ | auth/manual/ | ✅ MANTENER (ya correcto) |
| modules/Theme/Iser/ | theme/iser/ | ✅ MANTENER (ya correcto) |
| modules/User/ | lib/classes/user/ | MOVER a core |
| modules/Roles/ | lib/classes/role/ | MOVER a core |
| modules/Admin/Tool/UploadUser/ | admin/tool/uploaduser/ | MOVER y renombrar |
| modules/Admin/Tool/Mfa/ | admin/tool/mfa/ | MOVER y renombrar |
| modules/Report/Log/ | report/log/ | ✅ MANTENER |
| modules/Controllers/ | (Distribuir por módulo) | REFACTORIZAR |

---

## Cambios Principales Requeridos

### 1. Estructura de Directorios (Alta Prioridad)

**Crear:**
```
/lib/
  /classes/
    /user/         (de modules/User/)
    /role/         (de modules/Roles/)
    /plugin/       (de modules/Plugin/)
    /output/       (de core/View/)
    /db/           (de core/Database/)
/admin/
  /user/           (UI gestión usuarios)
  /roles/          (UI gestión roles)
  /tool/
    /uploaduser/   (de modules/Admin/Tool/UploadUser/)
    /mfa/          (de modules/Admin/Tool/Mfa/)
/login/            (de auth, pero fuera de public_html)
/user/             (perfil de usuario)
```

### 2. Namespaces (Alta Prioridad)

**Cambiar:**
```php
// De:
ISER\User\UserManager

// A:
core\user\user_manager

// De:
ISER\Controllers\AuthController

// A:
auth_manual\auth_controller
```

### 3. Front Controller (Alta Prioridad)

**Reducir `public_html/index.php` de 850 → <100 líneas:**

```php
// DESPUÉS:
<?php
define('BASE_DIR', dirname(__DIR__));
require BASE_DIR . '/vendor/autoload.php';

// Verificar instalación
if (!is_installed()) {
    header('Location: /install.php');
    exit;
}

// Bootstrap
$app = new Bootstrap(BASE_DIR);
$app->init();

// Cargar rutas desde archivos
require BASE_DIR . '/config/routes.php';

// Dispatch
$router->dispatch();
```

### 4. Sistema de Rutas (Alta Prioridad)

**Mover rutas a:**
- `/config/routes.php` (general)
- `/config/routes/admin.php` (admin)
- `/config/routes/api.php` (api)
- O mejor: cada módulo define `routes.php`

### 5. Componentes a Crear

| Componente | Ubicación | Descripción |
|------------|-----------|-------------|
| `lib/components.json` | /lib/ | Mapeo de tipos de plugins |
| `lib/setup.php` | /lib/ | Setup global |
| `lib/accesslib.php` | /lib/ | Funciones RBAC |
| Cada módulo/lib.php | En cada módulo | Funciones públicas |
| Cada módulo/version.php | En cada módulo | Metadata |

---

## Orden de Ejecución (Semanas)

### Semana 1: Preparación
- [ ] Backup completo del sistema
- [ ] Crear branch `frankenstyle-refactor`
- [ ] Crear estructura de directorios
- [ ] Actualizar composer.json (namespaces)

### Semana 2: Core
- [ ] Mover User y Roles a lib/classes/
- [ ] Crear lib/components.json
- [ ] Crear lib/setup.php, accesslib.php

### Semana 3: Front Controller y Rutas
- [ ] Extraer rutas a archivos separados
- [ ] Reducir index.php a <100 líneas
- [ ] Implementar Container IoC (opcional)

### Semana 4: Módulos
- [ ] Refactorizar auth_manual
- [ ] Refactorizar tool_uploaduser
- [ ] Refactorizar tool_mfa
- [ ] Refactorizar report_log

### Semana 5: Temas
- [ ] Estandarizar theme_core
- [ ] Actualizar theme_iser

### Semana 6: Testing y Validación
- [ ] Tests de regresión
- [ ] Validación de funcionalidades
- [ ] Documentación

---

## Riesgos

| Riesgo | Probabilidad | Impacto | Mitigación |
|--------|--------------|---------|------------|
| Romper funcionalidad existente | Media | Alto | Tests exhaustivos |
| Problemas con autoloading | Baja | Alto | Validación incremental |
| Tiempo mayor al estimado | Alta | Medio | Plan de rollback |

---

**CONCLUSIÓN:** Migración factible en 6 semanas con riesgo MEDIO.

**Estado:** ✅ COMPLETO  
**Próxima fase:** FASE 0.8 - Consolidación
