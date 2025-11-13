<?php
/**
 * ISER Theme System - Database Installation Schema
 *
 * Creates theme_backups table for theme configuration backup/restore functionality
 *
 * @package    ISER\Modules\Theme
 * @copyright  2024 ISER
 * @license    Proprietary
 * @version    1.0.0
 * @since      Phase 9
 */

defined('ISER_BASE_DIR') or die('Direct access not allowed');

use ISER\Core\Database\Database;

/**
 * Install theme database tables
 *
 * @param Database $db Database instance
 * @return bool True on success
 */
function install_theme_db(Database $db): bool
{
    $prefix = $db->getPrefix();

    // Theme backups table - stores theme configuration snapshots
    $sql_theme_backups = "CREATE TABLE IF NOT EXISTS {$prefix}theme_backups (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        backup_name VARCHAR(100) NOT NULL,
        backup_data LONGTEXT NOT NULL,
        created_by INT UNSIGNED NOT NULL,
        created_at INT UNSIGNED NOT NULL,
        is_system_backup TINYINT(1) NOT NULL DEFAULT 0,
        INDEX idx_created_at (created_at),
        INDEX idx_created_by (created_by),
        INDEX idx_is_system (is_system_backup),
        FOREIGN KEY (created_by) REFERENCES {$prefix}users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    COMMENT='Theme configuration backups for restore functionality'";

    try {
        // Create theme_backups table
        if (!$db->execute($sql_theme_backups)) {
            error_log("Failed to create theme_backups table");
            return false;
        }

        // Log success
        error_log("Theme database tables created successfully");

        return true;
    } catch (\Exception $e) {
        error_log("Error creating theme tables: " . $e->getMessage());
        return false;
    }
}

/**
 * Uninstall theme database tables
 *
 * @param Database $db Database instance
 * @return bool True on success
 */
function uninstall_theme_db(Database $db): bool
{
    $prefix = $db->getPrefix();

    try {
        // Drop theme_backups table
        $sql_drop = "DROP TABLE IF EXISTS {$prefix}theme_backups";

        if (!$db->execute($sql_drop)) {
            error_log("Failed to drop theme_backups table");
            return false;
        }

        error_log("Theme database tables dropped successfully");

        return true;
    } catch (\Exception $e) {
        error_log("Error dropping theme tables: " . $e->getMessage());
        return false;
    }
}

/**
 * Upgrade theme database schema
 *
 * @param Database $db Database instance
 * @param int $oldVersion Previous version number
 * @return bool True on success
 */
function upgrade_theme_db(Database $db, int $oldVersion): bool
{
    $prefix = $db->getPrefix();

    try {
        // Future upgrades will go here
        // Example:
        // if ($oldVersion < 2) {
        //     $sql = "ALTER TABLE {$prefix}theme_backups ADD COLUMN new_field VARCHAR(255)";
        //     $db->execute($sql);
        // }

        return true;
    } catch (\Exception $e) {
        error_log("Error upgrading theme database: " . $e->getMessage());
        return false;
    }
}
