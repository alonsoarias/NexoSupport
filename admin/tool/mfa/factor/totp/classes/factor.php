<?php
/**
 * TOTP MFA Factor
 *
 * @package    ISER\Admin\Tool\MFA\Factor\Totp
 * @copyright  2025 ISER
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace ISER\Admin\Tool\MFA\Factor\Totp;

use ISER\Core\Database\Database;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * TOTP factor class (Google Authenticator, Authy, etc.)
 *
 * Implements Time-based One-Time Password authentication.
 */
class factor extends \tool_mfa\local\factor\object_factor_base {

    /**
     * Get database instance
     *
     * @return Database
     */
    protected function get_db(): Database {
        return Database::getInstance();
    }

    /**
     * Get the factor name
     *
     * @return string
     */
    public function get_display_name(): string {
        return get_string('pluginname', 'factor_totp');
    }

    /**
     * Get factor weight (priority)
     *
     * @return int Weight between 0-100
     */
    public function get_weight(): int {
        return 100; // High priority - most secure
    }

    /**
     * Check if factor is enabled globally
     *
     * @return bool
     */
    public function is_enabled(): bool {
        return get_config('factor_totp', 'enabled');
    }

    /**
     * Check if user has this factor configured
     *
     * @param stdClass $user User object
     * @return bool
     */
    public function has_setup($user): bool {
        $db = $this->get_db();
        return $db->record_exists('tool_mfa_factor_totp', ['userid' => $user->id]);
    }

    /**
     * Setup factor for user
     *
     * @param \MoodleQuickForm $mform Form to add elements to
     */
    public function setup_factor_form_definition($mform) {
        global $USER;

        $mform->addElement('static', 'info', '',
            get_string('setupinfo', 'factor_totp'));

        // Generate secret if not exists
        $secret = $this->get_or_create_secret($USER->id);

        // Generate QR code URL
        $qrurl = $this->get_qr_code_url($USER, $secret);

        // Display QR code
        $mform->addElement('static', 'qrcode', '',
            '<img src="' . $qrurl . '" alt="QR Code">');

        // Show secret for manual entry
        $mform->addElement('static', 'secret',
            get_string('secret', 'factor_totp'),
            '<code>' . chunk_split($secret, 4, ' ') . '</code>');

        // Verification field
        $mform->addElement('text', 'verificationcode',
            get_string('verificationcode', 'factor_totp'));
        $mform->setType('verificationcode', PARAM_ALPHANUM);
        $mform->addRule('verificationcode', get_string('required'), 'required', null, 'client');

        return $mform;
    }

    /**
     * Setup factor submission
     *
     * @param \stdClass $data Form data
     * @return bool Success
     */
    public function setup_factor_form_submit($data): bool {
        global $USER;
        $db = $this->get_db();

        $secret = $this->get_or_create_secret($USER->id);

        // Verify the code
        if (!$this->verify_totp_code($secret, $data->verificationcode)) {
            return false;
        }

        // Mark as configured
        $record = $db->get_record('tool_mfa_factor_totp', ['userid' => $USER->id]);
        if ($record) {
            $record->confirmed = 1;
            $db->update_record('tool_mfa_factor_totp', $record);
        }

        return true;
    }

    /**
     * Verification form definition
     *
     * @param \MoodleQuickForm $mform Form to add elements to
     */
    public function verify_form_definition($mform) {
        $mform->addElement('text', 'verificationcode',
            get_string('verificationcode', 'factor_totp'));
        $mform->setType('verificationcode', PARAM_ALPHANUM);
        $mform->addRule('verificationcode', get_string('required'), 'required', null, 'client');

        $mform->addElement('static', 'info', '',
            get_string('verifyinfo', 'factor_totp'));

        return $mform;
    }

    /**
     * Verify the factor code
     *
     * @param stdClass $user User object
     * @param stdClass $data Form data
     * @return bool True if verification successful
     */
    public function verify_factor($user, $data): bool {
        $db = $this->get_db();

        $record = $db->get_record('tool_mfa_factor_totp', ['userid' => $user->id]);
        if (!$record || !$record->confirmed) {
            return false;
        }

        return $this->verify_totp_code($record->secret, $data->verificationcode);
    }

    /**
     * Get or create secret for user
     *
     * @param int $userid User ID
     * @return string Secret key
     */
    protected function get_or_create_secret($userid): string {
        $db = $this->get_db();

        $record = $db->get_record('tool_mfa_factor_totp', ['userid' => $userid]);

        if ($record) {
            return $record->secret;
        }

        // Generate new secret (base32 encoded, 16 bytes = 26 chars)
        $secret = $this->generate_secret();

        // Store in database
        $record = new \stdClass();
        $record->userid = $userid;
        $record->secret = $secret;
        $record->confirmed = 0;
        $record->timecreated = time();

        $db->insert_record('tool_mfa_factor_totp', $record);

        return $secret;
    }

    /**
     * Generate a random base32 secret
     *
     * @return string Base32 encoded secret
     */
    protected function generate_secret(): string {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < 26; $i++) {
            $secret .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $secret;
    }

    /**
     * Get QR code URL for Google Authenticator
     *
     * @param stdClass $user User object
     * @param string $secret Secret key
     * @return string QR code URL
     */
    protected function get_qr_code_url($user, $secret): string {
        global $CFG;

        $issuer = $CFG->sitename ?? 'NexoSupport';
        $label = $user->username . '@' . $issuer;

        $otpauth = sprintf(
            'otpauth://totp/%s?secret=%s&issuer=%s',
            rawurlencode($label),
            $secret,
            rawurlencode($issuer)
        );

        // Use Google Charts API for QR code
        return 'https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl=' .
               rawurlencode($otpauth);
    }

    /**
     * Verify TOTP code
     *
     * @param string $secret Secret key
     * @param string $code User-provided code
     * @return bool True if valid
     */
    protected function verify_totp_code($secret, $code): bool {
        // Allow ±1 time window (30 seconds before/after) for clock drift
        $timestamp = time();
        $timewindow = 30;

        for ($i = -1; $i <= 1; $i++) {
            $t = floor(($timestamp + ($i * $timewindow)) / $timewindow);
            $expectedcode = $this->generate_totp_code($secret, $t);

            if ($code === $expectedcode) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate TOTP code for a time value
     *
     * @param string $secret Base32 secret
     * @param int $time Time counter
     * @return string 6-digit code
     */
    protected function generate_totp_code($secret, $time): string {
        $key = $this->base32_decode($secret);
        $time = pack('N*', 0) . pack('N*', $time);
        $hash = hash_hmac('sha1', $time, $key, true);
        $offset = ord($hash[19]) & 0xf;
        $code = (
            ((ord($hash[$offset + 0]) & 0x7f) << 24) |
            ((ord($hash[$offset + 1]) & 0xff) << 16) |
            ((ord($hash[$offset + 2]) & 0xff) << 8) |
            (ord($hash[$offset + 3]) & 0xff)
        ) % 1000000;

        return str_pad($code, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Decode base32 string
     *
     * @param string $secret Base32 string
     * @return string Binary data
     */
    protected function base32_decode($secret): string {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = strtoupper($secret);
        $paddingCharCount = substr_count($secret, '=');
        $allowedValues = [6, 4, 3, 1, 0];

        if (!in_array($paddingCharCount, $allowedValues)) {
            return '';
        }

        for ($i = 0; $i < 4; $i++) {
            if ($paddingCharCount == $allowedValues[$i] &&
                substr($secret, -($allowedValues[$i])) != str_repeat('=', $allowedValues[$i])) {
                return '';
            }
        }

        $secret = str_replace('=', '', $secret);
        $secret = str_split($secret);
        $binaryString = '';

        for ($i = 0; $i < count($secret); $i = $i + 8) {
            $x = '';
            if (!in_array($secret[$i], str_split($chars))) {
                return '';
            }
            for ($j = 0; $j < 8; $j++) {
                $x .= str_pad(base_convert(@strpos($chars, @$secret[$i + $j]), 10, 2), 5, '0', STR_PAD_LEFT);
            }
            $eightBits = str_split($x, 8);
            for ($z = 0; $z < count($eightBits); $z++) {
                $binaryString .= (($y = chr(base_convert($eightBits[$z], 2, 10))) || ord($y) == 48) ? $y : '';
            }
        }

        return $binaryString;
    }

    /**
     * Possible states for this factor
     *
     * @param stdClass $user User object
     * @return array States
     */
    public function possible_states($user): array {
        if (!$this->has_setup($user)) {
            return [\tool_mfa\plugininfo\factor::STATE_NEUTRAL];
        }

        return [
            \tool_mfa\plugininfo\factor::STATE_PASS,
            \tool_mfa\plugininfo\factor::STATE_FAIL,
        ];
    }
}
