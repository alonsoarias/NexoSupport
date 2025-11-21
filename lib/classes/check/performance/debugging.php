<?php
/**
 * Check for debugging settings.
 *
 * @package    core
 * @subpackage check
 * @copyright  2025 NexoSupport
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\check\performance;

use core\check\check;
use core\check\result;

defined('INTERNAL_ACCESS') || die();

/**
 * Checks if debugging mode is enabled.
 *
 * Debugging should be disabled in production for performance and security.
 */
class debugging extends check {

    protected string $component = 'core';

    /**
     * Get the check name.
     *
     * @return string
     */
    public function get_name(): string {
        return 'Debug Mode';
    }

    /**
     * Execute the check.
     *
     * @return result
     */
    public function get_result(): result {
        global $CFG;

        $debug = $CFG->debug ?? 0;
        $debugdisplay = $CFG->debugdisplay ?? false;

        // Check for developer mode
        if ($debug >= 32767 || !empty($CFG->debugdeveloper)) {
            $status = result::WARNING;
            $summary = 'Developer debugging is enabled';
            $details = 'Full debugging is enabled, which impacts performance and may expose ' .
                'sensitive information. Disable debug mode in production environments.';
        } elseif ($debug > 0) {
            $status = result::INFO;
            $summary = 'Some debugging is enabled (level: ' . $debug . ')';
            $details = 'Partial debugging is enabled. For optimal performance, set debug to 0.';
        } else {
            $status = result::OK;
            $summary = 'Debugging is disabled';
            $details = 'Debug mode is off, which is optimal for production.';
        }

        if ($debugdisplay && $debug > 0) {
            $status = result::WARNING;
            $summary .= ' (displayed to users)';
            $details .= ' Debug messages are being displayed to users.';
        }

        return new result($status, $summary, $details);
    }

    /**
     * Get action link.
     *
     * @return \action_link|null
     */
    public function get_action_link(): ?\action_link {
        return new \action_link(
            new \nexo_url('/admin/settings.php', ['section' => 'debugging']),
            'Debugging settings'
        );
    }
}
