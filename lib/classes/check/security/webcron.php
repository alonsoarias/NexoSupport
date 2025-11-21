<?php
/**
 * Check for web-accessible cron.
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
 * Checks if the cron is accessible via web.
 */
class webcron extends check {

    protected string $component = 'core';

    /**
     * Get the check name.
     *
     * @return string
     */
    public function get_name(): string {
        return 'Web Accessible Cron';
    }

    /**
     * Execute the check.
     *
     * @return result
     */
    public function get_result(): result {
        $cronclionly = get_config('core', 'cronclionly');
        $cronremotepassword = get_config('core', 'cronremotepassword');

        if (!empty($cronclionly)) {
            return new result(
                result::OK,
                'Cron is CLI only',
                'The cron can only be executed from the command line, which is the most secure option.'
            );
        }

        if (!empty($cronremotepassword)) {
            return new result(
                result::WARNING,
                'Web cron is enabled with password',
                'The cron is accessible via web but protected with a password. ' .
                'Consider using CLI cron for better security.'
            );
        }

        return new result(
            result::WARNING,
            'Web cron may be unprotected',
            'The cron might be accessible via web without protection. ' .
            'Enable CLI-only cron or set a strong password for web cron access.'
        );
    }

    /**
     * Get action link.
     *
     * @return \action_link|null
     */
    public function get_action_link(): ?\action_link {
        return new \action_link(
            new \nexo_url('/admin/settings.php', ['section' => 'server']),
            'Server settings'
        );
    }
}
