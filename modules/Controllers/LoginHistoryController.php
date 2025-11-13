<?php

declare(strict_types=1);

namespace ISER\Controllers;

use ISER\Core\Controllers\BaseController;
use ISER\Core\Database\Database;
use ISER\User\UserManager;
use ISER\User\LoginHistoryManager;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Login History Controller (REFACTORIZADO con BaseController)
 * Manages user login history views and session termination
 *
 * Extiende BaseController para reducir código duplicado.
 */
class LoginHistoryController extends BaseController
{
    private UserManager $userManager;
    private LoginHistoryManager $loginHistoryManager;

    public function __construct(Database $db)
    {
        parent::__construct($db);
        $this->userManager = new UserManager($db);
        $this->loginHistoryManager = new LoginHistoryManager($db);
    }

    /**
     * Show current user's login history
     */
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        // Check authentication
        if (!$this->isAuthenticated()) {
            return $this->redirect('/login');
        }

        $userId = $_SESSION['user_id'];
        $page = (int)($request->getQueryParams()['page'] ?? 1);
        $limit = 50;
        $offset = ($page - 1) * $limit;

        // Get user data
        $user = $this->userManager->getUserById($userId);
        if (!$user) {
            $_SESSION['error'] = $this->translator->translate('errors.user_not_found');
            return $this->redirect('/dashboard');
        }

        // Get login history
        $history = $this->loginHistoryManager->getHistory($userId, $limit, $offset);
        $totalLogins = $this->loginHistoryManager->countLogins($userId);

        // Get active sessions
        $activeSessions = $this->loginHistoryManager->getActiveSessions($userId);
        $currentSessionId = $_SESSION['session_id'] ?? null;

        // Get statistics
        $stats = $this->loginHistoryManager->getStatistics($userId);

        // Format login history entries
        $formattedHistory = [];
        foreach ($history as $entry) {
            $isActive = $entry['logout_at'] === null;
            $isCurrent = $currentSessionId && $entry['session_id'] === $currentSessionId;

            $formattedHistory[] = [
                'id' => $entry['id'],
                'login_at' => $entry['login_at'],
                'login_at_formatted' => date('d/m/Y H:i:s', $entry['login_at']),
                'login_date_formatted' => date('d/m/Y', $entry['login_at']),
                'login_time_formatted' => date('H:i:s', $entry['login_at']),
                'logout_at' => $entry['logout_at'],
                'logout_at_formatted' => $entry['logout_at'] ? date('d/m/Y H:i:s', $entry['logout_at']) : null,
                'ip_address' => $entry['ip_address'],
                'user_agent' => $entry['user_agent'] ?? 'N/A',
                'session_id' => $entry['session_id'],
                'is_active' => $isActive,
                'is_current' => $isCurrent,
                'session_status' => $isActive ? 'active' : 'ended',
                'session_duration' => $entry['logout_at'] ?
                    $this->formatDuration($entry['logout_at'] - $entry['login_at']) : 'Active',
                'can_terminate' => $isActive && !$isCurrent
            ];
        }

        // Format stats
        $avgDuration = isset($stats['avg_session_duration']) && $stats['avg_session_duration']
            ? $this->formatDuration((int)$stats['avg_session_duration'])
            : 'N/A';

        $totalPages = ceil($totalLogins / $limit);
        $hasPrevious = $page > 1;
        $hasNext = $page < $totalPages;

        $data = [
            'locale' => $this->translator->getLocale(),
            'page_title' => $this->translator->translate('security.login_history_title'),
            'app_name' => 'NexoSupport',
            'user' => $user,
            'success' => $_SESSION['success'] ?? null,
            'error' => $_SESSION['error'] ?? null,
            'login_history' => $formattedHistory,
            'active_sessions_count' => count($activeSessions),
            'total_logins' => $totalLogins,
            'unique_ips' => $stats['unique_ips'] ?? 0,
            'avg_session_duration' => $avgDuration,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'show_pagination' => $totalPages > 1,
            'has_previous' => $hasPrevious,
            'has_next' => $hasNext,
            'previous_page' => $page - 1,
            'next_page' => $page + 1,
            'empty_history' => empty($formattedHistory),
            'trans' => [
                'login_history' => $this->translator->translate('security.login_history'),
                'login_history_title' => $this->translator->translate('security.login_history_title'),
                'login_time' => $this->translator->translate('security.login_time'),
                'ip_address' => $this->translator->translate('security.ip_address'),
                'user_agent' => $this->translator->translate('security.user_agent'),
                'session_status' => $this->translator->translate('security.session_status'),
                'active_session' => $this->translator->translate('security.active_session'),
                'ended_session' => $this->translator->translate('security.ended_session'),
                'current_session' => $this->translator->translate('security.current_session'),
                'terminate_session' => $this->translator->translate('security.terminate_session'),
                'session_duration' => $this->translator->translate('security.session_duration'),
                'total_logins' => $this->translator->translate('security.total_logins'),
                'unique_ips' => $this->translator->translate('security.unique_ips'),
                'avg_session_duration' => $this->translator->translate('security.avg_session_duration'),
                'logout_date' => $this->translator->translate('security.logout_date'),
                'no_logout' => $this->translator->translate('security.no_logout'),
                'no_history' => $this->translator->translate('security.no_login_history'),
            ],
        ];

        unset($_SESSION['success']);
        unset($_SESSION['error']);

        return $this->renderWithLayout('user/login-history', $data);
    }

    /**
     * Admin view of all users' login history
     */
    public function adminIndex(ServerRequestInterface $request): ResponseInterface
    {
        // Check authentication
        if (!$this->isAuthenticated()) {
            return $this->redirect('/login');
        }

        // Check if user is admin
        $userId = $_SESSION['user_id'];
        if (!$this->isAdmin($userId)) {
            $_SESSION['error'] = $this->translator->translate('errors.access_denied');
            return $this->redirect('/dashboard');
        }

        $page = (int)($request->getQueryParams()['page'] ?? 1);
        $filterUserId = $request->getQueryParams()['user_id'] ?? null;
        $filterIp = $request->getQueryParams()['ip'] ?? null;
        $filterStartDate = $request->getQueryParams()['start_date'] ?? null;
        $filterEndDate = $request->getQueryParams()['end_date'] ?? null;

        $limit = 50;
        $offset = ($page - 1) * $limit;

        // Get all recent logins with filters
        $allLogins = $this->getFilteredLogins($filterUserId, $filterIp, $filterStartDate, $filterEndDate, $limit, $offset);
        $totalLogins = $this->countFilteredLogins($filterUserId, $filterIp, $filterStartDate, $filterEndDate);

        // Format login entries
        $formattedHistory = [];
        foreach ($allLogins as $entry) {
            $isActive = $entry['logout_at'] === null;

            $formattedHistory[] = [
                'id' => $entry['id'],
                'user_id' => $entry['user_id'],
                'username' => $entry['username'],
                'email' => $entry['email'],
                'login_at' => $entry['login_at'],
                'login_at_formatted' => date('d/m/Y H:i:s', $entry['login_at']),
                'logout_at' => $entry['logout_at'],
                'logout_at_formatted' => $entry['logout_at'] ? date('d/m/Y H:i:s', $entry['logout_at']) : null,
                'ip_address' => $entry['ip_address'],
                'user_agent' => $entry['user_agent'] ?? 'N/A',
                'session_id' => $entry['session_id'],
                'is_active' => $isActive,
                'session_status' => $isActive ? 'active' : 'ended',
                'session_duration' => $entry['logout_at'] ?
                    $this->formatDuration($entry['logout_at'] - $entry['login_at']) : 'Active',
                'can_terminate' => $isActive
            ];
        }

        // Get statistics
        $totalStats = $this->getGlobalStatistics();

        // Get list of users for filter dropdown
        $users = $this->db->getConnection()->fetchAll(
            "SELECT id, username, email FROM {$this->db->table('users')} WHERE deleted_at IS NULL ORDER BY username LIMIT 100"
        );

        $totalPages = ceil($totalLogins / $limit);
        $hasPrevious = $page > 1;
        $hasNext = $page < $totalPages;

        $data = [
            'locale' => $this->translator->getLocale(),
            'page_title' => $this->translator->translate('security.login_history_title'),
            'app_name' => 'NexoSupport',
            'success' => $_SESSION['success'] ?? null,
            'error' => $_SESSION['error'] ?? null,
            'login_history' => $formattedHistory,
            'users' => $users,
            'filters' => [
                'user_id' => $filterUserId,
                'ip' => $filterIp,
                'start_date' => $filterStartDate,
                'end_date' => $filterEndDate,
            ],
            'total_logins' => $totalLogins,
            'unique_ips' => $totalStats['unique_ips'],
            'avg_session_duration' => $totalStats['avg_session_duration'],
            'current_page' => $page,
            'total_pages' => $totalPages,
            'show_pagination' => $totalPages > 1,
            'has_previous' => $hasPrevious,
            'has_next' => $hasNext,
            'previous_page' => $page - 1,
            'next_page' => $page + 1,
            'empty_history' => empty($formattedHistory),
            'trans' => [
                'admin_login_history' => $this->translator->translate('security.admin_login_history'),
                'login_history_title' => $this->translator->translate('security.login_history_title'),
                'login_time' => $this->translator->translate('security.login_time'),
                'ip_address' => $this->translator->translate('security.ip_address'),
                'user_agent' => $this->translator->translate('security.user_agent'),
                'session_status' => $this->translator->translate('security.session_status'),
                'active_session' => $this->translator->translate('security.active_session'),
                'ended_session' => $this->translator->translate('security.ended_session'),
                'terminate_session' => $this->translator->translate('security.terminate_session'),
                'session_duration' => $this->translator->translate('security.session_duration'),
                'total_logins' => $this->translator->translate('security.total_logins'),
                'unique_ips' => $this->translator->translate('security.unique_ips'),
                'avg_session_duration' => $this->translator->translate('security.avg_session_duration'),
                'logout_date' => $this->translator->translate('security.logout_date'),
                'user' => $this->translator->translate('security.user'),
                'email' => $this->translator->translate('security.email'),
                'filter_results' => $this->translator->translate('security.filter_results'),
                'filter' => $this->translator->translate('security.filter'),
                'clear_filters' => $this->translator->translate('security.clear_filters'),
                'export_csv' => $this->translator->translate('security.export_csv'),
                'no_logout' => $this->translator->translate('security.no_logout'),
                'no_history' => $this->translator->translate('security.no_login_history'),
            ],
        ];

        unset($_SESSION['success']);
        unset($_SESSION['error']);

        return $this->renderWithLayout('admin/login-history', $data);
    }

    /**
     * Terminate a session by login_id
     */
    public function terminate(ServerRequestInterface $request): ResponseInterface
    {
        // Check authentication
        if (!$this->isAuthenticated()) {
            return $this->jsonError('Unauthorized', [], 401);
        }

        $userId = $_SESSION['user_id'];
        $body = $request->getParsedBody();
        $loginId = isset($body['login_id']) ? (int)$body['login_id'] : 0;

        if (!$loginId) {
            return $this->jsonError('Invalid login ID', [], 400);
        }

        // Verify that this login belongs to the user (or user is admin)
        $sql = "SELECT user_id FROM {$this->db->table('login_history')} WHERE id = :id AND logout_at IS NULL";
        $login = $this->db->getConnection()->fetchOne($sql, [':id' => $loginId]);

        if (!$login) {
            return $this->jsonError('Session not found or already ended', [], 404);
        }

        $loginUserId = (int)$login['user_id'];

        // Check permissions: user can only terminate their own sessions, unless admin
        if ($loginUserId !== $userId && !$this->isAdmin($userId)) {
            return $this->jsonError('Unauthorized', [], 403);
        }

        // Prevent current session termination
        $currentSessionId = $_SESSION['session_id'] ?? null;
        if ($currentSessionId) {
            $sql = "SELECT session_id FROM {$this->db->table('login_history')} WHERE id = :id";
            $sessionRecord = $this->db->getConnection()->fetchOne($sql, [':id' => $loginId]);
            if ($sessionRecord && $sessionRecord['session_id'] === $currentSessionId) {
                return $this->jsonError('Cannot terminate current session', [], 400);
            }
        }

        // Terminate the session
        $success = $this->loginHistoryManager->recordLogout($loginId);

        if ($success) {
            $_SESSION['success'] = $this->translator->translate('security.session_terminated');
            return $this->jsonSuccess('Session terminated successfully');
        } else {
            return $this->jsonError('Failed to terminate session', [], 500);
        }
    }

    /**
     * Check if user is admin
     */
    private function isAdmin(int $userId): bool
    {
        return $this->userManager->hasRole($userId, 'admin')
            || $this->userManager->hasRole($userId, 'administrator');
    }

    /**
     * Format duration in seconds to readable string
     */
    private function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds . 's';
        } elseif ($seconds < 3600) {
            $minutes = floor($seconds / 60);
            return $minutes . 'm';
        } elseif ($seconds < 86400) {
            $hours = floor($seconds / 3600);
            return $hours . 'h';
        } else {
            $days = floor($seconds / 86400);
            return $days . 'd';
        }
    }

    /**
     * Get filtered login records
     */
    private function getFilteredLogins(
        ?string $userId = null,
        ?string $ipAddress = null,
        ?string $startDate = null,
        ?string $endDate = null,
        int $limit = 50,
        int $offset = 0
    ): array {
        $sql = "SELECT lh.*, u.username, u.email
                FROM {$this->db->table('login_history')} lh
                INNER JOIN {$this->db->table('users')} u ON lh.user_id = u.id
                WHERE 1=1";

        $params = [];

        if ($userId) {
            $sql .= " AND lh.user_id = :user_id";
            $params[':user_id'] = (int)$userId;
        }

        if ($ipAddress) {
            $sql .= " AND lh.ip_address LIKE :ip_address";
            $params[':ip_address'] = '%' . $ipAddress . '%';
        }

        if ($startDate) {
            $startTime = strtotime($startDate);
            $sql .= " AND lh.login_at >= :start_date";
            $params[':start_date'] = $startTime;
        }

        if ($endDate) {
            $endTime = strtotime($endDate . ' 23:59:59');
            $sql .= " AND lh.login_at <= :end_date";
            $params[':end_date'] = $endTime;
        }

        $sql .= " ORDER BY lh.login_at DESC LIMIT :limit OFFSET :offset";
        $params[':limit'] = $limit;
        $params[':offset'] = $offset;

        return $this->db->getConnection()->fetchAll($sql, $params);
    }

    /**
     * Count filtered login records
     */
    private function countFilteredLogins(
        ?string $userId = null,
        ?string $ipAddress = null,
        ?string $startDate = null,
        ?string $endDate = null
    ): int {
        $sql = "SELECT COUNT(*) as count FROM {$this->db->table('login_history')} lh WHERE 1=1";

        $params = [];

        if ($userId) {
            $sql .= " AND lh.user_id = :user_id";
            $params[':user_id'] = (int)$userId;
        }

        if ($ipAddress) {
            $sql .= " AND lh.ip_address LIKE :ip_address";
            $params[':ip_address'] = '%' . $ipAddress . '%';
        }

        if ($startDate) {
            $startTime = strtotime($startDate);
            $sql .= " AND lh.login_at >= :start_date";
            $params[':start_date'] = $startTime;
        }

        if ($endDate) {
            $endTime = strtotime($endDate . ' 23:59:59');
            $sql .= " AND lh.login_at <= :end_date";
            $params[':end_date'] = $endTime;
        }

        $result = $this->db->getConnection()->fetchOne($sql, $params);
        return (int)($result['count'] ?? 0);
    }

    /**
     * Get global statistics across all users
     */
    private function getGlobalStatistics(): array
    {
        $sql = "SELECT
                    COUNT(DISTINCT ip_address) as unique_ips,
                    AVG(CASE WHEN logout_at IS NOT NULL
                        THEN logout_at - login_at
                        ELSE NULL END) as avg_session_duration
                FROM {$this->db->table('login_history')}";

        $result = $this->db->getConnection()->fetchOne($sql);

        $avgDuration = isset($result['avg_session_duration']) && $result['avg_session_duration']
            ? $this->formatDuration((int)$result['avg_session_duration'])
            : 'N/A';

        return [
            'unique_ips' => (int)($result['unique_ips'] ?? 0),
            'avg_session_duration' => $avgDuration
        ];
    }
}
