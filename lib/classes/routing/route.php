<?php
namespace core\routing;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Route Definition Class
 *
 * Represents a single route in the system.
 * Following Moodle-style plugin architecture, routes can be
 * registered by plugins through their plugin class.
 *
 * @package core\routing
 */
class route {

    /** @var string HTTP method (GET, POST, etc.) */
    public string $method;

    /** @var string Route path pattern */
    public string $path;

    /** @var string|callable Handler for the route */
    public $handler;

    /** @var string|null Optional name for URL generation */
    public ?string $name = null;

    /** @var array Middleware to apply */
    public array $middleware = [];

    /** @var array Parameter constraints (regex patterns) */
    public array $where = [];

    /** @var string|null Component that registered this route */
    public ?string $component = null;

    /**
     * Constructor
     *
     * @param string $method HTTP method
     * @param string $path Route path
     * @param string|callable $handler Route handler
     */
    public function __construct(string $method, string $path, $handler) {
        $this->method = strtoupper($method);
        $this->path = $this->normalize_path($path);
        $this->handler = $handler;
    }

    /**
     * Normalize path (ensure leading slash, remove trailing slash)
     *
     * @param string $path
     * @return string
     */
    private function normalize_path(string $path): string {
        $path = '/' . trim($path, '/');
        return $path === '/' ? '/' : $path;
    }

    /**
     * Set route name for URL generation
     *
     * @param string $name Route name
     * @return self
     */
    public function name(string $name): self {
        $this->name = $name;
        return $this;
    }

    /**
     * Add parameter constraint
     *
     * @param string $param Parameter name
     * @param string $pattern Regex pattern
     * @return self
     */
    public function where(string $param, string $pattern): self {
        $this->where[$param] = $pattern;
        return $this;
    }

    /**
     * Add middleware
     *
     * @param string|array $middleware Middleware name(s)
     * @return self
     */
    public function middleware($middleware): self {
        $this->middleware = array_merge($this->middleware, (array)$middleware);
        return $this;
    }

    /**
     * Set component that owns this route
     *
     * @param string $component Component name (e.g., 'core', 'auth_manual')
     * @return self
     */
    public function component(string $component): self {
        $this->component = $component;
        return $this;
    }

    /**
     * Check if route matches the given path
     *
     * @param string $uri Request URI
     * @param array &$params Extracted parameters (output)
     * @return bool True if matches
     */
    public function matches(string $uri, array &$params = []): bool {
        $uri = $this->normalize_path($uri);

        // Exact match
        if ($this->path === $uri) {
            return true;
        }

        // Check for parameters in route
        if (strpos($this->path, '{') === false) {
            return false;
        }

        // Convert route pattern to regex
        $pattern = preg_replace_callback('/\{([a-zA-Z][a-zA-Z0-9_]*)\}/', function($matches) {
            $paramName = $matches[1];
            // Use constraint if defined, otherwise match anything except /
            $regex = $this->where[$paramName] ?? '[^/]+';
            return '(?P<' . $paramName . '>' . $regex . ')';
        }, $this->path);

        $pattern = '#^' . $pattern . '$#';

        if (preg_match($pattern, $uri, $matches)) {
            // Extract named parameters
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = $value;
                }
            }
            return true;
        }

        return false;
    }

    /**
     * Create GET route
     *
     * @param string $path Route path
     * @param string|callable $handler Route handler
     * @return self
     */
    public static function get(string $path, $handler): self {
        return new self('GET', $path, $handler);
    }

    /**
     * Create POST route
     *
     * @param string $path Route path
     * @param string|callable $handler Route handler
     * @return self
     */
    public static function post(string $path, $handler): self {
        return new self('POST', $path, $handler);
    }

    /**
     * Create route for any HTTP method
     *
     * @param string $path Route path
     * @param string|callable $handler Route handler
     * @return self
     */
    public static function any(string $path, $handler): self {
        return new self('ANY', $path, $handler);
    }
}
