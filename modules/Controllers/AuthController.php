<?php

declare(strict_types=1);

namespace ISER\Controllers;

use ISER\Core\Controllers\BaseController;
use ISER\Core\Database\Database;
use ISER\User\UserManager;
use ISER\User\AccountSecurityManager;
use ISER\User\LoginHistoryManager;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Authentication Controller (REFACTORIZADO con BaseController)
 * Sistema de autenticación simplificado y robusto
 * Updated for FASE 6: 3FN normalization with separate security and login history tables
 *
 * Extiende BaseController para reducir código duplicado.
 */
class AuthController extends BaseController
{
    private UserManager $userManager;
    private AccountSecurityManager $securityManager;
    private LoginHistoryManager $loginHistory;

    public function __construct(Database $db)
    {
        parent::__construct($db);
        $this->userManager = new UserManager($db);
        $this->securityManager = new AccountSecurityManager($db);
        $this->loginHistory = new LoginHistoryManager($db);
    }

    /**
     * Mostrar formulario de login
     */
    public function showLogin(ServerRequestInterface $request): ResponseInterface
    {
        // Si ya está autenticado, redirigir al dashboard
        if ($this->isAuthenticated()) {
            return $this->redirect('/dashboard');
        }

        $data = [
            'page_title' => $this->translator->translate('auth.login'),
            'header_title' => $this->translator->translate('auth.login'),
            'login_url' => '/login',
            'error' => $_SESSION['login_error'] ?? null,
            'success' => $_SESSION['login_success'] ?? null,
        ];

        unset($_SESSION['login_error']);
        unset($_SESSION['login_success']);

        return $this->renderWithLayout('auth/login', $data);
    }

    /**
     * Procesar login
     */
    public function processLogin(ServerRequestInterface $request): ResponseInterface
    {
        // Log inicio del proceso
        error_log("========================================");
        error_log("[LOGIN] Inicio del proceso de autenticación");
        error_log("========================================");

        // Obtener datos del formulario
        $body = $request->getParsedBody();

        if (!is_array($body)) {
            error_log("[LOGIN ERROR] Body no es array: " . gettype($body));
            $_SESSION['login_error'] = 'Error en el formato de datos';
            return $this->redirect('/login');
        }

        $username = trim($body['username'] ?? '');
        $password = $body['password'] ?? '';

        error_log("[LOGIN] Username recibido: '{$username}'");
        error_log("[LOGIN] Password recibido: " . (empty($password) ? 'VACIO' : 'OK (longitud: ' . strlen($password) . ')'));

        // Validar que no estén vacíos
        if (empty($username) || empty($password)) {
            error_log("[LOGIN ERROR] Credenciales vacías");
            $_SESSION['login_error'] = $this->translator->translate('auth.credentials_required');
            return $this->redirect('/login');
        }

        // Buscar usuario
        error_log("[LOGIN] Buscando usuario en base de datos...");
        $user = $this->userManager->getUserByUsername($username);

        if (!$user) {
            error_log("[LOGIN] Usuario no encontrado por username, intentando por email...");
            $user = $this->userManager->getUserByEmail($username);
        }

        if (!$user) {
            error_log("[LOGIN ERROR] Usuario no existe: {$username}");
            $this->recordFailedAttempt($username);
            $_SESSION['login_error'] = $this->translator->translate('auth.invalid_credentials');
            return $this->redirect('/login');
        }

        error_log("[LOGIN] Usuario encontrado - ID: {$user['id']}, Username: {$user['username']}, Email: {$user['email']}");

        // Verificar que el usuario esté activo (comparación case-insensitive)
        $userStatus = strtolower($user['status'] ?? 'active');
        if ($userStatus !== 'active') {
            error_log("[LOGIN ERROR] Usuario inactivo - Status: {$user['status']}");
            $_SESSION['login_error'] = 'Usuario inactivo o suspendido';
            return $this->redirect('/login');
        }

        // Verificar que no esté eliminado
        if (!empty($user['deleted_at'])) {
            error_log("[LOGIN ERROR] Usuario eliminado");
            $_SESSION['login_error'] = 'Usuario no disponible';
            return $this->redirect('/login');
        }

        // Verificar si la cuenta está bloqueada (FASE 6: using account_security table)
        if ($this->securityManager->isLocked($user['id'])) {
            $remainingTime = ceil($this->securityManager->getRemainingLockTime($user['id']) / 60);
            error_log("[LOGIN ERROR] Cuenta bloqueada por {$remainingTime} minutos más");
            $_SESSION['login_error'] = "Cuenta bloqueada. Intenta en {$remainingTime} minutos.";
            return $this->redirect('/login');
        }

        // Verificar contraseña
        error_log("[LOGIN] Verificando contraseña...");
        $passwordHash = $user['password'];
        $hashInfo = password_get_info($passwordHash);

        error_log("[LOGIN] Hash algorithm: {$hashInfo['algoName']}");
        error_log("[LOGIN] Hash preview: " . substr($passwordHash, 0, 30) . "...");

        $passwordValid = password_verify($password, $passwordHash);

        if (!$passwordValid) {
            error_log("[LOGIN ERROR] Contraseña incorrecta");
            $this->recordFailedAttempt($username, $user['id']);

            // Record failed attempt in account_security table (FASE 6)
            $this->securityManager->recordFailedAttempt($user['id']);
            $failedAttempts = $this->securityManager->getFailedAttempts($user['id']);
            error_log("[LOGIN] Intentos fallidos: {$failedAttempts}");

            // Check if account is now locked
            if ($this->securityManager->isLocked($user['id'])) {
                error_log("[LOGIN] Cuenta bloqueada por intentos fallidos");
                $_SESSION['login_error'] = 'Demasiados intentos fallidos. Cuenta bloqueada por 15 minutos.';
            } else {
                $_SESSION['login_error'] = $this->translator->translate('auth.invalid_credentials');
            }

            return $this->redirect('/login');
        }

        error_log("[LOGIN SUCCESS] Contraseña verificada correctamente");

        // Reset failed login attempts (FASE 6: using account_security table)
        $this->securityManager->resetAttempts($user['id']);

        // Record successful login in login_history (FASE 6: using login_history table)
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $sessionId = session_id();
        $loginId = $this->loginHistory->recordLogin($user['id'], $ipAddress, $userAgent, $sessionId);

        // Store login_id in session for logout tracking
        if ($loginId !== false) {
            $_SESSION['login_id'] = $loginId;
        }

        // Registrar intento exitoso
        $this->recordSuccessfulAttempt($username);

        // Crear sesión
        $this->createSession($user);

        error_log("[LOGIN SUCCESS] Sesión creada - Redirigiendo a dashboard");
        error_log("========================================");

        return $this->redirect('/dashboard');
    }

    /**
     * Cerrar sesión
     */
    public function logout(ServerRequestInterface $request): ResponseInterface
    {
        error_log("[LOGOUT] Usuario cerrando sesión - User ID: " . ($_SESSION['user_id'] ?? 'N/A'));

        // Record logout in login_history (FASE 6)
        if (isset($_SESSION['login_id'])) {
            $this->loginHistory->recordLogout($_SESSION['login_id']);
            error_log("[LOGOUT] Logout recorded in login_history - Login ID: " . $_SESSION['login_id']);
        }

        // Destruir sesión
        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        session_destroy();

        return $this->redirect('/');
    }

    /**
     * Crear sesión de usuario
     */
    private function createSession(array $user): void
    {
        // Regenerar ID de sesión por seguridad
        session_regenerate_id(true);

        // Establecer variables de sesión
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['authenticated'] = true;
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();

        error_log("[SESSION] Sesión creada:");
        error_log("[SESSION] - user_id: {$user['id']}");
        error_log("[SESSION] - username: {$user['username']}");
        error_log("[SESSION] - email: {$user['email']}");
    }

    /**
     * Registrar intento fallido
     */
    private function recordFailedAttempt(string $username, ?int $userId = null): void
    {
        try {
            $this->db->insert('login_attempts', [
                'username' => $username,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
                'success' => 0,
                'attempted_at' => time(),
            ]);
            error_log("[LOGIN] Intento fallido registrado en DB");
        } catch (\Exception $e) {
            error_log("[LOGIN ERROR] No se pudo registrar intento fallido: " . $e->getMessage());
        }
    }

    /**
     * Registrar intento exitoso
     */
    private function recordSuccessfulAttempt(string $username): void
    {
        try {
            $this->db->insert('login_attempts', [
                'username' => $username,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
                'success' => 1,
                'attempted_at' => time(),
            ]);
            error_log("[LOGIN] Intento exitoso registrado en DB");
        } catch (\Exception $e) {
            error_log("[LOGIN ERROR] No se pudo registrar intento exitoso: " . $e->getMessage());
        }
    }
}
