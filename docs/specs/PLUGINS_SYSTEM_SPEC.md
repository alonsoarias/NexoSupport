# ESPECIFICACIÓN: SISTEMA DE PLUGINS DINÁMICO

**Fecha**: 2025-11-12
**Versión**: 1.0
**Proyecto**: NexoSupport - Refactorización Integral
**Fase**: FASE 2 - Sistema de Plugins Dinámico

---

## 1. OBJETIVO DE LA FASE

Diseñar e implementar un **sistema de plugins dinámico** que permita:

1. **Instalación dinámica** de plugins mediante interfaz web
2. **Detección automática** del tipo de plugin (Tool, MFA, Theme, Report, Module)
3. **Segmentación por directorios** según el tipo detectado
4. **Desinstalación segura** con limpieza de recursos
5. **Activación/Desactivación** sin afectar el core
6. **Sistema de hooks/eventos** para integración profunda
7. **Gestión de dependencias** entre plugins
8. **Actualización** de plugins instalados

---

## 2. TIPOS DE PLUGINS SOPORTADOS

### 2.1 Taxonomía de Plugins

El sistema debe soportar **6 tipos principales** de plugins:

#### TIPO 1: Tools (Herramientas Administrativas)
**Propósito**: Herramientas auxiliares para administradores
**Ubicación**: `/modules/plugins/tools/{plugin_slug}/`
**Ejemplos**:
- MFA (Multi-Factor Authentication) - actualmente en `/modules/Admin/Tool/Mfa/`
- Backup & Restore
- Import/Export de datos
- Password Reset Tools avanzados
- Sistema de notificaciones
- Generador de reportes personalizados

**Características**:
- Punto de entrada en `/admin/tools/{tool_slug}`
- Pueden agregar opciones al menú de admin
- Acceso solo para usuarios con permisos específicos
- Pueden tener su propia configuración en BD

#### TIPO 2: Authentication Methods (Métodos de Autenticación)
**Propósito**: Métodos alternativos de autenticación
**Ubicación**: `/modules/plugins/auth/{plugin_slug}/`
**Ejemplos**:
- LDAP/Active Directory
- OAuth2 (Google, Facebook, GitHub)
- SAML 2.0
- Social Login
- Biometric Authentication

**Características**:
- Se integran en el flujo de login existente
- Deben implementar interfaz `AuthenticationMethodInterface`
- Pueden coexistir múltiples métodos simultáneamente
- Usuario elige método en la pantalla de login

#### TIPO 3: Themes (Temas Visuales)
**Propósito**: Temas visuales que sobrescriben el theme del core
**Ubicación**: `/modules/plugins/themes/{theme_slug}/`
**Ejemplos**:
- Dark Theme Professional
- Material Design Theme
- Bootstrap 5 Modern Theme
- Corporate Theme personalizado

**Características**:
- Solo UN theme activo a la vez (además del core fallback)
- Deben tener layouts compatibles (base, app, fullwidth, auth)
- Pueden sobrescribir componentes específicos
- El theme del core siempre es fallback si falta un template

#### TIPO 4: Reports (Módulos de Reportes)
**Propósito**: Reportes y análisis personalizados
**Ubicación**: `/modules/plugins/reports/{plugin_slug}/`
**Ejemplos**:
- Analytics Dashboard avanzado
- User Activity Reports
- Security Audit Reports
- Custom Business Reports

**Características**:
- Punto de entrada en `/admin/reports/{report_slug}`
- Pueden exportar datos (PDF, Excel, CSV)
- Acceso controlado por permisos
- Pueden consultar cualquier tabla de la BD

#### TIPO 5: Modules (Módulos Funcionales Completos)
**Propósito**: Módulos de negocio completos e independientes
**Ubicación**: `/modules/plugins/modules/{module_slug}/`
**Ejemplos**:
- Sistema de Tickets/Helpdesk
- CRM integrado
- Sistema de Inventario
- Módulo de Facturación

**Características**:
- Pueden tener sus propias rutas: `/app/{module_slug}/*`
- Controllers, Models, Views propios
- Pueden crear sus propias tablas en BD
- Pueden agregar permisos propios al sistema RBAC

#### TIPO 6: Integrations (Integraciones con APIs Externas)
**Propósito**: Conectores con servicios externos
**Ubicación**: `/modules/plugins/integrations/{plugin_slug}/`
**Ejemplos**:
- Slack Integration
- Microsoft Teams
- Webhook Manager
- Email Service Providers (SendGrid, Mailgun)

**Características**:
- Manejan comunicación con APIs externas
- Pueden tener configuración de API keys
- Pueden escuchar webhooks entrantes
- Pueden enviar notificaciones a servicios externos

---

## 3. ESTRUCTURA DE UN PLUGIN

### 3.1 Manifiesto: plugin.json

Cada plugin DEBE incluir un archivo `plugin.json` en su raíz con la siguiente estructura conceptual:

**Campos obligatorios**:
- `name`: Nombre legible del plugin
- `slug`: Identificador único (alfanumérico + guiones)
- `type`: Tipo de plugin (tool, auth, theme, report, module, integration)
- `version`: Versión semántica (ej: 1.0.0)
- `author`: Autor del plugin
- `description`: Descripción breve del plugin

**Campos opcionales**:
- `category`: Subcategoría específica (ej: para type=tool, category=mfa)
- `requires`: Dependencias del sistema y otros plugins
  - `nexosupport`: Versión mínima de NexoSupport
  - `php`: Versión mínima de PHP
  - `plugins`: Array de plugins requeridos
- `provides`: Array de funcionalidades que provee
- `routes`: Array de rutas que registra
- `permissions`: Array de permisos que necesita o crea
- `config`: Configuración por defecto del plugin
- `hooks`: Hooks que el plugin escucha
- `menu`: Items de menú que agrega al admin
- `assets`: Archivos CSS/JS que deben cargarse
- `icon`: Ícono del plugin (ruta o clase CSS)
- `license`: Licencia del plugin
- `homepage`: URL del sitio web del plugin
- `repository`: URL del repositorio de código

### 3.2 Estructura de Directorios de un Plugin

**Estructura conceptual mínima**:

```
/modules/plugins/{type}/{plugin_slug}/
├── plugin.json              # Manifiesto (OBLIGATORIO)
├── Plugin.php               # Clase principal (OBLIGATORIO)
├── install.xml              # Schema de BD (OPCIONAL)
├── routes.php               # Rutas adicionales (OPCIONAL)
├── hooks.php                # Registro de hooks (OPCIONAL)
├── config/
│   └── config.php           # Configuración por defecto
├── lang/
│   ├── es/
│   │   └── {plugin_slug}.php
│   └── en/
│       └── {plugin_slug}.php
├── views/                   # Templates Mustache (OPCIONAL)
│   ├── layouts/
│   ├── components/
│   └── pages/
├── assets/                  # CSS, JS, imágenes (OPCIONAL)
│   ├── css/
│   ├── js/
│   └── images/
├── src/                     # Código fuente del plugin
│   ├── Controllers/
│   ├── Models/
│   ├── Services/
│   └── ...
├── tests/                   # Tests unitarios (OPCIONAL)
└── README.md                # Documentación del plugin
```

### 3.3 Clase Principal: Plugin.php

**Responsabilidades**:
- Implementar interfaz `PluginInterface`
- Definir métodos del ciclo de vida:
  - `install()`: Ejecutado al instalar el plugin
  - `uninstall()`: Ejecutado al desinstalar el plugin
  - `activate()`: Ejecutado al activar el plugin
  - `deactivate()`: Ejecutado al desactivar el plugin
  - `update(oldVersion)`: Ejecutado al actualizar el plugin
- Registrar hooks, rutas y assets
- Proveer información del plugin

**Patrones a implementar**:
- **Template Method**: La clase base define el flujo, subclases implementan pasos específicos
- **Factory**: Para crear instancias de controladores y servicios del plugin
- **Strategy**: Para permitir diferentes comportamientos según configuración

---

## 4. ARQUITECTURA DEL SISTEMA DE PLUGINS

### 4.1 Componentes del Sistema

#### 4.1.1 PluginManager (Gestor Central)
**Responsabilidad**: Gestión global del ciclo de vida de plugins

**Funcionalidades**:
- Descubrir plugins instalados en el sistema de archivos
- Cargar manifiestos (plugin.json) de todos los plugins
- Instanciar plugins activos
- Mantener registro de plugins en base de datos
- Proveer API para consultar plugins disponibles
- Validar dependencias entre plugins
- Ordenar plugins por prioridad de carga

**Patrón**: Singleton (una sola instancia global)

#### 4.1.2 PluginInstaller (Instalador)
**Responsabilidad**: Instalación de plugins desde paquetes ZIP

**Funcionalidades**:
- Validar paquete ZIP
- Extraer y validar plugin.json
- Detectar tipo de plugin automáticamente
- Determinar directorio de instalación según tipo
- Extraer archivos a ubicación correcta
- Parsear y ejecutar install.xml si existe
- Registrar plugin en base de datos
- Ejecutar método install() de la clase Plugin
- Registrar hooks, rutas y permisos del plugin
- Manejo de errores con rollback

**Patrón**: Command (encapsula la operación de instalación)

#### 4.1.3 PluginUninstaller (Desinstalador)
**Responsabilidad**: Desinstalación segura de plugins

**Funcionalidades**:
- Verificar que el plugin puede desinstalarse (no es plugin del sistema)
- Desactivar plugin si está activo
- Ejecutar método uninstall() de la clase Plugin
- Ejecutar uninstall.xml o DROP TABLES según configuración
- Eliminar archivos del plugin del filesystem
- Eliminar registros de BD (tabla plugins, config, etc.)
- Limpiar hooks, rutas y permisos registrados
- Registrar en audit log

**Patrón**: Command (encapsula la operación de desinstalación)

#### 4.1.4 PluginLoader (Cargador)
**Responsabilidad**: Carga de plugins activos en cada request

**Funcionalidades**:
- Cargar solo plugins activos
- Respetar orden de prioridad de carga
- Instanciar clase principal Plugin.php
- Registrar autoloader PSR-4 del plugin
- Registrar rutas del plugin en el Router
- Registrar hooks del plugin en el EventDispatcher
- Cargar assets CSS/JS del plugin
- Cargar traducciones del plugin

**Patrón**: Lazy Loading (solo carga lo necesario)

#### 4.1.5 PluginRegistry (Registro)
**Responsabilidad**: Mantener registro en memoria de plugins cargados

**Funcionalidades**:
- Mantener array de plugins instanciados
- Proveer acceso rápido a plugins por slug
- Proveer acceso a plugins por tipo
- Cache de información de plugins
- Notificar cambios a observadores

**Patrón**: Registry (punto centralizado de acceso)

#### 4.1.6 HookManager / EventDispatcher (Sistema de Hooks)
**Responsabilidad**: Sistema de eventos para integración profunda

**Funcionalidades**:
- Registrar hooks disponibles en el sistema
- Permitir a plugins suscribirse a hooks
- Disparar eventos cuando ocurren acciones
- Pasar datos contextuales a los listeners
- Permitir modificación de datos por listeners
- Manejo de prioridad de ejecución

**Patrón**: Observer (pub/sub de eventos)

**Hooks del sistema**:
- `user.created`: Después de crear usuario
- `user.updated`: Después de actualizar usuario
- `user.deleted`: Después de eliminar usuario
- `user.login`: Después de login exitoso
- `user.logout`: Después de logout
- `user.password_changed`: Después de cambiar contraseña
- `role.assigned`: Después de asignar rol a usuario
- `role.revoked`: Después de revocar rol de usuario
- `plugin.installed`: Después de instalar plugin
- `plugin.activated`: Después de activar plugin
- `plugin.deactivated`: Después de desactivar plugin
- `plugin.uninstalled`: Después de desinstalar plugin
- `render.before`: Antes de renderizar vista
- `render.after`: Después de renderizar vista
- `request.before`: Antes de procesar request
- `request.after`: Después de procesar request

#### 4.1.7 DependencyResolver (Resolvedor de Dependencias)
**Responsabilidad**: Resolver y validar dependencias entre plugins

**Funcionalidades**:
- Verificar que plugins requeridos están instalados
- Verificar versiones compatibles
- Detectar dependencias circulares
- Determinar orden de carga óptimo
- Prevenir activación si dependencias no se cumplen
- Advertir sobre conflictos entre plugins

**Patrón**: Strategy (diferentes estrategias de resolución)

### 4.2 Diagrama de Componentes

```
┌─────────────────────────────────────────────────────────┐
│                   PluginManager                         │
│  (Singleton - Gestor Central)                           │
│  ├─ discoverPlugins()                                   │
│  ├─ getPlugin(slug)                                     │
│  ├─ getPluginsByType(type)                              │
│  └─ validateDependencies(plugin)                        │
└────────┬────────────────────────────────────────────────┘
         │
         ├─────────────────┬─────────────────┬─────────────────┐
         ▼                 ▼                 ▼                 ▼
┌─────────────────┐ ┌──────────────┐ ┌──────────────┐ ┌─────────────┐
│ PluginInstaller │ │PluginLoader  │ │PluginRegistry│ │ HookManager │
│  - validate()   │ │ - loadActive()│ │ - register() │ │ - register()│
│  - extract()    │ │ - instantiate│ │ - get()      │ │ - trigger() │
│  - register()   │ │ - register() │ │ - getByType()│ │ - listen()  │
└─────────────────┘ └──────────────┘ └──────────────┘ └─────────────┘
         │                                                     │
         └───────────────────┬─────────────────────────────────┘
                             ▼
                  ┌─────────────────────┐
                  │ DependencyResolver  │
                  │  - resolve()        │
                  │  - checkCircular()  │
                  │  - determineOrder() │
                  └─────────────────────┘
```

---

## 5. DETECCIÓN AUTOMÁTICA DE TIPO

### 5.1 Algoritmo de Detección

El **PluginInstaller** debe detectar automáticamente el tipo de plugin leyendo el campo `type` del `plugin.json`.

**Flujo conceptual**:

1. **Extraer ZIP** a directorio temporal
2. **Buscar y validar** `plugin.json` en la raíz
3. **Leer campo `type`** del manifiesto
4. **Validar** que el tipo es uno de los soportados: `tool`, `auth`, `theme`, `report`, `module`, `integration`
5. **Determinar directorio de destino** según el tipo:
   - `type: "tool"` → `/modules/plugins/tools/{slug}/`
   - `type: "auth"` → `/modules/plugins/auth/{slug}/`
   - `type: "theme"` → `/modules/plugins/themes/{slug}/`
   - `type: "report"` → `/modules/plugins/reports/{slug}/`
   - `type: "module"` → `/modules/plugins/modules/{slug}/`
   - `type: "integration"` → `/modules/plugins/integrations/{slug}/`
6. **Verificar** que el directorio de destino no existe ya
7. **Mover archivos** del directorio temporal al destino final
8. **Registrar en BD** con tipo y categoría
9. **Ejecutar install()** del plugin

### 5.2 Validaciones de Tipo

**Validaciones específicas por tipo**:

- **Tools**: Deben proveer al menos un punto de entrada en admin
- **Auth**: Deben implementar `AuthenticationMethodInterface`
- **Themes**: Deben tener layouts obligatorios (base, app)
- **Reports**: Deben implementar `ReportInterface`
- **Modules**: Deben registrar al menos una ruta
- **Integrations**: Deben tener configuración de API

---

## 6. SEGMENTACIÓN POR DIRECTORIOS

### 6.1 Estructura de Directorios del Sistema de Plugins

```
/modules/plugins/
├── tools/                          # Herramientas administrativas
│   ├── mfa-authenticator/          # MFA (migrado desde /Admin/Tool/Mfa/)
│   │   ├── plugin.json
│   │   ├── Plugin.php
│   │   ├── install.xml
│   │   └── ...
│   ├── backup-manager/
│   └── import-export/
│
├── auth/                           # Métodos de autenticación
│   ├── ldap-auth/
│   │   ├── plugin.json
│   │   ├── Plugin.php
│   │   └── ...
│   ├── oauth2-provider/
│   └── saml-auth/
│
├── themes/                         # Themes adicionales
│   ├── dark-professional/
│   │   ├── plugin.json
│   │   ├── Plugin.php
│   │   ├── layouts/
│   │   ├── components/
│   │   └── assets/
│   └── material-design/
│
├── reports/                        # Módulos de reportes
│   ├── analytics-dashboard/
│   └── security-audit/
│
├── modules/                        # Módulos completos
│   ├── ticketing-system/
│   └── crm-integration/
│
└── integrations/                   # Integraciones con APIs
    ├── slack-integration/
    └── webhook-manager/
```

### 6.2 Beneficios de la Segmentación

1. **Organización clara**: Cada tipo de plugin en su directorio específico
2. **Carga selectiva**: Cargar solo plugins del tipo necesario
3. **Escalabilidad**: Fácil agregar nuevos tipos de plugins
4. **Mantenibilidad**: Fácil localizar y gestionar plugins
5. **Performance**: No cargar todos los plugins siempre, solo los necesarios según contexto

---

## 7. INSTALACIÓN VÍA INTERFAZ WEB

### 7.1 UI de Instalación de Plugins

**Ubicación**: `/admin/plugins/install`
**Permiso requerido**: `system.plugins`

**Funcionalidades de la UI**:

1. **Upload de archivo ZIP**
   - Drag & drop o click para seleccionar
   - Validación de tamaño máximo (50MB por defecto)
   - Validación de tipo MIME (application/zip)
   - Barra de progreso durante la subida

2. **Validación en tiempo real**
   - Extraer ZIP temporalmente
   - Validar estructura del plugin
   - Mostrar información del plugin.json
   - Verificar dependencias
   - Detectar conflictos con plugins existentes
   - Mostrar warnings o errores antes de instalar

3. **Vista previa de información**
   - Nombre del plugin
   - Versión
   - Autor
   - Descripción
   - Tipo detectado automáticamente
   - Dependencias requeridas
   - Permisos que necesita
   - Tablas de BD que creará (si tiene install.xml)

4. **Confirmación de instalación**
   - Botón "Instalar Plugin"
   - Checkbox "Activar automáticamente después de instalar"
   - Opción de configuración inicial (si el plugin lo soporta)

5. **Feedback del proceso**
   - Barra de progreso con pasos:
     - Validando paquete...
     - Extrayendo archivos...
     - Instalando base de datos...
     - Registrando plugin...
     - Activando plugin...
     - ¡Instalación completada!
   - Log detallado de cada paso
   - Manejo de errores con mensaje claro

6. **Post-instalación**
   - Mensaje de éxito con información del plugin
   - Botón "Configurar Plugin" (si tiene settings)
   - Botón "Ir a {Tipo de Plugin}" (ej: "Ir a Tools", "Ir a Themes")
   - Link a documentación del plugin (si la tiene)

### 7.2 Flujo de Instalación Conceptual

```
Usuario Admin → /admin/plugins/install
    ↓
Selecciona archivo ZIP del plugin
    ↓
Upload con validación de tamaño y tipo
    ↓
PluginInstaller::validatePackage(zipPath)
    ├─ Extraer a /tmp/plugin_temp_{random}/
    ├─ Buscar plugin.json en raíz
    ├─ Parsear y validar plugin.json
    ├─ Detectar tipo automáticamente
    ├─ Verificar slug único
    ├─ Validar estructura de archivos
    └─ Verificar dependencias
    ↓
Mostrar vista previa con información del plugin
    ↓
Usuario hace click en "Instalar Plugin"
    ↓
PluginInstaller::install(zipPath, autoActivate)
    ├─ Determinar directorio de destino según tipo
    │  └─ /modules/plugins/{type}/{slug}/
    ├─ Mover archivos de temp a destino
    ├─ Parsear install.xml si existe
    │  └─ SchemaInstaller::installPluginSchema(xmlPath, prefix)
    ├─ Registrar plugin en BD (tabla plugins)
    │  └─ INSERT (slug, name, type, version, enabled=autoActivate)
    ├─ Instanciar clase Plugin.php
    ├─ Ejecutar Plugin::install()
    ├─ Registrar hooks del plugin en tabla plugin_hooks
    ├─ Registrar rutas del plugin en caché
    ├─ Registrar permisos del plugin si los tiene
    ├─ Si autoActivate: ejecutar Plugin::activate()
    └─ Registrar en audit_log
    ↓
Mostrar mensaje de éxito
    ↓
Redirect a /admin/plugins o configuración del plugin
```

---

## 8. GESTIÓN DE PLUGINS EN ADMIN PANEL

### 8.1 UI de Gestión de Plugins

**Ubicación**: `/admin/plugins`
**Permiso requerido**: `system.plugins`

**Vista principal**:
- Lista de plugins instalados en formato de tarjetas (cards)
- Filtros por tipo: Todos, Tools, Auth, Themes, Reports, Modules, Integrations
- Filtros por estado: Todos, Activos, Inactivos
- Búsqueda por nombre o descripción
- Botón destacado: "Instalar Nuevo Plugin"

**Información en cada card**:
- Ícono del plugin
- Nombre del plugin
- Versión instalada
- Autor
- Descripción breve
- Tipo y categoría (badges)
- Estado: Activo/Inactivo (toggle switch)
- Botones de acción:
  - Configurar (si tiene settings)
  - Desinstalar
  - Actualizar (si hay update disponible)
  - Ver detalles

### 8.2 Funcionalidades de Gestión

#### 8.2.1 Activar/Desactivar Plugin
- Toggle switch en la card del plugin
- Al desactivar: ejecuta Plugin::deactivate()
- Al activar: verifica dependencias y ejecuta Plugin::activate()
- Confirmación si el plugin tiene dependientes activos
- Feedback visual inmediato

#### 8.2.2 Desinstalar Plugin
- Botón "Desinstalar" en la card
- Modal de confirmación con advertencias:
  - "Este plugin será eliminado permanentemente"
  - "Las tablas de BD creadas por el plugin serán eliminadas"
  - "Esta acción no se puede deshacer"
  - Checkbox: "Estoy seguro de que quiero desinstalar este plugin"
- No permite desinstalar plugins del sistema (core)
- No permite desinstalar plugins con dependientes activos
- Ejecuta PluginUninstaller::uninstall(slug)

#### 8.2.3 Actualizar Plugin
- Detectar si hay versión nueva disponible (si el plugin tiene repository/update URL)
- Mostrar badge "Actualización disponible" en la card
- Botón "Actualizar" abre modal con changelog
- Descargar nueva versión y ejecutar PluginInstaller::update(slug, newZipPath)
- Ejecutar método Plugin::update(oldVersion)
- Migrar configuraciones si es necesario

#### 8.2.4 Configurar Plugin
- Si el plugin tiene configuración (método getSettings())
- Botón "Configurar" abre modal o página dedicada
- Formulario dinámico generado desde definición de settings del plugin
- Validación de configuración
- Guardar en tabla plugin_config con formato key-value

#### 8.2.5 Ver Detalles del Plugin
- Modal o página con información completa:
  - Información general (nombre, versión, autor, licencia)
  - Descripción larga
  - Changelog (historial de versiones)
  - Dependencias requeridas y provistas
  - Hooks que escucha
  - Rutas que registra
  - Permisos que usa
  - Tablas de BD que creó
  - Link a documentación
  - Link a repositorio
  - Link a reporte de bugs

---

## 9. SISTEMA DE HOOKS Y EVENTOS

### 9.1 Arquitectura de Hooks

El sistema de hooks permite a los plugins integrarse profundamente con el core sin modificarlo.

**Patrón**: Observer / Pub-Sub

**Componentes**:
- **HookManager**: Gestor central de hooks
- **Hook**: Representa un punto de extensión en el sistema
- **Listener**: Callback que se ejecuta cuando se dispara un hook
- **Event**: Objeto que contiene datos contextuales del evento

### 9.2 Registro de Hooks por Plugins

**En el archivo hooks.php del plugin**:

Los plugins registran sus listeners especificando:
- Hook name: Nombre del hook a escuchar
- Callback: Método a ejecutar (puede ser método de la clase Plugin o clase específica)
- Priority: Prioridad de ejecución (1-100, menor = primero)

### 9.3 Hooks del Sistema

**Hooks disponibles para plugins** (conceptual):

**User Management**:
- `user.before_create`: Antes de crear usuario (puede modificar datos)
- `user.created`: Después de crear usuario (solo lectura)
- `user.before_update`: Antes de actualizar usuario (puede modificar datos)
- `user.updated`: Después de actualizar usuario
- `user.before_delete`: Antes de eliminar usuario (puede cancelar)
- `user.deleted`: Después de eliminar usuario
- `user.restored`: Después de restaurar usuario soft-deleted

**Authentication**:
- `auth.login_attempt`: En cada intento de login (antes de validar)
- `auth.login_success`: Después de login exitoso
- `auth.login_failed`: Después de login fallido
- `auth.logout`: Después de logout
- `auth.password_reset_requested`: Al solicitar reset de contraseña
- `auth.password_changed`: Después de cambiar contraseña

**Authorization**:
- `permission.check`: Al verificar un permiso (puede modificar resultado)
- `role.assigned`: Después de asignar rol a usuario
- `role.revoked`: Después de revocar rol de usuario

**Rendering**:
- `render.before_layout`: Antes de renderizar layout (puede modificar datos)
- `render.after_layout`: Después de renderizar layout
- `render.before_view`: Antes de renderizar vista específica
- `render.after_view`: Después de renderizar vista

**Request Lifecycle**:
- `request.received`: Al recibir request HTTP
- `request.authenticated`: Después de autenticar request
- `request.authorized`: Después de autorizar request
- `request.before_controller`: Antes de ejecutar controller
- `request.after_controller`: Después de ejecutar controller
- `request.before_response`: Antes de enviar respuesta
- `request.response_sent`: Después de enviar respuesta

**Plugin Lifecycle**:
- `plugin.before_install`: Antes de instalar plugin
- `plugin.installed`: Después de instalar plugin
- `plugin.before_activate`: Antes de activar plugin
- `plugin.activated`: Después de activar plugin
- `plugin.before_deactivate`: Antes de desactivar plugin
- `plugin.deactivated`: Después de desactivar plugin
- `plugin.before_uninstall`: Antes de desinstalar plugin
- `plugin.uninstalled`: Después de desinstalar plugin

### 9.4 Uso de Hooks en Plugins

**Ejemplo conceptual de un plugin escuchando hooks**:

Un plugin MFA podría escuchar:
- `auth.login_success` → Verificar si el usuario tiene MFA habilitado y redirigir a verificación
- `user.created` → Ofrecer configurar MFA al nuevo usuario
- `render.before_view` → Inyectar UI de MFA en la página de login

Un plugin de auditoría podría escuchar:
- Todos los hooks `*.created`, `*.updated`, `*.deleted` → Registrar en audit log
- `auth.login_success` y `auth.login_failed` → Registrar intentos de acceso

---

## 10. GESTIÓN DE DEPENDENCIAS

### 10.1 Tipos de Dependencias

**1. Dependencia de versión de NexoSupport**
- Plugin requiere versión mínima del sistema core
- Especificado en `requires.nexosupport`
- Ejemplo: `">=1.0.0"`

**2. Dependencia de versión de PHP**
- Plugin requiere versión mínima de PHP
- Especificado en `requires.php`
- Ejemplo: `">=8.1"`

**3. Dependencia de extensiones PHP**
- Plugin requiere extensiones específicas
- Especificado en `requires.extensions`
- Ejemplo: `["gd", "imagick"]`

**4. Dependencia de otros plugins**
- Plugin requiere que otros plugins estén instalados y activos
- Especificado en `requires.plugins`
- Array de objetos con slug y versión mínima
- Ejemplo: `[{"slug": "backup-manager", "version": ">=1.0.0"}]`

### 10.2 Resolución de Dependencias

**DependencyResolver** debe:

1. **Validar dependencias al instalar**:
   - Verificar versión de NexoSupport compatible
   - Verificar versión de PHP compatible
   - Verificar extensiones PHP disponibles
   - Verificar plugins requeridos instalados
   - Verificar versiones de plugins requeridos

2. **Detectar conflictos**:
   - Plugins que proveen la misma funcionalidad (ej: dos plugins MFA)
   - Plugins incompatibles entre sí (especificado en campo `conflicts`)
   - Dependencias circulares (Plugin A requiere B, B requiere A)

3. **Determinar orden de carga**:
   - Plugins sin dependencias primero
   - Luego plugins que solo dependen de los ya cargados
   - Algoritmo topological sort para dependencias complejas

4. **Prevenir activación si dependencias no se cumplen**:
   - No permitir activar plugin si falta dependencia
   - Mostrar error claro: "Este plugin requiere {plugin_slug} versión {version}"
   - Ofrecer instalar dependencia faltante si está disponible

5. **Advertir al desinstalar plugin con dependientes**:
   - Mostrar lista de plugins que dependen del plugin a desinstalar
   - Ofrecer desactivar dependientes primero
   - No permitir desinstalar si hay dependientes activos

### 10.3 Notificación de Dependencias

**En la UI de gestión de plugins**:
- Mostrar badge de "Dependencias" en cada plugin
- Al hacer hover o click, mostrar:
  - Plugins de los que depende (required by this)
  - Plugins que dependen de este (depends on this)
- En la vista de detalles, sección completa de dependencias

---

## 11. BASE DE DATOS DEL SISTEMA DE PLUGINS

### 11.1 Tabla: plugins

**Propósito**: Registro de plugins instalados en el sistema

**Campos conceptuales**:
- `id`: Primary key
- `slug`: Identificador único del plugin (alfanumérico + guiones)
- `name`: Nombre legible del plugin
- `type`: Tipo del plugin (tool, auth, theme, report, module, integration)
- `category`: Subcategoría específica (ej: mfa, ldap, backup)
- `version`: Versión instalada actualmente
- `author`: Autor del plugin
- `description`: Descripción breve
- `enabled`: 1=activo, 0=inactivo
- `priority`: Orden de carga (menor = primero)
- `path`: Ruta relativa al plugin (ej: /modules/plugins/tools/mfa-authenticator/)
- `manifest`: JSON completo del plugin.json
- `installed_at`: Timestamp de instalación
- `activated_at`: Timestamp de última activación
- `updated_at`: Timestamp de última actualización
- `is_core`: 1=plugin del sistema (no desinstalable), 0=plugin de terceros

**Índices**:
- UNIQUE en `slug`
- INDEX en `type`
- INDEX en `enabled`
- INDEX en `priority`

### 11.2 Tabla: plugin_config

**Propósito**: Configuración de plugins (key-value)

**Campos conceptuales**:
- `id`: Primary key
- `plugin_slug`: FK a plugins.slug
- `config_key`: Clave de configuración
- `config_value`: Valor de configuración (TEXT para JSON)
- `config_type`: Tipo de dato (string, integer, boolean, json, array)
- `updated_at`: Timestamp de última actualización

**Índices**:
- UNIQUE en (plugin_slug, config_key)
- INDEX en `plugin_slug`

### 11.3 Tabla: plugin_hooks

**Propósito**: Registro de hooks que escuchan los plugins

**Campos conceptuales**:
- `id`: Primary key
- `plugin_slug`: FK a plugins.slug
- `hook_name`: Nombre del hook (ej: user.created)
- `callback`: Callback a ejecutar (formato: ClassName@method)
- `priority`: Prioridad de ejecución (1-100)
- `enabled`: 1=activo, 0=inactivo
- `created_at`: Timestamp de registro

**Índices**:
- INDEX en `plugin_slug`
- INDEX en `hook_name`
- INDEX en `priority`

### 11.4 Tabla: plugin_routes

**Propósito**: Registro de rutas que registran los plugins

**Campos conceptuales**:
- `id`: Primary key
- `plugin_slug`: FK a plugins.slug
- `method`: Método HTTP (GET, POST, PUT, DELETE, PATCH)
- `path`: Ruta (ej: /admin/tools/mfa/setup)
- `controller`: Controlador a ejecutar
- `action`: Método del controlador
- `name`: Nombre de la ruta (para url())
- `middleware`: Middleware aplicado (JSON array)
- `created_at`: Timestamp de registro

**Índices**:
- INDEX en `plugin_slug`
- INDEX en `path`

### 11.5 Tabla: plugin_permissions

**Propósito**: Permisos que crean los plugins

**Campos conceptuales**:
- `id`: Primary key
- `plugin_slug`: FK a plugins.slug
- `permission_name`: Nombre del permiso (ej: mfa.configure)
- `permission_description`: Descripción del permiso
- `module`: Módulo al que pertenece
- `created_at`: Timestamp de creación

**Índices**:
- UNIQUE en `permission_name`
- INDEX en `plugin_slug`
- INDEX en `module`

---

## 12. ACTUALIZACIÓN DE PLUGINS

### 12.1 Detección de Actualizaciones

**Métodos de detección**:

1. **Repositorio remoto**: Si el plugin especifica `repository` o `update_url` en plugin.json
   - Consultar API del repositorio (ej: GitHub Releases)
   - Comparar versión instalada vs versión disponible
   - Mostrar badge "Actualización disponible" si hay versión nueva

2. **Marketplace de plugins**: Si NexoSupport tiene un marketplace centralizado
   - Consultar API del marketplace
   - Obtener información de actualizaciones disponibles
   - Mostrar notificaciones de actualizaciones

3. **Verificación manual**: Permitir al admin verificar actualizaciones manualmente
   - Botón "Verificar actualizaciones" en /admin/plugins
   - Consultar todos los plugins que tienen update_url

### 12.2 Proceso de Actualización

**Flujo conceptual**:

```
Admin → Click "Actualizar" en plugin
    ↓
Mostrar información de la actualización:
    ├─ Versión actual: 1.0.0
    ├─ Versión nueva: 1.1.0
    ├─ Changelog:
    │   └─ - Nueva funcionalidad X
    │       - Corrección de bug Y
    │       - Mejora de performance Z
    └─ Botón "Actualizar ahora"
    ↓
PluginInstaller::update(slug, newVersion)
    ├─ Descargar nueva versión del plugin (ZIP)
    ├─ Validar paquete
    ├─ Desactivar plugin temporalmente
    ├─ Backup de archivos antiguos
    ├─ Backup de configuración actual
    ├─ Extraer nueva versión a directorio temporal
    ├─ Ejecutar método Plugin::update(oldVersion)
    │   └─ Plugin puede migrar datos, actualizar BD, etc.
    ├─ Reemplazar archivos del plugin con la nueva versión
    ├─ Actualizar versión en tabla plugins
    ├─ Actualizar manifiesto en BD
    ├─ Re-activar plugin
    ├─ Limpiar backup si éxito
    └─ Si error: restaurar desde backup (rollback)
    ↓
Mostrar mensaje de éxito
    └─ "Plugin actualizado correctamente a versión 1.1.0"
```

### 12.3 Compatibilidad de Actualizaciones

**Versioning semántico** (MAJOR.MINOR.PATCH):
- **MAJOR**: Cambios incompatibles con versiones anteriores
- **MINOR**: Nueva funcionalidad compatible con versiones anteriores
- **PATCH**: Correcciones de bugs compatibles

**Estrategias de actualización**:
- **Parches (PATCH)**: Actualización automática si está habilitado
- **Minor**: Actualización recomendada con notificación
- **Major**: Requiere confirmación del admin (posibles breaking changes)

---

## 13. SEGURIDAD EN EL SISTEMA DE PLUGINS

### 13.1 Validaciones de Seguridad

**Al instalar plugin**:
1. Validar que el ZIP no contiene archivos peligrosos (ej: .exe, .bat, .sh ejecutables)
2. Validar que plugin.json no contiene código malicioso
3. Validar que el plugin no sobrescribe archivos del core
4. Validar permisos de archivos (no ejecutables innecesarios)
5. Escanear con antivirus si está disponible (opcional)

**Al ejecutar plugin**:
1. Plugins corren en contexto aislado (namespace propio)
2. Plugins no pueden acceder directamente a configuración del core
3. Plugins deben usar APIs del core para operaciones sensibles
4. Logs de todas las acciones de plugins con nivel INFO o superior

### 13.2 Sandboxing de Plugins

**Limitaciones conceptuales**:
- Plugins no pueden modificar archivos fuera de su directorio
- Plugins no pueden ejecutar comandos del sistema sin permiso explícito
- Plugins no pueden acceder a variables globales del sistema
- Plugins no pueden desactivar seguridad del sistema (ej: CSRF, SQL injection protection)

### 13.3 Auditoría de Plugins

**Registro en audit_log**:
- Instalación de plugin (quién, cuándo, qué plugin)
- Activación/desactivación (quién, cuándo)
- Desinstalación (quién, cuándo)
- Actualización (de qué versión a qué versión)
- Errores de plugins (qué error, en qué plugin)

---

## 14. MIGRACIÓN DEL PLUGIN MFA EXISTENTE

### 14.1 Estado Actual del MFA

**Ubicación actual**: `/modules/Admin/Tool/Mfa/`
**Problema**: Está hardcoded en el core, no es un plugin independiente

### 14.2 Plan de Migración

**Paso 1**: Crear plugin independiente `mfa-authenticator`

**Estructura del nuevo plugin**:
```
/modules/plugins/tools/mfa-authenticator/
├── plugin.json
│   └── type: "tool"
│       category: "mfa"
├── Plugin.php
├── install.xml (para tablas user_mfa, mfa_audit, etc.)
├── routes.php
├── hooks.php (escuchar auth.login_success)
├── src/
│   ├── MfaManager.php (migrado)
│   ├── Factors/
│   │   ├── TotpFactor.php
│   │   ├── EmailFactor.php
│   │   └── BackupFactor.php
│   └── Controllers/
│       └── MfaController.php
└── views/
    └── setup, verify, backup, etc.
```

**Paso 2**: Migrar código existente

- Copiar archivos de `/modules/Admin/Tool/Mfa/` al nuevo plugin
- Ajustar namespaces: `ISER\Admin\Tool\Mfa` → `Plugins\MfaAuthenticator`
- Actualizar rutas para usar prefijo del plugin
- Registrar hooks en hooks.php

**Paso 3**: Actualizar referencias en el core

- Remover referencias hardcoded a MFA en AuthController
- Usar HookManager para despachar evento `auth.login_success`
- Plugin MFA escuchará ese hook y redirigirá a verificación si está habilitado

**Paso 4**: Migrar datos de BD (si es necesario)

- Las tablas `user_mfa`, `mfa_audit` ya existen
- No requiere migración, solo cambiar quién las gestiona

**Paso 5**: Instalación del plugin MFA en el instalador

- El instalador del sistema debe instalar automáticamente el plugin MFA como "core plugin"
- Marcar con `is_core=1` para que no se pueda desinstalar
- Puede desactivarse pero no eliminarse

---

## 15. EJEMPLO CONCEPTUAL: PLUGIN DE LDAP

### 15.1 Descripción del Plugin

**Nombre**: LDAP Authentication
**Slug**: `ldap-auth`
**Tipo**: `auth`
**Propósito**: Permitir autenticación contra servidor LDAP/Active Directory

### 15.2 Estructura del Plugin

```
/modules/plugins/auth/ldap-auth/
├── plugin.json
│   ├── type: "auth"
│   ├── provides: ["ldap_authentication"]
│   ├── requires: {"php": ">=8.1", "extensions": ["ldap"]}
│   └── hooks: ["auth.login_attempt"]
├── Plugin.php
├── routes.php (ruta para configurar conexión LDAP)
├── hooks.php
├── config/
│   └── config.php (servidor LDAP, puerto, base DN, etc.)
├── src/
│   ├── LdapConnection.php
│   ├── LdapAuthenticator.php
│   └── Controllers/
│       └── LdapConfigController.php
├── views/
│   └── config/
│       └── index.mustache (formulario de configuración)
└── lang/
    ├── es/
    │   └── ldap-auth.php
    └── en/
        └── ldap-auth.php
```

### 15.3 Integración con el Sistema

**Flujo conceptual**:

1. **Instalación**: Admin instala el plugin desde ZIP
2. **Configuración**: Admin va a `/admin/tools/ldap-auth/config` y configura:
   - Servidor LDAP (ej: ldap://ldap.example.com)
   - Puerto (389 o 636 para LDAPS)
   - Base DN (ej: dc=example,dc=com)
   - Bind DN (ej: cn=admin,dc=example,dc=com)
   - Bind password
3. **Activación**: Admin activa el plugin
4. **Login**: Usuario intenta hacer login
   - AuthController dispara hook `auth.login_attempt`
   - Plugin LDAP escucha ese hook
   - Intenta autenticar contra LDAP primero
   - Si éxito en LDAP:
     - Sincronizar usuario en BD local (crear si no existe)
     - Marcar como autenticado
   - Si falla LDAP:
     - Continuar con autenticación local (DB)

### 15.4 Configuración del Plugin

**Settings en BD** (tabla plugin_config):
- `ldap_server`: URL del servidor LDAP
- `ldap_port`: Puerto de conexión
- `ldap_base_dn`: Base DN para búsquedas
- `ldap_bind_dn`: DN para binding
- `ldap_bind_password`: Password para binding (encriptado)
- `ldap_user_filter`: Filtro LDAP para buscar usuarios
- `ldap_enabled`: 1=activo, 0=inactivo
- `ldap_fallback_local`: 1=intentar local si LDAP falla, 0=solo LDAP

---

## 16. CRITERIOS DE ÉXITO DE LA FASE 2

### 16.1 Funcionalidades Implementadas

✅ El sistema debe cumplir:

1. **Instalación dinámica de plugins**
   - Se puede subir un ZIP desde `/admin/plugins/install`
   - El instalador valida el paquete y muestra información
   - El plugin se instala en el directorio correcto según su tipo
   - Se registra en la base de datos
   - Se activa automáticamente si el usuario lo selecciona

2. **Detección automática de tipo**
   - El instalador lee el campo `type` del plugin.json
   - Determina automáticamente el directorio de destino
   - Valida que el tipo es soportado
   - Muestra el tipo detectado en la UI de instalación

3. **Segmentación por directorios**
   - Tools se instalan en `/modules/plugins/tools/`
   - Auth se instalan en `/modules/plugins/auth/`
   - Themes se instalan en `/modules/plugins/themes/`
   - Reports se instalan en `/modules/plugins/reports/`
   - Modules se instalan en `/modules/plugins/modules/`
   - Integrations se instalan en `/modules/plugins/integrations/`

4. **Desinstalación segura**
   - Se puede desinstalar un plugin desde la UI
   - Se ejecuta el método uninstall() del plugin
   - Se eliminan archivos del filesystem
   - Se eliminan tablas de BD creadas por el plugin (si aplica)
   - Se eliminan registros de configuración
   - No se pueden desinstalar plugins core (is_core=1)

5. **Activación/Desactivación**
   - Toggle switch en la card del plugin
   - Se ejecuta activate() o deactivate() del plugin
   - Se verifica dependencias antes de activar
   - Se advierte si hay dependientes antes de desactivar
   - El estado se refleja inmediatamente en la UI

6. **Sistema de hooks funcional**
   - HookManager permite registrar y disparar hooks
   - Plugins pueden escuchar hooks del sistema
   - Se ejecutan callbacks en orden de prioridad
   - Se pueden pasar datos contextuales a los listeners
   - Listeners pueden modificar datos si el hook lo permite

7. **Gestión de dependencias**
   - Se validan dependencias al instalar
   - Se previene activación si faltan dependencias
   - Se advierte al desinstalar si hay dependientes
   - Se muestra información de dependencias en la UI

8. **Plugin MFA migrado**
   - El MFA actual se convierte en plugin independiente
   - Se instala como plugin core (no desinstalable)
   - Funciona igual que antes pero como plugin
   - Se puede desactivar (pero no desinstalar)

### 16.2 Tests de Validación

**Casos de prueba conceptuales**:

1. **Instalar plugin tool de backup**
   - ✅ Se instala en `/modules/plugins/tools/backup-manager/`
   - ✅ Aparece en lista de plugins con tipo "Tool"
   - ✅ Se puede activar y desactivar
   - ✅ Aparece en `/admin/tools/backup-manager` cuando está activo

2. **Instalar plugin auth LDAP**
   - ✅ Se instala en `/modules/plugins/auth/ldap-auth/`
   - ✅ Escucha hook `auth.login_attempt`
   - ✅ Permite login con credenciales LDAP
   - ✅ Fallback a autenticación local si está configurado

3. **Instalar plugin theme dark-pro**
   - ✅ Se instala en `/modules/plugins/themes/dark-pro/`
   - ✅ Aparece en selector de themes en `/admin/appearance`
   - ✅ Al activarlo, cambia toda la UI
   - ✅ Si se desactiva, vuelve al theme core

4. **Plugin con dependencias**
   - ✅ Instalar plugin A que requiere plugin B
   - ✅ Muestra error: "Requiere plugin B versión >= 1.0.0"
   - ✅ Instalar plugin B primero
   - ✅ Ahora plugin A se puede instalar correctamente

5. **Desinstalar plugin con dependientes**
   - ✅ Instalar plugin A y plugin B (que depende de A)
   - ✅ Intentar desinstalar plugin A
   - ✅ Muestra error: "Plugin B depende de este plugin"
   - ✅ Desactivar plugin B primero, luego se puede desinstalar A

---

## 17. RESTRICCIONES Y CONSIDERACIONES

### 17.1 Restricciones de Implementación

**Según el prompt, en esta fase**:
- ✅ PERMITIDO: Describir arquitectura, patrones, flujos y requisitos
- ✅ PERMITIDO: Describir estructuras conceptuales de archivos
- ❌ PROHIBIDO: Proporcionar código PHP/SQL/XML específico
- ❌ PROHIBIDO: Proponer funcionalidades nuevas no solicitadas
- ❌ PROHIBIDO: Modificar lógica de negocio existente sin análisis

**Este documento cumple**:
- ✅ Solo describe arquitectura y diseño
- ✅ Define patrones a implementar
- ✅ Especifica flujos y procesos
- ✅ Define estructuras conceptuales
- ✅ NO incluye código de implementación

### 17.2 Trabajo sobre Funcionalidades Existentes

**Principio fundamental**: NO proponer funcionalidades nuevas

Este sistema de plugins:
- ✅ Mejora el sistema de plugins básico existente en `AdminPlugins.php`
- ✅ Usa el instalador de addons existente en `InstallAddon.php`
- ✅ Extiende capacidades sin cambiar lógica de negocio
- ✅ Migra MFA existente a formato de plugin
- ✅ NO agrega funcionalidades no solicitadas en el prompt

### 17.3 Próximos Pasos

Después de completar el diseño de las especificaciones de TODAS las fases (2-7), se procederá a:

1. **Obtener aprobación del diseño** del usuario
2. **Planificar implementación** detallada
3. **Generar código** siguiendo las especificaciones

**Documentos pendientes**:
- I18N_SPEC.md (FASE 3)
- THEME_SPEC.md (FASE 4)
- DATABASE_NORMALIZATION_SPEC.md (FASE 6)
- INSTALLER_SPEC.md (FASE 7)

---

**FIN DE ESPECIFICACIÓN FASE 2: SISTEMA DE PLUGINS DINÁMICO**

Este documento define la arquitectura completa del sistema de plugins sin incluir código de implementación, siguiendo estrictamente las restricciones del prompt.
