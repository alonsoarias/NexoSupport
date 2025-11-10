<?php

declare(strict_types=1);

namespace ISER\Controllers;

use ISER\Core\View\MustacheRenderer;
use ISER\Core\I18n\Translator;
use ISER\Core\Http\Response;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Home Controller
 *
 * Controlador para página principal y dashboard
 * Cumple con PSR-1, PSR-4, PSR-7 y PSR-12
 *
 * @package ISER\Controllers
 */
class HomeController
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
     * Landing page
     *
     * @param ServerRequestInterface $request PSR-7 Request
     * @return ResponseInterface PSR-7 Response
     */
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        // Si el usuario está autenticado, redirigir al dashboard
        if (isset($_SESSION['user_id']) && isset($_SESSION['authenticated'])) {
            return Response::redirect('/dashboard');
        }

        // Datos para la vista
        $data = [
            'locale' => $this->translator->getLocale(),
            'app_name' => $this->translator->translate('common.app_name'),
            'page_title' => $this->translator->translate('common.welcome'),
            'header_title' => $this->translator->translate('common.app_name'),
            'header_subtitle' => 'Sistema de Autenticación y Gestión',

            // Características del sistema
            'features' => [
                [
                    'icon' => 'shield-check',
                    'title' => 'Autenticación Segura',
                    'description' => 'Sistema robusto de autenticación con soporte MFA'
                ],
                [
                    'icon' => 'people',
                    'title' => 'Gestión de Usuarios',
                    'description' => 'Administración completa de usuarios y permisos'
                ],
                [
                    'icon' => 'graph-up',
                    'title' => 'Reportes Detallados',
                    'description' => 'Sistema de auditoría y reportes en tiempo real'
                ],
                [
                    'icon' => 'gear',
                    'title' => 'Altamente Configurable',
                    'description' => 'Personalización completa del sistema'
                ]
            ],

            // Links de acción
            'login_url' => '/login',
            'admin_url' => '/admin',
        ];

        $html = $this->renderer->render('home/index', $data);
        return Response::html($html);
    }

    /**
     * Dashboard principal
     *
     * @param ServerRequestInterface $request PSR-7 Request
     * @return ResponseInterface PSR-7 Response
     */
    public function dashboard(ServerRequestInterface $request): ResponseInterface
    {
        // Verificar autenticación
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['authenticated'])) {
            return Response::redirect('/login');
        }

        $data = [
            'locale' => $this->translator->getLocale(),
            'page_title' => $this->translator->translate('common.dashboard'),
            'header_title' => $this->translator->translate('common.dashboard'),
            'show_stats' => true,
            'username' => $_SESSION['username'] ?? 'Usuario',

            // Estadísticas del sistema
            'stats' => [
                ['label' => 'Usuarios Activos', 'value' => '150'],
                ['label' => 'Sesiones Hoy', 'value' => '45'],
                ['label' => 'Tasa de Éxito', 'value' => '98.5%'],
                ['label' => 'Uptime', 'value' => '99.9%'],
            ],
        ];

        $html = $this->renderer->render('dashboard/index', $data);
        return Response::html($html);
    }
}
