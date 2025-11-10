<?php

declare(strict_types=1);

namespace ISER\Core\Routing;

use ISER\Core\Http\Request;
use ISER\Core\Http\Response;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Router - Sistema de enrutamiento PSR-7
 *
 * Router que implementa PSR-7 HTTP Message Interface
 * Cumple con PSR-1, PSR-4 y PSR-12
 *
 * @package ISER\Core\Routing
 */
class Router
{
    private array $routes = [];
    private array $namedRoutes = [];
    private string $basePath = '';
    private ?string $currentRoute = null;

    /**
     * Constructor
     *
     * @param string $basePath Base path for the router
     */
    public function __construct(string $basePath = '')
    {
        $this->basePath = rtrim($basePath, '/');
    }

    /**
     * Registrar ruta GET
     *
     * @param string $path Ruta (e.g., '/admin/users')
     * @param callable|array $handler Handler (función o ['Class', 'method'])
     * @param string|null $name Nombre opcional de la ruta
     * @return self
     */
    public function get(string $path, callable|array $handler, ?string $name = null): self
    {
        return $this->addRoute('GET', $path, $handler, $name);
    }

    /**
     * Registrar ruta POST
     *
     * @param string $path Ruta
     * @param callable|array $handler Handler
     * @param string|null $name Nombre opcional
     * @return self
     */
    public function post(string $path, callable|array $handler, ?string $name = null): self
    {
        return $this->addRoute('POST', $path, $handler, $name);
    }

    /**
     * Registrar ruta PUT
     *
     * @param string $path Ruta
     * @param callable|array $handler Handler
     * @param string|null $name Nombre opcional
     * @return self
     */
    public function put(string $path, callable|array $handler, ?string $name = null): self
    {
        return $this->addRoute('PUT', $path, $handler, $name);
    }

    /**
     * Registrar ruta DELETE
     *
     * @param string $path Ruta
     * @param callable|array $handler Handler
     * @param string|null $name Nombre opcional
     * @return self
     */
    public function delete(string $path, callable|array $handler, ?string $name = null): self
    {
        return $this->addRoute('DELETE', $path, $handler, $name);
    }

    /**
     * Registrar ruta PATCH
     *
     * @param string $path Ruta
     * @param callable|array $handler Handler
     * @param string|null $name Nombre opcional
     * @return self
     */
    public function patch(string $path, callable|array $handler, ?string $name = null): self
    {
        return $this->addRoute('PATCH', $path, $handler, $name);
    }

    /**
     * Registrar ruta para cualquier método
     *
     * @param string $method Método HTTP (GET, POST, PUT, DELETE, etc.)
     * @param string $path Ruta
     * @param callable|array $handler Handler
     * @param string|null $name Nombre opcional
     * @return self
     */
    public function addRoute(string $method, string $path, callable|array $handler, ?string $name = null): self
    {
        // Normalizar path: eliminar slashes al inicio y fin
        $path = trim($path, '/');

        // Construir fullPath correctamente
        if ($this->basePath !== '' && $path !== '') {
            // Grupo con path: /admin + users = /admin/users
            $fullPath = $this->basePath . '/' . $path;
        } elseif ($this->basePath !== '') {
            // Grupo sin path: /admin + '' = /admin
            $fullPath = $this->basePath;
        } elseif ($path !== '') {
            // Sin grupo con path: '' + login = /login
            $fullPath = '/' . $path;
        } else {
            // Sin grupo sin path: '' + '' = /
            $fullPath = '/';
        }

        // Crear patrón regex
        $pattern = $this->pathToPattern($fullPath);

        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $fullPath,
            'pattern' => $pattern,
            'handler' => $handler,
            'name' => $name,
        ];

        if ($name !== null) {
            $this->namedRoutes[$name] = $fullPath;
        }

        return $this;
    }

    /**
     * Registrar grupo de rutas con prefijo
     *
     * @param string $prefix Prefijo para el grupo
     * @param callable $callback Callback que recibe el router
     * @return void
     */
    public function group(string $prefix, callable $callback): void
    {
        $previousBasePath = $this->basePath;
        $this->basePath = rtrim($previousBasePath . '/' . ltrim($prefix, '/'), '/');

        $callback($this);

        $this->basePath = $previousBasePath;
    }

    /**
     * Ejecutar el router con PSR-7
     *
     * @param ServerRequestInterface|null $request Request PSR-7 (null para crear desde globales)
     * @return ResponseInterface Response PSR-7
     * @throws \Exception Si no se encuentra la ruta
     */
    public function dispatch(?ServerRequestInterface $request = null): ResponseInterface
    {
        if ($request === null) {
            $request = Request::createFromGlobals();
        }

        $method = $request->getMethod();
        $uri = $request->getUri()->getPath();

        // Remover base path si existe
        if ($this->basePath !== '' && str_starts_with($uri, $this->basePath)) {
            $uri = substr($uri, strlen($this->basePath));
        }

        // Normalizar URI: remover trailing slash excepto para raíz
        $uri = '/' . ltrim($uri, '/');
        if ($uri !== '/' && str_ends_with($uri, '/')) {
            $uri = rtrim($uri, '/');
        }

        // Buscar ruta coincidente
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['pattern'], $uri, $matches)) {
                $this->currentRoute = $route['name'];

                // Extraer parámetros
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                // Agregar parámetros al request
                foreach ($params as $key => $value) {
                    $request = $request->withAttribute($key, $value);
                }

                // Ejecutar handler
                return $this->executeHandler($route['handler'], $request, $params);
            }
        }

        // No se encontró ruta
        throw new RouteNotFoundException("Route not found: {$method} {$uri}");
    }

    /**
     * Obtener URL por nombre de ruta
     *
     * @param string $name Nombre de la ruta
     * @param array $params Parámetros para reemplazar
     * @return string URL generada
     * @throws \Exception Si la ruta no existe
     */
    public function url(string $name, array $params = []): string
    {
        if (!isset($this->namedRoutes[$name])) {
            throw new \Exception("Route with name '{$name}' not found");
        }

        $path = $this->namedRoutes[$name];

        // Reemplazar parámetros
        foreach ($params as $key => $value) {
            $path = str_replace('{' . $key . '}', (string)$value, $path);
        }

        return $this->basePath . $path;
    }

    /**
     * Obtener ruta actual
     *
     * @return string|null Nombre de la ruta actual
     */
    public function currentRoute(): ?string
    {
        return $this->currentRoute;
    }

    /**
     * Convertir path a patrón regex
     *
     * @param string $path Path con parámetros (e.g., '/user/{id}')
     * @return string Patrón regex
     */
    private function pathToPattern(string $path): string
    {
        // Escapar slashes
        $pattern = str_replace('/', '\/', $path);

        // Convertir {param} a named groups
        $pattern = preg_replace('/\{(\w+)\}/', '(?P<$1>[^\/]+)', $pattern);

        return '/^' . $pattern . '$/';
    }

    /**
     * Ejecutar handler con PSR-7
     *
     * @param callable|array $handler Handler a ejecutar
     * @param ServerRequestInterface $request Request PSR-7
     * @param array $params Parámetros extraídos de la URL
     * @return ResponseInterface Response PSR-7
     * @throws \Exception Si el handler es inválido
     */
    private function executeHandler(
        callable|array $handler,
        ServerRequestInterface $request,
        array $params = []
    ): ResponseInterface {
        $result = null;

        if (is_callable($handler)) {
            $result = $handler($request, ...$params);
        } elseif (is_array($handler) && count($handler) === 2) {
            [$class, $method] = $handler;

            if (is_string($class)) {
                $class = new $class();
            }

            if (!method_exists($class, $method)) {
                throw new \Exception("Method {$method} does not exist in controller");
            }

            $result = $class->$method($request, ...$params);
        } else {
            throw new \Exception("Invalid handler");
        }

        // Si el resultado es una ResponseInterface, devolverlo
        if ($result instanceof ResponseInterface) {
            return $result;
        }

        // Si es un string, convertir a Response HTML
        if (is_string($result)) {
            return Response::html($result);
        }

        // Si es un array u objeto, convertir a JSON
        if (is_array($result) || is_object($result)) {
            return Response::json($result);
        }

        // Si es null o void, devolver respuesta vacía
        if ($result === null) {
            return new Response();
        }

        // Último recurso: convertir a string
        return Response::html((string)$result);
    }

    /**
     * Redireccionar a URL
     *
     * @param string $url URL de destino
     * @param int $code Código HTTP (301, 302, etc.)
     * @return ResponseInterface
     */
    public static function redirect(string $url, int $code = 302): ResponseInterface
    {
        return Response::redirect($url, $code);
    }

    /**
     * Retornar respuesta JSON
     *
     * @param mixed $data Datos a enviar
     * @param int $code Código HTTP
     * @return ResponseInterface
     */
    public static function json(mixed $data, int $code = 200): ResponseInterface
    {
        return Response::json($data, $code);
    }
}
