<?php
namespace core\routing;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Route Collection
 *
 * Holds a collection of routes and provides methods
 * for adding, grouping, and finding routes.
 *
 * @package core\routing
 */
class route_collection {

    /** @var array Registered routes */
    private array $routes = [];

    /** @var array Named routes for URL generation */
    private array $named_routes = [];

    /** @var array Current group attributes */
    private array $group_stack = [];

    /**
     * Add a route to the collection
     *
     * @param route $route Route to add
     * @return route The added route
     */
    public function add(route $route): route {
        // Apply group attributes if any
        if (!empty($this->group_stack)) {
            $group = end($this->group_stack);

            // Apply prefix
            if (!empty($group['prefix'])) {
                $route->path = rtrim($group['prefix'], '/') . '/' . ltrim($route->path, '/');
                $route->path = $route->path === '/' ? '/' : rtrim($route->path, '/');
            }

            // Apply middleware
            if (!empty($group['middleware'])) {
                $route->middleware = array_merge($group['middleware'], $route->middleware);
            }

            // Apply component
            if (!empty($group['component']) && $route->component === null) {
                $route->component = $group['component'];
            }
        }

        $this->routes[] = $route;

        // Index by name if named
        if ($route->name !== null) {
            $this->named_routes[$route->name] = $route;
        }

        return $route;
    }

    /**
     * Add a GET route
     *
     * @param string $path Route path
     * @param string|callable $handler Route handler
     * @return route
     */
    public function get(string $path, $handler): route {
        return $this->add(route::get($path, $handler));
    }

    /**
     * Add a POST route
     *
     * @param string $path Route path
     * @param string|callable $handler Route handler
     * @return route
     */
    public function post(string $path, $handler): route {
        return $this->add(route::post($path, $handler));
    }

    /**
     * Add route that responds to any HTTP method
     *
     * @param string $path Route path
     * @param string|callable $handler Route handler
     * @return route
     */
    public function any(string $path, $handler): route {
        return $this->add(route::any($path, $handler));
    }

    /**
     * Add route that responds to both GET and POST
     *
     * @param string $path Route path
     * @param string|callable $handler Route handler
     * @return array Two routes (GET and POST)
     */
    public function match(string $path, $handler): array {
        return [
            $this->get($path, $handler),
            $this->post($path, $handler),
        ];
    }

    /**
     * Create a route group with shared attributes
     *
     * @param array $attributes Group attributes (prefix, middleware, component)
     * @param callable $callback Callback to define routes in group
     * @return void
     */
    public function group(array $attributes, callable $callback): void {
        // Merge with parent group if nested
        if (!empty($this->group_stack)) {
            $parent = end($this->group_stack);

            // Merge prefixes
            if (isset($attributes['prefix']) && isset($parent['prefix'])) {
                $attributes['prefix'] = rtrim($parent['prefix'], '/') . '/' . ltrim($attributes['prefix'], '/');
            } elseif (isset($parent['prefix'])) {
                $attributes['prefix'] = $parent['prefix'];
            }

            // Merge middleware
            if (isset($parent['middleware'])) {
                $parentMiddleware = (array)$parent['middleware'];
                $groupMiddleware = isset($attributes['middleware']) ? (array)$attributes['middleware'] : [];
                $attributes['middleware'] = array_merge($parentMiddleware, $groupMiddleware);
            }

            // Inherit component
            if (!isset($attributes['component']) && isset($parent['component'])) {
                $attributes['component'] = $parent['component'];
            }
        }

        $this->group_stack[] = $attributes;

        $callback($this);

        array_pop($this->group_stack);
    }

    /**
     * Get all routes
     *
     * @return array Array of route objects
     */
    public function all(): array {
        return $this->routes;
    }

    /**
     * Get a named route
     *
     * @param string $name Route name
     * @return route|null
     */
    public function get_named(string $name): ?route {
        return $this->named_routes[$name] ?? null;
    }

    /**
     * Find a route that matches the given method and path
     *
     * @param string $method HTTP method
     * @param string $path Request path
     * @param array &$params Extracted parameters (output)
     * @return route|null
     */
    public function find(string $method, string $path, array &$params = []): ?route {
        $method = strtoupper($method);

        foreach ($this->routes as $route) {
            // Check method matches (ANY matches all)
            if ($route->method !== 'ANY' && $route->method !== $method) {
                continue;
            }

            $routeParams = [];
            if ($route->matches($path, $routeParams)) {
                $params = $routeParams;
                return $route;
            }
        }

        return null;
    }

    /**
     * Get count of routes
     *
     * @return int
     */
    public function count(): int {
        return count($this->routes);
    }

    /**
     * Generate URL for a named route
     *
     * @param string $name Route name
     * @param array $params Parameters to substitute
     * @return string|null URL or null if route not found
     */
    public function url(string $name, array $params = []): ?string {
        $route = $this->get_named($name);

        if ($route === null) {
            return null;
        }

        $url = $route->path;

        // Substitute parameters
        foreach ($params as $key => $value) {
            $url = str_replace('{' . $key . '}', $value, $url);
        }

        return $url;
    }

    /**
     * Merge another collection into this one
     *
     * @param route_collection $collection Collection to merge
     * @return void
     */
    public function merge(route_collection $collection): void {
        foreach ($collection->all() as $route) {
            $this->routes[] = $route;
            if ($route->name !== null) {
                $this->named_routes[$route->name] = $route;
            }
        }
    }
}
