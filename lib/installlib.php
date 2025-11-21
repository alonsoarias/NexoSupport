<?php
/**
 * Installation Library Functions
 *
 * Core functions for system installation, similar to Moodle's lib/installlib.php
 *
 * This file contains the main functions used during installation:
 * - install_core() - Main installation function
 * - install_from_xmldb_file() - Schema installation
 * - Environment validation helpers
 *
 * @package    core
 * @copyright  NexoSupport
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('NEXOSUPPORT_INTERNAL') || die();

// Installation phases (similar to Moodle)
define('INSTALL_WELCOME', 0);
define('INSTALL_ENVIRONMENT', 1);
define('INSTALL_PATHS', 2);
define('INSTALL_DOWNLOADLANG', 3);
define('INSTALL_DATABASETYPE', 4);
define('INSTALL_DATABASE', 5);
define('INSTALL_SAVE', 6);

/**
 * Install the core of NexoSupport completely
 *
 * This is the main installation function, similar to Moodle's install_core().
 * It creates the database schema and initializes the system.
 *
 * @param float $version Version to install
 * @param bool $verbose Display progress messages
 * @return bool True on success
 */
function install_core($version, $verbose = true) {
    global $CFG, $DB;

    raise_memory_limit(MEMORY_EXTRA);

    try {
        // 1. Clean directories
        if ($verbose) {
            install_progress('Preparing directories...', 5);
        }
        install_init_directories();

        // 2. Install database schema from XMLDB
        if ($verbose) {
            install_progress('Installing database schema...', 20);
        }
        $schemafile = $CFG->dirroot . '/lib/db/install.xml';
        if (!install_from_xmldb_file($schemafile)) {
            throw new install_exception('Failed to install database schema');
        }

        // 3. Execute post-installation
        if ($verbose) {
            install_progress('Running post-installation...', 50);
        }
        require_once($CFG->dirroot . '/lib/db/install.php');
        if (!xmldb_main_install()) {
            throw new install_exception('Post-installation failed');
        }

        // 4. Save version
        if ($verbose) {
            install_progress('Saving version information...', 70);
        }
        install_save_version($version);

        // 5. Apply default settings
        if ($verbose) {
            install_progress('Applying default settings...', 85);
        }
        install_apply_default_settings();

        // 6. Purge caches
        if ($verbose) {
            install_progress('Finalizing installation...', 95);
        }
        purge_all_caches();

        if ($verbose) {
            install_progress('Installation complete!', 100);
        }

        return true;

    } catch (Exception $ex) {
        install_handle_exception($ex);
        return false;
    }
}

/**
 * Initialize required directories
 *
 * Creates cache, temp, and localcache directories if they don't exist.
 *
 * @return void
 */
function install_init_directories() {
    global $CFG;

    $dirs = [
        'cachedir' => $CFG->dataroot . '/cache',
        'localcachedir' => $CFG->dataroot . '/localcache',
        'tempdir' => $CFG->dataroot . '/temp',
        'sessdir' => $CFG->dataroot . '/sessions',
        'filedir' => $CFG->dataroot . '/filedir',
    ];

    foreach ($dirs as $name => $dir) {
        if (!file_exists($dir)) {
            @mkdir($dir, 0755, true);
        }

        // Ensure directory is writable
        if (!is_writable($dir)) {
            throw new install_exception("Directory not writable: $dir");
        }

        // Set in CFG
        $CFG->$name = $dir;
    }
}

/**
 * Install database schema from XMLDB file
 *
 * Parses the install.xml file and creates all tables.
 *
 * @param string $filepath Path to install.xml
 * @return bool True on success
 */
function install_from_xmldb_file($filepath) {
    global $DB;

    if (!file_exists($filepath)) {
        throw new install_exception("Schema file not found: $filepath");
    }

    $dbman = $DB->get_manager();
    return $dbman->install_from_xmldb_file($filepath);
}

/**
 * Save version information to database
 *
 * @param float $version Version number
 * @return void
 */
function install_save_version($version) {
    global $CFG, $DB;

    // Get release info from version.php if available
    $release = '';
    $versionfile = $CFG->dirroot . '/lib/version.php';
    if (file_exists($versionfile)) {
        $plugin = new stdClass();
        include($versionfile);
        $release = $plugin->release ?? '';
    }

    // Save version
    set_config('version', $version);
    set_config('release', $release);
    set_config('branch', substr($version, 0, 6)); // YYYYMM

    debugging("Version $version ($release) saved to database", DEBUG_DEVELOPER);
}

/**
 * Apply default settings
 *
 * Applies default admin settings after installation.
 *
 * @return void
 */
function install_apply_default_settings() {
    global $CFG;

    // Load admin tree if available
    if (function_exists('admin_apply_default_settings')) {
        admin_apply_default_settings(null, true);
    }

    debugging('Default settings applied', DEBUG_DEVELOPER);
}

/**
 * Display installation progress
 *
 * @param string $message Progress message
 * @param int $percent Percentage complete (0-100)
 * @return void
 */
function install_progress($message, $percent = null) {
    static $started = false;

    if (!$started) {
        echo '<div class="install-progress">';
        $started = true;
    }

    echo '<div class="progress-item">';
    if ($percent !== null) {
        echo '<span class="progress-percent">[' . $percent . '%]</span> ';
    }
    echo '<span class="progress-message">' . htmlspecialchars($message) . '</span>';
    echo '</div>' . "\n";

    // Flush output
    if (ob_get_level() > 0) {
        ob_flush();
    }
    flush();
}

/**
 * Handle installation exceptions
 *
 * @param Exception $ex The exception
 * @return void
 */
function install_handle_exception($ex) {
    global $CFG;

    echo '<div class="install-error" style="background: #fee; border: 2px solid #c33; padding: 20px; margin: 20px 0;">';
    echo '<h3 style="color: #c33; margin-top: 0;">Installation Error</h3>';
    echo '<p><strong>Message:</strong> ' . htmlspecialchars($ex->getMessage()) . '</p>';

    if (!empty($CFG->debug)) {
        echo '<details>';
        echo '<summary>Stack Trace</summary>';
        echo '<pre style="background: #f5f5f5; padding: 10px; overflow: auto;">';
        echo htmlspecialchars($ex->getTraceAsString());
        echo '</pre>';
        echo '</details>';
    }

    echo '</div>';

    debugging('Installation error: ' . $ex->getMessage(), DEBUG_DEVELOPER);
}

/**
 * Check system requirements
 *
 * Validates that the server meets all requirements for installation.
 *
 * @return array Array with 'status' (bool) and 'results' (array of checks)
 */
function install_check_requirements() {
    $results = [];
    $status = true;

    // PHP Version check
    $phpversion = '8.1.0';
    $check = [
        'name' => 'PHP Version',
        'required' => $phpversion,
        'current' => PHP_VERSION,
        'status' => version_compare(PHP_VERSION, $phpversion, '>=')
    ];
    if (!$check['status']) {
        $status = false;
    }
    $results[] = $check;

    // Required extensions
    $extensions = [
        'pdo' => 'PDO Database Extension',
        'pdo_mysql' => 'PDO MySQL Driver (or pdo_pgsql)',
        'json' => 'JSON Extension',
        'mbstring' => 'Multibyte String Extension',
        'session' => 'Session Extension',
        'ctype' => 'Ctype Extension',
        'fileinfo' => 'Fileinfo Extension',
        'openssl' => 'OpenSSL Extension',
        'curl' => 'cURL Extension',
    ];

    foreach ($extensions as $ext => $name) {
        // Special case for database driver
        if ($ext === 'pdo_mysql') {
            $loaded = extension_loaded('pdo_mysql') || extension_loaded('pdo_pgsql');
        } else {
            $loaded = extension_loaded($ext);
        }

        $check = [
            'name' => $name,
            'required' => 'Loaded',
            'current' => $loaded ? 'Loaded' : 'Not loaded',
            'status' => $loaded
        ];

        if (!$check['status'] && $ext !== 'pdo_mysql') {
            $status = false;
        }
        $results[] = $check;
    }

    // Memory limit
    $minmemory = '128M';
    $memory = ini_get('memory_limit');
    $memorybytes = install_convert_to_bytes($memory);
    $minbytes = install_convert_to_bytes($minmemory);

    $check = [
        'name' => 'Memory Limit',
        'required' => $minmemory,
        'current' => $memory,
        'status' => ($memorybytes >= $minbytes) || ($memorybytes == -1)
    ];
    if (!$check['status']) {
        $status = false;
    }
    $results[] = $check;

    // Upload max filesize
    $uploadmax = ini_get('upload_max_filesize');
    $check = [
        'name' => 'Upload Max Filesize',
        'required' => '>= 8M recommended',
        'current' => $uploadmax,
        'status' => true // Warning only
    ];
    $results[] = $check;

    return [
        'status' => $status,
        'results' => $results
    ];
}

/**
 * Convert memory string to bytes
 *
 * @param string $val Memory value with suffix (K, M, G)
 * @return int Bytes
 */
function install_convert_to_bytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val) - 1]);
    $val = (int)$val;

    switch ($last) {
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }

    return $val;
}

/**
 * Validate database configuration
 *
 * @param array $config Database configuration array
 * @return array Array with 'valid' (bool) and 'errors' (array)
 */
function install_validate_database_config($config) {
    $errors = [];

    // Required fields
    $required = ['dbtype', 'dbhost', 'dbname', 'dbuser'];
    foreach ($required as $field) {
        if (empty($config[$field])) {
            $errors[] = "Missing required field: $field";
        }
    }

    // Validate dbtype
    $valid_types = ['mysqli', 'pgsql', 'mariadb'];
    if (!empty($config['dbtype']) && !in_array($config['dbtype'], $valid_types)) {
        $errors[] = "Invalid database type: {$config['dbtype']}";
    }

    // Validate prefix (optional)
    if (!empty($config['prefix'])) {
        if (!preg_match('/^[a-z][a-z0-9_]*$/i', $config['prefix'])) {
            $errors[] = "Invalid table prefix: must start with letter and contain only alphanumeric and underscore";
        }
    }

    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Test database connection
 *
 * @param array $config Database configuration
 * @return array Array with 'success' (bool) and 'message' (string)
 */
function install_test_database_connection($config) {
    try {
        $dsn = install_build_dsn($config);
        $pdo = new PDO($dsn, $config['dbuser'], $config['dbpass'] ?? '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Test with simple query
        $pdo->query('SELECT 1');

        return [
            'success' => true,
            'message' => 'Database connection successful'
        ];

    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => 'Connection failed: ' . $e->getMessage()
        ];
    }
}

/**
 * Build PDO DSN string
 *
 * @param array $config Database configuration
 * @return string DSN string
 */
function install_build_dsn($config) {
    $driver = $config['dbtype'];
    $host = $config['dbhost'];
    $dbname = $config['dbname'];
    $port = $config['dbport'] ?? '';

    // Convert mysqli to mysql for PDO
    if ($driver === 'mysqli') {
        $driver = 'mysql';
    }

    $dsn = "{$driver}:host={$host};dbname={$dbname}";

    if (!empty($port)) {
        $dsn .= ";port={$port}";
    }

    // Add charset
    if ($driver === 'mysql' || $driver === 'mariadb') {
        $dsn .= ';charset=utf8mb4';
    }

    return $dsn;
}

/**
 * Generate config.php content (or .env content for NexoSupport)
 *
 * Since NexoSupport uses .env instead of config.php,
 * this generates .env content.
 *
 * @param array $config Configuration array
 * @return string .env file content
 */
function install_generate_env_file($config) {
    $content = "# NexoSupport Environment Configuration\n";
    $content .= "# Generated: " . date('Y-m-d H:i:s') . "\n\n";

    // Database settings
    $content .= "# Database Configuration\n";
    $content .= "DB_CONNECTION=" . ($config['dbtype'] ?? 'mysql') . "\n";
    $content .= "DB_HOST=" . ($config['dbhost'] ?? 'localhost') . "\n";
    $content .= "DB_PORT=" . ($config['dbport'] ?? '3306') . "\n";
    $content .= "DB_DATABASE=" . ($config['dbname'] ?? 'nexosupport') . "\n";
    $content .= "DB_USERNAME=" . ($config['dbuser'] ?? 'root') . "\n";
    $content .= "DB_PASSWORD=" . ($config['dbpass'] ?? '') . "\n";
    $content .= "DB_PREFIX=" . ($config['prefix'] ?? 'ns_') . "\n\n";

    // Site settings
    $content .= "# Site Configuration\n";
    $content .= "WWWROOT=" . ($config['wwwroot'] ?? 'http://localhost') . "\n";
    $content .= "DATAROOT=" . ($config['dataroot'] ?? '/var/nexodata') . "\n\n";

    // Security
    $content .= "# Security\n";
    $content .= "APP_DEBUG=" . ($config['debug'] ?? 'false') . "\n";

    return $content;
}

/**
 * Save .env configuration file
 *
 * @param string $filepath Path to save .env file
 * @param array $config Configuration array
 * @return bool True on success
 */
function install_save_env_file($filepath, $config) {
    $content = install_generate_env_file($config);

    $result = file_put_contents($filepath, $content);

    if ($result === false) {
        throw new install_exception("Failed to write .env file: $filepath");
    }

    // Set permissions (owner read/write only)
    chmod($filepath, 0600);

    return true;
}

/**
 * Raise memory limit
 *
 * @param string $newlimit New memory limit (e.g., MEMORY_EXTRA)
 * @return void
 */
function raise_memory_limit($newlimit) {
    if ($newlimit === MEMORY_EXTRA || $newlimit === MEMORY_HUGE) {
        if ($newlimit === MEMORY_HUGE) {
            @ini_set('memory_limit', '512M');
        } else {
            @ini_set('memory_limit', '256M');
        }
    } elseif (is_numeric($newlimit)) {
        @ini_set('memory_limit', $newlimit . 'M');
    }
}

// Memory limit constants (if not defined)
if (!defined('MEMORY_STANDARD')) {
    define('MEMORY_STANDARD', '128M');
}
if (!defined('MEMORY_EXTRA')) {
    define('MEMORY_EXTRA', '256M');
}
if (!defined('MEMORY_HUGE')) {
    define('MEMORY_HUGE', '512M');
}

/**
 * Installation exception class
 */
class install_exception extends Exception {
}
