<?php
/**
 * SMS Factor for Multi-Factor Authentication
 *
 * Sends verification codes via SMS to user's registered phone number.
 * Supports multiple SMS providers through pluggable gateway system.
 *
 * @package    tool_mfa
 * @subpackage factors
 * @copyright  2024 ISER
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

namespace ISER\Admin\Tool\MFA\Factors;

defined('BASE_DIR') || define('BASE_DIR', dirname(__DIR__, 6));

/**
 * SMS Factor Implementation
 *
 * Provides SMS-based two-factor authentication by sending
 * verification codes to user's mobile phone.
 */
class SMSFactor
{
    /** @var \PDO Database connection */
    private $db;

    /** @var string Table name for SMS codes */
    private const TABLE_SMS = 'mfa_sms_codes';

    /** @var string Table name for audit log */
    private const TABLE_AUDIT = 'mfa_audit_log';

    /** @var int Code length (6 digits) */
    private const CODE_LENGTH = 6;

    /** @var int Code expiry time in seconds (10 minutes) */
    private const CODE_EXPIRY = 600;

    /** @var int Maximum attempts before lockout */
    private const MAX_ATTEMPTS = 3;

    /** @var int Rate limit: max codes per hour */
    private const RATE_LIMIT_COUNT = 5;

    /** @var int Rate limit window in seconds */
    private const RATE_LIMIT_WINDOW = 3600;

    /**
     * Constructor
     */
    public function __construct()
    {
        global $db;
        $this->db = $db;
    }

    /**
     * Send SMS code to user's phone number
     *
     * @param int $user_id User ID
     * @param string $phone_number Phone number (E.164 format preferred)
     * @return array Result with success/error
     */
    public function send_code(int $user_id, string $phone_number): array
    {
        // Validate phone number format
        $phone_number = $this->normalize_phone_number($phone_number);
        if (!$this->is_valid_phone_number($phone_number)) {
            return ['error' => 'Invalid phone number format'];
        }

        // Check rate limiting
        $rate_limit = $this->check_rate_limit($user_id);
        if (!$rate_limit['allowed']) {
            return [
                'error' => 'Rate limit exceeded. Try again in ' .
                          ceil($rate_limit['retry_after'] / 60) . ' minutes',
            ];
        }

        // Generate 6-digit code
        $code = $this->generate_code();

        // Hash code (bcrypt)
        $code_hash = password_hash($code, PASSWORD_BCRYPT);

        // Calculate expiry time
        $expires_at = date('Y-m-d H:i:s', time() + self::CODE_EXPIRY);

        // Store code in database
        $stmt = $this->db->prepare("
            INSERT INTO " . $this->get_table_name(self::TABLE_SMS) . "
            (user_id, phone_number, code_hash, created_at, expires_at, ip_address)
            VALUES (:user_id, :phone_number, :code_hash, NOW(), :expires_at, :ip_address)
        ");

        $stmt->execute([
            'user_id' => $user_id,
            'phone_number' => $phone_number,
            'code_hash' => $code_hash,
            'expires_at' => $expires_at,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
        ]);

        $code_id = $this->db->lastInsertId();

        // Send SMS via configured gateway
        $sms_result = $this->send_sms($phone_number, $code);

        if ($sms_result['success']) {
            // Log success
            $this->log_audit($user_id, 'sms_code_sent', [
                'phone_number' => $this->mask_phone_number($phone_number),
                'code_id' => $code_id,
                'gateway' => $sms_result['gateway'] ?? 'unknown',
            ]);

            return [
                'success' => true,
                'message' => 'SMS code sent to ' . $this->mask_phone_number($phone_number),
                'code_id' => $code_id,
                'expires_in' => self::CODE_EXPIRY,
            ];
        } else {
            // Log failure
            $this->log_audit($user_id, 'sms_send_failed', [
                'phone_number' => $this->mask_phone_number($phone_number),
                'error' => $sms_result['error'] ?? 'Unknown error',
            ]);

            return [
                'error' => 'Failed to send SMS: ' . ($sms_result['error'] ?? 'Unknown error'),
            ];
        }
    }

    /**
     * Verify SMS code
     *
     * @param int $user_id User ID
     * @param string $code 6-digit verification code
     * @return bool True if code is valid
     */
    public function verify_code(int $user_id, string $code): bool
    {
        // Sanitize code (remove spaces, dashes)
        $code = preg_replace('/\s|-/', '', $code);

        // Validate code format
        if (!preg_match('/^\d{' . self::CODE_DIGITS . '}$/', $code)) {
            return false;
        }

        // Get latest unverified code for user
        $stmt = $this->db->prepare("
            SELECT *
            FROM " . $this->get_table_name(self::TABLE_SMS) . "
            WHERE user_id = :user_id
              AND verified = FALSE
              AND expires_at > NOW()
            ORDER BY created_at DESC
            LIMIT 1
        ");

        $stmt->execute(['user_id' => $user_id]);
        $stored_code = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$stored_code) {
            $this->log_audit($user_id, 'sms_verify_failed', ['reason' => 'No valid code found']);
            return false;
        }

        // Check attempts
        if ($stored_code['attempts'] >= self::MAX_ATTEMPTS) {
            $this->log_audit($user_id, 'sms_max_attempts', ['code_id' => $stored_code['id']]);
            return false;
        }

        // Verify code
        if (password_verify($code, $stored_code['code_hash'])) {
            // Mark as verified
            $update_stmt = $this->db->prepare("
                UPDATE " . $this->get_table_name(self::TABLE_SMS) . "
                SET verified = TRUE,
                    attempts = attempts + 1
                WHERE id = :id
            ");
            $update_stmt->execute(['id' => $stored_code['id']]);

            $this->log_audit($user_id, 'sms_verified', [
                'code_id' => $stored_code['id'],
                'phone_number' => $this->mask_phone_number($stored_code['phone_number']),
            ]);

            return true;
        } else {
            // Increment attempts
            $update_stmt = $this->db->prepare("
                UPDATE " . $this->get_table_name(self::TABLE_SMS) . "
                SET attempts = attempts + 1
                WHERE id = :id
            ");
            $update_stmt->execute(['id' => $stored_code['id']]);

            $this->log_audit($user_id, 'sms_verify_failed', [
                'code_id' => $stored_code['id'],
                'attempts' => $stored_code['attempts'] + 1,
            ]);

            return false;
        }
    }

    /**
     * Generate random 6-digit code
     *
     * @return string 6-digit code
     */
    private function generate_code(): string
    {
        // Cryptographically secure random number
        $code = random_int(100000, 999999);
        return (string)$code;
    }

    /**
     * Send SMS via configured gateway
     *
     * This is a pluggable system. Configure SMS gateway in lib/config.php
     *
     * Supported gateways:
     * - Twilio
     * - Nexmo/Vonage
     * - AWS SNS
     * - Mock (for testing)
     *
     * @param string $phone_number Recipient phone number
     * @param string $code Verification code
     * @return array Result with success/error
     */
    private function send_sms(string $phone_number, string $code): array
    {
        $gateway = defined('SMS_GATEWAY') ? SMS_GATEWAY : 'mock';

        $message = sprintf(
            'Your %s verification code is: %s. Valid for %d minutes. Do not share this code.',
            defined('SITE_NAME') ? SITE_NAME : 'NexoSupport',
            $code,
            ceil(self::CODE_EXPIRY / 60)
        );

        switch ($gateway) {
            case 'twilio':
                return $this->send_via_twilio($phone_number, $message);

            case 'nexmo':
            case 'vonage':
                return $this->send_via_vonage($phone_number, $message);

            case 'sns':
                return $this->send_via_sns($phone_number, $message);

            case 'mock':
            default:
                // Mock gateway for testing (logs to file instead of sending)
                return $this->send_via_mock($phone_number, $message, $code);
        }
    }

    /**
     * Send SMS via Twilio
     *
     * Requires: TWILIO_ACCOUNT_SID, TWILIO_AUTH_TOKEN, TWILIO_PHONE_NUMBER in config.php
     *
     * @param string $phone_number Recipient
     * @param string $message Message content
     * @return array Result
     */
    private function send_via_twilio(string $phone_number, string $message): array
    {
        if (!defined('TWILIO_ACCOUNT_SID') || !defined('TWILIO_AUTH_TOKEN') || !defined('TWILIO_PHONE_NUMBER')) {
            return ['error' => 'Twilio not configured'];
        }

        try {
            // Twilio API endpoint
            $url = sprintf(
                'https://api.twilio.com/2010-04-01/Accounts/%s/Messages.json',
                TWILIO_ACCOUNT_SID
            );

            // POST data
            $data = [
                'From' => TWILIO_PHONE_NUMBER,
                'To' => $phone_number,
                'Body' => $message,
            ];

            // cURL request
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERPWD, TWILIO_ACCOUNT_SID . ':' . TWILIO_AUTH_TOKEN);

            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($http_code >= 200 && $http_code < 300) {
                return ['success' => true, 'gateway' => 'twilio'];
            } else {
                $error = json_decode($response, true);
                return ['error' => $error['message'] ?? 'Twilio API error'];
            }
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Send SMS via Vonage (formerly Nexmo)
     *
     * Requires: VONAGE_API_KEY, VONAGE_API_SECRET, VONAGE_FROM_NUMBER in config.php
     *
     * @param string $phone_number Recipient
     * @param string $message Message content
     * @return array Result
     */
    private function send_via_vonage(string $phone_number, string $message): array
    {
        if (!defined('VONAGE_API_KEY') || !defined('VONAGE_API_SECRET') || !defined('VONAGE_FROM_NUMBER')) {
            return ['error' => 'Vonage not configured'];
        }

        try {
            $url = 'https://rest.nexmo.com/sms/json';

            $data = [
                'api_key' => VONAGE_API_KEY,
                'api_secret' => VONAGE_API_SECRET,
                'from' => VONAGE_FROM_NUMBER,
                'to' => $phone_number,
                'text' => $message,
            ];

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($ch);
            curl_close($ch);

            $result = json_decode($response, true);

            if (isset($result['messages'][0]['status']) && $result['messages'][0]['status'] == '0') {
                return ['success' => true, 'gateway' => 'vonage'];
            } else {
                return ['error' => $result['messages'][0]['error-text'] ?? 'Vonage API error'];
            }
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Send SMS via AWS SNS
     *
     * Requires: AWS_REGION, AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY in config.php
     *
     * @param string $phone_number Recipient
     * @param string $message Message content
     * @return array Result
     */
    private function send_via_sns(string $phone_number, string $message): array
    {
        if (!defined('AWS_REGION') || !defined('AWS_ACCESS_KEY_ID') || !defined('AWS_SECRET_ACCESS_KEY')) {
            return ['error' => 'AWS SNS not configured'];
        }

        // Note: This is a simplified implementation
        // In production, use AWS SDK for PHP
        return ['error' => 'AWS SNS requires AWS SDK. Please install aws/aws-sdk-php via Composer.'];
    }

    /**
     * Mock SMS gateway for testing
     *
     * Instead of sending SMS, logs the code to a file
     *
     * @param string $phone_number Recipient
     * @param string $message Message content
     * @param string $code Verification code
     * @return array Result
     */
    private function send_via_mock(string $phone_number, string $message, string $code): array
    {
        $log_dir = defined('LOGS_DIR') ? LOGS_DIR : BASE_DIR . '/logs';
        $log_file = $log_dir . '/mfa_sms_mock.log';

        $log_entry = sprintf(
            "[%s] SMS to %s: Code = %s\n",
            date('Y-m-d H:i:s'),
            $this->mask_phone_number($phone_number),
            $code
        );

        file_put_contents($log_file, $log_entry, FILE_APPEND);

        return [
            'success' => true,
            'gateway' => 'mock',
            'note' => 'SMS not actually sent. Check ' . $log_file,
        ];
    }

    /**
     * Normalize phone number to E.164 format
     *
     * @param string $phone_number Raw phone number
     * @return string Normalized phone number
     */
    private function normalize_phone_number(string $phone_number): string
    {
        // Remove all non-digit characters except +
        $phone = preg_replace('/[^\d+]/', '', $phone_number);

        // If doesn't start with +, add default country code (if configured)
        if (substr($phone, 0, 1) !== '+') {
            $default_country_code = defined('SMS_DEFAULT_COUNTRY_CODE') ? SMS_DEFAULT_COUNTRY_CODE : '1';
            $phone = '+' . $default_country_code . $phone;
        }

        return $phone;
    }

    /**
     * Validate phone number format
     *
     * Basic E.164 validation: +[country code][number]
     * Length: 7-15 digits (excluding +)
     *
     * @param string $phone_number Phone number
     * @return bool True if valid
     */
    private function is_valid_phone_number(string $phone_number): bool
    {
        // E.164 format: +[1-15 digits]
        return preg_match('/^\+\d{7,15}$/', $phone_number) === 1;
    }

    /**
     * Mask phone number for display/logging
     *
     * Example: +1234567890 → +1234***890
     *
     * @param string $phone_number Phone number
     * @return string Masked phone number
     */
    private function mask_phone_number(string $phone_number): string
    {
        if (strlen($phone_number) < 7) {
            return '***';
        }

        $start = substr($phone_number, 0, 5);
        $end = substr($phone_number, -3);

        return $start . '***' . $end;
    }

    /**
     * Check rate limiting
     *
     * @param int $user_id User ID
     * @return array ['allowed' => bool, 'retry_after' => int]
     */
    private function check_rate_limit(int $user_id): array
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count,
                   MIN(created_at) as first_code_at
            FROM " . $this->get_table_name(self::TABLE_SMS) . "
            WHERE user_id = :user_id
              AND created_at > DATE_SUB(NOW(), INTERVAL :window SECOND)
        ");

        $stmt->execute([
            'user_id' => $user_id,
            'window' => self::RATE_LIMIT_WINDOW,
        ]);

        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($result['count'] >= self::RATE_LIMIT_COUNT) {
            $retry_after = self::RATE_LIMIT_WINDOW - (time() - strtotime($result['first_code_at']));

            return [
                'allowed' => false,
                'retry_after' => max(0, $retry_after),
            ];
        }

        return ['allowed' => true];
    }

    /**
     * Clean up expired codes
     *
     * @return int Number of codes deleted
     */
    public function cleanup_expired(): int
    {
        $stmt = $this->db->prepare("
            DELETE FROM " . $this->get_table_name(self::TABLE_SMS) . "
            WHERE expires_at < NOW()
        ");

        $stmt->execute();
        return $stmt->rowCount();
    }

    /**
     * Get SMS statistics
     *
     * @return array Statistics
     */
    public function get_stats(): array
    {
        $stmt = $this->db->query("
            SELECT
                COUNT(*) as total_codes,
                SUM(CASE WHEN verified = TRUE THEN 1 ELSE 0 END) as verified_codes,
                SUM(CASE WHEN verified = FALSE AND expires_at > NOW() THEN 1 ELSE 0 END) as pending_codes,
                SUM(CASE WHEN expires_at < NOW() THEN 1 ELSE 0 END) as expired_codes,
                AVG(attempts) as avg_attempts
            FROM " . $this->get_table_name(self::TABLE_SMS) . "
        ");

        return $stmt->fetch(\PDO::FETCH_ASSOC);
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
            VALUES (:user_id, 'sms', :event, :details, :ip_address, :user_agent, NOW())
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
