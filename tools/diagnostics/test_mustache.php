<?php
/**
 * Test de Mustache con datos de permisos
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');

define('BASE_DIR', dirname(__DIR__));
require_once BASE_DIR . '/vendor/autoload.php';

use ISER\Core\Bootstrap;
use ISER\Permission\PermissionManager;

session_start();

header('Content-Type: text/html; charset=UTF-8');

try {
    $app = new Bootstrap(BASE_DIR);
    $app->init();
    $database = $app->getDatabase();

    $permManager = new PermissionManager($database);
    $grouped = $permManager->getPermissionsGroupedByModule();

    echo "<h1>Test Mustache - Permisos Agrupados</h1>";
    echo "<h2>Datos PHP (var_dump):</h2>";
    echo "<pre>";
    var_dump($grouped);
    echo "</pre>";

    echo "<h2>Estructura del array:</h2>";
    echo "<ul>";
    foreach ($grouped as $module => $perms) {
        echo "<li><strong>$module</strong>: " . count($perms) . " permisos";
        echo "<ul>";
        foreach ($perms as $perm) {
            echo "<li>{$perm['name']} ({$perm['slug']})</li>";
        }
        echo "</ul>";
        echo "</li>";
    }
    echo "</ul>";

    // Problema: Mustache no puede iterar sobre arrays asociativos con {{#permissions_grouped}}
    // Necesitamos convertirlo a array indexado con la clave como propiedad

    echo "<h2>PROBLEMA IDENTIFICADO:</h2>";
    echo "<p style='color:red;font-weight:bold'>Mustache {{#array}} solo itera sobre arrays INDEXADOS, no ASOCIATIVOS</p>";
    echo "<p>El array \$grouped es asociativo: ['audit' => [...], 'users' => [...]]</p>";
    echo "<p>Mustache necesita: [['module' => 'audit', 'permissions' => [...]], ['module' => 'users', 'permissions' => [...]]]</p>";

    echo "<h2>Conversión necesaria:</h2>";
    $groupedFixed = [];
    foreach ($grouped as $module => $permissions) {
        $groupedFixed[] = [
            'module_name' => $module,
            'permissions' => $permissions,
            'permission_count' => count($permissions)
        ];
    }

    echo "<pre>";
    print_r($groupedFixed);
    echo "</pre>";

    echo "<h2>SOLUCIÓN:</h2>";
    echo "<p>El PermissionController debe transformar el array antes de pasarlo a Mustache</p>";
    echo "<pre>";
    echo htmlspecialchars('
$grouped = $this->permissionManager->getPermissionsGroupedByModule();
$groupedForMustache = [];
foreach ($grouped as $module => $permissions) {
    $groupedForMustache[] = [
        \'module_name\' => $module,
        \'permissions\' => $permissions,
        \'permission_count\' => count($permissions)
    ];
}

$data = [
    \'permissions_grouped\' => $groupedForMustache,
    ...
];
');
    echo "</pre>";

} catch (Exception $e) {
    echo "<h2 style='color:red'>ERROR</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
