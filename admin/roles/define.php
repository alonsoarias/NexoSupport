<?php
/**
 * Define Role Capabilities
 *
 * @package NexoSupport
 */

defined('NEXOSUPPORT_INTERNAL') || die();

require_login();
require_capability('nexosupport/admin:manageroles');

global $USER, $DB;

$roleid = required_param('roleid', 'int');

$role = \core\rbac\role::get_by_id($roleid);
if (!$role) {
    redirect('/admin/roles', 'Rol no encontrado');
}

$errors = [];
$success = null;

// Obtener todas las capabilities del sistema
$allcaps = $DB->get_records('capabilities', [], 'component, name');

// Obtener capabilities actuales del rol
$syscontext = \core\rbac\context::system();
$rolecaps = $role->get_capabilities($syscontext);

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();

    $updated = 0;

    foreach ($allcaps as $cap) {
        $permission = optional_param('cap_' . str_replace('/', '_', $cap->name), null, 'int');

        if ($permission !== null) {
            $currentperm = $rolecaps[$cap->name] ?? 0;

            if ($permission == 0 && isset($rolecaps[$cap->name])) {
                // Remover capability
                $role->remove_capability($cap->name, $syscontext);
                $updated++;
            } elseif ($permission != 0 && $permission != $currentperm) {
                // Asignar o actualizar capability
                $role->assign_capability($cap->name, $permission, $syscontext);
                $updated++;
            }
        }
    }

    if ($updated > 0) {
        $success = "Se actualizaron $updated capabilities";
        // Recargar capabilities
        $rolecaps = $role->get_capabilities($syscontext);
    } else {
        $success = "No se realizaron cambios";
    }
}

// Agrupar capabilities por componente
$capsByComponent = [];
foreach ($allcaps as $cap) {
    if (!isset($capsByComponent[$cap->component])) {
        $capsByComponent[$cap->component] = [];
    }
    $capsByComponent[$cap->component][] = $cap;
}

// Constantes de permisos
define('CAP_INHERIT', 0);
define('CAP_ALLOW', 1);
define('CAP_PREVENT', -1);
define('CAP_PROHIBIT', -1000);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Definir Capabilities - <?php echo htmlspecialchars($role->name); ?> - NexoSupport</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            max-width: 1200px;
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

        .role-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .component-section {
            margin-bottom: 30px;
        }

        .component-title {
            background: #667eea;
            color: white;
            padding: 10px 15px;
            border-radius: 6px 6px 0 0;
            font-weight: 600;
            cursor: pointer;
            user-select: none;
        }

        .component-title:hover {
            background: #5568d3;
        }

        .capability-list {
            border: 1px solid #e0e0e0;
            border-top: none;
            border-radius: 0 0 6px 6px;
        }

        .capability-row {
            padding: 12px 15px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .capability-row:last-child {
            border-bottom: none;
        }

        .capability-row:hover {
            background: #f8f9fa;
        }

        .capability-name {
            flex: 1;
            font-family: 'Courier New', monospace;
            font-size: 13px;
        }

        .capability-type {
            padding: 2px 8px;
            background: #e9ecef;
            border-radius: 4px;
            font-size: 11px;
            margin-right: 15px;
            color: #666;
        }

        .permission-selector {
            display: flex;
            gap: 5px;
        }

        .permission-btn {
            padding: 6px 12px;
            border: 2px solid #e0e0e0;
            background: white;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.2s;
        }

        .permission-btn:hover {
            border-color: #667eea;
        }

        .permission-btn.active-inherit {
            background: #6c757d;
            color: white;
            border-color: #6c757d;
        }

        .permission-btn.active-allow {
            background: #28a745;
            color: white;
            border-color: #28a745;
        }

        .permission-btn.active-prevent {
            background: #ffc107;
            color: #000;
            border-color: #ffc107;
        }

        .permission-btn.active-prohibit {
            background: #dc3545;
            color: white;
            border-color: #dc3545;
        }

        .form-actions {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            position: sticky;
            bottom: 0;
            background: white;
            z-index: 100;
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
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #6c757d;
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

        .legend {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
        }

        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="nav">
        <a href="/">Inicio</a>
        <a href="/admin">Administración</a>
        <a href="/admin/roles">Roles</a>
        <a href="/admin/roles/edit?id=<?php echo $roleid; ?>">Editar Rol</a>
        <a href="/logout">Cerrar sesión</a>
    </div>

    <div class="card">
        <h1>Definir Capabilities del Rol</h1>

        <div class="role-info">
            <strong>Rol:</strong> <?php echo htmlspecialchars($role->name); ?> (<?php echo htmlspecialchars($role->shortname); ?>)<br>
            <?php if ($role->description): ?>
                <strong>Descripción:</strong> <?php echo htmlspecialchars($role->description); ?>
            <?php endif; ?>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <div class="legend">
            <div class="legend-item">
                <div class="legend-color" style="background: #6c757d;"></div>
                <span><strong>Heredar (0):</strong> Hereda del contexto padre</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: #28a745;"></div>
                <span><strong>Permitir (1):</strong> Permite la acción</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: #ffc107;"></div>
                <span><strong>Prevenir (-1):</strong> Niega la acción (puede sobreescribirse)</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: #dc3545;"></div>
                <span><strong>Prohibir (-1000):</strong> Prohibe permanentemente</span>
            </div>
        </div>

        <form method="POST" id="capsForm">
            <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">

            <?php foreach ($capsByComponent as $component => $caps): ?>
                <div class="component-section">
                    <div class="component-title" onclick="toggleComponent('<?php echo $component; ?>')">
                        <?php echo htmlspecialchars($component); ?> (<?php echo count($caps); ?> capabilities)
                    </div>
                    <div class="capability-list" id="component-<?php echo $component; ?>">
                        <?php foreach ($caps as $cap): ?>
                            <?php
                            $currentPerm = $rolecaps[$cap->name] ?? CAP_INHERIT;
                            $fieldName = 'cap_' . str_replace('/', '_', $cap->name);
                            ?>
                            <div class="capability-row">
                                <div class="capability-name"><?php echo htmlspecialchars($cap->name); ?></div>
                                <div class="capability-type"><?php echo htmlspecialchars($cap->captype); ?></div>
                                <div class="permission-selector">
                                    <button type="button"
                                            class="permission-btn <?php echo $currentPerm == CAP_INHERIT ? 'active-inherit' : ''; ?>"
                                            onclick="setPerm('<?php echo $fieldName; ?>', <?php echo CAP_INHERIT; ?>)">
                                        Heredar
                                    </button>
                                    <button type="button"
                                            class="permission-btn <?php echo $currentPerm == CAP_ALLOW ? 'active-allow' : ''; ?>"
                                            onclick="setPerm('<?php echo $fieldName; ?>', <?php echo CAP_ALLOW; ?>)">
                                        Permitir
                                    </button>
                                    <button type="button"
                                            class="permission-btn <?php echo $currentPerm == CAP_PREVENT ? 'active-prevent' : ''; ?>"
                                            onclick="setPerm('<?php echo $fieldName; ?>', <?php echo CAP_PREVENT; ?>)">
                                        Prevenir
                                    </button>
                                    <button type="button"
                                            class="permission-btn <?php echo $currentPerm == CAP_PROHIBIT ? 'active-prohibit' : ''; ?>"
                                            onclick="setPerm('<?php echo $fieldName; ?>', <?php echo CAP_PROHIBIT; ?>)">
                                        Prohibir
                                    </button>
                                    <input type="hidden" name="<?php echo $fieldName; ?>" id="<?php echo $fieldName; ?>" value="<?php echo $currentPerm; ?>">
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="form-actions">
                <button type="submit" class="btn">Guardar Capabilities</button>
                <a href="/admin/roles" class="btn btn-secondary">Volver a Roles</a>
                <a href="/admin/roles/edit?id=<?php echo $roleid; ?>" class="btn btn-secondary">Editar Rol</a>
            </div>
        </form>
    </div>

    <script>
        function setPerm(fieldName, value) {
            // Actualizar campo hidden
            document.getElementById(fieldName).value = value;

            // Obtener todos los botones de este capability
            const row = document.getElementById(fieldName).closest('.capability-row');
            const buttons = row.querySelectorAll('.permission-btn');

            // Remover todas las clases active
            buttons.forEach(btn => {
                btn.classList.remove('active-inherit', 'active-allow', 'active-prevent', 'active-prohibit');
            });

            // Agregar clase active al botón clickeado
            const button = event.target;
            if (value == <?php echo CAP_INHERIT; ?>) {
                button.classList.add('active-inherit');
            } else if (value == <?php echo CAP_ALLOW; ?>) {
                button.classList.add('active-allow');
            } else if (value == <?php echo CAP_PREVENT; ?>) {
                button.classList.add('active-prevent');
            } else if (value == <?php echo CAP_PROHIBIT; ?>) {
                button.classList.add('active-prohibit');
            }
        }

        function toggleComponent(component) {
            const element = document.getElementById('component-' + component);
            if (element.style.display === 'none') {
                element.style.display = 'block';
            } else {
                element.style.display = 'none';
            }
        }
    </script>
</body>
</html>
