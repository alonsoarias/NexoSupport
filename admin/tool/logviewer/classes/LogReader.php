<?php
/**
 * NexoSupport - Log Reader Class
 *
 * Reads and filters system logs from database
 *
 * @package    ISER\Admin\Tool\LogViewer
 * @copyright  2024 ISER
 * @license    Proprietary
 */

namespace ISER\Admin\Tool\LogViewer;

use ISER\Core\Database\Database;

/**
 * Log Reader - Read and filter logs from database
 */
class LogReader
{
    private Database $db;
    private string $logsTable;

    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->logsTable = $db->table('logs');
    }

    /**
     * Get logs with filters
     *
     * @param string $type Log type (all, error, warning, info)
     * @param int $limit Limit
     * @param int $offset Offset
     * @param array $filters Additional filters
     * @return array Logs array
     */
    public function get_logs(string $type = 'all', int $limit = 50, int $offset = 0, array $filters = []): array
    {
        $params = [];
        $where = ['1 = 1'];

        // Type filter
        if ($type !== 'all') {
            $where[] = 'l.level = :type';
            $params[':type'] = $type;
        }

        // Level filter
        if (!empty($filters['level'])) {
            $where[] = 'l.level = :level';
            $params[':level'] = $filters['level'];
        }

        // User filter
        if (!empty($filters['user_id'])) {
            $where[] = 'l.user_id = :user_id';
            $params[':user_id'] = $filters['user_id'];
        }

        // Search filter
        if (!empty($filters['search'])) {
            $where[] = '(l.message LIKE :search OR l.context LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $whereClause = implode(' AND ', $where);

        $sql = "SELECT l.*, u.username
                FROM {$this->logsTable} l
                LEFT JOIN {$this->db->table('users')} u ON l.user_id = u.id
                WHERE {$whereClause}
                ORDER BY l.created_at DESC
                LIMIT :limit OFFSET :offset";

        $params[':limit'] = $limit;
        $params[':offset'] = $offset;

        try {
            return $this->db->getConnection()->fetchAll($sql, $params);
        } catch (\Exception $e) {
            // If logs table doesn't exist, return empty array
            return [];
        }
    }

    /**
     * Count logs with filters
     *
     * @param string $type Log type
     * @param array $filters Additional filters
     * @return int Count
     */
    public function count_logs(string $type = 'all', array $filters = []): int
    {
        $params = [];
        $where = ['1 = 1'];

        // Type filter
        if ($type !== 'all') {
            $where[] = 'level = :type';
            $params[':type'] = $type;
        }

        // Level filter
        if (!empty($filters['level'])) {
            $where[] = 'level = :level';
            $params[':level'] = $filters['level'];
        }

        // User filter
        if (!empty($filters['user_id'])) {
            $where[] = 'user_id = :user_id';
            $params[':user_id'] = $filters['user_id'];
        }

        // Search filter
        if (!empty($filters['search'])) {
            $where[] = '(message LIKE :search OR context LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $whereClause = implode(' AND ', $where);

        $sql = "SELECT COUNT(*) as count
                FROM {$this->logsTable}
                WHERE {$whereClause}";

        try {
            $result = $this->db->getConnection()->fetchOne($sql, $params);
            return (int)($result['count'] ?? 0);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get log statistics
     *
     * @return array Statistics
     */
    public function get_statistics(): array
    {
        $stats = [
            'total' => 0,
            'errors_24h' => 0,
            'warnings_24h' => 0,
            'today' => 0,
        ];

        try {
            // Total logs
            $sql = "SELECT COUNT(*) as count FROM {$this->logsTable}";
            $result = $this->db->getConnection()->fetchOne($sql);
            $stats['total'] = (int)($result['count'] ?? 0);

            // Errors in last 24 hours
            $yesterday = time() - 86400;
            $sql = "SELECT COUNT(*) as count FROM {$this->logsTable}
                    WHERE level = 'error' AND created_at >= :time";
            $result = $this->db->getConnection()->fetchOne($sql, [':time' => $yesterday]);
            $stats['errors_24h'] = (int)($result['count'] ?? 0);

            // Warnings in last 24 hours
            $sql = "SELECT COUNT(*) as count FROM {$this->logsTable}
                    WHERE level = 'warning' AND created_at >= :time";
            $result = $this->db->getConnection()->fetchOne($sql, [':time' => $yesterday]);
            $stats['warnings_24h'] = (int)($result['count'] ?? 0);

            // Today's logs
            $todayStart = strtotime('today');
            $sql = "SELECT COUNT(*) as count FROM {$this->logsTable}
                    WHERE created_at >= :time";
            $result = $this->db->getConnection()->fetchOne($sql, [':time' => $todayStart]);
            $stats['today'] = (int)($result['count'] ?? 0);
        } catch (\Exception $e) {
            // Silently fail if table doesn't exist
        }

        return $stats;
    }

    /**
     * Get logs by level
     *
     * @param string $level Log level
     * @param int $limit Limit
     * @return array Logs
     */
    public function get_logs_by_level(string $level, int $limit = 100): array
    {
        return $this->get_logs($level, $limit, 0);
    }

    /**
     * Get recent error logs
     *
     * @param int $limit Limit
     * @return array Logs
     */
    public function get_recent_errors(int $limit = 50): array
    {
        return $this->get_logs_by_level('error', $limit);
    }

    /**
     * Get logs for a specific user
     *
     * @param int $userId User ID
     * @param int $limit Limit
     * @return array Logs
     */
    public function get_user_logs(int $userId, int $limit = 50): array
    {
        return $this->get_logs('all', $limit, 0, ['user_id' => $userId]);
    }

    /**
     * Delete old logs
     *
     * @param int $daysOld Delete logs older than this many days
     * @return int Number of deleted logs
     */
    public function delete_old_logs(int $daysOld = 30): int
    {
        $timestamp = time() - ($daysOld * 86400);

        $sql = "DELETE FROM {$this->logsTable}
                WHERE created_at < :timestamp";

        try {
            $this->db->getConnection()->execute($sql, [':timestamp' => $timestamp]);
            return $this->db->getConnection()->rowCount();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Export logs to CSV
     *
     * @param array $filters Filters
     * @return string CSV content
     */
    public function export_to_csv(array $filters = []): string
    {
        $logs = $this->get_logs('all', 10000, 0, $filters);

        $csv = "Level,Timestamp,User,Message,Context\n";

        foreach ($logs as $log) {
            $csv .= sprintf(
                "%s,%s,%s,\"%s\",\"%s\"\n",
                $log['level'] ?? '',
                date('Y-m-d H:i:s', $log['created_at'] ?? time()),
                $log['username'] ?? 'System',
                str_replace('"', '""', $log['message'] ?? ''),
                str_replace('"', '""', $log['context'] ?? '')
            );
        }

        return $csv;
    }
}
