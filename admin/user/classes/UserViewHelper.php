<?php
/**
 * User View Helper - Presentation logic for user management
 *
 * @package    ISER\Admin\User
 * @copyright  2024 ISER
 * @license    Proprietary
 */

namespace ISER\Admin\User;

/**
 * Helper class for user-related view operations
 *
 * Provides methods for formatting, rendering, and presenting user data
 * in the admin interface.
 */
class UserViewHelper
{
    /**
     * Format user full name from user data
     *
     * If both first and last name are empty, returns username.
     *
     * @param array|object $user User data
     * @return string Full name or username
     */
    public static function formatFullName($user): string
    {
        if (is_array($user)) {
            $firstname = trim($user['firstname'] ?? '');
            $lastname = trim($user['lastname'] ?? '');
            $username = $user['username'] ?? 'Unknown';
        } else {
            $firstname = trim($user->firstname ?? '');
            $lastname = trim($user->lastname ?? '');
            $username = $user->username ?? 'Unknown';
        }

        if (empty($firstname) && empty($lastname)) {
            return $username;
        }

        return trim("$firstname $lastname");
    }

    /**
     * Render user status badge HTML
     *
     * @param string $status User status (active, suspended, pending)
     * @return string HTML badge
     */
    public static function renderStatusBadge(string $status): string
    {
        $badges = [
            'active' => '<span class="badge badge-success">Active</span>',
            'suspended' => '<span class="badge badge-warning">Suspended</span>',
            'pending' => '<span class="badge badge-info">Pending</span>',
        ];

        return $badges[strtolower($status)] ?? '<span class="badge badge-secondary">Unknown</span>';
    }

    /**
     * Get user management menu items for admin panel
     *
     * @return array Menu items
     */
    public static function getMenuItems(): array
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

    /**
     * Format user email with optional HTML escaping
     *
     * @param array|object $user User data
     * @param bool $htmlEscape Whether to escape HTML
     * @return string Email address
     */
    public static function formatEmail($user, bool $htmlEscape = true): string
    {
        $email = is_array($user) ? ($user['email'] ?? '') : ($user->email ?? '');

        if ($htmlEscape) {
            return htmlspecialchars($email);
        }

        return $email;
    }

    /**
     * Render user avatar HTML
     *
     * @param array|object $user User data
     * @param int $size Avatar size in pixels (default: 40)
     * @return string HTML img tag or placeholder
     */
    public static function renderAvatar($user, int $size = 40): string
    {
        $email = is_array($user) ? ($user['email'] ?? '') : ($user->email ?? '');
        $fullname = self::formatFullName($user);

        // Generate Gravatar URL
        $hash = md5(strtolower(trim($email)));
        $gravatarUrl = "https://www.gravatar.com/avatar/{$hash}?s={$size}&d=mp";

        return '<img src="' . htmlspecialchars($gravatarUrl) . '" ' .
            'alt="' . htmlspecialchars($fullname) . '" ' .
            'class="rounded-circle" ' .
            'width="' . $size . '" height="' . $size . '">';
    }

    /**
     * Render user initials badge (alternative to avatar)
     *
     * @param array|object $user User data
     * @param string $size Badge size class (sm, md, lg)
     * @return string HTML badge with initials
     */
    public static function renderInitialsBadge($user, string $size = 'md'): string
    {
        $fullname = self::formatFullName($user);
        $parts = explode(' ', $fullname, 2);

        if (count($parts) === 2) {
            $initials = strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1));
        } else {
            $initials = strtoupper(substr($fullname, 0, 2));
        }

        $sizeClasses = [
            'sm' => 'badge-sm',
            'md' => '',
            'lg' => 'badge-lg',
        ];

        $sizeClass = $sizeClasses[$size] ?? '';

        return '<span class="badge badge-primary rounded-circle ' . $sizeClass . '">' .
            htmlspecialchars($initials) . '</span>';
    }

    /**
     * Render user roles as badges
     *
     * @param array $roles Array of role names
     * @return string HTML badges
     */
    public static function renderRolesBadges(array $roles): string
    {
        if (empty($roles)) {
            return '<span class="text-muted">No roles</span>';
        }

        $badges = [];
        foreach ($roles as $role) {
            $roleName = is_array($role) ? ($role['name'] ?? 'Unknown') : (is_string($role) ? $role : 'Unknown');
            $badges[] = '<span class="badge badge-info">' . htmlspecialchars($roleName) . '</span>';
        }

        return implode(' ', $badges);
    }

    /**
     * Format last login time
     *
     * @param int|null $timestamp Last login timestamp
     * @return string Formatted time or "Never"
     */
    public static function formatLastLogin(?int $timestamp): string
    {
        if (!$timestamp) {
            return '<span class="text-muted">Never</span>';
        }

        $now = time();
        $diff = $now - $timestamp;

        if ($diff < 60) {
            return '<span class="text-success">Just now</span>';
        } elseif ($diff < 3600) {
            $minutes = floor($diff / 60);
            return '<span class="text-success">' . $minutes . ' min ago</span>';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return '<span class="text-info">' . $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago</span>';
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return '<span class="text-muted">' . $days . ' day' . ($days > 1 ? 's' : '') . ' ago</span>';
        } else {
            return '<span class="text-muted">' . date('Y-m-d', $timestamp) . '</span>';
        }
    }
}
