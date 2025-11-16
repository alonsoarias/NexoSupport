<?php
/**
 * NexoSupport - User Management Library Functions
 *
 * Library of functions and constants for user management component
 *
 * @package    admin
 * @subpackage user
 * @copyright  2024 ISER
 * @license    Proprietary
 */

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * User status constants
 */
if (!defined('USER_STATUS_ACTIVE')) {
    define('USER_STATUS_ACTIVE', 'active');
    define('USER_STATUS_SUSPENDED', 'suspended');
    define('USER_STATUS_PENDING', 'pending');
}

/**
 * Get user management capabilities required
 *
 * @return array Array of capabilities
 */
function admin_user_get_capabilities(): array
{
    return [
        'users.view' => [
            'name' => 'View users',
            'description' => 'View user list and details',
            'module' => 'admin_user',
        ],
        'users.create' => [
            'name' => 'Create users',
            'description' => 'Create new user accounts',
            'module' => 'admin_user',
        ],
        'users.edit' => [
            'name' => 'Edit users',
            'description' => 'Edit user account details',
            'module' => 'admin_user',
        ],
        'users.delete' => [
            'name' => 'Delete users',
            'description' => 'Delete user accounts (soft delete)',
            'module' => 'admin_user',
        ],
        'users.restore' => [
            'name' => 'Restore users',
            'description' => 'Restore deleted user accounts',
            'module' => 'admin_user',
        ],
        'users.assign_roles' => [
            'name' => 'Assign roles',
            'description' => 'Assign and unassign roles to users',
            'module' => 'admin_user',
        ],
    ];
}

/**
 * Format user full name
 *
 * @param array|object $user User data
 * @return string Full name
 */
function admin_user_fullname($user): string
{
    if (is_array($user)) {
        $firstname = trim($user['firstname'] ?? '');
        $lastname = trim($user['lastname'] ?? '');
    } else {
        $firstname = trim($user->firstname ?? '');
        $lastname = trim($user->lastname ?? '');
    }

    if (empty($firstname) && empty($lastname)) {
        return is_array($user) ? ($user['username'] ?? 'Unknown') : ($user->username ?? 'Unknown');
    }

    return trim("$firstname $lastname");
}

/**
 * Get user status badge HTML
 *
 * @param string $status User status
 * @return string HTML badge
 */
function admin_user_status_badge(string $status): string
{
    $badges = [
        'active' => '<span class="badge badge-success">Active</span>',
        'suspended' => '<span class="badge badge-warning">Suspended</span>',
        'pending' => '<span class="badge badge-info">Pending</span>',
    ];

    return $badges[strtolower($status)] ?? '<span class="badge badge-secondary">Unknown</span>';
}

/**
 * Get user menu items for admin panel
 *
 * @return array Menu items
 */
function admin_user_get_menu_items(): array
{
    $items = [];

    if (has_capability('users.view')) {
        $items[] = [
            'title' => 'Users',
            'url' => '/admin/users',
            'icon' => 'users',
            'active' => strpos($_SERVER['REQUEST_URI'] ?? '', '/admin/users') === 0,
        ];
    }

    return $items;
}
