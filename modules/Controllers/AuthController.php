<?php

declare(strict_types=1);

namespace ISER\Controllers;

use ISER\Core\View\MustacheRenderer;
use ISER\Core\I18n\Translator;
use ISER\Core\Http\Response;
use ISER\Core\Database\Database;
use ISER\Core\Utils\Helpers;
use ISER\User\UserManager;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Authentication Controller
 * Sistema de autenticación simplificado y robusto
 */
class AuthController
{
    private MustacheRenderer $renderer;
    private Translator $translator;
    private Database $db;
    private UserManager $userManager;

    public function __construct(Database $db)
    {
        $this->renderer = MustacheRenderer::getInstance();
        $this->translator = Translator::getInstance();
        $this->db = $db;
        $this->userManager = new UserManager($db);
    }

    /**
     * Mostrar formulario de login
     */
    public function showLogin(ServerRequestInterface $request): ResponseInterface
    {
        // Si ya está autenticado, redirigir al dashboard
        if ($this->isAuthenticated()) {
            return Response::redirect('/dashboard');
        }

        $data = [
            'locale' => $this->translator->getLocale(),
            'page_title' => $this->translator->translate('auth.login'),
            'header_title' => $this->translator->translate('auth.login'),
            'login_url' => '/login',
            'error' => $_SESSION['login_error'] ?? null,
        ];

        unset($_SESSION['login_error']);

        $html = $this->renderer->render('auth/login', $data);
        return Response::html($html);
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
            return Response::redirect('/login');
        }

        $username = trim($body['username'] ?? '');
        $password = $body['password'] ?? '';

        error_log("[LOGIN] Username recibido: '{$username}'");
        error_log("[LOGIN] Password recibido: " . (empty($password) ? 'VACIO' : 'OK (longitud: ' . strlen($password) . ')'));

        // Validar que no estén vacíos
        if (empty($username) || empty($password)) {
            error_log("[LOGIN ERROR] Credenciales vacías");
            $_SESSION['login_error'] = $this->translator->translate('auth.credentials_required');
            return Response::redirect('/login');
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
            return Response::redirect('/login');
        }

        error_log("[LOGIN] Usuario encontrado - ID: {$user['id']}, Username: {$user['username']}, Email: {$user['email']}");

        // Verificar que el usuario esté activo (comparación case-insensitive)
        $userStatus = strtolower($user['status'] ?? 'active');
        if ($userStatus !== 'active') {
            error_log("[LOGIN ERROR] Usuario inactivo - Status: {$user['status']}");
            $_SESSION['login_error'] = 'Usuario inactivo o suspendido';
            return Response::redirect('/login');
        }

        // Verificar que no esté eliminado
        if (!empty($user['deleted_at'])) {
            error_log("[LOGIN ERROR] Usuario eliminado");
            $_SESSION['login_error'] = 'Usuario no disponible';
            return Response::redirect('/login');
        }

        // Verificar si la cuenta está bloqueada
        $lockedUntil = $user['locked_until'] ?? 0;
        if ($lockedUntil > time()) {
            $remainingTime = ceil(($lockedUntil - time()) / 60);
            error_log("[LOGIN ERROR] Cuenta bloqueada por {$remainingTime} minutos más");
            $_SESSION['login_error'] = "Cuenta bloqueada. Intenta en {$remainingTime} minutos.";
            return Response::redirect('/login');
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

            // Incrementar contador de intentos fallidos
            $failedAttempts = ($user['failed_login_attempts'] ?? 0) + 1;
            error_log("[LOGIN] Intentos fallidos: {$failedAttempts}");

            // Bloquear cuenta después de 5 intentos
            if ($failedAttempts >= 5) {
                $lockDuration = 900; // 15 minutos
                $this->userManager->update($user['id'], [
                    'failed_login_attempts' => $failedAttempts,
                    'locked_until' => time() + $lockDuration
                ]);
                error_log("[LOGIN] Cuenta bloqueada por 15 minutos");
                $_SESSION['login_error'] = 'Demasiados intentos fallidos. Cuenta bloqueada por 15 minutos.';
            } else {
                $this->userManager->update($user['id'], [
                    'failed_login_attempts' => $failedAttempts
                ]);
                $_SESSION['login_error'] = $this->translator->translate('auth.invalid_credentials');
            }

            return Response::redirect('/login');
        }

        error_log("[LOGIN SUCCESS] Contraseña verificada correctamente");

        // Resetear intentos fallidos
        $this->userManager->update($user['id'], [
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'last_login_at' => time(),
            'last_login_ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
        ]);

        // Registrar intento exitoso
        $this->recordSuccessfulAttempt($username);

        // Crear sesión
        $this->createSession($user);

        error_log("[LOGIN SUCCESS] Sesión creada - Redirigiendo a dashboard");
        error_log("========================================");

        return Response::redirect('/dashboard');
    }

    /**
     * Cerrar sesión
     */
    public function logout(ServerRequestInterface $request): ResponseInterface
    {
        error_log("[LOGOUT] Usuario cerrando sesión - User ID: " . ($_SESSION['user_id'] ?? 'N/A'));

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

        return Response::redirect('/');
    }

    /**
     * Verificar si el usuario está autenticado
     */
    private function isAuthenticated(): bool
    {
        return isset($_SESSION['user_id'])
            && isset($_SESSION['authenticated'])
            && $_SESSION['authenticated'] === true;
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
