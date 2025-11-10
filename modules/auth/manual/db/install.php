<?php
/**
 * ISER Authentication System - Database Installation Schema
 *
 * @package    ISER\Modules\Auth\Manual
 * @copyright  2024 ISER
 * @license    Proprietary
 * @version    2.0.0
 * @since      Phase 2
 */

defined('ISER_BASE_DIR') or die('Direct access not allowed');

use ISER\Core\Database\Database;
use ISER\Core\Utils\Helpers;

/**
 * Install authentication database tables
 *
 * @param Database $db Database instance
 * @return bool True on success
 */
function install_auth_manual_db(Database $db): bool
{
    $prefix = $db->getPrefix();

    // Users table
    $sql_users = "CREATE TABLE IF NOT EXISTS {$prefix}users (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) NOT NULL UNIQUE,
        email VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        firstname VARCHAR(100) NOT NULL,
        lastname VARCHAR(100) NOT NULL,
        status TINYINT DEFAULT 1 COMMENT '0=disabled, 1=active, 2=suspended',
        failed_attempts INT DEFAULT 0,
        locked_until BIGINT DEFAULT 0,
        timecreated BIGINT NOT NULL,
        timemodified BIGINT NOT NULL,
        INDEX idx_username (username),
        INDEX idx_email (email),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    // Sessions table
    $sql_sessions = "CREATE TABLE IF NOT EXISTS {$prefix}sessions (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        userid BIGINT UNSIGNED NOT NULL,
        token VARCHAR(500) NOT NULL,
        refresh_token VARCHAR(500),
        ip_address VARCHAR(45),
        user_agent VARCHAR(255),
        expires_at BIGINT NOT NULL,
        created_at BIGINT NOT NULL,
        last_activity BIGINT NOT NULL,
        INDEX idx_userid (userid),
        INDEX idx_token (token(100)),
        INDEX idx_expires (expires_at),
        FOREIGN KEY (userid) REFERENCES {$prefix}users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    // Login attempts table
    $sql_attempts = "CREATE TABLE IF NOT EXISTS {$prefix}login_attempts (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) NOT NULL,
        ip_address VARCHAR(45) NOT NULL,
        user_agent VARCHAR(255),
        success TINYINT NOT NULL DEFAULT 0,
        attempted_at BIGINT NOT NULL,
        INDEX idx_username (username),
        INDEX idx_ip (ip_address),
        INDEX idx_attempted (attempted_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    try {
        // Create tables
        $db->execute($sql_users);
        $db->execute($sql_sessions);
        $db->execute($sql_attempts);

        // Create admin user if not exists
        $adminExists = $db->count('users', ['username' => 'admin']);

        if ($adminExists === 0) {
            $now = time();
            $db->insert('users', [
                'username' => 'admin',
                'email' => 'admin@iser.edu',
                'password' => Helpers::hashPassword('Admin@123'),
                'firstname' => 'System',
                'lastname' => 'Administrator',
                'status' => 1,
                'failed_attempts' => 0,
                'locked_until' => 0,
                'timecreated' => $now,
                'timemodified' => $now,
            ]);
        }

        return true;
    } catch (\Exception $e) {
        error_log('Database installation failed: ' . $e->getMessage());
        return false;
    }
}
