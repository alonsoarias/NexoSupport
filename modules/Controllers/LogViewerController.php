<?php

declare(strict_types=1);

namespace ISER\Controllers;

use ISER\Controllers\Traits\NavigationTrait;
use ISER\Core\Database\Database;
use ISER\Core\Http\Response;
use ISER\Core\View\MustacheRenderer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * LogViewerController - System Logs Viewer
 *
 * Handles viewing, filtering, clearing and downloading system logs.
 * Admin only access.
 *
 * @package ISER\Controllers
 * @author ISER Desarrollo
 * @license Propietario
 */
class LogViewerController
{
    use NavigationTrait;

    private Database $database;
    private MustacheRenderer $renderer;

    public function __construct(Database $database)
    {
        $this->database = $database;
        $this->renderer = MustacheRenderer::getInstance();
    }

    /**
     * Render with layout
     */
    private function renderWithLayout(string $view, array $data = [], string $layout = 'layouts/app'): ResponseInterface
    {
        $html = $this->renderer->render($view, $data, $layout);
        return Response::html($html);
    }

    /**
     * Show logs page with filters and pagination
     */
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        // Check admin permission
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
            return Response::redirect('/login');
        }

        $queryParams = $request->getQueryParams();

        // Pagination
        $page = isset($queryParams['page']) ? max(1, (int)$queryParams['page']) : 1;
        $perPage = 50;
        $offset = ($page - 1) * $perPage;

        // Build filters
        $where = [];
        $params = [];

        // Filter by level
        if (!empty($queryParams['level']) && in_array($queryParams['level'], ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'])) {
            $where[] = 'level = :level';
            $params['level'] = $queryParams['level'];
        }

        // Filter by date range
        if (!empty($queryParams['date_from'])) {
            $dateFrom = strtotime($queryParams['date_from']);
            if ($dateFrom !== false) {
                $where[] = 'created_at >= :date_from';
                $params['date_from'] = $dateFrom;
            }
        }

        if (!empty($queryParams['date_to'])) {
            $dateTo = strtotime($queryParams['date_to'] . ' 23:59:59');
            if ($dateTo !== false) {
                $where[] = 'created_at <= :date_to';
                $params['date_to'] = $dateTo;
            }
        }

        // Filter by search text
        if (!empty($queryParams['search'])) {
            $search = '%' . $queryParams['search'] . '%';
            $where[] = '(message LIKE :search OR channel LIKE :search)';
            $params['search'] = $search;
        }

        // Filter by user
        if (!empty($queryParams['user_id'])) {
            $where[] = 'user_id = :user_id';
            $params['user_id'] = (int)$queryParams['user_id'];
        }

        // Filter by IP
        if (!empty($queryParams['ip_address'])) {
            $where[] = 'ip_address = :ip_address';
            $params['ip_address'] = $queryParams['ip_address'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // Get total logs
        $totalResult = $this->database->query(
            "SELECT COUNT(*) as count FROM iser_logs {$whereClause}",
            $params
        );
        $totalLogs = (int)($totalResult[0]['count'] ?? 0);
        $totalPages = (int)ceil($totalLogs / $perPage);

        // Get logs
        $logs = $this->database->query(
            "SELECT l.*, u.username, u.first_name, u.last_name
             FROM iser_logs l
             LEFT JOIN iser_users u ON l.user_id = u.id
             {$whereClause}
             ORDER BY l.created_at DESC
             LIMIT :limit OFFSET :offset",
            array_merge($params, ['limit' => $perPage, 'offset' => $offset])
        );

        // Format logs for display
        foreach ($logs as &$log) {
            $log['created_at_formatted'] = date('Y-m-d H:i:s', (int)$log['created_at']);
            $log['user_full_name'] = trim(($log['first_name'] ?? '') . ' ' . ($log['last_name'] ?? '')) ?: ($log['username'] ?? 'System');
            if ($log['context']) {
                $log['context'] = is_string($log['context']) ? json_decode($log['context'], true) : $log['context'];
            }
        }

        // Get statistics
        $stats = $this->getLogStatistics($params, $whereClause);

        // Current filters
        $currentFilters = [
            'level' => $queryParams['level'] ?? '',
            'date_from' => $queryParams['date_from'] ?? '',
            'date_to' => $queryParams['date_to'] ?? '',
            'search' => $queryParams['search'] ?? '',
            'user_id' => $queryParams['user_id'] ?? '',
            'ip_address' => $queryParams['ip_address'] ?? '',
        ];

        // Log levels for dropdown
        $logLevels = ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'];

        $data = [
            'logs' => $logs,
            'stats' => $stats,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_logs' => $totalLogs,
                'per_page' => $perPage,
                'has_prev' => $page > 1,
                'has_next' => $page < $totalPages,
                'prev_page' => $page - 1,
                'next_page' => $page + 1,
            ],
            'current_filters' => $currentFilters,
            'log_levels' => $logLevels,
            'current_user' => [
                'full_name' => trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '')) ?: ($_SESSION['username'] ?? 'User'),
            ],
        ];

        return $this->renderWithLayout('admin/logs/index', $data);
    }

    /**
     * View single log entry details
     */
    public function view(ServerRequestInterface $request, int $id): ResponseInterface
    {
        // Check admin permission
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
            return Response::redirect('/login');
        }

        // Get log entry
        $logs = $this->database->query(
            "SELECT l.*, u.username, u.first_name, u.last_name
             FROM iser_logs l
             LEFT JOIN iser_users u ON l.user_id = u.id
             WHERE l.id = :id",
            ['id' => $id]
        );

        if (empty($logs)) {
            return Response::redirect('/admin/logs');
        }

        $log = $logs[0];
        $log['created_at_formatted'] = date('Y-m-d H:i:s', (int)$log['created_at']);
        $log['user_full_name'] = trim(($log['first_name'] ?? '') . ' ' . ($log['last_name'] ?? '')) ?: ($log['username'] ?? 'System');

        if ($log['context']) {
            $log['context'] = is_string($log['context']) ? json_decode($log['context'], true) : $log['context'];
            $log['context_json'] = json_encode($log['context'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }

        // Get related logs (same user or same IP)
        $related = [];
        if (!empty($log['user_id']) || !empty($log['ip_address'])) {
            $relatedWhere = [];
            $relatedParams = ['current_id' => $id];

            if (!empty($log['user_id'])) {
                $relatedWhere[] = 'l.user_id = :user_id';
                $relatedParams['user_id'] = $log['user_id'];
            }

            if (!empty($log['ip_address'])) {
                $relatedWhere[] = 'l.ip_address = :ip_address';
                $relatedParams['ip_address'] = $log['ip_address'];
            }

            $relatedWhereClause = implode(' OR ', $relatedWhere);

            $related = $this->database->query(
                "SELECT l.id, l.level, l.message, l.created_at
                 FROM iser_logs l
                 WHERE ({$relatedWhereClause}) AND l.id != :current_id
                 ORDER BY l.created_at DESC
                 LIMIT 10",
                $relatedParams
            );

            foreach ($related as &$rel) {
                $rel['created_at_formatted'] = date('Y-m-d H:i:s', (int)$rel['created_at']);
            }
        }

        $data = [
            'log' => $log,
            'related_logs' => $related,
            'current_user' => [
                'full_name' => trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '')) ?: ($_SESSION['username'] ?? 'User'),
            ],
        ];

        return $this->renderWithLayout('admin/logs/view', $data);
    }

    /**
     * Clear old logs (admin action with confirmation)
     */
    public function clear(ServerRequestInterface $request): ResponseInterface
    {
        // Check admin permission
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
            return Response::redirect('/login');
        }

        $body = $request->getParsedBody();

        // Verify confirmation
        if (empty($body['confirm']) || $body['confirm'] !== 'yes') {
            return Response::redirect('/admin/logs?error=confirm_required');
        }

        // Determine retention days from settings (default 90)
        $retentionDays = 90;
        $configResult = $this->database->query(
            "SELECT config_value FROM iser_config WHERE config_key = 'report.retention_days'",
            []
        );
        if (!empty($configResult)) {
            $retentionDays = (int)$configResult[0]['config_value'];
        }

        $cutoffTime = time() - ($retentionDays * 86400);

        // Delete old logs
        $deleted = $this->database->execute(
            "DELETE FROM iser_logs WHERE created_at < :cutoff",
            ['cutoff' => $cutoffTime]
        );

        // Log the action
        $this->database->execute(
            "INSERT INTO iser_audit_log (user_id, action, entity_type, ip_address, created_at)
             VALUES (:user_id, :action, :type, :ip, :time)",
            [
                'user_id' => $_SESSION['user_id'],
                'action' => 'clear_logs',
                'type' => 'logs',
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                'time' => time(),
            ]
        );

        return Response::redirect('/admin/logs?success=logs_cleared&deleted=' . $deleted);
    }

    /**
     * Download logs as CSV or JSON file
     */
    public function download(ServerRequestInterface $request): ResponseInterface
    {
        // Check admin permission
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
            return Response::redirect('/login');
        }

        $queryParams = $request->getQueryParams();
        $format = $queryParams['format'] ?? 'csv';

        // Validate format
        if (!in_array($format, ['csv', 'json'])) {
            $format = 'csv';
        }

        // Build same filters as index
        $where = [];
        $params = [];

        if (!empty($queryParams['level'])) {
            $where[] = 'level = :level';
            $params['level'] = $queryParams['level'];
        }

        if (!empty($queryParams['date_from'])) {
            $dateFrom = strtotime($queryParams['date_from']);
            if ($dateFrom !== false) {
                $where[] = 'created_at >= :date_from';
                $params['date_from'] = $dateFrom;
            }
        }

        if (!empty($queryParams['date_to'])) {
            $dateTo = strtotime($queryParams['date_to'] . ' 23:59:59');
            if ($dateTo !== false) {
                $where[] = 'created_at <= :date_to';
                $params['date_to'] = $dateTo;
            }
        }

        if (!empty($queryParams['search'])) {
            $search = '%' . $queryParams['search'] . '%';
            $where[] = '(message LIKE :search OR channel LIKE :search)';
            $params['search'] = $search;
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // Get logs
        $logs = $this->database->query(
            "SELECT l.id, l.level, l.channel, l.message, l.context, l.user_id, l.ip_address, l.created_at,
                    u.username, u.first_name, u.last_name
             FROM iser_logs l
             LEFT JOIN iser_users u ON l.user_id = u.id
             {$whereClause}
             ORDER BY l.created_at DESC
             LIMIT 10000",
            $params
        );

        $filename = 'logs_' . date('Y-m-d_Hi') . '.' . $format;

        if ($format === 'csv') {
            return $this->downloadCSV($logs, $filename);
        } else {
            return $this->downloadJSON($logs, $filename);
        }
    }

    /**
     * Download as CSV
     */
    private function downloadCSV(array $logs, string $filename): ResponseInterface
    {
        $csv = "ID,Level,Channel,Message,Context,User,IP Address,Timestamp\n";

        foreach ($logs as $log) {
            $user = !empty($log['username']) ? $log['username'] : 'System';
            $context = !empty($log['context']) ? str_replace('"', '""', (string)$log['context']) : '';
            $message = str_replace('"', '""', (string)$log['message']);

            $csv .= sprintf(
                '"%d","%s","%s","%s","%s","%s","%s","%s"' . "\n",
                $log['id'],
                $log['level'],
                $log['channel'],
                $message,
                $context,
                $user,
                $log['ip_address'] ?? '',
                date('Y-m-d H:i:s', (int)$log['created_at'])
            );
        }

        // Create temporary file
        $tempFile = sys_get_temp_dir() . '/' . $filename;
        file_put_contents($tempFile, $csv);

        // Create response with proper headers
        $response = new \ISER\Core\Http\Response(200, [], $csv);
        return $response
            ->withHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->withHeader('Content-Length', (string)strlen($csv));
    }

    /**
     * Download as JSON
     */
    private function downloadJSON(array $logs, string $filename): ResponseInterface
    {
        $data = [];

        foreach ($logs as $log) {
            $data[] = [
                'id' => $log['id'],
                'level' => $log['level'],
                'channel' => $log['channel'],
                'message' => $log['message'],
                'context' => !empty($log['context']) ? (is_string($log['context']) ? json_decode($log['context'], true) : $log['context']) : null,
                'user' => [
                    'id' => $log['user_id'],
                    'username' => $log['username'] ?? 'System',
                    'name' => trim(($log['first_name'] ?? '') . ' ' . ($log['last_name'] ?? '')) ?: null,
                ],
                'ip_address' => $log['ip_address'],
                'timestamp' => (int)$log['created_at'],
                'timestamp_formatted' => date('Y-m-d H:i:s', (int)$log['created_at']),
            ];
        }

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        // Create response with proper headers
        $response = new \ISER\Core\Http\Response(200, [], $json);
        return $response
            ->withHeader('Content-Type', 'application/json; charset=UTF-8')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->withHeader('Content-Length', (string)strlen($json));
    }

    /**
     * Get log statistics
     */
    private function getLogStatistics(array $params, string $whereClause): array
    {
        // Total logs
        $totalResult = $this->database->query(
            "SELECT COUNT(*) as count FROM iser_logs {$whereClause}",
            $params
        );
        $total = (int)($totalResult[0]['count'] ?? 0);

        // By level
        $byLevel = $this->database->query(
            "SELECT level, COUNT(*) as count
             FROM iser_logs {$whereClause}
             GROUP BY level
             ORDER BY count DESC",
            $params
        );

        $levelCounts = [];
        foreach ($byLevel as $row) {
            $levelCounts[$row['level']] = (int)$row['count'];
        }

        return [
            'total' => $total,
            'by_level' => $levelCounts,
            'error_count' => $levelCounts['error'] ?? 0,
            'warning_count' => $levelCounts['warning'] ?? 0,
            'info_count' => $levelCounts['info'] ?? 0,
            'debug_count' => $levelCounts['debug'] ?? 0,
        ];
    }
}
