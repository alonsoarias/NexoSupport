<?php

declare(strict_types=1);

namespace ISER\Roles;

use ISER\Core\Database\Database;

/**
 * Role Manager
 *
 * Gestiona roles del sistema: crear, leer, actualizar, eliminar
 * y gestionar permisos de roles
 */
class RoleManager
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Obtener todos los roles
     */
    public function getRoles(int $limit = 100, int $offset = 0, array $filters = []): array
    {
        $sql = "SELECT * FROM {$this->db->table('roles')} WHERE 1=1";
        $params = [];

        // Aplicar filtros
        if (isset($filters['is_system'])) {
            $sql .= " AND is_system = :is_system";
            $params[':is_system'] = $filters['is_system'] ? 1 : 0;
        }

        $sql .= " ORDER BY name ASC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->getConnection()->getConnection()->prepare($sql);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Contar roles
     */
    public function countRoles(array $filters = []): int
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->db->table('roles')} WHERE 1=1";
        $params = [];

        if (isset($filters['is_system'])) {
            $sql .= " AND is_system = :is_system";
            $params[':is_system'] = $filters['is_system'] ? 1 : 0;
        }

        $result = $this->db->getConnection()->fetchOne($sql, $params);
        return (int)($result['count'] ?? 0);
    }

    /**
     * Obtener rol por ID
     */
    public function getRoleById(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->db->table('roles')} WHERE id = :id";
        $result = $this->db->getConnection()->fetchOne($sql, [':id' => $id]);
        return $result ?: null;
    }

    /**
     * Obtener rol por slug
     */
    public function getRoleBySlug(string $slug): ?array
    {
        $sql = "SELECT * FROM {$this->db->table('roles')} WHERE slug = :slug";
        $result = $this->db->getConnection()->fetchOne($sql, [':slug' => $slug]);
        return $result ?: null;
    }

    /**
     * Crear rol
     */
    public function create(array $data): int
    {
        $now = time();
        $data['created_at'] = $now;
        $data['updated_at'] = $now;

        return (int)$this->db->insert('roles', $data);
    }

    /**
     * Actualizar rol
     */
    public function update(int $id, array $data): bool
    {
        $data['updated_at'] = time();
        $rowsAffected = $this->db->update('roles', $data, ['id' => $id]);
        return $rowsAffected > 0;
    }

    /**
     * Eliminar rol (solo si no es sistema)
     */
    public function delete(int $id): bool
    {
        $role = $this->getRoleById($id);

        if (!$role || $role['is_system']) {
            return false; // No se pueden eliminar roles del sistema
        }

        $sql = "DELETE FROM {$this->db->table('roles')} WHERE id = :id";
        $this->db->getConnection()->execute($sql, [':id' => $id]);
        return true;
    }

    /**
     * Obtener permisos de un rol
     */
    public function getRolePermissions(int $roleId): array
    {
        $sql = "SELECT p.* FROM {$this->db->table('permissions')} p
                INNER JOIN {$this->db->table('role_permissions')} rp ON p.id = rp.permission_id
                WHERE rp.role_id = :role_id
                ORDER BY p.module, p.name";

        return $this->db->getConnection()->fetchAll($sql, [':role_id' => $roleId]);
    }

    /**
     * Asignar permiso a rol
     */
    public function assignPermission(int $roleId, int $permissionId): bool
    {
        $sql = "INSERT IGNORE INTO {$this->db->table('role_permissions')}
                (role_id, permission_id, granted_at)
                VALUES (:role_id, :permission_id, :granted_at)";

        $this->db->getConnection()->execute($sql, [
            ':role_id' => $roleId,
            ':permission_id' => $permissionId,
            ':granted_at' => time()
        ]);

        return true;
    }

    /**
     * Remover permiso de rol
     */
    public function removePermission(int $roleId, int $permissionId): bool
    {
        $sql = "DELETE FROM {$this->db->table('role_permissions')}
                WHERE role_id = :role_id AND permission_id = :permission_id";

        $this->db->getConnection()->execute($sql, [
            ':role_id' => $roleId,
            ':permission_id' => $permissionId
        ]);

        return true;
    }

    /**
     * Sincronizar permisos de un rol (reemplaza todos)
     */
    public function syncPermissions(int $roleId, array $permissionIds): bool
    {
        // Eliminar permisos actuales
        $sql = "DELETE FROM {$this->db->table('role_permissions')} WHERE role_id = :role_id";
        $this->db->getConnection()->execute($sql, [':role_id' => $roleId]);

        // Agregar nuevos permisos
        foreach ($permissionIds as $permissionId) {
            $this->assignPermission($roleId, (int)$permissionId);
        }

        return true;
    }

    /**
     * Obtener usuarios con un rol específico
     */
    public function getRoleUsers(int $roleId): array
    {
        $sql = "SELECT u.* FROM {$this->db->table('users')} u
                INNER JOIN {$this->db->table('user_roles')} ur ON u.id = ur.user_id
                WHERE ur.role_id = :role_id AND u.deleted_at IS NULL
                ORDER BY u.username";

        return $this->db->getConnection()->fetchAll($sql, [':role_id' => $roleId]);
    }
}
