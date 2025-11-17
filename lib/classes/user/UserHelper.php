<?php
/**
 * NexoSupport - User Helper Class
 *
 * Provides helper methods for user operations
 * Bridges legacy UserManager with new ISER\Core\User classes
 *
 * @package    ISER\Core\User
 * @copyright  2024 ISER
 * @license    Proprietary
 */

namespace ISER\Core\User;

use ISER\Core\Database\Database;
use ISER\User\UserManager as LegacyUserManager;

/**
 * User Helper - High-level user operations
 *
 * Provides convenient methods for common user operations
 * while maintaining compatibility with legacy code
 */
class UserHelper
{
    private Database $db;
    private UserRepository $repository;
    private ?LegacyUserManager $legacyManager = null;

    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->repository = new UserRepository($db);
    }

    /**
     * Get legacy UserManager instance for backward compatibility
     *
     * @return LegacyUserManager
     */
    private function getLegacyManager(): LegacyUserManager
    {
        if ($this->legacyManager === null) {
            $this->legacyManager = new LegacyUserManager($this->db);
        }
        return $this->legacyManager;
    }

    /**
     * Get user by ID (returns ISER\Core\User\User object)
     *
     * @param int $id User ID
     * @return User|null User object or null
     */
    public function get_user(int $id): ?User
    {
        return $this->repository->get_by_id($id);
    }

    /**
     * Get user by username
     *
     * @param string $username Username
     * @return User|null User object or null
     */
    public function get_user_by_username(string $username): ?User
    {
        return $this->repository->get_by_username($username);
    }

    /**
     * Get user by email
     *
     * @param string $email Email address
     * @return User|null User object or null
     */
    public function get_user_by_email(string $email): ?User
    {
        return $this->repository->get_by_email($email);
    }

    /**
     * Create new user
     *
     * @param array $data User data (username, email, password, etc.)
     * @return int|null User ID if created, null on failure
     */
    public function create_user(array $data): ?int
    {
        // Hash password if provided
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        return $this->repository->create($data);
    }

    /**
     * Update user
     *
     * @param int $id User ID
     * @param array $data Data to update
     * @return bool Success
     */
    public function update_user(int $id, array $data): bool
    {
        // Hash password if being updated
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        } else {
            // Don't update password if empty
            unset($data['password']);
        }

        return $this->repository->update($id, $data);
    }

    /**
     * Delete user (soft delete)
     *
     * @param int $id User ID
     * @return bool Success
     */
    public function delete_user(int $id): bool
    {
        return $this->repository->soft_delete($id);
    }

    /**
     * Restore deleted user
     *
     * @param int $id User ID
     * @return bool Success
     */
    public function restore_user(int $id): bool
    {
        return $this->repository->restore($id);
    }

    /**
     * Check if username exists
     *
     * @param string $username Username to check
     * @param int|null $excludeId User ID to exclude from check
     * @return bool True if exists
     */
    public function username_exists(string $username, ?int $excludeId = null): bool
    {
        return $this->repository->username_exists($username, $excludeId);
    }

    /**
     * Check if email exists
     *
     * @param string $email Email to check
     * @param int|null $excludeId User ID to exclude from check
     * @return bool True if exists
     */
    public function email_exists(string $email, ?int $excludeId = null): bool
    {
        return $this->repository->email_exists($email, $excludeId);
    }

    /**
     * Get all users with filters (for admin listing)
     * Returns array format compatible with legacy code
     *
     * @param int $limit Limit
     * @param int $offset Offset
     * @param array $filters Filters (status, search, deleted)
     * @return array Users array
     */
    public function get_users_list(int $limit = 20, int $offset = 0, array $filters = []): array
    {
        // Use legacy manager for complex queries
        // TODO: Migrate this to ISER\Core\User\UserRepository
        return $this->getLegacyManager()->getUsers($limit, $offset, $filters);
    }

    /**
     * Count users with filters
     *
     * @param array $filters Filters
     * @return int User count
     */
    public function count_users(array $filters = []): int
    {
        // Use legacy manager for now
        return $this->getLegacyManager()->countUsers($filters);
    }

    /**
     * Get user roles (uses legacy system for now)
     *
     * @param int $userId User ID
     * @return array Roles array
     */
    public function get_user_roles(int $userId): array
    {
        // Use new RBAC system
        return get_user_roles($userId);
    }

    /**
     * Validate user data
     *
     * @param array $data User data to validate
     * @param bool $isUpdate Whether this is an update (vs create)
     * @return array Validation errors (empty if valid)
     */
    public function validate_user_data(array $data, bool $isUpdate = false): array
    {
        $errors = [];

        // Username validation
        if (!$isUpdate || isset($data['username'])) {
            if (empty($data['username'])) {
                $errors['username'] = 'Username is required';
            } elseif (strlen($data['username']) < 3) {
                $errors['username'] = 'Username must be at least 3 characters';
            } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $data['username'])) {
                $errors['username'] = 'Username can only contain letters, numbers, and underscores';
            }
        }

        // Email validation
        if (!$isUpdate || isset($data['email'])) {
            if (empty($data['email'])) {
                $errors['email'] = 'Email is required';
            } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Invalid email format';
            }
        }

        // Password validation (only for new users or if password provided)
        if (!$isUpdate) {
            if (empty($data['password'])) {
                $errors['password'] = 'Password is required';
            } elseif (strlen($data['password']) < 8) {
                $errors['password'] = 'Password must be at least 8 characters';
            }
        } elseif (isset($data['password']) && !empty($data['password'])) {
            if (strlen($data['password']) < 8) {
                $errors['password'] = 'Password must be at least 8 characters';
            }
        }

        return $errors;
    }

    /**
     * Get current logged in user
     *
     * @return User|null Current user or null
     */
    public function get_current_user(): ?User
    {
        $userId = get_current_userid();
        if ($userId === 0) {
            return null;
        }
        return $this->get_user($userId);
    }
}
