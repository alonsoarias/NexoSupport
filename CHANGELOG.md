# Changelog - NexoSupport

Todos los cambios notables en este proyecto serán documentados en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/),
y este proyecto adhiere a [Semantic Versioning](https://semver.org/lang/es/).

---

## [Unreleased]

### Added ✨
- Sistema de navegación completo con sidebar y topbar
- Modo oscuro (dark mode) con toggle persistente
- Breadcrumbs dinámicos en todas las rutas
- Contadores reales en badges del sidebar
- Menú desplegable de usuario con avatar
- Soporte completo responsive (desktop, tablet, móvil)
- NavigationTrait para enriquecer controllers con navegación
- Caché de contadores en sesión (5 minutos)
- Detección automática de preferencia de dark mode del sistema
- Documentación completa de performance y optimizaciones

### Changed 🔄
- Todos los controllers ahora usan NavigationTrait
- Layout por defecto cambiado de 'base' a 'app' en controllers admin
- Estructura modular de CSS (navigation, sidebar, responsive, dark-mode)
- JavaScript con event delegation y debouncing optimizado

### Fixed 🐛
- Permisos ahora se listan correctamente en `/admin/permissions`
- Arrays asociativos convertidos a indexados para Mustache
- Eliminadas referencias a columna `level` borrada de roles

### Security 🔒
- Archivos de diagnóstico movidos fuera de `public_html/`
- Scripts de test ahora solo accesibles vía CLI

---

## [1.1.0] - 2025-11-11

### Added ✨
- **Sistema de Navegación Completo**
  - Sidebar colapsable con animación fluida
  - Topbar fija con logo y menú de usuario
  - Breadcrumbs navegables
  - Highlighting automático de página activa
  - Badges con contadores reales (usuarios, roles, permisos)

- **Modo Oscuro**
  - Toggle en menú de usuario
  - Persistencia en localStorage
  - Detección de preferencia del sistema
  - Transiciones suaves entre modos
  - Tema completo para todos los componentes

- **Optimizaciones de Performance**
  - Caché de contadores en sesión (reduce 3 queries/request)
  - CSS con GPU acceleration
  - Event debouncing en resize
  - LocalStorage para preferencias
  - Lazy loading de estilos

- **Componentes Nuevos**
  - `resources/views/layouts/app.mustache`
  - `resources/views/components/navigation/topbar.mustache`
  - `resources/views/components/navigation/sidebar.mustache`
  - `resources/views/components/navigation/breadcrumbs.mustache`
  - `resources/views/components/navigation/user-menu.mustache`

- **Assets Nuevos**
  - `public_html/assets/css/navigation.css`
  - `public_html/assets/css/sidebar.css`
  - `public_html/assets/css/responsive.css`
  - `public_html/assets/css/dark-mode.css`
  - `public_html/assets/js/navigation.js`

- **Documentación**
  - `docs/NAVIGATION_IMPROVEMENT_PROPOSAL.md`
  - `docs/PROJECT_STRUCTURE_ANALYSIS.md`
  - `docs/PERFORMANCE_OPTIMIZATIONS.md`
  - `tools/diagnostics/README.md`

### Changed 🔄
- **Controllers Actualizados** (6 archivos)
  - PermissionController - Layout app + NavigationTrait
  - RoleController - Layout app + NavigationTrait
  - UserManagementController - Layout app + NavigationTrait
  - AdminController - Layout app + NavigationTrait
  - HomeController - Dashboard con NavigationTrait
  - AuthController - Mantiene layout base (sin navegación)

- **Base de Datos**
  - Eliminada columna `level` de tabla `roles`
  - Actualizados todos los queries que usaban `level`
  - Schema normalizado a 3FN

### Fixed 🐛
- **Renderizado de Permisos**
  - Arrays asociativos convertidos a indexados para Mustache
  - Permisos ahora se listan en `/admin/permissions`
  - Permisos ahora se muestran en `/admin/roles/create`

- **Queries SQL**
  - RoleManager - Eliminado `ORDER BY level`
  - UserManager - Eliminado `ORDER BY r.level`
  - PermissionManager - Eliminado `ORDER BY r.level`

### Security 🔒
- **Archivos de Diagnóstico**
  - Movidos de `public_html/` a `tools/diagnostics/`
  - Solo accesibles vía CLI
  - No expuestos vía web

### Performance ⚡
- Reducción del 67% en clicks de navegación
- Caché de contadores reduce ~3 queries/request
- CSS optimizado con GPU acceleration
- JavaScript con debouncing y event delegation
- Tiempo de carga mejorado ~50ms por página

---

## [1.0.0] - 2025-11-10

### Added ✨
- Sistema RBAC completo (Roles y Permisos)
- Gestión de usuarios con soft delete
- Base de datos normalizada (3FN)
- Theme ISER institucional
- Sistema de autenticación seguro
- Instalador automático de schema
- Controllers con patrón PSR
- Views con Mustache templates

### Changed 🔄
- Estructura de proyecto normalizada
- Migraciones a nueva arquitectura

### Security 🔒
- Protección contra SQL injection
- Validación de inputs
- Sesiones seguras
- CSRF protection

---

## Tipos de Cambios

- **Added** ✨ - Nuevas características
- **Changed** 🔄 - Cambios en funcionalidad existente
- **Deprecated** ⚠️ - Características que serán removidas
- **Removed** 🗑️ - Características removidas
- **Fixed** 🐛 - Corrección de bugs
- **Security** 🔒 - Correcciones de seguridad
- **Performance** ⚡ - Mejoras de rendimiento

---

**Nota:** Este archivo se actualiza con cada release significativo del proyecto.
