<?php
namespace core\rbac;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Access Control System
 *
 * Maneja la verificación de capabilities y permisos.
 *
 * @package core\rbac
 */
class access {

    /** Permission values */
    const PERMISSION_PROHIBIT = -1000;
    const PERMISSION_PREVENT = -1;
    const PERMISSION_INHERIT = 0;
    const PERMISSION_ALLOW = 1;

    /** @var array Cache de capabilities */
    private static array $cache = [];

    /**
     * Check if user has capability
     *
     * @param string $capability
     * @param int|null $userid
     * @param context|null $context
     * @return bool
     */
    public static function has_capability(string $capability, ?int $userid = null, ?context $context = null): bool {
        global $USER, $DB;

        // Default user
        if ($userid === null) {
            $userid = $USER->id ?? 0;
        }

        // Guest user has no capabilities
        if ($userid == 0) {
            return false;
        }

        // Default context
        if ($context === null) {
            $context = context::system();
        }

        // Check cache
        $cachekey = "{$userid}_{$capability}_{$context->id}";
        if (isset(self::$cache[$cachekey])) {
            return self::$cache[$cachekey];
        }

        // Get user roles in this context and parent contexts
        $roles = self::get_user_roles_in_context($userid, $context);

        if (empty($roles)) {
            self::$cache[$cachekey] = false;
            return false;
        }

        // Get all capabilities for these roles
        $permission = self::get_permission_for_roles($roles, $capability, $context);

        $result = ($permission == self::PERMISSION_ALLOW);

        // Cache result
        self::$cache[$cachekey] = $result;

        return $result;
    }

    /**
     * Get user roles in context (including parent contexts)
     *
     * @param int $userid
     * @param context $context
     * @return array Array of role IDs
     */
    protected static function get_user_roles_in_context(int $userid, context $context): array {
        global $DB;

        // Get context path (self + parents)
        $contextids = $context->get_path_array();

        if (empty($contextids)) {
            $contextids = [$context->id];
        }

        // Get role assignments for this user in these contexts
        list($insql, $params) = $DB->get_in_or_equal($contextids);
        $params[] = $userid;

        $sql = "SELECT DISTINCT roleid
                FROM {role_assignments}
                WHERE contextid $insql
                  AND userid = ?";

        $records = $DB->get_records_sql($sql, $params);

        $roleids = [];
        foreach ($records as $record) {
            $roleids[] = (int)$record->roleid;
        }

        return $roleids;
    }

    /**
     * Get permission for capability across roles
     *
     * @param array $roleids
     * @param string $capability
     * @param context $context
     * @return int Permission value
     */
    protected static function get_permission_for_roles(array $roleids, string $capability, context $context): int {
        global $DB;

        if (empty($roleids)) {
            return self::PERMISSION_INHERIT;
        }

        // Get context path (for inheritance)
        $contextids = $context->get_path_array();
        if (empty($contextids)) {
            $contextids = [$context->id];
        }

        // Get all role_capabilities for these roles in these contexts
        list($roleinsql, $roleparams) = $DB->get_in_or_equal($roleids);
        list($contextinsql, $contextparams) = $DB->get_in_or_equal($contextids);

        $params = array_merge($roleparams, $contextparams);
        $params[] = $capability;

        $sql = "SELECT *
                FROM {role_capabilities}
                WHERE roleid $roleinsql
                  AND contextid $contextinsql
                  AND capability = ?
                ORDER BY permission DESC";

        $records = $DB->get_records_sql($sql, $params);

        // Process permissions
        $permission = self::PERMISSION_INHERIT;

        foreach ($records as $record) {
            $perm = (int)$record->permission;

            // PROHIBIT always wins
            if ($perm == self::PERMISSION_PROHIBIT) {
                return self::PERMISSION_PROHIBIT;
            }

            // ALLOW > PREVENT > INHERIT
            if ($perm == self::PERMISSION_ALLOW) {
                $permission = self::PERMISSION_ALLOW;
            } else if ($perm == self::PERMISSION_PREVENT && $permission != self::PERMISSION_ALLOW) {
                $permission = self::PERMISSION_PREVENT;
            }
        }

        return $permission;
    }

    /**
     * Assign role to user
     *
     * @param int $roleid
     * @param int $userid
     * @param context $context
     * @return int Assignment ID
     */
    public static function assign_role(int $roleid, int $userid, context $context): int {
        global $DB;

        // Check if already assigned
        $existing = $DB->get_record('role_assignments', [
            'roleid' => $roleid,
            'userid' => $userid,
            'contextid' => $context->id
        ]);

        if ($existing) {
            return $existing->id;
        }

        // Create assignment
        $record = new \stdClass();
        $record->roleid = $roleid;
        $record->userid = $userid;
        $record->contextid = $context->id;
        $record->timemodified = time();

        $id = $DB->insert_record('role_assignments', $record);

        // Clear cache for this user
        self::clear_user_cache($userid);

        return $id;
    }

    /**
     * Unassign role from user
     *
     * @param int $roleid
     * @param int $userid
     * @param context $context
     * @return bool
     */
    public static function unassign_role(int $roleid, int $userid, context $context): bool {
        global $DB;

        $result = $DB->delete_records('role_assignments', [
            'roleid' => $roleid,
            'userid' => $userid,
            'contextid' => $context->id
        ]);

        // Clear cache
        self::clear_user_cache($userid);

        return $result;
    }

    /**
     * Clear capability cache for user
     *
     * @param int $userid
     * @return void
     */
    public static function clear_user_cache(int $userid): void {
        // Remove all cache entries for this user
        foreach (array_keys(self::$cache) as $key) {
            if (str_starts_with($key, "{$userid}_")) {
                unset(self::$cache[$key]);
            }
        }
    }

    /**
     * Clear all capability cache
     *
     * @return void
     */
    public static function clear_all_cache(): void {
        self::$cache = [];
    }

    /**
     * Get all roles assigned to a user in a context
     *
     * @param int $userid
     * @param context $context
     * @return array Array of role objects
     */
    public static function get_user_roles(int $userid, context $context): array {
        global $DB;

        $sql = "SELECT r.*
                FROM {roles} r
                JOIN {role_assignments} ra ON ra.roleid = r.id
                WHERE ra.userid = ?
                  AND ra.contextid = ?
                ORDER BY r.sortorder";

        return $DB->get_records_sql($sql, [$userid, $context->id]);
    }
}
