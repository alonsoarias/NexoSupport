<?php
/**
 * NexoSupport - IP Range MFA Factor
 *
 * @package    tool_mfa
 * @copyright  2024 ISER
 * @license    Proprietary
 */

namespace ISER\Tools\MFA\Factors;

defined('NEXOSUPPORT_INTERNAL') || die();

use PDO;

/**
 * IP Range-based MFA Factor
 *
 * Restricts access based on IP address ranges
 */
class IPRangeFactor
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
     * Check if user's IP is allowed
     *
     * @param int $user_id User ID
     * @return array Result with allowed status
     */
    public function check_access(int $user_id): array
    {
        $ip = $this->get_user_ip();

        // Get all enabled IP ranges
        $whitelists = $this->get_ranges('whitelist');
        $blacklists = $this->get_ranges('blacklist');

        $allowed = true;
        $reason = '';

        // Check blacklist first (higher priority)
        foreach ($blacklists as $range) {
            if ($this->is_ip_in_range($ip, $range['range_cidr'])) {
                $allowed = false;
                $reason = "IP blocked by blacklist rule: {$range['description']}";
                break;
            }
        }

        // If there are whitelists, IP must be in at least one
        if ($allowed && !empty($whitelists)) {
            $in_whitelist = false;
            foreach ($whitelists as $range) {
                if ($this->is_ip_in_range($ip, $range['range_cidr'])) {
                    $in_whitelist = true;
                    break;
                }
            }

            if (!$in_whitelist) {
                $allowed = false;
                $reason = "IP not in any whitelist range";
            }
        }

        // Log the access attempt
        $this->log_access($user_id, $ip, $allowed, $reason);

        // Update last used if allowed
        if ($allowed) {
            $this->update_factor_usage($user_id);
        }

        return [
            'allowed' => $allowed,
            'ip' => $ip,
            'reason' => $reason,
        ];
    }

    /**
     * Add IP range
     *
     * @param string $range_cidr CIDR notation (e.g., 192.168.1.0/24)
     * @param string $type whitelist or blacklist
     * @param string $description Description
     * @param int $created_by User ID who created it
     * @return array Result
     */
    public function add_range(string $range_cidr, string $type, string $description, int $created_by): array
    {
        // Validate CIDR format
        if (!$this->validate_cidr($range_cidr)) {
            return [
                'success' => false,
                'error' => 'Invalid CIDR format',
            ];
        }

        // Validate type
        if (!in_array($type, ['whitelist', 'blacklist'])) {
            return [
                'success' => false,
                'error' => 'Invalid type. Must be whitelist or blacklist',
            ];
        }

        try {
            $stmt = $this->db->prepare("
                INSERT INTO mfa_ip_ranges
                (range_cidr, type, description, created_by)
                VALUES (?, ?, ?, ?)
            ");

            $stmt->execute([$range_cidr, $type, $description, $created_by]);

            $this->log_action($created_by, 'range_added', true,
                "Added $type range: $range_cidr");

            return [
                'success' => true,
                'id' => $this->db->lastInsertId(),
            ];

        } catch (\PDOException $e) {
            error_log("IP Range MFA error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Database error',
            ];
        }
    }

    /**
     * Remove IP range
     *
     * @param int $id Range ID
     * @param int $user_id User performing the action
     * @return array Result
     */
    public function remove_range(int $id, int $user_id): array
    {
        try {
            // Get range info before deleting
            $stmt = $this->db->prepare("SELECT range_cidr, type FROM mfa_ip_ranges WHERE id = ?");
            $stmt->execute([$id]);
            $range = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$range) {
                return [
                    'success' => false,
                    'error' => 'Range not found',
                ];
            }

            $stmt = $this->db->prepare("DELETE FROM mfa_ip_ranges WHERE id = ?");
            $stmt->execute([$id]);

            $this->log_action($user_id, 'range_removed', true,
                "Removed {$range['type']} range: {$range['range_cidr']}");

            return ['success' => true];

        } catch (\PDOException $e) {
            error_log("IP Range MFA error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Database error',
            ];
        }
    }

    /**
     * Toggle range enabled/disabled
     *
     * @param int $id Range ID
     * @param bool $enabled Enabled status
     * @return array Result
     */
    public function toggle_range(int $id, bool $enabled): array
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE mfa_ip_ranges
                SET enabled = ?
                WHERE id = ?
            ");

            $stmt->execute([$enabled ? 1 : 0, $id]);

            return ['success' => true];

        } catch (\PDOException $e) {
            return [
                'success' => false,
                'error' => 'Database error',
            ];
        }
    }

    /**
     * Get IP ranges by type
     *
     * @param string $type whitelist or blacklist
     * @return array Ranges
     */
    public function get_ranges(string $type = null): array
    {
        try {
            if ($type) {
                $stmt = $this->db->prepare("
                    SELECT * FROM mfa_ip_ranges
                    WHERE type = ? AND enabled = TRUE
                    ORDER BY created_at DESC
                ");
                $stmt->execute([$type]);
            } else {
                $stmt = $this->db->query("
                    SELECT * FROM mfa_ip_ranges
                    WHERE enabled = TRUE
                    ORDER BY type, created_at DESC
                ");
            }

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (\PDOException $e) {
            error_log("Failed to get IP ranges: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Check if IP is in CIDR range
     *
     * @param string $ip IP address
     * @param string $range CIDR range
     * @return bool True if IP is in range
     */
    private function is_ip_in_range(string $ip, string $range): bool
    {
        // Handle both IPv4 and IPv6
        if (strpos($range, '/') === false) {
            // No CIDR, exact match
            return $ip === $range;
        }

        list($subnet, $mask) = explode('/', $range);

        // IPv4
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $ip_long = ip2long($ip);
            $subnet_long = ip2long($subnet);
            $mask_long = -1 << (32 - (int)$mask);
            $subnet_long &= $mask_long;

            return ($ip_long & $mask_long) === $subnet_long;
        }

        // IPv6
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $this->is_ipv6_in_range($ip, $range);
        }

        return false;
    }

    /**
     * Check if IPv6 is in range
     *
     * @param string $ip IPv6 address
     * @param string $range IPv6 CIDR range
     * @return bool True if IP is in range
     */
    private function is_ipv6_in_range(string $ip, string $range): bool
    {
        list($subnet, $mask) = explode('/', $range);

        $ip_bin = inet_pton($ip);
        $subnet_bin = inet_pton($subnet);

        if ($ip_bin === false || $subnet_bin === false) {
            return false;
        }

        $ip_bits = $this->inet_to_bits($ip_bin);
        $subnet_bits = $this->inet_to_bits($subnet_bin);

        return substr($ip_bits, 0, (int)$mask) === substr($subnet_bits, 0, (int)$mask);
    }

    /**
     * Convert inet representation to binary string
     *
     * @param string $inet Inet representation
     * @return string Binary string
     */
    private function inet_to_bits(string $inet): string
    {
        $unpacked = unpack('A16', $inet);
        $unpacked = str_split($unpacked[1]);
        $binaryip = '';
        foreach ($unpacked as $char) {
            $binaryip .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }
        return $binaryip;
    }

    /**
     * Validate CIDR format
     *
     * @param string $cidr CIDR notation
     * @return bool Valid or not
     */
    private function validate_cidr(string $cidr): bool
    {
        if (strpos($cidr, '/') === false) {
            // Single IP
            return filter_var($cidr, FILTER_VALIDATE_IP) !== false;
        }

        list($ip, $mask) = explode('/', $cidr);

        // Validate IP
        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return false;
        }

        // Validate mask
        $mask = (int)$mask;
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $mask >= 0 && $mask <= 32;
        } else {
            return $mask >= 0 && $mask <= 128;
        }
    }

    /**
     * Get user's IP address
     *
     * @return string IP address
     */
    private function get_user_ip(): string
    {
        // Don't trust X-Forwarded-For for security-critical operations
        // Only use REMOTE_ADDR which can't be spoofed
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Log access attempt
     *
     * @param int $user_id User ID
     * @param string $ip IP address
     * @param bool $allowed Allowed or not
     * @param string $reason Reason
     * @return void
     */
    private function log_access(int $user_id, string $ip, bool $allowed, string $reason): void
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO mfa_ip_logs
                (user_id, ip_address, allowed, reason)
                VALUES (?, ?, ?, ?)
            ");

            $stmt->execute([$user_id, $ip, $allowed ? 1 : 0, $reason]);

        } catch (\PDOException $e) {
            error_log("Failed to log IP access: " . $e->getMessage());
        }
    }

    /**
     * Log MFA action
     *
     * @param int $user_id User ID
     * @param string $action Action
     * @param bool $success Success status
     * @param string $details Details
     * @return void
     */
    private function log_action(int $user_id, string $action, bool $success, string $details): void
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO mfa_audit_log
                (user_id, factor, action, success, ip_address, details)
                VALUES (?, 'iprange', ?, ?, ?, ?)
            ");

            $stmt->execute([
                $user_id,
                $action,
                $success ? 1 : 0,
                $this->get_user_ip(),
                $details,
            ]);
        } catch (\PDOException $e) {
            error_log("Failed to log MFA action: " . $e->getMessage());
        }
    }

    /**
     * Update factor last used timestamp
     *
     * @param int $user_id User ID
     * @return void
     */
    private function update_factor_usage(int $user_id): void
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO mfa_user_factors (user_id, factor, last_used)
                VALUES (?, 'iprange', NOW())
                ON DUPLICATE KEY UPDATE last_used = NOW()
            ");
            $stmt->execute([$user_id]);
        } catch (\PDOException $e) {
            // Silently fail
        }
    }

    /**
     * Get statistics
     *
     * @return array Statistics
     */
    public function get_stats(): array
    {
        try {
            $stmt = $this->db->query("
                SELECT
                    COUNT(*) as total_checks,
                    SUM(CASE WHEN allowed = TRUE THEN 1 ELSE 0 END) as allowed_access,
                    SUM(CASE WHEN allowed = FALSE THEN 1 ELSE 0 END) as blocked_access,
                    COUNT(DISTINCT ip_address) as unique_ips
                FROM mfa_ip_logs
                WHERE timestamp > DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");

            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * Get recent blocked IPs
     *
     * @param int $limit Number of records
     * @return array Recent blocks
     */
    public function get_recent_blocks(int $limit = 50): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT user_id, ip_address, reason, timestamp
                FROM mfa_ip_logs
                WHERE allowed = FALSE
                ORDER BY timestamp DESC
                LIMIT ?
            ");

            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (\PDOException $e) {
            return [];
        }
    }
}
