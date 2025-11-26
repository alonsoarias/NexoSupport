# Arquitectura del Sistema de Navegación - NexoSupport

## Visión General

El sistema de navegación de NexoSupport sigue la arquitectura de Moodle 4.x, implementando una jerarquía de tres niveles con soporte para permisos RBAC y branding ISER.

## Estructura de Capas

```
┌─────────────────────────────────────────────────────────────────┐
│                    NAVEGACIÓN PRIMARIA (Header)                  │
│  Logo │ Dashboard │ Admin │ Profile │ ··· │ Notificaciones │ User│
├─────────────────────────────────────────────────────────────────┤
│                  NAVEGACIÓN SECUNDARIA (Tabs)                    │
│  Tab 1 │ Tab 2 │ Tab 3 │ Tab 4 │ Tab 5 │ Más ▼                  │
├──────────────────┬──────────────────────────────────────────────┤
│     SIDEBAR      │              CONTENIDO PRINCIPAL              │
│                  │                                               │
│  ▼ Administración│   Breadcrumbs: Inicio > Admin > Usuarios      │
│    • Usuarios    │                                               │
│    • Roles       │   ┌─────────────────────────────────────┐     │
│    • Config      │   │                                     │     │
│                  │   │         Área de Contenido           │     │
│  ▼ Mi Perfil     │   │                                     │     │
│    • Ver         │   │                                     │     │
│    • Editar      │   └─────────────────────────────────────┘     │
└──────────────────┴──────────────────────────────────────────────┘
```

## Componentes PHP

### 1. Navegación Primaria (`primary_navigation.php`)

Maneja la barra de navegación principal (header).

```php
namespace core\navigation;

class primary_navigation {
    // Nodos de navegación a nivel de sitio
    protected array $nodes = [];

    // Métodos principales
    public function add_node(navigation_node $node): self;
    public function set_active(string $key): self;
    public function get_visible_nodes(): array;
    public function export_for_template(): array;
}
```

### 2. Navegación Secundaria (`secondary_navigation.php`)

Maneja las pestañas contextuales basadas en la página actual.

```php
namespace core\navigation;

class secondary_navigation {
    protected array $tabs = [];
    protected int $max_visible_tabs = 5;

    // Factory methods para diferentes contextos
    public static function for_context(string $context): self;
    public static function for_admin_context(): self;
    public static function for_user_context(): self;

    // Métodos principales
    public function add_tab(navigation_node $node): self;
    public function get_visible_tabs(): array;
    public function get_more_menu_tabs(): array;
}
```

### 3. Nodo de Navegación (`navigation_node.php`)

Representa un elemento individual en cualquier nivel de navegación.

```php
namespace core\navigation;

class navigation_node {
    const TYPE_CATEGORY = 'category';
    const TYPE_ITEM = 'item';
    const TYPE_SEPARATOR = 'separator';

    // Propiedades principales
    private string $key;
    private string $type;
    private ?string $text;
    private ?string $url;
    private ?string $icon;
    private int $order;
    private bool $visible;
    private bool $active;
    private ?string $capability;
    private ?string $badge;
    private bool $divider_after;
}
```

### 4. Árbol de Navegación (`navigation_tree.php`)

Maneja la estructura jerárquica completa del sidebar.

```php
namespace core\navigation;

class navigation_tree {
    private array $nodes = [];
    private array $roots = [];

    public function add_node(navigation_node $node, ?string $parent_key): self;
    public function get_breadcrumbs(): array;
    public function filter_by_permissions(): self;
    public function to_array(): array;
}
```

### 5. Constructor de Navegación (`navigation_builder.php`)

API fluida para construir estructuras de navegación.

```php
namespace core\navigation;

class navigation_builder {
    public function add_category(string $key, array $config): self;
    public function add_item(string $key, array $config): self;
    public function add_separator(string $key): self;
    public function build(bool $filter_permissions = true): navigation_tree;
}
```

## Flujo de Datos

```
1. Request → Router → Page Controller
                          │
2. $PAGE->initialize_navigation()
                          │
   ┌──────────────────────┼──────────────────────┐
   │                      │                      │
   ▼                      ▼                      ▼
primary_navigation   secondary_navigation   navigation_tree
   │                      │                      │
   │ export_for_template()│                      │ to_array()
   ▼                      ▼                      ▼
   └──────────────────────┼──────────────────────┘
                          │
3. renderer.php → header()
                          │
   ┌──────────────────────┼──────────────────────┐
   │                      │                      │
   ▼                      ▼                      ▼
primary_navigation   secondary_navigation   sidebar_navigation
    _renderer.php        _renderer.php         _renderer.php
                          │
4. HTML Output ←──────────┘
```

## Sistema de Permisos

Cada nodo puede requerir capabilities para ser visible:

```php
// Capability única
$builder->add_item('users', [
    'capability' => 'nexosupport/admin:manageusers',
]);

// Múltiples capabilities (OR logic)
$builder->add_item('reports', [
    'capabilities' => [
        'nexosupport/admin:viewreports',
        'nexosupport/admin:viewlogs',
    ],
]);
```

El método `check_access()` verifica:
1. Si el usuario es site admin → acceso total
2. Si no hay capabilities → acceso permitido
3. Verifica capability única
4. Verifica capabilities múltiples (cualquier match)

## Templates Mustache

| Template | Propósito |
|----------|-----------|
| `primary_navigation.mustache` | Header principal |
| `secondary_navigation.mustache` | Tabs contextuales |
| `sidebar.mustache` | Navegación lateral |
| `sidebar_node.mustache` | Nodo individual (recursivo) |
| `breadcrumbs.mustache` | Migas de pan |
| `mobile_drawer.mustache` | Drawer móvil |
| `user_menu.mustache` | Menú de usuario |

## JavaScript Modules

| Módulo | Funcionalidad |
|--------|---------------|
| `primary-navigation.js` | Dropdown usuario, hamburger |
| `secondary-navigation.js` | Overflow tabs, "More" menu |
| `sidebar-navigation.js` | Collapse/expand, localStorage |
| `mobile-drawer.js` | Swipe, focus trap |

## Responsive Breakpoints

| Breakpoint | Comportamiento |
|------------|----------------|
| Desktop (>1200px) | Todo visible, sidebar expandido |
| Tablet (768-1200px) | Sidebar colapsable, tabs scroll |
| Mobile (<768px) | Hamburger menu, drawer overlay |

## Extensibilidad

### Agregar Items a Navegación Primaria

```php
$PAGE->primary_nav->add_node(new navigation_node('custom',
    navigation_node::TYPE_ITEM, [
        'text' => 'Mi Item',
        'url' => '/custom',
        'icon' => 'fa-star',
        'order' => 50,
    ]
));
```

### Crear Contexto Secundario Personalizado

```php
class secondary_navigation {
    public static function for_custom_context(): self {
        $nav = new self('custom');
        $nav->add_tab(new navigation_node('tab1', ...));
        return $nav;
    }
}
```

### Agregar Badge a Nodo

```php
$node->set_badge('5', 'warning');
```

## Archivos Clave

```
lib/classes/navigation/
├── navigation_node.php
├── navigation_tree.php
├── navigation_builder.php
├── primary_navigation.php
├── primary_navigation_renderer.php
├── secondary_navigation.php
├── secondary_navigation_renderer.php
└── sidebar_navigation_renderer.php

templates/navigation/
├── primary_navigation.mustache
├── secondary_navigation.mustache
├── sidebar.mustache
├── sidebar_node.mustache
├── breadcrumbs.mustache
├── mobile_drawer.mustache
└── user_menu.mustache

public_html/js/navigation/
├── primary-navigation.js
├── secondary-navigation.js
├── sidebar-navigation.js
└── mobile-drawer.js

theme/core/scss/navigation/
├── _iser-branding.scss
├── _primary.scss
├── _secondary.scss
├── _sidebar.scss
├── _breadcrumbs.scss
├── _mobile.scss
└── navigation.scss
```
