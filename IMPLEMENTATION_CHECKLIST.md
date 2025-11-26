# Checklist de Implementación - Reconstrucción de Navegación NexoSupport

**Fecha de inicio**: _____________  
**Desarrollador**: _____________  
**Versión objetivo**: v1.2.0

---

## FASE 0: Pre-implementación

### Análisis y Preparación
- [ ] Leído prompt completo (agent.md)
- [ ] Entendida paleta de colores ISER (8 colores)
- [ ] Entendidas restricciones de tipografía (Verdana/Arial)
- [ ] Identificadas todas las rutas en `lib/routing/routes.php`
- [ ] Comprendido sistema RBAC existente
- [ ] Revisada estructura de plantillas Mustache
- [ ] Identificados archivos a crear (20+ archivos)
- [ ] Identificados archivos a modificar (5 archivos)
- [ ] Plan de fases establecido

### Backup y Seguridad
- [ ] Creado branch de trabajo: `feature/navigation-rebuild`
- [ ] Backup de archivos a modificar
- [ ] Commit inicial: "chore: Preparación para reconstrucción de navegación"

---

## FASE 1: Navegación Primaria (Header)

### Eliminación de UI Antigua
- [ ] Comentado HTML del navbar actual en `lib/classes/output/renderer.php`
- [ ] Comentados estilos purple/blue antiguos
- [ ] Verificado que app funciona sin estilos (temporal)
- [ ] Commit: "chore: Comentar UI antigua del header"

### Creación de Clases Backend
- [ ] Creado `lib/classes/navigation/primary_navigation.php`
- [ ] Creado `lib/classes/navigation/primary_navigation_renderer.php`
- [ ] Implementados métodos: `add_node()`, `set_active()`, `get_nodes()`
- [ ] Implementado `export_for_template()`
- [ ] Testing unitario de clases
- [ ] Commit: "feat: Clases de navegación primaria"

### Template Mustache
- [ ] Creado `templates/navigation/primary_navigation.mustache`
- [ ] Estructura HTML5 semántica (`<nav>`, `<header>`)
- [ ] Logo ISER a la izquierda
- [ ] Menú items en centro
- [ ] User menu a la derecha
- [ ] Sin estilos inline (todo en SCSS)
- [ ] Commit: "feat: Template navegación primaria"

### Estilos SCSS
- [ ] Creado `theme/core/scss/navigation/_iser-branding.scss` con variables
- [ ] Creado `theme/core/scss/navigation/_primary.scss`
- [ ] Aplicado gradiente verde (#1B9E88) a azul (#5894EF)
- [ ] Texto blanco (#FFFFFF)
- [ ] Active item con border amarillo (#FCBD05)
- [ ] Hover con overlay blanco semi-transparente
- [ ] Responsive: Desktop completo, Mobile hamburger
- [ ] SCSS compilado a CSS
- [ ] Commit: "style: Estilos SCSS navegación primaria ISER"

### JavaScript
- [ ] Creado `public_html/js/navigation/primary-navigation.js`
- [ ] Toggle mobile drawer funcional
- [ ] Close drawer on click outside
- [ ] User menu dropdown funcional
- [ ] Keyboard navigation (Tab, Enter, Esc)
- [ ] Sin errores en consola
- [ ] Commit: "feat: JavaScript navegación primaria"

### Integración
- [ ] Modificado `lib/classes/output/renderer.php` método `header()`
- [ ] Navegación primaria renderizada correctamente
- [ ] Eliminado código antiguo comentado
- [ ] Commit: "feat: Integrar navegación primaria en renderer"

### Testing FASE 1
- [ ] Logo redirige a `/` (dashboard)
- [ ] Menú items visibles según permisos
- [ ] User menu funciona (dropdown con logout)
- [ ] Mobile drawer abre/cierra
- [ ] Responsive: Desktop ✅ Tablet ✅ Mobile ✅
- [ ] Colores ISER verificados (NO purple/blue)
- [ ] Sin errores consola PHP/JS
- [ ] **Rutas probadas**: 
  - [ ] `/` ✅
  - [ ] `/login` ✅
  - [ ] `/logout` ✅
  - [ ] `/admin` ✅
- [ ] Screenshots tomados (desktop, tablet, mobile)
- [ ] Commit: "test: Validación navegación primaria completa"

---

## FASE 2: Navegación Secundaria (Context Tabs)

### Creación de Clases Backend
- [ ] Creado `lib/classes/navigation/secondary_navigation.php`
- [ ] Creado `lib/classes/navigation/secondary_navigation_renderer.php`
- [ ] Implementados factory methods: `for_admin_context()`, `for_user_context()`
- [ ] Implementado `add_tab()`, `get_visible_tabs()`, `get_more_menu_tabs()`
- [ ] Lógica de overflow (max 5 tabs visibles)
- [ ] Commit: "feat: Clases de navegación secundaria"

### Template Mustache
- [ ] Creado `templates/navigation/secondary_navigation.mustache`
- [ ] Tabs horizontales
- [ ] Indicador visual de tab activo (border-bottom)
- [ ] Menú "More" dropdown para overflow
- [ ] Responsive: Select dropdown en mobile
- [ ] Commit: "feat: Template navegación secundaria"

### Estilos SCSS
- [ ] Creado `theme/core/scss/navigation/_secondary.scss`
- [ ] Background blanco (#FFFFFF)
- [ ] Texto inactivo gris oscuro (#646363)
- [ ] Texto activo verde ISER (#1B9E88)
- [ ] Border activo verde 3px
- [ ] Hover con background gris claro
- [ ] Commit: "style: Estilos navegación secundaria ISER"

### JavaScript
- [ ] Creado `public_html/js/navigation/secondary-navigation.js`
- [ ] Detectar overflow de tabs
- [ ] Mover tabs a "More" menu dinámicamente
- [ ] Responsive resize handler
- [ ] Active tab indicator animation
- [ ] Commit: "feat: JavaScript navegación secundaria"

### Integración Contextual
- [ ] Modificado `lib/classes/output/page.php` (agregar `$secondary_nav`)
- [ ] Implementado `initialize_navigation()`
- [ ] Implementado `set_secondary_active_tab()`
- [ ] Integrado en `renderer.php` header()
- [ ] **Contexto Admin**: Tabs en `/admin`
  - [ ] Dashboard | Usuarios | Roles | Configuración | Plugins | Reportes
- [ ] **Contexto Admin Usuarios**: Tabs en `/admin/user/*`
  - [ ] Lista | Crear nuevo | Carga masiva | Permisos
- [ ] **Contexto Admin Roles**: Tabs en `/admin/roles/*`
  - [ ] Lista | Crear | Definir | Asignar
- [ ] Commit: "feat: Integrar navegación secundaria contextual"

### Testing FASE 2
- [ ] Tabs se generan según contexto de página
- [ ] Tab activo marcado correctamente
- [ ] Overflow a menú "More" funciona
- [ ] Responsive: Desktop ✅ Tablet ✅ Mobile ✅
- [ ] Colores ISER verificados
- [ ] **Rutas probadas**:
  - [ ] `/admin` (tabs: Dashboard, Usuarios, Roles, etc.) ✅
  - [ ] `/admin/users` (tabs: Lista, Crear, etc.) ✅
  - [ ] `/admin/roles` (tabs: Lista, Crear, Definir, etc.) ✅
- [ ] Screenshots tomados
- [ ] Commit: "test: Validación navegación secundaria completa"

---

## FASE 3: Sidebar Mejorado

### Refactorización de Clases
- [ ] Creado `lib/classes/navigation/sidebar_navigation_renderer.php`
- [ ] Modificado `lib/classes/navigation/navigation_node.php`
  - [ ] Agregada propiedad `$badge`
  - [ ] Agregada propiedad `$divider_after`
  - [ ] Agregado método `set_badge()`
  - [ ] Agregado método `add_divider_after()`
- [ ] Commit: "refactor: Mejorar navigation_node con badges y dividers"

### Template Mustache
- [ ] Modificado `templates/navigation/sidebar.mustache`
- [ ] Iconos Font Awesome para categorías e items
- [ ] Badges para notificaciones/contadores
- [ ] Separadores visuales entre grupos
- [ ] Animaciones smooth para collapse/expand
- [ ] Width toggle (full ↔ icons only)
- [ ] Commit: "feat: Template sidebar mejorado"

### Estilos SCSS
- [ ] Creado `theme/core/scss/navigation/_sidebar.scss`
- [ ] Background blanco (#FFFFFF)
- [ ] Border gris claro (#CFCFCF)
- [ ] Texto gris oscuro (#646363)
- [ ] Item activo: Background verde claro + border verde (#1B9E88)
- [ ] Iconos verde ISER
- [ ] Categorías: Negro (#000000), Arial Bold uppercase
- [ ] Smooth transitions (300ms)
- [ ] Commit: "style: Estilos sidebar ISER"

### JavaScript
- [ ] Modificado `public_html/js/navigation/sidebar-navigation.js`
- [ ] Collapse/expand categories con animación
- [ ] Save state to localStorage
- [ ] Auto-expand active category
- [ ] Width toggle funcional
- [ ] Badge animations (pulse para nuevos)
- [ ] Commit: "feat: JavaScript sidebar mejorado"

### Población de Estructura
- [ ] Implementada estructura completa en navigation_builder:
  - [ ] Dashboard
  - [ ] Administración (con permisos)
    - [ ] Usuarios (subcategoría)
    - [ ] Roles (subcategoría)
    - [ ] Configuración (subcategoría)
    - [ ] Plugins (subcategoría)
    - [ ] Reportes (subcategoría)
    - [ ] Mantenimiento (subcategoría)
  - [ ] Mi Perfil
    - [ ] Ver perfil
    - [ ] Editar perfil
    - [ ] Preferencias
- [ ] Verificación de capabilities en cada nodo
- [ ] Site administrator ve todo
- [ ] Commit: "feat: Estructura completa sidebar con RBAC"

### Testing FASE 3
- [ ] Categorías colapsan/expanden
- [ ] Estado persiste en localStorage
- [ ] Página activa auto-expande padres
- [ ] Página activa highlighted correctamente
- [ ] Badges se muestran
- [ ] Separadores aparecen
- [ ] Width toggle funciona
- [ ] Responsive: Desktop ✅ Tablet ✅ Mobile ✅
- [ ] Permisos RBAC verificados:
  - [ ] Site admin ve todo ✅
  - [ ] Usuario normal solo ve permitido ✅
  - [ ] Usuario sin permisos no ve admin ✅
- [ ] Screenshots tomados
- [ ] Commit: "test: Validación sidebar completa"

---

## FASE 4: Breadcrumbs + Mobile + Pulido

### Breadcrumbs
- [ ] Creado `templates/navigation/breadcrumbs.mustache`
- [ ] Creado `theme/core/scss/navigation/_breadcrumbs.scss`
- [ ] Auto-generación desde sidebar navigation
- [ ] Override manual con `$PAGE->add_breadcrumb()`
- [ ] Separador "›" entre items
- [ ] Responsive: Truncate en mobile
- [ ] Texto gris medio (#9C9C9B)
- [ ] Links verde ISER (#1B9E88)
- [ ] Integrado en `renderer.php`
- [ ] Commit: "feat: Sistema de breadcrumbs"

### Mobile Drawer
- [ ] Creado `templates/navigation/mobile_drawer.mustache`
- [ ] Creado `public_html/js/navigation/mobile-drawer.js`
- [ ] Full-screen drawer desde izquierda
- [ ] Overlay backdrop semi-transparente
- [ ] Swipe to close gesture
- [ ] Lock body scroll cuando abierto
- [ ] Animación smooth de apertura/cierre
- [ ] Commit: "feat: Mobile drawer completo"

### User Menu Dropdown
- [ ] Creado `templates/navigation/user_menu.mustache`
- [ ] Avatar circular (40px desktop, 32px mobile)
- [ ] Dropdown con opciones:
  - [ ] Ver perfil
  - [ ] Preferencias
  - [ ] Cambiar contraseña
  - [ ] Cerrar sesión
- [ ] Commit: "feat: User menu dropdown"

### Mobile Responsive
- [ ] Creado `theme/core/scss/navigation/_mobile.scss`
- [ ] Media queries para todos los breakpoints
- [ ] Desktop (>1200px): Todo visible
- [ ] Tablet (768-1200px): Sidebar overlay
- [ ] Mobile (<768px): Hamburger + drawer
- [ ] Probado en devices reales
- [ ] Commit: "style: Responsive completo"

### Optimizaciones
- [ ] CSS minificado: `navigation.min.css`
- [ ] JS minificado: Scripts concatenados
- [ ] Cache de navegación implementado
- [ ] Performance <2 segundos verificado
- [ ] Commit: "perf: Optimizaciones de navegación"

### Testing FASE 4
- [ ] Breadcrumbs generan automáticamente
- [ ] Breadcrumbs responsive
- [ ] Mobile drawer funciona perfecto
- [ ] User menu dropdown funciona
- [ ] Performance <2s en todas las páginas
- [ ] Responsive probado en:
  - [ ] Desktop 1920x1080 ✅
  - [ ] Laptop 1366x768 ✅
  - [ ] Tablet iPad 768x1024 ✅
  - [ ] Mobile iPhone SE 375x667 ✅
  - [ ] Mobile iPhone 12 390x844 ✅
- [ ] Screenshots tomados
- [ ] Commit: "test: Validación fase 4 completa"

---

## VALIDACIÓN FINAL: Todas las Rutas

### Testing Exhaustivo de Funcionalidad

**Autenticación**:
- [ ] `/login` GET - Formulario muestra ✅
- [ ] `/login` POST - Login funciona ✅
- [ ] `/logout` GET - Logout funciona ✅
- [ ] `/login/forgot_password` - Recuperación funciona ✅
- [ ] `/login/change_password` - Cambio funciona ✅

**Dashboard**:
- [ ] `/` - Dashboard carga ✅
- [ ] Usuario ve nombre/avatar ✅
- [ ] Sidebar según permisos ✅
- [ ] Breadcrumb correcto ✅

**Admin General**:
- [ ] `/admin` - Panel admin carga ✅
- [ ] Site admin ve todo ✅
- [ ] Usuario sin permisos → error ✅
- [ ] Tabs secundarios correctos ✅
- [ ] Sidebar "Administración" expandida ✅

**Gestión Usuarios**:
- [ ] `/admin/users` - Lista con paginación ✅
- [ ] Búsqueda funciona ✅
- [ ] `/admin/user/edit` - Crear nuevo ✅
- [ ] `/admin/user/edit?id=X` - Editar existente ✅
- [ ] POST `/admin/user/edit` - Guardar funciona ✅
- [ ] Validación campos ✅
- [ ] Suspender/reactivar ✅
- [ ] Links "Asignar roles" ✅

**Gestión Roles**:
- [ ] `/admin/roles` - Lista roles ✅
- [ ] `/admin/roles/edit` - Crear rol ✅
- [ ] `/admin/roles/edit?id=X` - Editar rol ✅
- [ ] POST `/admin/roles/edit` - Guardar ✅
- [ ] Roles sistema no se eliminan ✅
- [ ] `/admin/roles/define` - Matriz capabilities ✅
- [ ] `/admin/roles/define?id=X` - Definir permisos ✅
- [ ] POST `/admin/roles/define` - Guardar caps ✅
- [ ] 4 niveles permisos funcionan ✅
- [ ] `/admin/roles/assign` - Vista asignación ✅
- [ ] `/admin/roles/assign?userid=X` - Roles usuario ✅
- [ ] `/admin/roles/assign?roleid=X` - Usuarios con rol ✅
- [ ] POST `/admin/roles/assign` - Asignar/remover ✅

**Configuración y Caché**:
- [ ] `/admin/settings` - Configuración carga ✅
- [ ] POST `/admin/settings` - Guardar funciona ✅
- [ ] `/admin/cache/purge` - Página purga ✅
- [ ] Purga OPcache ✅
- [ ] Purga Mustache ✅
- [ ] Purga i18n ✅
- [ ] Purga total ✅

**Sistema Upgrade**:
- [ ] `/admin/upgrade.php` - Detecta versión ✅
- [ ] Ejecuta upgrade si necesario ✅
- [ ] Logs se muestran ✅
- [ ] Versión actualiza en BD ✅

**Seguridad**:
- [ ] `require_login()` redirige ✅
- [ ] `require_capability()` bloquea ✅
- [ ] Site admin bypasea ✅
- [ ] CSRF protection funciona ✅
- [ ] XSS protection funciona ✅

**Notificaciones**:
- [ ] Flash notifications muestran ✅
- [ ] 4 tipos tienen estilos correctos ✅
- [ ] Se eliminan después de mostrar ✅

### TOTAL RUTAS FUNCIONALES: _____ / 24

---

## VALIDACIÓN DE BRANDING ISER

### Colores
- [ ] Primaria: Gradiente verde (#1B9E88) a azul (#5894EF) ✅
- [ ] Texto primaria: Blanco (#FFFFFF) ✅
- [ ] Active: Border amarillo (#FCBD05) ✅
- [ ] Sidebar: Colores neutrales ISER ✅
- [ ] Item activo: Background verde + border verde ✅
- [ ] **CERO colores purple/blue antiguos** ✅

### Tipografías
- [ ] Navegación: Verdana/Arial ✅
- [ ] Logo: Elza (si aplica) ✅
- [ ] Tamaños consistentes ✅
- [ ] Máximo 2 tipografías total ✅

### Responsive
- [ ] Desktop >1200px perfecto ✅
- [ ] Tablet 768-1200px perfecto ✅
- [ ] Mobile <768px perfecto ✅

---

## DOCUMENTACIÓN

### Archivos Requeridos
- [ ] `docs/NAVIGATION_ARCHITECTURE.md` creado
- [ ] `docs/NAVIGATION_ISER_BRANDING.md` creado
- [ ] `docs/NAVIGATION_API.md` creado
- [ ] `docs/TESTING_REPORT.md` creado con screenshots
- [ ] Commit: "docs: Documentación completa de navegación"

---

## LIMPIEZA Y PULIDO

### Code Quality
- [ ] Sin código comentado innecesario
- [ ] Sin `console.log()` en JS
- [ ] Sin `var_dump()` en PHP
- [ ] PSR-12 compliant
- [ ] Type hints en todos los métodos
- [ ] Código comentado donde es complejo

### Performance
- [ ] CSS minificado producción
- [ ] JS minificado producción
- [ ] Cache configurado
- [ ] Sin N+1 queries

### Accesibilidad
- [ ] ARIA labels presentes
- [ ] Navegación por teclado OK
- [ ] Contrast ratio ≥4.5:1
- [ ] Screen reader friendly

### Git
- [ ] Todos los commits bien descritos
- [ ] Branch `feature/navigation-rebuild` completo
- [ ] Merge request creado
- [ ] Code review solicitado

---

## CRITERIOS DE ACEPTACIÓN FINAL

**El proyecto está COMPLETO cuando**:

- [x] UI totalmente nueva (cero restos antiguos) ✅
- [x] Branding 100% ISER ✅
- [x] 24+ rutas críticas funcionan ✅
- [x] Responsive en todos devices ✅
- [x] Documentación 4 archivos ✅
- [x] Testing report con screenshots ✅
- [x] Performance <2s ✅
- [x] Sin errores consola ✅
- [x] Accesibilidad WCAG AA ✅
- [x] Code review aprobado ✅

---

**Fecha de finalización**: _____________  
**Tiempo total**: _____ días  
**Commits totales**: _____  
**Estado**: [ ] Completo [ ] Pendiente
