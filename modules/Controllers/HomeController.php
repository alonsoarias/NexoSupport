<?php
/**
 * Home Controller
 *
 * @package ISER\Controllers
 */

namespace ISER\Controllers;

use ISER\Core\View\MustacheRenderer;
use ISER\Core\I18n\Translator;

class HomeController
{
    private MustacheRenderer $renderer;
    private Translator $translator;

    public function __construct()
    {
        $this->renderer = MustacheRenderer::getInstance();
        $this->translator = Translator::getInstance();
    }

    /**
     * Landing page
     */
    public function index(): void
    {
        // Si el usuario está autenticado, redirigir al dashboard
        if (isset($_SESSION['user_id']) && isset($_SESSION['authenticated'])) {
            header('Location: /dashboard');
            exit;
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
            'admin_url' => '/admin'
        ];

        // Template inline para landing page (temporalmente hasta migración completa)
        echo $this->renderer->render('home/index', $data);
    }

    /**
     * Dashboard principal
     */
    public function dashboard(): void
    {
        // Verificar autenticación
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['authenticated'])) {
            header('Location: /login');
            exit;
        }

        $data = [
            'locale' => $this->translator->getLocale(),
            'page_title' => $this->translator->translate('common.dashboard'),
            'header_title' => $this->translator->translate('common.dashboard'),
            'show_stats' => true,

            // Estadísticas del sistema
            'stats' => [
                ['label' => 'Usuarios Activos', 'value' => '150'],
                ['label' => 'Sesiones Hoy', 'value' => '45'],
                ['label' => 'Tasa de Éxito', 'value' => '98.5%'],
                ['label' => 'Uptime', 'value' => '99.9%']
            ]
        ];

        echo $this->renderer->render('dashboard/index', $data);
    }
}
