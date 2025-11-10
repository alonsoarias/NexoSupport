<?php

declare(strict_types=1);

namespace ISER\Controllers;

use ISER\Core\Database\Database;
use ISER\Core\Http\Response;
use ISER\Core\View\MustacheRenderer;
use ISER\User\UserManager;
use ISER\Role\RoleManager;
use ISER\Permission\PermissionManager;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * User Management Controller
 *
 * Gestiona usuarios, roles y permisos del sistema
 */
class UserManagementController
{
    private Database $db;
    private UserManager $userManager;
    private RoleManager $roleManager;
    private PermissionManager $permissionManager;
    private MustacheRenderer $view;

    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->userManager = new UserManager($db);
        $this->roleManager = new RoleManager($db);
        $this->permissionManager = new PermissionManager($db);
        $this->view = MustacheRenderer::getInstance();
    }

    /**
     * Lista de usuarios con paginación y filtros
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
        if (!empty($queryParams['status'])) {
            $filters['status'] = $queryParams['status'];
        }
        if (!empty($queryParams['search'])) {
            $filters['search'] = $queryParams['search'];
        }
        if (isset($queryParams['deleted']) && $queryParams['deleted'] === '1') {
            $filters['deleted'] = true;
        }

        // Obtener usuarios
        $users = $this->userManager->getUsers($perPage, $offset, $filters);
        $totalUsers = $this->userManager->countUsers($filters);
        $totalPages = (int)ceil($totalUsers / $perPage);

        // Enriquecer usuarios con sus roles
        foreach ($users as &$user) {
            $user['roles'] = $this->userManager->getUserRoles((int)$user['id']);
            $user['role_names'] = array_map(fn($r) => $r['name'], $user['roles']);
            $user['created_at_formatted'] = date('d/m/Y H:i', (int)$user['created_at']);
            $user['is_deleted'] = !empty($user['deleted_at']);
            $user['is_active'] = ($user['status'] ?? 'active') === 'active';
            $user['is_suspended'] = ($user['status'] ?? 'active') === 'suspended';
        }

        // Preparar datos para la vista
        $data = [
            'users' => $users,
            'total_users' => $totalUsers,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'has_previous' => $page > 1,
            'has_next' => $page < $totalPages,
            'previous_page' => $page - 1,
            'next_page' => $page + 1,
            'filters' => $filters,
            'showing_deleted' => isset($filters['deleted']),
        ];

        return $this->renderWithLayout('admin/users/index', $data);
    }

    /**
     * Formulario de creación de usuario
     */
    public function create(ServerRequestInterface $request): ResponseInterface
    {
        // Obtener todos los roles disponibles
        $roles = $this->roleManager->getRoles(100, 0);

        $data = [
            'roles' => $roles,
            'page_title' => 'Crear Usuario',
        ];

        return $this->renderWithLayout('admin/users/create', $data);
    }

    /**
     * Procesar creación de usuario
     */
    public function store(ServerRequestInterface $request): ResponseInterface
    {
        $body = $request->getParsedBody();

        // Validar datos requeridos
        $errors = [];

        if (empty($body['username'])) {
            $errors[] = 'El nombre de usuario es requerido';
        } elseif ($this->userManager->usernameExists($body['username'])) {
            $errors[] = 'El nombre de usuario ya está en uso';
        }

        if (empty($body['email'])) {
            $errors[] = 'El email es requerido';
        } elseif ($this->userManager->emailExists($body['email'])) {
            $errors[] = 'El email ya está en uso';
        }

        if (empty($body['password'])) {
            $errors[] = 'La contraseña es requerida';
        } elseif (strlen($body['password']) < 8) {
            $errors[] = 'La contraseña debe tener al menos 8 caracteres';
        }

        if (empty($body['first_name'])) {
            $errors[] = 'El nombre es requerido';
        }

        if (empty($body['last_name'])) {
            $errors[] = 'El apellido es requerido';
        }

        // Si hay errores, regresar al formulario
        if (!empty($errors)) {
            $roles = $this->roleManager->getRoles(100, 0);
            $data = [
                'errors' => $errors,
                'roles' => $roles,
                'form_data' => $body,
                'page_title' => 'Crear Usuario',
            ];
            return $this->renderWithLayout('admin/users/create', $data);
        }

        // Crear usuario
        $userId = $this->userManager->create([
            'username' => $body['username'],
            'email' => $body['email'],
            'password' => $body['password'],
            'first_name' => $body['first_name'],
            'last_name' => $body['last_name'],
            'status' => $body['status'] ?? 'active',
        ]);

        if ($userId === false) {
            $errors[] = 'Error al crear el usuario';
            $roles = $this->roleManager->getRoles(100, 0);
            $data = [
                'errors' => $errors,
                'roles' => $roles,
                'form_data' => $body,
                'page_title' => 'Crear Usuario',
            ];
            return $this->renderWithLayout('admin/users/create', $data);
        }

        // Asignar roles si se seleccionaron
        if (!empty($body['roles']) && is_array($body['roles'])) {
            $currentUserId = $_SESSION['user_id'] ?? null;
            $this->userManager->syncRoles($userId, array_map('intval', $body['roles']), $currentUserId);
        }

        // Redirigir a la lista con mensaje de éxito
        return Response::redirect('/admin/users?success=created');
    }

    /**
     * Formulario de edición de usuario
     */
    public function edit(ServerRequestInterface $request): ResponseInterface
    {
        $id = (int)$request->getAttribute('id');

        $user = $this->userManager->getUserById($id);
        if (!$user) {
            return Response::json(['error' => 'Usuario no encontrado'], 404);
        }

        // Obtener roles del usuario
        $userRoles = $this->userManager->getUserRoles($id);
        $userRoleIds = array_column($userRoles, 'id');

        // Obtener todos los roles disponibles
        $allRoles = $this->roleManager->getRoles(100, 0);

        // Marcar roles asignados
        foreach ($allRoles as &$role) {
            $role['is_assigned'] = in_array($role['id'], $userRoleIds);
        }

        $data = [
            'user' => $user,
            'user_roles' => $userRoles,
            'all_roles' => $allRoles,
            'page_title' => 'Editar Usuario',
        ];

        return $this->renderWithLayout('admin/users/edit', $data);
    }

    /**
     * Procesar actualización de usuario
     */
    public function update(ServerRequestInterface $request): ResponseInterface
    {
        $id = (int)$request->getAttribute('id');
        $body = $request->getParsedBody();

        $user = $this->userManager->getUserById($id);
        if (!$user) {
            return Response::json(['error' => 'Usuario no encontrado'], 404);
        }

        // Validar datos
        $errors = [];

        if (empty($body['username'])) {
            $errors[] = 'El nombre de usuario es requerido';
        } elseif ($this->userManager->usernameExists($body['username'], $id)) {
            $errors[] = 'El nombre de usuario ya está en uso';
        }

        if (empty($body['email'])) {
            $errors[] = 'El email es requerido';
        } elseif ($this->userManager->emailExists($body['email'], $id)) {
            $errors[] = 'El email ya está en uso';
        }

        if (empty($body['first_name'])) {
            $errors[] = 'El nombre es requerido';
        }

        if (empty($body['last_name'])) {
            $errors[] = 'El apellido es requerido';
        }

        // Validar contraseña si se proporciona
        if (!empty($body['password']) && strlen($body['password']) < 8) {
            $errors[] = 'La contraseña debe tener al menos 8 caracteres';
        }

        // Si hay errores, regresar al formulario
        if (!empty($errors)) {
            $userRoles = $this->userManager->getUserRoles($id);
            $userRoleIds = array_column($userRoles, 'id');
            $allRoles = $this->roleManager->getRoles(100, 0);

            foreach ($allRoles as &$role) {
                $role['is_assigned'] = in_array($role['id'], $userRoleIds);
            }

            $data = [
                'errors' => $errors,
                'user' => array_merge($user, $body),
                'user_roles' => $userRoles,
                'all_roles' => $allRoles,
                'page_title' => 'Editar Usuario',
            ];
            return $this->renderWithLayout('admin/users/edit', $data);
        }

        // Preparar datos para actualización
        $updateData = [
            'username' => $body['username'],
            'email' => $body['email'],
            'first_name' => $body['first_name'],
            'last_name' => $body['last_name'],
            'status' => $body['status'] ?? 'active',
        ];

        // Agregar contraseña solo si se proporcionó
        if (!empty($body['password'])) {
            $updateData['password'] = $body['password'];
        }

        // Actualizar usuario
        $success = $this->userManager->update($id, $updateData);

        if (!$success) {
            $errors[] = 'Error al actualizar el usuario';
            $userRoles = $this->userManager->getUserRoles($id);
            $userRoleIds = array_column($userRoles, 'id');
            $allRoles = $this->roleManager->getRoles(100, 0);

            foreach ($allRoles as &$role) {
                $role['is_assigned'] = in_array($role['id'], $userRoleIds);
            }

            $data = [
                'errors' => $errors,
                'user' => array_merge($user, $body),
                'user_roles' => $userRoles,
                'all_roles' => $allRoles,
                'page_title' => 'Editar Usuario',
            ];
            return $this->renderWithLayout('admin/users/edit', $data);
        }

        // Sincronizar roles
        if (isset($body['roles']) && is_array($body['roles'])) {
            $currentUserId = $_SESSION['user_id'] ?? null;
            $this->userManager->syncRoles($id, array_map('intval', $body['roles']), $currentUserId);
        } else {
            // Si no se enviaron roles, limpiar todos
            $this->userManager->syncRoles($id, [], null);
        }

        // Redirigir con mensaje de éxito
        return Response::redirect('/admin/users?success=updated');
    }

    /**
     * Eliminar usuario (soft delete)
     */
    public function delete(ServerRequestInterface $request): ResponseInterface
    {
        $id = (int)$request->getAttribute('id');

        $user = $this->userManager->getUserById($id);
        if (!$user) {
            return Response::json(['error' => 'Usuario no encontrado'], 404);
        }

        // Evitar que el usuario se elimine a sí mismo
        if ($id === ($_SESSION['user_id'] ?? 0)) {
            return Response::json(['error' => 'No puedes eliminar tu propia cuenta'], 400);
        }

        // Soft delete
        $success = $this->userManager->softDelete($id);

        if ($success) {
            return Response::json(['success' => true, 'message' => 'Usuario eliminado correctamente']);
        }

        return Response::json(['error' => 'Error al eliminar el usuario'], 500);
    }

    /**
     * Restaurar usuario eliminado
     */
    public function restore(ServerRequestInterface $request): ResponseInterface
    {
        $id = (int)$request->getAttribute('id');

        $user = $this->userManager->getUserById($id);
        if (!$user) {
            return Response::json(['error' => 'Usuario no encontrado'], 404);
        }

        $success = $this->userManager->restore($id);

        if ($success) {
            return Response::json(['success' => true, 'message' => 'Usuario restaurado correctamente']);
        }

        return Response::json(['error' => 'Error al restaurar el usuario'], 500);
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
