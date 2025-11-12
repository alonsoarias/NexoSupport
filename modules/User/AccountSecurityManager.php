<?php

declare(strict_types=1);

/**
 * ISER - Account Security Manager
 *
 * Manages account security state in the account_security table (3FN normalized).
 * Handles failed login attempts, account lockouts, and security-related operations.
 *
 * @package    ISER\User
 * @category   Security
 * @author     ISER Development Team
 * @copyright  2024 ISER
 * @license    Proprietary
 * @version    1.0.0
 * @since      Phase 6
 */

namespace ISER\User;

use ISER\Core\Database\Database;
use ISER\Core\Utils\Logger;

/**
 * AccountSecurityManager Class
 *
 * Handles account security operations that were previously stored in users table.
 * Replaces users.failed_login_attempts, users.locked_until fields.
 */
class AccountSecurityManager
{
    /**
     * Database instance
     */
    private Database $db;

    /**
     * Maximum failed login attempts before locking (from config)
     */
    private int $maxAttempts;

    /**
     * Lockout duration in seconds (from config)
     */
    private int $lockoutDuration;

    /**
     * Constructor
     *
     * @param Database $db Database instance
     * @param int $maxAttempts Maximum failed login attempts (default: 5)
     * @param int $lockoutDuration Lockout duration in seconds (default: 900 = 15 minutes)
     */
    public function __construct(Database $db, int $maxAttempts = 5, int $lockoutDuration = 900)
    {
        $this->db = $db;
        $this->maxAttempts = $maxAttempts;
        $this->lockoutDuration = $lockoutDuration;
    }

    /**
     * Initialize security record for a new user
     *
     * @param int $userId User ID
     * @return bool True on success
     */
    public function initialize(int $userId): bool
    {
        try {
            $now = time();

            $result = $this->db->insert('account_security', [
                'user_id' => $userId,
                'failed_login_attempts' => 0,
                'locked_until' => null,
                'last_failed_attempt_at' => null,
                'updated_at' => $now
            ]) !== false;

            if ($result) {
                Logger::debug('Account security initialized', ['user_id' => $userId]);
            }

            return $result;

        } catch (\Exception $e) {
            Logger::error('Failed to initialize account security', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get account security state for a user
     *
     * @param int $userId User ID
     * @return array|false Security state or false if not found
     */
    public function get(int $userId): array|false
    {
        try {
            return $this->db->selectOne('account_security', ['user_id' => $userId]);
        } catch (\Exception $e) {
            Logger::error('Failed to get account security', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Check if an account is currently locked
     *
     * @param int $userId User ID
     * @return bool True if locked
     */
    public function isLocked(int $userId): bool
    {
        try {
            $security = $this->get($userId);

            if (!$security) {
                return false;
            }

            // Check if locked_until is set and in the future
            $lockedUntil = $security['locked_until'] ?? null;

            if ($lockedUntil === null) {
                return false;
            }

            $now = time();

            if ($lockedUntil > $now) {
                return true;
            }

            // Lock has expired, clear it
            if ($lockedUntil <= $now && $lockedUntil > 0) {
                $this->unlock($userId);
            }

            return false;

        } catch (\Exception $e) {
            Logger::error('Failed to check lock status', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Record a failed login attempt
     *
     * @param int $userId User ID
     * @return bool True on success
     */
    public function recordFailedAttempt(int $userId): bool
    {
        try {
            $security = $this->get($userId);

            if (!$security) {
                // Initialize if doesn't exist
                $this->initialize($userId);
                $security = $this->get($userId);
            }

            $now = time();
            $attempts = ((int)($security['failed_login_attempts'] ?? 0)) + 1;

            $updateData = [
                'failed_login_attempts' => $attempts,
                'last_failed_attempt_at' => $now,
                'updated_at' => $now
            ];

            // Lock account if max attempts reached
            if ($attempts >= $this->maxAttempts) {
                $updateData['locked_until'] = $now + $this->lockoutDuration;

                Logger::warning('Account locked due to failed attempts', [
                    'user_id' => $userId,
                    'attempts' => $attempts,
                    'locked_until' => $updateData['locked_until']
                ]);
            }

            $result = $this->db->update('account_security', $updateData, [
                'user_id' => $userId
            ]) > 0;

            if ($result) {
                Logger::debug('Failed login attempt recorded', [
                    'user_id' => $userId,
                    'attempts' => $attempts
                ]);
            }

            return $result;

        } catch (\Exception $e) {
            Logger::error('Failed to record failed attempt', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Reset failed login attempts (called on successful login)
     *
     * @param int $userId User ID
     * @return bool True on success
     */
    public function resetAttempts(int $userId): bool
    {
        try {
            $now = time();

            $result = $this->db->update('account_security', [
                'failed_login_attempts' => 0,
                'locked_until' => null,
                'updated_at' => $now
            ], [
                'user_id' => $userId
            ]) > 0;

            if ($result) {
                Logger::debug('Failed login attempts reset', ['user_id' => $userId]);
            }

            return $result;

        } catch (\Exception $e) {
            Logger::error('Failed to reset attempts', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Manually unlock an account (admin action)
     *
     * @param int $userId User ID
     * @return bool True on success
     */
    public function unlock(int $userId): bool
    {
        try {
            $now = time();

            $result = $this->db->update('account_security', [
                'failed_login_attempts' => 0,
                'locked_until' => null,
                'updated_at' => $now
            ], [
                'user_id' => $userId
            ]) > 0;

            if ($result) {
                Logger::info('Account manually unlocked', ['user_id' => $userId]);
            }

            return $result;

        } catch (\Exception $e) {
            Logger::error('Failed to unlock account', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Manually lock an account (admin action)
     *
     * @param int $userId User ID
     * @param int $duration Lock duration in seconds (0 = permanent)
     * @return bool True on success
     */
    public function lock(int $userId, int $duration = 0): bool
    {
        try {
            $now = time();
            $lockedUntil = $duration > 0 ? ($now + $duration) : PHP_INT_MAX;

            $result = $this->db->update('account_security', [
                'locked_until' => $lockedUntil,
                'updated_at' => $now
            ], [
                'user_id' => $userId
            ]) > 0;

            if ($result) {
                Logger::info('Account manually locked', [
                    'user_id' => $userId,
                    'duration' => $duration
                ]);
            }

            return $result;

        } catch (\Exception $e) {
            Logger::error('Failed to lock account', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get failed login attempts count
     *
     * @param int $userId User ID
     * @return int Number of failed attempts
     */
    public function getFailedAttempts(int $userId): int
    {
        $security = $this->get($userId);
        return (int)($security['failed_login_attempts'] ?? 0);
    }

    /**
     * Get remaining lock time in seconds
     *
     * @param int $userId User ID
     * @return int Remaining seconds (0 if not locked)
     */
    public function getRemainingLockTime(int $userId): int
    {
        if (!$this->isLocked($userId)) {
            return 0;
        }

        $security = $this->get($userId);
        $lockedUntil = $security['locked_until'] ?? 0;
        $now = time();

        return max(0, $lockedUntil - $now);
    }

    /**
     * Get lock expiration timestamp
     *
     * @param int $userId User ID
     * @return int|null Timestamp or null if not locked
     */
    public function getLockExpiration(int $userId): ?int
    {
        if (!$this->isLocked($userId)) {
            return null;
        }

        $security = $this->get($userId);
        return $security['locked_until'] ?? null;
    }

    /**
     * Delete security record for a user (called when user is deleted)
     *
     * @param int $userId User ID
     * @return bool True on success
     */
    public function delete(int $userId): bool
    {
        try {
            $result = $this->db->delete('account_security', ['user_id' => $userId]) > 0;

            if ($result) {
                Logger::debug('Account security record deleted', ['user_id' => $userId]);
            }

            return $result;

        } catch (\Exception $e) {
            Logger::error('Failed to delete account security', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get statistics about account lockouts
     *
     * @return array Statistics
     */
    public function getStatistics(): array
    {
        try {
            $sql = "SELECT
                        COUNT(*) as total_records,
                        SUM(CASE WHEN locked_until > UNIX_TIMESTAMP() THEN 1 ELSE 0 END) as currently_locked,
                        SUM(CASE WHEN failed_login_attempts > 0 THEN 1 ELSE 0 END) as with_failed_attempts,
                        AVG(failed_login_attempts) as avg_failed_attempts
                    FROM {$this->db->table('account_security')}";

            $result = $this->db->getConnection()->fetchOne($sql, []);

            return [
                'total_records' => (int)($result['total_records'] ?? 0),
                'currently_locked' => (int)($result['currently_locked'] ?? 0),
                'with_failed_attempts' => (int)($result['with_failed_attempts'] ?? 0),
                'avg_failed_attempts' => (float)($result['avg_failed_attempts'] ?? 0)
            ];

        } catch (\Exception $e) {
            Logger::error('Failed to get security statistics', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
}
