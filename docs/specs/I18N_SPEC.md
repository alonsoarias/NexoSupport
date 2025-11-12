# ESPECIFICACIÓN: INTERNACIONALIZACIÓN COMPLETA

**Fecha**: 2025-11-12
**Versión**: 1.0
**Proyecto**: NexoSupport - Refactorización Integral
**Fase**: FASE 3 - Internacionalización Completa

---

## 1. OBJETIVO DE LA FASE

Completar el sistema de internacionalización (i18n) existente para que:

1. **TODOS los strings** del sistema estén traducibles (sin hardcoding)
2. **Helper de Mustache** para traducir strings en vistas
3. **Archivos de idioma completos** para todos los módulos
4. **Idiomas soportados**: Español (es), Inglés (en), Portugués (pt)
5. **API i18n** para JavaScript
6. **Plugins** puedan incluir sus propias traducciones

---

## 2. ESTADO ACTUAL DEL SISTEMA I18N

### 2.1 Funcionalidades YA Implementadas ✅

**Sistema base funcional** (`core/I18n/Translator.php`):
- ✅ Singleton pattern
- ✅ Carga archivos PHP de `/resources/lang/{locale}/*.php`
- ✅ Helper function `__(key, replacements, locale)`
- ✅ Pluralización con `trans_choice(key, count)`
- ✅ Fallback automático a locale por defecto
- ✅ Reemplazo de variables: `:variable`, `:VARIABLE`, `:Variable`

**Archivos de idioma parciales**:
- ✅ `/resources/lang/es/auth.php` - Autenticación
- ✅ `/resources/lang/es/common.php` - Strings comunes
- ✅ `/resources/lang/es/installer.php` - Instalador
- ✅ `/resources/lang/en/` - Mismo conjunto en inglés

### 2.2 Problemas Detectados ⚠️

1. ❌ **Strings hardcodeados** en vistas Mustache
   - Muchos templates tienen strings directamente en español
   - Ejemplo: `<h1>Gestión de Usuarios</h1>` en lugar de `<h1>{{#__}}users.management{{/__}}</h1>`

2. ❌ **Falta helper de traducción para Mustache**
   - Actualmente `__()`solo funciona en PHP
   - No hay helper `{{#__}}string_key{{/__}}` en Mustache

3. ❌ **Archivos de idioma incompletos**
   - Solo 3 archivos por idioma (auth, common, installer)
   - Faltan: admin.php, users.php, roles.php, permissions.php, settings.php, reports.php, logs.php, audit.php

4. ❌ **Portugués NO implementado**
   - No existe directorio `/resources/lang/pt/`

5. ❌ **Sin API para JavaScript**
   - No hay endpoint `/api/i18n/{lang}` para cargar traducciones en JS
   - Frontend no puede traducir dinámicamente

---

## 3. ARQUITECTURA DE INTERNACIONALIZACIÓN COMPLETA

### 3.1 Componentes del Sistema

#### 3.1.1 Translator (Existente - Mejorar)
**Ubicación**: `core/I18n/Translator.php`

**Funcionalidades actuales**:
- Cargar archivos de idioma
- Traducir strings con reemplazo de variables
- Pluralización básica
- Fallback a locale por defecto

**Mejoras requeridas**:
- Método `registerHelper()` para registrar helpers de Mustache
- Método `loadPluginTranslations(pluginSlug)` para cargar traducciones de plugins
- Cache de traducciones cargadas para performance
- Método `getAll(namespace)` para obtener todas las traducciones de un namespace (para JS)

#### 3.1.2 MustacheTranslationHelper (NUEVO)
**Responsabilidad**: Helper personalizado de Mustache para traducciones

**Funcionalidades**:
- Registrarse como helper `__` en el motor Mustache
- Recibir string key como parámetro
- Traducir usando `Translator::translate()`
- Soportar variables en la sintaxis Mustache

**Sintaxis en templates**:
```
{{#__}}auth.welcome{{/__}}
{{#__}}users.created_count{{/__}} con variable: {{user_count}}
```

#### 3.1.3 LocaleDetector (NUEVO)
**Responsabilidad**: Detectar idioma del usuario

**Fuentes de detección (en orden de prioridad)**:
1. Parámetro GET: `?lang=es`
2. Sesión: `$_SESSION['locale']`
3. Preferencia de usuario en BD: `user_preferences.locale`
4. Header HTTP `Accept-Language`
5. Configuración por defecto del sistema: `DEFAULT_LOCALE` (.env)

**Flujo conceptual**:
- Al iniciar request, detectar locale
- Establecer en `Translator::setLocale()`
- Guardar en sesión para persistencia
- Si usuario autenticado y cambia idioma, guardar preferencia en BD

#### 3.1.4 I18nApiController (NUEVO)
**Responsabilidad**: API para cargar traducciones en JavaScript

**Endpoint**: `GET /api/i18n/{locale}`
**Ejemplo**: `/api/i18n/es`

**Respuesta JSON**:
```json
{
  "locale": "es",
  "translations": {
    "auth": {
      "welcome": "Bienvenido, :name",
      "login": "Iniciar Sesión",
      "logout": "Cerrar Sesión"
    },
    "users": {
      "management": "Gestión de Usuarios",
      "create": "Crear Usuario",
      "edit": "Editar Usuario",
      "delete": "Eliminar Usuario"
    }
  }
}
```

**Funcionalidades**:
- Cargar todas las traducciones de un locale
- Cache con TTL de 1 hora (las traducciones no cambian frecuentemente)
- Soportar namespace específico: `/api/i18n/es/users` (solo namespace users)
- Minificar JSON para reducir tamaño

---

## 4. ARCHIVOS DE IDIOMA COMPLETOS

### 4.1 Estructura de Directorios

```
/resources/lang/
├── es/                              # Español (España/LATAM)
│   ├── auth.php                    # ✅ YA EXISTE
│   ├── common.php                  # ✅ YA EXISTE
│   ├── installer.php               # ✅ YA EXISTE
│   ├── admin.php                   # ❌ CREAR
│   ├── users.php                   # ❌ CREAR
│   ├── roles.php                   # ❌ CREAR
│   ├── permissions.php             # ❌ CREAR
│   ├── settings.php                # ❌ CREAR
│   ├── reports.php                 # ❌ CREAR
│   ├── logs.php                    # ❌ CREAR
│   ├── audit.php                   # ❌ CREAR
│   ├── dashboard.php               # ❌ CREAR
│   ├── profile.php                 # ❌ CREAR
│   ├── errors.php                  # ❌ CREAR (mensajes de error)
│   └── validation.php              # ❌ CREAR (mensajes de validación)
│
├── en/                              # Inglés
│   ├── auth.php                    # ✅ YA EXISTE
│   ├── common.php                  # ✅ YA EXISTE
│   ├── installer.php               # ✅ YA EXISTE
│   └── (... mismos archivos que es/)
│
└── pt/                              # Portugués ❌ CREAR TODO
    ├── auth.php
    ├── common.php
    ├── installer.php
    └── (... todos los archivos)
```

### 4.2 Contenido de Archivos de Idioma

**Estructura conceptual de cada archivo** (retorna array asociativo):

#### admin.php
**Propósito**: Strings del panel de administración

**Namespace conceptual**:
- Títulos de página (dashboard, configuración, reportes, seguridad)
- Labels de menú (usuarios, roles, permisos, plugins, logs)
- Acciones (guardar, cancelar, aplicar cambios)
- Mensajes de éxito/error
- Confirmaciones (¿Estás seguro?)

#### users.php
**Propósito**: Strings de gestión de usuarios

**Namespace conceptual**:
- Títulos (lista de usuarios, crear usuario, editar usuario)
- Labels de campos (username, email, password, first name, last name, status)
- Acciones (crear, editar, eliminar, restaurar, suspender, activar)
- Mensajes (usuario creado, usuario actualizado, usuario eliminado)
- Estados (activo, inactivo, suspendido, eliminado)
- Filtros (todos, activos, inactivos, eliminados)

#### roles.php
**Propósito**: Strings de gestión de roles

**Namespace conceptual**:
- Títulos (lista de roles, crear rol, editar rol)
- Labels (nombre, shortname, descripción, permisos)
- Acciones (crear, editar, eliminar, asignar permisos, clonar)
- Mensajes (rol creado, rol actualizado, rol eliminado)
- Advertencias (rol del sistema no puede eliminarse)

#### permissions.php
**Propósito**: Strings de gestión de permisos

**Namespace conceptual**:
- Títulos (lista de permisos, vista por módulos)
- Módulos (users, roles, permissions, dashboard, settings, logs, audit, reports, sessions)
- Nombres de permisos (view, create, update, delete, restore, export, etc.)
- Descripciones de permisos
- Niveles (inherit, allow, prevent, prohibit)

#### settings.php
**Propósito**: Strings de configuración del sistema

**Namespace conceptual**:
- Grupos de configuración (general, email, seguridad, cache, logs)
- Labels de configuración (app name, app url, timezone, debug mode)
- Mensajes (configuración guardada, configuración restaurada)

#### dashboard.php
**Propósito**: Strings del dashboard

**Namespace conceptual**:
- Widgets (usuarios totales, roles activos, plugins instalados, logins hoy)
- Gráficos (actividad de usuarios, intentos de login)
- Acciones rápidas (crear usuario, ver logs, reportes)

#### profile.php
**Propósito**: Strings del perfil de usuario

**Namespace conceptual**:
- Títulos (mi perfil, editar perfil, cambiar contraseña)
- Labels (información personal, preferencias, seguridad)
- Acciones (actualizar perfil, cambiar contraseña, configurar MFA)
- Mensajes (perfil actualizado, contraseña cambiada)

#### errors.php
**Propósito**: Mensajes de error del sistema

**Namespace conceptual**:
- Errores HTTP (404, 403, 500, 503)
- Errores de autenticación (credenciales inválidas, cuenta bloqueada)
- Errores de autorización (sin permisos)
- Errores de base de datos (conexión fallida, query error)
- Errores de archivos (archivo no encontrado, sin permisos de escritura)

#### validation.php
**Propósito**: Mensajes de validación de formularios

**Namespace conceptual**:
- Campos requeridos (:field es requerido)
- Formatos (email inválido, URL inválida)
- Longitudes (mínimo :min caracteres, máximo :max caracteres)
- Unicidad (ya existe un usuario con este email)
- Coincidencias (las contraseñas no coinciden)
- Tipos (debe ser un número, debe ser una fecha válida)

### 4.3 Convenciones de Nomenclatura

**Claves de traducción** (keys):
- Usar snake_case: `user_created`, `password_changed`
- Prefijo con contexto: `users.list.title`, `admin.dashboard.welcome`
- Acciones en infinitivo: `create`, `edit`, `delete`, `restore`
- Estados como adjetivos: `active`, `inactive`, `deleted`

**Variables en strings**:
- Usar dos puntos antes: `:name`, `:count`, `:email`
- Mayúsculas para uppercase: `:NAME`
- Ucfirst para capitalizar: `:Name`

**Pluralización**:
- Key singular: `user`
- Key plural: `users`
- Usar `trans_choice()` para formas singulares/plurales
- Formato: `{0} No hay usuarios|{1} 1 usuario|[2,*] :count usuarios`

---

## 5. HELPER DE MUSTACHE PARA TRADUCCIÓN

### 5.1 Registro del Helper

**En `MustacheRenderer::__construct()`**:
- Crear instancia de `MustacheTranslationHelper`
- Registrar helper en Mustache Engine con nombre `__`
- Pasar instancia de `Translator` al helper

### 5.2 Uso en Templates Mustache

**Sintaxis básica**:
```mustache
{{#__}}auth.welcome{{/__}}
```

**Con variables de contexto**:
```mustache
{{#__}}users.created_count{{/__}}: {{count}}
```
Donde `users.created_count` es `":count usuarios creados"`

**En atributos HTML**:
```mustache
<input type="text" placeholder="{{#__}}users.search_placeholder{{/__}}">
```

**En links y botones**:
```mustache
<a href="/users/create">{{#__}}users.create_button{{/__}}</a>
```

**Con pluralización** (requiere helper especializado):
```mustache
{{#__choice}}users.count_label|{{user_count}}{{/__choice}}
```

### 5.3 Migración de Strings Hardcodeados

**Proceso conceptual**:

1. **Identificar strings hardcodeados**:
   - Buscar en todos los templates `.mustache`
   - Identificar strings en español/inglés
   - Marcar para extracción

2. **Extraer a archivos de idioma**:
   - Crear key descriptiva: `users.management_title`
   - Agregar a archivo correspondiente (ej: `users.php`)
   - Traducir a todos los idiomas soportados

3. **Reemplazar en template**:
   - `<h1>Gestión de Usuarios</h1>` → `<h1>{{#__}}users.management_title{{/__}}</h1>`

4. **Verificar rendering**:
   - Probar en español: debe mostrar "Gestión de Usuarios"
   - Cambiar a inglés: debe mostrar "User Management"
   - Cambiar a portugués: debe mostrar "Gestão de Usuários"

---

## 6. API I18N PARA JAVASCRIPT

### 6.1 Endpoint de API

**Ruta**: `GET /api/i18n/{locale}`
**Ejemplo**: `/api/i18n/es`

**Response Headers**:
- `Content-Type: application/json`
- `Cache-Control: public, max-age=3600` (cache de 1 hora)
- `ETag: "hash_del_contenido"` (para validación de cache)

**Response Body** (conceptual):
```json
{
  "locale": "es",
  "fallback_locale": "en",
  "translations": {
    "auth": { ... },
    "users": { ... },
    "roles": { ... },
    ...
  }
}
```

**Con namespace específico**: `GET /api/i18n/es/users`
```json
{
  "locale": "es",
  "namespace": "users",
  "translations": {
    "management_title": "Gestión de Usuarios",
    "create_button": "Crear Usuario",
    ...
  }
}
```

### 6.2 Cliente JavaScript

**Librería i18n.js** (conceptual):

**Funcionalidades**:
- Cargar traducciones desde API al iniciar la app
- Cache en localStorage con TTL
- Función `__(key, variables)` similar a PHP
- Función `trans_choice(key, count)` para pluralización
- Detectar cambio de idioma y recargar traducciones
- Actualizar UI sin reload de página

**Uso en JavaScript**:
```javascript
// Cargar traducciones
await i18n.load('es');

// Traducir string
const title = __('users.management_title');
// → "Gestión de Usuarios"

// Con variables
const message = __('users.created_message', { name: 'Juan' });
// → "Usuario Juan creado correctamente"

// Pluralización
const count = trans_choice('users.count_label', 5);
// → "5 usuarios"

// Cambiar idioma dinámicamente
await i18n.setLocale('en');
// Re-renderizar UI con nuevas traducciones
```

### 6.3 Integración con Frontend

**Al cargar la página**:
1. Detectar locale del usuario (desde HTML `lang` attribute)
2. Cargar traducciones desde `/api/i18n/{locale}`
3. Inicializar librería i18n.js
4. Renderizar UI con traducciones

**Selector de idioma**:
- Dropdown en topbar con banderas
- Opciones: Español, English, Português
- Al seleccionar: hacer POST a `/api/user/locale` para guardar preferencia
- Recargar traducciones y actualizar UI sin reload completo

---

## 7. SOPORTE DE IDIOMAS

### 7.1 Idiomas a Implementar

#### Español (es) - ✅ PARCIAL
**Estado**: Implementado parcialmente (3 archivos)
**Acción**: Completar archivos faltantes

**Variantes**:
- `es` - Español (neutro)
- `es-ES` - Español de España (opcional)
- `es-419` - Español de Latinoamérica (opcional)

#### Inglés (en) - ✅ PARCIAL
**Estado**: Implementado parcialmente (3 archivos)
**Acción**: Completar archivos faltantes

**Variantes**:
- `en` - Inglés (neutro)
- `en-US` - Inglés estadounidense (opcional)
- `en-GB` - Inglés británico (opcional)

#### Portugués (pt) - ❌ NO IMPLEMENTADO
**Estado**: No existe
**Acción**: Crear directorio completo `/resources/lang/pt/`

**Variantes**:
- `pt` - Portugués (neutro)
- `pt-BR` - Portugués de Brasil
- `pt-PT` - Portugués de Portugal

### 7.2 Fallback de Locales

**Estrategia de fallback**:

1. Intentar locale específico (ej: `es-MX`)
2. Si no existe, intentar locale genérico (`es`)
3. Si no existe, usar fallback configurado (`en`)
4. Si no existe, mostrar key sin traducir (modo debug)

**Ejemplo**:
```
Usuario solicita: pt-BR
  ↓
¿Existe /resources/lang/pt-BR/? → NO
  ↓
¿Existe /resources/lang/pt/? → SÍ
  ↓
Usar /resources/lang/pt/
  ↓
¿Falta algún string? → SÍ
  ↓
Fallback a /resources/lang/en/ para ese string específico
```

---

## 8. INTERNACIONALIZACIÓN DE PLUGINS

### 8.1 Estructura de Traducciones en Plugins

Cada plugin puede incluir sus propias traducciones:

```
/modules/plugins/{type}/{plugin_slug}/
└── lang/
    ├── es/
    │   └── {plugin_slug}.php
    ├── en/
    │   └── {plugin_slug}.php
    └── pt/
        └── {plugin_slug}.php
```

**Ejemplo para plugin MFA**:
```
/modules/plugins/tools/mfa-authenticator/
└── lang/
    ├── es/
    │   └── mfa-authenticator.php
    │       return [
    │           'setup_title' => 'Configurar Autenticación de Dos Factores',
    │           'qr_code_scan' => 'Escanea este código QR',
    │           ...
    │       ]
    ├── en/
    │   └── mfa-authenticator.php
    │       return [
    │           'setup_title' => 'Setup Two-Factor Authentication',
    │           'qr_code_scan' => 'Scan this QR code',
    │           ...
    │       ]
    └── pt/
        └── mfa-authenticator.php
            return [
                'setup_title' => 'Configurar Autenticação de Dois Fatores',
                'qr_code_scan' => 'Digitalize este código QR',
                ...
            ]
```

### 8.2 Carga de Traducciones de Plugins

**Al activar plugin**:
1. PluginLoader detecta directorio `lang/` en el plugin
2. Registrar namespace del plugin: `{plugin_slug}.*`
3. Cargar archivos de idioma del plugin en el locale actual
4. Agregar al pool de traducciones del Translator

**Uso en vistas del plugin**:
```mustache
{{#__}}mfa-authenticator.setup_title{{/__}}
```

**Uso en código PHP del plugin**:
```php
__('mfa-authenticator.setup_title')
```

### 8.3 Namespace de Plugins

**Convención**:
- Namespace del plugin: `{plugin_slug}.*`
- Keys del plugin: `{plugin_slug}.{context}.{string}`
- Ejemplo: `mfa-authenticator.setup.title`

**Evita conflictos** entre:
- Traducciones del core
- Traducciones de diferentes plugins
- Permite a los plugins sobrescribir strings del core si es necesario (uso avanzado)

---

## 9. FORMATEO DE DATOS SEGÚN LOCALE

### 9.1 Fechas y Horas

**Formateo según locale**:
- `es`: `31/12/2025 23:59`
- `en-US`: `12/31/2025 11:59 PM`
- `en-GB`: `31/12/2025 23:59`
- `pt-BR`: `31/12/2025 23:59`

**Helper de Mustache** para fechas:
```mustache
{{#date_format}}{{timestamp}}|d/m/Y H:i{{/date_format}}
```

**En PHP**:
```php
DateHelper::format($timestamp, $format, $locale);
```

### 9.2 Números

**Formateo según locale**:
- `es`: `1.234,56` (punto para miles, coma para decimales)
- `en`: `1,234.56` (coma para miles, punto para decimales)
- `pt`: `1.234,56` (igual que español)

**Helper de Mustache** para números:
```mustache
{{#number_format}}{{value}}|2{{/number_format}}
```
(2 = decimales)

### 9.3 Monedas

**Formateo según locale y moneda**:
- `es` + `EUR`: `1.234,56 €`
- `en-US` + `USD`: `$1,234.56`
- `pt-BR` + `BRL`: `R$ 1.234,56`

**Helper de Mustache** para monedas:
```mustache
{{#currency_format}}{{amount}}|USD{{/currency_format}}
```

---

## 10. DETECCIÓN Y CAMBIO DE IDIOMA

### 10.1 Selector de Idioma en UI

**Ubicación**: Topbar (header), esquina superior derecha

**Componente**:
- Dropdown con banderas de países
- Opciones:
  - 🇪🇸 Español
  - 🇬🇧 English
  - 🇧🇷 Português
- Muestra idioma actual seleccionado
- Al hacer click, lista de idiomas disponibles

**Comportamiento**:
- Click en idioma → Request a `/api/user/locale`
- Guardar preferencia en BD (si usuario autenticado) o sesión (si no)
- Recargar traducciones desde `/api/i18n/{new_locale}`
- Actualizar UI sin reload completo de página
- Actualizar atributo `lang` del HTML

### 10.2 Persistencia de Preferencia

**Para usuarios autenticados**:
- Guardar en tabla `user_preferences`
- Key: `locale`, Value: `es`/`en`/`pt`
- Al hacer login, cargar preferencia y establecer locale

**Para usuarios no autenticados**:
- Guardar en `$_SESSION['locale']`
- Guardar en cookie `locale` (30 días)
- Al visitar nuevamente, leer de cookie

**Para visitantes nuevos**:
- Detectar desde header `Accept-Language`
- Establecer locale automáticamente
- Permitir cambio manual

---

## 11. TESTING DE INTERNACIONALIZACIÓN

### 11.1 Tests de Cobertura

**Verificar que**:
1. ✅ Todos los templates Mustache usan `{{#__}}` (ningún string hardcodeado)
2. ✅ Todas las keys usadas en templates existen en archivos de idioma
3. ✅ Todos los idiomas tienen el mismo conjunto de keys
4. ✅ No hay keys huérfanas (definidas pero no usadas)
5. ✅ Variables en strings coinciden con las pasadas en código

**Herramienta conceptual**: `I18nValidator`
- Escanear todos los templates
- Extraer keys usadas
- Comparar con keys definidas en archivos de idioma
- Reportar keys faltantes o sobrantes

### 11.2 Tests Funcionales

**Casos de prueba**:
1. Cambiar idioma a español → Toda la UI en español
2. Cambiar idioma a inglés → Toda la UI en inglés
3. Cambiar idioma a portugués → Toda la UI en portugués
4. Usuario con preferencia guardada → Login y ver UI en su idioma preferido
5. String con variables → Variables reemplazadas correctamente
6. Pluralización → Forma singular o plural según count
7. Fechas y números → Formateados según locale

---

## 12. CRITERIOS DE ÉXITO DE LA FASE 3

### 12.1 Funcionalidades Implementadas

✅ El sistema debe cumplir:

1. **Helper de Mustache funcional**
   - Se puede usar `{{#__}}key{{/__}}` en todos los templates
   - Las traducciones se renderizan correctamente
   - Soporta variables en el contexto de Mustache

2. **Archivos de idioma completos**
   - Todos los módulos tienen archivo de idioma (14 archivos por idioma)
   - Español, Inglés y Portugués completos
   - Sin strings hardcodeados en el código

3. **API i18n funcional**
   - `/api/i18n/{locale}` retorna todas las traducciones
   - Cache HTTP configurado (1 hora)
   - JavaScript puede cargar y usar traducciones

4. **Selector de idioma en UI**
   - Dropdown en topbar con 3 opciones
   - Cambio de idioma sin reload
   - Preferencia guardada en BD o sesión

5. **Plugins con traducciones**
   - Plugins pueden incluir directorio `lang/`
   - Traducciones de plugins se cargan automáticamente
   - Se usan con namespace: `{plugin_slug}.key`

6. **Formateo de datos**
   - Fechas formateadas según locale
   - Números formateados según locale
   - Monedas formateadas según locale y moneda

### 12.2 Tests de Validación

**Validar que**:
- ✅ Cambiar idioma a español muestra "Gestión de Usuarios"
- ✅ Cambiar idioma a inglés muestra "User Management"
- ✅ Cambiar idioma a portugués muestra "Gestão de Usuários"
- ✅ Usuario guarda preferencia de idioma y persiste después del logout
- ✅ String con variable se renderiza: "Bienvenido, Juan"
- ✅ Fecha se formatea: `31/12/2025` en es, `12/31/2025` en en-US
- ✅ Número se formatea: `1.234,56` en es, `1,234.56` en en
- ✅ Plugin MFA muestra traducciones en los 3 idiomas

---

## 13. RESTRICCIONES Y CONSIDERACIONES

### 13.1 Trabajo sobre Funcionalidades Existentes

**Principio fundamental**: Completar el sistema i18n existente, NO reemplazarlo

Esta fase:
- ✅ Usa el `Translator.php` existente (solo agrega métodos)
- ✅ Usa los archivos de idioma existentes (solo completa los faltantes)
- ✅ Mantiene la función `__()` existente
- ✅ Agrega helper Mustache compatible con el sistema actual
- ✅ NO cambia la arquitectura base de i18n

### 13.2 Restricciones de Implementación

**Según el prompt**:
- ✅ PERMITIDO: Describir arquitectura, patrones, flujos y requisitos
- ❌ PROHIBIDO: Proporcionar código PHP/SQL/JS específico
- ❌ PROHIBIDO: Proponer funcionalidades nuevas no solicitadas

**Este documento cumple**:
- ✅ Describe arquitectura y diseño del sistema i18n completo
- ✅ Define estructura de archivos de idioma conceptual
- ✅ Especifica flujos de traducción y formateo
- ✅ NO incluye código de implementación

---

**FIN DE ESPECIFICACIÓN FASE 3: INTERNACIONALIZACIÓN COMPLETA**

Este documento define el sistema i18n completo sin código de implementación, siguiendo estrictamente las restricciones del prompt y el objetivo de completar (no reemplazar) el sistema existente.
