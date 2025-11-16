# FASE 7: IMPLEMENTACIÓN COMPLETA DE TEMAS

**Fecha Inicio:** 2024-11-16
**Estado:** 📋 PLANIFICACIÓN
**Prioridad:** 🟡 MEDIA (Frontend y UX)

---

## 📋 RESUMEN EJECUTIVO

La Fase 7 completará la implementación de los sistemas de temas **theme_core** y **theme_iser** con todos los assets visuales, CSS, JavaScript, templates Mustache y layouts funcionales.

En Fase 5 creamos la estructura Frankenstyle básica (version.php, lib.php, README.md). Ahora vamos a implementar:

1. **CSS Completo** - Estilos responsive y accesibles
2. **JavaScript** - Interactividad y dark mode
3. **Templates Mustache** - Sistema de plantillas
4. **Layouts** - Estructuras de página
5. **Sistema de Configuración** - Theme switcher

---

## 🎯 OBJETIVOS

### Objetivos Principales

1. ✅ Implementar CSS completo para ambos temas
2. ✅ Crear sistema de templates Mustache
3. ✅ Desarrollar layouts responsive
4. ✅ Implementar JavaScript para interactividad
5. ✅ Crear theme switcher/configurator
6. ✅ Soporte para dark mode (theme_iser)
7. ✅ WCAG 2.1 Level AA compliance

### Métricas Esperadas

- **Archivos CSS:** ~8 archivos
- **Archivos JS:** ~4 archivos
- **Templates:** ~10 templates
- **Layouts:** ~5 layouts
- **Total archivos:** ~27-30
- **Líneas estimadas:** ~2,500-3,000
- **Tiempo estimado:** 2-3 horas

---

## 🎨 COMPONENTE 1: THEME_CORE (Tema Base)

### Estado Actual (Fase 5)

**Existente:**
- ✅ version.php (metadata Frankenstyle)
- ✅ lib.php (2 capabilities, 6 funciones, 3 layouts)
- ✅ README.md (documentación completa)

**Faltante:**
- ❌ CSS (styles/)
- ❌ JavaScript (scripts/)
- ❌ Templates (templates/)
- ❌ Layouts implementados
- ❌ Configuración visual

### Archivos a Crear

#### 1. Estructura de Directorios

```
theme/core/
├── version.php (existente)
├── lib.php (existente)
├── README.md (existente)
├── config.php (NUEVO - configuración del tema)
├── styles/
│   ├── main.css (NUEVO - estilos principales)
│   ├── variables.css (NUEVO - CSS custom properties)
│   ├── reset.css (NUEVO - normalize/reset)
│   └── responsive.css (NUEVO - media queries)
├── scripts/
│   ├── theme.js (NUEVO - funcionalidad del tema)
│   └── menu.js (NUEVO - navegación responsive)
├── templates/
│   ├── header.mustache (NUEVO)
│   ├── footer.mustache (NUEVO)
│   ├── navigation.mustache (NUEVO)
│   └── layouts/
│       ├── base.mustache (NUEVO)
│       ├── standard.mustache (NUEVO)
│       └── fullwidth.mustache (NUEVO)
└── images/
    └── logo.svg (NUEVO - logo placeholder)
```

#### 2. theme/core/config.php (~50 líneas)
**Funcionalidad:**
- Configuración del tema
- Colores por defecto
- Fuentes
- Opciones generales

```php
<?php
return [
    'name' => 'Core',
    'version' => '2.0.0',
    'colors' => [
        'primary' => '#0066cc',
        'secondary' => '#6c757d',
        'success' => '#28a745',
        'danger' => '#dc3545',
        'warning' => '#ffc107',
        'info' => '#17a2b8',
    ],
    'fonts' => [
        'base' => 'system-ui, -apple-system, sans-serif',
        'headings' => 'system-ui, -apple-system, sans-serif',
        'monospace' => 'Monaco, Consolas, monospace',
    ],
    'layout' => [
        'max_width' => '1200px',
        'sidebar_width' => '280px',
    ],
];
```

#### 3. theme/core/styles/variables.css (~100 líneas)
**CSS Custom Properties:**

```css
:root {
    /* Colors */
    --color-primary: #0066cc;
    --color-secondary: #6c757d;
    --color-success: #28a745;
    --color-danger: #dc3545;

    /* Typography */
    --font-base: system-ui, -apple-system, sans-serif;
    --font-size-base: 16px;
    --line-height-base: 1.5;

    /* Spacing */
    --spacing-xs: 0.25rem;
    --spacing-sm: 0.5rem;
    --spacing-md: 1rem;
    --spacing-lg: 1.5rem;
    --spacing-xl: 2rem;

    /* Layout */
    --max-width: 1200px;
    --sidebar-width: 280px;

    /* Borders */
    --border-radius: 4px;
    --border-color: #dee2e6;

    /* Shadows */
    --shadow-sm: 0 1px 3px rgba(0,0,0,0.1);
    --shadow-md: 0 4px 6px rgba(0,0,0,0.1);
}
```

#### 4. theme/core/styles/main.css (~400 líneas)
**Estilos principales:**
- Layout general
- Componentes básicos
- Formularios
- Tablas
- Botones
- Cards
- Navigation

#### 5. theme/core/templates/header.mustache (~80 líneas)
**Template de header:**
- Logo
- Navegación principal
- User menu
- Búsqueda

#### 6. theme/core/templates/layouts/standard.mustache (~120 líneas)
**Layout estándar:**
- Header
- Sidebar
- Content area
- Footer

### Estimación theme_core

- **Archivos:** 13
- **Líneas:** ~1,200
- **Tiempo:** 60-75 minutos

---

## 🎨 COMPONENTE 2: THEME_ISER (Tema Corporativo)

### Estado Actual (Fase 5)

**Existente:**
- ✅ version.php (metadata Frankenstyle)
- ✅ lib.php (3 capabilities, 10 funciones, 5 layouts, 4 color schemes)
- ✅ README.md (documentación completa 287 líneas)

**Faltante:**
- ❌ CSS con soporte dark mode
- ❌ JavaScript con theme switcher
- ❌ Templates Mustache
- ❌ Layouts implementados
- ❌ Sistema de personalización

### Archivos a Crear

#### 1. Estructura de Directorios

```
theme/iser/
├── version.php (existente)
├── lib.php (existente)
├── README.md (existente)
├── config.php (NUEVO - configuración ISER)
├── styles/
│   ├── main.css (NUEVO - estilos base)
│   ├── variables.css (NUEVO - CSS variables ISER)
│   ├── dark-mode.css (NUEVO - tema oscuro)
│   ├── components.css (NUEVO - componentes)
│   ├── responsive.css (NUEVO - responsive)
│   └── layouts/
│       ├── base.css (NUEVO)
│       ├── two-column.css (NUEVO)
│       └── landing.css (NUEVO)
├── scripts/
│   ├── theme.js (NUEVO - funcionalidad)
│   ├── dark-mode.js (NUEVO - toggle dark mode)
│   ├── customizer.js (NUEVO - theme customizer)
│   └── menu.js (NUEVO - navegación)
├── templates/
│   ├── header.mustache (NUEVO)
│   ├── footer.mustache (NUEVO)
│   ├── navigation.mustache (NUEVO)
│   ├── breadcrumbs.mustache (NUEVO)
│   ├── user-menu.mustache (NUEVO)
│   └── layouts/
│       ├── base.mustache (NUEVO)
│       ├── standard.mustache (NUEVO)
│       ├── fullwidth.mustache (NUEVO)
│       ├── two-column.mustache (NUEVO)
│       └── landing.mustache (NUEVO)
└── images/
    ├── logo.svg (NUEVO - ISER logo)
    ├── logo-dark.svg (NUEVO - logo dark mode)
    └── favicon.ico (NUEVO)
```

#### 2. theme/iser/config.php (~100 líneas)
**Configuración ISER:**
- 4 color schemes predefinidos
- Opciones de personalización
- Dark mode settings
- Logo paths

```php
<?php
return [
    'name' => 'ISER',
    'version' => '2.0.0',
    'color_schemes' => [
        'default' => [
            'primary' => '#1e3a8a',   // ISER Blue
            'secondary' => '#059669', // ISER Green
            'accent' => '#dc2626',
        ],
        'ocean' => [...],
        'forest' => [...],
        'sunset' => [...],
    ],
    'dark_mode' => [
        'enabled' => true,
        'auto_detect' => true,
        'default' => false,
    ],
    'customization' => [
        'logo' => true,
        'favicon' => true,
        'custom_css' => true,
        'color_picker' => true,
    ],
];
```

#### 3. theme/iser/styles/variables.css (~200 líneas)
**CSS Custom Properties con soporte dark mode:**

```css
:root {
    /* Light Mode Colors */
    --color-primary: #1e3a8a;
    --color-secondary: #059669;
    --color-accent: #dc2626;
    --color-bg: #ffffff;
    --color-text: #1f2937;

    /* ... más variables ... */
}

[data-theme="dark"] {
    /* Dark Mode Colors */
    --color-primary: #3b82f6;
    --color-secondary: #10b981;
    --color-accent: #ef4444;
    --color-bg: #1f2937;
    --color-text: #f9fafb;

    /* ... más variables ... */
}
```

#### 4. theme/iser/styles/dark-mode.css (~150 líneas)
**Estilos específicos dark mode:**
- Overrides para dark theme
- Transiciones suaves
- Ajustes de contraste

#### 5. theme/iser/scripts/dark-mode.js (~120 líneas)
**Funcionalidad dark mode:**
- Toggle dark/light
- Persistencia (localStorage)
- Auto-detect system preference
- Smooth transitions

```javascript
class DarkModeToggle {
    constructor() {
        this.init();
    }

    init() {
        // Load saved preference or detect system
        const saved = localStorage.getItem('theme');
        const systemPreference = window.matchMedia('(prefers-color-scheme: dark)').matches;

        this.setTheme(saved || (systemPreference ? 'dark' : 'light'));
        this.attachListeners();
    }

    setTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('theme', theme);
    }

    toggle() {
        const current = document.documentElement.getAttribute('data-theme');
        this.setTheme(current === 'dark' ? 'light' : 'dark');
    }
}
```

#### 6. theme/iser/scripts/customizer.js (~200 líneas)
**Theme Customizer:**
- Color picker para primary/secondary/accent
- Upload de logo
- Preview en tiempo real
- Save settings
- Reset to defaults

#### 7. theme/iser/templates/layouts/two-column.mustache (~150 líneas)
**Layout two-column:**
- Header
- Left sidebar
- Content
- Right sidebar
- Footer

### Estimación theme_iser

- **Archivos:** 20
- **Líneas:** ~1,800
- **Tiempo:** 90-120 minutos

---

## 🔧 COMPONENTE 3: SISTEMA DE TEMAS

### Archivos Globales a Crear

#### 1. lib/classes/theme/theme_manager.php (~300 líneas)
**Clase:** `ISER\Core\Theme\ThemeManager`

**Funcionalidad:**
- Gestión de temas instalados
- Tema activo
- Compilación de CSS
- Carga de templates
- Configuración

**Métodos:**
- `get_active_theme()` - Obtener tema activo
- `set_active_theme($name)` - Cambiar tema
- `get_available_themes()` - Listar temas disponibles
- `load_template($name, $data)` - Cargar template Mustache
- `compile_css($theme)` - Compilar/minificar CSS
- `get_theme_config($theme)` - Obtener configuración

#### 2. lib/classes/theme/mustache_engine.php (~200 líneas)
**Clase:** `ISER\Core\Theme\MustacheEngine`

**Funcionalidad:**
- Wrapper para Mustache.php
- Template caching
- Helper functions
- Partials support

**Métodos:**
- `render($template, $data)` - Renderizar template
- `add_helper($name, $callback)` - Agregar helper
- `add_partial($name, $template)` - Agregar partial
- `clear_cache()` - Limpiar cache

#### 3. admin/theme/index.php (~250 líneas)
**Interfaz de Configuración de Temas:**
- Selector de tema (theme_core / theme_iser)
- Preview de temas
- Configuración del tema activo
- Theme customizer (para ISER)
- Estadísticas de uso

**Características:**
- Grid de temas disponibles
- Live preview
- Settings panel
- Save/Reset buttons

### Estimación Sistema de Temas

- **Archivos:** 3
- **Líneas:** ~750
- **Tiempo:** 45-60 minutos

---

## 📊 RESUMEN DE FASE 7

### Totales Estimados

| Componente | Archivos | Líneas | Tiempo |
|------------|:--------:|:------:|:------:|
| **theme_core** | 13 | ~1,200 | 60-75m |
| **theme_iser** | 20 | ~1,800 | 90-120m |
| **Theme System** | 3 | ~750 | 45-60m |
| **TOTAL** | **36** | **~3,750** | **3-4h** |

### Distribución de Archivos

```
Total: 36 archivos
├── CSS: 11 archivos (~1,500 líneas)
├── JavaScript: 7 archivos (~800 líneas)
├── Templates: 13 archivos (~900 líneas)
├── PHP Classes: 3 archivos (~750 líneas)
├── Config: 2 archivos (~150 líneas)
└── Images: Varios SVG/PNG
```

### Tecnologías

- **CSS3**: Custom Properties, Flexbox, Grid
- **JavaScript ES6+**: Classes, modules
- **Mustache**: Logic-less templates
- **Responsive**: Mobile-first
- **Accessibility**: WCAG 2.1 Level AA
- **Performance**: CSS minification, lazy loading

---

## ✅ CRITERIOS DE ACEPTACIÓN

### theme_core

- [ ] CSS completo y responsive
- [ ] 3 layouts funcionales (base, standard, fullwidth)
- [ ] Templates Mustache para header/footer/navigation
- [ ] JavaScript para menú responsive
- [ ] WCAG 2.1 Level AA compliant
- [ ] Sin dependencias externas (CDN-free)

### theme_iser

- [ ] CSS completo con dark mode
- [ ] 5 layouts funcionales
- [ ] Dark mode toggle funcional
- [ ] Theme customizer (color picker, logo upload)
- [ ] 4 color schemes predefinidos
- [ ] Templates Mustache completos
- [ ] Persistencia de preferencias (localStorage)
- [ ] Smooth transitions entre temas

### Sistema de Temas

- [ ] Theme Manager funcional
- [ ] Mustache Engine con caching
- [ ] Interfaz de configuración (admin/theme/)
- [ ] Theme switcher funcional
- [ ] Preview de temas
- [ ] Save/Load configuración

---

## 🎨 CARACTERÍSTICAS DE DISEÑO

### theme_core (Minimalista)

**Filosofía:** Clean, simple, performant

- **Colores:** Paleta limitada (primary, secondary, grays)
- **Typography:** System fonts (cero overhead)
- **Layout:** Estructura básica, sin adornos
- **Components:** Esenciales (forms, tables, buttons, cards)
- **Performance:** CSS < 30KB, JS < 10KB

### theme_iser (Corporativo Avanzado)

**Filosofía:** Branded, customizable, feature-rich

- **Colores:** 4 esquemas predefinidos + custom
- **Typography:** Web fonts opcionales
- **Dark Mode:** Completo con auto-detect
- **Layouts:** 5 variantes para diferentes usos
- **Components:** Extensos (breadcrumbs, sidebars, etc.)
- **Customization:** Logo, colores, CSS custom
- **Performance:** CSS < 50KB, JS < 25KB

---

## 🔒 ACCESIBILIDAD (WCAG 2.1 Level AA)

### Requisitos

- [x] Contraste mínimo 4.5:1 (texto normal)
- [x] Contraste mínimo 3:1 (texto grande)
- [x] Navegación por teclado completa
- [x] Focus indicators visibles
- [x] ARIA labels apropiados
- [x] Semántica HTML correcta
- [x] Responsive sin zoom horizontal
- [x] Textos alternativos en imágenes

### Testing

- Validar con Wave (extensión Chrome)
- Lighthouse accessibility score > 90
- Keyboard navigation testing
- Screen reader testing (NVDA/JAWS)

---

## 📱 RESPONSIVE DESIGN

### Breakpoints

```css
/* Mobile first */
:root {
    --breakpoint-sm: 576px;  /* Phones */
    --breakpoint-md: 768px;  /* Tablets */
    --breakpoint-lg: 992px;  /* Desktop */
    --breakpoint-xl: 1200px; /* Large desktop */
}
```

### Estrategia

1. **Mobile First**: Estilos base para móvil
2. **Progressive Enhancement**: Media queries para pantallas grandes
3. **Flexible Grid**: CSS Grid + Flexbox
4. **Fluid Typography**: clamp() para tamaños de fuente
5. **Touch Targets**: Min 44x44px para botones

---

## 🚀 PERFORMANCE

### Optimizaciones

- **CSS Minification**: Reducir tamaño ~40%
- **Critical CSS**: Inline para above-the-fold
- **Lazy Loading**: Imágenes y JS no crítico
- **HTTP/2**: Múltiples archivos CSS sin penalty
- **Caching**: Cache headers apropiados
- **No CDN Dependencies**: Todo local

### Métricas Objetivo

- First Contentful Paint (FCP): < 1.8s
- Largest Contentful Paint (LCP): < 2.5s
- Cumulative Layout Shift (CLS): < 0.1
- Time to Interactive (TTI): < 3.5s

---

## 📚 DOCUMENTACIÓN A CREAR

1. **FASE_7_IMPLEMENTACION_TEMAS.md** - Reporte completo de Fase 7
2. **theme/core/DEVELOPMENT.md** - Guía de desarrollo para theme_core
3. **theme/iser/CUSTOMIZATION.md** - Guía de personalización para theme_iser

---

## 🔄 ORDEN DE IMPLEMENTACIÓN

### Paso 1: Theme System (Base)
1. lib/classes/theme/theme_manager.php
2. lib/classes/theme/mustache_engine.php
3. Instalar Mustache.php (composer o vendor)

### Paso 2: theme_core (Básico primero)
1. config.php
2. styles/variables.css
3. styles/reset.css
4. styles/main.css
5. styles/responsive.css
6. templates/header.mustache
7. templates/footer.mustache
8. templates/layouts/standard.mustache
9. scripts/theme.js
10. scripts/menu.js

### Paso 3: theme_iser (Sobre base de core)
1. config.php (con color schemes)
2. styles/variables.css (con dark mode)
3. styles/main.css
4. styles/dark-mode.css
5. styles/components.css
6. styles/responsive.css
7. templates/ (todos)
8. scripts/dark-mode.js
9. scripts/customizer.js
10. scripts/theme.js

### Paso 4: Admin Interface
1. admin/theme/index.php
2. Integration testing
3. Theme switcher testing

---

## ✨ CONCLUSIÓN

La Fase 7 transformará NexoSupport de un backend robusto a una aplicación web completa con:

- ✅ **2 temas profesionales** (minimalista y corporativo)
- ✅ **Dark mode** completo
- ✅ **Responsive design** mobile-first
- ✅ **Accesibilidad WCAG 2.1** Level AA
- ✅ **Sistema de personalización** avanzado
- ✅ **Performance optimizado**

Con esta fase, NexoSupport tendrá una interfaz visual profesional lista para producción.

---

**Estado:** 📋 PLAN COMPLETO
**Siguiente Acción:** Comenzar implementación con Theme System

---

## 🎯 FASE 7 LISTA PARA IMPLEMENTACIÓN ✅
