<?php

declare(strict_types=1);

/**
 * ISER - Cleanup Old Columns After 3FN Migration
 *
 * Removes old denormalized columns from users and user_profiles tables
 * after data has been migrated to new 3FN tables.
 *
 * WARNING: This is a destructive operation. Ensure migration is complete
 * and data is verified before running this script.
 *
 * @package    ISER\Database\Migrations
 * @category   Database
 * @author     ISER Development Team
 * @copyright  2024 ISER
 * @license    Proprietary
 * @version    1.0.0
 * @since      Phase 6
 *
 * USAGE:
 *   php database/cleanup_old_columns.php [--confirm]
 *
 * OPTIONS:
 *   --confirm    Required to actually perform the cleanup
 */

require_once __DIR__ . '/../vendor/autoload.php';

use ISER\Core\Bootstrap;
use ISER\Core\Database\Database;
use ISER\Core\Utils\Logger;

// Parse command line arguments
$confirmed = in_array('--confirm', $argv);

if (!$confirmed) {
    echo "╔════════════════════════════════════════════════════════════════╗\n";
    echo "║  WARNING: This will permanently remove columns from database  ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n\n";

    echo "This script will remove the following columns:\n\n";
    echo "FROM users table:\n";
    echo "  - last_login_at\n";
    echo "  - last_login_ip\n";
    echo "  - failed_login_attempts\n";
    echo "  - locked_until\n\n";

    echo "FROM user_profiles table:\n";
    echo "  - timezone\n";
    echo "  - locale\n\n";

    echo "PREREQUISITES:\n";
    echo "1. Migration script (migrate_to_3fn.php) has been run successfully\n";
    echo "2. Data has been verified in new tables (login_history, account_security, user_preferences)\n";
    echo "3. Application is using new managers (AccountSecurityManager, LoginHistoryManager, PreferencesManager)\n";
    echo "4. Database backup has been created\n\n";

    echo "To proceed, run:\n";
    echo "  php database/cleanup_old_columns.php --confirm\n\n";

    exit(0);
}

echo "FASE 6: Cleanup Old Columns (3FN Migration)\n";
echo "============================================\n\n";

try {
    // Initialize Bootstrap to get database connection
    $bootstrap = Bootstrap::getInstance();
    $db = Database::getInstance();
    $conn = $db->getConnection();

    echo "✓ Database connection established\n\n";

    // Get table prefix
    $tablePrefix = $db->getTablePrefix();
    $usersTable = $tablePrefix . 'users';
    $profilesTable = $tablePrefix . 'user_profiles';

    // ========================================
    // STEP 1: Verify new tables have data
    // ========================================
    echo "STEP 1: Verifying new tables have data...\n";

    $loginHistoryCount = $conn->fetchOne("SELECT COUNT(*) as count FROM {$tablePrefix}login_history", []);
    $accountSecurityCount = $conn->fetchOne("SELECT COUNT(*) as count FROM {$tablePrefix}account_security", []);
    $userPreferencesCount = $conn->fetchOne("SELECT COUNT(*) as count FROM {$tablePrefix}user_preferences", []);

    echo "  login_history:     " . ($loginHistoryCount['count'] ?? 0) . " records\n";
    echo "  account_security:  " . ($accountSecurityCount['count'] ?? 0) . " records\n";
    echo "  user_preferences:  " . ($userPreferencesCount['count'] ?? 0) . " records\n\n";

    if (($accountSecurityCount['count'] ?? 0) === 0) {
        echo "✗ ERROR: account_security table is empty!\n";
        echo "  Please run migrate_to_3fn.php first.\n\n";
        exit(1);
    }

    echo "✓ New tables have data\n\n";

    // ========================================
    // STEP 2: Create backup of current data
    // ========================================
    echo "STEP 2: Creating backup of current data...\n";

    $backupFile = __DIR__ . '/backup_3fn_' . date('Y-m-d_His') . '.sql';

    // Export users table data
    $usersData = $conn->fetchAll("SELECT * FROM {$usersTable}", []);
    $profilesData = $conn->fetchAll("SELECT * FROM {$profilesTable}", []);

    $backupContent = "-- FASE 6 3FN Migration Backup\n";
    $backupContent .= "-- Created: " . date('Y-m-d H:i:s') . "\n\n";
    $backupContent .= "-- Users data (" . count($usersData) . " records)\n";
    $backupContent .= json_encode($usersData, JSON_PRETTY_PRINT) . "\n\n";
    $backupContent .= "-- User profiles data (" . count($profilesData) . " records)\n";
    $backupContent .= json_encode($profilesData, JSON_PRETTY_PRINT) . "\n";

    file_put_contents($backupFile, $backupContent);

    echo "✓ Backup created: {$backupFile}\n\n";

    // ========================================
    // STEP 3: Drop old columns from users table
    // ========================================
    echo "STEP 3: Dropping old columns from users table...\n";

    $columnsToDropUsers = [
        'last_login_at',
        'last_login_ip',
        'failed_login_attempts',
        'locked_until'
    ];

    foreach ($columnsToDropUsers as $column) {
        try {
            $sql = "ALTER TABLE {$usersTable} DROP COLUMN {$column}";
            $conn->execute($sql, []);
            echo "  ✓ Dropped column: {$column}\n";
        } catch (\Exception $e) {
            // Column might not exist, check if it's just a "column doesn't exist" error
            if (strpos($e->getMessage(), "doesn't exist") !== false ||
                strpos($e->getMessage(), "Unknown column") !== false) {
                echo "  ⓘ Column {$column} doesn't exist (already removed)\n";
            } else {
                echo "  ✗ Failed to drop column {$column}: {$e->getMessage()}\n";
                throw $e;
            }
        }
    }

    echo "\n";

    // ========================================
    // STEP 4: Drop old columns from user_profiles table
    // ========================================
    echo "STEP 4: Dropping old columns from user_profiles table...\n";

    $columnsToDropProfiles = [
        'timezone',
        'locale'
    ];

    foreach ($columnsToDropProfiles as $column) {
        try {
            $sql = "ALTER TABLE {$profilesTable} DROP COLUMN {$column}";
            $conn->execute($sql, []);
            echo "  ✓ Dropped column: {$column}\n";
        } catch (\Exception $e) {
            // Column might not exist
            if (strpos($e->getMessage(), "doesn't exist") !== false ||
                strpos($e->getMessage(), "Unknown column") !== false) {
                echo "  ⓘ Column {$column} doesn't exist (already removed)\n";
            } else {
                echo "  ✗ Failed to drop column {$column}: {$e->getMessage()}\n";
                throw $e;
            }
        }
    }

    echo "\n";

    // ========================================
    // SUMMARY
    // ========================================
    echo "============================================\n";
    echo "Cleanup Summary:\n";
    echo "============================================\n";
    echo "Columns removed from users:        " . count($columnsToDropUsers) . "\n";
    echo "Columns removed from user_profiles: " . count($columnsToDropProfiles) . "\n";
    echo "Backup file:                       {$backupFile}\n";
    echo "============================================\n\n";

    echo "✓ Cleanup completed successfully!\n\n";

    echo "FINAL STEPS:\n";
    echo "1. Update schema.xml to remove the old column definitions\n";
    echo "2. Test all authentication and user management functionality\n";
    echo "3. Monitor application logs for any issues\n";
    echo "4. Keep backup file for at least 30 days\n\n";

    Logger::info('3FN Cleanup completed', [
        'users_columns_dropped' => $columnsToDropUsers,
        'profiles_columns_dropped' => $columnsToDropProfiles,
        'backup_file' => $backupFile
    ]);

} catch (\Exception $e) {
    echo "\n✗ Cleanup failed: {$e->getMessage()}\n";
    echo "Stack trace:\n{$e->getTraceAsString()}\n";

    Logger::error('3FN Cleanup failed', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);

    exit(1);
}

exit(0);
