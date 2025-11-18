<?php
/**
 * Role Edit Form
 *
 * @package NexoSupport
 */

defined('NEXOSUPPORT_INTERNAL') || die();

require_login();
require_capability('nexosupport/admin:manageroles');

global $USER;

$roleid = optional_param('id', 0, 'int');
$action = optional_param('action', '', 'text');
$isNew = $roleid === 0;

$errors = [];
$success = null;

// Cargar rol existente
if (!$isNew) {
    $role = \core\rbac\role::get_by_id($roleid);
    if (!$role) {
        redirect('/admin/roles', 'Rol no encontrado');
    }
} else {
    $role = new stdClass();
    $role->id = 0;
    $role->name = '';
    $role->shortname = '';
    $role->description = '';
    $role->archetype = 0;
}

// Manejar eliminación
if ($action === 'delete' && !$isNew) {
    require_sesskey();

    // Verificar que no sea un rol del sistema
    if (in_array($role->shortname, ['administrator', 'manager', 'user'])) {
        $errors[] = 'No se puede eliminar un rol del sistema';
    } else {
        try {
            \core\rbac\role::delete($roleid);
            redirect('/admin/roles', 'Rol eliminado exitosamente');
        } catch (\Exception $e) {
            $errors[] = 'Error al eliminar el rol: ' . $e->getMessage();
        }
    }
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action !== 'delete') {
    require_sesskey();

    $data = new stdClass();
    $data->name = required_param('name', 'text');
    $data->shortname = required_param('shortname', 'alphanumext');
    $data->description = optional_param('description', '', 'text');
    $data->archetype = optional_param('archetype', 0, 'int');

    if (empty($errors)) {
        try {
            if ($isNew) {
                // Crear rol
                $newrole = \core\rbac\role::create(
                    $data->shortname,
                    $data->name,
                    $data->description,
                    $data->archetype
                );
                $success = 'Rol creado exitosamente';
                redirect('/admin/roles/define?roleid=' . $newrole->id, $success);
            } else {
                // Actualizar rol
                $role->name = $data->name;
                $role->description = $data->description;
                $role->archetype = $data->archetype;

                if ($role->shortname !== $data->shortname) {
                    // Solo permitir cambiar shortname si no es rol del sistema
                    if (!in_array($role->shortname, ['administrator', 'manager', 'user'])) {
                        $role->shortname = $data->shortname;
                    } else {
                        $errors[] = 'No se puede cambiar el nombre corto de un rol del sistema';
                    }
                }

                if (empty($errors)) {
                    \core\rbac\role::update($role);
                    $success = 'Rol actualizado exitosamente';
                    $role = \core\rbac\role::get_by_id($roleid);
                }
            }
        } catch (\coding_exception $e) {
            $errors[] = $e->getMessage();
        }
    }
}

$isSystemRole = !$isNew && in_array($role->shortname, ['administrator', 'manager', 'user']);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isNew ? 'Nuevo Rol' : 'Editar Rol'; ?> - NexoSupport</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            max-width: 800px;
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
        }

        h1 {
            margin-top: 0;
            color: #333;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: 500;
        }

        .required::after {
            content: ' *';
            color: #dc3545;
        }

        input[type="text"],
        textarea,
        select {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
            font-family: inherit;
        }

        textarea {
            min-height: 100px;
            resize: vertical;
        }

        input:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn {
            padding: 10px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            margin-right: 10px;
            display: inline-block;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #6c757d;
        }

        .btn-danger {
            background: #dc3545;
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

        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border-left: 4px solid #ffc107;
        }

        .form-actions {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
        }

        .help-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="nav">
        <a href="/">Inicio</a>
        <a href="/admin">Administración</a>
        <a href="/admin/roles">Roles</a>
        <a href="/logout">Cerrar sesión</a>
    </div>

    <div class="card">
        <h1><?php echo $isNew ? 'Nuevo Rol' : 'Editar Rol'; ?></h1>

        <?php if ($isSystemRole): ?>
            <div class="alert alert-warning">
                <strong>Advertencia:</strong> Este es un rol del sistema. Algunos campos no pueden ser modificados.
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <strong>Error:</strong>
                <ul style="margin: 5px 0 0 20px;">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">

            <div class="form-group">
                <label for="name" class="required">Nombre del rol</label>
                <input type="text"
                       id="name"
                       name="name"
                       value="<?php echo htmlspecialchars($role->name); ?>"
                       required>
                <div class="help-text">Nombre descriptivo del rol (ej: "Administrador", "Gerente")</div>
            </div>

            <div class="form-group">
                <label for="shortname" class="required">Nombre corto</label>
                <input type="text"
                       id="shortname"
                       name="shortname"
                       value="<?php echo htmlspecialchars($role->shortname); ?>"
                       required
                       <?php echo $isSystemRole ? 'readonly' : ''; ?>>
                <div class="help-text">
                    Identificador único del rol (solo letras, números y guiones bajos)
                    <?php if ($isSystemRole): ?>
                        <br><strong>Este campo no se puede modificar en roles del sistema</strong>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-group">
                <label for="description">Descripción</label>
                <textarea id="description"
                          name="description"><?php echo htmlspecialchars($role->description); ?></textarea>
                <div class="help-text">Descripción opcional del rol y sus responsabilidades</div>
            </div>

            <div class="form-actions">
                <div>
                    <button type="submit" class="btn">
                        <?php echo $isNew ? 'Crear Rol' : 'Guardar Cambios'; ?>
                    </button>
                    <a href="/admin/roles" class="btn btn-secondary">Cancelar</a>

                    <?php if (!$isNew): ?>
                        <a href="/admin/roles/define?roleid=<?php echo $roleid; ?>" class="btn btn-secondary">
                            Definir Capabilities
                        </a>
                    <?php endif; ?>
                </div>

                <?php if (!$isNew && !$isSystemRole): ?>
                    <form method="POST" style="display: inline;" onsubmit="return confirm('¿Está seguro de eliminar este rol?');">
                        <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">
                        <input type="hidden" name="action" value="delete">
                        <button type="submit" class="btn btn-danger">Eliminar Rol</button>
                    </form>
                <?php endif; ?>
            </div>
        </form>
    </div>
</body>
</html>
