<?php
/**
 * Email MFA Factor
 *
 * @package    ISER\Admin\Tool\MFA\Factor\Email
 * @copyright  2025 ISER
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace ISER\Admin\Tool\MFA\Factor\Email;

use ISER\Core\Database\Database;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Email factor class
 *
 * Sends a verification code via email for multi-factor authentication.
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
        return get_string('pluginname', 'factor_email');
    }

    /**
     * Get factor weight (priority)
     *
     * Higher numbers = higher priority
     *
     * @return int Weight between 0-100
     */
    public function get_weight(): int {
        return 50; // Medium priority
    }

    /**
     * Check if factor is enabled globally
     *
     * @return bool
     */
    public function is_enabled(): bool {
        return get_config('factor_email', 'enabled');
    }

    /**
     * Check if user has this factor configured
     *
     * @param stdClass $user User object
     * @return bool
     */
    public function has_setup($user): bool {
        // Email factor is configured if user has an email
        return !empty($user->email);
    }

    /**
     * Setup factor for user
     *
     * Adds elements to the factor setup form
     *
     * @param \MoodleQuickForm $mform Form to add elements to
     */
    public function setup_factor_form_definition($mform) {
        global $USER;

        $mform->addElement('static', 'info', '',
            get_string('setupinfo', 'factor_email'));

        // Show current email
        $mform->addElement('static', 'current_email',
            get_string('currentemail', 'factor_email'),
            $USER->email);

        // Option to send test code
        $mform->addElement('checkbox', 'sendtestcode',
            get_string('sendtestcode', 'factor_email'));

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

        if (!empty($data->sendtestcode)) {
            // Send test code
            $code = $this->generate_code($USER->id);
            $this->send_code($USER, $code);
        }

        // Email factor is automatically configured if user has email
        return true;
    }

    /**
     * Verification form definition
     *
     * @param \MoodleQuickForm $mform Form to add elements to
     */
    public function verify_form_definition($mform) {
        $mform->addElement('text', 'verificationcode',
            get_string('verificationcode', 'factor_email'));
        $mform->setType('verificationcode', PARAM_ALPHANUM);
        $mform->addRule('verificationcode', get_string('required'), 'required', null, 'client');

        $mform->addElement('static', 'info', '',
            get_string('verifyinfo', 'factor_email'));

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

        // Get the stored code
        $record = $db->get_record('tool_mfa_factor_email', [
            'userid' => $user->id,
        ], '*', IGNORE_MULTIPLE);

        if (!$record) {
            return false;
        }

        // Check if code has expired (valid for 10 minutes)
        if (time() - $record->timecreated > 600) {
            return false;
        }

        // Verify the code
        $valid = ($data->verificationcode === $record->code);

        // Delete used code
        if ($valid) {
            $db->delete_records('tool_mfa_factor_email', ['id' => $record->id]);
        }

        return $valid;
    }

    /**
     * Request verification (send code)
     *
     * @param stdClass $user User object
     * @return bool Success
     */
    public function request_verification($user): bool {
        $code = $this->generate_code($user->id);
        return $this->send_code($user, $code);
    }

    /**
     * Generate a random 6-digit code
     *
     * @param int $userid User ID
     * @return string 6-digit code
     */
    protected function generate_code($userid): string {
        $db = $this->get_db();

        // Generate random 6-digit code
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Store code in database
        $record = new \stdClass();
        $record->userid = $userid;
        $record->code = $code;
        $record->timecreated = time();

        // Delete old codes for this user
        $db->delete_records('tool_mfa_factor_email', ['userid' => $userid]);

        // Insert new code
        $db->insert_record('tool_mfa_factor_email', $record);

        return $code;
    }

    /**
     * Send verification code via email
     *
     * @param stdClass $user User object
     * @param string $code Verification code
     * @return bool Success
     */
    protected function send_code($user, $code): bool {
        global $CFG;

        $subject = get_string('emailsubject', 'factor_email');
        $message = get_string('emailbody', 'factor_email', [
            'code' => $code,
            'sitename' => $CFG->sitename ?? 'NexoSupport',
        ]);

        return email_to_user($user, get_admin(), $subject, $message);
    }

    /**
     * Check if factor should be in revoked state
     *
     * @param stdClass $user User object
     * @return bool
     */
    public function possible_states($user): array {
        if (empty($user->email)) {
            return [\tool_mfa\plugininfo\factor::STATE_NEUTRAL];
        }

        return [
            \tool_mfa\plugininfo\factor::STATE_PASS,
            \tool_mfa\plugininfo\factor::STATE_FAIL,
            \tool_mfa\plugininfo\factor::STATE_NEUTRAL,
        ];
    }
}
