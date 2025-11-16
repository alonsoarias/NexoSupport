<?php
/**
 * NexoSupport - Manual Authentication Library
 *
 * Library functions for manual authentication plugin
 *
 * @package    auth_manual
 * @copyright  2024 ISER
 * @license    Proprietary
 */

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Get plugin capabilities
 *
 * @return array Capabilities
 */
function auth_manual_get_capabilities(): array
{
    return [
        'auth/manual:login' => [
            'name' => 'Login via manual auth',
            'description' => 'Allow users to login using username/password',
            'module' => 'auth_manual',
        ],
        'auth/manual:logout' => [
            'name' => 'Logout',
            'description' => 'Allow users to logout',
            'module' => 'auth_manual',
        ],
        'auth/manual:manage' => [
            'name' => 'Manage manual authentication',
            'description' => 'Configure manual authentication settings',
            'module' => 'auth_manual',
        ],
    ];
}

/**
 * Get plugin title
 *
 * @return string Plugin title
 */
function auth_manual_get_title(): string
{
    return __('Manual Authentication');
}

/**
 * Get plugin description
 *
 * @return string Plugin description
 */
function auth_manual_get_description(): string
{
    return __('Traditional username/password authentication');
}

/**
 * Check if manual authentication is enabled
 *
 * @return bool True if enabled
 */
function auth_manual_is_enabled(): bool
{
    // Manual auth is always enabled as primary authentication method
    return true;
}

/**
 * Get supported auth features
 *
 * @return array Supported features
 */
function auth_manual_get_features(): array
{
    return [
        'login' => true,
        'logout' => true,
        'password_reset' => true,
        'password_change' => true,
        'username_login' => true,
        'email_login' => true,
    ];
}

/**
 * Validate login credentials format
 *
 * @param string $username Username or email
 * @param string $password Password
 * @return array Validation errors (empty if valid)
 */
function auth_manual_validate_credentials(string $username, string $password): array
{
    $errors = [];

    if (empty($username)) {
        $errors['username'] = 'Username or email is required';
    }

    if (empty($password)) {
        $errors['password'] = 'Password is required';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters';
    }

    return $errors;
}

/**
 * Get menu items for this auth plugin
 *
 * @return array Menu items
 */
function auth_manual_get_menu_items(): array
{
    return []; // No specific menu items for manual auth
}

/**
 * Get configuration options
 *
 * @return array Configuration options
 */
function auth_manual_get_config_options(): array
{
    return [
        'password_min_length' => [
            'name' => 'Minimum password length',
            'description' => 'Minimum number of characters for passwords',
            'type' => 'integer',
            'default' => 8,
        ],
        'password_require_uppercase' => [
            'name' => 'Require uppercase letter',
            'description' => 'Password must contain at least one uppercase letter',
            'type' => 'boolean',
            'default' => false,
        ],
        'password_require_lowercase' => [
            'name' => 'Require lowercase letter',
            'description' => 'Password must contain at least one lowercase letter',
            'type' => 'boolean',
            'default' => false,
        ],
        'password_require_number' => [
            'name' => 'Require number',
            'description' => 'Password must contain at least one number',
            'type' => 'boolean',
            'default' => false,
        ],
        'password_require_special' => [
            'name' => 'Require special character',
            'description' => 'Password must contain at least one special character',
            'type' => 'boolean',
            'default' => false,
        ],
        'allow_email_login' => [
            'name' => 'Allow email login',
            'description' => 'Allow users to login with email instead of username',
            'type' => 'boolean',
            'default' => true,
        ],
        'lockout_threshold' => [
            'name' => 'Login attempt threshold',
            'description' => 'Number of failed attempts before account lockout',
            'type' => 'integer',
            'default' => 5,
        ],
        'lockout_duration' => [
            'name' => 'Lockout duration (minutes)',
            'description' => 'How long to lock account after failed attempts',
            'type' => 'integer',
            'default' => 30,
        ],
    ];
}
