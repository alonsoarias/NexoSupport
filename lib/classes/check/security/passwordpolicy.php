<?php
/**
 * Check for password policy settings.
 *
 * @package    core
 * @subpackage check
 * @copyright  2025 NexoSupport
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\check\security;

use core\check\check;
use core\check\result;

defined('INTERNAL_ACCESS') || die();

/**
 * Checks if a strong password policy is enabled.
 */
class passwordpolicy extends check {

    protected string $component = 'core';

    /**
     * Get the check name.
     *
     * @return string
     */
    public function get_name(): string {
        return 'Password Policy';
    }

    /**
     * Execute the check.
     *
     * @return result
     */
    public function get_result(): result {
        $passwordpolicy = get_config('core', 'passwordpolicy');
        $minlength = get_config('core', 'minpasswordlength') ?: 8;
        $requiredigits = get_config('core', 'passwordrequiredigits');
        $requirelower = get_config('core', 'passwordrequirelower');
        $requireupper = get_config('core', 'passwordrequireupper');

        $issues = [];

        if (empty($passwordpolicy)) {
            return new result(
                result::WARNING,
                'Password policy is disabled',
                'Strong password requirements are not enforced. Enable password policy in security settings ' .
                'to require complex passwords.'
            );
        }

        if ($minlength < 8) {
            $issues[] = 'Minimum password length is less than 8 characters';
        }

        if (empty($requiredigits)) {
            $issues[] = 'Digits are not required in passwords';
        }

        if (empty($requirelower)) {
            $issues[] = 'Lowercase letters are not required';
        }

        if (empty($requireupper)) {
            $issues[] = 'Uppercase letters are not required';
        }

        if (!empty($issues)) {
            return new result(
                result::INFO,
                'Password policy could be stronger',
                'Password policy is enabled but could be improved:<ul><li>' .
                implode('</li><li>', $issues) . '</li></ul>'
            );
        }

        return new result(
            result::OK,
            'Strong password policy is enabled',
            'Password policy requires a minimum of ' . $minlength . ' characters with digits, ' .
            'uppercase, and lowercase letters.'
        );
    }

    /**
     * Get action link.
     *
     * @return \action_link|null
     */
    public function get_action_link(): ?\action_link {
        return new \action_link(
            new \moodle_url('/admin/settings.php', ['section' => 'security']),
            'Security settings'
        );
    }
}
