<?php
/**
 * Front Controller - Single Entry Point
 *
 * Este es el UNICO archivo en public_html.
 * Todo el sistema es accesible a traves de este punto.
 *
 * Funciones:
 * 1. Servir assets de themes (/theme/nombre/...)
 * 2. Redirigir a instalador si no esta instalado
 * 3. Cargar sistema y despachar rutas dinamicamente
 *
 * Las rutas ya NO estan hardcodeadas en este archivo.
 * Se cargan desde lib/routing/routes.php y desde los plugins.
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

    // Validacion de seguridad: evitar path traversal
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
// SISTEMA PRINCIPAL - DETECCION DE INSTALACION
// ============================================

// Cargar autoloader para usar environment_checker
if (file_exists(BASE_DIR . '/vendor/autoload.php')) {
    require_once(BASE_DIR . '/vendor/autoload.php');
}

// Define MATURITY constants needed by lib/version.php
if (!defined('MATURITY_ALPHA')) {
    define('MATURITY_ALPHA', 50);
    define('MATURITY_BETA', 100);
    define('MATURITY_RC', 150);
    define('MATURITY_STABLE', 200);
}

// Usar environment_checker para determinar estado del sistema
$envChecker = new \core\install\environment_checker();

// ¿Necesita instalacion?
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

// Acceder a variables globales inicializadas en setup.php
global $DB, $CFG, $USER, $PAGE, $OUTPUT;

// Verificar que la base de datos esta accesible
if ($DB === null) {
    http_response_code(500);
    echo '<h1>Database Error</h1>';
    echo '<p>Could not connect to database. Please check your configuration.</p>';
    exit;
}

// ============================================
// VERIFICAR MODO MANTENIMIENTO (Patron Moodle)
// ============================================
if (file_exists(BASE_DIR . '/lib/maintenancelib.php')) {
    require_once(BASE_DIR . '/lib/maintenancelib.php');
    check_maintenance_mode($uri);
}

// ============================================
// VERIFICAR SI NECESITA ACTUALIZACION (Patron Moodle)
// ============================================
if ($envChecker->needs_upgrade()) {
    // Lista de URIs permitidas durante upgrade
    $allowed_during_upgrade = [
        '/admin/upgrade.php',
        '/admin/upgrade',
        '/login',
        '/logout',
    ];

    $is_allowed = false;
    foreach ($allowed_during_upgrade as $allowed_uri) {
        if ($uri === $allowed_uri || str_starts_with($uri, $allowed_uri)) {
            $is_allowed = true;
            break;
        }
    }

    if (!$is_allowed) {
        $is_logged_in = isset($USER->id) && $USER->id > 0;

        if ($is_logged_in) {
            header('Location: /admin/upgrade.php');
            exit;
        } else {
            $return = urlencode('/admin/upgrade.php');
            header("Location: /login?returnurl={$return}");
            exit;
        }
    }
}

// ============================================
// ROUTING - DYNAMIC SYSTEM
// ============================================
// Las rutas se cargan dinamicamente desde:
// 1. lib/routing/routes.php (rutas core)
// 2. Plugins que implementan register_routes()
// ============================================

use core\routing\router;
use core\routing\route_manager;

// Cargar todas las rutas del core y plugins
$route_collection = route_manager::load_all_routes();

// Crear router con la coleccion de rutas
$router = new router($route_collection);

// Despachar la peticion
try {
    $router->dispatch($uri, $method);
} catch (\core\routing\route_not_found_exception $e) {
    http_response_code(404);
    echo '<h1>404 Not Found</h1>';
    echo '<p>The requested page was not found.</p>';
    echo '<p><a href="/">Return to home</a></p>';
} catch (\Exception $e) {
    http_response_code(500);

    if (isset($CFG) && $CFG->debug) {
        echo '<h1>Error</h1>';
        echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    } else {
        echo '<h1>Internal Server Error</h1>';
        echo '<p>An error occurred. Please try again later.</p>';
    }
}
