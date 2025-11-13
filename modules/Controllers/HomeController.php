<?php

declare(strict_types=1);

namespace ISER\Controllers;

use ISER\Core\Controllers\BaseController;
use ISER\Core\Database\Database;
use ISER\User\UserManager;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Home Controller (REFACTORIZADO con BaseController)
 *
 * Controlador para página principal y dashboard
 * Cumple con PSR-1, PSR-4, PSR-7 y PSR-12
 *
 * Extiende BaseController para reducir código duplicado.
 *
 * @package ISER\Controllers
 */
class HomeController extends BaseController
{
    private UserManager $userManager;

    /**
     * Constructor
     */
    public function __construct(Database $db)
    {
        parent::__construct($db);
        $this->userManager = new UserManager($db);
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
        if ($this->isAuthenticated()) {
            return $this->redirect('/dashboard');
        }

        // Datos para la vista
        $data = [
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

        // Landing page sin navegación admin
        return $this->renderWithLayout('home/index', $data);
    }

    /**
     * Dashboard principal
     *
     * @param ServerRequestInterface $request PSR-7 Request
     * @return ResponseInterface PSR-7 Response
     */
    public function dashboard(ServerRequestInterface $request): ResponseInterface
    {
        // Verificar autenticación (usar método de BaseController)
        if (!$this->isAuthenticated()) {
            return $this->redirect('/login');
        }

        // Obtener estadísticas reales de la base de datos
        $totalUsers = $this->userManager->countUsers();
        $activeUsers = $this->userManager->countUsers(['status' => 'active']);

        // Obtener sesiones de hoy (login_attempts con success = 1)
        $todayStart = strtotime('today');
        $sessionsToday = $this->getSessionsToday($todayStart);

        // Obtener tasa de éxito de login
        $loginSuccessRate = $this->getLoginSuccessRate();

        // Obtener información del usuario actual (usar método de BaseController)
        $currentUserId = $this->getCurrentUserId();
        $currentUser = $this->userManager->getUserById($currentUserId);
        $fullName = $currentUser
            ? trim(($currentUser['first_name'] ?? '') . ' ' . ($currentUser['last_name'] ?? ''))
            : ($_SESSION['username'] ?? 'Usuario');

        $data = [
            'page_title' => $this->translator->translate('common.dashboard'),
            'header_title' => $this->translator->translate('common.dashboard'),
            'show_stats' => true,
            'username' => $fullName ?: $_SESSION['username'] ?? 'Usuario',
            'user_email' => $_SESSION['email'] ?? '',

            // Estadísticas reales del sistema
            'stats' => [
                ['label' => 'Total Usuarios', 'value' => (string)$totalUsers],
                ['label' => 'Usuarios Activos', 'value' => (string)$activeUsers],
                ['label' => 'Sesiones Hoy', 'value' => (string)$sessionsToday],
                ['label' => 'Tasa de Éxito Login', 'value' => $loginSuccessRate . '%'],
            ],
        ];

        // Usar render() de BaseController con navegación automática
        return $this->render('dashboard/index', $data, '/dashboard');
    }

    /**
     * Página de perfil de usuario
     *
     * @param ServerRequestInterface $request PSR-7 Request
     * @return ResponseInterface PSR-7 Response
     */
    public function profile(ServerRequestInterface $request): ResponseInterface
    {
        // Verificar que el usuario esté autenticado
        if (!$this->isAuthenticated()) {
            return $this->redirect('/login');
        }

        // Obtener información del usuario (usar getCurrentUserId de BaseController)
        $userId = $this->getCurrentUserId();
        $user = $this->userManager->getUserById($userId);

        if (!$user) {
            return $this->redirect('/login');
        }

        $data = [
            'user' => $user,
            'page_title' => 'Mi Perfil - ' . $this->translator->translate('app_name'),
        ];

        // Usar render() con navegación automática
        return $this->render('profile/index', $data, '/profile');
    }

    /**
     * Obtener número de sesiones iniciadas hoy
     */
    private function getSessionsToday(int $todayStart): int
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM {$this->db->table('login_attempts')}
                    WHERE success = 1 AND attempted_at >= :today_start";
            $result = $this->db->getConnection()->fetchOne($sql, [':today_start' => $todayStart]);
            return (int)($result['count'] ?? 0);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Obtener tasa de éxito de login (últimos 7 días)
     */
    private function getLoginSuccessRate(): string
    {
        try {
            $weekAgo = time() - (7 * 24 * 60 * 60);

            // Total intentos
            $sqlTotal = "SELECT COUNT(*) as count FROM {$this->db->table('login_attempts')}
                        WHERE attempted_at >= :week_ago";
            $resultTotal = $this->db->getConnection()->fetchOne($sqlTotal, [':week_ago' => $weekAgo]);
            $total = (int)($resultTotal['count'] ?? 0);

            if ($total === 0) {
                return '0.0';
            }

            // Intentos exitosos
            $sqlSuccess = "SELECT COUNT(*) as count FROM {$this->db->table('login_attempts')}
                          WHERE success = 1 AND attempted_at >= :week_ago";
            $resultSuccess = $this->db->getConnection()->fetchOne($sqlSuccess, [':week_ago' => $weekAgo]);
            $success = (int)($resultSuccess['count'] ?? 0);

            $rate = ($success / $total) * 100;
            return number_format($rate, 1);
        } catch (\Exception $e) {
            return '0.0';
        }
    }
}
