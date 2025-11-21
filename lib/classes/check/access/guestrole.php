<?php
/**
 * Check for guest role configuration.
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
 * Checks the guest role configuration.
 */
class guestrole extends check {

    protected string $component = 'core';

    /**
     * Get the check name.
     *
     * @return string
     */
    public function get_name(): string {
        return 'Guest Role';
    }

    /**
     * Execute the check.
     *
     * @return result
     */
    public function get_result(): result {
        global $DB;

        $guestroleid = get_config('core', 'guestroleid');

        if (empty($guestroleid)) {
            return new result(
                result::OK,
                'Guest access is disabled',
                'No guest role is configured, meaning anonymous access is not available.'
            );
        }

        $role = $DB->get_record('roles', ['id' => $guestroleid]);

        if (!$role) {
            return new result(
                result::WARNING,
                'Guest role not found',
                'The configured guest role (ID: ' . $guestroleid . ') does not exist.'
            );
        }

        // Check if guest role has any write capabilities
        $writecaps = $DB->get_records_sql(
            "SELECT rc.* FROM {role_capabilities} rc
             WHERE rc.roleid = ? AND rc.permission = 1
             AND (rc.capability LIKE '%:write%'
                  OR rc.capability LIKE '%:create%'
                  OR rc.capability LIKE '%:delete%'
                  OR rc.capability LIKE '%:update%')",
            [$guestroleid]
        );

        if (!empty($writecaps)) {
            return new result(
                result::CRITICAL,
                'Guest role has write capabilities',
                'The guest role has permissions that allow modifying content. ' .
                'Guest users should only have read-only access.'
            );
        }

        return new result(
            result::INFO,
            'Guest role: ' . htmlspecialchars($role->shortname),
            'Guest users are assigned the "' . htmlspecialchars($role->name) . '" role. ' .
            'Ensure this role has minimal, read-only permissions.'
        );
    }

    /**
     * Get action link.
     *
     * @return \action_link|null
     */
    public function get_action_link(): ?\action_link {
        return new \action_link(
            new \moodle_url('/admin/roles/'),
            'Manage roles'
        );
    }
}
