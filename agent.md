# Prompt para Claude Code: Reconstrucción de Navegación NexoSupport tipo Moodle

## ⚠️ INSTRUCCIÓN CRÍTICA: RECONSTRUCCIÓN TOTAL

**IMPORTANTE**: Este proyecto requiere **ELIMINAR COMPLETAMENTE** la interfaz de usuario existente y **CREAR UNA TOTALMENTE NUEVA** desde cero, siguiendo la arquitectura de Moodle 4.x.

### Lo que SE DEBE ELIMINAR:
- ❌ **Todos los estilos CSS actuales de navegación** (gradientes purple/blue actuales)
- ❌ **Navbar actual del header** en `renderer.php`
- ❌ **Sistema de breadcrumbs actual** (reemplazar completamente)
- ❌ **Cualquier HTML/CSS que no cumpla con branding ISER**
- ❌ **Templates Mustache de navegación existentes** (crear nuevos)
- ❌ **JavaScript de navegación actual** (si existe)

### Lo que SE DEBE MANTENER (solo backend):
- ✅ **Sistema RBAC** (roles, capabilities, contexts) - funciona correctamente
- ✅ **Base de datos y PDO** - no tocar
- ✅ **Sistema de routing** - mantener rutas existentes
- ✅ **Clases de navegación** (`navigation_node.php`, `navigation_tree.php`) - refactorizar pero mantener lógica
- ✅ **Sistema de sesiones y autenticación** - funciona correctamente
- ✅ **Plugin manager y autoloader** - no tocar
- ✅ **Sistema de i18n** - mantener

### RECONSTRUCCIÓN TOTAL SIGNIFICA:
1. **Eliminar** todo el HTML/CSS del header actual en `lib/classes/output/renderer.php`
2. **Crear** nueva estructura HTML5 semántica desde cero
3. **Aplicar** SOLO colores y tipografías ISER (ningún otro estilo)
4. **Implementar** navegación primaria + secundaria + sidebar + breadcrumbs NUEVOS
5. **Reemplazar** todos los templates Mustache de navegación
6. **Crear** nuevo JavaScript para todas las interacciones
7. **Asegurar** que TODAS las rutas y funcionalidades existentes sigan funcionando

### VALIDACIÓN OBLIGATORIA:
Al finalizar, TODAS estas rutas deben ser funcionales:
- ✅ `/` - Dashboard
- ✅ `/login` - Login funcional
- ✅ `/logout` - Logout funcional
- ✅ `/admin` - Panel admin (solo con permisos)
- ✅ `/admin/users` - Lista usuarios
- ✅ `/admin/user/edit` - Crear/editar usuario
- ✅ `/admin/roles` - Lista roles
- ✅ `/admin/roles/edit` - Crear/editar rol
- ✅ `/admin/roles/define` - Definir capabilities
- ✅ `/admin/roles/assign` - Asignar roles
- ✅ `/admin/settings` - Configuración
- ✅ `/admin/cache/purge` - Purgar caché
- ✅ Todas las demás rutas existentes en `lib/routing/routes.php`

### TABLA DE RUTAS CRÍTICAS A VALIDAR:

| Ruta | Método | Descripción | Validar | Permisos Requeridos |
|------|--------|-------------|---------|---------------------|
| `/` | GET | Dashboard principal | ✅ Login requerido | Autenticado |
| `/login` | GET | Formulario login | ✅ Sin login | Público |
| `/login` | POST | Procesar login | ✅ Credenciales | Público |
| `/logout` | GET | Cerrar sesión | ✅ Sesión activa | Autenticado |
| `/admin` | GET | Panel administración | ✅ Permisos admin | `nexosupport/admin:view` |
| `/admin/users` | GET | Lista de usuarios | ✅ Permisos | `nexosupport/admin:manageusers` |
| `/admin/user/edit` | GET | Formulario crear usuario | ✅ Permisos | `nexosupport/admin:manageusers` |
| `/admin/user/edit?id=X` | GET | Formulario editar usuario | ✅ Permisos + user existe | `nexosupport/admin:manageusers` |
| `/admin/user/edit` | POST | Guardar usuario (nuevo/editar) | ✅ Validación + sesskey | `nexosupport/admin:manageusers` |
| `/admin/roles` | GET | Lista de roles | ✅ Permisos | `nexosupport/admin:manageroles` |
| `/admin/roles/edit` | GET | Formulario crear rol | ✅ Permisos | `nexosupport/admin:manageroles` |
| `/admin/roles/edit?id=X` | GET | Formulario editar rol | ✅ Permisos + rol existe | `nexosupport/admin:manageroles` |
| `/admin/roles/edit` | POST | Guardar rol | ✅ Validación + sesskey | `nexosupport/admin:manageroles` |
| `/admin/roles/define` | GET | Matriz de capabilities | ✅ Permisos | `nexosupport/admin:manageroles` |
| `/admin/roles/define?id=X` | GET | Definir caps para rol | ✅ Permisos + rol existe | `nexosupport/admin:manageroles` |
| `/admin/roles/define` | POST | Guardar capabilities | ✅ Validación + sesskey | `nexosupport/admin:manageroles` |
| `/admin/roles/assign` | GET | Vista de asignación | ✅ Permisos | `nexosupport/admin:assignroles` |
| `/admin/roles/assign?userid=X` | GET | Ver roles de usuario | ✅ Permisos + user existe | `nexosupport/admin:assignroles` |
| `/admin/roles/assign?roleid=X` | GET | Ver usuarios con rol | ✅ Permisos + rol existe | `nexosupport/admin:assignroles` |
| `/admin/roles/assign` | POST | Asignar/remover rol | ✅ Validación + sesskey | `nexosupport/admin:assignroles` |
| `/admin/settings` | GET | Configuración general | ✅ Permisos | `nexosupport/admin:managesettings` |
| `/admin/settings` | POST | Guardar configuración | ✅ Validación + sesskey | `nexosupport/admin:managesettings` |
| `/admin/cache/purge` | GET | Página purgar caché | ✅ Site admin | Site Administrator |
| `/admin/cache/purge?type=X` | GET | Purgar tipo específico | ✅ Site admin | Site Administrator |

**INSTRUCCIÓN**: Después de completar cada fase de implementación, el desarrollador DEBE marcar en esta tabla qué rutas ha probado y verificado que funcionan. Si alguna ruta falla, DEBE depurarla ANTES de continuar con la siguiente fase.

**CRITERIO DE ÉXITO**: La aplicación debe verse y comportarse como Moodle 4.x con branding ISER, pero TODAS las funcionalidades actuales deben seguir operando sin errores.

### MÉTODO DE TRABAJO OBLIGATORIO:

**PASO 1: ANALIZAR** antes de tocar código
- Leer TODO el prompt
- Entender estructura actual del proyecto
- Identificar qué se elimina vs qué se mantiene
- Planificar fases de implementación

**PASO 2: IMPLEMENTAR** fase por fase
- FASE 1: Navegación primaria
- FASE 2: Navegación secundaria
- FASE 3: Sidebar mejorado
- FASE 4: Breadcrumbs + mobile + pulido

**PASO 3: VALIDAR** después de cada fase
- Ejecutar checklist de rutas
- Probar en navegador real
- Verificar colores ISER
- Verificar responsive
- Documentar testing

**PASO 4: ITERAR** si algo no funciona
- Depurar errores inmediatamente
- NO pasar a siguiente fase con errores pendientes
- Documentar soluciones aplicadas

### LISTA DE VERIFICACIÓN PRE-INICIO:

Antes de escribir una sola línea de código, Claude Code debe:
- [ ] Leer prompt completo (todas las 1,200+ líneas)
- [ ] Entender paleta de colores ISER (8 colores primarios/secundarios/neutrales)
- [ ] Comprender restricciones de tipografía (Verdana/Arial para web)
- [ ] Identificar todas las rutas existentes en `lib/routing/routes.php`
- [ ] Comprender sistema RBAC (capabilities, contexts, roles)
- [ ] Entender estructura de plantillas Mustache
- [ ] Conocer ubicación de archivos a crear/modificar
- [ ] Tener claro el flujo: $PAGE → navigation → renderer → template

---

## CONTEXTO DEL PROYECTO

### Sistema Actual: NexoSupport v1.1.10
NexoSupport es un sistema de autenticación y gestión PHP 8.1+ con arquitectura Frankenstyle (inspirada en Moodle). Actualmente implementa:

#### Estructura de Navegación Existente (Básica):
- **Archivo principal**: `lib/classes/navigation/navigation_node.php` (537 líneas)
- **Árbol de navegación**: `lib/classes/navigation/navigation_tree.php` (464 líneas)
- **Constructor**: `lib/classes/navigation/navigation_builder.php` (250 líneas)
- **Renderizador**: `lib/classes/navigation/navigation_renderer.php` (122 líneas)
- **Template Mustache**: `templates/navigation/sidebar.mustache`
- **Gestor heredado**: `lib/classes/navigation/nav_manager.php` (compatibilidad v1.1.8)

#### Características Actuales:
- Sistema de nodos jerárquicos con permisos
- Categorías colapsables
- Iconos con conversión emoji → Font Awesome
- Detección automática de página activa
- Breadcrumbs automáticos
- Separadores visuales
- Cache de i18n para strings de idioma

#### Sistema de Output/Rendering:
- **Renderer global**: `lib/classes/output/renderer.php`
  - `header()` - Header completo con navbar, breadcrumbs, notificaciones
  - `footer()` - Footer con inyección de CSS/JS
  - `notification()` - Sistema de notificaciones flash
- **Page manager**: `lib/classes/output/page.php`
  - `set_title()`, `set_heading()`
  - `add_breadcrumb()` - Sistema breadcrumb
  - `add_css()`, `add_js()` - Gestión de recursos

#### Sistema RBAC:
- Roles con capabilities contextuales
- `require_capability()` para protección de páginas
- `has_capability()` para verificación de permisos
- Site administrators con acceso total (bypass RBAC)

#### Base de Datos:
- PDO con parámetros preparados
- Métodos avanzados: `get_records_sql()`, `get_records_select()`
- Sistema de prefijos de tabla: `{tablename}` → tabla real

---

## OBJETIVO

Reconstruir **COMPLETAMENTE** el sistema de navegación de NexoSupport siguiendo la arquitectura de Moodle 4.x, con las siguientes características:

### 1. Estructura de Navegación Tipo Moodle

#### A. Navegación Primaria (Header/Top Bar)
- **Ubicación**: Barra superior horizontal
- **Elementos**:
  - Logo institucional (izquierda)
  - Menú principal de sitio (Home, Dashboard, Courses, etc.)
  - Icono de notificaciones
  - Menú de usuario con avatar (derecha)
  - Selector de idioma (si aplica)
- **Responsive**: En móvil se convierte en hamburger menu drawer

#### B. Navegación Secundaria (Context Tabs)
- **Ubicación**: Debajo del header, en el área de contenido
- **Función**: Tabs contextuales según la página actual
- **Ejemplos**:
  - En `/admin`: Usuarios | Roles | Configuración | Plugins | Reportes
  - En `/admin/users`: Lista | Crear nuevo | Carga masiva | Permisos
  - En perfil de usuario: Ver perfil | Editar | Preferencias | Roles
- **Características**:
  - Máximo 5 tabs visibles
  - Tab activo con indicador visual (borde inferior, color)
  - Tabs extra en menú "Más" (More)
  - Auto-scroll horizontal si es necesario

#### C. Navegación Lateral (Sidebar/Drawer)
- **Ubicación**: Sidebar izquierdo expandible/colapsable
- **Estructura**: Árbol de navegación jerárquico
- **Características**:
  - Categorías colapsables con iconos
  - Indicador de página activa (highlight)
  - Iconos Font Awesome para cada item
  - Badges para notificaciones/contadores
  - Auto-expand de categoría activa
  - Smooth transitions en collapse/expand
  - Persistencia de estado (localStorage)
- **Secciones principales**:
  1. **Dashboard** (🏠)
  2. **Administración del Sitio** (⚙️) - solo si `has_capability('nexosupport/admin:*')`
     - Usuarios y permisos
     - Roles
     - Configuración
     - Plugins
     - Reportes
     - Caché
  3. **Mi Perfil** (👤)
     - Ver perfil
     - Editar perfil
     - Preferencias
     - Cambiar contraseña
  4. **Plugins** (🔌) - dinámico según plugins instalados

#### D. Breadcrumbs (Migas de Pan)
- **Ubicación**: Parte superior del área de contenido, debajo de tabs secundarios
- **Formato**: Home > Administración > Usuarios > Editar usuario
- **Características**:
  - Links clickeables (excepto último item)
  - Separador visual (›)
  - Responsive: En móvil puede truncar items intermedios

---

### 2. Implementación Técnica Requerida

#### A. Sistema de Nodos Mejorado

**Archivo**: `lib/classes/navigation/navigation_node.php` (REFACTORIZAR)

```php
class navigation_node {
    // Propiedades existentes +
    public string $badge = '';           // Badge texto/número
    public bool $divider_after = false;  // Separador después del nodo
    public array $data_attrs = [];       // Atributos data-* para HTML
    public int $sort_order = 0;          // Orden de visualización
    public bool $force_into_more = false; // Forzar a menú "More"
    
    // Métodos nuevos
    public function set_badge(string $badge, string $type = 'default'): self;
    public function set_sort_order(int $order): self;
    public function add_divider_after(): self;
    public function to_secondary_nav(): array; // Para tabs secundarios
}
```

#### B. Clase de Navegación Primaria (NUEVA)

**Archivo**: `lib/classes/navigation/primary_navigation.php`

```php
namespace core\navigation;

class primary_navigation {
    protected array $nodes = [];
    protected string $active_key = '';
    
    public function __construct();
    public function add_node(navigation_node $node): self;
    public function set_active(string $key): self;
    public function get_nodes(): array;
    public function export_for_template(): array;
    
    // Populate con items del sistema
    protected function populate_site_navigation(): void;
}
```

#### C. Clase de Navegación Secundaria (NUEVA)

**Archivo**: `lib/classes/navigation/secondary_navigation.php`

```php
namespace core\navigation;

class secondary_navigation {
    protected array $tabs = [];
    protected string $context = ''; // 'system', 'course', 'user', etc.
    protected int $max_visible_tabs = 5;
    
    public function __construct(string $context);
    public function add_tab(navigation_node $node): self;
    public function get_visible_tabs(): array;
    public function get_more_menu_tabs(): array;
    public function export_for_template(): array;
    
    // Factory methods contextuales
    public static function for_admin_context(): self;
    public static function for_user_context(int $userid): self;
    public static function for_system_context(): self;
}
```

#### D. Integración con $PAGE

**Archivo**: `lib/classes/output/page.php` (MODIFICAR)

Agregar propiedades:
```php
class page {
    // Existentes...
    public array $breadcrumbs = [];
    
    // NUEVAS
    public ?primary_navigation $primary_nav = null;
    public ?secondary_navigation $secondary_nav = null;
    public navigation_tree $sidebar_nav;
    
    // Métodos nuevos
    public function initialize_navigation(): void {
        $this->primary_nav = new primary_navigation();
        $this->secondary_nav = secondary_navigation::for_context($this->context);
        $this->sidebar_nav = $this->build_sidebar_navigation();
    }
    
    protected function build_sidebar_navigation(): navigation_tree {
        // Construir árbol completo del sidebar
    }
    
    public function set_secondary_active_tab(string $key): self;
}
```

#### E. Renderizadores Especializados

**Archivos NUEVOS**:

1. **`lib/classes/navigation/primary_navigation_renderer.php`**
```php
namespace core\navigation;
use core\output\renderer_base;

class primary_navigation_renderer extends renderer_base {
    public function render(primary_navigation $nav): string;
    protected function render_desktop_view(array $nodes): string;
    protected function render_mobile_drawer(array $nodes): string;
}
```

2. **`lib/classes/navigation/secondary_navigation_renderer.php`**
```php
namespace core\navigation;
use core\output\renderer_base;

class secondary_navigation_renderer extends renderer_base {
    public function render(secondary_navigation $nav): string;
    protected function render_tabs(array $tabs): string;
    protected function render_more_menu(array $tabs): string;
}
```

3. **`lib/classes/navigation/sidebar_navigation_renderer.php`** (refactorizar existente)

#### F. Templates Mustache

**NUEVOS TEMPLATES** (crear en `templates/navigation/`):

1. **`primary_navigation.mustache`**
   - Header horizontal responsive
   - Logo, menú items, user menu, notifications

2. **`secondary_navigation.mustache`**
   - Tabs horizontales con indicador activo
   - Menú "More" dropdown

3. **`sidebar_navigation.mustache`** (MEJORAR existente)
   - Árbol colapsable con smooth transitions
   - Badges, iconos, separadores
   - Estados persistentes

4. **`breadcrumbs.mustache`**
   - Migas de pan con separadores
   - Responsive

5. **`user_menu.mustache`**
   - Dropdown con avatar
   - Links: Perfil, Preferencias, Logout

6. **`mobile_drawer.mustache`**
   - Navegación móvil full-screen
   - Hamburger toggle
   - Overlay

#### G. Integración con Renderer Global

**Archivo**: `lib/classes/output/renderer.php` (MODIFICAR método `header()`)

```php
public function header(): string {
    global $PAGE, $USER;
    
    // Inicializar navegación si no está inicializada
    if (!$PAGE->primary_nav) {
        $PAGE->initialize_navigation();
    }
    
    $html = $this->render_html_start();
    
    // Renderizar navegación primaria
    $primary_renderer = new \core\navigation\primary_navigation_renderer($PAGE);
    $html .= $primary_renderer->render($PAGE->primary_nav);
    
    // Iniciar layout flex (sidebar + content)
    $html .= '<div class="nexo-main-layout">';
    
    // Sidebar izquierdo
    $sidebar_renderer = new \core\navigation\sidebar_navigation_renderer($PAGE);
    $html .= '<aside class="nexo-sidebar">';
    $html .= $sidebar_renderer->render($PAGE->sidebar_nav);
    $html .= '</aside>';
    
    // Área de contenido
    $html .= '<main class="nexo-content">';
    
    // Navegación secundaria (tabs)
    if ($PAGE->secondary_nav) {
        $secondary_renderer = new \core\navigation\secondary_navigation_renderer($PAGE);
        $html .= $secondary_renderer->render($PAGE->secondary_nav);
    }
    
    // Breadcrumbs
    $html .= $this->render_breadcrumbs();
    
    // Notificaciones
    $html .= $this->render_notifications();
    
    return $html;
}
```

---

### 3. Branding ISER

#### A. Paleta de Colores

**Colores Primarios** (usar en navegación primaria, elementos destacados):
```scss
$iser-verde: #1B9E88;     // RGB(27, 158, 136) - Color principal
$iser-amarillo: #FCBD05;  // RGB(252, 189, 7) - Acentos/highlights
$iser-rojo: #EB4335;      // RGB(235, 67, 53) - Alertas/danger
$iser-blanco: #FFFFFF;    // RGB(255, 255, 255) - Backgrounds
```

**Colores Secundarios** (máximo 30% del diseño, usar para variedad):
```scss
$iser-naranja: #E27C32;   // RGB(226, 124, 50)
$iser-lima: #CFDA4B;      // RGB(207, 218, 75)
$iser-azul: #5894EF;      // RGB(88, 148, 239)
$iser-magenta: #C82260;   // RGB(200, 34, 96)
```

**Colores Neutrales** (textos, bordes, backgrounds secundarios):
```scss
$iser-gris-claro: #CFCFCF;   // RGB(207, 207, 207)
$iser-gris-medio: #9C9C9B;   // RGB(156, 156, 155)
$iser-gris-oscuro: #646363;  // RGB(100, 100, 99)
$iser-negro: #000000;        // RGB(0, 0, 0)
```

#### B. Tipografías

**Aplicar según contexto**:
- **Logotipo**: Elza (Light, Medium, Bold) - solo en logo
- **Navegación y UI**: Verdana o Arial (Regular, Bold)
- **Contenido web**: Verdana o Arial (Regular, Italic, Bold, Bold Italic)
- **Certificados/Diplomas**: Sitka (Regular, Italic) - NO aplica a navegación
- **Piezas gráficas adicionales**: Myriad Pro (si se necesita)

**Restricción importante**: Máximo 2 tipografías por diseño, una siempre debe ser Elza (pero solo para logo).

#### C. Aplicación del Branding en Navegación

**Navegación Primaria (Header)**:
- Background: Gradiente de `$iser-verde` (#1B9E88) a `$iser-azul` (#5894EF)
- Texto: `$iser-blanco` (#FFFFFF)
- Hover: Overlay semi-transparente blanco (10% opacity)
- Active: Border-bottom `$iser-amarillo` (#FCBD05) 3px solid
- Tipografía: Verdana Bold 14px

**Navegación Secundaria (Tabs)**:
- Background: `$iser-blanco` (#FFFFFF)
- Texto inactivo: `$iser-gris-oscuro` (#646363)
- Texto activo: `$iser-verde` (#1B9E88)
- Border activo: `$iser-verde` (#1B9E88) 3px solid (bottom)
- Hover: Background `$iser-gris-claro` (#CFCFCF) con 30% opacity
- Tipografía: Arial Bold 13px

**Sidebar (Navegación Lateral)**:
- Background: `$iser-blanco` (#FFFFFF)
- Border: `$iser-gris-claro` (#CFCFCF) 1px solid
- Texto: `$iser-gris-oscuro` (#646363)
- Categorías (headers): `$iser-negro` (#000000), Arial Bold 12px uppercase
- Items hover: Background `$iser-gris-claro` (#CFCFCF) con 20% opacity
- Item activo: Background `$iser-verde` (#1B9E88) con 10% opacity, Border-left `$iser-verde` 4px solid
- Iconos: `$iser-verde` (#1B9E88)
- Tipografía: Verdana Regular 13px

**Breadcrumbs**:
- Texto: `$iser-gris-medio` (#9C9C9B)
- Links: `$iser-verde` (#1B9E88)
- Separador: `$iser-gris-medio` (#9C9C9B) - usar "›"
- Item actual: `$iser-gris-oscuro` (#646363), sin link
- Tipografía: Arial Regular 12px

**Badges/Notificaciones**:
- Info: `$iser-azul` (#5894EF)
- Success: `$iser-verde` (#1B9E88)
- Warning: `$iser-amarillo` (#FCBD05) con texto `$iser-negro`
- Error: `$iser-rojo` (#EB4335)

---

### 4. Arquitectura de Archivos a Crear/Modificar

#### NUEVOS ARCHIVOS A CREAR:

```
lib/classes/navigation/
├── primary_navigation.php                    (NUEVO)
├── secondary_navigation.php                  (NUEVO)
├── primary_navigation_renderer.php           (NUEVO)
├── secondary_navigation_renderer.php         (NUEVO)
└── sidebar_navigation_renderer.php           (NUEVO - refactor del renderer actual)

templates/navigation/
├── primary_navigation.mustache               (NUEVO)
├── secondary_navigation.mustache             (NUEVO)
├── sidebar_navigation.mustache               (MEJORAR EXISTENTE)
├── breadcrumbs.mustache                      (NUEVO)
├── user_menu.mustache                        (NUEVO)
├── mobile_drawer.mustache                    (NUEVO)
└── notification_badge.mustache               (NUEVO)

theme/core/scss/
└── navigation/
    ├── _primary.scss                         (NUEVO)
    ├── _secondary.scss                       (NUEVO)
    ├── _sidebar.scss                         (NUEVO - refactor)
    ├── _breadcrumbs.scss                     (NUEVO)
    ├── _mobile.scss                          (NUEVO)
    └── _iser-branding.scss                   (NUEVO - variables de colores)

public_html/js/
└── navigation/
    ├── primary-navigation.js                 (NUEVO)
    ├── secondary-navigation.js               (NUEVO)
    ├── sidebar-navigation.js                 (MEJORAR EXISTENTE)
    └── mobile-drawer.js                      (NUEVO)
```

#### ARCHIVOS A MODIFICAR:

```
lib/classes/output/
├── page.php                                  (AGREGAR propiedades navigation)
└── renderer.php                              (MODIFICAR header() y footer())

lib/classes/navigation/
├── navigation_node.php                       (AGREGAR propiedades badge, divider, etc.)
├── navigation_tree.php                       (MEJORAR métodos de filtrado)
└── navigation_builder.php                    (AGREGAR builders para cada tipo)

lib/setup.php                                 (INICIALIZAR navegación automática)

admin/*.php                                   (AGREGAR configuración de tabs secundarios)
admin/user/*.php                              (CONFIGURAR navegación contextual)
admin/roles/*.php                             (CONFIGURAR navegación contextual)
```

---

### 5. Especificaciones de Funcionalidad

#### A. Navegación Primaria

**Desktop (>768px)**:
- Barra horizontal fija en top (position: sticky, top: 0)
- Logo ISER a la izquierda (120px ancho, link a dashboard)
- Menú horizontal centrado con items:
  - Dashboard
  - Administración (solo si `is_siteadmin()` o `has_capability('nexosupport/admin:view')`)
  - Mi Perfil
- Usuario menu a la derecha:
  - Avatar circular (40px)
  - Nombre del usuario
  - Dropdown con: Ver perfil, Preferencias, Cambiar contraseña, Cerrar sesión
- Icono de notificaciones (si hay sistema de notificaciones)

**Mobile (<768px)**:
- Hamburger icon (☰) a la izquierda
- Logo ISER centrado (80px)
- Avatar usuario a la derecha (solo icono, 32px)
- Al tocar hamburger: Drawer full-width desde la izquierda
- Drawer incluye: Logo, menú completo, user info

**Comportamiento**:
- Scroll down: Header se mantiene visible (sticky)
- Active item: Marcado con border-bottom amarillo ISER
- Hover: Overlay blanco semi-transparente
- Transitions suaves (300ms ease)

#### B. Navegación Secundaria

**Ubicación**: Justo debajo del header primario, dentro del área de contenido

**Funcionamiento**:
- Se puebla automáticamente según el contexto de `$PAGE`
- Detección de contexto:
  ```php
  // En admin/index.php
  $PAGE->set_context(context_system::instance());
  $PAGE->set_url('/admin');
  $PAGE->secondary_nav = secondary_navigation::for_admin_context();
  $PAGE->set_secondary_active_tab('dashboard');
  ```

**Tabs contextuales por área**:

1. **Contexto: Admin General** (`/admin`)
   - Dashboard | Usuarios | Roles | Configuración | Plugins | Reportes | Caché

2. **Contexto: Admin Usuarios** (`/admin/user/*`)
   - Lista de usuarios | Crear nuevo | Carga masiva | Permisos

3. **Contexto: Admin Roles** (`/admin/roles/*`)
   - Lista de roles | Crear nuevo | Definir permisos | Asignar roles

4. **Contexto: Usuario** (`/user/profile.php?id=X`)
   - Ver perfil | Editar perfil | Preferencias | Seguridad | Roles asignados

**Responsive**:
- Desktop: Tabs horizontales
- Tablet: Primeros 4 tabs + "More" menu
- Mobile: Dropdown selector (similar a select)

#### C. Sidebar (Navegación Lateral)

**Estructura jerárquica**:

```
Dashboard
   ├─ Vista general
   └─ Mis cursos (placeholder futuro)

Administración del Sitio (si tiene permisos)
   ├─ Usuarios
   │  ├─ Lista de usuarios
   │  ├─ Agregar usuario
   │  └─ Carga masiva (plugin futuro)
   ├─ Roles y permisos
   │  ├─ Definir roles
   │  └─ Asignar roles
   ├─ Configuración
   │  ├─ General
   │  ├─ Seguridad
   │  ├─ Apariencia
   │  └─ Avanzado
   ├─ Plugins
   │  ├─ Gestionar plugins
   │  ├─ Instalar plugin
   │  └─ [Plugins instalados dinámicamente]
   ├─ Reportes
   │  ├─ Logs del sistema
   │  └─ Reporte de seguridad (futuro)
   └─ Mantenimiento
      ├─ Caché
      └─ Información del sistema

Mi Perfil
   ├─ Ver perfil
   ├─ Editar perfil
   ├─ Preferencias
   │  ├─ Cambiar contraseña
   │  └─ Preferencias de notificaciones (futuro)
   └─ Mis roles
```

**Funcionalidad**:
- Click en categoría: Toggle collapse/expand con animación
- Página activa: Auto-expand de todas las categorías padres
- Persistencia: Guardar estado en `localStorage('nav_collapsed_categories')`
- Iconos Font Awesome: Categorías tienen iconos, items individuales opcionales
- Badges: Mostrar contadores (ej: "3" en "Notificaciones")
- Separadores: Divider line entre grupos principales

**Responsive**:
- Desktop (>1200px): Sidebar visible 280px width, colapsable a 60px (solo iconos)
- Tablet (768-1200px): Sidebar 240px width, overlay en mobile
- Mobile (<768px): Sidebar oculto, accesible via drawer del header

#### D. Breadcrumbs

**Auto-generación**:
- Construido automáticamente desde la navegación del sidebar
- `$PAGE->add_breadcrumb($text, $url)` para override manual

**Ejemplos**:
```
Dashboard

Dashboard > Administración

Dashboard > Administración > Usuarios

Dashboard > Administración > Usuarios > Editar usuario: Juan Pérez

Dashboard > Mi Perfil > Editar perfil
```

**Responsive**:
- Desktop: Mostrar todos los niveles
- Mobile: Mostrar "... > Penúltimo > Último" si excede 3 niveles

---

### 6. JavaScript Requerido

#### A. `public_html/js/navigation/primary-navigation.js`

```javascript
// Funcionalidad:
// - Toggle mobile drawer
// - Close drawer on click outside
// - Keyboard navigation (Tab, Enter, Esc)
// - User menu dropdown
// - Notificaciones dropdown (futuro)
```

#### B. `public_html/js/navigation/secondary-navigation.js`

```javascript
// Funcionalidad:
// - Detectar overflow de tabs
// - Mover tabs excedentes a "More" menu
// - Responsive resize handler
// - Active tab indicator animation
```

#### C. `public_html/js/navigation/sidebar-navigation.js`

```javascript
// Funcionalidad:
// - Collapse/expand categories
// - Save state to localStorage
// - Auto-expand active category
// - Smooth animations (slide down/up)
// - Badge animations (pulse para nuevos items)
// - Sidebar width toggle (full width ↔ icons only)
```

#### D. `public_html/js/navigation/mobile-drawer.js`

```javascript
// Funcionalidad:
// - Open/close drawer con animación
// - Overlay backdrop
// - Swipe to close gesture
// - Lock body scroll cuando drawer abierto
// - Touch event handlers
```

---

### 7. CSS/SCSS Estructura

#### A. `theme/core/scss/navigation/_iser-branding.scss`

```scss
// Variables de colores ISER
$iser-verde: #1B9E88;
$iser-amarillo: #FCBD05;
$iser-rojo: #EB4335;
$iser-blanco: #FFFFFF;
$iser-naranja: #E27C32;
$iser-lima: #CFDA4B;
$iser-azul: #5894EF;
$iser-magenta: #C82260;
$iser-gris-claro: #CFCFCF;
$iser-gris-medio: #9C9C9B;
$iser-gris-oscuro: #646363;
$iser-negro: #000000;

// Tipografías
$font-nav-primary: Verdana, Arial, sans-serif;
$font-nav-secondary: Arial, sans-serif;
$font-nav-logo: Elza, sans-serif;

// Tamaños
$nav-primary-height: 60px;
$nav-secondary-height: 48px;
$sidebar-width-full: 280px;
$sidebar-width-collapsed: 60px;
$breadcrumb-height: 36px;

// Transitions
$transition-fast: 0.2s ease;
$transition-normal: 0.3s ease;
$transition-slow: 0.5s ease;

// Z-index
$z-header: 1000;
$z-drawer: 1100;
$z-overlay: 1050;
```

#### B. `theme/core/scss/navigation/_primary.scss`

```scss
// Estilos para navegación primaria
.nexo-header-primary {
  position: sticky;
  top: 0;
  height: $nav-primary-height;
  background: linear-gradient(135deg, $iser-verde 0%, $iser-azul 100%);
  z-index: $z-header;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  
  // Logo, menú items, user menu
  // Desktop styles
  // Mobile styles con media queries
}
```

#### C. `theme/core/scss/navigation/_secondary.scss`

```scss
// Estilos para tabs secundarios
.nexo-nav-secondary {
  height: $nav-secondary-height;
  background: $iser-blanco;
  border-bottom: 1px solid $iser-gris-claro;
  
  .nexo-nav-tab {
    // Estilos de tab
    // Active state
    // Hover state
  }
  
  .nexo-nav-more-menu {
    // Dropdown "More"
  }
}
```

#### D. `theme/core/scss/navigation/_sidebar.scss`

```scss
// Estilos para sidebar
.nexo-sidebar {
  width: $sidebar-width-full;
  background: $iser-blanco;
  border-right: 1px solid $iser-gris-claro;
  transition: width $transition-normal;
  
  &.collapsed {
    width: $sidebar-width-collapsed;
    
    // Ocultar textos, mostrar solo iconos
  }
  
  .nexo-nav-category {
    // Categoría header
    
    &.expanded {
      // Estado expandido
    }
  }
  
  .nexo-nav-item {
    // Item individual
    
    &.active {
      background: rgba($iser-verde, 0.1);
      border-left: 4px solid $iser-verde;
    }
  }
}
```

---

### 8. Integración con Sistema Existente

#### A. Compatibilidad con RBAC

**Todos los nodos de navegación deben verificar permisos**:

```php
// Ejemplo en navigation_builder.php
public function build_admin_navigation(): navigation_tree {
    $tree = new navigation_tree();
    
    // Solo agregar si tiene permiso
    if (has_capability('nexosupport/admin:manageusers')) {
        $tree->add(
            new navigation_node('users', 'Usuarios', '/admin/users', 'fa-users')
        );
    }
    
    return $tree;
}
```

**Site administrators siempre ven toda la navegación** (bypass RBAC).

#### B. Integración con Sistema de Eventos

**Disparar eventos en cambios de navegación**:

```php
// Cuando el usuario colapsa una categoría
\core\event\navigation_category_collapsed::create([
    'userid' => $USER->id,
    'other' => ['category' => 'admin', 'state' => 'collapsed']
])->trigger();
```

#### C. Integración con Cache

**Cachear estructuras de navegación**:

```php
// En navigation_builder.php
public function get_cached_navigation(string $context): navigation_tree {
    $cache = \core\cache\manager::get_instance('navigation', $context);
    
    if ($tree = $cache->get($context . '_' . $USER->id)) {
        return $tree;
    }
    
    $tree = $this->build_navigation_for_context($context);
    $cache->set($context . '_' . $USER->id, $tree, 3600); // 1 hora
    
    return $tree;
}
```

**Purgar cache cuando cambian permisos**:

```php
// En rbac/role.php después de assign_capability()
\core\cache\manager::purge('navigation');
```

#### D. Integración con i18n

**Todas las cadenas de navegación deben ser traducibles**:

```php
// En lugar de texto hardcoded:
$node = new navigation_node('users', 'Users', '/admin/users');

// Usar get_string():
$node = new navigation_node(
    'users',
    get_string('users', 'admin'),
    '/admin/users'
);
```

**Agregar strings necesarios a** `lang/es/core.php` **y** `lang/en/core.php`.

---

### 9. Testing y Validación

#### A. Validación de Rutas y Funcionalidades (CRÍTICO)

**TODAS las rutas existentes deben funcionar correctamente tras la reconstrucción**:

**Autenticación**:
- [ ] `/login` - Formulario muestra correctamente con nueva UI
- [ ] POST `/login` - Login funciona (crea sesión, redirige a dashboard)
- [ ] `/logout` - Cierra sesión y redirige a login
- [ ] `/login/forgot_password` - Recuperación de contraseña funcional
- [ ] `/login/change_password` - Cambio de contraseña funcional

**Dashboard**:
- [ ] `/` - Dashboard carga con nueva navegación
- [ ] Usuario ve su nombre y avatar en header
- [ ] Sidebar muestra opciones según permisos del usuario
- [ ] Breadcrumb muestra "Dashboard"

**Administración General**:
- [ ] `/admin` - Panel admin carga (solo con permisos)
- [ ] Site administrator ve todas las opciones
- [ ] Usuario sin permisos recibe error [[nopermissions]]
- [ ] Navegación secundaria muestra tabs correctos
- [ ] Sidebar muestra categoría "Administración" expandida

**Gestión de Usuarios**:
- [ ] `/admin/users` - Lista usuarios con paginación
- [ ] Búsqueda de usuarios funciona
- [ ] `/admin/user/edit` - Formulario crear usuario nuevo
- [ ] `/admin/user/edit?id=X` - Formulario editar usuario existente
- [ ] POST `/admin/user/edit` - Guardar usuario funciona
- [ ] Validación de campos funciona (username duplicado, email, etc.)
- [ ] Suspender/reactivar usuario funciona
- [ ] Links a "Asignar roles" funcionan

**Gestión de Roles**:
- [ ] `/admin/roles` - Lista roles muestra correctamente
- [ ] `/admin/roles/edit` - Crear rol nuevo
- [ ] `/admin/roles/edit?id=X` - Editar rol existente
- [ ] POST `/admin/roles/edit` - Guardar rol funciona
- [ ] Roles del sistema no se pueden eliminar
- [ ] `/admin/roles/define` - Matriz de capabilities carga
- [ ] `/admin/roles/define?id=X` - Define permisos para rol
- [ ] POST `/admin/roles/define` - Guardar capabilities funciona
- [ ] 4 niveles de permisos (Prohibir, No establecido, Permitir, Prevenir) funcionan
- [ ] `/admin/roles/assign` - Vista de asignación carga
- [ ] `/admin/roles/assign?userid=X` - Ver roles de usuario
- [ ] `/admin/roles/assign?roleid=X` - Ver usuarios con rol
- [ ] POST `/admin/roles/assign` - Asignar/remover roles funciona

**Configuración**:
- [ ] `/admin/settings` - Página de configuración carga
- [ ] Formulario de settings muestra correctamente
- [ ] POST `/admin/settings` - Guardar configuración funciona
- [ ] Cambios se reflejan en `config` table

**Caché**:
- [ ] `/admin/cache/purge` - Página de purga carga
- [ ] Botones de purga por tipo funcionan
- [ ] Purga de OPcache funciona
- [ ] Purga de Mustache funciona
- [ ] Purga de i18n funciona
- [ ] Purga total funciona

**Sistema de Upgrade**:
- [ ] `/admin/upgrade.php` - Detecta versión actual vs código
- [ ] Si hay upgrade pendiente, lo ejecuta correctamente
- [ ] Logs de upgrade se muestran
- [ ] Después de upgrade, versión se actualiza en BD

**Permisos y Seguridad**:
- [ ] `require_login()` redirige a login si no autenticado
- [ ] `require_capability()` bloquea acceso sin permiso
- [ ] Site administrator bypasea verificación de capabilities
- [ ] CSRF protection funciona (sesskey en forms)
- [ ] XSS protection funciona (htmlspecialchars en outputs)

**Sistema de Notificaciones**:
- [ ] Notificaciones flash se muestran después de acciones
- [ ] 4 tipos (success, error, warning, info) tienen estilos correctos
- [ ] Notificaciones se eliminan después de mostrar

**Navegación Específica**:
- [ ] Breadcrumbs se generan automáticamente
- [ ] Página activa está marcada en sidebar
- [ ] Categoría de página activa está expandida
- [ ] Hover effects funcionan en todos los menús
- [ ] Click en categoría colapsa/expande
- [ ] Estado de sidebar persiste (localStorage)

#### B. Casos de Prueba de Navegación

**Navegación Primaria**:
- [ ] Logo redirige a dashboard
- [ ] Items solo visibles según permisos
- [ ] User menu funciona correctamente
- [ ] Mobile drawer abre/cierra correctamente
- [ ] Responsive en todos los breakpoints

**Navegación Secundaria**:
- [ ] Tabs se generan según contexto
- [ ] Tab activo está marcado correctamente
- [ ] Overflow a menú "More" funciona
- [ ] Responsive en tablet/mobile

**Sidebar**:
- [ ] Categorías colapsan/expanden
- [ ] Estado persiste en localStorage
- [ ] Página activa auto-expande padres
- [ ] Página activa está highlighted
- [ ] Badges se muestran correctamente
- [ ] Separadores aparecen donde corresponde
- [ ] Width toggle funciona (full ↔ icons)

**Breadcrumbs**:
- [ ] Se generan automáticamente
- [ ] Override manual funciona
- [ ] Truncate en mobile funciona
- [ ] Links son clickeables (excepto último)

**Permisos en Navegación**:
- [ ] Site administrator ve todo
- [ ] Usuario normal solo ve lo permitido
- [ ] Usuario sin permisos de admin no ve sección de administración
- [ ] Cambio de permisos purga cache correctamente

#### C. Validación de Estilos ISER

**Colores**:
- [ ] Navegación primaria usa gradiente verde (#1B9E88) a azul (#5894EF)
- [ ] Texto en primaria es blanco (#FFFFFF)
- [ ] Active item tiene border amarillo (#FCBD05)
- [ ] Sidebar usa colores neutrales ISER
- [ ] Item activo tiene background verde claro y border verde
- [ ] No hay ningún color purple/blue del sistema anterior

**Tipografías**:
- [ ] Navegación usa Verdana o Arial
- [ ] Logo usa Elza (si aplica)
- [ ] Tamaños de fuente son consistentes
- [ ] No hay más de 2 tipografías en toda la interfaz

**Responsive**:
- [ ] Desktop (>1200px): Sidebar visible, header completo
- [ ] Tablet (768-1200px): Sidebar overlay, tabs ajustados
- [ ] Mobile (<768px): Hamburger menu, drawer, todo accesible

#### D. Compatibilidad Cross-Browser

**Navegadores a probar**:
- [ ] Chrome/Edge (últimas 2 versiones)
- [ ] Firefox (últimas 2 versiones)
- [ ] Safari (últimas 2 versiones en macOS/iOS)

**Devices**:
- [ ] Desktop (1920x1080, 1366x768)
- [ ] Tablet (iPad, 768x1024)
- [ ] Mobile (iPhone SE 375x667, iPhone 12 390x844, Android común)

#### E. Testing de Performance

**Cargas**:
- [ ] Página admin carga en <2 segundos
- [ ] Cache de navegación funciona (verificar queries)
- [ ] No hay N+1 queries para permisos
- [ ] CSS/JS están minificados (en producción)

**Validación Manual REQUERIDA**:

Después de completar la implementación, el desarrollador DEBE:

1. **Crear usuario de prueba sin permisos**: Verificar que no ve admin
2. **Crear rol personalizado**: Verificar capabilities
3. **Asignar rol a usuario**: Verificar que permisos se aplican
4. **Probar todas las rutas**: Click en cada link de navegación
5. **Probar en mobile**: Usar Chrome DevTools device emulation
6. **Verificar colores ISER**: Comparar con paleta oficial
7. **Probar logout/login**: Verificar que navegación se actualiza

**Reporte de Testing**: Documentar en `/docs/TESTING_REPORT.md` con screenshots.

---

### 10. Documentación Requerida

#### A. Archivo: `docs/NAVIGATION_ARCHITECTURE.md`

Documentar:
- Arquitectura completa del sistema de navegación
- Diagrama de clases
- Flujo de datos ($PAGE → navigation → renderer → template)
- Ejemplos de uso para cada tipo de navegación
- Cómo extender la navegación desde plugins

#### B. Archivo: `docs/NAVIGATION_ISER_BRANDING.md`

Documentar:
- Guía de aplicación del branding ISER
- Paleta de colores con ejemplos de uso
- Tipografías y sus contextos
- Screenshots de cada componente de navegación
- Código de ejemplo para cada elemento

#### C. Archivo: `docs/NAVIGATION_API.md`

Documentar API pública:

```php
// Construcción de navegación
$PAGE->initialize_navigation();
$PAGE->set_secondary_active_tab('users');
$PAGE->add_breadcrumb('Usuarios', '/admin/users');

// Agregar nodo personalizado al sidebar
$PAGE->sidebar_nav->add(
    new navigation_node('custom', 'Mi Página', '/custom/page', 'fa-star')
);

// Agregar tab secundario
$PAGE->secondary_nav->add_tab(
    new navigation_node('custom_tab', 'Mi Tab', '/custom/tab')
);

// Agregar al menú primario (desde plugin)
$PAGE->primary_nav->add_node(
    new navigation_node('plugin', 'Mi Plugin', '/plugin/index', 'fa-puzzle-piece')
);
```

---

### 11. Prioridades de Implementación

#### FASE 1 (Crítico - Primera semana):
1. Refactorizar `navigation_node.php` con nuevas propiedades
2. Crear `primary_navigation.php` y su renderer
3. Crear template `primary_navigation.mustache` con branding ISER
4. Modificar `renderer.php` header() para usar nueva navegación primaria
5. CSS `_primary.scss` completo y responsive
6. JavaScript `primary-navigation.js` funcional
7. Testing de navegación primaria en todos los devices

#### FASE 2 (Alta prioridad - Segunda semana):
1. Crear `secondary_navigation.php` y su renderer
2. Implementar factory methods para cada contexto (admin, user, etc.)
3. Crear template `secondary_navigation.mustache`
4. CSS `_secondary.scss` completo
5. JavaScript `secondary-navigation.js` con overflow handling
6. Integrar tabs secundarios en páginas principales de admin

#### FASE 3 (Media prioridad - Tercera semana):
1. Refactorizar sidebar existente a `sidebar_navigation_renderer.php`
2. Mejorar template `sidebar_navigation.mustache`
3. Agregar funcionalidad de badges, separadores, width toggle
4. CSS `_sidebar.scss` mejorado con branding ISER
5. JavaScript `sidebar-navigation.js` con persistencia y animaciones
6. Poblar sidebar con estructura completa del sistema

#### FASE 4 (Complementario - Cuarta semana):
1. Implementar breadcrumbs completo
2. Mobile drawer con gestures
3. User menu dropdown
4. Notificaciones badge (estructura, sin backend aún)
5. Optimización de cache de navegación
6. Testing cross-browser exhaustivo
7. Documentación completa

---

## RESTRICCIONES Y CONSIDERACIONES

### 1. NO Romper Funcionalidad de Backend (Mantener)
- ✅ **NO** modificar estructura de base de datos
- ✅ **NO** cambiar lógica de RBAC (roles, capabilities, contexts)
- ✅ **NO** modificar sistema de routing existente
- ✅ **NO** cambiar firmas de métodos públicos en clases de backend (DB, RBAC, Session)
- ✅ **SÍ** mantener todas las rutas existentes funcionales
- ✅ **SÍ** mantener autenticación y sesiones funcionando

### 1.1. SÍ Eliminar/Reemplazar UI Existente (Reconstruir)
- ❌ **ELIMINAR** todos los estilos CSS actuales de navegación
- ❌ **ELIMINAR** HTML del navbar actual
- ❌ **ELIMINAR** sistema de breadcrumbs actual
- ❌ **REEMPLAZAR** templates Mustache de navegación
- ❌ **REEMPLAZAR** cualquier código que genere UI no compatible con ISER
- ✅ **CREAR** nueva interfaz desde cero siguiendo especificaciones

### 2. Performance
- Cache agresivo de estructuras de navegación (1 hora)
- Lazy loading de templates Mustache
- Minimizar queries a BD (batch loading de permisos)
- CSS/JS minificado en producción

### 3. Accesibilidad
- ARIA labels en todos los elementos de navegación
- Navegación por teclado completa (Tab, Enter, Arrow keys)
- Contrast ratio mínimo 4.5:1 (WCAG AA)
- Screen reader friendly

### 4. SEO
- Estructura semántica HTML5 (`<nav>`, `<header>`, `<main>`)
- Breadcrumbs con schema.org markup
- URLs amigables

### 5. Seguridad
- CSRF protection en todos los forms de navegación
- XSS prevention (escape de strings)
- Verificación de permisos en cada nodo

---

## ENTREGABLES ESPERADOS

### 1. Código Funcional Completo

**Archivos NUEVOS creados**:
- [ ] `lib/classes/navigation/primary_navigation.php`
- [ ] `lib/classes/navigation/secondary_navigation.php`
- [ ] `lib/classes/navigation/primary_navigation_renderer.php`
- [ ] `lib/classes/navigation/secondary_navigation_renderer.php`
- [ ] `lib/classes/navigation/sidebar_navigation_renderer.php`
- [ ] `templates/navigation/primary_navigation.mustache`
- [ ] `templates/navigation/secondary_navigation.mustache`
- [ ] `templates/navigation/breadcrumbs.mustache`
- [ ] `templates/navigation/user_menu.mustache`
- [ ] `templates/navigation/mobile_drawer.mustache`
- [ ] `theme/core/scss/navigation/_iser-branding.scss`
- [ ] `theme/core/scss/navigation/_primary.scss`
- [ ] `theme/core/scss/navigation/_secondary.scss`
- [ ] `theme/core/scss/navigation/_sidebar.scss`
- [ ] `theme/core/scss/navigation/_breadcrumbs.scss`
- [ ] `theme/core/scss/navigation/_mobile.scss`
- [ ] `public_html/js/navigation/primary-navigation.js`
- [ ] `public_html/js/navigation/secondary-navigation.js`
- [ ] `public_html/js/navigation/sidebar-navigation.js`
- [ ] `public_html/js/navigation/mobile-drawer.js`

**Archivos MODIFICADOS** (eliminando UI antigua, creando nueva):
- [ ] `lib/classes/output/renderer.php` - Método `header()` completamente reescrito
- [ ] `lib/classes/output/page.php` - Agregadas propiedades de navegación
- [ ] `lib/classes/navigation/navigation_node.php` - Extendido con badges, dividers, etc.
- [ ] `templates/navigation/sidebar.mustache` - Completamente reemplazado
- [ ] `lib/setup.php` - Agregada inicialización automática de navegación

**Archivos ELIMINADOS** (limpiar UI antigua):
- [ ] Cualquier CSS inline con colores purple/blue en `renderer.php`
- [ ] Estilos antiguos de navbar en cualquier archivo
- [ ] Templates Mustache obsoletos (si existen)

### 2. CSS/SCSS Compilado con Branding ISER

- [ ] **Variables ISER**: Archivo `_iser-branding.scss` con todos los colores y tipografías
- [ ] **CSS compilado**: Archivo `theme/core/style/navigation.css` generado desde SCSS
- [ ] **Minificado**: Versión `.min.css` para producción
- [ ] **Sin restos antiguos**: Cero referencias a colores purple, blue, pink del sistema anterior
- [ ] **Validado**: Todos los colores usados están en la paleta ISER oficial

### 3. JavaScript Funcional

- [ ] **Todas las interacciones funcionan**: Hamburger, collapse, dropdown, etc.
- [ ] **Sin errores de consola**: Verificado en Chrome DevTools
- [ ] **Compatible con ES6+**: Uso de arrow functions, const/let, etc.
- [ ] **Event listeners eficientes**: Delegation donde sea apropiado
- [ ] **localStorage funciona**: Estado de sidebar persiste

### 4. Documentación Técnica

**Archivos de documentación requeridos** (crear en `/docs`):

- [ ] `docs/NAVIGATION_ARCHITECTURE.md`
  - Arquitectura completa del nuevo sistema
  - Diagrama de flujo de datos
  - Clases y sus responsabilidades
  
- [ ] `docs/NAVIGATION_ISER_BRANDING.md`
  - Guía de aplicación del branding
  - Screenshots de cada componente
  - Código de ejemplo para cada elemento
  
- [ ] `docs/NAVIGATION_API.md`
  - API pública para extender navegación
  - Ejemplos de uso desde plugins
  - Métodos disponibles en $PAGE
  
- [ ] `docs/TESTING_REPORT.md`
  - Resultado de todas las pruebas
  - Screenshots en diferentes devices
  - Lista de rutas validadas (checklist completo)
  
- [ ] `docs/MIGRATION_GUIDE.md`
  - Guía de integración en sistema existente
  - Pasos para deploy sin downtime
  - Rollback plan si algo falla

### 5. Testing Report

**Incluir en** `docs/TESTING_REPORT.md`:

- [ ] Screenshots de todas las páginas principales
- [ ] Screenshots en desktop, tablet, mobile
- [ ] Tabla de compatibilidad cross-browser
- [ ] Lista de todas las rutas probadas (✅ funcional / ❌ con errores)
- [ ] Validación de colores ISER (comparación visual)
- [ ] Performance metrics (tiempos de carga)

### 6. Checklist de Validación Final

**Antes de considerar el trabajo completo, verificar**:

#### Funcionalidad
- [ ] TODAS las rutas en `lib/routing/routes.php` son funcionales
- [ ] Login/logout funcionan correctamente
- [ ] Dashboard carga sin errores
- [ ] Panel admin solo accesible con permisos
- [ ] Crear/editar usuarios funciona
- [ ] Crear/editar roles funciona
- [ ] Asignar roles funciona
- [ ] Definir capabilities funciona
- [ ] Cambiar configuración funciona
- [ ] Purgar caché funciona
- [ ] Notificaciones flash se muestran

#### UI/UX
- [ ] Navegación primaria visible en todas las páginas
- [ ] Navegación secundaria aparece donde corresponde
- [ ] Sidebar funciona en todas las páginas
- [ ] Breadcrumbs se generan automáticamente
- [ ] Logo ISER visible en header
- [ ] User menu funciona (dropdown con perfil, logout)
- [ ] Mobile drawer funciona
- [ ] Responsive en todos los breakpoints
- [ ] Animaciones suaves (sin lag)
- [ ] Sin flash de contenido sin estilos (FOUC)

#### Branding ISER
- [ ] Colores primarios aplicados correctamente
- [ ] Colores secundarios <30% del diseño
- [ ] Colores neutrales en textos/bordes
- [ ] CERO colores del sistema anterior (purple/blue)
- [ ] Tipografías correctas (Verdana/Arial)
- [ ] Máximo 2 tipografías en toda la interfaz
- [ ] Logo sin deformación

#### Seguridad y Permisos
- [ ] Site administrator ve toda la navegación
- [ ] Usuario normal solo ve opciones permitidas
- [ ] Usuario sin permisos no accede a admin
- [ ] Verificación de capabilities en cada nodo
- [ ] CSRF protection en todos los forms
- [ ] XSS prevention en todos los outputs

#### Performance
- [ ] Cache de navegación funciona
- [ ] Sin N+1 queries
- [ ] CSS/JS minificados
- [ ] Imágenes optimizadas
- [ ] Tiempos de carga <2 segundos

#### Accesibilidad
- [ ] ARIA labels en elementos de navegación
- [ ] Navegación por teclado funcional
- [ ] Contrast ratio ≥4.5:1
- [ ] Screen reader friendly

#### Código
- [ ] PSR-12 compliant
- [ ] Type hints en todas las firmas
- [ ] Código comentado donde sea complejo
- [ ] Sin código muerto/comentado
- [ ] Sin console.log() en JS de producción
- [ ] Sin errores de PHP/JS en consola

---

## NOTAS FINALES

### Filosofía de Implementación

Este proyecto es una **RECONSTRUCCIÓN TOTAL DE LA INTERFAZ DE USUARIO**, no un simple "restyling" o ajuste de colores. 

**Lo que esto significa**:
- Borrar todo lo visual existente que no cumpla con ISER
- Crear desde cero cada componente de navegación
- Aplicar únicamente colores y tipografías ISER
- Asegurar que funcionalidad de backend se mantiene 100%

### Principios de Código

- **Priorizar código limpio y mantenible** sobre soluciones "clever"
- **Comentar código complejo** (especialmente algoritmos de construcción de árbol)
- **Usar type hints de PHP 8.1** en todas las firmas de métodos
- **Seguir PSR-12** para estilo de código
- **Commit frecuente** con mensajes descriptivos
- **Testing continuo** en cada fase

### Workflow Recomendado

**FASE 1**: Eliminar UI antigua
1. Comentar (no borrar aún) el HTML del header actual en `renderer.php`
2. Comentar estilos antiguos de navbar
3. Verificar que la app sigue funcionando (sin estilos, pero funcional)

**FASE 2**: Crear navegación primaria
1. Implementar clases nuevas
2. Crear templates Mustache
3. Aplicar estilos ISER
4. Integrar en `renderer.php`
5. Testing exhaustivo

**FASE 3**: Crear navegación secundaria
1. Implementar sistema de tabs contextuales
2. Integrar en páginas de admin
3. Testing

**FASE 4**: Mejorar sidebar
1. Refactorizar sidebar existente
2. Aplicar estilos ISER
3. Testing

**FASE 5**: Integración y pulido
1. Breadcrumbs
2. Mobile drawer
3. Optimizaciones
4. Testing final exhaustivo

### Validación Continua

**Durante cada fase, verificar**:
1. ¿Las rutas siguen funcionando?
2. ¿Los permisos se verifican correctamente?
3. ¿Los estilos son 100% ISER?
4. ¿No hay errores en consola?
5. ¿El responsive funciona?

### Comunicación de Progreso

**Documentar en commits**:
```bash
git commit -m "feat: Eliminar UI antigua del header
- Comentado HTML del navbar actual en renderer.php
- Comentados estilos purple/blue
- App funcional sin estilos (temporal)
- Ref: #reconstruccion-navegacion"

git commit -m "feat: Implementar navegación primaria ISER
- Creadas clases primary_navigation y renderer
- Template Mustache con branding ISER
- Estilos SCSS con gradiente verde-azul
- JavaScript para mobile drawer
- Integrado en renderer.php header()
- TODAS las rutas funcionan
- Testing: ✅ Desktop, ✅ Tablet, ✅ Mobile
- Ref: #reconstruccion-navegacion"
```

### Criterios de Aceptación

Este trabajo se considera **COMPLETO** únicamente cuando:

1. ✅ **CERO errores de PHP/JavaScript** en consola
2. ✅ **TODAS las rutas funcionan** (verificado checklist completo)
3. ✅ **Branding 100% ISER** (sin colores/fuentes antiguas)
4. ✅ **Responsive en todos los devices** (probado en real/emulación)
5. ✅ **Documentación completa** (4 archivos en `/docs`)
6. ✅ **Testing report con screenshots** (desktop, tablet, mobile)
7. ✅ **Performance <2 segundos** por página
8. ✅ **Accesibilidad WCAG AA** (navegación por teclado, contraste, ARIA)

### Soporte y Dudas

Si durante la implementación surgen dudas sobre:
- **Branding**: Referirse a la paleta de colores ISER en el prompt
- **Funcionalidad**: Referirse a la documentación existente del proyecto
- **Arquitectura Moodle**: Consultar enlaces de referencia en sección de búsqueda web

### Mensaje Final para Claude Code

**RECORDATORIO CRÍTICO**: 

Esta es una reconstrucción total de UI, NO un simple restyling:
- Elimina toda la UI que no cumpla con ISER
- Crea todo desde cero con arquitectura Moodle 4.x
- Verifica CONTINUAMENTE que las rutas funcionan
- Valida CONSTANTEMENTE contra paleta de colores ISER
- Documenta TODO el trabajo realizado

Este sistema de navegación será **la base para los próximos 5 años** del proyecto NexoSupport. La **calidad, funcionalidad, y adherencia al branding ISER son prioritarias absolutas**.

**NO comprometas la calidad por velocidad.**  
**NO te saltes testing.**  
**NO inventes colores que no estén en la paleta ISER.**  
**SÍ verifica que cada ruta funciona después de cada cambio.**  
**SÍ documenta lo que haces.**  
**SÍ haz commits frecuentes.**

---

**Fecha de creación**: 2025-01-25  
**Versión del sistema**: NexoSupport v1.1.10  
**Autor**: Alonso Arias / ISER Development Team  
**Revisión**: v2.0 - Énfasis en reconstrucción total de UI  
**Estado**: Listo para implementación en Claude Code

---

## RESUMEN EJECUTIVO (TL;DR)

### ¿Qué se debe hacer?

**ELIMINAR TOTALMENTE** la interfaz de usuario actual y **CREAR UNA NUEVA** desde cero que:
1. Se vea como Moodle 4.x (navegación primaria + secundaria + sidebar + breadcrumbs)
2. Use ÚNICAMENTE colores y tipografías de la paleta ISER (verde #1B9E88, amarillo #FCBD05, etc.)
3. Mantenga TODAS las funcionalidades existentes (login, admin, usuarios, roles, etc.)

### ¿Qué NO se debe tocar?

**MANTENER** todo el backend:
- Base de datos (no tocar)
- Sistema RBAC (solo usar, no modificar)
- Routing (mantener todas las rutas)
- Autenticación y sesiones (funciona, no tocar)
- Lógica de negocio (mantener)

### ¿Cómo validar que está bien hecho?

1. **Visual**: Parece Moodle 4.x con colores ISER (NO purple/blue)
2. **Funcional**: TODAS las rutas de la tabla funcionan
3. **Responsive**: Se ve bien en desktop, tablet, mobile
4. **Performance**: Carga <2 segundos por página
5. **Sin errores**: Cero errores en consola PHP/JavaScript

### ¿Cuál es el criterio de éxito?

**El proyecto está completo ÚNICAMENTE cuando**:
- [x] Interfaz totalmente nueva (cero restos de UI antigua)
- [x] Branding 100% ISER (verificado con paleta oficial)
- [x] Todas las 20+ rutas críticas funcionan (tabla completa ✅)
- [x] Responsive en todos los devices
- [x] Documentación completa (4 archivos .md)
- [x] Testing report con screenshots

### ¿Cuánto tiempo debería tomar?

**Estimación realista**:
- FASE 1 (Navegación primaria): 1-2 días
- FASE 2 (Navegación secundaria): 1-2 días
- FASE 3 (Sidebar mejorado): 1-2 días
- FASE 4 (Breadcrumbs + mobile + pulido): 1-2 días
- **TOTAL: 1-2 semanas de trabajo dedicado**

### ¿Qué pasa si algo no funciona?

**NO continuar hasta resolver**:
1. Depurar el error inmediatamente
2. Verificar contra tabla de rutas
3. Revisar paleta de colores ISER
4. Probar en navegador real
5. Documentar la solución

### Mensaje final ultra-claro

Este es un proyecto de **RECONSTRUCCIÓN TOTAL DE INTERFAZ**.

NO es:
- ❌ Cambiar algunos colores
- ❌ Ajustar el CSS existente
- ❌ Agregar algunas clases nuevas

SÍ es:
- ✅ Borrar toda la UI actual
- ✅ Crear TODO desde cero
- ✅ Aplicar solo branding ISER
- ✅ Asegurar que TODO funciona

**Si después de leer este prompt tienes dudas, vuelve a leerlo completo. Todo está explicado aquí.**
