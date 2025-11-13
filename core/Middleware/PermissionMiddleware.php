<?php
/**
 * ISER - Permission Authorization Middleware (Phase 4)
 * Provides capability-based access control for routes
 *
 * @package ISER\Core\Middleware
 * @copyright 2024 ISER
 * @license Proprietary
 */

namespace ISER\Core\Middleware;

use ISER\Core\Session\JWTSession;
use ISER\Core\Utils\Helpers;
use ISER\Core\Utils\Logger;
use ISER\Core\Database\Database;
use ISER\User\UserManager;
use ISER\Roles\PermissionManager;

class PermissionMiddleware
{
    private JWTSession $jwt;
    private UserManager $userManager;
    private PermissionManager $permissionManager;
    private ?array $currentUser = null;
    private ?int $userId = null;

    public function __construct(JWTSession $jwt, UserManager $userManager, PermissionManager $permissionManager)
    {
        $this->jwt = $jwt;
        $this->userManager = $userManager;
        $this->permissionManager = $permissionManager;
    }

    /**
     * Check if user is authenticated and has required capability
     *
     * @param string $capability Required capability (e.g., 'moodle/user:create')
     * @param int $contextId Context ID (default: system context)
     * @return bool True if user has permission
     */
    public function handle(string $capability, int $contextId = 1): bool
    {
        // First, check if user is authenticated
        if (!$this->authenticate()) {
            return false;
        }

        // Check if user has the required capability
        if (!$this->hasCapability($capability, $contextId)) {
            Logger::security('Permission denied', [
                'userid' => $this->userId,
                'username' => $this->currentUser['username'] ?? 'unknown',
                'capability' => $capability,
                'contextid' => $contextId,
                'ip' => Helpers::getClientIp()
            ]);

            $this->accessDenied("Permiso requerido: {$capability}");
            return false;
        }

        return true;
    }

    /**
     * Require a specific capability (use in controllers)
     * Exits with 403 if permission denied
     *
     * @param string $capability Required capability
     * @param int $contextId Context ID (default: system context)
     */
    public function requireCapability(string $capability, int $contextId = 1): void
    {
        if (!$this->handle($capability, $contextId)) {
            exit;
        }
    }

    /**
     * Require any of multiple capabilities (user needs at least one)
     *
     * @param array $capabilities Array of capability names
     * @param int $contextId Context ID (default: system context)
     */
    public function requireAnyCapability(array $capabilities, int $contextId = 1): void
    {
        if (!$this->authenticate()) {
            exit;
        }

        foreach ($capabilities as $capability) {
            if ($this->hasCapability($capability, $contextId)) {
                return; // User has at least one capability
            }
        }

        Logger::security('Permission denied - none of required capabilities', [
            'userid' => $this->userId,
            'username' => $this->currentUser['username'] ?? 'unknown',
            'capabilities' => $capabilities,
            'contextid' => $contextId,
            'ip' => Helpers::getClientIp()
        ]);

        $this->accessDenied('No tienes ninguno de los permisos requeridos');
        exit;
    }

    /**
     * Require all of multiple capabilities (user needs all)
     *
     * @param array $capabilities Array of capability names
     * @param int $contextId Context ID (default: system context)
     */
    public function requireAllCapabilities(array $capabilities, int $contextId = 1): void
    {
        if (!$this->authenticate()) {
            exit;
        }

        foreach ($capabilities as $capability) {
            if (!$this->hasCapability($capability, $contextId)) {
                Logger::security('Permission denied - missing required capability', [
                    'userid' => $this->userId,
                    'username' => $this->currentUser['username'] ?? 'unknown',
                    'capability' => $capability,
                    'all_required' => $capabilities,
                    'contextid' => $contextId,
                    'ip' => Helpers::getClientIp()
                ]);

                $this->accessDenied("Permiso requerido: {$capability}");
                exit;
            }
        }
    }

    /**
     * Check if current user has a capability (non-blocking)
     *
     * @param string $capability Capability name
     * @param int $contextId Context ID (default: system context)
     * @return bool True if user has capability
     */
    public function hasCapability(string $capability, int $contextId = 1): bool
    {
        if (!$this->userId) {
            return false;
        }

        return $this->permissionManager->hasCapability($this->userId, $capability, $contextId);
    }

    /**
     * Check if current user is admin
     *
     * @return bool True if user is admin
     */
    public function isAdmin(): bool
    {
        if (!$this->userId) {
            return false;
        }

        return $this->permissionManager->isAdmin($this->userId);
    }

    /**
     * Get current authenticated user
     *
     * @return array|null User data or null if not authenticated
     */
    public function getUser(): ?array
    {
        return $this->currentUser;
    }

    /**
     * Get current user ID
     *
     * @return int|null User ID or null if not authenticated
     */
    public function getUserId(): ?int
    {
        return $this->userId;
    }

    /**
     * Get all capabilities for current user
     *
     * @param int $contextId Context ID (default: system context)
     * @return array Array of capabilities
     */
    public function getUserCapabilities(int $contextId = 1): array
    {
        if (!$this->userId) {
            return [];
        }

        return $this->permissionManager->getUserCapabilities($this->userId, $contextId);
    }

    /**
     * Authenticate user from JWT token
     *
     * @return bool True if authenticated
     */
    private function authenticate(): bool
    {
        // Get token from request
        $token = $this->jwt->getTokenFromRequest();

        if (!$token || !($userData = $this->jwt->validate($token))) {
            $this->redirectToLogin('No autenticado');
            return false;
        }

        // Get user ID from token
        $userId = $userData->user_id ?? null;
        if (!$userId) {
            $this->redirectToLogin('Token inválido');
            return false;
        }

        // Get user from database
        $user = $this->userManager->getUserById($userId);
        if (!$user) {
            $this->redirectToLogin('Usuario no encontrado');
            return false;
        }

        // Check user status
        if ($user['status'] != 1) {
            Logger::security('Inactive user attempted access', ['userid' => $userId]);
            $this->accessDenied('Cuenta no activa');
            return false;
        }

        // Check if user is suspended
        if ($this->userManager->isSuspended($userId)) {
            Logger::security('Suspended user attempted access', ['userid' => $userId]);
            $this->accessDenied('Cuenta suspendida');
            return false;
        }

        // Check if user is deleted
        if ($this->userManager->isDeleted($userId)) {
            Logger::security('Deleted user attempted access', ['userid' => $userId]);
            $this->accessDenied('Cuenta eliminada');
            return false;
        }

        // Store current user
        $this->currentUser = $user;
        $this->userId = $userId;

        return true;
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
     * Show access denied error (403)
     */
    private function accessDenied(string $message = 'Acceso Denegado'): void
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
            return;
        }

        // HTML response
        echo $this->renderAccessDeniedPage($message);
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
            margin-bottom: 20px;
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
        <p><strong>{$message}</strong></p>
        <p>No tienes los permisos necesarios para acceder a esta sección del sistema.</p>
        <div>
            <a href="/" class="btn btn-secondary">Volver al Inicio</a>
            <a href="/login.php" class="btn">Iniciar Sesión</a>
        </div>
    </div>
</body>
</html>
HTML;
    }
}
