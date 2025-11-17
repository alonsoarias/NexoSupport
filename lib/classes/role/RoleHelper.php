<?php
/**
 * NexoSupport - Role Helper Class
 *
 * Provides helper methods for role and permission operations
 * Bridges legacy RoleManager with new ISER\Core\Role classes
 *
 * @package    ISER\Core\Role
 * @copyright  2024 ISER
 * @license    Proprietary
 */

namespace ISER\Core\Role;

use ISER\Core\Database\Database;
use ISER\Roles\RoleManager as LegacyRoleManager;

/**
 * Role Helper - High-level role operations
 *
 * Provides convenient methods for common role operations
 * while maintaining compatibility with legacy code
 */
class RoleHelper
{
    private Database $db;
    private AccessManager $accessManager;
    private ?LegacyRoleManager $legacyManager = null;

    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->accessManager = new AccessManager($db);
    }

    /**
     * Get legacy RoleManager instance for backward compatibility
     *
     * @return LegacyRoleManager
     */
    private function getLegacyManager(): LegacyRoleManager
    {
        if ($this->legacyManager === null) {
            $this->legacyManager = new LegacyRoleManager($this->db);
        }
        return $this->legacyManager;
    }

    /**
     * Get role by ID
     *
     * @param int $id Role ID
     * @return array|null Role data or null
     */
    public function get_role(int $id): ?array
    {
        return $this->getLegacyManager()->getRoleById($id);
    }

    /**
     * Get role by slug
     *
     * @param string $slug Role slug
     * @return array|null Role data or null
     */
    public function get_role_by_slug(string $slug): ?array
    {
        return $this->getLegacyManager()->getRoleBySlug($slug);
    }

    /**
     * Get all roles
     *
     * @param int $limit Limit
     * @param int $offset Offset
     * @param array $filters Filters (is_system)
     * @return array Roles array
     */
    public function get_roles_list(int $limit = 100, int $offset = 0, array $filters = []): array
    {
        return $this->getLegacyManager()->getRoles($limit, $offset, $filters);
    }

    /**
     * Count roles
     *
     * @param array $filters Filters
     * @return int Role count
     */
    public function count_roles(array $filters = []): int
    {
        return $this->getLegacyManager()->countRoles($filters);
    }

    /**
     * Get role permissions
     *
     * @param int $roleId Role ID
     * @return array Permissions array
     */
    public function get_role_permissions(int $roleId): array
    {
        return $this->getLegacyManager()->getRolePermissions($roleId);
    }

    /**
     * Get users with a specific role
     *
     * @param int $roleId Role ID
     * @return array Users array
     */
    public function get_role_users(int $roleId): array
    {
        return $this->getLegacyManager()->getRoleUsers($roleId);
    }

    /**
     * Assign permission to role (uses new RBAC system)
     *
     * @param int $roleId Role ID
     * @param int $permissionId Permission ID
     * @return bool Success
     */
    public function assign_permission(int $roleId, int $permissionId): bool
    {
        // Use new AccessManager
        return $this->accessManager->grant_permission($roleId, $permissionId);
    }

    /**
     * Remove permission from role (uses new RBAC system)
     *
     * @param int $roleId Role ID
     * @param int $permissionId Permission ID
     * @return bool Success
     */
    public function remove_permission(int $roleId, int $permissionId): bool
    {
        // Use new AccessManager
        return $this->accessManager->revoke_permission($roleId, $permissionId);
    }

    /**
     * Sync role permissions (uses new RBAC system)
     *
     * @param int $roleId Role ID
     * @param array $permissionIds Array of permission IDs
     * @return bool Success
     */
    public function sync_permissions(int $roleId, array $permissionIds): bool
    {
        // Use legacy manager for now (it handles the sync logic)
        return $this->getLegacyManager()->syncPermissions($roleId, $permissionIds);
    }

    /**
     * Assign role to user (uses new RBAC system)
     *
     * @param int $userId User ID
     * @param int $roleId Role ID
     * @param int|null $assignedBy User ID who assigned
     * @param int|null $expiresAt Expiration timestamp
     * @return bool Success
     */
    public function assign_role_to_user(int $userId, int $roleId, ?int $assignedBy = null, ?int $expiresAt = null): bool
    {
        if ($assignedBy === null) {
            $assignedBy = get_current_userid();
        }

        return $this->accessManager->assign_role($userId, $roleId, $assignedBy, $expiresAt);
    }

    /**
     * Unassign role from user (uses new RBAC system)
     *
     * @param int $userId User ID
     * @param int $roleId Role ID
     * @return bool Success
     */
    public function unassign_role_from_user(int $userId, int $roleId): bool
    {
        return $this->accessManager->unassign_role($userId, $roleId);
    }

    /**
     * Check if user has role (uses new RBAC system)
     *
     * @param int $userId User ID
     * @param string $roleSlug Role slug
     * @return bool True if user has role
     */
    public function user_has_role(int $userId, string $roleSlug): bool
    {
        return $this->accessManager->user_has_role($userId, $roleSlug);
    }

    /**
     * Check if user has permission (uses new RBAC system)
     *
     * @param int $userId User ID
     * @param string $permission Permission slug
     * @return bool True if user has permission
     */
    public function user_has_permission(int $userId, string $permission): bool
    {
        return $this->accessManager->user_has_permission($userId, $permission);
    }

    /**
     * Get user roles (uses new RBAC system)
     *
     * @param int $userId User ID
     * @return array Roles array
     */
    public function get_user_roles(int $userId): array
    {
        return $this->accessManager->get_user_roles($userId);
    }

    /**
     * Get user permissions (uses new RBAC system)
     *
     * @param int $userId User ID
     * @return array Permission slugs array
     */
    public function get_user_permissions(int $userId): array
    {
        return $this->accessManager->get_user_permissions($userId);
    }

    /**
     * Clear RBAC caches
     */
    public function clear_caches(): void
    {
        $this->accessManager->clear_all_caches();
    }

    /**
     * Validate role data
     *
     * @param array $data Role data to validate
     * @param bool $isUpdate Whether this is an update
     * @return array Validation errors (empty if valid)
     */
    public function validate_role_data(array $data, bool $isUpdate = false): array
    {
        $errors = [];

        // Name validation
        if (!$isUpdate || isset($data['name'])) {
            if (empty($data['name'])) {
                $errors['name'] = 'Role name is required';
            } elseif (strlen($data['name']) < 2) {
                $errors['name'] = 'Role name must be at least 2 characters';
            }
        }

        // Slug validation
        if (!$isUpdate || isset($data['slug'])) {
            if (empty($data['slug'])) {
                $errors['slug'] = 'Role slug is required';
            } elseif (!preg_match('/^[a-z0-9_]+$/', $data['slug'])) {
                $errors['slug'] = 'Slug can only contain lowercase letters, numbers, and underscores';
            }
        }

        return $errors;
    }

    /**
     * Check if role can be deleted
     *
     * @param int $roleId Role ID
     * @return bool True if can be deleted
     */
    public function can_delete_role(int $roleId): bool
    {
        $role = $this->get_role($roleId);
        if (!$role) {
            return false;
        }

        // Cannot delete system roles
        if (!empty($role['is_system'])) {
            return false;
        }

        return true;
    }

    /**
     * Delete role
     *
     * @param int $roleId Role ID
     * @return bool Success
     */
    public function delete_role(int $roleId): bool
    {
        if (!$this->can_delete_role($roleId)) {
            return false;
        }

        return $this->getLegacyManager()->delete($roleId);
    }
}
