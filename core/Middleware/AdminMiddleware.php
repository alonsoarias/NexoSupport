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
use ISER\Modules\Roles\PermissionManager;
use ISER\Modules\Roles\RoleAssignment;

class AdminMiddleware
{
    private JWTSession $jwt;
    private UserManager $userManager;
    private PermissionManager $permissionManager;
    private RoleAssignment $roleAssignment;
    private ?array $currentUser = null;

    public function __construct(
        JWTSession $jwt,
        UserManager $userManager,
        PermissionManager $permissionManager,
        RoleAssignment $roleAssignment
    ) {
        $this->jwt = $jwt;
        $this->userManager = $userManager;
        $this->permissionManager = $permissionManager;
        $this->roleAssignment = $roleAssignment;
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

        // Phase 4: RBAC-based admin check using PermissionManager
        if (!$this->isAdmin($userId)) {
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
     * Check if user has admin privileges (Phase 4: RBAC)
     * Uses PermissionManager to check for site:config capability
     */
    private function isAdmin(int $userId): bool
    {
        // Admin capability: moodle/site:config (highest level permission)
        return $this->permissionManager->isAdmin($userId);
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
     * Check specific capability (Phase 4: RBAC)
     * Uses PermissionManager to verify capability
     *
     * @param string $capability Capability name (e.g., 'moodle/user:create')
     * @param int $contextId Context ID (default: system context)
     */
    public function can(string $capability, int $contextId = 1): bool
    {
        if (!$this->currentUser || !isset($this->currentUser['id'])) {
            return false;
        }

        return $this->permissionManager->hasCapability(
            $this->currentUser['id'],
            $capability,
            $contextId
        );
    }

    /**
     * Check if user can manage users
     */
    public function canManageUsers(): bool
    {
        return $this->can('moodle/user:update');
    }

    /**
     * Check if user can create users
     */
    public function canCreateUsers(): bool
    {
        return $this->can('moodle/user:create');
    }

    /**
     * Check if user can delete users
     */
    public function canDeleteUsers(): bool
    {
        return $this->can('moodle/user:delete');
    }

    /**
     * Check if user can view system logs
     */
    public function canViewLogs(): bool
    {
        return $this->can('moodle/site:viewlogs');
    }

    /**
     * Check if user can manage system settings
     */
    public function canManageSettings(): bool
    {
        return $this->can('moodle/site:config');
    }

    /**
     * Check if user can manage roles
     */
    public function canManageRoles(): bool
    {
        return $this->can('moodle/role:manage');
    }

    /**
     * Get user roles
     */
    public function getUserRoles(int $contextId = 1): array
    {
        if (!$this->currentUser || !isset($this->currentUser['id'])) {
            return [];
        }

        return $this->roleAssignment->getUserRoles($this->currentUser['id'], $contextId);
    }

    /**
     * Check if user has specific role by shortname
     */
    public function hasRole(string $roleShortname, int $contextId = 1): bool
    {
        if (!$this->currentUser || !isset($this->currentUser['id'])) {
            return false;
        }

        return $this->roleAssignment->userHasRoleByShortname(
            $this->currentUser['id'],
            $roleShortname,
            $contextId
        );
    }
}
