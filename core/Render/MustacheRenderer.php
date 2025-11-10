<?php
/**
 * Sistema de renderizado Mustache para el ISER
 * @package core
 * @author ISER Desarrollo
 * @license Propietario
 */

namespace ISER\Core\Render;

use Mustache_Engine;
use Mustache_Loader_FilesystemLoader;

class MustacheRenderer
{
    private Mustache_Engine $mustache;
    private array $templatePaths = [];
    private array $globalData = [];
    private array $partials = [];

    public function __construct(array $templatePaths = [])
    {
        $this->templatePaths = $templatePaths;
        $this->initializeMustache();
    }

    /**
     * Inicializar el motor Mustache
     */
    private function initializeMustache(): void
    {
        $loaders = [];
        foreach ($this->templatePaths as $path) {
            if (is_dir($path)) {
                $loaders[] = new Mustache_Loader_FilesystemLoader($path, ['extension' => '.mustache']);
            }
        }

        $this->mustache = new Mustache_Engine([
            'loader' => $loaders[0] ?? new Mustache_Loader_FilesystemLoader('.'),
            'partials_loader' => new Mustache_Loader_FilesystemLoader(
                $this->templatePaths[0] ?? '.',
                ['extension' => '.mustache']
            ),
            'helpers' => $this->getHelpers(),
            'escape' => function($value) {
                return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            },
            'charset' => 'UTF-8',
            'strict_callables' => true,
            'pragmas' => [Mustache_Engine::PRAGMA_FILTERS]
        ]);
    }

    /**
     * Agregar una ruta de templates
     */
    public function addTemplatePath(string $path): void
    {
        if (!in_array($path, $this->templatePaths)) {
            $this->templatePaths[] = $path;
            $this->initializeMustache();
        }
    }

    /**
     * Registrar datos globales disponibles en todos los templates
     */
    public function setGlobalData(array $data): void
    {
        $this->globalData = array_merge($this->globalData, $data);
    }

    /**
     * Renderizar un template
     */
    public function render(string $template, array $data = []): string
    {
        $mergedData = array_merge($this->globalData, $data);

        // Agregar helpers específicos para cada renderizado
        $mergedData = $this->addContextHelpers($mergedData);

        return $this->mustache->render($template, $mergedData);
    }

    /**
     * Renderizar un archivo de template
     */
    public function renderFile(string $templatePath, array $data = []): string
    {
        foreach ($this->templatePaths as $path) {
            $fullPath = $path . '/' . $templatePath;
            if (!str_ends_with($fullPath, '.mustache')) {
                $fullPath .= '.mustache';
            }

            if (file_exists($fullPath)) {
                $templateContent = file_get_contents($fullPath);
                return $this->render($templateContent, $data);
            }
        }

        throw new \RuntimeException("Template no encontrado: {$templatePath}");
    }

    /**
     * Obtener helpers de Mustache
     */
    private function getHelpers(): array
    {
        return [
            // Helper para formato de fechas
            'date' => function($timestamp, $format = 'd/m/Y H:i') {
                if (is_numeric($timestamp)) {
                    return date($format, $timestamp);
                }
                return $timestamp;
            },

            // Helper para uppercase
            'upper' => function($text) {
                return strtoupper($text);
            },

            // Helper para lowercase
            'lower' => function($text) {
                return strtolower($text);
            },

            // Helper para capitalizar
            'capitalize' => function($text) {
                return ucfirst($text);
            },

            // Helper para truncar texto
            'truncate' => function($text, $length = 100) {
                if (strlen($text) > $length) {
                    return substr($text, 0, $length) . '...';
                }
                return $text;
            },

            // Helper para formato de números
            'number_format' => function($number, $decimals = 0) {
                return number_format($number, $decimals, ',', '.');
            },

            // Helper para URLs
            'url' => function($path) {
                $baseUrl = $_ENV['APP_URL'] ?? '';
                return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
            }
        ];
    }

    /**
     * Agregar helpers contextuales
     */
    private function addContextHelpers(array $data): array
    {
        // Agregar flags para tipos de campos en formularios
        if (isset($data['type'])) {
            $data['type_' . $data['type']] = true;
        }

        // Agregar flags para campos con valores específicos
        if (isset($data['fields']) && is_array($data['fields'])) {
            foreach ($data['fields'] as &$field) {
                if (isset($field['type'])) {
                    $field['type_' . $field['type']] = true;
                }
            }
        }

        return $data;
    }

    /**
     * Obtener el motor Mustache
     */
    public function getEngine(): Mustache_Engine
    {
        return $this->mustache;
    }

    /**
     * Verificar si un template existe
     */
    public function templateExists(string $templatePath): bool
    {
        foreach ($this->templatePaths as $path) {
            $fullPath = $path . '/' . $templatePath;
            if (!str_ends_with($fullPath, '.mustache')) {
                $fullPath .= '.mustache';
            }

            if (file_exists($fullPath)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Obtener lista de templates disponibles
     */
    public function getAvailableTemplates(): array
    {
        $templates = [];

        foreach ($this->templatePaths as $path) {
            if (is_dir($path)) {
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($path)
                );

                foreach ($iterator as $file) {
                    if ($file->isFile() && $file->getExtension() === 'mustache') {
                        $relativePath = str_replace($path . '/', '', $file->getPathname());
                        $relativePath = str_replace('.mustache', '', $relativePath);
                        $templates[] = $relativePath;
                    }
                }
            }
        }

        return array_unique($templates);
    }

    /**
     * Registrar un partial
     */
    public function registerPartial(string $name, string $content): void
    {
        $this->partials[$name] = $content;
    }

    /**
     * Limpiar caché de templates (si existe)
     */
    public function clearCache(): void
    {
        // Implementar si se usa caché de templates
    }
}
