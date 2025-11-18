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
global $USER;

// Check if user is logged in
if (!isset($USER->id) || $USER->id == 0) {
    // Usuario no está logueado, redirigir a login
    $returnurl = urlencode($_SERVER['REQUEST_URI']);
    redirect("/login?returnurl={$returnurl}", get_string('pleaselogin', 'core'), 3);
    exit;
}

// Check if user is site administrator
if (!is_siteadmin($USER->id)) {
    // Usuario logueado pero no es administrador
    print_error('nopermissions', 'core');
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

if (isset($_POST['upgrade']) && $_POST['upgrade'] === 'true') {
    // Verify sesskey for security
    if (!isset($_POST['sesskey']) || $_POST['sesskey'] !== sesskey()) {
        $upgradeErrors[] = get_string('invalidtoken');
    } else {
        // Execute upgrade
        $upgradeExecuted = true;

        try {
            // Run core upgrade
            $upgradeSuccess = xmldb_core_upgrade($dbversion ?? 0);

            if (!$upgradeSuccess) {
                $upgradeErrors[] = get_string('error');
            }
        } catch (Exception $e) {
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
    'sesskey' => sesskey(),
];

// Render and output
echo render_template('admin/upgrade', $context);
