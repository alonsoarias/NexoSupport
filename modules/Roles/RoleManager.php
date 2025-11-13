<?php

declare(strict_types=1);

namespace ISER\Roles;

use ISER\Core\Database\Database;
use ISER\Core\Database\BaseRepository;

/**
 * Role Manager
 *
 * Gestiona roles del sistema: crear, leer, actualizar, eliminar
 * y gestionar permisos de roles
 *
 * Extiende BaseRepository para reducir código duplicado.
 */
class RoleManager extends BaseRepository
{
    protected string $table = 'roles';
    protected string $defaultOrderBy = 'name ASC';

    /**
     * Obtener todos los roles
     *
     * @param int $limit Límite de resultados
     * @param int $offset Offset para paginación
     * @param array $filters Filtros opcionales (is_system)
     * @return array Lista de roles
     */
    public function getRoles(int $limit = 100, int $offset = 0, array $filters = []): array
    {
        return $this->getAll($limit, $offset, $filters);
    }

    /**
     * Contar roles
     *
     * @param array $filters Filtros opcionales (is_system)
     * @return int Total de roles
     */
    public function countRoles(array $filters = []): int
    {
        return $this->count($filters);
    }

    /**
     * Obtener rol por ID
     *
     * @param int $id Role ID
     * @return array|null Rol o null si no existe
     */
    public function getRoleById(int $id): ?array
    {
        return $this->findById($id);
    }

    /**
     * Obtener rol por slug
     *
     * @param string $slug Role slug
     * @return array|null Rol o null si no existe
     */
    public function getRoleBySlug(string $slug): ?array
    {
        return $this->findByField('slug', $slug);
    }

    /**
     * Eliminar rol (solo si no es sistema)
     *
     * Override del método delete() de BaseRepository para
     * prevenir eliminación de roles del sistema
     *
     * @param int $id Role ID
     * @return bool True si se eliminó, false si es rol del sistema
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
     *
     * @param int $roleId Role ID
     * @return array Lista de permisos del rol
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
     *
     * @param int $roleId Role ID
     * @param int $permissionId Permission ID
     * @return bool True si se asignó correctamente
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
     *
     * @param int $roleId Role ID
     * @param int $permissionId Permission ID
     * @return bool True si se removió correctamente
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
     *
     * @param int $roleId Role ID
     * @param array $permissionIds Array de Permission IDs
     * @return bool True si se sincronizó correctamente
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
     *
     * @param int $roleId Role ID
     * @return array Lista de usuarios con el rol
     */
    public function getRoleUsers(int $roleId): array
    {
        $sql = "SELECT u.* FROM {$this->db->table('users')} u
                INNER JOIN {$this->db->table('user_roles')} ur ON u.id = ur.user_id
                WHERE ur.role_id = :role_id AND u.deleted_at IS NULL
                ORDER BY u.username";

        return $this->db->getConnection()->fetchAll($sql, [':role_id' => $roleId]);
    }

    /**
     * Apply custom filters for role queries
     *
     * Override del método applyFilters() de BaseRepository para
     * soportar el filtro is_system
     *
     * @param string $sql SQL base query
     * @param array $filters Filtros a aplicar
     * @param array $params Parámetros (por referencia)
     * @return string SQL modificado
     */
    protected function applyFilters(string $sql, array $filters, array &$params): string
    {
        if (isset($filters['is_system'])) {
            $sql .= " AND is_system = :is_system";
            $params[':is_system'] = $filters['is_system'] ? 1 : 0;
        }

        // Call parent for any additional filters
        return parent::applyFilters($sql, $filters, $params);
    }
}
