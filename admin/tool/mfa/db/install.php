<?php
/**
 * NexoSupport - MFA Database Schema
 *
 * @package    tool_mfa
 * @copyright  2024 ISER
 * @license    Proprietary
 */

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Get MFA database schema
 *
 * @return array Database schema definitions
 */
function tool_mfa_get_schema(): array
{
    return [
        'mfa_email_codes' => [
            'columns' => [
                'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
                'user_id' => 'INT NOT NULL',
                'code_hash' => 'VARCHAR(255) NOT NULL',
                'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
                'expires_at' => 'TIMESTAMP NOT NULL',
                'attempts' => 'INT DEFAULT 0',
                'verified' => 'BOOLEAN DEFAULT FALSE',
                'ip_address' => 'VARCHAR(45)',
            ],
            'indexes' => [
                'idx_user_id' => 'user_id',
                'idx_expires' => 'expires_at',
                'idx_verified' => 'verified',
            ],
            'description' => 'Email verification codes for MFA',
        ],

        'mfa_ip_ranges' => [
            'columns' => [
                'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
                'range_cidr' => 'VARCHAR(50) NOT NULL',
                'type' => "ENUM('whitelist', 'blacklist') NOT NULL",
                'description' => 'TEXT',
                'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
                'created_by' => 'INT',
                'enabled' => 'BOOLEAN DEFAULT TRUE',
            ],
            'indexes' => [
                'idx_type' => 'type',
                'idx_enabled' => 'enabled',
            ],
            'description' => 'IP range restrictions for MFA',
        ],

        'mfa_ip_logs' => [
            'columns' => [
                'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
                'user_id' => 'INT',
                'ip_address' => 'VARCHAR(45) NOT NULL',
                'allowed' => 'BOOLEAN NOT NULL',
                'reason' => 'VARCHAR(255)',
                'timestamp' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
            ],
            'indexes' => [
                'idx_user_id' => 'user_id',
                'idx_timestamp' => 'timestamp',
                'idx_allowed' => 'allowed',
            ],
            'description' => 'Access logs for IP-based MFA',
        ],

        'mfa_user_factors' => [
            'columns' => [
                'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
                'user_id' => 'INT NOT NULL',
                'factor' => 'VARCHAR(50) NOT NULL',
                'enabled' => 'BOOLEAN DEFAULT TRUE',
                'configured_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
                'last_used' => 'TIMESTAMP NULL',
            ],
            'indexes' => [
                'idx_user_id' => 'user_id',
                'idx_factor' => 'factor',
                'uk_user_factor' => 'UNIQUE (user_id, factor)',
            ],
            'description' => 'User-specific MFA factor configuration',
        ],

        'mfa_totp_secrets' => [
            'columns' => [
                'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
                'user_id' => 'INT NOT NULL UNIQUE',
                'secret' => 'VARCHAR(255) NOT NULL',
                'verified' => 'BOOLEAN DEFAULT FALSE',
                'last_counter' => 'BIGINT DEFAULT NULL',
                'failed_attempts' => 'INT DEFAULT 0',
                'lockout_until' => 'TIMESTAMP NULL',
                'last_used_at' => 'TIMESTAMP NULL',
                'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
                'updated_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            ],
            'indexes' => [
                'idx_user_id' => 'user_id',
                'idx_verified' => 'verified',
                'idx_lockout' => 'lockout_until',
            ],
            'description' => 'TOTP secrets for Google Authenticator',
        ],

        'mfa_sms_codes' => [
            'columns' => [
                'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
                'user_id' => 'INT NOT NULL',
                'phone_number' => 'VARCHAR(20) NOT NULL',
                'code_hash' => 'VARCHAR(255) NOT NULL',
                'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
                'expires_at' => 'TIMESTAMP NOT NULL',
                'attempts' => 'INT DEFAULT 0',
                'verified' => 'BOOLEAN DEFAULT FALSE',
                'ip_address' => 'VARCHAR(45)',
            ],
            'indexes' => [
                'idx_user_id' => 'user_id',
                'idx_expires' => 'expires_at',
                'idx_verified' => 'verified',
                'idx_phone' => 'phone_number',
            ],
            'description' => 'SMS verification codes for MFA',
        ],

        'mfa_backup_codes' => [
            'columns' => [
                'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
                'user_id' => 'INT NOT NULL',
                'code_hash' => 'VARCHAR(255) NOT NULL',
                'used' => 'BOOLEAN DEFAULT FALSE',
                'used_at' => 'TIMESTAMP NULL',
                'used_ip' => 'VARCHAR(45) NULL',
                'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
            ],
            'indexes' => [
                'idx_user_id' => 'user_id',
                'idx_used' => 'used',
            ],
            'description' => 'Backup codes for account recovery',
        ],

        'mfa_audit_log' => [
            'columns' => [
                'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
                'user_id' => 'INT NOT NULL',
                'factor_type' => 'VARCHAR(50) NOT NULL',
                'event' => 'VARCHAR(100) NOT NULL',
                'details' => 'TEXT',
                'ip_address' => 'VARCHAR(45)',
                'user_agent' => 'TEXT',
                'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
            ],
            'indexes' => [
                'idx_user_id' => 'user_id',
                'idx_created_at' => 'created_at',
                'idx_factor_type' => 'factor_type',
                'idx_event' => 'event',
            ],
            'description' => 'Comprehensive MFA audit trail',
        ],
    ];
}

/**
 * Install MFA database tables
 *
 * @param PDO $pdo Database connection
 * @return bool Success status
 */
function tool_mfa_install_db($pdo): bool
{
    $schema = tool_mfa_get_schema();

    foreach ($schema as $table => $definition) {
        try {
            // Build CREATE TABLE statement
            $columns = [];
            foreach ($definition['columns'] as $col => $def) {
                $columns[] = "$col $def";
            }

            $sql = "CREATE TABLE IF NOT EXISTS $table (\n";
            $sql .= "    " . implode(",\n    ", $columns);
            $sql .= "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

            $pdo->exec($sql);

            // Create indexes
            if (isset($definition['indexes'])) {
                foreach ($definition['indexes'] as $index_name => $index_cols) {
                    // Check if it's a unique index
                    if (strpos($index_name, 'uk_') === 0) {
                        // Extract the UNIQUE constraint from column definition
                        continue; // Already in column definition
                    }

                    $sql = "CREATE INDEX $index_name ON $table ($index_cols)";
                    try {
                        $pdo->exec($sql);
                    } catch (PDOException $e) {
                        // Index might already exist, continue
                        continue;
                    }
                }
            }

        } catch (PDOException $e) {
            error_log("Failed to create table $table: " . $e->getMessage());
            return false;
        }
    }

    return true;
}

/**
 * Uninstall MFA database tables
 *
 * @param PDO $pdo Database connection
 * @return bool Success status
 */
function tool_mfa_uninstall_db($pdo): bool
{
    $tables = [
        'mfa_audit_log',
        'mfa_backup_codes',
        'mfa_sms_codes',
        'mfa_totp_secrets',
        'mfa_user_factors',
        'mfa_ip_logs',
        'mfa_ip_ranges',
        'mfa_email_codes',
    ];

    foreach ($tables as $table) {
        try {
            $pdo->exec("DROP TABLE IF EXISTS $table");
        } catch (PDOException $e) {
            error_log("Failed to drop table $table: " . $e->getMessage());
            return false;
        }
    }

    return true;
}
