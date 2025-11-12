<?php

declare(strict_types=1);

/**
 * ISER - Login History Manager
 *
 * Manages login history in the login_history table (3FN normalized).
 * Tracks all login/logout events with complete session information.
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
 * LoginHistoryManager Class
 *
 * Handles complete login history tracking that replaces users.last_login_at
 * and users.last_login_ip fields with a full audit trail.
 */
class LoginHistoryManager
{
    /**
     * Database instance
     */
    private Database $db;

    /**
     * Constructor
     *
     * @param Database $db Database instance
     */
    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Record a successful login
     *
     * @param int $userId User ID
     * @param string $ipAddress IP address
     * @param string|null $userAgent User agent string
     * @param string|null $sessionId Session ID
     * @return int|false Login history record ID or false on failure
     */
    public function recordLogin(
        int $userId,
        string $ipAddress,
        ?string $userAgent = null,
        ?string $sessionId = null
    ): int|false {
        try {
            $now = time();

            $loginId = $this->db->insert('login_history', [
                'user_id' => $userId,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'login_at' => $now,
                'logout_at' => null,
                'session_id' => $sessionId
            ]);

            if ($loginId !== false) {
                Logger::info('Login recorded', [
                    'user_id' => $userId,
                    'ip' => $ipAddress,
                    'login_id' => $loginId
                ]);
            }

            return $loginId;

        } catch (\Exception $e) {
            Logger::error('Failed to record login', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Record a logout
     *
     * @param int $loginId Login history record ID
     * @return bool True on success
     */
    public function recordLogout(int $loginId): bool
    {
        try {
            $now = time();

            $result = $this->db->update('login_history', [
                'logout_at' => $now
            ], [
                'id' => $loginId
            ]) > 0;

            if ($result) {
                Logger::info('Logout recorded', ['login_id' => $loginId]);
            }

            return $result;

        } catch (\Exception $e) {
            Logger::error('Failed to record logout', [
                'login_id' => $loginId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Record logout by session ID
     *
     * @param string $sessionId Session ID
     * @return bool True on success
     */
    public function recordLogoutBySession(string $sessionId): bool
    {
        try {
            $now = time();

            $result = $this->db->update('login_history', [
                'logout_at' => $now
            ], [
                'session_id' => $sessionId,
                'logout_at' => null  // Only update if not already logged out
            ]) > 0;

            if ($result) {
                Logger::info('Logout recorded by session', ['session_id' => $sessionId]);
            }

            return $result;

        } catch (\Exception $e) {
            Logger::error('Failed to record logout by session', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get last login for a user
     *
     * @param int $userId User ID
     * @return array|false Last login record or false
     */
    public function getLastLogin(int $userId): array|false
    {
        try {
            $sql = "SELECT * FROM {$this->db->table('login_history')}
                    WHERE user_id = :user_id
                    ORDER BY login_at DESC
                    LIMIT 1";

            return $this->db->getConnection()->fetchOne($sql, [
                ':user_id' => $userId
            ]);

        } catch (\Exception $e) {
            Logger::error('Failed to get last login', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get login history for a user
     *
     * @param int $userId User ID
     * @param int $limit Maximum records to return
     * @param int $offset Offset for pagination
     * @return array Login history records
     */
    public function getHistory(int $userId, int $limit = 50, int $offset = 0): array
    {
        try {
            $sql = "SELECT * FROM {$this->db->table('login_history')}
                    WHERE user_id = :user_id
                    ORDER BY login_at DESC
                    LIMIT :limit OFFSET :offset";

            return $this->db->getConnection()->fetchAll($sql, [
                ':user_id' => $userId,
                ':limit' => $limit,
                ':offset' => $offset
            ]);

        } catch (\Exception $e) {
            Logger::error('Failed to get login history', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get active sessions for a user
     *
     * @param int $userId User ID
     * @return array Active session records (not logged out)
     */
    public function getActiveSessions(int $userId): array
    {
        try {
            $sql = "SELECT * FROM {$this->db->table('login_history')}
                    WHERE user_id = :user_id
                    AND logout_at IS NULL
                    ORDER BY login_at DESC";

            return $this->db->getConnection()->fetchAll($sql, [
                ':user_id' => $userId
            ]);

        } catch (\Exception $e) {
            Logger::error('Failed to get active sessions', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Terminate all active sessions for a user
     *
     * @param int $userId User ID
     * @return int Number of sessions terminated
     */
    public function terminateAllSessions(int $userId): int
    {
        try {
            $now = time();

            $count = $this->db->update('login_history', [
                'logout_at' => $now
            ], [
                'user_id' => $userId,
                'logout_at' => null
            ]);

            if ($count > 0) {
                Logger::info('All sessions terminated', [
                    'user_id' => $userId,
                    'count' => $count
                ]);
            }

            return $count;

        } catch (\Exception $e) {
            Logger::error('Failed to terminate sessions', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Get login history by IP address
     *
     * @param string $ipAddress IP address
     * @param int $limit Maximum records to return
     * @return array Login history records
     */
    public function getByIpAddress(string $ipAddress, int $limit = 50): array
    {
        try {
            $sql = "SELECT * FROM {$this->db->table('login_history')}
                    WHERE ip_address = :ip_address
                    ORDER BY login_at DESC
                    LIMIT :limit";

            return $this->db->getConnection()->fetchAll($sql, [
                ':ip_address' => $ipAddress,
                ':limit' => $limit
            ]);

        } catch (\Exception $e) {
            Logger::error('Failed to get history by IP', [
                'ip' => $ipAddress,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Count total logins for a user
     *
     * @param int $userId User ID
     * @param int|null $since Optional timestamp to count logins since
     * @return int Number of logins
     */
    public function countLogins(int $userId, ?int $since = null): int
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM {$this->db->table('login_history')}
                    WHERE user_id = :user_id";

            $params = [':user_id' => $userId];

            if ($since !== null) {
                $sql .= " AND login_at >= :since";
                $params[':since'] = $since;
            }

            $result = $this->db->getConnection()->fetchOne($sql, $params);
            return (int)($result['count'] ?? 0);

        } catch (\Exception $e) {
            Logger::error('Failed to count logins', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Delete login history for a user (called when user is deleted)
     *
     * @param int $userId User ID
     * @return int Number of records deleted
     */
    public function deleteHistory(int $userId): int
    {
        try {
            $count = $this->db->delete('login_history', ['user_id' => $userId]);

            if ($count > 0) {
                Logger::info('Login history deleted', [
                    'user_id' => $userId,
                    'count' => $count
                ]);
            }

            return $count;

        } catch (\Exception $e) {
            Logger::error('Failed to delete login history', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Clean up old login history records
     *
     * @param int $retentionDays Number of days to keep (default: 90)
     * @return int Number of records deleted
     */
    public function cleanupOldRecords(int $retentionDays = 90): int
    {
        try {
            $cutoffTime = time() - ($retentionDays * 86400);

            $sql = "DELETE FROM {$this->db->table('login_history')}
                    WHERE login_at < :cutoff_time";

            $this->db->getConnection()->execute($sql, [':cutoff_time' => $cutoffTime]);

            $count = $this->db->getConnection()->rowCount();

            if ($count > 0) {
                Logger::info('Old login history cleaned up', [
                    'retention_days' => $retentionDays,
                    'deleted' => $count
                ]);
            }

            return $count;

        } catch (\Exception $e) {
            Logger::error('Failed to cleanup login history', [
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Get login statistics for a user
     *
     * @param int $userId User ID
     * @return array Statistics
     */
    public function getStatistics(int $userId): array
    {
        try {
            $sql = "SELECT
                        COUNT(*) as total_logins,
                        COUNT(DISTINCT ip_address) as unique_ips,
                        MAX(login_at) as last_login,
                        MIN(login_at) as first_login,
                        AVG(CASE WHEN logout_at IS NOT NULL
                            THEN logout_at - login_at
                            ELSE NULL END) as avg_session_duration
                    FROM {$this->db->table('login_history')}
                    WHERE user_id = :user_id";

            $result = $this->db->getConnection()->fetchOne($sql, [
                ':user_id' => $userId
            ]);

            return [
                'total_logins' => (int)($result['total_logins'] ?? 0),
                'unique_ips' => (int)($result['unique_ips'] ?? 0),
                'last_login' => $result['last_login'] ?? null,
                'first_login' => $result['first_login'] ?? null,
                'avg_session_duration' => $result['avg_session_duration'] ?? null
            ];

        } catch (\Exception $e) {
            Logger::error('Failed to get login statistics', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get recent logins across all users (for admin dashboard)
     *
     * @param int $limit Maximum records to return
     * @return array Recent login records with user info
     */
    public function getRecentLogins(int $limit = 20): array
    {
        try {
            $sql = "SELECT lh.*, u.username, u.email
                    FROM {$this->db->table('login_history')} lh
                    INNER JOIN {$this->db->table('users')} u ON lh.user_id = u.id
                    ORDER BY lh.login_at DESC
                    LIMIT :limit";

            return $this->db->getConnection()->fetchAll($sql, [':limit' => $limit]);

        } catch (\Exception $e) {
            Logger::error('Failed to get recent logins', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
}
