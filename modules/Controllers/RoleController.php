<?php

declare(strict_types=1);

namespace ISER\Controllers;

use ISER\Core\Database\Database;
use ISER\Core\Http\Response;
use ISER\Core\View\MustacheRenderer;
use ISER\Role\RoleManager;
use ISER\Permission\PermissionManager;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Role Controller
 *
 * Gestiona roles y sus permisos
 */
class RoleController
{
    private Database $db;
    private RoleManager $roleManager;
    private PermissionManager $permissionManager;
    private MustacheRenderer $view;

    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->roleManager = new RoleManager($db);
        $this->permissionManager = new PermissionManager($db);
        $this->view = MustacheRenderer::getInstance();
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

        // Filtros
        $filters = [];

        // Obtener roles
        $roles = $this->roleManager->getRoles($perPage, $offset, $filters);
        $totalRoles = $this->roleManager->countRoles($filters);
        $totalPages = (int)ceil($totalRoles / $perPage);

        // Enriquecer roles con contadores
        foreach ($roles as &$role) {
            $role['users'] = $this->roleManager->getRoleUsers((int)$role['id']);
            $role['user_count'] = count($role['users']);
            $role['permissions'] = $this->roleManager->getRolePermissions((int)$role['id']);
            $role['permission_count'] = count($role['permissions']);
            $role['created_at_formatted'] = date('d/m/Y H:i', (int)$role['created_at']);
            $role['is_system_role'] = !empty($role['is_system']);
        }

        $data = [
            'roles' => $roles,
            'total_roles' => $totalRoles,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'has_previous' => $page > 1,
            'has_next' => $page < $totalPages,
            'previous_page' => $page - 1,
            'next_page' => $page + 1,
        ];

        return $this->renderWithLayout('admin/roles/index', $data);
    }

    /**
     * Formulario de creación de rol
     */
    public function create(ServerRequestInterface $request): ResponseInterface
    {
        // Obtener todos los permisos agrupados por módulo
        $permissionsGrouped = $this->permissionManager->getPermissionsGroupedByModule();

        $data = [
            'permissions_grouped' => $permissionsGrouped,
            'page_title' => 'Crear Rol',
        ];

        return $this->renderWithLayout('admin/roles/create', $data);
    }

    /**
     * Procesar creación de rol
     */
    public function store(ServerRequestInterface $request): ResponseInterface
    {
        $body = $request->getParsedBody();

        // Validar datos
        $errors = [];

        if (empty($body['name'])) {
            $errors[] = 'El nombre del rol es requerido';
        }

        if (empty($body['slug'])) {
            $errors[] = 'El slug es requerido';
        } elseif ($this->roleManager->getRoleBySlug($body['slug'])) {
            $errors[] = 'El slug ya está en uso';
        }

        if (empty($body['level']) || !is_numeric($body['level'])) {
            $errors[] = 'El nivel es requerido y debe ser numérico';
        }

        // Si hay errores, regresar al formulario
        if (!empty($errors)) {
            $permissionsGrouped = $this->permissionManager->getPermissionsGroupedByModule();
            $data = [
                'errors' => $errors,
                'permissions_grouped' => $permissionsGrouped,
                'form_data' => $body,
                'page_title' => 'Crear Rol',
            ];
            return $this->renderWithLayout('admin/roles/create', $data);
        }

        // Crear rol
        $roleId = $this->roleManager->create([
            'name' => $body['name'],
            'slug' => $body['slug'],
            'description' => $body['description'] ?? '',
            'level' => (int)$body['level'],
            'is_system' => 0,
        ]);

        if ($roleId === 0) {
            $errors[] = 'Error al crear el rol';
            $permissionsGrouped = $this->permissionManager->getPermissionsGroupedByModule();
            $data = [
                'errors' => $errors,
                'permissions_grouped' => $permissionsGrouped,
                'form_data' => $body,
                'page_title' => 'Crear Rol',
            ];
            return $this->renderWithLayout('admin/roles/create', $data);
        }

        // Asignar permisos si se seleccionaron
        if (!empty($body['permissions']) && is_array($body['permissions'])) {
            $this->roleManager->syncPermissions($roleId, array_map('intval', $body['permissions']));
        }

        // Redirigir con mensaje de éxito
        return Response::redirect('/admin/roles?success=created');
    }

    /**
     * Formulario de edición de rol
     */
    public function edit(ServerRequestInterface $request): ResponseInterface
    {
        $id = (int)$request->getAttribute('id');

        $role = $this->roleManager->getRoleById($id);
        if (!$role) {
            return Response::json(['error' => 'Rol no encontrado'], 404);
        }

        // Obtener permisos del rol
        $rolePermissions = $this->roleManager->getRolePermissions($id);
        $rolePermissionIds = array_column($rolePermissions, 'id');

        // Obtener todos los permisos agrupados
        $permissionsGrouped = $this->permissionManager->getPermissionsGroupedByModule();

        // Marcar permisos asignados
        foreach ($permissionsGrouped as $module => &$permissions) {
            foreach ($permissions as &$permission) {
                $permission['is_assigned'] = in_array($permission['id'], $rolePermissionIds);
            }
        }

        $data = [
            'role' => $role,
            'role_permissions' => $rolePermissions,
            'permissions_grouped' => $permissionsGrouped,
            'is_system_role' => !empty($role['is_system']),
            'page_title' => 'Editar Rol',
        ];

        return $this->renderWithLayout('admin/roles/edit', $data);
    }

    /**
     * Procesar actualización de rol
     */
    public function update(ServerRequestInterface $request): ResponseInterface
    {
        $id = (int)$request->getAttribute('id');
        $body = $request->getParsedBody();

        $role = $this->roleManager->getRoleById($id);
        if (!$role) {
            return Response::json(['error' => 'Rol no encontrado'], 404);
        }

        // No permitir editar roles del sistema
        if (!empty($role['is_system'])) {
            return Response::json(['error' => 'No se pueden editar roles del sistema'], 400);
        }

        // Validar datos
        $errors = [];

        if (empty($body['name'])) {
            $errors[] = 'El nombre del rol es requerido';
        }

        if (empty($body['level']) || !is_numeric($body['level'])) {
            $errors[] = 'El nivel es requerido y debe ser numérico';
        }

        // Si hay errores, regresar al formulario
        if (!empty($errors)) {
            $rolePermissions = $this->roleManager->getRolePermissions($id);
            $rolePermissionIds = array_column($rolePermissions, 'id');
            $permissionsGrouped = $this->permissionManager->getPermissionsGroupedByModule();

            foreach ($permissionsGrouped as $module => &$permissions) {
                foreach ($permissions as &$permission) {
                    $permission['is_assigned'] = in_array($permission['id'], $rolePermissionIds);
                }
            }

            $data = [
                'errors' => $errors,
                'role' => array_merge($role, $body),
                'role_permissions' => $rolePermissions,
                'permissions_grouped' => $permissionsGrouped,
                'is_system_role' => false,
                'page_title' => 'Editar Rol',
            ];
            return $this->renderWithLayout('admin/roles/edit', $data);
        }

        // Actualizar rol
        $success = $this->roleManager->update($id, [
            'name' => $body['name'],
            'description' => $body['description'] ?? '',
            'level' => (int)$body['level'],
        ]);

        if (!$success) {
            $errors[] = 'Error al actualizar el rol';
            $rolePermissions = $this->roleManager->getRolePermissions($id);
            $rolePermissionIds = array_column($rolePermissions, 'id');
            $permissionsGrouped = $this->permissionManager->getPermissionsGroupedByModule();

            foreach ($permissionsGrouped as $module => &$permissions) {
                foreach ($permissions as &$permission) {
                    $permission['is_assigned'] = in_array($permission['id'], $rolePermissionIds);
                }
            }

            $data = [
                'errors' => $errors,
                'role' => array_merge($role, $body),
                'role_permissions' => $rolePermissions,
                'permissions_grouped' => $permissionsGrouped,
                'is_system_role' => false,
                'page_title' => 'Editar Rol',
            ];
            return $this->renderWithLayout('admin/roles/edit', $data);
        }

        // Sincronizar permisos
        if (isset($body['permissions']) && is_array($body['permissions'])) {
            $this->roleManager->syncPermissions($id, array_map('intval', $body['permissions']));
        } else {
            // Si no se enviaron permisos, limpiar todos
            $this->roleManager->syncPermissions($id, []);
        }

        // Redirigir con mensaje de éxito
        return Response::redirect('/admin/roles?success=updated');
    }

    /**
     * Eliminar rol
     */
    public function delete(ServerRequestInterface $request): ResponseInterface
    {
        $id = (int)$request->getAttribute('id');

        $role = $this->roleManager->getRoleById($id);
        if (!$role) {
            return Response::json(['error' => 'Rol no encontrado'], 404);
        }

        // No permitir eliminar roles del sistema
        if (!empty($role['is_system'])) {
            return Response::json(['error' => 'No se pueden eliminar roles del sistema'], 400);
        }

        // Verificar si hay usuarios asignados
        $users = $this->roleManager->getRoleUsers($id);
        if (!empty($users)) {
            return Response::json([
                'error' => 'No se puede eliminar el rol porque tiene usuarios asignados',
                'user_count' => count($users)
            ], 400);
        }

        // Eliminar rol
        $success = $this->roleManager->delete($id);

        if ($success) {
            return Response::json(['success' => true, 'message' => 'Rol eliminado correctamente']);
        }

        return Response::json(['error' => 'Error al eliminar el rol'], 500);
    }

    /**
     * Renderizar vista con layout
     */
    private function renderWithLayout(string $template, array $data): ResponseInterface
    {
        // Agregar datos de sesión
        $data['user'] = [
            'username' => $_SESSION['username'] ?? 'Usuario',
            'email' => $_SESSION['email'] ?? '',
        ];

        $html = $this->view->render($template, $data);
        return Response::html($html);
    }
}
