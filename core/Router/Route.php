<?php

/**
 * ISER Authentication System - Route Class
 *
 * Represents a single route in the routing system.
 *
 * @package    ISER\Core\Router
 * @category   Core
 * @author     ISER Development Team
 * @copyright  2024 ISER
 * @license    Proprietary
 * @version    1.0.0
 * @since      Phase 1
 */

namespace ISER\Core\Router;

/**
 * Route Class
 *
 * Encapsulates route information and matching logic.
 */
class Route
{
    /**
     * Route path pattern
     */
    private string $path;

    /**
     * Route handler (callable or string)
     */
    private mixed $handler;

    /**
     * HTTP methods allowed for this route
     */
    private array $methods;

    /**
     * Route name
     */
    private ?string $name;

    /**
     * Route middleware
     */
    private array $middleware = [];

    /**
     * Route parameters
     */
    private array $parameters = [];

    /**
     * Constructor
     *
     * @param string $path Route path pattern
     * @param mixed $handler Route handler
     * @param array $methods HTTP methods (default: ['GET'])
     * @param string|null $name Route name
     */
    public function __construct(
        string $path,
        mixed $handler,
        array $methods = ['GET'],
        ?string $name = null
    ) {
        $this->path = $path;
        $this->handler = $handler;
        $this->methods = array_map('strtoupper', $methods);
        $this->name = $name;
    }

    /**
     * Get route path
     *
     * @return string Route path
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Get route handler
     *
     * @return mixed Route handler
     */
    public function getHandler(): mixed
    {
        return $this->handler;
    }

    /**
     * Get HTTP methods
     *
     * @return array HTTP methods
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * Get route name
     *
     * @return string|null Route name
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Set route name
     *
     * @param string $name Route name
     * @return self
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Add middleware to route
     *
     * @param string|callable $middleware Middleware
     * @return self
     */
    public function middleware(string|callable $middleware): self
    {
        $this->middleware[] = $middleware;
        return $this;
    }

    /**
     * Get route middleware
     *
     * @return array Middleware array
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    /**
     * Check if route matches the given path and method
     *
     * @param string $path Request path
     * @param string $method HTTP method
     * @return bool True if matches
     */
    public function matches(string $path, string $method): bool
    {
        // Check HTTP method
        if (!in_array(strtoupper($method), $this->methods)) {
            return false;
        }

        // Check path pattern
        $pattern = $this->buildPattern();
        return preg_match($pattern, $path, $this->parameters) === 1;
    }

    /**
     * Build regex pattern from route path
     *
     * @return string Regex pattern
     */
    private function buildPattern(): string
    {
        $pattern = $this->path;

        // Replace {param} with named regex groups
        $pattern = preg_replace(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',
            '(?P<$1>[^/]+)',
            $pattern
        );

        // Replace {param?} with optional named regex groups
        $pattern = preg_replace(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)\?\}/',
            '(?P<$1>[^/]*)',
            $pattern
        );

        // Escape forward slashes
        $pattern = str_replace('/', '\/', $pattern);

        return '/^' . $pattern . '$/';
    }

    /**
     * Get route parameters from matched path
     *
     * @return array Route parameters
     */
    public function getParameters(): array
    {
        // Filter out numeric keys from preg_match results
        return array_filter(
            $this->parameters,
            fn($key) => !is_numeric($key),
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * Get specific parameter value
     *
     * @param string $name Parameter name
     * @param mixed $default Default value
     * @return mixed Parameter value or default
     */
    public function getParameter(string $name, mixed $default = null): mixed
    {
        $params = $this->getParameters();
        return $params[$name] ?? $default;
    }

    /**
     * Check if route accepts given method
     *
     * @param string $method HTTP method
     * @return bool True if method is accepted
     */
    public function acceptsMethod(string $method): bool
    {
        return in_array(strtoupper($method), $this->methods);
    }

    /**
     * Generate URL for this route
     *
     * @param array $params Parameters to fill in the path
     * @return string Generated URL
     */
    public function url(array $params = []): string
    {
        $url = $this->path;

        foreach ($params as $key => $value) {
            $url = str_replace('{' . $key . '}', $value, $url);
            $url = str_replace('{' . $key . '?}', $value, $url);
        }

        // Remove optional parameters that weren't provided
        $url = preg_replace('/\{[a-zA-Z_][a-zA-Z0-9_]*\?\}/', '', $url);

        return $url;
    }

    /**
     * Convert route to array
     *
     * @return array Route data
     */
    public function toArray(): array
    {
        return [
            'path' => $this->path,
            'handler' => is_callable($this->handler) ? 'Closure' : $this->handler,
            'methods' => $this->methods,
            'name' => $this->name,
            'middleware' => $this->middleware,
        ];
    }

    /**
     * String representation of route
     *
     * @return string Route string
     */
    public function __toString(): string
    {
        return sprintf(
            '%s %s => %s',
            implode('|', $this->methods),
            $this->path,
            is_callable($this->handler) ? 'Closure' : $this->handler
        );
    }
}
