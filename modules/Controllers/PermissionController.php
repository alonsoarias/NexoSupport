<?php

declare(strict_types=1);

namespace ISER\Controllers;

use ISER\Controllers\Traits\NavigationTrait;
use ISER\Core\Database\Database;
use ISER\Core\Http\Response;
use ISER\Core\View\MustacheRenderer;
use ISER\Core\Utils\Validator;
use ISER\Permission\PermissionManager;
use ISER\Roles\RoleManager;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * PermissionController - Gestión completa de permisos (REFACTORIZADO)
 *
 * PATRÓN DE SESIONES:
 * - Usa sesiones para almacenar ID durante edición
 * - IDs se pasan como permission_id (string) para compatibilidad con Mustache
 * - Sin exposición de IDs en URLs ni campos hidden
 */
class PermissionController
{
    use NavigationTrait;

    private PermissionManager $permissionManager;
    private RoleManager $roleManager;
    private MustacheRenderer $renderer;

    public function __construct(Database $db)
    {
        $this->permissionManager = new PermissionManager($db);
        $this->roleManager = new RoleManager($db);
        $this->renderer = MustacheRenderer::getInstance();
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
     * Lista de permisos agrupados por módulo
     */
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();

        // Obtener permisos agrupados por módulo
        $permissionsGrouped = $this->permissionManager->getPermissionsGroupedByModule();

        // CRÍTICO: Transformar array asociativo a indexado para Mustache
        // Mustache no puede iterar sobre arrays asociativos correctamente
        $permissionsForMustache = [];
        foreach ($permissionsGrouped as $module => $permissions) {
            // Enriquecer cada permiso con información de roles
            foreach ($permissions as &$permission) {
                $permissionId = (int)$permission['id'];
                $roles = $this->permissionManager->getPermissionRoles($permissionId);
                $permission['roles'] = $roles;
                $permission['role_count'] = count($roles);
                $permission['role_names'] = array_map(fn($r) => $r['name'], $roles);
                $permission['created_at_formatted'] = date('d/m/Y H:i', (int)$permission['created_at']);

                // CRÍTICO: Usar permission_id como string para Mustache
                $permission['permission_id'] = (string)$permission['id'];
            }

            // Agregar al array indexado con el módulo como propiedad
            $permissionsForMustache[] = [
                'module_name' => $module,
                'module_name_capitalized' => ucfirst($module),
                'permissions' => $permissions,
                'permission_count' => count($permissions),
            ];
        }

        // Obtener módulos disponibles
        $modules = $this->permissionManager->getModules();

        $data = [
            'permissions_grouped' => $permissionsForMustache,
            'modules' => $modules,
            'total_permissions' => $this->permissionManager->countPermissions(),
            'page_title' => __('permissions.management_title'),
        ];

        // Mensajes
        if (isset($queryParams['success'])) {
            $messages = [
                'created' => __('permissions.created_message', ['name' => '']),
                'updated' => __('permissions.updated_message', ['name' => '']),
                'deleted' => __('permissions.deleted_message', ['name' => '']),
            ];
            $data['success_message'] = $messages[$queryParams['success']] ?? null;
        }

        if (isset($queryParams['error'])) {
            $errors = [
                'invalid_id' => __('permissions.name_required'),
                'not_found' => __('errors.not_found'),
            ];
            $data['error_message'] = $errors[$queryParams['error']] ?? __('errors.unknown_error');
        }

        // Enriquecer con navegación
        $data = $this->enrichWithNavigation($data, '/admin/permissions');

        return $this->renderWithLayout('admin/permissions/index', $data);
    }

    /**
     * Formulario de creación
     */
    public function create(ServerRequestInterface $request): ResponseInterface
    {
        $modules = $this->permissionManager->getModules();

        $data = [
            'modules' => $modules,
            'page_title' => 'Crear Permiso',
        ];

        // Enriquecer con navegación
        $data = $this->enrichWithNavigation($data, '/admin/permissions/create');

        return $this->renderWithLayout('admin/permissions/create', $data);
    }

    /**
     * Procesar creación
     */
    public function store(ServerRequestInterface $request): ResponseInterface
    {
        $body = $request->getParsedBody();

        // Validar datos
        $errors = $this->validatePermissionData($body);

        if (!empty($errors)) {
            $modules = $this->permissionManager->getModules();
            $data = [
                'errors' => $errors,
                'form_data' => $body,
                'modules' => $modules,
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
                'form_data' => $body,
                'modules' => $modules,
                'page_title' => 'Crear Permiso',
            ];
            return $this->renderWithLayout('admin/permissions/create', $data);
        }

        return Response::redirect('/admin/permissions?success=created');
    }

    /**
     * Formulario de edición - USA SESIÓN
     */
    public function edit(ServerRequestInterface $request): ResponseInterface
    {
        $body = $request->getParsedBody();
        $permissionId = (int)($body['permission_id'] ?? 0);

        if (!$permissionId) {
            return Response::redirect('/admin/permissions?error=invalid_id');
        }

        // GUARDAR ID EN SESIÓN
        $_SESSION['editing_permission_id'] = $permissionId;

        $permission = $this->permissionManager->getPermissionById($permissionId);
        if (!$permission) {
            unset($_SESSION['editing_permission_id']);
            return Response::redirect('/admin/permissions?error=not_found');
        }

        // Obtener roles que tienen este permiso
        $roles = $this->permissionManager->getPermissionRoles($permissionId);

        // Obtener módulos existentes
        $modules = $this->permissionManager->getModules();

        $data = [
            'permission' => $permission,
            'permission_roles' => $roles,
            'modules' => $modules,
            'page_title' => 'Editar Permiso: ' . $permission['name'],
            'editing_mode' => true,
        ];

        // Enriquecer con navegación
        $data = $this->enrichWithNavigation($data, '/admin/permissions/edit');

        return $this->renderWithLayout('admin/permissions/edit', $data);
    }

    /**
     * Procesar actualización - USA ID DE SESIÓN
     */
    public function update(ServerRequestInterface $request): ResponseInterface
    {
        // OBTENER ID DESDE SESIÓN
        $permissionId = (int)($_SESSION['editing_permission_id'] ?? 0);

        if (!$permissionId) {
            return Response::redirect('/admin/permissions?error=session_expired');
        }

        $body = $request->getParsedBody();

        $permission = $this->permissionManager->getPermissionById($permissionId);
        if (!$permission) {
            unset($_SESSION['editing_permission_id']);
            return Response::redirect('/admin/permissions?error=not_found');
        }

        // Validar datos
        $errors = $this->validatePermissionData($body, $permissionId);

        if (!empty($errors)) {
            $roles = $this->permissionManager->getPermissionRoles($permissionId);
            $modules = $this->permissionManager->getModules();

            $data = [
                'errors' => $errors,
                'permission' => array_merge($permission, $body),
                'permission_roles' => $roles,
                'modules' => $modules,
                'page_title' => 'Editar Permiso',
                'editing_mode' => true,
            ];
            return $this->renderWithLayout('admin/permissions/edit', $data);
        }

        // Actualizar permiso (no se puede cambiar el slug)
        $success = $this->permissionManager->update($permissionId, [
            'name' => $body['name'],
            'description' => $body['description'] ?? '',
            'module' => $body['module'],
        ]);

        if (!$success) {
            $errors[] = 'Error al actualizar el permiso';
            $roles = $this->permissionManager->getPermissionRoles($permissionId);
            $modules = $this->permissionManager->getModules();

            $data = [
                'errors' => $errors,
                'permission' => array_merge($permission, $body),
                'permission_roles' => $roles,
                'modules' => $modules,
                'page_title' => 'Editar Permiso',
                'editing_mode' => true,
            ];
            return $this->renderWithLayout('admin/permissions/edit', $data);
        }

        // LIMPIAR SESIÓN
        unset($_SESSION['editing_permission_id']);

        return Response::redirect('/admin/permissions?success=updated');
    }

    /**
     * Eliminar permiso
     */
    public function delete(ServerRequestInterface $request): ResponseInterface
    {
        $body = $request->getParsedBody();
        $permissionId = (int)($body['permission_id'] ?? 0);

        if (!$permissionId) {
            return Response::json(['error' => 'ID de permiso no proporcionado'], 400);
        }

        $permission = $this->permissionManager->getPermissionById($permissionId);
        if (!$permission) {
            return Response::json(['error' => 'Permiso no encontrado'], 404);
        }

        // Verificar si hay roles asignados
        $roles = $this->permissionManager->getPermissionRoles($permissionId);
        if (!empty($roles)) {
            return Response::json([
                'error' => 'No se puede eliminar el permiso porque está asignado a ' . count($roles) . ' rol(es)',
            ], 400);
        }

        $success = $this->permissionManager->delete($permissionId);

        if ($success) {
            return Response::json(['success' => true, 'message' => __('permissions.deleted_message', ['name' => ''])]);
        }

        return Response::json(['error' => __('errors.delete_failed')], 500);
    }

    /**
     * Validar datos de permiso (usando Validator centralizado)
     */
    private function validatePermissionData(array $data, ?int $excludePermissionId = null): array
    {
        // Define validation rules
        $rules = [
            'name' => ['required', 'maxLength:100'],
            'module' => ['required', 'maxLength:50'],
            'description' => ['maxLength:500'],
        ];

        // Slug required only when creating
        if ($excludePermissionId === null) {
            $rules['slug'] = ['required', 'slug', 'maxLength:100'];
        }

        // Run centralized validation
        $errors = Validator::validate($data, $rules);

        // Add custom business logic validations (uniqueness check for slug)
        if ($excludePermissionId === null && empty($errors['slug'])) {
            if ($this->permissionManager->getPermissionBySlug($data['slug'])) {
                $errors['slug'] = 'El slug ya está en uso';
            }
        }

        // Convert field-keyed errors to simple array for backwards compatibility
        return array_values($errors);
    }
}
