<?php
/**
 * Backup Codes Factor for Multi-Factor Authentication
 *
 * Generates one-time use backup codes for account recovery when
 * primary MFA factors are unavailable.
 *
 * @package    tool_mfa
 * @subpackage factors
 * @copyright  2024 ISER
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

namespace ISER\Admin\Tool\MFA\Factors;

defined('BASE_DIR') || define('BASE_DIR', dirname(__DIR__, 6));

/**
 * Backup Codes Factor Implementation
 *
 * Provides emergency backup codes that can be used when
 * other MFA methods are unavailable (lost phone, etc.)
 */
class BackupCodesFactor
{
    /** @var \PDO Database connection */
    private $db;

    /** @var string Table name for backup codes */
    private const TABLE_CODES = 'mfa_backup_codes';

    /** @var string Table name for audit log */
    private const TABLE_AUDIT = 'mfa_audit_log';

    /** @var int Number of backup codes to generate */
    private const CODES_COUNT = 10;

    /** @var int Length of each backup code (excluding dashes) */
    private const CODE_LENGTH = 8;

    /** @var int Code format: XXXX-XXXX (4 chars, dash, 4 chars) */
    private const CODE_FORMAT_SEGMENT = 4;

    /**
     * Constructor
     */
    public function __construct()
    {
        global $db;
        $this->db = $db;
    }

    /**
     * Generate backup codes for a user
     *
     * Creates a new set of backup codes and invalidates old ones
     *
     * @param int $user_id User ID
     * @param bool $regenerate If true, delete old codes first
     * @return array Generated codes (plain text, to show user once)
     */
    public function generate_codes(int $user_id, bool $regenerate = false): array
    {
        // Check if user already has unused codes
        $existing_count = $this->get_unused_codes_count($user_id);

        if ($existing_count > 0 && !$regenerate) {
            return [
                'error' => sprintf(
                    'User already has %d unused backup codes. Use regenerate=true to replace them.',
                    $existing_count
                ),
            ];
        }

        // Start transaction
        $this->db->beginTransaction();

        try {
            // Delete existing codes if regenerating
            if ($regenerate) {
                $this->delete_user_codes($user_id);
            }

            // Generate new codes
            $codes = [];
            $codes_data = [];

            for ($i = 0; $i < self::CODES_COUNT; $i++) {
                // Generate cryptographically secure random code
                $code = $this->generate_single_code();
                $codes[] = $code;

                // Hash code for storage (bcrypt)
                $code_hash = password_hash($code, PASSWORD_BCRYPT);

                $codes_data[] = [
                    'user_id' => $user_id,
                    'code_hash' => $code_hash,
                ];
            }

            // Insert codes into database
            $stmt = $this->db->prepare("
                INSERT INTO " . $this->get_table_name(self::TABLE_CODES) . "
                (user_id, code_hash, created_at)
                VALUES (:user_id, :code_hash, NOW())
            ");

            foreach ($codes_data as $data) {
                $stmt->execute($data);
            }

            // Commit transaction
            $this->db->commit();

            // Log generation
            $this->log_audit($user_id, 'backup_codes_generated', [
                'count' => self::CODES_COUNT,
                'regenerate' => $regenerate,
            ]);

            return [
                'success' => true,
                'codes' => $codes,
                'count' => self::CODES_COUNT,
                'message' => 'Backup codes generated successfully. Save these codes in a secure location. Each code can only be used once.',
                'warning' => 'These codes will NOT be shown again. Save them now!',
            ];
        } catch (\Exception $e) {
            $this->db->rollBack();

            return [
                'error' => 'Failed to generate backup codes: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Verify a backup code
     *
     * Checks if code is valid and not yet used, then marks it as used
     *
     * @param int $user_id User ID
     * @param string $code Backup code to verify
     * @return bool True if code is valid
     */
    public function verify_code(int $user_id, string $code): bool
    {
        // Normalize code (remove spaces, dashes, lowercase)
        $code = $this->normalize_code($code);

        // Validate code format
        if (!$this->is_valid_code_format($code)) {
            $this->log_audit($user_id, 'backup_code_invalid_format', [
                'code_length' => strlen($code),
            ]);
            return false;
        }

        // Get all unused codes for user
        $stmt = $this->db->prepare("
            SELECT id, code_hash
            FROM " . $this->get_table_name(self::TABLE_CODES) . "
            WHERE user_id = :user_id
              AND used = FALSE
            ORDER BY created_at DESC
        ");

        $stmt->execute(['user_id' => $user_id]);
        $stored_codes = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($stored_codes)) {
            $this->log_audit($user_id, 'backup_code_none_available', []);
            return false;
        }

        // Try to verify against each stored code
        foreach ($stored_codes as $stored_code) {
            if (password_verify($code, $stored_code['code_hash'])) {
                // Mark code as used
                $this->mark_code_as_used($stored_code['id'], $user_id);

                // Log successful verification
                $this->log_audit($user_id, 'backup_code_used', [
                    'code_id' => $stored_code['id'],
                    'remaining' => $this->get_unused_codes_count($user_id),
                ]);

                // Warn if running low on backup codes
                $remaining = $this->get_unused_codes_count($user_id);
                if ($remaining <= 2) {
                    $this->log_audit($user_id, 'backup_codes_low', [
                        'remaining' => $remaining,
                    ]);
                }

                return true;
            }
        }

        // Code did not match any stored codes
        $this->log_audit($user_id, 'backup_code_invalid', [
            'code_length' => strlen($code),
        ]);

        return false;
    }

    /**
     * Get count of unused backup codes for user
     *
     * @param int $user_id User ID
     * @return int Number of unused codes
     */
    public function get_unused_codes_count(int $user_id): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count
            FROM " . $this->get_table_name(self::TABLE_CODES) . "
            WHERE user_id = :user_id
              AND used = FALSE
        ");

        $stmt->execute(['user_id' => $user_id]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return (int)$result['count'];
    }

    /**
     * Get backup codes status for user
     *
     * @param int $user_id User ID
     * @return array Status information
     */
    public function get_status(int $user_id): array
    {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) as total,
                SUM(CASE WHEN used = FALSE THEN 1 ELSE 0 END) as unused,
                SUM(CASE WHEN used = TRUE THEN 1 ELSE 0 END) as used,
                MAX(created_at) as last_generated,
                MAX(used_at) as last_used
            FROM " . $this->get_table_name(self::TABLE_CODES) . "
            WHERE user_id = :user_id
        ");

        $stmt->execute(['user_id' => $user_id]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        $has_codes = $result['total'] > 0;
        $status = 'none';

        if ($has_codes) {
            if ($result['unused'] == 0) {
                $status = 'all_used';
            } elseif ($result['unused'] <= 2) {
                $status = 'low';
            } else {
                $status = 'active';
            }
        }

        return [
            'has_codes' => $has_codes,
            'total' => (int)$result['total'],
            'unused' => (int)$result['unused'],
            'used' => (int)$result['used'],
            'status' => $status,
            'last_generated' => $result['last_generated'],
            'last_used' => $result['last_used'],
            'needs_regeneration' => $result['unused'] <= 2,
        ];
    }

    /**
     * Delete all backup codes for a user
     *
     * @param int $user_id User ID
     * @return int Number of codes deleted
     */
    public function delete_user_codes(int $user_id): int
    {
        $stmt = $this->db->prepare("
            DELETE FROM " . $this->get_table_name(self::TABLE_CODES) . "
            WHERE user_id = :user_id
        ");

        $stmt->execute(['user_id' => $user_id]);
        $deleted_count = $stmt->rowCount();

        if ($deleted_count > 0) {
            $this->log_audit($user_id, 'backup_codes_deleted', [
                'count' => $deleted_count,
            ]);
        }

        return $deleted_count;
    }

    /**
     * Generate a single backup code
     *
     * Format: XXXX-XXXX (8 alphanumeric characters, uppercase, no ambiguous chars)
     *
     * @return string Backup code
     */
    private function generate_single_code(): string
    {
        // Character set: alphanumeric excluding ambiguous characters (0, O, I, 1)
        $charset = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $charset_length = strlen($charset);

        $code = '';
        for ($i = 0; $i < self::CODE_LENGTH; $i++) {
            $random_index = random_int(0, $charset_length - 1);
            $code .= $charset[$random_index];

            // Add dash after first segment
            if ($i === self::CODE_FORMAT_SEGMENT - 1) {
                $code .= '-';
            }
        }

        return $code;
    }

    /**
     * Normalize backup code
     *
     * Removes spaces, dashes, converts to uppercase
     *
     * @param string $code Raw code
     * @return string Normalized code
     */
    private function normalize_code(string $code): string
    {
        // Remove whitespace and dashes
        $code = preg_replace('/[\s\-]/', '', $code);

        // Convert to uppercase
        $code = strtoupper($code);

        return $code;
    }

    /**
     * Validate backup code format
     *
     * @param string $code Normalized code
     * @return bool True if valid format
     */
    private function is_valid_code_format(string $code): bool
    {
        // Must be exactly CODE_LENGTH characters
        if (strlen($code) !== self::CODE_LENGTH) {
            return false;
        }

        // Must contain only valid characters (no ambiguous ones)
        $charset = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        for ($i = 0; $i < strlen($code); $i++) {
            if (strpos($charset, $code[$i]) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Mark a backup code as used
     *
     * @param int $code_id Code ID
     * @param int $user_id User ID (for audit)
     */
    private function mark_code_as_used(int $code_id, int $user_id): void
    {
        $stmt = $this->db->prepare("
            UPDATE " . $this->get_table_name(self::TABLE_CODES) . "
            SET used = TRUE,
                used_at = NOW(),
                used_ip = :ip_address
            WHERE id = :code_id
        ");

        $stmt->execute([
            'code_id' => $code_id,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
        ]);
    }

    /**
     * Get backup codes statistics
     *
     * @return array Statistics
     */
    public function get_stats(): array
    {
        $stmt = $this->db->query("
            SELECT
                COUNT(DISTINCT user_id) as users_with_codes,
                COUNT(*) as total_codes,
                SUM(CASE WHEN used = FALSE THEN 1 ELSE 0 END) as unused_codes,
                SUM(CASE WHEN used = TRUE THEN 1 ELSE 0 END) as used_codes,
                AVG(CASE WHEN used = FALSE THEN 1 ELSE 0 END) as avg_unused_per_user
            FROM " . $this->get_table_name(self::TABLE_CODES) . "
        ");

        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Format code for display
     *
     * Adds dash for readability: ABCD1234 → ABCD-1234
     *
     * @param string $code Code without formatting
     * @return string Formatted code
     */
    public static function format_code(string $code): string
    {
        if (strlen($code) !== self::CODE_LENGTH) {
            return $code;
        }

        return substr($code, 0, self::CODE_FORMAT_SEGMENT) . '-' .
               substr($code, self::CODE_FORMAT_SEGMENT);
    }

    /**
     * Get table name with prefix
     *
     * @param string $table Table name without prefix
     * @return string Table name with prefix
     */
    private function get_table_name(string $table): string
    {
        $prefix = defined('DB_PREFIX') ? DB_PREFIX : '';
        return $prefix . $table;
    }

    /**
     * Log audit event
     *
     * @param int $user_id User ID
     * @param string $event Event type
     * @param array $details Event details
     */
    private function log_audit(int $user_id, string $event, array $details = []): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO " . $this->get_table_name(self::TABLE_AUDIT) . "
            (user_id, factor_type, event, details, ip_address, user_agent, created_at)
            VALUES (:user_id, 'backup_codes', :event, :details, :ip_address, :user_agent, NOW())
        ");

        $stmt->execute([
            'user_id' => $user_id,
            'event' => $event,
            'details' => json_encode($details),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
        ]);
    }
}
