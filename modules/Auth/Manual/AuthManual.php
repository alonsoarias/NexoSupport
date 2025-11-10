<?php
/**
 * ISER - Manual Authentication Module
 */

namespace ISER\Auth\Manual;

use ISER\Core\Interfaces\AuthInterface;
use ISER\Core\Session\JWTSession;
use ISER\Core\Utils\Helpers;
use ISER\Core\Utils\Logger;
use ISER\User\UserManager;
use ISER\Auth\PasswordResetTokenManager;
use ISER\Core\Utils\Mailer;
use PDO;

class AuthManual implements AuthInterface
{
    private UserManager $userManager;
    private JWTSession $jwtSession;
    private ?PasswordResetTokenManager $resetTokenManager = null;
    private ?Mailer $mailer = null;
    private ?array $currentUser = null;

    public function __construct(
        UserManager $userManager,
        JWTSession $jwtSession,
        ?PasswordResetTokenManager $resetTokenManager = null,
        ?Mailer $mailer = null
    ) {
        $this->userManager = $userManager;
        $this->jwtSession = $jwtSession;
        $this->resetTokenManager = $resetTokenManager;
        $this->mailer = $mailer;
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

    /**
     * Solicitar reseteo de contraseña
     * ACTUALIZADO: Usa nueva tabla password_reset_tokens normalizada
     *
     * @param string $email Email del usuario
     * @return string|false Token generado o false si falla
     */
    public function resetPassword(string $email): string|false
    {
        if (!$this->resetTokenManager) {
            Logger::error('PasswordResetTokenManager not initialized');
            return false;
        }

        // Buscar usuario por email
        $user = $this->userManager->getUserByEmail($email);
        if (!$user) {
            Logger::warning('Password reset requested for non-existent email', ['email' => $email]);
            // No revelar si el email existe o no (seguridad)
            return 'pending';
        }

        // Verificar que el usuario esté activo
        if ($user['status'] !== 'active') {
            Logger::warning('Password reset requested for inactive user', ['email' => $email]);
            return false;
        }

        // Verificar rate limit (prevenir abuso)
        if (!$this->resetTokenManager->checkResetRateLimit($user['id'])) {
            Logger::security('Password reset rate limit exceeded', [
                'user_id' => $user['id'],
                'email' => $email
            ]);
            return false;
        }

        // Generar token (expira en 1 hora)
        $token = $this->resetTokenManager->createToken($user['id'], 3600);

        // Enviar email si el mailer está disponible
        if ($this->mailer) {
            $resetUrl = $_ENV['APP_URL'] . '/auth/reset-password?token=' . $token;
            $this->mailer->sendPasswordResetEmail($user['email'], $token, $resetUrl);
        }

        Logger::info('Password reset token generated', [
            'user_id' => $user['id'],
            'email' => $email
        ]);

        return $token;
    }

    /**
     * Verificar token de reseteo de contraseña
     * ACTUALIZADO: Usa nueva tabla password_reset_tokens normalizada
     *
     * @param string $token Token a verificar
     * @return array|false Datos del usuario si el token es válido
     */
    public function verifyResetToken(string $token): array|false
    {
        if (!$this->resetTokenManager) {
            Logger::error('PasswordResetTokenManager not initialized');
            return false;
        }

        // Validar token
        $tokenData = $this->resetTokenManager->validateToken($token);
        if (!$tokenData) {
            Logger::warning('Invalid or expired password reset token', ['token' => substr($token, 0, 10) . '...']);
            return false;
        }

        // Obtener usuario
        $user = $this->resetTokenManager->getUserByToken($token);
        if (!$user) {
            Logger::error('User not found for valid token', ['token' => substr($token, 0, 10) . '...']);
            return false;
        }

        unset($user['password']);
        return $user;
    }

    /**
     * Completar reseteo de contraseña con token
     * ACTUALIZADO: Usa nueva tabla password_reset_tokens normalizada
     *
     * @param string $token Token de reseteo
     * @param string $newPassword Nueva contraseña
     * @return bool True si se actualizó correctamente
     */
    public function completePasswordReset(string $token, string $newPassword): bool
    {
        if (!$this->resetTokenManager) {
            Logger::error('PasswordResetTokenManager not initialized');
            return false;
        }

        // Verificar token
        $user = $this->verifyResetToken($token);
        if (!$user) {
            return false;
        }

        // Actualizar contraseña
        $success = $this->userManager->update($user['id'], [
            'password' => $newPassword
        ]);

        if ($success) {
            // Marcar token como usado
            $this->resetTokenManager->markAsUsed($token);

            Logger::info('Password reset completed', [
                'user_id' => $user['id'],
                'email' => $user['email']
            ]);

            // Opcional: enviar notificación por email
            if ($this->mailer) {
                $this->mailer->sendPasswordChangedNotification($user['email']);
            }
        }

        return $success;
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
