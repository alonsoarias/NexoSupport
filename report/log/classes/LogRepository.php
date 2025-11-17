<?php
/**
 * Log Repository - Data access layer for audit logs
 *
 * @package    ISER\Report\Log
 * @copyright  2025 ISER
 * @license    Proprietary
 */

namespace ISER\Report\Log;

use ISER\Core\Database\Database;

/**
 * Repository for accessing audit log data
 */
class LogRepository
{
    /**
     * @var Database Database instance
     */
    private Database $db;

    /**
     * Constructor.
     *
     * @param Database $db Database instance
     */
    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Get log entries with filters and pagination.
     *
     * @param array $filters Filters to apply
     * @param int $page Page number (0-indexed)
     * @param int $perpage Items per page
     * @return array Log entries
     */
    public function get_entries(array $filters = [], int $page = 0, int $perpage = 50): array
    {
        $sql = "SELECT l.*, u.username
                FROM logs l
                LEFT JOIN users u ON l.user_id = u.id
                WHERE 1=1";
        $params = [];

        // Apply filters
        if (!empty($filters['user_id'])) {
            $sql .= " AND l.user_id = ?";
            $params[] = $filters['user_id'];
        }

        if (!empty($filters['action'])) {
            $sql .= " AND l.action LIKE ?";
            $params[] = '%' . $filters['action'] . '%';
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND l.created_at >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND l.created_at <= ?";
            $params[] = $filters['date_to'];
        }

        // Order by newest first
        $sql .= " ORDER BY l.created_at DESC";

        // Pagination
        $offset = $page * $perpage;
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $perpage;
        $params[] = $offset;

        return $this->db->get_records_sql($sql, $params);
    }

    /**
     * Count total entries matching filters.
     *
     * @param array $filters Filters to apply
     * @return int Total count
     */
    public function count_entries(array $filters = []): int
    {
        $sql = "SELECT COUNT(*) FROM logs WHERE 1=1";
        $params = [];

        // Apply filters
        if (!empty($filters['user_id'])) {
            $sql .= " AND user_id = ?";
            $params[] = $filters['user_id'];
        }

        if (!empty($filters['action'])) {
            $sql .= " AND action LIKE ?";
            $params[] = '%' . $filters['action'] . '%';
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND created_at >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND created_at <= ?";
            $params[] = $filters['date_to'];
        }

        return $this->db->count_records_sql($sql, $params);
    }

    /**
     * Export log entries to array for CSV/Excel.
     *
     * @param array $filters Filters to apply
     * @return array Log entries formatted for export
     */
    public function export_entries(array $filters = []): array
    {
        $sql = "SELECT l.*, u.username
                FROM logs l
                LEFT JOIN users u ON l.user_id = u.id
                WHERE 1=1";
        $params = [];

        // Apply filters (same as get_entries)
        if (!empty($filters['user_id'])) {
            $sql .= " AND l.user_id = ?";
            $params[] = $filters['user_id'];
        }

        if (!empty($filters['action'])) {
            $sql .= " AND l.action LIKE ?";
            $params[] = '%' . $filters['action'] . '%';
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND l.created_at >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND l.created_at <= ?";
            $params[] = $filters['date_to'];
        }

        $sql .= " ORDER BY l.created_at DESC";

        return $this->db->get_records_sql($sql, $params);
    }
}
