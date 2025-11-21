<?php
/**
 * Check for display_errors PHP setting.
 *
 * @package    core
 * @subpackage check
 * @copyright  2025 NexoSupport
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\check\environment;

use core\check\check;
use core\check\result;

defined('INTERNAL_ACCESS') || die();

/**
 * Checks if PHP display_errors is disabled.
 *
 * Display errors should be off in production to prevent information leakage.
 */
class displayerrors extends check {

    protected string $component = 'core';

    /**
     * Get the check name.
     *
     * @return string
     */
    public function get_name(): string {
        return 'PHP Display Errors';
    }

    /**
     * Execute the check.
     *
     * @return result
     */
    public function get_result(): result {
        $displayerrors = ini_get('display_errors');

        if (!$displayerrors || $displayerrors === 'Off' || $displayerrors === '0') {
            return new result(
                result::OK,
                'PHP display_errors is disabled',
                'Error messages are not displayed to users, which is the recommended setting for production.'
            );
        }

        return new result(
            result::WARNING,
            'PHP display_errors is enabled',
            'Error messages may be displayed to users. This can leak sensitive information ' .
            'about your server configuration and should be disabled in production environments. ' .
            'Set <code>display_errors = Off</code> in php.ini.'
        );
    }

    /**
     * Get action link.
     *
     * @return \action_link|null
     */
    public function get_action_link(): ?\action_link {
        global $CFG;
        return new \action_link(
            new \nexo_url('/admin/settings.php', ['section' => 'server']),
            'Server settings'
        );
    }
}
