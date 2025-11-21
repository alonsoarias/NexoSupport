<?php
/**
 * URL handling class.
 *
 * @package    core
 * @copyright  2025 NexoSupport
 * @license    Proprietary - NexoSupport
 */

namespace core;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Class for creating and manipulating URLs.
 *
 * NexoSupport URL class based on Moodle's architecture.
 */
class nexo_url {

    /** @var string Base URL */
    protected string $url;

    /** @var array URL parameters */
    protected array $params = [];

    /** @var string Anchor/fragment */
    protected string $anchor = '';

    /**
     * Create a new URL.
     *
     * @param string $url Base URL (can be relative)
     * @param array $params URL parameters
     * @param string $anchor URL anchor
     */
    public function __construct(string $url, array $params = [], string $anchor = '') {
        global $CFG;

        // Handle URLs starting with /
        if (strpos($url, '/') === 0 && strpos($url, '//') !== 0) {
            $url = $CFG->wwwroot . $url;
        }

        // Parse existing URL parameters
        $parsedUrl = parse_url($url);

        if (isset($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $existingParams);
            $params = array_merge($existingParams, $params);
        }

        // Build base URL without query string
        $this->url = '';
        if (isset($parsedUrl['scheme'])) {
            $this->url .= $parsedUrl['scheme'] . '://';
        }
        if (isset($parsedUrl['host'])) {
            $this->url .= $parsedUrl['host'];
        }
        if (isset($parsedUrl['port'])) {
            $this->url .= ':' . $parsedUrl['port'];
        }
        if (isset($parsedUrl['path'])) {
            $this->url .= $parsedUrl['path'];
        }

        $this->params = $params;

        if (isset($parsedUrl['fragment'])) {
            $this->anchor = $parsedUrl['fragment'];
        } elseif ($anchor) {
            $this->anchor = $anchor;
        }
    }

    /**
     * Add or update a parameter.
     *
     * @param string $name Parameter name
     * @param mixed $value Parameter value
     * @return self
     */
    public function param(string $name, $value): self {
        $this->params[$name] = $value;
        return $this;
    }

    /**
     * Add or update multiple parameters.
     *
     * @param array $params Parameters to add
     * @return self
     */
    public function params(array $params): self {
        $this->params = array_merge($this->params, $params);
        return $this;
    }

    /**
     * Remove a parameter.
     *
     * @param string $name Parameter name
     * @return self
     */
    public function remove_param(string $name): self {
        unset($this->params[$name]);
        return $this;
    }

    /**
     * Remove all parameters.
     *
     * @return self
     */
    public function remove_all_params(): self {
        $this->params = [];
        return $this;
    }

    /**
     * Get the URL as a string.
     *
     * @param bool $escaped Whether to escape ampersands
     * @return string The full URL
     */
    public function out(bool $escaped = true): string {
        $url = $this->url;

        if (!empty($this->params)) {
            $separator = $escaped ? '&amp;' : '&';
            $queryString = http_build_query($this->params, '', $separator);
            $url .= '?' . $queryString;
        }

        if (!empty($this->anchor)) {
            $url .= '#' . $this->anchor;
        }

        return $url;
    }

    /**
     * Get the URL without query string.
     *
     * @return string Base URL without parameters
     */
    public function out_omit_querystring(): string {
        return $this->url;
    }

    /**
     * Get the parameters.
     *
     * @return array URL parameters
     */
    public function get_params(): array {
        return $this->params;
    }

    /**
     * Get a specific parameter.
     *
     * @param string $name Parameter name
     * @return mixed Parameter value or null
     */
    public function get_param(string $name) {
        return $this->params[$name] ?? null;
    }

    /**
     * Convert to string.
     *
     * @return string The URL
     */
    public function __toString(): string {
        return $this->out(false);
    }

    /**
     * Create a URL from the current page.
     *
     * @return nexo_url The current page URL
     */
    public static function make_pluginfile_url(): nexo_url {
        global $CFG;
        return new self($CFG->wwwroot . '/pluginfile.php');
    }
}

// Backward compatibility alias for global namespace
class_alias('core\\nexo_url', 'nexo_url');
