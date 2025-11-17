<?php
/**
 * ViewRenderer - Template rendering system wrapper
 *
 * This class provides a unified interface for template rendering,
 * wrapping the MustacheRenderer with plugin-aware template loading.
 *
 * @package    ISER\Core\View
 * @copyright  2025 ISER
 * @license    Proprietary
 */

declare(strict_types=1);

namespace ISER\Core\View;

use Mustache_Engine;
use Mustache_Loader_FilesystemLoader;

/**
 * ViewRenderer - Manages template rendering for plugins and core
 */
class ViewRenderer
{
    /**
     * @var ViewRenderer Singleton instance
     */
    private static ?ViewRenderer $instance = null;

    /**
     * @var Mustache_Engine Mustache engine instance
     */
    private Mustache_Engine $mustache;

    /**
     * @var string Base templates path
     */
    private string $basePath;

    /**
     * @var array Template paths for different components
     */
    private array $templatePaths = [];

    /**
     * Constructor - Initialize Mustache engine
     */
    private function __construct()
    {
        // Set base path to the root of the application
        $this->basePath = dirname(__DIR__, 2);

        // Configure Mustache with custom loader that supports plugin paths
        $this->mustache = new Mustache_Engine([
            'loader' => new Mustache_Loader_FilesystemLoader($this->basePath),
            'partials_loader' => new Mustache_Loader_FilesystemLoader($this->basePath),
            'escape' => function ($value) {
                return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
            },
            'entity_flags' => ENT_QUOTES,
            'charset' => 'UTF-8',
        ]);
    }

    /**
     * Get singleton instance
     *
     * @return ViewRenderer
     */
    public static function getInstance(): ViewRenderer
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Render a template
     *
     * Supports plugin-style template paths like:
     * - "report_log/index" -> looks in report/log/templates/index.mustache
     * - "admin_user/list" -> looks in admin/user/templates/list.mustache
     * - "core/header" -> looks in core/templates/header.mustache
     *
     * @param string $template Template name (e.g., "report_log/index")
     * @param array $data Data to pass to template
     * @return string Rendered HTML
     */
    public function render(string $template, array $data = []): string
    {
        $templatePath = $this->resolveTemplatePath($template);

        if (!file_exists($templatePath)) {
            throw new \RuntimeException("Template not found: {$template} (looked in: {$templatePath})");
        }

        // Load template content
        $templateContent = file_get_contents($templatePath);

        // Render with Mustache
        return $this->mustache->render($templateContent, $data);
    }

    /**
     * Render a partial template
     *
     * @param string $partial Partial name
     * @param array $data Data to pass to partial
     * @return string Rendered HTML
     */
    public function renderPartial(string $partial, array $data = []): string
    {
        return $this->render($partial, $data);
    }

    /**
     * Resolve template path from template name
     *
     * Converts plugin-style paths to actual filesystem paths:
     * - "report_log/index" -> "report/log/templates/index.mustache"
     * - "report_log/partials/table" -> "report/log/templates/partials/table.mustache"
     *
     * @param string $template Template name
     * @return string Full filesystem path to template
     */
    private function resolveTemplatePath(string $template): string
    {
        // Check if it's a plugin-style path (e.g., "report_log/...")
        if (strpos($template, '/') !== false) {
            $parts = explode('/', $template, 2);
            $component = $parts[0];
            $templateFile = $parts[1] ?? 'index';

            // Convert component name to path (e.g., "report_log" -> "report/log")
            $componentPath = $this->componentToPath($component);

            // Build full path: component/templates/file.mustache
            return $this->basePath . '/' . $componentPath . '/templates/' . $templateFile . '.mustache';
        }

        // Fallback: treat as direct path
        return $this->basePath . '/templates/' . $template . '.mustache';
    }

    /**
     * Convert component name to directory path
     *
     * Examples:
     * - "report_log" -> "report/log"
     * - "admin_user" -> "admin/user"
     * - "core" -> "core"
     *
     * @param string $component Component name
     * @return string Directory path
     */
    private function componentToPath(string $component): string
    {
        // Handle component names with underscores (e.g., "report_log")
        if (strpos($component, '_') !== false) {
            return str_replace('_', '/', $component);
        }

        return $component;
    }

    /**
     * Check if a template exists
     *
     * @param string $template Template name
     * @return bool
     */
    public function exists(string $template): bool
    {
        try {
            $templatePath = $this->resolveTemplatePath($template);
            return file_exists($templatePath);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Add a custom helper to Mustache
     *
     * @param string $name Helper name
     * @param callable $helper Helper function
     * @return void
     */
    public function addHelper(string $name, callable $helper): void
    {
        $this->mustache->addHelper($name, $helper);
    }
}
