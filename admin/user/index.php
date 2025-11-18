<?php
/**
 * User Management Interface
 *
 * @package NexoSupport
 */

defined('NEXOSUPPORT_INTERNAL') || die();

require_login();
require_capability('nexosupport/admin:manageusers');

global $USER;

// Obtener usuarios
$search = optional_param('search', '', 'text');
$page = optional_param('page', 0, 'int');
$perpage = 25;

$users = $search
    ? \core\user\manager::search_users($search, $page * $perpage, $perpage)
    : \core\user\manager::get_all_users(false, $page * $perpage, $perpage);

$totalusers = \core\user\manager::count_users();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - NexoSupport</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
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

        .actions-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 20px 0;
        }

        .search-box {
            display: flex;
            gap: 10px;
        }

        .search-box input {
            padding: 8px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            min-width: 300px;
        }

        .btn {
            padding: 8px 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            display: inline-block;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #6c757d;
        }

        .btn-sm {
            padding: 4px 12px;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }

        thead {
            background: #f8f9fa;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        th {
            font-weight: 600;
            color: #333;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge-success {
            background: #d4edda;
            color: #155724;
        }

        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="nav">
        <a href="/">Inicio</a>
        <a href="/admin">Administración</a>
        <a href="/admin/users">Usuarios</a>
        <a href="/logout">Cerrar sesión</a>
    </div>

    <h1>Gestión de Usuarios</h1>
    <p>Total de usuarios: <?php echo $totalusers; ?></p>

    <div class="actions-bar">
        <form method="GET" class="search-box">
            <input type="text" name="search" placeholder="Buscar usuarios..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="btn">Buscar</button>
            <?php if ($search): ?>
                <a href="/admin/users" class="btn btn-secondary">Limpiar</a>
            <?php endif; ?>
        </form>

        <a href="/admin/user/edit?id=0" class="btn">+ Nuevo Usuario</a>
    </div>

    <?php if (empty($users)): ?>
        <div class="empty-state">
            <h3>No se encontraron usuarios</h3>
            <p>No hay usuarios que mostrar con los criterios seleccionados.</p>
        </div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Usuario</th>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Estado</th>
                    <th>Último acceso</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo $user->id; ?></td>
                        <td><?php echo htmlspecialchars($user->username); ?></td>
                        <td><?php echo htmlspecialchars($user->firstname . ' ' . $user->lastname); ?></td>
                        <td><?php echo htmlspecialchars($user->email); ?></td>
                        <td>
                            <?php if ($user->suspended): ?>
                                <span class="badge badge-warning">Suspendido</span>
                            <?php else: ?>
                                <span class="badge badge-success">Activo</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            if ($user->lastlogin) {
                                echo date('d/m/Y H:i', $user->lastlogin);
                            } else {
                                echo 'Nunca';
                            }
                            ?>
                        </td>
                        <td>
                            <a href="/admin/user/edit?id=<?php echo $user->id; ?>" class="btn btn-sm">Editar</a>
                            <a href="/admin/roles/assign?userid=<?php echo $user->id; ?>" class="btn btn-sm btn-secondary">Roles</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</body>
</html>
