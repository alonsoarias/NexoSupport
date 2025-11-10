<?php
/**
 * Router - Sistema de enrutamiento PSR-4
 *
 * @package ISER\Core\Routing
 */

namespace ISER\Core\Routing;

class Router
{
    private array $routes = [];
    private array $namedRoutes = [];
    private string $basePath = '';
    private ?string $currentRoute = null;

    /**
     * Constructor
     *
     * @param string $basePath Base path for the router (e.g., '/public_html')
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
    public function get(string $path, $handler, ?string $name = null): self
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
    public function post(string $path, $handler, ?string $name = null): self
    {
        return $this->addRoute('POST', $path, $handler, $name);
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
    public function addRoute(string $method, string $path, $handler, ?string $name = null): self
    {
        $path = '/' . ltrim($path, '/');
        $pattern = $this->pathToPattern($path);

        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'pattern' => $pattern,
            'handler' => $handler,
            'name' => $name
        ];

        if ($name !== null) {
            $this->namedRoutes[$name] = $path;
        }

        return $this;
    }

    /**
     * Registrar grupo de rutas con prefijo
     *
     * @param string $prefix Prefijo para el grupo
     * @param callable $callback Callback que recibe el router
     */
    public function group(string $prefix, callable $callback): void
    {
        $previousBasePath = $this->basePath;
        $this->basePath = rtrim($previousBasePath . '/' . ltrim($prefix, '/'), '/');

        $callback($this);

        $this->basePath = $previousBasePath;
    }

    /**
     * Ejecutar el router
     *
     * @return mixed Resultado del handler
     * @throws \Exception Si no se encuentra la ruta
     */
    public function dispatch()
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        // Remover query string
        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }

        // Remover base path si existe
        if ($this->basePath !== '' && strpos($uri, $this->basePath) === 0) {
            $uri = substr($uri, strlen($this->basePath));
        }

        $uri = '/' . ltrim($uri, '/');

        // Buscar ruta coincidente
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['pattern'], $uri, $matches)) {
                $this->currentRoute = $route['name'];

                // Extraer parámetros
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                return $this->executeHandler($route['handler'], $params);
            }
        }

        // No se encontró ruta
        http_response_code(404);
        throw new \Exception("Ruta no encontrada: {$method} {$uri}");
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
            throw new \Exception("Ruta con nombre '{$name}' no encontrada");
        }

        $path = $this->namedRoutes[$name];

        // Reemplazar parámetros
        foreach ($params as $key => $value) {
            $path = str_replace('{' . $key . '}', $value, $path);
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
     * Ejecutar handler
     *
     * @param callable|array $handler Handler a ejecutar
     * @param array $params Parámetros extraídos de la URL
     * @return mixed Resultado del handler
     */
    private function executeHandler($handler, array $params = [])
    {
        if (is_callable($handler)) {
            return call_user_func_array($handler, $params);
        }

        if (is_array($handler) && count($handler) === 2) {
            [$class, $method] = $handler;

            if (is_string($class)) {
                $class = new $class();
            }

            return call_user_func_array([$class, $method], $params);
        }

        throw new \Exception("Handler inválido");
    }

    /**
     * Redireccionar a URL
     *
     * @param string $url URL de destino
     * @param int $code Código HTTP (301, 302, etc.)
     */
    public static function redirect(string $url, int $code = 302): void
    {
        http_response_code($code);
        header("Location: {$url}");
        exit;
    }

    /**
     * Retornar respuesta JSON
     *
     * @param mixed $data Datos a enviar
     * @param int $code Código HTTP
     */
    public static function json($data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
