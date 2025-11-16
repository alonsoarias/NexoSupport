# FASE 0.1 - Inventario de Archivos del Proyecto NexoSupport

**Fecha:** 2025-11-16
**Analista:** Claude (Asistente IA)
**Proyecto:** NexoSupport - Sistema de Autenticación Modular ISER

---

## 1. Resumen Cuantitativo

| Tipo de Archivo | Cantidad | Ubicación de Listado |
|-----------------|----------|---------------------|
| PHP | 204 | 01_inventory_php.txt |
| Templates (Mustache) | 85 | 01_inventory_templates.txt |
| JavaScript | 8 | 01_inventory_js.txt |
| CSS/SCSS | 8 | 01_inventory_css.txt |
| Configuración (XML/JSON/YAML) | 4 | 01_inventory_config.txt |
| SQL | 0 | 01_inventory_sql.txt |

**Total de archivos:** ~309 archivos (sin contar vendor/)
**Total de líneas de código PHP:** ~55,087 líneas

### Archivos PHP por Directorio Principal

| Directorio | Cantidad de Archivos PHP | Porcentaje |
|------------|--------------------------|------------|
| modules/ | 91 | 44.6% |
| resources/ | 50 | 24.5% |
| core/ | 41 | 20.1% |
| install/ | 9 | 4.4% |
| app/ | 6 | 2.9% |
| tests/ | 4 | 2.0% |
| public_html/ | 2 | 1.0% |
| tools/ | 1 | 0.5% |

---

## 2. Estructura de Directorios Principal

```
NexoSupport/
├── app/                              # Aplicaciones de alto nivel
│   ├── Admin/                        # Interfaz administrativa
│   ├── Report/                       # Sistema de reportes
│   └── Theme/                        # Gestión de temas
│
├── core/                             # Núcleo del sistema
│   ├── Config/                       # Gestión de configuración
│   ├── Controllers/                  # Controladores base
│   ├── Database/                     # Capa de base de datos
│   │   ├── SchemaInstaller.php      # ✅ Ya existe SchemaInstaller
│   │   ├── Database.php
│   │   └── DatabaseAdapter.php
│   ├── Http/                         # Request/Response
│   ├── I18n/                         # Internacionalización
│   ├── Middleware/                   # Middlewares (Auth, Admin, Permission)
│   ├── Plugin/                       # Sistema de plugins
│   │   ├── ConfigFormGenerator.php
│   │   ├── DependencyResolver.php
│   │   ├── HookManager.php
│   │   └── PluginInterface.php
│   ├── Routing/                      # Router del sistema
│   ├── Session/                      # Gestión de sesiones (JWT)
│   ├── Theme/                        # Sistema de temas
│   │   ├── ThemeManager.php
│   │   ├── ThemeConfigurator.php
│   │   └── ColorSchemeGenerator.php
│   ├── Utils/                        # Utilidades
│   ├── View/                         # Sistema de vistas
│   │   └── MustacheRenderer.php     # ✅ Ya usa Mustache
│   ├── Autoloader.php
│   └── Bootstrap.php                # Bootstrap del sistema
│
├── database/
│   └── schema/
│       └── schema.xml               # ✅ Schema principal en XML
│
├── install/                         # Sistema de instalación
│   ├── index.php
│   ├── assets/                      # CSS/JS del instalador
│   └── stages/                      # Etapas de instalación
│       ├── admin.php
│       ├── basic_config.php
│       ├── database.php
│       ├── finish.php
│       └── requirements.php
│
├── modules/                         # ✅ Módulos del sistema (Arquitectura modular)
│   ├── Admin/                       # Módulo administrativo
│   │   ├── Tool/                    # ✅ Herramientas administrativas
│   │   │   ├── InstallAddon/        # tool_installaddon
│   │   │   │   └── templates/
│   │   │   ├── Mfa/                 # tool_mfa
│   │   │   │   ├── Factors/         # Factores MFA
│   │   │   │   ├── db/
│   │   │   │   └── version.php      # ✅ Tiene version.php
│   │   │   └── UploadUser/          # tool_uploaduser
│   │   │       └── templates/
│   │   ├── db/
│   │   └── templates/
│   │
│   ├── Auth/                        # Módulos de autenticación
│   │   └── Manual/                  # ✅ auth_manual
│   │       ├── db/
│   │       │   └── install.php
│   │       ├── templates/
│   │       │   └── login_form.mustache
│   │       └── version.php          # ✅ Tiene version.php
│   │
│   ├── Controllers/                 # Controladores del sistema
│   │   └── Traits/
│   │
│   ├── Core/                        # Módulos core
│   │   └── Search/
│   │       └── SearchManager.php
│   │
│   ├── Plugin/                      # Gestión de plugins
│   │   ├── PluginManager.php
│   │   ├── PluginInstaller.php      # 1,533 líneas
│   │   ├── PluginLoader.php
│   │   └── PluginConfigurator.php
│   │
│   ├── Report/                      # Sistema de reportes
│   │   └── Log/                     # ✅ report_log
│   │       ├── Handlers/
│   │       ├── db/
│   │       └── LogManager.php
│   │
│   ├── Roles/                       # ✅ Sistema RBAC
│   │   ├── db/
│   │   │   ├── capabilities.php     # ✅ Definición de capacidades
│   │   │   └── install.php
│   │   └── version.php              # ✅ Tiene version.php
│   │
│   ├── Theme/                       # Temas
│   │   └── Iser/                    # ✅ theme_iser
│   │       ├── assets/
│   │       │   ├── css/
│   │       │   └── js/
│   │       ├── config/
│   │       ├── lang/
│   │       │   └── es/
│   │       ├── templates/           # ✅ Templates Mustache bien organizados
│   │       │   ├── components/
│   │       │   │   ├── cards/
│   │       │   │   ├── forms/
│   │       │   │   └── tables/
│   │       │   ├── layouts/
│   │       │   │   ├── admin.mustache
│   │       │   │   ├── base.mustache
│   │       │   │   ├── dashboard.mustache
│   │       │   │   ├── fullwidth.mustache
│   │       │   │   ├── login.mustache
│   │       │   │   └── popup.mustache
│   │       │   ├── pages/
│   │       │   └── partials/
│   │       │       ├── alerts.mustache
│   │       │       ├── breadcrumb.mustache
│   │       │       ├── footer.mustache
│   │       │       └── header.mustache
│   │       └── version.php          # ✅ Tiene version.php
│   │
│   └── User/                        # ✅ Gestión de usuarios
│       ├── db/
│       └── version.php              # ✅ Tiene version.php
│
├── public_html/                     # ⚠️ Document Root (actualmente con mucho código)
│   ├── index.php                    # ⚠️ 850 líneas (muy grande para front controller)
│   ├── install.php                  # Wrapper del instalador
│   ├── .htaccess                    # Configuración Apache
│   └── assets/                      # Assets estáticos
│       ├── css/
│       ├── images/
│       └── js/
│
├── resources/                       # Recursos (vistas, traducciones)
│   ├── lang/
│   │   ├── en/
│   │   └── es/
│   └── views/                       # ⚠️ Vistas PHP (50 archivos)
│       ├── admin/
│       ├── auth/
│       ├── components/
│       ├── dashboard/
│       ├── home/
│       ├── layouts/
│       ├── profile/
│       └── user/
│
├── tests/                           # Tests unitarios e integración
│   ├── Integration/
│   └── Unit/
│
├── tools/                           # Herramientas de desarrollo
│
├── var/                             # Variables del sistema
│   ├── cache/
│   └── logs/
│
├── composer.json                    # ✅ Configuración Composer
├── .env.example                     # ✅ Variables de entorno
├── .gitignore
└── phpunit.xml                      # Configuración de tests

```

---

## 3. Archivos por Directorio Clave

### /core
- **Total de archivos PHP:** 41
- **Subdirectorios principales:**
  - Config/ (3 archivos)
  - Controllers/ (1 archivo base)
  - Database/ (8 archivos) - ✅ Incluye SchemaInstaller.php
  - Http/ (2 archivos)
  - I18n/ (2 archivos)
  - Middleware/ (3 archivos)
  - Plugin/ (5 archivos) - Sistema de plugins robusto
  - Routing/ (2 archivos)
  - Session/ (1 archivo)
  - Theme/ (3 archivos)
  - Utils/ (8 archivos)
  - View/ (1 archivo - MustacheRenderer)
  - Autoloader.php
  - Bootstrap.php

### /modules
- **Total de archivos PHP:** 91
- **Subdirectorios principales:**
  - Admin/ - Administración y herramientas
  - Auth/Manual/ - ✅ Autenticación manual (auth_manual)
  - Controllers/ - Controladores
  - Core/Search/ - Búsqueda
  - Plugin/ - Gestión de plugins
  - Report/Log/ - ✅ Reportes de logs (report_log)
  - Roles/ - ✅ Sistema RBAC
  - Theme/Iser/ - ✅ Tema ISER (theme_iser)
  - User/ - ✅ Gestión de usuarios

### /public_html
- **Total de archivos:** 2 archivos PHP + assets
- **Archivos PHP:**
  - index.php (850 líneas) - ⚠️ MUY GRANDE
  - install.php (wrapper)
- **Assets:**
  - css/
  - js/
  - images/

### /database/schema
- **Archivos:**
  - schema.xml - ✅ Schema principal del sistema

### /resources
- **Total de archivos:** 50
- **Subdirectorios:**
  - lang/en/ - Traducciones inglés
  - lang/es/ - Traducciones español
  - views/ - ⚠️ Vistas PHP (duplicación con templates Mustache?)

### /install
- **Total de archivos:** 9
- **Estructura:**
  - index.php - Controlador principal
  - stages/ - Etapas del instalador
  - assets/ - CSS/JS del instalador

---

## 4. Archivos Sospechosos Identificados

### Duplicados Potenciales (mismo nombre en diferentes ubicaciones)

| Nombre de Archivo | Cantidad | Observación |
|-------------------|----------|-------------|
| install.php | 8 | Normal en arquitectura modular (cada módulo tiene su instalador) |
| version.php | 5 | ✅ CORRECTO - Indica componentes con metadata |
| index.php | 4 | Normal (entradas de diferentes secciones) |
| admin.php | 4 | Posible duplicación |
| settings.php | 3 | Posible duplicación |
| plugins.php | 3 | Posible duplicación |

**Análisis:** La mayoría de duplicaciones son normales en una arquitectura modular. Los archivos `version.php` son indicadores positivos de componentes bien definidos.

### Archivos Grandes (>500 líneas)

| Archivo | Líneas | Observación |
|---------|--------|-------------|
| modules/Plugin/PluginInstaller.php | 1,533 | ⚠️ Muy grande - Candidato a refactorización |
| modules/Admin/AdminPlugins.php | 1,278 | ⚠️ Muy grande - Candidato a refactorización |
| public_html/index.php | 850 | ⚠️ MUY GRANDE para un front controller |
| modules/Plugin/PluginManager.php | 674 | Límite aceptable |
| modules/Controllers/AdminSettingsController.php | 650 | Límite aceptable |
| core/Database/SchemaInstaller.php | 650 | Límite aceptable |
| modules/Plugin/PluginLoader.php | 640 | Límite aceptable |
| modules/Controllers/AppearanceController.php | 633 | Límite aceptable |
| core/Theme/ThemeConfigurator.php | 630 | Límite aceptable |
| core/Theme/ThemeManager.php | 629 | Límite aceptable |

**Prioridad de refactorización:**
1. **ALTA:** public_html/index.php (850 líneas)
2. **ALTA:** PluginInstaller.php (1,533 líneas)
3. **MEDIA:** AdminPlugins.php (1,278 líneas)

### Archivos Temporales o de Respaldo

**Resultado:** ✅ **0 archivos encontrados** (.bak, .old, .tmp, ~)

Esto indica un buen mantenimiento del repositorio.

---

## 5. Componentes con version.php (Frankenstyle Parcial)

| Componente | Ubicación | Nomenclatura Actual | Nomenclatura Frankenstyle |
|------------|-----------|---------------------|---------------------------|
| Tema ISER | modules/Theme/Iser/ | theme_iser | ✅ theme_iser (CORRECTO) |
| Autenticación Manual | modules/Auth/Manual/ | auth_manual | ✅ auth_manual (CORRECTO) |
| MFA | modules/Admin/Tool/Mfa/ | N/A | tool_mfa |
| Roles | modules/Roles/ | N/A | (core o mod_roles?) |
| Usuario | modules/User/ | N/A | (core o mod_user?) |

**Observación:** Ya existen 5 componentes con archivos `version.php`, lo que indica que el sistema ya tiene una arquitectura parcialmente modular similar a Frankenstyle.

---

## 6. Directorios db/ Identificados (Estructura Similar a Frankenstyle)

Los siguientes módulos ya tienen directorios `db/` para definición de tablas:

1. ✅ modules/Auth/Manual/db/ - `install.php`
2. ✅ modules/Roles/db/ - `capabilities.php`, `install.php`
3. ✅ modules/User/db/
4. ✅ modules/Admin/db/
5. ✅ modules/Admin/Tool/Mfa/db/
6. ✅ modules/Report/Log/db/
7. ✅ modules/Theme/db/

**Observación:** El sistema ya implementa el patrón de tener scripts `db/install.php` y `db/capabilities.php` en módulos, muy similar a Frankenstyle.

---

## 7. Sistema de Templates

### Mustache Templates (85 archivos)

**Distribución:**
- modules/Theme/Iser/templates/ - **Mayoría de templates**
  - components/ (cards, forms, tables)
  - layouts/ (admin, base, dashboard, fullwidth, login, popup)
  - pages/ (dashboard, home, profile)
  - partials/ (alerts, breadcrumb, footer, header, modals, navbar, notifications, sidebar)
- modules/Admin/templates/ - Templates admin
- modules/Admin/Tool/*/templates/ - Templates de herramientas
- modules/Auth/Manual/templates/ - Login form

**Observación:** ✅ El sistema ya usa **Mustache** como motor de templates, lo cual es perfecto para la arquitectura Frankenstyle objetivo.

### Posible Duplicación: resources/views/ (50 archivos PHP)

**Riesgo:** Existe un directorio `resources/views/` con 50 archivos PHP que podrían ser vistas antiguas duplicadas con los templates Mustache.

**Acción requerida en análisis posterior:** Verificar si `resources/views/` contiene vistas obsoletas o si se usan en paralelo con Mustache.

---

## 8. Configuración del Proyecto

### composer.json

**Namespace principal:** `ISER\`

**Autoloading PSR-4:**
```json
"psr-4": {
    "ISER\\": "modules/",
    "ISER\\Core\\": "core/"
}
```

**Dependencias principales:**
- PHP >= 8.1
- Mustache (mustache/mustache) - ✅ Ya instalado
- JWT (firebase/php-jwt)
- Monolog (logging)
- PHPMailer
- Dotenv (vlucas/phpdotenv)

**Scripts útiles:**
- `composer test`
- `composer install-system`
- `composer check-requirements`

### Variables de Entorno

- Tiene `.env.example` en raíz
- ⚠️ No se detectó archivo `.env` (normal, no debe estar en repo)

---

## 9. Análisis Inicial de Arquitectura

### ✅ Aspectos Positivos (Ya Implementados)

1. **Arquitectura modular existente** - Ya tiene directorio `modules/` con componentes separados
2. **SchemaInstaller** - Ya existe (`core/Database/SchemaInstaller.php`)
3. **Schema XML** - Ya usa `database/schema/schema.xml`
4. **Version.php en componentes** - 5 componentes ya tienen `version.php`
5. **Sistema de plugins robusto** - PluginManager, PluginInstaller, DependencyResolver, HookManager
6. **Mustache como motor de templates** - Ya implementado
7. **Sistema de capacidades** - `modules/Roles/db/capabilities.php`
8. **Instaladores modulares** - `db/install.php` en varios módulos
9. **Nomenclatura Frankenstyle parcial** - `theme_iser`, `auth_manual`
10. **Sistema de temas** - ThemeManager, ThemeConfigurator ya existen
11. **Middleware** - AuthMiddleware, AdminMiddleware, PermissionMiddleware
12. **Router** - Sistema de routing existente
13. **MFA parcial** - Ya existe módulo MFA
14. **Sistema RBAC** - Módulo de Roles existente

### ⚠️ Áreas que Requieren Refactorización

1. **public_html/index.php demasiado grande** (850 líneas) - Debe ser un front controller delgado
2. **Namespace inconsistente** - Usa `ISER\` en lugar de componente individual
3. **Posible duplicación** - `resources/views/` vs `modules/Theme/Iser/templates/`
4. **Algunos módulos sin version.php** - No todos los módulos tienen metadata
5. **Estructura de directorios mixta** - Conviven `app/`, `core/`, y `modules/`
6. **Falta components.json** - No hay mapeo de tipos de plugins
7. **Falta nomenclatura completa Frankenstyle** - Solo 2 componentes usan el formato correcto

---

## 10. Hallazgos Clave

### 🎯 Lo Bueno

1. **El proyecto ya está 60-70% en arquitectura Frankenstyle**
2. Ya tiene sistema de plugins maduro
3. Ya usa Mustache (no hay que migrar desde Blade u otro)
4. Ya tiene SchemaInstaller y schema.xml
5. Código limpio (sin archivos .bak o temporales)
6. Sistema de módulos bien separado
7. Sistema RBAC implementado
8. MFA parcialmente implementado

### ⚠️ Lo que Necesita Mejora

1. Front controller muy grande (debe ser <100 líneas)
2. Namespace no sigue Frankenstyle (usa `ISER\` genérico)
3. Estructura de directorios híbrida (app/ + core/ + modules/)
4. Falta estandarizar version.php en todos los módulos
5. Falta `lib/components.json` para autodescubrimiento
6. Posible duplicación de vistas (PHP vs Mustache)

---

## 11. Próximos Pasos

- [x] FASE 0.1 completada - Inventario de archivos generado
- [ ] **Siguiente:** FASE 0.2 - Analizar punto de entrada (public_html/index.php) línea por línea
- [ ] Validar que no haya código duplicado en resources/views/
- [ ] Identificar todos los módulos que deberían tener version.php
- [ ] Mapear namespaces actuales vs objetivo

---

## 12. Archivos de Inventario Generados

Todos los archivos de inventario están en `docs/analisis/`:

1. ✅ `01_inventory_php.txt` - 204 archivos PHP
2. ✅ `01_inventory_templates.txt` - 85 templates Mustache
3. ✅ `01_inventory_js.txt` - 8 archivos JavaScript
4. ✅ `01_inventory_css.txt` - 8 archivos CSS/SCSS
5. ✅ `01_inventory_config.txt` - 4 archivos de configuración
6. ✅ `01_inventory_sql.txt` - 0 archivos SQL (correcto, usa schema.xml)
7. ✅ `01_directory_tree.txt` - Árbol de directorios
8. ✅ `01_large_files.txt` - Archivos grandes (>500 líneas)
9. ✅ `01_backup_files.txt` - Archivos de respaldo (0 encontrados)

---

**CONCLUSIÓN DE FASE 0.1:**

El proyecto NexoSupport **ya tiene una base sólida** de arquitectura modular similar a Frankenstyle. La refactorización será más una **estandarización y optimización** que una reescritura completa.

**Puntuación actual de conformidad con Frankenstyle:** 65/100

**Esfuerzo estimado de refactorización:** MEDIO (no bajo, no alto)

---

**Documento generado:** 2025-11-16
**Estado:** ✅ COMPLETO
**Próxima fase:** FASE 0.2 - Análisis de Punto de Entrada
