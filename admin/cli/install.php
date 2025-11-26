<?php
/**
 * CLI Installation Script
 *
 * Command-line installation for NexoSupport.
 * Similar to Moodle's admin/cli/install.php
 *
 * Usage:
 *   php admin/cli/install.php [options]
 *
 * Options:
 *   --lang=es              Language (default: es)
 *   --wwwroot=URL          Full URL of the site
 *   --dataroot=PATH        Path to data directory
 *   --dbtype=TYPE          Database type: mysqli, pgsql
 *   --dbhost=HOST          Database host
 *   --dbport=PORT          Database port
 *   --dbname=NAME          Database name
 *   --dbuser=USER          Database user
 *   --dbpass=PASS          Database password
 *   --prefix=PREFIX        Table prefix (default: ns_)
 *   --fullname=NAME        Site full name
 *   --shortname=NAME       Site short name
 *   --adminuser=USER       Admin username (default: admin)
 *   --adminpass=PASS       Admin password
 *   --adminemail=EMAIL     Admin email
 *   --non-interactive      No interactive prompts
 *   --agree-license        Agree to license
 *   --help                 Show this help
 *
 * @package    core
 * @copyright  NexoSupport
 * @license    Proprietary - NexoSupport
 */

define('CLI_SCRIPT', true);
define('NEXOSUPPORT_INTERNAL', true);
define('NEXOSUPPORT_INSTALLING', true);

// Must be run from command line
if (isset($_SERVER['REMOTE_ADDR'])) {
    die('This script must be run from the command line.');
}

// Base directory
define('BASE_DIR', dirname(dirname(__DIR__)));

// Memory and constants
define('MEMORY_STANDARD', '128M');
define('MEMORY_EXTRA', '256M');
define('MEMORY_HUGE', '512M');
define('CONTEXT_SYSTEM', 10);
define('CONTEXT_USER', 30);
define('CONTEXT_COURSE', 50);
define('CONTEXT_MODULE', 70);
define('RISK_CONFIG', 1);
define('RISK_DATALOSS', 2);
define('RISK_PERSONAL', 4);
define('RISK_XSS', 8);
define('RISK_SPAM', 16);

// Load installation library
require_once(BASE_DIR . '/lib/installlib.php');

// Parse command line options
$longopts = [
    'lang:',
    'wwwroot:',
    'dataroot:',
    'dbtype:',
    'dbhost:',
    'dbport:',
    'dbname:',
    'dbuser:',
    'dbpass:',
    'prefix:',
    'fullname:',
    'shortname:',
    'adminuser:',
    'adminpass:',
    'adminemail:',
    'non-interactive',
    'agree-license',
    'skip-database',
    'help',
];

$options = getopt('h', $longopts);

// Show help
if (isset($options['h']) || isset($options['help'])) {
    cli_show_help();
    exit(0);
}

// Installation configuration
$config = [
    'lang' => $options['lang'] ?? 'es',
    'wwwroot' => $options['wwwroot'] ?? '',
    'dataroot' => $options['dataroot'] ?? '',
    'dbtype' => $options['dbtype'] ?? 'mysqli',
    'dbhost' => $options['dbhost'] ?? 'localhost',
    'dbport' => $options['dbport'] ?? '3306',
    'dbname' => $options['dbname'] ?? 'nexosupport',
    'dbuser' => $options['dbuser'] ?? '',
    'dbpass' => $options['dbpass'] ?? '',
    'prefix' => $options['prefix'] ?? 'ns_',
    'sitename' => $options['fullname'] ?? 'NexoSupport',
    'shortname' => $options['shortname'] ?? 'NS',
    'adminuser' => $options['adminuser'] ?? 'admin',
    'adminpass' => $options['adminpass'] ?? '',
    'adminemail' => $options['adminemail'] ?? '',
];

$interactive = !isset($options['non-interactive']);
$agreelicense = isset($options['agree-license']);
$skipdb = isset($options['skip-database']);

// Welcome message
cli_heading("NexoSupport CLI Installation");
cli_writeln("=======================================");
cli_writeln("");

// Check license agreement
if (!$agreelicense && $interactive) {
    cli_writeln("NexoSupport is proprietary software.");
    cli_writeln("You must agree to the license terms to continue.");
    cli_writeln("");

    $agree = cli_input("Do you agree to the license? (y/n): ");
    if (strtolower($agree) !== 'y') {
        cli_error("License not accepted. Installation aborted.");
        exit(1);
    }
} elseif (!$agreelicense) {
    cli_error("You must use --agree-license option in non-interactive mode.");
    exit(1);
}

// Validate and gather configuration
if ($interactive) {
    $config = cli_gather_config($config);
}

// Validate required fields
$errors = cli_validate_config($config);
if (!empty($errors)) {
    cli_error("Configuration errors:");
    foreach ($errors as $error) {
        cli_writeln("  - $error");
    }
    exit(1);
}

// Check system requirements
cli_writeln("");
cli_heading("Checking system requirements...");
$requirements = install_check_requirements();

if (!$requirements['status']) {
    cli_error("System requirements not met:");
    foreach ($requirements['results'] as $req) {
        if (!$req['status']) {
            cli_writeln("  - {$req['name']}: {$req['current']} (required: {$req['required']})");
        }
    }
    exit(1);
}
cli_writeln("All system requirements met.");

// Show configuration summary
cli_writeln("");
cli_heading("Installation Configuration");
cli_writeln("  Site URL:     " . $config['wwwroot']);
cli_writeln("  Data Dir:     " . $config['dataroot']);
cli_writeln("  Database:     " . $config['dbtype'] . "://" . $config['dbhost'] . "/" . $config['dbname']);
cli_writeln("  Table Prefix: " . $config['prefix']);
cli_writeln("  Admin User:   " . $config['adminuser']);
cli_writeln("  Admin Email:  " . $config['adminemail']);
cli_writeln("");

if ($interactive) {
    $confirm = cli_input("Proceed with installation? (y/n): ");
    if (strtolower($confirm) !== 'y') {
        cli_writeln("Installation cancelled.");
        exit(0);
    }
}

// Execute installation
cli_writeln("");
cli_heading("Installing NexoSupport...");

try {
    // Step 1: Create directories
    cli_writeln("[1/7] Creating directories...");
    cli_create_directories($config);
    cli_writeln("      Done.");

    // Step 2: Create .env file
    cli_writeln("[2/7] Creating configuration file...");
    $envfile = BASE_DIR . '/.env';
    install_save_env_file($envfile, $config);
    cli_writeln("      Done.");

    // Step 3: Initialize database connection
    cli_writeln("[3/7] Connecting to database...");

    // Set up global CFG
    global $CFG, $DB;
    $CFG = new stdClass();
    $CFG->dirroot = BASE_DIR;
    $CFG->wwwroot = $config['wwwroot'];
    $CFG->dataroot = $config['dataroot'];
    $CFG->dbtype = $config['dbtype'];
    $CFG->dbhost = $config['dbhost'];
    $CFG->dbport = $config['dbport'];
    $CFG->dbname = $config['dbname'];
    $CFG->dbuser = $config['dbuser'];
    $CFG->dbpass = $config['dbpass'];
    $CFG->prefix = $config['prefix'];
    $CFG->debug = 0;

    require_once(BASE_DIR . '/lib/classes/db/database.php');
    $DB = \core\db\database::get_instance($CFG);
    cli_writeln("      Done.");

    // Step 4: Install database schema
    if (!$skipdb) {
        cli_writeln("[4/7] Installing database schema...");
        $schemafile = BASE_DIR . '/lib/db/install.xml';
        if (file_exists($schemafile)) {
            $dbman = $DB->get_manager();
            $dbman->install_from_xmldb_file($schemafile);
        }
        cli_writeln("      Done.");
    } else {
        cli_writeln("[4/7] Skipping database schema (--skip-database).");
    }

    // Step 5: Get version info and save config
    cli_writeln("[5/7] Saving version information...");
    $plugin = new stdClass();
    require_once(BASE_DIR . '/lib/version.php');
    $CFG->version = $plugin->version;
    $CFG->release = $plugin->release;

    // Insert config records
    try {
        $existingVersion = $DB->get_field('config', 'value', ['name' => 'version']);
    } catch (Exception $e) {
        $existingVersion = null;
    }

    if (!$existingVersion) {
        $DB->insert_record('config', ['name' => 'version', 'value' => $plugin->version]);
        $DB->insert_record('config', ['name' => 'release', 'value' => $plugin->release]);
        $DB->insert_record('config', ['name' => 'sitename', 'value' => $config['sitename']]);
        $DB->insert_record('config', ['name' => 'installed', 'value' => time()]);
    }
    cli_writeln("      Done.");

    // Step 6: Run post-installation
    cli_writeln("[6/7] Running post-installation...");
    $installfile = BASE_DIR . '/lib/db/install.php';
    if (file_exists($installfile)) {
        require_once($installfile);
        if (function_exists('xmldb_main_install')) {
            xmldb_main_install();
        }
    }
    cli_writeln("      Done.");

    // Step 7: Create admin user
    cli_writeln("[7/7] Creating admin user...");
    $adminid = cli_create_admin_user($config, $DB);
    cli_writeln("      Done. Admin user ID: $adminid");

    // Success!
    cli_writeln("");
    cli_heading("Installation Complete!");
    cli_writeln("=======================================");
    cli_writeln("");
    cli_writeln("NexoSupport has been successfully installed.");
    cli_writeln("");
    cli_writeln("Site URL: " . $config['wwwroot']);
    cli_writeln("Admin:    " . $config['adminuser']);
    cli_writeln("");
    cli_writeln("You can now access your site at:");
    cli_writeln("  " . $config['wwwroot'] . "/login");
    cli_writeln("");

} catch (Exception $e) {
    cli_error("Installation failed: " . $e->getMessage());
    exit(1);
}

exit(0);

// ============================================
// CLI HELPER FUNCTIONS
// ============================================

/**
 * Show help message
 */
function cli_show_help() {
    echo <<<HELP
NexoSupport CLI Installation

Usage:
  php admin/cli/install.php [options]

Required Options:
  --wwwroot=URL          Full URL of the site (e.g., https://example.com/nexo)
  --dataroot=PATH        Path to data directory (outside webroot)
  --dbname=NAME          Database name
  --dbuser=USER          Database user
  --adminpass=PASS       Admin password (min 8 characters)
  --adminemail=EMAIL     Admin email address

Optional Options:
  --lang=LANG            Language code (default: es)
  --dbtype=TYPE          Database type: mysqli, pgsql (default: mysqli)
  --dbhost=HOST          Database host (default: localhost)
  --dbport=PORT          Database port (default: 3306)
  --dbpass=PASS          Database password
  --prefix=PREFIX        Table prefix (default: ns_)
  --fullname=NAME        Site full name (default: NexoSupport)
  --shortname=NAME       Site short name (default: NS)
  --adminuser=USER       Admin username (default: admin)

Flags:
  --non-interactive      Run without prompts
  --agree-license        Agree to license terms
  --skip-database        Skip database schema installation
  -h, --help             Show this help

Example:
  php admin/cli/install.php \\
    --wwwroot=https://example.com \\
    --dataroot=/var/nexodata \\
    --dbhost=localhost \\
    --dbname=nexosupport \\
    --dbuser=nexouser \\
    --dbpass=secret123 \\
    --adminuser=admin \\
    --adminpass=Admin123! \\
    --adminemail=admin@example.com \\
    --non-interactive \\
    --agree-license

HELP;
}

/**
 * Gather configuration interactively
 */
function cli_gather_config($config) {
    cli_writeln("Please provide the installation configuration:");
    cli_writeln("");

    // WWWRoot
    if (empty($config['wwwroot'])) {
        $config['wwwroot'] = cli_input("Site URL (e.g., https://example.com): ");
    }

    // Dataroot
    if (empty($config['dataroot'])) {
        $default = dirname(BASE_DIR) . '/nexodata';
        $config['dataroot'] = cli_input("Data directory [$default]: ", $default);
    }

    // Database type
    $config['dbtype'] = cli_input("Database type (mysqli/pgsql) [{$config['dbtype']}]: ", $config['dbtype']);

    // Database host
    $config['dbhost'] = cli_input("Database host [{$config['dbhost']}]: ", $config['dbhost']);

    // Database port
    $defaultport = $config['dbtype'] === 'pgsql' ? '5432' : '3306';
    $config['dbport'] = cli_input("Database port [$defaultport]: ", $defaultport);

    // Database name
    $config['dbname'] = cli_input("Database name [{$config['dbname']}]: ", $config['dbname']);

    // Database user
    $config['dbuser'] = cli_input("Database user: ");

    // Database password
    $config['dbpass'] = cli_input("Database password: ", '', true);

    // Table prefix
    $config['prefix'] = cli_input("Table prefix [{$config['prefix']}]: ", $config['prefix']);

    // Site name
    $config['sitename'] = cli_input("Site name [{$config['sitename']}]: ", $config['sitename']);

    // Admin user
    $config['adminuser'] = cli_input("Admin username [{$config['adminuser']}]: ", $config['adminuser']);

    // Admin password
    if (empty($config['adminpass'])) {
        $config['adminpass'] = cli_input("Admin password (min 8 chars): ", '', true);
    }

    // Admin email
    if (empty($config['adminemail'])) {
        $config['adminemail'] = cli_input("Admin email: ");
    }

    return $config;
}

/**
 * Validate configuration
 */
function cli_validate_config($config) {
    $errors = [];

    if (empty($config['wwwroot'])) {
        $errors[] = "Site URL (wwwroot) is required";
    } elseif (!filter_var($config['wwwroot'], FILTER_VALIDATE_URL)) {
        $errors[] = "Site URL (wwwroot) must be a valid URL";
    }

    if (empty($config['dataroot'])) {
        $errors[] = "Data directory (dataroot) is required";
    }

    if (empty($config['dbname'])) {
        $errors[] = "Database name is required";
    }

    if (empty($config['dbuser'])) {
        $errors[] = "Database user is required";
    }

    if (empty($config['adminpass'])) {
        $errors[] = "Admin password is required";
    } elseif (strlen($config['adminpass']) < 8) {
        $errors[] = "Admin password must be at least 8 characters";
    }

    if (empty($config['adminemail'])) {
        $errors[] = "Admin email is required";
    } elseif (!filter_var($config['adminemail'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Admin email must be valid";
    }

    // Test database connection
    if (empty($errors)) {
        $test = install_test_database_connection($config);
        if (!$test['success']) {
            $errors[] = "Database connection failed: " . $test['message'];
        }
    }

    return $errors;
}

/**
 * Create directories
 */
function cli_create_directories($config) {
    $dirs = [
        $config['dataroot'],
        $config['dataroot'] . '/cache',
        $config['dataroot'] . '/temp',
        $config['dataroot'] . '/sessions',
        $config['dataroot'] . '/filedir',
        $config['dataroot'] . '/localcache',
        $config['dataroot'] . '/trashdir',
    ];

    foreach ($dirs as $dir) {
        if (!file_exists($dir)) {
            if (!@mkdir($dir, 0755, true)) {
                throw new Exception("Failed to create directory: $dir");
            }
        }

        if (!is_writable($dir)) {
            throw new Exception("Directory not writable: $dir");
        }
    }
}

/**
 * Create admin user
 */
function cli_create_admin_user($config, $DB) {
    // Check if user exists
    try {
        $existing = $DB->get_record('users', ['username' => $config['adminuser']]);
        if ($existing) {
            return $existing->id;
        }
    } catch (Exception $e) {
        // Table might not exist
    }

    $user = new stdClass();
    $user->auth = 'manual';
    $user->confirmed = 1;
    $user->username = $config['adminuser'];
    $user->password = password_hash($config['adminpass'], PASSWORD_DEFAULT);
    $user->firstname = 'Admin';
    $user->lastname = 'User';
    $user->email = $config['adminemail'];
    $user->lang = $config['lang'];
    $user->timezone = 'America/Mexico_City';
    $user->timecreated = time();
    $user->timemodified = time();

    try {
        $id = $DB->insert_record('users', $user);

        // Assign admin role
        try {
            $adminrole = $DB->get_record('roles', ['shortname' => 'admin']);
            $syscontext = $DB->get_record('contexts', ['contextlevel' => CONTEXT_SYSTEM, 'instanceid' => 0]);

            if ($adminrole && $syscontext) {
                $DB->insert_record('role_assignments', [
                    'roleid' => $adminrole->id,
                    'contextid' => $syscontext->id,
                    'userid' => $id,
                    'timemodified' => time(),
                    'modifierid' => $id
                ]);
            }
        } catch (Exception $e) {
            // Roles might not be set up
        }

        return $id;
    } catch (Exception $e) {
        throw new Exception("Failed to create admin user: " . $e->getMessage());
    }
}

/**
 * Write line to console
 */
function cli_writeln($message) {
    echo $message . "\n";
}

/**
 * Write heading
 */
function cli_heading($message) {
    echo "\033[1;36m" . $message . "\033[0m\n";
}

/**
 * Write error
 */
function cli_error($message) {
    echo "\033[1;31mError: " . $message . "\033[0m\n";
}

/**
 * Get input from user
 */
function cli_input($prompt, $default = '', $hidden = false) {
    echo $prompt;

    if ($hidden && function_exists('readline')) {
        // Try to hide password input
        system('stty -echo');
        $input = trim(fgets(STDIN));
        system('stty echo');
        echo "\n";
    } else {
        $input = trim(fgets(STDIN));
    }

    return ($input !== '') ? $input : $default;
}
