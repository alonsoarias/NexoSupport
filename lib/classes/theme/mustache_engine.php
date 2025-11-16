<?php
/**
 * NexoSupport - Mustache Template Engine
 *
 * @package    core
 * @copyright  2024 ISER
 * @license    Proprietary
 */

namespace ISER\Core\Theme;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Mustache Template Engine
 *
 * Simple Mustache-compatible template engine
 */
class MustacheEngine
{
    /** @var array Partials */
    private $partials = [];

    /** @var array Helpers */
    private $helpers = [];

    /** @var bool Enable caching */
    private $cache_enabled = true;

    /** @var array Template cache */
    private static $cache = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        // Register default helpers
        $this->register_default_helpers();
    }

    /**
     * Render template
     *
     * @param string $template_path Template file path or content
     * @param array $data Data to render
     * @return string Rendered output
     */
    public function render(string $template_path, array $data = []): string
    {
        // Load template content
        if (file_exists($template_path)) {
            $template = file_get_contents($template_path);
        } else {
            $template = $template_path;
        }

        // Check cache
        $cache_key = md5($template);
        if ($this->cache_enabled && isset(self::$cache[$cache_key])) {
            return $this->render_cached(self::$cache[$cache_key], $data);
        }

        // Parse and render
        $rendered = $this->parse($template, $data);

        // Cache compiled template
        if ($this->cache_enabled) {
            self::$cache[$cache_key] = $template;
        }

        return $rendered;
    }

    /**
     * Parse template
     *
     * @param string $template Template content
     * @param array $data Data
     * @return string Parsed content
     */
    private function parse(string $template, array $data): string
    {
        // Handle {{variable}}
        $template = preg_replace_callback('/\{\{([^}]+)\}\}/', function ($matches) use ($data) {
            $key = trim($matches[1]);

            // Handle negation {{^var}}
            if (substr($key, 0, 1) === '^') {
                return ''; // Simplified - would need block handling
            }

            // Handle sections {{#var}}
            if (substr($key, 0, 1) === '#') {
                return ''; // Simplified - would need block handling
            }

            // Handle partials {{>partial}}
            if (substr($key, 0, 1) === '>') {
                $partial_name = trim(substr($key, 1));
                return $this->render_partial($partial_name, $data);
            }

            // Handle comments {{!comment}}
            if (substr($key, 0, 1) === '!') {
                return '';
            }

            // Handle unescaped {{{var}}}
            // (not handling triple braces in this regex)

            // Get value from data
            return $this->get_value($data, $key);
        }, $template);

        // Handle {{{unescaped}}}
        $template = preg_replace_callback('/\{\{\{([^}]+)\}\}\}/', function ($matches) use ($data) {
            $key = trim($matches[1]);
            return $this->get_value($data, $key, false);
        }, $template);

        // Handle sections {{#section}}...{{/section}}
        $template = $this->parse_sections($template, $data);

        // Handle inverted sections {{^section}}...{{/section}}
        $template = $this->parse_inverted_sections($template, $data);

        return $template;
    }

    /**
     * Parse sections
     *
     * @param string $template Template
     * @param array $data Data
     * @return string Parsed template
     */
    private function parse_sections(string $template, array $data): string
    {
        return preg_replace_callback('/\{\{#(\w+)\}\}(.*?)\{\{\/\1\}\}/s', function ($matches) use ($data) {
            $key = $matches[1];
            $content = $matches[2];

            $value = $this->get_value($data, $key, false);

            if (!$value) {
                return '';
            }

            if (is_array($value)) {
                // Loop through array
                $output = '';
                foreach ($value as $item) {
                    $item_data = is_array($item) ? $item : ['.' => $item];
                    $output .= $this->parse($content, array_merge($data, $item_data));
                }
                return $output;
            }

            // Boolean true or object
            return $this->parse($content, $data);
        }, $template);
    }

    /**
     * Parse inverted sections
     *
     * @param string $template Template
     * @param array $data Data
     * @return string Parsed template
     */
    private function parse_inverted_sections(string $template, array $data): string
    {
        return preg_replace_callback('/\{\{\^(\w+)\}\}(.*?)\{\{\/\1\}\}/s', function ($matches) use ($data) {
            $key = $matches[1];
            $content = $matches[2];

            $value = $this->get_value($data, $key, false);

            // Render if value is false, empty, or doesn't exist
            if (!$value || (is_array($value) && empty($value))) {
                return $this->parse($content, $data);
            }

            return '';
        }, $template);
    }

    /**
     * Get value from data
     *
     * @param array $data Data array
     * @param string $key Key (supports dot notation)
     * @param bool $escape Escape HTML
     * @return string Value
     */
    private function get_value(array $data, string $key, bool $escape = true): string
    {
        // Handle dot notation
        if (strpos($key, '.') !== false) {
            $keys = explode('.', $key);
            $value = $data;

            foreach ($keys as $k) {
                if (is_array($value) && isset($value[$k])) {
                    $value = $value[$k];
                } else {
                    return '';
                }
            }
        } else {
            $value = $data[$key] ?? '';
        }

        // Convert to string
        if (is_bool($value)) {
            $value = $value ? 'true' : '';
        } elseif (is_array($value) || is_object($value)) {
            $value = json_encode($value);
        } else {
            $value = (string)$value;
        }

        // Escape HTML if needed
        return $escape ? htmlspecialchars($value, ENT_QUOTES, 'UTF-8') : $value;
    }

    /**
     * Render partial
     *
     * @param string $name Partial name
     * @param array $data Data
     * @return string Rendered partial
     */
    private function render_partial(string $name, array $data): string
    {
        if (isset($this->partials[$name])) {
            return $this->parse($this->partials[$name], $data);
        }

        return "<!-- Partial not found: $name -->";
    }

    /**
     * Add partial
     *
     * @param string $name Partial name
     * @param string $template Template content
     * @return void
     */
    public function add_partial(string $name, string $template): void
    {
        $this->partials[$name] = $template;
    }

    /**
     * Add helper
     *
     * @param string $name Helper name
     * @param callable $callback Callback function
     * @return void
     */
    public function add_helper(string $name, callable $callback): void
    {
        $this->helpers[$name] = $callback;
    }

    /**
     * Register default helpers
     *
     * @return void
     */
    private function register_default_helpers(): void
    {
        // Date helper
        $this->add_helper('date', function ($value, $format = 'Y-m-d') {
            return date($format, strtotime($value));
        });

        // Uppercase helper
        $this->add_helper('upper', function ($value) {
            return strtoupper($value);
        });

        // Lowercase helper
        $this->add_helper('lower', function ($value) {
            return strtolower($value);
        });
    }

    /**
     * Render cached template
     *
     * @param string $template Cached template
     * @param array $data Data
     * @return string Rendered output
     */
    private function render_cached(string $template, array $data): string
    {
        return $this->parse($template, $data);
    }

    /**
     * Clear cache
     *
     * @return void
     */
    public function clear_cache(): void
    {
        self::$cache = [];
    }

    /**
     * Enable/disable caching
     *
     * @param bool $enabled Enable caching
     * @return void
     */
    public function set_caching(bool $enabled): void
    {
        $this->cache_enabled = $enabled;
    }
}
