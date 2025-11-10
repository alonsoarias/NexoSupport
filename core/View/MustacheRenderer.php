<?php

declare(strict_types=1);

namespace ISER\Core\View;

use Mustache_Engine;
use Mustache_Loader_FilesystemLoader;
use ISER\Core\I18n\Translator;

/**
 * MustacheRenderer - Sistema de Templates con Mustache
 *
 * Renderiza templates Mustache con soporte i18n y tema ISER
 * Compatible con PSR-4 y PSR-12
 *
 * @package ISER\Core\View
 * @author ISER Desarrollo
 */
class MustacheRenderer
{
    private static ?MustacheRenderer $instance = null;
    private Mustache_Engine $mustache;
    private Translator $translator;
    private string $viewsPath;
    private array $globalData = [];

    /**
     * Constructor privado (Singleton)
     */
    private function __construct(string $viewsPath)
    {
        $this->viewsPath = $viewsPath;
        $this->translator = Translator::getInstance();

        // Configurar Mustache
        $this->mustache = new Mustache_Engine([
            'loader' => new Mustache_Loader_FilesystemLoader($viewsPath),
            'partials_loader' => new Mustache_Loader_FilesystemLoader($viewsPath),
            'escape' => function ($value) {
                // Convertir a string antes de escapar (soporta int, float, bool, etc.)
                return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
            },
            'helpers' => $this->getHelpers(),
        ]);

        // Establecer datos globales por defecto
        // No usamos __() aquí para evitar dependencias circulares
        $this->setGlobalData([
            'app_name' => 'ISER Auth System',
            'locale' => $this->translator->getLocale(),
            'iser_colors' => [
                'green' => '#1B9E88',
                'yellow' => '#F4C430',
                'red' => '#EB4335',
            ],
        ]);
    }

    /**
     * Obtener instancia única
     */
    public static function getInstance(?string $viewsPath = null): self
    {
        if (self::$instance === null) {
            if ($viewsPath === null) {
                // Usar DIRECTORY_SEPARATOR para compatibilidad entre Windows y Unix
                $viewsPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views';

                // Normalizar la ruta para Windows
                if (DIRECTORY_SEPARATOR === '\\') {
                    $viewsPath = str_replace('/', '\\', $viewsPath);
                }
            }
            self::$instance = new self($viewsPath);
        }

        return self::$instance;
    }

    /**
     * Renderizar template
     *
     * @param string $template Nombre del template (ej: "pages/dashboard")
     * @param array $data Datos para el template
     * @param string|null $layout Layout a usar (null = sin layout)
     * @return string HTML renderizado
     */
    public function render(string $template, array $data = [], ?string $layout = 'layouts/base'): string
    {
        // Merger datos globales con datos del template
        $data = array_merge($this->globalData, $data);

        // Renderizar template
        $content = $this->mustache->render($template, $data);

        // Si hay layout, renderizar con el layout
        if ($layout !== null) {
            $layoutData = array_merge($data, ['content' => $content]);
            return $this->mustache->render($layout, $layoutData);
        }

        return $content;
    }

    /**
     * Renderizar partial (sin layout)
     */
    public function renderPartial(string $partial, array $data = []): string
    {
        $data = array_merge($this->globalData, $data);
        return $this->mustache->render($partial, $data);
    }

    /**
     * Establecer datos globales
     */
    public function setGlobalData(array $data): void
    {
        $this->globalData = array_merge($this->globalData, $data);
    }

    /**
     * Obtener helpers de Mustache
     */
    private function getHelpers(): array
    {
        // Capturar referencia al traductor para usar en closures
        $translator = $this->translator;

        return [
            // Helper de traducción
            '__' => function ($text) use ($translator) {
                if (strpos($text, '|') !== false) {
                    [$key, $params] = explode('|', $text, 2);
                    parse_str($params, $replace);
                    return $translator->translate($key, $replace);
                }
                return $translator->translate($text);
            },

            // Helper de fecha
            'date' => function ($timestamp, $format = 'Y-m-d H:i:s') {
                if (is_numeric($timestamp)) {
                    return date($format, (int)$timestamp);
                }
                return $timestamp;
            },

            // Helper de número formateado
            'number' => function ($number, $decimals = 2) {
                return number_format((float)$number, (int)$decimals, '.', ',');
            },

            // Helper de porcentaje
            'percentage' => function ($value) {
                return number_format((float)$value, 2) . '%';
            },

            // Helper de URL
            'url' => function ($path) {
                $baseUrl = $this->globalData['base_url'] ?? '';
                return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
            },

            // Helper de asset
            'asset' => function ($path) {
                $baseUrl = $this->globalData['base_url'] ?? '';
                return rtrim($baseUrl, '/') . '/assets/' . ltrim($path, '/');
            },

            // Helper de capitalize
            'capitalize' => function ($text) {
                return ucfirst(strtolower($text));
            },

            // Helper de uppercase
            'uppercase' => function ($text) {
                return strtoupper($text);
            },

            // Helper de lowercase
            'lowercase' => function ($text) {
                return strtolower($text);
            },

            // Helper de JSON encode
            'json' => function ($data) {
                return json_encode($data, JSON_UNESCAPED_UNICODE);
            },
        ];
    }

    /**
     * Agregar helper personalizado
     */
    public function addHelper(string $name, callable $helper): void
    {
        $this->mustache->addHelper($name, $helper);
    }

    /**
     * Establecer locale
     */
    public function setLocale(string $locale): void
    {
        $this->translator->setLocale($locale);
        $this->setGlobalData([
            'locale' => $locale,
            'app_name' => $this->translator->translate('common.app_name'),
        ]);
    }

    /**
     * Verificar si existe un template
     */
    public function exists(string $template): bool
    {
        $path = $this->viewsPath . '/' . $template . '.mustache';
        return file_exists($path);
    }
}
