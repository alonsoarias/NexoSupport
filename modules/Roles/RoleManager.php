<?php
/**
 * ISER Roles System - Role Manager
 *
 * @package    ISER\Modules\Roles
 * @copyright  2024 ISER
 * @license    Proprietary
 */

namespace ISER\Modules\Roles;

use ISER\Core\Database\Database;

class RoleManager
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Create a new role
     */
    public function createRole(array $data): int|false
    {
        $required = ['name', 'shortname'];
        foreach ($required as $field) {
            if (empty($data[$field])) return false;
        }

        // Check if shortname already exists
        if ($this->roleExists($data['shortname'])) {
            return false;
        }

        $now = time();
        return $this->db->insert('roles', [
            'name' => $data['name'],
            'shortname' => $data['shortname'],
            'description' => $data['description'] ?? '',
            'archetype' => $data['archetype'] ?? null,
            'sortorder' => $data['sortorder'] ?? 0,
            'timecreated' => $now,
            'timemodified' => $now
        ]);
    }

    /**
     * Update a role
     */
    public function updateRole(int $roleId, array $data): bool
    {
        // Don't allow changing shortname if it would conflict
        if (isset($data['shortname'])) {
            $existing = $this->db->selectOne('roles', ['shortname' => $data['shortname']]);
            if ($existing && $existing['id'] != $roleId) {
                return false;
            }
        }

        $data['timemodified'] = time();
        unset($data['id']); // Prevent ID modification

        return $this->db->update('roles', $data, ['id' => $roleId]) > 0;
    }

    /**
     * Delete a role
     */
    public function deleteRole(int $roleId): bool
    {
        // Don't allow deleting system roles
        $role = $this->getRole($roleId);
        if (!$role) return false;

        if (in_array($role['shortname'], ['admin', 'user', 'guest'])) {
            return false; // Protected system roles
        }

        return $this->db->delete('roles', ['id' => $roleId]) > 0;
    }

    /**
     * Get role by ID
     */
    public function getRole(int $roleId): array|false
    {
        return $this->db->selectOne('roles', ['id' => $roleId]);
    }

    /**
     * Get role by shortname
     */
    public function getRoleByShortname(string $shortname): array|false
    {
        return $this->db->selectOne('roles', ['shortname' => $shortname]);
    }

    /**
     * Get all roles
     */
    public function getAllRoles(bool $sorted = true): array
    {
        $sql = "SELECT * FROM {$this->db->table('roles')}";
        if ($sorted) {
            $sql .= " ORDER BY sortorder ASC, name ASC";
        }
        return $this->db->getConnection()->fetchAll($sql);
    }

    /**
     * Check if role exists by shortname
     */
    public function roleExists(string $shortname): bool
    {
        $role = $this->getRoleByShortname($shortname);
        return $role !== false;
    }

    /**
     * Clone a role
     */
    public function cloneRole(int $sourceRoleId, string $newName, string $newShortname): int|false
    {
        $sourceRole = $this->getRole($sourceRoleId);
        if (!$sourceRole) return false;

        if ($this->roleExists($newShortname)) {
            return false;
        }

        // Create new role
        $newRoleId = $this->createRole([
            'name' => $newName,
            'shortname' => $newShortname,
            'description' => $sourceRole['description'],
            'archetype' => $sourceRole['archetype'],
            'sortorder' => $sourceRole['sortorder']
        ]);

        if (!$newRoleId) return false;

        // Copy capabilities
        $sql = "SELECT * FROM {$this->db->table('role_capabilities')} WHERE roleid = :roleid";
        $capabilities = $this->db->getConnection()->fetchAll($sql, [':roleid' => $sourceRoleId]);

        $now = time();
        foreach ($capabilities as $cap) {
            $this->db->insert('role_capabilities', [
                'roleid' => $newRoleId,
                'capabilityid' => $cap['capabilityid'],
                'permission' => $cap['permission'],
                'contextid' => $cap['contextid'],
                'timecreated' => $now,
                'timemodified' => $now
            ]);
        }

        return $newRoleId;
    }

    /**
     * Get role statistics
     */
    public function getRoleStats(int $roleId): array
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->db->table('role_assignments')} WHERE roleid = :roleid";
        $result = $this->db->getConnection()->fetchOne($sql, [':roleid' => $roleId]);

        return [
            'user_count' => (int)($result['count'] ?? 0)
        ];
    }

    /**
     * Get roles with user counts
     */
    public function getRolesWithCounts(): array
    {
        $sql = "SELECT r.*, COUNT(ra.id) as user_count
                FROM {$this->db->table('roles')} r
                LEFT JOIN {$this->db->table('role_assignments')} ra ON r.id = ra.roleid
                GROUP BY r.id
                ORDER BY r.sortorder ASC, r.name ASC";

        return $this->db->getConnection()->fetchAll($sql);
    }
}
