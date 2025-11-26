# Guía de Branding ISER - Sistema de Navegación NexoSupport

## Paleta de Colores Oficial

### Colores Primarios (70% del diseño)

| Color | Hex | RGB | Uso Principal |
|-------|-----|-----|---------------|
| **Verde ISER** | `#1B9E88` | rgb(27, 158, 136) | Color principal, header, links activos |
| **Amarillo ISER** | `#FCBD05` | rgb(252, 189, 7) | Acentos, indicadores activos, highlights |
| **Rojo ISER** | `#EB4335` | rgb(235, 67, 53) | Alertas, errores, acciones peligrosas |
| **Blanco** | `#FFFFFF` | rgb(255, 255, 255) | Fondos, textos sobre colores oscuros |

### Colores Secundarios (30% del diseño)

| Color | Hex | RGB | Uso Principal |
|-------|-----|-----|---------------|
| **Naranja** | `#E27C32` | rgb(226, 124, 50) | Badges warning |
| **Lima** | `#CFDA4B` | rgb(207, 218, 75) | Elementos destacados secundarios |
| **Azul** | `#5894EF` | rgb(88, 148, 239) | Info, links secundarios, gradiente |
| **Magenta** | `#C82260` | rgb(200, 34, 96) | Acentos especiales |

### Colores Neutrales

| Color | Hex | RGB | Uso Principal |
|-------|-----|-----|---------------|
| **Gris Claro** | `#CFCFCF` | rgb(207, 207, 207) | Bordes, separadores, backgrounds |
| **Gris Medio** | `#9C9C9B` | rgb(156, 156, 155) | Texto secundario, placeholders |
| **Gris Oscuro** | `#646363` | rgb(100, 100, 99) | Texto principal body |
| **Negro** | `#000000` | rgb(0, 0, 0) | Títulos, encabezados |

## Aplicación en Navegación

### Header (Navegación Primaria)

```scss
.nexo-header-primary {
    // Gradiente de Verde a Azul ISER
    background: linear-gradient(135deg, #1B9E88 0%, #5894EF 100%);
}

.nexo-nav-primary-item a {
    color: #FFFFFF;  // Texto blanco
}

.nexo-nav-primary-item.active a {
    // Indicador activo amarillo
    border-bottom: 3px solid #FCBD05;
}

.nexo-nav-primary-item a:hover {
    // Hover con overlay blanco semi-transparente
    background: rgba(255, 255, 255, 0.15);
}
```

### Navegación Secundaria (Tabs)

```scss
.nexo-nav-secondary {
    background: #FFFFFF;
    border-bottom: 1px solid #CFCFCF;
}

.nexo-nav-secondary-tab a {
    color: #646363;  // Gris oscuro inactivo
}

.nexo-nav-secondary-tab.active a {
    color: #1B9E88;  // Verde activo
    border-bottom: 3px solid #1B9E88;
}
```

### Sidebar

```scss
.nexo-sidebar {
    background: #FFFFFF;
    border-right: 1px solid #CFCFCF;
}

.nexo-sidebar-category-header {
    color: #000000;  // Negro para categorías
    font-weight: bold;
    text-transform: uppercase;
}

.nexo-sidebar-category-header i {
    color: #1B9E88;  // Iconos verde
}

.nexo-sidebar-item a {
    color: #646363;  // Gris oscuro
}

.nexo-sidebar-item.active a {
    background: rgba(27, 158, 136, 0.1);  // Background verde claro
    border-left: 4px solid #1B9E88;
    color: #1B9E88;
}
```

### Breadcrumbs

```scss
.nexo-breadcrumb-item a {
    color: #1B9E88;  // Links verde
}

.nexo-breadcrumb-item span {
    color: #9C9C9B;  // Texto gris medio
}

.nexo-breadcrumb-separator {
    color: #9C9C9B;
}
```

### Notificaciones

```scss
.nexo-notification-success {
    background: rgba(27, 158, 136, 0.1);
    border-left: 4px solid #1B9E88;
    color: #1B9E88;
}

.nexo-notification-error {
    background: rgba(235, 67, 53, 0.1);
    border-left: 4px solid #EB4335;
    color: #EB4335;
}

.nexo-notification-warning {
    background: rgba(252, 189, 7, 0.15);
    border-left: 4px solid #FCBD05;
    color: #8a6d00;
}

.nexo-notification-info {
    background: rgba(88, 148, 239, 0.1);
    border-left: 4px solid #5894EF;
    color: #5894EF;
}
```

### Botones

```scss
.nexo-btn-primary {
    background: #1B9E88;
    color: #FFFFFF;

    &:hover {
        background: #178a77;  // Verde más oscuro
    }
}

.nexo-btn-danger {
    background: #EB4335;
    color: #FFFFFF;
}
```

## Tipografía

### Fuentes Permitidas

| Uso | Fuente | Fallback |
|-----|--------|----------|
| Navegación | Verdana | Arial, sans-serif |
| Logo | Elza | Verdana, sans-serif |
| Contenido | Arial | sans-serif |

### Tamaños de Fuente

| Elemento | Tamaño | Peso |
|----------|--------|------|
| Logo | 20px | Bold |
| Nav Primary Items | 14px | 600 |
| Nav Secondary Tabs | 13px | 600 |
| Sidebar Categories | 12px | Bold |
| Sidebar Items | 13px | Normal |
| Breadcrumbs | 13px | Normal |

## Reglas de Diseño

### Hacer

- ✅ Usar gradiente verde-azul para header
- ✅ Usar amarillo ISER para indicadores activos en header
- ✅ Usar verde ISER para texto/bordes activos en sidebar
- ✅ Usar colores neutrales para fondos
- ✅ Mantener contraste mínimo 4.5:1 para accesibilidad
- ✅ Usar iconos Font Awesome en color verde ISER
- ✅ Máximo 2 tipografías por página

### No Hacer

- ❌ NO usar purple (#667eea) - color antiguo
- ❌ NO usar azul oscuro como primario
- ❌ NO usar más de 3 colores principales por sección
- ❌ NO usar tipografías decorativas
- ❌ NO usar colores fuera de la paleta ISER
- ❌ NO usar sombras excesivas

## Variables SCSS

```scss
// _iser-branding.scss

// Colores Primarios
$iser-verde: #1B9E88;
$iser-amarillo: #FCBD05;
$iser-rojo: #EB4335;
$iser-blanco: #FFFFFF;

// Colores Secundarios
$iser-naranja: #E27C32;
$iser-lima: #CFDA4B;
$iser-azul: #5894EF;
$iser-magenta: #C82260;

// Neutrales
$iser-gris-claro: #CFCFCF;
$iser-gris-medio: #9C9C9B;
$iser-gris-oscuro: #646363;
$iser-negro: #000000;

// Tipografías
$font-nav-primary: Verdana, Arial, sans-serif;
$font-nav-logo: Elza, Verdana, sans-serif;
```

## Variables CSS

```css
:root {
    --iser-verde: #1B9E88;
    --iser-amarillo: #FCBD05;
    --iser-rojo: #EB4335;
    --iser-blanco: #FFFFFF;
    --iser-naranja: #E27C32;
    --iser-lima: #CFDA4B;
    --iser-azul: #5894EF;
    --iser-magenta: #C82260;
    --iser-gris-claro: #CFCFCF;
    --iser-gris-medio: #9C9C9B;
    --iser-gris-oscuro: #646363;
    --iser-negro: #000000;
}
```

## Verificación de Branding

Antes de hacer deploy, verificar:

- [ ] Header usa gradiente verde (#1B9E88) a azul (#5894EF)
- [ ] No hay colores purple/violet en ningún elemento
- [ ] Texto sobre header es blanco (#FFFFFF)
- [ ] Indicador activo en header es amarillo (#FCBD05)
- [ ] Sidebar usa verde (#1B9E88) para activos
- [ ] Categorías de sidebar son negras (#000000) uppercase
- [ ] Iconos son verde ISER (#1B9E88)
- [ ] Links usan verde ISER
- [ ] Fondos son blancos o grises claros
- [ ] Bordes son gris claro (#CFCFCF)
