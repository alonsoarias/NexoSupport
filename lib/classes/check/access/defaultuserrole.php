<?php
/**
 * Check for default user role settings.
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
 * Checks the default role assigned to new users.
 */
class defaultuserrole extends check {

    protected string $component = 'core';

    /**
     * Get the check name.
     *
     * @return string
     */
    public function get_name(): string {
        return 'Default User Role';
    }

    /**
     * Execute the check.
     *
     * @return result
     */
    public function get_result(): result {
        global $DB;

        $defaultroleid = get_config('core', 'defaultuserroleid');

        if (empty($defaultroleid)) {
            return new result(
                result::OK,
                'No default role assigned to new users',
                'New users are not automatically assigned any role beyond basic authenticated user.'
            );
        }

        $role = $DB->get_record('roles', ['id' => $defaultroleid]);

        if (!$role) {
            return new result(
                result::WARNING,
                'Default role not found',
                'The configured default role (ID: ' . $defaultroleid . ') does not exist.'
            );
        }

        // Check if the role has any risky capabilities
        $riskyCapabilities = $DB->get_records_sql(
            "SELECT rc.* FROM {role_capabilities} rc
             WHERE rc.roleid = ? AND rc.permission = 1
             AND rc.capability IN (
                 'core/site:config',
                 'core/user:delete',
                 'core/role:manage'
             )",
            [$defaultroleid]
        );

        if (!empty($riskyCapabilities)) {
            return new result(
                result::CRITICAL,
                'Default role has risky capabilities',
                'The default role "' . htmlspecialchars($role->shortname) . '" has administrative capabilities. ' .
                'This means all new users would receive elevated privileges!'
            );
        }

        return new result(
            result::INFO,
            'Default role: ' . htmlspecialchars($role->shortname),
            'New users are assigned the "' . htmlspecialchars($role->name) . '" role. ' .
            'Ensure this role has only appropriate permissions for new users.'
        );
    }

    /**
     * Get action link.
     *
     * @return \action_link|null
     */
    public function get_action_link(): ?\action_link {
        return new \action_link(
            new \nexo_url('/admin/roles/'),
            'Manage roles'
        );
    }
}
