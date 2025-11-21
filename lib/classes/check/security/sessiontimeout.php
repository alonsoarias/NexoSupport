<?php
/**
 * Check for session timeout settings.
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
 * Checks if session timeout is appropriately configured.
 */
class sessiontimeout extends check {

    protected string $component = 'core';

    /**
     * Get the check name.
     *
     * @return string
     */
    public function get_name(): string {
        return 'Session Timeout';
    }

    /**
     * Execute the check.
     *
     * @return result
     */
    public function get_result(): result {
        $sessiontimeout = get_config('core', 'sessiontimeout');

        if (empty($sessiontimeout)) {
            $sessiontimeout = 7200; // Default 2 hours
        }

        // Convert to hours for readability
        $hours = $sessiontimeout / 3600;

        if ($sessiontimeout > 86400) { // More than 24 hours
            return new result(
                result::WARNING,
                'Session timeout is very long (' . round($hours, 1) . ' hours)',
                'Sessions that remain valid for extended periods increase the risk of session hijacking. ' .
                'Consider reducing the timeout to 2-8 hours.'
            );
        }

        if ($sessiontimeout < 300) { // Less than 5 minutes
            return new result(
                result::WARNING,
                'Session timeout is very short (' . ($sessiontimeout / 60) . ' minutes)',
                'Very short session timeouts may frustrate users with frequent re-authentication.'
            );
        }

        return new result(
            result::OK,
            'Session timeout is reasonable (' . round($hours, 1) . ' hours)',
            'The session timeout is set to an appropriate duration.'
        );
    }

    /**
     * Get action link.
     *
     * @return \action_link|null
     */
    public function get_action_link(): ?\action_link {
        return new \action_link(
            new \nexo_url('/admin/settings.php', ['section' => 'sessionhandling']),
            'Session settings'
        );
    }
}
