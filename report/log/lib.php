<?php
/**
 * NexoSupport - Log Report Plugin - Library
 *
 * @package    report_log
 * @copyright  2024 ISER
 * @license    Proprietary
 */

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Get plugin name
 *
 * @return string
 */
function report_log_get_name(): string
{
    return get_string('pluginname', 'report_log');
}

/**
 * Get log entries
 *
 * @param array $filters Filters
 * @param int $page Page number
 * @param int $perpage Items per page
 * @return array Log entries
 */
function report_log_get_entries(array $filters = [], int $page = 0, int $perpage = 50): array
{
    global $DB;

    $sql = "SELECT l.*, u.username, u.email
            FROM {audit_logs} l
            LEFT JOIN {users} u ON l.user_id = u.id
            WHERE 1=1";

    $params = [];

    if (!empty($filters['user_id'])) {
        $sql .= " AND l.user_id = :user_id";
        $params['user_id'] = $filters['user_id'];
    }

    if (!empty($filters['action'])) {
        $sql .= " AND l.action = :action";
        $params['action'] = $filters['action'];
    }

    if (!empty($filters['from_date'])) {
        $sql .= " AND l.created_at >= :from_date";
        $params['from_date'] = $filters['from_date'];
    }

    if (!empty($filters['to_date'])) {
        $sql .= " AND l.created_at <= :to_date";
        $params['to_date'] = $filters['to_date'];
    }

    $sql .= " ORDER BY l.created_at DESC";

    return $DB->get_records_sql($sql, $params, $page * $perpage, $perpage);
}

/**
 * Export logs to CSV
 *
 * @param array $filters Filters
 * @return string CSV content
 */
function report_log_export_csv(array $filters = []): string
{
    $entries = report_log_get_entries($filters, 0, 10000);

    $csv = "ID,Usuario,Acción,IP,Fecha\n";

    foreach ($entries as $entry) {
        $csv .= sprintf(
            "%d,%s,%s,%s,%s\n",
            $entry->id,
            $entry->username ?? 'N/A',
            $entry->action,
            $entry->ip_address ?? 'N/A',
            date('Y-m-d H:i:s', $entry->created_at)
        );
    }

    return $csv;
}
