<?php

declare(strict_types=1);

namespace ISER\Controllers;

use ISER\Core\View\MustacheRenderer;
use ISER\Core\I18n\Translator;
use ISER\Core\Http\Response;
use ISER\Core\Database\Database;
use ISER\User\UserManager;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Audit Log Controller
 * Manages audit log viewing, filtering, and exporting
 */
class AuditLogController
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
     * Check if user is authenticated and has admin role
     */
    private function isAuthenticated(): bool
    {
        return isset($_SESSION['user_id']) && isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
    }

    /**
     * Check if user has audit.view permission
     */
    private function canViewAudit(): bool
    {
        if (!$this->isAuthenticated()) {
            return false;
        }

        // Check user role - admin only for now
        $userId = $_SESSION['user_id'];
        $user = $this->userManager->getUserById($userId);
        if (!$user) {
            return false;
        }

        // Check if user has admin role
        $roles = $this->userManager->getUserRoles($userId);
        foreach ($roles as $role) {
            if ($role['slug'] === 'admin') {
                return true;
            }
        }

        return false;
    }

    /**
     * Show audit logs (admin only)
     */
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        // Check authentication and permissions
        if (!$this->isAuthenticated()) {
            return Response::redirect('/login');
        }

        if (!$this->canViewAudit()) {
            $_SESSION['error'] = $this->translator->translate('errors.permission_denied');
            return Response::redirect('/dashboard');
        }

        $page = (int)($request->getQueryParams()['page'] ?? 1);
        $limit = 50;
        $offset = ($page - 1) * $limit;

        // Get filters from query params
        $filters = [
            'user_id' => $request->getQueryParams()['user_id'] ?? null,
            'action' => $request->getQueryParams()['action'] ?? null,
            'entity_type' => $request->getQueryParams()['entity_type'] ?? null,
            'date_from' => $request->getQueryParams()['date_from'] ?? null,
            'date_to' => $request->getQueryParams()['date_to'] ?? null,
            'ip' => $request->getQueryParams()['ip'] ?? null,
        ];

        // Build query
        $query = 'SELECT a.*, u.username, u.email FROM audit_log a LEFT JOIN users u ON a.user_id = u.id WHERE 1=1';
        $params = [];

        if ($filters['user_id']) {
            $query .= ' AND a.user_id = ?';
            $params[] = (int)$filters['user_id'];
        }

        if ($filters['action']) {
            $query .= ' AND a.action LIKE ?';
            $params[] = '%' . $filters['action'] . '%';
        }

        if ($filters['entity_type']) {
            $query .= ' AND a.entity_type = ?';
            $params[] = $filters['entity_type'];
        }

        if ($filters['date_from']) {
            $from = strtotime($filters['date_from']);
            $query .= ' AND a.created_at >= ?';
            $params[] = $from;
        }

        if ($filters['date_to']) {
            $to = strtotime($filters['date_to']) + 86400; // End of day
            $query .= ' AND a.created_at <= ?';
            $params[] = $to;
        }

        if ($filters['ip']) {
            $query .= ' AND a.ip_address LIKE ?';
            $params[] = '%' . $filters['ip'] . '%';
        }

        // Get total count
        $countQuery = 'SELECT COUNT(*) as total FROM (' . $query . ') as count_query';
        $countResult = $this->db->query($countQuery, $params);
        $total = $countResult[0]['total'] ?? 0;

        // Get paginated results
        $query .= ' ORDER BY a.created_at DESC LIMIT ? OFFSET ?';
        $params[] = $limit;
        $params[] = $offset;

        $auditLogs = $this->db->query($query, $params);

        // Format audit logs for display
        $formattedLogs = [];
        $actionStats = [];
        $entityStats = [];

        foreach ($auditLogs as $log) {
            $action = $log['action'] ?? 'unknown';
            $entityType = $log['entity_type'] ?? 'unknown';

            // Count statistics
            $actionStats[$action] = ($actionStats[$action] ?? 0) + 1;
            $entityStats[$entityType] = ($entityStats[$entityType] ?? 0) + 1;

            // Format action badge
            $actionClass = match($action) {
                'create' => 'badge-success',
                'update' => 'badge-info',
                'delete' => 'badge-danger',
                'login', 'logout' => 'badge-secondary',
                default => 'badge-secondary'
            };

            $actionLabel = $this->translator->translate('audit.event_types.' . $action) ?? ucfirst($action);
            $entityLabel = $this->translator->translate('audit.entities.' . $entityType) ?? ucfirst($entityType);

            $formattedLogs[] = [
                'id' => $log['id'],
                'timestamp' => $log['created_at'],
                'timestamp_formatted' => date('d/m/Y H:i:s', $log['created_at']),
                'timestamp_short' => date('d/m/Y', $log['created_at']),
                'time' => date('H:i:s', $log['created_at']),
                'user_id' => $log['user_id'],
                'username' => $log['username'] ?? 'System',
                'email' => $log['email'] ?? 'N/A',
                'action' => $action,
                'action_label' => $actionLabel,
                'action_class' => $actionClass,
                'entity_type' => $entityType,
                'entity_label' => $entityLabel,
                'entity_id' => $log['entity_id'],
                'ip_address' => $log['ip_address'] ?? 'N/A',
                'user_agent' => $log['user_agent'] ?? 'N/A',
                'old_values' => $log['old_values'],
                'new_values' => $log['new_values'],
                'has_changes' => !empty($log['old_values']) || !empty($log['new_values']),
            ];
        }

        // Get all users for filter dropdown
        $allUsers = $this->db->query('SELECT id, username, email FROM users WHERE deleted_at IS NULL ORDER BY username');

        // Calculate statistics
        $totalQuery = 'SELECT COUNT(*) as total FROM audit_log';
        $totalResult = $this->db->query($totalQuery);
        $totalActions = $totalResult[0]['total'] ?? 0;

        // Get unique entity types
        $entityTypes = array_unique(array_column($auditLogs, 'entity_type'));
        $actionTypes = array_unique(array_column($auditLogs, 'action'));

        $data = [
            'trans' => $this->translator->getTranslations('audit'),
            'audit_logs' => $formattedLogs,
            'all_users' => $allUsers,
            'total_logs' => $total,
            'total_actions' => $totalActions,
            'page' => $page,
            'limit' => $limit,
            'offset' => $offset,
            'has_logs' => count($formattedLogs) > 0,
            'empty_logs' => count($formattedLogs) === 0,
            'filters' => $filters,
            'filters_active' => !empty(array_filter($filters)),
            'pagination' => [
                'current' => $page,
                'total_pages' => ceil($total / $limit),
                'has_prev' => $page > 1,
                'has_next' => $page < ceil($total / $limit),
                'prev_page' => $page - 1,
                'next_page' => $page + 1,
            ],
            'stats' => [
                'total_events' => $totalActions,
                'events_today' => $this->countEventsToday(),
                'by_action' => $actionStats,
                'by_entity' => $entityStats,
            ],
        ];

        return $this->renderer->render('admin/audit/index', $data);
    }

    /**
     * View single audit entry with changes
     */
    public function view(ServerRequestInterface $request, int $id): ResponseInterface
    {
        // Check authentication and permissions
        if (!$this->isAuthenticated()) {
            return Response::redirect('/login');
        }

        if (!$this->canViewAudit()) {
            $_SESSION['error'] = $this->translator->translate('errors.permission_denied');
            return Response::redirect('/dashboard');
        }

        // Get audit log entry
        $query = 'SELECT a.*, u.username, u.email FROM audit_log a LEFT JOIN users u ON a.user_id = u.id WHERE a.id = ?';
        $result = $this->db->query($query, [$id]);

        if (empty($result)) {
            $_SESSION['error'] = $this->translator->translate('errors.not_found');
            return Response::redirect('/admin/audit');
        }

        $log = $result[0];

        // Parse JSON values
        $oldValues = $log['old_values'] ? json_decode($log['old_values'], true) : [];
        $newValues = $log['new_values'] ? json_decode($log['new_values'], true) : [];

        // Build comparison table
        $changes = [];
        $allKeys = array_unique(array_merge(array_keys($oldValues), array_keys($newValues)));

        foreach ($allKeys as $key) {
            $oldValue = $oldValues[$key] ?? null;
            $newValue = $newValues[$key] ?? null;

            if ($oldValue !== $newValue) {
                $changes[] = [
                    'field' => $key,
                    'old_value' => is_array($oldValue) ? json_encode($oldValue, JSON_PRETTY_PRINT) : $oldValue,
                    'new_value' => is_array($newValue) ? json_encode($newValue, JSON_PRETTY_PRINT) : $newValue,
                    'changed' => true,
                ];
            }
        }

        // Get related entity information if available
        $relatedEntity = null;
        if ($log['entity_type'] && $log['entity_id']) {
            $entityType = strtolower($log['entity_type']);
            $tableName = $this->getTableNameFromEntityType($entityType);

            if ($tableName) {
                $entityResult = $this->db->query("SELECT * FROM {$tableName} WHERE id = ?", [$log['entity_id']]);
                if (!empty($entityResult)) {
                    $relatedEntity = $entityResult[0];
                }
            }
        }

        // Format action
        $action = $log['action'] ?? 'unknown';
        $actionClass = match($action) {
            'create' => 'badge-success',
            'update' => 'badge-info',
            'delete' => 'badge-danger',
            'login', 'logout' => 'badge-secondary',
            default => 'badge-secondary'
        };

        $actionLabel = $this->translator->translate('audit.event_types.' . $action) ?? ucfirst($action);
        $entityLabel = $this->translator->translate('audit.entities.' . $log['entity_type']) ?? ucfirst($log['entity_type']);

        $data = [
            'trans' => $this->translator->getTranslations('audit'),
            'audit_log' => [
                'id' => $log['id'],
                'timestamp' => $log['created_at'],
                'timestamp_formatted' => date('d/m/Y H:i:s', $log['created_at']),
                'user_id' => $log['user_id'],
                'username' => $log['username'] ?? 'System',
                'email' => $log['email'] ?? 'N/A',
                'action' => $action,
                'action_label' => $actionLabel,
                'action_class' => $actionClass,
                'entity_type' => $log['entity_type'],
                'entity_label' => $entityLabel,
                'entity_id' => $log['entity_id'],
                'ip_address' => $log['ip_address'] ?? 'N/A',
                'user_agent' => $log['user_agent'] ?? 'N/A',
                'old_values' => json_encode($oldValues, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                'new_values' => json_encode($newValues, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                'has_changes' => count($changes) > 0,
                'changes' => $changes,
            ],
            'related_entity' => $relatedEntity,
            'related_entity_type' => $log['entity_type'],
        ];

        return $this->renderer->render('admin/audit/view', $data);
    }

    /**
     * Export audit logs to CSV
     */
    public function export(ServerRequestInterface $request): ResponseInterface
    {
        // Check authentication and permissions
        if (!$this->isAuthenticated()) {
            return Response::redirect('/login');
        }

        if (!$this->canViewAudit()) {
            return Response::error(403, 'Permission denied');
        }

        // Get filters from query params
        $filters = [
            'user_id' => $request->getQueryParams()['user_id'] ?? null,
            'action' => $request->getQueryParams()['action'] ?? null,
            'entity_type' => $request->getQueryParams()['entity_type'] ?? null,
            'date_from' => $request->getQueryParams()['date_from'] ?? null,
            'date_to' => $request->getQueryParams()['date_to'] ?? null,
            'ip' => $request->getQueryParams()['ip'] ?? null,
        ];

        // Build query (same as index but without pagination)
        $query = 'SELECT a.*, u.username, u.email FROM audit_log a LEFT JOIN users u ON a.user_id = u.id WHERE 1=1';
        $params = [];

        if ($filters['user_id']) {
            $query .= ' AND a.user_id = ?';
            $params[] = (int)$filters['user_id'];
        }

        if ($filters['action']) {
            $query .= ' AND a.action LIKE ?';
            $params[] = '%' . $filters['action'] . '%';
        }

        if ($filters['entity_type']) {
            $query .= ' AND a.entity_type = ?';
            $params[] = $filters['entity_type'];
        }

        if ($filters['date_from']) {
            $from = strtotime($filters['date_from']);
            $query .= ' AND a.created_at >= ?';
            $params[] = $from;
        }

        if ($filters['date_to']) {
            $to = strtotime($filters['date_to']) + 86400;
            $query .= ' AND a.created_at <= ?';
            $params[] = $to;
        }

        if ($filters['ip']) {
            $query .= ' AND a.ip_address LIKE ?';
            $params[] = '%' . $filters['ip'] . '%';
        }

        $query .= ' ORDER BY a.created_at DESC LIMIT 10000';

        $auditLogs = $this->db->query($query, $params);

        // Create CSV
        $filename = 'audit_log_' . date('Y-m-d_H-i-s') . '.csv';
        $fp = fopen('php://memory', 'w');

        // Write header
        $headers = [
            $this->translator->translate('audit.id'),
            $this->translator->translate('audit.timestamp'),
            $this->translator->translate('audit.user'),
            $this->translator->translate('audit.email'),
            $this->translator->translate('audit.event'),
            $this->translator->translate('audit.entity_type'),
            $this->translator->translate('audit.entity_id'),
            $this->translator->translate('audit.ip_address'),
            $this->translator->translate('audit.user_agent'),
        ];

        fputcsv($fp, $headers);

        // Write data
        foreach ($auditLogs as $log) {
            $action = $this->translator->translate('audit.event_types.' . $log['action']) ?? ucfirst($log['action']);

            $row = [
                $log['id'],
                date('d/m/Y H:i:s', $log['created_at']),
                $log['username'] ?? 'System',
                $log['email'] ?? 'N/A',
                $action,
                $log['entity_type'] ?? 'N/A',
                $log['entity_id'] ?? 'N/A',
                $log['ip_address'] ?? 'N/A',
                $log['user_agent'] ?? 'N/A',
            ];

            fputcsv($fp, $row);
        }

        rewind($fp);
        $csv = stream_get_contents($fp);
        fclose($fp);

        // Return CSV response
        return Response::csvResponse($csv, $filename);
    }

    /**
     * Count events for today
     */
    private function countEventsToday(): int
    {
        $today = strtotime('today');
        $tomorrow = $today + 86400;

        $result = $this->db->query(
            'SELECT COUNT(*) as count FROM audit_log WHERE created_at >= ? AND created_at < ?',
            [$today, $tomorrow]
        );

        return $result[0]['count'] ?? 0;
    }

    /**
     * Map entity type to table name
     */
    private function getTableNameFromEntityType(string $entityType): ?string
    {
        $mapping = [
            'user' => 'users',
            'role' => 'roles',
            'permission' => 'permissions',
            'setting' => 'config',
            'log' => 'logs',
            'session' => 'sessions',
            'plugin' => 'plugins',
            'report' => 'reports',
            'login_attempt' => 'login_attempts',
        ];

        return $mapping[strtolower($entityType)] ?? null;
    }
}
