<?php
/**
 * Role Management Interface
 *
 * @package NexoSupport
 */

defined('NEXOSUPPORT_INTERNAL') || die();

require_login();
require_capability('nexosupport/admin:manageroles');

global $USER;

use core\rbac\role;
use core\rbac\context;

// Get all roles
$roles = role::get_all();
$syscontext = context::system();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Roles - NexoSupport</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }

        h1 {
            color: #333;
        }

        .nav {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
        }

        .nav a {
            margin-right: 20px;
            color: #667eea;
            text-decoration: none;
        }

        .roles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .role-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .role-card h3 {
            margin-top: 0;
            color: #667eea;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }

        .role-card .shortname {
            color: #666;
            font-size: 14px;
            margin-top: -10px;
            margin-bottom: 15px;
        }

        .role-card .description {
            color: #555;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .capability-list {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 6px;
            max-height: 300px;
            overflow-y: auto;
        }

        .capability-item {
            padding: 8px;
            border-bottom: 1px solid #e0e0e0;
            font-size: 13px;
            font-family: monospace;
        }

        .capability-item:last-child {
            border-bottom: none;
        }

        .capability-item .permission {
            float: right;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
        }

        .permission-allow {
            background: #4caf50;
            color: white;
        }

        .permission-prevent {
            background: #f44336;
            color: white;
        }

        .user-count {
            background: #e3f2fd;
            padding: 10px;
            border-radius: 6px;
            margin-top: 15px;
            text-align: center;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin-right: 10px;
        }

        .btn:hover {
            background: #5568d3;
        }
    </style>
</head>
<body>
    <div class="nav">
        <a href="/">Inicio</a>
        <a href="/admin">Administración</a>
        <a href="/admin/roles">Roles</a>
        <a href="/admin/users">Usuarios</a>
        <a href="/user/profile">Mi Perfil</a>
        <a href="/logout">Cerrar sesión</a>
    </div>

    <h1>Gestión de Roles y Permisos</h1>
    <p>Usuario: <?php echo htmlspecialchars($USER->firstname . ' ' . $USER->lastname); ?></p>

    <?php if (has_capability('nexosupport/admin:manageroles')): ?>
    <div style="margin-bottom: 20px;">
        <a href="/admin/roles/edit?id=0" class="btn">Crear Nuevo Rol</a>
    </div>
    <?php endif; ?>

    <div class="roles-grid">
        <?php foreach ($roles as $role): ?>
            <div class="role-card">
                <h3><?php echo htmlspecialchars($role->name); ?></h3>
                <div class="shortname">Código: <?php echo htmlspecialchars($role->shortname); ?></div>

                <?php if ($role->description): ?>
                <div class="description">
                    <?php echo htmlspecialchars($role->description); ?>
                </div>
                <?php endif; ?>

                <h4>Capabilities:</h4>
                <div class="capability-list">
                    <?php
                    $capabilities = $role->get_capabilities($syscontext);
                    if (empty($capabilities)):
                    ?>
                        <p style="color: #999; text-align: center; margin: 10px 0;">Sin capabilities asignadas</p>
                    <?php else: ?>
                        <?php foreach ($capabilities as $capname => $permission): ?>
                            <div class="capability-item">
                                <?php echo htmlspecialchars($capname); ?>
                                <span class="permission permission-<?php echo $permission == 1 ? 'allow' : 'prevent'; ?>">
                                    <?php echo $permission == 1 ? 'PERMITIR' : 'DENEGAR'; ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <?php
                $users = $role->get_users($syscontext);
                $usercount = count($users);
                ?>
                <div class="user-count">
                    <strong><?php echo $usercount; ?></strong> usuario(s) con este rol
                </div>

                <?php if (has_capability('nexosupport/admin:manageroles')): ?>
                <div style="margin-top: 15px;">
                    <a href="/admin/roles/edit?id=<?php echo $role->id; ?>" class="btn">Editar Rol</a>
                    <a href="/admin/roles/define?roleid=<?php echo $role->id; ?>" class="btn">Capabilities</a>
                    <a href="/admin/roles/assign?roleid=<?php echo $role->id; ?>" class="btn">Ver Usuarios</a>
                </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if (empty($roles)): ?>
        <div style="background: white; padding: 40px; text-align: center; border-radius: 8px;">
            <p style="font-size: 18px; color: #999;">No hay roles definidos en el sistema</p>
            <a href="/admin/roles/edit?id=0" class="btn">Crear Primer Rol</a>
        </div>
    <?php endif; ?>
</body>
</html>
