<?php
/**
 * Permission Manager (Runtime Authorization & Capabilities)
 *
 * IMPORTANTE: Este es el sistema de autorización en runtime (Moodle-style).
 * Para operaciones CRUD de permisos, ver ISER\Permission\PermissionManager.
 *
 * PROPÓSITO:
 * - Verificación de capabilities en runtime (hasCapability)
 * - Sistema de permisos tipo Moodle con contextos
 * - Cache de permisos de usuario
 * - Enforcement de permisos en middleware
 * - Soporte para CAP_ALLOW, CAP_PREVENT, CAP_PROHIBIT
 *
 * USADO EN:
 * - core/Middleware/PermissionMiddleware.php (protección de rutas)
 * - core/Middleware/AdminMiddleware.php (verificación admin)
 * - app/Admin/*.php (páginas administrativas)
 *
 * NO USAR PARA: Gestión CRUD de permisos (usar Permission\PermissionManager)
 *
 * CAPABILITY FORMAT: 'moodle/module:action' (e.g., 'moodle/user:create')
 *
 * @package    ISER\Roles
 * @copyright  2024 ISER
 * @license    Proprietary
 * @see \ISER\Permission\PermissionManager Para operaciones CRUD administrativas
 */

namespace ISER\Roles;

use ISER\Core\Database\Database;

// Permission constants (from capabilities.php)
if (!defined('CAP_INHERIT')) define('CAP_INHERIT', 0);
if (!defined('CAP_ALLOW')) define('CAP_ALLOW', 1);
if (!defined('CAP_PREVENT')) define('CAP_PREVENT', -1);
if (!defined('CAP_PROHIBIT')) define('CAP_PROHIBIT', -1000);

class PermissionManager
{
    private Database $db;
    private array $permissionCache = [];

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Check if user has a specific capability
     *
     * @param int $userId User ID
     * @param string $capability Capability name (e.g., 'moodle/user:create')
     * @param int $contextId Context ID (default: system context)
     * @return bool True if user has permission
     */
    public function hasCapability(int $userId, string $capability, int $contextId = 1): bool
    {
        // Check cache
        $cacheKey = "{$userId}:{$capability}:{$contextId}";
        if (isset($this->permissionCache[$cacheKey])) {
            return $this->permissionCache[$cacheKey];
        }

        // Get user roles
        $roles = $this->getUserRoles($userId, $contextId);
        if (empty($roles)) {
            $this->permissionCache[$cacheKey] = false;
            return false;
        }

        // Get capability ID
        $cap = $this->db->selectOne('capabilities', ['name' => $capability]);
        if (!$cap) {
            $this->permissionCache[$cacheKey] = false;
            return false;
        }

        // Check permissions for each role
        $finalPermission = CAP_INHERIT;

        foreach ($roles as $role) {
            $perm = $this->getRoleCapabilityPermission($role['id'], $cap['id'], $contextId);

            // CAP_PROHIBIT always wins
            if ($perm === CAP_PROHIBIT) {
                $this->permissionCache[$cacheKey] = false;
                return false;
            }

            // CAP_ALLOW takes precedence over INHERIT
            if ($perm === CAP_ALLOW) {
                $finalPermission = CAP_ALLOW;
            }

            // CAP_PREVENT blocks if no ALLOW found yet
            if ($perm === CAP_PREVENT && $finalPermission !== CAP_ALLOW) {
                $finalPermission = CAP_PREVENT;
            }
        }

        $result = ($finalPermission === CAP_ALLOW);
        $this->permissionCache[$cacheKey] = $result;
        return $result;
    }

    /**
     * Require a capability (throws exception if not allowed)
     */
    public function requireCapability(int $userId, string $capability, int $contextId = 1): void
    {
        if (!$this->hasCapability($userId, $capability, $contextId)) {
            throw new \Exception("Permission denied: {$capability}");
        }
    }

    /**
     * Get user roles in a context
     */
    private function getUserRoles(int $userId, int $contextId): array
    {
        $sql = "SELECT r.* FROM {$this->db->table('roles')} r
                JOIN {$this->db->table('role_assignments')} ra ON r.id = ra.roleid
                WHERE ra.userid = :userid
                AND (ra.contextid = :contextid OR ra.contextid = 1)
                AND (ra.timestart = 0 OR ra.timestart <= :now1)
                AND (ra.timeend = 0 OR ra.timeend >= :now2)";

        $now = time();
        return $this->db->getConnection()->fetchAll($sql, [
            ':userid' => $userId,
            ':contextid' => $contextId,
            ':now1' => $now,
            ':now2' => $now
        ]);
    }

    /**
     * Get permission level for a role-capability combination
     */
    private function getRoleCapabilityPermission(int $roleId, int $capabilityId, int $contextId): int
    {
        $sql = "SELECT permission FROM {$this->db->table('role_capabilities')}
                WHERE roleid = :roleid AND capabilityid = :capabilityid
                AND (contextid = :contextid OR contextid = 1)
                ORDER BY contextid DESC
                LIMIT 1";

        $result = $this->db->getConnection()->fetchOne($sql, [
            ':roleid' => $roleId,
            ':capabilityid' => $capabilityId,
            ':contextid' => $contextId
        ]);

        return $result ? (int)$result['permission'] : CAP_INHERIT;
    }

    /**
     * Get all capabilities for a user
     */
    public function getUserCapabilities(int $userId, int $contextId = 1): array
    {
        $roles = $this->getUserRoles($userId, $contextId);
        if (empty($roles)) {
            return [];
        }

        $roleIds = array_column($roles, 'id');
        $placeholders = implode(',', array_fill(0, count($roleIds), '?'));

        $sql = "SELECT DISTINCT c.name, c.description, rc.permission
                FROM {$this->db->table('capabilities')} c
                JOIN {$this->db->table('role_capabilities')} rc ON c.id = rc.capabilityid
                WHERE rc.roleid IN ({$placeholders})
                AND rc.permission = " . CAP_ALLOW;

        return $this->db->getConnection()->fetchAll($sql, $roleIds);
    }

    /**
     * Check if user is admin (has all permissions)
     */
    public function isAdmin(int $userId): bool
    {
        return $this->hasCapability($userId, 'moodle/site:config');
    }

    /**
     * Clear permission cache
     */
    public function clearCache(?int $userId = null): void
    {
        if ($userId === null) {
            $this->permissionCache = [];
        } else {
            foreach ($this->permissionCache as $key => $value) {
                if (str_starts_with($key, "{$userId}:")) {
                    unset($this->permissionCache[$key]);
                }
            }
        }
    }

    /**
     * Assign capability to role
     */
    public function assignCapability(int $roleId, string $capabilityName, int $permission, int $contextId = 1): bool
    {
        $cap = $this->db->selectOne('capabilities', ['name' => $capabilityName]);
        if (!$cap) return false;

        // Check if already exists
        $existing = $this->db->selectOne('role_capabilities', [
            'roleid' => $roleId,
            'capabilityid' => $cap['id'],
            'contextid' => $contextId
        ]);

        $now = time();
        if ($existing) {
            return $this->db->update('role_capabilities', [
                'permission' => $permission,
                'timemodified' => $now
            ], ['id' => $existing['id']]) > 0;
        } else {
            return $this->db->insert('role_capabilities', [
                'roleid' => $roleId,
                'capabilityid' => $cap['id'],
                'permission' => $permission,
                'contextid' => $contextId,
                'timecreated' => $now,
                'timemodified' => $now
            ]) !== false;
        }
    }

    /**
     * Get role capabilities
     */
    public function getRoleCapabilities(int $roleId, int $contextId = 1): array
    {
        $sql = "SELECT c.name, c.description, rc.permission
                FROM {$this->db->table('capabilities')} c
                JOIN {$this->db->table('role_capabilities')} rc ON c.id = rc.capabilityid
                WHERE rc.roleid = :roleid
                AND rc.contextid = :contextid";

        return $this->db->getConnection()->fetchAll($sql, [
            ':roleid' => $roleId,
            ':contextid' => $contextId
        ]);
    }
}
