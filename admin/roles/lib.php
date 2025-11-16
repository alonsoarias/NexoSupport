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
 * @param array|object $role Role data
 * @return bool True if system role
 */
function admin_roles_is_system_role($role): bool
{
    if (is_array($role)) {
        return !empty($role['is_system']);
    }
    return !empty($role->issystem);
}

/**
 * Get role badge HTML
 *
 * @param array|object $role Role data
 * @return string HTML badge
 */
function admin_roles_badge($role): string
{
    $isSystem = admin_roles_is_system_role($role);
    $name = is_array($role) ? ($role['name'] ?? 'Unknown') : ($role->name ?? 'Unknown');

    if ($isSystem) {
        return '<span class="badge badge-primary">' . htmlspecialchars($name) . ' (System)</span>';
    }

    return '<span class="badge badge-secondary">' . htmlspecialchars($name) . '</span>';
}

/**
 * Format permission count for display
 *
 * @param int $count Permission count
 * @return string Formatted count
 */
function admin_roles_permission_count(int $count): string
{
    if ($count === 0) {
        return '<span class="text-muted">No permissions</span>';
    } elseif ($count === 1) {
        return '<span class="text-info">1 permission</span>';
    } else {
        return '<span class="text-info">' . $count . ' permissions</span>';
    }
}

/**
 * Get role menu items for admin panel
 *
 * @return array Menu items
 */
function admin_roles_get_menu_items(): array
{
    $items = [];

    if (has_capability('roles.view')) {
        $items[] = [
            'title' => 'Roles',
            'url' => '/admin/roles',
            'icon' => 'shield',
            'active' => strpos($_SERVER['REQUEST_URI'] ?? '', '/admin/roles') === 0,
        ];
    }

    if (has_capability('permissions.view')) {
        $items[] = [
            'title' => 'Permissions',
            'url' => '/admin/permissions',
            'icon' => 'key',
            'active' => strpos($_SERVER['REQUEST_URI'] ?? '', '/admin/permissions') === 0,
        ];
    }

    return $items;
}

/**
 * Group permissions by module
 *
 * @param array $permissions Array of permissions
 * @return array Grouped permissions
 */
function admin_roles_group_permissions_by_module(array $permissions): array
{
    $grouped = [];

    foreach ($permissions as $permission) {
        $module = $permission['module'] ?? 'core';
        if (!isset($grouped[$module])) {
            $grouped[$module] = [];
        }
        $grouped[$module][] = $permission;
    }

    ksort($grouped);
    return $grouped;
}
