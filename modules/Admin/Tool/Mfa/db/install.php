<?php
/**
 * ISER MFA System - Database Installation Schema
 *
 * @package    ISER\Modules\Admin\Tool\Mfa
 * @copyright  2024 ISER
 * @license    Proprietary
 * @version    5.0.0
 * @since      Phase 5
 */

defined('ISER_BASE_DIR') or die('Direct access not allowed');

use ISER\Core\Database\Database;

/**
 * Install MFA database tables
 *
 * @param Database $db Database instance
 * @return bool True on success
 */
function install_mfa_db(Database $db): bool
{
    $prefix = $db->getPrefix();

    // MFA factors table - available factors
    $sql_factors = "CREATE TABLE IF NOT EXISTS {$prefix}mfa_factors (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        displayname VARCHAR(255) NOT NULL,
        description TEXT,
        enabled TINYINT NOT NULL DEFAULT 1,
        sortorder INT NOT NULL DEFAULT 0,
        timecreated BIGINT NOT NULL,
        timemodified BIGINT NOT NULL,
        INDEX idx_name (name),
        INDEX idx_enabled (enabled)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    // MFA user configuration table
    $sql_user_config = "CREATE TABLE IF NOT EXISTS {$prefix}mfa_user_config (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        userid BIGINT UNSIGNED NOT NULL,
        factor VARCHAR(100) NOT NULL,
        secret TEXT,
        config TEXT,
        enabled TINYINT NOT NULL DEFAULT 1,
        timecreated BIGINT NOT NULL,
        timemodified BIGINT NOT NULL,
        UNIQUE KEY unique_user_factor (userid, factor),
        INDEX idx_userid (userid),
        INDEX idx_factor (factor),
        INDEX idx_enabled (enabled),
        FOREIGN KEY (userid) REFERENCES {$prefix}users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    // MFA backup codes table
    $sql_backup_codes = "CREATE TABLE IF NOT EXISTS {$prefix}mfa_backup_codes (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        userid BIGINT UNSIGNED NOT NULL,
        code_hash VARCHAR(255) NOT NULL,
        used TINYINT NOT NULL DEFAULT 0,
        used_at BIGINT DEFAULT NULL,
        timecreated BIGINT NOT NULL,
        INDEX idx_userid (userid),
        INDEX idx_used (used),
        FOREIGN KEY (userid) REFERENCES {$prefix}users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    // MFA email codes table (temporary codes)
    $sql_email_codes = "CREATE TABLE IF NOT EXISTS {$prefix}mfa_email_codes (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        userid BIGINT UNSIGNED NOT NULL,
        code VARCHAR(10) NOT NULL,
        expires_at BIGINT NOT NULL,
        used TINYINT NOT NULL DEFAULT 0,
        attempts INT NOT NULL DEFAULT 0,
        timecreated BIGINT NOT NULL,
        INDEX idx_userid (userid),
        INDEX idx_expires (expires_at),
        INDEX idx_used (used),
        FOREIGN KEY (userid) REFERENCES {$prefix}users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    // MFA policies table (role-based requirements)
    $sql_policies = "CREATE TABLE IF NOT EXISTS {$prefix}mfa_policies (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        roleid BIGINT UNSIGNED NOT NULL,
        factor VARCHAR(100),
        requirement TINYINT NOT NULL DEFAULT 0,
        timecreated BIGINT NOT NULL,
        timemodified BIGINT NOT NULL,
        UNIQUE KEY unique_role_factor (roleid, factor),
        INDEX idx_roleid (roleid),
        INDEX idx_requirement (requirement),
        FOREIGN KEY (roleid) REFERENCES {$prefix}roles(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    // MFA audit log table
    $sql_audit = "CREATE TABLE IF NOT EXISTS {$prefix}mfa_audit (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        userid BIGINT UNSIGNED NOT NULL,
        factor VARCHAR(100),
        action VARCHAR(50) NOT NULL,
        success TINYINT NOT NULL DEFAULT 0,
        ip_address VARCHAR(45),
        user_agent TEXT,
        details TEXT,
        timecreated BIGINT NOT NULL,
        INDEX idx_userid (userid),
        INDEX idx_factor (factor),
        INDEX idx_action (action),
        INDEX idx_timecreated (timecreated),
        FOREIGN KEY (userid) REFERENCES {$prefix}users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    try {
        $db->execute($sql_factors);
        $db->execute($sql_user_config);
        $db->execute($sql_backup_codes);
        $db->execute($sql_email_codes);
        $db->execute($sql_policies);
        $db->execute($sql_audit);

        // Install default factors
        install_default_mfa_factors($db);

        return true;
    } catch (\Exception $e) {
        error_log('MFA database installation failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Install default MFA factors
 *
 * @param Database $db Database instance
 * @return bool True on success
 */
function install_default_mfa_factors(Database $db): bool
{
    $now = time();

    $factors = [
        [
            'name' => 'totp',
            'displayname' => 'Aplicación Autenticadora (TOTP)',
            'description' => 'Usa una aplicación como Google Authenticator o Authy para generar códigos',
            'enabled' => 1,
            'sortorder' => 1
        ],
        [
            'name' => 'email',
            'displayname' => 'Código por Email',
            'description' => 'Recibe un código de verificación en tu email',
            'enabled' => 1,
            'sortorder' => 2
        ],
        [
            'name' => 'backup',
            'displayname' => 'Códigos de Respaldo',
            'description' => 'Códigos de un solo uso para emergencias',
            'enabled' => 1,
            'sortorder' => 3
        ]
    ];

    foreach ($factors as $factor) {
        $factor['timecreated'] = $now;
        $factor['timemodified'] = $now;

        try {
            $db->insert('mfa_factors', $factor);
        } catch (\Exception $e) {
            // Factor might already exist
            error_log('Failed to insert MFA factor: ' . $e->getMessage());
        }
    }

    return true;
}

/**
 * Upgrade MFA database schema
 *
 * @param Database $db Database instance
 * @param int $oldversion Previous version number
 * @return bool True on success
 */
function upgrade_mfa_db(Database $db, int $oldversion): bool
{
    // Future upgrades will be handled here
    return true;
}
