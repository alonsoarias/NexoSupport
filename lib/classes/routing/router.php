<?php
namespace core\routing;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Router
 *
 * Sistema de routing para NexoSupport.
 * Supports both legacy route registration and route_collection.
 *
 * @package core\routing
 */
class router {

    /** @var array Rutas registradas (legacy) */
    private array $routes = [];

    /** @var route_collection|null Route collection */
    private ?route_collection $collection = null;

    /**
     * Constructor
     *
     * @param route_collection|null $collection Optional route collection
     */
    public function __construct(?route_collection $collection = null) {
        $this->collection = $collection;
    }

    /**
     * Registrar ruta GET (legacy API)
     *
     * @param string $path
     * @param string|callable $handler
     * @return void
     */
    public function get(string $path, string|callable $handler): void {
        $this->add_route('GET', $path, $handler);
    }

    /**
     * Registrar ruta POST (legacy API)
     *
     * @param string $path
     * @param string|callable $handler
     * @return void
     */
    public function post(string $path, string|callable $handler): void {
        $this->add_route('POST', $path, $handler);
    }

    /**
     * Registrar ruta para cualquier método (legacy API)
     *
     * @param string $method
     * @param string $path
     * @param string|callable $handler
     * @return void
     */
    private function add_route(string $method, string $path, string|callable $handler): void {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler
        ];
    }

    /**
     * Despachar petición
     *
     * @param string $uri
     * @param string $method
     * @return mixed
     */
    public function dispatch(string $uri, string $method = 'GET'): mixed {
        // Strip query strings if present
        if (strpos($uri, '?') !== false) {
            $uri = parse_url($uri, PHP_URL_PATH) ?? $uri;
        }

        // Try route collection first (new system)
        if ($this->collection !== null) {
            $params = [];
            $route = $this->collection->find($method, $uri, $params);

            if ($route !== null) {
                return $this->call_handler($route->handler, $params);
            }
        }

        // Fall back to legacy routes
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $params = [];
            if ($this->match_path($route['path'], $uri, $params)) {
                return $this->call_handler($route['handler'], $params);
            }
        }

        throw new route_not_found_exception("Route not found: $method $uri");
    }

    /**
     * Verificar si un path coincide con la ruta (legacy)
     *
     * @param string $routepath
     * @param string $uri
     * @param array $params (output)
     * @return bool
     */
    private function match_path(string $routepath, string $uri, &$params = []): bool {
        // Normalizar paths
        $routepath = rtrim($routepath, '/');
        $uri = rtrim($uri, '/');

        if ($routepath === '' || $routepath === '/') {
            $routepath = '/';
        }

        if ($uri === '') {
            $uri = '/';
        }

        // Coincidencia exacta
        if ($routepath === $uri) {
            return true;
        }

        // Patrón con parámetros {param}
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([^/]+)', $routepath);
        $pattern = '#^' . $pattern . '$#';

        if (preg_match($pattern, $uri, $matches)) {
            array_shift($matches);

            if (preg_match_all('/\{([a-zA-Z0-9_]+)\}/', $routepath, $paramnames)) {
                $params = array_combine($paramnames[1], $matches);
            }

            return true;
        }

        return false;
    }

    /**
     * Llamar al handler
     *
     * @param string|callable $handler
     * @param array $params
     * @return mixed
     */
    private function call_handler(string|callable $handler, array $params = []): mixed {
        if (is_callable($handler)) {
            return call_user_func_array($handler, $params);
        }

        // String format: "Class@method"
        if (is_string($handler) && strpos($handler, '@') !== false) {
            list($class, $method) = explode('@', $handler);

            if (!class_exists($class)) {
                throw new \coding_exception("Controller class not found: $class");
            }

            $controller = new $class();

            if (!method_exists($controller, $method)) {
                throw new \coding_exception("Controller method not found: $class@$method");
            }

            return call_user_func_array([$controller, $method], $params);
        }

        throw new \coding_exception("Invalid handler format");
    }

    /**
     * Get route collection
     *
     * @return route_collection|null
     */
    public function get_collection(): ?route_collection {
        return $this->collection;
    }

    /**
     * Set route collection
     *
     * @param route_collection $collection
     * @return void
     */
    public function set_collection(route_collection $collection): void {
        $this->collection = $collection;
    }
}
