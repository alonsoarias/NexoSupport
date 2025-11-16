# FASE 6: COMPLETAR HERRAMIENTAS ADMINISTRATIVAS CRÍTICAS

**Fecha Inicio:** 2024-11-16
**Estado:** 📋 PLANIFICACIÓN
**Prioridad:** 🔴 ALTA (Seguridad y Compliance)

---

## 📋 RESUMEN EJECUTIVO

La Fase 6 completará la implementación de tres herramientas administrativas críticas que quedaron en estado "base" durante la Fase 4:

1. **tool_mfa** - Multi-Factor Authentication (Seguridad)
2. **tool_installaddon** - Install Plugin (Extensibilidad)
3. **tool_dataprivacy** - Data Privacy/GDPR (Compliance)

Estas herramientas son fundamentales para la seguridad, extensibilidad y cumplimiento normativo del sistema.

---

## 🎯 OBJETIVOS

### Objetivos Principales

1. ✅ Implementar sistema completo de autenticación multifactor (MFA)
2. ✅ Desarrollar instalador de plugins desde archivos ZIP
3. ✅ Crear sistema de gestión de privacidad de datos (GDPR)
4. ✅ Mantener consistencia con arquitectura Frankenstyle
5. ✅ Garantizar seguridad y validación robusta

### Métricas Esperadas

- **Archivos a crear:** ~15 archivos (5 por tool)
- **Líneas de código:** ~2,500-3,000 líneas
- **Capabilities:** 7 existentes (ya definidas en Fase 4)
- **Tiempo estimado:** 2-3 horas

---

## 📦 COMPONENTE 1: TOOL_MFA (Multi-Factor Authentication)

### Estado Actual

**Ubicación:** `admin/tool/mfa/`

**Existente:**
- ✅ version.php (metadata completo)
- ✅ lib.php (2 capabilities, funciones helper)

**Faltante:**
- ❌ index.php (interfaz de administración)
- ❌ classes/mfa_manager.php (gestor de factores)
- ❌ classes/factors/email_factor.php (factor email)
- ❌ classes/factors/iprange_factor.php (factor IP range)
- ❌ db/install.php (schema de base de datos)

### Capabilities Existentes

```php
'tool/mfa:manage' => 'Configure multi-factor authentication settings'
'tool/mfa:configure_factors' => 'Enable/disable MFA factors'
```

### Factores MFA a Implementar

#### 1. Email Factor
- Envío de código de verificación por email
- Códigos de 6 dígitos
- Expiración de 10 minutos
- Límite de intentos (3)

#### 2. IP Range Factor
- Restricción por rangos de IP
- Formato CIDR (192.168.1.0/24)
- Lista blanca/negra
- Logging de accesos bloqueados

### Archivos a Crear

#### 1. admin/tool/mfa/index.php (~200 líneas)
**Funcionalidad:**
- Dashboard de factores MFA
- Habilitar/deshabilitar factores
- Configuración por rol
- Estadísticas de uso

**Secciones:**
- Lista de factores disponibles
- Estado (habilitado/deshabilitado)
- Configuración de cada factor
- Logs de autenticación MFA

#### 2. admin/tool/mfa/classes/mfa_manager.php (~300 líneas)
**Clase:** `ISER\Tools\MFA\MFAManager`

**Métodos:**
- `get_enabled_factors()` - Factores habilitados
- `enable_factor($factor)` - Habilitar factor
- `disable_factor($factor)` - Deshabilitar factor
- `verify_user($user_id, $factor, $code)` - Verificar usuario
- `get_factors_for_user($user_id)` - Factores del usuario
- `require_mfa_for_role($role_id)` - Requerir MFA por rol
- `get_verification_stats()` - Estadísticas

#### 3. admin/tool/mfa/classes/factors/email_factor.php (~250 líneas)
**Clase:** `ISER\Tools\MFA\Factors\EmailFactor`

**Métodos:**
- `send_code($user_id)` - Enviar código
- `verify_code($user_id, $code)` - Verificar código
- `generate_code()` - Generar código 6 dígitos
- `is_code_expired($timestamp)` - Verificar expiración
- `increment_attempts($user_id)` - Incrementar intentos
- `reset_attempts($user_id)` - Resetear intentos

**Tabla DB:** `mfa_email_codes`
- id, user_id, code, created_at, expires_at, attempts, verified

#### 4. admin/tool/mfa/classes/factors/iprange_factor.php (~200 líneas)
**Clase:** `ISER\Tools\MFA\Factors\IPRangeFactor`

**Métodos:**
- `add_ip_range($range, $type)` - Agregar rango (whitelist/blacklist)
- `remove_ip_range($id)` - Eliminar rango
- `check_ip($ip)` - Verificar IP
- `is_ip_in_range($ip, $range)` - Verificar si IP está en rango
- `get_user_ip()` - Obtener IP del usuario
- `log_access($user_id, $ip, $allowed)` - Log acceso

**Tabla DB:** `mfa_ip_ranges`
- id, range_cidr, type (whitelist/blacklist), description, created_at

**Tabla DB:** `mfa_ip_logs`
- id, user_id, ip, allowed, timestamp

#### 5. admin/tool/mfa/db/install.php (~150 líneas)
**Schema:**
```sql
CREATE TABLE mfa_email_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    code VARCHAR(6) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    attempts INT DEFAULT 0,
    verified BOOLEAN DEFAULT FALSE,
    INDEX idx_user_id (user_id),
    INDEX idx_expires (expires_at)
);

CREATE TABLE mfa_ip_ranges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    range_cidr VARCHAR(50) NOT NULL,
    type ENUM('whitelist', 'blacklist') NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_type (type)
);

CREATE TABLE mfa_ip_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    ip VARCHAR(45) NOT NULL,
    allowed BOOLEAN NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_timestamp (timestamp)
);
```

### Estimación tool_mfa

- **Archivos:** 5
- **Líneas:** ~1,100
- **Tablas DB:** 3
- **Tiempo:** 45-60 minutos

---

## 📦 COMPONENTE 2: TOOL_INSTALLADDON (Install Plugin)

### Estado Actual

**Ubicación:** `admin/tool/installaddon/`

**Existente:**
- ✅ version.php (metadata completo)
- ✅ lib.php (2 capabilities, funciones helper)

**Faltante:**
- ❌ index.php (interfaz de instalación)
- ❌ classes/addon_installer.php (instalador)
- ❌ classes/addon_validator.php (validador)
- ❌ classes/zip_extractor.php (extractor ZIP)

### Capabilities Existentes

```php
'tool/installaddon:install' => 'Install plugins from ZIP files'
'tool/installaddon:validate' => 'Validate plugin packages before installation'
```

### Funcionalidad a Implementar

#### Proceso de Instalación

1. **Upload ZIP** - Subir archivo ZIP
2. **Validación** - Verificar estructura y seguridad
3. **Extracción** - Descomprimir en directorio temporal
4. **Verificación** - Validar version.php y estructura
5. **Instalación** - Copiar a directorio final
6. **Registro** - Registrar en sistema de plugins

### Archivos a Crear

#### 1. admin/tool/installaddon/index.php (~250 líneas)
**Funcionalidad:**
- Formulario de upload ZIP
- Validación de archivo
- Progreso de instalación
- Resultado y logs

**Secciones:**
- Upload form (drag & drop)
- Validation results
- Installation progress
- Success/Error messages
- Lista de plugins instalados recientemente

#### 2. admin/tool/installaddon/classes/addon_installer.php (~350 líneas)
**Clase:** `ISER\Tools\InstallAddon\AddonInstaller`

**Métodos:**
- `install_from_zip($filepath)` - Instalar desde ZIP
- `extract_to_temp($zipfile)` - Extraer a temporal
- `validate_structure($dir)` - Validar estructura
- `detect_plugin_type($dir)` - Detectar tipo (auth, tool, theme, etc.)
- `copy_to_destination($source, $type, $name)` - Copiar a destino
- `register_plugin($component)` - Registrar plugin
- `cleanup_temp($dir)` - Limpiar archivos temporales
- `rollback_installation($component)` - Rollback en caso de error

#### 3. admin/tool/installaddon/classes/addon_validator.php (~300 líneas)
**Clase:** `ISER\Tools\InstallAddon\AddonValidator`

**Métodos:**
- `validate_zip($filepath)` - Validar archivo ZIP
- `check_file_size($size)` - Verificar tamaño (max 50MB)
- `check_file_extension($filename)` - Verificar extensión
- `validate_version_php($content)` - Validar version.php
- `validate_lib_php($content)` - Validar lib.php
- `check_security_threats($dir)` - Buscar amenazas
- `validate_component_name($name)` - Validar nombre Frankenstyle
- `check_dependencies($plugin)` - Verificar dependencias

**Validaciones de Seguridad:**
- No permitir `eval()`, `exec()`, `system()`
- No permitir archivos `.phar`
- Validar que no contenga malware conocido
- Verificar firma digital (opcional)

#### 4. admin/tool/installaddon/classes/zip_extractor.php (~200 líneas)
**Clase:** `ISER\Tools\InstallAddon\ZipExtractor`

**Métodos:**
- `extract($zipfile, $destination)` - Extraer ZIP
- `verify_zip($zipfile)` - Verificar integridad
- `get_file_list($zipfile)` - Listar archivos
- `check_path_traversal($path)` - Prevenir path traversal
- `sanitize_filename($name)` - Sanitizar nombres
- `get_extraction_stats()` - Estadísticas

### Estimación tool_installaddon

- **Archivos:** 4
- **Líneas:** ~1,100
- **Tiempo:** 45-60 minutos

---

## 📦 COMPONENTE 3: TOOL_DATAPRIVACY (Data Privacy/GDPR)

### Estado Actual

**Ubicación:** `admin/tool/dataprivacy/`

**Existente:**
- ✅ version.php (metadata completo)
- ✅ lib.php (3 capabilities, funciones helper)

**Faltante:**
- ❌ index.php (interfaz de gestión)
- ❌ classes/privacy_manager.php (gestor de privacidad)
- ❌ classes/data_exporter.php (exportador de datos)
- ❌ classes/data_eraser.php (eliminador de datos)
- ❌ db/install.php (schema)

### Capabilities Existentes

```php
'tool/dataprivacy:manage' => 'Configure data privacy and GDPR settings'
'tool/dataprivacy:export' => 'Export user data for GDPR compliance'
'tool/dataprivacy:delete' => 'Permanently delete user data'
```

### Funcionalidad GDPR

#### Derechos del Usuario (GDPR)

1. **Derecho de Acceso** - Exportar todos los datos del usuario
2. **Derecho al Olvido** - Eliminar permanentemente los datos
3. **Portabilidad** - Exportar en formato legible por máquina
4. **Rectificación** - Actualizar datos personales

### Archivos a Crear

#### 1. admin/tool/dataprivacy/index.php (~300 líneas)
**Funcionalidad:**
- Dashboard de solicitudes
- Solicitudes de exportación
- Solicitudes de eliminación
- Configuración de retención
- Logs de compliance

**Secciones:**
- Pending requests (export/delete)
- Completed requests
- Retention policies
- Audit log
- GDPR settings

#### 2. admin/tool/dataprivacy/classes/privacy_manager.php (~350 líneas)
**Clase:** `ISER\Tools\DataPrivacy\PrivacyManager`

**Métodos:**
- `create_export_request($user_id)` - Crear solicitud de exportación
- `create_delete_request($user_id)` - Crear solicitud de eliminación
- `approve_request($request_id)` - Aprobar solicitud
- `reject_request($request_id, $reason)` - Rechazar solicitud
- `get_pending_requests()` - Solicitudes pendientes
- `get_user_data_categories()` - Categorías de datos
- `set_retention_policy($category, $days)` - Política de retención
- `cleanup_expired_data()` - Limpiar datos expirados
- `get_compliance_report()` - Reporte de compliance

#### 3. admin/tool/dataprivacy/classes/data_exporter.php (~300 líneas)
**Clase:** `ISER\Tools\DataPrivacy\DataExporter`

**Métodos:**
- `export_user_data($user_id, $format)` - Exportar datos
- `collect_user_info($user_id)` - Recopilar info de usuario
- `collect_user_activity($user_id)` - Recopilar actividad
- `collect_user_files($user_id)` - Recopilar archivos
- `export_to_json($data)` - Exportar a JSON
- `export_to_xml($data)` - Exportar a XML
- `export_to_pdf($data)` - Exportar a PDF
- `create_export_package($user_id)` - Crear paquete ZIP

**Categorías de Datos:**
- Personal information (name, email, phone)
- Activity logs
- Uploaded files
- Settings and preferences
- Authentication history

#### 4. admin/tool/dataprivacy/classes/data_eraser.php (~250 líneas)
**Clase:** `ISER\Tools\DataPrivacy\DataEraser`

**Métodos:**
- `delete_user_data($user_id)` - Eliminar todos los datos
- `anonymize_user($user_id)` - Anonimizar usuario
- `delete_personal_info($user_id)` - Eliminar info personal
- `delete_activity_logs($user_id)` - Eliminar logs
- `delete_user_files($user_id)` - Eliminar archivos
- `verify_deletion($user_id)` - Verificar eliminación completa
- `create_deletion_report($user_id)` - Reporte de eliminación

**Estrategias:**
- **Hard Delete**: Eliminación permanente de registros
- **Soft Delete**: Marcar como deleted, mantener por período
- **Anonymization**: Reemplazar datos personales con placeholders

#### 5. admin/tool/dataprivacy/db/install.php (~200 líneas)
**Schema:**
```sql
CREATE TABLE dataprivacy_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('export', 'delete') NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'completed') NOT NULL,
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL,
    processed_by INT NULL,
    notes TEXT,
    export_file VARCHAR(255),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_type (type)
);

CREATE TABLE dataprivacy_retention (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category VARCHAR(100) NOT NULL,
    retention_days INT NOT NULL,
    description TEXT,
    UNIQUE KEY uk_category (category)
);

CREATE TABLE dataprivacy_audit (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    category VARCHAR(100),
    performed_by INT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    details TEXT,
    INDEX idx_user_id (user_id),
    INDEX idx_timestamp (timestamp),
    INDEX idx_action (action)
);
```

### Estimación tool_dataprivacy

- **Archivos:** 5
- **Líneas:** ~1,400
- **Tablas DB:** 3
- **Tiempo:** 60-75 minutos

---

## 📊 RESUMEN DE FASE 6

### Totales Estimados

| Métrica | tool_mfa | tool_installaddon | tool_dataprivacy | TOTAL |
|---------|:--------:|:-----------------:|:----------------:|:-----:|
| **Archivos** | 5 | 4 | 5 | **14** |
| **Líneas** | ~1,100 | ~1,100 | ~1,400 | **~3,600** |
| **Clases** | 3 | 3 | 3 | **9** |
| **Tablas DB** | 3 | 0 | 3 | **6** |
| **Tiempo** | 45-60m | 45-60m | 60-75m | **2.5-3h** |

### Distribución de Archivos

```
admin/tool/
├── mfa/
│   ├── version.php (existente)
│   ├── lib.php (existente)
│   ├── index.php (NUEVO - 200 líneas)
│   ├── classes/
│   │   ├── mfa_manager.php (NUEVO - 300 líneas)
│   │   └── factors/
│   │       ├── email_factor.php (NUEVO - 250 líneas)
│   │       └── iprange_factor.php (NUEVO - 200 líneas)
│   └── db/
│       └── install.php (NUEVO - 150 líneas)
│
├── installaddon/
│   ├── version.php (existente)
│   ├── lib.php (existente)
│   ├── index.php (NUEVO - 250 líneas)
│   └── classes/
│       ├── addon_installer.php (NUEVO - 350 líneas)
│       ├── addon_validator.php (NUEVO - 300 líneas)
│       └── zip_extractor.php (NUEVO - 200 líneas)
│
└── dataprivacy/
    ├── version.php (existente)
    ├── lib.php (existente)
    ├── index.php (NUEVO - 300 líneas)
    ├── classes/
    │   ├── privacy_manager.php (NUEVO - 350 líneas)
    │   ├── data_exporter.php (NUEVO - 300 líneas)
    │   └── data_eraser.php (NUEVO - 250 líneas)
    └── db/
        └── install.php (NUEVO - 200 líneas)
```

---

## ✅ CRITERIOS DE ACEPTACIÓN

### Generales

- [ ] Todas las herramientas tienen interfaz funcional (index.php)
- [ ] Todas las clases implementan lógica completa
- [ ] Schemas de DB creados y documentados
- [ ] Validación de seguridad implementada
- [ ] Manejo de errores robusto
- [ ] Logs de auditoría
- [ ] Backward compatibility mantenida

### tool_mfa

- [ ] Email factor envía códigos correctamente
- [ ] IP range factor valida rangos CIDR
- [ ] Factores pueden habilitarse/deshabilitarse
- [ ] Dashboard muestra estadísticas
- [ ] Límite de intentos funciona
- [ ] Códigos expiran correctamente

### tool_installaddon

- [ ] Upload de ZIP funciona
- [ ] Validación detecta estructuras inválidas
- [ ] Validación de seguridad previene malware
- [ ] Extracción maneja path traversal
- [ ] Instalación copia archivos correctamente
- [ ] Rollback funciona en caso de error

### tool_dataprivacy

- [ ] Solicitudes de exportación generan archivos
- [ ] Exportación incluye todas las categorías de datos
- [ ] Eliminación borra todos los datos del usuario
- [ ] Anonymization reemplaza datos personales
- [ ] Retention policies se aplican automáticamente
- [ ] Audit log registra todas las acciones

---

## 🔒 CONSIDERACIONES DE SEGURIDAD

### tool_mfa

1. **Email Codes**
   - Códigos aleatorios criptográficamente seguros
   - Hash de códigos en DB (no plain text)
   - Rate limiting para evitar brute force
   - Expiración obligatoria

2. **IP Ranges**
   - Validación de formato CIDR
   - Prevenir bypass con headers (X-Forwarded-For)
   - Logging de todos los intentos

### tool_installaddon

1. **ZIP Validation**
   - Límite de tamaño (50MB)
   - Detección de zip bombs
   - Path traversal prevention
   - Escaneo de malware básico

2. **Code Validation**
   - Detectar eval(), exec(), system()
   - Validar sintaxis PHP
   - Verificar firma digital (futuro)

3. **Filesystem**
   - Permisos correctos en archivos
   - Sanitización de nombres de archivo
   - Limpieza de archivos temporales

### tool_dataprivacy

1. **Access Control**
   - Solo admin puede aprobar solicitudes
   - Usuarios solo ven sus propias solicitudes
   - Logging de todas las acciones

2. **Data Export**
   - Archivos temporales con nombres únicos
   - Limpieza automática de exports antiguos
   - Encriptación de archivos ZIP (futuro)

3. **Data Deletion**
   - Confirmación doble para delete
   - Backup antes de eliminar (opcional)
   - Verificación post-eliminación

---

## 🎯 BENEFICIOS ESPERADOS

### 1. Seguridad Mejorada (tool_mfa)
- Protección adicional contra accesos no autorizados
- Restricción por ubicación geográfica
- Compliance con estándares de seguridad

### 2. Extensibilidad (tool_installaddon)
- Ecosistema de plugins
- Instalación fácil y segura
- Validación automática de calidad

### 3. Compliance Legal (tool_dataprivacy)
- Cumplimiento con GDPR
- Respuesta a solicitudes de usuarios
- Auditoría completa
- Reducción de riesgo legal

---

## 📈 IMPACTO EN EL PROYECTO

### Antes de Fase 6

```
Tools Completos: 3/6 (50%)
Tools Base: 3/6 (50%)
Seguridad MFA: ❌
Extensibilidad Plugins: ❌
GDPR Compliance: ❌
```

### Después de Fase 6

```
Tools Completos: 6/6 (100%) ✅
Seguridad MFA: ✅
Extensibilidad Plugins: ✅
GDPR Compliance: ✅
Archivos Nuevos: +14
Líneas de Código: +3,600
Tablas DB: +6
```

---

## 🚀 ORDEN DE IMPLEMENTACIÓN

### Prioridad 1: tool_mfa (Seguridad)
**Razón:** Seguridad es crítica, debe implementarse primero

**Orden:**
1. db/install.php (schema)
2. classes/factors/email_factor.php
3. classes/factors/iprange_factor.php
4. classes/mfa_manager.php
5. index.php

### Prioridad 2: tool_dataprivacy (Compliance)
**Razón:** Compliance legal es urgente

**Orden:**
1. db/install.php (schema)
2. classes/privacy_manager.php
3. classes/data_exporter.php
4. classes/data_eraser.php
5. index.php

### Prioridad 3: tool_installaddon (Extensibilidad)
**Razón:** Extensibilidad es importante pero no urgente

**Orden:**
1. classes/zip_extractor.php
2. classes/addon_validator.php
3. classes/addon_installer.php
4. index.php

---

## 📚 DOCUMENTACIÓN A CREAR

1. **FASE_6_HERRAMIENTAS_ADMINISTRATIVAS.md** - Reporte completo de Fase 6
2. **Actualizar RESUMEN_REFACTORING_FRANKENSTYLE.md** - Agregar Fase 6

---

## ✨ CONCLUSIÓN

La Fase 6 completará las herramientas administrativas críticas del sistema, agregando:

- ✅ **Seguridad robusta** con MFA
- ✅ **Extensibilidad total** con instalador de plugins
- ✅ **Compliance GDPR** completo

Con esta fase, NexoSupport tendrá un conjunto completo de herramientas administrativas de nivel empresarial.

---

**Estado:** 📋 PLAN COMPLETO
**Siguiente Acción:** Comenzar implementación con tool_mfa

---

## 🎯 FASE 6 LISTA PARA IMPLEMENTACIÓN ✅
