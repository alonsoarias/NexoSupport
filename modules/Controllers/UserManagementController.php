<?php

declare(strict_types=1);

namespace ISER\Controllers;

use ISER\Core\Database\Database;
use ISER\Core\Http\Response;
use ISER\Core\View\MustacheRenderer;
use ISER\User\UserManager;
use ISER\Role\RoleManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * UserManagementController - Gestión completa de usuarios (REFACTORIZADO)
 *
 * NUEVO ENFOQUE:
 * - Usa sesiones para almacenar ID durante edición
 * - Evita renderizar IDs en campos hidden
 * - IDs se pasan como user_id (string) para compatibilidad con Mustache
 */
class UserManagementController
{
    private UserManager $userManager;
    private RoleManager $roleManager;
    private MustacheRenderer $renderer;

    public function __construct(Database $database)
    {
        $this->userManager = new UserManager($database);
        $this->roleManager = new RoleManager($database);
        $this->renderer = MustacheRenderer::getInstance();
    }

    /**
     * Renderizar con layout
     */
    private function renderWithLayout(string $view, array $data = []): ResponseInterface
    {
        $html = $this->renderer->render($view, $data, 'layouts/admin');
        return Response::html($html);
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

        // Enriquecer usuarios con roles y flags
        foreach ($users as &$user) {
            $userId = (int)$user['id'];
            $user['roles'] = $this->userManager->getUserRoles($userId);
            $user['role_names'] = array_map(fn($r) => $r['name'], $user['roles']);
            $user['created_at_formatted'] = date('d/m/Y H:i', (int)$user['created_at']);
            $user['is_deleted'] = !empty($user['deleted_at']);

            // Normalizar status a minúsculas para comparación
            $status = strtolower($user['status'] ?? 'active');
            $user['is_active'] = $status === 'active';
            $user['is_suspended'] = $status === 'suspended';

            // CRÍTICO: Usar user_id como string para que Mustache lo renderice correctamente
            $user['user_id'] = (string)$user['id'];
        }

        $data = [
            'users' => $users,
            'total_users' => $totalUsers,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'show_pagination' => $totalPages > 1,
            'has_previous' => $page > 1,
            'has_next' => $page < $totalPages,
            'previous_page' => $page - 1,
            'next_page' => $page + 1,
            'filters' => $filters,
            'showing_deleted' => isset($filters['deleted']),
            'page_title' => 'Gestión de Usuarios',
        ];

        // Mensajes
        if (isset($queryParams['success'])) {
            $messages = [
                'created' => 'Usuario creado correctamente',
                'updated' => 'Usuario actualizado correctamente',
                'deleted' => 'Usuario eliminado correctamente',
                'restored' => 'Usuario restaurado correctamente',
            ];
            $data['success_message'] = $messages[$queryParams['success']] ?? null;
        }

        if (isset($queryParams['error'])) {
            $errors = [
                'invalid_id' => 'ID de usuario inválido',
                'not_found' => 'Usuario no encontrado',
            ];
            $data['error_message'] = $errors[$queryParams['error']] ?? 'Error desconocido';
        }

        return $this->renderWithLayout('admin/users/index', $data);
    }

    /**
     * Formulario de creación
     */
    public function create(ServerRequestInterface $request): ResponseInterface
    {
        $allRoles = $this->roleManager->getRoles(100, 0);

        $data = [
            'all_roles' => $allRoles,
            'page_title' => 'Crear Usuario',
        ];

        return $this->renderWithLayout('admin/users/create', $data);
    }

    /**
     * Procesar creación
     */
    public function store(ServerRequestInterface $request): ResponseInterface
    {
        $body = $request->getParsedBody();

        // Validar datos
        $errors = $this->validateUserData($body);

        if (!empty($errors)) {
            $allRoles = $this->roleManager->getRoles(100, 0);
            $data = [
                'errors' => $errors,
                'form_data' => $body,
                'all_roles' => $allRoles,
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
            'status' => strtoupper($body['status'] ?? 'ACTIVE'),
        ]);

        // Asignar roles si se proporcionaron
        if (isset($body['roles']) && is_array($body['roles'])) {
            $currentUserId = $_SESSION['user_id'] ?? null;
            $this->userManager->syncRoles($userId, array_map('intval', $body['roles']), $currentUserId);
        }

        return Response::redirect('/admin/users?success=created');
    }

    /**
     * Formulario de edición - USA SESIÓN PARA ALMACENAR ID
     */
    public function edit(ServerRequestInterface $request): ResponseInterface
    {
        $body = $request->getParsedBody();
        $userId = (int)($body['user_id'] ?? 0);

        if (!$userId) {
            return Response::redirect('/admin/users?error=invalid_id');
        }

        // GUARDAR ID EN SESIÓN para uso en update()
        $_SESSION['editing_user_id'] = $userId;

        $user = $this->userManager->getUserById($userId);
        if (!$user) {
            unset($_SESSION['editing_user_id']);
            return Response::redirect('/admin/users?error=not_found');
        }

        // Obtener roles del usuario
        $userRoles = $this->userManager->getUserRoles($userId);
        $userRoleIds = array_column($userRoles, 'id');

        // Obtener todos los roles disponibles
        $allRoles = $this->roleManager->getRoles(100, 0);

        // Marcar roles asignados
        foreach ($allRoles as &$role) {
            $role['is_assigned'] = in_array($role['id'], $userRoleIds);
        }

        // Preparar datos para la vista
        $data = [
            'user' => $user,
            'user_roles' => $userRoles,
            'all_roles' => $allRoles,
            'page_title' => 'Editar Usuario: ' . $user['username'],
            'editing_mode' => true, // Flag para saber que estamos editando
        ];

        return $this->renderWithLayout('admin/users/edit', $data);
    }

    /**
     * Procesar actualización - USA ID DE SESIÓN
     */
    public function update(ServerRequestInterface $request): ResponseInterface
    {
        // OBTENER ID DESDE SESIÓN
        $userId = (int)($_SESSION['editing_user_id'] ?? 0);

        if (!$userId) {
            return Response::redirect('/admin/users?error=session_expired');
        }

        $body = $request->getParsedBody();

        $user = $this->userManager->getUserById($userId);
        if (!$user) {
            unset($_SESSION['editing_user_id']);
            return Response::redirect('/admin/users?error=not_found');
        }

        // Validar datos
        $errors = $this->validateUserData($body, $userId);

        if (!empty($errors)) {
            // Mantener sesión activa y volver al formulario con errores
            $userRoles = $this->userManager->getUserRoles($userId);
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
                'editing_mode' => true,
            ];
            return $this->renderWithLayout('admin/users/edit', $data);
        }

        // Preparar datos de actualización
        $updateData = [
            'username' => $body['username'],
            'email' => $body['email'],
            'first_name' => $body['first_name'],
            'last_name' => $body['last_name'],
            'status' => strtoupper($body['status'] ?? 'ACTIVE'),
        ];

        // Solo actualizar password si se proporcionó uno nuevo
        if (!empty($body['password'])) {
            $updateData['password'] = $body['password'];
        }

        // Actualizar usuario
        $success = $this->userManager->update($userId, $updateData);

        if (!$success) {
            return Response::json(['error' => 'Error al actualizar usuario'], 500);
        }

        // Actualizar roles
        if (isset($body['roles']) && is_array($body['roles'])) {
            $currentUserId = $_SESSION['user_id'] ?? null;
            $this->userManager->syncRoles($userId, array_map('intval', $body['roles']), $currentUserId);
        } else {
            $this->userManager->syncRoles($userId, [], null);
        }

        // LIMPIAR SESIÓN
        unset($_SESSION['editing_user_id']);

        return Response::redirect('/admin/users?success=updated');
    }

    /**
     * Eliminar usuario (soft delete)
     */
    public function delete(ServerRequestInterface $request): ResponseInterface
    {
        $body = $request->getParsedBody();
        $userId = (int)($body['user_id'] ?? 0);

        if (!$userId) {
            return Response::json(['error' => 'ID de usuario no proporcionado'], 400);
        }

        $user = $this->userManager->getUserById($userId);
        if (!$user) {
            return Response::json(['error' => 'Usuario no encontrado'], 404);
        }

        // Evitar que el usuario se elimine a sí mismo
        if ($userId === ($_SESSION['user_id'] ?? 0)) {
            return Response::json(['error' => 'No puedes eliminar tu propia cuenta'], 400);
        }

        $success = $this->userManager->softDelete($userId);

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
        $body = $request->getParsedBody();
        $userId = (int)($body['user_id'] ?? 0);

        if (!$userId) {
            return Response::json(['error' => 'ID de usuario no proporcionado'], 400);
        }

        $user = $this->userManager->getUserById($userId);
        if (!$user) {
            return Response::json(['error' => 'Usuario no encontrado'], 404);
        }

        $success = $this->userManager->restore($userId);

        if ($success) {
            return Response::json(['success' => true, 'message' => 'Usuario restaurado correctamente']);
        }

        return Response::json(['error' => 'Error al restaurar el usuario'], 500);
    }

    /**
     * Validar datos de usuario
     */
    private function validateUserData(array $data, ?int $excludeUserId = null): array
    {
        $errors = [];

        if (empty($data['username'])) {
            $errors[] = 'El nombre de usuario es requerido';
        } elseif ($this->userManager->usernameExists($data['username'], $excludeUserId)) {
            $errors[] = 'El nombre de usuario ya está en uso';
        }

        if (empty($data['email'])) {
            $errors[] = 'El email es requerido';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'El email no es válido';
        } elseif ($this->userManager->emailExists($data['email'], $excludeUserId)) {
            $errors[] = 'El email ya está en uso';
        }

        if (empty($data['first_name'])) {
            $errors[] = 'El nombre es requerido';
        }

        if (empty($data['last_name'])) {
            $errors[] = 'El apellido es requerido';
        }

        // Validar password solo si se está creando o si se proporcionó uno nuevo
        if ($excludeUserId === null) {
            // Creando nuevo usuario
            if (empty($data['password'])) {
                $errors[] = 'La contraseña es requerida';
            } elseif (strlen($data['password']) < 8) {
                $errors[] = 'La contraseña debe tener al menos 8 caracteres';
            }
        } else {
            // Editando usuario existente
            if (!empty($data['password']) && strlen($data['password']) < 8) {
                $errors[] = 'La contraseña debe tener al menos 8 caracteres';
            }
        }

        return $errors;
    }
}
