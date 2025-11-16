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

        'mfa_audit_log' => [
            'columns' => [
                'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
                'user_id' => 'INT NOT NULL',
                'factor' => 'VARCHAR(50) NOT NULL',
                'action' => 'VARCHAR(100) NOT NULL',
                'success' => 'BOOLEAN NOT NULL',
                'ip_address' => 'VARCHAR(45)',
                'user_agent' => 'TEXT',
                'details' => 'TEXT',
                'timestamp' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
            ],
            'indexes' => [
                'idx_user_id' => 'user_id',
                'idx_timestamp' => 'timestamp',
                'idx_success' => 'success',
                'idx_factor' => 'factor',
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
