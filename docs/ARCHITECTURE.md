# ARQUITECTURA ACTUAL - NEXOSUPPORT

**Fecha**: 2025-11-12
**Versión**: 1.0
**Proyecto**: NexoSupport Authentication System

---

## 1. VISIÓN GENERAL DE LA ARQUITECTURA

NexoSupport implementa una arquitectura **MVC (Model-View-Controller)** con capas bien definidas y cumplimiento de estándares PSR (PSR-4 para autoloading, PSR-7 para HTTP messages).

### 1.1 Diagrama de Arquitectura de Alto Nivel

```
┌─────────────────────────────────────────────────────────────────┐
│                         PUBLIC LAYER                             │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │  public_html/index.php (Front Controller)                │   │
│  │  ┌──────────┐  ┌────────┐  ┌────────┐  ┌──────────┐     │   │
│  │  │  Assets  │  │ Images │  │  CSS   │  │    JS    │     │   │
│  │  └──────────┘  └────────┘  └────────┘  └──────────┘     │   │
│  └──────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                         CORE LAYER                               │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │  Bootstrap → Autoloader → Router → Middleware            │   │
│  └──────────────────────────────────────────────────────────┘   │
│  ┌──────────────┬─────────────┬──────────────┬──────────────┐   │
│  │   Database   │   Session   │    I18n      │    Utils     │   │
│  │   (PDO +     │   (JWT)     │ (Translator) │  (XMLParser) │   │
│  │  Abstraction)│             │              │   (Logger)   │   │
│  └──────────────┴─────────────┴──────────────┴──────────────┘   │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                       MODULES LAYER                              │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │  Controllers (AuthController, AdminController, etc.)    │    │
│  └─────────────────────────────────────────────────────────┘    │
│  ┌──────────────┬─────────────────┬─────────────────────────┐   │
│  │   Managers   │      Theme      │     Admin Tools         │   │
│  │ (UserManager,│   (ThemeIser)   │  (Mfa, InstallAddon)    │   │
│  │ RoleManager, │                 │                         │   │
│  │ PermManager) │                 │                         │   │
│  └──────────────┴─────────────────┴─────────────────────────┘   │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                      PRESENTATION LAYER                          │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │  Mustache Renderer → Views (layouts, components, pages) │    │
│  └─────────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                         DATA LAYER                               │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │  MySQL / PostgreSQL / SQLite (via PDO)                  │    │
│  │  14 Tables + Indexes + Foreign Keys                     │    │
│  └─────────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────────┘
```

---

## 2. ARQUITECTURA POR CAPAS

### 2.1 CAPA PÚBLICA (Public Layer)

**Responsabilidad**: Punto de entrada único y recursos estáticos

#### Componentes
- **Front Controller** (`public_html/index.php`)
  - Verifica instalación
  - Inicializa Bootstrap
  - Registra rutas
  - Despacha requests con Router
  - Maneja errores 404 y 500

#### Flujo de Entrada
```
HTTP Request
    ↓
.htaccess (mod_rewrite) → index.php
    ↓
checkInstallation()
    ├─ Si no instalado → Redirect /install.php
    └─ Si instalado → Continuar
    ↓
Bootstrap::init()
    ↓
Router::dispatch(Request) → Response
    ↓
Response::send()
```

---

### 2.2 CAPA CORE (Core Layer)

**Responsabilidad**: Funcionalidades fundamentales del framework

#### 2.2.1 Bootstrap (`core/Bootstrap.php`)

**Inicialización del sistema en orden:**

```
1. loadConfiguration()     → ConfigManager (.env o config.php)
2. setupEnvironment()       → Validar requisitos PHP, extensiones
3. initializeLogging()      → Monolog (daily, single, syslog)
4. registerAutoloader()     → PSR-4 Autoloader
5. initializeDatabase()     → PDOConnection + Database abstraction
6. initializeSession()      → JWTSession (access + refresh tokens)
7. initializeRouter()       → PSR-7 Router
8. discoverModules()        → Auto-discovery de /modules/
```

#### 2.2.2 Routing System

**Router PSR-7** (`core/Routing/Router.php`):
- Soporta métodos HTTP: GET, POST, PUT, DELETE, PATCH
- Named routes: `url('route.name')`
- Grupos de rutas con prefijos: `$router->group('/admin', ...)`
- Parámetros dinámicos: `/users/{id}`, `/posts/{slug}`
- Dispatcher: `dispatch(ServerRequestInterface): ResponseInterface`

**Middleware** (`core/Middleware/`):
- `AuthMiddleware`: Verificar autenticación
- `AdminMiddleware`: Verificar rol admin
- `PermissionMiddleware`: Verificar permisos granulares (RBAC)

⚠️ **Nota**: Middleware NO está integrado en el Router, se aplica manualmente en controllers.

#### 2.2.3 Database System

**Database Abstraction** (`core/Database/Database.php`):
```
PDOConnection (wrapper sobre PDO nativo)
    ↓
DatabaseAdapter (abstracción multi-driver: MySQL, PostgreSQL, SQLite)
    ↓
Database (métodos CRUD: insert, update, delete, select, count, etc.)
```

**SchemaInstaller** (`core/Database/SchemaInstaller.php`):
- Parsea `schema.xml` con DOMDocument
- Crea tablas con DatabaseAdapter
- Inserta datos iniciales
- Crea índices y foreign keys
- Asigna permisos al rol admin

#### 2.2.4 Session & Authentication

**JWTSession** (`core/Session/JWTSession.php`):
- Genera access tokens (corta vida: 1h)
- Genera refresh tokens (larga vida: 7 días)
- Valida firma HMAC SHA256
- Enriquece tokens con roles del usuario
- Maneja cookies seguras

**Token Structure**:
```json
{
  "iat": 1699876543,
  "exp": 1699880143,
  "type": "access",
  "user_id": 1,
  "username": "admin",
  "email": "admin@example.com",
  "roles": ["admin"],
  "role_ids": [1]
}
```

#### 2.2.5 Internationalization (i18n)

**Translator** (`core/I18n/Translator.php`):
- Singleton pattern
- Carga archivos PHP: `/resources/lang/{locale}/*.php`
- Helper function: `__(key, replacements, locale)`
- Pluralización: `trans_choice(key, count)`
- Fallback automático a locale por defecto

**Ejemplo de uso**:
```php
// En controllers
echo __('auth.welcome', ['name' => 'Juan']);

// En vistas Mustache (requiere helper custom)
{{#__}}auth.welcome{{/__}}
```

⚠️ **Pendiente**: Helper de traducción para Mustache no está implementado.

#### 2.2.6 Utilities

**XMLParser** (`core/Utils/XMLParser.php`):
- Soporte DOMDocument y SimpleXML
- Validación XSD: `validateSchema(xsdPath)`
- XPath queries: `getValue(xpath)`, `getValues(xpath)`
- Conversión: `toArray()`, `fromArray()`

**Logger** (`core/Utils/Logger.php`):
- Wrapper sobre Monolog
- Canales: daily, single, syslog
- Niveles: DEBUG, INFO, WARNING, ERROR, CRITICAL
- Métodos estáticos: `Logger::info()`, `Logger::error()`, etc.

**Helpers** (`core/Utils/Helpers.php`):
- `validateEmail()`: Validación de emails
- `hashPassword()`: Hash con `password_hash()`
- `generateToken()`: Tokens aleatorios
- `sanitizeInput()`: Sanitización de inputs

---

### 2.3 CAPA MODULES (Modules Layer)

**Responsabilidad**: Lógica de negocio y controladores

#### 2.3.1 Controllers

**Patrón**: Controllers reciben Request PSR-7 y devuelven Response PSR-7

**Controllers existentes**:
- **AuthController**: Login, logout, autenticación
- **HomeController**: Home pública, dashboard usuario
- **AdminController**: Panel admin, configuración, reportes
- **UserManagementController**: CRUD usuarios
- **RoleController**: CRUD roles
- **PermissionController**: CRUD permisos

**Ejemplo de flujo**:
```
Request → AuthController::processLogin()
    ├─ Validar credenciales
    ├─ UserManager::getUserByUsername()
    ├─ password_verify()
    ├─ Crear sesión
    └─ Response::redirect('/dashboard')
```

#### 2.3.2 Managers (Service Layer)

**UserManager** (`modules/User/UserManager.php`):
- CRUD usuarios
- Soft delete & restore
- Suspend/unsuspend
- Búsqueda con filtros
- Asignación de roles: `assignRole()`, `syncRoles()`

**RoleManager** (`modules/Roles/RoleManager.php`):
- CRUD roles
- Clone role con capabilities
- Protección de roles del sistema (admin, user, guest)
- Estadísticas de roles

**PermissionManager** (`modules/Roles/PermissionManager.php`):
- RBAC con 4 niveles de permisos:
  - `CAP_INHERIT = 0` (heredar)
  - `CAP_ALLOW = 1` (permitir)
  - `CAP_PREVENT = -1` (prevenir)
  - `CAP_PROHIBIT = -1000` (prohibir absolutamente)
- `hasCapability(userId, capability)` con cache
- `requireCapability()` lanza excepción si no tiene permiso

#### 2.3.3 Admin Tools

**AdminPlugins** (`modules/Admin/AdminPlugins.php`):
- Listar plugins
- Enable/disable plugins
- Registrar nuevos plugins
- Actualizar versiones

**InstallAddon** (`modules/Admin/Tool/InstallAddon/InstallAddon.php`):
- Instalar plugins desde ZIP
- Validar paquete ZIP (busca `version.php`)
- Extraer a `/modules/`
- Ejecutar `install.php` si existe
- Desinstalar: ejecutar `uninstall.php` y eliminar archivos

**MfaManager** (`modules/Admin/Tool/Mfa/MfaManager.php`):
- Gestionar factores MFA (TOTP, Email, Backup Codes)
- Validar códigos MFA
- Verificar políticas MFA (requerido por rol)
- Auditoría completa de eventos MFA
- Grace period para nuevos usuarios

#### 2.3.4 Theme System

**ThemeIser** (`modules/Theme/Iser/ThemeIser.php`):
- Cargar configuración del theme (PHP o BD)
- Renderizar layouts: base, app, fullwidth
- Gestionar assets (CSS, JS) con `ThemeAssets`
- Gestionar navegación con `ThemeNavigation`
- Preferencias de usuario: dark mode, sidebar collapsed, font size

**Estructura de Theme**:
```
/modules/Theme/Iser/
├── ThemeIser.php           (Clase principal)
├── ThemeAssets.php         (Gestión de CSS/JS)
├── ThemeLayouts.php        (Definición de layouts)
├── ThemeNavigation.php     (Generación de menús)
├── config/
│   └── theme_settings.php  (Configuración por defecto)
├── templates/              (Templates Mustache)
└── assets/                 (CSS, JS, imágenes)
```

---

### 2.4 CAPA PRESENTATION (Presentation Layer)

**Responsabilidad**: Renderizado de vistas con Mustache

#### MustacheRenderer (`core/View/MustacheRenderer.php`)

**Características**:
- Paths de templates configurables
- Datos globales compartidos en todos los templates
- Helpers custom (pueden agregarse)
- Autoescaping de HTML por defecto

**Estructura de Vistas** (`/resources/views/`):
```
layouts/
├── base.mustache          (HTML base con <!DOCTYPE>, <head>, <body>)
└── app.mustache           (Layout con sidebar y topbar)

components/
├── navigation/
│   ├── sidebar.mustache
│   ├── topbar.mustache
│   ├── breadcrumbs.mustache
│   └── user-menu.mustache
├── header.mustache
├── footer.mustache
├── card.mustache
└── stats.mustache

pages/
├── auth/
│   └── login.mustache
├── dashboard/
│   └── index.mustache
├── admin/
│   ├── index.mustache
│   ├── users/
│   ├── roles/
│   └── permissions/
└── profile/
    └── index.mustache
```

**Ejemplo de renderizado**:
```php
$renderer = MustacheRenderer::getInstance();
$html = $renderer->render('admin/users/index', [
    'users' => $users,
    'total' => $totalUsers,
    'page_title' => 'User Management'
]);
```

---

### 2.5 CAPA DATA (Data Layer)

**Responsabilidad**: Persistencia de datos

#### Schema de Base de Datos

**14 Tablas**:
1. `config` - Configuración del sistema (key-value)
2. `users` - Usuarios
3. `password_reset_tokens` - Tokens de reset
4. `login_attempts` - Historial de intentos de login
5. `user_profiles` - Perfiles de usuario (1:1 con users)
6. `roles` - Roles del sistema
7. `permissions` - 35 permisos granulares
8. `user_roles` - Relación N:M usuarios-roles
9. `role_permissions` - Relación N:M roles-permisos
10. `sessions` - Sesiones activas
11. `jwt_tokens` - Tokens JWT (preparado para blacklist)
12. `user_mfa` - Configuración MFA por usuario
13. `logs` - Logs del sistema
14. `audit_log` - Auditoría de acciones

**Relaciones Principales**:
```
users 1:N user_roles N:1 roles
roles 1:N role_permissions N:1 permissions
users 1:1 user_profiles
users 1:N login_attempts
users 1:N user_mfa
users 1:N jwt_tokens
users 1:N password_reset_tokens
```

---

## 3. PATRONES DE DISEÑO IMPLEMENTADOS

### 3.1 Patrones Creacionales

**Singleton**:
- `Bootstrap::getInstance()`
- `ConfigManager::getInstance()`
- `Translator::getInstance()`
- `MustacheRenderer::getInstance()`

**Factory**:
- `Bootstrap` crea instancias de Database, Router, Logger, etc.
- `DatabaseDriverDetector::createAdapter()` crea adaptadores según el driver

### 3.2 Patrones Estructurales

**Adapter**:
- `DatabaseAdapter` adapta diferentes drivers SQL (MySQL, PostgreSQL, SQLite)

**Facade**:
- `Database` es facade sobre PDOConnection y DatabaseAdapter
- `Logger` es facade sobre Monolog

### 3.3 Patrones de Comportamiento

**Strategy**:
- MFA factors implementan `MfaFactorInterface` (TOTP, Email, Backup)
- Database adapters implementan `DatabaseAdapterInterface`

**Observer** (parcialmente):
- Sistema de logging observa eventos del sistema

**Template Method**:
- `SchemaInstaller::install()` define flujo de instalación, subclases sobrescriben pasos

---

## 4. FLUJO DE REQUEST COMPLETO

### 4.1 Request Público (No Autenticado)

```
1. HTTP GET /login
    ↓
2. .htaccess → public_html/index.php
    ↓
3. checkInstallation()
    ├─ Verificar .env existe
    └─ Verificar INSTALLED=true
    ↓
4. Bootstrap::init()
    ├─ ConfigManager::loadConfiguration()
    ├─ Environment::checkRequirements()
    ├─ Logger::initialize()
    ├─ Autoloader::register()
    ├─ Database::connect()
    ├─ JWTSession::initialize()
    └─ Router::initialize()
    ↓
5. Router::dispatch(Request)
    ├─ Match route: GET /login → AuthController::showLogin
    ├─ Ejecutar handler
    └─ Retornar Response
    ↓
6. AuthController::showLogin()
    ├─ MustacheRenderer::render('auth/login')
    └─ Response::html($html)
    ↓
7. Response::send()
    └─ Output HTML al navegador
```

### 4.2 Request Protegido (Autenticado con RBAC)

```
1. HTTP GET /admin/users
    ↓
2. Bootstrap::init() (igual que arriba)
    ↓
3. Router::dispatch(Request)
    ├─ Match route: GET /admin/users → UserManagementController::index
    ├─ Ejecutar handler
    └─ Retornar Response
    ↓
4. UserManagementController::index()
    ├─ Verificar autenticación:
    │   └─ isset($_SESSION['user_id']) && $_SESSION['authenticated']
    ├─ PermissionManager::hasCapability($userId, 'users.view')
    │   ├─ Obtener roles del usuario
    │   ├─ Para cada rol, obtener permisos
    │   ├─ Aplicar algoritmo de resolución (PROHIBIT > ALLOW > PREVENT)
    │   └─ Cache resultado
    ├─ Si NO tiene permiso → Response::html(403)
    ├─ Si SÍ tiene permiso → Continuar
    ├─ UserManager::getUsers(limit, offset, filters)
    │   └─ Database::select('users', ...)
    ├─ MustacheRenderer::render('admin/users/index', data)
    └─ Response::html($html)
    ↓
5. Response::send()
    └─ Output HTML al navegador
```

### 4.3 Request de Instalación

```
1. HTTP GET /install.php
    ↓
2. checkInstallation()
    ├─ Si ya instalado → Mostrar "Ya instalado"
    └─ Si no instalado → Continuar con instalador
    ↓
3. Instalador por Etapas:
    ETAPA 1: Verificación de requisitos
        ├─ PHP >= 8.1
        ├─ Extensiones: pdo, json, mbstring, openssl
        └─ Permisos de escritura: /var/, raíz
    ETAPA 2: Configuración de BD
        ├─ Driver, host, port, database, user, pass
        └─ Probar conexión (PDO)
    ETAPA 3: Instalación de BD
        ├─ SchemaInstaller::parseFile('schema.xml')
        ├─ SchemaInstaller::createTable(table) × 14
        ├─ SchemaInstaller::insertInitialData()
        └─ Asignar permisos al rol admin
    ETAPA 4: Usuario Administrador
        ├─ Validar datos (username, email, password)
        └─ UserManager::create() + assignRole(admin)
    ETAPA 5: Finalización
        ├─ Generar .env con todas las variables
        ├─ Establecer INSTALLED=true
        └─ Mostrar "Instalación completada"
```

---

## 5. PUNTOS DE EXTENSIÓN

### 5.1 Para Sistema de Plugins Dinámico

**Puntos actuales**:
1. `Autoloader::discoverModules()` - Descubre módulos automáticamente
2. `AdminPlugins::registerPlugin()` - Registra plugins en BD
3. `InstallAddon::installPackage()` - Instala desde ZIP

**Mejoras requeridas**:
- Leer `plugin.json` en lugar de `version.php`
- Detectar tipo automáticamente (`type` field)
- Segmentar por directorios: `/plugins/tools/`, `/plugins/mfa/`, etc.
- Sistema de hooks: `register_hook('user.created', callback)`
- UI web de instalación en `/admin/plugins/install`

### 5.2 Para Theme Configurable

**Puntos actuales**:
1. `ThemeIser::loadDatabaseConfig()` - Carga config de BD
2. `ThemeIser::updateThemeSettings()` - Actualiza settings
3. `SettingsManager` - Almacena configuraciones en BD

**Mejoras requeridas**:
- UI de configuración en `/admin/appearance`
- Configurar: colores, tipografía, logos, layouts, modo oscuro
- Theme plugins que sobrescriban el core
- Preview en tiempo real

### 5.3 Para Internacionalización Completa

**Puntos actuales**:
1. `Translator::translate()` - Traduce strings
2. Helper `__()` - Acceso rápido
3. Archivos `/resources/lang/{locale}/*.php`

**Mejoras requeridas**:
- Helper Mustache: `{{#__}}auth.welcome{{/__}}`
- Más archivos de idioma: `admin.php`, `users.php`, `roles.php`, etc.
- Implementar portugués (pt)
- API endpoint: `/api/i18n/{lang}` para JavaScript

---

## 6. DEPENDENCIAS ENTRE COMPONENTES

### 6.1 Diagrama de Dependencias

```
Bootstrap
├─> ConfigManager
├─> Environment
├─> Logger (Monolog)
├─> Autoloader
├─> PDOConnection
│   └─> DatabaseAdapter
│       └─> Database
├─> JWTSession
│   └─> Database (para obtener roles)
└─> Router

Controllers
├─> Database
├─> Managers (UserManager, RoleManager, PermissionManager)
├─> MustacheRenderer
└─> Translator

Managers
└─> Database

MustacheRenderer
├─> Mustache_Engine (vendor)
└─> ThemeIser (opcional, para layouts)

ThemeIser
├─> MustacheRenderer
├─> SettingsManager
├─> ThemeAssets
├─> ThemeLayouts
└─> ThemeNavigation
```

### 6.2 Acoplamiento y Cohesión

**Bajo acoplamiento** ✅:
- `Database` abstrae PDO → Controllers no dependen de PDO directamente
- `Translator` es independiente → Puede usarse en cualquier componente
- `Logger` es estático → Acceso global sin inyección

**Alto acoplamiento** ⚠️:
- `Controllers` crean instancias de Managers directamente (debería usar DI Container)
- `Bootstrap` depende de muchas clases concretas (debería usar interfaces)
- `UserManager` maneja users + roles (debería separarse)

**Alta cohesión** ✅:
- `JWTSession` solo maneja JWT
- `XMLParser` solo parsea XML
- `SchemaInstaller` solo instala schema

---

## 7. SEGURIDAD

### 7.1 Medidas de Seguridad Implementadas

**Autenticación**:
- ✅ Password hashing con `password_hash()` (bcrypt)
- ✅ JWT con firma HMAC SHA256
- ✅ Refresh tokens para renovación segura
- ✅ Bloqueo de cuentas después de 5 intentos fallidos
- ✅ Cookies HTTP-only y Secure (en producción)

**Autorización**:
- ✅ RBAC con 4 niveles de permisos (INHERIT, ALLOW, PREVENT, PROHIBIT)
- ✅ Middleware de verificación de permisos
- ✅ Roles del sistema protegidos (admin, user, guest)

**SQL Injection**:
- ✅ Prepared statements en todas las queries
- ✅ Binding de parámetros con PDO

**XSS (Cross-Site Scripting)**:
- ✅ Mustache auto-escapa HTML por defecto
- ⚠️ Sanitización manual en inputs (debería mejorarse)

**CSRF (Cross-Site Request Forgery)**:
- ⚠️ Token CSRF generado pero no validado consistentemente
- ⚠️ Debería validarse en todos los POST/PUT/DELETE

**Auditoría**:
- ✅ Tabla `login_attempts` registra todos los intentos
- ✅ Tabla `audit_log` registra acciones críticas
- ✅ MFA audit en tabla `mfa_audit`

---

## 8. PERFORMANCE

### 8.1 Optimizaciones Implementadas

**Cache**:
- ✅ `PermissionManager` cachea permisos en memoria (request-level)
- ✅ Translator cachea traducciones (request-level)
- ⚠️ NO hay cache persistente (Redis, Memcached)

**Database**:
- ✅ Índices en columnas frecuentemente consultadas
- ✅ Foreign keys para integridad referencial
- ⚠️ NO hay query caching

**Assets**:
- ⚠️ NO hay minificación automática de CSS/JS
- ⚠️ NO hay CDN configurado

### 8.2 Cuellos de Botella Potenciales

1. **Sin cache persistente**: Cada request recalcula permisos, traducciones
2. **Sin query caching**: Queries repetidas se ejecutan múltiples veces
3. **Logging síncrono**: Escribir logs en disco puede ralentizar requests
4. **Sin lazy loading**: Todos los módulos se cargan siempre

---

## 9. CONCLUSIONES

### 9.1 Fortalezas de la Arquitectura

✅ **Cumplimiento de estándares**: PSR-4, PSR-7
✅ **Separación de responsabilidades**: MVC bien definido
✅ **Seguridad robusta**: Autenticación JWT + RBAC granular
✅ **Extensibilidad**: Autoloader permite agregar módulos dinámicamente
✅ **Multi-driver DB**: Soporta MySQL, PostgreSQL, SQLite
✅ **Internacionalización funcional**: Sistema i18n completo

### 9.2 Áreas de Mejora

⚠️ **Middleware no integrado en Router**: Se aplica manualmente
⚠️ **Dependency Injection**: Controllers crean dependencias directamente
⚠️ **Cache**: Sin cache persistente (Redis, Memcached)
⚠️ **Testing**: Sin tests unitarios/integración
⚠️ **Plugins**: Sistema básico, falta detección de tipos y segmentación
⚠️ **Theme**: No completamente configurable desde admin
⚠️ **CSRF**: Token generado pero no validado consistentemente

### 9.3 Recomendaciones Arquitectónicas

1. **Implementar Dependency Injection Container** (PSR-11)
2. **Integrar middleware en Router** (PSR-15)
3. **Agregar capa de cache persistente** (Redis/Memcached)
4. **Implementar Event Dispatcher** para hooks de plugins
5. **Crear interfaces para todos los servicios** (Dependency Inversion)
6. **Separar Service Layer de Controllers** (Services + Repositories)

---

**Siguiente paso**: Revisar `DATABASE_ANALYSIS.md` para análisis detallado de normalización y `FLOWS.md` para flujos funcionales completos.
