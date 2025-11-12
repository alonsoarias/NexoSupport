# ESPECIFICACIÓN: INSTALADOR WEB 11 ETAPAS

**Fecha**: 2025-11-12  
**Proyecto**: NexoSupport - Fase 7

---

## 1. OBJETIVO

Rediseñar el instalador web de 5 etapas actuales a **11 etapas completas** con configuración exhaustiva de todos los aspectos del sistema.

## 2. ETAPAS DEL NUEVO INSTALADOR

### ETAPA 1: Bienvenida
- Idioma de instalación (es, en, pt)
- Información del sistema NexoSupport
- Términos y condiciones (checkbox)
- Botón "Comenzar Instalación"

### ETAPA 2: Verificación de Requisitos
**Ya existe - MEJORAR**:
- PHP >= 8.1 ✅
- Extensiones: PDO, JSON, mbstring, openssl, session, ctype, hash, curl, gd
- Permisos de escritura: /var/, /modules/plugins/, raíz
- Memoria PHP >= 128MB
- Upload max filesize >= 10MB

### ETAPA 3: Configuración de Base de Datos
**Ya existe - MANTENER**:
- Driver (MySQL, PostgreSQL, SQLite)
- Host, Port, Database, Username, Password
- Prefijo de tablas
- Botón "Probar Conexión"

### ETAPA 4: Instalación de Base de Datos
**Ya existe - MANTENER**:
- Parsear schema.xml
- Crear 14 tablas (o 17 si normalizado a 3FN)
- Insertar datos iniciales
- Barra de progreso en tiempo real

### ETAPA 5: Usuario Administrador
**Ya existe - MANTENER**:
- Username, Email, Password (con validación)
- First Name, Last Name
- Crear usuario con rol admin

### ETAPA 6: Configuración de Seguridad (NUEVA)
- JWT Secret (generado automáticamente, opción de personalizar)
- JWT expiration time (default: 3600s)
- Password policy:
  - Mínimo caracteres (default: 8)
  - Requiere mayúsculas (checkbox)
  - Requiere números (checkbox)
  - Requiere caracteres especiales (checkbox)
- reCAPTCHA (opcional):
  - Enable/disable
  - Site key, Secret key
- Rate limiting:
  - Max login attempts (default: 5)
  - Lockout duration (default: 15 min)

### ETAPA 7: Configuración de Logging (NUEVA)
- Log channel (single, daily, syslog)
- Log level (debug, info, warning, error)
- Log path (default: var/logs/iser.log)
- Log rotation:
  - Max files (default: 14 días)
  - Max size per file (default: 10MB)
- Enable query logging (checkbox, default: false en producción)

### ETAPA 8: Configuración de Email (NUEVA)
- Mail driver (smtp, sendmail, mailgun, postmark)
- SMTP settings:
  - Host, Port, Encryption (tls/ssl)
  - Username, Password
  - From address, From name
- Botón "Test Email" (enviar email de prueba)

### ETAPA 9: Configuración de Cache y Storage (NUEVA)
- Cache driver (file, redis, memcached)
- Cache TTL default (default: 3600s)
- Storage:
  - Avatar storage path
  - Max avatar size
  - Allowed avatar types
- Upload settings:
  - Max filesize
  - Allowed extensions

### ETAPA 10: Configuración Regional (NUEVA)
- Timezone por defecto (select con zonas horarias)
- Locale por defecto (es, en, pt)
- Date format
- Time format
- Number format
- Currency (si aplica)

### ETAPA 11: Verificación Final y Completar
**Expandir existente**:
- Resumen de toda la configuración (todas las etapas)
- Verificar:
  - Conexión a BD OK
  - Archivos escribibles OK
  - Usuario admin creado OK
  - Email funcional (si configurado) OK
- Generar archivo .env con TODAS las variables
- Establecer INSTALLED=true
- Instrucciones post-instalación:
  - Eliminar o proteger /install/
  - Cambiar contraseña de admin
  - Configurar backups
  - Revisar logs
- Botón "Ir al Panel de Administración"

## 3. MEJORAS DE UI

**Barra de progreso global**:
```
[=====>          ] Etapa 5 de 11: Usuario Administrador
```

**Navegación**:
- Botones "Anterior" y "Siguiente" en cada etapa
- Breadcrumb visual de etapas
- Indicador de etapa actual

**Validación**:
- Validación en tiempo real (JavaScript)
- Mensajes de error claros
- No permite avanzar si hay errores

**Feedback visual**:
- Iconos de estado (✅ completado, ⚠️ advertencia, ❌ error)
- Animaciones suaves entre etapas
- Loading spinners en operaciones async

## 4. CONFIGURACIÓN DE .ENV GENERADO

El instalador debe generar un .env completo con ~150 variables basado en:
- Etapa 3: DB_*
- Etapa 5: Usuario admin (no en .env, en BD)
- Etapa 6: JWT_*, PASSWORD_*, RECAPTCHA_*, RATE_LIMIT_*
- Etapa 7: LOG_*
- Etapa 8: MAIL_*
- Etapa 9: CACHE_*, UPLOAD_*, AVATAR_*
- Etapa 10: APP_TIMEZONE, DEFAULT_LOCALE
- Variables adicionales: APP_ENV, APP_DEBUG, APP_NAME, APP_URL, INSTALLED=true

## 5. MODO CLI (OPCIONAL)

**Instalador CLI** para instalaciones automatizadas:
```
php install/cli-installer.php \
  --db-driver=mysql \
  --db-host=localhost \
  --db-database=nexosupport \
  --admin-username=admin \
  --admin-email=admin@example.com \
  --admin-password=securepass123 \
  --non-interactive
```

## 6. CRITERIOS DE ÉXITO

✅ El instalador debe:
1. Tener 11 etapas completas
2. Permitir configurar TODOS los aspectos del sistema
3. Validar inputs en cada etapa
4. Mostrar progreso claro (X de 11)
5. Permitir volver atrás sin perder datos
6. Generar .env con ~150 variables
7. Verificar instalación al final
8. Tener UI moderna y responsive

---

**FIN ESPECIFICACIÓN FASE 7**
