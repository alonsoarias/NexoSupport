<?php
/**
 * TOTP (Time-based One-Time Password) Factor
 *
 * Compatible with Google Authenticator, Authy, Microsoft Authenticator, etc.
 * Implements RFC 6238 (TOTP) and RFC 4226 (HOTP)
 *
 * @package    tool_mfa
 * @subpackage factors
 * @copyright  2024 ISER
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

namespace ISER\Admin\Tool\MFA\Factors;

defined('BASE_DIR') || define('BASE_DIR', dirname(__DIR__, 6));

/**
 * TOTP Factor Implementation
 *
 * Provides Time-based One-Time Password authentication using
 * the TOTP algorithm (RFC 6238). Compatible with standard
 * authenticator apps like Google Authenticator.
 */
class TOTPFactor
{
    /** @var \PDO Database connection */
    private $db;

    /** @var string Table name for TOTP secrets */
    private const TABLE_TOTP = 'mfa_totp_secrets';

    /** @var string Table name for audit log */
    private const TABLE_AUDIT = 'mfa_audit_log';

    /** @var int Time step in seconds (standard is 30) */
    private const TIME_STEP = 30;

    /** @var int Number of digits in TOTP code (standard is 6) */
    private const CODE_DIGITS = 6;

    /** @var int Time drift tolerance (allow ±1 time window) */
    private const TIME_DRIFT = 1;

    /** @var int Maximum verification attempts */
    private const MAX_ATTEMPTS = 5;

    /** @var int Lockout duration in seconds (30 minutes) */
    private const LOCKOUT_DURATION = 1800;

    /**
     * Constructor
     */
    public function __construct()
    {
        global $db;
        $this->db = $db;
    }

    /**
     * Generate a new secret key for TOTP
     *
     * Creates a cryptographically secure random secret
     * encoded in Base32 format (160 bits / 32 characters)
     *
     * @return string Base32-encoded secret (32 characters)
     */
    public function generate_secret(): string
    {
        // Generate 20 random bytes (160 bits)
        $random_bytes = random_bytes(20);

        // Encode to Base32 (RFC 4648)
        return $this->base32_encode($random_bytes);
    }

    /**
     * Setup TOTP for a user
     *
     * @param int $user_id User ID
     * @param string $secret Base32-encoded secret (if null, generates new one)
     * @return array Setup information with QR code data
     */
    public function setup_totp(int $user_id, string $secret = null): array
    {
        // Generate secret if not provided
        if ($secret === null) {
            $secret = $this->generate_secret();
        }

        // Validate secret format
        if (!$this->is_valid_base32($secret)) {
            return ['error' => 'Invalid secret format'];
        }

        // Check if user already has TOTP enabled
        $existing = $this->get_user_totp($user_id);
        if ($existing && $existing['verified']) {
            return ['error' => 'TOTP already enabled for this user'];
        }

        // Get user info for QR code
        $user = $this->get_user_info($user_id);
        if (!$user) {
            return ['error' => 'User not found'];
        }

        // Insert or update TOTP secret (unverified)
        $stmt = $this->db->prepare("
            INSERT INTO " . $this->get_table_name(self::TABLE_TOTP) . "
            (user_id, secret, verified, created_at, updated_at)
            VALUES (:user_id, :secret, 0, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                secret = :secret,
                verified = 0,
                updated_at = NOW()
        ");

        $stmt->execute([
            'user_id' => $user_id,
            'secret' => $secret,
        ]);

        // Generate QR code URI (otpauth:// format)
        $issuer = defined('SITE_NAME') ? SITE_NAME : 'NexoSupport';
        $account = $user['email'] ?? $user['username'];

        $qr_uri = sprintf(
            'otpauth://totp/%s:%s?secret=%s&issuer=%s&digits=%d&period=%d',
            rawurlencode($issuer),
            rawurlencode($account),
            $secret,
            rawurlencode($issuer),
            self::CODE_DIGITS,
            self::TIME_STEP
        );

        // Log setup
        $this->log_audit($user_id, 'totp_setup_initiated', [
            'verified' => false,
        ]);

        return [
            'success' => true,
            'secret' => $secret,
            'qr_uri' => $qr_uri,
            'issuer' => $issuer,
            'account' => $account,
            'message' => 'Scan QR code with your authenticator app and verify with a code',
        ];
    }

    /**
     * Verify TOTP code and enable TOTP for user
     *
     * @param int $user_id User ID
     * @param string $code 6-digit TOTP code
     * @return array Verification result
     */
    public function verify_and_enable(int $user_id, string $code): array
    {
        // Get unverified TOTP
        $totp = $this->get_user_totp($user_id);
        if (!$totp) {
            return ['error' => 'TOTP not set up for this user'];
        }

        if ($totp['verified']) {
            return ['error' => 'TOTP already enabled'];
        }

        // Check lockout
        if ($this->is_locked_out($user_id)) {
            return [
                'error' => 'Too many failed attempts. Locked out for ' .
                          ceil((strtotime($totp['lockout_until']) - time()) / 60) . ' minutes',
            ];
        }

        // Verify code
        if ($this->verify_code($user_id, $code)) {
            // Mark as verified
            $stmt = $this->db->prepare("
                UPDATE " . $this->get_table_name(self::TABLE_TOTP) . "
                SET verified = 1,
                    failed_attempts = 0,
                    lockout_until = NULL,
                    last_used_at = NOW(),
                    updated_at = NOW()
                WHERE user_id = :user_id
            ");
            $stmt->execute(['user_id' => $user_id]);

            $this->log_audit($user_id, 'totp_enabled', ['success' => true]);

            return [
                'success' => true,
                'message' => 'TOTP enabled successfully',
            ];
        } else {
            // Increment failed attempts
            $this->increment_failed_attempts($user_id);

            return ['error' => 'Invalid TOTP code'];
        }
    }

    /**
     * Verify TOTP code
     *
     * @param int $user_id User ID
     * @param string $code 6-digit code from authenticator app
     * @return bool True if code is valid
     */
    public function verify_code(int $user_id, string $code): bool
    {
        // Get user TOTP
        $totp = $this->get_user_totp($user_id);
        if (!$totp) {
            return false;
        }

        // Check lockout
        if ($this->is_locked_out($user_id)) {
            return false;
        }

        // Sanitize code (remove spaces, dashes)
        $code = preg_replace('/\s|-/', '', $code);

        // Validate code format
        if (!preg_match('/^\d{' . self::CODE_DIGITS . '}$/', $code)) {
            return false;
        }

        // Get current time counter
        $current_time = time();
        $current_counter = floor($current_time / self::TIME_STEP);

        // Check code with time drift tolerance (±TIME_DRIFT windows)
        for ($i = -self::TIME_DRIFT; $i <= self::TIME_DRIFT; $i++) {
            $counter = $current_counter + $i;
            $expected_code = $this->generate_totp_code($totp['secret'], $counter);

            if (hash_equals($expected_code, $code)) {
                // Check for replay attack (same counter used twice)
                if ($totp['last_counter'] && $counter <= $totp['last_counter']) {
                    $this->log_audit($user_id, 'totp_replay_detected', [
                        'counter' => $counter,
                        'last_counter' => $totp['last_counter'],
                    ]);
                    return false;
                }

                // Update last used counter
                $this->update_last_counter($user_id, $counter);

                // Log successful verification
                $this->log_audit($user_id, 'totp_verified', ['success' => true]);

                return true;
            }
        }

        // Log failed verification
        $this->log_audit($user_id, 'totp_verification_failed', [
            'code_length' => strlen($code),
        ]);

        return false;
    }

    /**
     * Generate TOTP code for a given counter
     *
     * Implements HOTP (RFC 4226) with time-based counter
     *
     * @param string $secret Base32-encoded secret
     * @param int $counter Time counter
     * @return string 6-digit TOTP code
     */
    private function generate_totp_code(string $secret, int $counter): string
    {
        // Decode Base32 secret to binary
        $secret_binary = $this->base32_decode($secret);

        // Convert counter to 8-byte big-endian format
        $counter_bytes = pack('N*', 0) . pack('N*', $counter);

        // Generate HMAC-SHA1 hash
        $hash = hash_hmac('sha1', $counter_bytes, $secret_binary, true);

        // Dynamic truncation (RFC 4226)
        $offset = ord($hash[19]) & 0x0F;
        $truncated = (
            ((ord($hash[$offset]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF)
        );

        // Generate N-digit code
        $code = $truncated % pow(10, self::CODE_DIGITS);

        // Pad with leading zeros
        return str_pad((string)$code, self::CODE_DIGITS, '0', STR_PAD_LEFT);
    }

    /**
     * Disable TOTP for a user
     *
     * @param int $user_id User ID
     * @return array Result
     */
    public function disable_totp(int $user_id): array
    {
        $stmt = $this->db->prepare("
            DELETE FROM " . $this->get_table_name(self::TABLE_TOTP) . "
            WHERE user_id = :user_id
        ");

        $stmt->execute(['user_id' => $user_id]);

        $this->log_audit($user_id, 'totp_disabled', ['success' => true]);

        return [
            'success' => true,
            'message' => 'TOTP disabled successfully',
        ];
    }

    /**
     * Get user TOTP information
     *
     * @param int $user_id User ID
     * @return array|null TOTP info or null
     */
    public function get_user_totp(int $user_id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM " . $this->get_table_name(self::TABLE_TOTP) . "
            WHERE user_id = :user_id
        ");

        $stmt->execute(['user_id' => $user_id]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Check if user is locked out due to failed attempts
     *
     * @param int $user_id User ID
     * @return bool True if locked out
     */
    private function is_locked_out(int $user_id): bool
    {
        $totp = $this->get_user_totp($user_id);
        if (!$totp) {
            return false;
        }

        if ($totp['lockout_until'] && strtotime($totp['lockout_until']) > time()) {
            return true;
        }

        return false;
    }

    /**
     * Increment failed attempts and apply lockout if necessary
     *
     * @param int $user_id User ID
     */
    private function increment_failed_attempts(int $user_id): void
    {
        $totp = $this->get_user_totp($user_id);
        if (!$totp) {
            return;
        }

        $failed_attempts = $totp['failed_attempts'] + 1;
        $lockout_until = null;

        if ($failed_attempts >= self::MAX_ATTEMPTS) {
            $lockout_until = date('Y-m-d H:i:s', time() + self::LOCKOUT_DURATION);
            $this->log_audit($user_id, 'totp_locked_out', [
                'attempts' => $failed_attempts,
                'lockout_duration' => self::LOCKOUT_DURATION,
            ]);
        }

        $stmt = $this->db->prepare("
            UPDATE " . $this->get_table_name(self::TABLE_TOTP) . "
            SET failed_attempts = :failed_attempts,
                lockout_until = :lockout_until
            WHERE user_id = :user_id
        ");

        $stmt->execute([
            'user_id' => $user_id,
            'failed_attempts' => $failed_attempts,
            'lockout_until' => $lockout_until,
        ]);
    }

    /**
     * Update last used counter (replay attack prevention)
     *
     * @param int $user_id User ID
     * @param int $counter Counter value
     */
    private function update_last_counter(int $user_id, int $counter): void
    {
        $stmt = $this->db->prepare("
            UPDATE " . $this->get_table_name(self::TABLE_TOTP) . "
            SET last_counter = :counter,
                last_used_at = NOW(),
                failed_attempts = 0,
                lockout_until = NULL
            WHERE user_id = :user_id
        ");

        $stmt->execute([
            'user_id' => $user_id,
            'counter' => $counter,
        ]);
    }

    /**
     * Get TOTP statistics
     *
     * @return array Statistics
     */
    public function get_stats(): array
    {
        $stmt = $this->db->query("
            SELECT
                COUNT(*) as total_users,
                SUM(CASE WHEN verified = 1 THEN 1 ELSE 0 END) as verified_users,
                SUM(CASE WHEN verified = 0 THEN 1 ELSE 0 END) as pending_users,
                SUM(CASE WHEN lockout_until IS NOT NULL AND lockout_until > NOW() THEN 1 ELSE 0 END) as locked_out_users
            FROM " . $this->get_table_name(self::TABLE_TOTP) . "
        ");

        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Encode binary data to Base32 (RFC 4648)
     *
     * @param string $data Binary data
     * @return string Base32-encoded string
     */
    private function base32_encode(string $data): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $output = '';
        $v = 0;
        $vbits = 0;

        for ($i = 0, $j = strlen($data); $i < $j; $i++) {
            $v = ($v << 8) | ord($data[$i]);
            $vbits += 8;

            while ($vbits >= 5) {
                $vbits -= 5;
                $output .= $alphabet[($v >> $vbits) & 31];
            }
        }

        if ($vbits > 0) {
            $output .= $alphabet[($v << (5 - $vbits)) & 31];
        }

        return $output;
    }

    /**
     * Decode Base32 to binary (RFC 4648)
     *
     * @param string $data Base32-encoded string
     * @return string Binary data
     */
    private function base32_decode(string $data): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $output = '';
        $v = 0;
        $vbits = 0;
        $data = strtoupper($data);

        for ($i = 0, $j = strlen($data); $i < $j; $i++) {
            $v = ($v << 5) | strpos($alphabet, $data[$i]);
            $vbits += 5;

            if ($vbits >= 8) {
                $vbits -= 8;
                $output .= chr(($v >> $vbits) & 255);
            }
        }

        return $output;
    }

    /**
     * Validate Base32 format
     *
     * @param string $data String to validate
     * @return bool True if valid Base32
     */
    private function is_valid_base32(string $data): bool
    {
        return preg_match('/^[A-Z2-7]+$/', $data) === 1;
    }

    /**
     * Get user information
     *
     * @param int $user_id User ID
     * @return array|null User info
     */
    private function get_user_info(int $user_id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT id, username, email
            FROM users
            WHERE id = :user_id
        ");

        $stmt->execute(['user_id' => $user_id]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $result ?: null;
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
            VALUES (:user_id, 'totp', :event, :details, :ip_address, :user_agent, NOW())
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
