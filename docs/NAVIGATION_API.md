# API de Navegación NexoSupport

## Referencia Completa de la API

Esta documentación proporciona una referencia completa de todas las clases, métodos y configuraciones disponibles en el sistema de navegación.

---

## Tabla de Contenidos

1. [navigation_node](#navigation_node)
2. [navigation_tree](#navigation_tree)
3. [primary_navigation](#primary_navigation)
4. [secondary_navigation](#secondary_navigation)
5. [Renderers](#renderers)
6. [Eventos y Hooks](#eventos-y-hooks)
7. [API JavaScript](#api-javascript)

---

## navigation_node

**Namespace:** `core\navigation`
**Archivo:** `lib/classes/navigation/navigation_node.php`

### Constantes

```php
const TYPE_CATEGORY = 'category';  // Nodo contenedor con hijos
const TYPE_ITEM = 'item';          // Elemento navegable
const TYPE_SEPARATOR = 'separator'; // Separador visual
```

### Constructor

```php
public function __construct(string $key, string $type, array $config = [])
```

**Parámetros:**
| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| `$key` | string | Identificador único del nodo |
| `$type` | string | Tipo de nodo (usar constantes TYPE_*) |
| `$config` | array | Configuración inicial |

**Opciones de $config:**
```php
$config = [
    'text' => 'Texto visible',
    'url' => '/ruta/destino',
    'icon' => 'fa-home',           // Clase Font Awesome
    'order' => 100,                // Orden de aparición
    'visible' => true,             // Visibilidad
    'active' => false,             // Estado activo
    'expanded' => false,           // Expandido (categorías)
    'capability' => 'admin/view',  // Capacidad requerida
    'capabilities' => ['cap1', 'cap2'], // Múltiples (OR)
    'badge' => '5',                // Texto del badge
    'badge_type' => 'primary',     // default|primary|warning|danger
    'divider_after' => false,      // Separador después
    'force_into_more' => false,    // Forzar al menú "Más"
    'data' => ['key' => 'value'],  // Datos personalizados
];
```

### Métodos Getter

```php
// Identificación
public function get_key(): string
public function get_type(): string

// Contenido
public function get_text(): ?string
public function get_url(): ?string
public function get_icon(): ?string

// Estado
public function get_order(): int
public function is_visible(): bool
public function is_active(): bool
public function is_expanded(): bool

// Badge
public function get_badge(): ?string
public function get_badge_type(): string
public function has_badge(): bool

// Jerarquía
public function get_parent(): ?navigation_node
public function get_children(bool $sorted = true): array
public function get_child(string $key): ?navigation_node
public function has_children(): bool

// Otros
public function has_divider_after(): bool
public function should_force_into_more(): bool
public function get_data(string $key, mixed $default = null): mixed
```

### Métodos Setter

```php
// Contenido
public function set_text(string $text): self
public function set_url(string $url): self
public function set_icon(string $icon): self

// Estado
public function set_order(int $order): self
public function set_visible(bool $visible): self
public function set_active(bool $active): self
public function set_expanded(bool $expanded): self

// Badge
public function set_badge(string $text, string $type = 'default'): self

// Jerarquía
public function set_parent(?navigation_node $parent): self
public function add_child(navigation_node $child): self
public function remove_child(string $key): self

// Otros
public function add_divider_after(bool $divider = true): self
public function set_force_into_more(bool $force = true): self
public function set_data(string $key, mixed $value): self
```

### Métodos de Utilidad

```php
// Verificar acceso basado en capacidades
public function check_access(?int $userid = null, ?object $context = null): bool

// Exportar a array para templates
public function to_array(bool $recursive = true): array
```

### Ejemplo de Uso

```php
use core\navigation\navigation_node;

// Crear categoría
$category = new navigation_node('admin', navigation_node::TYPE_CATEGORY, [
    'text' => 'Administración',
    'icon' => 'fa-cogs',
    'order' => 100,
    'capability' => 'site/config'
]);

// Agregar items hijos
$category->add_child(new navigation_node('users', navigation_node::TYPE_ITEM, [
    'text' => 'Usuarios',
    'url' => '/admin/users',
    'icon' => 'fa-users',
    'order' => 10,
    'badge' => '3',
    'badge_type' => 'warning'
]));

// Verificar acceso
if ($category->check_access($USER->id)) {
    // Mostrar categoría
}
```

---

## navigation_tree

**Namespace:** `core\navigation`
**Archivo:** `lib/classes/navigation/navigation_tree.php`

### Constructor

```php
public function __construct(string $id = 'default')
```

### Métodos Principales

```php
// Gestión de nodos raíz
public function add_node(navigation_node $node): self
public function get_node(string $key): ?navigation_node
public function remove_node(string $key): self
public function get_nodes(bool $sorted = true): array

// Búsqueda en profundidad
public function find_node(string $key): ?navigation_node

// Estado activo
public function set_active_path(string $path): void
public function get_active_node(): ?navigation_node

// Exportación
public function to_array(): array
public function get_id(): string
```

### Ejemplo de Uso

```php
use core\navigation\navigation_tree;
use core\navigation\navigation_node;

$tree = new navigation_tree('sidebar');

// Agregar categoría con items
$category = new navigation_node('reports', navigation_node::TYPE_CATEGORY, [
    'text' => 'Reportes',
    'icon' => 'fa-chart-bar',
    'order' => 50
]);

$category->add_child(new navigation_node('sales', navigation_node::TYPE_ITEM, [
    'text' => 'Ventas',
    'url' => '/reports/sales',
    'order' => 10
]));

$tree->add_node($category);

// Marcar ruta activa
$tree->set_active_path('reports/sales');
```

---

## primary_navigation

**Namespace:** `core\navigation`
**Archivo:** `lib/classes/navigation/primary_navigation.php`

Extiende `navigation_tree` con funcionalidad específica para la navegación primaria del header.

### Constructor

```php
public function __construct()
```

### Métodos de Construcción

```php
// Inicializar con items por defecto
public function init_default_items(): void

// Construir desde configuración
public function build_from_config(array $config): void
```

### Items por Defecto

La navegación primaria incluye por defecto:

| Key | Texto | URL | Orden |
|-----|-------|-----|-------|
| `home` | Inicio | / | 10 |
| `dashboard` | Mis cursos | /my | 20 |
| `courses` | Explorar | /course | 30 |
| `calendar` | Calendario | /calendar | 40 |

### Ejemplo de Personalización

```php
use core\navigation\primary_navigation;
use core\navigation\navigation_node;

$primary = new primary_navigation();
$primary->init_default_items();

// Agregar item personalizado
$primary->add_node(new navigation_node('support', navigation_node::TYPE_ITEM, [
    'text' => 'Soporte',
    'url' => '/support',
    'icon' => 'fa-life-ring',
    'order' => 50
]));

// Eliminar item existente
$primary->remove_node('calendar');
```

---

## secondary_navigation

**Namespace:** `core\navigation`
**Archivo:** `lib/classes/navigation/secondary_navigation.php`

Navegación contextual que cambia según la página actual.

### Métodos Factory

```php
// Crear navegación para contexto de curso
public static function for_course(object $course): self

// Crear navegación para contexto de usuario
public static function for_user(object $user): self

// Crear navegación para administración
public static function for_admin(): self

// Crear navegación personalizada
public static function custom(array $tabs): self
```

### Ejemplo: Navegación de Curso

```php
use core\navigation\secondary_navigation;

// Obtener navegación para un curso
$secondary = secondary_navigation::for_course($course);

// Tabs incluidos:
// - Curso (vista principal)
// - Participantes
// - Calificaciones
// - Configuración (si tiene permisos)
```

### Ejemplo: Navegación Personalizada

```php
$secondary = secondary_navigation::custom([
    [
        'key' => 'overview',
        'text' => 'Vista General',
        'url' => '/module/overview',
        'active' => true
    ],
    [
        'key' => 'settings',
        'text' => 'Configuración',
        'url' => '/module/settings',
        'capability' => 'module/configure'
    ]
]);
```

---

## Renderers

### primary_navigation_renderer

**Archivo:** `lib/classes/navigation/primary_navigation_renderer.php`

```php
use core\navigation\primary_navigation_renderer;

$renderer = new primary_navigation_renderer($primary_nav, $user);
$html = $renderer->render();

// O con output renderer
echo $OUTPUT->render_primary_navigation($primary_nav);
```

### secondary_navigation_renderer

**Archivo:** `lib/classes/navigation/secondary_navigation_renderer.php`

```php
use core\navigation\secondary_navigation_renderer;

$renderer = new secondary_navigation_renderer($secondary_nav);
$html = $renderer->render();

// O con output renderer
echo $OUTPUT->render_secondary_navigation($secondary_nav);
```

### sidebar_navigation_renderer

**Archivo:** `lib/classes/navigation/sidebar_navigation_renderer.php`

```php
use core\navigation\sidebar_navigation_renderer;

$renderer = new sidebar_navigation_renderer($sidebar_tree);
$html = $renderer->render();

// O con output renderer
echo $OUTPUT->render_sidebar_navigation($sidebar_tree);
```

---

## Eventos y Hooks

### Eventos de Navegación

```php
// Evento: navegación primaria construida
\core\event\primary_navigation_built::create([
    'context' => \context_system::instance(),
    'other' => ['node_count' => count($nodes)]
])->trigger();

// Evento: nodo de navegación agregado
\core\event\navigation_node_added::create([
    'context' => $context,
    'other' => [
        'key' => $node->get_key(),
        'type' => $node->get_type()
    ]
])->trigger();
```

### Hooks para Plugins

Los plugins pueden extender la navegación implementando callbacks:

```php
// En plugin/lib.php
function pluginname_extend_navigation(navigation_tree $nav): void {
    $nav->add_node(new navigation_node('plugin_item', navigation_node::TYPE_ITEM, [
        'text' => 'Mi Plugin',
        'url' => '/plugin/index',
        'icon' => 'fa-puzzle-piece',
        'order' => 500
    ]));
}

function pluginname_extend_primary_navigation(primary_navigation $nav): void {
    // Agregar item a navegación primaria
}

function pluginname_extend_secondary_navigation(secondary_navigation $nav, string $context): void {
    // Agregar tabs contextuales
}
```

---

## API JavaScript

### NexoPrimaryNavigation

**Archivo:** `public_html/js/navigation/primary-navigation.js`

```javascript
// Acceder a instancia global
const primaryNav = window.NexoPrimaryNavigation;

// Métodos disponibles (a través de la clase)
class PrimaryNavigation {
    // Alternar menú móvil
    toggleMobileMenu()

    // Abrir/cerrar dropdown de usuario
    toggleUserDropdown()
    closeUserDropdown()

    // Establecer item activo
    setActive(key)
}
```

### NexoSecondaryNavigation

**Archivo:** `public_html/js/navigation/secondary-navigation.js`

```javascript
const secondaryNav = window.NexoSecondaryNavigation;

class SecondaryNavigation {
    // Verificar overflow de tabs
    checkOverflow()

    // Alternar menú "Más"
    toggleMoreMenu()
    closeMoreMenu()

    // Establecer tab activo
    setActive(key)
}
```

### NexoSidebarNavigation

**Archivo:** `public_html/js/navigation/sidebar-navigation.js`

```javascript
const sidebarNav = window.NexoSidebarNavigation;

class SidebarNavigation {
    // Alternar categoría
    toggleCategory(key)

    // Expandir/colapsar todas
    expandAll()
    collapseAll()

    // Establecer item activo
    setActive(key)

    // Obtener estado guardado
    getSavedState()
}
```

### NexoMobileDrawer

**Archivo:** `public_html/js/navigation/mobile-drawer.js`

```javascript
const drawer = window.NexoMobileDrawer;

class MobileDrawer {
    // Abrir/cerrar drawer
    open()
    close()
    toggle()

    // Verificar estado
    isOpen()
}
```

### Eventos JavaScript

```javascript
// Escuchar cambios de navegación
document.addEventListener('nexo:nav:change', (e) => {
    console.log('Navegación cambió:', e.detail);
});

// Escuchar apertura de drawer móvil
document.addEventListener('nexo:drawer:open', () => {
    console.log('Drawer abierto');
});

// Escuchar cierre de drawer móvil
document.addEventListener('nexo:drawer:close', () => {
    console.log('Drawer cerrado');
});
```

---

## Contexto de Templates (Mustache)

### primary_navigation.mustache

```mustache
{{! Variables disponibles }}
{{#logo}}
    {{logo_url}}
    {{logo_alt}}
    {{site_name}}
{{/logo}}

{{#items}}
    {{key}}
    {{text}}
    {{url}}
    {{icon_class}}
    {{active}}
    {{has_children}}
    {{#children}}...{{/children}}
{{/items}}

{{#user}}
    {{logged_in}}
    {{fullname}}
    {{avatar_url}}
    {{profile_url}}
    {{logout_url}}
{{/user}}
```

### secondary_navigation.mustache

```mustache
{{! Variables disponibles }}
{{#tabs}}
    {{key}}
    {{text}}
    {{url}}
    {{active}}
    {{has_badge}}
    {{badge}}
    {{badge_type}}
{{/tabs}}

{{has_overflow}}
{{#overflow_items}}...{{/overflow_items}}
```

### sidebar_navigation.mustache

```mustache
{{! Variables disponibles }}
{{#categories}}
    {{key}}
    {{text}}
    {{icon_class}}
    {{expanded}}
    {{has_children}}
    {{#items}}
        {{key}}
        {{text}}
        {{url}}
        {{active}}
        {{has_badge}}
        {{badge}}
    {{/items}}
{{/categories}}
```

---

## Configuración Global

### config.php

```php
// Habilitar/deshabilitar componentes
$CFG->navigation_primary_enabled = true;
$CFG->navigation_secondary_enabled = true;
$CFG->navigation_sidebar_enabled = true;

// Configuración de caché
$CFG->navigation_cache_ttl = 3600; // 1 hora

// Configuración de breakpoints
$CFG->navigation_mobile_breakpoint = 768;
$CFG->navigation_tablet_breakpoint = 1200;
```

### Archivo de navegación personalizada

```php
// config/navigation.php
return [
    'primary' => [
        ['key' => 'home', 'text' => 'Inicio', 'url' => '/', 'icon' => 'fa-home', 'order' => 10],
        ['key' => 'courses', 'text' => 'Cursos', 'url' => '/course', 'icon' => 'fa-graduation-cap', 'order' => 20],
    ],
    'sidebar' => [
        'categories' => [
            [
                'key' => 'main',
                'text' => 'Principal',
                'icon' => 'fa-home',
                'items' => [
                    ['key' => 'dashboard', 'text' => 'Panel', 'url' => '/my'],
                ]
            ]
        ]
    ]
];
```

---

## Troubleshooting

### Problema: Nodo no aparece

```php
// Verificar visibilidad
$node->is_visible(); // true?

// Verificar acceso
$node->check_access($USER->id); // true?

// Verificar que fue agregado al árbol
$tree->find_node('my_node'); // no null?
```

### Problema: Estilos no se aplican

```bash
# Recompilar SCSS
php admin/cli/build_scss.php

# Limpiar caché de tema
php admin/cli/purge_caches.php --theme
```

### Problema: JavaScript no funciona

```javascript
// Verificar que DOM está listo
document.addEventListener('DOMContentLoaded', () => {
    console.log('Primary Nav:', window.NexoPrimaryNavigation);
    console.log('Secondary Nav:', window.NexoSecondaryNavigation);
    console.log('Sidebar Nav:', window.NexoSidebarNavigation);
});
```

---

## Versionado de la API

| Versión | Fecha | Cambios |
|---------|-------|---------|
| 1.0.0 | 2025-01 | Versión inicial |
| 1.1.0 | 2025-01 | Agregado soporte para badges |
| 1.2.0 | 2025-01 | Agregado mobile drawer |

---

## Referencias

- [NAVIGATION_ARCHITECTURE.md](NAVIGATION_ARCHITECTURE.md) - Arquitectura general
- [NAVIGATION_ISER_BRANDING.md](NAVIGATION_ISER_BRANDING.md) - Guía de branding
- [TESTING_REPORT.md](TESTING_REPORT.md) - Reporte de pruebas
