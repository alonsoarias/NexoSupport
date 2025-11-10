<?php

declare(strict_types=1);

namespace ISER\Permission;

use ISER\Core\Database\Database;

/**
 * Permission Manager
 *
 * Gestiona permisos del sistema: crear, leer, actualizar, eliminar
 */
class PermissionManager
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Obtener todos los permisos
     */
    public function getPermissions(int $limit = 100, int $offset = 0, array $filters = []): array
    {
        $sql = "SELECT * FROM {$this->db->table('permissions')} WHERE 1=1";
        $params = [];

        // Filtrar por módulo
        if (isset($filters['module'])) {
            $sql .= " AND module = :module";
            $params[':module'] = $filters['module'];
        }

        $sql .= " ORDER BY module, name LIMIT :limit OFFSET :offset";

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
     * Obtener permisos agrupados por módulo
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
     */
    public function countPermissions(array $filters = []): int
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->db->table('permissions')} WHERE 1=1";
        $params = [];

        if (isset($filters['module'])) {
            $sql .= " AND module = :module";
            $params[':module'] = $filters['module'];
        }

        $result = $this->db->getConnection()->fetchOne($sql, $params);
        return (int)($result['count'] ?? 0);
    }

    /**
     * Obtener permiso por ID
     */
    public function getPermissionById(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->db->table('permissions')} WHERE id = :id";
        $result = $this->db->getConnection()->fetchOne($sql, [':id' => $id]);
        return $result ?: null;
    }

    /**
     * Obtener permiso por slug
     */
    public function getPermissionBySlug(string $slug): ?array
    {
        $sql = "SELECT * FROM {$this->db->table('permissions')} WHERE slug = :slug";
        $result = $this->db->getConnection()->fetchOne($sql, [':slug' => $slug]);
        return $result ?: null;
    }

    /**
     * Crear permiso
     */
    public function create(array $data): int
    {
        $now = time();
        $data['created_at'] = $now;
        $data['updated_at'] = $now;

        return (int)$this->db->insert('permissions', $data);
    }

    /**
     * Actualizar permiso
     */
    public function update(int $id, array $data): bool
    {
        $data['updated_at'] = time();
        $rowsAffected = $this->db->update('permissions', $data, ['id' => $id]);
        return $rowsAffected > 0;
    }

    /**
     * Eliminar permiso
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM {$this->db->table('permissions')} WHERE id = :id";
        $this->db->getConnection()->execute($sql, [':id' => $id]);
        return true;
    }

    /**
     * Obtener roles que tienen un permiso específico
     */
    public function getPermissionRoles(int $permissionId): array
    {
        $sql = "SELECT r.* FROM {$this->db->table('roles')} r
                INNER JOIN {$this->db->table('role_permissions')} rp ON r.id = rp.role_id
                WHERE rp.permission_id = :permission_id
                ORDER BY r.level DESC";

        return $this->db->getConnection()->fetchAll($sql, [':permission_id' => $permissionId]);
    }

    /**
     * Verificar si un usuario tiene un permiso específico
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
     */
    public function getModules(): array
    {
        $sql = "SELECT DISTINCT module FROM {$this->db->table('permissions')} ORDER BY module";
        $results = $this->db->getConnection()->fetchAll($sql);

        return array_column($results, 'module');
    }
}
