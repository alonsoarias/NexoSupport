<?php
/**
 * Role View Helper - Presentation logic for role management
 *
 * @package    ISER\Admin\Roles
 * @copyright  2024 ISER
 * @license    Proprietary
 */

namespace ISER\Admin\Roles;

/**
 * Helper class for role-related view operations
 *
 * Provides methods for formatting, rendering, and presenting role data
 * in the admin interface.
 */
class RoleViewHelper
{
    /**
     * Check if role is a system role
     *
     * @param array|object $role Role data
     * @return bool True if system role
     */
    public static function isSystemRole($role): bool
    {
        if (is_array($role)) {
            return !empty($role['is_system']);
        }
        return !empty($role->issystem);
    }

    /**
     * Render role badge HTML
     *
     * @param array|object $role Role data
     * @return string HTML badge
     */
    public static function renderBadge($role): string
    {
        $isSystem = self::isSystemRole($role);
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
     * @return string Formatted count HTML
     */
    public static function renderPermissionCount(int $count): string
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
     * Get role management menu items for admin panel
     *
     * @return array Menu items
     */
    public static function getMenuItems(): array
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
     * Group permissions by module for organized display
     *
     * @param array $permissions Array of permissions
     * @return array Grouped permissions (module => permissions[])
     */
    public static function groupPermissionsByModule(array $permissions): array
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

    /**
     * Render role name with system indicator
     *
     * @param array|object $role Role data
     * @param bool $htmlEscape Whether to escape HTML
     * @return string Formatted role name
     */
    public static function renderRoleName($role, bool $htmlEscape = true): string
    {
        $name = is_array($role) ? ($role['name'] ?? 'Unknown') : ($role->name ?? 'Unknown');
        $isSystem = self::isSystemRole($role);

        if ($htmlEscape) {
            $name = htmlspecialchars($name);
        }

        if ($isSystem) {
            return $name . ' <small class="text-muted">(System)</small>';
        }

        return $name;
    }

    /**
     * Render permission badge
     *
     * @param array|object $permission Permission data
     * @return string HTML badge
     */
    public static function renderPermissionBadge($permission): string
    {
        $name = is_array($permission) ? ($permission['name'] ?? 'Unknown') : ($permission->name ?? 'Unknown');
        $module = is_array($permission) ? ($permission['module'] ?? 'core') : ($permission->module ?? 'core');

        return '<span class="badge badge-info" title="' . htmlspecialchars($module) . '">' .
            htmlspecialchars($name) . '</span>';
    }
}
