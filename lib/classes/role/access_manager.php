<?php
/**
 * NexoSupport - Access Manager (RBAC Core)
 *
 * Central component for Role-Based Access Control
 * Similar to Moodle's access manager
 *
 * @package    core\role
 * @copyright  2024 ISER
 * @license    Proprietary
 */

namespace core\role;

defined('NEXOSUPPORT_INTERNAL') || die();

use ISER\Core\Database\Database;

/**
 * Access Manager Class
 *
 * Handles permission checking and role assignment
 */
class access_manager
{
    /** @var Database Database instance */
    private Database $db;

    /** @var array Permission cache */
    private static array $permissionCache = [];

    /** @var array Role cache */
    private static array $roleCache = [];

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
     * Check if user has a specific permission
     *
     * @param int $userid User ID
     * @param string $permission Permission slug (e.g., 'users.view')
     * @return bool True if user has permission
     */
    public function user_has_permission(int $userid, string $permission): bool
    {
        // Check cache first
        $cacheKey = "user_{$userid}_perm_{$permission}";
        if (isset(self::$permissionCache[$cacheKey])) {
            return self::$permissionCache[$cacheKey];
        }

        // Get user roles
        $roles = $this->get_user_roles($userid);

        if (empty($roles)) {
            self::$permissionCache[$cacheKey] = false;
            return false;
        }

        // Check if any role has the permission
        foreach ($roles as $role) {
            if ($this->role_has_permission($role->id, $permission)) {
                self::$permissionCache[$cacheKey] = true;
                return true;
            }
        }

        self::$permissionCache[$cacheKey] = false;
        return false;
    }

    /**
     * Check if role has a specific permission
     *
     * @param int $roleid Role ID
     * @param string $permission Permission slug
     * @return bool True if role has permission
     */
    public function role_has_permission(int $roleid, string $permission): bool
    {
        $sql = "SELECT COUNT(*) as total
                FROM " . DB_PREFIX . "role_permissions rp
                JOIN " . DB_PREFIX . "permissions p ON rp.permission_id = p.id
                WHERE rp.role_id = :roleid AND p.slug = :permission";

        $result = $this->db->query($sql, [
            'roleid' => $roleid,
            'permission' => $permission
        ]);

        return (int)($result[0]->total ?? 0) > 0;
    }

    /**
     * Get all roles for a user
     *
     * @param int $userid User ID
     * @return array Array of role objects
     */
    public function get_user_roles(int $userid): array
    {
        // Check cache
        $cacheKey = "user_{$userid}_roles";
        if (isset(self::$roleCache[$cacheKey])) {
            return self::$roleCache[$cacheKey];
        }

        $sql = "SELECT r.*
                FROM " . DB_PREFIX . "roles r
                JOIN " . DB_PREFIX . "user_roles ur ON r.id = ur.role_id
                WHERE ur.user_id = :userid
                AND (ur.expires_at IS NULL OR ur.expires_at > :now)";

        $results = $this->db->query($sql, [
            'userid' => $userid,
            'now' => time()
        ]);

        $roles = [];
        foreach ($results as $row) {
            $roles[] = new role($row);
        }

        self::$roleCache[$cacheKey] = $roles;
        return $roles;
    }

    /**
     * Check if user has a specific role
     *
     * @param int $userid User ID
     * @param string $roleslug Role slug (e.g., 'admin', 'moderator')
     * @return bool True if user has role
     */
    public function user_has_role(int $userid, string $roleslug): bool
    {
        $roles = $this->get_user_roles($userid);

        foreach ($roles as $role) {
            if ($role->slug === $roleslug) {
                return true;
            }
        }

        return false;
    }

    /**
     * Assign role to user
     *
     * @param int $userid User ID
     * @param int $roleid Role ID
     * @param int|null $assignedby User ID who assigned (for audit)
     * @param int|null $expiresat Expiration timestamp (optional)
     * @return bool Success
     */
    public function assign_role(int $userid, int $roleid, ?int $assignedby = null, ?int $expiresat = null): bool
    {
        // Check if assignment already exists
        $sql = "SELECT COUNT(*) as total FROM " . DB_PREFIX . "user_roles
                WHERE user_id = :userid AND role_id = :roleid";

        $result = $this->db->query($sql, [
            'userid' => $userid,
            'roleid' => $roleid
        ]);

        if ((int)($result[0]->total ?? 0) > 0) {
            return true; // Already assigned
        }

        // Insert assignment
        $sql = "INSERT INTO " . DB_PREFIX . "user_roles
                (user_id, role_id, assigned_at, assigned_by, expires_at)
                VALUES
                (:userid, :roleid, :assignedat, :assignedby, :expiresat)";

        $success = $this->db->execute($sql, [
            'userid' => $userid,
            'roleid' => $roleid,
            'assignedat' => time(),
            'assignedby' => $assignedby,
            'expiresat' => $expiresat
        ]);

        // Clear cache
        $this->clear_user_cache($userid);

        return $success;
    }

    /**
     * Unassign role from user
     *
     * @param int $userid User ID
     * @param int $roleid Role ID
     * @return bool Success
     */
    public function unassign_role(int $userid, int $roleid): bool
    {
        $sql = "DELETE FROM " . DB_PREFIX . "user_roles
                WHERE user_id = :userid AND role_id = :roleid";

        $success = $this->db->execute($sql, [
            'userid' => $userid,
            'roleid' => $roleid
        ]);

        // Clear cache
        $this->clear_user_cache($userid);

        return $success;
    }

    /**
     * Get all permissions for a user (aggregated from roles)
     *
     * @param int $userid User ID
     * @return array Array of permission slugs
     */
    public function get_user_permissions(int $userid): array
    {
        $sql = "SELECT DISTINCT p.slug
                FROM " . DB_PREFIX . "permissions p
                JOIN " . DB_PREFIX . "role_permissions rp ON p.id = rp.permission_id
                JOIN " . DB_PREFIX . "user_roles ur ON rp.role_id = ur.role_id
                WHERE ur.user_id = :userid
                AND (ur.expires_at IS NULL OR ur.expires_at > :now)
                ORDER BY p.slug";

        $results = $this->db->query($sql, [
            'userid' => $userid,
            'now' => time()
        ]);

        return array_column($results, 'slug');
    }

    /**
     * Grant permission to role
     *
     * @param int $roleid Role ID
     * @param int $permissionid Permission ID
     * @return bool Success
     */
    public function grant_permission(int $roleid, int $permissionid): bool
    {
        // Check if already granted
        $sql = "SELECT COUNT(*) as total FROM " . DB_PREFIX . "role_permissions
                WHERE role_id = :roleid AND permission_id = :permissionid";

        $result = $this->db->query($sql, [
            'roleid' => $roleid,
            'permissionid' => $permissionid
        ]);

        if ((int)($result[0]->total ?? 0) > 0) {
            return true; // Already granted
        }

        // Insert grant
        $sql = "INSERT INTO " . DB_PREFIX . "role_permissions
                (role_id, permission_id, granted_at)
                VALUES
                (:roleid, :permissionid, :grantedat)";

        $success = $this->db->execute($sql, [
            'roleid' => $roleid,
            'permissionid' => $permissionid,
            'grantedat' => time()
        ]);

        // Clear all permission caches
        self::$permissionCache = [];

        return $success;
    }

    /**
     * Revoke permission from role
     *
     * @param int $roleid Role ID
     * @param int $permissionid Permission ID
     * @return bool Success
     */
    public function revoke_permission(int $roleid, int $permissionid): bool
    {
        $sql = "DELETE FROM " . DB_PREFIX . "role_permissions
                WHERE role_id = :roleid AND permission_id = :permissionid";

        $success = $this->db->execute($sql, [
            'roleid' => $roleid,
            'permissionid' => $permissionid
        ]);

        // Clear all permission caches
        self::$permissionCache = [];

        return $success;
    }

    /**
     * Clear cache for specific user
     *
     * @param int $userid User ID
     */
    private function clear_user_cache(int $userid): void
    {
        // Clear role cache
        unset(self::$roleCache["user_{$userid}_roles"]);

        // Clear permission cache (all entries for this user)
        foreach (array_keys(self::$permissionCache) as $key) {
            if (strpos($key, "user_{$userid}_") === 0) {
                unset(self::$permissionCache[$key]);
            }
        }
    }

    /**
     * Clear all caches
     */
    public function clear_all_caches(): void
    {
        self::$permissionCache = [];
        self::$roleCache = [];
    }
}
