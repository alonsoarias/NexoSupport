<?php
/**
 * NexoSupport - Privacy Manager
 *
 * @package    tool_dataprivacy
 * @copyright  2024 ISER
 * @license    Proprietary
 */

namespace ISER\Tools\DataPrivacy;

defined('NEXOSUPPORT_INTERNAL') || die();

use PDO;

/**
 * Privacy Manager
 *
 * Manages data privacy requests and compliance
 */
class PrivacyManager
{
    /** @var PDO Database connection */
    private $db;

    /**
     * Constructor
     *
     * @param PDO $db Database connection
     */
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Create export request
     *
     * @param int $user_id User ID
     * @param string $format Export format (json, xml, pdf)
     * @return array Result
     */
    public function create_export_request(int $user_id, string $format = 'json'): array
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO dataprivacy_requests
                (user_id, type, status, export_format)
                VALUES (?, 'export', 'pending', ?)
            ");

            $stmt->execute([$user_id, $format]);
            $request_id = $this->db->lastInsertId();

            $this->log_action($user_id, 'export_requested', null, $user_id, "Format: $format");

            return [
                'success' => true,
                'request_id' => $request_id,
                'message' => 'Export request created successfully',
            ];

        } catch (\PDOException $e) {
            error_log("Export request error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Database error'];
        }
    }

    /**
     * Create delete request
     *
     * @param int $user_id User ID
     * @param string $reason Reason for deletion
     * @return array Result
     */
    public function create_delete_request(int $user_id, string $reason = ''): array
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO dataprivacy_requests
                (user_id, type, status, notes)
                VALUES (?, 'delete', 'pending', ?)
            ");

            $stmt->execute([$user_id, $reason]);
            $request_id = $this->db->lastInsertId();

            $this->log_action($user_id, 'delete_requested', null, $user_id, $reason);

            return [
                'success' => true,
                'request_id' => $request_id,
                'message' => 'Delete request created successfully',
            ];

        } catch (\PDOException $e) {
            error_log("Delete request error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Database error'];
        }
    }

    /**
     * Approve request
     *
     * @param int $request_id Request ID
     * @param int $admin_id Admin who approves
     * @return array Result
     */
    public function approve_request(int $request_id, int $admin_id): array
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE dataprivacy_requests
                SET status = 'approved', processed_by = ?, processed_at = NOW()
                WHERE id = ? AND status = 'pending'
            ");

            $stmt->execute([$admin_id, $request_id]);

            if ($stmt->rowCount() > 0) {
                // Get request info
                $request = $this->get_request($request_id);
                $this->log_action($request['user_id'], 'request_approved', null, $admin_id,
                    "Request #$request_id ({$request['type']})");

                return ['success' => true, 'message' => 'Request approved'];
            } else {
                return ['success' => false, 'error' => 'Request not found or already processed'];
            }

        } catch (\PDOException $e) {
            return ['success' => false, 'error' => 'Database error'];
        }
    }

    /**
     * Reject request
     *
     * @param int $request_id Request ID
     * @param int $admin_id Admin who rejects
     * @param string $reason Rejection reason
     * @return array Result
     */
    public function reject_request(int $request_id, int $admin_id, string $reason): array
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE dataprivacy_requests
                SET status = 'rejected', processed_by = ?, processed_at = NOW(), notes = ?
                WHERE id = ? AND status = 'pending'
            ");

            $stmt->execute([$admin_id, $reason, $request_id]);

            if ($stmt->rowCount() > 0) {
                $request = $this->get_request($request_id);
                $this->log_action($request['user_id'], 'request_rejected', null, $admin_id, $reason);

                return ['success' => true, 'message' => 'Request rejected'];
            } else {
                return ['success' => false, 'error' => 'Request not found'];
            }

        } catch (\PDOException $e) {
            return ['success' => false, 'error' => 'Database error'];
        }
    }

    /**
     * Complete request
     *
     * @param int $request_id Request ID
     * @param string $export_file Export filename (for export requests)
     * @return array Result
     */
    public function complete_request(int $request_id, string $export_file = ''): array
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE dataprivacy_requests
                SET status = 'completed', export_file = ?
                WHERE id = ?
            ");

            $stmt->execute([$export_file, $request_id]);

            return ['success' => true];

        } catch (\PDOException $e) {
            return ['success' => false, 'error' => 'Database error'];
        }
    }

    /**
     * Get pending requests
     *
     * @param string $type Optional filter by type
     * @return array Requests
     */
    public function get_pending_requests(string $type = null): array
    {
        try {
            if ($type) {
                $stmt = $this->db->prepare("
                    SELECT * FROM dataprivacy_requests
                    WHERE status = 'pending' AND type = ?
                    ORDER BY requested_at ASC
                ");
                $stmt->execute([$type]);
            } else {
                $stmt = $this->db->query("
                    SELECT * FROM dataprivacy_requests
                    WHERE status = 'pending'
                    ORDER BY requested_at ASC
                ");
            }

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * Get request by ID
     *
     * @param int $request_id Request ID
     * @return array|null Request data
     */
    public function get_request(int $request_id): ?array
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM dataprivacy_requests WHERE id = ?");
            $stmt->execute([$request_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (\PDOException $e) {
            return null;
        }
    }

    /**
     * Get user's data categories
     *
     * @return array Categories
     */
    public function get_data_categories(): array
    {
        return [
            'personal_info' => 'Personal Information',
            'activity_logs' => 'Activity Logs',
            'files' => 'Uploaded Files',
            'settings' => 'Settings and Preferences',
            'authentication' => 'Authentication History',
        ];
    }

    /**
     * Set retention policy
     *
     * @param string $category Category name
     * @param int $days Retention days
     * @param string $description Description
     * @return array Result
     */
    public function set_retention_policy(string $category, int $days, string $description = ''): array
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO dataprivacy_retention (category, retention_days, description)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE retention_days = ?, description = ?
            ");

            $stmt->execute([$category, $days, $description, $days, $description]);

            return ['success' => true];

        } catch (\PDOException $e) {
            return ['success' => false, 'error' => 'Database error'];
        }
    }

    /**
     * Get retention policies
     *
     * @return array Policies
     */
    public function get_retention_policies(): array
    {
        try {
            $stmt = $this->db->query("
                SELECT * FROM dataprivacy_retention
                WHERE enabled = TRUE
                ORDER BY category
            ");

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * Cleanup expired data based on retention policies
     *
     * @return array Cleanup results
     */
    public function cleanup_expired_data(): array
    {
        $results = [];
        $policies = $this->get_retention_policies();

        foreach ($policies as $policy) {
            $category = $policy['category'];
            $days = $policy['retention_days'];

            // This would delete data based on category and retention period
            // Implementation depends on actual data structure
            $results[$category] = 0; // Placeholder
        }

        return $results;
    }

    /**
     * Get compliance report
     *
     * @return array Compliance statistics
     */
    public function get_compliance_report(): array
    {
        try {
            $report = [];

            // Request statistics
            $stmt = $this->db->query("
                SELECT
                    type,
                    status,
                    COUNT(*) as count
                FROM dataprivacy_requests
                GROUP BY type, status
            ");
            $report['requests'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Recent activity
            $stmt = $this->db->query("
                SELECT
                    action,
                    COUNT(*) as count
                FROM dataprivacy_audit
                WHERE timestamp > DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY action
            ");
            $report['recent_activity'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Deleted users
            $stmt = $this->db->query("
                SELECT
                    deletion_type,
                    COUNT(*) as count
                FROM dataprivacy_deleted_users
                WHERE deleted_at > DATE_SUB(NOW(), INTERVAL 90 DAY)
                GROUP BY deletion_type
            ");
            $report['deletions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $report;

        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * Log privacy action
     *
     * @param int $user_id User ID
     * @param string $action Action performed
     * @param string $category Data category
     * @param int $performed_by Who performed the action
     * @param string $details Additional details
     * @return void
     */
    private function log_action(int $user_id, string $action, ?string $category, int $performed_by, string $details): void
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO dataprivacy_audit
                (user_id, action, category, performed_by, details, ip_address)
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $user_id,
                $action,
                $category,
                $performed_by,
                $details,
                $_SERVER['REMOTE_ADDR'] ?? null,
            ]);
        } catch (\PDOException $e) {
            error_log("Failed to log privacy action: " . $e->getMessage());
        }
    }

    /**
     * Get audit log
     *
     * @param array $filters Filters
     * @param int $limit Limit
     * @return array Log entries
     */
    public function get_audit_log(array $filters = [], int $limit = 100): array
    {
        try {
            $where = [];
            $params = [];

            if (isset($filters['user_id'])) {
                $where[] = 'user_id = ?';
                $params[] = $filters['user_id'];
            }

            if (isset($filters['action'])) {
                $where[] = 'action = ?';
                $params[] = $filters['action'];
            }

            $sql = "SELECT * FROM dataprivacy_audit";
            if (!empty($where)) {
                $sql .= " WHERE " . implode(' AND ', $where);
            }
            $sql .= " ORDER BY timestamp DESC LIMIT ?";
            $params[] = $limit;

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (\PDOException $e) {
            return [];
        }
    }
}
