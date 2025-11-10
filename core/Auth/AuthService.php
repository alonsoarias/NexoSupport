<?php

declare(strict_types=1);

namespace ISER\Core\Auth;

use ISER\Core\Database\Database;
use ISER\Core\Utils\Helpers;
use ISER\User\UserManager;

/**
 * Authentication Service
 *
 * Handles user authentication and session management
 *
 * @package ISER\Core\Auth
 */
class AuthService
{
    private Database $db;
    private UserManager $userManager;

    /**
     * Constructor
     */
    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->userManager = new UserManager($db);
    }

    /**
     * Authenticate user with username/email and password
     *
     * @param string $usernameOrEmail Username or email
     * @param string $password Plain text password
     * @param string $ipAddress IP address of the user
     * @return array|false User data if authenticated, false otherwise
     */
    public function authenticate(string $usernameOrEmail, string $password, string $ipAddress): array|false
    {
        // Check if account is locked
        if ($this->userManager->isAccountLocked($usernameOrEmail)) {
            return false;
        }

        // Try to find user by username or email
        $user = $this->userManager->getUserByUsername($usernameOrEmail);
        if (!$user) {
            $user = $this->userManager->getUserByEmail($usernameOrEmail);
        }

        // User not found
        if (!$user) {
            $this->userManager->recordLoginAttempt($usernameOrEmail, false, $ipAddress);
            return false;
        }

        // Verify password
        if (!Helpers::verifyPassword($password, $user['password'])) {
            $this->userManager->recordLoginAttempt($usernameOrEmail, false, $ipAddress);

            // Check if we need to lock the account
            $failedAttempts = $this->userManager->getFailedAttempts($usernameOrEmail);
            if ($failedAttempts >= 5) {
                $this->userManager->lockAccount($usernameOrEmail);
            }

            return false;
        }

        // Check if user is deleted
        if (!empty($user['deleted_at'])) {
            return false;
        }

        // Check if user status is active
        if (($user['status'] ?? 'active') !== 'active') {
            return false;
        }

        // Authentication successful
        $this->userManager->recordLoginAttempt($usernameOrEmail, true, $ipAddress);
        $this->userManager->resetFailedAttempts($usernameOrEmail);
        $this->userManager->updateLastLogin((int)$user['id'], $ipAddress);

        // Return user data (without password)
        unset($user['password']);
        return $user;
    }

    /**
     * Create authenticated session
     *
     * @param array $user User data
     * @return void
     */
    public function createSession(array $user): void
    {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'] ?? '';
        $_SESSION['first_name'] = $user['first_name'] ?? '';
        $_SESSION['last_name'] = $user['last_name'] ?? '';
        $_SESSION['authenticated'] = true;
        $_SESSION['login_time'] = time();
    }

    /**
     * Destroy authenticated session
     *
     * @return void
     */
    public function destroySession(): void
    {
        session_destroy();
        $_SESSION = [];
    }

    /**
     * Check if user is authenticated
     *
     * @return bool
     */
    public function isAuthenticated(): bool
    {
        return isset($_SESSION['user_id']) && isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
    }

    /**
     * Get current authenticated user ID
     *
     * @return int|null
     */
    public function getCurrentUserId(): ?int
    {
        return $this->isAuthenticated() ? (int)$_SESSION['user_id'] : null;
    }

    /**
     * Get current authenticated user data
     *
     * @return array|null
     */
    public function getCurrentUser(): ?array
    {
        $userId = $this->getCurrentUserId();
        if ($userId === null) {
            return null;
        }

        $user = $this->userManager->getUserById($userId);
        if ($user) {
            unset($user['password']);
        }

        return $user ?: null;
    }
}
