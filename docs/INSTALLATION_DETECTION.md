# Sistema de Detección de Instalación - NexoSupport

## Filosofía: Igual que Moodle

NexoSupport detecta si está instalado **exactamente como Moodle**:

1. **Un solo archivo de configuración:** `.env` (equivalente a `config.php` de Moodle)
2. **Detección por base de datos:** Verifica si existe la tabla `{prefix}config`
3. **Sin archivos marcadores:** NO usa `.installed` ni similar

## Flujo de Detección

```
Usuario accede al sistema
    ↓
¿Existe .env?
├─ NO → Redirigir a /install
│
└─ SÍ → Leer configuración de BD desde .env
    ↓
¿Configuración válida?
├─ NO → Redirigir a /install
│
└─ SÍ → Intentar conectar a BD
    ↓
¿Conexión exitosa?
├─ NO → Redirigir a /install
│
└─ SÍ → Verificar tabla {prefix}config
    ↓
¿Existe tabla config?
├─ NO → Redirigir a /install (completar instalación)
│
└─ SÍ → SISTEMA INSTALADO ✓
    ↓
Cargar sistema normalmente
```

## Comparación con Moodle

| Aspecto | Moodle | NexoSupport |
|---------|--------|-------------|
| Archivo de configuración | `config.php` | `.env` |
| Tabla de detección | `mdl_config` | `{prefix}config` |
| Archivo marcador | ❌ No usa | ❌ No usa |
| Detección por BD | ✅ Sí | ✅ Sí |
| Lógica | Si existe config.php y tabla, está instalado | Si existe .env y tabla, está instalado |

## Archivos Clave

### 1. `public_html/index.php` (Front Controller)

**Responsabilidad:** Detectar estado de instalación

```php
// PASO 1: ¿Existe .env?
if (!file_exists('.env')) → INSTALADOR

// PASO 2: Leer .env
$dbConfig = parse_env();

// PASO 3: ¿Configuración válida?
if (!valid_config($dbConfig)) → INSTALADOR

// PASO 4: ¿Puede conectar a BD?
try {
    $pdo = new PDO(...);
} catch (PDOException $e) → INSTALADOR

// PASO 5: ¿Existe tabla config?
if (!table_exists('config')) → INSTALADOR

// PASO 6: Sistema instalado
load_system();
```

### 2. `lib/setup.php`

**Responsabilidad:** Conectar a BD y determinar si está instalado

```php
// Leer .env
$CFG->dbhost = getenv('DB_HOST');
// ...

// Conectar siempre (el front controller ya verificó que .env existe)
try {
    $pdo = new PDO(...);
    $DB = new database($pdo);

    // Verificar si está instalado
    $CFG->installed = table_exists('config');
} catch (PDOException $e) {
    $DB = null;
    $CFG->installed = false;
}
```

### 3. `install/stages/finish.php`

**Responsabilidad:** Completar instalación

```php
// Guardar versión del core en tabla config
$DB->insert_record('config', [
    'name' => 'version',
    'value' => $plugin->version
]);

// ¡NO crear archivo .installed!
// La instalación está completa cuando:
// 1. Existe .env
// 2. La tabla config tiene datos
```

## Escenarios

### Escenario 1: Primera instalación

**Estado inicial:**
- ❌ `.env` no existe
- ❌ Base de datos vacía

**Flujo:**
1. Usuario accede → No existe `.env` → Redirige a instalador
2. Instalador solicita configuración de BD
3. Instalador crea archivo `.env`
4. Instalador crea tablas en BD
5. Instalador inserta datos iniciales en `config`
6. Usuario accede → Existe `.env` Y tabla `config` → Sistema funciona ✓

### Escenario 2: Se borró archivo `.env`

**Estado inicial:**
- ❌ `.env` no existe (borrado accidentalmente)
- ✅ Base de datos tiene datos

**Flujo:**
1. Usuario accede → No existe `.env` → Redirige a instalador
2. Usuario recrea `.env` con sus credenciales (o va al instalador)
3. Sistema detecta que BD ya tiene tablas
4. Instalador muestra error: "Ya existe instalación"
5. Usuario crea `.env` manualmente con credenciales correctas
6. Usuario accede → Existe `.env` Y tabla `config` → Sistema funciona ✓

**Solución rápida:**
```bash
# Copiar desde example
cp .env.example .env

# Editar con credenciales correctas
nano .env

# Listo, acceder al sistema
```

### Escenario 3: Instalación parcial (existe `.env` pero no hay tablas)

**Estado inicial:**
- ✅ `.env` existe
- ❌ BD no tiene tablas (instalación interrumpida)

**Flujo:**
1. Usuario accede → Existe `.env` → Lee configuración
2. Sistema verifica tabla `config` → No existe
3. Redirige a instalador
4. Instalador detecta que `.env` ya existe
5. Instalador usa esa configuración y crea tablas
6. Usuario accede → Sistema funciona ✓

### Escenario 4: Error anterior "Duplicate entry"

**Problema previo (cuando usábamos `.installed`):**
```
Error: SQLSTATE[23000]: Integrity constraint violation: 1062
Duplicate entry '10-0' for key 'idx_contextlevel_instanceid'
```

**Causa:** Se borraba `.installed` pero BD tenía datos, instalador intentaba insertar duplicados.

**Solución nueva:**
- El instalador verifica si existe tabla `config` ANTES de instalar
- Si existe, muestra error y NO intenta insertar
- Ya no hay errores de duplicate entry

## Ventajas de Este Enfoque

1. **Simplicidad:** Solo un archivo de configuración (`.env`)
2. **Consistencia:** Igual que Moodle, patrón conocido
3. **Confiabilidad:** La fuente de verdad es la BD, no archivos
4. **Sin duplicación:** No hay conflicto entre `.env` e `.installed`
5. **Recuperación fácil:** Basta con recrear `.env` con credenciales correctas

## Archivos Eliminados

Los siguientes archivos/conceptos ya NO se usan:

- ❌ `.installed` - No se crea ni se verifica
- ❌ `install/recovery.php` - No es necesario
- ❌ Variable `INSTALLED=true/false` en `.env` - No se usa

## Ejemplo de `.env` Mínimo

```env
# Base de datos (REQUERIDO)
DB_HOST=localhost
DB_DATABASE=nexosupport
DB_USERNAME=root
DB_PASSWORD=
DB_PREFIX=nxs_

# Aplicación (REQUERIDO)
APP_URL=http://localhost

# Opcional
APP_DEBUG=false
APP_ENV=production
```

Con solo estos valores, el sistema puede:
1. Conectar a BD
2. Verificar si está instalado
3. Funcionar normalmente

## Troubleshooting

### Problema: "Page not found" o redirección infinita

**Causa:** `.env` no existe o es inválido

**Solución:**
1. Verificar que existe `.env`
2. Verificar que tiene las variables de BD correctas
3. Verificar que puede conectar a la BD

### Problema: "Database connection failed"

**Causa:** Credenciales incorrectas en `.env`

**Solución:**
1. Revisar valores en `.env`
2. Verificar que MySQL/MariaDB está corriendo
3. Verificar permisos del usuario de BD
4. Probar conexión manualmente:
```bash
mysql -h localhost -u root -p nexosupport
```

### Problema: "Class access_exception not found"

**Causa:** Código antiguo intentando usar excepción que no existía

**Solución:** Actualizada en v1.1.7 - la clase `access_exception` ahora existe en `lib/functions.php`

### Problema: Sistema va al instalador aunque ya está instalado

**Causa:**
- `.env` no existe
- BD no tiene tabla `config`
- No puede conectar a BD

**Solución:**
1. Verificar que existe `.env` con configuración correcta
2. Verificar que BD existe y tiene tablas
3. Verificar conexión manual a BD

## Código de Referencia

### Verificar manualmente si está instalado

```php
// Leer .env
if (!file_exists('.env')) {
    echo "No instalado - falta .env\n";
    exit;
}

// Conectar
$env = parse_ini_file('.env');
$pdo = new PDO(
    "mysql:host={$env['DB_HOST']};dbname={$env['DB_DATABASE']}",
    $env['DB_USERNAME'],
    $env['DB_PASSWORD']
);

// Verificar tabla
$stmt = $pdo->query("SHOW TABLES LIKE '{$env['DB_PREFIX']}config'");
$installed = ($stmt->rowCount() > 0);

echo $installed ? "Instalado ✓\n" : "No instalado - falta BD\n";
```

## Migración desde Versiones Antiguas

Si tenías una instalación con `.installed`:

1. **No hacer nada** - El archivo `.installed` se ignora ahora
2. Si quieres limpieza, puedes borrarlo: `rm .installed`
3. El sistema solo verifica `.env` y la BD

## Contacto y Soporte

- **Alonso Arias** (Arquitecto): soporteplataformas@iser.edu.co
- **Yulian Moreno** (Desarrollador): nexo.operativo@iser.edu.co
- **Mauricio Zafra** (Supervisor): vicerrectoria@iser.edu.co

---

**Última actualización:** 2025-11-18
**Versión del sistema:** 1.1.7
**Patrón:** Moodle-style detection (solo `.env` + BD)
