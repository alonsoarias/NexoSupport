<?php

declare(strict_types=1);

namespace ISER\Controllers\Traits;

/**
 * NavigationTrait
 *
 * Trait para agregar funcionalidad de navegación a los controladores
 * Genera breadcrumbs, marca rutas activas, y prepara datos de navegación
 */
trait NavigationTrait
{
    /**
     * Enriquecer datos con información de navegación
     *
     * @param array $data Datos existentes de la vista
     * @param string $activeRoute Ruta activa (ej: '/admin/users')
     * @param array|null $customBreadcrumbs Breadcrumbs personalizados (opcional)
     * @return array Datos enriquecidos con navegación
     */
    protected function enrichWithNavigation(array $data, string $activeRoute, ?array $customBreadcrumbs = null): array
    {
        // Obtener usuario actual de sesión
        $user = $_SESSION['user'] ?? null;

        // Generar breadcrumbs
        $breadcrumbs = $customBreadcrumbs ?? $this->generateBreadcrumbs($activeRoute);

        // Agregar datos de navegación
        $data['navigation'] = [
            'user' => $user,
            'breadcrumbs' => $breadcrumbs,
            'notifications_count' => 0, // TODO: Implementar sistema de notificaciones

            // Marcar ruta activa para sidebar
            'is_home' => $activeRoute === '/',
            'is_admin_dashboard' => $activeRoute === '/admin',
            'is_users' => str_starts_with($activeRoute, '/admin/users'),
            'is_roles' => str_starts_with($activeRoute, '/admin/roles'),
            'is_permissions' => str_starts_with($activeRoute, '/admin/permissions'),
            'is_settings' => str_starts_with($activeRoute, '/admin/settings'),
            'is_reports' => str_starts_with($activeRoute, '/admin/reports'),
            'is_security' => str_starts_with($activeRoute, '/admin/security'),
            'is_profile' => str_starts_with($activeRoute, '/profile'),
        ];

        return $data;
    }

    /**
     * Generar breadcrumbs basados en la ruta actual
     *
     * @param string $route Ruta actual
     * @return array Breadcrumbs estructurados para Mustache
     */
    protected function generateBreadcrumbs(string $route): array
    {
        $breadcrumbMap = [
            '/' => [
                ['label' => 'Inicio', 'url' => '/', 'icon' => 'house-door', 'is_active' => true],
            ],
            '/dashboard' => [
                ['label' => 'Inicio', 'url' => '/', 'icon' => 'house-door', 'is_active' => false],
                ['label' => 'Dashboard', 'url' => null, 'icon' => null, 'is_active' => true],
            ],
            '/admin' => [
                ['label' => 'Inicio', 'url' => '/', 'icon' => 'house-door', 'is_active' => false],
                ['label' => 'Administración', 'url' => null, 'icon' => null, 'is_active' => true],
            ],
            '/admin/users' => [
                ['label' => 'Inicio', 'url' => '/', 'icon' => 'house-door', 'is_active' => false],
                ['label' => 'Administración', 'url' => '/admin', 'icon' => null, 'is_active' => false],
                ['label' => 'Usuarios', 'url' => null, 'icon' => null, 'is_active' => true],
            ],
            '/admin/users/create' => [
                ['label' => 'Inicio', 'url' => '/', 'icon' => 'house-door', 'is_active' => false],
                ['label' => 'Administración', 'url' => '/admin', 'icon' => null, 'is_active' => false],
                ['label' => 'Usuarios', 'url' => '/admin/users', 'icon' => null, 'is_active' => false],
                ['label' => 'Crear Usuario', 'url' => null, 'icon' => null, 'is_active' => true],
            ],
            '/admin/users/edit' => [
                ['label' => 'Inicio', 'url' => '/', 'icon' => 'house-door', 'is_active' => false],
                ['label' => 'Administración', 'url' => '/admin', 'icon' => null, 'is_active' => false],
                ['label' => 'Usuarios', 'url' => '/admin/users', 'icon' => null, 'is_active' => false],
                ['label' => 'Editar Usuario', 'url' => null, 'icon' => null, 'is_active' => true],
            ],
            '/admin/roles' => [
                ['label' => 'Inicio', 'url' => '/', 'icon' => 'house-door', 'is_active' => false],
                ['label' => 'Administración', 'url' => '/admin', 'icon' => null, 'is_active' => false],
                ['label' => 'Roles', 'url' => null, 'icon' => null, 'is_active' => true],
            ],
            '/admin/roles/create' => [
                ['label' => 'Inicio', 'url' => '/', 'icon' => 'house-door', 'is_active' => false],
                ['label' => 'Administración', 'url' => '/admin', 'icon' => null, 'is_active' => false],
                ['label' => 'Roles', 'url' => '/admin/roles', 'icon' => null, 'is_active' => false],
                ['label' => 'Crear Rol', 'url' => null, 'icon' => null, 'is_active' => true],
            ],
            '/admin/roles/edit' => [
                ['label' => 'Inicio', 'url' => '/', 'icon' => 'house-door', 'is_active' => false],
                ['label' => 'Administración', 'url' => '/admin', 'icon' => null, 'is_active' => false],
                ['label' => 'Roles', 'url' => '/admin/roles', 'icon' => null, 'is_active' => false],
                ['label' => 'Editar Rol', 'url' => null, 'icon' => null, 'is_active' => true],
            ],
            '/admin/permissions' => [
                ['label' => 'Inicio', 'url' => '/', 'icon' => 'house-door', 'is_active' => false],
                ['label' => 'Administración', 'url' => '/admin', 'icon' => null, 'is_active' => false],
                ['label' => 'Permisos', 'url' => null, 'icon' => null, 'is_active' => true],
            ],
            '/admin/permissions/create' => [
                ['label' => 'Inicio', 'url' => '/', 'icon' => 'house-door', 'is_active' => false],
                ['label' => 'Administración', 'url' => '/admin', 'icon' => null, 'is_active' => false],
                ['label' => 'Permisos', 'url' => '/admin/permissions', 'icon' => null, 'is_active' => false],
                ['label' => 'Crear Permiso', 'url' => null, 'icon' => null, 'is_active' => true],
            ],
            '/admin/permissions/edit' => [
                ['label' => 'Inicio', 'url' => '/', 'icon' => 'house-door', 'is_active' => false],
                ['label' => 'Administración', 'url' => '/admin', 'icon' => null, 'is_active' => false],
                ['label' => 'Permisos', 'url' => '/admin/permissions', 'icon' => null, 'is_active' => false],
                ['label' => 'Editar Permiso', 'url' => null, 'icon' => null, 'is_active' => true],
            ],
            '/admin/settings' => [
                ['label' => 'Inicio', 'url' => '/', 'icon' => 'house-door', 'is_active' => false],
                ['label' => 'Administración', 'url' => '/admin', 'icon' => null, 'is_active' => false],
                ['label' => 'Configuración', 'url' => null, 'icon' => null, 'is_active' => true],
            ],
            '/admin/reports' => [
                ['label' => 'Inicio', 'url' => '/', 'icon' => 'house-door', 'is_active' => false],
                ['label' => 'Administración', 'url' => '/admin', 'icon' => null, 'is_active' => false],
                ['label' => 'Reportes', 'url' => null, 'icon' => null, 'is_active' => true],
            ],
            '/admin/security' => [
                ['label' => 'Inicio', 'url' => '/', 'icon' => 'house-door', 'is_active' => false],
                ['label' => 'Administración', 'url' => '/admin', 'icon' => null, 'is_active' => false],
                ['label' => 'Seguridad', 'url' => null, 'icon' => null, 'is_active' => true],
            ],
            '/profile' => [
                ['label' => 'Inicio', 'url' => '/', 'icon' => 'house-door', 'is_active' => false],
                ['label' => 'Mi Perfil', 'url' => null, 'icon' => null, 'is_active' => true],
            ],
        ];

        // Devolver breadcrumbs si existe en el mapa, sino breadcrumbs genéricos
        return $breadcrumbMap[$route] ?? [
            ['label' => 'Inicio', 'url' => '/', 'icon' => 'house-door', 'is_active' => false],
            ['label' => 'Página', 'url' => null, 'icon' => null, 'is_active' => true],
        ];
    }
}
