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
define('ENV_FILE', BASE_DIR . '/.env');

/**
 * Verificación de instalación (al estilo Moodle/WordPress)
 *
 * Sistema de 3 niveles:
 * 1. Verificar que existe .env
 * 2. Verificar que .env contiene INSTALLED=true
 * 3. Verificar que la BD está accesible
 */
function checkInstallation(): bool {
    // Nivel 1: Verificar que existe .env
    if (!file_exists(ENV_FILE)) {
        return false;
    }

    // Nivel 2: Verificar que .env contiene INSTALLED=true
    $envContent = file_get_contents(ENV_FILE);
    if ($envContent === false) {
        return false;
    }

    // Parsear .env y buscar INSTALLED=true
    $lines = explode("\n", $envContent);
    $installed = false;
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || $line[0] === '#') {
            continue;
        }
        if (strpos($line, 'INSTALLED=') === 0) {
            $value = trim(str_replace('INSTALLED=', '', $line));
            $installed = ($value === 'true');
            break;
        }
    }

    if (!$installed) {
        return false;
    }

    // Nivel 3: Verificar acceso a BD (básico)
    // No verificamos aquí la BD para no ralentizar cada request
    // Si .env existe y tiene INSTALLED=true, asumimos instalación completa

    return true;
}

// Verificar instalación
if (!checkInstallation()) {
    // No instalado: redirigir al instalador wrapper
    // El instalador está en public_html/install.php (accesible vía web)
    header('Location: /install.php');
    exit;
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
use ISER\Controllers\AdminController;
use ISER\Controllers\UserManagementController;
use ISER\Controllers\RoleController;
use ISER\Controllers\PermissionController;

// Inicializar la aplicación
try {
    $app = new Bootstrap(BASE_DIR);
    $app->init();
} catch (Exception $e) {
    error_log('Bootstrap Error: ' . $e->getMessage());
    error_log('Bootstrap Stack Trace: ' . $e->getTraceAsString());
    http_response_code(500);

    // Mostrar error detallado temporalmente para debugging
    die('<h1>System Error</h1><p>Failed to initialize the application.</p><pre style="text-align:left;background:#f5f5f5;padding:15px;margin:20px;border:1px solid #ddd;overflow:auto;max-width:800px;margin-left:auto;margin-right:auto;">'
        . '<strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . "\n\n"
        . '<strong>File:</strong> ' . htmlspecialchars($e->getFile()) . ':' . $e->getLine() . "\n\n"
        . '<strong>Stack Trace:</strong>' . "\n" . htmlspecialchars($e->getTraceAsString())
        . '</pre>');
}

// Obtener instancia de Database para inyección de dependencias
$database = $app->getDatabase();

// Crear router
$router = new Router();

// ===== RUTAS PÚBLICAS =====
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

// ===== RUTAS PROTEGIDAS =====
$router->get('/dashboard', function ($request) use ($database) {
    $controller = new HomeController($database);
    return $controller->dashboard($request);
}, 'dashboard');

// ===== RUTAS DE ADMINISTRACIÓN =====
$router->group('/admin', function (Router $router) use ($database) {
    // Panel principal de administración
    $router->get('', function ($request) use ($database) {
        $controller = new AdminController($database);
        return $controller->index($request);
    }, 'admin');

    // Configuración del sistema
    $router->get('/settings', function ($request) use ($database) {
        $controller = new AdminController($database);
        return $controller->settings($request);
    }, 'admin.settings');

    // Reportes del sistema
    $router->get('/reports', function ($request) use ($database) {
        $controller = new AdminController($database);
        return $controller->reports($request);
    }, 'admin.reports');

    // Seguridad del sistema
    $router->get('/security', function ($request) use ($database) {
        $controller = new AdminController($database);
        return $controller->security($request);
    }, 'admin.security');

    // ===== GESTIÓN DE USUARIOS =====
    // Lista de usuarios
    $router->get('/users', function ($request) use ($database) {
        $controller = new UserManagementController($database);
        return $controller->index($request);
    }, 'admin.users.index');

    // Formulario de creación
    $router->get('/users/create', function ($request) use ($database) {
        $controller = new UserManagementController($database);
        return $controller->create($request);
    }, 'admin.users.create');

    // Procesar creación
    $router->post('/users/store', function ($request) use ($database) {
        $controller = new UserManagementController($database);
        return $controller->store($request);
    }, 'admin.users.store');

    // Formulario de edición
    $router->get('/users/{id}/edit', function ($request) use ($database) {
        $controller = new UserManagementController($database);
        return $controller->edit($request);
    }, 'admin.users.edit');

    // Procesar actualización
    $router->post('/users/{id}/update', function ($request) use ($database) {
        $controller = new UserManagementController($database);
        return $controller->update($request);
    }, 'admin.users.update');

    // Eliminar usuario (soft delete)
    $router->delete('/users/{id}/delete', function ($request) use ($database) {
        $controller = new UserManagementController($database);
        return $controller->delete($request);
    }, 'admin.users.delete');

    // Restaurar usuario eliminado
    $router->post('/users/{id}/restore', function ($request) use ($database) {
        $controller = new UserManagementController($database);
        return $controller->restore($request);
    }, 'admin.users.restore');

    // ===== GESTIÓN DE ROLES =====
    // Lista de roles
    $router->get('/roles', function ($request) use ($database) {
        $controller = new RoleController($database);
        return $controller->index($request);
    }, 'admin.roles.index');

    // Formulario de creación
    $router->get('/roles/create', function ($request) use ($database) {
        $controller = new RoleController($database);
        return $controller->create($request);
    }, 'admin.roles.create');

    // Procesar creación
    $router->post('/roles/store', function ($request) use ($database) {
        $controller = new RoleController($database);
        return $controller->store($request);
    }, 'admin.roles.store');

    // Formulario de edición
    $router->get('/roles/{id}/edit', function ($request) use ($database) {
        $controller = new RoleController($database);
        return $controller->edit($request);
    }, 'admin.roles.edit');

    // Procesar actualización
    $router->post('/roles/{id}/update', function ($request) use ($database) {
        $controller = new RoleController($database);
        return $controller->update($request);
    }, 'admin.roles.update');

    // Eliminar rol
    $router->delete('/roles/{id}/delete', function ($request) use ($database) {
        $controller = new RoleController($database);
        return $controller->delete($request);
    }, 'admin.roles.delete');

    // ===== GESTIÓN DE PERMISOS =====
    // Lista de permisos
    $router->get('/permissions', function ($request) use ($database) {
        $controller = new PermissionController($database);
        return $controller->index($request);
    }, 'admin.permissions.index');

    // Formulario de creación
    $router->get('/permissions/create', function ($request) use ($database) {
        $controller = new PermissionController($database);
        return $controller->create($request);
    }, 'admin.permissions.create');

    // Procesar creación
    $router->post('/permissions/store', function ($request) use ($database) {
        $controller = new PermissionController($database);
        return $controller->store($request);
    }, 'admin.permissions.store');

    // Formulario de edición
    $router->get('/permissions/{id}/edit', function ($request) use ($database) {
        $controller = new PermissionController($database);
        return $controller->edit($request);
    }, 'admin.permissions.edit');

    // Procesar actualización
    $router->post('/permissions/{id}/update', function ($request) use ($database) {
        $controller = new PermissionController($database);
        return $controller->update($request);
    }, 'admin.permissions.update');

    // Eliminar permiso
    $router->delete('/permissions/{id}/delete', function ($request) use ($database) {
        $controller = new PermissionController($database);
        return $controller->delete($request);
    }, 'admin.permissions.delete');
});

// ===== RUTAS DE REPORTES =====
$router->get('/report', function ($request) {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['authenticated'])) {
        return Response::redirect('/login');
    }
    ob_start();
    require BASE_DIR . '/app/Report/index.php';
    $content = ob_get_clean();
    return Response::html($content);
}, 'report');

// ===== RUTAS DE THEME =====
$router->get('/theme', function ($request) {
    ob_start();
    require BASE_DIR . '/app/Theme/index.php';
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
    error_log('Server Error Stack Trace: ' . $e->getTraceAsString());

    // Mostrar error detallado temporalmente para debugging
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
        <div style="text-align: left; background: #f5f5f5; padding: 15px; margin: 20px auto; border: 1px solid #ddd; max-width: 800px; overflow: auto;">
            <strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '<br><br>
            <strong>File:</strong> ' . htmlspecialchars($e->getFile()) . ':' . $e->getLine() . '<br><br>
            <strong>Stack Trace:</strong><br><pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>
        </div>
        <a href="/" class="btn btn-primary">Volver al inicio</a>
    </div>
</body>
</html>';
    Response::html($html, 500)->send();
}
