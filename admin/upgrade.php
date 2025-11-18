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
// SPECIAL CASE: If siteadmins is not configured yet (first upgrade), allow any logged-in user
// to proceed with upgrade. This solves the chicken-and-egg problem where upgrade creates siteadmins.
global $DB;
$siteadmins_config = null;
try {
    $sql = "SELECT * FROM {config} WHERE name = ? AND component = ? LIMIT 1";
    $siteadmins_config = $DB->get_record_sql($sql, ['siteadmins', 'core']);
} catch (\Exception $e) {
    // Table might not exist yet, continue
}

if ($siteadmins_config && !empty($siteadmins_config->value)) {
    // siteadmins is configured, enforce it
    if (!is_siteadmin($USER->id)) {
        print_error('nopermissions', 'core');
    }
} else {
    // siteadmins not configured yet - allow if user has administrator role
    try {
        $syscontext = \core\rbac\context::system();
        $adminrole = \core\rbac\role::get_by_shortname('administrator');

        if ($adminrole) {
            $has_admin_role = \core\rbac\access::user_has_role($USER->id, $adminrole->id, $syscontext);
            if (!$has_admin_role) {
                // Not administrator, check if first user
                $firstuser = $DB->get_record_sql('SELECT * FROM {users} WHERE deleted = 0 ORDER BY id ASC LIMIT 1');
                if (!$firstuser || $firstuser->id != $USER->id) {
                    print_error('nopermissions', 'core');
                }
            }
        }
    } catch (\Exception $e) {
        // If we can't check, allow the first user only
        $firstuser = $DB->get_record_sql('SELECT * FROM {users} WHERE deleted = 0 ORDER BY id ASC LIMIT 1');
        if (!$firstuser || $firstuser->id != $USER->id) {
            print_error('nopermissions', 'core');
        }
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
