<?php
/**
 * Assign Roles to Users
 *
 * @package NexoSupport
 */

defined('NEXOSUPPORT_INTERNAL') || die();

require_login();
require_capability('nexosupport/admin:assignroles');

global $USER, $DB;

$userid = optional_param('userid', 0, 'int');
$roleid = optional_param('roleid', 0, 'int');
$action = optional_param('action', '', 'text');

$errors = [];
$success = null;

// Si se especifica userid, mostrar roles del usuario
if ($userid > 0) {
    $user = \core\user\manager::get_user($userid);
    if (!$user) {
        redirect('/admin/users', 'Usuario no encontrado');
    }

    $syscontext = \core\rbac\context::system();

    // Obtener roles asignados al usuario
    $userroles = \core\rbac\access::get_user_roles($userid, $syscontext);

    // Obtener todos los roles disponibles
    $allroles = \core\rbac\role::get_all();

    // Procesar asignación/desasignación
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        require_sesskey();

        $assignroleid = required_param('assignroleid', 'int');
        $assignaction = required_param('assignaction', 'text');

        try {
            if ($assignaction === 'assign') {
                $role = \core\rbac\role::get_by_id($assignroleid);
                if ($role) {
                    \core\rbac\access::assign_role($assignroleid, $userid, $syscontext);
                    $success = "Rol '{$role->name}' asignado exitosamente";
                    // Recargar roles
                    $userroles = \core\rbac\access::get_user_roles($userid, $syscontext);
                }
            } elseif ($assignaction === 'unassign') {
                $role = \core\rbac\role::get_by_id($assignroleid);
                if ($role) {
                    \core\rbac\access::unassign_role($assignroleid, $userid, $syscontext);
                    $success = "Rol '{$role->name}' removido exitosamente";
                    // Recargar roles
                    $userroles = \core\rbac\access::get_user_roles($userid, $syscontext);
                }
            }
        } catch (\Exception $e) {
            $errors[] = 'Error: ' . $e->getMessage();
        }
    }

    // IDs de roles ya asignados
    $assignedRoleIds = array_column($userroles, 'id');

} elseif ($roleid > 0) {
    // Si se especifica roleid, mostrar usuarios con ese rol
    $role = \core\rbac\role::get_by_id($roleid);
    if (!$role) {
        redirect('/admin/roles', 'Rol no encontrado');
    }

    $syscontext = \core\rbac\context::system();
    $roleusers = $role->get_users($syscontext);

} else {
    redirect('/admin/users', 'Debe especificar un usuario o rol');
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asignar Roles - NexoSupport</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
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

        .card {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        h1, h2 {
            margin-top: 0;
            color: #333;
        }

        .user-info, .role-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        th {
            background: #f8f9fa;
            font-weight: 600;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .btn {
            padding: 6px 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-size: 13px;
            display: inline-block;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn-sm {
            padding: 4px 10px;
            font-size: 12px;
        }

        .btn-secondary {
            background: #6c757d;
        }

        .btn-danger {
            background: #dc3545;
        }

        .btn-success {
            background: #28a745;
        }

        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .roles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .role-card {
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            padding: 15px;
            transition: all 0.2s;
        }

        .role-card:hover {
            border-color: #667eea;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.2);
        }

        .role-card.assigned {
            background: #d4edda;
            border-color: #28a745;
        }

        .role-card h3 {
            margin: 0 0 10px 0;
            font-size: 16px;
        }

        .role-card p {
            margin: 0 0 15px 0;
            font-size: 13px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="nav">
        <a href="/">Inicio</a>
        <a href="/admin">Administración</a>
        <a href="/admin/users">Usuarios</a>
        <a href="/admin/roles">Roles</a>
        <a href="/logout">Cerrar sesión</a>
    </div>

    <?php if ($userid > 0): ?>
        <!-- Vista por usuario -->
        <div class="card">
            <h1>Asignar Roles al Usuario</h1>

            <div class="user-info">
                <strong>Usuario:</strong> <?php echo htmlspecialchars($user->username); ?><br>
                <strong>Nombre:</strong> <?php echo htmlspecialchars($user->firstname . ' ' . $user->lastname); ?><br>
                <strong>Email:</strong> <?php echo htmlspecialchars($user->email); ?>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul style="margin: 0; padding-left: 20px;">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <h2>Roles Disponibles</h2>

            <div class="roles-grid">
                <?php foreach ($allroles as $role): ?>
                    <?php $isAssigned = in_array($role->id, $assignedRoleIds); ?>
                    <div class="role-card <?php echo $isAssigned ? 'assigned' : ''; ?>">
                        <h3><?php echo htmlspecialchars($role->name); ?></h3>
                        <p><?php echo htmlspecialchars($role->description); ?></p>

                        <?php if ($isAssigned): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">
                                <input type="hidden" name="assignroleid" value="<?php echo $role->id; ?>">
                                <input type="hidden" name="assignaction" value="unassign">
                                <button type="submit" class="btn btn-sm btn-danger">Remover Rol</button>
                            </form>
                        <?php else: ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">
                                <input type="hidden" name="assignroleid" value="<?php echo $role->id; ?>">
                                <input type="hidden" name="assignaction" value="assign">
                                <button type="submit" class="btn btn-sm btn-success">Asignar Rol</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    <?php elseif ($roleid > 0): ?>
        <!-- Vista por rol -->
        <div class="card">
            <h1>Usuarios con el Rol</h1>

            <div class="role-info">
                <strong>Rol:</strong> <?php echo htmlspecialchars($role->name); ?><br>
                <strong>Shortname:</strong> <?php echo htmlspecialchars($role->shortname); ?><br>
                <?php if ($role->description): ?>
                    <strong>Descripción:</strong> <?php echo htmlspecialchars($role->description); ?>
                <?php endif; ?>
            </div>

            <?php if (empty($roleusers)): ?>
                <div class="empty-state">
                    <h3>No hay usuarios con este rol</h3>
                    <p>Este rol no ha sido asignado a ningún usuario aún.</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($roleusers as $roleuser): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($roleuser->username); ?></td>
                                <td><?php echo htmlspecialchars($roleuser->firstname . ' ' . $roleuser->lastname); ?></td>
                                <td><?php echo htmlspecialchars($roleuser->email); ?></td>
                                <td>
                                    <a href="/admin/user/edit?id=<?php echo $roleuser->id; ?>" class="btn btn-sm btn-secondary">
                                        Ver Usuario
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</body>
</html>
