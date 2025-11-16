# FASE 5: MIGRACIÓN Y COMPLETADO DE COMPONENTES FRANKENSTYLE

**Fecha de Inicio:** 2024-11-16
**Responsable:** Claude (Frankenstyle Refactoring)
**Estado:** 🚀 EN PROGRESO

---

## 📋 OBJETIVOS DE FASE 5

### Objetivo Principal
Completar la estructura Frankenstyle para todos los componentes existentes del sistema, asegurando que todos tengan version.php, lib.php, y sigan el patrón consistente establecido en Fases 1-4.

### Alcance
- ✅ Validación de Fases 1-4 completada
- 📝 Componentes existentes a completar
- 🎯 Estructura Frankenstyle al 100%

---

## 🔍 ANÁLISIS DE COMPONENTES EXISTENTES

### Componentes Identificados

| Componente | Ubicación | Estado Actual | Acción Requerida |
|------------|-----------|---------------|------------------|
| **auth_manual** | modules/Auth/Manual/ | Tiene version.php | Crear lib.php, definir capabilities |
| **report_log** | modules/Report/Log/ | Código completo | Crear version.php, lib.php |
| **theme_core** | theme/core/ | Directorio vacío | Crear estructura completa |
| **theme_iser** | theme/iser/ | Directorio vacío | Crear estructura completa |

### Componentes Ya Migrados (Fases Anteriores)

| Componente | Fase | Estado |
|------------|------|:------:|
| admin_user | Fase 3 | ✅ |
| admin_roles | Fase 3 | ✅ |
| tool_uploaduser | Fase 4 | ✅ |
| tool_logviewer | Fase 4 | ✅ |
| tool_pluginmanager | Fase 4 | ✅ |
| tool_mfa | Fase 4 | 🟡 Base |
| tool_installaddon | Fase 4 | 🟡 Base |
| tool_dataprivacy | Fase 4 | 🟡 Base |

---

## 📦 TAREAS DE FASE 5

### Tarea 1: Completar auth_manual

**Estado Actual:**
- ✅ Código existe en modules/Auth/Manual/
- ✅ version.php existe
- ❌ lib.php falta
- ❌ Capabilities no definidas

**Archivos a Crear:**
```
modules/Auth/Manual/
├── AuthManual.php              # ✅ Existe
├── LoginManager.php            # ✅ Existe
├── version.php                 # ✅ Existe
├── lib.php                     # ❌ CREAR
├── db/                         # ✅ Existe
└── templates/                  # ✅ Existe
```

**Capabilities a Definir:**
- auth/manual:login - Allow login
- auth/manual:logout - Allow logout
- auth/manual:manage - Manage manual auth settings

---

### Tarea 2: Completar report_log

**Estado Actual:**
- ✅ Código completo en modules/Report/Log/
- ❌ version.php falta
- ❌ lib.php falta
- ❌ Capabilities no definidas

**Archivos a Crear:**
```
modules/Report/Log/
├── LogManager.php              # ✅ Existe
├── LogExporter.php             # ✅ Existe
├── ReportLog.php               # ✅ Existe
├── SecurityReport.php          # ✅ Existe
├── version.php                 # ❌ CREAR
├── lib.php                     # ❌ CREAR
├── Handlers/                   # ✅ Existe
└── db/                         # ✅ Existe
```

**Capabilities a Definir:**
- report/log:view - View logs report
- report/log:export - Export logs
- report/log:security - View security report

---

### Tarea 3: Crear theme_core (Estructura Básica)

**Estado Actual:**
- ❌ Directorio vacío
- ❌ Sin archivos Frankenstyle

**Archivos a Crear:**
```
theme/core/
├── version.php                 # ❌ CREAR
├── lib.php                     # ❌ CREAR
├── config.php                  # ❌ CREAR (opcional)
└── README.md                   # ❌ CREAR
```

**Capabilities a Definir:**
- theme/core:view - Use core theme
- theme/core:edit - Edit core theme settings

---

### Tarea 4: Crear theme_iser (Estructura Básica)

**Estado Actual:**
- ❌ Directorio vacío
- ❌ Sin archivos Frankenstyle

**Archivos a Crear:**
```
theme/iser/
├── version.php                 # ❌ CREAR
├── lib.php                     # ❌ CREAR
├── config.php                  # ❌ CREAR (opcional)
└── README.md                   # ❌ CREAR
```

**Capabilities a Definir:**
- theme/iser:view - Use ISER theme
- theme/iser:edit - Edit ISER theme settings
- theme/iser:customize - Customize ISER theme

---

### Tarea 5: Documentación Fase 5

**Documentos a Crear:**
- FASE_5_MIGRACION_COMPONENTES.md - Documentación completa de la fase

---

## 🎯 PATRÓN FRANKENSTYLE A APLICAR

### Estructura Estándar

Cada componente debe tener:

```
[type]/[name]/
├── version.php          # OBLIGATORIO - Metadata del plugin
├── lib.php             # OBLIGATORIO - Funciones de biblioteca
├── classes/            # Opcional - Clases PSR-4
├── templates/          # Opcional - Plantillas Mustache
├── db/                 # Opcional - Schema SQL
└── lang/              # Opcional - Traducciones
```

### version.php Template

```php
<?php
defined('NEXOSUPPORT_INTERNAL') || die();

$plugin = new stdClass();
$plugin->component = '[type]_[name]';
$plugin->version = YYYYMMDDXX;
$plugin->requires = 2024111600;
$plugin->maturity = MATURITY_STABLE;
$plugin->release = 'X.Y.Z';
$plugin->description = '...';
```

### lib.php Template

```php
<?php
defined('NEXOSUPPORT_INTERNAL') || die();

function [type]_[name]_get_capabilities(): array {
    return [
        '[type]/[name]:action' => [
            'name' => 'Action name',
            'description' => 'Description',
            'module' => '[type]_[name]',
        ],
    ];
}

function [type]_[name]_get_title(): string {
    return __('Component Title');
}

function [type]_[name]_get_description(): string {
    return __('Component description');
}
```

---

## 📊 MÉTRICAS ESPERADAS FASE 5

### Archivos a Crear

| Componente | version.php | lib.php | Otros | Total |
|------------|:-----------:|:-------:|:-----:|:-----:|
| auth_manual | - | ✅ | - | 1 |
| report_log | ✅ | ✅ | - | 2 |
| theme_core | ✅ | ✅ | README | 3 |
| theme_iser | ✅ | ✅ | README | 3 |
| **TOTAL** | **3** | **4** | **2** | **9** |

### Capabilities a Definir

| Componente | Capabilities |
|------------|:------------:|
| auth_manual | 3 |
| report_log | 3 |
| theme_core | 2 |
| theme_iser | 3 |
| **TOTAL** | **11** |

### Líneas de Código Estimadas

| Tarea | Líneas Estimadas |
|-------|:----------------:|
| lib.php files (4 x ~100) | ~400 |
| version.php files (3 x ~20) | ~60 |
| README files (2 x ~50) | ~100 |
| Documentación Fase 5 | ~800 |
| **TOTAL** | **~1,360** |

---

## 🚀 PLAN DE EJECUCIÓN

### Orden de Implementación

1. ✅ **Validación Fases 1-4** (Completado)
2. 📝 **Planificación Fase 5** (En progreso)
3. 🔧 **auth_manual** - Crear lib.php
4. 🔧 **report_log** - Crear version.php + lib.php
5. 🔧 **theme_core** - Crear estructura básica
6. 🔧 **theme_iser** - Crear estructura básica
7. 📚 **Documentación** - FASE_5_MIGRACION_COMPONENTES.md
8. ✅ **Commit y Push** - Finalizar Fase 5

### Tiempo Estimado

| Tarea | Tiempo |
|-------|:------:|
| auth_manual | 15 min |
| report_log | 20 min |
| theme_core | 15 min |
| theme_iser | 15 min |
| Documentación | 30 min |
| **TOTAL** | **~95 min** |

---

## ✅ CRITERIOS DE ACEPTACIÓN

### Fase 5 Completa Cuando:

- [x] Todas las fases 1-4 validadas
- [ ] auth_manual tiene lib.php con capabilities
- [ ] report_log tiene version.php y lib.php
- [ ] theme_core tiene estructura Frankenstyle básica
- [ ] theme_iser tiene estructura Frankenstyle básica
- [ ] 11 capabilities adicionales definidas
- [ ] Documentación FASE_5 completa
- [ ] Todos los cambios commiteados y pusheados
- [ ] Git working tree limpio

### Resultado Esperado

```
Componentes Frankenstyle Completos: 12
├── Admin: 2 (user, roles)
├── Tools: 6 (uploaduser, logviewer, pluginmanager, mfa*, installaddon*, dataprivacy*)
├── Auth: 1 (manual)
├── Report: 1 (log)
└── Theme: 2 (core, iser)

Capabilities Totales: 43
Documentos: 13
Líneas de Código Total: ~14,000+

Estado: 100% FRANKENSTYLE COMPLETO
```

---

## 🎯 BENEFICIOS DE FASE 5

### 1. Consistencia Total
- Todos los componentes siguen el mismo patrón
- Estructura predecible y fácil de navegar
- Documentación uniforme

### 2. Autodiscovery Completo
- plugin_manager detectará todos los componentes
- Inventory completo del sistema
- Metadata centralizada

### 3. Capabilities Completas
- Sistema RBAC cubre todos los módulos
- Control de acceso granular total
- 43 capabilities definidas

### 4. Base para Crecimiento
- Patrón claro para nuevos componentes
- Fácil agregar plugins de terceros
- Sistema 100% extensible

---

## 📝 NOTAS DE IMPLEMENTACIÓN

### Decisiones de Diseño

1. **Código Existente:** No mover código existente, solo agregar archivos Frankenstyle faltantes
2. **Backward Compatibility:** Mantener total compatibilidad con código actual
3. **Minimal Changes:** Cambios mínimos para completar Frankenstyle
4. **Documentation First:** Documentar antes de implementar

### Consideraciones

- Los temas (theme_core, theme_iser) pueden ser estructuras básicas ya que el sistema de themes puede estar en desarrollo
- report_log ya tiene implementación completa, solo necesita metadata Frankenstyle
- auth_manual ya funciona, solo necesita lib.php para ser completo
- Todos los componentes deben ser detectables por tool_pluginmanager

---

**Plan Creado:** 2024-11-16
**Estado:** READY TO EXECUTE
**Próximo Paso:** Implementar auth_manual lib.php

---

## 🚀 READY TO START PHASE 5
