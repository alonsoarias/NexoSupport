<?php
/**
 * Test Router - Verificar que el Router extrae parámetros correctamente
 */

require_once dirname(__DIR__) . '/vendor/autoload.php';

use ISER\Core\Routing\Router;
use ISER\Core\Http\Request;
use ISER\Core\Http\Response;

echo "<h1>Test Router</h1>";

// Crear router
$router = new Router();

// Ruta de test
$router->get('/test/{id}/edit', function ($request) {
    $id = $request->getAttribute('id');

    echo "<h2>Test Results:</h2>";
    echo "<ul>";
    echo "<li>Received ID: " . var_export($id, true) . "</li>";
    echo "<li>ID Type: " . gettype($id) . "</li>";
    echo "<li>ID as int: " . (int)$id . "</li>";
    echo "</ul>";

    echo "<h3>All Request Attributes:</h3>";
    echo "<pre>";
    print_r($request->getAttributes());
    echo "</pre>";

    echo "<h3>Request URI:</h3>";
    echo "<pre>";
    echo "URI Path: " . $request->getUri()->getPath() . "\n";
    echo "Method: " . $request->getMethod() . "\n";
    echo "</pre>";

    return Response::html('Test complete');
}, 'test.edit');

try {
    // Simular request
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/test/123/edit';

    echo "<h2>Simulating Request:</h2>";
    echo "<p>URL: <code>/test/123/edit</code></p>";

    $request = Request::createFromGlobals();
    $response = $router->dispatch($request);

    echo "<hr>";
    echo "<h2>Response:</h2>";
    echo $response->getBody();

} catch (Exception $e) {
    echo "<p style='color: red;'>ERROR: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<p><a href='/admin/users'>← Volver a usuarios</a></p>";
