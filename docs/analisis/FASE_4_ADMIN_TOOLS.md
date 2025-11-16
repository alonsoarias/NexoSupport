# FASE 4: Herramientas Administrativas (admin/tool/*)

**Fecha:** 2024-11-16
**Responsable:** Claude (Frankenstyle Refactoring)
**Estado:** ✅ COMPLETADO

---

## 📋 Resumen Ejecutivo

La Fase 4 implementa herramientas administrativas siguiendo la arquitectura Frankenstyle. Estas herramientas proporcionan funcionalidad extendida para administración del sistema, gestión de usuarios masiva, visualización de logs, y gestión de plugins.

### Objetivos Cumplidos

1. ✅ Implementar tool_uploaduser para carga masiva de usuarios vía CSV
2. ✅ Implementar tool_logviewer para visualización de logs del sistema
3. ✅ Implementar tool_pluginmanager para gestión de plugins instalados
4. ✅ Crear estructura Frankenstyle para tool_mfa (Multi-Factor Authentication)
5. ✅ Crear estructura Frankenstyle para tool_installaddon
6. ✅ Crear estructura Frankenstyle para tool_dataprivacy

---

## 🏗️ Arquitectura Implementada

### Estructura de Directorios

```
admin/tool/
├── uploaduser/              ✅ Carga masiva de usuarios
│   ├── index.php           # Interfaz web completa
│   ├── version.php         # Metadata
│   ├── lib.php             # Funciones de biblioteca
│   └── classes/
│       └── uploader.php    # Procesador CSV
│
├── logviewer/              ✅ Visualizador de logs
│   ├── index.php           # Interfaz web completa
│   ├── version.php         # Metadata
│   ├── lib.php             # Funciones de biblioteca
│   └── classes/
│       └── log_reader.php  # Lector de logs
│
├── pluginmanager/          ✅ Gestor de plugins
│   ├── index.php           # Interfaz web completa
│   ├── version.php         # Metadata
│   ├── lib.php             # Funciones de biblioteca
│   └── classes/
│       └── plugin_manager.php  # Descubridor de plugins
│
├── mfa/                    ✅ Multi-Factor Auth (estructura básica)
│   ├── version.php         # Metadata
│   └── lib.php             # Funciones de biblioteca
│
├── installaddon/           ✅ Instalador de plugins (estructura básica)
│   ├── version.php         # Metadata
│   └── lib.php             # Funciones de biblioteca
│
└── dataprivacy/            ✅ Privacidad de datos (estructura básica)
    ├── version.php         # Metadata
    └── lib.php             # Funciones de biblioteca
```

---

## 🔧 Herramientas Implementadas

### 1. tool_uploaduser - Carga Masiva de Usuarios

**Propósito:** Permitir la creación de múltiples usuarios simultáneamente mediante un archivo CSV.

#### Archivos Creados

**index.php (300+ líneas)**
- Interfaz web completa con HTML/CSS embebido
- Formulario de carga de archivos CSV
- Validación de archivos (solo .csv, .txt)
- Tabla de resultados detallados por usuario
- Instrucciones de formato CSV
- Ejemplos de uso

**version.php**
```php
$plugin->component = 'tool_uploaduser';
$plugin->version = 2024111601;
$plugin->maturity = MATURITY_STABLE;
$plugin->release = '1.0.0';
```

**lib.php**
Funciones de biblioteca:
- `tool_uploaduser_get_capabilities()` - 2 capabilities definidas
- `tool_uploaduser_get_title()` - Título de la herramienta
- `tool_uploaduser_get_description()` - Descripción
- `tool_uploaduser_get_required_columns()` - Columnas requeridas CSV
- `tool_uploaduser_get_optional_columns()` - Columnas opcionales CSV
- `tool_uploaduser_validate_user_data()` - Validación de datos
- `tool_uploaduser_format_results()` - Formato de resultados
- `tool_uploaduser_get_menu_items()` - Items de menú admin

**classes/uploader.php**
Clase principal de procesamiento:
```php
namespace tool_uploaduser;

class uploader {
    // Procesamiento de archivos CSV
    public function process_file(string $filepath, string $filename): array

    // Validación de usuarios individuales
    private function process_user(array $userData, int $rowNumber): array

    // Validación de headers CSV
    private function validate_headers(array $headers): void

    // Validación de datos de usuario
    private function validate_user_data(array $data): array

    // Verificación de extensiones
    private function is_valid_extension(string $filename): bool
}
```

#### Características

**Formato CSV:**
```csv
username,email,password,firstname,lastname,status
jdoe,john.doe@example.com,SecurePass123,John,Doe,active
jsmith,jane.smith@example.com,AnotherPass456,Jane,Smith,active
```

**Columnas Requeridas:**
- username (mínimo 3 caracteres, alfanumérico + guiones bajos)
- email (formato válido)
- password (mínimo 8 caracteres)

**Columnas Opcionales:**
- firstname
- lastname
- status (active, suspended, pending)

**Validaciones:**
- Username único
- Email único
- Formato de email válido
- Longitud de password
- Formato de status

**Procesamiento:**
1. Validar extensión de archivo
2. Leer header CSV
3. Validar columnas requeridas
4. Procesar cada fila
5. Hash automático de passwords
6. Verificar unicidad de username/email
7. Crear usuario con user_helper
8. Reportar resultados detallados

**Capabilities Definidas:**
- `tool/uploaduser:upload` - Subir usuarios
- `tool/uploaduser:view` - Ver historial de cargas

---

### 2. tool_logviewer - Visualizador de Logs

**Propósito:** Visualizar y filtrar logs del sistema desde la base de datos.

#### Archivos Creados

**index.php (400+ líneas)**
- Interfaz web completa con estadísticas
- Filtros por nivel (error, warning, info, debug)
- Búsqueda en mensajes
- Paginación avanzada
- Estadísticas en tiempo real:
  - Total de logs
  - Errores en 24h
  - Warnings en 24h
  - Actividad del día

**version.php**
```php
$plugin->component = 'tool_logviewer';
$plugin->version = 2024111601;
$plugin->maturity = MATURITY_STABLE;
$plugin->release = '1.0.0';
```

**lib.php**
Funciones de biblioteca:
- `tool_logviewer_get_capabilities()` - 3 capabilities definidas
- `tool_logviewer_get_title()` - Título
- `tool_logviewer_get_description()` - Descripción
- `tool_logviewer_level_badge()` - Badge HTML para niveles
- `tool_logviewer_get_menu_items()` - Items de menú

**classes/log_reader.php**
Clase de lectura de logs:
```php
namespace tool_logviewer;

class log_reader {
    // Obtener logs con filtros
    public function get_logs(string $type, int $limit, int $offset, array $filters): array

    // Contar logs con filtros
    public function count_logs(string $type, array $filters): int

    // Estadísticas de logs
    public function get_statistics(): array

    // Logs por nivel
    public function get_logs_by_level(string $level, int $limit): array

    // Errores recientes
    public function get_recent_errors(int $limit): array

    // Logs de usuario específico
    public function get_user_logs(int $userId, int $limit): array

    // Eliminar logs antiguos
    public function delete_old_logs(int $daysOld): int

    // Exportar a CSV
    public function export_to_csv(array $filters): string
}
```

#### Características

**Filtros Disponibles:**
- Nivel de log (error, warning, info, debug)
- Búsqueda en mensajes y contexto
- Usuario específico
- Rango de fechas (implementable)

**Estadísticas:**
- Total de logs en sistema
- Errores en últimas 24 horas
- Warnings en últimas 24 horas
- Logs del día actual

**Paginación:**
- 50 logs por página (configurable)
- Navegación Previous/Next
- Saltos directos a páginas

**Exportación:**
- Formato CSV
- Incluye todos los filtros aplicados
- Máximo 10,000 registros

**Capabilities Definidas:**
- `tool/logviewer:view` - Ver logs
- `tool/logviewer:export` - Exportar logs
- `tool/logviewer:delete` - Eliminar logs antiguos

---

### 3. tool_pluginmanager - Gestor de Plugins

**Propósito:** Descubrir y mostrar todos los plugins instalados siguiendo Frankenstyle.

#### Archivos Creados

**index.php (200+ líneas)**
- Interfaz web para visualizar plugins
- Grid responsive de tarjetas de plugin
- Agrupación por tipo (auth, tool, theme, report, factor)
- Información detallada:
  - Nombre del componente
  - Versión formateada
  - Nivel de madurez (alpha, beta, rc, stable)
  - Release version
  - Descripción

**version.php**
```php
$plugin->component = 'tool_pluginmanager';
$plugin->version = 2024111601;
$plugin->maturity = MATURITY_STABLE;
$plugin->release = '1.0.0';
```

**lib.php**
Funciones de biblioteca:
- `tool_pluginmanager_get_capabilities()` - 3 capabilities
- `tool_pluginmanager_get_title()` - Título
- `tool_pluginmanager_get_description()` - Descripción
- `tool_pluginmanager_get_menu_items()` - Items de menú

**classes/plugin_manager.php**
Clase de descubrimiento de plugins:
```php
namespace tool_pluginmanager;

class plugin_manager {
    // Cargar tipos de plugins desde components.json
    private function load_plugin_types(): void

    // Obtener tipos de plugins
    public function get_plugin_types(): array

    // Obtener todos los plugins instalados
    public function get_installed_plugins(): array

    // Escanear directorio de plugins
    private function scan_plugin_directory(string $path, string $type): array

    // Cargar info desde version.php
    private function load_plugin_info(string $versionFile, string $type, string $name): ?array

    // Formatear número de versión (YYYYMMDDXX)
    private function format_version(int $version): string

    // Obtener string de madurez
    private function get_maturity_string(int $maturity): string

    // Formatear nombre de plugin
    private function format_plugin_name(string $name): string

    // Obtener plugin por componente
    public function get_plugin(string $component): ?array

    // Contar plugins instalados
    public function count_plugins(): int
}
```

#### Características

**Autodiscovery de Plugins:**
- Lee lib/components.json para tipos de plugins
- Escanea directorios automáticamente
- Detecta version.php en cada plugin
- Extrae metadata del plugin

**Tipos de Plugins Soportados:**
- auth (Authentication plugins)
- tool (Admin tools)
- factor (MFA factors)
- theme (Themes)
- report (Reports)

**Información Mostrada:**
- Component name (e.g., tool_uploaduser)
- Version number (formato YYYY-MM-DD.XX)
- Maturity level (alpha, beta, rc, stable)
- Release version
- Description

**Capabilities Definidas:**
- `tool/pluginmanager:manage` - Gestionar plugins
- `tool/pluginmanager:install` - Instalar plugins
- `tool/pluginmanager:uninstall` - Desinstalar plugins

---

### 4. Herramientas con Estructura Básica

#### tool_mfa - Multi-Factor Authentication

**Archivos Creados:**
- **version.php** - Metadata del componente
  - Component: `tool_mfa`
  - Version: 2024111601
  - Maturity: BETA
  - Release: 0.9.0

- **lib.php** - Funciones de biblioteca
  - Capabilities: manage, configure_factors
  - Funciones helper para MFA
  - Lista de factores disponibles (email, iprange)

**Estado:** Estructura Frankenstyle lista, implementación completa pendiente

**Factores Definidos:**
- Email Verification
- IP Range Restriction

#### tool_installaddon - Instalador de Plugins

**Archivos Creados:**
- **version.php** - Metadata del componente
  - Component: `tool_installaddon`
  - Version: 2024111601
  - Maturity: ALPHA
  - Release: 0.5.0

- **lib.php** - Funciones de biblioteca
  - Capabilities: install, validate
  - Funciones helper para instalación

**Estado:** Estructura Frankenstyle lista, implementación completa pendiente

#### tool_dataprivacy - Privacidad de Datos

**Archivos Creados:**
- **version.php** - Metadata del componente
  - Component: `tool_dataprivacy`
  - Version: 2024111601
  - Maturity: ALPHA
  - Release: 0.5.0

- **lib.php** - Funciones de biblioteca
  - Capabilities: manage, export, delete
  - Funciones helper para GDPR

**Estado:** Estructura Frankenstyle lista, implementación completa pendiente

---

## 📊 Métricas de Implementación

### Archivos Creados por Herramienta

| Herramienta | Archivos | Líneas de Código | Estado |
|-------------|----------|------------------|--------|
| **tool_uploaduser** | 4 | ~800 | ✅ Completo |
| **tool_logviewer** | 4 | ~900 | ✅ Completo |
| **tool_pluginmanager** | 4 | ~650 | ✅ Completo |
| **tool_mfa** | 2 | ~150 | 🟡 Estructura básica |
| **tool_installaddon** | 2 | ~120 | 🟡 Estructura básica |
| **tool_dataprivacy** | 2 | ~140 | 🟡 Estructura básica |
| **TOTAL** | **18 archivos** | **~2,760 líneas** | - |

### Capabilities Definidas

| Herramienta | Capabilities | Descripción |
|-------------|--------------|-------------|
| uploaduser | 2 | Upload, view history |
| logviewer | 3 | View, export, delete |
| pluginmanager | 3 | Manage, install, uninstall |
| mfa | 2 | Manage, configure factors |
| installaddon | 2 | Install, validate |
| dataprivacy | 3 | Manage, export, delete |
| **TOTAL** | **15** | - |

### Clases Implementadas

| Clase | Namespace | Métodos | Propósito |
|-------|-----------|---------|-----------|
| uploader | tool_uploaduser | 6 | Procesar CSV y crear usuarios |
| log_reader | tool_logviewer | 9 | Leer y filtrar logs |
| plugin_manager | tool_pluginmanager | 10 | Descubrir plugins instalados |

---

## 🎯 Beneficios de la Fase 4

### 1. Administración Masiva de Usuarios
- Ahorro de tiempo significativo
- Reducción de errores manuales
- Validación automática de datos
- Reporte detallado de resultados

### 2. Monitoreo del Sistema
- Visibilidad completa de logs
- Filtrado avanzado
- Detección temprana de errores
- Exportación para análisis

### 3. Gestión de Plugins
- Inventario automático
- Información centralizada
- Detección de versiones
- Planificación de actualizaciones

### 4. Arquitectura Extensible
- Estructura Frankenstyle completa
- Fácil adición de nuevas herramientas
- Patrón consistente
- Autodiscovery de componentes

---

## 🔧 Uso de las Herramientas

### Carga Masiva de Usuarios

```bash
# Acceso directo
http://localhost/admin/tool/uploaduser

# Vía router (recomendado)
http://localhost/admin/tool/uploaduser
```

**Requisitos:**
- Capability: `tool/uploaduser:upload`
- Archivo CSV con formato correcto
- Usernames y emails únicos

**Proceso:**
1. Preparar archivo CSV con columnas requeridas
2. Acceder a la herramienta
3. Seleccionar archivo CSV
4. Click en "Upload Users"
5. Revisar resultados detallados

### Visualización de Logs

```bash
# Acceso
http://localhost/admin/tool/logviewer

# Con filtros
http://localhost/admin/tool/logviewer?level=error&search=database
```

**Requisitos:**
- Capability: `tool/logviewer:view`
- Tabla `iser_logs` en base de datos

**Funciones:**
- Filtrar por nivel
- Buscar en mensajes
- Navegar paginación
- Ver estadísticas

### Gestión de Plugins

```bash
# Acceso
http://localhost/admin/tool/pluginmanager
```

**Requisitos:**
- Capability: `tool/pluginmanager:manage`
- Plugins con version.php

**Funciones:**
- Ver todos los plugins instalados
- Información de versión
- Nivel de madurez
- Agrupación por tipo

---

## 🔐 Seguridad

### Control de Acceso

Todas las herramientas implementan:
```php
// Verificar autenticación
require_login();

// Verificar capability específica
require_capability('tool/[toolname]:[action]');
```

### Validación de Datos

**tool_uploaduser:**
- Validación de extensión de archivo
- Sanitización de datos CSV
- Verificación de unicidad
- Formato de email
- Longitud de password
- Hash automático de passwords

**tool_logviewer:**
- Prepared statements SQL
- Escape de output HTML
- Límites de paginación
- Validación de filtros

**tool_pluginmanager:**
- Validación de rutas
- Aislamiento de include
- Escape de output HTML

---

## 📝 Patrones de Frankenstyle Aplicados

### 1. Estructura de Archivos

✅ Cada herramienta incluye:
- index.php (punto de entrada)
- version.php (metadata)
- lib.php (funciones de biblioteca)
- classes/ (clases del componente)

### 2. Naming Convention

✅ Componentes nombrados como `tool_[nombre]`:
- tool_uploaduser
- tool_logviewer
- tool_pluginmanager
- tool_mfa
- tool_installaddon
- tool_dataprivacy

### 3. Metadata Completa

✅ Todos los version.php incluyen:
```php
$plugin->component = 'tool_xxx';
$plugin->version = YYYYMMDDXX;
$plugin->requires = YYYYMMDDXX;
$plugin->maturity = MATURITY_*;
$plugin->release = 'X.Y.Z';
$plugin->description = '...';
```

### 4. Capabilities

✅ Formato estandarizado:
```
tool/[toolname]:[action]
```

Ejemplos:
- `tool/uploaduser:upload`
- `tool/logviewer:view`
- `tool/pluginmanager:manage`

### 5. Library Functions

✅ Todas las lib.php incluyen:
- `tool_[name]_get_capabilities()`
- `tool_[name]_get_title()`
- `tool_[name]_get_description()`
- `tool_[name]_get_menu_items()`

### 6. Namespaces

✅ Clases usan namespace correcto:
```php
namespace tool_uploaduser;
namespace tool_logviewer;
namespace tool_pluginmanager;
```

---

## 🧪 Testing

### Pruebas Manuales

**tool_uploaduser:**
```bash
# Preparar CSV de prueba
cat > test_users.csv << 'EOF'
username,email,password,firstname,lastname,status
test1,test1@example.com,password123,Test,One,active
test2,test2@example.com,password456,Test,Two,active
EOF

# Subir archivo
# Verificar resultados
# Confirmar usuarios en base de datos
```

**tool_logviewer:**
```bash
# Generar logs de prueba
# Acceder a herramienta
# Probar filtros
# Verificar paginación
```

**tool_pluginmanager:**
```bash
# Acceder a herramienta
# Verificar que detecta todos los plugins
# Confirmar información correcta
```

### Verificación de Capabilities

```php
// Verificar que capabilities están definidas
$caps = tool_uploaduser_get_capabilities();
assert(count($caps) === 2);

$caps = tool_logviewer_get_capabilities();
assert(count($caps) === 3);

$caps = tool_pluginmanager_get_capabilities();
assert(count($caps) === 3);
```

---

## 🚀 Próximas Mejoras (Post-Fase 4)

### tool_uploaduser
- [ ] Soporte para Excel (.xlsx)
- [ ] Preview de datos antes de importar
- [ ] Importación incremental (actualizar existentes)
- [ ] Plantillas CSV descargables
- [ ] Historial de importaciones

### tool_logviewer
- [ ] Filtros de fecha/hora
- [ ] Gráficos de actividad
- [ ] Alertas automáticas
- [ ] Exportación a múltiples formatos (JSON, XML)
- [ ] Rotación automática de logs

### tool_pluginmanager
- [ ] Actualización de plugins
- [ ] Instalación desde marketplace
- [ ] Desinstalación de plugins
- [ ] Habilitación/deshabilitación
- [ ] Verificación de dependencias

### tool_mfa
- [ ] Implementación completa de factores
- [ ] UI de configuración
- [ ] Integración con login
- [ ] Factores adicionales (TOTP, SMS)

### tool_installaddon
- [ ] Upload de ZIP files
- [ ] Validación de estructura
- [ ] Extracción segura
- [ ] Instalación automática

### tool_dataprivacy
- [ ] Exportación de datos de usuario
- [ ] Eliminación permanente (right to be forgotten)
- [ ] Reportes de compliance
- [ ] Consentimientos

---

## ✅ Checklist de Completitud

### Herramientas Completas
- [x] tool_uploaduser implementado
  - [x] index.php con interfaz completa
  - [x] version.php con metadata
  - [x] lib.php con funciones
  - [x] uploader.php con lógica de negocio
  - [x] Capabilities definidas (2)
  - [x] Validaciones implementadas
  - [x] Integración con user_helper

- [x] tool_logviewer implementado
  - [x] index.php con interfaz completa
  - [x] version.php con metadata
  - [x] lib.php con funciones
  - [x] log_reader.php con lógica de negocio
  - [x] Capabilities definidas (3)
  - [x] Filtros implementados
  - [x] Estadísticas en tiempo real
  - [x] Paginación avanzada

- [x] tool_pluginmanager implementado
  - [x] index.php con interfaz completa
  - [x] version.php con metadata
  - [x] lib.php con funciones
  - [x] plugin_manager.php con lógica de negocio
  - [x] Capabilities definidas (3)
  - [x] Autodiscovery de plugins
  - [x] Formateo de versiones
  - [x] Agrupación por tipos

### Estructuras Básicas
- [x] tool_mfa estructura creada
  - [x] version.php con metadata
  - [x] lib.php con capabilities

- [x] tool_installaddon estructura creada
  - [x] version.php con metadata
  - [x] lib.php con capabilities

- [x] tool_dataprivacy estructura creada
  - [x] version.php con metadata
  - [x] lib.php con capabilities

### Documentación
- [x] FASE_4_ADMIN_TOOLS.md completo
- [x] Descripción de arquitectura
- [x] Detalles de implementación
- [x] Ejemplos de uso
- [x] Guías de testing

---

## 📖 Referencias

### Archivos Clave

- `lib/components.json` - Definición de tipos de plugins
- `lib/setup.php` - Sistema de componentes
- `lib/accesslib.php` - Funciones RBAC
- `lib/classes/user/user_helper.php` - Helper de usuarios
- `composer.json` - Autoloading de namespaces

### Namespaces Definidos

```json
{
  "tool_uploaduser\\": "admin/tool/uploaduser/classes/",
  "tool_logviewer\\": "admin/tool/logviewer/classes/",
  "tool_pluginmanager\\": "admin/tool/pluginmanager/classes/"
}
```

### Capabilities por Herramienta

**15 capabilities totales** definidas en Fase 4:
- uploaduser: 2 capabilities
- logviewer: 3 capabilities
- pluginmanager: 3 capabilities
- mfa: 2 capabilities
- installaddon: 2 capabilities
- dataprivacy: 3 capabilities

---

## 🎓 Lecciones Aprendidas

1. **Patrón Consistente:** Mantener la misma estructura (index.php, version.php, lib.php, classes/) facilita enormemente el desarrollo y mantenimiento.

2. **Capabilities Granulares:** Definir capabilities específicas por acción (view, upload, export, etc.) proporciona control de acceso fino.

3. **Helper Classes:** Usar helpers (user_helper, role_helper) simplifica la lógica de negocio en las herramientas.

4. **Autodiscovery:** El plugin_manager demuestra el poder del autodiscovery de Frankenstyle.

5. **Validación Robusta:** Validar datos en múltiples capas (frontend, backend, helper) previene errores y mejora la seguridad.

6. **UI Embebida:** Incluir HTML/CSS en index.php es aceptable para herramientas administrativas simples (evita complejidad innecesaria).

7. **Progressive Enhancement:** Implementar primero las herramientas críticas (uploaduser, logviewer) y estructuras básicas para las demás permite avance incremental.

---

**Fase 4 Completada:** 2024-11-16
**Archivos Creados:** 18
**Líneas de Código:** ~2,760
**Capabilities:** 15

**Próxima Fase:** Fase 5 - Migración de Temas a Frankenstyle (theme/*)
