# ESTADO ACTUAL DEL PROYECTO - VALIDACIÓN FRANKENSTYLE

**Fecha**: 2024-11-16
**Validación**: Post-limpieza legacy y public_html

---

## RESUMEN EJECUTIVO

### Estado General: ⚠️ **PARCIALMENTE COMPLETO**

**Problemas identificados:**
1. ❌ Factores MFA NO son subplugins Frankenstyle
2. ⚠️ Themes tienen estructura básica pero incompleta

---

## VALIDACIÓN POR COMPONENTE

### ✅ COMPONENTES ADMIN CORRECTOS

| Componente | Ubicación | version.php | lib.php | Estado |
|------------|-----------|:-----------:|:-------:|:------:|
| admin_user | admin/user/ | ✅ | ✅ | ✅ CORRECTO |
| admin_roles | admin/roles/ | ✅ | ✅ | ✅ CORRECTO |

### ✅ TOOLS CORRECTOS

| Componente | Ubicación | version.php | lib.php | db/ | Estado |
|------------|-----------|:-----------:|:-------:|:---:|:------:|
| tool_uploaduser | admin/tool/uploaduser/ | ✅ | ✅ | ✅ | ✅ CORRECTO |
| tool_logviewer | admin/tool/logviewer/ | ✅ | ✅ | ✅ | ✅ CORRECTO |
| tool_pluginmanager | admin/tool/pluginmanager/ | ✅ | ✅ | ✅ | ✅ CORRECTO |
| tool_mfa | admin/tool/mfa/ | ✅ | ✅ | ✅ | ✅ CORRECTO |
| tool_installaddon | admin/tool/installaddon/ | ✅ | ✅ | ✅ | ✅ CORRECTO |
| tool_dataprivacy | admin/tool/dataprivacy/ | ✅ | ✅ | ✅ | ✅ CORRECTO |

### ❌ FACTORES MFA - INCORRECTOS

**Problema:** Los factores están como clases simples en `admin/tool/mfa/classes/factors/`, NO como subplugins Frankenstyle.

**Estado actual:**
```
admin/tool/mfa/classes/factors/
├── email_factor.php          ❌ Debería ser factor_email/
├── iprange_factor.php         ❌ Debería ser factor_iprange/
├── totp_factor.php            ❌ Debería ser factor_totp/
├── sms_factor.php             ❌ Debería ser factor_sms/
└── backup_codes_factor.php    ❌ Debería ser factor_backupcodes/
```

**Estructura correcta según Frankenstyle:**
```
admin/tool/mfa/factor/
├── email/                     # factor_email
│   ├── version.php
│   ├── lib.php
│   ├── classes/
│   │   └── factor.php
│   ├── db/
│   │   └── install.php
│   ├── lang/
│   │   └── es/
│   │       └── factor_email.php
│   └── templates/
│       ├── setup.mustache
│       └── verify.mustache
│
├── iprange/                   # factor_iprange
│   ├── version.php
│   ├── lib.php
│   ├── classes/
│   │   └── factor.php
│   ├── db/
│   │   └── install.php
│   └── lang/
│
├── totp/                      # factor_totp
│   ├── version.php
│   ├── lib.php
│   ├── classes/
│   │   └── factor.php
│   ├── db/
│   │   └── install.php
│   └── lang/
│
├── sms/                       # factor_sms
│   ├── version.php
│   ├── lib.php
│   ├── classes/
│   │   └── factor.php
│   ├── db/
│   │   └── install.php
│   └── lang/
│
└── backupcodes/               # factor_backupcodes
    ├── version.php
    ├── lib.php
    ├── classes/
    │   └── factor.php
    ├── db/
    │   └── install.php
    └── lang/
```

### ⚠️ THEMES - ESTRUCTURA INCOMPLETA

**Estado actual:**
```
theme/iser/
├── version.php                ✅
├── lib.php                    ✅
├── config.php                 ✅
├── README.md                  ✅
├── scripts/                   ✅
├── styles/                    ✅
└── templates/                 ⚠️ Parcial
    └── layouts/
```

**Faltante según prompt:**
```
theme/iser/
├── classes/                   ❌ FALTA
│   └── output/
│       └── core_renderer.php
├── layout/                    ❌ FALTA (layouts PHP, no solo templates)
│   ├── base.php
│   └── admin.php
├── scss/                      ❌ FALTA (o renombrar styles/)
│   ├── preset/
│   │   └── iser.scss
│   └── iser.scss
├── pix/                       ❌ FALTA
│   ├── logo.svg
│   └── favicon.ico
└── lang/                      ❌ FALTA
    └── es/
        └── theme_iser.php
```

**Lo mismo aplica para theme/core/**

### ✅ AUTH CORRECTO

| Componente | Ubicación | version.php | lib.php | Estado |
|------------|-----------|:-----------:|:-------:|:------:|
| auth_manual | auth/manual/ | ✅ | ✅ | ✅ CORRECTO |

### ✅ REPORTS CORRECTOS

| Componente | Ubicación | version.php | lib.php | Estado |
|------------|-----------|:-----------:|:-------:|:------:|
| report_log | report/log/ | ✅ | ✅ | ✅ CORRECTO |

---

## ACCIONES REQUERIDAS

### 1. CREAR SUBPLUGINS MFA (Alta Prioridad)

Para cada factor (email, iprange, totp, sms, backupcodes):

**Tareas:**
1. ✅ Crear directorio `admin/tool/mfa/factor/[nombre]/`
2. ✅ Crear `version.php` con component = `factor_[nombre]`
3. ✅ Crear `lib.php` con capabilities
4. ✅ Mover lógica de `classes/factors/[nombre]_factor.php` a `classes/factor.php`
5. ✅ Crear `db/install.php` con tablas específicas
6. ✅ Crear `lang/es/factor_[nombre].php`
7. ✅ Crear templates Mustache (si aplica)
8. ✅ Actualizar composer.json con namespace PSR-4
9. ✅ Actualizar `lib/components.json` con tipo 'factor'

**Ejemplo version.php:**
```php
<?php
defined('NEXOSUPPORT_INTERNAL') || die();

$plugin = new stdClass();
$plugin->component = 'factor_email';
$plugin->version = 2025011600;
$plugin->requires = 2025010100;
$plugin->release = '1.0.0';
$plugin->maturity = MATURITY_STABLE;
```

**Ejemplo composer.json:**
```json
{
  "autoload": {
    "psr-4": {
      "factor_email\\": "admin/tool/mfa/factor/email/classes/",
      "factor_iprange\\": "admin/tool/mfa/factor/iprange/classes/",
      "factor_totp\\": "admin/tool/mfa/factor/totp/classes/",
      "factor_sms\\": "admin/tool/mfa/factor/sms/classes/",
      "factor_backupcodes\\": "admin/tool/mfa/factor/backupcodes/classes/"
    }
  }
}
```

### 2. COMPLETAR ESTRUCTURA THEMES (Media Prioridad)

**theme/iser/ y theme/core/:**

1. ✅ Crear `classes/output/core_renderer.php`
2. ✅ Crear `layout/*.php` (archivos PHP de layouts)
3. ✅ Renombrar `styles/` a `scss/` o crear `scss/`
4. ✅ Crear `pix/` con imágenes
5. ✅ Crear `lang/es/theme_[nombre].php`

### 3. ACTUALIZAR lib/components.json

Agregar tipo de plugin 'factor':

```json
{
  "plugintypes": {
    "auth": "auth",
    "tool": "admin/tool",
    "factor": "admin/tool/mfa/factor",    ← AGREGAR
    "theme": "theme",
    "report": "report"
  }
}
```

---

## RESUMEN DE CORRECCIONES

| Corrección | Prioridad | Archivos Afectados | Estimado |
|------------|-----------|-------------------|----------|
| Crear 5 subplugins MFA | 🔴 Alta | ~50 archivos | 2-3 horas |
| Completar themes | 🟡 Media | ~20 archivos | 1 hora |
| Actualizar composer.json | 🔴 Alta | 1 archivo | 5 min |
| Actualizar components.json | 🔴 Alta | 1 archivo | 5 min |

---

## IMPACTO

### Sin Correcciones:
- ❌ Sistema MFA NO es extensible (factores no son plugins)
- ❌ No se pueden agregar factores MFA de terceros
- ❌ Themes incompletos según estándar Frankenstyle
- ❌ Sistema no cumple con arquitectura definida

### Con Correcciones:
- ✅ Sistema MFA completamente modular
- ✅ Factores pueden agregarse/removerse como plugins
- ✅ Themes completos y extensibles
- ✅ 100% adherencia a Frankenstyle
- ✅ Sistema listo para producción y mantenimiento

---

## PRÓXIMOS PASOS

1. Crear estructura de subplugins MFA
2. Migrar lógica de factores a subplugins
3. Eliminar `admin/tool/mfa/classes/factors/`
4. Completar estructura de themes
5. Actualizar composer.json y components.json
6. Ejecutar `composer dump-autoload`
7. Validar que todo funciona
8. Commit y push

---

**Prioridad**: 🔴 ALTA - Estos cambios son críticos para cumplir con la arquitectura Frankenstyle
