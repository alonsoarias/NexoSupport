<?php
/**
 * NexoSupport - Data Privacy Database Schema
 *
 * @package    tool_dataprivacy
 * @copyright  2024 ISER
 * @license    Proprietary
 */

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Get Data Privacy database schema
 *
 * @return array Database schema definitions
 */
function tool_dataprivacy_get_schema(): array
{
    return [
        'dataprivacy_requests' => [
            'columns' => [
                'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
                'user_id' => 'INT NOT NULL',
                'type' => "ENUM('export', 'delete') NOT NULL",
                'status' => "ENUM('pending', 'approved', 'rejected', 'completed') NOT NULL DEFAULT 'pending'",
                'requested_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
                'processed_at' => 'TIMESTAMP NULL',
                'processed_by' => 'INT NULL',
                'notes' => 'TEXT',
                'export_file' => 'VARCHAR(255)',
                'export_format' => 'VARCHAR(10)',
            ],
            'indexes' => [
                'idx_user_id' => 'user_id',
                'idx_status' => 'status',
                'idx_type' => 'type',
                'idx_requested_at' => 'requested_at',
            ],
            'description' => 'User data privacy requests (GDPR)',
        ],

        'dataprivacy_retention' => [
            'columns' => [
                'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
                'category' => 'VARCHAR(100) NOT NULL',
                'retention_days' => 'INT NOT NULL',
                'description' => 'TEXT',
                'enabled' => 'BOOLEAN DEFAULT TRUE',
                'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
                'updated_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            ],
            'indexes' => [
                'uk_category' => 'UNIQUE (category)',
                'idx_enabled' => 'enabled',
            ],
            'description' => 'Data retention policies by category',
        ],

        'dataprivacy_audit' => [
            'columns' => [
                'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
                'user_id' => 'INT NOT NULL',
                'action' => 'VARCHAR(100) NOT NULL',
                'category' => 'VARCHAR(100)',
                'performed_by' => 'INT',
                'timestamp' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
                'details' => 'TEXT',
                'ip_address' => 'VARCHAR(45)',
                'data_summary' => 'TEXT',
            ],
            'indexes' => [
                'idx_user_id' => 'user_id',
                'idx_timestamp' => 'timestamp',
                'idx_action' => 'action',
                'idx_performed_by' => 'performed_by',
            ],
            'description' => 'Comprehensive data privacy audit trail',
        ],

        'dataprivacy_deleted_users' => [
            'columns' => [
                'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
                'original_user_id' => 'INT NOT NULL',
                'deletion_type' => "ENUM('hard', 'soft', 'anonymize') NOT NULL",
                'deleted_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
                'deleted_by' => 'INT NOT NULL',
                'reason' => 'TEXT',
                'data_snapshot' => 'LONGTEXT',
            ],
            'indexes' => [
                'idx_original_user_id' => 'original_user_id',
                'idx_deleted_at' => 'deleted_at',
                'idx_deletion_type' => 'deletion_type',
            ],
            'description' => 'Record of deleted users for compliance',
        ],
    ];
}

/**
 * Install Data Privacy database tables
 *
 * @param PDO $pdo Database connection
 * @return bool Success status
 */
function tool_dataprivacy_install_db($pdo): bool
{
    $schema = tool_dataprivacy_get_schema();

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
                    // Skip unique constraints (already in column definition)
                    if (strpos($index_name, 'uk_') === 0) {
                        continue;
                    }

                    $sql = "CREATE INDEX $index_name ON $table ($index_cols)";
                    try {
                        $pdo->exec($sql);
                    } catch (PDOException $e) {
                        // Index might already exist
                        continue;
                    }
                }
            }

        } catch (PDOException $e) {
            error_log("Failed to create table $table: " . $e->getMessage());
            return false;
        }
    }

    // Insert default retention policies
    try {
        $defaults = [
            ['personal_info', 365, 'Personal information (name, email, etc.)'],
            ['activity_logs', 90, 'User activity and access logs'],
            ['files', 180, 'User uploaded files'],
            ['settings', 365, 'User preferences and settings'],
            ['authentication', 60, 'Authentication history'],
        ];

        $stmt = $pdo->prepare("
            INSERT IGNORE INTO dataprivacy_retention (category, retention_days, description)
            VALUES (?, ?, ?)
        ");

        foreach ($defaults as $policy) {
            $stmt->execute($policy);
        }

    } catch (PDOException $e) {
        error_log("Failed to insert default policies: " . $e->getMessage());
    }

    return true;
}

/**
 * Uninstall Data Privacy database tables
 *
 * @param PDO $pdo Database connection
 * @return bool Success status
 */
function tool_dataprivacy_uninstall_db($pdo): bool
{
    $tables = [
        'dataprivacy_deleted_users',
        'dataprivacy_audit',
        'dataprivacy_retention',
        'dataprivacy_requests',
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
