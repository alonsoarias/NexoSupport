<?php
/**
 * Authentication Controller
 *
 * @package ISER\Controllers
 */

namespace ISER\Controllers;

use ISER\Core\View\MustacheRenderer;
use ISER\Core\I18n\Translator;

class AuthController
{
    private MustacheRenderer $renderer;
    private Translator $translator;

    public function __construct()
    {
        $this->renderer = MustacheRenderer::getInstance();
        $this->translator = Translator::getInstance();
    }

    /**
     * Mostrar formulario de login
     */
    public function showLogin(): void
    {
        // Si ya está autenticado, redirigir al dashboard
        if (isset($_SESSION['user_id']) && isset($_SESSION['authenticated'])) {
            header('Location: /dashboard');
            exit;
        }

        $data = [
            'locale' => $this->translator->getLocale(),
            'page_title' => $this->translator->translate('auth.login'),
            'header_title' => $this->translator->translate('auth.login'),
            'login_url' => '/login',
            'error' => $_SESSION['login_error'] ?? null
        ];

        unset($_SESSION['login_error']);

        echo $this->renderer->render('auth/login', $data);
    }

    /**
     * Procesar login
     */
    public function processLogin(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /login');
            exit;
        }

        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        // TODO: Implementar lógica real de autenticación
        // Por ahora, solo validación básica
        if (empty($username) || empty($password)) {
            $_SESSION['login_error'] = 'Usuario y contraseña son requeridos';
            header('Location: /login');
            exit;
        }

        // Autenticación exitosa (temporal)
        $_SESSION['user_id'] = 1;
        $_SESSION['username'] = $username;
        $_SESSION['authenticated'] = true;

        header('Location: /dashboard');
        exit;
    }

    /**
     * Cerrar sesión
     */
    public function logout(): void
    {
        session_destroy();
        header('Location: /');
        exit;
    }
}
