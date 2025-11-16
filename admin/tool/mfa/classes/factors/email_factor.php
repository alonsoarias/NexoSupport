<?php
/**
 * NexoSupport - Email MFA Factor
 *
 * @package    tool_mfa
 * @copyright  2024 ISER
 * @license    Proprietary
 */

namespace ISER\Tools\MFA\Factors;

defined('NEXOSUPPORT_INTERNAL') || die();

use PDO;

/**
 * Email-based MFA Factor
 *
 * Sends verification codes via email
 */
class EmailFactor
{
    /** @var PDO Database connection */
    private $db;

    /** @var int Code expiration time in minutes */
    private $expiration_minutes = 10;

    /** @var int Maximum attempts allowed */
    private $max_attempts = 3;

    /** @var int Code length */
    private $code_length = 6;

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
     * Send verification code to user's email
     *
     * @param int $user_id User ID
     * @param string $email User email
     * @return array Result with success status and code_id
     */
    public function send_code(int $user_id, string $email): array
    {
        // Generate random 6-digit code
        $code = $this->generate_code();

        // Hash the code for secure storage
        $code_hash = password_hash($code, PASSWORD_BCRYPT);

        // Calculate expiration time
        $expires_at = date('Y-m-d H:i:s', strtotime("+{$this->expiration_minutes} minutes"));

        // Get user IP
        $ip_address = $this->get_user_ip();

        try {
            // Invalidate previous codes for this user
            $this->invalidate_previous_codes($user_id);

            // Insert new code
            $stmt = $this->db->prepare("
                INSERT INTO mfa_email_codes
                (user_id, code_hash, expires_at, ip_address)
                VALUES (?, ?, ?, ?)
            ");

            $stmt->execute([$user_id, $code_hash, $expires_at, $ip_address]);
            $code_id = $this->db->lastInsertId();

            // Send email
            $sent = $this->send_email($email, $code);

            if (!$sent) {
                return [
                    'success' => false,
                    'error' => 'Failed to send verification email',
                ];
            }

            // Log the action
            $this->log_action($user_id, 'code_sent', true, "Code sent to $email");

            return [
                'success' => true,
                'code_id' => $code_id,
                'expires_at' => $expires_at,
            ];

        } catch (\PDOException $e) {
            error_log("Email MFA error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Database error',
            ];
        }
    }

    /**
     * Verify user-provided code
     *
     * @param int $user_id User ID
     * @param string $code User-provided code
     * @return array Result with success status
     */
    public function verify_code(int $user_id, string $code): array
    {
        try {
            // Get most recent non-verified code for user
            $stmt = $this->db->prepare("
                SELECT id, code_hash, expires_at, attempts
                FROM mfa_email_codes
                WHERE user_id = ?
                AND verified = FALSE
                ORDER BY created_at DESC
                LIMIT 1
            ");

            $stmt->execute([$user_id]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$record) {
                $this->log_action($user_id, 'verify_failed', false, 'No valid code found');
                return [
                    'success' => false,
                    'error' => 'No verification code found',
                ];
            }

            // Check if code is expired
            if (strtotime($record['expires_at']) < time()) {
                $this->log_action($user_id, 'verify_failed', false, 'Code expired');
                return [
                    'success' => false,
                    'error' => 'Verification code has expired',
                ];
            }

            // Check if max attempts exceeded
            if ($record['attempts'] >= $this->max_attempts) {
                $this->log_action($user_id, 'verify_failed', false, 'Max attempts exceeded');
                return [
                    'success' => false,
                    'error' => 'Maximum verification attempts exceeded',
                ];
            }

            // Verify the code
            if (password_verify($code, $record['code_hash'])) {
                // Mark as verified
                $stmt = $this->db->prepare("
                    UPDATE mfa_email_codes
                    SET verified = TRUE
                    WHERE id = ?
                ");
                $stmt->execute([$record['id']]);

                // Update last used timestamp
                $this->update_factor_usage($user_id);

                $this->log_action($user_id, 'verify_success', true, 'Code verified successfully');

                return [
                    'success' => true,
                    'message' => 'Verification successful',
                ];
            } else {
                // Increment attempts
                $stmt = $this->db->prepare("
                    UPDATE mfa_email_codes
                    SET attempts = attempts + 1
                    WHERE id = ?
                ");
                $stmt->execute([$record['id']]);

                $remaining = $this->max_attempts - ($record['attempts'] + 1);
                $this->log_action($user_id, 'verify_failed', false, 'Invalid code provided');

                return [
                    'success' => false,
                    'error' => 'Invalid verification code',
                    'attempts_remaining' => max(0, $remaining),
                ];
            }

        } catch (\PDOException $e) {
            error_log("Email MFA verify error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Database error',
            ];
        }
    }

    /**
     * Generate random verification code
     *
     * @return string 6-digit code
     */
    private function generate_code(): string
    {
        // Use cryptographically secure random number generator
        $code = '';
        for ($i = 0; $i < $this->code_length; $i++) {
            $code .= random_int(0, 9);
        }
        return $code;
    }

    /**
     * Send verification email
     *
     * @param string $email Recipient email
     * @param string $code Verification code
     * @return bool Success status
     */
    private function send_email(string $email, string $code): bool
    {
        $subject = 'NexoSupport - Verification Code';

        $message = "Your NexoSupport verification code is: $code\n\n";
        $message .= "This code will expire in {$this->expiration_minutes} minutes.\n";
        $message .= "If you did not request this code, please ignore this email.\n\n";
        $message .= "Do not share this code with anyone.";

        $headers = [
            'From: noreply@nexosupport.com',
            'Reply-To: support@nexosupport.com',
            'X-Mailer: PHP/' . phpversion(),
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
        ];

        return mail($email, $subject, $message, implode("\r\n", $headers));
    }

    /**
     * Invalidate previous codes for user
     *
     * @param int $user_id User ID
     * @return void
     */
    private function invalidate_previous_codes(int $user_id): void
    {
        $stmt = $this->db->prepare("
            UPDATE mfa_email_codes
            SET verified = TRUE
            WHERE user_id = ? AND verified = FALSE
        ");
        $stmt->execute([$user_id]);
    }

    /**
     * Get user's IP address
     *
     * @return string IP address
     */
    private function get_user_ip(): string
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // Take first IP from list (could be proxy chain)
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
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
        $stmt = $this->db->prepare("
            INSERT INTO mfa_user_factors (user_id, factor, last_used)
            VALUES (?, 'email', NOW())
            ON DUPLICATE KEY UPDATE last_used = NOW()
        ");
        $stmt->execute([$user_id]);
    }

    /**
     * Log MFA action
     *
     * @param int $user_id User ID
     * @param string $action Action performed
     * @param bool $success Success status
     * @param string $details Additional details
     * @return void
     */
    private function log_action(int $user_id, string $action, bool $success, string $details): void
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO mfa_audit_log
                (user_id, factor, action, success, ip_address, user_agent, details)
                VALUES (?, 'email', ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $user_id,
                $action,
                $success ? 1 : 0,
                $this->get_user_ip(),
                $_SERVER['HTTP_USER_AGENT'] ?? '',
                $details,
            ]);
        } catch (\PDOException $e) {
            error_log("Failed to log MFA action: " . $e->getMessage());
        }
    }

    /**
     * Clean up expired codes
     *
     * @return int Number of codes deleted
     */
    public function cleanup_expired(): int
    {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM mfa_email_codes
                WHERE expires_at < NOW()
            ");
            $stmt->execute();
            return $stmt->rowCount();
        } catch (\PDOException $e) {
            error_log("Failed to cleanup expired codes: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get statistics for email MFA
     *
     * @return array Statistics
     */
    public function get_stats(): array
    {
        try {
            $stmt = $this->db->query("
                SELECT
                    COUNT(*) as total_codes,
                    SUM(CASE WHEN verified = TRUE THEN 1 ELSE 0 END) as verified_codes,
                    SUM(CASE WHEN expires_at < NOW() THEN 1 ELSE 0 END) as expired_codes,
                    AVG(attempts) as avg_attempts
                FROM mfa_email_codes
                WHERE created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");

            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Failed to get stats: " . $e->getMessage());
            return [];
        }
    }
}
