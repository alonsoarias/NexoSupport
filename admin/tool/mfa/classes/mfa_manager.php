<?php
/**
 * NexoSupport - MFA Manager
 *
 * @package    tool_mfa
 * @copyright  2024 ISER
 * @license    Proprietary
 */

namespace ISER\Tools\MFA;

defined('NEXOSUPPORT_INTERNAL') || die();

use ISER\Tools\MFA\Factors\EmailFactor;
use ISER\Tools\MFA\Factors\IPRangeFactor;
use PDO;

/**
 * Multi-Factor Authentication Manager
 *
 * Coordinates MFA factors and user authentication
 */
class MFAManager
{
    /** @var PDO Database connection */
    private $db;

    /** @var EmailFactor Email factor instance */
    private $email_factor;

    /** @var IPRangeFactor IP range factor instance */
    private $iprange_factor;

    /** @var array Available factors */
    private $available_factors = ['email', 'iprange'];

    /**
     * Constructor
     *
     * @param PDO $db Database connection
     */
    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->email_factor = new EmailFactor($db);
        $this->iprange_factor = new IPRangeFactor($db);
    }

    /**
     * Get enabled system-wide factors
     *
     * @return array Enabled factors
     */
    public function get_enabled_factors(): array
    {
        // In a full implementation, this would check system config
        // For now, return available factors from lib.php
        return tool_mfa_get_available_factors();
    }

    /**
     * Get factors enabled for specific user
     *
     * @param int $user_id User ID
     * @return array User's enabled factors
     */
    public function get_user_factors(int $user_id): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT factor, enabled, last_used
                FROM mfa_user_factors
                WHERE user_id = ? AND enabled = TRUE
                ORDER BY factor
            ");

            $stmt->execute([$user_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (\PDOException $e) {
            error_log("Failed to get user factors: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Enable factor for user
     *
     * @param int $user_id User ID
     * @param string $factor Factor name
     * @return array Result
     */
    public function enable_factor(int $user_id, string $factor): array
    {
        if (!in_array($factor, $this->available_factors)) {
            return [
                'success' => false,
                'error' => 'Invalid factor',
            ];
        }

        try {
            $stmt = $this->db->prepare("
                INSERT INTO mfa_user_factors (user_id, factor, enabled)
                VALUES (?, ?, TRUE)
                ON DUPLICATE KEY UPDATE enabled = TRUE
            ");

            $stmt->execute([$user_id, $factor]);

            return [
                'success' => true,
                'message' => ucfirst($factor) . ' factor enabled',
            ];

        } catch (\PDOException $e) {
            return [
                'success' => false,
                'error' => 'Database error',
            ];
        }
    }

    /**
     * Disable factor for user
     *
     * @param int $user_id User ID
     * @param string $factor Factor name
     * @return array Result
     */
    public function disable_factor(int $user_id, string $factor): array
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE mfa_user_factors
                SET enabled = FALSE
                WHERE user_id = ? AND factor = ?
            ");

            $stmt->execute([$user_id, $factor]);

            return [
                'success' => true,
                'message' => ucfirst($factor) . ' factor disabled',
            ];

        } catch (\PDOException $e) {
            return [
                'success' => false,
                'error' => 'Database error',
            ];
        }
    }

    /**
     * Check if user requires MFA
     *
     * @param int $user_id User ID
     * @return bool True if MFA required
     */
    public function is_mfa_required(int $user_id): bool
    {
        $factors = $this->get_user_factors($user_id);
        return !empty($factors);
    }

    /**
     * Verify user with all enabled factors
     *
     * @param int $user_id User ID
     * @param array $verification_data Data for verification (codes, etc.)
     * @return array Verification result
     */
    public function verify_user(int $user_id, array $verification_data): array
    {
        $factors = $this->get_user_factors($user_id);

        if (empty($factors)) {
            return [
                'success' => true,
                'message' => 'No MFA required',
            ];
        }

        $results = [];
        $all_passed = true;

        foreach ($factors as $factor_info) {
            $factor = $factor_info['factor'];

            switch ($factor) {
                case 'email':
                    if (isset($verification_data['email_code'])) {
                        $result = $this->email_factor->verify_code(
                            $user_id,
                            $verification_data['email_code']
                        );
                        $results['email'] = $result;
                        if (!$result['success']) {
                            $all_passed = false;
                        }
                    } else {
                        $all_passed = false;
                        $results['email'] = [
                            'success' => false,
                            'error' => 'Email code not provided',
                        ];
                    }
                    break;

                case 'iprange':
                    $result = $this->iprange_factor->check_access($user_id);
                    $results['iprange'] = $result;
                    if (!$result['allowed']) {
                        $all_passed = false;
                    }
                    break;
            }
        }

        return [
            'success' => $all_passed,
            'results' => $results,
            'message' => $all_passed ? 'MFA verification successful' : 'MFA verification failed',
        ];
    }

    /**
     * Start MFA verification process
     *
     * Sends codes, etc. for user to verify
     *
     * @param int $user_id User ID
     * @param string $email User email
     * @return array Result
     */
    public function start_verification(int $user_id, string $email): array
    {
        $factors = $this->get_user_factors($user_id);
        $sent = [];

        foreach ($factors as $factor_info) {
            $factor = $factor_info['factor'];

            if ($factor === 'email') {
                $result = $this->email_factor->send_code($user_id, $email);
                $sent['email'] = $result;
            }
            // IP range is passive, no need to "start"
        }

        return [
            'success' => true,
            'factors' => array_column($factors, 'factor'),
            'sent' => $sent,
        ];
    }

    /**
     * Get verification statistics
     *
     * @return array Statistics
     */
    public function get_stats(): array
    {
        $stats = [];

        $stats['email'] = $this->email_factor->get_stats();
        $stats['iprange'] = $this->iprange_factor->get_stats();

        // Get overall stats
        try {
            $stmt = $this->db->query("
                SELECT
                    COUNT(DISTINCT user_id) as users_with_mfa,
                    SUM(CASE WHEN factor = 'email' THEN 1 ELSE 0 END) as email_users,
                    SUM(CASE WHEN factor = 'iprange' THEN 1 ELSE 0 END) as iprange_users
                FROM mfa_user_factors
                WHERE enabled = TRUE
            ");

            $stats['overall'] = $stmt->fetch(PDO::FETCH_ASSOC);

            // Get recent activity
            $stmt = $this->db->query("
                SELECT
                    factor,
                    COUNT(*) as total_actions,
                    SUM(CASE WHEN success = TRUE THEN 1 ELSE 0 END) as successful_actions
                FROM mfa_audit_log
                WHERE timestamp > DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY factor
            ");

            $stats['recent_activity'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (\PDOException $e) {
            error_log("Failed to get MFA stats: " . $e->getMessage());
        }

        return $stats;
    }

    /**
     * Get audit log
     *
     * @param int $limit Number of records
     * @param array $filters Optional filters
     * @return array Log entries
     */
    public function get_audit_log(int $limit = 100, array $filters = []): array
    {
        try {
            $where = [];
            $params = [];

            if (isset($filters['user_id'])) {
                $where[] = 'user_id = ?';
                $params[] = $filters['user_id'];
            }

            if (isset($filters['factor'])) {
                $where[] = 'factor = ?';
                $params[] = $filters['factor'];
            }

            if (isset($filters['success'])) {
                $where[] = 'success = ?';
                $params[] = $filters['success'] ? 1 : 0;
            }

            $sql = "SELECT * FROM mfa_audit_log";
            if (!empty($where)) {
                $sql .= " WHERE " . implode(' AND ', $where);
            }
            $sql .= " ORDER BY timestamp DESC LIMIT ?";
            $params[] = $limit;

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (\PDOException $e) {
            error_log("Failed to get audit log: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Require MFA for role
     *
     * @param int $role_id Role ID
     * @param array $factors Factors to require
     * @return array Result
     */
    public function require_mfa_for_role(int $role_id, array $factors): array
    {
        // Get all users with this role
        try {
            $stmt = $this->db->prepare("
                SELECT user_id FROM user_roles WHERE role_id = ?
            ");
            $stmt->execute([$role_id]);
            $users = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $enabled = 0;
            foreach ($users as $user_id) {
                foreach ($factors as $factor) {
                    $result = $this->enable_factor($user_id, $factor);
                    if ($result['success']) {
                        $enabled++;
                    }
                }
            }

            return [
                'success' => true,
                'users_affected' => count($users),
                'factors_enabled' => $enabled,
            ];

        } catch (\PDOException $e) {
            return [
                'success' => false,
                'error' => 'Database error',
            ];
        }
    }

    /**
     * Cleanup old data
     *
     * @return array Cleanup results
     */
    public function cleanup(): array
    {
        $results = [];

        // Cleanup expired email codes
        $results['expired_codes'] = $this->email_factor->cleanup_expired();

        // Cleanup old audit logs (keep 90 days)
        try {
            $stmt = $this->db->prepare("
                DELETE FROM mfa_audit_log
                WHERE timestamp < DATE_SUB(NOW(), INTERVAL 90 DAY)
            ");
            $stmt->execute();
            $results['old_logs'] = $stmt->rowCount();
        } catch (\PDOException $e) {
            $results['old_logs'] = 0;
        }

        // Cleanup old IP logs (keep 30 days)
        try {
            $stmt = $this->db->prepare("
                DELETE FROM mfa_ip_logs
                WHERE timestamp < DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            $stmt->execute();
            $results['old_ip_logs'] = $stmt->rowCount();
        } catch (\PDOException $e) {
            $results['old_ip_logs'] = 0;
        }

        return $results;
    }

    /**
     * Get email factor instance
     *
     * @return EmailFactor
     */
    public function get_email_factor(): EmailFactor
    {
        return $this->email_factor;
    }

    /**
     * Get IP range factor instance
     *
     * @return IPRangeFactor
     */
    public function get_iprange_factor(): IPRangeFactor
    {
        return $this->iprange_factor;
    }
}
