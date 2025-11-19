<?php
/**
 * Setup de NexoSupport
 *
 * Este archivo inicializa el sistema y establece las variables globales.
 * Debe ser incluido al inicio de cada script.
 *
 * @package NexoSupport
 */

// Verificar que se ha definido la constante de seguridad
defined('NEXOSUPPORT_INTERNAL') || die();

// ============================================
// DEBUG LEVEL CONSTANTS (similar to Moodle)
// ============================================

/** No debug messages */
define('DEBUG_NONE', 0);

/** Minimal debug messages - errors only */
define('DEBUG_MINIMAL', E_ERROR | E_PARSE);

/** Normal debug messages - errors and warnings */
define('DEBUG_NORMAL', E_ERROR | E_PARSE | E_WARNING | E_NOTICE);

/** All debug messages except strict and deprecated */
define('DEBUG_DEVELOPER', E_ALL & ~E_STRICT & ~E_DEPRECATED);

/** All debug messages including strict and deprecated (for developers only) */
define('DEBUG_ALL', E_ALL);

// ============================================
// PASO 1: Configuración básica de PHP
// ============================================

// Timezone
date_default_timezone_set('America/Bogota');

// Error reporting (depende del modo)
if (getenv('APP_ENV') === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', '0');
}

// ============================================
// PASO 2: Definir directorios
// ============================================

if (!defined('BASE_DIR')) {
    define('BASE_DIR', dirname(__DIR__));
}

// ============================================
// PASO 3: Cargar Composer Autoloader
// ============================================

if (file_exists(BASE_DIR . '/vendor/autoload.php')) {
    require_once(BASE_DIR . '/vendor/autoload.php');
}

// ============================================
// PASO 4: Cargar funciones globales
// ============================================

require_once(__DIR__ . '/functions.php');
require_once(__DIR__ . '/authlib.php');
require_once(__DIR__ . '/userlib.php');
require_once(__DIR__ . '/adminlib.php');

// ============================================
// PASO 5: Inicializar objeto $CFG
// ============================================

global $CFG;

$CFG = new stdClass();

// Directorios
$CFG->dirroot = BASE_DIR;
$CFG->dataroot = BASE_DIR . '/var';
$CFG->cachedir = BASE_DIR . '/var/cache';
$CFG->logdir = BASE_DIR . '/var/logs';
$CFG->sessiondir = BASE_DIR . '/var/sessions';

// Cargar variables de entorno
if (file_exists(BASE_DIR . '/.env')) {
    load_environment(BASE_DIR . '/.env');
}

// Configuración de base de datos
$CFG->dbtype = getenv('DB_DRIVER') ?: 'mysql';
$CFG->dbhost = getenv('DB_HOST') ?: 'localhost';
$CFG->dbname = getenv('DB_DATABASE') ?: 'nexosupport';
$CFG->dbuser = getenv('DB_USERNAME') ?: 'root';
$CFG->dbpass = getenv('DB_PASSWORD') ?: '';
$CFG->dbprefix = getenv('DB_PREFIX') ?: 'nxs_';

// Configuración general
$CFG->wwwroot = getenv('APP_URL') ?: 'http://localhost';

// Debug configuration - will be loaded from DB later if available
// Default from .env for installation/early errors
$CFG->debug = DEBUG_NONE;  // Will be overridden from DB
$CFG->debugdisplay = false; // Will be overridden from DB

// ============================================
// PASO 6: Conectar a base de datos
// ============================================
// El front controller ya verificó con environment_checker que el sistema está instalado
// Aquí solo conectamos a la BD

global $DB;

try {
    $dsn = build_dsn($CFG->dbtype, $CFG->dbhost, $CFG->dbname);
    $pdo = new PDO($dsn, $CFG->dbuser, $CFG->dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $DB = new \core\db\database($pdo, $CFG->dbprefix, $CFG->dbtype);

    // Marcar como instalado (el front controller ya lo verificó)
    $CFG->installed = true;

    // Load debug configuration from database
    try {
        $debug_level = $DB->get_field('config', 'value', ['name' => 'debug', 'component' => 'core']);
        $debug_display = $DB->get_field('config', 'value', ['name' => 'debugdisplay', 'component' => 'core']);

        if ($debug_level !== false) {
            $CFG->debug = (int)$debug_level;
        } else {
            // Default to NONE in production
            $CFG->debug = DEBUG_NONE;
        }

        if ($debug_display !== false) {
            $CFG->debugdisplay = (bool)(int)$debug_display;
        } else {
            $CFG->debugdisplay = false;
        }

        // Apply debug level to PHP error reporting
        if ($CFG->debug !== DEBUG_NONE) {
            error_reporting($CFG->debug);
            ini_set('display_errors', $CFG->debugdisplay ? '1' : '0');
        } else {
            error_reporting(0);
            ini_set('display_errors', '0');
        }
    } catch (Exception $e) {
        // If config table doesn't exist or error, use safe defaults
        $CFG->debug = DEBUG_NONE;
        $CFG->debugdisplay = false;
        error_reporting(0);
        ini_set('display_errors', '0');
    }

} catch (PDOException $e) {
    debugging("Database connection failed: " . $e->getMessage());
    $DB = null;
    $CFG->installed = false;
}

// ============================================
// PASO 7: Iniciar sesión
// ============================================

if (!headers_sent()) {
    // CRITICAL: Always use file-based sessions for reliability
    // Database sessions can cause issues during installation/upgrade
    if (session_status() === PHP_SESSION_NONE) {
        // Configure session settings
        ini_set('session.use_cookies', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_strict_mode', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_secure', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? '1' : '0');
        ini_set('session.cookie_samesite', 'Lax');
        ini_set('session.gc_maxlifetime', '7200'); // 2 hours
        ini_set('session.save_path', $CFG->sessiondir);

        // Set session name
        session_name('NEXOSUPPORT_SESSION');

        // Start session
        session_start();
    }
}

// ============================================
// PASO 8: Inicializar $USER
// ============================================

global $USER;

// Si hay usuario en sesión, recargar desde BD para tener datos frescos
if ($CFG->installed && $DB !== null && isset($_SESSION['USER']) && isset($_SESSION['USER']->id) && $_SESSION['USER']->id > 0) {
    try {
        // Recargar usuario desde BD para tener todos los campos actualizados
        $userid = $_SESSION['USER']->id;
        $USER = $DB->get_record('users', ['id' => $userid]);

        if (!$USER || $USER->deleted) {
            // Usuario eliminado o no existe, cerrar sesión
            unset($_SESSION['USER']);
            $USER = new stdClass();
            $USER->id = 0;
        } else {
            // Actualizar sesión con datos frescos
            $_SESSION['USER'] = $USER;
        }
    } catch (Exception $e) {
        debugging('Error loading user from database: ' . $e->getMessage());
        // Si falla, usar datos de sesión
        $USER = $_SESSION['USER'];
    }
} else if (isset($_SESSION['USER'])) {
    // Base de datos no disponible, usar datos de sesión
    $USER = $_SESSION['USER'];
} else {
    // Usuario no logueado
    $USER = new stdClass();
    $USER->id = 0;
}

// ============================================
// PASO 9: Inicializar sistema de idiomas
// ============================================

// Determinar idioma
$currentlang = 'es'; // Idioma por defecto

// Si hay usuario logueado, usar su idioma preferido
if ($CFG->installed && $DB !== null && isset($USER->id) && $USER->id > 0) {
    if (isset($USER->lang) && !empty($USER->lang)) {
        $currentlang = $USER->lang;
    }
}

// Permitir override por parámetro URL (útil para testing)
if (isset($_GET['lang'])) {
    $lang_param = clean_param($_GET['lang'], 'alphanumext');
    if (in_array($lang_param, ['es', 'en'])) {
        $currentlang = $lang_param;
    }
}

// Configurar idioma en string_manager
\core\string_manager::set_language($currentlang);

// Mantener compatibilidad con código antiguo
global $LANG;
$LANG = [];

// ============================================
// PASO 10: Verificar si hay actualizaciones pendientes
// ============================================
// Similar a Moodle: verifica si la versión del código es mayor que la versión en BD
// y redirige a /admin/upgrade.php si es necesario

if ($CFG->installed && $DB !== null) {
    // Solo verificar si no estamos ya en upgrade, instalador, o páginas públicas
    $uri = $_SERVER['REQUEST_URI'] ?? '';

    $skip_upgrade_check = (
        str_contains($uri, '/install') ||
        str_contains($uri, '/admin/upgrade.php') ||
        str_contains($uri, '/login') ||
        str_contains($uri, '/logout') ||
        str_contains($uri, '/theme/') // Assets de themes
    );

    // NOTE: Upgrade detection is now handled in public_html/index.php
    // using environment_checker BEFORE loading this file.
    // This ensures upgrade happens regardless of login status.
    //
    // We only mark upgrade_pending flag here for templates to show notifications.

    if (!$skip_upgrade_check) {
        require_once(__DIR__ . '/upgrade.php');

        if (core_upgrade_required()) {
            // Mark that upgrade is pending (for notifications in templates)
            $CFG->upgrade_pending = true;
        }
    }
}

// ============================================
// FUNCIONES HELPER DE SETUP
// ============================================

/**
 * Cargar variables de entorno desde archivo .env
 *
 * @param string $filepath
 * @return void
 */
function load_environment(string $filepath): void {
    if (!file_exists($filepath)) {
        return;
    }

    $lines = file($filepath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        // Ignorar comentarios
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Parsear línea
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);

            $name = trim($name);
            $value = trim($value);

            // Remover comillas
            $value = trim($value, '"\'');

            // Establecer variable de entorno
            putenv("$name=$value");
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

/**
 * Construir DSN para PDO
 *
 * @param string $driver
 * @param string $host
 * @param string $dbname
 * @return string
 */
function build_dsn(string $driver, string $host, string $dbname): string {
    switch ($driver) {
        case 'mysql':
            return "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

        case 'pgsql':
            return "pgsql:host=$host;dbname=$dbname";

        case 'sqlite':
            return "sqlite:$dbname";

        default:
            throw new coding_exception("Unsupported database driver: $driver");
    }
}
