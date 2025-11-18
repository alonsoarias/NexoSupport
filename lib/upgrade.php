<?php
/**
 * Core Upgrade Script
 *
 * Contiene las funciones de upgrade para actualizar el sistema
 * de una versión a otra.
 *
 * Patrón similar a Moodle: cada función xmldb_core_upgrade($oldversion)
 * verifica la versión antigua y ejecuta los cambios necesarios.
 *
 * @package core
 */

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Main core upgrade function
 *
 * @param int $oldversion Version number of the old installation
 * @return bool Success
 */
function xmldb_core_upgrade(int $oldversion): bool {
    global $DB;

    $result = true;

    // =========================================================
    // Upgrade to v1.1.0 (2025011800) - RBAC System
    // =========================================================
    if ($oldversion < 2025011800) {

        // Las tablas de RBAC ya existen desde v1.0.0
        // Solo necesitamos instalar los datos iniciales

        require_once(__DIR__ . '/install_rbac.php');

        // Instalar sistema RBAC (roles, capabilities, contexts)
        if (!install_rbac_system()) {
            debugging('Failed to install RBAC system during upgrade');
            return false;
        }

        // Asignar rol administrator al primer usuario (ID=1)
        // Normalmente es el administrador inicial
        try {
            $syscontext = \core\rbac\context::system();
            $adminrole = \core\rbac\role::get_by_shortname('administrator');

            if ($adminrole) {
                // Obtener el primer usuario (normalmente el admin)
                $firstuser = $DB->get_record_sql('SELECT * FROM {users} WHERE deleted = 0 ORDER BY id ASC LIMIT 1');

                if ($firstuser) {
                    \core\rbac\access::assign_role($adminrole->id, $firstuser->id, $syscontext);
                    debugging("Administrator role assigned to user {$firstuser->username}", DEBUG_DEVELOPER);
                }
            }
        } catch (Exception $e) {
            debugging('Error assigning administrator role: ' . $e->getMessage());
            // No es crítico, continuar
        }

        // Actualizar versión guardada
        upgrade_core_savepoint(true, 2025011800);
    }

    // =========================================================
    // Upgrade to v1.1.1 (2025011801) - Fix Missing Capabilities
    // =========================================================
    if ($oldversion < 2025011801) {

        require_once(__DIR__ . '/install_rbac.php');

        try {
            $syscontext = \core\rbac\context::system();
            $adminrole = \core\rbac\role::get_by_shortname('administrator');

            if ($adminrole) {
                // Get all capabilities from system
                $capabilities = get_system_capabilities();

                // Install any missing capabilities and assign to administrator
                foreach ($capabilities as $cap) {
                    // Check if capability exists
                    $existing = $DB->get_record('capabilities', ['name' => $cap['name']]);

                    if (!$existing) {
                        // Insert new capability
                        $record = new stdClass();
                        $record->name = $cap['name'];
                        $record->captype = $cap['captype'];
                        $record->contextlevel = $cap['contextlevel'];
                        $record->component = $cap['component'];
                        $record->riskbitmask = $cap['riskbitmask'];

                        $DB->insert_record('capabilities', $record);
                        debugging("Installed missing capability: {$cap['name']}", DEBUG_DEVELOPER);
                    }

                    // Assign to administrator if not already assigned
                    $existing_perm = $DB->get_record('role_capabilities', [
                        'roleid' => $adminrole->id,
                        'capability' => $cap['name'],
                        'contextid' => $syscontext->id
                    ]);

                    if (!$existing_perm) {
                        $adminrole->assign_capability(
                            $cap['name'],
                            \core\rbac\access::PERMISSION_ALLOW,
                            $syscontext
                        );
                        debugging("Assigned capability {$cap['name']} to administrator", DEBUG_DEVELOPER);
                    }
                }
            }
        } catch (Exception $e) {
            debugging('Error installing missing capabilities: ' . $e->getMessage());
        }

        upgrade_core_savepoint(true, 2025011801);
    }

    // =========================================================
    // Future upgrades go here
    // =========================================================

    // if ($oldversion < 2025011900) {
    //     // Upgrade to v1.2.0
    //     upgrade_core_savepoint(true, 2025011900);
    // }

    return $result;
}

/**
 * Save core upgrade checkpoint
 *
 * @param bool $result
 * @param int $version
 * @return void
 */
function upgrade_core_savepoint(bool $result, int $version): void {
    global $DB;

    if ($result) {
        // Update core version in config
        $DB->delete_records('config', ['name' => 'version']);

        $record = new stdClass();
        $record->name = 'version';
        $record->value = (string)$version;

        $DB->insert_record('config', $record);

        debugging("Upgrade savepoint reached: version $version", DEBUG_DEVELOPER);
    }
}

/**
 * Get core version from database
 *
 * @return int|null
 */
function get_core_version_from_db(): ?int {
    global $DB;

    try {
        $record = $DB->get_record('config', ['name' => 'version']);
        return $record ? (int)$record->value : null;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Get core version from code
 *
 * @return int
 */
function get_core_version_from_code(): int {
    require(__DIR__ . '/version.php');
    return $plugin->version;
}

/**
 * Check if upgrade is required
 *
 * @return bool
 */
function core_upgrade_required(): bool {
    $dbversion = get_core_version_from_db();
    $codeversion = get_core_version_from_code();

    // If no version in DB, assume it needs upgrade
    if ($dbversion === null) {
        return true;
    }

    return $codeversion > $dbversion;
}
