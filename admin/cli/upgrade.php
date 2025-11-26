<?php
/**
 * CLI Upgrade Script
 *
 * Command-line upgrade for NexoSupport.
 * Similar to Moodle's admin/cli/upgrade.php
 *
 * Usage:
 *   php admin/cli/upgrade.php [options]
 *
 * Options:
 *   --non-interactive      Run without prompts
 *   --allow-unstable       Allow unstable versions
 *   --verbose              Show detailed output
 *   --lang=LANG            Output language
 *   -h, --help             Show help
 *
 * @package    core
 * @copyright  NexoSupport
 * @license    Proprietary - NexoSupport
 */

define('CLI_SCRIPT', true);

// Must be run from command line
if (isset($_SERVER['REMOTE_ADDR'])) {
    die('This script must be run from the command line.');
}

// Load configuration
require_once(dirname(dirname(__DIR__)) . '/config.php');
require_once($CFG->dirroot . '/lib/upgradelib.php');
require_once($CFG->dirroot . '/lib/environmentlib.php');

// Parse command line options
$longopts = [
    'non-interactive',
    'allow-unstable',
    'verbose',
    'lang:',
    'help',
];

$options = getopt('hv', $longopts);

// Show help
if (isset($options['h']) || isset($options['help'])) {
    cli_show_help();
    exit(0);
}

$interactive = !isset($options['non-interactive']);
$verbose = isset($options['verbose']) || isset($options['v']);
$allowunstable = isset($options['allow-unstable']);

// Welcome message
cli_heading("NexoSupport CLI Upgrade");
cli_writeln("=======================================");
cli_writeln("");

// Check if upgrade is already running
if (upgrade_is_running()) {
    cli_error("An upgrade is already in progress.");
    cli_writeln("If you are sure no upgrade is running, wait for the timeout to expire");
    cli_writeln("or manually clear the 'upgraderunning' config value.");
    exit(1);
}

// Get version information
$currentversion = upgrade_get_current_version();
$targetversion = upgrade_get_target_version();

// Get release info from version.php
$plugin = new stdClass();
require_once($CFG->dirroot . '/lib/version.php');
$targetrelease = $plugin->release ?? '';
$targetmaturity = $plugin->maturity ?? MATURITY_STABLE;

// Display version info
cli_writeln("Current version: " . ($currentversion ?: 'Not installed'));
cli_writeln("Target version:  $targetversion ($targetrelease)");
cli_writeln("");

// Check if upgrade is needed
if (!upgrade_is_needed()) {
    cli_writeln("NexoSupport is already up to date.");
    cli_writeln("No upgrade required.");
    exit(0);
}

// Check for unstable version
if ($targetmaturity < MATURITY_STABLE && !$allowunstable) {
    cli_error("Target version is not stable (maturity: $targetmaturity).");
    cli_writeln("Use --allow-unstable to proceed anyway.");
    exit(1);
}

// Check system requirements
cli_writeln("Checking system requirements...");
list($envstatus, $envresults) = check_nexosupport_environment(normalize_version($targetrelease));

if (!$envstatus) {
    cli_error("System requirements not met:");
    foreach ($envresults as $result) {
        if ($result['status'] !== ENVIRONMENT_PASS) {
            $icon = $result['status'] === ENVIRONMENT_FAIL ? '[FAIL]' : '[WARN]';
            cli_writeln("  $icon {$result['name']}: {$result['current']} (required: {$result['info']})");
        }
    }

    // Only fail on required items
    $hasfail = false;
    foreach ($envresults as $result) {
        if ($result['status'] === ENVIRONMENT_FAIL) {
            $hasfail = true;
            break;
        }
    }

    if ($hasfail) {
        exit(1);
    }
}
cli_writeln("System requirements OK.");
cli_writeln("");

// Check plugin dependencies
cli_writeln("Checking plugin dependencies...");
$failed = [];
if (!all_plugins_ok($targetversion, $failed)) {
    cli_error("Plugin dependency check failed:");
    foreach ($failed as $f) {
        cli_writeln("  - {$f['plugin']} requires {$f['dependency']} >= {$f['required']}");
    }
    exit(1);
}
cli_writeln("Plugin dependencies OK.");
cli_writeln("");

// Confirm upgrade
if ($interactive) {
    cli_writeln("This will upgrade NexoSupport from version $currentversion to $targetversion.");
    cli_writeln("");
    $confirm = cli_input("Do you want to proceed? (y/n): ");

    if (strtolower($confirm) !== 'y') {
        cli_writeln("Upgrade cancelled.");
        exit(0);
    }
}

// Execute upgrade
cli_writeln("");
cli_heading("Starting upgrade...");
cli_writeln("");

try {
    // Mark upgrade as started
    upgrade_started(600); // 10 minute timeout

    // Disable caches
    define('CACHE_DISABLE_ALL', true);

    // Step 1: Purge caches
    if ($verbose) {
        cli_writeln("[1/5] Purging caches...");
    }
    purge_all_caches();

    // Step 2: Run pre-upgrade script
    $preupgradefile = $CFG->dirroot . '/local/preupgrade.php';
    if (file_exists($preupgradefile)) {
        if ($verbose) {
            cli_writeln("[2/5] Running pre-upgrade script...");
        }
        require($preupgradefile);
    } elseif ($verbose) {
        cli_writeln("[2/5] No pre-upgrade script found, skipping...");
    }

    // Step 3: Upgrade core
    if ($verbose) {
        cli_writeln("[3/5] Upgrading core...");
    }

    require_once($CFG->dirroot . '/lib/db/upgrade.php');

    // Execute upgrade function
    $result = xmldb_core_upgrade($currentversion);

    if (!$result) {
        throw new Exception("Core upgrade function returned false");
    }

    // Save new version
    upgrade_main_savepoint(true, $targetversion);

    if ($verbose) {
        cli_writeln("      Core upgraded to $targetversion");
    }

    // Step 4: Upgrade plugins
    if ($verbose) {
        cli_writeln("[4/5] Upgrading plugins...");
    }

    $startcb = function($component, $install, $verbose) {
        if ($verbose) {
            $action = $install ? 'Installing' : 'Upgrading';
            cli_writeln("      $action $component...");
        }
    };

    $endcb = function($component, $install, $verbose) {
        // Nothing
    };

    upgrade_noncore($verbose);

    // Step 5: Finalize
    if ($verbose) {
        cli_writeln("[5/5] Finalizing upgrade...");
    }

    // Purge caches again
    purge_all_caches();

    // Mark upgrade as finished
    upgrade_finished();

    // Success!
    cli_writeln("");
    cli_heading("Upgrade Complete!");
    cli_writeln("=======================================");
    cli_writeln("");
    cli_writeln("NexoSupport has been successfully upgraded to version $targetversion ($targetrelease).");
    cli_writeln("");

} catch (Exception $e) {
    // Mark upgrade as finished (so it can be retried)
    upgrade_finished();

    cli_error("Upgrade failed: " . $e->getMessage());

    if ($verbose) {
        cli_writeln("");
        cli_writeln("Stack trace:");
        cli_writeln($e->getTraceAsString());
    }

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
NexoSupport CLI Upgrade

Usage:
  php admin/cli/upgrade.php [options]

Options:
  --non-interactive      Run without prompts
  --allow-unstable       Allow upgrading to unstable versions
  --verbose, -v          Show detailed output
  --lang=LANG            Output language (default: es)
  -h, --help             Show this help

Description:
  This script performs an upgrade of NexoSupport from the command line.
  It will:
    1. Check system requirements
    2. Check plugin dependencies
    3. Run database migrations
    4. Upgrade core and plugins
    5. Purge caches

Example:
  # Interactive upgrade
  php admin/cli/upgrade.php

  # Non-interactive upgrade with verbose output
  php admin/cli/upgrade.php --non-interactive --verbose

  # Allow unstable version
  php admin/cli/upgrade.php --allow-unstable

Notes:
  - Make sure to backup your database before upgrading
  - Put the site in maintenance mode before upgrading
  - After upgrade, clear browser cache

HELP;
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
function cli_input($prompt, $default = '') {
    echo $prompt;
    $input = trim(fgets(STDIN));
    return ($input !== '') ? $input : $default;
}
