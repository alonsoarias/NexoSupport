<?php
/**
 * ISER - Manual Authentication Module
 */

namespace ISER\Modules\Auth\Manual;

use ISER\Core\Interfaces\AuthInterface;
use ISER\Core\Session\JWTSession;
use ISER\Core\Utils\Helpers;
use ISER\Core\Utils\Logger;
use ISER\Modules\User\UserManager;

class AuthManual implements AuthInterface
{
    private UserManager $userManager;
    private JWTSession $jwtSession;
    private ?array $currentUser = null;

    public function __construct(UserManager $userManager, JWTSession $jwtSession)
    {
        $this->userManager = $userManager;
        $this->jwtSession = $jwtSession;
    }

    public function authenticate(array $credentials): array|false
    {
        $username = $credentials['username'] ?? '';
        $password = $credentials['password'] ?? '';
        $ip = Helpers::getClientIp();

        if (empty($username) || empty($password)) {
            Logger::auth('Empty credentials', ['username' => $username, 'ip' => $ip]);
            return false;
        }

        // Check if account is locked
        if ($this->userManager->isAccountLocked($username)) {
            Logger::security('Attempt to login to locked account', ['username' => $username, 'ip' => $ip]);
            $this->userManager->recordLoginAttempt($username, false, $ip);
            return false;
        }

        // Get user
        $user = $this->userManager->getUserByUsername($username);
        if (!$user) {
            $this->userManager->recordLoginAttempt($username, false, $ip);
            Logger::auth('User not found', ['username' => $username]);
            return false;
        }

        // Verify password
        if (!Helpers::verifyPassword($password, $user['password'])) {
            $this->userManager->recordLoginAttempt($username, false, $ip);

            // Check failed attempts
            $failedAttempts = $this->userManager->getFailedAttempts($username);
            if ($failedAttempts >= 4) { // Lock after 5 attempts (this is the 5th)
                $this->userManager->lockAccount($username);
                Logger::security('Account locked due to failed attempts', ['username' => $username]);
            }

            Logger::auth('Invalid password', ['username' => $username]);
            return false;
        }

        // Check user status
        if ($user['status'] != 1) {
            Logger::auth('Inactive user login attempt', ['username' => $username]);
            return false;
        }

        // Success
        $this->userManager->recordLoginAttempt($username, true, $ip);
        $this->userManager->resetFailedAttempts($username);

        unset($user['password']);
        $this->currentUser = $user;

        Logger::auth('User authenticated successfully', ['userid' => $user['id'], 'username' => $username]);
        return $user;
    }

    public function validate(string $token): array|false
    {
        $decoded = $this->jwtSession->validate($token);
        if ($decoded === false) return false;

        $userId = $decoded->user_id ?? null;
        if (!$userId) return false;

        $user = $this->userManager->getUserById($userId);
        if (!$user || $user['status'] != 1) return false;

        unset($user['password']);
        $this->currentUser = $user;
        return $user;
    }

    public function logout(string $token): bool
    {
        $this->jwtSession->revoke($token);
        $this->currentUser = null;
        Logger::auth('User logged out');
        return true;
    }

    public function refresh(string $token): string|false
    {
        return $this->jwtSession->refresh($token);
    }

    public function isAuthenticated(): bool
    {
        return $this->currentUser !== null;
    }

    public function getUser(): ?array
    {
        return $this->currentUser;
    }

    public function hasPermission(string $permission): bool
    {
        // TODO: Implement permission system in Phase 3
        return true;
    }

    public function hasRole(string $role): bool
    {
        // TODO: Implement role system in Phase 3
        return true;
    }

    public function getPermissions(): array
    {
        return [];
    }

    public function getRoles(): array
    {
        return [];
    }

    // Remaining methods for AuthInterface
    public function createUser(array $userData): int|false
    {
        return $this->userManager->create($userData);
    }

    public function updateUser(int $userId, array $userData): bool
    {
        return $this->userManager->update($userId, $userData);
    }

    public function deleteUser(int $userId): bool
    {
        return $this->userManager->delete($userId);
    }

    public function getUserById(int $userId): array|false
    {
        return $this->userManager->getUserById($userId);
    }

    public function getUserByUsername(string $username): array|false
    {
        return $this->userManager->getUserByUsername($username);
    }

    public function getUserByEmail(string $email): array|false
    {
        return $this->userManager->getUserByEmail($email);
    }

    public function changePassword(int $userId, string $newPassword, ?string $oldPassword = null): bool
    {
        if ($oldPassword !== null) {
            $user = $this->userManager->getUserById($userId);
            if (!$user || !Helpers::verifyPassword($oldPassword, $user['password'])) {
                return false;
            }
        }
        return $this->userManager->update($userId, ['password' => $newPassword]);
    }

    public function resetPassword(string $email): string|false
    {
        // TODO: Implement in Phase 3
        return false;
    }

    public function verifyResetToken(string $token): array|false
    {
        // TODO: Implement in Phase 3
        return false;
    }

    public function usernameExists(string $username): bool
    {
        return $this->userManager->getUserByUsername($username) !== false;
    }

    public function emailExists(string $email): bool
    {
        return $this->userManager->getUserByEmail($email) !== false;
    }

    public function activateUser(int $userId): bool
    {
        return $this->userManager->update($userId, ['status' => 1]);
    }

    public function deactivateUser(int $userId): bool
    {
        return $this->userManager->update($userId, ['status' => 0]);
    }

    public function isUserActive(int $userId): bool
    {
        $user = $this->userManager->getUserById($userId);
        return $user && $user['status'] == 1;
    }

    public function recordLoginAttempt(string $username, bool $success, string $ipAddress): void
    {
        $this->userManager->recordLoginAttempt($username, $success, $ipAddress);
    }

    public function getFailedAttempts(string $username, int $timeWindow = 900): int
    {
        return $this->userManager->getFailedAttempts($username, $timeWindow);
    }

    public function isAccountLocked(string $username): bool
    {
        return $this->userManager->isAccountLocked($username);
    }

    public function lockAccount(string $username, int $duration = 900): bool
    {
        return $this->userManager->lockAccount($username, $duration);
    }

    public function unlockAccount(string $username): bool
    {
        return $this->userManager->resetFailedAttempts($username);
    }
}
