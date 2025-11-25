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

    /** @var \Mustache\Engine Mustache engine instance */
    private \Mustache\Engine $engine;

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
        $this->engine = new \Mustache\Engine([
            'loader' => new \Mustache\Loader\FilesystemLoader($templatePath, [
                'extension' => '.mustache',
            ]),
            'partials_loader' => new \Mustache\Loader\FilesystemLoader($templatePath, [
                'extension' => '.mustache',
            ]),
            'cache' => $cacheDir,
            'escape' => function($value) {
                return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
            },
            'pragmas' => [\Mustache\Engine::PRAGMA_BLOCKS],
        ]);

        // Add string helper for i18n
        // Syntax: {{#str}}identifier,component{{/str}} or {{#str}}identifier,component,data{{/str}}
        $this->engine->addHelper('str', function($text, \Mustache\LambdaHelper $helper) {
            $rendered = $helper->render($text);

            // Parse the string carefully - we need to handle JSON data with commas
            // Format: identifier,component or identifier,component,{json}
            $identifier = '';
            $component = 'core';
            $a = null;

            // Check if there's JSON data (starts with { after second comma)
            if (preg_match('/^([^,]+),([^,]+),(\{.+\})$/', $rendered, $matches)) {
                $identifier = trim($matches[1]);
                $component = trim($matches[2]);
                $jsonData = $matches[3];
                $a = json_decode($jsonData);
            } else {
                // No JSON data - simple split
                $parts = explode(',', $rendered, 3);
                $identifier = trim($parts[0]);
                if (isset($parts[1])) {
                    $component = trim($parts[1]);
                }
                // Third part could be a simple value
                if (isset($parts[2])) {
                    $a = trim($parts[2]);
                }
            }

            if (function_exists('get_string')) {
                return get_string($identifier, $component, $a);
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
     * @return \Mustache\Engine
     */
    public function getEngine(): \Mustache\Engine {
        return $this->engine;
    }
}
