<?php
/**
 * Tema ISER - Clase principal
 * @package theme_iser
 * @author ISER Desarrollo
 * @license Propietario
 */

namespace ISER\Theme\Iser;

use ISER\Core\View\MustacheRenderer;
use ISER\Core\Config\SettingsManager;
use ISER\Core\Database\Database;
use Monolog\Logger;

class ThemeIser
{
    private array $config;
    private MustacheRenderer $renderer;
    private ThemeAssets $assets;
    private ThemeLayouts $layouts;
    private ThemeNavigation $navigation;
    private SettingsManager $settings;
    private Logger $logger;
    private ?int $userId;

    public function __construct(
        Database $db,
        SettingsManager $settings,
        Logger $logger,
        ?int $userId = null
    ) {
        $this->settings = $settings;
        $this->logger = $logger;
        $this->userId = $userId;

        // Cargar configuración del tema
        $this->loadConfig();

        // Inicializar componentes
        $this->initializeRenderer();
        $this->assets = new ThemeAssets($this->config['assets'] ?? []);
        $this->layouts = new ThemeLayouts($this->config['layouts'] ?? []);
        $this->navigation = new ThemeNavigation($db, $this->config['navigation'] ?? []);
    }

    /**
     * Inicializar el tema
     */
    public function init(): void
    {
        // Registrar assets globales
        $this->registerDefaultAssets();

        // Configurar datos globales del renderer
        $this->setupGlobalData();

        $this->logger->info('Tema ISER inicializado', [
            'user_id' => $this->userId,
            'theme_version' => $this->config['version'] ?? '1.0.0'
        ]);
    }

    /**
     * Cargar configuración del tema
     */
    private function loadConfig(): void
    {
        $configPath = __DIR__ . '/../config/theme_settings.php';
        if (file_exists($configPath)) {
            $this->config = require $configPath;
        } else {
            $this->config = $this->getDefaultConfig();
        }

        // Sobrescribir con configuraciones de base de datos
        $this->loadDatabaseConfig();
    }

    /**
     * Cargar configuraciones desde la base de datos
     */
    private function loadDatabaseConfig(): void
    {
        $dbColors = $this->settings->getArray('theme_colors', 'theme_iser');
        if (!empty($dbColors)) {
            $this->config['colors'] = array_merge($this->config['colors'] ?? [], $dbColors);
        }
    }

    /**
     * Obtener configuración por defecto
     */
    private function getDefaultConfig(): array
    {
        return [
            'name' => 'iser',
            'version' => '1.0.0',
            'author' => 'ISER Desarrollo',
            'description' => 'Tema oficial del ISER basado en Bootstrap 5',
            'colors' => [
                'primary' => '#2c7be5',
                'secondary' => '#6e84a3',
                'success' => '#00d97e',
                'danger' => '#e63757',
                'warning' => '#f6c343',
                'info' => '#39afd1',
                'light' => '#f9fafd',
                'dark' => '#0b1727'
            ]
        ];
    }

    /**
     * Inicializar el renderer
     */
    private function initializeRenderer(): void
    {
        $templatePaths = [
            __DIR__ . '/../templates',
            __DIR__ . '/../templates/partials',
            __DIR__ . '/../templates/components'
        ];

        $this->renderer = new MustacheRenderer($templatePaths);
    }

    /**
     * Configurar datos globales para todos los templates
     */
    private function setupGlobalData(): void
    {
        $globalData = [
            'site_name' => $this->settings->getString('sitename', 'core', 'ISER Plataforma'),
            'site_description' => $this->settings->getString('sitedescription', 'core', 'Sistema ISER'),
            'base_url' => $_ENV['APP_URL'] ?? '',
            'theme_url' => $this->getThemeUrl(),
            'colors' => $this->config['colors'],
            'favicon_url' => $this->getFaviconUrl(),
            'logo_url' => $this->getLogoUrl(),
            'current_year' => date('Y'),
            'csrf_token' => $_SESSION['csrf_token'] ?? '',
            'user_json' => $this->getUserJson()
        ];

        $this->renderer->setGlobalData($globalData);
    }

    /**
     * Registrar assets por defecto
     */
    private function registerDefaultAssets(): void
    {
        $themeUrl = $this->getThemeUrl();

        // CSS
        $this->assets->addCss("{$themeUrl}/assets/css/vendor/bootstrap.min.css", [], 10);
        $this->assets->addCss("{$themeUrl}/assets/css/vendor/fontawesome.min.css", [], 20);
        $this->assets->addCss("{$themeUrl}/assets/css/theme.css", [], 30);

        // JavaScript
        $this->assets->addJs("{$themeUrl}/assets/js/vendor/bootstrap.bundle.min.js", [], true, 10);
    }

    /**
     * Obtener un layout
     */
    public function getLayout(string $layoutName): ?array
    {
        return $this->layouts->getLayoutConfig($layoutName);
    }

    /**
     * Renderizar un layout
     */
    public function renderLayout(string $layoutName, array $data = []): string
    {
        $layout = $this->layouts->getLayoutConfig($layoutName);
        if (!$layout) {
            throw new \RuntimeException("Layout no encontrado: {$layoutName}");
        }

        // Agregar datos del layout
        $data['layout'] = $layout;
        $data['body_classes'] = $this->getBodyClasses($layoutName);
        $data['has_sidebar'] = $layout['has_sidebar'] ?? false;

        // Agregar assets
        $data['css_files'] = $this->assets->getCssFiles();
        $data['js_files'] = $this->assets->getJsFiles();

        // Agregar navegación
        if ($layout['has_navbar'] ?? true) {
            $data['sections'] = $this->navigation->getMainMenu();
        }

        // Renderizar el template del layout
        $templateName = $layout['template'];
        return $this->renderer->renderFile($templateName, $data);
    }

    /**
     * Obtener configuración del tema
     */
    public function getThemeSettings(): array
    {
        return $this->config;
    }

    /**
     * Actualizar configuración del tema
     */
    public function updateThemeSettings(array $settings): bool
    {
        try {
            // Guardar colores personalizados
            if (isset($settings['colors'])) {
                $this->settings->set('theme_colors', $settings['colors'], 'theme_iser');
                $this->config['colors'] = array_merge($this->config['colors'], $settings['colors']);
            }

            $this->logger->info('Configuración del tema actualizada', [
                'user_id' => $this->userId,
                'settings' => array_keys($settings)
            ]);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Error actualizando configuración del tema', [
                'error' => $e->getMessage(),
                'user_id' => $this->userId
            ]);
            return false;
        }
    }

    /**
     * Obtener URL del logo
     */
    public function getLogoUrl(): string
    {
        $customLogo = $this->settings->getString('theme_logo', 'theme_iser');
        if ($customLogo) {
            return $customLogo;
        }

        return $this->getThemeUrl() . '/assets/images/logo/iser-logo.png';
    }

    /**
     * Obtener URL del favicon
     */
    public function getFaviconUrl(): string
    {
        $customFavicon = $this->settings->getString('theme_favicon', 'theme_iser');
        if ($customFavicon) {
            return $customFavicon;
        }

        return $this->getThemeUrl() . '/assets/images/logo/favicon.ico';
    }

    /**
     * Obtener preferencias del tema del usuario
     */
    public function getUserThemePreferences(?int $userId = null): array
    {
        $userId = $userId ?? $this->userId;
        if (!$userId) {
            return $this->getDefaultPreferences();
        }

        $preferences = $this->settings->getArray("user_{$userId}_theme_preferences", 'theme_iser');
        return array_merge($this->getDefaultPreferences(), $preferences);
    }

    /**
     * Guardar preferencias del tema del usuario
     */
    public function saveUserThemePreferences(array $preferences, ?int $userId = null): bool
    {
        $userId = $userId ?? $this->userId;
        if (!$userId) {
            return false;
        }

        return $this->settings->set("user_{$userId}_theme_preferences", $preferences, 'theme_iser');
    }

    /**
     * Obtener preferencias por defecto
     */
    private function getDefaultPreferences(): array
    {
        return [
            'theme_mode' => 'light',
            'sidebar_collapsed' => false,
            'font_size' => 'medium'
        ];
    }

    /**
     * Obtener URL base del tema
     */
    private function getThemeUrl(): string
    {
        $baseUrl = $_ENV['APP_URL'] ?? '';
        return rtrim($baseUrl, '/') . '/theme/iser';
    }

    /**
     * Obtener clases del body según el layout
     */
    private function getBodyClasses(string $layoutName): string
    {
        $classes = ['theme-iser', "layout-{$layoutName}"];

        $preferences = $this->getUserThemePreferences();
        if ($preferences['sidebar_collapsed'] ?? false) {
            $classes[] = 'sidebar-collapsed';
        }

        return implode(' ', $classes);
    }

    /**
     * Obtener JSON del usuario para JavaScript
     */
    private function getUserJson(): string
    {
        if (!$this->userId) {
            return json_encode(['authenticated' => false]);
        }

        $userData = [
            'authenticated' => true,
            'id' => $this->userId,
            'preferences' => $this->getUserThemePreferences()
        ];

        return json_encode($userData);
    }

    /**
     * Obtener renderer
     */
    public function getRenderer(): MustacheRenderer
    {
        return $this->renderer;
    }

    /**
     * Obtener assets manager
     */
    public function getAssets(): ThemeAssets
    {
        return $this->assets;
    }

    /**
     * Obtener layouts manager
     */
    public function getLayouts(): ThemeLayouts
    {
        return $this->layouts;
    }

    /**
     * Obtener navigation manager
     */
    public function getNavigation(): ThemeNavigation
    {
        return $this->navigation;
    }
}
