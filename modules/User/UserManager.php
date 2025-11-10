<?php
/**
 * ISER - User Manager
 * @package ISER\Modules\User
 */

namespace ISER\Modules\User;

use ISER\Core\Database\Database;
use ISER\Core\Utils\Helpers;

class UserManager
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function create(array $data): int|false
    {
        $required = ['username', 'email', 'password', 'firstname', 'lastname'];
        foreach ($required as $field) {
            if (empty($data[$field])) return false;
        }

        if (!Helpers::validateEmail($data['email'])) return false;

        $now = time();
        return $this->db->insert('users', [
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => Helpers::hashPassword($data['password']),
            'firstname' => $data['firstname'],
            'lastname' => $data['lastname'],
            'status' => $data['status'] ?? 1,
            'failed_attempts' => 0,
            'locked_until' => 0,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
    }

    public function getUserById(int $id): array|false
    {
        return $this->db->selectOne('users', ['id' => $id]);
    }

    public function getUserByUsername(string $username): array|false
    {
        return $this->db->selectOne('users', ['username' => $username]);
    }

    public function getUserByEmail(string $email): array|false
    {
        return $this->db->selectOne('users', ['email' => $email]);
    }

    public function update(int $id, array $data): bool
    {
        $data['timemodified'] = time();
        if (isset($data['password'])) {
            $data['password'] = Helpers::hashPassword($data['password']);
        }
        return $this->db->update('users', $data, ['id' => $id]) > 0;
    }

    public function delete(int $id): bool
    {
        return $this->db->delete('users', ['id' => $id]) > 0;
    }

    /**
     * Soft delete a user (mark as deleted instead of removing)
     */
    public function softDelete(int $id): bool
    {
        return $this->update($id, ['deleted' => 1]);
    }

    /**
     * Restore a soft-deleted user
     */
    public function restore(int $id): bool
    {
        return $this->update($id, ['deleted' => 0]);
    }

    /**
     * Check if user is soft-deleted
     */
    public function isDeleted(int $id): bool
    {
        $user = $this->getUserById($id);
        return $user && ($user['deleted'] ?? 0) == 1;
    }

    /**
     * Suspend a user account
     */
    public function suspend(int $id): bool
    {
        return $this->update($id, ['suspended' => 1]);
    }

    /**
     * Reactivate a suspended user account
     */
    public function unsuspend(int $id): bool
    {
        return $this->update($id, ['suspended' => 0]);
    }

    /**
     * Check if user is suspended
     */
    public function isSuspended(int $id): bool
    {
        $user = $this->getUserById($id);
        return $user && ($user['suspended'] ?? 0) == 1;
    }

    /**
     * Update user's last login information
     */
    public function updateLastLogin(int $id, string $ip): bool
    {
        return $this->update($id, [
            'lastlogin' => time(),
            'lastip' => $ip
        ]);
    }

    /**
     * Get all users with pagination and filters
     */
    public function getUsers(int $limit = 50, int $offset = 0, array $filters = []): array
    {
        $sql = "SELECT * FROM {$this->db->table('users')} WHERE 1=1";
        $params = [];

        // Apply filters
        if (isset($filters['status'])) {
            $sql .= " AND status = :status";
            $params[':status'] = $filters['status'];
        }

        if (isset($filters['deleted'])) {
            $sql .= " AND deleted = :deleted";
            $params[':deleted'] = $filters['deleted'];
        } else {
            // By default, exclude deleted users
            $sql .= " AND deleted = 0";
        }

        if (isset($filters['suspended'])) {
            $sql .= " AND suspended = :suspended";
            $params[':suspended'] = $filters['suspended'];
        }

        if (isset($filters['search'])) {
            $sql .= " AND (username LIKE :search OR email LIKE :search
                     OR firstname LIKE :search OR lastname LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $sql .= " ORDER BY timecreated DESC LIMIT :limit OFFSET :offset";

        return $this->db->getConnection()->fetchAll($sql, array_merge($params, [
            ':limit' => $limit,
            ':offset' => $offset
        ]));
    }

    /**
     * Count total users with filters
     */
    public function countUsers(array $filters = []): int
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->db->table('users')} WHERE 1=1";
        $params = [];

        // Apply same filters as getUsers
        if (isset($filters['status'])) {
            $sql .= " AND status = :status";
            $params[':status'] = $filters['status'];
        }

        if (isset($filters['deleted'])) {
            $sql .= " AND deleted = :deleted";
            $params[':deleted'] = $filters['deleted'];
        } else {
            $sql .= " AND deleted = 0";
        }

        if (isset($filters['suspended'])) {
            $sql .= " AND suspended = :suspended";
            $params[':suspended'] = $filters['suspended'];
        }

        if (isset($filters['search'])) {
            $sql .= " AND (username LIKE :search OR email LIKE :search
                     OR firstname LIKE :search OR lastname LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $result = $this->db->getConnection()->fetchOne($sql, $params);
        return (int)($result['count'] ?? 0);
    }

    /**
     * Bulk update users
     */
    public function bulkUpdate(array $userIds, array $data): int
    {
        if (empty($userIds)) return 0;

        $data['timemodified'] = time();
        $updated = 0;

        foreach ($userIds as $userId) {
            if ($this->update($userId, $data)) {
                $updated++;
            }
        }

        return $updated;
    }

    /**
     * Bulk soft delete users
     */
    public function bulkSoftDelete(array $userIds): int
    {
        return $this->bulkUpdate($userIds, ['deleted' => 1]);
    }

    /**
     * Bulk suspend users
     */
    public function bulkSuspend(array $userIds): int
    {
        return $this->bulkUpdate($userIds, ['suspended' => 1]);
    }

    /**
     * Get user's full name
     */
    public function getFullName(int $id): string
    {
        $user = $this->getUserById($id);
        if (!$user) return '';
        return trim($user['firstname'] . ' ' . $user['lastname']);
    }

    /**
     * Check if username exists (excluding a specific user id for updates)
     */
    public function usernameExists(string $username, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->db->table('users')}
                WHERE username = :username";
        $params = [':username' => $username];

        if ($excludeId !== null) {
            $sql .= " AND id != :excludeId";
            $params[':excludeId'] = $excludeId;
        }

        $result = $this->db->getConnection()->fetchOne($sql, $params);
        return ($result['count'] ?? 0) > 0;
    }

    /**
     * Check if email exists (excluding a specific user id for updates)
     */
    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->db->table('users')}
                WHERE email = :email";
        $params = [':email' => $email];

        if ($excludeId !== null) {
            $sql .= " AND id != :excludeId";
            $params[':excludeId'] = $excludeId;
        }

        $result = $this->db->getConnection()->fetchOne($sql, $params);
        return ($result['count'] ?? 0) > 0;
    }

    public function recordLoginAttempt(string $username, bool $success, string $ip): void
    {
        $this->db->insert('login_attempts', [
            'username' => $username,
            'ip_address' => $ip,
            'user_agent' => Helpers::getUserAgent(),
            'success' => $success ? 1 : 0,
            'attempted_at' => time(),
        ]);
    }

    public function getFailedAttempts(string $username, int $timeWindow = 900): int
    {
        $since = time() - $timeWindow;
        $sql = "SELECT COUNT(*) as count FROM {$this->db->table('login_attempts')}
                WHERE username = :username AND success = 0 AND attempted_at > :since";
        $result = $this->db->getConnection()->fetchOne($sql, [
            ':username' => $username,
            ':since' => $since
        ]);
        return (int)($result['count'] ?? 0);
    }

    public function lockAccount(string $username, int $duration = 900): bool
    {
        $user = $this->getUserByUsername($username);
        if (!$user) return false;

        return $this->update($user['id'], [
            'locked_until' => time() + $duration,
            'failed_attempts' => $this->getFailedAttempts($username)
        ]);
    }

    public function isAccountLocked(string $username): bool
    {
        $user = $this->getUserByUsername($username);
        if (!$user) return false;
        return $user['locked_until'] > time();
    }

    public function resetFailedAttempts(string $username): bool
    {
        $user = $this->getUserByUsername($username);
        if (!$user) return false;
        return $this->update($user['id'], ['failed_attempts' => 0, 'locked_until' => 0]);
    }
}
