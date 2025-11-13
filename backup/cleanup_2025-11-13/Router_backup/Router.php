<?php

/**
 * ISER Authentication System - Router
 *
 * Main routing system for the application.
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

use ISER\Core\Utils\Logger;
use RuntimeException;

/**
 * Router Class
 *
 * Handles HTTP routing similar to Moodle's routing system.
 */
class Router
{
    /**
     * Registered routes
     */
    private array $routes = [];

    /**
     * Named routes
     */
    private array $namedRoutes = [];

    /**
     * Current matched route
     */
    private ?Route $currentRoute = null;

    /**
     * Base path for routes
     */
    private string $basePath = '';

    /**
     * Global middleware
     */
    private array $globalMiddleware = [];

    /**
     * 404 handler
     */
    private mixed $notFoundHandler = null;

    /**
     * Error handler
     */
    private mixed $errorHandler = null;

    /**
     * Constructor
     *
     * @param string $basePath Base path for routes
     */
    public function __construct(string $basePath = '')
    {
        $this->basePath = rtrim($basePath, '/');
    }

    /**
     * Add a GET route
     *
     * @param string $path Route path
     * @param mixed $handler Route handler
     * @param string|null $name Route name
     * @return Route Route object
     */
    public function get(string $path, mixed $handler, ?string $name = null): Route
    {
        return $this->addRoute($path, $handler, ['GET'], $name);
    }

    /**
     * Add a POST route
     *
     * @param string $path Route path
     * @param mixed $handler Route handler
     * @param string|null $name Route name
     * @return Route Route object
     */
    public function post(string $path, mixed $handler, ?string $name = null): Route
    {
        return $this->addRoute($path, $handler, ['POST'], $name);
    }

    /**
     * Add a PUT route
     *
     * @param string $path Route path
     * @param mixed $handler Route handler
     * @param string|null $name Route name
     * @return Route Route object
     */
    public function put(string $path, mixed $handler, ?string $name = null): Route
    {
        return $this->addRoute($path, $handler, ['PUT'], $name);
    }

    /**
     * Add a DELETE route
     *
     * @param string $path Route path
     * @param mixed $handler Route handler
     * @param string|null $name Route name
     * @return Route Route object
     */
    public function delete(string $path, mixed $handler, ?string $name = null): Route
    {
        return $this->addRoute($path, $handler, ['DELETE'], $name);
    }

    /**
     * Add a route for any HTTP method
     *
     * @param string $path Route path
     * @param mixed $handler Route handler
     * @param string|null $name Route name
     * @return Route Route object
     */
    public function any(string $path, mixed $handler, ?string $name = null): Route
    {
        return $this->addRoute($path, $handler, ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], $name);
    }

    /**
     * Add a route
     *
     * @param string $path Route path
     * @param mixed $handler Route handler
     * @param array $methods HTTP methods
     * @param string|null $name Route name
     * @return Route Route object
     */
    public function addRoute(string $path, mixed $handler, array $methods, ?string $name = null): Route
    {
        $fullPath = $this->basePath . $path;
        $route = new Route($fullPath, $handler, $methods, $name);

        $this->routes[] = $route;

        if ($name !== null) {
            $this->namedRoutes[$name] = $route;
        }

        return $route;
    }

    /**
     * Add global middleware
     *
     * @param string|callable $middleware Middleware
     * @return self
     */
    public function middleware(string|callable $middleware): self
    {
        $this->globalMiddleware[] = $middleware;
        return $this;
    }

    /**
     * Set 404 not found handler
     *
     * @param callable $handler Handler
     * @return self
     */
    public function setNotFoundHandler(callable $handler): self
    {
        $this->notFoundHandler = $handler;
        return $this;
    }

    /**
     * Set error handler
     *
     * @param callable $handler Handler
     * @return self
     */
    public function setErrorHandler(callable $handler): self
    {
        $this->errorHandler = $handler;
        return $this;
    }

    /**
     * Dispatch the router
     *
     * @param string|null $path Request path
     * @param string|null $method HTTP method
     * @return mixed Handler result
     */
    public function dispatch(?string $path = null, ?string $method = null): mixed
    {
        $path = $path ?? $this->getCurrentPath();
        $method = $method ?? $this->getCurrentMethod();

        Logger::info('Routing request', [
            'path' => $path,
            'method' => $method,
        ]);

        try {
            // Find matching route
            $route = $this->match($path, $method);

            if ($route === null) {
                return $this->handleNotFound($path);
            }

            $this->currentRoute = $route;

            // Execute route handler
            return $this->executeRoute($route);

        } catch (\Throwable $e) {
            return $this->handleError($e);
        }
    }

    /**
     * Find matching route
     *
     * @param string $path Request path
     * @param string $method HTTP method
     * @return Route|null Matched route or null
     */
    public function match(string $path, string $method): ?Route
    {
        foreach ($this->routes as $route) {
            if ($route->matches($path, $method)) {
                return $route;
            }
        }

        return null;
    }

    /**
     * Execute route handler
     *
     * @param Route $route Route to execute
     * @return mixed Handler result
     */
    private function executeRoute(Route $route): mixed
    {
        $handler = $route->getHandler();
        $params = $route->getParameters();

        // Execute middleware
        $middleware = array_merge($this->globalMiddleware, $route->getMiddleware());
        foreach ($middleware as $mw) {
            if (is_callable($mw)) {
                $result = $mw($route);
                if ($result !== null) {
                    return $result;
                }
            }
        }

        // Execute handler
        if (is_callable($handler)) {
            return call_user_func_array($handler, $params);
        }

        if (is_string($handler)) {
            return $this->executeStringHandler($handler, $params);
        }

        throw new RuntimeException('Invalid route handler');
    }

    /**
     * Execute string handler (Class@method or Class::method)
     *
     * @param string $handler Handler string
     * @param array $params Parameters
     * @return mixed Handler result
     */
    private function executeStringHandler(string $handler, array $params): mixed
    {
        // Support Class@method format
        if (str_contains($handler, '@')) {
            [$class, $method] = explode('@', $handler, 2);
            $instance = new $class();
            return call_user_func_array([$instance, $method], $params);
        }

        // Support Class::method format
        if (str_contains($handler, '::')) {
            [$class, $method] = explode('::', $handler, 2);
            return call_user_func_array([$class, $method], $params);
        }

        throw new RuntimeException('Invalid handler format: ' . $handler);
    }

    /**
     * Handle 404 Not Found
     *
     * @param string $path Request path
     * @return mixed Handler result
     */
    private function handleNotFound(string $path): mixed
    {
        http_response_code(404);

        Logger::warning('Route not found', ['path' => $path]);

        if ($this->notFoundHandler !== null) {
            return call_user_func($this->notFoundHandler, $path);
        }

        return $this->defaultNotFoundHandler($path);
    }

    /**
     * Default 404 handler
     *
     * @param string $path Request path
     * @return string 404 message
     */
    private function defaultNotFoundHandler(string $path): string
    {
        return "404 Not Found: {$path}";
    }

    /**
     * Handle errors
     *
     * @param \Throwable $exception Exception
     * @return mixed Handler result
     */
    private function handleError(\Throwable $exception): mixed
    {
        http_response_code(500);

        Logger::exception($exception);

        if ($this->errorHandler !== null) {
            return call_user_func($this->errorHandler, $exception);
        }

        return $this->defaultErrorHandler($exception);
    }

    /**
     * Default error handler
     *
     * @param \Throwable $exception Exception
     * @return string Error message
     */
    private function defaultErrorHandler(\Throwable $exception): string
    {
        return "500 Internal Server Error: " . $exception->getMessage();
    }

    /**
     * Get current request path
     *
     * @return string Request path
     */
    private function getCurrentPath(): string
    {
        $path = $_SERVER['REQUEST_URI'] ?? '/';

        // Remove query string
        if (($pos = strpos($path, '?')) !== false) {
            $path = substr($path, 0, $pos);
        }

        // Remove base path
        if ($this->basePath !== '' && str_starts_with($path, $this->basePath)) {
            $path = substr($path, strlen($this->basePath));
        }

        return $path ?: '/';
    }

    /**
     * Get current HTTP method
     *
     * @return string HTTP method
     */
    private function getCurrentMethod(): string
    {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    /**
     * Generate URL for named route
     *
     * @param string $name Route name
     * @param array $params Parameters
     * @return string|null Generated URL or null if not found
     */
    public function url(string $name, array $params = []): ?string
    {
        if (!isset($this->namedRoutes[$name])) {
            return null;
        }

        return $this->namedRoutes[$name]->url($params);
    }

    /**
     * Get all routes
     *
     * @return array Routes array
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Get named routes
     *
     * @return array Named routes
     */
    public function getNamedRoutes(): array
    {
        return $this->namedRoutes;
    }

    /**
     * Get current route
     *
     * @return Route|null Current route
     */
    public function getCurrentRoute(): ?Route
    {
        return $this->currentRoute;
    }

    /**
     * Clear all routes
     *
     * @return self
     */
    public function clearRoutes(): self
    {
        $this->routes = [];
        $this->namedRoutes = [];
        $this->currentRoute = null;
        return $this;
    }
}
