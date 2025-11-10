<?php

declare(strict_types=1);

/**
 * ISER Authentication System - Front Controller
 * Single entry point for all requests (PSR-7 compliant)
 *
 * @package Core
 * @author ISER Desarrollo
 * @license Propietario
 */

// Define base directory
define('BASE_DIR', dirname(__DIR__));
define('INSTALL_LOCK', BASE_DIR . '/.installed');
define('ENV_FILE', BASE_DIR . '/.env');

// Verificar si el sistema está instalado
if (!file_exists(INSTALL_LOCK)) {
    // Redirigir al instalador
    header('Location: /install.php');
    exit;
}

// Verificar que existe el archivo .env
if (!file_exists(ENV_FILE)) {
    http_response_code(500);
    die('<h1>Configuration Error</h1><p>El archivo .env no fue encontrado. Por favor, ejecute el instalador.</p>');
}

// Cargar autoloader
if (!file_exists(BASE_DIR . '/vendor/autoload.php')) {
    http_response_code(500);
    die('<h1>Dependency Error</h1><p>Composer dependencies not installed. Run: composer install</p>');
}

require_once BASE_DIR . '/vendor/autoload.php';

// Iniciar sesión
session_start();

use ISER\Core\Bootstrap;
use ISER\Core\Routing\Router;
use ISER\Core\Routing\RouteNotFoundException;
use ISER\Core\Http\Request;
use ISER\Core\Http\Response;
use ISER\Controllers\HomeController;
use ISER\Controllers\AuthController;

// Inicializar la aplicación
try {
    $app = new Bootstrap(BASE_DIR);
    $app->init();
} catch (Exception $e) {
    error_log('Bootstrap Error: ' . $e->getMessage());
    http_response_code(500);
    die('<h1>System Error</h1><p>Failed to initialize the application. Check logs for details.</p>');
}

// Crear router
$router = new Router();

// ===== RUTAS PÚBLICAS =====
$router->get('/', [HomeController::class, 'index'], 'home');
$router->get('/login', [AuthController::class, 'showLogin'], 'login');
$router->post('/login', [AuthController::class, 'processLogin'], 'login.process');
$router->get('/logout', [AuthController::class, 'logout'], 'logout');

// ===== RUTAS PROTEGIDAS =====
$router->get('/dashboard', [HomeController::class, 'dashboard'], 'dashboard');

// ===== RUTAS DE ADMINISTRACIÓN =====
$router->group('/admin', function (Router $router) {
    // Incluir archivos de admin existentes temporalmente
    $router->get('', function ($request) {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['authenticated'])) {
            return Response::redirect('/login');
        }
        ob_start();
        require BASE_DIR . '/public_html/admin.php';
        $content = ob_get_clean();
        return Response::html($content);
    }, 'admin');

    $router->get('/plugins', function ($request) {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['authenticated'])) {
            return Response::redirect('/login');
        }
        ob_start();
        require BASE_DIR . '/public_html/admin/plugins.php';
        $content = ob_get_clean();
        return Response::html($content);
    }, 'admin.plugins');

    $router->get('/settings', function ($request) {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['authenticated'])) {
            return Response::redirect('/login');
        }
        ob_start();
        require BASE_DIR . '/public_html/admin/settings.php';
        $content = ob_get_clean();
        return Response::html($content);
    }, 'admin.settings');
});

// ===== RUTAS DE REPORTES =====
$router->get('/report', function ($request) {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['authenticated'])) {
        return Response::redirect('/login');
    }
    ob_start();
    require BASE_DIR . '/public_html/report/index.php';
    $content = ob_get_clean();
    return Response::html($content);
}, 'report');

// ===== RUTAS DE THEME =====
$router->get('/theme', function ($request) {
    ob_start();
    require BASE_DIR . '/public_html/theme/index.php';
    $content = ob_get_clean();
    return Response::html($content);
}, 'theme');

// ===== RUTAS API =====
$router->group('/api', function (Router $router) {
    // API routes aquí
    $router->get('/status', function ($request) {
        return Response::json(['status' => 'ok', 'timestamp' => time()]);
    }, 'api.status');
});

// Ejecutar el router con PSR-7
try {
    $request = Request::createFromGlobals();
    $response = $router->dispatch($request);
    $response->send();
} catch (RouteNotFoundException $e) {
    // Página 404
    error_log('Route not found: ' . $e->getMessage());
    $html = '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Página no encontrada</title>
    <link rel="stylesheet" href="/assets/css/iser-theme.css">
</head>
<body>
    <div class="container" style="text-align: center; padding: 100px 20px;">
        <h1 style="color: var(--iser-red); font-size: 4rem;">404</h1>
        <h2>Página no encontrada</h2>
        <p style="color: var(--text-secondary); margin: 20px 0;">La página que buscas no existe.</p>
        <a href="/" class="btn btn-primary">Volver al inicio</a>
    </div>
</body>
</html>';
    Response::html($html, 404)->send();
} catch (Exception $e) {
    // Error 500
    error_log('Server Error: ' . $e->getMessage());
    $html = '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Error del Servidor</title>
    <link rel="stylesheet" href="/assets/css/iser-theme.css">
</head>
<body>
    <div class="container" style="text-align: center; padding: 100px 20px;">
        <h1 style="color: var(--iser-red); font-size: 4rem;">500</h1>
        <h2>Error del Servidor</h2>
        <p style="color: var(--text-secondary); margin: 20px 0;">Ocurrió un error inesperado.</p>
        <a href="/" class="btn btn-primary">Volver al inicio</a>
    </div>
</body>
</html>';
    Response::html($html, 500)->send();
}
