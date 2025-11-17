<?php
/**
 * Backup Codes MFA Factor
 *
 * @package    ISER\Admin\Tool\MFA\Factor\BackupCodes
 * @copyright  2025 ISER
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace ISER\Admin\Tool\MFA\Factor\BackupCodes;

use ISER\Core\Database\Database;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Backup codes factor class
 *
 * Provides one-time backup codes for account recovery.
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

    /** Number of backup codes to generate */
    const NUM_CODES = 10;

    /** Length of each backup code */
    const CODE_LENGTH = 8;

    public function get_display_name(): string {
        return get_string('pluginname', 'factor_backupcodes');
    }

    public function get_weight(): int {
        return 10; // Lowest priority - backup only
    }

    public function is_enabled(): bool {
        return get_config('factor_backupcodes', 'enabled');
    }

    public function has_setup($user): bool {
        $db = $this->get_db();
        return $db->count_records('tool_mfa_factor_backupcodes', [
            'userid' => $user->id,
            'used' => 0,
        ]) > 0;
    }

    public function setup_factor_form_definition($mform) {
        global $USER;
        $db = $this->get_db();

        $mform->addElement('static', 'info', '',
            get_string('setupinfo', 'factor_backupcodes'));

        // Count remaining codes
        $remaining = $db->count_records('tool_mfa_factor_backupcodes', [
            'userid' => $USER->id,
            'used' => 0,
        ]);

        if ($remaining > 0) {
            $mform->addElement('static', 'remaining',
                get_string('remainingcodes', 'factor_backupcodes'),
                $remaining . ' / ' . self::NUM_CODES);

            $mform->addElement('checkbox', 'regenerate',
                get_string('regenerate', 'factor_backupcodes'));
        } else {
            $mform->addElement('checkbox', 'generate',
                get_string('generatecodes', 'factor_backupcodes'));
            $mform->setDefault('generate', 1);
        }

        return $mform;
    }

    public function setup_factor_form_submit($data): bool {
        global $USER;
        $db = $this->get_db();

        if (!empty($data->generate) || !empty($data->regenerate)) {
            // Delete old codes
            $db->delete_records('tool_mfa_factor_backupcodes', ['userid' => $USER->id]);

            // Generate new codes
            $codes = $this->generate_codes($USER->id);

            // Display codes to user (they should save them)
            // This would typically be handled by the form display
            return true;
        }

        return true;
    }

    public function verify_form_definition($mform) {
        $mform->addElement('text', 'backupcode',
            get_string('enterbackupcode', 'factor_backupcodes'));
        $mform->setType('backupcode', PARAM_ALPHANUM);
        $mform->addRule('backupcode', get_string('required'), 'required', null, 'client');

        $mform->addElement('static', 'info', '',
            get_string('verifyinfo', 'factor_backupcodes'));

        return $mform;
    }

    public function verify_factor($user, $data): bool {
        $db = $this->get_db();

        $code = strtoupper(str_replace(' ', '', $data->backupcode));

        $record = $db->get_record('tool_mfa_factor_backupcodes', [
            'userid' => $user->id,
            'code' => $code,
            'used' => 0,
        ]);

        if (!$record) {
            return false;
        }

        // Mark code as used
        $record->used = 1;
        $record->timeused = time();
        $db->update_record('tool_mfa_factor_backupcodes', $record);

        return true;
    }

    /**
     * Generate backup codes for user
     *
     * @param int $userid User ID
     * @return array Array of generated codes
     */
    protected function generate_codes($userid): array {
        $db = $this->get_db();

        $codes = [];

        for ($i = 0; $i < self::NUM_CODES; $i++) {
            $code = $this->generate_random_code();

            $record = new \stdClass();
            $record->userid = $userid;
            $record->code = $code;
            $record->used = 0;
            $record->timecreated = time();

            $db->insert_record('tool_mfa_factor_backupcodes', $record);

            $codes[] = $code;
        }

        return $codes;
    }

    /**
     * Generate a random backup code
     *
     * @return string Backup code
     */
    protected function generate_random_code(): string {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $code = '';

        for ($i = 0; $i < self::CODE_LENGTH; $i++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }

        // Format as XXXX-XXXX
        return substr($code, 0, 4) . '-' . substr($code, 4, 4);
    }

    /**
     * Get unused backup codes for user
     *
     * @param stdClass $user User object
     * @return array Array of unused codes
     */
    public function get_unused_codes($user): array {
        $db = $this->get_db();

        $records = $db->get_records('tool_mfa_factor_backupcodes', [
            'userid' => $user->id,
            'used' => 0,
        ]);

        return array_column($records, 'code');
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
