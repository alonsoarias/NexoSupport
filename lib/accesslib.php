<?php
/**
 * NexoSupport - Access Library (Global RBAC Functions)
 *
 * Global helper functions for Role-Based Access Control
 * Provides Moodle-style API for permission checking
 *
 * @package    core
 * @copyright  2024 ISER
 * @license    Proprietary
 */

defined('NEXOSUPPORT_INTERNAL') || die();

use core\role\access_manager;
use ISER\Core\Database\Database;

/**
 * Get access manager instance (singleton pattern)
 *
 * @return access_manager Access manager instance
 */
function get_access_manager(): access_manager
{
    static $instance = null;

    if ($instance === null) {
        $db = Database::getInstance();
        $instance = new access_manager($db);
    }

    return $instance;
}

/**
 * Check if user has a specific permission (capability)
 *
 * @param string $permission Permission slug (e.g., 'users.view', 'roles.create')
 * @param int|null $userid User ID (null = current user)
 * @return bool True if user has permission
 */
function has_capability(string $permission, ?int $userid = null): bool
{
    if ($userid === null) {
        $userid = get_current_userid();
    }

    $manager = get_access_manager();
    return $manager->user_has_permission($userid, $permission);
}

/**
 * Require user to have a specific permission (capability)
 * Throws exception if user doesn't have permission
 *
 * @param string $permission Permission slug
 * @param int|null $userid User ID (null = current user)
 * @throws Exception If user doesn't have permission
 */
function require_capability(string $permission, ?int $userid = null): void
{
    if (!has_capability($permission, $userid)) {
        $userid = $userid ?? get_current_userid();
        throw new Exception(
            "Access denied. User {$userid} requires permission '{$permission}'."
        );
    }
}

/**
 * Check if user has a specific role
 *
 * @param string $roleslug Role slug (e.g., 'admin', 'moderator')
 * @param int|null $userid User ID (null = current user)
 * @return bool True if user has role
 */
function user_has_role(string $roleslug, ?int $userid = null): bool
{
    if ($userid === null) {
        $userid = get_current_userid();
    }

    $manager = get_access_manager();
    return $manager->user_has_role($userid, $roleslug);
}

/**
 * Require user to have a specific role
 * Throws exception if user doesn't have role
 *
 * @param string $roleslug Role slug
 * @param int|null $userid User ID (null = current user)
 * @throws Exception If user doesn't have role
 */
function require_role(string $roleslug, ?int $userid = null): void
{
    if (!user_has_role($roleslug, $userid)) {
        $userid = $userid ?? get_current_userid();
        throw new Exception(
            "Access denied. User {$userid} requires role '{$roleslug}'."
        );
    }
}

/**
 * Get all roles for a user
 *
 * @param int|null $userid User ID (null = current user)
 * @return array Array of role objects
 */
function get_user_roles(?int $userid = null): array
{
    if ($userid === null) {
        $userid = get_current_userid();
    }

    $manager = get_access_manager();
    return $manager->get_user_roles($userid);
}

/**
 * Get all permissions for a user
 *
 * @param int|null $userid User ID (null = current user)
 * @return array Array of permission slugs
 */
function get_user_permissions(?int $userid = null): array
{
    if ($userid === null) {
        $userid = get_current_userid();
    }

    $manager = get_access_manager();
    return $manager->get_user_permissions($userid);
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
function assign_user_role(int $userid, int $roleid, ?int $assignedby = null, ?int $expiresat = null): bool
{
    if ($assignedby === null) {
        $assignedby = get_current_userid();
    }

    $manager = get_access_manager();
    return $manager->assign_role($userid, $roleid, $assignedby, $expiresat);
}

/**
 * Unassign role from user
 *
 * @param int $userid User ID
 * @param int $roleid Role ID
 * @return bool Success
 */
function unassign_user_role(int $userid, int $roleid): bool
{
    $manager = get_access_manager();
    return $manager->unassign_role($userid, $roleid);
}

/**
 * Grant permission to role
 *
 * @param int $roleid Role ID
 * @param int $permissionid Permission ID
 * @return bool Success
 */
function grant_role_permission(int $roleid, int $permissionid): bool
{
    $manager = get_access_manager();
    return $manager->grant_permission($roleid, $permissionid);
}

/**
 * Revoke permission from role
 *
 * @param int $roleid Role ID
 * @param int $permissionid Permission ID
 * @return bool Success
 */
function revoke_role_permission(int $roleid, int $permissionid): bool
{
    $manager = get_access_manager();
    return $manager->revoke_permission($roleid, $permissionid);
}

/**
 * Check if user is admin
 *
 * @param int|null $userid User ID (null = current user)
 * @return bool True if user is admin
 */
function is_admin(?int $userid = null): bool
{
    return user_has_role('admin', $userid);
}

/**
 * Require admin access
 * Throws exception if user is not admin
 *
 * @param int|null $userid User ID (null = current user)
 * @throws Exception If user is not admin
 */
function require_admin(?int $userid = null): void
{
    if (!is_admin($userid)) {
        $userid = $userid ?? get_current_userid();
        throw new Exception(
            "Access denied. Administrative privileges required."
        );
    }
}

/**
 * Check if user is logged in
 *
 * @return bool True if user is logged in
 */
function is_logged_in(): bool
{
    return isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
}

/**
 * Require user to be logged in
 * Throws exception if user is not logged in
 *
 * @throws Exception If user is not logged in
 */
function require_login(): void
{
    if (!is_logged_in()) {
        throw new Exception("Login required. Please authenticate.");
    }
}

/**
 * Get current user ID from session
 *
 * @return int Current user ID (0 if not logged in)
 */
function get_current_userid(): int
{
    return (int)($_SESSION['user_id'] ?? 0);
}

/**
 * Clear all RBAC caches
 * Useful after bulk role/permission changes
 */
function clear_access_caches(): void
{
    $manager = get_access_manager();
    $manager->clear_all_caches();
}

/**
 * Check if a role has a specific permission
 *
 * @param int $roleid Role ID
 * @param string $permission Permission slug
 * @return bool True if role has permission
 */
function role_has_permission(int $roleid, string $permission): bool
{
    $manager = get_access_manager();
    return $manager->role_has_permission($roleid, $permission);
}

/**
 * Convenience function: Check multiple permissions (ANY logic)
 * Returns true if user has ANY of the specified permissions
 *
 * @param array $permissions Array of permission slugs
 * @param int|null $userid User ID (null = current user)
 * @return bool True if user has at least one permission
 */
function has_any_capability(array $permissions, ?int $userid = null): bool
{
    foreach ($permissions as $permission) {
        if (has_capability($permission, $userid)) {
            return true;
        }
    }
    return false;
}

/**
 * Convenience function: Check multiple permissions (ALL logic)
 * Returns true if user has ALL of the specified permissions
 *
 * @param array $permissions Array of permission slugs
 * @param int|null $userid User ID (null = current user)
 * @return bool True if user has all permissions
 */
function has_all_capabilities(array $permissions, ?int $userid = null): bool
{
    foreach ($permissions as $permission) {
        if (!has_capability($permission, $userid)) {
            return false;
        }
    }
    return true;
}

/**
 * Convenience function: Require multiple permissions (ALL logic)
 * Throws exception if user doesn't have all permissions
 *
 * @param array $permissions Array of permission slugs
 * @param int|null $userid User ID (null = current user)
 * @throws Exception If user doesn't have all permissions
 */
function require_all_capabilities(array $permissions, ?int $userid = null): void
{
    if (!has_all_capabilities($permissions, $userid)) {
        $userid = $userid ?? get_current_userid();
        $permList = implode(', ', $permissions);
        throw new Exception(
            "Access denied. User {$userid} requires all permissions: {$permList}."
        );
    }
}

// ===== HELPER INSTANCE FUNCTIONS =====

/**
 * Get user helper instance (singleton pattern)
 *
 * @return \core\user\user_helper User helper instance
 */
function get_user_helper(): \core\user\user_helper
{
    static $instance = null;

    if ($instance === null) {
        $db = Database::getInstance();
        $instance = new \core\user\user_helper($db);
    }

    return $instance;
}

/**
 * Get role helper instance (singleton pattern)
 *
 * @return \core\role\role_helper Role helper instance
 */
function get_role_helper(): \core\role\role_helper
{
    static $instance = null;

    if ($instance === null) {
        $db = Database::getInstance();
        $instance = new \core\role\role_helper($db);
    }

    return $instance;
}
