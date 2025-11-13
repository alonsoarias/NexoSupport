<?php

declare(strict_types=1);

namespace ISER\Permission;

use ISER\Core\Database\Database;
use ISER\Core\Database\BaseRepository;

/**
 * Permission Manager (CRUD Operations)
 *
 * IMPORTANTE: Este es el sistema de gestión administrativa de permisos.
 * Para verificaciones de autorización en runtime, ver ISER\Roles\PermissionManager.
 *
 * PROPÓSITO:
 * - Operaciones CRUD para la tabla 'permissions'
 * - Gestión administrativa de permisos en la UI
 * - Agrupación y listado de permisos por módulo
 * - Consultas para relación permission-role
 *
 * USADO EN:
 * - Controllers/PermissionController.php (gestión de permisos)
 * - Controllers/RoleController.php (asignación de permisos a roles)
 *
 * NO USAR PARA: Verificaciones de autorización en runtime (usar Roles\PermissionManager)
 *
 * Extiende BaseRepository para reducir código duplicado.
 *
 * @package ISER\Permission
 * @see \ISER\Roles\PermissionManager Para sistema de capabilities/autorización
 */
class PermissionManager extends BaseRepository
{
    protected string $table = 'permissions';
    protected string $defaultOrderBy = 'module, name';

    /**
     * Obtener todos los permisos
     *
     * @param int $limit Límite de resultados
     * @param int $offset Offset para paginación
     * @param array $filters Filtros opcionales (module)
     * @return array Lista de permisos
     */
    public function getPermissions(int $limit = 100, int $offset = 0, array $filters = []): array
    {
        return $this->getAll($limit, $offset, $filters);
    }

    /**
     * Obtener permisos agrupados por módulo
     *
     * @return array Permisos agrupados por módulo
     */
    public function getPermissionsGroupedByModule(): array
    {
        $sql = "SELECT * FROM {$this->db->table('permissions')} ORDER BY module, name";
        $permissions = $this->db->getConnection()->fetchAll($sql);

        $grouped = [];
        foreach ($permissions as $permission) {
            $module = $permission['module'] ?? 'general';
            if (!isset($grouped[$module])) {
                $grouped[$module] = [];
            }
            $grouped[$module][] = $permission;
        }

        return $grouped;
    }

    /**
     * Contar permisos
     *
     * @param array $filters Filtros opcionales (module)
     * @return int Total de permisos
     */
    public function countPermissions(array $filters = []): int
    {
        return $this->count($filters);
    }

    /**
     * Obtener permiso por ID
     *
     * @param int $id Permission ID
     * @return array|null Permiso o null si no existe
     */
    public function getPermissionById(int $id): ?array
    {
        return $this->findById($id);
    }

    /**
     * Obtener permiso por slug
     *
     * @param string $slug Permission slug
     * @return array|null Permiso o null si no existe
     */
    public function getPermissionBySlug(string $slug): ?array
    {
        return $this->findByField('slug', $slug);
    }

    /**
     * Obtener roles que tienen un permiso específico
     *
     * @param int $permissionId Permission ID
     * @return array Lista de roles con el permiso
     */
    public function getPermissionRoles(int $permissionId): array
    {
        $sql = "SELECT r.* FROM {$this->db->table('roles')} r
                INNER JOIN {$this->db->table('role_permissions')} rp ON r.id = rp.role_id
                WHERE rp.permission_id = :permission_id
                ORDER BY r.name ASC";

        return $this->db->getConnection()->fetchAll($sql, [':permission_id' => $permissionId]);
    }

    /**
     * Verificar si un usuario tiene un permiso específico
     *
     * @param int $userId User ID
     * @param string $permissionSlug Permission slug
     * @return bool True si el usuario tiene el permiso
     */
    public function userHasPermission(int $userId, string $permissionSlug): bool
    {
        $sql = "SELECT COUNT(*) as count
                FROM {$this->db->table('permissions')} p
                INNER JOIN {$this->db->table('role_permissions')} rp ON p.id = rp.permission_id
                INNER JOIN {$this->db->table('user_roles')} ur ON rp.role_id = ur.role_id
                WHERE ur.user_id = :user_id AND p.slug = :slug";

        $result = $this->db->getConnection()->fetchOne($sql, [
            ':user_id' => $userId,
            ':slug' => $permissionSlug
        ]);

        return ((int)($result['count'] ?? 0)) > 0;
    }

    /**
     * Obtener todos los módulos disponibles
     *
     * @return array Lista de módulos
     */
    public function getModules(): array
    {
        $sql = "SELECT DISTINCT module FROM {$this->db->table('permissions')} ORDER BY module";
        $results = $this->db->getConnection()->fetchAll($sql);

        return array_column($results, 'module');
    }

    /**
     * Apply custom filters for permission queries
     *
     * Override del método applyFilters() de BaseRepository para
     * soportar el filtro module
     *
     * @param string $sql SQL base query
     * @param array $filters Filtros a aplicar
     * @param array $params Parámetros (por referencia)
     * @return string SQL modificado
     */
    protected function applyFilters(string $sql, array $filters, array &$params): string
    {
        if (isset($filters['module'])) {
            $sql .= " AND module = :module";
            $params[':module'] = $filters['module'];
        }

        // Call parent for any additional filters
        return parent::applyFilters($sql, $filters, $params);
    }
}
