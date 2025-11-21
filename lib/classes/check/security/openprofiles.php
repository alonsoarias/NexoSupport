<?php
/**
 * Check for open user profiles.
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
 * Checks if user profiles are accessible without login.
 */
class openprofiles extends check {

    protected string $component = 'core';

    /**
     * Get the check name.
     *
     * @return string
     */
    public function get_name(): string {
        return 'Open User Profiles';
    }

    /**
     * Execute the check.
     *
     * @return result
     */
    public function get_result(): result {
        $forcelogin = get_config('core', 'forcelogin');
        $forceloginforprofiles = get_config('core', 'forceloginforprofiles');

        if (!empty($forcelogin)) {
            return new result(
                result::OK,
                'Login is required to access the site',
                'Force login is enabled, so all content including profiles requires authentication.'
            );
        }

        if (!empty($forceloginforprofiles)) {
            return new result(
                result::OK,
                'Login is required to view profiles',
                'While the site is publicly accessible, user profiles require authentication.'
            );
        }

        return new result(
            result::WARNING,
            'User profiles may be publicly accessible',
            'Neither force login nor force login for profiles is enabled. ' .
            'User profile information may be visible to unauthenticated visitors.'
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
