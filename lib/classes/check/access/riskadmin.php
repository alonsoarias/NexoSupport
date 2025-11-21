<?php
/**
 * Check for site administrators.
 *
 * @package    core
 * @subpackage check
 * @copyright  2025 NexoSupport
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\check\access;

use core\check\check;
use core\check\result;

defined('INTERNAL_ACCESS') || die();

/**
 * Lists all site administrators.
 */
class riskadmin extends check {

    protected string $component = 'core';

    /**
     * Get the check name.
     *
     * @return string
     */
    public function get_name(): string {
        return 'Site Administrators';
    }

    /**
     * Execute the check.
     *
     * @return result
     */
    public function get_result(): result {
        global $DB, $CFG;

        $siteadmins = !empty($CFG->siteadmins) ? explode(',', $CFG->siteadmins) : [];
        $siteadmins = array_map('trim', $siteadmins);
        $siteadmins = array_filter($siteadmins);

        if (empty($siteadmins)) {
            return new result(
                result::WARNING,
                'No site administrators configured',
                'The site has no administrators defined. This may indicate a configuration issue.'
            );
        }

        // Get admin user details
        $adminlist = [];
        foreach ($siteadmins as $adminid) {
            $user = $DB->get_record('users', ['id' => $adminid]);
            if ($user) {
                $adminlist[] = htmlspecialchars($user->firstname . ' ' . $user->lastname .
                    ' (' . $user->username . ')');
            }
        }

        $count = count($adminlist);

        $details = '<p>The following users have full administrative access:</p>';
        $details .= '<ul><li>' . implode('</li><li>', $adminlist) . '</li></ul>';
        $details .= '<p>Site administrators bypass all permission checks. ' .
            'Keep this list as small as possible.</p>';

        return new result(
            result::INFO,
            $count . ' site administrator(s)',
            $details
        );
    }

    /**
     * Get action link.
     *
     * @return \action_link|null
     */
    public function get_action_link(): ?\action_link {
        return new \action_link(
            new \nexo_url('/admin/user/user_bulk.php'),
            'Manage users'
        );
    }
}
