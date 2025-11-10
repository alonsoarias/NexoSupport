<?php
/**
 * Gestión de navegación del tema
 * @package theme_iser
 * @author ISER Desarrollo
 */

namespace ISER\Theme\Iser;

use ISER\Core\Database\Database;

class ThemeNavigation
{
    private Database $db;
    private array $config;
    private array $menuCache = [];
    private ?int $userId;

    public function __construct(Database $db, array $config = [], ?int $userId = null)
    {
        $this->db = $db;
        $this->config = $config;
        $this->userId = $userId;
    }

    /**
     * Obtener menú principal
     */
    public function getMainMenu(): array
    {
        if (isset($this->menuCache['main'])) {
            return $this->menuCache['main'];
        }

        $menu = $this->config['main_menu'] ?? $this->getDefaultMainMenu();

        // Filtrar por permisos
        $menu = $this->filterByPermissions($menu);

        // Agregar estados activos
        $menu = $this->addActiveStates($menu);

        $this->menuCache['main'] = $menu;
        return $menu;
    }

    /**
     * Obtener menú de administración
     */
    public function getAdminMenu(): array
    {
        if (isset($this->menuCache['admin'])) {
            return $this->menuCache['admin'];
        }

        $menu = [
            'dashboard' => [
                'title' => 'Dashboard',
                'url' => '/admin',
                'icon' => 'fas fa-tachometer-alt',
                'permission' => 'admin',
                'order' => 10
            ],
            'users' => [
                'title' => 'Usuarios',
                'url' => '/admin/user',
                'icon' => 'fas fa-users',
                'permission' => 'admin',
                'order' => 20,
                'badge' => $this->getUserCount()
            ],
            'roles' => [
                'title' => 'Roles y Permisos',
                'url' => '/admin/roles/manage',
                'icon' => 'fas fa-user-shield',
                'permission' => 'admin',
                'order' => 30
            ],
            'settings' => [
                'title' => 'Configuración',
                'url' => '/admin/settings.php',
                'icon' => 'fas fa-cog',
                'permission' => 'admin',
                'order' => 40,
                'subsections' => [
                    [
                        'title' => 'General',
                        'url' => '/admin/settings.php?section=general'
                    ],
                    [
                        'title' => 'Autenticación',
                        'url' => '/admin/settings.php?section=manageauths'
                    ],
                    [
                        'title' => 'Correo',
                        'url' => '/admin/settings.php?section=outgoingmailconfig'
                    ]
                ]
            ],
            'plugins' => [
                'title' => 'Plugins',
                'url' => '/admin/plugins.php',
                'icon' => 'fas fa-puzzle-piece',
                'permission' => 'admin',
                'order' => 50
            ],
            'tools' => [
                'title' => 'Herramientas',
                'url' => '/admin/tools.php',
                'icon' => 'fas fa-tools',
                'permission' => 'admin',
                'order' => 60,
                'subsections' => [
                    [
                        'title' => 'Subir Usuarios',
                        'url' => '/admin/tool/uploaduser/index.php'
                    ],
                    [
                        'title' => 'Instalar Addon',
                        'url' => '/admin/tool/installaddon/index.php'
                    ]
                ]
            ],
            'reports' => [
                'title' => 'Reportes',
                'url' => '/admin/reports.php',
                'icon' => 'fas fa-chart-bar',
                'permission' => 'admin',
                'order' => 70
            ]
        ];

        // Filtrar por permisos
        $menu = $this->filterByPermissions($menu);

        // Agregar estados activos
        $menu = $this->addActiveStates($menu);

        // Ordenar por 'order'
        uasort($menu, function($a, $b) {
            return ($a['order'] ?? 999) <=> ($b['order'] ?? 999);
        });

        $this->menuCache['admin'] = $menu;
        return $menu;
    }

    /**
     * Obtener menú de usuario
     */
    public function getUserMenu(): array
    {
        if (!$this->userId) {
            return [];
        }

        return [
            'profile' => [
                'title' => 'Mi Perfil',
                'url' => '/profile',
                'icon' => 'fas fa-user',
                'order' => 10
            ],
            'settings' => [
                'title' => 'Preferencias',
                'url' => '/user/preferences',
                'icon' => 'fas fa-cog',
                'order' => 20
            ],
            'logout' => [
                'title' => 'Cerrar Sesión',
                'url' => '/logout',
                'icon' => 'fas fa-sign-out-alt',
                'order' => 30
            ]
        ];
    }

    /**
     * Obtener breadcrumbs
     */
    public function getBreadcrumbs(): array
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $breadcrumbs = [
            ['text' => 'Inicio', 'url' => '/']
        ];

        // Parsear la URL para crear breadcrumbs
        $path = parse_url($uri, PHP_URL_PATH);
        $segments = array_filter(explode('/', $path));

        $currentPath = '';
        foreach ($segments as $segment) {
            $currentPath .= '/' . $segment;

            // Convertir segment a título legible
            $title = ucfirst(str_replace(['-', '_'], ' ', $segment));

            $breadcrumbs[] = [
                'text' => $title,
                'url' => $currentPath
            ];
        }

        return $breadcrumbs;
    }

    /**
     * Obtener menú principal por defecto
     */
    private function getDefaultMainMenu(): array
    {
        return [
            'dashboard' => [
                'title' => 'Dashboard',
                'url' => '/dashboard',
                'icon' => 'fas fa-tachometer-alt',
                'permission' => 'user',
                'order' => 10
            ],
            'courses' => [
                'title' => 'Cursos',
                'url' => '/courses',
                'icon' => 'fas fa-book',
                'permission' => 'user',
                'order' => 20
            ],
            'calendar' => [
                'title' => 'Calendario',
                'url' => '/calendar',
                'icon' => 'fas fa-calendar',
                'permission' => 'user',
                'order' => 30
            ]
        ];
    }

    /**
     * Filtrar menú por permisos del usuario
     */
    private function filterByPermissions(array $menu): array
    {
        if (!$this->userId) {
            // Si no hay usuario, solo mostrar items públicos
            return array_filter($menu, function($item) {
                return ($item['permission'] ?? 'public') === 'public';
            });
        }

        // Obtener roles del usuario
        $userRoles = $this->getUserRoles();

        return array_filter($menu, function($item) use ($userRoles) {
            $requiredPermission = $item['permission'] ?? 'user';

            // Si es público, siempre mostrar
            if ($requiredPermission === 'public') {
                return true;
            }

            // Si requiere 'user' y el usuario está autenticado
            if ($requiredPermission === 'user') {
                return true;
            }

            // Si requiere 'admin', verificar roles
            if ($requiredPermission === 'admin') {
                return in_array('admin', $userRoles) || in_array('administrator', $userRoles);
            }

            // Verificar permiso específico
            return in_array($requiredPermission, $userRoles);
        });
    }

    /**
     * Agregar estados activos a los items del menú
     */
    private function addActiveStates(array $menu): array
    {
        $currentUrl = $_SERVER['REQUEST_URI'] ?? '/';

        foreach ($menu as &$item) {
            // Verificar si la URL actual coincide con el item
            $item['is_active'] = $this->isUrlActive($item['url'], $currentUrl);

            // Verificar subsecciones
            if (isset($item['subsections'])) {
                foreach ($item['subsections'] as &$subsection) {
                    $subsection['is_active'] = $this->isUrlActive($subsection['url'], $currentUrl);

                    // Si alguna subsección está activa, marcar el item padre como activo
                    if ($subsection['is_active']) {
                        $item['is_active'] = true;
                    }
                }
            }
        }

        return $menu;
    }

    /**
     * Verificar si una URL está activa
     */
    private function isUrlActive(string $itemUrl, string $currentUrl): bool
    {
        // Normalizar URLs
        $itemUrl = rtrim($itemUrl, '/');
        $currentUrl = rtrim(parse_url($currentUrl, PHP_URL_PATH) ?? '/', '/');

        // Coincidencia exacta
        if ($itemUrl === $currentUrl) {
            return true;
        }

        // Coincidencia de prefijo (para secciones padre)
        if (str_starts_with($currentUrl, $itemUrl . '/')) {
            return true;
        }

        return false;
    }

    /**
     * Obtener roles del usuario
     */
    private function getUserRoles(): array
    {
        if (!$this->userId) {
            return [];
        }

        try {
            $query = "
                SELECT r.shortname
                FROM role_assignments ra
                JOIN roles r ON r.id = ra.roleid
                WHERE ra.userid = :userid
            ";

            $result = $this->db->query($query, ['userid' => $this->userId]);
            return array_column($result, 'shortname');
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Obtener conteo de usuarios (para badge en menú admin)
     */
    private function getUserCount(): ?int
    {
        try {
            $result = $this->db->query("SELECT COUNT(*) as count FROM users WHERE deleted = 0");
            return $result[0]['count'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Construir árbol de navegación jerárquico
     */
    public function buildNavigationTree(array $items, int $parentId = 0): array
    {
        $tree = [];

        foreach ($items as $item) {
            if (($item['parent_id'] ?? 0) === $parentId) {
                $children = $this->buildNavigationTree($items, $item['id']);
                if ($children) {
                    $item['children'] = $children;
                }
                $tree[] = $item;
            }
        }

        return $tree;
    }

    /**
     * Limpiar caché de menús
     */
    public function clearCache(): void
    {
        $this->menuCache = [];
    }

    /**
     * Establecer ID de usuario
     */
    public function setUserId(?int $userId): void
    {
        $this->userId = $userId;
        $this->clearCache(); // Limpiar caché al cambiar usuario
    }

    /**
     * Renderizar menú como HTML
     */
    public function renderMenu(array $menu, string $className = 'nav'): string
    {
        $html = "<ul class=\"{$className}\">";

        foreach ($menu as $key => $item) {
            $activeClass = ($item['is_active'] ?? false) ? ' active' : '';
            $icon = isset($item['icon']) ? "<i class=\"{$item['icon']}\"></i> " : '';
            $badge = isset($item['badge']) ? " <span class=\"badge bg-primary\">{$item['badge']}</span>" : '';

            $html .= "<li class=\"nav-item{$activeClass}\">";
            $html .= "<a href=\"{$item['url']}\" class=\"nav-link\">";
            $html .= $icon . htmlspecialchars($item['title']) . $badge;
            $html .= "</a>";

            // Renderizar subsecciones si existen
            if (isset($item['subsections']) && !empty($item['subsections'])) {
                $html .= $this->renderMenu($item['subsections'], 'nav flex-column ms-3');
            }

            $html .= "</li>";
        }

        $html .= "</ul>";

        return $html;
    }
}
