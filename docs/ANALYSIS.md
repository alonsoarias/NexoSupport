# ANÁLISIS EXHAUSTIVO DEL PROYECTO NEXOSUPPORT

**Fecha**: 2025-11-12
**Versión**: 1.0
**Proyecto**: NexoSupport Authentication System
**Autor**: Claude (Análisis para Refactorización Integral)

---

## RESUMEN EJECUTIVO

Este documento presenta un análisis exhaustivo del proyecto **NexoSupport**, sistema de autenticación y gestión basado en PHP 8.1+. El análisis se realizó archivo por archivo para identificar la arquitectura actual, funcionalidades implementadas y áreas de mejora para la refactorización integral.

### Hallazgos Clave

✅ **YA IMPLEMENTADO:**
- Sistema de internacionalización (i18n) completo con `Translator.php`
- XML Parser robusto para instalación de base de datos
- SchemaInstaller que parsea `schema.xml`
- Sistema RBAC con 35 permisos granulares
- MFA (Multi-Factor Authentication) con TOTP, Email y Backup Codes
- Theme system base (ISER)
- Instalador web por etapas
- Gestión de usuarios con soft delete
- Sistema de plugins básico

❌ **REQUIERE IMPLEMENTACIÓN/MEJORA:**
- Sistema de plugins dinámico con detección automática de tipo
- Segmentación de herramientas (tools, mfa, themes, reports, etc.)
- Theme configurable desde panel admin (colores, tipografía, layouts)
- Instalación dinámica de plugins vía interfaz web
- Internacionalización completa (muchos strings aún hardcodeados)
- Normalización estricta a 3FN (algunas violaciones detectadas)

---

## 1. ANÁLISIS DEL DIRECTORIO /core/

El directorio `/core/` contiene el núcleo del sistema con arquitectura MVC bien estructurada.

### 1.1 Bootstrap.php

**Ubicación**: `core/Bootstrap.php`
**Líneas**: 503
**Responsabilidad**: Sistema de inicialización del framework

#### Análisis de Funcionalidad

```
Proceso de inicialización:
1. Carga configuración (ConfigManager)
2. Setup environment (production/development)
3. Inicializa logging (Monolog)
4. Registra autoloader PSR-4
5. Inicializa database (PDOConnection + Database)
6. Inicializa JWT session (JWTSession)
7. Inicializa router (PSR-7 compliant)
8. Descubre y registra módulos automáticamente
```

#### Patrones de Diseño Identificados
- **Singleton Pattern**: Instancia única del Bootstrap
- **Dependency Injection**: Inyecta dependencias en componentes
- **Factory Pattern**: Crea instancias de Database, Router, etc.

#### Puntos de Extensión
- `discoverModules()`: Permite carga dinámica de módulos
- `run()`: Método principal que despacha el router

#### Dependencias
- `ConfigManager`: Gestión de configuración
- `Environment`: Validación de entorno y requisitos PHP
- `Logger`: Sistema de logging
- `PDOConnection`, `Database`: Capa de BD
- `Router`: Enrutamiento PSR-7

#### Evaluación SOLID
- ✅ **Single Responsibility**: Maneja solo la inicialización del sistema
- ✅ **Open/Closed**: Extensible vía discovery de módulos
- ⚠️ **Dependency Inversion**: Depende de implementaciones concretas, no interfaces

---

### 1.2 Autoloader.php

**Ubicación**: `core/Autoloader.php`
**Líneas**: 301
**Responsabilidad**: Cargador de clases PSR-4 compliant

#### Funcionalidades Principales
1. **Registro PSR-4**: `addNamespace()` registra namespaces con sus directorios
2. **Carga dinámica**: `loadClass()` carga clases automáticamente
3. **Discovery de módulos**: `discoverModules()` escanea `/modules/` y registra namespaces

#### Estructura de Namespaces
```
ISER\Core\ → /core/
ISER\Modules\ → /modules/
```

#### Evaluación
- ✅ Cumple PSR-4 estrictamente
- ✅ Permite registro dinámico de namespaces
- ✅ `discoverModules()` facilita sistema de plugins

---

### 1.3 Routing/Router.php

**Ubicación**: `core/Routing/Router.php`
**Líneas**: 354
**Responsabilidad**: Sistema de enrutamiento PSR-7

#### Funcionalidades
1. **Registro de rutas**: `get()`, `post()`, `put()`, `delete()`, `patch()`
2. **Grupos de rutas**: `group()` con prefijo compartido
3. **Parámetros dinámicos**: Soporta `{id}`, `{slug}`, etc.
4. **Named routes**: `url('route.name')` genera URLs
5. **PSR-7 Compliant**: `dispatch(ServerRequestInterface): ResponseInterface`

#### Evaluación
- ✅ PSR-7 compliant
- ✅ Soporte de middleware implícito (puede mejorarse)
- ✅ Parámetros de ruta con regex

---

### 1.4 Database/Database.php

**Ubicación**: `core/Database/Database.php`
**Líneas**: 463
**Responsabilidad**: Capa de abstracción de base de datos

#### Métodos Principales
- `insert()`, `update()`, `delete()`: CRUD básico
- `select()`, `selectOne()`: Queries SELECT
- `count()`, `exists()`: Operaciones auxiliares
- `query()`, `execute()`: Raw SQL
- `transaction()`: Manejo de transacciones con callback

#### Evaluación
- ✅ Abstracción limpia sobre PDO
- ✅ Prepared statements (seguridad SQL injection)
- ✅ Soporte de transacciones
- ⚠️ Query logging opcional (performance)
- ⚠️ No usa Query Builder avanzado (podría mejorarse)

---

### 1.5 Database/SchemaInstaller.php

**Ubicación**: `core/Database/SchemaInstaller.php`
**Líneas**: 651
**Responsabilidad**: Instalación de base de datos desde schema.xml

#### Funcionalidades Críticas
1. **Parseo XML**: Usa `DOMDocument` + `SimpleXML`
2. **Creación de tablas**: `createTable()` con DatabaseAdapter
3. **Índices y Foreign Keys**: `createIndexes()`, `createForeignKeys()`
4. **Datos iniciales**: `insertInitialData()` desde `<data>` nodes
5. **Multi-driver**: Soporta MySQL, PostgreSQL, SQLite vía `DatabaseAdapter`
6. **Permisos de admin**: `assignAdminPermissions()` asigna todos los permisos al rol admin

#### Flujo de Instalación
```
1. Parsear schema.xml con DOMDocument
2. Convertir a array con convertSimpleXMLToArray()
3. Extraer metadata (charset, collation, engine)
4. Para cada tabla:
   a. Construir SQL con DatabaseAdapter
   b. Ejecutar CREATE TABLE
   c. Crear índices
   d. Crear foreign keys
   e. Insertar datos iniciales
5. Asignar permisos al rol admin (role_id=1)
```

#### Evaluación
- ✅ XML parser robusto
- ✅ Soporte multi-driver (MySQL, PostgreSQL, SQLite)
- ✅ Rollback en caso de error
- ✅ Logging detallado del proceso
- ⚠️ Modo silent/verbose podría mejorarse

---

### 1.6 Config/ConfigManager.php

**Ubicación**: `core/Config/ConfigManager.php`
**Líneas**: 379
**Responsabilidad**: Gestión de configuración desde .env o config.php

#### Características Importantes
1. **Mutual Exclusivity**: Solo permite .env O config.php (no ambos)
2. **Validación**: Verifica claves requeridas (`APP_ENV`, `DB_HOST`, `DB_DATABASE`, etc.)
3. **Helpers específicos**: `getDatabaseConfig()`, `getJwtConfig()`, `getMailConfig()`, etc.
4. **Singleton Pattern**: Una sola instancia

#### Claves Requeridas
```
- APP_ENV
- DB_HOST
- DB_DATABASE
- DB_USERNAME
- JWT_SECRET
```

#### Evaluación
- ✅ Bien estructurado
- ✅ Validación de configuración requerida
- ✅ Helpers por categoría
- ⚠️ Podría usar dotenv con cache para performance

---

### 1.7 Session/JWTSession.php

**Ubicación**: `core/Session/JWTSession.php`
**Líneas**: 496
**Responsabilidad**: Manejo de sesiones con JSON Web Tokens

#### Funcionalidades
1. **Generación de tokens**: `generate()` con payload personalizado
2. **Validación**: `validate()` verifica firma y expiración
3. **Token pairs**: `generateTokenPair()` crea access + refresh tokens
4. **Enriquecimiento de roles**: `getUserRolesFromDb()` obtiene roles dinámicamente
5. **Refresh tokens**: `refresh()` renueva access token desde refresh token
6. **Cookie management**: `setTokenCookie()`, `clearTokenCookie()`

#### Estructura del Token
```json
{
  "iat": 1699876543,
  "exp": 1699880143,
  "type": "access",
  "user_id": 1,
  "username": "admin",
  "email": "admin@example.com",
  "roles": ["admin", "moderator"],
  "role_ids": [1, 2]
}
```

#### Evaluación
- ✅ JWT estándar (firebase/php-jwt)
- ✅ Access + refresh tokens
- ✅ Roles en el token para performance
- ✅ Manejo seguro de cookies
- ⚠️ Falta blacklist de tokens revocados (mencionado en TODOs)

---

### 1.8 I18n/Translator.php

**Ubicación**: `core/I18n/Translator.php`
**Líneas**: 275
**Responsabilidad**: Sistema de internacionalización

#### ✅ SISTEMA I18N YA IMPLEMENTADO

**Funcionalidades:**
1. **Singleton**: Una instancia global
2. **Carga dinámica**: Lee archivos PHP de `/resources/lang/{locale}/*.php`
3. **Fallback locale**: Si no encuentra traducción, usa locale por defecto
4. **Helper function**: `__(string $key, array $replace = [], ?string $locale = null)`
5. **Pluralización**: `trans_choice()` para formas singulares/plurales
6. **Reemplazo de variables**: `:variable`, `:VARIABLE`, `:Variable`

#### Estructura de Archivos
```
/resources/lang/
├── es/
│   ├── common.php
│   ├── auth.php
│   └── installer.php
├── en/
│   ├── common.php
│   ├── auth.php
│   └── installer.php
└── pt/  (pendiente)
```

#### Ejemplo de Uso
```php
// En controller
echo __('auth.welcome', ['name' => 'Juan']);

// En vista Mustache (requiere helper)
{{#__}}auth.welcome{{/__}}
```

#### Evaluación
- ✅ Sistema completo e implementado
- ✅ Fallback automático
- ✅ Pluralización básica
- ⚠️ Falta helper para Mustache (necesita implementarse)
- ⚠️ Faltan traducciones en muchos archivos

---

### 1.9 Utils/XMLParser.php

**Ubicación**: `core/Utils/XMLParser.php`
**Líneas**: 464
**Responsabilidad**: Parser XML genérico y robusto

#### Funcionalidades
1. **Parseo flexible**: DOMDocument o SimpleXML
2. **Validación XSD**: `validateSchema()`
3. **XPath queries**: `getValue()`, `getValues()`
4. **Conversión**: `toArray()`, `fromArray()`
5. **Guardado**: `save()` a archivo

#### Evaluación
- ✅ Parser muy completo
- ✅ Manejo de errores robusto
- ✅ Soporte XPath
- ✅ Usado por SchemaInstaller

---

### 1.10 Middleware

**Ubicación**: `core/Middleware/`

#### AuthMiddleware.php
- Verifica que el usuario esté autenticado
- Redirige a `/login` si no está autenticado

#### AdminMiddleware.php
- Verifica que el usuario tenga rol de administrador
- Bloquea acceso si no es admin

#### PermissionMiddleware.php
- Verifica permisos granulares
- Usa `PermissionManager::hasCapability()`

#### Evaluación
- ✅ Middleware bien estructurado
- ⚠️ No está integrado con el Router (aplicación manual)
- ⚠️ Debería integrarse en el routing system

---

## 2. ANÁLISIS DEL DIRECTORIO /modules/

### 2.1 User/UserManager.php

**Ubicación**: `modules/User/UserManager.php`
**Líneas**: 380
**Responsabilidad**: Gestión completa de usuarios

#### Funcionalidades
1. **CRUD**: `create()`, `getUserById()`, `update()`, `delete()`
2. **Soft delete**: `softDelete()`, `restore()`, `isDeleted()`
3. **Suspensión**: `suspend()`, `unsuspend()`, `isSuspended()`
4. **Búsqueda**: `getUsers()` con filtros y paginación
5. **Bulk operations**: `bulkUpdate()`, `bulkSoftDelete()`, `bulkSuspend()`
6. **Roles**: `getUserRoles()`, `assignRole()`, `removeRole()`, `syncRoles()`, `hasRole()`

#### Evaluación
- ✅ CRUD completo
- ✅ Soft delete implementado
- ✅ Gestión de roles integrada
- ✅ Búsqueda con filtros

---

### 2.2 Roles/RoleManager.php

**Ubicación**: `modules/Roles/RoleManager.php`
**Líneas**: 191
**Responsabilidad**: Gestión de roles del sistema

#### Funcionalidades
1. **CRUD**: `createRole()`, `updateRole()`, `deleteRole()`, `getRole()`
2. **Clonación**: `cloneRole()` duplica un rol con sus capabilities
3. **Protección**: No permite eliminar roles del sistema (admin, user, guest)
4. **Estadísticas**: `getRoleStats()`, `getRolesWithCounts()`

#### Evaluación
- ✅ Gestión completa de roles
- ✅ Protección de roles del sistema
- ✅ Clone functionality útil

---

### 2.3 Roles/PermissionManager.php

**Ubicación**: `modules/Roles/PermissionManager.php`
**Líneas**: 235
**Responsabilidad**: Sistema RBAC con permisos granulares

#### Sistema de Permisos

**Niveles de permiso:**
```
CAP_INHERIT = 0      (heredar del contexto padre)
CAP_ALLOW = 1        (permitir explícitamente)
CAP_PREVENT = -1     (prevenir explícitamente)
CAP_PROHIBIT = -1000 (prohibir absolutamente)
```

**Algoritmo de resolución:**
1. Si algún rol tiene CAP_PROHIBIT → DENY (siempre)
2. Si algún rol tiene CAP_ALLOW → ALLOW (a menos que prohibit)
3. Si algún rol tiene CAP_PREVENT y ningún ALLOW → DENY
4. Por defecto CAP_INHERIT → DENY

#### Funcionalidades
1. **Verificación**: `hasCapability()` con cache
2. **Requerimiento**: `requireCapability()` lanza excepción si no tiene permiso
3. **Asignación**: `assignCapability()` asigna capability a rol
4. **Cache**: `clearCache()` limpia cache de permisos

#### Evaluación
- ✅ Sistema RBAC robusto
- ✅ Algoritmo de resolución bien definido
- ✅ Cache de permisos para performance
- ✅ 35 permisos granulares en 9 módulos

---

### 2.4 Admin/AdminPlugins.php

**Ubicación**: `modules/Admin/AdminPlugins.php`
**Líneas**: 210
**Responsabilidad**: Gestión de plugins del sistema

#### Funcionalidades Básicas
1. **Listar**: `getPlugins()`, `getEnabledPlugins()`
2. **Enable/Disable**: `enablePlugin()`, `disablePlugin()`
3. **Registro**: `registerPlugin()`
4. **Actualización**: `updatePluginVersion()`
5. **Estadísticas**: `getPluginCount()`, `getEnabledPluginCount()`

#### Evaluación
- ⚠️ Funcionalidad básica implementada
- ❌ NO tiene sistema de tipos (tool, mfa, theme, etc.)
- ❌ NO tiene instalación dinámica vía UI
- ❌ NO tiene segmentación por directorios
- ❌ NO tiene sistema de hooks/eventos

---

### 2.5 Admin/Tool/InstallAddon/InstallAddon.php

**Ubicación**: `modules/Admin/Tool/InstallAddon/InstallAddon.php`
**Líneas**: 314
**Responsabilidad**: Instalación de addons desde paquetes ZIP

#### Funcionalidades
1. **Validación**: `validatePackage()` verifica ZIP y busca `version.php`
2. **Instalación**: `installPackage()` extrae, registra y ejecuta `install.php`
3. **Desinstalación**: `uninstallPackage()` elimina archivos y ejecuta `uninstall.php`
4. **Dependencias**: `checkDependencies()` verifica plugins requeridos

#### Flujo de Instalación
```
1. Validar ZIP (tamaño máx 50MB)
2. Buscar version.php en el ZIP
3. Verificar que el plugin no existe
4. Extraer ZIP a /modules/
5. Leer version.php para obtener metadata
6. Registrar plugin en config_plugins
7. Ejecutar install.php si existe
```

#### Evaluación
- ✅ Instalación desde ZIP implementada
- ⚠️ Extrae a `/modules/` sin subdirectorios por tipo
- ❌ NO detecta tipo automáticamente
- ❌ NO tiene UI web de instalación
- ❌ NO soporta plugin.json (usa version.php)

---

### 2.6 Admin/Tool/Mfa/MfaManager.php

**Ubicación**: `modules/Admin/Tool/Mfa/MfaManager.php`
**Líneas**: 253
**Responsabilidad**: Sistema de autenticación multifactor

#### ✅ MFA YA IMPLEMENTADO

**Factores soportados:**
1. **TOTP** (Time-based One-Time Password): Apps como Google Authenticator
2. **Email**: Códigos enviados por email
3. **Backup Codes**: Códigos de respaldo

**Funcionalidades:**
1. **Registro de factores**: `registerFactor(MfaFactorInterface)`
2. **Validación**: `validateMfa()` verifica códigos
3. **Políticas**: `isMfaRequired()` verifica si MFA es obligatorio
4. **Configuración por usuario**: `getUserFactors()`
5. **Auditoría**: `logAudit()` registra todos los eventos MFA
6. **Revocación**: `revokeAllFactors()` desactiva MFA
7. **Grace period**: `isInGracePeriod()` período de gracia para nuevos usuarios

#### Evaluación
- ✅ Sistema MFA completo
- ✅ Multiple factores soportados
- ✅ Auditoría detallada
- ✅ Políticas por rol
- ⚠️ Debería ser un plugin independiente (está hardcoded en /modules/Admin/Tool/)

---

### 2.7 Theme/Iser/ThemeIser.php

**Ubicación**: `modules/Theme/Iser/ThemeIser.php`
**Líneas**: 376
**Responsabilidad**: Sistema de temas (theme ISER)

#### Funcionalidades
1. **Configuración**: `loadConfig()` carga desde archivos PHP o BD
2. **Personalización**: `updateThemeSettings()` guarda colores personalizados
3. **Layouts**: `renderLayout()` renderiza layouts (base, app, fullwidth, etc.)
4. **Assets**: `ThemeAssets` gestiona CSS/JS
5. **Navegación**: `ThemeNavigation` genera menús
6. **Preferencias de usuario**: `getUserThemePreferences()`, `saveUserThemePreferences()`

#### Configuración Soportada
```php
[
  'colors' => [
    'primary' => '#2c7be5',
    'secondary' => '#6e84a3',
    ...
  ]
]
```

#### Evaluación
- ✅ Theme system básico implementado
- ⚠️ Configuración limitada (solo colores)
- ❌ NO permite configurar tipografía
- ❌ NO permite configurar layouts desde admin
- ❌ NO permite themes plugins que sobrescriban el core
- ❌ NO tiene UI de configuración en admin panel

---

### 2.8 Controllers/AuthController.php

**Ubicación**: `modules/Controllers/AuthController.php`
**Líneas**: 290
**Responsabilidad**: Controlador de autenticación

#### Flujo de Login
```
1. showLogin() → Muestra formulario
2. processLogin():
   a. Validar credenciales
   b. Buscar usuario (por username o email)
   c. Verificar status (active, no eliminado)
   d. Verificar si está bloqueado
   e. Verificar contraseña con password_verify()
   f. Si falla: incrementar intentos, bloquear después de 5
   g. Si OK: resetear intentos, actualizar last_login
   h. Crear sesión con session_regenerate_id()
   i. Redirigir a /dashboard
3. logout() → Destruir sesión
```

#### Evaluación
- ✅ Autenticación segura
- ✅ Bloqueo de cuentas después de intentos fallidos
- ✅ Logging detallado de intentos
- ✅ Soporte login por username o email
- ⚠️ Falta integración con MFA (debería verificar MFA después de password)

---

## 3. ANÁLISIS DE DATABASE

### 3.1 Schema XML

**Ubicación**: `database/schema/schema.xml`
**Líneas**: 645
**Tablas**: 14

#### Listado de Tablas
1. `config` - Configuración del sistema (normalizada)
2. `users` - Usuarios del sistema
3. `password_reset_tokens` - Tokens de reset (separada, normalizada)
4. `login_attempts` - Intentos de login (auditoría)
5. `user_profiles` - Perfiles de usuario (1:1 con users)
6. `roles` - Roles del sistema
7. `permissions` - 35 permisos granulares
8. `user_roles` - Relación N:M usuarios-roles
9. `role_permissions` - Relación N:M roles-permisos
10. `sessions` - Sesiones activas
11. `jwt_tokens` - Tokens JWT (blacklist preparada)
12. `user_mfa` - Configuración MFA por usuario
13. `logs` - Logs del sistema
14. `audit_log` - Auditoría de acciones

#### Permisos Granulares (35 permisos en 9 módulos)

**Usuarios (7 permisos):**
- users.view, users.create, users.update, users.delete
- users.restore, users.assign_roles, users.view_profile

**Roles (5 permisos):**
- roles.view, roles.create, roles.update, roles.delete
- roles.assign_permissions

**Permisos (4 permisos):**
- permissions.view, permissions.create, permissions.update, permissions.delete

**Dashboard (3 permisos):**
- dashboard.view, dashboard.stats, dashboard.charts

**Settings (3 permisos):**
- settings.view, settings.update, settings.critical

**Logs (3 permisos):**
- logs.view, logs.delete, logs.export

**Audit (2 permisos):**
- audit.view, audit.export

**Reports (3 permisos):**
- reports.view, reports.generate, reports.export

**Sessions (2 permisos):**
- sessions.view, sessions.terminate

#### Análisis de Normalización

**Primera Forma Normal (1FN):**
- ✅ Todos los campos son atómicos
- ✅ No hay grupos repetitivos
- ✅ Cada columna tiene un solo valor

**Segunda Forma Normal (2FN):**
- ✅ Está en 1FN
- ✅ No hay dependencias parciales detectadas
- ✅ Atributos no-clave dependen completamente de la PK

**Tercera Forma Normal (3FN):**
- ✅ Está en 2FN
- ⚠️ **Posible violación en `users`**:
  - `last_login_at` y `last_login_ip` deberían estar en tabla separada `login_history`
  - `failed_login_attempts` y `locked_until` son estados derivados
- ⚠️ **Posible violación en `user_profiles`**:
  - `timezone` y `locale` podrían estar en `user_preferences` separada
- ✅ Tabla `config` está bien normalizada (key-value pairs)
- ✅ Tabla `password_reset_tokens` correctamente separada de `users`

#### Recomendaciones de Normalización
1. Crear tabla `login_history` para historial completo de logins
2. Crear tabla `user_preferences` para preferencias generales
3. Considerar tabla `account_security` para status de seguridad

---

## 4. ANÁLISIS DE RESOURCES

### 4.1 Vistas Mustache

**Total de templates**: 28 archivos .mustache

#### Layouts
- `/resources/views/layouts/base.mustache` - Layout base HTML
- `/resources/views/layouts/app.mustache` - Layout con sidebar

#### Componentes
- `/resources/views/components/navigation/sidebar.mustache`
- `/resources/views/components/navigation/topbar.mustache`
- `/resources/views/components/navigation/breadcrumbs.mustache`
- `/resources/views/components/navigation/user-menu.mustache`
- `/resources/views/components/header.mustache`
- `/resources/views/components/footer.mustache`
- `/resources/views/components/card.mustache`
- `/resources/views/components/stats.mustache`

#### Vistas de Admin
- `/resources/views/admin/index.mustache` - Dashboard admin
- `/resources/views/admin/users/index.mustache` - Lista de usuarios
- `/resources/views/admin/users/create.mustache` - Crear usuario
- `/resources/views/admin/users/edit.mustache` - Editar usuario
- `/resources/views/admin/roles/index.mustache` - Lista de roles
- `/resources/views/admin/roles/create.mustache` - Crear rol
- `/resources/views/admin/roles/edit.mustache` - Editar rol
- `/resources/views/admin/permissions/index.mustache` - Lista de permisos
- `/resources/views/admin/permissions/create.mustache` - Crear permiso
- `/resources/views/admin/permissions/edit.mustache` - Editar permiso
- `/resources/views/admin/settings.mustache` - Configuración
- `/resources/views/admin/reports.mustache` - Reportes
- `/resources/views/admin/security.mustache` - Seguridad

#### Vistas Públicas
- `/resources/views/auth/login.mustache` - Login
- `/resources/views/home/index.mustache` - Home pública
- `/resources/views/dashboard/index.mustache` - Dashboard usuario
- `/resources/views/profile/index.mustache` - Perfil usuario

#### Evaluación
- ✅ Estructura modular con componentes
- ✅ Separación layouts/components/pages
- ⚠️ **Strings hardcodeados**: Muchas vistas tienen strings en español sin usar `__()`
- ⚠️ Falta helper de traducción para Mustache

---

### 4.2 Archivos de Idioma

**Ubicación**: `/resources/lang/`

#### Idiomas Implementados
- **Español (es)**: Parcialmente implementado
  - `auth.php` - Autenticación
  - `common.php` - Strings comunes
  - `installer.php` - Instalador
- **Inglés (en)**: Parcialmente implementado
  - `auth.php`
  - `common.php`
  - `installer.php`
- **Portugués (pt)**: NO implementado

#### Evaluación
- ✅ Sistema de traducción funcional
- ⚠️ Solo 3 archivos por idioma (falta: admin.php, users.php, roles.php, permissions.php, etc.)
- ❌ Portugués no implementado
- ⚠️ Muchos strings aún hardcodeados en código

---

## 5. ANÁLISIS DE PUBLIC_HTML

### 5.1 index.php (Front Controller)

**Ubicación**: `public_html/index.php`
**Líneas**: 378
**Responsabilidad**: Punto de entrada único del sistema

#### Verificación de Instalación
```php
function checkInstallation(): bool {
    // 1. Verificar que existe .env
    // 2. Verificar que .env contiene INSTALLED=true
    // 3. Si no está instalado, redirigir a /install.php
}
```

#### Registro de Rutas
El archivo define todas las rutas del sistema:
- **Públicas**: `/`, `/login`, `/logout`
- **Protegidas**: `/dashboard`, `/profile`
- **Admin**: `/admin/*` (users, roles, permissions, settings, etc.)
- **API**: `/api/*`

#### Evaluación
- ✅ Front controller PSR-7 compliant
- ✅ Verificación de instalación robusta
- ✅ Todas las rutas centralizadas
- ⚠️ Rutas definidas en index.php (debería estar en archivos separados)
- ⚠️ Middleware aplicado manualmente (no integrado en router)

---

## 6. ANÁLISIS DE INSTALL

### 6.1 Instalador Web

**Ubicación**: `/install/index.php`
**Líneas**: 370
**Responsabilidad**: Instalador web paso a paso

#### Etapas del Instalador (5 etapas actuales)
1. **STAGE_REQUIREMENTS**: Verificación de requisitos PHP
2. **STAGE_DATABASE**: Configuración de base de datos
3. **STAGE_INSTALL_DB**: Instalación del schema.xml
4. **STAGE_ADMIN**: Creación de usuario administrador
5. **STAGE_FINISH**: Finalización y generación de .env

#### Evaluación
- ✅ Instalador funcional con UI ISER
- ✅ Verifica requisitos PHP
- ✅ Usa SchemaInstaller para instalar BD
- ⚠️ Solo 5 etapas (el prompt solicitaba 11 etapas)
- ❌ Falta: Configuración de logging, email, security policy, etc.
- ⚠️ UI moderna pero podría mejorar el feedback visual

---

## 7. ANÁLISIS DE TECNOLOGÍAS Y DEPENDENCIAS

### 7.1 composer.json

**PHP**: >= 8.1
**Extensiones requeridas**:
- ext-pdo, ext-json, ext-mbstring, ext-openssl, ext-curl

**Dependencias principales**:
1. `vlucas/phpdotenv` (^5.6) - Variables de entorno
2. `firebase/php-jwt` (^6.10) - JSON Web Tokens
3. `mustache/mustache` (^2.14) - Motor de plantillas
4. `monolog/monolog` (^3.5) - Sistema de logging
5. `phpmailer/phpmailer` (^6.9) - Envío de emails
6. `guzzlehttp/psr7` (^2.6) - PSR-7 HTTP Message

**Dependencias dev**:
- `phpunit/phpunit` (^10.5) - Testing

**Autoload PSR-4**:
```json
{
  "ISER\\": "modules/",
  "ISER\\Core\\": "core/"
}
```

#### Evaluación
- ✅ Dependencias mínimas y bien elegidas
- ✅ PSR-4 autoloading
- ✅ PHP 8.1+ aprovecha typed properties y enums
- ✅ Monolog para logging profesional

---

### 7.2 .env.example

**Líneas**: 169
**Secciones**: 15

#### Configuración Completa
1. **Environment**: APP_ENV, APP_DEBUG, APP_NAME, APP_URL, APP_TIMEZONE
2. **Database**: DB_CONNECTION, DB_HOST, DB_PORT, DB_DATABASE, etc.
3. **JWT**: JWT_SECRET, JWT_ALGORITHM, JWT_EXPIRATION
4. **Session**: SESSION_LIFETIME, SESSION_SECURE, SESSION_HTTPONLY
5. **Security**: RECAPTCHA_*, PASSWORD_* policies
6. **Email**: MAIL_DRIVER, MAIL_HOST, MAIL_PORT, etc.
7. **Logging**: LOG_CHANNEL, LOG_LEVEL, LOG_PATH
8. **Cache**: CACHE_DRIVER, CACHE_PATH, CACHE_TTL
9. **Paths**: PUBLIC_PATH, CORE_PATH, MODULES_PATH
10. **MFA**: MFA_ENABLED, MFA_TOTP_*, MFA_EMAIL_*, MFA_BACKUP_*
11. **Rate Limiting**: RATE_LIMIT_LOGIN_ATTEMPTS
12. **User Management**: USERS_PER_PAGE, UPLOAD_*, AVATAR_*
13. **Roles**: PERMISSION_CACHE_*, DEFAULT_USER_ROLE
14. **Development**: SHOW_SQL_QUERIES, ENABLE_QUERY_LOG

#### Evaluación
- ✅ Configuración exhaustiva y bien documentada
- ✅ Todas las configuraciones necesarias
- ✅ Comentarios claros
- ✅ Valores por defecto razonables

---

## 8. FLUJOS FUNCIONALES IDENTIFICADOS

### 8.1 Flujo de Autenticación

```
Usuario → /login (GET)
  ↓
AuthController::showLogin()
  ↓
Render login.mustache
  ↓
Usuario ingresa credenciales → /login (POST)
  ↓
AuthController::processLogin()
  ├─ Validar credenciales
  ├─ Buscar usuario (username o email)
  ├─ Verificar status (active, no deleted)
  ├─ Verificar si está bloqueado
  ├─ Verificar contraseña (password_verify)
  ├─ Si falla:
  │   ├─ Incrementar failed_login_attempts
  │   ├─ Bloquear después de 5 intentos
  │   └─ Registrar en login_attempts (success=0)
  └─ Si OK:
      ├─ Resetear failed_login_attempts
      ├─ Actualizar last_login_at y last_login_ip
      ├─ Registrar en login_attempts (success=1)
      ├─ Crear sesión (session_regenerate_id)
      └─ Redirigir a /dashboard
```

### 8.2 Flujo RBAC (Role-Based Access Control)

```
Usuario autenticado → Acceso a ruta protegida
  ↓
Middleware verifica autenticación
  ↓
PermissionManager::hasCapability(userId, capability, contextId)
  ├─ Obtener roles del usuario (getUserRoles)
  ├─ Para cada rol:
  │   └─ Obtener permission level (getRoleCapabilityPermission)
  ├─ Aplicar algoritmo de resolución:
  │   ├─ Si algún rol tiene CAP_PROHIBIT → DENY
  │   ├─ Si algún rol tiene CAP_ALLOW → ALLOW
  │   ├─ Si algún rol tiene CAP_PREVENT y ningún ALLOW → DENY
  │   └─ Por defecto CAP_INHERIT → DENY
  └─ Cache el resultado
  ↓
Si tiene permiso → Permitir acceso
Si no tiene permiso → Error 403
```

### 8.3 Flujo de Instalación

```
Usuario → /install.php
  ↓
¿Sistema instalado? (.env existe y INSTALLED=true)
  ├─ Sí → Mostrar "Ya instalado" con opción de reinstalar
  └─ No → Iniciar instalador
      ↓
Etapa 1: Verificación de Requisitos
  ├─ PHP >= 8.1
  ├─ Extensiones: PDO, JSON, mbstring, openssl, curl
  └─ Permisos de escritura: /var/, raíz para .env
      ↓
Etapa 2: Configuración de Base de Datos
  ├─ Driver (MySQL/PostgreSQL/SQLite)
  ├─ Host, Port, Database, Username, Password
  └─ Botón "Probar Conexión"
      ↓
Etapa 3: Instalación de Base de Datos
  ├─ Parsear schema.xml con SchemaInstaller
  ├─ Crear 14 tablas con índices y foreign keys
  ├─ Insertar datos iniciales (roles, permisos)
  └─ Asignar todos los permisos al rol admin
      ↓
Etapa 4: Crear Usuario Administrador
  ├─ Username, Email, Password
  ├─ First Name, Last Name
  └─ Insertar en tabla users con role admin
      ↓
Etapa 5: Finalización
  ├─ Generar archivo .env con todas las variables
  ├─ Establecer INSTALLED=true
  └─ Mostrar mensaje de éxito y botón "Ir al Dashboard"
```

### 8.4 Flujo de Gestión de Usuarios (Admin)

```
Admin → /admin/users
  ↓
UserManagementController::index()
  ├─ Verificar permiso users.view
  ├─ Obtener usuarios con paginación
  │   └─ UserManager::getUsers(limit, offset, filters)
  └─ Render admin/users/index.mustache
      ↓
Admin → Crear usuario (GET /admin/users/create)
  ↓
UserManagementController::create()
  ├─ Verificar permiso users.create
  ├─ Obtener roles disponibles
  └─ Render admin/users/create.mustache
      ↓
Admin → Submit formulario (POST /admin/users/store)
  ↓
UserManagementController::store()
  ├─ Validar datos
  ├─ UserManager::create(data)
  ├─ UserManager::syncRoles(userId, roleIds)
  └─ Redirigir a /admin/users con mensaje de éxito
```

---

## 9. IDENTIFICACIÓN DE VIOLACIONES SOLID

### 9.1 Single Responsibility Principle (SRP)

**Violaciones detectadas:**

❌ **Bootstrap.php**
- Maneja: inicialización, configuración, logging, database, router, módulos
- **Recomendación**: Separar en `ApplicationInitializer`, `ServiceProvider`

❌ **UserManager.php**
- Maneja: CRUD de users + roles + búsqueda + bulk operations
- **Recomendación**: Separar en `UserRepository`, `UserService`, `RoleAssignmentService`

✅ **SchemaInstaller.php** - Solo maneja instalación de schema XML
✅ **JWTSession.php** - Solo maneja sesiones JWT
✅ **Translator.php** - Solo maneja traducciones

### 9.2 Open/Closed Principle (OCP)

**Cumplimientos:**
✅ **Autoloader**: Permite agregar namespaces sin modificar código
✅ **Router**: Permite agregar rutas sin modificar la clase
✅ **Database**: Abstracción permite cambiar driver sin modificar queries

**Violaciones:**
❌ **AdminPlugins**: Hardcodea plugins core (auth_manual, user, roles)
- **Recomendación**: Usar configuración o atributos para marcar plugins core

### 9.3 Liskov Substitution Principle (LSP)

**Evaluación:**
- ⚠️ No hay muchas jerarquías de herencia
- ✅ Las interfaces (MfaFactorInterface) se implementan correctamente

### 9.4 Interface Segregation Principle (ISP)

**Violaciones:**
❌ **ModuleInterface**: No está siendo usado consistentemente
- **Recomendación**: Crear interfaces específicas: `Installable`, `Configurable`, `Routable`

### 9.5 Dependency Inversion Principle (DIP)

**Violaciones:**
❌ **Bootstrap**: Depende de clases concretas (PDOConnection, ConfigManager)
- **Recomendación**: Inyectar interfaces: `ConfigurationInterface`, `DatabaseConnectionInterface`

❌ **Controllers**: Instancian Managers directamente
- **Recomendación**: Usar Dependency Injection Container

---

## 10. RESUMEN DE HALLAZGOS Y RECOMENDACIONES

### 10.1 Funcionalidades YA Implementadas ✅

1. **Sistema de internacionalización (i18n)** completo con `Translator.php` y función `__()`
2. **XML Parser** robusto en `core/Utils/XMLParser.php`
3. **SchemaInstaller** que parsea `schema.xml` para instalación de BD
4. **Sistema RBAC** con 35 permisos granulares en 9 módulos
5. **MFA** con TOTP, Email y Backup Codes
6. **Theme system** base (ISER) con layouts y assets
7. **Instalador web** por etapas con UI moderna
8. **Gestión de usuarios** con soft delete y búsqueda
9. **Sistema de plugins** básico (enable/disable)
10. **Autenticación JWT** con refresh tokens

### 10.2 Áreas que Requieren Refactorización ⚠️

1. **Sistema de Plugins**
   - ❌ NO detecta tipo automáticamente (tool, mfa, theme, report)
   - ❌ NO segmenta por directorios (`/plugins/tools/`, `/plugins/mfa/`, etc.)
   - ❌ NO tiene instalación vía UI web
   - ❌ NO usa `plugin.json` estándar
   - ❌ NO tiene sistema de hooks/eventos

2. **Theme Configurable**
   - ⚠️ Solo permite configurar colores (limitado)
   - ❌ NO permite configurar tipografía desde admin
   - ❌ NO permite configurar layouts desde admin
   - ❌ Themes plugins no pueden sobrescribir el core

3. **Internacionalización**
   - ⚠️ Muchos strings hardcodeados en vistas Mustache
   - ❌ Falta helper de traducción para Mustache (`{{#__}}`)
   - ❌ Solo 3 archivos por idioma (falta admin.php, users.php, etc.)
   - ❌ Portugués no implementado

4. **Normalización de Base de Datos**
   - ⚠️ Tabla `users` tiene campos que deberían estar en `login_history`
   - ⚠️ `user_profiles` podría separar preferences
   - ✅ El resto cumple 3FN correctamente

5. **Instalador Web**
   - ⚠️ Solo 5 etapas (prompt solicitaba 11)
   - ❌ Falta configuración de logging avanzada
   - ❌ Falta configuración de email
   - ❌ Falta configuración de security policy
   - ❌ Falta configuración adicional (caché, storage)

6. **Middleware**
   - ⚠️ No está integrado en el Router
   - ⚠️ Se aplica manualmente en controllers
   - ⚠️ Debería ser parte del routing system

### 10.3 Violaciones SOLID Detectadas

1. **SRP**: `Bootstrap.php`, `UserManager.php` hacen demasiadas cosas
2. **OCP**: `AdminPlugins.php` hardcodea plugins core
3. **DIP**: Controllers dependen de implementaciones concretas
4. **ISP**: `ModuleInterface` no se usa consistentemente

### 10.4 Arquitectura Actual vs Objetivo

| Aspecto | Estado Actual | Objetivo Refactorización |
|---------|---------------|--------------------------|
| **Plugins** | Sistema básico, sin tipos | Sistema dinámico con detección automática |
| **I18n** | Implementado, incompleto | Completo con helper Mustache |
| **Theme** | Base implementado | Totalmente configurable desde admin |
| **BD** | Casi 3FN, algunas violaciones | Estrictamente 3FN |
| **Instalador** | 5 etapas, funcional | 11 etapas, configuración exhaustiva |
| **MFA** | Implementado como módulo | Debe ser plugin independiente |

---

## CONCLUSIONES

El proyecto **NexoSupport** tiene una base arquitectónica sólida con:
- ✅ PSR-4 y PSR-7 compliance
- ✅ Sistema RBAC robusto
- ✅ Internacionalización funcional
- ✅ XML Parser e instalador de BD
- ✅ MFA implementado
- ✅ Theme system base

**Sin embargo, requiere refactorización en:**
1. Sistema de plugins dinámico con segmentación por tipos
2. Theme completamente configurable desde admin
3. Completar internacionalización (helper Mustache, más archivos de idioma)
4. Normalización estricta a 3FN
5. Instalador web con 11 etapas completas
6. Integración de middleware en routing system
7. Mejora de adherencia a principios SOLID

El código está bien estructurado y es mantenible, pero necesita evolucionar hacia una arquitectura más modular, extensible y configurable.

---

**Siguiente paso**: Revisar ARCHITECTURE.md, DATABASE_ANALYSIS.md y FLOWS.md para diseñar las especificaciones de refactorización.
