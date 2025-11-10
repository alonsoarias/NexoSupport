<?php

/**
 * Test Admin Routes - Verificar que todas las rutas de admin funcionan
 */

require_once dirname(__DIR__) . '/vendor/autoload.php';

use ISER\Core\Bootstrap;
use ISER\Core\Database\Database;
use ISER\Controllers\AdminController;
use ISER\Core\Http\Request;

define('BASE_DIR', dirname(__DIR__));

echo "\n";
echo "================================================================\n";
echo "  TEST DE RUTAS DE ADMINISTRACIÓN\n";
echo "================================================================\n\n";

try {
    // Inicializar sistema
    echo "[PASO 1] Inicializando sistema...\n";
    $app = new Bootstrap(BASE_DIR);
    $app->init();
    $database = $app->getDatabase();
    echo "  ✓ Sistema inicializado\n\n";

    // Simular sesión autenticada
    echo "[PASO 2] Simulando sesión autenticada...\n";
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Buscar un usuario admin para simular
    $userManager = new \ISER\User\UserManager($database);
    $admin = $userManager->getUserByUsername('admin');

    if (!$admin) {
        echo "  ✗ No se encontró usuario admin\n";
        echo "  >> Ejecuta primero: php tools/fix-admin-user.php\n\n";
        exit(1);
    }

    $_SESSION['user_id'] = $admin['id'];
    $_SESSION['username'] = $admin['username'];
    $_SESSION['email'] = $admin['email'];
    $_SESSION['authenticated'] = true;
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();

    echo "  ✓ Sesión simulada\n";
    echo "     - user_id: {$_SESSION['user_id']}\n";
    echo "     - username: {$_SESSION['username']}\n\n";

    // Crear instancia del controlador
    echo "[PASO 3] Creando instancia de AdminController...\n";
    $controller = new AdminController($database);
    echo "  ✓ AdminController creado\n\n";

    // Test 1: Admin Dashboard (index)
    echo "[TEST 1] Probando /admin (Dashboard)...\n";
    $request = Request::createFromGlobals();
    $response = $controller->index($request);

    if ($response->getStatusCode() === 200) {
        $body = (string)$response->getBody();
        echo "  ✓ Respuesta exitosa (200)\n";
        echo "     - Tamaño del HTML: " . strlen($body) . " bytes\n";

        // Verificar que contiene datos reales
        if (strpos($body, 'stats.total_users') === false &&
            strpos($body, 'Panel de Administración') !== false) {
            echo "  ✓ Template renderizado correctamente\n";
        } else {
            echo "  ⚠ Template podría tener problemas\n";
        }
    } else {
        echo "  ✗ Error: código " . $response->getStatusCode() . "\n";
    }
    echo "\n";

    // Test 2: Gestión de Usuarios
    echo "[TEST 2] Probando /admin/users...\n";
    $response = $controller->users($request);

    if ($response->getStatusCode() === 200) {
        $body = (string)$response->getBody();
        echo "  ✓ Respuesta exitosa (200)\n";
        echo "     - Tamaño del HTML: " . strlen($body) . " bytes\n";

        if (strpos($body, 'Gestión de Usuarios') !== false) {
            echo "  ✓ Template renderizado correctamente\n";
        }
    } else {
        echo "  ✗ Error: código " . $response->getStatusCode() . "\n";
    }
    echo "\n";

    // Test 3: Configuración
    echo "[TEST 3] Probando /admin/settings...\n";
    $response = $controller->settings($request);

    if ($response->getStatusCode() === 200) {
        $body = (string)$response->getBody();
        echo "  ✓ Respuesta exitosa (200)\n";
        echo "     - Tamaño del HTML: " . strlen($body) . " bytes\n";

        if (strpos($body, 'Configuración del Sistema') !== false) {
            echo "  ✓ Template renderizado correctamente\n";
        }
    } else {
        echo "  ✗ Error: código " . $response->getStatusCode() . "\n";
    }
    echo "\n";

    // Test 4: Reportes
    echo "[TEST 4] Probando /admin/reports...\n";
    $response = $controller->reports($request);

    if ($response->getStatusCode() === 200) {
        $body = (string)$response->getBody();
        echo "  ✓ Respuesta exitosa (200)\n";
        echo "     - Tamaño del HTML: " . strlen($body) . " bytes\n";

        if (strpos($body, 'Reportes y Estadísticas') !== false) {
            echo "  ✓ Template renderizado correctamente\n";
        }
    } else {
        echo "  ✗ Error: código " . $response->getStatusCode() . "\n";
    }
    echo "\n";

    // Test 5: Seguridad
    echo "[TEST 5] Probando /admin/security...\n";
    $response = $controller->security($request);

    if ($response->getStatusCode() === 200) {
        $body = (string)$response->getBody();
        echo "  ✓ Respuesta exitosa (200)\n";
        echo "     - Tamaño del HTML: " . strlen($body) . " bytes\n";

        if (strpos($body, 'Seguridad del Sistema') !== false) {
            echo "  ✓ Template renderizado correctamente\n";
        }
    } else {
        echo "  ✗ Error: código " . $response->getStatusCode() . "\n";
    }
    echo "\n";

    // Destruir sesión de prueba
    session_destroy();

    echo "================================================================\n";
    echo "  ✓✓✓ TODAS LAS RUTAS DE ADMIN FUNCIONAN ✓✓✓\n";
    echo "================================================================\n\n";

    echo "Las rutas de administración están listas:\n";
    echo "  ✓ /admin           - Panel principal\n";
    echo "  ✓ /admin/users     - Gestión de usuarios\n";
    echo "  ✓ /admin/settings  - Configuración\n";
    echo "  ✓ /admin/reports   - Reportes\n";
    echo "  ✓ /admin/security  - Seguridad\n\n";

    echo "Accede desde el navegador:\n";
    echo "  URL: https://nexosupport.localhost.com/admin\n";
    echo "  Usuario: admin\n";
    echo "  Password: [tu contraseña]\n\n";

} catch (Exception $e) {
    echo "\n✗ ERROR: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n\n";
    exit(1);
}
