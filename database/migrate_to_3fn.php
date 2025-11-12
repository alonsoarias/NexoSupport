<?php

declare(strict_types=1);

/**
 * ISER - Database Migration to 3FN
 *
 * Migrates data from denormalized users/user_profiles tables to new 3FN tables:
 * - users.last_login_* → login_history
 * - users.failed_login_attempts, locked_until → account_security
 * - user_profiles.timezone, locale → user_preferences
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
 *   php database/migrate_to_3fn.php [--dry-run]
 *
 * OPTIONS:
 *   --dry-run    Show what would be migrated without making changes
 */

require_once __DIR__ . '/../vendor/autoload.php';

use ISER\Core\Bootstrap;
use ISER\Core\Database\Database;
use ISER\Core\Utils\Logger;

// Parse command line arguments
$dryRun = in_array('--dry-run', $argv);

if ($dryRun) {
    echo "=== DRY RUN MODE - NO CHANGES WILL BE MADE ===\n\n";
}

echo "FASE 6: Migration to Third Normal Form (3FN)\n";
echo "============================================\n\n";

try {
    // Initialize Bootstrap to get database connection
    $bootstrap = Bootstrap::getInstance();
    $db = Database::getInstance();

    echo "✓ Database connection established\n\n";

    // ========================================
    // STEP 1: Migrate last_login data to login_history
    // ========================================
    echo "STEP 1: Migrating last login data to login_history table...\n";

    $sql = "SELECT id, last_login_at, last_login_ip
            FROM {$db->table('users')}
            WHERE last_login_at IS NOT NULL
            AND last_login_at > 0";

    $usersWithLogins = $db->getConnection()->fetchAll($sql, []);
    $loginsMigrated = 0;

    echo "Found " . count($usersWithLogins) . " users with login history\n";

    foreach ($usersWithLogins as $user) {
        if ($dryRun) {
            echo "  [DRY-RUN] Would migrate login for user_id={$user['id']}, ip={$user['last_login_ip']}\n";
            $loginsMigrated++;
        } else {
            try {
                $db->insert('login_history', [
                    'user_id' => $user['id'],
                    'ip_address' => $user['last_login_ip'] ?? '0.0.0.0',
                    'user_agent' => null,
                    'login_at' => $user['last_login_at'],
                    'logout_at' => null,
                    'session_id' => null
                ]);
                $loginsMigrated++;
            } catch (\Exception $e) {
                echo "  ✗ Failed to migrate login for user_id={$user['id']}: {$e->getMessage()}\n";
            }
        }
    }

    echo "✓ Migrated {$loginsMigrated} login records\n\n";

    // ========================================
    // STEP 2: Migrate security data to account_security
    // ========================================
    echo "STEP 2: Migrating security data to account_security table...\n";

    $sql = "SELECT id, failed_login_attempts, locked_until
            FROM {$db->table('users')}";

    $users = $db->getConnection()->fetchAll($sql, []);
    $securityMigrated = 0;

    echo "Found " . count($users) . " users to migrate\n";

    foreach ($users as $user) {
        $failedAttempts = (int)($user['failed_login_attempts'] ?? 0);
        $lockedUntil = $user['locked_until'] ?? null;

        if ($dryRun) {
            echo "  [DRY-RUN] Would migrate security for user_id={$user['id']}, attempts={$failedAttempts}\n";
            $securityMigrated++;
        } else {
            try {
                // Check if record already exists
                $existing = $db->selectOne('account_security', ['user_id' => $user['id']]);

                if (!$existing) {
                    $db->insert('account_security', [
                        'user_id' => $user['id'],
                        'failed_login_attempts' => $failedAttempts,
                        'locked_until' => $lockedUntil,
                        'last_failed_attempt_at' => null,
                        'updated_at' => time()
                    ]);
                    $securityMigrated++;
                }
            } catch (\Exception $e) {
                echo "  ✗ Failed to migrate security for user_id={$user['id']}: {$e->getMessage()}\n";
            }
        }
    }

    echo "✓ Migrated {$securityMigrated} security records\n\n";

    // ========================================
    // STEP 3: Migrate preferences to user_preferences
    // ========================================
    echo "STEP 3: Migrating preferences to user_preferences table...\n";

    $sql = "SELECT user_id, timezone, locale
            FROM {$db->table('user_profiles')}";

    $profiles = $db->getConnection()->fetchAll($sql, []);
    $preferencesMigrated = 0;

    echo "Found " . count($profiles) . " user profiles\n";

    foreach ($profiles as $profile) {
        $userId = $profile['user_id'];
        $timezone = $profile['timezone'];
        $locale = $profile['locale'];

        // Migrate timezone if set
        if ($timezone && $timezone !== '') {
            if ($dryRun) {
                echo "  [DRY-RUN] Would migrate timezone for user_id={$userId}, value={$timezone}\n";
                $preferencesMigrated++;
            } else {
                try {
                    // Check if preference already exists
                    $existing = $db->selectOne('user_preferences', [
                        'user_id' => $userId,
                        'preference_key' => 'timezone'
                    ]);

                    if (!$existing) {
                        $db->insert('user_preferences', [
                            'user_id' => $userId,
                            'preference_key' => 'timezone',
                            'preference_value' => $timezone,
                            'preference_type' => 'string',
                            'updated_at' => time()
                        ]);
                        $preferencesMigrated++;
                    }
                } catch (\Exception $e) {
                    echo "  ✗ Failed to migrate timezone for user_id={$userId}: {$e->getMessage()}\n";
                }
            }
        }

        // Migrate locale if set
        if ($locale && $locale !== '') {
            if ($dryRun) {
                echo "  [DRY-RUN] Would migrate locale for user_id={$userId}, value={$locale}\n";
                $preferencesMigrated++;
            } else {
                try {
                    // Check if preference already exists
                    $existing = $db->selectOne('user_preferences', [
                        'user_id' => $userId,
                        'preference_key' => 'locale'
                    ]);

                    if (!$existing) {
                        $db->insert('user_preferences', [
                            'user_id' => $userId,
                            'preference_key' => 'locale',
                            'preference_value' => $locale,
                            'preference_type' => 'string',
                            'updated_at' => time()
                        ]);
                        $preferencesMigrated++;
                    }
                } catch (\Exception $e) {
                    echo "  ✗ Failed to migrate locale for user_id={$userId}: {$e->getMessage()}\n";
                }
            }
        }
    }

    echo "✓ Migrated {$preferencesMigrated} preference records\n\n";

    // ========================================
    // SUMMARY
    // ========================================
    echo "============================================\n";
    echo "Migration Summary:\n";
    echo "============================================\n";
    echo "Login history records:   {$loginsMigrated}\n";
    echo "Security records:        {$securityMigrated}\n";
    echo "Preference records:      {$preferencesMigrated}\n";
    echo "============================================\n\n";

    if ($dryRun) {
        echo "DRY RUN COMPLETE - No changes were made\n";
        echo "Run without --dry-run to apply changes\n\n";
    } else {
        echo "✓ Migration completed successfully!\n\n";

        echo "IMPORTANT NEXT STEPS:\n";
        echo "1. Verify the migrated data in the new tables\n";
        echo "2. Test login/logout functionality\n";
        echo "3. Run: php database/cleanup_old_columns.php to remove old columns\n";
        echo "4. Update schema.xml to reflect the changes\n\n";

        Logger::info('3FN Migration completed', [
            'logins_migrated' => $loginsMigrated,
            'security_migrated' => $securityMigrated,
            'preferences_migrated' => $preferencesMigrated
        ]);
    }

} catch (\Exception $e) {
    echo "\n✗ Migration failed: {$e->getMessage()}\n";
    echo "Stack trace:\n{$e->getTraceAsString()}\n";

    Logger::error('3FN Migration failed', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);

    exit(1);
}

exit(0);
