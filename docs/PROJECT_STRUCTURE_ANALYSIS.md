# 📊 Análisis Completo de Estructura del Proyecto NexoSupport

## 🎯 Objetivo
Analizar la estructura completa del proyecto para implementar el nuevo sistema de navegación de forma consistente en TODOS los componentes sin breaking changes.

---

## 📁 Estructura de Vistas Actual

### Layouts Existentes:
```
resources/views/layouts/
└── base.mustache          (Layout actual - simple con header/footer)
```

### Componentes:
```
resources/views/components/
├── header.mustache        (Logo ISER + título)
├── footer.mustache        (Footer institucional)
├── stats.mustache         (Estadísticas en dashboard)
└── card.mustache          (Card genérico)
```

### Vistas por Módulo:

#### 1. **Auth (Autenticación)**
```
resources/views/auth/
└── login.mustache         → AuthController::showLogin()
```
**Layout usado:** `base.mustache` (sin navegación)
**Navegación necesaria:** NO (es pantalla de login)

---

#### 2. **Home (Inicio)**
```
resources/views/home/
└── index.mustache         → HomeController::index()
```
**Layout usado:** `base.mustache`
**Navegación necesaria:** SÍ (dashboard principal)

---

#### 3. **Dashboard**
```
resources/views/dashboard/
└── index.mustache         → HomeController::dashboard()
```
**Layout usado:** `base.mustache`
**Navegación necesaria:** SÍ (dashboard de usuario)

---

#### 4. **Admin (Administración)**
```
resources/views/admin/
├── index.mustache         → AdminController::index()
├── settings.mustache      → AdminController::settings()
├── reports.mustache       → AdminController::reports()
├── security.mustache      → AdminController::security()
└── users.mustache         → (legacy?)
```
**Layout usado:** `base.mustache`
**Navegación necesaria:** SÍ (todas)

---

#### 5. **Admin > Users (Gestión Usuarios)**
```
resources/views/admin/users/
├── index.mustache         → UserManagementController::index()
├── create.mustache        → UserManagementController::create()
└── edit.mustache          → UserManagementController::edit()
```
**Layout usado:** `base.mustache`
**Navegación necesaria:** SÍ (todas)

---

#### 6. **Admin > Roles (Gestión Roles)**
```
resources/views/admin/roles/
├── index.mustache         → RoleController::index()
├── create.mustache        → RoleController::create()
└── edit.mustache          → RoleController::edit()
```
**Layout usado:** `base.mustache`
**Navegación necesaria:** SÍ (todas)

---

#### 7. **Admin > Permissions (Gestión Permisos)**
```
resources/views/admin/permissions/
├── index.mustache         → PermissionController::index()
├── create.mustache        → PermissionController::create()
└── edit.mustache          → PermissionController::edit()
```
**Layout usado:** `base.mustache`
**Navegación necesaria:** SÍ (todas)

---

## 🎨 Controladores y Rutas

### 1. AuthController
```php
Routes:
- GET  /login          → showLogin()    [auth/login.mustache]
- POST /login          → processLogin()
- GET  /logout         → logout()
```
**Navegación:** NO necesita sidebar (pantalla login)

---

### 2. HomeController
```php
Routes:
- GET  /               → index()        [home/index.mustache]
- GET  /dashboard      → dashboard()    [dashboard/index.mustache]
```
**Navegación:** SÍ necesita sidebar

---

### 3. AdminController
```php
Routes:
- GET  /admin          → index()        [admin/index.mustache]
- GET  /admin/settings → settings()     [admin/settings.mustache]
- GET  /admin/reports  → reports()      [admin/reports.mustache]
- GET  /admin/security → security()     [admin/security.mustache]
```
**Navegación:** SÍ necesita sidebar

---

### 4. UserManagementController
```php
Routes:
- GET  /admin/users         → index()   [admin/users/index.mustache]
- GET  /admin/users/create  → create()  [admin/users/create.mustache]
- POST /admin/users/edit    → edit()    [admin/users/edit.mustache]
- POST /admin/users/update  → update()
- POST /admin/users/delete  → delete()
- POST /admin/users/restore → restore()
```
**Navegación:** SÍ necesita sidebar

---

### 5. RoleController
```php
Routes:
- GET  /admin/roles        → index()    [admin/roles/index.mustache]
- GET  /admin/roles/create → create()   [admin/roles/create.mustache]
- POST /admin/roles/edit   → edit()     [admin/roles/edit.mustache]
- POST /admin/roles/update → update()
- POST /admin/roles/delete → delete()
```
**Navegación:** SÍ necesita sidebar

---

### 6. PermissionController
```php
Routes:
- GET  /admin/permissions        → index()  [admin/permissions/index.mustache]
- GET  /admin/permissions/create → create() [admin/permissions/create.mustache]
- POST /admin/permissions/edit   → edit()   [admin/permissions/edit.mustache]
- POST /admin/permissions/update → update()
- POST /admin/permissions/delete → delete()
```
**Navegación:** SÍ necesita sidebar

---

## 🗺️ Mapa de Navegación Propuesto

```
┌─────────────────────────────────────────────────────┐
│ TOPBAR                                              │
│ [Logo] [Breadcrumbs] ... [Search][Notif][User▼]   │
├──────────┬──────────────────────────────────────────┤
│ SIDEBAR  │                                          │
│          │  CONTENT AREA                            │
│ 🏠 Inicio│  (vistas se renderizan aquí)            │
│          │                                          │
│ ⚡ Admin │                                          │
│  👥 Users│                                          │
│  🛡️ Roles │                                          │
│  🔑 Perms │                                          │
│  📊 Dash │                                          │
│  ⚙️  Sets  │                                          │
│  📈 Repos │                                          │
│  🔒 Secur │                                          │
│          │                                          │
│ 👤 Perfil│                                          │
│ 🚪 Logout│                                          │
└──────────┴──────────────────────────────────────────┘
```

---

## 📋 Estructura de Menú del Sidebar

### Menú Principal:
```javascript
{
  sections: [
    {
      title: null,  // Sin título de sección
      items: [
        { icon: 'house-door', label: 'Inicio', url: '/', active: false }
      ]
    },
    {
      title: 'Administración',
      items: [
        { icon: 'speedometer2', label: 'Dashboard Admin', url: '/admin', active: false },
        { icon: 'people', label: 'Usuarios', url: '/admin/users', badge: count, active: false },
        { icon: 'shield-check', label: 'Roles', url: '/admin/roles', badge: count, active: false },
        { icon: 'key', label: 'Permisos', url: '/admin/permissions', badge: count, active: false }
      ]
    },
    {
      title: 'Sistema',
      items: [
        { icon: 'gear', label: 'Configuración', url: '/admin/settings', active: false },
        { icon: 'bar-chart', label: 'Reportes', url: '/admin/reports', active: false },
        { icon: 'shield-lock', label: 'Seguridad', url: '/admin/security', active: false }
      ]
    },
    {
      title: 'Usuario',
      items: [
        { icon: 'person-circle', label: 'Mi Perfil', url: '/profile', active: false },
        { icon: 'box-arrow-right', label: 'Cerrar Sesión', url: '/logout', active: false }
      ]
    }
  ]
}
```

---

## 🎯 Plan de Implementación Detallado

### FASE 1: Crear Nueva Infraestructura (Sin romper nada)

#### 1.1. Crear Nuevo Layout
```
resources/views/layouts/app.mustache
```
**Contenido:**
- Topbar fija
- Sidebar colapsable
- Área de contenido principal
- Footer
- Scripts de navegación

#### 1.2. Crear Componentes de Navegación
```
resources/views/components/navigation/
├── topbar.mustache          (Barra superior)
├── sidebar.mustache         (Menú lateral)
├── breadcrumbs.mustache     (Migas de pan)
└── user-menu.mustache       (Menú desplegable usuario)
```

#### 1.3. Crear CSS Modular
```
public_html/assets/css/
├── iser-theme.css           (Mantener - base)
├── navigation.css           (Nuevo - navegación)
├── sidebar.css              (Nuevo - sidebar)
└── responsive.css           (Nuevo - media queries)
```

#### 1.4. Crear JavaScript de Navegación
```
public_html/assets/js/
├── iser-theme.js            (Mantener - base)
└── navigation.js            (Nuevo - sidebar toggle, etc)
```

---

### FASE 2: Migración Gradual de Vistas

#### Prioridad 1 - Dashboard y Admin (Alto impacto)
1. ✅ `admin/index.mustache` → usar `app.mustache`
2. ✅ `dashboard/index.mustache` → usar `app.mustache`

#### Prioridad 2 - CRUD Admin (Uso frecuente)
3. ✅ `admin/users/index.mustache` → usar `app.mustache`
4. ✅ `admin/users/create.mustache` → usar `app.mustache`
5. ✅ `admin/users/edit.mustache` → usar `app.mustache`
6. ✅ `admin/roles/index.mustache` → usar `app.mustache`
7. ✅ `admin/roles/create.mustache` → usar `app.mustache`
8. ✅ `admin/roles/edit.mustache` → usar `app.mustache`
9. ✅ `admin/permissions/index.mustache` → usar `app.mustache`
10. ✅ `admin/permissions/create.mustache` → usar `app.mustache`
11. ✅ `admin/permissions/edit.mustache` → usar `app.mustache`

#### Prioridad 3 - Otros Admin
12. ✅ `admin/settings.mustache` → usar `app.mustache`
13. ✅ `admin/reports.mustache` → usar `app.mustache`
14. ✅ `admin/security.mustache` → usar `app.mustache`

#### Prioridad 4 - Home
15. ✅ `home/index.mustache` → usar `app.mustache`

#### NO Migrar (sin navegación)
- ❌ `auth/login.mustache` → mantener `base.mustache` (es login)

---

### FASE 3: Actualizar Controladores

#### Cambio en todos los controladores:
```php
// ANTES
return $this->renderWithLayout('admin/users/index', $data);

// DESPUÉS
return $this->renderWithLayout('admin/users/index', $data, 'layouts/app');
//                                                            ↑ nuevo layout
```

#### Agregar datos de navegación:
```php
protected function enrichNavigation(array $data, string $activeRoute): array
{
    $data['navigation'] = [
        'active_route' => $activeRoute,
        'breadcrumbs' => $this->generateBreadcrumbs($activeRoute),
        'user' => $_SESSION['user'] ?? null,
        'notifications_count' => 0, // TODO: implementar
    ];

    return $data;
}
```

---

## 🔒 Seguridad - Mover Archivos de Test

### Archivos a mover fuera de public_html:
```bash
# DE:
public_html/test_permissions.php
public_html/test_controller.php
public_html/test_mustache.php
public_html/debug_permissions.php

# A:
tools/diagnostics/test_permissions.php
tools/diagnostics/test_controller.php
tools/diagnostics/test_mustache.php
tools/diagnostics/debug_permissions.php
```

**Justificación:** Estos archivos de diagnóstico NO deben ser accesibles vía web por seguridad.

---

## 📊 Estimación de Tiempo

| Fase | Tarea | Tiempo | Prioridad |
|------|-------|--------|-----------|
| 1.1 | Layout app.mustache | 1h | ⭐⭐⭐⭐⭐ |
| 1.2 | Componentes navegación | 1h | ⭐⭐⭐⭐⭐ |
| 1.3 | CSS modular | 1h | ⭐⭐⭐⭐⭐ |
| 1.4 | JavaScript | 30min | ⭐⭐⭐⭐⭐ |
| 2.1 | Migrar 15 vistas | 2h | ⭐⭐⭐⭐ |
| 3.1 | Actualizar 6 controladores | 1h | ⭐⭐⭐⭐ |
| - | **TOTAL FASE 1** | **6.5h** | - |

---

## ✅ Checklist de Implementación

### Pre-implementación:
- [x] Análisis completo de estructura
- [x] Identificar todas las vistas
- [x] Mapear controladores y rutas
- [x] Diseñar estructura de navegación
- [ ] Mover archivos de test fuera de public_html

### Implementación Fase 1:
- [ ] Crear `layouts/app.mustache`
- [ ] Crear componentes de navegación
- [ ] Crear CSS de navegación
- [ ] Crear JavaScript de navegación
- [ ] Agregar Bootstrap Icons CDN
- [ ] Probar en desktop/tablet/mobile

### Migración:
- [ ] Migrar vistas prioritarias (admin, dashboard)
- [ ] Actualizar controladores
- [ ] Agregar breadcrumbs dinámicos
- [ ] Marcar ruta activa en sidebar
- [ ] Testing completo

### Post-implementación:
- [ ] Documentar cambios
- [ ] Guía de uso para desarrolladores
- [ ] Cleanup de código legacy
- [ ] Optimización de performance

---

## 🎨 Mockup de Breadcrumbs por Vista

```
/                           → Inicio
/dashboard                  → Inicio > Dashboard
/admin                      → Inicio > Administración
/admin/users                → Inicio > Administración > Usuarios
/admin/users/create         → Inicio > Administración > Usuarios > Crear Usuario
/admin/users/edit           → Inicio > Administración > Usuarios > Editar Usuario
/admin/roles                → Inicio > Administración > Roles
/admin/roles/create         → Inicio > Administración > Roles > Crear Rol
/admin/permissions          → Inicio > Administración > Permisos
/admin/settings             → Inicio > Administración > Configuración
/admin/reports              → Inicio > Administración > Reportes
/admin/security             → Inicio > Administración > Seguridad
```

---

## 📦 Dependencias Nuevas

### CDN a Agregar:
```html
<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
```

### JavaScript Vanilla (Sin dependencias externas)
- No se requiere jQuery
- No se requiere Bootstrap JS
- Solo JavaScript vanilla para sidebar toggle

---

## 🚀 Próximo Paso

**¿Proceder con la implementación de Fase 1?**

1. Crear nueva infraestructura (layouts + componentes)
2. Mover archivos de test a `tools/diagnostics/`
3. Implementar navegación completa
4. Migrar primera vista de prueba

---

**Status:** Análisis completado ✅
**Listo para:** Implementación Fase 1 🚀
