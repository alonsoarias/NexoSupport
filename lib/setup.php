<?php
/**
 * NexoSupport System Setup
 *
 * Main initialization file that sets up the entire system.
 * This file should be included at the start of every request.
 *
 * Following Moodle's lib/setup.php pattern.
 *
 * @package NexoSupport
 */

// Security check - prevent direct access
defined('NEXOSUPPORT_INTERNAL') || die();

// ============================================
// STEP 1: DEFINE BASE CONSTANTS
// ============================================

// Base directory (should be defined in config.php, but ensure it exists)
if (!defined('BASE_DIR')) {
    define('BASE_DIR', dirname(__DIR__));
}

// ============================================
// STEP 2: MATURITY LEVEL CONSTANTS
// ============================================

if (!defined('MATURITY_ALPHA')) {
    define('MATURITY_ALPHA', 50);
}
if (!defined('MATURITY_BETA')) {
    define('MATURITY_BETA', 100);
}
if (!defined('MATURITY_RC')) {
    define('MATURITY_RC', 150);
}
if (!defined('MATURITY_STABLE')) {
    define('MATURITY_STABLE', 200);
}

// ============================================
// STEP 3: DEBUG LEVEL CONSTANTS
// ============================================

if (!defined('DEBUG_NONE')) {
    define('DEBUG_NONE', 0);
}
if (!defined('DEBUG_MINIMAL')) {
    define('DEBUG_MINIMAL', E_ERROR | E_PARSE);
}
if (!defined('DEBUG_NORMAL')) {
    define('DEBUG_NORMAL', E_ERROR | E_PARSE | E_WARNING);
}
if (!defined('DEBUG_ALL')) {
    define('DEBUG_ALL', E_ALL);
}
if (!defined('DEBUG_DEVELOPER')) {
    define('DEBUG_DEVELOPER', E_ALL | E_STRICT);
}

// ============================================
// STEP 4: DATABASE CONSTANTS
// ============================================

// Strictness constants for get_record functions
if (!defined('IGNORE_MISSING')) {
    define('IGNORE_MISSING', 0);
}
if (!defined('IGNORE_MULTIPLE')) {
    define('IGNORE_MULTIPLE', 1);
}
if (!defined('MUST_EXIST')) {
    define('MUST_EXIST', 2);
}

// SQL parameter types
if (!defined('SQL_PARAMS_QM')) {
    define('SQL_PARAMS_QM', 1);  // Question mark placeholders
}
if (!defined('SQL_PARAMS_NAMED')) {
    define('SQL_PARAMS_NAMED', 2);  // Named placeholders
}

// ============================================
// STEP 5: CONTEXT LEVEL CONSTANTS
// ============================================

if (!defined('CONTEXT_SYSTEM')) {
    define('CONTEXT_SYSTEM', 10);
}
if (!defined('CONTEXT_USER')) {
    define('CONTEXT_USER', 30);
}
if (!defined('CONTEXT_ROLEASSIGN')) {
    define('CONTEXT_ROLEASSIGN', 40);
}

// ============================================
// STEP 6: LOAD COMPOSER AUTOLOADER
// ============================================

$autoloader = BASE_DIR . '/vendor/autoload.php';
if (file_exists($autoloader)) {
    require_once($autoloader);
} else {
    die('Composer autoloader not found. Run: composer install');
}

// ============================================
// STEP 7: LOAD PARAMETER DEFINITIONS
// ============================================

require_once(__DIR__ . '/params.php');

// ============================================
// STEP 8: INITIALIZE $CFG GLOBAL
// ============================================

global $CFG;
$CFG = new stdClass();

// Set directory paths
$CFG->dirroot = BASE_DIR;
$CFG->libdir = BASE_DIR . '/lib';
$CFG->dataroot = BASE_DIR . '/var';
$CFG->cachedir = BASE_DIR . '/var/cache';
$CFG->tempdir = BASE_DIR . '/var/temp';
$CFG->localcachedir = BASE_DIR . '/var/localcache';
$CFG->sessionsdir = BASE_DIR . '/var/sessions';

// ============================================
// STEP 9: PARSE .ENV FILE
// ============================================

$envfile = BASE_DIR . '/.env';
if (file_exists($envfile)) {
    $envContent = file_get_contents($envfile);
    $lines = explode("\n", $envContent);

    foreach ($lines as $line) {
        $line = trim($line);

        // Skip empty lines and comments
        if (empty($line) || $line[0] === '#') {
            continue;
        }

        // Parse KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Remove quotes from value
            if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                $value = substr($value, 1, -1);
            }

            // Set in environment
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

// ============================================
// STEP 10: CONFIGURE $CFG FROM ENVIRONMENT
// ============================================

// Application settings
$CFG->wwwroot = $_ENV['APP_URL'] ?? 'http://localhost';
$CFG->sitename = $_ENV['APP_NAME'] ?? 'NexoSupport';
$CFG->debug = ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
$CFG->environment = $_ENV['APP_ENV'] ?? 'production';

// Debug settings
if ($CFG->debug) {
    $CFG->debuglevel = DEBUG_ALL;
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    $CFG->debuglevel = DEBUG_NONE;
    error_reporting(0);
    ini_set('display_errors', '0');
}

// Database settings (support both DB_DRIVER and DB_CONNECTION)
$CFG->dbdriver = $_ENV['DB_DRIVER'] ?? $_ENV['DB_CONNECTION'] ?? 'mysql';
$CFG->dbhost = $_ENV['DB_HOST'] ?? 'localhost';
$CFG->dbport = $_ENV['DB_PORT'] ?? '3306';
$CFG->dbname = $_ENV['DB_DATABASE'] ?? 'nexosupport';
$CFG->dbuser = $_ENV['DB_USERNAME'] ?? 'root';
$CFG->dbpass = $_ENV['DB_PASSWORD'] ?? '';
$CFG->dbprefix = $_ENV['DB_PREFIX'] ?? 'nxs_';
$CFG->dbcharset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';

// Session settings
$CFG->sessiontimeout = (int)($_ENV['SESSION_LIFETIME'] ?? 7200);
$CFG->sessioncookiesecure = ($_ENV['SESSION_SECURE'] ?? 'false') === 'true';

// Default language
$CFG->lang = $_ENV['APP_LANG'] ?? 'es';

// Default authentication method
$CFG->auth = 'manual';

// ============================================
// STEP 11: LOAD CORE LIBRARIES
// ============================================

require_once(__DIR__ . '/functions.php');

// ============================================
// STEP 12: INITIALIZE DATABASE CONNECTION
// ============================================

global $DB;
$DB = null;

// Only initialize database if .env exists and has database config
if (!empty($CFG->dbhost) && !empty($CFG->dbname)) {
    try {
        $dsn = sprintf(
            '%s:host=%s;port=%s;dbname=%s;charset=%s',
            $CFG->dbdriver,
            $CFG->dbhost,
            $CFG->dbport,
            $CFG->dbname,
            $CFG->dbcharset
        );

        $pdo = new PDO($dsn, $CFG->dbuser, $CFG->dbpass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        $DB = new \core\db\database($pdo, $CFG->dbprefix, $CFG->dbdriver);

    } catch (PDOException $e) {
        // Database not available - this is OK during installation
        if ($CFG->debug) {
            error_log('NexoSupport DB Error: ' . $e->getMessage());
        }
        $DB = null;
    }
}

// ============================================
// STEP 13: LOAD ADDITIONAL LIBRARIES
// ============================================

require_once(__DIR__ . '/authlib.php');
require_once(__DIR__ . '/userlib.php');

// ============================================
// STEP 14: INITIALIZE SESSION
// ============================================

if (session_status() === PHP_SESSION_NONE) {
    // Configure session
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_samesite', 'Lax');

    if ($CFG->sessioncookiesecure) {
        ini_set('session.cookie_secure', '1');
    }

    // Set session save path if configured
    if (!empty($CFG->sessionsdir) && is_dir($CFG->sessionsdir)) {
        ini_set('session.save_path', $CFG->sessionsdir);
    }

    // Start session
    session_start();
}

// ============================================
// STEP 15: INITIALIZE $USER GLOBAL
// ============================================

global $USER;

// Check if user is logged in (session has user id)
if (isset($_SESSION['USER']) && isset($_SESSION['USER']->id) && $_SESSION['USER']->id > 0) {
    $USER = $_SESSION['USER'];

    // Optionally refresh user data from database
    if ($DB !== null) {
        try {
            $freshuser = $DB->get_record('users', ['id' => $USER->id, 'deleted' => 0]);
            if ($freshuser) {
                // Update session with fresh data but keep session-specific data
                $sessiondata = isset($USER->sesskey) ? $USER->sesskey : null;
                $USER = $freshuser;
                if ($sessiondata) {
                    $USER->sesskey = $sessiondata;
                }
                $_SESSION['USER'] = $USER;
            } else {
                // User no longer exists or is deleted, clear session
                $USER = new stdClass();
                $USER->id = 0;
                $USER->guest = true;
                unset($_SESSION['USER']);
            }
        } catch (Exception $e) {
            // Keep existing session data if DB error
        }
    }
} else {
    // Guest user
    $USER = new stdClass();
    $USER->id = 0;
    $USER->guest = true;
    $USER->username = 'guest';
    $USER->firstname = 'Guest';
    $USER->lastname = 'User';
    $USER->email = '';
}

// Ensure sesskey exists
if (!isset($USER->sesskey)) {
    $USER->sesskey = random_string(20);
}

// ============================================
// STEP 16: INITIALIZE $PAGE GLOBAL (LAZY)
// ============================================

global $PAGE;
$PAGE = null;

// $PAGE will be initialized on first use via get_page() function

// ============================================
// STEP 17: INITIALIZE $OUTPUT GLOBAL (LAZY)
// ============================================

global $OUTPUT;
$OUTPUT = null;

// $OUTPUT will be initialized on first use via get_output() function

// ============================================
// STEP 18: INITIALIZE $ADMIN GLOBAL (LAZY)
// ============================================

global $ADMIN;
$ADMIN = null;

// $ADMIN will be initialized when admin tree is needed

// ============================================
// STEP 19: SET TIMEZONE
// ============================================

$timezone = $_ENV['APP_TIMEZONE'] ?? 'America/Bogota';
date_default_timezone_set($timezone);

// ============================================
// STEP 20: SETUP COMPLETE
// ============================================

// System is now initialized
// Available globals: $CFG, $DB, $USER, $PAGE, $OUTPUT, $ADMIN
