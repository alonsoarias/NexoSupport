<?php
/**
 * NexoSupport - Roles Compatibility Layer
 *
 * Provides backward compatibility between old ISER\Roles namespace
 * and new core\role Frankenstyle architecture
 *
 * @package    core
 * @copyright  2024 ISER
 * @license    Proprietary
 */

defined('NEXOSUPPORT_INTERNAL') || die();

use ISER\Roles\RoleManager as LegacyRoleManager;
use ISER\Roles\PermissionManager as LegacyPermissionManager;
use core\role\access_manager;
use ISER\Core\Database\Database;

/**
 * Compatibility wrapper for RoleManager
 *
 * Extends legacy RoleManager to maintain backward compatibility
 * while gradually migrating to new core\role system
 */
class RoleManagerCompat extends LegacyRoleManager
{
    private access_manager $accessManager;

    public function __construct(Database $db)
    {
        parent::__construct($db);
        $this->accessManager = new access_manager($db);
    }

    /**
     * Get access manager instance for new functionality
     *
     * @return access_manager
     */
    public function getAccessManager(): access_manager
    {
        return $this->accessManager;
    }

    /**
     * Override assignPermission to also update new system
     *
     * @param int $roleId Role ID
     * @param int $permissionId Permission ID
     * @return bool Success
     */
    public function assignPermission(int $roleId, int $permissionId): bool
    {
        // Call parent (legacy) method
        $legacySuccess = parent::assignPermission($roleId, $permissionId);

        // Also update new core system
        $coreSuccess = $this->accessManager->grant_permission($roleId, $permissionId);

        return $legacySuccess && $coreSuccess;
    }

    /**
     * Override removePermission to also update new system
     *
     * @param int $roleId Role ID
     * @param int $permissionId Permission ID
     * @return bool Success
     */
    public function removePermission(int $roleId, int $permissionId): bool
    {
        // Call parent (legacy) method
        $legacySuccess = parent::removePermission($roleId, $permissionId);

        // Also update new core system
        $coreSuccess = $this->accessManager->revoke_permission($roleId, $permissionId);

        return $legacySuccess && $coreSuccess;
    }

    /**
     * Override syncPermissions to also update new system
     *
     * @param int $roleId Role ID
     * @param array $permissionIds Array of Permission IDs
     * @return bool Success
     */
    public function syncPermissions(int $roleId, array $permissionIds): bool
    {
        // Call parent (legacy) method
        $legacySuccess = parent::syncPermissions($roleId, $permissionIds);

        // Clear all permissions for role in new system
        // (Note: This assumes we'll add a method to clear all role permissions)
        // For now, we'll just rely on parent method

        // Add new permissions
        foreach ($permissionIds as $permissionId) {
            $this->accessManager->grant_permission($roleId, (int)$permissionId);
        }

        return $legacySuccess;
    }
}

/**
 * Compatibility wrapper for PermissionManager
 *
 * Extends legacy PermissionManager to maintain backward compatibility
 * while gradually migrating to new core\role system
 */
class PermissionManagerCompat extends LegacyPermissionManager
{
    private access_manager $accessManager;

    public function __construct(Database $db)
    {
        parent::__construct($db);
        $this->accessManager = new access_manager($db);
    }

    /**
     * Get access manager instance for new functionality
     *
     * @return access_manager
     */
    public function getAccessManager(): access_manager
    {
        return $this->accessManager;
    }

    /**
     * Check capability using new system as fallback
     *
     * Tries legacy system first, falls back to new core system
     *
     * @param int $userId User ID
     * @param string $capability Capability name
     * @param int $contextId Context ID
     * @return bool True if user has permission
     */
    public function hasCapability(int $userId, string $capability, int $contextId = 1): bool
    {
        // Try legacy system first
        try {
            return parent::hasCapability($userId, $capability, $contextId);
        } catch (\Exception $e) {
            // Fall back to new core system
            // Convert Moodle-style capability to permission slug
            // e.g., 'moodle/user:create' -> 'users.create'
            $permissionSlug = $this->convertCapabilityToPermission($capability);
            return $this->accessManager->user_has_permission($userId, $permissionSlug);
        }
    }

    /**
     * Check if user is admin using new system
     *
     * @param int $userId User ID
     * @return bool True if user is admin
     */
    public function isAdmin(int $userId): bool
    {
        // Check using new core system
        return $this->accessManager->user_has_role($userId, 'admin');
    }

    /**
     * Clear cache in both systems
     *
     * @param int|null $userId User ID (null = all users)
     */
    public function clearCache(?int $userId = null): void
    {
        // Clear legacy cache
        parent::clearCache($userId);

        // Clear new core cache
        if ($userId === null) {
            $this->accessManager->clear_all_caches();
        } else {
            // Note: access_manager doesn't have per-user clear yet
            // For now, clear all
            $this->accessManager->clear_all_caches();
        }
    }

    /**
     * Convert Moodle-style capability to permission slug
     *
     * @param string $capability Capability name (e.g., 'moodle/user:create')
     * @return string Permission slug (e.g., 'users.create')
     */
    private function convertCapabilityToPermission(string $capability): string
    {
        // Remove 'moodle/' prefix
        $capability = str_replace('moodle/', '', $capability);

        // Convert ':' to '.'
        $capability = str_replace(':', '.', $capability);

        // Pluralize if needed (basic implementation)
        $parts = explode('.', $capability);
        if (count($parts) === 2) {
            $module = $parts[0];
            $action = $parts[1];

            // Simple pluralization
            if (!str_ends_with($module, 's')) {
                $module .= 's';
            }

            return "{$module}.{$action}";
        }

        return $capability;
    }
}

/**
 * Factory function to get RoleManager with compatibility
 *
 * @deprecated Use ISER\Core\Role\RoleHelper or ISER\Core\Role\AccessManager directly
 * @param Database|null $db Database instance (null = get default)
 * @return RoleManagerCompat Role manager instance
 */
function get_role_manager(?Database $db = null): RoleManagerCompat
{
    static $instance = null;

    if ($instance === null) {
        $db = $db ?? Database::getInstance();
        $instance = new RoleManagerCompat($db);
    }

    return $instance;
}

/**
 * Factory function to get PermissionManager with compatibility
 *
 * @deprecated Use ISER\Core\Role\AccessManager directly
 * @param Database|null $db Database instance (null = get default)
 * @return PermissionManagerCompat Permission manager instance
 */
function get_permission_manager(?Database $db = null): PermissionManagerCompat
{
    static $instance = null;

    if ($instance === null) {
        $db = $db ?? Database::getInstance();
        $instance = new PermissionManagerCompat($db);
    }

    return $instance;
}
