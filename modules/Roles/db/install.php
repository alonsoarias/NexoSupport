<?php
/**
 * ISER Roles and Permissions System - Database Installation Schema
 *
 * @package    ISER\Modules\Roles
 * @copyright  2024 ISER
 * @license    Proprietary
 * @version    4.0.0
 * @since      Phase 4
 */

defined('ISER_BASE_DIR') or die('Direct access not allowed');

use ISER\Core\Database\Database;

/**
 * Install roles and permissions database tables
 *
 * @param Database $db Database instance
 * @return bool True on success
 */
function install_roles_db(Database $db): bool
{
    $prefix = $db->getPrefix();

    // Roles table
    $sql_roles = "CREATE TABLE IF NOT EXISTS {$prefix}roles (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        shortname VARCHAR(100) NOT NULL UNIQUE,
        description TEXT,
        archetype VARCHAR(30),
        sortorder INT NOT NULL DEFAULT 0,
        timecreated BIGINT NOT NULL,
        timemodified BIGINT NOT NULL,
        INDEX idx_shortname (shortname),
        INDEX idx_archetype (archetype)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    // Capabilities table
    $sql_capabilities = "CREATE TABLE IF NOT EXISTS {$prefix}capabilities (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL UNIQUE,
        component VARCHAR(100) NOT NULL,
        contextlevel INT NOT NULL DEFAULT 10,
        riskbitmask INT NOT NULL DEFAULT 0,
        description TEXT,
        timecreated BIGINT NOT NULL,
        timemodified BIGINT NOT NULL,
        INDEX idx_name (name),
        INDEX idx_component (component),
        INDEX idx_contextlevel (contextlevel)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    // Role capabilities (permissions) table
    $sql_role_capabilities = "CREATE TABLE IF NOT EXISTS {$prefix}role_capabilities (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        roleid BIGINT UNSIGNED NOT NULL,
        capabilityid BIGINT UNSIGNED NOT NULL,
        permission TINYINT NOT NULL DEFAULT 0,
        contextid BIGINT UNSIGNED NOT NULL DEFAULT 1,
        timecreated BIGINT NOT NULL,
        timemodified BIGINT NOT NULL,
        UNIQUE KEY unique_role_cap_context (roleid, capabilityid, contextid),
        INDEX idx_roleid (roleid),
        INDEX idx_capabilityid (capabilityid),
        INDEX idx_contextid (contextid),
        FOREIGN KEY (roleid) REFERENCES {$prefix}roles(id) ON DELETE CASCADE,
        FOREIGN KEY (capabilityid) REFERENCES {$prefix}capabilities(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    // Role assignments table
    $sql_role_assignments = "CREATE TABLE IF NOT EXISTS {$prefix}role_assignments (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        roleid BIGINT UNSIGNED NOT NULL,
        userid BIGINT UNSIGNED NOT NULL,
        contextid BIGINT UNSIGNED NOT NULL DEFAULT 1,
        timestart BIGINT DEFAULT 0,
        timeend BIGINT DEFAULT 0,
        timecreated BIGINT NOT NULL,
        timemodified BIGINT NOT NULL,
        UNIQUE KEY unique_role_user_context (roleid, userid, contextid),
        INDEX idx_roleid (roleid),
        INDEX idx_userid (userid),
        INDEX idx_contextid (contextid),
        INDEX idx_timestart (timestart),
        INDEX idx_timeend (timeend),
        FOREIGN KEY (roleid) REFERENCES {$prefix}roles(id) ON DELETE CASCADE,
        FOREIGN KEY (userid) REFERENCES {$prefix}users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    // Context table for permission contexts
    $sql_context = "CREATE TABLE IF NOT EXISTS {$prefix}context (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        contextlevel INT NOT NULL,
        instanceid BIGINT UNSIGNED NOT NULL DEFAULT 0,
        path VARCHAR(255),
        depth INT NOT NULL DEFAULT 0,
        timecreated BIGINT NOT NULL,
        timemodified BIGINT NOT NULL,
        UNIQUE KEY unique_contextlevel_instance (contextlevel, instanceid),
        INDEX idx_contextlevel (contextlevel),
        INDEX idx_instanceid (instanceid),
        INDEX idx_path (path)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    // Role allow assign - which roles can assign which roles
    $sql_role_allow_assign = "CREATE TABLE IF NOT EXISTS {$prefix}role_allow_assign (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        roleid BIGINT UNSIGNED NOT NULL,
        allowassign BIGINT UNSIGNED NOT NULL,
        timecreated BIGINT NOT NULL,
        UNIQUE KEY unique_role_allowassign (roleid, allowassign),
        INDEX idx_roleid (roleid),
        INDEX idx_allowassign (allowassign),
        FOREIGN KEY (roleid) REFERENCES {$prefix}roles(id) ON DELETE CASCADE,
        FOREIGN KEY (allowassign) REFERENCES {$prefix}roles(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    // Role allow override - which roles can override permissions of which roles
    $sql_role_allow_override = "CREATE TABLE IF NOT EXISTS {$prefix}role_allow_override (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        roleid BIGINT UNSIGNED NOT NULL,
        allowoverride BIGINT UNSIGNED NOT NULL,
        timecreated BIGINT NOT NULL,
        UNIQUE KEY unique_role_allowoverride (roleid, allowoverride),
        INDEX idx_roleid (roleid),
        INDEX idx_allowoverride (allowoverride),
        FOREIGN KEY (roleid) REFERENCES {$prefix}roles(id) ON DELETE CASCADE,
        FOREIGN KEY (allowoverride) REFERENCES {$prefix}roles(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    try {
        $db->execute($sql_roles);
        $db->execute($sql_capabilities);
        $db->execute($sql_context);
        $db->execute($sql_role_capabilities);
        $db->execute($sql_role_assignments);
        $db->execute($sql_role_allow_assign);
        $db->execute($sql_role_allow_override);

        // Create system context
        $now = time();
        $db->insert('context', [
            'contextlevel' => 10, // CONTEXT_SYSTEM
            'instanceid' => 0,
            'path' => '/1',
            'depth' => 1,
            'timecreated' => $now,
            'timemodified' => $now
        ]);

        // Create default roles
        install_default_roles($db);

        return true;
    } catch (\Exception $e) {
        error_log('Roles database installation failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Create default system roles
 *
 * @param Database $db Database instance
 * @return bool True on success
 */
function install_default_roles(Database $db): bool
{
    $now = time();

    $roles = [
        [
            'name' => 'Administrador',
            'shortname' => 'admin',
            'description' => 'Los administradores pueden hacer cualquier cosa y acceder a todo el sitio',
            'archetype' => 'admin',
            'sortorder' => 1
        ],
        [
            'name' => 'Gestor',
            'shortname' => 'manager',
            'description' => 'Los gestores pueden acceder a la gestión de usuarios y contenidos',
            'archetype' => 'manager',
            'sortorder' => 2
        ],
        [
            'name' => 'Usuario',
            'shortname' => 'user',
            'description' => 'Usuario autenticado con permisos básicos',
            'archetype' => 'user',
            'sortorder' => 3
        ],
        [
            'name' => 'Invitado',
            'shortname' => 'guest',
            'description' => 'Los invitados tienen privilegios mínimos y generalmente no pueden escribir',
            'archetype' => 'guest',
            'sortorder' => 4
        ]
    ];

    foreach ($roles as $role) {
        $role['timecreated'] = $now;
        $role['timemodified'] = $now;
        $db->insert('roles', $role);
    }

    return true;
}

/**
 * Upgrade roles database schema
 *
 * @param Database $db Database instance
 * @param int $oldversion Previous version number
 * @return bool True on success
 */
function upgrade_roles_db(Database $db, int $oldversion): bool
{
    // Future upgrades will be handled here
    return true;
}
