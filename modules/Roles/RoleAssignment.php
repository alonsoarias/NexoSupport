<?php
/**
 * ISER Roles System - Role Assignment
 *
 * @package    ISER\Modules\Roles
 * @copyright  2024 ISER
 * @license    Proprietary
 */

namespace ISER\Roles;

use ISER\Core\Database\Database;
use ISER\Core\Utils\Logger;

class RoleAssignment
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Assign role to user
     *
     * @param int $roleId Role ID
     * @param int $userId User ID
     * @param int $contextId Context ID (default: system context)
     * @param int $timeStart Start time (0 = immediate)
     * @param int $timeEnd End time (0 = permanent)
     * @return bool True on success
     */
    public function assignRole(int $roleId, int $userId, int $contextId = 1, int $timeStart = 0, int $timeEnd = 0): bool
    {
        // Check if assignment already exists
        $existing = $this->db->selectOne('role_assignments', [
            'roleid' => $roleId,
            'userid' => $userId,
            'contextid' => $contextId
        ]);

        if ($existing) {
            // Update existing assignment
            return $this->db->update('role_assignments', [
                'timestart' => $timeStart,
                'timeend' => $timeEnd,
                'timemodified' => time()
            ], ['id' => $existing['id']]) > 0;
        }

        // Create new assignment
        $now = time();
        $result = $this->db->insert('role_assignments', [
            'roleid' => $roleId,
            'userid' => $userId,
            'contextid' => $contextId,
            'timestart' => $timeStart,
            'timeend' => $timeEnd,
            'timecreated' => $now,
            'timemodified' => $now
        ]);

        if ($result) {
            Logger::auth('Role assigned', [
                'roleid' => $roleId,
                'userid' => $userId,
                'contextid' => $contextId
            ]);
        }

        return $result !== false;
    }

    /**
     * Unassign role from user
     */
    public function unassignRole(int $roleId, int $userId, int $contextId = 1): bool
    {
        $result = $this->db->delete('role_assignments', [
            'roleid' => $roleId,
            'userid' => $userId,
            'contextid' => $contextId
        ]) > 0;

        if ($result) {
            Logger::auth('Role unassigned', [
                'roleid' => $roleId,
                'userid' => $userId,
                'contextid' => $contextId
            ]);
        }

        return $result;
    }

    /**
     * Get user roles
     */
    public function getUserRoles(int $userId, int $contextId = 1): array
    {
        $sql = "SELECT r.*, ra.timestart, ra.timeend
                FROM {$this->db->table('roles')} r
                JOIN {$this->db->table('role_assignments')} ra ON r.id = ra.roleid
                WHERE ra.userid = :userid
                AND (ra.contextid = :contextid OR ra.contextid = 1)
                AND (ra.timestart = 0 OR ra.timestart <= :now1)
                AND (ra.timeend = 0 OR ra.timeend >= :now2)
                ORDER BY r.sortorder ASC";

        $now = time();
        return $this->db->getConnection()->fetchAll($sql, [
            ':userid' => $userId,
            ':contextid' => $contextId,
            ':now1' => $now,
            ':now2' => $now
        ]);
    }

    /**
     * Get users with a specific role
     */
    public function getRoleUsers(int $roleId, int $contextId = 1): array
    {
        $sql = "SELECT u.*, ra.timestart, ra.timeend
                FROM {$this->db->table('users')} u
                JOIN {$this->db->table('role_assignments')} ra ON u.id = ra.userid
                WHERE ra.roleid = :roleid
                AND (ra.contextid = :contextid OR ra.contextid = 1)
                AND (ra.timestart = 0 OR ra.timestart <= :now1)
                AND (ra.timeend = 0 OR ra.timeend >= :now2)
                AND u.deleted = 0
                ORDER BY u.username ASC";

        $now = time();
        return $this->db->getConnection()->fetchAll($sql, [
            ':roleid' => $roleId,
            ':contextid' => $contextId,
            ':now1' => $now,
            ':now2' => $now
        ]);
    }

    /**
     * Check if user has role
     */
    public function userHasRole(int $userId, int $roleId, int $contextId = 1): bool
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->db->table('role_assignments')}
                WHERE userid = :userid AND roleid = :roleid
                AND (contextid = :contextid OR contextid = 1)
                AND (timestart = 0 OR timestart <= :now1)
                AND (timeend = 0 OR timeend >= :now2)";

        $now = time();
        $result = $this->db->getConnection()->fetchOne($sql, [
            ':userid' => $userId,
            ':roleid' => $roleId,
            ':contextid' => $contextId,
            ':now1' => $now,
            ':now2' => $now
        ]);

        return ($result['count'] ?? 0) > 0;
    }

    /**
     * Check if user has role by shortname
     */
    public function userHasRoleByShortname(int $userId, string $shortname, int $contextId = 1): bool
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->db->table('role_assignments')} ra
                JOIN {$this->db->table('roles')} r ON ra.roleid = r.id
                WHERE ra.userid = :userid AND r.shortname = :shortname
                AND (ra.contextid = :contextid OR ra.contextid = 1)
                AND (ra.timestart = 0 OR ra.timestart <= :now1)
                AND (ra.timeend = 0 OR ra.timeend >= :now2)";

        $now = time();
        $result = $this->db->getConnection()->fetchOne($sql, [
            ':userid' => $userId,
            ':shortname' => $shortname,
            ':contextid' => $contextId,
            ':now1' => $now,
            ':now2' => $now
        ]);

        return ($result['count'] ?? 0) > 0;
    }

    /**
     * Bulk assign role to multiple users
     */
    public function bulkAssignRole(int $roleId, array $userIds, int $contextId = 1): int
    {
        $assigned = 0;
        foreach ($userIds as $userId) {
            if ($this->assignRole($roleId, $userId, $contextId)) {
                $assigned++;
            }
        }
        return $assigned;
    }

    /**
     * Bulk unassign role from multiple users
     */
    public function bulkUnassignRole(int $roleId, array $userIds, int $contextId = 1): int
    {
        $unassigned = 0;
        foreach ($userIds as $userId) {
            if ($this->unassignRole($roleId, $userId, $contextId)) {
                $unassigned++;
            }
        }
        return $unassigned;
    }

    /**
     * Get all role assignments for a user (including expired)
     */
    public function getAllUserAssignments(int $userId): array
    {
        $sql = "SELECT r.*, ra.timestart, ra.timeend, ra.contextid,
                CASE
                    WHEN ra.timestart > 0 AND ra.timestart > :now THEN 'future'
                    WHEN ra.timeend > 0 AND ra.timeend < :now THEN 'expired'
                    ELSE 'active'
                END as status
                FROM {$this->db->table('roles')} r
                JOIN {$this->db->table('role_assignments')} ra ON r.id = ra.roleid
                WHERE ra.userid = :userid
                ORDER BY r.sortorder ASC";

        return $this->db->getConnection()->fetchAll($sql, [
            ':userid' => $userId,
            ':now' => time()
        ]);
    }

    /**
     * Clean expired role assignments
     */
    public function cleanExpiredAssignments(): int
    {
        $sql = "DELETE FROM {$this->db->table('role_assignments')}
                WHERE timeend > 0 AND timeend < :now";

        return $this->db->getConnection()->execute($sql, [':now' => time()]);
    }
}
