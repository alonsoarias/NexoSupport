<?php

/**
 * Debug Routes - Ver todas las rutas registradas en el router
 */

require_once dirname(__DIR__) . '/vendor/autoload.php';

use ISER\Core\Routing\Router;
use ISER\Core\Database\Database;
use ISER\Controllers\HomeController;
use ISER\Controllers\AuthController;
use ISER\Controllers\AdminController;

define('BASE_DIR', dirname(__DIR__));

echo "\n";
echo "================================================================\n";
echo "  DEBUG DE RUTAS - ANÁLISIS COMPLETO\n";
echo "================================================================\n\n";

// Simular base de datos mock para evitar error de conexión
$database = new class {
    public function table($name) { return $name; }
    public function getConnection() { return $this; }
    public function fetchOne() { return []; }
    public function fetchAll() { return []; }
    public function getPdo() { return $this; }
};

// Crear router y registrar rutas EXACTAMENTE como en index.php
$router = new Router();

echo "[PASO 1] Registrando rutas públicas...\n";

$router->get('/', function ($request) use ($database) {
    $controller = new HomeController($database);
    return $controller->index($request);
}, 'home');

$router->get('/login', function ($request) use ($database) {
    $controller = new AuthController($database);
    return $controller->showLogin($request);
}, 'login');

$router->post('/login', function ($request) use ($database) {
    $controller = new AuthController($database);
    return $controller->processLogin($request);
}, 'login.process');

$router->get('/logout', function ($request) use ($database) {
    $controller = new AuthController($database);
    return $controller->logout($request);
}, 'logout');

$router->get('/dashboard', function ($request) use ($database) {
    $controller = new HomeController($database);
    return $controller->dashboard($request);
}, 'dashboard');

echo "  ✓ Rutas públicas registradas\n\n";

echo "[PASO 2] Registrando grupo /admin...\n";

$router->group('/admin', function (Router $router) use ($database) {
    echo "  → Dentro del grupo /admin\n";

    // Panel principal de administración
    $router->get('', function ($request) use ($database) {
        $controller = new AdminController($database);
        return $controller->index($request);
    }, 'admin');
    echo "    ✓ Registrada: '' (vacío)\n";

    // Gestión de usuarios
    $router->get('/users', function ($request) use ($database) {
        $controller = new AdminController($database);
        return $controller->users($request);
    }, 'admin.users');
    echo "    ✓ Registrada: '/users'\n";

    // Configuración del sistema
    $router->get('/settings', function ($request) use ($database) {
        $controller = new AdminController($database);
        return $controller->settings($request);
    }, 'admin.settings');
    echo "    ✓ Registrada: '/settings'\n";

    // Reportes del sistema
    $router->get('/reports', function ($request) use ($database) {
        $controller = new AdminController($database);
        return $controller->reports($request);
    }, 'admin.reports');
    echo "    ✓ Registrada: '/reports'\n";

    // Seguridad del sistema
    $router->get('/security', function ($request) use ($database) {
        $controller = new AdminController($database);
        return $controller->security($request);
    }, 'admin.security');
    echo "    ✓ Registrada: '/security'\n";
});

echo "  ✓ Grupo /admin registrado\n\n";

echo "================================================================\n";
echo "  INSPECCIÓN DE RUTAS REGISTRADAS\n";
echo "================================================================\n\n";

// Usar reflexión para acceder a las rutas privadas
$reflection = new ReflectionClass($router);
$property = $reflection->getProperty('routes');
$property->setAccessible(true);
$routes = $property->getValue($router);

echo "Total de rutas registradas: " . count($routes) . "\n\n";

echo str_pad("MÉTODO", 8) . " | " . str_pad("PATH", 30) . " | " . str_pad("PATTERN", 40) . " | NOMBRE\n";
echo str_repeat("-", 120) . "\n";

foreach ($routes as $route) {
    echo str_pad($route['method'], 8) . " | ";
    echo str_pad($route['path'], 30) . " | ";
    echo str_pad($route['pattern'], 40) . " | ";
    echo ($route['name'] ?? 'sin nombre') . "\n";
}

echo "\n";
echo "================================================================\n";
echo "  PRUEBAS DE MATCHING\n";
echo "================================================================\n\n";

$testURIs = [
    '/admin',
    '/admin/',
    '/admin/users',
    '/admin/settings',
    '/admin/reports',
    '/admin/security',
    '/',
    '/login',
    '/dashboard',
];

foreach ($testURIs as $testURI) {
    echo "Probando URI: " . str_pad($testURI, 20);

    // Normalizar URI como lo hace dispatch()
    $uri = '/' . ltrim($testURI, '/');
    if ($uri !== '/' && str_ends_with($uri, '/')) {
        $uri = rtrim($uri, '/');
    }

    echo " (normalizado: " . str_pad($uri . ")", 15);

    $found = false;
    foreach ($routes as $route) {
        if ($route['method'] === 'GET' && preg_match($route['pattern'], $uri)) {
            echo " → MATCH con: " . $route['path'];
            if ($route['name']) {
                echo " (nombre: {$route['name']})";
            }
            $found = true;
            break;
        }
    }

    if (!$found) {
        echo " → ❌ NO MATCH";
    }

    echo "\n";
}

echo "\n";
echo "================================================================\n";
echo "  ANÁLISIS DE BASEPATH\n";
echo "================================================================\n\n";

$basePathProperty = $reflection->getProperty('basePath');
$basePathProperty->setAccessible(true);
$basePath = $basePathProperty->getValue($router);

echo "BasePath actual: '" . $basePath . "'\n";
echo "Nota: BasePath debe estar vacío después de salir del grupo\n\n";

echo "================================================================\n";
echo "  FIN DEL ANÁLISIS\n";
echo "================================================================\n\n";
