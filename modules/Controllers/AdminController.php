<?php

declare(strict_types=1);

namespace ISER\Controllers;

use ISER\Core\Controllers\BaseController;
use ISER\Core\Database\Database;
use ISER\User\UserManager;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Admin Controller (REFACTORIZADO con BaseController)
 *
 * Controlador para panel de administración
 * Maneja usuarios, configuración, reportes, etc.
 *
 * Extiende BaseController para reducir código duplicado.
 */
class AdminController extends BaseController
{
    private UserManager $userManager;

    public function __construct(Database $db)
    {
        parent::__construct($db);
        $this->userManager = new UserManager($db);
    }

    /**
     * Panel principal de administración
     */
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        // Verificar autenticación
        if (!$this->isAuthenticated()) {
            return $this->redirect('/login');
        }

        // Obtener usuario actual
        $currentUser = $this->userManager->getUserById((int)$_SESSION['user_id']);

        // Estadísticas del sistema
        $stats = $this->getSystemStats();

        // Actividad reciente (últimos 10 logins)
        $recentActivity = $this->getRecentActivity(10);

        // Usuarios recientes (últimos 5)
        $recentUsers = $this->getRecentUsers(5);

        $data = [
            'locale' => $this->translator->getLocale(),
            'page_title' => __('admin.dashboard'),
            'header_title' => __('admin.system'),
            'current_user' => [
                'full_name' => trim(($currentUser['first_name'] ?? '') . ' ' . ($currentUser['last_name'] ?? '')),
                'email' => $currentUser['email'] ?? '',
                'username' => $currentUser['username'] ?? '',
            ],
            'stats' => $stats,
            'recent_activity' => $recentActivity,
            'recent_users' => $recentUsers,
            'menu_items' => [
                ['icon' => 'people', 'title' => __('admin.menu.users'), 'url' => '/admin/users', 'count' => $stats['total_users']],
                ['icon' => 'shield-check', 'title' => __('admin.security'), 'url' => '/admin/security', 'count' => $stats['login_attempts_today']],
                ['icon' => 'gear', 'title' => __('admin.configuration'), 'url' => '/admin/settings', 'count' => null],
                ['icon' => 'graph-up', 'title' => __('admin.reports'), 'url' => '/admin/reports', 'count' => null],
            ],
        ];

        return $this->render('admin/index', $data, '/admin');
    }

    /**
     * Gestión de usuarios
     */
    public function users(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->isAuthenticated()) {
            return $this->redirect('/login');
        }

        // Obtener todos los usuarios
        $users = $this->userManager->getUsers(100, 0, ['deleted' => false]);

        // Estadísticas de usuarios
        $totalUsers = $this->userManager->countUsers();
        $activeUsers = $this->userManager->countUsers(['status' => 'active']);
        $inactiveUsers = $this->userManager->countUsers(['status' => 'inactive']);
        $suspendedUsers = $this->userManager->countUsers(['status' => 'suspended']);

        $data = [
            'locale' => $this->translator->getLocale(),
            'page_title' => __('users.management_title'),
            'header_title' => __('users.management_title'),
            'users' => array_map(function($user) {
                return [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'full_name' => trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')),
                    'status' => $user['status'] ?? 'active',
                    'status_label' => $this->getStatusLabel($user['status'] ?? 'active'),
                    'created_at' => date('Y-m-d H:i', $user['created_at'] ?? time()),
                    'last_login' => $user['last_login_at'] ? date('Y-m-d H:i', $user['last_login_at']) : __('common.never'),
                ];
            }, $users),
            'stats' => [
                'total' => $totalUsers,
                'active' => $activeUsers,
                'inactive' => $inactiveUsers,
                'suspended' => $suspendedUsers,
            ],
        ];

        return $this->renderWithLayout('admin/users', $data);
    }

    /**
     * Configuración del sistema
     */
    public function settings(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->isAuthenticated()) {
            return $this->redirect('/login');
        }

        // Obtener configuración actual del sistema
        $config = $this->getSystemConfig();

        $data = [
            'locale' => $this->translator->getLocale(),
            'page_title' => __('settings.system_title'),
            'header_title' => __('admin.configuration'),
            'config' => $config,
        ];

        return $this->render('admin/settings', $data, '/admin/settings');
    }

    /**
     * Reportes del sistema
     */
    public function reports(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->isAuthenticated()) {
            return $this->redirect('/login');
        }

        // Estadísticas de login por día (últimos 7 días)
        $loginStats = $this->getLoginStatsByDay(7);

        // Top 10 IPs con más intentos
        $topIPs = $this->getTopIPsByAttempts(10);

        $data = [
            'locale' => $this->translator->getLocale(),
            'page_title' => 'Reportes del Sistema',
            'header_title' => 'Reportes y Estadísticas',
            'login_stats' => $loginStats,
            'top_ips' => $topIPs,
        ];

        return $this->render('admin/reports', $data, '/admin/reports');
    }

    /**
     * Seguridad del sistema
     */
    public function security(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->isAuthenticated()) {
            return $this->redirect('/login');
        }

        // Intentos de login fallidos recientes
        $failedAttempts = $this->getFailedAttempts(20);

        // Cuentas bloqueadas
        $lockedAccounts = $this->getLockedAccounts();

        $data = [
            'locale' => $this->translator->getLocale(),
            'page_title' => 'Seguridad del Sistema',
            'header_title' => 'Seguridad',
            'failed_attempts' => $failedAttempts,
            'locked_accounts' => $lockedAccounts,
        ];

        return $this->render('admin/security', $data, '/admin/security');
    }

    /**
     * Obtener estadísticas del sistema
     */
    private function getSystemStats(): array
    {
        $totalUsers = $this->userManager->countUsers();
        $activeUsers = $this->userManager->countUsers(['status' => 'active']);

        // Login attempts hoy
        $todayStart = strtotime('today');
        $sqlToday = "SELECT COUNT(*) as count FROM {$this->db->table('login_attempts')}
                     WHERE attempted_at >= :today";
        $resultToday = $this->db->getConnection()->fetchOne($sqlToday, [':today' => $todayStart]);

        // Successful logins hoy
        $sqlSuccess = "SELECT COUNT(*) as count FROM {$this->db->table('login_attempts')}
                       WHERE success = 1 AND attempted_at >= :today";
        $resultSuccess = $this->db->getConnection()->fetchOne($sqlSuccess, [':today' => $todayStart]);

        return [
            'total_users' => $totalUsers,
            'active_users' => $activeUsers,
            'login_attempts_today' => (int)($resultToday['count'] ?? 0),
            'successful_logins_today' => (int)($resultSuccess['count'] ?? 0),
        ];
    }

    /**
     * Obtener actividad reciente
     */
    private function getRecentActivity(int $limit = 10): array
    {
        try {
            $sql = "SELECT * FROM {$this->db->table('login_attempts')}
                    ORDER BY attempted_at DESC LIMIT :limit";
            $stmt = $this->db->getConnection()->getConnection()->prepare($sql);
            $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
            $stmt->execute();
            $attempts = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            return array_map(function($attempt) {
                return [
                    'username' => $attempt['username'],
                    'ip_address' => $attempt['ip_address'],
                    'success' => (bool)$attempt['success'],
                    'success_label' => $attempt['success'] ? 'Exitoso' : 'Fallido',
                    'attempted_at' => date('Y-m-d H:i:s', $attempt['attempted_at']),
                ];
            }, $attempts);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Obtener usuarios recientes
     */
    private function getRecentUsers(int $limit = 5): array
    {
        $users = $this->userManager->getUsers($limit, 0, ['deleted' => false]);

        return array_map(function($user) {
            return [
                'username' => $user['username'],
                'email' => $user['email'],
                'full_name' => trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')),
                'status' => $user['status'] ?? 'active',
                'created_at' => date('Y-m-d H:i', $user['created_at'] ?? time()),
            ];
        }, $users);
    }

    /**
     * Obtener configuración del sistema
     */
    private function getSystemConfig(): array
    {
        // Aquí cargarías la configuración desde la base de datos o .env
        return [
            'app_name' => 'NexoSupport',
            'app_version' => '1.0.0',
            'php_version' => PHP_VERSION,
            'db_driver' => 'MySQL',
            'timezone' => date_default_timezone_get(),
        ];
    }

    /**
     * Obtener estadísticas de login por día
     */
    private function getLoginStatsByDay(int $days = 7): array
    {
        try {
            $stats = [];
            for ($i = $days - 1; $i >= 0; $i--) {
                $dayStart = strtotime("-{$i} days", strtotime('today'));
                $dayEnd = $dayStart + 86400;

                $sql = "SELECT
                        COUNT(*) as total,
                        SUM(CASE WHEN success = 1 THEN 1 ELSE 0 END) as successful
                        FROM {$this->db->table('login_attempts')}
                        WHERE attempted_at >= :start AND attempted_at < :end";

                $result = $this->db->getConnection()->fetchOne($sql, [
                    ':start' => $dayStart,
                    ':end' => $dayEnd
                ]);

                $stats[] = [
                    'date' => date('Y-m-d', $dayStart),
                    'total' => (int)($result['total'] ?? 0),
                    'successful' => (int)($result['successful'] ?? 0),
                ];
            }

            return $stats;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Obtener top IPs por intentos
     */
    private function getTopIPsByAttempts(int $limit = 10): array
    {
        try {
            $sql = "SELECT ip_address, COUNT(*) as attempts
                    FROM {$this->db->table('login_attempts')}
                    GROUP BY ip_address
                    ORDER BY attempts DESC
                    LIMIT :limit";

            $stmt = $this->db->getConnection()->getConnection()->prepare($sql);
            $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Obtener intentos fallidos recientes
     */
    private function getFailedAttempts(int $limit = 20): array
    {
        try {
            $sql = "SELECT * FROM {$this->db->table('login_attempts')}
                    WHERE success = 0
                    ORDER BY attempted_at DESC
                    LIMIT :limit";

            $stmt = $this->db->getConnection()->getConnection()->prepare($sql);
            $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
            $stmt->execute();
            $attempts = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            return array_map(function($attempt) {
                return [
                    'username' => $attempt['username'],
                    'ip_address' => $attempt['ip_address'],
                    'attempted_at' => date('Y-m-d H:i:s', $attempt['attempted_at']),
                ];
            }, $attempts);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Obtener cuentas bloqueadas
     */
    private function getLockedAccounts(): array
    {
        try {
            $now = time();
            $sql = "SELECT username, email, first_name, last_name, locked_until
                    FROM {$this->db->table('users')}
                    WHERE locked_until > :now AND deleted_at IS NULL
                    ORDER BY locked_until DESC";

            $result = $this->db->getConnection()->fetchAll($sql, [':now' => $now]);

            return array_map(function($user) use ($now) {
                $remainingTime = $user['locked_until'] - $now;
                return [
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'full_name' => trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')),
                    'locked_until' => date('Y-m-d H:i:s', $user['locked_until']),
                    'remaining_minutes' => ceil($remainingTime / 60),
                ];
            }, $result);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Obtener etiqueta de status
     */
    private function getStatusLabel(string $status): string
    {
        return match(strtolower($status)) {
            'active' => __('users.status_active'),
            'inactive' => __('users.status_inactive'),
            'suspended' => __('users.status_suspended'),
            'pending' => __('users.status_pending'),
            default => ucfirst($status),
        };
    }
}
