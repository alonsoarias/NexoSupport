<?php
/**
 * Front Controller - Single Entry Point
 *
 * Este es el ÚNICO archivo en public_html.
 * Todo el sistema es accesible a través de este punto.
 *
 * Funciones:
 * 1. Servir assets de themes (/theme/nombre/...)
 * 2. Redirigir a instalador si no está instalado
 * 3. Cargar sistema y despachar rutas
 *
 * @package NexoSupport
 */

declare(strict_types=1);

// Definir constantes
define('BASE_DIR', dirname(__DIR__));
define('NEXOSUPPORT_INTERNAL', true);

// Obtener URI solicitada
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// ============================================
// SERVICIO DE ASSETS DE THEMES
// Los themes contienen sus propios assets
// Se sirven directamente sin procesamiento
// ============================================
if (preg_match('#^/theme/([a-z0-9_]+)/(.+)$#', $uri, $matches)) {
    $themename = $matches[1];
    $resource = $matches[2];

    // Validación de seguridad: evitar path traversal
    if (strpos($resource, '..') !== false || strpos($themename, '..') !== false) {
        http_response_code(403);
        die('Forbidden');
    }

    // Construir ruta al archivo
    $filepath = BASE_DIR . '/theme/' . $themename . '/' . $resource;

    // Verificar que existe
    if (!file_exists($filepath) || !is_file($filepath)) {
        http_response_code(404);
        die('Not found');
    }

    // Determinar MIME type
    $extension = pathinfo($filepath, PATHINFO_EXTENSION);
    $mimetypes = [
        'css' => 'text/css',
        'js' => 'application/javascript',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
        'eot' => 'application/vnd.ms-fontobject',
    ];

    $mime = $mimetypes[$extension] ?? 'application/octet-stream';

    // Enviar headers
    header('Content-Type: ' . $mime);
    header('Cache-Control: public, max-age=31536000'); // 1 año
    header('Content-Length: ' . filesize($filepath));

    // Enviar archivo
    readfile($filepath);
    exit;
}

// ============================================
// SISTEMA PRINCIPAL - DETECCIÓN DE INSTALACIÓN
// ============================================
// Similar a Moodle: verifica si existe config.php (.env en nuestro caso)
// y si la BD tiene las tablas del sistema

// Cargar autoloader para usar environment_checker
// (necesario antes de cargar setup.php)
if (file_exists(BASE_DIR . '/vendor/autoload.php')) {
    require_once(BASE_DIR . '/vendor/autoload.php');
}

// Usar environment_checker para determinar estado del sistema
$envChecker = new \core\install\environment_checker();

// ¿Necesita instalación?
if ($envChecker->needs_install()) {
    // Sistema no instalado, redirigir a instalador
    if ($uri !== '/install' && !str_starts_with($uri, '/install/')) {
        header('Location: /install');
        exit;
    }

    // Cargar instalador
    require_once(BASE_DIR . '/install/index.php');
    exit;
}

// ============================================
// Sistema instalado: cargar normalmente
// ============================================

// Cargar setup del sistema
require_once(BASE_DIR . '/lib/setup.php');

// Verificar que la base de datos está accesible
if ($DB === null) {
    http_response_code(500);
    echo '<h1>Database Error</h1>';
    echo '<p>Could not connect to database. Please check your configuration.</p>';
    exit;
}

// ============================================
// ROUTING
// ============================================

use core\routing\router;

$router = new router();

// Rutas principales
$router->get('/', function() {
    require(BASE_DIR . '/dashboard.php');
});

// Login routes
$router->get('/login', function() {
    require(BASE_DIR . '/login/index.php');
});

$router->post('/login', function() {
    require(BASE_DIR . '/login/index.php');
});

// Logout
$router->get('/logout', function() {
    require(BASE_DIR . '/login/logout.php');
});

// Password management routes
$router->get('/login/change_password', function() {
    require(BASE_DIR . '/login/change_password.php');
});

$router->post('/login/change_password', function() {
    require(BASE_DIR . '/login/change_password.php');
});

$router->get('/login/forgot_password', function() {
    require(BASE_DIR . '/login/forgot_password.php');
});

$router->post('/login/forgot_password', function() {
    require(BASE_DIR . '/login/forgot_password.php');
});

$router->get('/login/confirm', function() {
    require(BASE_DIR . '/login/confirm.php');
});

// Admin routes
$router->get('/admin', function() {
    require(BASE_DIR . '/admin/index.php');
});

$router->get('/admin/upgrade.php', function() {
    require(BASE_DIR . '/admin/upgrade.php');
});

$router->post('/admin/upgrade.php', function() {
    require(BASE_DIR . '/admin/upgrade.php');
});

$router->get('/admin/users', function() {
    require(BASE_DIR . '/admin/user/index.php');
});

$router->get('/admin/roles', function() {
    require(BASE_DIR . '/admin/roles/index.php');
});

$router->get('/admin/user/edit', function() {
    require(BASE_DIR . '/admin/user/edit.php');
});

$router->post('/admin/user/edit', function() {
    require(BASE_DIR . '/admin/user/edit.php');
});

$router->get('/admin/roles/edit', function() {
    require(BASE_DIR . '/admin/roles/edit.php');
});

$router->post('/admin/roles/edit', function() {
    require(BASE_DIR . '/admin/roles/edit.php');
});

$router->get('/admin/roles/define', function() {
    require(BASE_DIR . '/admin/roles/define.php');
});

$router->post('/admin/roles/define', function() {
    require(BASE_DIR . '/admin/roles/define.php');
});

$router->get('/admin/roles/assign', function() {
    require(BASE_DIR . '/admin/roles/assign.php');
});

$router->post('/admin/roles/assign', function() {
    require(BASE_DIR . '/admin/roles/assign.php');
});

$router->get('/admin/settings', function() {
    require(BASE_DIR . '/admin/settings/index.php');
});

$router->post('/admin/settings', function() {
    require(BASE_DIR . '/admin/settings/index.php');
});

// User profile
$router->get('/user/profile', function() {
    require(BASE_DIR . '/user/profile.php');
});

// Despachar
try {
    $router->dispatch($uri, $method);
} catch (\core\routing\route_not_found_exception $e) {
    http_response_code(404);
    echo '<h1>404 Not Found</h1>';
    echo '<p>The requested page was not found.</p>';
    echo '<p><a href="/">Return to home</a></p>';
} catch (\Exception $e) {
    http_response_code(500);

    if ($CFG->debug) {
        echo '<h1>Error</h1>';
        echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    } else {
        echo '<h1>Internal Server Error</h1>';
        echo '<p>An error occurred. Please try again later.</p>';
    }
}
