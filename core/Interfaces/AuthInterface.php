<?php

/**
 * ISER Authentication System - Authentication Interface
 *
 * Interface for authentication systems.
 *
 * @package    ISER\Core\Interfaces
 * @category   Core
 * @author     ISER Development Team
 * @copyright  2024 ISER
 * @license    Proprietary
 * @version    1.0.0
 * @since      Phase 1
 */

namespace ISER\Core\Interfaces;

/**
 * AuthInterface
 *
 * Defines the contract that all authentication systems must implement.
 */
interface AuthInterface
{
    /**
     * Authenticate user with credentials
     *
     * @param array $credentials User credentials (username, password, etc.)
     * @return array|false User data on success, false on failure
     */
    public function authenticate(array $credentials): array|false;

    /**
     * Validate authentication token/session
     *
     * @param string $token Authentication token
     * @return array|false User data on success, false on failure
     */
    public function validate(string $token): array|false;

    /**
     * Logout user
     *
     * @param string $token Authentication token
     * @return bool True on success
     */
    public function logout(string $token): bool;

    /**
     * Refresh authentication token
     *
     * @param string $token Current authentication token
     * @return string|false New token on success, false on failure
     */
    public function refresh(string $token): string|false;

    /**
     * Check if user is authenticated
     *
     * @return bool True if authenticated
     */
    public function isAuthenticated(): bool;

    /**
     * Get authenticated user data
     *
     * @return array|null User data or null if not authenticated
     */
    public function getUser(): ?array;

    /**
     * Check if user has permission
     *
     * @param string $permission Permission name
     * @return bool True if user has permission
     */
    public function hasPermission(string $permission): bool;

    /**
     * Check if user has role
     *
     * @param string $role Role name
     * @return bool True if user has role
     */
    public function hasRole(string $role): bool;

    /**
     * Get user permissions
     *
     * @return array Array of permission names
     */
    public function getPermissions(): array;

    /**
     * Get user roles
     *
     * @return array Array of role names
     */
    public function getRoles(): array;

    /**
     * Create new user
     *
     * @param array $userData User data
     * @return int|false User ID on success, false on failure
     */
    public function createUser(array $userData): int|false;

    /**
     * Update user data
     *
     * @param int $userId User ID
     * @param array $userData User data to update
     * @return bool True on success
     */
    public function updateUser(int $userId, array $userData): bool;

    /**
     * Delete user
     *
     * @param int $userId User ID
     * @return bool True on success
     */
    public function deleteUser(int $userId): bool;

    /**
     * Get user by ID
     *
     * @param int $userId User ID
     * @return array|false User data on success, false on failure
     */
    public function getUserById(int $userId): array|false;

    /**
     * Get user by username
     *
     * @param string $username Username
     * @return array|false User data on success, false on failure
     */
    public function getUserByUsername(string $username): array|false;

    /**
     * Get user by email
     *
     * @param string $email Email address
     * @return array|false User data on success, false on failure
     */
    public function getUserByEmail(string $email): array|false;

    /**
     * Change user password
     *
     * @param int $userId User ID
     * @param string $newPassword New password
     * @param string|null $oldPassword Old password (for verification)
     * @return bool True on success
     */
    public function changePassword(int $userId, string $newPassword, ?string $oldPassword = null): bool;

    /**
     * Reset user password
     *
     * @param string $email User email
     * @return string|false Reset token on success, false on failure
     */
    public function resetPassword(string $email): string|false;

    /**
     * Verify password reset token
     *
     * @param string $token Reset token
     * @return array|false User data on success, false on failure
     */
    public function verifyResetToken(string $token): array|false;

    /**
     * Check if username exists
     *
     * @param string $username Username
     * @return bool True if username exists
     */
    public function usernameExists(string $username): bool;

    /**
     * Check if email exists
     *
     * @param string $email Email address
     * @return bool True if email exists
     */
    public function emailExists(string $email): bool;

    /**
     * Activate user account
     *
     * @param int $userId User ID
     * @return bool True on success
     */
    public function activateUser(int $userId): bool;

    /**
     * Deactivate user account
     *
     * @param int $userId User ID
     * @return bool True on success
     */
    public function deactivateUser(int $userId): bool;

    /**
     * Check if user is active
     *
     * @param int $userId User ID
     * @return bool True if user is active
     */
    public function isUserActive(int $userId): bool;

    /**
     * Record login attempt
     *
     * @param string $username Username
     * @param bool $success Whether login was successful
     * @param string $ipAddress IP address
     * @return void
     */
    public function recordLoginAttempt(string $username, bool $success, string $ipAddress): void;

    /**
     * Get failed login attempts count
     *
     * @param string $username Username
     * @param int $timeWindow Time window in seconds
     * @return int Number of failed attempts
     */
    public function getFailedAttempts(string $username, int $timeWindow = 900): int;

    /**
     * Check if account is locked
     *
     * @param string $username Username
     * @return bool True if account is locked
     */
    public function isAccountLocked(string $username): bool;

    /**
     * Lock user account
     *
     * @param string $username Username
     * @param int $duration Lock duration in seconds
     * @return bool True on success
     */
    public function lockAccount(string $username, int $duration = 900): bool;

    /**
     * Unlock user account
     *
     * @param string $username Username
     * @return bool True on success
     */
    public function unlockAccount(string $username): bool;
}
