<?php
/**
 * NexoSupport - Role Management Library Functions
 *
 * Library of functions and constants for role management component
 *
 * @package    admin
 * @subpackage roles
 * @copyright  2024 ISER
 * @license    Proprietary
 */

defined('NEXOSUPPORT_INTERNAL') || die();

// Autoload classes
require_once(__DIR__ . '/classes/RoleViewHelper.php');

use ISER\Admin\Roles\RoleViewHelper;

/**
 * Get role management capabilities required
 *
 * @return array Array of capabilities
 */
function admin_roles_get_capabilities(): array
{
    return [
        'roles.view' => [
            'name' => 'View roles',
            'description' => 'View role list and details',
            'module' => 'admin_roles',
        ],
        'roles.create' => [
            'name' => 'Create roles',
            'description' => 'Create new roles',
            'module' => 'admin_roles',
        ],
        'roles.edit' => [
            'name' => 'Edit roles',
            'description' => 'Edit role details',
            'module' => 'admin_roles',
        ],
        'roles.delete' => [
            'name' => 'Delete roles',
            'description' => 'Delete roles (non-system only)',
            'module' => 'admin_roles',
        ],
        'roles.assign_permissions' => [
            'name' => 'Assign permissions',
            'description' => 'Assign and revoke permissions to/from roles',
            'module' => 'admin_roles',
        ],
    ];
}

/**
 * Get permission management capabilities
 *
 * @return array Array of capabilities
 */
function admin_roles_get_permission_capabilities(): array
{
    return [
        'permissions.view' => [
            'name' => 'View permissions',
            'description' => 'View permission list',
            'module' => 'admin_roles',
        ],
        'permissions.create' => [
            'name' => 'Create permissions',
            'description' => 'Create new permissions',
            'module' => 'admin_roles',
        ],
        'permissions.edit' => [
            'name' => 'Edit permissions',
            'description' => 'Edit permission details',
            'module' => 'admin_roles',
        ],
        'permissions.delete' => [
            'name' => 'Delete permissions',
            'description' => 'Delete permissions',
            'module' => 'admin_roles',
        ],
    ];
}

/**
 * Get all RBAC capabilities (roles + permissions)
 *
 * @return array Array of all capabilities
 */
function admin_roles_get_all_capabilities(): array
{
    return array_merge(
        admin_roles_get_capabilities(),
        admin_roles_get_permission_capabilities()
    );
}

/**
 * Check if role is system role
 *
 * @deprecated Use ISER\Admin\Roles\RoleViewHelper::isSystemRole() instead
 * @param array|object $role Role data
 * @return bool True if system role
 */
function admin_roles_is_system_role($role): bool
{
    return RoleViewHelper::isSystemRole($role);
}

/**
 * Get role badge HTML
 *
 * @deprecated Use ISER\Admin\Roles\RoleViewHelper::renderBadge() instead
 * @param array|object $role Role data
 * @return string HTML badge
 */
function admin_roles_badge($role): string
{
    return RoleViewHelper::renderBadge($role);
}

/**
 * Format permission count for display
 *
 * @deprecated Use ISER\Admin\Roles\RoleViewHelper::renderPermissionCount() instead
 * @param int $count Permission count
 * @return string Formatted count
 */
function admin_roles_permission_count(int $count): string
{
    return RoleViewHelper::renderPermissionCount($count);
}

/**
 * Get role menu items for admin panel
 *
 * @deprecated Use ISER\Admin\Roles\RoleViewHelper::getMenuItems() instead
 * @return array Menu items
 */
function admin_roles_get_menu_items(): array
{
    return RoleViewHelper::getMenuItems();
}

/**
 * Group permissions by module
 *
 * @deprecated Use ISER\Admin\Roles\RoleViewHelper::groupPermissionsByModule() instead
 * @param array $permissions Array of permissions
 * @return array Grouped permissions
 */
function admin_roles_group_permissions_by_module(array $permissions): array
{
    return RoleViewHelper::groupPermissionsByModule($permissions);
}
