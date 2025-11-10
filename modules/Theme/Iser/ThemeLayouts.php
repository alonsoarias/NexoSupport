<?php
/**
 * Gestión de layouts del tema
 * @package theme_iser
 * @author ISER Desarrollo
 */

namespace ISER\Theme\Iser;

class ThemeLayouts
{
    private array $layouts = [];
    private ?string $currentLayout = null;
    private array $contentRegions = [];

    public function __construct(array $config = [])
    {
        // Registrar layouts predefinidos
        $this->registerDefaultLayouts();

        // Registrar layouts de la configuración
        if (isset($config['layouts'])) {
            foreach ($config['layouts'] as $name => $layoutConfig) {
                $this->registerLayout($name, $layoutConfig);
            }
        }
    }

    /**
     * Registrar layouts predefinidos
     */
    private function registerDefaultLayouts(): void
    {
        $this->layouts = [
            'base' => [
                'template' => 'layouts/base',
                'regions' => ['header', 'navbar', 'sidebar', 'content', 'footer'],
                'has_sidebar' => true,
                'has_navbar' => true,
                'has_breadcrumbs' => true,
                'container_fluid' => true
            ],
            'admin' => [
                'template' => 'layouts/admin',
                'regions' => ['header', 'navbar', 'sidebar', 'content', 'footer'],
                'has_sidebar' => true,
                'has_navbar' => true,
                'has_breadcrumbs' => true,
                'container_fluid' => true,
                'sidebar_fixed' => true
            ],
            'login' => [
                'template' => 'layouts/login',
                'regions' => ['header', 'content', 'footer'],
                'has_sidebar' => false,
                'has_navbar' => false,
                'has_breadcrumbs' => false,
                'container_fluid' => false,
                'centered' => true
            ],
            'dashboard' => [
                'template' => 'layouts/dashboard',
                'regions' => ['header', 'navbar', 'sidebar', 'content', 'footer'],
                'has_sidebar' => true,
                'has_navbar' => true,
                'has_breadcrumbs' => true,
                'container_fluid' => true,
                'grid_layout' => true
            ],
            'fullwidth' => [
                'template' => 'layouts/fullwidth',
                'regions' => ['header', 'navbar', 'content', 'footer'],
                'has_sidebar' => false,
                'has_navbar' => true,
                'has_breadcrumbs' => false,
                'container_fluid' => true
            ],
            'popup' => [
                'template' => 'layouts/popup',
                'regions' => ['content'],
                'has_sidebar' => false,
                'has_navbar' => false,
                'has_breadcrumbs' => false,
                'has_header' => false,
                'has_footer' => false,
                'minimal' => true
            ]
        ];
    }

    /**
     * Registrar un layout personalizado
     */
    public function registerLayout(string $name, array $config): void
    {
        // Validar configuración mínima
        if (!isset($config['template'])) {
            throw new \InvalidArgumentException("Layout '{$name}' debe tener una propiedad 'template'");
        }

        // Valores por defecto
        $defaults = [
            'regions' => ['content'],
            'has_sidebar' => false,
            'has_navbar' => true,
            'has_breadcrumbs' => false,
            'container_fluid' => false
        ];

        $this->layouts[$name] = array_merge($defaults, $config);
    }

    /**
     * Establecer el layout actual
     */
    public function setLayout(string $name): void
    {
        if (!isset($this->layouts[$name])) {
            throw new \RuntimeException("Layout '{$name}' no está registrado");
        }

        $this->currentLayout = $name;
    }

    /**
     * Obtener configuración de un layout
     */
    public function getLayoutConfig(string $name): ?array
    {
        return $this->layouts[$name] ?? null;
    }

    /**
     * Obtener el layout actual
     */
    public function getCurrentLayout(): ?string
    {
        return $this->currentLayout;
    }

    /**
     * Obtener todos los layouts registrados
     */
    public function getAllLayouts(): array
    {
        return $this->layouts;
    }

    // ==================== LAYOUTS PREDEFINIDOS ====================

    /**
     * Obtener layout base
     */
    public function getBaseLayout(): array
    {
        return $this->getLayoutConfig('base');
    }

    /**
     * Obtener layout admin
     */
    public function getAdminLayout(): array
    {
        return $this->getLayoutConfig('admin');
    }

    /**
     * Obtener layout de login
     */
    public function getLoginLayout(): array
    {
        return $this->getLayoutConfig('login');
    }

    /**
     * Obtener layout de dashboard
     */
    public function getDashboardLayout(): array
    {
        return $this->getLayoutConfig('dashboard');
    }

    /**
     * Obtener layout fullwidth
     */
    public function getFullwidthLayout(): array
    {
        return $this->getLayoutConfig('fullwidth');
    }

    /**
     * Obtener layout popup
     */
    public function getPopupLayout(): array
    {
        return $this->getLayoutConfig('popup');
    }

    // ==================== REGIONES DE CONTENIDO ====================

    /**
     * Agregar contenido a una región
     */
    public function addContentRegion(string $region, string $content): void
    {
        if (!isset($this->contentRegions[$region])) {
            $this->contentRegions[$region] = [];
        }

        $this->contentRegions[$region][] = $content;
    }

    /**
     * Establecer contenido de una región (sobrescribe)
     */
    public function setContentRegion(string $region, string $content): void
    {
        $this->contentRegions[$region] = [$content];
    }

    /**
     * Obtener contenido de una región
     */
    public function getContentRegion(string $region): string
    {
        if (!isset($this->contentRegions[$region])) {
            return '';
        }

        return implode("\n", $this->contentRegions[$region]);
    }

    /**
     * Obtener todas las regiones de contenido
     */
    public function getContentRegions(): array
    {
        $regions = [];

        foreach ($this->contentRegions as $region => $contents) {
            $regions[$region] = implode("\n", $contents);
        }

        return $regions;
    }

    /**
     * Limpiar región de contenido
     */
    public function clearContentRegion(string $region): void
    {
        unset($this->contentRegions[$region]);
    }

    /**
     * Limpiar todas las regiones
     */
    public function clearAllContentRegions(): void
    {
        $this->contentRegions = [];
    }

    /**
     * Verificar si una región tiene contenido
     */
    public function hasContentInRegion(string $region): bool
    {
        return isset($this->contentRegions[$region]) && !empty($this->contentRegions[$region]);
    }

    // ==================== UTILIDADES ====================

    /**
     * Obtener clases CSS del layout
     */
    public function getLayoutClasses(string $layoutName): array
    {
        $layout = $this->getLayoutConfig($layoutName);
        if (!$layout) {
            return [];
        }

        $classes = ["layout-{$layoutName}"];

        if ($layout['has_sidebar'] ?? false) {
            $classes[] = 'has-sidebar';
        }

        if ($layout['sidebar_fixed'] ?? false) {
            $classes[] = 'sidebar-fixed';
        }

        if ($layout['container_fluid'] ?? false) {
            $classes[] = 'container-fluid-layout';
        }

        if ($layout['centered'] ?? false) {
            $classes[] = 'centered-layout';
        }

        if ($layout['minimal'] ?? false) {
            $classes[] = 'minimal-layout';
        }

        return $classes;
    }

    /**
     * Detectar layout automáticamente según la URL
     */
    public function autoDetectLayout(string $currentUrl): string
    {
        // Admin panel
        if (str_contains($currentUrl, '/admin/')) {
            return 'admin';
        }

        // Login/registro
        if (str_contains($currentUrl, '/login') || str_contains($currentUrl, '/register')) {
            return 'login';
        }

        // Dashboard
        if (str_contains($currentUrl, '/dashboard')) {
            return 'dashboard';
        }

        // Popup/modal
        if (isset($_GET['popup']) || isset($_GET['modal'])) {
            return 'popup';
        }

        // Por defecto, base
        return 'base';
    }

    /**
     * Verificar si un layout existe
     */
    public function layoutExists(string $name): bool
    {
        return isset($this->layouts[$name]);
    }

    /**
     * Obtener nombres de todos los layouts
     */
    public function getLayoutNames(): array
    {
        return array_keys($this->layouts);
    }

    /**
     * Clonar un layout con modificaciones
     */
    public function cloneLayout(string $sourceName, string $newName, array $modifications = []): void
    {
        if (!$this->layoutExists($sourceName)) {
            throw new \RuntimeException("Layout fuente '{$sourceName}' no existe");
        }

        $sourceLayout = $this->getLayoutConfig($sourceName);
        $newLayout = array_merge($sourceLayout, $modifications);

        $this->registerLayout($newName, $newLayout);
    }
}
