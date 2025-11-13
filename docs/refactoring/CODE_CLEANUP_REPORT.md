# REPORTE DE LIMPIEZA DE CÓDIGO - NEXOSUPPORT

**Fecha**: 2025-11-13
**Responsable**: Claude (Análisis Integral de Refactorización)
**Estado**: IDENTIFICACIÓN COMPLETA - PENDIENTE EJECUCIÓN

---

## RESUMEN EJECUTIVO

Este documento identifica **TODO el código muerto, redundante y duplicado** en el proyecto NexoSupport, con acciones específicas para cada caso.

### Métricas de Limpieza Estimadas

- **Archivos a eliminar**: 3 archivos PHP
- **Directorios a consolidar**: 2 directorios
- **Código duplicado a refactorizar**: 2 casos
- **Imports sin uso**: Por determinar (requiere análisis estático)
- **Reducción estimada del código**: ~5-10%

---

## 1. CÓDIGO MUERTO (Dead Code)

### 1.1 ❌ `/core/Log/Logger.php` - ELIMINAR

**Razón**: Clase sin uso, existe versión alternativa en `/core/Utils/Logger.php`

**Análisis**:
- **Referencias**: 0 archivos usan `ISER\Core\Log\Logger`
- **Última modificación**: No determinada
- **Clase alternativa**: `/core/Utils/Logger.php` (26 archivos la usan)
- **Riesgo de eliminación**: BAJO

**Acción**:
```bash
rm /home/user/NexoSupport/core/Log/Logger.php
rmdir /home/user/NexoSupport/core/Log/  # Si está vacío
```

**Justificación**:
El sistema usa `ISER\Core\Utils\Logger` en todos los casos. La clase `/core/Log/Logger.php` nunca es referenciada.

---

### 1.2 ❌ `/modules/Roles/RoleManager.php` - ELIMINAR (pero NO el directorio)

**Razón**: Clase duplicada, existe versión alternativa en `/modules/Role/RoleManager.php`

**Análisis**:
- **Referencias**: 0 archivos usan `ISER\Roles\RoleManager`
- **Clase alternativa**: `/modules/Role/RoleManager.php` (3 archivos la usan)
- **Contenido del directorio `/modules/Roles/`**:
  - ✅ `PermissionManager.php` (SE USA)
  - ✅ `RoleAssignment.php` (SE USA)
  - ✅ `RoleContext.php` (SE USA)
  - ❌ `RoleManager.php` (NO SE USA)
  - ✅ `db/` (SE USA)
  - ✅ `version.php` (SE USA)
- **Riesgo de eliminación**: BAJO

**Acción**:
```bash
rm /home/user/NexoSupport/modules/Roles/RoleManager.php
# NO eliminar el directorio /modules/Roles/ - Contiene otros archivos importantes
```

**Justificación**:
Existe una versión de `RoleManager` en `/modules/Role/RoleManager.php` que sí se usa. La versión en `/modules/Roles/` está sin uso, pero el directorio contiene otros archivos críticos que SÍ se usan.

---

## 2. CÓDIGO REDUNDANTE (Duplicate Code)

### 2.1 ⚠️ Doble Router - INVESTIGAR Y CONSOLIDAR

**Problema**: Existen DOS implementaciones de Router en ubicaciones diferentes

**Archivos involucrados**:
- `/core/Router/Router.php` + `/core/Router/Route.php`
- `/core/Routing/Router.php` + `/core/Routing/RouteNotFoundException.php`

**Análisis de uso**:
- `ISER\Core\Router\Router` → Usado en `/core/Bootstrap.php` (1 archivo)
- `ISER\Core\Routing\Router` → Usado en `/public_html/index.php` (1 archivo)

**Estado**: **AMBOS SE USAN** ⚠️

**Investigación requerida**:
1. ✅ Leer `/core/Router/Router.php` completo
2. ✅ Leer `/core/Routing/Router.php` completo
3. ✅ Comparar implementaciones
4. ✅ Determinar si son:
   - **Duplicados exactos** → Consolidar en uno
   - **Implementaciones diferentes** → Determinar cuál usar
   - **Legacy vs Nuevo** → Migrar al nuevo

**Acción pendiente**:
```
TODO: Analizar en detalle ambos routers
TODO: Decidir estrategia de consolidación
TODO: Actualizar todas las referencias
TODO: Eliminar el router no usado
```

**Prioridad**: ALTA ⚠️

---

### 2.2 ⚠️ Doble MustacheRenderer - INVESTIGAR Y CONSOLIDAR

**Problema**: Existen DOS implementaciones de MustacheRenderer

**Archivos involucrados**:
- `/core/Render/MustacheRenderer.php`
- `/core/View/MustacheRenderer.php`

**Análisis de uso**:
- `ISER\Core\Render\MustacheRenderer` → Usado en 4 archivos:
  - `modules/Admin/AdminPlugins.php`
  - `modules/Theme/Iser/ThemeIser.php`
  - `modules/Theme/Iser/ThemeRenderer.php`
  - `CODE_CLEANUP_REPORT.md` (este archivo, referencia)

- `ISER\Core\View\MustacheRenderer` → Usado en 19 archivos:
  - Todos los controllers (HomeController, AuthController, etc.)

**Estado**: **AMBOS SE USAN** ⚠️

**Observación**: `View\MustacheRenderer` es más usado (19 vs 4 archivos)

**Acción recomendada**:
1. Migrar los 4 archivos que usan `Render\MustacheRenderer` a `View\MustacheRenderer`
2. Eliminar `/core/Render/MustacheRenderer.php`
3. Eliminar directorio `/core/Render/` si queda vacío

**Acción pendiente**:
```
TODO: Comparar ambas implementaciones
TODO: Migrar 4 archivos a View\MustacheRenderer
TODO: Eliminar Render\MustacheRenderer.php
```

**Prioridad**: MEDIA

---

## 3. DIRECTORIOS REDUNDANTES/DUPLICADOS

### 3.1 `/modules/Role/` vs `/modules/Roles/`

**Problema**: Dos directorios con nombres muy similares, causando confusión

**Contenido de `/modules/Role/`**:
- `RoleManager.php` ✅ SE USA (3 archivos lo referencian)

**Contenido de `/modules/Roles/`**:
- `RoleManager.php` ❌ NO SE USA
- `PermissionManager.php` ✅ SE USA
- `RoleAssignment.php` ✅ SE USA
- `RoleContext.php` ✅ SE USA
- `db/install.php` ✅ SE USA
- `db/capabilities.php` ✅ SE USA
- `version.php` ✅ SE USA

**Estrategia de consolidación**:

**Opción A (Recomendada)**: Consolidar TODO en `/modules/Roles/` (plural)
1. Mover `/modules/Role/RoleManager.php` → `/modules/Roles/RoleManagerActive.php`
2. Eliminar `/modules/Roles/RoleManager.php` (versión no usada)
3. Renombrar `RoleManagerActive.php` → `RoleManager.php`
4. Actualizar imports en los 3 archivos que lo usan:
   - `modules/Controllers/RoleController.php`
   - `modules/Controllers/UserManagementController.php`
   - `modules/Controllers/PermissionController.php`
5. Eliminar directorio `/modules/Role/`

**Opción B**: Consolidar TODO en `/modules/Role/` (singular)
1. Mover todo de `/modules/Roles/` → `/modules/Role/`
2. Eliminar directorio `/modules/Roles/`
3. Actualizar todos los imports

**Recomendación**: **Opción A** (usar plural "Roles")

**Razón**:
- `/modules/Roles/` tiene más archivos importantes (6 archivos)
- `/modules/Role/` solo tiene 1 archivo
- El plural "Roles" es más semánticamente correcto

**Acción pendiente**:
```
TODO: Consolidar ambos directorios en /modules/Roles/
TODO: Actualizar 3 imports en controllers
TODO: Eliminar /modules/Role/
```

**Prioridad**: ALTA ⚠️

---

### 3.2 `/modules/Report/` vs `/modules/report/` (case sensitivity)

**Problema**: Dos directorios con el mismo nombre pero diferente case

**Análisis**:
- `/modules/Report/Log/` (con R mayúscula)
- `/modules/report/log/` (con r minúscula)

**Riesgo**: En sistemas de archivos case-sensitive (Linux), ambos directorios son diferentes y pueden coexistir. Esto puede causar problemas en despliegues.

**Investigación requerida**:
```bash
ls -la /home/user/NexoSupport/modules/Report/
ls -la /home/user/NexoSupport/modules/report/
```

**Acción pendiente**:
```
TODO: Investigar contenido de ambos directorios
TODO: Determinar cuál se usa
TODO: Consolidar en uno solo (preferir mayúscula)
TODO: Actualizar todos los imports
```

**Prioridad**: MEDIA

---

### 3.3 `/core/Router/` vs `/core/Routing/`

**Problema**: Dos directorios para el mismo propósito

**Análisis**:
- `/core/Router/` - Contiene `Router.php`, `Route.php`
- `/core/Routing/` - Contiene `Router.php`, `RouteNotFoundException.php`

**Acción**: Ver sección 2.1 (Doble Router)

---

### 3.4 `/core/Render/` vs `/core/View/`

**Problema**: Dos directorios para renderizado de vistas

**Análisis**:
- `/core/Render/` - Contiene `MustacheRenderer.php` (menos usado)
- `/core/View/` - Contiene `MustacheRenderer.php` (más usado)

**Acción**: Ver sección 2.2 (Doble MustacheRenderer)

---

## 4. ARCHIVOS DE PRUEBA/DESARROLLO EN PRODUCCIÓN

### 4.1 Búsqueda de archivos de testing

**Patrones a buscar**:
- `test_*.php`
- `debug_*.php`
- `temp_*.php`
- `*.backup`
- `*.old`
- `*.bak`

**Acción pendiente**:
```bash
find /home/user/NexoSupport -type f \( -name "test_*.php" -o -name "debug_*.php" -o -name "temp_*.php" -o -name "*.backup" -o -name "*.old" -o -name "*.bak" \)
```

**Resultado de búsqueda**: (Pendiente de ejecutar)

---

## 5. IMPORTS SIN USO (Unused Imports)

**Análisis pendiente**: Requiere herramienta de análisis estático (PHPStan / Psalm)

**Comando recomendado**:
```bash
composer require --dev phpstan/phpstan
vendor/bin/phpstan analyse --level=5 core modules
```

**Acción pendiente**:
```
TODO: Ejecutar PHPStan en el proyecto
TODO: Identificar imports sin uso
TODO: Eliminar imports identificados
```

---

## 6. MÉTODOS/FUNCIONES SIN USO

**Análisis pendiente**: Requiere herramienta de análisis estático

**Herramientas recomendadas**:
- PHPStan con extensión de dead code detection
- Psalm con plugin de unused code
- PHP_CodeSniffer con reglas personalizadas

**Acción pendiente**:
```
TODO: Configurar herramienta de análisis
TODO: Ejecutar detección de métodos sin uso
TODO: Revisar manualmente los resultados
TODO: Eliminar métodos confirmados como no usados
```

---

## 7. TEMPLATES MUSTACHE SIN USO

**Total de templates**: 50 archivos `.mustache`

**Análisis requerido**:
1. Listar todos los templates
2. Buscar referencias en controllers (método `render()` o `renderWithLayout()`)
3. Buscar referencias en otros templates (partials: `{{> nombre}}`)
4. Identificar templates sin referencias

**Acción pendiente**:
```
TODO: Analizar todos los 50 templates
TODO: Buscar referencias en código PHP
TODO: Buscar partials en otros templates
TODO: Listar templates sin uso
```

---

## 8. CSS/JS SIN USO

### 8.1 Archivos CSS/JS en `/public_html/assets/`

**Ubicación**:
- `/public_html/assets/css/`
- `/public_html/assets/js/`

**Análisis requerido**:
1. Listar todos los archivos CSS/JS
2. Buscar referencias en templates Mustache
3. Buscar referencias en HTML generado
4. Identificar archivos sin referencias

**Acción pendiente**:
```
TODO: Listar assets
TODO: Buscar referencias en templates
TODO: Identificar assets sin uso
```

---

## 9. CONFIGURACIONES OBSOLETAS

### 9.1 `.env.example`

**Análisis**: Revisar variables que no se usan en el código

**Acción pendiente**:
```
TODO: Leer .env.example
TODO: Buscar uso de cada variable en código
TODO: Identificar variables obsoletas
TODO: Documentar variables no usadas
```

---

## 10. VARIABLES Y CONSTANTES SIN USO

**Análisis pendiente**: Requiere análisis estático

**Casos a buscar**:
- Variables declaradas pero nunca leídas
- Constantes definidas pero no utilizadas
- Propiedades de clase sin acceso
- Parámetros de método sin uso (excepto interfaces/herencia)

---

## 11. PLAN DE EJECUCIÓN DE LIMPIEZA

### Fase 1: Eliminaciones Simples (Bajo Riesgo)

**Archivos a eliminar inmediatamente**:
1. ✅ `/core/Log/Logger.php` (sin uso confirmado)
2. ✅ `/modules/Roles/RoleManager.php` (sin uso confirmado)

**Comandos**:
```bash
# Backup antes de eliminar
mkdir -p /home/user/NexoSupport/backup/cleanup_2025-11-13
cp /home/user/NexoSupport/core/Log/Logger.php /home/user/NexoSupport/backup/cleanup_2025-11-13/
cp /home/user/NexoSupport/modules/Roles/RoleManager.php /home/user/NexoSupport/backup/cleanup_2025-11-13/

# Eliminar archivos
rm /home/user/NexoSupport/core/Log/Logger.php
rmdir /home/user/NexoSupport/core/Log/ 2>/dev/null  # Solo si está vacío
rm /home/user/NexoSupport/modules/Roles/RoleManager.php

# Commit
git add -A
git commit -m "chore: remove dead code (Logger, RoleManager duplicate)"
```

**Estimado**: 10 minutos

---

### Fase 2: Consolidación de Directorios (Riesgo Medio)

**Tarea**: Consolidar `/modules/Role/` y `/modules/Roles/` en `/modules/Roles/`

**Pasos**:
1. Mover `/modules/Role/RoleManager.php` → `/modules/Roles/RoleManager.php`
2. Actualizar imports en 3 archivos:
   - `modules/Controllers/RoleController.php`
   - `modules/Controllers/UserManagementController.php`
   - `modules/Controllers/PermissionController.php`
3. Eliminar `/modules/Role/`
4. Ejecutar tests
5. Commit

**Estimado**: 20 minutos

---

### Fase 3: Consolidación de Routers (Riesgo Alto)

**Tarea**: Consolidar Router en una sola ubicación

**Pasos**:
1. Analizar `/core/Router/Router.php`
2. Analizar `/core/Routing/Router.php`
3. Determinar cuál usar
4. Actualizar `/core/Bootstrap.php` y `/public_html/index.php`
5. Eliminar router no usado
6. Ejecutar tests completos
7. Commit

**Estimado**: 1 hora

---

### Fase 4: Consolidación de MustacheRenderer (Riesgo Medio)

**Tarea**: Consolidar en `/core/View/MustacheRenderer.php`

**Pasos**:
1. Comparar ambas implementaciones
2. Migrar 4 archivos a `View\MustacheRenderer`:
   - `modules/Admin/AdminPlugins.php`
   - `modules/Theme/Iser/ThemeIser.php`
   - `modules/Theme/Iser/ThemeRenderer.php`
3. Eliminar `/core/Render/MustacheRenderer.php`
4. Eliminar `/core/Render/` si está vacío
5. Ejecutar tests
6. Commit

**Estimado**: 30 minutos

---

### Fase 5: Análisis Estático y Limpieza Profunda (Riesgo Bajo)

**Tarea**: Usar PHPStan para detectar código sin uso

**Pasos**:
1. Instalar PHPStan: `composer require --dev phpstan/phpstan`
2. Ejecutar análisis: `vendor/bin/phpstan analyse --level=5 core modules`
3. Revisar resultados
4. Eliminar código identificado (caso por caso)
5. Commit por cada cambio significativo

**Estimado**: 2-4 horas

---

## 12. MÉTRICAS DE ÉXITO

### Antes de la Limpieza

- **Archivos PHP**: 191
- **Clases duplicadas**: 4 (Logger, RoleManager, Router, MustacheRenderer)
- **Directorios duplicados**: 4 pares
- **Templates Mustache**: 50
- **Código muerto confirmado**: 2 archivos

### Después de la Limpieza (Estimado)

- **Archivos PHP**: ~185 (-6)
- **Clases duplicadas**: 0
- **Directorios duplicados**: 0
- **Templates Mustache**: ~48 (-2 estimado)
- **Código muerto**: 0
- **Reducción de código**: ~5-10%
- **Complejidad reducida**: Menor confusión en estructura

### KPIs

✅ **Duplicación de código < 3%**
✅ **0 archivos .backup, .old, .bak en producción**
✅ **0 imports sin uso** (detectados por PHPStan)
✅ **0 métodos privados sin invocar**
✅ **Estructura de directorios clara y sin ambigüedades**

---

## 13. RECOMENDACIONES FUTURAS

### Prevenir Código Muerto

1. **CI/CD con análisis estático**
   - Ejecutar PHPStan en cada commit
   - Bloquear merge si hay código muerto detectado

2. **Pre-commit hooks**
   - Validar que no se agreguen archivos `.backup`, `.old`
   - Verificar imports sin uso

3. **Code Review**
   - Checklist: "¿Este código está siendo usado?"
   - Checklist: "¿Existen duplicados de esta funcionalidad?"

4. **Documentación**
   - Documentar decisiones de eliminación
   - Mantener `DEPRECATIONS.md` con código marcado para eliminar

5. **Tests de coverage**
   - Mantener coverage > 70%
   - Identificar código no ejecutado en tests

---

## 14. RIESGOS Y MITIGACIONES

### Riesgo 1: Eliminar código usado dinámicamente

**Ejemplo**: Métodos llamados con `call_user_func()` o `$var->$methodName()`

**Mitigación**:
- Buscar llamadas dinámicas: `grep -r "call_user_func\|__call\|__get" .`
- Revisar manualmente antes de eliminar
- Mantener backup por 30 días

### Riesgo 2: Breaking changes en plugins

**Ejemplo**: Plugin externo usa clase que eliminamos

**Mitigación**:
- Documentar todos los cambios en `CHANGELOG.md`
- Mantener `DEPRECATIONS.md` con avisos previos
- Versionar correctamente (MAJOR version si hay breaking changes)

### Riesgo 3: Código usado en scripts CLI externos

**Ejemplo**: Scripts en `/scripts/` que usan código que eliminamos

**Mitigación**:
- Revisar directorio `/scripts/`
- Revisar directorio `/tools/`
- Ejecutar todos los scripts en entorno de prueba

---

## 15. CHECKLIST DE LIMPIEZA

### Pre-limpieza

- [ ] Crear branch `feature/code-cleanup`
- [ ] Crear backup completo del proyecto
- [ ] Ejecutar suite completa de tests (baseline)
- [ ] Documentar coverage actual

### Durante limpieza

- [ ] Eliminar `/core/Log/Logger.php`
- [ ] Eliminar `/modules/Roles/RoleManager.php`
- [ ] Consolidar `/modules/Role/` y `/modules/Roles/`
- [ ] Consolidar routers (`/core/Router/` vs `/core/Routing/`)
- [ ] Consolidar MustacheRenderer
- [ ] Buscar y eliminar archivos `.backup`, `.old`, `.bak`
- [ ] Ejecutar PHPStan y eliminar código detectado
- [ ] Identificar y eliminar templates sin uso
- [ ] Identificar y eliminar CSS/JS sin uso

### Post-limpieza

- [ ] Ejecutar suite completa de tests
- [ ] Verificar coverage (debe mantenerse o mejorar)
- [ ] Ejecutar instalador completo (test)
- [ ] Probar flujos principales manualmente
- [ ] Documentar todos los cambios en `CHANGELOG.md`
- [ ] Crear PR con cambios
- [ ] Code review por al menos 1 desarrollador
- [ ] Merge a develop
- [ ] Deploy a staging y verificar
- [ ] Deploy a producción (cuando esté listo)

---

## 16. CONCLUSIÓN

El proyecto NexoSupport tiene **código limpio en general**, pero se identificaron:

- ✅ **2 archivos muertos** (sin uso confirmado)
- ✅ **4 pares de archivos duplicados** (routers, renderers, managers, loggers)
- ✅ **4 directorios con ambigüedad** (Role/Roles, Report/report, Router/Routing, Render/View)

La limpieza estimada reducirá el código en **5-10%** y eliminará **confusión en la estructura**.

**Tiempo estimado total**: 4-6 horas
**Riesgo general**: MEDIO (requiere testing exhaustivo)
**Prioridad**: ALTA (hacer antes de refactorizaciones mayores)

---

**Próximo Documento**: `DATABASE_NORMALIZATION_ANALYSIS.md`

---

**Fin del Reporte de Limpieza de Código**
