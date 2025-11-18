<?php
/**
 * TEMPORARY UPGRADE SCRIPT
 *
 * This script runs the system upgrade directly without requiring login.
 * Use this ONLY to fix the upgrade issue where you cannot login because
 * the siteadmins configuration doesn't exist.
 *
 * ⚠️ SECURITY WARNING: This script bypasses all security checks.
 * DELETE THIS FILE after running the upgrade!
 *
 * Usage:
 *   1. Run: php run_upgrade.php
 *   2. Delete this file immediately after
 *
 * @package NexoSupport
 */

// Define constants
define('BASE_DIR', __DIR__);
define('NEXOSUPPORT_INTERNAL', true);
define('CLI_SCRIPT', true); // Bypass session checks

// Load system setup
require_once(BASE_DIR . '/lib/setup.php');

// Check if database is connected
if ($DB === null) {
    echo "ERROR: Could not connect to database\n";
    echo "Please check your .env file and database configuration\n";
    exit(1);
}

echo "═══════════════════════════════════════════════════════\n";
echo "  NexoSupport Manual Upgrade Script\n";
echo "═══════════════════════════════════════════════════════\n\n";

// Load upgrade functions
require_once(__DIR__ . '/lib/upgrade.php');

// Get versions
$dbversion = get_core_version_from_db();
$codeversion = get_core_version_from_code();

echo "Database version: " . ($dbversion ?: 'Not found') . "\n";
echo "Code version: " . $codeversion . "\n\n";

// Check if upgrade is needed
if (!core_upgrade_required()) {
    echo "✓ No upgrade required. System is up to date!\n\n";
    exit(0);
}

echo "⚠️  Upgrade required!\n";
echo "───────────────────────────────────────────────────────\n\n";

// Ask for confirmation
echo "This will upgrade your database from version {$dbversion} to {$codeversion}.\n";
echo "Do you want to continue? (yes/no): ";

$handle = fopen("php://stdin", "r");
$line = trim(fgets($handle));
fclose($handle);

if (strtolower($line) !== 'yes') {
    echo "\nUpgrade cancelled.\n\n";
    exit(0);
}

echo "\n";
echo "═══════════════════════════════════════════════════════\n";
echo "  Starting Upgrade Process\n";
echo "═══════════════════════════════════════════════════════\n\n";

// Execute upgrade
try {
    $result = xmldb_core_upgrade($dbversion ?: 0);

    if ($result) {
        echo "\n";
        echo "═══════════════════════════════════════════════════════\n";
        echo "  ✓ UPGRADE COMPLETED SUCCESSFULLY!\n";
        echo "═══════════════════════════════════════════════════════\n\n";

        // Get new version
        $newversion = get_core_version_from_db();
        echo "New database version: {$newversion}\n\n";

        echo "⚠️  IMPORTANT: DELETE THIS FILE NOW!\n";
        echo "For security reasons, delete run_upgrade.php immediately.\n\n";

        echo "You can now log in to the system normally.\n\n";

        exit(0);
    } else {
        echo "\n";
        echo "═══════════════════════════════════════════════════════\n";
        echo "  ✗ UPGRADE FAILED!\n";
        echo "═══════════════════════════════════════════════════════\n\n";
        echo "Please check the error messages above.\n\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "\n";
    echo "═══════════════════════════════════════════════════════\n";
    echo "  ✗ UPGRADE ERROR!\n";
    echo "═══════════════════════════════════════════════════════\n\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n\n";
    exit(1);
}
