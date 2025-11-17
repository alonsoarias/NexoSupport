<?php
/**
 * Log Controller - Handles audit log requests
 *
 * @package    ISER\Report\Log
 * @copyright  2025 ISER
 * @license    Proprietary
 */

namespace ISER\Report\Log;

use ISER\Core\Database\Database;
use ISER\Core\Config\Config;
use ISER\Core\I18n\Translator;
use ISER\Core\View\ViewRenderer;

/**
 * Controller for audit log report
 */
class LogController
{
    /**
     * @var LogRepository Repository instance
     */
    private LogRepository $repository;

    /**
     * @var Config Configuration instance
     */
    private Config $config;

    /**
     * @var Translator Translation instance
     */
    private Translator $translator;

    /**
     * @var ViewRenderer View renderer instance
     */
    private ViewRenderer $renderer;

    /**
     * Constructor.
     *
     * @param Database $db Database instance
     * @param Config $config Configuration instance
     * @param Translator $translator Translation instance
     * @param ViewRenderer $renderer View renderer instance
     */
    public function __construct(
        Database $db,
        Config $config,
        Translator $translator,
        ViewRenderer $renderer
    ) {
        $this->repository = new LogRepository($db);
        $this->config = $config;
        $this->translator = $translator;
        $this->renderer = $renderer;
    }

    /**
     * Display audit log index page.
     *
     * @param array $params Request parameters
     * @return string Rendered HTML
     */
    public function index(array $params = []): string
    {
        // Extract parameters with defaults
        $page = (int)($params['page'] ?? 0);
        $perpage = (int)($params['perpage'] ?? 50);
        $userid = (int)($params['userid'] ?? 0);
        $action = $params['action'] ?? '';
        $datefrom = (int)($params['datefrom'] ?? 0);
        $dateto = (int)($params['dateto'] ?? 0);

        // Build filters
        $filters = [];
        if ($userid > 0) {
            $filters['user_id'] = $userid;
        }
        if (!empty($action)) {
            $filters['action'] = $action;
        }
        if ($datefrom > 0) {
            $filters['date_from'] = $datefrom;
        }
        if ($dateto > 0) {
            $filters['date_to'] = $dateto;
        }

        // Get data
        $entries = $this->repository->get_entries($filters, $page, $perpage);
        $totalcount = $this->repository->count_entries($filters);

        // Format entries for display
        $formattedEntries = [];
        foreach ($entries as $entry) {
            $formattedEntries[] = [
                'id' => $entry->id,
                'username' => $entry->username ?? $this->translator->get_string('unknown', 'report_log'),
                'action' => htmlspecialchars($entry->action),
                'ip_address' => $entry->ip_address ?? 'N/A',
                'details' => !empty($entry->details) ? htmlspecialchars(substr($entry->details, 0, 100)) : '',
                'created_at' => date('Y-m-d H:i:s', $entry->created_at),
            ];
        }

        // Calculate pagination
        $totalpages = ceil($totalcount / $perpage);

        // Build pagination URLs with current filters
        $paginationParams = [];
        if ($userid > 0) {
            $paginationParams[] = 'userid=' . $userid;
        }
        if (!empty($action)) {
            $paginationParams[] = 'action=' . urlencode($action);
        }
        if ($datefrom > 0) {
            $paginationParams[] = 'datefrom=' . date('Y-m-d', $datefrom);
        }
        if ($dateto > 0) {
            $paginationParams[] = 'dateto=' . date('Y-m-d', $dateto);
        }
        $baseParams = !empty($paginationParams) ? '&' . implode('&', $paginationParams) : '';

        $pagination = [
            'current' => $page,
            'current_display' => $page + 1, // Display as 1-indexed
            'total' => $totalpages,
            'perpage' => $perpage,
            'has_prev' => $page > 0,
            'has_next' => $page < ($totalpages - 1),
            'prev_page' => $page - 1,
            'next_page' => $page + 1,
            'prev_url' => '?page=' . ($page - 1) . $baseParams,
            'next_url' => '?page=' . ($page + 1) . $baseParams,
        ];

        // Prepare template data
        $data = [
            'title' => $this->translator->get_string('pluginname', 'report_log'),
            'heading' => $this->translator->get_string('auditlogs', 'report_log'),
            'filters' => [
                'userid' => $userid,
                'action' => $action,
                'datefrom' => $datefrom ? date('Y-m-d', $datefrom) : '',
                'dateto' => $dateto ? date('Y-m-d', $dateto) : '',
            ],
            'entries' => $formattedEntries,
            'has_entries' => !empty($formattedEntries),
            'pagination' => $pagination,
            'export_url' => $this->build_export_url($filters),
            'strings' => $this->get_strings(),
        ];

        // Render template
        return $this->renderer->render('report_log/index', $data);
    }

    /**
     * Export audit logs to CSV.
     *
     * @param array $params Request parameters
     * @return void
     */
    public function export(array $params = []): void
    {
        // Build filters (same as index)
        $filters = [];
        if (!empty($params['userid'])) {
            $filters['user_id'] = (int)$params['userid'];
        }
        if (!empty($params['action'])) {
            $filters['action'] = $params['action'];
        }
        if (!empty($params['datefrom'])) {
            $filters['date_from'] = (int)$params['datefrom'];
        }
        if (!empty($params['dateto'])) {
            $filters['date_to'] = (int)$params['dateto'];
        }

        // Get all entries
        $entries = $this->repository->export_entries($filters);

        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=audit_logs_' . date('Y-m-d') . '.csv');

        // Output CSV
        $output = fopen('php://output', 'w');

        // Header row
        fputcsv($output, ['ID', 'Username', 'Action', 'IP Address', 'Details', 'Date']);

        // Data rows
        foreach ($entries as $entry) {
            fputcsv($output, [
                $entry->id,
                $entry->username ?? 'Unknown',
                $entry->action,
                $entry->ip_address ?? 'N/A',
                $entry->details ?? '',
                date('Y-m-d H:i:s', $entry->created_at),
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * Build export URL with filters.
     *
     * @param array $filters Filters
     * @return string Export URL
     */
    private function build_export_url(array $filters): string
    {
        $wwwroot = $this->config->get('wwwroot', '');
        $params = [];

        if (!empty($filters['user_id'])) {
            $params[] = 'userid=' . $filters['user_id'];
        }
        if (!empty($filters['action'])) {
            $params[] = 'action=' . urlencode($filters['action']);
        }
        if (!empty($filters['date_from'])) {
            $params[] = 'datefrom=' . $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $params[] = 'dateto=' . $filters['date_to'];
        }

        $queryString = !empty($params) ? '?' . implode('&', $params) : '';
        return $wwwroot . '/report/log/export.php' . $queryString;
    }

    /**
     * Get all strings needed for the template.
     *
     * @return array Localized strings
     */
    private function get_strings(): array
    {
        return [
            'user' => $this->translator->get_string('user', 'core'),
            'allusers' => $this->translator->get_string('allusers', 'report_log'),
            'action' => $this->translator->get_string('action', 'report_log'),
            'allactions' => $this->translator->get_string('allactions', 'report_log'),
            'from' => $this->translator->get_string('from', 'core'),
            'to' => $this->translator->get_string('to', 'core'),
            'filter' => $this->translator->get_string('filter', 'report_log'),
            'nologs' => $this->translator->get_string('nologs', 'report_log'),
            'id' => $this->translator->get_string('id', 'report_log'),
            'ipaddress' => $this->translator->get_string('ipaddress', 'report_log'),
            'details' => $this->translator->get_string('details', 'report_log'),
            'date' => $this->translator->get_string('date', 'report_log'),
            'exportcsv' => $this->translator->get_string('exportcsv', 'report_log'),
            'previous' => $this->translator->get_string('previous', 'core'),
            'next' => $this->translator->get_string('next', 'core'),
        ];
    }
}
