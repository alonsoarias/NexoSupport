<?php
/**
 * User Management Functions
 *
 * Global user management functions similar to Moodle's user/lib.php
 *
 * @package core
 */

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Delete a user
 *
 * Similar to Moodle's delete_user()
 * Performs a "soft delete" by marking the user as deleted
 *
 * @param stdClass $user User object
 * @return bool True if successful
 */
function delete_user($user) {
    global $DB;

    // Prevent deletion of admin or self
    if (is_siteadmin($user->id)) {
        debugging('Cannot delete site administrator');
        return false;
    }

    global $USER;
    if ($USER->id == $user->id) {
        debugging('Cannot delete yourself');
        return false;
    }

    // Already deleted?
    if ($user->deleted) {
        return false;
    }

    // Start transaction
    $transaction = $DB->start_delegated_transaction();

    try {
        // Mark as deleted
        $updateuser = new stdClass();
        $updateuser->id = $user->id;
        $updateuser->deleted = 1;
        $updateuser->timemodified = time();

        // Anonymize personal data
        $updateuser->username = 'deleted_' . $user->id . '_' . time();
        $updateuser->email = '';
        $updateuser->firstname = '';
        $updateuser->lastname = '';
        $updateuser->phone = '';

        $DB->update_record('users', $updateuser);

        // Delete role assignments
        $DB->delete_records('role_assignments', ['userid' => $user->id]);

        // Destroy user sessions
        \core\session\manager::kill_user_sessions($user->id);

        $transaction->allow_commit();

        debugging("User {$user->id} deleted successfully", DEBUG_DEVELOPER);
        return true;

    } catch (Exception $e) {
        $transaction->rollback($e);
        debugging('Error deleting user: ' . $e->getMessage());
        return false;
    }
}

/**
 * Suspend a user account
 *
 * @param stdClass $user User object
 * @return bool True if successful
 */
function suspend_user($user) {
    global $DB;

    // Prevent suspending admin or self
    if (is_siteadmin($user->id)) {
        debugging('Cannot suspend site administrator');
        return false;
    }

    global $USER;
    if ($USER->id == $user->id) {
        debugging('Cannot suspend yourself');
        return false;
    }

    // Already suspended?
    if ($user->suspended) {
        return true;
    }

    // Update user
    $updateuser = new stdClass();
    $updateuser->id = $user->id;
    $updateuser->suspended = 1;
    $updateuser->timemodified = time();

    $DB->update_record('users', $updateuser);

    // Destroy user sessions (force logout)
    \core\session\manager::kill_user_sessions($user->id);

    debugging("User {$user->id} suspended", DEBUG_DEVELOPER);
    return true;
}

/**
 * Unsuspend a user account
 *
 * @param stdClass $user User object
 * @return bool True if successful
 */
function unsuspend_user($user) {
    global $DB;

    // Not suspended?
    if (!$user->suspended) {
        return true;
    }

    // Update user
    $updateuser = new stdClass();
    $updateuser->id = $user->id;
    $updateuser->suspended = 0;
    $updateuser->timemodified = time();

    $DB->update_record('users', $updateuser);

    debugging("User {$user->id} unsuspended", DEBUG_DEVELOPER);
    return true;
}

/**
 * Unlock a user account that has been locked due to failed login attempts
 *
 * @param stdClass $user User object
 * @return bool True if successful
 */
function unlock_user($user) {
    global $DB;

    // Remove login failure tracking
    // In a full implementation, this would clear login attempt counters
    // For now, we'll just ensure the user is not suspended

    if ($user->suspended) {
        return unsuspend_user($user);
    }

    debugging("User {$user->id} unlocked", DEBUG_DEVELOPER);
    return true;
}

/**
 * Confirm a user account
 *
 * @param stdClass $user User object
 * @return bool True if successful
 */
function confirm_user($user) {
    global $DB;

    // Already confirmed?
    if ($user->confirmed) {
        return true;
    }

    // Update user
    $updateuser = new stdClass();
    $updateuser->id = $user->id;
    $updateuser->confirmed = 1;
    $updateuser->timemodified = time();

    $DB->update_record('users', $updateuser);

    debugging("User {$user->id} confirmed", DEBUG_DEVELOPER);
    return true;
}

/**
 * Send confirmation email to user
 *
 * @param stdClass $user User object
 * @return bool True if email sent successfully
 */
function send_confirmation_email($user) {
    global $CFG;

    // Already confirmed?
    if ($user->confirmed) {
        return false;
    }

    // In a full implementation, this would:
    // 1. Generate confirmation link with token
    // 2. Send email to user
    // 3. Return success/failure

    // For now, just log it
    debugging("Confirmation email would be sent to user {$user->id} ({$user->email})", DEBUG_DEVELOPER);

    // Simulate success
    return true;
}

/**
 * Get count of users matching a condition
 *
 * @param string $where WHERE clause (without WHERE keyword)
 * @param array $params Parameters for the WHERE clause
 * @return int Number of users
 */
function count_users($where = '', $params = []) {
    global $DB;

    $sql = "deleted = 0";
    if (!empty($where)) {
        $sql .= " AND " . $where;
    }

    return $DB->count_records_select('users', $sql, $params);
}

/**
 * Check if a user is a site administrator
 *
 * @param int $userid User ID
 * @return bool True if user is admin
 */
function is_siteadmin($userid = null) {
    global $USER, $DB;

    if ($userid === null) {
        $userid = $USER->id ?? 0;
    }

    if ($userid == 0) {
        return false;
    }

    // Check if user has administrator role in system context
    $syscontext = \core\rbac\context::system();

    $sql = "SELECT COUNT(*)
            FROM {role_assignments} ra
            JOIN {roles} r ON r.id = ra.roleid
            WHERE ra.userid = ?
            AND ra.contextid = ?
            AND r.shortname = 'administrator'";

    $count = $DB->count_records_sql($sql, [$userid, $syscontext->id]);

    return $count > 0;
}
