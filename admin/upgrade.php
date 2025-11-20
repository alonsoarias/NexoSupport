<?php
/**
 * System Upgrade Page
 *
 * Detecta y ejecuta actualizaciones del sistema.
 * Similar a admin/index.php?upgrade en Moodle.
 *
 * @package NexoSupport
 */

require_once(__DIR__ . '/../config.php');

require_login();

// Verify user is site administrator
// CRITICAL: Only site administrators can run upgrades
global $USER, $DB;

// SPECIAL CASE: If siteadmins is not configured yet (first upgrade after fresh install),
// allow the first user to proceed. This solves the chicken-and-egg problem where
// upgrade v1.1.0 creates the siteadmins config.
$siteadmins_configured = false;
try {
    $siteadmins_config = $DB->get_record('config', ['name' => 'siteadmins', 'component' => 'core']);
    $siteadmins_configured = ($siteadmins_config && !empty($siteadmins_config->value));
} catch (\Exception $e) {
    // Config table might not exist yet during very first upgrade
    $siteadmins_configured = false;
}

if ($siteadmins_configured) {
    // Normal case: siteadmins is configured, enforce it strictly
    if (!is_siteadmin($USER->id)) {
        print_error('upgrademinrequired', 'core', '/', null,
            'Only site administrators can perform system upgrades.');
    }
} else {
    // Fallback case: siteadmins not configured yet, allow only the first user
    // This handles fresh installs or upgrades from very old versions
    try {
        $firstuser = $DB->get_record_sql('SELECT * FROM {users} WHERE deleted = 0 ORDER BY id ASC LIMIT 1');
        if (!$firstuser || $firstuser->id != $USER->id) {
            print_error('upgrademinrequired', 'core', '/', null,
                'Only the first registered user can perform upgrades when site administrators are not configured.');
        }
    } catch (\Exception $e) {
        // If we can't even query users table, something is very wrong
        // Allow to proceed so upgrade can potentially fix it
        debugging('Cannot verify user permissions for upgrade: ' . $e->getMessage(), DEBUG_DEVELOPER);
    }
}

require_once(__DIR__ . '/../lib/upgrade.php');

// Get versions
$dbversion = get_core_version_from_db();
$codeversion = get_core_version_from_code();

// Get version info
require(__DIR__ . '/../lib/version.php');
$release = $plugin->release;

// Check if upgrade is needed
$upgradeNeeded = core_upgrade_required();

// Process upgrade request
$upgradeExecuted = false;
$upgradeSuccess = false;
$upgradeErrors = [];
$upgradeOutput = '';

if (isset($_POST['upgrade']) && $_POST['upgrade'] === 'true') {
    // Verify sesskey for security
    if (!isset($_POST['sesskey']) || $_POST['sesskey'] !== sesskey()) {
        $upgradeErrors[] = get_string('invalidtoken');
    } else {
        // Execute upgrade
        $upgradeExecuted = true;

        try {
            // Capture output from upgrade process
            ob_start();

            // Run core upgrade
            $upgradeSuccess = xmldb_core_upgrade($dbversion ?? 0);

            // Get captured output
            $upgradeOutput = ob_get_clean();

            if (!$upgradeSuccess) {
                $upgradeErrors[] = get_string('error');
            }
        } catch (Exception $e) {
            // Make sure to clean buffer even on exception
            if (ob_get_level() > 0) {
                $upgradeOutput = ob_get_clean();
            }

            $upgradeErrors[] = get_string('error') . ': ' . $e->getMessage();
            $upgradeSuccess = false;
        }

        // Refresh version after upgrade
        if ($upgradeSuccess) {
            $dbversion = get_core_version_from_db();
            $upgradeNeeded = core_upgrade_required();
        }
    }
}

// Prepare context for template
$context = [
    'dbversion' => $dbversion ?? get_string('notfound', 'core'),
    'codeversion' => $codeversion,
    'release' => $release,
    'upgradeexecuted' => $upgradeExecuted,
    'upgradesuccess' => $upgradeSuccess,
    'upgradeerrors' => array_map('htmlspecialchars', $upgradeErrors),
    'haserrors' => !empty($upgradeErrors),
    'upgradeneeded' => $upgradeNeeded,
    'upgradeoutput' => $upgradeOutput,  // Output from upgrade process
    'hasoutput' => !empty($upgradeOutput),  // Whether there's output to show
    'sesskey' => sesskey(),
    'has_navigation' => false,  // Upgrade page doesn't need navigation sidebar
];

// Render and output
echo render_template('admin/upgrade', $context);
