<?php
/**
 * SMS MFA Factor
 *
 * @package    ISER\Admin\Tool\MFA\Factor\Sms
 * @copyright  2025 ISER
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace ISER\Admin\Tool\MFA\Factor\Sms;

use ISER\Core\Database\Database;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * SMS factor class
 *
 * Sends verification codes via SMS.
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

    public function get_display_name(): string {
        return get_string('pluginname', 'factor_sms');
    }

    public function get_weight(): int {
        return 80; // High priority
    }

    public function is_enabled(): bool {
        return get_config('factor_sms', 'enabled');
    }

    public function has_setup($user): bool {
        $db = $this->get_db();
        return $db->record_exists('tool_mfa_factor_sms', ['userid' => $user->id]);
    }

    public function setup_factor_form_definition($mform) {
        $mform->addElement('text', 'phone',
            get_string('phonenumber', 'factor_sms'));
        $mform->setType('phone', PARAM_TEXT);
        $mform->addRule('phone', get_string('required'), 'required', null, 'client');

        $mform->addElement('static', 'info', '',
            get_string('setupinfo', 'factor_sms'));

        return $mform;
    }

    public function setup_factor_form_submit($data): bool {
        global $USER;
        $db = $this->get_db();

        // Normalize phone number
        $phone = preg_replace('/[^0-9+]/', '', $data->phone);

        // Store phone number
        $record = $db->get_record('tool_mfa_factor_sms', ['userid' => $USER->id]);
        if ($record) {
            $record->phone = $phone;
            $db->update_record('tool_mfa_factor_sms', $record);
        } else {
            $record = new \stdClass();
            $record->userid = $USER->id;
            $record->phone = $phone;
            $record->timecreated = time();
            $db->insert_record('tool_mfa_factor_sms', $record);
        }

        return true;
    }

    public function verify_form_definition($mform) {
        $mform->addElement('text', 'verificationcode',
            get_string('verificationcode', 'factor_sms'));
        $mform->setType('verificationcode', PARAM_ALPHANUM);
        $mform->addRule('verificationcode', get_string('required'), 'required', null, 'client');

        $mform->addElement('static', 'info', '',
            get_string('verifyinfo', 'factor_sms'));

        return $mform;
    }

    public function verify_factor($user, $data): bool {
        $db = $this->get_db();

        $record = $db->get_record('tool_mfa_factor_sms_codes', [
            'userid' => $user->id,
        ], '*', IGNORE_MULTIPLE);

        if (!$record) {
            return false;
        }

        // Check if code has expired (valid for 5 minutes)
        if (time() - $record->timecreated > 300) {
            return false;
        }

        $valid = ($data->verificationcode === $record->code);

        if ($valid) {
            $db->delete_records('tool_mfa_factor_sms_codes', ['id' => $record->id]);
        }

        return $valid;
    }

    public function request_verification($user): bool {
        $code = $this->generate_code($user->id);
        return $this->send_sms($user, $code);
    }

    protected function generate_code($userid): string {
        $db = $this->get_db();

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $record = new \stdClass();
        $record->userid = $userid;
        $record->code = $code;
        $record->timecreated = time();

        $db->delete_records('tool_mfa_factor_sms_codes', ['userid' => $userid]);
        $db->insert_record('tool_mfa_factor_sms_codes', $record);

        return $code;
    }

    protected function send_sms($user, $code): bool {
        $db = $this->get_db();

        $userrecord = $db->get_record('tool_mfa_factor_sms', ['userid' => $user->id]);
        if (!$userrecord) {
            return false;
        }

        $phone = $userrecord->phone;
        $message = get_string('smsbody', 'factor_sms', ['code' => $code]);

        // TODO: Integrate with SMS provider (Twilio, AWS SNS, etc.)
        // For now, log the SMS
        error_log("SMS to {$phone}: {$message}");

        return true;
    }

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
