<?php

declare(strict_types=1);

namespace ISER\Controllers;

use ISER\Controllers\Traits\NavigationTrait;
use ISER\Core\Database\Database;
use ISER\Core\Http\Response;
use ISER\Core\View\MustacheRenderer;
use ISER\Role\RoleManager;
use ISER\Permission\PermissionManager;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * RoleController - Gestión completa de roles (REFACTORIZADO)
 *
 * PATRÓN DE SESIONES:
 * - Usa sesiones para almacenar ID durante edición
 * - IDs se pasan como role_id (string) para compatibilidad con Mustache
 * - Sin exposición de IDs en URLs ni campos hidden
 */
class RoleController
{
    use NavigationTrait;

    private RoleManager $roleManager;
    private PermissionManager $permissionManager;
    private MustacheRenderer $renderer;
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->roleManager = new RoleManager($db);
        $this->permissionManager = new PermissionManager($db);
        $this->renderer = MustacheRenderer::getInstance();
    }

    /**
     * Log audit event
     */
    private function logAudit(string $action, string $entityType, ?int $entityId, ?array $oldValues = null, ?array $newValues = null): void
    {
        $userId = $_SESSION['user_id'] ?? null;
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

        $sql = "INSERT INTO {$this->db->table('audit_log')}
                (user_id, action, entity_type, entity_id, old_values, new_values, ip_address, user_agent, created_at)
                VALUES (:user_id, :action, :entity_type, :entity_id, :old_values, :new_values, :ip_address, :user_agent, :created_at)";

        $this->db->getConnection()->execute($sql, [
            ':user_id' => $userId,
            ':action' => $action,
            ':entity_type' => $entityType,
            ':entity_id' => $entityId,
            ':old_values' => $oldValues ? json_encode($oldValues) : null,
            ':new_values' => $newValues ? json_encode($newValues) : null,
            ':ip_address' => $ipAddress,
            ':user_agent' => $userAgent,
            ':created_at' => time(),
        ]);
    }

    /**
     * Renderizar con layout
     */
    private function renderWithLayout(string $view, array $data = [], string $layout = 'layouts/app'): ResponseInterface
    {
        $html = $this->renderer->render($view, $data, $layout);
        return Response::html($html);
    }

    /**
     * Lista de roles
     */
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();

        // Paginación
        $page = isset($queryParams['page']) ? max(1, (int)$queryParams['page']) : 1;
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        // Obtener roles
        $roles = $this->roleManager->getRoles($perPage, $offset);
        $totalRoles = $this->roleManager->countRoles();
        $totalPages = (int)ceil($totalRoles / $perPage);

        // Enriquecer roles con permisos y flags
        foreach ($roles as &$role) {
            $roleId = (int)$role['id'];
            $role['permissions'] = $this->roleManager->getRolePermissions($roleId);
            $role['permission_count'] = count($role['permissions']);
            $role['is_system_role'] = !empty($role['is_system']);

            // CRÍTICO: Usar role_id como string para Mustache
            $role['role_id'] = (string)$role['id'];
        }

        $data = [
            'roles' => $roles,
            'total_roles' => $totalRoles,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'show_pagination' => $totalPages > 1,
            'has_previous' => $page > 1,
            'has_next' => $page < $totalPages,
            'previous_page' => $page - 1,
            'next_page' => $page + 1,
            'page_title' => 'Gestión de Roles',
        ];

        // Mensajes
        if (isset($queryParams['success'])) {
            $messages = [
                'created' => 'Rol creado correctamente',
                'updated' => 'Rol actualizado correctamente',
                'deleted' => 'Rol eliminado correctamente',
            ];
            $data['success_message'] = $messages[$queryParams['success']] ?? null;
        }

        if (isset($queryParams['error'])) {
            $errors = [
                'invalid_id' => 'ID de rol inválido',
                'not_found' => 'Rol no encontrado',
                'system_role' => 'No se pueden modificar roles del sistema',
            ];
            $data['error_message'] = $errors[$queryParams['error']] ?? 'Error desconocido';
        }

        // Enriquecer con navegación
        $data = $this->enrichWithNavigation($data, '/admin/roles');

        return $this->renderWithLayout('admin/roles/index', $data);
    }

    /**
     * Transformar permisos agrupados para Mustache
     * Mustache no puede iterar sobre arrays asociativos correctamente
     */
    private function transformPermissionsForMustache(array $permissionsGrouped, array $assignedPermissionIds = []): array
    {
        $result = [];
        foreach ($permissionsGrouped as $module => $permissions) {
            foreach ($permissions as &$permission) {
                $permission['permission_id'] = (string)$permission['id'];
                if (!empty($assignedPermissionIds)) {
                    $permission['is_assigned'] = in_array($permission['id'], $assignedPermissionIds);
                }
            }

            $result[] = [
                'module_name' => $module,
                'module_name_capitalized' => ucfirst($module),
                'permissions' => $permissions,
                'permission_count' => count($permissions),
            ];
        }
        return $result;
    }

    /**
     * Formulario de creación
     */
    public function create(ServerRequestInterface $request): ResponseInterface
    {
        $permissionsGrouped = $this->permissionManager->getPermissionsGroupedByModule();
        $permissionsForMustache = $this->transformPermissionsForMustache($permissionsGrouped);

        $data = [
            'permissions_grouped' => $permissionsForMustache,
            'page_title' => 'Crear Rol',
        ];

        // Enriquecer con navegación
        $data = $this->enrichWithNavigation($data, '/admin/roles/create');

        return $this->renderWithLayout('admin/roles/create', $data);
    }

    /**
     * Procesar creación
     */
    public function store(ServerRequestInterface $request): ResponseInterface
    {
        $body = $request->getParsedBody();

        // Validar datos
        $errors = $this->validateRoleData($body);

        if (!empty($errors)) {
            $permissionsGrouped = $this->permissionManager->getPermissionsGroupedByModule();
            $permissionsForMustache = $this->transformPermissionsForMustache($permissionsGrouped);
            $data = [
                'errors' => $errors,
                'form_data' => $body,
                'permissions_grouped' => $permissionsForMustache,
                'page_title' => 'Crear Rol',
            ];
            return $this->renderWithLayout('admin/roles/create', $data);
        }

        // Crear rol
        $roleData = [
            'name' => $body['name'],
            'slug' => $this->generateSlug($body['name']),
            'description' => $body['description'] ?? '',
        ];
        $roleId = $this->roleManager->create($roleData);

        // Asignar permisos si se proporcionaron
        $permissionIds = [];
        if (isset($body['permissions']) && is_array($body['permissions'])) {
            $permissionIds = array_map('intval', $body['permissions']);
            $this->roleManager->syncPermissions($roleId, $permissionIds);
        }

        // Log audit event
        $this->logAudit(
            'role.created',
            'role',
            $roleId,
            null,
            array_merge($roleData, ['permissions' => $permissionIds])
        );

        return Response::redirect('/admin/roles?success=created');
    }

    /**
     * Formulario de edición - USA SESIÓN
     */
    public function edit(ServerRequestInterface $request): ResponseInterface
    {
        $body = $request->getParsedBody();
        $roleId = (int)($body['role_id'] ?? 0);

        if (!$roleId) {
            return Response::redirect('/admin/roles?error=invalid_id');
        }

        // GUARDAR ID EN SESIÓN
        $_SESSION['editing_role_id'] = $roleId;

        $role = $this->roleManager->getRoleById($roleId);
        if (!$role) {
            unset($_SESSION['editing_role_id']);
            return Response::redirect('/admin/roles?error=not_found');
        }

        // Obtener permisos del rol
        $rolePermissions = $this->roleManager->getRolePermissions($roleId);
        $rolePermissionIds = array_column($rolePermissions, 'id');

        // Obtener todos los permisos agrupados y transformar para Mustache
        $permissionsGrouped = $this->permissionManager->getPermissionsGroupedByModule();
        $permissionsForMustache = $this->transformPermissionsForMustache($permissionsGrouped, $rolePermissionIds);

        $data = [
            'role' => $role,
            'role_permissions' => $rolePermissions,
            'permissions_grouped' => $permissionsForMustache,
            'is_system_role' => !empty($role['is_system']),
            'page_title' => 'Editar Rol: ' . $role['name'],
            'editing_mode' => true,
        ];

        // Enriquecer con navegación
        $data = $this->enrichWithNavigation($data, '/admin/roles/edit');

        return $this->renderWithLayout('admin/roles/edit', $data);
    }

    /**
     * Procesar actualización - USA ID DE SESIÓN
     */
    public function update(ServerRequestInterface $request): ResponseInterface
    {
        // OBTENER ID DESDE SESIÓN
        $roleId = (int)($_SESSION['editing_role_id'] ?? 0);

        if (!$roleId) {
            return Response::redirect('/admin/roles?error=session_expired');
        }

        $body = $request->getParsedBody();

        $role = $this->roleManager->getRoleById($roleId);
        if (!$role) {
            unset($_SESSION['editing_role_id']);
            return Response::redirect('/admin/roles?error=not_found');
        }

        // No permitir editar roles del sistema (excepto permisos)
        $isSystemRole = !empty($role['is_system']);

        // Validar datos
        $errors = $this->validateRoleData($body, $roleId, $isSystemRole);

        if (!empty($errors)) {
            $rolePermissions = $this->roleManager->getRolePermissions($roleId);
            $rolePermissionIds = array_column($rolePermissions, 'id');
            $permissionsGrouped = $this->permissionManager->getPermissionsGroupedByModule();
            $permissionsForMustache = $this->transformPermissionsForMustache($permissionsGrouped, $rolePermissionIds);

            $data = [
                'errors' => $errors,
                'role' => array_merge($role, $body),
                'role_permissions' => $rolePermissions,
                'permissions_grouped' => $permissionsForMustache,
                'is_system_role' => $isSystemRole,
                'page_title' => 'Editar Rol',
                'editing_mode' => true,
            ];
            return $this->renderWithLayout('admin/roles/edit', $data);
        }

        // Obtener permisos actuales ANTES de actualizar (para audit log)
        $oldPermissionIds = array_column($this->roleManager->getRolePermissions($roleId), 'id');

        // Preparar datos de actualización
        $updateData = [];
        if (!$isSystemRole) {
            $updateData = [
                'name' => $body['name'],
                'description' => $body['description'] ?? '',
            ];
            $this->roleManager->update($roleId, $updateData);
        } else {
            // Para roles del sistema, solo actualizar descripción
            $updateData = [
                'description' => $body['description'] ?? '',
            ];
            $this->roleManager->update($roleId, $updateData);
        }

        // Actualizar permisos (permitido para todos los roles)
        $newPermissionIds = [];
        if (isset($body['permissions']) && is_array($body['permissions'])) {
            $newPermissionIds = array_map('intval', $body['permissions']);
            $this->roleManager->syncPermissions($roleId, $newPermissionIds);
        } else {
            $this->roleManager->syncPermissions($roleId, []);
        }

        // Log audit event
        $this->logAudit(
            'role.updated',
            'role',
            $roleId,
            array_merge($role, ['permissions' => $oldPermissionIds]),
            array_merge($updateData, ['permissions' => $newPermissionIds])
        );

        // LIMPIAR SESIÓN
        unset($_SESSION['editing_role_id']);

        return Response::redirect('/admin/roles?success=updated');
    }

    /**
     * Eliminar rol
     */
    public function delete(ServerRequestInterface $request): ResponseInterface
    {
        $body = $request->getParsedBody();
        $roleId = (int)($body['role_id'] ?? 0);

        if (!$roleId) {
            return Response::json(['error' => 'ID de rol no proporcionado'], 400);
        }

        $role = $this->roleManager->getRoleById($roleId);
        if (!$role) {
            return Response::json(['error' => 'Rol no encontrado'], 404);
        }

        // No permitir eliminar roles del sistema
        if (!empty($role['is_system'])) {
            return Response::json(['error' => 'No se pueden eliminar roles del sistema'], 400);
        }

        // Verificar si hay usuarios asignados
        $users = $this->roleManager->getRoleUsers($roleId);
        if (!empty($users)) {
            return Response::json([
                'error' => 'No se puede eliminar el rol porque tiene ' . count($users) . ' usuario(s) asignado(s)',
            ], 400);
        }

        // Get role data before deletion for audit log
        $roleData = $this->roleManager->getRoleById($roleId);
        $rolePermissions = array_column($this->roleManager->getRolePermissions($roleId), 'id');

        $success = $this->roleManager->delete($roleId);

        if ($success) {
            // Log audit event
            $this->logAudit(
                'role.deleted',
                'role',
                $roleId,
                array_merge($roleData, ['permissions' => $rolePermissions]),
                null
            );

            return Response::json(['success' => true, 'message' => 'Rol eliminado correctamente']);
        }

        return Response::json(['error' => 'Error al eliminar el rol'], 500);
    }

    /**
     * Validar datos de rol
     */
    private function validateRoleData(array $data, ?int $excludeRoleId = null, bool $isSystemRole = false): array
    {
        $errors = [];

        // Roles del sistema solo permiten cambiar descripción
        if ($isSystemRole) {
            return $errors;
        }

        if (empty($data['name'])) {
            $errors[] = 'El nombre del rol es requerido';
        }

        if (isset($data['level'])) {
            $level = (int)$data['level'];
            if ($level < 1 || $level > 100) {
                $errors[] = 'El nivel debe estar entre 1 y 100';
            }
        }

        return $errors;
    }

    /**
     * Generar slug desde nombre
     */
    private function generateSlug(string $name): string
    {
        $slug = strtolower($name);
        $slug = preg_replace('/[^a-z0-9]+/', '_', $slug);
        $slug = trim($slug, '_');
        return $slug;
    }
}
