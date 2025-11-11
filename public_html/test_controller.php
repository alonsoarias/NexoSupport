<?php
/**
 * Test del PermissionController
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');

define('BASE_DIR', dirname(__DIR__));
require_once BASE_DIR . '/vendor/autoload.php';

use ISER\Core\Bootstrap;
use ISER\Controllers\PermissionController;
use ISER\Core\Http\Request;

session_start();

header('Content-Type: text/html; charset=UTF-8');

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Test Controller</title></head><body>";
echo "<h1>Test de PermissionController</h1>";

try {
    // Inicializar aplicación
    echo "<h2>1. Inicializando aplicación...</h2>";
    $app = new Bootstrap(BASE_DIR);
    $app->init();
    $database = $app->getDatabase();
    echo "<p style='color:green'>✓ OK</p>";

    // Crear controller
    echo "<h2>2. Creando PermissionController...</h2>";
    $controller = new PermissionController($database);
    echo "<p style='color:green'>✓ OK</p>";

    // Crear request
    echo "<h2>3. Creando Request...</h2>";
    $request = Request::createFromGlobals();
    echo "<p style='color:green'>✓ OK</p>";

    // Ejecutar index()
    echo "<h2>4. Ejecutando controller->index()...</h2>";
    $response = $controller->index($request);
    echo "<p style='color:green'>✓ OK - Response creado</p>";

    // Obtener body
    echo "<h2>5. Obteniendo HTML del response...</h2>";
    $html = (string)$response->getBody();
    $htmlLength = strlen($html);
    echo "<p style='color:green'>✓ OK - HTML length: $htmlLength bytes</p>";

    // Analizar contenido
    echo "<h2>6. Analizando contenido HTML...</h2>";

    $checks = [
        'Gestión de Permisos' => strpos($html, 'Gestión de Permisos') !== false,
        'permissions_grouped' => strpos($html, 'permissions_grouped') !== false,
        'Tabla de permisos' => strpos($html, 'table') !== false || strpos($html, 'TABLE') !== false,
        'Bootstrap Icons' => strpos($html, 'bi bi-') !== false,
        'audit' => strpos($html, 'audit') !== false,
        'users' => strpos($html, 'users') !== false,
        'roles' => strpos($html, 'roles') !== false,
    ];

    echo "<table border='1' cellpadding='10' style='border-collapse:collapse'>";
    echo "<tr><th>Check</th><th>Resultado</th></tr>";
    foreach ($checks as $name => $result) {
        $status = $result ? "<span style='color:green'>✓ Encontrado</span>" : "<span style='color:red'>✗ NO encontrado</span>";
        echo "<tr><td>$name</td><td>$status</td></tr>";
    }
    echo "</table>";

    // Mostrar fragmento del HTML
    echo "<h2>7. Fragmento del HTML (primeros 1000 caracteres):</h2>";
    echo "<pre style='background:#f5f5f5;padding:15px;overflow:auto;max-height:300px'>";
    echo htmlspecialchars(substr($html, 0, 1000));
    echo "\n...\n</pre>";

    // Buscar y mostrar módulos en el HTML
    echo "<h2>8. Búsqueda de módulos en el HTML:</h2>";
    $modules = ['audit', 'dashboard', 'logs', 'permissions', 'reports', 'roles', 'sessions', 'settings', 'users'];
    echo "<ul>";
    foreach ($modules as $module) {
        $count = substr_count(strtolower($html), $module);
        echo "<li><strong>$module:</strong> aparece $count veces</li>";
    }
    echo "</ul>";

    // Renderizar el HTML completo
    echo "<h2>9. HTML Completo Renderizado:</h2>";
    echo "<div style='border:2px solid #000; padding:20px; background:white'>";
    echo "<h3>Inicio del contenido:</h3>";
    echo $html;
    echo "</div>";

} catch (Exception $e) {
    echo "<h2 style='color:red'>❌ ERROR FATAL</h2>";
    echo "<p><strong>Mensaje:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Archivo:</strong> " . htmlspecialchars($e->getFile()) . ":" . $e->getLine() . "</p>";
    echo "<h3>Stack trace:</h3>";
    echo "<pre style='background:#f5f5f5;padding:15px'>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "</body></html>";
