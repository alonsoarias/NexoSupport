<?php

/**
 * Script para crear permisos granulares del sistema
 *
 * Ejecutar: php scripts/seed_permissions.php
 */

require_once __DIR__ . '/../bootstrap/app.php';

use ISER\Core\Database\Database;

$db = Database::getInstance();
$conn = $db->getConnection()->getConnection();

// Permisos granulares del sistema
$permissions = [
    // Módulo: users
    [
        'name' => 'Ver Usuarios',
        'slug' => 'users.view',
        'description' => 'Permite ver la lista de usuarios del sistema',
        'module' => 'users'
    ],
    [
        'name' => 'Crear Usuarios',
        'slug' => 'users.create',
        'description' => 'Permite crear nuevos usuarios en el sistema',
        'module' => 'users'
    ],
    [
        'name' => 'Editar Usuarios',
        'slug' => 'users.update',
        'description' => 'Permite editar información de usuarios existentes',
        'module' => 'users'
    ],
    [
        'name' => 'Eliminar Usuarios',
        'slug' => 'users.delete',
        'description' => 'Permite eliminar usuarios del sistema',
        'module' => 'users'
    ],
    [
        'name' => 'Restaurar Usuarios',
        'slug' => 'users.restore',
        'description' => 'Permite restaurar usuarios eliminados',
        'module' => 'users'
    ],

    // Módulo: roles
    [
        'name' => 'Ver Roles',
        'slug' => 'roles.view',
        'description' => 'Permite ver la lista de roles del sistema',
        'module' => 'roles'
    ],
    [
        'name' => 'Crear Roles',
        'slug' => 'roles.create',
        'description' => 'Permite crear nuevos roles en el sistema',
        'module' => 'roles'
    ],
    [
        'name' => 'Editar Roles',
        'slug' => 'roles.update',
        'description' => 'Permite editar roles existentes',
        'module' => 'roles'
    ],
    [
        'name' => 'Eliminar Roles',
        'slug' => 'roles.delete',
        'description' => 'Permite eliminar roles del sistema',
        'module' => 'roles'
    ],
    [
        'name' => 'Asignar Permisos a Roles',
        'slug' => 'roles.assign_permissions',
        'description' => 'Permite asignar o quitar permisos de los roles',
        'module' => 'roles'
    ],

    // Módulo: permissions
    [
        'name' => 'Ver Permisos',
        'slug' => 'permissions.view',
        'description' => 'Permite ver la lista de permisos del sistema',
        'module' => 'permissions'
    ],
    [
        'name' => 'Crear Permisos',
        'slug' => 'permissions.create',
        'description' => 'Permite crear nuevos permisos en el sistema',
        'module' => 'permissions'
    ],
    [
        'name' => 'Editar Permisos',
        'slug' => 'permissions.update',
        'description' => 'Permite editar permisos existentes',
        'module' => 'permissions'
    ],
    [
        'name' => 'Eliminar Permisos',
        'slug' => 'permissions.delete',
        'description' => 'Permite eliminar permisos del sistema',
        'module' => 'permissions'
    ],

    // Módulo: dashboard
    [
        'name' => 'Ver Dashboard',
        'slug' => 'dashboard.view',
        'description' => 'Permite acceder al panel de administración',
        'module' => 'dashboard'
    ],
    [
        'name' => 'Ver Estadísticas',
        'slug' => 'dashboard.stats',
        'description' => 'Permite ver estadísticas del sistema',
        'module' => 'dashboard'
    ],

    // Módulo: settings
    [
        'name' => 'Ver Configuración',
        'slug' => 'settings.view',
        'description' => 'Permite ver la configuración del sistema',
        'module' => 'settings'
    ],
    [
        'name' => 'Editar Configuración',
        'slug' => 'settings.update',
        'description' => 'Permite modificar la configuración del sistema',
        'module' => 'settings'
    ],

    // Módulo: logs
    [
        'name' => 'Ver Logs',
        'slug' => 'logs.view',
        'description' => 'Permite ver los registros de actividad del sistema',
        'module' => 'logs'
    ],
    [
        'name' => 'Eliminar Logs',
        'slug' => 'logs.delete',
        'description' => 'Permite eliminar registros de actividad',
        'module' => 'logs'
    ],

    // Módulo: reports
    [
        'name' => 'Ver Reportes',
        'slug' => 'reports.view',
        'description' => 'Permite ver reportes del sistema',
        'module' => 'reports'
    ],
    [
        'name' => 'Generar Reportes',
        'slug' => 'reports.generate',
        'description' => 'Permite generar nuevos reportes',
        'module' => 'reports'
    ],
    [
        'name' => 'Exportar Reportes',
        'slug' => 'reports.export',
        'description' => 'Permite exportar reportes en diferentes formatos',
        'module' => 'reports'
    ],
];

echo "🔑 Creando permisos granulares del sistema...\n\n";

$tableName = $db->table('permissions');
$created = 0;
$skipped = 0;

foreach ($permissions as $permission) {
    // Verificar si ya existe
    $checkSql = "SELECT id FROM $tableName WHERE slug = :slug";
    $stmt = $conn->prepare($checkSql);
    $stmt->execute([':slug' => $permission['slug']]);

    if ($stmt->fetch()) {
        echo "⏭️  Ya existe: {$permission['name']} ({$permission['slug']})\n";
        $skipped++;
        continue;
    }

    // Insertar permiso
    $insertSql = "INSERT INTO $tableName (name, slug, description, module, created_at)
                  VALUES (:name, :slug, :description, :module, :created_at)";

    $stmt = $conn->prepare($insertSql);
    $result = $stmt->execute([
        ':name' => $permission['name'],
        ':slug' => $permission['slug'],
        ':description' => $permission['description'],
        ':module' => $permission['module'],
        ':created_at' => time()
    ]);

    if ($result) {
        echo "✅ Creado: {$permission['name']} ({$permission['slug']})\n";
        $created++;
    } else {
        echo "❌ Error al crear: {$permission['name']}\n";
    }
}

echo "\n" . str_repeat('=', 60) . "\n";
echo "📊 Resumen:\n";
echo "   ✅ Permisos creados: $created\n";
echo "   ⏭️  Permisos omitidos (ya existían): $skipped\n";
echo "   📦 Total de permisos en el sistema: " . ($created + $skipped) . "\n";
echo str_repeat('=', 60) . "\n\n";

// Asignar todos los permisos al rol de Admin
echo "🔐 Asignando permisos al rol Admin...\n";

$roleTableName = $db->table('roles');
$rolePermTableName = $db->table('role_permissions');

// Obtener el rol Admin
$adminRoleSql = "SELECT id FROM $roleTableName WHERE slug = 'admin' LIMIT 1";
$stmt = $conn->query($adminRoleSql);
$adminRole = $stmt->fetch(\PDO::FETCH_ASSOC);

if ($adminRole) {
    $adminRoleId = $adminRole['id'];

    // Obtener todos los permisos
    $permissionsSql = "SELECT id FROM $tableName";
    $stmt = $conn->query($permissionsSql);
    $allPermissions = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    $assigned = 0;
    foreach ($allPermissions as $perm) {
        // Verificar si ya está asignado
        $checkSql = "SELECT id FROM $rolePermTableName WHERE role_id = :role_id AND permission_id = :permission_id";
        $stmt = $conn->prepare($checkSql);
        $stmt->execute([
            ':role_id' => $adminRoleId,
            ':permission_id' => $perm['id']
        ]);

        if ($stmt->fetch()) {
            continue; // Ya asignado
        }

        // Asignar permiso
        $insertSql = "INSERT INTO $rolePermTableName (role_id, permission_id) VALUES (:role_id, :permission_id)";
        $stmt = $conn->prepare($insertSql);
        $stmt->execute([
            ':role_id' => $adminRoleId,
            ':permission_id' => $perm['id']
        ]);
        $assigned++;
    }

    echo "✅ Asignados $assigned permisos al rol Admin\n\n";
} else {
    echo "⚠️  No se encontró el rol Admin. Los permisos deben ser asignados manualmente.\n\n";
}

echo "✅ Proceso completado exitosamente!\n";
echo "🌐 Ahora puedes crear roles y asignarles permisos granulares en:\n";
echo "   https://nexosupport.localhost.com/admin/roles/create\n\n";
