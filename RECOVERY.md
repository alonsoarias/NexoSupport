# Sistema de Recuperación de Instalación - NexoSupport

## Descripción General

NexoSupport implementa un sistema de detección y recuperación de instalación similar al de Moodle. Este sistema previene errores cuando faltan archivos de configuración pero existe una base de datos con datos del sistema.

## ¿Cuándo se Usa?

El sistema de recuperación se activa automáticamente cuando:

1. **Falta el archivo `.installed`** en el directorio raíz
2. **Existe una base de datos** con tablas del sistema
3. **Puede o no existir** el archivo `.env`

## Flujo de Detección

### Paso 1: Verificación de Instalación

Cuando accedes al sistema, el front controller (`public_html/index.php`) realiza las siguientes verificaciones:

```
¿Existe .installed?
├─ SÍ → Cargar sistema normalmente
└─ NO → Continuar con verificación de BD
    │
    ├─ Estrategia 1: Leer configuración desde .env (si existe)
    ├─ Estrategia 2: Leer configuración desde .env.example
    ├─ Estrategia 3: Usar valores por defecto hardcoded
    │
    └─ Intentar conectar a BD
        │
        ├─ ¿Existe tabla config?
        │   ├─ SÍ → REDIRIGIR A RECUPERACIÓN
        │   └─ NO → Redirigir a instalación normal
        │
        └─ ¿Error de conexión?
            └─ Redirigir a instalación normal
```

### Paso 2: Recuperación Automática

Si se detecta una instalación existente, el usuario es redirigido a `/install/recovery.php` donde:

1. Se presenta un formulario para ingresar credenciales de BD
2. Se pre-cargan valores desde `.env.example` si está disponible
3. Al enviar el formulario:
   - Se verifica conexión a la BD
   - Se verifica que existen tablas del sistema
   - Se verifica que hay al menos un usuario
   - Se regeneran los archivos `.env` y `.installed`
   - Se redirige al sistema

## Archivos Modificados

### 1. `public_html/index.php`

**Cambios principales:**
- Agregada detección de instalación existente en BD
- Tres estrategias de detección de configuración
- Redirección automática a recuperación o instalación

**Ubicación:** Líneas 86-181

### 2. `install/recovery.php` (NUEVO)

**Funcionalidad:**
- Interfaz para recuperación de instalación
- Validación de conexión a BD
- Regeneración de archivos `.env` y `.installed`
- Prevención de reinstalación sobre datos existentes

### 3. `install/stages/install_db.php`

**Cambios principales:**
- Verificación de instalación existente antes de instalar
- Prevención de inserciones duplicadas
- Redirección a recuperación si se detectan datos

**Ubicación:** Líneas 38-59, 65-78

## Escenarios de Uso

### Escenario 1: Se borró el archivo `.env`

**Situación:**
```
✗ .env no existe
✓ .installed existe
✓ Base de datos existe con datos
```

**Resultado:**
- El sistema NO permite acceso (falta .env para conectar a BD)
- Borra también `.installed` y accede al sistema
- Se redirige a recuperación automáticamente
- Completa el formulario de recuperación
- Archivos regenerados → Sistema funciona

### Escenario 2: Se borraron ambos archivos (`.env` y `.installed`)

**Situación:**
```
✗ .env no existe
✗ .installed no existe
✓ Base de datos existe con datos
```

**Resultado:**
- Front controller intenta detectar BD usando `.env.example`
- Si encuentra la BD → Redirige a recuperación
- Si no puede conectar → Redirige a instalación normal
- Usuario completa formulario de recuperación
- Archivos regenerados → Sistema funciona

### Escenario 3: Primera instalación (sin BD previa)

**Situación:**
```
✗ .env no existe
✗ .installed no existe
✗ Base de datos vacía o no existe
```

**Resultado:**
- Front controller intenta detectar BD
- No encuentra tabla `config`
- Redirige a instalación normal
- Usuario completa instalación desde cero

### Escenario 4: Intento de reinstalación sobre BD existente

**Situación:**
- Usuario borra archivos de configuración
- Usuario intenta instalar desde cero
- Base de datos tiene datos

**Resultado:**
- El stage `install_db.php` detecta datos existentes
- Muestra error con enlace a recuperación
- Previene duplicate entry errors
- Protege datos existentes

## Valores de Configuración por Defecto

El sistema busca configuración en este orden:

### 1. Archivo `.env` (primera prioridad)

```env
DB_HOST=localhost
DB_DATABASE=iser_auth
DB_USERNAME=root
DB_PASSWORD=
DB_PREFIX=iser_
```

### 2. Archivo `.env.example` (segunda prioridad)

Los valores configurados en `.env.example` se usan si `.env` no existe.

### 3. Valores Hardcoded (última prioridad)

```php
[
    'host' => 'localhost',
    'database' => 'nexosupport',
    'username' => 'root',
    'password' => '',
    'prefix' => 'nxs_'
]
```

## Cómo Usar el Sistema de Recuperación

### Opción 1: Recuperación Automática

1. Accede a tu instalación de NexoSupport (ej: `http://localhost`)
2. El sistema detectará automáticamente la instalación existente
3. Serás redirigido a `/install/recovery.php`
4. Completa el formulario con las credenciales de tu BD
5. Haz clic en "Recuperar Instalación"
6. Los archivos `.env` y `.installed` serán regenerados
7. Accede al sistema normalmente

### Opción 2: Recuperación Manual

1. Accede directamente a `http://tu-dominio/install/recovery.php`
2. Completa el formulario
3. Continúa como en la opción 1

### Opción 3: Regeneración Manual de `.env`

Si prefieres crear el archivo manualmente:

```bash
# Copiar desde example
cp .env.example .env

# Editar con tus credenciales
nano .env

# Crear archivo .installed
echo "$(date) - Instalación recuperada" > .installed

# Ajustar permisos
chmod 644 .env .installed
```

## Prevención de Errores

### Error Original (ANTES)

```
Error: SQLSTATE[23000]: Integrity constraint violation: 1062
Duplicate entry '10-0' for key 'idx_contextlevel_instanceid'
```

**Causa:** El instalador intentaba insertar datos en una BD que ya los tenía.

### Solución (AHORA)

1. **Detección temprana:** El front controller detecta la instalación existente ANTES de llegar al instalador
2. **Prevención en instalador:** Si se llega al instalador con BD poblada, muestra error y enlace a recuperación
3. **Verificación antes de insertar:** Se verifica que no existan datos antes de insertarlos

## Compatibilidad con Moodle

Este sistema sigue los mismos principios que Moodle:

| Característica | Moodle | NexoSupport |
|----------------|--------|-------------|
| Archivo de marca de instalación | `config.php` | `.installed` |
| Archivo de configuración | `config.php` | `.env` |
| Detección de instalación en BD | ✓ | ✓ |
| Recuperación automática | ✓ | ✓ |
| Prevención de reinstalación | ✓ | ✓ |

## Troubleshooting

### Problema: "No se pudo conectar a la base de datos"

**Solución:**
- Verifica que MySQL/MariaDB esté corriendo
- Verifica las credenciales en el formulario
- Verifica que la base de datos existe
- Verifica permisos del usuario de BD

### Problema: "La base de datos no contiene una instalación válida"

**Solución:**
- La tabla `{prefix}config` no existe
- Esto significa que no hay instalación previa
- Usa "Instalar desde Cero" en lugar de recuperación

### Problema: "No se pudo crear el archivo .env"

**Solución:**
- Verifica permisos de escritura en el directorio raíz
```bash
chmod 755 /ruta/a/nexosupport
chown usuario:grupo /ruta/a/nexosupport
```

### Problema: El sistema sigue redirigiendo a instalación en lugar de recuperación

**Solución:**
- Verifica que los valores en `.env.example` coincidan con tu BD real
- Verifica que el prefijo de tablas sea correcto
- Verifica que la tabla `{prefix}config` exista en la BD

## Logs y Debugging

Para ver qué está detectando el sistema:

1. Habilita modo debug en `.env` (si existe):
```env
APP_DEBUG=true
APP_ENV=development
```

2. Revisa los logs en `var/logs/`

3. Verifica la conexión a BD manualmente:
```bash
mysql -u root -p
use iser_auth;
show tables like 'iser_config';
```

## Desarrollo Futuro

Posibles mejoras:

- [ ] Logging detallado del proceso de detección
- [ ] Interfaz CLI para recuperación
- [ ] Detección de múltiples bases de datos
- [ ] Backup automático antes de recuperación
- [ ] Validación de versión de BD vs archivos
- [ ] Migración asistida de configuración

## Contacto y Soporte

- **Alonso Arias** (Arquitecto): soporteplataformas@iser.edu.co
- **Yulian Moreno** (Desarrollador): nexo.operativo@iser.edu.co
- **Mauricio Zafra** (Supervisor): vicerrectoria@iser.edu.co

---

**Última actualización:** 2025-11-18
**Versión del sistema:** 1.1.6
