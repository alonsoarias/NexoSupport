<?php
/**
 * ISER - Admin Authorization Middleware
 * @package ISER\Core\Middleware
 */

namespace ISER\Core\Middleware;

use ISER\Core\Session\JWTSession;
use ISER\Core\Utils\Helpers;
use ISER\Core\Utils\Logger;
use ISER\Modules\User\UserManager;

class AdminMiddleware
{
    private JWTSession $jwt;
    private UserManager $userManager;
    private ?array $currentUser = null;

    public function __construct(JWTSession $jwt, UserManager $userManager)
    {
        $this->jwt = $jwt;
        $this->userManager = $userManager;
    }

    /**
     * Check if user is authenticated and has admin privileges
     */
    public function handle(): bool
    {
        // First, check if user is authenticated
        $token = $this->jwt->getTokenFromRequest();

        if (!$token || !($userData = $this->jwt->validate($token))) {
            $this->redirectToLogin('Not authenticated');
            return false;
        }

        // Get user from database
        $userId = $userData->user_id ?? null;
        if (!$userId) {
            $this->redirectToLogin('Invalid token data');
            return false;
        }

        $user = $this->userManager->getUserById($userId);
        if (!$user) {
            $this->redirectToLogin('User not found');
            return false;
        }

        // Check user status
        if ($user['status'] != 1) {
            Logger::security('Inactive user attempted admin access', ['userid' => $userId]);
            $this->accessDenied('Account is not active');
            return false;
        }

        // Check if user is suspended
        if ($this->userManager->isSuspended($userId)) {
            Logger::security('Suspended user attempted admin access', ['userid' => $userId]);
            $this->accessDenied('Account is suspended');
            return false;
        }

        // Check if user is deleted
        if ($this->userManager->isDeleted($userId)) {
            Logger::security('Deleted user attempted admin access', ['userid' => $userId]);
            $this->accessDenied('Account has been deleted');
            return false;
        }

        // For Phase 3: Basic admin check
        // TODO: In Phase 4, implement proper role-based access control
        if (!$this->isAdmin($user)) {
            Logger::security('Non-admin user attempted admin access', [
                'userid' => $userId,
                'username' => $user['username'],
                'ip' => Helpers::getClientIp()
            ]);
            $this->accessDenied('Insufficient privileges');
            return false;
        }

        // Store current user for future use
        $this->currentUser = $user;

        return true;
    }

    /**
     * Require admin access (use this in controllers)
     */
    public function requireAdmin(): void
    {
        if (!$this->handle()) {
            exit;
        }
    }

    /**
     * Check if user has admin role (basic implementation for Phase 3)
     * TODO: Replace with proper role system in Phase 4
     */
    private function isAdmin(array $user): bool
    {
        // For Phase 3: Consider username 'admin' as admin
        // In Phase 4, this will be replaced with proper role checking
        if ($user['username'] === 'admin') {
            return true;
        }

        // Check if user has admin role field (if it exists)
        if (isset($user['role']) && $user['role'] === 'admin') {
            return true;
        }

        // Check if user is in admins table (future implementation)
        // For now, return false for non-admin users
        return false;
    }

    /**
     * Get current authenticated admin user
     */
    public function getUser(): ?array
    {
        return $this->currentUser;
    }

    /**
     * Redirect to login page
     */
    private function redirectToLogin(string $reason = ''): void
    {
        $currentUrl = Helpers::getCurrentUrl();
        $_SESSION['redirect_after_login'] = $currentUrl;

        if ($reason) {
            $_SESSION['auth_error'] = $reason;
        }

        Helpers::redirect('/login.php');
    }

    /**
     * Show access denied error
     */
    private function accessDenied(string $message = 'Access Denied'): void
    {
        http_response_code(403);

        // Check if request expects JSON
        if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
            header('Content-Type: application/json');
            echo json_encode([
                'error' => true,
                'message' => $message,
                'code' => 403
            ]);
            exit;
        }

        // HTML response
        echo $this->renderAccessDeniedPage($message);
        exit;
    }

    /**
     * Render access denied HTML page
     */
    private function renderAccessDeniedPage(string $message): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Denegado - Sistema ISER</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 100%;
            padding: 40px;
            text-align: center;
        }
        .error-icon {
            font-size: 72px;
            color: #dc3545;
            margin-bottom: 20px;
        }
        h1 {
            color: #333;
            margin-bottom: 15px;
            font-size: 28px;
        }
        p {
            color: #666;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        .btn {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 12px 30px;
            border-radius: 5px;
            text-decoration: none;
            transition: background 0.3s;
            margin: 5px;
        }
        .btn:hover {
            background: #5568d3;
        }
        .btn-secondary {
            background: #6c757d;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="error-icon">🚫</div>
        <h1>Acceso Denegado</h1>
        <p>{$message}</p>
        <p>No tienes permisos suficientes para acceder a esta sección del sistema.</p>
        <div>
            <a href="/" class="btn btn-secondary">Volver al Inicio</a>
            <a href="/login.php" class="btn">Iniciar Sesión</a>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Check specific permission (for future use with role system)
     * TODO: Implement in Phase 4 with proper permission system
     */
    public function can(string $permission): bool
    {
        if (!$this->currentUser) {
            return false;
        }

        // For Phase 3, admin users can do everything
        return $this->isAdmin($this->currentUser);
    }

    /**
     * Check if user can manage users
     */
    public function canManageUsers(): bool
    {
        return $this->can('manage_users');
    }

    /**
     * Check if user can view system logs
     */
    public function canViewLogs(): bool
    {
        return $this->can('view_logs');
    }

    /**
     * Check if user can manage system settings
     */
    public function canManageSettings(): bool
    {
        return $this->can('manage_settings');
    }
}
