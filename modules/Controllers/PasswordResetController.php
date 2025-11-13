<?php

declare(strict_types=1);

namespace ISER\Controllers;

use ISER\Core\Controllers\BaseController;
use ISER\Core\Database\Database;
use ISER\Core\Utils\Helpers;
use ISER\User\UserManager;
use ISER\Auth\PasswordResetTokenManager;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Password Reset Controller (REFACTORIZADO con BaseController)
 * Handles complete password reset flow
 *
 * Extiende BaseController para reducir código duplicado.
 */
class PasswordResetController extends BaseController
{
    private UserManager $userManager;
    private PasswordResetTokenManager $tokenManager;

    public function __construct(Database $db)
    {
        parent::__construct($db);
        $this->userManager = new UserManager($db);
        $this->tokenManager = new PasswordResetTokenManager($db->getPdo());
    }

    /**
     * Show forgot password form
     */
    public function showForgotForm(ServerRequestInterface $request): ResponseInterface
    {
        // If already authenticated, redirect to dashboard
        if ($this->isAuthenticated()) {
            return $this->redirect('/dashboard');
        }

        $data = [
            'locale' => $this->translator->getLocale(),
            'page_title' => $this->translator->translate('auth.forgot_password'),
            'header_title' => $this->translator->translate('auth.forgot_password'),
            'error' => $_SESSION['password_reset_error'] ?? null,
            'success' => $_SESSION['password_reset_success'] ?? null,
        ];

        unset($_SESSION['password_reset_error']);
        unset($_SESSION['password_reset_success']);

        return $this->renderWithLayout('auth/forgot-password', $data);
    }

    /**
     * Process forgot password request and send reset link
     */
    public function sendResetLink(ServerRequestInterface $request): ResponseInterface
    {
        error_log("========================================");
        error_log("[PASSWORD RESET] Starting forgot password process");
        error_log("========================================");

        $body = $request->getParsedBody();

        if (!is_array($body)) {
            error_log("[PASSWORD RESET ERROR] Body is not array: " . gettype($body));
            $_SESSION['password_reset_error'] = $this->translator->translate('common.error_occurred');
            return $this->redirect('/forgot-password');
        }

        $email = trim($body['email'] ?? '');

        error_log("[PASSWORD RESET] Email received: '{$email}'");

        // Validate email
        if (empty($email)) {
            error_log("[PASSWORD RESET ERROR] Email is empty");
            $_SESSION['password_reset_error'] = $this->translator->translate('auth.email_required');
            return $this->redirect('/forgot-password');
        }

        if (!Helpers::validateEmail($email)) {
            error_log("[PASSWORD RESET ERROR] Invalid email format: {$email}");
            $_SESSION['password_reset_error'] = $this->translator->translate('auth.invalid_email');
            return $this->redirect('/forgot-password');
        }

        // Check if user exists
        $user = $this->userManager->getUserByEmail($email);

        if (!$user) {
            error_log("[PASSWORD RESET] User not found for email: {$email}");
            // Security: Don't reveal if user exists or not
            // Show success message anyway to prevent user enumeration
            $_SESSION['password_reset_success'] = $this->translator->translate('auth.reset_link_sent');
            return $this->redirect('/forgot-password');
        }

        error_log("[PASSWORD RESET] User found - ID: {$user['id']}, Username: {$user['username']}");

        // Verify user is active
        $userStatus = strtolower($user['status'] ?? 'active');
        if ($userStatus !== 'active') {
            error_log("[PASSWORD RESET ERROR] User not active - Status: {$user['status']}");
            $_SESSION['password_reset_error'] = $this->translator->translate('auth.account_inactive');
            return $this->redirect('/forgot-password');
        }

        // Check if user is deleted
        if (!empty($user['deleted_at'])) {
            error_log("[PASSWORD RESET ERROR] User is deleted");
            $_SESSION['password_reset_error'] = $this->translator->translate('auth.account_not_found');
            return $this->redirect('/forgot-password');
        }

        // Check rate limit (3 attempts per hour)
        if (!$this->tokenManager->checkResetRateLimit($user['id'], 3600, 3)) {
            error_log("[PASSWORD RESET ERROR] Rate limit exceeded for user ID: {$user['id']}");
            $_SESSION['password_reset_error'] = $this->translator->translate('auth.reset_rate_limit');
            return $this->redirect('/forgot-password');
        }

        // Create reset token (expires in 24 hours = 86400 seconds)
        try {
            $token = $this->tokenManager->createToken($user['id'], 86400);
            error_log("[PASSWORD RESET] Token created successfully for user ID: {$user['id']}");
            error_log("[PASSWORD RESET] Token: {$token}");

            // Build reset link
            $resetLink = Helpers::getBaseUrl() . "/reset-password?token={$token}&email=" . urlencode($email);

            // Log the reset link (email functionality to be implemented later)
            error_log("========================================");
            error_log("[PASSWORD RESET] EMAIL SIMULATION");
            error_log("========================================");
            error_log("To: {$email}");
            error_log("Subject: Password Reset Request");
            error_log("Body:");
            error_log("Hello {$user['first_name']} {$user['last_name']},");
            error_log("");
            error_log("You have requested to reset your password. Please click the link below:");
            error_log("");
            error_log("Reset Link: {$resetLink}");
            error_log("");
            error_log("This link will expire in 24 hours.");
            error_log("");
            error_log("If you did not request this, please ignore this email.");
            error_log("========================================");

            $_SESSION['password_reset_success'] = $this->translator->translate('auth.reset_link_sent');
        } catch (\Exception $e) {
            error_log("[PASSWORD RESET ERROR] Failed to create token: " . $e->getMessage());
            $_SESSION['password_reset_error'] = $this->translator->translate('common.error_occurred');
        }

        return $this->redirect('/forgot-password');
    }

    /**
     * Show reset password form with token
     */
    public function showResetForm(ServerRequestInterface $request): ResponseInterface
    {
        // If already authenticated, redirect to dashboard
        if ($this->isAuthenticated()) {
            return $this->redirect('/dashboard');
        }

        $queryParams = $request->getQueryParams();
        $token = $queryParams['token'] ?? '';
        $email = $queryParams['email'] ?? '';

        error_log("[PASSWORD RESET] Show reset form - Token: {$token}, Email: {$email}");

        // Validate token presence
        if (empty($token) || empty($email)) {
            error_log("[PASSWORD RESET ERROR] Missing token or email");
            $_SESSION['password_reset_error'] = $this->translator->translate('auth.invalid_reset_link');
            return $this->redirect('/forgot-password');
        }

        // Validate token
        $tokenData = $this->tokenManager->validateToken($token);

        if (!$tokenData) {
            error_log("[PASSWORD RESET ERROR] Invalid or expired token");
            $_SESSION['password_reset_error'] = $this->translator->translate('auth.expired_reset_link');
            return $this->redirect('/forgot-password');
        }

        error_log("[PASSWORD RESET] Token valid for user ID: {$tokenData['user_id']}");

        // Get user info
        $user = $this->tokenManager->getUserByToken($token);

        if (!$user || $user['email'] !== $email) {
            error_log("[PASSWORD RESET ERROR] User not found or email mismatch");
            $_SESSION['password_reset_error'] = $this->translator->translate('auth.invalid_reset_link');
            return $this->redirect('/forgot-password');
        }

        $data = [
            'locale' => $this->translator->getLocale(),
            'page_title' => $this->translator->translate('auth.reset_password'),
            'header_title' => $this->translator->translate('auth.reset_password'),
            'token' => $token,
            'email' => $email,
            'error' => $_SESSION['password_reset_error'] ?? null,
        ];

        unset($_SESSION['password_reset_error']);

        return $this->renderWithLayout('auth/reset-password', $data);
    }

    /**
     * Process password reset
     */
    public function resetPassword(ServerRequestInterface $request): ResponseInterface
    {
        error_log("========================================");
        error_log("[PASSWORD RESET] Processing password reset");
        error_log("========================================");

        $body = $request->getParsedBody();

        if (!is_array($body)) {
            error_log("[PASSWORD RESET ERROR] Body is not array: " . gettype($body));
            $_SESSION['password_reset_error'] = $this->translator->translate('common.error_occurred');
            return $this->redirect('/forgot-password');
        }

        $token = trim($body['token'] ?? '');
        $email = trim($body['email'] ?? '');
        $password = $body['password'] ?? '';
        $passwordConfirm = $body['password_confirm'] ?? '';

        error_log("[PASSWORD RESET] Token: {$token}");
        error_log("[PASSWORD RESET] Email: {$email}");
        error_log("[PASSWORD RESET] Password length: " . strlen($password));

        // Validate inputs
        if (empty($token) || empty($email) || empty($password) || empty($passwordConfirm)) {
            error_log("[PASSWORD RESET ERROR] Missing required fields");
            $_SESSION['password_reset_error'] = $this->translator->translate('auth.all_fields_required');
            return $this->redirect("/reset-password?token={$token}&email=" . urlencode($email));
        }

        // Validate password match
        if ($password !== $passwordConfirm) {
            error_log("[PASSWORD RESET ERROR] Passwords do not match");
            $_SESSION['password_reset_error'] = $this->translator->translate('auth.passwords_not_match');
            return $this->redirect("/reset-password?token={$token}&email=" . urlencode($email));
        }

        // Validate password strength (minimum 8 characters)
        if (strlen($password) < 8) {
            error_log("[PASSWORD RESET ERROR] Password too short");
            $_SESSION['password_reset_error'] = $this->translator->translate('auth.password_too_short');
            return $this->redirect("/reset-password?token={$token}&email=" . urlencode($email));
        }

        // Validate token
        $tokenData = $this->tokenManager->validateToken($token);

        if (!$tokenData) {
            error_log("[PASSWORD RESET ERROR] Invalid or expired token");
            $_SESSION['password_reset_error'] = $this->translator->translate('auth.expired_reset_link');
            return $this->redirect('/forgot-password');
        }

        error_log("[PASSWORD RESET] Token valid for user ID: {$tokenData['user_id']}");

        // Get user
        $user = $this->tokenManager->getUserByToken($token);

        if (!$user || $user['email'] !== $email) {
            error_log("[PASSWORD RESET ERROR] User not found or email mismatch");
            $_SESSION['password_reset_error'] = $this->translator->translate('auth.invalid_reset_link');
            return $this->redirect('/forgot-password');
        }

        error_log("[PASSWORD RESET] User found - ID: {$user['id']}, Username: {$user['username']}");

        // Update password
        try {
            $updated = $this->userManager->update($user['id'], [
                'password' => $password // UserManager will hash it via Helpers::hashPassword()
            ]);

            if (!$updated) {
                error_log("[PASSWORD RESET ERROR] Failed to update password");
                $_SESSION['password_reset_error'] = $this->translator->translate('common.error_occurred');
                return $this->redirect("/reset-password?token={$token}&email=" . urlencode($email));
            }

            // Mark token as used
            $this->tokenManager->markAsUsed($token);
            error_log("[PASSWORD RESET SUCCESS] Password updated and token marked as used");

            // Log the event
            error_log("[PASSWORD RESET SUCCESS] Password reset completed for user ID: {$user['id']}");

            $_SESSION['login_success'] = $this->translator->translate('auth.password_reset_success');
            return $this->redirect('/login');

        } catch (\Exception $e) {
            error_log("[PASSWORD RESET ERROR] Exception: " . $e->getMessage());
            $_SESSION['password_reset_error'] = $this->translator->translate('common.error_occurred');
            return $this->redirect("/reset-password?token={$token}&email=" . urlencode($email));
        }
    }
}
