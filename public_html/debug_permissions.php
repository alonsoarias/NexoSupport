<?php
/**
 * Script de diagnóstico web: Verificar permisos
 * Acceder desde: http://nexosupport.localhost.com/debug_permissions.php
 */

define('BASE_DIR', dirname(__DIR__));
require_once BASE_DIR . '/vendor/autoload.php';

// Iniciar sesión
session_start();

use ISER\Core\Bootstrap;

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico de Permisos</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: #2d2d2d;
            padding: 30px;
            border-radius: 8px;
        }
        h1 { color: #4ec9b0; margin-bottom: 30px; }
        h2 { color: #569cd6; margin-top: 30px; }
        .success { color: #4ec9b0; }
        .error { color: #f48771; }
        .warning { color: #dcdcaa; }
        .info { color: #9cdcfe; }
        pre {
            background: #1e1e1e;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #404040;
        }
        th {
            background: #1e1e1e;
            color: #4ec9b0;
        }
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 0.85em;
            margin-right: 5px;
        }
        .badge-module {
            background: #569cd6;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Diagnóstico del Módulo de Permisos</h1>

        <?php
        try {
            // Inicializar aplicación
            $app = new Bootstrap(BASE_DIR);
            $app->init();
            $database = $app->getDatabase();

            echo '<p class="success">✓ Aplicación inicializada correctamente</p>';

            // Obtener configuración de BD
            $connection = $database->getConnection();
            $prefix = $connection->getPrefix();

            echo '<h2>1. Configuración de Base de Datos</h2>';
            echo '<pre>';
            echo 'Prefix: ' . $prefix . "\n";
            echo '</pre>';

            // Verificar tabla permissions
            echo '<h2>2. Verificación de Tabla</h2>';

            $stmt = $connection->getConnection()->query("SHOW TABLES LIKE '{$prefix}permissions'");
            $tableExists = $stmt->fetch();

            if (!$tableExists) {
                echo '<p class="error">❌ ERROR: La tabla ' . $prefix . 'permissions NO EXISTE</p>';

                echo '<p class="warning">Tablas disponibles:</p>';
                echo '<pre>';
                $tables = $connection->getConnection()->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
                foreach ($tables as $table) {
                    echo "  - $table\n";
                }
                echo '</pre>';
                exit;
            }

            echo '<p class="success">✓ Tabla ' . $prefix . 'permissions existe</p>';

            // Contar permisos
            echo '<h2>3. Contenido de la Tabla</h2>';

            $stmt = $connection->getConnection()->query("SELECT COUNT(*) as total FROM {$prefix}permissions");
            $count = $stmt->fetch();
            $total = $count['total'];

            echo '<p class="info">Total de permisos en BD: <strong>' . $total . '</strong></p>';

            if ($total == 0) {
                echo '<p class="error">❌ ERROR: No hay permisos en la base de datos</p>';
                echo '<p class="warning">⚠️  Los permisos deberían haberse insertado durante la instalación del schema.</p>';
                exit;
            }

            // Permisos por módulo
            echo '<h2>4. Distribución por Módulos</h2>';
            $stmt = $connection->getConnection()->query(
                "SELECT module, COUNT(*) as count
                 FROM {$prefix}permissions
                 GROUP BY module
                 ORDER BY module"
            );
            $modules = $stmt->fetchAll();

            echo '<table>';
            echo '<thead><tr><th>Módulo</th><th>Cantidad</th></tr></thead>';
            echo '<tbody>';
            $totalPerms = 0;
            foreach ($modules as $module) {
                echo '<tr>';
                echo '<td><span class="badge badge-module">' . htmlspecialchars($module['module']) . '</span></td>';
                echo '<td>' . $module['count'] . '</td>';
                echo '</tr>';
                $totalPerms += $module['count'];
            }
            echo '</tbody>';
            echo '</table>';

            echo '<p class="success">✓ Total verificado: ' . $totalPerms . ' permisos</p>';

            // Todos los permisos
            echo '<h2>5. Lista Completa de Permisos</h2>';
            $stmt = $connection->getConnection()->query(
                "SELECT id, name, slug, module, description
                 FROM {$prefix}permissions
                 ORDER BY module, name"
            );
            $permissions = $stmt->fetchAll();

            echo '<table>';
            echo '<thead><tr><th>ID</th><th>Nombre</th><th>Slug</th><th>Módulo</th><th>Descripción</th></tr></thead>';
            echo '<tbody>';
            foreach ($permissions as $perm) {
                echo '<tr>';
                echo '<td>' . $perm['id'] . '</td>';
                echo '<td>' . htmlspecialchars($perm['name']) . '</td>';
                echo '<td><code>' . htmlspecialchars($perm['slug']) . '</code></td>';
                echo '<td><span class="badge badge-module">' . htmlspecialchars($perm['module']) . '</span></td>';
                echo '<td>' . htmlspecialchars($perm['description'] ?? '-') . '</td>';
                echo '</tr>';
            }
            echo '</tbody>';
            echo '</table>';

            // Probar PermissionManager
            echo '<h2>6. Prueba de PermissionManager</h2>';

            use ISER\Permission\PermissionManager;
            $permManager = new PermissionManager($database);

            $grouped = $permManager->getPermissionsGroupedByModule();

            echo '<p class="info">Módulos encontrados por getPermissionsGroupedByModule(): ' . count($grouped) . '</p>';

            if (empty($grouped)) {
                echo '<p class="error">❌ ERROR: getPermissionsGroupedByModule() retorna array vacío</p>';
            } else {
                echo '<p class="success">✓ getPermissionsGroupedByModule() funciona correctamente</p>';
                echo '<pre>';
                foreach ($grouped as $module => $perms) {
                    echo "$module: " . count($perms) . " permisos\n";
                }
                echo '</pre>';
            }

            // Probar PermissionController
            echo '<h2>7. Prueba de PermissionController</h2>';

            use ISER\Controllers\PermissionController;
            use ISER\Core\Http\Request;

            $controller = new PermissionController($database);
            $request = Request::createFromGlobals();

            try {
                $response = $controller->index($request);
                echo '<p class="success">✓ PermissionController->index() ejecutado sin errores</p>';

                $body = (string)$response->getBody();
                if (strpos($body, 'permissions_grouped') !== false || strpos($body, 'Gestión de Permisos') !== false) {
                    echo '<p class="success">✓ La respuesta contiene datos de permisos</p>';
                } else {
                    echo '<p class="warning">⚠️  La respuesta no parece contener datos de permisos</p>';
                }
            } catch (Exception $e) {
                echo '<p class="error">❌ ERROR en PermissionController: ' . htmlspecialchars($e->getMessage()) . '</p>';
                echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
            }

            echo '<h2>✅ Diagnóstico Completado</h2>';
            echo '<p class="success">Si ves este mensaje, la base de datos y el módulo de permisos están funcionando correctamente.</p>';
            echo '<p class="info">Si /admin/permissions no muestra permisos, el problema está en:</p>';
            echo '<ul>';
            echo '<li>Autenticación/Autorización</li>';
            echo '<li>Renderizado de la vista Mustache</li>';
            echo '<li>CSS que oculta elementos</li>';
            echo '<li>JavaScript que interfiere</li>';
            echo '</ul>';

        } catch (Exception $e) {
            echo '<h2 class="error">❌ Error Fatal</h2>';
            echo '<p class="error">' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
        }
        ?>

        <hr style="margin: 40px 0; border-color: #404040;">
        <p style="text-align: center; color: #808080;">
            <small>
                Ejecutado: <?php echo date('Y-m-d H:i:s'); ?><br>
                PHP Version: <?php echo PHP_VERSION; ?>
            </small>
        </p>
    </div>
</body>
</html>
