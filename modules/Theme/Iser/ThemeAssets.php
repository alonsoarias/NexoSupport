<?php
/**
 * Gestión de CSS, JS, imágenes y fuentes del tema
 * @package theme_iser
 * @author ISER Desarrollo
 */

namespace ISER\Theme\Iser;

class ThemeAssets
{
    private array $assetPaths;
    private array $cssFiles = [];
    private array $jsFiles = [];
    private array $jsVariables = [];
    private string $themeBasePath;

    public function __construct(array $config = [])
    {
        $this->themeBasePath = __DIR__ . '/../assets';
        $this->assetPaths = [
            'css' => $this->themeBasePath . '/css',
            'js' => $this->themeBasePath . '/js',
            'images' => $this->themeBasePath . '/images',
            'fonts' => $this->themeBasePath . '/fonts'
        ];

        // Cargar configuración de assets predefinidos
        if (isset($config['css'])) {
            foreach ($config['css'] as $css) {
                $this->addCss($css);
            }
        }

        if (isset($config['js'])) {
            foreach ($config['js'] as $js) {
                $this->addJs($js);
            }
        }
    }

    // ==================== GESTIÓN DE CSS ====================

    /**
     * Agregar archivo CSS
     */
    public function addCss(string $file, array $dependencies = [], int $priority = 50): void
    {
        $this->cssFiles[] = [
            'file' => $file,
            'dependencies' => $dependencies,
            'priority' => $priority
        ];
    }

    /**
     * Obtener salida HTML de CSS
     */
    public function getCssOutput(): string
    {
        $output = '';
        $sortedCss = $this->sortByPriority($this->cssFiles);

        foreach ($sortedCss as $css) {
            $url = $this->resolveAssetUrl($css['file'], 'css');
            $output .= "<link rel=\"stylesheet\" href=\"{$url}\">\n";
        }

        return $output;
    }

    /**
     * Obtener lista de archivos CSS
     */
    public function getCssFiles(): array
    {
        $files = [];
        $sortedCss = $this->sortByPriority($this->cssFiles);

        foreach ($sortedCss as $css) {
            $files[] = $this->resolveAssetUrl($css['file'], 'css');
        }

        return $files;
    }

    /**
     * Compilar archivo SCSS (si scssphp está disponible)
     */
    public function compileScss(string $scssFile): ?string
    {
        if (!class_exists('ScssPhp\ScssPhp\Compiler')) {
            return null;
        }

        try {
            $compiler = new \ScssPhp\ScssPhp\Compiler();
            $compiler->setImportPaths($this->assetPaths['css']);

            $scssPath = $this->assetPaths['css'] . '/' . $scssFile;
            if (!file_exists($scssPath)) {
                return null;
            }

            $scss = file_get_contents($scssPath);
            return $compiler->compileString($scss)->getCss();
        } catch (\Exception $e) {
            error_log("Error compilando SCSS: " . $e->getMessage());
            return null;
        }
    }

    // ==================== GESTIÓN DE JAVASCRIPT ====================

    /**
     * Agregar archivo JavaScript
     */
    public function addJs(
        string $file,
        array $dependencies = [],
        bool $inFooter = false,
        int $priority = 50
    ): void {
        $this->jsFiles[] = [
            'file' => $file,
            'dependencies' => $dependencies,
            'in_footer' => $inFooter,
            'priority' => $priority
        ];
    }

    /**
     * Agregar variable JavaScript
     */
    public function addJsVariable(string $name, mixed $value): void
    {
        $this->jsVariables[$name] = $value;
    }

    /**
     * Obtener salida HTML de JavaScript
     */
    public function getJsOutput(bool $footerOnly = false): string
    {
        $output = '';

        // Variables JavaScript
        if (!$footerOnly && !empty($this->jsVariables)) {
            $output .= "<script>\n";
            foreach ($this->jsVariables as $name => $value) {
                $jsonValue = json_encode($value);
                $output .= "window.{$name} = {$jsonValue};\n";
            }
            $output .= "</script>\n";
        }

        // Archivos JavaScript
        $sortedJs = $this->sortByPriority($this->jsFiles);

        foreach ($sortedJs as $js) {
            if ($footerOnly && !$js['in_footer']) {
                continue;
            }
            if (!$footerOnly && $js['in_footer']) {
                continue;
            }

            $url = $this->resolveAssetUrl($js['file'], 'js');
            $defer = $js['in_footer'] ? ' defer' : '';
            $output .= "<script src=\"{$url}\"{$defer}></script>\n";
        }

        return $output;
    }

    /**
     * Obtener lista de archivos JavaScript
     */
    public function getJsFiles(bool $footerOnly = false): array
    {
        $files = [];
        $sortedJs = $this->sortByPriority($this->jsFiles);

        foreach ($sortedJs as $js) {
            if ($footerOnly && !$js['in_footer']) {
                continue;
            }
            if (!$footerOnly && $js['in_footer']) {
                continue;
            }

            $files[] = [
                'url' => $this->resolveAssetUrl($js['file'], 'js'),
                'defer' => $js['in_footer']
            ];
        }

        return $files;
    }

    // ==================== GESTIÓN DE IMÁGENES Y FUENTES ====================

    /**
     * Obtener URL de imagen
     */
    public function getImageUrl(string $imageName): string
    {
        return $this->resolveAssetUrl($imageName, 'images');
    }

    /**
     * Obtener URL de fuente
     */
    public function getFontUrl(string $fontName): string
    {
        return $this->resolveAssetUrl($fontName, 'fonts');
    }

    /**
     * Verificar si una imagen existe
     */
    public function imageExists(string $imageName): bool
    {
        $path = $this->assetPaths['images'] . '/' . $imageName;
        return file_exists($path);
    }

    // ==================== OPTIMIZACIÓN ====================

    /**
     * Minificar CSS
     */
    public function minifyCss(string $css): string
    {
        // Eliminar comentarios
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);

        // Eliminar espacios en blanco innecesarios
        $css = str_replace(["\r\n", "\r", "\n", "\t"], '', $css);
        $css = preg_replace('/\s+/', ' ', $css);
        $css = preg_replace('/\s*([:;{}])\s*/', '$1', $css);

        return trim($css);
    }

    /**
     * Minificar JavaScript
     */
    public function minifyJs(string $js): string
    {
        // Nota: Para minificación completa se recomienda usar herramientas como Terser o UglifyJS
        // Esta es una minificación básica

        // Eliminar comentarios de una línea
        $js = preg_replace('/\/\/.*$/m', '', $js);

        // Eliminar comentarios multilínea
        $js = preg_replace('/\/\*[\s\S]*?\*\//', '', $js);

        // Eliminar espacios en blanco excesivos (pero preservar espacios necesarios)
        $js = preg_replace('/\s+/', ' ', $js);

        return trim($js);
    }

    /**
     * Combinar múltiples assets en uno solo
     */
    public function combineAssets(array $assets, string $type = 'css'): string
    {
        $combined = '';

        foreach ($assets as $asset) {
            $path = $this->assetPaths[$type] . '/' . $asset;
            if (file_exists($path)) {
                $content = file_get_contents($path);
                $combined .= $content . "\n";
            }
        }

        // Minificar el resultado combinado
        if ($type === 'css') {
            $combined = $this->minifyCss($combined);
        } elseif ($type === 'js') {
            $combined = $this->minifyJs($combined);
        }

        return $combined;
    }

    /**
     * Crear archivo combinado y minificado en caché
     */
    public function createCombinedFile(array $assets, string $type, string $outputName): bool
    {
        $combined = $this->combineAssets($assets, $type);
        $outputPath = $this->assetPaths[$type] . '/' . $outputName;

        return file_put_contents($outputPath, $combined) !== false;
    }

    // ==================== UTILIDADES ====================

    /**
     * Resolver URL de asset
     */
    private function resolveAssetUrl(string $file, string $type): string
    {
        // Si ya es una URL completa, devolverla tal cual
        if (str_starts_with($file, 'http://') || str_starts_with($file, 'https://') || str_starts_with($file, '//')) {
            return $file;
        }

        $baseUrl = $_ENV['APP_URL'] ?? '';
        $themeUrl = rtrim($baseUrl, '/') . '/theme/iser';

        return "{$themeUrl}/{$type}/{$file}";
    }

    /**
     * Ordenar assets por prioridad
     */
    private function sortByPriority(array $assets): array
    {
        usort($assets, function($a, $b) {
            return $a['priority'] <=> $b['priority'];
        });

        return $assets;
    }

    /**
     * Obtener tamaño total de assets
     */
    public function getTotalAssetsSize(): array
    {
        $sizes = [
            'css' => 0,
            'js' => 0,
            'images' => 0,
            'fonts' => 0,
            'total' => 0
        ];

        foreach ($this->assetPaths as $type => $path) {
            if (is_dir($path)) {
                $sizes[$type] = $this->getDirectorySize($path);
                $sizes['total'] += $sizes[$type];
            }
        }

        return $sizes;
    }

    /**
     * Obtener tamaño de un directorio
     */
    private function getDirectorySize(string $path): int
    {
        $size = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }

        return $size;
    }

    /**
     * Limpiar caché de assets
     */
    public function clearCache(): bool
    {
        // Implementar si se usa sistema de caché
        $cacheDir = $this->themeBasePath . '/cache';
        if (is_dir($cacheDir)) {
            $files = glob($cacheDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            return true;
        }

        return false;
    }

    /**
     * Obtener información de assets
     */
    public function getAssetsInfo(): array
    {
        return [
            'css_count' => count($this->cssFiles),
            'js_count' => count($this->jsFiles),
            'js_variables' => count($this->jsVariables),
            'total_size' => $this->getTotalAssetsSize()
        ];
    }
}
