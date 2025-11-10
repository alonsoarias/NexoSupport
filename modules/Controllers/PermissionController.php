<?php

declare(strict_types=1);

namespace ISER\Controllers;

use ISER\Core\Database\Database;
use ISER\Core\Http\Response;
use ISER\Core\View\MustacheRenderer;
use ISER\Permission\PermissionManager;
use ISER\Role\RoleManager;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Permission Controller
 *
 * Gestiona permisos del sistema
 */
class PermissionController
{
    private Database $db;
    private PermissionManager $permissionManager;
    private RoleManager $roleManager;
    private MustacheRenderer $view;

    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->permissionManager = new PermissionManager($db);
        $this->roleManager = new RoleManager($db);
        $this->view = MustacheRenderer::getInstance();
    }

    /**
     * Lista de permisos agrupados por módulo
     */
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        // Obtener permisos agrupados por módulo
        $permissionsGrouped = $this->permissionManager->getPermissionsGroupedByModule();

        // Enriquecer cada permiso con información de roles
        foreach ($permissionsGrouped as $module => &$permissions) {
            foreach ($permissions as &$permission) {
                $roles = $this->permissionManager->getPermissionRoles((int)$permission['id']);
                $permission['roles'] = $roles;
                $permission['role_count'] = count($roles);
                $permission['role_names'] = array_map(fn($r) => $r['name'], $roles);
                $permission['created_at_formatted'] = date('d/m/Y H:i', (int)$permission['created_at']);
            }
        }

        // Obtener módulos disponibles
        $modules = $this->permissionManager->getModules();

        $data = [
            'permissions_grouped' => $permissionsGrouped,
            'modules' => $modules,
            'total_permissions' => $this->permissionManager->countPermissions(),
        ];

        return $this->renderWithLayout('admin/permissions/index', $data);
    }

    /**
     * Formulario de creación de permiso
     */
    public function create(ServerRequestInterface $request): ResponseInterface
    {
        // Obtener módulos existentes
        $modules = $this->permissionManager->getModules();

        $data = [
            'modules' => $modules,
            'page_title' => 'Crear Permiso',
        ];

        return $this->renderWithLayout('admin/permissions/create', $data);
    }

    /**
     * Procesar creación de permiso
     */
    public function store(ServerRequestInterface $request): ResponseInterface
    {
        $body = $request->getParsedBody();

        // Validar datos
        $errors = [];

        if (empty($body['name'])) {
            $errors[] = 'El nombre del permiso es requerido';
        }

        if (empty($body['slug'])) {
            $errors[] = 'El slug es requerido';
        } elseif ($this->permissionManager->getPermissionBySlug($body['slug'])) {
            $errors[] = 'El slug ya está en uso';
        }

        if (empty($body['module'])) {
            $errors[] = 'El módulo es requerido';
        }

        // Si hay errores, regresar al formulario
        if (!empty($errors)) {
            $modules = $this->permissionManager->getModules();
            $data = [
                'errors' => $errors,
                'modules' => $modules,
                'form_data' => $body,
                'page_title' => 'Crear Permiso',
            ];
            return $this->renderWithLayout('admin/permissions/create', $data);
        }

        // Crear permiso
        $permissionId = $this->permissionManager->create([
            'name' => $body['name'],
            'slug' => $body['slug'],
            'description' => $body['description'] ?? '',
            'module' => $body['module'],
        ]);

        if ($permissionId === 0) {
            $errors[] = 'Error al crear el permiso';
            $modules = $this->permissionManager->getModules();
            $data = [
                'errors' => $errors,
                'modules' => $modules,
                'form_data' => $body,
                'page_title' => 'Crear Permiso',
            ];
            return $this->renderWithLayout('admin/permissions/create', $data);
        }

        // Redirigir con mensaje de éxito
        return Response::redirect('/admin/permissions?success=created');
    }

    /**
     * Formulario de edición de permiso
     */
    public function edit(ServerRequestInterface $request): ResponseInterface
    {
        $id = (int)$request->getAttribute('id');

        $permission = $this->permissionManager->getPermissionById($id);
        if (!$permission) {
            return Response::json(['error' => 'Permiso no encontrado'], 404);
        }

        // Obtener roles que tienen este permiso
        $roles = $this->permissionManager->getPermissionRoles($id);

        // Obtener módulos existentes
        $modules = $this->permissionManager->getModules();

        $data = [
            'permission' => $permission,
            'permission_roles' => $roles,
            'modules' => $modules,
            'page_title' => 'Editar Permiso',
        ];

        return $this->renderWithLayout('admin/permissions/edit', $data);
    }

    /**
     * Procesar actualización de permiso
     */
    public function update(ServerRequestInterface $request): ResponseInterface
    {
        $id = (int)$request->getAttribute('id');
        $body = $request->getParsedBody();

        $permission = $this->permissionManager->getPermissionById($id);
        if (!$permission) {
            return Response::json(['error' => 'Permiso no encontrado'], 404);
        }

        // Validar datos
        $errors = [];

        if (empty($body['name'])) {
            $errors[] = 'El nombre del permiso es requerido';
        }

        if (empty($body['module'])) {
            $errors[] = 'El módulo es requerido';
        }

        // Si hay errores, regresar al formulario
        if (!empty($errors)) {
            $roles = $this->permissionManager->getPermissionRoles($id);
            $modules = $this->permissionManager->getModules();

            $data = [
                'errors' => $errors,
                'permission' => array_merge($permission, $body),
                'permission_roles' => $roles,
                'modules' => $modules,
                'page_title' => 'Editar Permiso',
            ];
            return $this->renderWithLayout('admin/permissions/edit', $data);
        }

        // Actualizar permiso (no se puede cambiar el slug)
        $success = $this->permissionManager->update($id, [
            'name' => $body['name'],
            'description' => $body['description'] ?? '',
            'module' => $body['module'],
        ]);

        if (!$success) {
            $errors[] = 'Error al actualizar el permiso';
            $roles = $this->permissionManager->getPermissionRoles($id);
            $modules = $this->permissionManager->getModules();

            $data = [
                'errors' => $errors,
                'permission' => array_merge($permission, $body),
                'permission_roles' => $roles,
                'modules' => $modules,
                'page_title' => 'Editar Permiso',
            ];
            return $this->renderWithLayout('admin/permissions/edit', $data);
        }

        // Redirigir con mensaje de éxito
        return Response::redirect('/admin/permissions?success=updated');
    }

    /**
     * Eliminar permiso
     */
    public function delete(ServerRequestInterface $request): ResponseInterface
    {
        $id = (int)$request->getAttribute('id');

        $permission = $this->permissionManager->getPermissionById($id);
        if (!$permission) {
            return Response::json(['error' => 'Permiso no encontrado'], 404);
        }

        // Verificar si hay roles asignados
        $roles = $this->permissionManager->getPermissionRoles($id);
        if (!empty($roles)) {
            return Response::json([
                'error' => 'No se puede eliminar el permiso porque está asignado a roles',
                'role_count' => count($roles)
            ], 400);
        }

        // Eliminar permiso
        $success = $this->permissionManager->delete($id);

        if ($success) {
            return Response::json(['success' => true, 'message' => 'Permiso eliminado correctamente']);
        }

        return Response::json(['error' => 'Error al eliminar el permiso'], 500);
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
