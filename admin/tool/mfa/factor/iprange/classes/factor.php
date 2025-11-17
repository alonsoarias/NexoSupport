<?php
/**
 * IP Range MFA Factor
 *
 * @package    ISER\Admin\Tool\MFA\Factor\IpRange
 * @copyright  2025 ISER
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace ISER\Admin\Tool\MFA\Factor\IpRange;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * IP Range factor class
 *
 * Validates user login based on IP address ranges.
 */
class factor extends \tool_mfa\local\factor\object_factor_base {

    public function get_display_name(): string {
        return get_string('pluginname', 'factor_iprange');
    }

    public function get_weight(): int {
        return 30; // Low-medium priority
    }

    public function is_enabled(): bool {
        return get_config('factor_iprange', 'enabled');
    }

    public function has_setup($user): bool {
        // IP range is configured globally, not per user
        return true;
    }

    public function setup_factor_form_definition($mform) {
        $mform->addElement('static', 'info', '',
            get_string('setupinfo', 'factor_iprange'));
        return $mform;
    }

    public function setup_factor_form_submit($data): bool {
        // No per-user setup needed
        return true;
    }

    public function verify_form_definition($mform) {
        // IP verification is automatic, no form needed
        $mform->addElement('static', 'info', '',
            get_string('verifyinfo', 'factor_iprange'));
        return $mform;
    }

    public function verify_factor($user, $data): bool {
        $userip = getremoteaddr();
        $allowedranges = get_config('factor_iprange', 'allowed_ranges');

        if (empty($allowedranges)) {
            return false;
        }

        $ranges = explode("\n", $allowedranges);
        foreach ($ranges as $range) {
            $range = trim($range);
            if (empty($range)) {
                continue;
            }

            if ($this->ip_in_range($userip, $range)) {
                return true;
            }
        }

        return false;
    }

    protected function ip_in_range($ip, $range): bool {
        if (strpos($range, '/') === false) {
            // Single IP
            return $ip === $range;
        }

        // CIDR notation
        list($subnet, $mask) = explode('/', $range);
        $ip_long = ip2long($ip);
        $subnet_long = ip2long($subnet);
        $mask_long = ~((1 << (32 - $mask)) - 1);

        return ($ip_long & $mask_long) === ($subnet_long & $mask_long);
    }

    public function possible_states($user): array {
        return [
            \tool_mfa\plugininfo\factor::STATE_PASS,
            \tool_mfa\plugininfo\factor::STATE_FAIL,
            \tool_mfa\plugininfo\factor::STATE_NEUTRAL,
        ];
    }
}
