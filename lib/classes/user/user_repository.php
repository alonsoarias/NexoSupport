<?php
/**
 * NexoSupport - User Repository
 *
 * Data access layer for users
 *
 * @package    core\user
 * @copyright  2024 ISER
 * @license    Proprietary
 */

namespace core\user;

defined('NEXOSUPPORT_INTERNAL') || die();

use ISER\Core\Database\Database;

/**
 * User Repository Class
 *
 * Handles database operations for users
 */
class user_repository
{
    /** @var Database Database instance */
    private Database $db;

    /** @var string Table name */
    private string $table;

    /**
     * Constructor
     *
     * @param Database $db Database instance
     */
    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->table = DB_PREFIX . 'users';
    }

    /**
     * Get user by ID
     *
     * @param int $id User ID
     * @return user|null User object or null if not found
     */
    public function get_by_id(int $id): ?user
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id LIMIT 1";
        $result = $this->db->query($sql, ['id' => $id]);

        if (empty($result)) {
            return null;
        }

        return new user($result[0]);
    }

    /**
     * Get user by username
     *
     * @param string $username Username
     * @return user|null User object or null if not found
     */
    public function get_by_username(string $username): ?user
    {
        $sql = "SELECT * FROM {$this->table} WHERE username = :username LIMIT 1";
        $result = $this->db->query($sql, ['username' => $username]);

        if (empty($result)) {
            return null;
        }

        return new user($result[0]);
    }

    /**
     * Get user by email
     *
     * @param string $email Email
     * @return user|null User object or null if not found
     */
    public function get_by_email(string $email): ?user
    {
        $sql = "SELECT * FROM {$this->table} WHERE email = :email LIMIT 1";
        $result = $this->db->query($sql, ['email' => $email]);

        if (empty($result)) {
            return null;
        }

        return new user($result[0]);
    }

    /**
     * Get all active users
     *
     * @param int $limit Limit
     * @param int $offset Offset
     * @return array Array of user objects
     */
    public function get_all_active(int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE status = 'active' AND deleted_at IS NULL
                ORDER BY username ASC
                LIMIT :limit OFFSET :offset";

        $results = $this->db->query($sql, [
            'limit' => $limit,
            'offset' => $offset
        ]);

        $users = [];
        foreach ($results as $row) {
            $users[] = new user($row);
        }

        return $users;
    }

    /**
     * Count total users
     *
     * @param bool $activeOnly Count only active users
     * @return int Total count
     */
    public function count_users(bool $activeOnly = false): int
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        $params = [];

        if ($activeOnly) {
            $sql .= " WHERE status = 'active' AND deleted_at IS NULL";
        }

        $result = $this->db->query($sql, $params);

        return (int)($result[0]->total ?? 0);
    }

    /**
     * Create new user
     *
     * @param array $data User data
     * @return int|null New user ID or null on failure
     */
    public function create(array $data): ?int
    {
        $now = time();

        $sql = "INSERT INTO {$this->table}
                (username, email, password, first_name, last_name, status, created_at, updated_at)
                VALUES
                (:username, :email, :password, :firstname, :lastname, :status, :createdat, :updatedat)";

        $params = [
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => $data['password'], // Already hashed
            'firstname' => $data['firstname'] ?? '',
            'lastname' => $data['lastname'] ?? '',
            'status' => $data['status'] ?? 'active',
            'createdat' => $now,
            'updatedat' => $now,
        ];

        $success = $this->db->execute($sql, $params);

        if ($success) {
            return (int)$this->db->getLastInsertId();
        }

        return null;
    }

    /**
     * Update user
     *
     * @param int $id User ID
     * @param array $data User data to update
     * @return bool Success
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = ['id' => $id];

        // Build dynamic UPDATE query
        $allowedFields = ['username', 'email', 'first_name', 'last_name', 'status', 'email_verified'];

        foreach ($allowedFields as $field) {
            $paramKey = str_replace('_', '', $field);
            if (isset($data[$paramKey])) {
                $fields[] = "{$field} = :{$paramKey}";
                $params[$paramKey] = $data[$paramKey];
            }
        }

        if (empty($fields)) {
            return false;
        }

        // Always update updated_at
        $fields[] = "updated_at = :updatedat";
        $params['updatedat'] = time();

        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = :id";

        return $this->db->execute($sql, $params);
    }

    /**
     * Soft delete user
     *
     * @param int $id User ID
     * @return bool Success
     */
    public function soft_delete(int $id): bool
    {
        $sql = "UPDATE {$this->table}
                SET deleted_at = :deletedat, updated_at = :updatedat
                WHERE id = :id";

        $now = time();

        return $this->db->execute($sql, [
            'id' => $id,
            'deletedat' => $now,
            'updatedat' => $now,
        ]);
    }

    /**
     * Restore soft-deleted user
     *
     * @param int $id User ID
     * @return bool Success
     */
    public function restore(int $id): bool
    {
        $sql = "UPDATE {$this->table}
                SET deleted_at = NULL, updated_at = :updatedat
                WHERE id = :id";

        return $this->db->execute($sql, [
            'id' => $id,
            'updatedat' => time(),
        ]);
    }

    /**
     * Check if username exists
     *
     * @param string $username Username
     * @param int|null $excludeId Exclude user ID (for updates)
     * @return bool True if exists
     */
    public function username_exists(string $username, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE username = :username";
        $params = ['username' => $username];

        if ($excludeId) {
            $sql .= " AND id != :excludeid";
            $params['excludeid'] = $excludeId;
        }

        $result = $this->db->query($sql, $params);

        return (int)($result[0]->total ?? 0) > 0;
    }

    /**
     * Check if email exists
     *
     * @param string $email Email
     * @param int|null $excludeId Exclude user ID (for updates)
     * @return bool True if exists
     */
    public function email_exists(string $email, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE email = :email";
        $params = ['email' => $email];

        if ($excludeId) {
            $sql .= " AND id != :excludeid";
            $params['excludeid'] = $excludeId;
        }

        $result = $this->db->query($sql, $params);

        return (int)($result[0]->total ?? 0) > 0;
    }
}
