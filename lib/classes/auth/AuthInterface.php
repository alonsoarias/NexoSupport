<?php
/**
 * Authentication Plugin Interface
 *
 * Defines the contract that all authentication plugins must follow.
 *
 * @package    ISER\Core\Auth
 * @copyright  2025 ISER
 * @license    Proprietary
 */

namespace ISER\Core\Auth;

/**
 * Interface for authentication plugins
 */
interface AuthInterface
{
    /**
     * Authenticate a user with username and password.
     *
     * @param string $username The username
     * @param string $password The password in plain text
     * @return bool true if authentication was successful, false otherwise
     */
    public function user_login(string $username, string $password): bool;

    /**
     * Update a user's password in the database.
     *
     * @param object $user The user object
     * @param string $newpassword The new password in plain text
     * @return bool true if update was successful, false otherwise
     */
    public function user_update_password(object $user, string $newpassword): bool;

    /**
     * Indicates if this authentication plugin allows password changes.
     *
     * @return bool true if password changes are allowed
     */
    public function can_change_password(): bool;

    /**
     * Indicates if this authentication plugin allows profile editing.
     *
     * @return bool true if profile editing is allowed
     */
    public function can_edit_profile(): bool;

    /**
     * Indicates if this authentication plugin is internal.
     *
     * Internal plugins use NexoSupport's user table.
     *
     * @return bool true if internal
     */
    public function is_internal(): bool;

    /**
     * Indicates if new users can sign up.
     *
     * @return bool true if signup is allowed
     */
    public function can_signup(): bool;

    /**
     * Indicates if this plugin can confirm users.
     *
     * @return bool true if user confirmation is supported
     */
    public function can_confirm(): bool;

    /**
     * Indicates if this plugin can reset passwords.
     *
     * @return bool true if password reset is supported
     */
    public function can_reset_password(): bool;

    /**
     * Hook called before displaying login page.
     *
     * @return bool true to continue with login page display
     */
    public function pre_loginpage_hook(): bool;

    /**
     * Hook called after successful authentication.
     *
     * @param object $user The authenticated user object
     * @param string $username The username
     * @param string $password The password
     * @return void
     */
    public function user_authenticated_hook(object &$user, string $username, string $password): void;

    /**
     * Hook called on logout page.
     *
     * @return void
     */
    public function logoutpage_hook(): void;

    /**
     * Synchronize users from external source.
     *
     * @param bool $do_updates true to apply updates
     * @return bool true if synchronization was successful
     */
    public function sync_users(bool $do_updates = false): bool;

    /**
     * Get user information from external source.
     *
     * @param string $username The username
     * @return array|false Array with user data or false if not found
     */
    public function get_userinfo(string $username): array|false;

    /**
     * Get the URL for password change.
     *
     * @return string|null URL for password change
     */
    public function change_password_url(): ?string;

    /**
     * Get the authentication type identifier.
     *
     * @return string Authentication type (e.g., 'manual', 'ldap', 'oauth2')
     */
    public function get_authtype(): string;
}
