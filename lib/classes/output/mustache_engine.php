<?php
namespace core\output;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Mustache Engine Wrapper
 *
 * Simple wrapper class for Mustache template rendering.
 * Used by the installer and other components that need
 * direct template rendering without the full template_manager.
 *
 * @package    core\output
 */
class mustache_engine {

    /** @var \Mustache_Engine Mustache engine instance */
    private \Mustache_Engine $engine;

    /**
     * Constructor
     *
     * Initializes the Mustache engine with default settings.
     * Uses templates directory as the default template path.
     */
    public function __construct() {
        // Determine template path
        $templatePath = BASE_DIR . '/templates';

        // Cache directory
        $cacheDir = BASE_DIR . '/var/cache/mustache';

        // Create cache directory if not exists
        if (!file_exists($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }

        // Initialize Mustache engine
        $this->engine = new \Mustache_Engine([
            'loader' => new \Mustache_Loader_FilesystemLoader($templatePath, [
                'extension' => '.mustache',
            ]),
            'partials_loader' => new \Mustache_Loader_FilesystemLoader($templatePath, [
                'extension' => '.mustache',
            ]),
            'cache' => $cacheDir,
            'escape' => function($value) {
                return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
            },
            'pragmas' => [\Mustache_Engine::PRAGMA_BLOCKS],
        ]);

        // Add string helper for i18n
        $this->engine->addHelper('str', function($text, \Mustache_LambdaHelper $helper) {
            $rendered = $helper->render($text);
            $parts = explode(',', $rendered, 2);
            $identifier = trim($parts[0]);
            $component = isset($parts[1]) ? trim($parts[1]) : 'core';

            if (function_exists('get_string')) {
                return get_string($identifier, $component);
            }
            return $identifier;
        });
    }

    /**
     * Render a template with context
     *
     * @param string $templateName Template name (e.g., 'install/welcome')
     * @param array|object $context Data context for template
     * @return string Rendered HTML
     */
    public function render(string $templateName, $context = []): string {
        // Convert object to array if needed
        if (is_object($context)) {
            $context = $this->objectToArray($context);
        }

        try {
            return $this->engine->render($templateName, $context);
        } catch (\Exception $e) {
            return '<div class="alert alert-danger">Template error: '
                . htmlspecialchars($templateName)
                . ' - ' . htmlspecialchars($e->getMessage())
                . '</div>';
        }
    }

    /**
     * Render a template from string
     *
     * @param string $template Template string
     * @param array|object $context Data context
     * @return string Rendered HTML
     */
    public function renderString(string $template, $context = []): string {
        if (is_object($context)) {
            $context = $this->objectToArray($context);
        }

        try {
            return $this->engine->render($template, $context);
        } catch (\Exception $e) {
            return '<div class="alert alert-danger">Template error: '
                . htmlspecialchars($e->getMessage())
                . '</div>';
        }
    }

    /**
     * Convert object to array recursively
     *
     * @param mixed $data Data to convert
     * @return mixed Converted data
     */
    private function objectToArray($data) {
        if (is_object($data)) {
            $data = get_object_vars($data);
        }

        if (is_array($data)) {
            return array_map([$this, 'objectToArray'], $data);
        }

        return $data;
    }

    /**
     * Add a helper to the engine
     *
     * @param string $name Helper name
     * @param callable $helper Helper function
     * @return void
     */
    public function addHelper(string $name, callable $helper): void {
        $this->engine->addHelper($name, $helper);
    }

    /**
     * Get the underlying Mustache engine
     *
     * @return \Mustache_Engine
     */
    public function getEngine(): \Mustache_Engine {
        return $this->engine;
    }
}
