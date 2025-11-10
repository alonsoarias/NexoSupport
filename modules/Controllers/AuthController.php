<?php

declare(strict_types=1);

namespace ISER\Controllers;

use ISER\Core\View\MustacheRenderer;
use ISER\Core\I18n\Translator;
use ISER\Core\Http\Response;
use ISER\Core\Http\Request;
use ISER\Core\Auth\AuthService;
use ISER\Core\Database\Database;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Authentication Controller
 *
 * Controlador para autenticación de usuarios
 * Cumple con PSR-1, PSR-4, PSR-7 y PSR-12
 *
 * @package ISER\Controllers
 */
class AuthController
{
    private MustacheRenderer $renderer;
    private Translator $translator;
    private AuthService $authService;

    /**
     * Constructor
     */
    public function __construct(Database $db)
    {
        $this->renderer = MustacheRenderer::getInstance();
        $this->translator = Translator::getInstance();
        $this->authService = new AuthService($db);
    }

    /**
     * Mostrar formulario de login
     *
     * @param ServerRequestInterface $request PSR-7 Request
     * @return ResponseInterface PSR-7 Response
     */
    public function showLogin(ServerRequestInterface $request): ResponseInterface
    {
        // Si ya está autenticado, redirigir al dashboard
        if (isset($_SESSION['user_id']) && isset($_SESSION['authenticated'])) {
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
     *
     * @param ServerRequestInterface $request PSR-7 Request
     * @return ResponseInterface PSR-7 Response
     */
    public function processLogin(ServerRequestInterface $request): ResponseInterface
    {
        error_log("[AuthController] Processing login request");

        // Obtener datos del POST
        $body = $request->getParsedBody();
        $username = $body['username'] ?? '';
        $password = $body['password'] ?? '';

        error_log("[AuthController] Username: {$username}, Password length: " . strlen($password));
        error_log("[AuthController] POST data: " . json_encode(array_keys($body)));

        // Validación básica
        if (empty($username) || empty($password)) {
            error_log("[AuthController] Empty credentials");
            $_SESSION['login_error'] = $this->translator->translate('auth.credentials_required');
            return Response::redirect('/login');
        }

        // Obtener IP del cliente
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        error_log("[AuthController] Client IP: {$ipAddress}");

        // Autenticar usuario
        $user = $this->authService->authenticate($username, $password, $ipAddress);

        if (!$user) {
            error_log("[AuthController] Authentication FAILED for username: {$username}");
            $_SESSION['login_error'] = $this->translator->translate('auth.invalid_credentials');
            return Response::redirect('/login');
        }

        error_log("[AuthController] Authentication SUCCESS, creating session");

        // Crear sesión
        $this->authService->createSession($user);

        error_log("[AuthController] Session created, redirecting to dashboard");

        // Redirigir al dashboard
        return Response::redirect('/dashboard');
    }

    /**
     * Cerrar sesión
     *
     * @param ServerRequestInterface $request PSR-7 Request
     * @return ResponseInterface PSR-7 Response
     */
    public function logout(ServerRequestInterface $request): ResponseInterface
    {
        $this->authService->destroySession();
        return Response::redirect('/');
    }
}
