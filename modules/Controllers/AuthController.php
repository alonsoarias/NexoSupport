<?php

declare(strict_types=1);

namespace ISER\Controllers;

use ISER\Core\View\MustacheRenderer;
use ISER\Core\I18n\Translator;
use ISER\Core\Http\Response;
use ISER\Core\Http\Request;
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

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->renderer = MustacheRenderer::getInstance();
        $this->translator = Translator::getInstance();
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
        // Obtener datos del POST
        $body = $request->getParsedBody();
        $username = $body['username'] ?? '';
        $password = $body['password'] ?? '';

        // Validación básica
        if (empty($username) || empty($password)) {
            $_SESSION['login_error'] = 'Usuario y contraseña son requeridos';
            return Response::redirect('/login');
        }

        // TODO: Implementar lógica real de autenticación contra base de datos
        // Por ahora, solo validación básica para demo

        // Autenticación exitosa (temporal)
        $_SESSION['user_id'] = 1;
        $_SESSION['username'] = $username;
        $_SESSION['authenticated'] = true;

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
        session_destroy();
        return Response::redirect('/');
    }
}
