<?php

declare(strict_types=1);

namespace ISER\Controllers;

use ISER\Core\Controllers\BaseController;
use ISER\Core\Database\Database;
use ISER\Roles\RoleManager;
use ISER\Roles\PermissionRepository;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * RoleController - Gestión completa de roles (REFACTORIZADO con BaseController)
 *
 * PATRÓN DE SESIONES:
 * - Usa sesiones para almacenar ID durante edición
 * - IDs se pasan como role_id (string) para compatibilidad con Mustache
 * - Sin exposición de IDs en URLs ni campos hidden
 *
 * Extiende BaseController para reducir código duplicado.
 */
class RoleController extends BaseController
{
    private RoleManager $roleManager;
    private PermissionRepository $permissionRepository;

    public function __construct(Database $db)
    {
        parent::__construct($db);
        $this->roleManager = new RoleManager($db);
        $this->permissionRepository = new PermissionRepository($db);
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
            'page_title' => __('roles.management_title'),
        ];

        // Mensajes flash (ahora manejados por BaseController)
        if (isset($queryParams['success'])) {
            $messages = [
                'created' => __('roles.created_message', ['name' => '']),
                'updated' => __('roles.updated_message', ['name' => '']),
                'deleted' => __('roles.deleted_message', ['name' => '']),
            ];
            $message = $messages[$queryParams['success']] ?? null;
            if ($message) {
                $this->flash('success', $message);
            }
        }

        if (isset($queryParams['error'])) {
            $errors = [
                'invalid_id' => __('roles.name_required'),
                'not_found' => __('errors.not_found'),
                'system_role' => __('roles.system_role_error'),
            ];
            $message = $errors[$queryParams['error']] ?? __('errors.unknown_error');
            $this->flash('error', $message);
        }

        // Usar render() de BaseController con navegación automática
        return $this->render('admin/roles/index', $data, '/admin/roles');
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
                $permission['is_assigned'] = in_array($permission['id'], $assignedPermissionIds);
                $permission['permission_id'] = (string)$permission['id'];
            }

            $result[] = [
                'module_name' => $module,
                'module_name_capitalized' => ucfirst($module),
                'permissions' => $permissions,
            ];
        }
        return $result;
    }

    /**
     * Formulario de creación de rol
     */
    public function create(ServerRequestInterface $request): ResponseInterface
    {
        // Obtener permisos agrupados por módulo
        $permissionsGrouped = $this->permissionRepository->getPermissionsGroupedByModule();
        $permissionsForMustache = $this->transformPermissionsForMustache($permissionsGrouped);

        $data = [
            'page_title' => __('roles.create_title'),
            'permissions_grouped' => $permissionsForMustache,
            'form_action' => '/admin/roles/store',
        ];

        return $this->render('admin/roles/create', $data, '/admin/roles');
    }

    /**
     * Guardar nuevo rol
     */
    public function store(ServerRequestInterface $request): ResponseInterface
    {
        $body = $request->getParsedBody();

        // Validación básica
        if (empty($body['name']) || empty($body['slug'])) {
            return $this->redirect('/admin/roles/create?error=validation');
        }

        // Crear rol
        $roleId = $this->roleManager->create([
            'name' => $body['name'],
            'slug' => $body['slug'],
            'description' => $body['description'] ?? '',
            'is_system' => 0,
        ]);

        if ($roleId === false) {
            return $this->redirect('/admin/roles/create?error=create_failed');
        }

        // Asignar permisos seleccionados
        $selectedPermissions = $body['permissions'] ?? [];
        if (!empty($selectedPermissions)) {
            foreach ($selectedPermissions as $permissionId) {
                $this->roleManager->assignPermission((int)$roleId, (int)$permissionId);
            }
        }

        // Log audit (usar método de BaseController)
        $this->logAudit('create', 'role', $roleId, null, [
            'name' => $body['name'],
            'slug' => $body['slug'],
        ]);

        return $this->redirect('/admin/roles?success=created');
    }

    /**
     * Formulario de edición de rol
     */
    public function edit(ServerRequestInterface $request): ResponseInterface
    {
        // El ID viene de la sesión (patrón de seguridad)
        $roleId = $_SESSION['editing_role_id'] ?? null;

        if (!$roleId) {
            return $this->redirect('/admin/roles?error=invalid_id');
        }

        $role = $this->roleManager->getRoleById((int)$roleId);

        if (!$role) {
            unset($_SESSION['editing_role_id']);
            return $this->redirect('/admin/roles?error=not_found');
        }

        // Obtener permisos del rol
        $assignedPermissions = $this->roleManager->getRolePermissions((int)$roleId);
        $assignedPermissionIds = array_column($assignedPermissions, 'id');

        // Obtener todos los permisos agrupados
        $permissionsGrouped = $this->permissionRepository->getPermissionsGroupedByModule();
        $permissionsForMustache = $this->transformPermissionsForMustache($permissionsGrouped, $assignedPermissionIds);

        $data = [
            'page_title' => __('roles.edit_title'),
            'role' => $role,
            'role_id' => (string)$role['id'],
            'permissions_grouped' => $permissionsForMustache,
            'form_action' => '/admin/roles/update',
            'is_system_role' => !empty($role['is_system']),
        ];

        return $this->render('admin/roles/edit', $data, '/admin/roles');
    }

    /**
     * Actualizar rol existente
     */
    public function update(ServerRequestInterface $request): ResponseInterface
    {
        $body = $request->getParsedBody();
        $roleId = $_SESSION['editing_role_id'] ?? null;

        if (!$roleId) {
            return $this->redirect('/admin/roles?error=invalid_id');
        }

        $role = $this->roleManager->getRoleById((int)$roleId);

        if (!$role) {
            unset($_SESSION['editing_role_id']);
            return $this->redirect('/admin/roles?error=not_found');
        }

        // No se pueden editar roles del sistema
        if ($role['is_system']) {
            return $this->redirect('/admin/roles?error=system_role');
        }

        // Validación
        if (empty($body['name'])) {
            return $this->redirect('/admin/roles/edit?error=validation');
        }

        $oldValues = $role;

        // Actualizar rol
        $success = $this->roleManager->update((int)$roleId, [
            'name' => $body['name'],
            'description' => $body['description'] ?? '',
        ]);

        if (!$success) {
            return $this->redirect('/admin/roles/edit?error=update_failed');
        }

        // Sincronizar permisos
        $selectedPermissions = $body['permissions'] ?? [];
        $this->roleManager->syncPermissions((int)$roleId, $selectedPermissions);

        // Log audit
        $this->logAudit('update', 'role', (int)$roleId, $oldValues, [
            'name' => $body['name'],
            'description' => $body['description'] ?? '',
        ]);

        // Limpiar sesión
        unset($_SESSION['editing_role_id']);

        return $this->redirect('/admin/roles?success=updated');
    }

    /**
     * Establecer rol para edición (guarda ID en sesión)
     */
    public function setEditing(ServerRequestInterface $request): ResponseInterface
    {
        $body = $request->getParsedBody();
        $roleId = $body['role_id'] ?? null;

        if ($roleId) {
            $_SESSION['editing_role_id'] = (int)$roleId;
            return $this->redirect('/admin/roles/edit');
        }

        return $this->redirect('/admin/roles?error=invalid_id');
    }

    /**
     * Eliminar rol
     */
    public function delete(ServerRequestInterface $request): ResponseInterface
    {
        $body = $request->getParsedBody();
        $roleId = $body['role_id'] ?? null;

        if (!$roleId) {
            return $this->jsonError('ID de rol inválido', [], 400);
        }

        $role = $this->roleManager->getRoleById((int)$roleId);

        if (!$role) {
            return $this->jsonError('Rol no encontrado', [], 404);
        }

        // No se pueden eliminar roles del sistema
        if ($role['is_system']) {
            return $this->jsonError('No se pueden eliminar roles del sistema', [], 403);
        }

        $success = $this->roleManager->delete((int)$roleId);

        if (!$success) {
            return $this->jsonError('Error al eliminar rol', [], 500);
        }

        // Log audit
        $this->logAudit('delete', 'role', (int)$roleId, $role, null);

        return $this->jsonSuccess('Rol eliminado correctamente');
    }
}
