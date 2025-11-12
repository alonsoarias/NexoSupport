# ESPECIFICACIÓN: THEME CONFIGURABLE

**Fecha**: 2025-11-12  
**Proyecto**: NexoSupport - Fase 4

---

## 1. OBJETIVO

Convertir el theme ISER existente en un **theme totalmente configurable desde el panel admin**, permitiendo personalización de colores, tipografía, logos, layouts y modo oscuro sin modificar código.

## 2. ESTADO ACTUAL

**Ya implementado**:
- ✅ Theme base ISER en `/modules/Theme/Iser/`
- ✅ Configuración parcial (solo colores) vía BD
- ✅ Preferencias de usuario (dark mode, sidebar collapsed)

**Falta**:
- ❌ UI de configuración en `/admin/appearance`
- ❌ Configurar tipografía (fuentes, tamaños)
- ❌ Configurar layouts (sidebar position, container width)
- ❌ Subir logos personalizados
- ❌ Preview en tiempo real
- ❌ Theme plugins que sobrescriban el core

## 3. ARQUITECTURA

### 3.1 Componentes

**ThemeConfigurator** (NUEVO):
- Gestor de configuración del theme
- CRUD de settings en tabla `theme_config`
- Validación de valores (colores HEX, fuentes válidas)
- Cache de configuración

**ThemePreview** (NUEVO):
- Renderizar vista previa sin guardar
- Inyectar CSS dinámico en preview
- Iframe aislado para preview

**AppearanceController** (NUEVO):
- Controlador para `/admin/appearance`
- Mostrar configurador
- Procesar cambios
- Reset a defaults

### 3.2 Configuraciones Soportadas

**1. Colores** (expandir existente):
- Primary, secondary, success, danger, warning, info
- Background, text, borders
- Link colors, hover states

**2. Tipografía** (NUEVO):
- Fuente de títulos (Google Fonts)
- Fuente de cuerpo (Google Fonts)
- Tamaños: xs, sm, base, lg, xl, 2xl
- Line heights y letter spacing

**3. Layouts** (NUEVO):
- Layout por defecto (with-sidebar, fullwidth)
- Sidebar position (left, right)
- Container width (fluid, boxed-lg, boxed-xl)
- Topbar style (fixed, sticky, static)
- Footer visibility

**4. Logo e Identidad** (NUEVO):
- Logo principal (upload)
- Logo small (para sidebar colapsado)
- Favicon (upload)
- Nombre del sistema
- Slogan/tagline

**5. Modo Oscuro** (mejorar existente):
- Enable/disable
- Auto según hora del día
- Toggle manual por usuario
- Colores específicos para dark mode

## 4. UI DE CONFIGURACIÓN

**Ubicación**: `/admin/appearance`  
**Permiso**: `settings.update`

**Estructura de la página**:

```
┌─────────────────────────────────────────────────────────────┐
│  Appearance Settings                                         │
│  ┌────────────┬─────────────────────────────────────────┐   │
│  │ - General  │  [ PREVIEW PANEL ]                      │   │
│  │ - Colors   │                                          │   │
│  │ - Typography│  Muestra cambios en tiempo real        │   │
│  │ - Layouts  │                                          │   │
│  │ - Logo     │                                          │   │
│  │ - Dark Mode│                                          │   │
│  └────────────┴─────────────────────────────────────────┘   │
│                                                              │
│  [Guardar Cambios]  [Previsualizar]  [Restaurar Defaults]  │
└─────────────────────────────────────────────────────────────┘
```

**Tabs de configuración**:

1. **General**:
   - Nombre del sitio
   - Slogan
   - Timezone
   - Language por defecto

2. **Colors**:
   - Color pickers para cada color
   - Paleta de colores predefinida
   - Preview de componentes con colores aplicados

3. **Typography**:
   - Select de Google Fonts para títulos
   - Select de Google Fonts para cuerpo
   - Sliders para tamaños de fuente

4. **Layouts**:
   - Radio buttons para layout por defecto
   - Radio buttons para sidebar position
   - Select para container width
   - Checkboxes para topbar y footer

5. **Logo**:
   - Upload de logo principal
   - Upload de logo small
   - Upload de favicon
   - Preview de logos subidos

6. **Dark Mode**:
   - Toggle enable/disable
   - Toggle auto según hora
   - Color pickers para dark mode colors

## 5. PREVIEW EN TIEMPO REAL

**Funcionamiento**:
1. Cambiar configuración → Trigger evento JavaScript
2. Generar CSS dinámico con nuevos valores
3. Inyectar CSS en iframe de preview
4. Actualizar preview sin guardar en BD

**Tecnologías**:
- Iframe con página de ejemplo
- PostMessage para comunicación
- CSS variables para cambios dinámicos

## 6. THEME PLUGINS

**Concepto**: Plugins de tipo `theme` que sobrescriben completamente la UI del core.

**Funcionamiento**:
1. Instalar theme plugin en `/modules/plugins/themes/{slug}/`
2. Theme plugin tiene layouts, components, assets propios
3. Activar theme plugin desde `/admin/appearance`
4. Solo UN theme plugin activo a la vez
5. Core theme siempre es fallback si falta un template

**Prioridad de búsqueda de templates**:
```
1. Theme plugin activo (si existe)
2. Theme core ISER
3. Error si no encuentra
```

## 7. BASE DE DATOS

**Tabla: theme_config**:
- id, config_key, config_value, config_group, config_type, updated_at

**Grupos de configuración**:
- `colors`, `typography`, `layouts`, `identity`, `dark_mode`

**Tabla: theme_presets** (OPCIONAL):
- id, name, description, config_json, is_default, created_at
- Permite guardar presets de configuración completa

## 8. CRITERIOS DE ÉXITO

✅ Admin puede:
1. Cambiar colores desde UI y ver preview
2. Cambiar fuentes y tamaños
3. Subir logo personalizado
4. Cambiar layout (sidebar position)
5. Activar/desactivar modo oscuro
6. Ver preview antes de guardar
7. Restaurar a configuración default
8. Instalar theme plugin y activarlo

---

**FIN ESPECIFICACIÓN FASE 4**
