<?php
/**
 * ISER User Management System - Database Installation Schema
 *
 * @package    ISER\Modules\User
 * @copyright  2024 ISER
 * @license    Proprietary
 * @version    3.0.0
 * @since      Phase 3
 */

defined('ISER_BASE_DIR') or die('Direct access not allowed');

use ISER\Core\Database\Database;

/**
 * Install user management database tables
 *
 * @param Database $db Database instance
 * @return bool True on success
 */
function install_user_db(Database $db): bool
{
    $prefix = $db->getPrefix();

    // User profiles table - Extended user information
    $sql_profiles = "CREATE TABLE IF NOT EXISTS {$prefix}user_profiles (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        userid BIGINT UNSIGNED NOT NULL UNIQUE,
        phone VARCHAR(20),
        address TEXT,
        city VARCHAR(100),
        country VARCHAR(100),
        postalcode VARCHAR(20),
        institution VARCHAR(255),
        department VARCHAR(255),
        position VARCHAR(255),
        bio TEXT,
        website VARCHAR(255),
        linkedin VARCHAR(255),
        twitter VARCHAR(255),
        timecreated BIGINT NOT NULL,
        timemodified BIGINT NOT NULL,
        INDEX idx_userid (userid),
        INDEX idx_institution (institution),
        FOREIGN KEY (userid) REFERENCES {$prefix}users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    // User avatars table - Avatar management
    $sql_avatars = "CREATE TABLE IF NOT EXISTS {$prefix}user_avatars (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        userid BIGINT UNSIGNED NOT NULL UNIQUE,
        filename VARCHAR(255) NOT NULL,
        filesize INT UNSIGNED NOT NULL,
        mimetype VARCHAR(100) NOT NULL,
        filepath VARCHAR(500) NOT NULL,
        is_default TINYINT DEFAULT 0,
        timecreated BIGINT NOT NULL,
        timemodified BIGINT NOT NULL,
        INDEX idx_userid (userid),
        FOREIGN KEY (userid) REFERENCES {$prefix}users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    // Add additional columns to users table if they don't exist
    $sql_alter_users = [
        "ALTER TABLE {$prefix}users ADD COLUMN IF NOT EXISTS lastlogin BIGINT DEFAULT 0",
        "ALTER TABLE {$prefix}users ADD COLUMN IF NOT EXISTS lastip VARCHAR(45)",
        "ALTER TABLE {$prefix}users ADD COLUMN IF NOT EXISTS suspended TINYINT DEFAULT 0",
        "ALTER TABLE {$prefix}users ADD COLUMN IF NOT EXISTS deleted TINYINT DEFAULT 0",
        "ALTER TABLE {$prefix}users ADD INDEX IF NOT EXISTS idx_suspended (suspended)",
        "ALTER TABLE {$prefix}users ADD INDEX IF NOT EXISTS idx_deleted (deleted)",
        "ALTER TABLE {$prefix}users ADD INDEX IF NOT EXISTS idx_lastlogin (lastlogin)",
    ];

    try {
        // Create profile table
        $db->execute($sql_profiles);

        // Create avatars table
        $db->execute($sql_avatars);

        // Alter users table - add new columns
        foreach ($sql_alter_users as $sql) {
            try {
                $db->execute($sql);
            } catch (\Exception $e) {
                // Column might already exist, continue
                error_log('User table alteration: ' . $e->getMessage());
            }
        }

        return true;
    } catch (\Exception $e) {
        error_log('User database installation failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Upgrade user database schema
 *
 * @param Database $db Database instance
 * @param int $oldversion Previous version number
 * @return bool True on success
 */
function upgrade_user_db(Database $db, int $oldversion): bool
{
    // Future upgrades will be handled here
    return true;
}
