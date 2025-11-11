<?php
/**
 * Test simple de permisos
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');

define('BASE_DIR', dirname(__DIR__));
require_once BASE_DIR . '/vendor/autoload.php';

session_start();

header('Content-Type: text/plain; charset=UTF-8');

echo "=== TEST DE PERMISOS ===\n\n";

try {
    use ISER\Core\Bootstrap;
    use ISER\Permission\PermissionManager;

    // Inicializar aplicación
    echo "1. Inicializando aplicación...\n";
    $app = new Bootstrap(BASE_DIR);
    $app->init();
    $database = $app->getDatabase();
    echo "   ✓ OK\n\n";

    // Crear PermissionManager
    echo "2. Creando PermissionManager...\n";
    $permManager = new PermissionManager($database);
    echo "   ✓ OK\n\n";

    // Test 1: getPermissions
    echo "3. Test getPermissions()...\n";
    $permissions = $permManager->getPermissions(100, 0);
    echo "   Total obtenido: " . count($permissions) . "\n";
    if (count($permissions) > 0) {
        echo "   Primer permiso: " . $permissions[0]['name'] . " (" . $permissions[0]['slug'] . ")\n";
    }
    echo "\n";

    // Test 2: countPermissions
    echo "4. Test countPermissions()...\n";
    $count = $permManager->countPermissions();
    echo "   Total: $count\n\n";

    // Test 3: getModules
    echo "5. Test getModules()...\n";
    $modules = $permManager->getModules();
    echo "   Módulos encontrados: " . count($modules) . "\n";
    if (count($modules) > 0) {
        echo "   Módulos: " . implode(', ', $modules) . "\n";
    }
    echo "\n";

    // Test 4: getPermissionsGroupedByModule
    echo "6. Test getPermissionsGroupedByModule()...\n";
    $grouped = $permManager->getPermissionsGroupedByModule();
    echo "   Módulos en el resultado: " . count($grouped) . "\n";

    if (count($grouped) == 0) {
        echo "   ❌ ERROR: Array vacío!\n\n";

        // Debug adicional
        echo "7. Debug directo en BD...\n";
        $connection = $database->getConnection();
        $prefix = $connection->getPrefix();

        $sql = "SELECT * FROM {$prefix}permissions ORDER BY module, name";
        echo "   SQL: $sql\n";

        $result = $connection->fetchAll($sql);
        echo "   Resultados: " . count($result) . "\n";

        if (count($result) > 0) {
            echo "\n   Primeros 5 permisos:\n";
            for ($i = 0; $i < min(5, count($result)); $i++) {
                $p = $result[$i];
                echo "   - [{$p['id']}] {$p['name']} ({$p['slug']}) - módulo: {$p['module']}\n";
            }
        }
    } else {
        echo "   ✓ OK - Datos agrupados:\n";
        foreach ($grouped as $module => $perms) {
            echo "   - $module: " . count($perms) . " permisos\n";
        }
    }

    echo "\n=== FIN DEL TEST ===\n";

} catch (Exception $e) {
    echo "\n❌ ERROR FATAL:\n";
    echo "Mensaje: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
}
